<?php

namespace OnlineService\Classes\Handlers\Search;

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;
Loader::includeModule("iblock");

class Stemming
{
    protected static $targetIblocks = [43,44];

    /**
     * Генерирует все варианты написания артикула для поиска
     * Включает сокращения и опечатки
     * 
     * @param string $articleValue Значение артикула
     * @return string Строка со всеми вариантами для индексации
     */
    protected static function generateArticleSearchVariants($articleValue)
    {
        if (empty($articleValue)) {
            return '';
        }

        $variants = [];
        
        // Варианты написания слова "артикул" (включая опечатки)
        $articleWords = [
            'Артикул',
            'артикул',
            'Арт',
            'арт',
            'Арт.',
            'арт.',
            // Опечатки и варианты
            'Артикл',
            'артикл',
            'Артикль',
            'артикль',
            'Артикуль',
            'артикуль',
            'Артик',
            'артик',
            'Артк',
            'артк',
            'Артик',
            'артик',
            'Артикл.',
            'артикл.',
        ];

        // Генерируем варианты с пробелом и без
        foreach ($articleWords as $word) {
            // С пробелом: "Артикул 1350007"
            $variants[] = $word . ' ' . $articleValue;
            // Без пробела: "Артикул1350007"
            $variants[] = $word . $articleValue;
            // С двоеточием: "Артикул: 1350007"
            $variants[] = $word . ': ' . $articleValue;
            // С двоеточием без пробела: "Артикул:1350007"
            $variants[] = $word . ':' . $articleValue;
        }

        // Также добавляем просто значение артикула
        $variants[] = $articleValue;

        return implode(' ', $variants);
    }

    /**
     * Заменяет только самые проблемные кириллические буквы на латинские
     * Заменяет только С/с и О/о, которые визуально неотличимы от C/c и O/o
     *
     * ВАЖНО: Используется для добавления ДОПОЛНИТЕЛЬНОГО варианта в индекс,
     * а не для замены оригинала!
     *
     * @param string $text Исходный текст
     * @return string Текст с замененными буквами
     */
    protected static function replaceCyrillicWithLatin($text)
    {
        if (empty($text)) {
            return '';
        }

        // Заменяем только самые проблемные буквы, которые визуально неотличимы
        $replacements = [
            'С' => 'C',  // Кириллическая С → латинская C (неотличимы визуально)
            'с' => 'c',  // Кириллическая с → латинская c
            'О' => 'O',  // Кириллическая О → латинская O (неотличимы визуально)
            'о' => 'o',  // Кириллическая о → латинская o
        ];

        $result = $text;
        foreach ($replacements as $cyrillic => $latin) {
            $result = str_replace($cyrillic, $latin, $result);
        }

        return $result;
    }
    /**
     * Извлекает отдельные слова из названия, разбивая по дефисам и другим разделителям
     * Это помогает находить товары по ключевым словам из составных названий
     * Например: "Сумка-шоппер" → ["Сумка", "шоппер"]
     *
     * @param string $title Название товара
     * @return string Строка с отдельными словами для индексации
     */
    protected static function extractWordsFromTitle($title)
    {
        if (empty($title)) {
            return '';
        }

        // Разбиваем по дефисам, пробелам и другим разделителям
        $words = preg_split('/[\s\-_]+/u', $title);

        // Фильтруем пустые значения и слишком короткие слова (меньше 2 символов)
        $words = array_filter($words, function($word) {
            $word = trim($word);
            return mb_strlen($word) >= 2;
        });

        // Убираем кавычки и другие знаки препинания из слов
        $words = array_map(function($word) {
            // Убираем кавычки, скобки и другие знаки в начале и конце слова
            $word = trim($word, ' "\'()[]{}«»„"');
            return $word;
        }, $words);

        // Убираем пустые значения после очистки
        $words = array_filter($words, function($word) {
            return mb_strlen(trim($word)) >= 2;
        });

        return implode(' ', $words);
    }

