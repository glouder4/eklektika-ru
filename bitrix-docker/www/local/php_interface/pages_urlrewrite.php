<?php

/**
 * ЧПУ инфоблока pages (IBLOCK_ID=25): /pages/{ELEMENT_CODE}/ → /pages/detail.php
 */
return [
    [
        'CONDITION' => '#^/pages/([^/?]+)/?#',
        'RULE' => 'ELEMENT_CODE=$1',
        'ID' => null,
        'PATH' => '/pages/detail.php',
        'SORT' => 100,
    ],
];
