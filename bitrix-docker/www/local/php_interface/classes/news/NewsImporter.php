<?php

use Bitrix\Main\Loader;

/**
 * Импорт новостей из SQLite в IBLOCK 16 (тип content, SEF /novosti/#ELEMENT_CODE#/).
 */
final class NewsImporter
{
    public const IBLOCK_ID = 16;
    public const SEF_FOLDER = '/novosti/';
    /** Фактическое расположение файлов на сервере */ 
    public const UPLOAD_IMAGES_DIR = '/upload/news_images/';
    /** Устаревший префикс в SQLite/content_html (парсер подставлял /upload/news/) */
    public const LEGACY_UPLOAD_IMAGES_DIR = '/upload/news/';
    public const XML_ID_PREFIX = 'news-import-';

    /** @var callable|null */
    private $logger;

    private string $documentRoot;
    private string $imagesSourceDir;

    public function __construct(string $documentRoot, string $imagesSourceDir, ?callable $logger = null)
    {
        $this->documentRoot = rtrim($documentRoot, '/');
        $this->imagesSourceDir = rtrim($imagesSourceDir, '/');
        $this->logger = $logger;
    }

    /**
     * @param list<array{
     *     id:int,
     *     title:string,
     *     preview_text:string,
     *     preview_picture_upload_path:string,
     *     published_at:string,
     *     content_html:string,
     *     url:string
     * }> $rows
     * @return array{
     *     total:int,
     *     created:int,
     *     updated:int,
     *     skipped:int,
     *     images_found:int,
     *     errors:int,
     *     redirects:list<array{old_path:string,new_path:string,title:string}>,
     *     details:list<string>
     * }
     */
    public function import(array $rows, bool $dryRun = true, bool $skipImages = false): array
    {
        if (!Loader::includeModule('iblock')) {
            throw new \RuntimeException('Module iblock is not available');
        }

        $stats = [
            'total' => count($rows),
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'images_found' => 0,
            'errors' => 0,
            'redirects' => [],
            'details' => [],
        ];

        $usedCodes = $this->loadExistingCodes();

        foreach ($rows as $row) {
            $id = (int)$row['id'];
            $title = trim((string)$row['title']);
            if ($title === '') {
                ++$stats['skipped'];
                continue;
            }

            $code = self::buildElementCode($row, $usedCodes);
            $xmlId = self::XML_ID_PREFIX . $id;
            $activeFrom = self::formatBitrixDateTime((string)$row['published_at']);
            $fields = [
                'IBLOCK_ID' => self::IBLOCK_ID,
                'NAME' => $title,
                'CODE' => $code,
                'XML_ID' => $xmlId,
                'ACTIVE' => 'Y',
                'PREVIEW_TEXT' => (string)$row['preview_text'],
                'PREVIEW_TEXT_TYPE' => 'text',
                'DETAIL_TEXT' => self::normalizeContentHtmlImagePaths((string)$row['content_html']),
                'DETAIL_TEXT_TYPE' => 'html',
            ];

            if ($activeFrom !== null) {
                $fields['ACTIVE_FROM'] = $activeFrom;
                $fields['DATE_ACTIVE_FROM'] = $activeFrom;
            }

            $redirect = self::buildRedirectEntry((string)$row['url'], $code);
            if ($redirect !== null) {
                $stats['redirects'][] = [
                    'old_path' => $redirect['old_path'],
                    'new_path' => $redirect['new_path'],
                    'title' => $title,
                ];
            }

            if (!$skipImages) {
                $picturePath = self::resolvePreviewPicturePath(
                    (string)$row['preview_picture_upload_path'],
                    $this->documentRoot,
                    $this->imagesSourceDir
                );
                if ($picturePath !== null) {
                    ++$stats['images_found'];
                    if (!$dryRun) {
                        $fileArray = \CFile::MakeFileArray($picturePath);
                        if (is_array($fileArray) && !empty($fileArray['tmp_name'])) {
                            $fields['PREVIEW_PICTURE'] = $fileArray;
                        }
                    }
                }
            }

            $existingId = $this->findExistingElementId($xmlId, $code);

            if ($dryRun) {
                if ($existingId > 0) {
                    ++$stats['updated'];
                    $this->log(sprintf(
                        '[dry-run update] id=%d element=%d code=%s title=%s',
                        $id,
                        $existingId,
                        $code,
                        $title
                    ));
                } else {
                    ++$stats['created'];
                    $this->log(sprintf('[dry-run create] id=%d code=%s title=%s', $id, $code, $title));
                }
                continue;
            }

            $element = new \CIBlockElement();
            if ($existingId > 0) {
                $ok = $element->Update($existingId, $fields);
                if (!$ok) {
                    ++$stats['errors'];
                    $msg = sprintf('[error update] id=%d element=%d: %s', $id, $existingId, (string)$element->LAST_ERROR);
                    $stats['details'][] = $msg;
                    $this->log($msg);
                    continue;
                }
                ++$stats['updated'];
                $this->log(sprintf('[updated] id=%d element=%d code=%s', $id, $existingId, $code));
                continue;
            }

            $newId = (int)$element->Add($fields);
            if ($newId <= 0) {
                ++$stats['errors'];
                $msg = sprintf('[error create] id=%d: %s', $id, (string)$element->LAST_ERROR);
                $stats['details'][] = $msg;
                $this->log($msg);
                continue;
            }

            ++$stats['created'];
            $this->log(sprintf('[created] id=%d element=%d code=%s', $id, $newId, $code));
        }

        return $stats;
    }

