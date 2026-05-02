<?php

namespace OnlineService\B24\Registration\AjaxRegister;

/**
 * Нормализованный JSON-ответ сценария публичной регистрации (фасад для ajax-register-action).
 */
final class AjaxRegisterResponse
{
    /**
     * @param array<string, mixed> $body
     */
    private function __construct(private readonly array $body)
    {
    }

    public static function fail(string $error): self
    {
        return new self([
            'success' => false,
            'error' => $error,
        ]);
    }

    public static function ok(): self
    {
        return new self([
            'success' => true,
            'message' => 'Регистрация успешно завершена',
            'redirect' => '/?reg_pending=1',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->body;
    }
}

