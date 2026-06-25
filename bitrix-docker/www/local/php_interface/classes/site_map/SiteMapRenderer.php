<?php

class SiteMapRenderer
{
    /**
     * @param array<int, array{ID?:int,NAME?:string,TITLE?:string,URL:string,CHILDREN?:array}> $nodes
     */
    public static function renderTree(array $nodes): void
    {
        if ($nodes === []) {
            return;
        }

        echo '<ul class="site-map__list">';
        foreach ($nodes as $node) {
            $title = (string)($node['NAME'] ?? $node['TITLE'] ?? '');
            $url = (string)($node['URL'] ?? '');
            if ($title === '' || $url === '') {
                continue;
            }

            echo '<li class="site-map__item">';
            echo '<a class="site-map__link" href="' . htmlspecialcharsbx($url) . '">';
            echo htmlspecialcharsbx($title);
            echo '</a>';

            if (!empty($node['CHILDREN']) && is_array($node['CHILDREN'])) {
                self::renderTree($node['CHILDREN']);
            }

            echo '</li>';
        }
        echo '</ul>';
    }

    /**
     * @param array<int, array{TITLE:string,URL:string}> $links
     */
    public static function renderFlatLinks(array $links): void
    {
        if ($links === []) {
            return;
        }

        echo '<ul class="site-map__list site-map__list_flat">';
        foreach ($links as $link) {
            $title = trim((string)($link['TITLE'] ?? ''));
            $url = trim((string)($link['URL'] ?? ''));
            if ($title === '' || $url === '') {
                continue;
            }

            echo '<li class="site-map__item">';
            echo '<a class="site-map__link" href="' . htmlspecialcharsbx($url) . '">';
            echo htmlspecialcharsbx($title);
            echo '</a>';
            echo '</li>';
        }
        echo '</ul>';
    }
}
