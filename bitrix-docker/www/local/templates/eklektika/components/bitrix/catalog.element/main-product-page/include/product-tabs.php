<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

function getAttributeName(string $code) : string {
    if( $code == "ARTIKUL" )
        return "Артикул";
    elseif( $code == "ARTIKUL_POSTAVSHCHIKA" )
        return "Артикул поставщика";
    elseif( $code == "TSVET" )
        return "Цвет";
    elseif( $code == "MATERIAL" )
        return "Материал";
    elseif( $code == "BRAND" )
        return "Бренд";
    elseif( $code == "METOD_NANESENIYA" )
        return "Метод нанесения";
    elseif( $code == "WEIGHT" )
        return "Вес";

    return $code;
}

function getListPropertyValueByEnumId($value): string
{
    if ($value === null || $value === '') {
        return '';
    }

    if (!is_array($value)) {
        $value = [$value];
    }

    $labels = [];
    foreach ($value as $rawValue) {
        $rawValue = trim((string)$rawValue);
        if ($rawValue === '') {
            continue;
        }

        if (ctype_digit($rawValue)) {
            $enum = \CIBlockPropertyEnum::GetByID((int)$rawValue);
            if (is_array($enum) && !empty($enum['VALUE'])) {
                $labels[] = (string)$enum['VALUE'];
                continue;
            }
        }

        $labels[] = $rawValue;
    }

    return implode(', ', array_unique($labels));
}

