<?php

namespace OnlineService\Site;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use OnlineService\Site\Config\ManagerCompositeConfig;

/**
 * Разрешение полей compositeproperties для карточки менеджера.
 */
final class ManagerCompositeFieldResolver
{
    /**
     * @return array{
     *   field_id: int,
     *   field_type: string,
     *   inner_fields: list<array<string, mixed>>
     * }|null
     */
    public function resolveSocialRepeaterMeta(): ?array
    {
        if (!Loader::includeModule('amiiyaproduction.compositeproperties')) {
            return null;
        }

        $connection = Application::getConnection();
        if (!$connection->isTableExists('b_cp_field_group') || !$connection->isTableExists('b_cp_field')) {
            return null;
        }

        $helper = $connection->getSqlHelper();
        $groupKey = $helper->forSql(ManagerCompositeConfig::GROUP_KEY);
        $repeaterCode = $helper->forSql(ManagerCompositeConfig::SOCIAL_REPEATER_CODE);

        try {
            $res = $connection->query(
                'SELECT f.`ID`, f.`TYPE`, IFNULL(f.`SETTINGS`, \'\') AS `SETTINGS`'
                . ' FROM `b_cp_field` f'
                . ' INNER JOIN `b_cp_field_group` g ON g.`ID` = f.`GROUP_ID`'
                . ' WHERE g.`KEY` = \'' . $groupKey . '\''
                . ' AND f.`CODE` = \'' . $repeaterCode . '\''
                . ' ORDER BY f.`ID` ASC LIMIT 1'
            );
            $row = $res->fetch();
        } catch (\Throwable) {
            return null;
        }

        if (!is_array($row)) {
            return null;
        }

        $fieldId = (int)($row['ID'] ?? 0);
        if ($fieldId <= 0) {
            return null;
        }

        $settings = json_decode((string)($row['SETTINGS'] ?? ''), true);
        if (!is_array($settings)) {
            $settings = [];
        }

        $innerFields = isset($settings['inner_fields']) && is_array($settings['inner_fields'])
            ? $settings['inner_fields']
            : [];

        return [
            'field_id' => $fieldId,
            'field_type' => trim((string)($row['TYPE'] ?? '')),
            'inner_fields' => $innerFields,
        ];
    }
}
