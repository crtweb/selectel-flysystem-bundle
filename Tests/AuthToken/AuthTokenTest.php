<?php

declare(strict_types=1);

namespace Creative\SelectelBundle\Tests\AuthToken;

use Creative\SelectelBundle\AuthToken\AuthToken;
use Creative\SelectelBundle\Exception\AuthException;
use Creative\SelectelBundle\Tests\BaseCase;
use DateTime;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Набор тестов для объекта, который содержит токен доступа к selectel.
 */
class AuthTokenTest extends BaseCase
{
    /**
     * Проверяет, что объект выбросит исключение, если в ответе не 200 статус.
     */
    public function testConstructBadStatusException()
    {
        $response = $this->createResponse(400);

        $this->expectException(AuthException::class);
        new AuthToken($response);
    }

    /**
     * Проверяет, что объект выбросит исключение, если ответ пуст.
     */
    public function testConstructEmptyBodyException()
    {
        $response = $this->createResponse(null, null, '');

        $this->expectException(AuthException::class);
        new AuthToken($response);
    }

    /**
     * Проверяет, что объект выбросит исключение, если в ответе невалидный json.
     */
    public function testConstructBodyDecodingException()
    {
        $response = $this->createResponse(null, null, '{test');

        $this->expectException(AuthException::class);
        new AuthToken($response);
    }

    /**
     * Проверяет, что токен еще валиден.
     */
    public function testIsValid()
    {
        $tokenCode = $this->createFakeData()->word;
        $response = $this->createResponse(null, $tokenCode, [
            'token' => ['expires_at' => (new DateTime('+ 1 hour'))->format('Y-m-d\TH:i:s.uP')],
        ]);

        $token = new AuthToken($response);

        $this->assertTrue($token->isValid());
    }

    /**
     * Проверяет, что токен возвращает верную последовательность для авторизации.
     */
    public function testToHeader()
    {
        $tokenCode = $this->createFakeData()->word;
        $response = $this->createResponse(null, $tokenCode);

        $token = new AuthToken($response);

        $this->assertSame($tokenCode, $token->toHeader());
    }

    /**
     * Созадает объект с ответом, в котором содержится токен.
     */
    protected function createResponse($status = null, $headers = null, $bodyData = null): ResponseInterface
    {
        if ($status === null) {
            $status = 200;
        }
        if ($headers === null) {
            $headers = ['X-Subject-Token' => [$this->createFakeData()->word]];
        } elseif (is_string($headers)) {
            $headers = ['X-Subject-Token' => [$headers]];
        }
        if ($bodyData === null) {
            $bodyData = ['token' => []];
        }

        $body = $this->getMockBuilder(StreamInterface::class)->getMock();
        $body->method('getContents')->will(
            $this->returnValue(is_string($bodyData) ? $bodyData : json_encode($bodyData))
        );

        $response = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $response->method('getHeader')->will($this->returnCallback(function ($headerName) use ($headers) {
            return isset($headers[$headerName]) ? $headers[$headerName] : [];
        }));
        $response->method('getStatusCode')->will($this->returnValue($status));
        $response->method('getBody')->will($this->returnValue($body));

        return $response;
    }
}
