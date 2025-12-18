<?php
$arUrlRewrite=array (
  3 => 
  array (
    'CONDITION' => '#^/dejstvuyushhie-akcii/([^/?]+)/?#',
    'RULE' => 'ELEMENT_CODE=$1',
    'ID' => NULL,
    'PATH' => '/dejstvuyushhie-akcii/detail.php',
    'SORT' => 100,
  ),
  0 => 
  array (
    'CONDITION' => '#^\\/?\\/mobileapp/jn\\/(.*)\\/.*#',
    'RULE' => 'componentName=$1',
    'ID' => NULL,
    'PATH' => '/bitrix/services/mobileapp/jn.php',
    'SORT' => 100,
  ),
  2 => 
  array (
    'CONDITION' => '#^/bitrix/services/ymarket/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/bitrix/services/ymarket/index.php',
    'SORT' => 100,
  ),
  4 => 
  array (
    'CONDITION' => '#^/novosti/([^/?]+)/?#',
    'RULE' => 'ELEMENT_CODE=$1',
    'ID' => NULL,
    'PATH' => '/novosti/detail.php',
    'SORT' => 100,
  ),
  1 => 
  array (
    'CONDITION' => '#^/rest/#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/bitrix/services/rest/index.php',
    'SORT' => 100,
  ),
);
