<?php

declare(strict_types=1);

namespace Youtool\SelectelBundle\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Youtool\SelectelBundle\Config\ConfigInterface;
use Youtool\SelectelBundle\Exception\TransportException;
use Youtool\SelectelBundle\Exception\AuthException;
use Youtool\SelectelBundle\Exception\UnexpectedResponseException;
use Youtool\SelectelBundle\AuthToken\AuthTokenInterface;
use Youtool\SelectelBundle\AuthToken\AuthToken;
use Psr\Http\Message\ResponseInterface;
use DateTime;
use Exception;
use InvalidArgumentException;

/**
 * Объект, который совершает запросы к API selectel.
 */
class SelectelService implements ServiceInterface
{
    /**
     * Объект, который совершает http запросы.
     *
     * @var ClientInterface
     */
    protected $transport;
    /**
     * Объект с настроуками для доступа к API selectel.
     *
     * @var ConfigInterface
     */
    protected $config;
    /**
     * Токен авторизации на сервисе.
     *
     * Если null, значит токен еще не получен.
     *
     * @var AuthTokenInterface|null
     */
    protected $authToken;

    public function __construct(ConfigInterface $config, ClientInterface $transport)
    {
        $this->config = $config;
        $this->transport = $transport;
    }

    /**
     * @inheritdoc
     */
    public function write(string $file, string $contents): void
    {
        $file = $this->normalizeFileName($file);
        $response = $this->requestAuthorized('put', $file, [
            RequestOptions::BODY => $contents,
        ]);

        if ($response->getStatusCode() !== 201) {
            throw new UnexpectedResponseException(
                'Expects 201 on write response, got: ' . $response->getStatusCode()
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function writeStream(string $file, $resource): void
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException(
                'Second parameter for writeStream must be a valid resource'
            );
        }

        $file = $this->normalizeFileName($file);
        $stream = \GuzzleHttp\Psr7\stream_for($resource);
        $response = $this->requestAuthorized('put', $file, [
            RequestOptions::BODY => $stream,
        ]);

        $stream->detach();

        if ($response->getStatusCode() !== 201) {
            throw new UnexpectedResponseException(
                'Expects 201 on writeStream response, got: ' . $response->getStatusCode()
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function read(string $file): string
    {
        $file = $this->normalizeFileName($file);

        $response = $this->requestAuthorized('get', $file);
        if ($response->getStatusCode() !== 200) {
            throw new UnexpectedResponseException(
                'Expects 200 on read response, got: ' . $response->getStatusCode()
            );
        }

        return $response->getBody()->getContents();
    }

    /**
     * @inheritdoc
     */
    public function readStream(string $file)
    {
        $file = $this->normalizeFileName($file);
        $temp = tmpfile();

        $stream = \GuzzleHttp\Psr7\stream_for($temp);
        $response = $this->requestAuthorized('get', $file, [
            RequestOptions::SINK => $stream,
        ]);
        $stream->detach();

        if ($response->getStatusCode() !== 200) {
            throw new UnexpectedResponseException(
                'Expects 200 on read response, got: ' . $response->getStatusCode()
            );
        }

        fseek($temp, 0);

        return $temp;
    }

    /**
     * @inheritdoc
     */
    public function copy(string $from, string $to): void
    {
        $from = $this->normalizeFileName($from);
        $to = $this->normalizeFileName($to);

        $response = $this->requestAuthorized('put', $to, [
            RequestOptions::HEADERS => [
                'X-Copy-From' => $this->config->getContainer() . '/' . ltrim($from, '/'),
            ],
        ]);
        if ($response->getStatusCode() !== 201) {
            throw new UnexpectedResponseException(
                'Expects 201 on copy response, got: ' . $response->getStatusCode()
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function delete(array $files): void
    {
        $container = $this->config->getContainer();
        $toDelete = [];
        foreach ($files as $file) {
            $toDelete[] = $container . '/' . $this->normalizeFileName($file);
        }

        $response = $this->requestAuthorized('post', 'v1/SEL_' . $this->config->getAccountId(), [
            RequestOptions::HEADERS => ['Content-Type' => 'text/plain'],
            RequestOptions::QUERY => ['bulk-delete' => 'true'],
            RequestOptions::BODY => implode("\n", $toDelete),
        ]);
        if ($response->getStatusCode() !== 200) {
            throw new UnexpectedResponseException(
                'Expects 200 on delete response, got: ' . $response->getStatusCode()
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function fileInfo(string $file): ?array
    {
        $fileInfo = null;
        $matched = $this->listMatched($file);

        if (count($matched) === 1) {
            $fileInfo = reset($matched);
        } elseif (count($matched) > 1) {
            $fileInfo = [
                'type' => 'dir',
                'path' => $this->normalizeFileName($file),
            ];
        }

        return $fileInfo;
    }

    /**
     * @inheritdoc
     */
    public function listMatched(string $matching): array
    {
        $matching = $this->normalizeFileName($matching);

        $response = $this->requestAuthorized('get', '', [
            RequestOptions::QUERY => [
                'format' => 'json',
                'prefix' => $matching,
            ],
        ]);
        if ($response->getStatusCode() !== 200) {
            throw new UnexpectedResponseException(
                'Expects 200 on delete response, got: ' . $response->getStatusCode()
            );
        }

        $rawList = $this->decodeJsonResponse($response);
        $list = [];
        foreach ($rawList as $li) {
            $list[] = $this->normilizeFileInfo($li);
        }

        return $list;
    }

    /**
     * Отправляет запрос на сервис с токеном авторизации.
     *
     * @throws TransportException
     * @throws AuthException
     */
    protected function requestAuthorized(string $method, string $command, array $options = []): ResponseInterface
    {
        if (!isset($options[RequestOptions::HEADERS])) {
            $options[RequestOptions::HEADERS] = [];
        }
        $options[RequestOptions::HEADERS]['X-Auth-Token'] = $this->getAuthToken()->toHeader();

        return $this->request($method, $command, $options);
    }

    /**
     * Возвращает токен авторизации, при необходимости получает новый.
     *
     * @throws TransportException
     * @throws AuthException
     */
    protected function getAuthToken(): AuthTokenInterface
    {
        if (!($this->authToken instanceof AuthTokenInterface) || !$this->authToken->isValid()) {
            $response = $this->request('post', 'v3/auth/tokens', [
                RequestOptions::JSON => [
                    'auth' => [
                        'identity' => [
                            'methods' => ['password'],
                            'password' => [
                                'user' => [
                                    'id' => $this->config->getUserId(),
                                    'password' => $this->config->getUserPassword(),
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            $this->authToken = new AuthToken($response);
        }

        return $this->authToken;
    }

    /**
     * Отправляет запрос на сервис.
     *
     * @throws TransportException
     */
    protected function request(string $method, string $command, array $options = []): ResponseInterface
    {
        $url = $this->getCommandUrl($command);

        try {
            $requestResult = $this->transport->request(strtoupper($method), $url, $options);
        } catch (Exception $e) {
            throw new TransportException("Error while requesting {$url}.", 0, $e);
        }

        return $requestResult;
    }

    /**
     * Возвращает ссылку на эндпоинт джля указанной команды.
     */
    protected function getCommandUrl(string $command): string
    {
        $apiHost = $this->config->getApiHost();
        if (!preg_match('/^v\d+.*$/', $command)) {
            $prefix = 'v1/SEL_' . $this->config->getAccountId() . '/' . $this->config->getContainer();
            $command = rtrim($prefix . '/' . ltrim($command, '/'), '/');
        }

        return "{$apiHost}/{$command}";
    }

    /**
     * Преобразует ответ сервиса с json в ассоциативный массив.
     *
     * @throws TransportException
     */
    protected function decodeJsonResponse(ResponseInterface $response): array
    {
        $body = $response->getBody()->getContents();
        if ($body === '') {
            throw new TransportException("Can't convert empty response to token.");
        }

        $decodedResponse = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new TransportException('Get json parsing error while parsing token.');
        }

        return $decodedResponse;
    }

    /**
     * Приводит имена файлов к общему унифицированному виду.
     */
    protected function normalizeFileName(string $fileName): string
    {
        $normalizedName = trim($fileName);
        $normalizedName = str_replace('\\', '/', $normalizedName);
        $normalizedName = preg_replace('#/{2,}#', '/', $normalizedName);
        $normalizedName = trim($normalizedName, '/');

        return $normalizedName;
    }

    /**
     * Приводит информацию о файле к общему унифицированному виду.
     */
    protected function normilizeFileInfo(array $fileInfo): array
    {
        $date = isset($fileInfo['last_modified'])
            ? DateTime::createFromFormat('Y-m-d\TH:i:s.u', $fileInfo['last_modified'])
            : false;

        return [
            'type' => 'file',
            'path' => $fileInfo['name'] ?? '',
            'timestamp' => $date ? $date->getTimestamp() : 0,
            'size' => $fileInfo['bytes'] ?? 0,
            'mimetype' => $fileInfo['content_type'] ?? 'application/octet-stream',
        ];
    }
}