    public static function BeforeIndexHandler($arFields)
    {
        if (
            $arFields['MODULE_ID'] === 'iblock' &&
            $arFields['ITEM_ID'] &&
            in_array($arFields['PARAM2'], self::$targetIblocks)
        )
        {

            if( $arFields['PARAM2'] == 44 ) {
                $arFilter = array("IBLOCK_ID " => $arFields['PARAM2'], "ID" => $arFields['ITEM_ID']);
                $res = \CIBlockElement::GetList(array(), $arFilter); // с помощью метода CIBlockElement::GetList вытаскиваем все значения из нужного элемента
                if ($ob = $res->GetNextElement()) { // переходим к след элементу, если такой есть
                    $arProps = $ob->GetProperties(); // свойства элемента

                    if( isset($arProps['ARTIKUL_POSTAVSHCHIKA']) && !empty($arProps['ARTIKUL_POSTAVSHCHIKA']['VALUE']) ){
                        $val = $arProps['ARTIKUL_POSTAVSHCHIKA']['VALUE'];

                        $arFields["BODY"] .= " ";
                        $arFields["BODY"] .= $arFields['TITLE']; // Оригинальный текст остается

                        // Извлекаем отдельные слова из названия для лучшего поиска
                        // Например: "Сумка-шоппер" → "Сумка шоппер"
                        $titleWords = self::extractWordsFromTitle($arFields['TITLE']);
                        if (!empty($titleWords)) {
                            $arFields["BODY"] .= " ";
                            $arFields["BODY"] .= $titleWords;
                        }

                        // Генерируем все варианты написания артикула для поиска
                        $searchVariants = self::generateArticleSearchVariants($val);

                        $arFields["BODY"] .= " ";
                        $arFields["BODY"] .= $searchVariants;

                        // Добавляем ДОПОЛНИТЕЛЬНЫЙ вариант TITLE с замененными буквами
                        // Это не заменяет оригинал, а добавляет еще один вариант для поиска
                        $transliteratedTitle = self::replaceCyrillicWithLatin($arFields['TITLE']);
                        if ($transliteratedTitle !== $arFields['TITLE']) {
                            $arFields["BODY"] .= " ";
                            $arFields["BODY"] .= $transliteratedTitle; // Добавляем вариант с латинскими буквами
                        }

                        // Также добавляем отдельные слова из транслитерированного названия
                        $transliteratedWords = self::extractWordsFromTitle($transliteratedTitle);
                        if (!empty($transliteratedWords) && $transliteratedWords !== $titleWords) {
                            $arFields["BODY"] .= " ";
                            $arFields["BODY"] .= $transliteratedWords;
                        }

                        // Сохраняем существующие теги, если они есть
                        $existingTags = [];
                        if (!empty($arFields["TAGS"]) && is_string($arFields["TAGS"])) {
                            $existingTags = array_filter(array_map('trim', explode(',', $arFields["TAGS"])));
                        }

                        // Добавляем наш тег
                        $existingTags[] = "ARTIKUL_POSTAVSHCHIKA:".$val;

                        // Устанавливаем теги (очищаем старые и добавляем новые)
                        $arFields["TAGS"] = implode(',', array_unique($existingTags));

                        // Устанавливаем TAGS_FORMATED напрямую с правильным значением
                        if (!isset($arFields["TAGS_FORMATED"]) || !is_array($arFields["TAGS_FORMATED"])) {
                            $arFields["TAGS_FORMATED"] = [];
                        }
                        $arFields["TAGS_FORMATED"]["ARTIKUL_POSTAVSHCHIKA"] = $val;
                    }
                    /*if($arFields['ITEM_ID'] == 123907){
                        pre($arFields);
                        die();
                    } */
                }
            }


                /*$arFields["BODY"] .= " ";
                $arFields["BODY"] .= mb_substr($arFields["TITLE"],0,$i, 'UTF-8');*/
        }

        return $arFields; // вернём изменения
    }

    public static function beforeIndexUpdate($ID, $arFields)
    {

        return $arFields;
    }

    public static function OnAfterIndexAdd($ID, $arFields)
    {

        return $arFields;
    }

}