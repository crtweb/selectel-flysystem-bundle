<?php

declare(strict_types=1);

namespace Youtool\SelectelBundle\Tests\Config;

use Youtool\SelectelBundle\Tests\BaseCase;
use Youtool\SelectelBundle\Config\MemoryConfig;
use InvalidArgumentException;

/**
 * Набор тестов для объекта с настройками для доступа к API selectel.
 */
class MemoryConfigTest extends BaseCase
{
    /**
     * Проверяет, что объект выбросит исключение, если задать неверную ссылку
     * на сервис.
     */
    public function testConstructWrongApiHost()
    {
        $accountId = $this->createFakeData()->word;
        $userId = $this->createFakeData()->uuid;
        $userPassword = $this->createFakeData()->word;
        $container = $this->createFakeData()->word;

        $this->expectException(InvalidArgumentException::class);
        new MemoryConfig($accountId, $userId, $userPassword, $container, '/test');
    }

    /**
     * Проверяет, что объект правильно возвращает базовую ссылку на API.
     */
    public function testGetApiHost()
    {
        $accountId = $this->createFakeData()->word;
        $userId = $this->createFakeData()->uuid;
        $userPassword = $this->createFakeData()->word;
        $apiHost = $this->createFakeData()->url;
        $container = $this->createFakeData()->word;

        $config = new MemoryConfig($accountId, $userId, $userPassword, $container, $apiHost . '/');

        $this->assertSame(rtrim($apiHost, '/'), $config->getApiHost());
    }

    /**
     * Проверяет, что объект правильно возвращает идентификатор клиента.
     */
    public function testGetUserId()
    {
        $accountId = $this->createFakeData()->word;
        $userId = $this->createFakeData()->uuid;
        $userPassword = $this->createFakeData()->word;
        $container = $this->createFakeData()->word;

        $config = new MemoryConfig($accountId, $userId, $userPassword, $container);

        $this->assertSame($userId, $config->getUserId());
    }

    /**
     * Проверяет, что объект правильно возвращает пароль клиента.
     */
    public function testGetUserPassword()
    {
        $accountId = $this->createFakeData()->word;
        $userId = $this->createFakeData()->uuid;
        $userPassword = $this->createFakeData()->word;
        $container = $this->createFakeData()->word;

        $config = new MemoryConfig($accountId, $userId, $userPassword, $container);

        $this->assertSame($userPassword, $config->getUserPassword());
    }

    /**
     * Проверяет, что объект правильно возвращает имя контейнера.
     */
    public function testGetContainer()
    {
        $accountId = $this->createFakeData()->word;
        $userId = $this->createFakeData()->uuid;
        $userPassword = $this->createFakeData()->word;
        $container = $this->createFakeData()->word;

        $config = new MemoryConfig($accountId, $userId, $userPassword, $container);

        $this->assertSame($container, $config->getContainer());
    }

    /**
     * Проверяет, что объект правильно возвращает идентификатор аккаунта.
     */
    public function testGetAccountId()
    {
        $accountId = $this->createFakeData()->word;
        $userId = $this->createFakeData()->uuid;
        $userPassword = $this->createFakeData()->word;
        $container = $this->createFakeData()->word;

        $config = new MemoryConfig($accountId, $userId, $userPassword, $container);

        $this->assertSame($accountId, $config->getAccountId());
    }
}
