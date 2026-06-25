<?php

class SiteMapMenuProvider
{
    /**
     * @return array<int, array{TITLE:string,LINKS:array<int, array{TITLE:string,URL:string}>}>
     */
    public static function getSections(): array
    {
        $sections = [
            [
                'TITLE' => 'Основные разделы',
                'LINKS' => self::loadMenu('top', '/'),
            ],
            [
                'TITLE' => 'Нанесение логотипа',
                'LINKS' => self::mergeLinks(array_merge(
                    [self::singleLink('Нанесение', '/nanesenie/')],
                    self::loadMenu('left', '/nanesenie/')
                )),
            ],
            [
                'TITLE' => 'О компании',
                'LINKS' => self::mergeLinks(array_merge(
                    [self::singleLink('О компании', '/o-kompanii/')],
                    self::loadMenu('left', '/o-kompanii/')
                )),
            ],
            [
                'TITLE' => 'Информация',
                'LINKS' => self::mergeLinks(array_merge(
                    self::loadMenu('footer_col2', '/'),
                    self::loadMenu('footer_col3', '/')
                )),
            ],
            [
                'TITLE' => 'Спецразделы',
                'LINKS' => self::loadMenu('footer_col4', '/'),
            ],
        ];

        return array_values(array_filter(
            $sections,
            static function (array $section): bool {
                return !empty($section['LINKS']);
            }
        ));
    }

    /**
     * @return array<int, array{TITLE:string,URL:string}>
     */
    private static function loadMenu(string $menuType, string $dir): array
    {
        $menu = new CMenu($menuType);
        if (!$menu->Init($dir, true)) {
            return [];
        }

        $links = [];
        foreach ($menu->arMenu as $item) {
            $title = trim((string)($item[0] ?? ''));
            $url = trim((string)($item[1] ?? ''));
            if ($title === '' || $url === '' || $url === '/karta-sajta.php') {
                continue;
            }
            $links[] = [
                'TITLE' => $title,
                'URL' => $url,
            ];
        }

        return $links;
    }

    /**
     * @param array<int, array{TITLE:string,URL:string}> $links
     * @return array<int, array{TITLE:string,URL:string}>
     */
    private static function mergeLinks(array $links): array
    {
        $seen = [];
        $result = [];

        foreach ($links as $link) {
            $url = $link['URL'] ?? '';
            if ($url === '' || isset($seen[$url])) {
                continue;
            }
            $seen[$url] = true;
            $result[] = $link;
        }

        return $result;
    }

    /**
     * @return array{TITLE:string,URL:string}
     */
    private static function singleLink(string $title, string $url): array
    {
        return [
            'TITLE' => $title,
            'URL' => $url,
        ];
    }
}
