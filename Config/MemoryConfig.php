<?php

declare(strict_types=1);

namespace Creative\SelectelBundle\Config;

use InvalidArgumentException;

/**
 * Объект, который содержит в памяти настройки для доступа к API selectel.
 */
class MemoryConfig implements ConfigInterface
{
    /**
     * @var string
     */
    protected $apiHost;
    /**
     * @var string
     */
    protected $accountId;
    /**
     * @var string
     */
    protected $userId;
    /**
     * @var string
     */
    protected $userPassword;
    /**
     * @var string
     */
    protected $container;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(string $accountId, string $userId, string $userPassword, string $container, string $apiHost = null)
    {
        if ($apiHost !== null && !$this->isStringAbsoluteUri($apiHost)) {
            throw new InvalidArgumentException(
                'apiHost parameter must be an absolute uri'
            );
        }

        $this->apiHost = $apiHost ? rtrim($apiHost, '/ ') : 'https://api.selcdn.ru';
        $this->accountId = $accountId;
        $this->userId = $userId;
        $this->userPassword = $userPassword;
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function getApiHost(): string
    {
        return $this->apiHost;
    }

    /**
     * @inheritdoc
     */
    public function getAccountId(): string
    {
        return $this->accountId;
    }

    /**
     * @inheritdoc
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * @inheritdoc
     */
    public function getUserPassword(): string
    {
        return $this->userPassword;
    }

    /**
     * @inheritdoc
     */
    public function getContainer(): string
    {
        return $this->container;
    }

    /**
     * Проверяет, что строка содержит абсолютную ссылку.
     */
    protected function isStringAbsoluteUri(string $uri): bool
    {
        return (bool) preg_match('/^https?:\/\/\S+\.\S+$/', $uri);
    }
}
