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
    'CONDITION' => '#^/novosti/([^/]+)/?.*#',
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
  52 =>
    array (
      'CONDITION' => '#^/lazernaya-gravirovka-na-stekle/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/lazernaya-gravirovka/lazernaya-gravirovka-na-stekle/index.php',
      'SORT' => 100,
  ),
  53 =>
    array (
      'CONDITION' => '#^/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-pao-rosseti/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/portfolio/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-pao-rosseti.php',
      'SORT' => 100,
  ),
  54 =>
    array (
      'CONDITION' => '#^/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-oao-rzhd/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/portfolio/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-oao-rzhd/index.php',
      'SORT' => 100,
  ),
  55 =>
    array (
      'CONDITION' => '#^/krugovaya-lazernaya-gravirovka/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/lazernaya-gravirovka/krugovaya-lazernaya-gravirovka/index.php',
      'SORT' => 100,
  ),
  56 =>
    array (
      'CONDITION' => '#^/lazernaya-gravirovka-suvenirov/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/lazernaya-gravirovka/lazernaya-gravirovka-suvenirov/index.php',
      'SORT' => 100,
  ),
  57 =>
    array (
      'CONDITION' => '#^/lazernaya-gravirovka-ruchek/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/lazernaya-gravirovka/lazernaya-gravirovka-ruchek/index.php',
      'SORT' => 100,
  ),
  58 =>
    array (
      'CONDITION' => '#^/lazernaya-gravirovka-na-metalle/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/lazernaya-gravirovka/lazernaya-gravirovka-na-metalle/index.php',
      'SORT' => 100,
  ),
  59 =>
    array (
      'CONDITION' => '#^/lazernaya-gravirovka-na-plastike-i-krashennyix-poverxnostyax/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/lazernaya-gravirovka/lazernaya-gravirovka-na-plastike-i-krashennyix-poverxnostyax/index.php',
      'SORT' => 100,
  ),
  60 =>
    array (
      'CONDITION' => '#^/uf-pechat-na-dereve/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/polnocvetnaya-uf-pechat/uf-pechat-na-dereve/index.php',
      'SORT' => 100,
  ),
  61 =>
    array (
      'CONDITION' => '#^/uf-pechat-na-chehlah/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/polnocvetnaya-uf-pechat/uf-pechat-na-chehlah/index.php',
      'SORT' => 100,
  ),
  62 =>
    array (
      'CONDITION' => '#^/uf-pechat-na-metalle/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/polnocvetnaya-uf-pechat/uf-pechat-na-metalle/index.php',
      'SORT' => 100,
  ),
  63 =>
    array (
      'CONDITION' => '#^/uf-pechat-na-kozhe/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/polnocvetnaya-uf-pechat/uf-pechat-na-kozhe/index.php',
      'SORT' => 100,
  ),
  64 =>
    array (
      'CONDITION' => '#^/uf-pechat-na-ruchkah/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/polnocvetnaya-uf-pechat/uf-pechat-na-ruchkah/index.php',
      'SORT' => 100,
  ),
  65 =>
    array (
      'CONDITION' => '#^/uf-pechat-na-fleshkah/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/polnocvetnaya-uf-pechat/uf-pechat-na-fleshkah/index.php',
      'SORT' => 100,
  ),
  66 =>
    array (
      'CONDITION' => '#^/uf-pechat-na-plastike/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/polnocvetnaya-uf-pechat/uf-pechat-na-plastike/index.php',
      'SORT' => 100,
  ),
  67 =>
    array (
      'CONDITION' => '#^/tampopechat_nashi_raboty/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/tampopechat/tampopechat_nashi_raboty/index.php',
      'SORT' => 100,
  ),
  68 =>
    array (
      'CONDITION' => '#^/tampopechat-na-stekle/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/tampopechat/tampopechat-na-stekle/index.php',
      'SORT' => 100,
  ),
  69 =>
    array (
      'CONDITION' => '#^/nashi-raboty-shelkografiya-i-termotransfer/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/shelkografiya/nashi-raboty-shelkografiya-i-termotransfer/index.php',
      'SORT' => 100,
  ),
  70 =>
    array (
      'CONDITION' => '#^/tisnenie_nashi_raboty/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/tisnenie/tisnenie_nashi_raboty/index.php',
      'SORT' => 100,
  ),
  71 =>
    array (
      'CONDITION' => '#^/nanesenie-polnoczvetnyix-logotipov/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/polnocvetnaya-uf-pechat/nanesenie-polnoczvetnyix-logotipov/index.php',
      'SORT' => 100,
  ),
  72 =>
    array (
      'CONDITION' => '#^/sublimacionnaya-pechat-na-zontah.-cena-naneseniya/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/sublimacionnaya_pechat/sublimacionnaya-pechat-na-zontah-cena-naneseniya/index.php',
      'SORT' => 100,
  ),
  73 =>
    array (
      'CONDITION' => '#^/sublimatciia-cena/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/nanesenie/sublimatciia-cena/index.php',
      'SORT' => 100,
  ),
  74 =>
    array (
      'CONDITION' => '#^/izgotovlenie-nomerkov/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/izgotovlenie-nomerkov/index.php',
      'SORT' => 100,
  ),
  75 =>
    array (
      'CONDITION' => '#^/izgotovlenie-ofisnyix-tablichek/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/izgotovlenie-ofisnyix-tablichek/index.php',
      'SORT' => 100,
  ),
  76 =>
    array (
      'CONDITION' => '#^/izgotovlenie-kruzhek-s-logotipom/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/izgotovlenie-kruzhek-s-logotipom/index.php',
      'SORT' => 100,
  ),
  77 =>
    array (
      'CONDITION' => '#^/izgotovlenie-ezhednevnikov-pod-zakaz-po-individualnomu-dizajnu/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/izgotovlenie-ezhednevnikov-pod-zakaz-po-individualnomu-dizajnu/index.php',
      'SORT' => 100,
  ),
  78 =>
    array (
      'CONDITION' => '#^/proizvodstvo-ezhednevnikov-s-logotipom-na-zakaz?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/proizvodstvo-ezhednevnikov-s-logotipom-na-zakaz/index.php',
      'SORT' => 100,
  ),
  79 =>
    array (
      'CONDITION' => '#^/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-nic-kurchatovskij-institut/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/portfolio/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-nic-kurchatovskij-institut/index.php',
      'SORT' => 100,
    ),

  80 =>
    array (
      'CONDITION' => '#^/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-ao-planetarij\.php$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/portfolio/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-ao-planetarij.php',
      'SORT' => 100,
    ),

  81 =>
    array (
      'CONDITION' => '#^/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-edinogo-transportnogo-portala-moskovskij-transport\.php$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/portfolio/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-edinogo-transportnogo-portala-moskovskij-transport.php',
      'SORT' => 100,
    ),

  82 =>
    array (
      'CONDITION' => '#^/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-scloud/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/portfolio/suvenirnaya-produkciya-po-individualnomu-dizajnu-dlya-scloud/index.php',
      'SORT' => 100,
    ),

  83 =>
    array (
      'CONDITION' => '#^/kurchatovskiy-institut\.php$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/portfolio/kurchatovskiy-institut.php',
      'SORT' => 100,
    ),

  84 =>
    array (
      'CONDITION' => '#^/mosenergo/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/portfolio/mosenergo/index.php',
      'SORT' => 100,
    ),

  85 =>
    array (
      'CONDITION' => '#^/grav-for-tvz\.php$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/portfolio/grav-for-tvz.php',
      'SORT' => 100,
    ),

  86 =>
    array (
      'CONDITION' => '#^/grav-for-gazpoma/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/portfolio/grav-for-gazpoma/index.php',
      'SORT' => 100,
    ),

  87 =>
    array (
      'CONDITION' => '#^/grav-for-adprrf/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/portfolio/grav-for-adprrf/index.php',
      'SORT' => 100,
    ),

  88 =>
    array (
      'CONDITION' => '#^/gravyura-dlya-rzhd\.php$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/portfolio/gravyura-dlya-rzhd.php',
      'SORT' => 100,
    ),

  89 =>
    array (
      'CONDITION' => '#^/gravyura-dlya-rzhd1/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/portfolio/gravyura-dlya-rzhd1/index.php',
      'SORT' => 100,
    ),

  90 =>
    array (
      'CONDITION' => '#^/gravyura-dlya-rzhd2\.php$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/portfolio/gravyura-dlya-rzhd2.php',
      'SORT' => 100,
    ),

  91 =>
    array (
      'CONDITION' => '#^/fotografii-nashih-rabot-lg/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/portfolio/fotografii-nashih-rabot-lg/index.php',
      'SORT' => 100,
    ),
  92 =>
    array (
      'CONDITION' => '#^/korporativnyie-podarochnyie-naboryi/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/projects/korporativnyie-podarochnyie-naboryi/index.php',
      'SORT' => 100,
    ),

  93 =>
    array (
      'CONDITION' => '#^/korporativnyij-podarochnyij-proekt-novogodnij\.php$#',
      'PATH' => '/projects/korporativnyij-podarochnyij-proekt-novogodnij.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  94 =>
    array (
      'CONDITION' => '#^/korporativnyij-podarochnyij-nabor-kofe-brejk\.php$#',
      'PATH' => '/projects/korporativnyij-podarochnyij-nabor-kofe-brejk.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  95 =>
    array (
      'CONDITION' => '#^/korporativnyij-podarochnyij-nabor-dlya-muzhchin\.php$#',
      'PATH' => '/projects/korporativnyij-podarochnyij-nabor-dlya-muzhchin.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  96 =>
    array (
      'CONDITION' => '#^/korporativnyij-podarochnyij-nabor-k-istoricheskoj-date\.php$#',
      'PATH' => '/projects/korporativnyij-podarochnyij-nabor-k-istoricheskoj-date.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  97 =>
    array (
      'CONDITION' => '#^/korporativnyij-podarochnyij-nabor-delovogo-planirovaniya\.php$#',
      'PATH' => '/projects/korporativnyij-podarochnyij-nabor-delovogo-planirovaniya.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  98 =>
    array (
      'CONDITION' => '#^/korporativnyij-podarochnyij-nabor-k-yubileyu-kompanii\.php$#',
      'PATH' => '/projects/korporativnyij-podarochnyij-nabor-k-yubileyu-kompanii.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  99 =>
    array (
      'CONDITION' => '#^/korporativnyij-podarochnyij-nabor-dlya-vyistavki\.php$#',
      'PATH' => '/projects/korporativnyij-podarochnyij-nabor-dlya-vyistavki.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  100 =>
    array (
      'CONDITION' => '#^/korporativnyij-podarochnyij-nabor-nezabyivaemoe-leto\.php$#',
      'PATH' => '/projects/korporativnyij-podarochnyij-nabor-nezabyivaemoe-leto.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  101 =>
    array (
      'CONDITION' => '#^/korporativnyij-podarochnyij-nabor-nastolnaya-igra-monopoliya\.php$#',
      'PATH' => '/projects/korporativnyij-podarochnyij-nabor-nastolnaya-igra-monopoliya.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  102 =>
    array (
      'CONDITION' => '#^/korporativnyj-podarochnyj-nabor-dlya-kompanii-evrocement-grupp\.php$#',
      'PATH' => '/projects/korporativnyj-podarochnyj-nabor-dlya-kompanii-evrocement-grupp.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  103 =>
    array (
      'CONDITION' => '#^/korporativnyj-podarochnyj-nabor-i-suveniry-k-novomu-godu-dlya-kompanii-evrocement-grupp\.php$#',
      'PATH' => '/projects/korporativnyj-podarochnyj-nabor-i-suveniry-k-novomu-godu-dlya-kompanii-evrocement-grupp.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  104 =>
    array (
      'CONDITION' => '#^/korporativnyj-nabor-datirovannoj-produkcii\.php$#',
      'PATH' => '/projects/korporativnyj-nabor-datirovannoj-produkcii.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  105 =>
    array (
      'CONDITION' => '#^/novogodnij-kejs-2026\.php$#',
      'PATH' => '/projects/novogodnij-kejs-2026.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  106 =>
    array (
      'CONDITION' => '#^/novogodnij-kejs-2026-n2\.php$#',
      'PATH' => '/projects/novogodnij-kejs-2026-n2.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  107 =>
    array (
      'CONDITION' => '#^/novogodnij-kejs-2026-n3\.php$#',
      'PATH' => '/projects/novogodnij-kejs-2026-n3.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),
  108 =>
    array (
      'CONDITION' => '#^/novosti/vsem-kto-ne-uspel-zakazat/?(?:\?.*)?$#',
      'PATH' => '/novosti/vsem-kto-ne-uspel-zakazat/index.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  109 =>
    array (
      'CONDITION' => '#^/novosti/idei-biznes-podarkov/?(?:\?.*)?$#',
      'PATH' => '/novosti/idei-biznes-podarkov/index.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  110 =>
    array (
      'CONDITION' => '#^/novosti/korporativnye-podarki-zimoj/?(?:\?.*)?$#',
      'PATH' => '/novosti/korporativnye-podarki-zimoj/index.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  111 =>
    array (
      'CONDITION' => '#^/akciya-\!-besplatnoe-nanesenie-logotipa-pri-zakaze-ot-80-000-rub/?(?:\?.*)?$#',
      'PATH' => '/akcii/akciya-besplatnoe-nanesenie-logotipa/index.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  112 =>
    array (
      'CONDITION' => '#^/prajslist-na-tampopechat/?(?:\?.*)?$#',
      'PATH' => '/tampopechat/novyj-prajs-list-na-tampopechat/prajslist-na-tampopechat/index.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  113 =>
    array (
      'CONDITION' => '#^/o-kompanii/sotrudniki/yuliya-suhorukova\.php$#',
      'PATH' => '/o-kompanii/sotrudniki/yuliya-suhorukova.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  114 =>
    array (
      'CONDITION' => '#^/o-kompanii/sotrudniki/vavilova-tatyana\.php$#',
      'PATH' => '/o-kompanii/sotrudniki/vavilova-tatyana.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  115 =>
    array (
      'CONDITION' => '#^/o-kompanii/sotrudniki/oksana-kapyshova\.php$#',
      'PATH' => '/o-kompanii/sotrudniki/oksana-kapyshova.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  116 =>
    array (
      'CONDITION' => '#^/aspire-to-inspire\.php$#',
      'PATH' => '/landing/aspire-to-inspire.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  117 =>
    array (
      'CONDITION' => '#^/work-hard-dream\.php$#',
      'PATH' => '/landing/work-hard-dream.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  118 =>
    array (
      'CONDITION' => '#^/eko-logika\.php$#',
      'PATH' => '/landing/eko-logika.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  119 =>
    array (
      'CONDITION' => '#^/be-here\.php$#',
      'PATH' => '/landing/be-here.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  120 =>
    array (
      'CONDITION' => '#^/connect\.php$#',
      'PATH' => '/landing/connect.php',
      'RULE' => '',
      'ID' => NULL,
      'SORT' => 100,
    ),

  121 =>
    array (
      'CONDITION' => '#^/dejstvuyushhie-akcii/detail\.php$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/dejstvuyushhie-akcii/index.php',
      'SORT' => 100,
    ),

  122 =>
    array (
      'CONDITION' => '#^/prajs-list-na-polnoczvetnuyu-uf-pechat-s-3d-effektom/?(?:\?.*)?$#',
      'RULE' => '',
      'ID' => NULL,
      'PATH' => '/polnocvetnaya-uf-pechat/prajs-list-na-polnoczvetnuyu-uf-pechat-s-3d-effektom/index.php',
      'SORT' => 100,
    ),

123 =>
array (
  'CONDITION' => '#^/prosecco-picnic\.php$#',
  'PATH' => '/landing/prosecco-picnic.php',
  'RULE' => '',
  'ID' => NULL,
  'SORT' => 100,
),

124 =>
array (
  'CONDITION' => '#^/na-scene-zhizni\.php$#',
  'PATH' => '/landing/na-scene-zhizni.php',
  'RULE' => '',
  'ID' => NULL,
  'SORT' => 100,
),

125 =>
array (
  'CONDITION' => '#^/energy-pack\.php$#',
  'PATH' => '/landing/energy-pack.php',
  'RULE' => '',
  'ID' => NULL,
  'SORT' => 100,
),

126 =>
array (
  'CONDITION' => '#^/osoznannyj-stil\.php$#',
  'PATH' => '/landing/osoznannyj-stil.php',
  'RULE' => '',
  'ID' => NULL,
  'SORT' => 100,
),

127 =>
array (
  'CONDITION' => '#^/be-free-as-wind\.php$#',
  'PATH' => '/landing/be-free-as-wind.php',
  'RULE' => '',
  'ID' => NULL,
  'SORT' => 100,
),

128 =>
array (
  'CONDITION' => '#^/with-love\.php$#',
  'PATH' => '/landing/with-love.php',
  'RULE' => '',
  'ID' => NULL,
  'SORT' => 100,
),

129 =>
array (
  'CONDITION' => '#^/idea-agency\.php$#',
  'PATH' => '/landing/idea-agency.php',
  'RULE' => '',
  'ID' => NULL,
  'SORT' => 100,
),

130 =>
array (
  'CONDITION' => '#^/let’s-rock\.php$#',
  'PATH' => '/landing/lets-rock.php',
  'RULE' => '',
  'ID' => NULL,
  'SORT' => 100,
),

131 =>
array (
  'CONDITION' => '#^/summer-pulse\.php$#',
  'PATH' => '/landing/summer-pulse.php',
  'RULE' => '',
  'ID' => NULL,
  'SORT' => 100,
),

132 =>
array (
  'CONDITION' => '#^/wine-and-cheese\.php$#',
  'PATH' => '/landing/wine-and-cheese.php',
  'RULE' => '',
  'ID' => NULL,
  'SORT' => 100,
),

133 =>
array (
  'CONDITION' => '#^/svezhij-format\.php$#',
  'PATH' => '/landing/svezhij-format.php',
  'RULE' => '',
  'ID' => NULL,
  'SORT' => 100,
),

134 =>
array (
  'CONDITION' => '#^/playlist-in-pocket\.php$#',
  'PATH' => '/landing/playlist-in-pocket.php',
  'RULE' => '',
  'ID' => NULL,
  'SORT' => 100,
),

135 =>
array (
  'CONDITION' => '#^/russian-pattern\.php$#',
  'PATH' => '/landing/russian-pattern.php',
  'RULE' => '',
  'ID' => NULL,
  'SORT' => 100,
),

136 =>
array (
  'CONDITION' => '#^/urban-kapsula\.php$#',
  'PATH' => '/landing/urban-kapsula.php',
  'RULE' => '',
  'ID' => NULL,
  'SORT' => 100,
),

137 =>
array (
  'CONDITION' => '#^/kapsula-svobodnyh-marshrutov\.php$#',
  'PATH' => '/landing/kapsula-svobodnyh-marshrutov.php',
  'RULE' => '',
  'ID' => NULL,
  'SORT' => 100,
),

138 =>
array (
  'CONDITION' => '#^/rasprodazha-s-maksimalnoj-skidkoj/?(?:\?.*)?$#',
  'PATH' => '/rasprodaja/index.php',
  'RULE' => '',
  'ID' => NULL,
  'SORT' => 100,
),
    139 =>
        array (
            'CONDITION' => '#^/katalog/(.+)_(\d+)\.php(?:\?.*)?$#',
            'RULE' => 'ARTIKUL=$2',
            'ID' => NULL,
            'PATH' => '/catalog/redirect-by-artikul.php',
            'SORT' => 50,
        ),
    140 =>
        array (
            'CONDITION' => '#^/feed/yandex\\.yml/?(?:\?.*)?$#',
            'RULE' => '',
            'ID' => NULL,
            'PATH' => '/feed/yandex.yml/index.php',
            'SORT' => 40,
        ),
);

$brandCatalogUrlRewritePath = $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/brand_catalog_urlrewrite.php';
if (is_file($brandCatalogUrlRewritePath)) {
    $brandCatalogUrlRewrite = include $brandCatalogUrlRewritePath;
    if (is_array($brandCatalogUrlRewrite)) {
        foreach ($brandCatalogUrlRewrite as $brandCatalogRule) {
            $arUrlRewrite[] = $brandCatalogRule;
        }
    }
}

$ymlFeedUrlRewritePath = $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/yml_feed_urlrewrite.php';
if (is_file($ymlFeedUrlRewritePath)) {
    $ymlFeedUrlRewrite = include $ymlFeedUrlRewritePath;
    if (is_array($ymlFeedUrlRewrite)) {
        foreach ($ymlFeedUrlRewrite as $ymlFeedRule) {
            $arUrlRewrite[] = $ymlFeedRule;
        }
    }
}
