<?php

declare(strict_types=1);

namespace Creative\SelectelBundle\AuthToken;

/**
 * Интерфейс для объекта, который содержит токен авторизации на стороне API.
 */
interface AuthTokenInterface
{
    /**
     * Проверяет, что токен еще валиден.
     */
    public function isValid(): bool;

    /**
     * Возвращает строку с токеном для заголовка.
     */
    public function toHeader(): string;
}