function getAttributeValue(string $code, $value): string
{
    if ($value === null || $value === '') {
        return '';
    }

    if ($code === 'TSVET') {
        return getListPropertyValueByEnumId($value);
    }

    if ($code === 'WEIGHT') {
        $weight = (float)$value;
        if ($weight <= 0) {
            return '';
        }
        $formatted = abs($weight - round($weight)) < 0.00001
            ? (string)(int)round($weight)
            : rtrim(rtrim(number_format($weight, 2, '.', ''), '0'), '.');

        return $formatted . ' гр.';
    }

    if (is_array($value)) {
        return implode(', ', array_map('strval', $value));
    }

    return (string)$value;
}
?>
<div class="tabs product-tabs">
    <ul class="tabs__caption">
        <li class="active">Описание</li>
        <li>Файлы</li>
        <li>Транспорт</li>
        <li>Условия</li>
    </ul>
    <div class="tabs__content active">
        <?php
        if (!empty($currentOffer['DISPLAY_PROPERTIES'])) { ?>
            <table class="product-table">
                <tbody>
                <?php
                foreach ($currentOffer['DISPLAY_PROPERTIES'] as $code => $attribute) {
                    ?>
                    <tr>
                        <td><?= getAttributeName($code); ?></td>
                        <td><?= htmlspecialcharsbx(getAttributeValue($code, $attribute)); ?></td>
                    </tr>
                <?php }
                ?>
                </tbody>
            </table>
            <?php
        }
        ?>
        <hr>
        <?=$arResult['~DETAIL_TEXT'];?>
        <?php
        if (!empty($currentOffer['DETAIL_TEXT'])) { ?>
            <hr>
            <?= $currentOffer['DETAIL_TEXT']; ?>
            <?php
        }
        ?>
        <br>
        <br>Быстрая поставка - отличный вариант корпоративной сувенирной продукции и подарков.
        <br>
        <br>Стоимость заказа определяется количеством. Специальные цены и предложения для оптовых заказчиков. Мы создаем не просто
        сувенирную продукцию с Вашим логотипом, а продумываем идею и оформление будущего корпоративного подарка или мерча до
        мельчайших деталей, с учетом фирменного стиля компании и заданного бюджета.
        <br>
        <br>
    </div>
    <div class="tabs__content">
        <p>Макет cdr</p>
        <div class="lin"><a id="cdr" href="#">&nbsp;Скачать макет в cdr &gt;&gt;&gt;&nbsp;</a></div>
    </div>
    <div class="tabs__content 5">
        <p class="strong">Способы доставки товаров.</p>
        <p class="strong">1. Самовывоз (доставка транспортом покупателя).
            <br>
            <br>2. Доставка транспортом поставщика (Компания Эклектика) - для клиентов, расположенных в городе Москве и Московской
            области.
            <br>
            <br>3. Транспортными компаниями-партнерами Компании Эклектика:
            <br>- «<a href="http://nordw.ru/tarify/kalkulyator/" target="_blank" rel="nofollow noopener">Норд-Вил</a>» , для
            клиентов, расположенных в городах Санкт-Петербург и Казань;
            <br>- "<a href="http://tk-tline.ru/" rel="nofollow">Т-Лайн</a>", для клиентов расположенных в г. Екатеринбурге;
            <br>- "Байкал - Сервис" ;
            <br>- «<a href="http://www.pecom.ru/ru/service/store/msk/" target="_blank" rel="nofollow noopener">Первая
                Экспедиционная Компания</a>» - терминал "Москва Восток".
            <br>
            <br class="strong">4. Транспортными компаниями, выбранными клиентом.</p>
        <p class="strong">Способы оплаты</p>
        <p><span class="strong">&nbsp; 1. Наличными<br></span><span style="position:relative;left:25px">Оплата производится при получении товара в пункте самовывоза .</span>
        </p>
        <p>&nbsp; <span class="strong">2.&nbsp;</span><span class="strong">Безналичными<br></span><span style="position:relative;left:25px">Оплата производится на расчетный счет, согласно выставленному счету.</span>
        </p>
        <p>&nbsp; <span class="strong">3</span>.&nbsp;<span class="strong">Банковской картой<br></span><span
                    style="position:relative;left:25px">Оплата производится в пункте самовывоза или через безопасный сервер Яндекс.Касса.</span>
        </p>
    </div>
    <div class="tabs__content">
        <h3 class="red">Минимальная сумма заказа товара со склада в Москве - 5000 руб.</h3>
        <hr>
        <h3>Варианты доставки корпоративных подарков и сувениров, например таких как Штопор-нож "Сомелье" в подарочной упаковке,
            цвет серебряный, мы можем предложить:</h3>
        <ol>
            <li>
                <p><span style="font-weiht:bold">Самовывоз</span></p>
                <p>Мы все знаем про товар и ответим на вопросы.</p>
            </li>
            <li>
                <p><span style="font-weiht:bold">Доставка транспортом Эклектика</span></p>
                <p>Для клиентов, расположенных в городе Москве и Московской области.</p>
            </li>
            <li>
                <p><span style="font-weiht:bold">Транспортными компаниями-партнерами</span></p>
                <ul>
                    <li>«Норд-Вил» , для клиентов, расположенных в городах Санкт-Петербург и Казань;</li>
                    <li>"Т-Лайн", для клиентов расположенных в г. Екатеринбурге;</li>
                    <li>"Байкал - Сервис";</li>
                    <li>«Первая Экспедиционная Компания» - терминал "Москва Восток"</li>
                </ul>
            </li>
            <li><span style="font-weiht:bold">Транспортными компаниями, выбранными клиентом.</span></li>
        </ol>
        <hr>
        <h3>Какие Способы оплаты при покупке корпоративных подарков и сувениров, таких как Штопор-нож "Сомелье" в подарочной
            упаковке, цвет серебряный. Мы готовы предложить оптимальные решения:</h3>
        <ol>
            <li>
                <p><span style="font-weiht:bold">Наличными</span></p>
                <p>Оплата производится при получении товара в пункте самовывоза или в процессе доставки.</p>
            </li>
            <li>
                <p><span style="font-weiht:bold">Безналичными</span></p>
                <p>Оплата производится на расчетный счет, согласно выставленному счету.</p>
            </li>
            <li>
                <p><span style="font-weiht:bold">Банковской картой</span></p>
                <p>Оплата производится в пункте самовывоза или через безопасный сервер Яндекс.Касса.</p>
            </li>
        </ol>
        <hr>
        <h3>Знаете, где купить корпоративные подарки и сувениры, такие как Штопор-нож "Сомелье" в подарочной упаковке, цвет
            серебряный в интернет-магазине компании Эклектика, так как:</h3>
        <ol class="no-counter">
            <li>
                <p><span style="font-weiht:bold">Все по-честному</span></p>
                <p>Мы все знаем про товар в нашем интернет-магазине и ответим на вопросы.</p>
            </li>
            <li>
                <p><span style="font-weiht:bold">Цены и акции</span></p>
                <p>Кроме оптимальных цен вас ждут постоянные акции, бонусы и ошеломительные скидки.</p>
            </li>
            <li>
                <p><span style="font-weiht:bold">Приятные сюрпризы</span></p>
                <p>В каждом заказе тебя ждет журнал с полезными статьями, подарок-сюрприз или подарок на выбор.</p>
            </li>
        </ol>
    </div>
</div>