    /**
     * Исправляет /upload/news/ → /upload/news_images/ в уже импортированных элементах IBLOCK 16.
     *
     * @return array{total:int,fixed:int,skipped:int,errors:int,details:list<string>}
     */
    public function fixImportedContentImagePaths(bool $dryRun = true): array
    {
        if (!Loader::includeModule('iblock')) {
            throw new \RuntimeException('Module iblock is not available');
        }

        $stats = [
            'total' => 0,
            'fixed' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => [],
        ];

        $res = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => self::IBLOCK_ID],
            false,
            false,
            ['ID', 'NAME', 'DETAIL_TEXT']
        );

        while ($row = $res->Fetch()) {
            ++$stats['total'];
            $elementId = (int)$row['ID'];
            $original = (string)($row['DETAIL_TEXT'] ?? '');
            $fixed = self::normalizeContentHtmlImagePaths($original);

            if ($fixed === $original) {
                ++$stats['skipped'];
                continue;
            }

            if ($dryRun) {
                ++$stats['fixed'];
                $this->log(sprintf('[dry-run fix paths] element=%d name=%s', $elementId, (string)$row['NAME']));
                continue;
            }

            $element = new \CIBlockElement();
            $ok = $element->Update($elementId, [
                'DETAIL_TEXT' => $fixed,
                'DETAIL_TEXT_TYPE' => 'html',
            ]);

            if (!$ok) {
                ++$stats['errors'];
                $msg = sprintf('[error fix paths] element=%d: %s', $elementId, (string)$element->LAST_ERROR);
                $stats['details'][] = $msg;
                $this->log($msg);
                continue;
            }

            ++$stats['fixed'];
            $this->log(sprintf('[fixed paths] element=%d', $elementId));
        }

        return $stats;
    }

    public static function normalizeContentHtmlImagePaths(string $html): string
    {
        if ($html === '' || !str_contains($html, self::LEGACY_UPLOAD_IMAGES_DIR)) {
            return $html;
        }

        return str_replace(self::LEGACY_UPLOAD_IMAGES_DIR, self::UPLOAD_IMAGES_DIR, $html);
    }

    public static function resolvePreviewPicturePath(
        string $uploadPath,
        string $documentRoot,
        string $imagesSourceDir
    ): ?string {
        $uploadPath = trim($uploadPath);
        if ($uploadPath === '') {
            return null;
        }

        $filename = basename($uploadPath);
        if ($filename === '') {
            return null;
        }

        $candidates = [
            rtrim($imagesSourceDir, '/') . '/' . $filename,
            rtrim($documentRoot, '/') . self::UPLOAD_IMAGES_DIR . $filename,
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param list<array{old_path:string,new_path:string,title:string}> $redirects
     */
    public static function writeRedirectsPhpFile(string $targetPath, array $redirects): int
    {
        $map = [];
        foreach ($redirects as $item) {
            $oldPath = (string)($item['old_path'] ?? '');
            $newPath = (string)($item['new_path'] ?? '');
            if ($oldPath === '' || $newPath === '' || $oldPath === $newPath) {
                continue;
            }
            $map[$oldPath] = $newPath;
        }

        ksort($map);

        $lines = [];
        $lines[] = '<?php';
        $lines[] = '/**';
        $lines[] = ' * Редиректы старых URL новостей eklektika.ru → /novosti/{code}/';
        $lines[] = ' * Сгенерировано: php local/tools/import-news.php --export-redirects';
        $lines[] = ' * old_path => new_path';
        $lines[] = ' */';
        $lines[] = 'return [';

        foreach ($map as $oldPath => $newPath) {
            $lines[] = '    ' . var_export($oldPath, true) . ' => ' . var_export($newPath, true) . ',';
        }

        $lines[] = '];';
        $lines[] = '';

        $content = implode(PHP_EOL, $lines);
        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($targetPath, $content);

        return count($map);
    }

    /**
     * @param array{id:int,title:string,url:string} $row
     * @param array<string, true> $usedCodes
     */
    public static function buildElementCode(array $row, array &$usedCodes): string
    {
        $code = self::extractCodeFromUrl((string)($row['url'] ?? ''));
        if ($code === '') {
            $code = \CUtil::translit(
                (string)$row['title'],
                'ru',
                [
                    'max_len' => 100,
                    'change_case' => 'L',
                    'replace_space' => '-',
                    'replace_other' => '-',
                    'delete_repeat_replace' => true,
                ]
            );
        }

        $code = trim($code, '-');
        if ($code === '') {
            $code = 'news-' . (int)$row['id'];
        }

        $base = $code;
        $suffix = 1;
        while (isset($usedCodes[$code])) {
            $code = $base . '-' . $suffix;
            ++$suffix;
        }

        $usedCodes[$code] = true;

        return $code;
    }

    public static function extractCodeFromUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $path = (string)(parse_url($url, PHP_URL_PATH) ?? '');
        $path = trim($path, '/');
        if ($path === '') {
            return '';
        }

        if (preg_match('#(?:^|/)novosti/([^/]+)/?$#u', '/' . $path . '/', $matches)) {
            return trim((string)$matches[1]);
        }

        $parts = explode('/', $path);

        return trim((string)end($parts));
    }

    /**
     * @return array{old_path:string,new_path:string}|null
     */
    public static function buildRedirectEntry(string $oldUrl, string $elementCode): ?array
    {
        $elementCode = trim($elementCode);
        if ($elementCode === '') {
            return null;
        }

        $oldPath = self::extractPathFromUrl($oldUrl);
        if ($oldPath === null) {
            return null;
        }

        $newPath = self::SEF_FOLDER . $elementCode . '/';
        if (self::normalizeRedirectPath($oldPath) === self::normalizeRedirectPath($newPath)) {
            return null;
        }

        return [
            'old_path' => self::normalizeRedirectPath($oldPath),
            'new_path' => $newPath,
        ];
    }

    public static function normalizeRedirectPath(string $path): string
    {
        $path = (string)(parse_url($path, PHP_URL_PATH) ?? $path);
        if ($path !== '' && strpos(basename($path), '.') === false) {
            return rtrim($path, '/') . '/';
        }

        return $path;
    }

    public static function formatBitrixDateTime(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return null;
        }

        return date('d.m.Y H:i:s', $timestamp);
    }

    /**
     * @return array<string, true>
     */
    private function loadExistingCodes(): array
    {
        $usedCodes = [];
        $res = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => self::IBLOCK_ID],
            false,
            false,
            ['ID', 'CODE']
        );
        while ($row = $res->Fetch()) {
            $code = trim((string)($row['CODE'] ?? ''));
            if ($code !== '') {
                $usedCodes[$code] = true;
            }
        }

        return $usedCodes;
    }

    private function findExistingElementId(string $xmlId, string $code): int
    {
        if ($xmlId !== '') {
            $res = \CIBlockElement::GetList(
                [],
                ['IBLOCK_ID' => self::IBLOCK_ID, 'XML_ID' => $xmlId],
                false,
                ['nTopCount' => 1],
                ['ID']
            );
            if ($row = $res->Fetch()) {
                return (int)$row['ID'];
            }
        }

        if ($code !== '') {
            $res = \CIBlockElement::GetList(
                [],
                ['IBLOCK_ID' => self::IBLOCK_ID, 'CODE' => $code],
                false,
                ['nTopCount' => 1],
                ['ID']
            );
            if ($row = $res->Fetch()) {
                return (int)$row['ID'];
            }
        }

        return 0;
    }

    private static function extractPathFromUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return null;
        }

        return $path;
    }

    private function log(string $message): void
    {
        if ($this->logger !== null) {
            ($this->logger)($message);
        }
    }
}
