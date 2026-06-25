<?php

/**
 * Редирект с «голого» detail.php раздела на индекс (без ELEMENT_CODE / ELEMENT_ID).
 *
 * @param list<string> $invalidElementCodes ложные коды из urlrewrite, напр. detail.php
 */
function eklektikaRedirectSectionDetailWithoutElement(string $indexPath, array $invalidElementCodes = ['detail.php']): void
{
    $code = trim((string)($_REQUEST['ELEMENT_CODE'] ?? $_GET['ELEMENT_CODE'] ?? ''));
    $id = (int)($_REQUEST['ELEMENT_ID'] ?? $_GET['ELEMENT_ID'] ?? 0);

    if ($id > 0) {
        return;
    }

    if ($code === '' || \in_array($code, $invalidElementCodes, true)) {
        $indexPath = '/' . \trim($indexPath, '/') . '/';
        \header('Location: ' . $indexPath, true, 301);
        exit;
    }
}
