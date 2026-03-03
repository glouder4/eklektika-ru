<?php
$arUrlRewrite = array (
  0 =>
  array (
    'CONDITION' => '#^\\/?\\/mobileapp/jn\\/(.*)\\/.*#',
    'RULE' => 'componentName=$1',
    'ID' => NULL,
    'PATH' => '/bitrix/services/mobileapp/jn.php',
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
  2 =>
  array (
    'CONDITION' => '#^/bitrix/services/ymarket/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/bitrix/services/ymarket/index.php',
    'SORT' => 100,
  ),
  3 =>
  array (
    'CONDITION' => '#^/dejstvuyushhie-akcii/([^/?]+)/?#',
    'RULE' => 'ELEMENT_CODE=$1',
    'ID' => NULL,
    'PATH' => '/dejstvuyushhie-akcii/detail.php',
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
  5 =>
  array (
    'CONDITION' => '#^/o-kompanii/clients/([^/?]+)/?#',
    'RULE' => 'ELEMENT_CODE=$1',
    'ID' => NULL,
    'PATH' => '/o-kompanii/clients/detail.php',
    'SORT' => 100,
  ),
  14 =>
  array (
    'CONDITION' => '#^/clients/([^/?]+)/?#',
    'RULE' => 'ELEMENT_CODE=$1',
    'ID' => NULL,
    'PATH' => '/o-kompanii/clients/detail.php',
    'SORT' => 100,
  ),
  6 =>
  array (
    'CONDITION' => '#^/catalog/#',
    'RULE' => '',
    'ID' => 'bitrix:catalog',
    'PATH' => '/catalog/index.php',
    'SORT' => 100,
  ),
  7 =>
  array (
    'CONDITION' => '#^/search/#',
    'RULE' => '',
    'ID' => 'bitrix:catalog',
    'PATH' => '/search/index.php',
    'SORT' => 100,
  ),
  8 =>
  array (
    'CONDITION' => '#^/company/profile/([^/?]+)/?#',
    'RULE' => 'ELEMENT_ID=$1',
    'ID' => 'bitrix:news',
    'PATH' => '/company/profile/index.php',
    'SORT' => 100,
  ),
  9 =>
  array (
    'CONDITION' => '#^/feedback/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/o-kompanii/feedback/index.php',
    'SORT' => 100,
  ),
  10 =>
  array (
    'CONDITION' => '#^/razrabotka-suvenirnoj-produkcii\.php(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/informacziya-dlya-dilerov/razrabotka-suvenirnoj-produkcii.php',
    'SORT' => 100,
  ),
  11 =>
  array (
    'CONDITION' => '#^/vakansii\.php(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/o-kompanii/vakansii.php',
    'SORT' => 100,
  ),
  12 =>
  array (
    'CONDITION' => '#^/sotrudniki/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/o-kompanii/sotrudniki/index.php',
    'SORT' => 100,
  ),
  13 =>
  array (
    'CONDITION' => '#^/vsem-podarki-za-zakazy!/?(?:\?.*)?$#',
    'RULE' => 'ELEMENT_CODE=darim-podarki-za-zakaz',
    'ID' => NULL,
    'PATH' => '/dejstvuyushhie-akcii/detail.php',
    'SORT' => 100,
  ),
  15 =>
  array (
    'CONDITION' => '#^/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-gruppy-kompanij-novotrans/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/clients/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-gruppy-kompanij-novotrans/index.php',
    'SORT' => 100,
  ),
  16 =>
  array (
    'CONDITION' => '#^/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-gruppy-kompanij-novotrans/podarochnyj-nabor-s-medom-dlya-gruppy-kompanij-novotrans\.php(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/clients/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-gruppy-kompanij-novotrans/podarochnyj-nabor-s-medom-dlya-gruppy-kompanij-novotrans.php',
    'SORT' => 100,
  ),
  17 =>
  array (
    'CONDITION' => '#^/podarochnyj-nabor-s-medom-dlya-gruppy-kompanij-novotrans\.php(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/clients/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-gruppy-kompanij-novotrans/podarochnyj-nabor-s-medom-dlya-gruppy-kompanij-novotrans.php',
    'SORT' => 100,
  ),
  18 =>
  array (
    'CONDITION' => '#^/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-soyuza-pediatrov-rossii/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/clients/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-soyuza-pediatrov-rossii/index.php',
    'SORT' => 100,
  ),
  19 =>
  array (
    'CONDITION' => '#^/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-soyuza-pediatrov-rossii/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-soyuza-pediatrov-rossii1\.php(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/clients/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-soyuza-pediatrov-rossii/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-soyuza-pediatrov-rossii1.php',
    'SORT' => 100,
  ),
  20 =>
  array (
    'CONDITION' => '#^/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-soyuza-pediatrov-rossii/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-soyuz-pediatrov-rossii\.php(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/clients/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-soyuza-pediatrov-rossii/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-soyuz-pediatrov-rossii.php',
    'SORT' => 100,
  ),
  21 =>
  array (
    'CONDITION' => '#^/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-soyuz-pediatrov-rossii\.php(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/clients/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-soyuza-pediatrov-rossii/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-soyuz-pediatrov-rossii.php',
    'SORT' => 100,
  ),
  22 =>
  array (
    'CONDITION' => '#^/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-soyuza-pediatrov-rossii1\.php(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/clients/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-soyuza-pediatrov-rossii/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-soyuza-pediatrov-rossii1.php',
    'SORT' => 100,
  ),
  23 =>
  array (
    'CONDITION' => '#^/moskovskij-planetarij1\.php(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/clients/suvenirnaya-produktsiya-po-individualnomu-dizaynu-dlya-ao-planetariy/index.php',
    'SORT' => 100,
  ),
  24 =>
  array (
    'CONDITION' => '#^/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-ao-alfa-bank/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/clients/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-ao-alfa-bank/index.php',
    'SORT' => 100,
  ),
  25 =>
  array (
    'CONDITION' => '#^/Endress/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/clients/suvenirnaya-produktsiya-po-individualnomu-dizaynu-dlya-gk-endress-hauser/index.php',
    'SORT' => 100,
  ),
  27 =>
  array (
    'CONDITION' => '#^/suvenirnaya-produktsiya-po-individualnomu-dizaynu-dlya-gk-endress-hauser/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/clients/suvenirnaya-produktsiya-po-individualnomu-dizaynu-dlya-gk-endress-hauser/index.php',
    'SORT' => 100,
  ),
  26 =>
  array (
    'CONDITION' => '#^/korporativnyj-podarochnyj-nabor\.php(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/clients/suvenirnaya-produktsiya-po-individualnomu-dizaynu-dlya-gk-endress-hauser/korporativnyj-podarochnyj-nabor.php',
    'SORT' => 100,
  ),
  28 =>
  array (
    'CONDITION' => '#^/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-vip-milk\.php(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/clients/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-vip-milk.php',
    'SORT' => 100,
  ),
  29 =>
  array (
    'CONDITION' => '#^/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-evrocement-grupp/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/clients/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-evrocement-grupp/index.php',
    'SORT' => 100,
  ),
  30 =>
  array (
    'CONDITION' => '#^/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-evrocement\.php(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/clients/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-evrocement-grupp/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-evrocement.php',
    'SORT' => 100,
  ),
  31 =>
  array (
    'CONDITION' => '#^/korporativnye-podarochnye-nabory-i-suveniry-k-novomu-godu-dlya-kompanii-evrocement-grupp\.php(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/clients/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-evrocement-grupp/korporativnye-podarochnye-nabory-i-suveniry-k-novomu-godu-dlya-kompanii-evrocement-grupp.php',
    'SORT' => 100,
  ),
  32 =>
  array (
    'CONDITION' => '#^/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-kompanii-forest\.php(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/clients/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-kompanii-forest.php',
    'SORT' => 100,
  ),
  33 =>
  array (
    'CONDITION' => '#^/tampopechat-na-metalle/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/tampopechat/tampopechat-na-metalle/index.php',
    'SORT' => 100,
  ),
  34 =>
  array (
    'CONDITION' => '#^/novyj-prajs-list-na-tampopechat/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/tampopechat/novyj-prajs-list-na-tampopechat/index.php',
    'SORT' => 100,
  ),
  35 =>
  array (
    'CONDITION' => '#^/tampopechat-na-kruzhkax/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/tampopechat/tampopechat-na-kruzhkax/index.php',
    'SORT' => 100,
  ),
  36 =>
  array (
    'CONDITION' => '#^/tampopechat-na-plastike/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/tampopechat/tampopechat-na-plastike/index.php',
    'SORT' => 100,
  ),
  37 =>
  array (
    'CONDITION' => '#^/tampopechat-na-ruchkax/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/tampopechat/tampopechat-na-ruchkax/index.php',
    'SORT' => 100,
  ),
  38 =>
  array (
    'CONDITION' => '#^/tampopechat-na-zazhigalkax/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/tampopechat/tampopechat-na-zazhigalkax/index.php',
    'SORT' => 100,
  ),
  39 =>
  array (
    'CONDITION' => '#^/sublimacionnaya-pechat-na-zontah-cena-naneseniya/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/sublimacionnaya_pechat/sublimacionnaya-pechat-na-zontah-cena-naneseniya/index.php',
    'SORT' => 100,
  ),
  40 =>
  array (
    'CONDITION' => '#^/sublimaciya-na-futbolkah-cena/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/sublimacionnaya_pechat/sublimaciya-na-futbolkah-cena/index.php',
    'SORT' => 100,
  ),
  41 =>
  array (
    'CONDITION' => '#^/sublimaciya-na-kepkah-cena/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/sublimacionnaya_pechat/sublimaciya-na-kepkah-cena/index.php',
    'SORT' => 100,
  ),
  42 =>
  array (
    'CONDITION' => '#^/sublimaciya-na-kruzhkah-cena/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/sublimacionnaya_pechat/sublimaciya-na-kruzhkah-cena/index.php',
    'SORT' => 100,
  ),
  43 =>
  array (
    'CONDITION' => '#^/sublimaciya-na-ploskih-poverhnostyah-cena/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/sublimacionnaya_pechat/sublimaciya-na-ploskih-poverhnostyah-cena/index.php',
    'SORT' => 100,
  ),
  44 =>
  array (
    'CONDITION' => '#^/pechat-na-futbolkakh-shelkografiya/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/shelkografiya/pechat-na-futbolkakh-shelkografiya/index.php',
    'SORT' => 100,
  ),
  45 =>
  array (
    'CONDITION' => '#^/pechat-na-tkani-shelkografiya/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/shelkografiya/pechat-na-tkani-shelkografiya/index.php',
    'SORT' => 100,
  ),
  46 =>
  array (
    'CONDITION' => '#^/shelkografiya-na-zontakh/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/shelkografiya/shelkografiya-na-zontakh/index.php',
    'SORT' => 100,
  ),
  47 =>
  array (
    'CONDITION' => '#^/shelkotransfer-na-beysbolki-i-kepki/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/shelkografiya/shelkotransfer-na-beysbolki-i-kepki/index.php',
    'SORT' => 100,
  ),
  48 =>
  array (
    'CONDITION' => '#^/prays-list-na-tisnenie/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/tisnenie/prays-list-na-tisnenie/index.php',
    'SORT' => 100,
  ),
  49 =>
  array (
    'CONDITION' => '#^/tisnenie-ezhednevnikov/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/tisnenie/tisnenie-ezhednevnikov/index.php',
    'SORT' => 100,
  ),
  50 =>
  array (
    'CONDITION' => '#^/tisnenie-folgoj/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/tisnenie/tisnenie-folgoj/index.php',
    'SORT' => 100,
  ),
  51 =>
  array (
    'CONDITION' => '#^/tisnenie-na-kozhe/?(?:\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/tisnenie/tisnenie-na-kozhe/index.php',
    'SORT' => 100,
  ),
);
