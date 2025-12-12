<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// Построение иерархии секций из плоского массива
if (0 < $arResult['SECTIONS_COUNT'] && !empty($arResult['SECTIONS']))
{
	// Собираем все секции в массив с ключом по ID
	$arSectionsById = array();
	foreach ($arResult['SECTIONS'] as $arSection)
	{
		$arSectionsById[$arSection['ID']] = $arSection;
		$arSectionsById[$arSection['ID']]['SECTIONS'] = array(); // Инициализируем массив для дочерних секций
	}
	
	// Строим иерархию: добавляем дочерние секции к родительским
	$arHierarchy = array();
	foreach ($arResult['SECTIONS'] as $arSection)
	{
		if ($arSection['DEPTH_LEVEL'] == 1)
		{
			// Секции первого уровня - добавляем в результат
			$arHierarchy[$arSection['ID']] = &$arSectionsById[$arSection['ID']];
		}
		elseif (!empty($arSection['IBLOCK_SECTION_ID']) && isset($arSectionsById[$arSection['IBLOCK_SECTION_ID']]))
		{
			// Вложенные секции - добавляем к родителю
			$arSectionsById[$arSection['IBLOCK_SECTION_ID']]['SECTIONS'][] = $arSection;
		}
	}
	
	// Заменяем исходный массив на иерархический
	$arResult['SECTIONS'] = array_values($arHierarchy);
	$arResult['SECTIONS_COUNT'] = count($arHierarchy);
	
	// Очистка ссылок
	unset($arHierarchy, $arSectionsById);
}
?>