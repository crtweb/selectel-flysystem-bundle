<?php

declare(strict_types=1);

namespace Creative\SelectelBundle\AuthToken;

use Creative\SelectelBundle\Exception\AuthException;
use DateTime;
use Psr\Http\Message\ResponseInterface;

/**
 * Объект, который содержит токен авторизации на стороне API.
 */
class AuthToken implements AuthTokenInterface
{
    /**
     * @var string
     */
    protected $token;
    /**
     * @var array
     */
    protected $tokenDescription;

    /**
     * @throws AuthException
     */
    public function __construct(ResponseInterface $response)
    {
        $tokenHeader = $response->getHeader('X-Subject-Token');
        $this->token = reset($tokenHeader);
        $this->tokenDescription = $this->decodeJsonResponse($response);
    }

    /**
     * @inheritdoc
     */
    public function isValid(): bool
    {
        $isValid = false;

        if (!empty($this->tokenDescription['token']['expires_at'])) {
            $now = new DateTime;
            $expiredAt = DateTime::createFromFormat('Y-m-d\TH:i:s.uP', $this->tokenDescription['token']['expires_at']);
            $isValid = $now <= $expiredAt;
        }

        return $isValid;
    }

    /**
     * @inheritdoc
     */
    public function toHeader(): string
    {
        return $this->token;
    }

    /**
     * Преобразует ответ сервиса с json в ассоциативный массив.
     *
     * @throws AuthException
     */
    protected function decodeJsonResponse(ResponseInterface $response): array
    {
        $statusCode = (int) $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new AuthException("Authorization status code error. Code was: {$statusCode}. Expects: 20x.");
        }

        $body = $response->getBody()->getContents();
        if ($body === '') {
            throw new AuthException("Can't convert empty response to token.");
        }

        $decodedResponse = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new AuthException('Get json parsing error while parsing token.');
        }

        return $decodedResponse;
    }
}
