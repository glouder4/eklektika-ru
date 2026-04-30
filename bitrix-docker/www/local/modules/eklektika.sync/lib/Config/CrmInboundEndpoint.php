<?php

declare(strict_types=1);

namespace OnlineService\Sync\Config;

/**
 * Канонический URL входящего канала CRM → сайт (публичный endpoint модуля).
 * Дублирование пути в {@see \OnlineService\B24\Config\RestTransportConfig} — только строкой (порядок include).
 */
final class CrmInboundEndpoint
{
    public const HTTP_PATH = '/local/modules/eklektika.sync/public/inbound_crm.php';
}
