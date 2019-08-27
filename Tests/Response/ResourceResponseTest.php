<?php

declare(strict_types=1);

namespace Youtool\SelectelBundle\Tests\Response;

use Youtool\SelectelBundle\Tests\BaseCase;
use Youtool\SelectelBundle\Response\ResourceResponse;
use InvalidArgumentException;
use LogicException;

/**
 * Набор тестов для объекта, который формирует ответ из resource.
 */
class ResourceResponseTest extends BaseCase
{
    /**
     * Проверяет, что объект выбросит исключение, если в конструкторе не указан ресурс.
     */
    public function testConstructNonResourceException()
    {
        $this->expectException(InvalidArgumentException::class);
        new ResourceResponse('123');
    }

    /**
     * Проверяет, что ответ отдается верно.
     */
    public function testSendContent()
    {
        $text = $this->createFakeData()->text;

        $tmp = tmpfile();
        fwrite($tmp, $text);

        $response = new ResourceResponse($tmp);

        ob_start();
        ob_implicit_flush();
        $response->sendContent();
        $sendedResponse = ob_get_clean();

        ob_start();
        ob_implicit_flush();
        $response->sendContent();
        $nextResponse = ob_get_clean();

        $this->assertSame($text, $sendedResponse);
        $this->assertSame('', $nextResponse);
    }

    /**
     * Проверяет, что объект выбросит исключение, если не указан ресурс.
     */
    public function testSendContentEmptyResourceException()
    {
        $response = new ResourceResponse;

        $this->expectException(LogicException::class);
        $response->sendContent();
    }

    /**
     * Проверяет, что объект выбросит исключение, при попытке задать контент напрямую.
     */
    public function testSetContentException()
    {
        $response = new ResourceResponse;

        $this->expectException(LogicException::class);
        $response->setContent('123');
    }
}
