<?php

namespace OnlineService\Catalog\Import1c\Config;

final class PostImportConfig
{
    public const IBLOCK_ID_1C = 45;
    public const TARGET_APPLICATION_TYPES_PROPERTY = 'APPLICATION_TYPES';

    /**
     * @return array<string, string>
     */
    public static function getApplicationTypePropertyMap(): array
    {
        return [
            'PROPERTY_TAMPOPECHAT_VALUE' => 'Тампопечать',
            'PROPERTY_SHELKOGRAFIYA_VALUE' => 'Шелкография',
            'PROPERTY_FLEKSOGRAFIYA_VALUE' => 'Флексография',
            'PROPERTY_LAZERNAYA_GRAVIROVKA_VALUE' => 'Лазерная гравировка',
            'PROPERTY_UF_PECHAT_VALUE' => 'УФ-печать',
            'PROPERTY_POLIMERNAYA_NAKLEYKA_VALUE' => 'Полимерная наклейка',
            'PROPERTY_VYSHIVKA_VALUE' => 'Вышивка',
            'PROPERTY_SHEVRON_VALUE' => 'Шеврон',
            'PROPERTY_PRYAMAYA_TSIFROVAYA_PECHAT_VALUE' => 'Прямая цифровая печать',
            'PROPERTY_SUBLIMATSIONNAYA_PECHAT_VALUE' => 'Сублимационная печать',
            'PROPERTY_DEKOLIROVANIE_VALUE' => 'Деколирование',
            'PROPERTY_SHILDY_I_NAKLEYKI_VALUE' => 'Шильды и наклейки',
            'PROPERTY_TISNENIE_VALUE' => 'Тиснение',
            'PROPERTY_TERMOTRANSFER_VALUE' => 'Термотрансфер',
            'PROPERTY_ZALIVKA_POLIMERNOY_SMOLOY_VALUE' => 'Заливка полимерной смолой',
            'PROPERTY_POLIGRAFICHESKAYA_VSTAVKA_VALUE' => 'Полиграфическая вставка',
            'PROPERTY_TSIFROVAYA_PECHAT_VALUE' => 'Цифровая печать',
        ];
    }
}
