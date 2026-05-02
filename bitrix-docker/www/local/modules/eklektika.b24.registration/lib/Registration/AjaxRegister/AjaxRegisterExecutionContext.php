<?php

namespace OnlineService\B24\Registration\AjaxRegister;

/**
 * Состояние конвейера регистрации: входные поля и промежуточные артефакты между шагами.
 */
final class AjaxRegisterExecutionContext
{
    /**
     * @param array<string, string> $post
     */
    public function __construct(public array $post)
    {
    }

    public bool $isExistingCompanyByInn = false;

    /**
     * Сырой успешный ответ пречека ИНН (n8n crm-check-inn-v1); как в {@see \OnlineService\B24\Registration\CrmRegistrationOrchestrator::createB24Company}: непустой — в CRM есть реквизиты/компания по ИНН.
     * Непустой массив — в CRM уже есть компания/реквизиты по этому ИНН; [] или null — ИНН считается свободным в контексте ответа.
     *
     * @var array<mixed>|null
     */
    public ?array $crmInnPrecheckPayload = null;

    /** @var array<string, mixed> */
    public array $userFields = [];

    public int $newUserId = 0;
}

