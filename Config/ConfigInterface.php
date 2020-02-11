<?php

declare(strict_types=1);

namespace Creative\SelectelBundle\Config;

/**
 * Интерфейс для объекта, который содержит настройки для доступа к API selectel.
 */
interface ConfigInterface
{
    /**
     * Возвращает базовую ссылку на API.
     */
    public function getApiHost(): string;

    /**
     * Возвращает идентификатор договорана стороне selectel.
     */
    public function getAccountId(): string;

    /**
     * Возвращает идентификатор пользователя.
     */
    public function getUserId(): string;

    /**
     * Возвращает пароль пользователя.
     */
    public function getUserPassword(): string;

    /**
     * Возвращает имя контейнера для обращения.
     */
    public function getContainer(): string;
}
