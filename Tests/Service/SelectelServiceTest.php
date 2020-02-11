<?php

declare(strict_types=1);

namespace Creative\SelectelBundle\Tests\Service;

use Creative\SelectelBundle\Config\ConfigInterface;
use Creative\SelectelBundle\Config\MemoryConfig;
use Creative\SelectelBundle\Exception\UnexpectedResponseException;
use Creative\SelectelBundle\Service\SelectelService;
use Creative\SelectelBundle\Tests\BaseCase;
use DateTime;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

/**
 * Набор тестов для объекта, который предоставляет доступ к API selectel.
 */
class SelectelServiceTest extends BaseCase
{
    /**
     * Проверяет, что объект верно записывает файл на сервис.
     */
    public function testWrite()
    {
        $file = '/path//to\file.txt';
        $fileNormalized = 'path/to/file.txt';
        $content = 'file contents';

        $config = $this->createConfig();
        $transport = $this->createTransport($config, [
            [
                'method' => 'put',
                'url' => $config->getApiHost() . '/v1/SEL_' . $config->getAccountId() . '/' . $config->getContainer() . '/' . $fileNormalized,
                'options' => [
                    RequestOptions::BODY => $content,
                    RequestOptions::HEADERS => ['X-Auth-Token' => 'token'],
                ],
                'response' => new Psr7\Response(201),
            ],
        ]);

        $service = new SelectelService($config, $transport);
        $service->write($file, $content);
    }

    /**
     * Проверяет, что объект выбросит исключение, если статус ответа не 201.
     */
    public function testWriteBadStatusException()
    {
        $file = '/path//to\file.txt';
        $fileNormalized = 'path/to/file.txt';
        $content = 'file contents';

        $config = $this->createConfig();
        $transport = $this->createTransport($config, [
            [
                'method' => 'put',
                'url' => $config->getApiHost() . '/v1/SEL_' . $config->getAccountId() . '/' . $config->getContainer() . '/' . $fileNormalized,
                'options' => [
                    RequestOptions::BODY => $content,
                    RequestOptions::HEADERS => ['X-Auth-Token' => 'token'],
                ],
                'response' => new Psr7\Response(500),
            ],
        ]);

        $service = new SelectelService($config, $transport);

        $this->expectException(UnexpectedResponseException::class);
        $service->write($file, $content);
    }

    /**
     * Проверяет, что объект верно записывает поток на сервис.
     */
    public function testWriteStream()
    {
        $file = '/path//to\file.txt';
        $fileNormalized = 'path/to/file.txt';
        $content = tmpfile();

        $config = $this->createConfig();
        $transport = $this->getMockBuilder(ClientInterface::class)->getMock();

        $transport->expects($this->at(0))->method('request')->will($this->returnCallback(function ($method, $url, $options) use ($config) {
            $goodResponse = new Psr7\Response(
                200,
                ['X-Subject-Token' => ['token']],
                json_encode(['token' => ['expires_at' => (new DateTime('+1 hour'))->format('Y-m-d\TH:i:s.uP')]])
            );
            $badResponse = new Psr7\Response(500);
            $isSuites = strtolower($method) === 'post'
                && $url === $config->getApiHost() . '/v3/auth/tokens';

            return $isSuites ? $goodResponse : $badResponse;
        }));

        $transport->expects($this->at(1))->method('request')->will($this->returnCallback(function ($method, $url, $options) use ($config, $fileNormalized) {
            $goodResponse = new Psr7\Response(201);
            $badResponse = new Psr7\Response(500);
            $isSuites = strtolower($method) === 'put'
                && $url === $config->getApiHost() . '/v1/SEL_' . $config->getAccountId() . '/' . $config->getContainer() . '/' . $fileNormalized
                && !empty($options[RequestOptions::HEADERS]['X-Auth-Token'])
                && $options[RequestOptions::HEADERS]['X-Auth-Token'] === 'token'
                && !empty($options[RequestOptions::BODY])
                && $options[RequestOptions::BODY] instanceof StreamInterface;

            return $isSuites ? $goodResponse : $badResponse;
        }));

        $service = new SelectelService($config, $transport);
        $service->writeStream($file, $content);
    }

    /**
     * Проверяет, что объект выбросит исключение, если статус ответа не 201.
     */
    public function testWriteStreamBadStatusException()
    {
        $file = '/path//to\file.txt';
        $fileNormalized = 'path/to/file.txt';
        $content = tmpfile();

        $config = $this->createConfig();

        $transport = $this->getMockBuilder(ClientInterface::class)->getMock();
        $transport->expects($this->at(0))->method('request')->will($this->returnCallback(function ($method, $url, $options) use ($config) {
            $goodResponse = new Psr7\Response(
                200,
                ['X-Subject-Token' => ['token']],
                json_encode(['token' => ['expires_at' => (new DateTime('+1 hour'))->format('Y-m-d\TH:i:s.uP')]])
            );
            $badResponse = new Psr7\Response(500);
            $isSuites = strtolower($method) === 'post'
                && $url === $config->getApiHost() . '/v3/auth/tokens';

            return $isSuites ? $goodResponse : $badResponse;
        }));
        $transport->expects($this->at(1))->method('request')->will($this->returnValue(new Psr7\Response(500)));

        $service = new SelectelService($config, $transport);

        $this->expectException(UnexpectedResponseException::class);
        $service->writeStream($file, $content);
    }

    /**
     * Проверяет, что объект выбросит исключение,
     * если в качестве параметра передан не resource.
     */
    public function testWriteStreamNotStreamException()
    {
        $config = $this->createConfig();
        $transport = $this->getMockBuilder(ClientInterface::class)->getMock();

        $service = new SelectelService($config, $transport);

        $this->expectException(InvalidArgumentException::class);
        $service->writeStream('/file.txt', 123123);
    }

    /**
     * Проверяет, что объект правильно читает файл с сервиса.
     */
    public function testRead()
    {
        $file = '/path//to\file.txt';
        $fileNormalized = 'path/to/file.txt';
        $contents = $this->createFakeData()->text;

        $config = $this->createConfig();
        $transport = $this->createTransport($config, [
            [
                'method' => 'get',
                'url' => $config->getApiHost() . '/v1/SEL_' . $config->getAccountId() . '/' . $config->getContainer() . '/' . $fileNormalized,
                'options' => [
                    RequestOptions::HEADERS => ['X-Auth-Token' => 'token'],
                ],
                'response' => new Psr7\Response(200, [], $contents),
            ],
        ]);

        $service = new SelectelService($config, $transport);

        $this->assertSame($contents, $service->read($file));
    }

    /**
     * Проверяет, что объект выбосит исключени, если пришел неверный статус ответа.
     */
    public function testReadBadStatusException()
    {
        $file = '/path//to\file.txt';
        $fileNormalized = 'path/to/file.txt';
        $contents = $this->createFakeData()->text;

        $config = $this->createConfig();
        $transport = $this->createTransport($config, [
            [
                'method' => 'get',
                'url' => $config->getApiHost() . '/v1/SEL_' . $config->getAccountId() . '/' . $config->getContainer() . '/' . $fileNormalized,
                'options' => [
                    RequestOptions::HEADERS => ['X-Auth-Token' => 'token'],
                ],
                'response' => new Psr7\Response(500, [], $contents),
            ],
        ]);

        $service = new SelectelService($config, $transport);

        $this->expectException(UnexpectedResponseException::class);
        $service->read($file);
    }

    /**
     * Проверяет, что объект правильно читает файл с сервиса в поток.
     */
    public function testReadStream()
    {
        $file = '/path//to\file.txt';
        $fileNormalized = 'path/to/file.txt';

        $config = $this->createConfig();
        $transport = $this->getMockBuilder(ClientInterface::class)->getMock();

        $transport->expects($this->at(0))->method('request')->will($this->returnCallback(function ($method, $url, $options) use ($config) {
            $goodResponse = new Psr7\Response(
                200,
                ['X-Subject-Token' => ['token']],
                json_encode(['token' => ['expires_at' => (new DateTime('+1 hour'))->format('Y-m-d\TH:i:s.uP')]])
            );
            $badResponse = new Psr7\Response(500);
            $isSuites = strtolower($method) === 'post'
                && $url === $config->getApiHost() . '/v3/auth/tokens';

            return $isSuites ? $goodResponse : $badResponse;
        }));

        $transport->expects($this->at(1))->method('request')->will($this->returnCallback(function ($method, $url, $options) use ($config, $fileNormalized) {
            $goodResponse = new Psr7\Response(200);
            $badResponse = new Psr7\Response(500);
            $isSuites = strtolower($method) === 'get'
                && $url === $config->getApiHost() . '/v1/SEL_' . $config->getAccountId() . '/' . $config->getContainer() . '/' . $fileNormalized
                && !empty($options[RequestOptions::SINK])
                && $options[RequestOptions::SINK] instanceof StreamInterface;

            return $isSuites ? $goodResponse : $badResponse;
        }));

        $service = new SelectelService($config, $transport);
        $service->readStream($file);
    }

    /**
     * Проверяет, что объект выбосит исключени, если пришел неверный статус ответа.
     */
    public function testReadStreamBadStatusException()
    {
        $file = '/path//to\file.txt';
        $fileNormalized = 'path/to/file.txt';
        $contents = $this->createFakeData()->text;

        $config = $this->createConfig();
        $transport = $this->getMockBuilder(ClientInterface::class)->getMock();
        $transport->expects($this->at(0))->method('request')->will($this->returnCallback(function ($method, $url, $options) use ($config) {
            $goodResponse = new Psr7\Response(
                200,
                ['X-Subject-Token' => ['token']],
                json_encode(['token' => ['expires_at' => (new DateTime('+1 hour'))->format('Y-m-d\TH:i:s.uP')]])
            );
            $badResponse = new Psr7\Response(500);
            $isSuites = strtolower($method) === 'post'
                && $url === $config->getApiHost() . '/v3/auth/tokens';

            return $isSuites ? $goodResponse : $badResponse;
        }));
        $transport->expects($this->at(1))->method('request')->will($this->returnValue(new Psr7\Response(500)));

        $service = new SelectelService($config, $transport);

        $this->expectException(UnexpectedResponseException::class);
        $service->readStream($file);
    }

    /**
     * Проверяет, что объект копирует файлы на сервисе.
     */
    public function testCopy()
    {
        $from = '/path//to\from.txt';
        $fromNormalized = 'path/to/from.txt';
        $to = '/path//to\to.txt';
        $toNormalized = 'path/to/to.txt';

        $config = $this->createConfig();
        $transport = $this->createTransport($config, [
            [
                'method' => 'put',
                'url' => $config->getApiHost() . '/v1/SEL_' . $config->getAccountId() . '/' . $config->getContainer() . '/' . $toNormalized,
                'options' => [
                    RequestOptions::HEADERS => [
                        'X-Copy-From' => $config->getContainer() . '/' . $fromNormalized,
                        'X-Auth-Token' => 'token',
                    ],
                ],
                'response' => new Psr7\Response(201),
            ],
        ]);

        $service = new SelectelService($config, $transport);

        $service->copy($from, $to);
    }

    /**
     * Проверяет, что объект выбосит исключени, если пришел неверный статус ответа.
     */
    public function testCopyBadStatusException()
    {
        $from = '/path//to\from.txt';
        $fromNormalized = 'path/to/from.txt';
        $to = '/path//to\to.txt';
        $toNormalized = 'path/to/to.txt';

        $config = $this->createConfig();
        $transport = $this->createTransport($config, [
            [
                'method' => 'put',
                'url' => $config->getApiHost() . '/v1/SEL_' . $config->getAccountId() . '/' . $config->getContainer() . '/' . $toNormalized,
                'options' => [
                    RequestOptions::HEADERS => [
                        'X-Copy-From' => $config->getContainer() . '/' . $fromNormalized,
                        'X-Auth-Token' => 'token',
                    ],
                ],
                'response' => new Psr7\Response(500),
            ],
        ]);

        $service = new SelectelService($config, $transport);

        $this->expectException(UnexpectedResponseException::class);
        $service->copy($from, $to);
    }

    /**
     * Проверяет, что объект удаляет файлы на сервисе.
     */
    public function testDelete()
    {
        $config = $this->createConfig();
        $file1 = '/path//to\from.txt';
        $file1Normalized = $config->getContainer() . '/path/to/from.txt';
        $file2 = '/path//to\to.txt';
        $file2Normalized = $config->getContainer() . '/path/to/to.txt';

        $transport = $this->createTransport($config, [
            [
                'method' => 'post',
                'url' => $config->getApiHost() . '/v1/SEL_' . $config->getAccountId(),
                'options' => [
                    RequestOptions::HEADERS => ['Content-Type' => 'text/plain', 'X-Auth-Token' => 'token'],
                    RequestOptions::QUERY => ['bulk-delete' => 'true'],
                    RequestOptions::BODY => implode("\n", [$file1Normalized, $file2Normalized]),
                ],
                'response' => new Psr7\Response(200),
            ],
        ]);

        $service = new SelectelService($config, $transport);

        $service->delete([$file1, $file2]);
    }

    /**
     * Проверяет, что объект выбосит исключени, если пришел неверный статус ответа.
     */
    public function testDeleteBasStatusException()
    {
        $config = $this->createConfig();
        $file1 = '/path//to\from.txt';
        $file1Normalized = $config->getContainer() . '/path/to/from.txt';
        $file2 = '/path//to\to.txt';
        $file2Normalized = $config->getContainer() . '/path/to/to.txt';

        $transport = $this->createTransport($config, [
            [
                'method' => 'post',
                'url' => $config->getApiHost() . '/v1/SEL_' . $config->getAccountId(),
                'options' => [
                    RequestOptions::HEADERS => ['Content-Type' => 'text/plain', 'X-Auth-Token' => 'token'],
                    RequestOptions::QUERY => ['bulk-delete' => 'true'],
                    RequestOptions::BODY => implode("\n", [$file1Normalized, $file2Normalized]),
                ],
                'response' => new Psr7\Response(400),
            ],
        ]);

        $service = new SelectelService($config, $transport);

        $this->expectException(UnexpectedResponseException::class);
        $service->delete([$file1, $file2]);
    }

    /**
     * Проверяет, что объект возвращает информацию о файле.
     */
    public function testFileInfo()
    {
        $date = new DateTime();
        $file = '/path//to\from.txt';
        $fileNormalized = 'path/to/from.txt';
        $fileResponse = [
            'name' => $fileNormalized,
            'last_modified' => $date->format('Y-m-d\TH:i:s.u'),
            'bytes' => 10,
            'content_type' => 'application/octet-stream',
        ];

        $config = $this->createConfig();
        $transport = $this->createTransport($config, [
            [
                'method' => 'get',
                'url' => $config->getApiHost() . '/v1/SEL_' . $config->getAccountId() . '/' . $config->getContainer(),
                'options' => [
                    RequestOptions::HEADERS => ['X-Auth-Token' => 'token'],
                    RequestOptions::QUERY => [
                        'format' => 'json',
                        'prefix' => $fileNormalized,
                    ],
                ],
                'response' => new Psr7\Response(200, [], json_encode([$fileResponse])),
            ],
        ]);

        $service = new SelectelService($config, $transport);
        $fileInfo = [
            'type' => 'file',
            'path' => $fileNormalized,
            'timestamp' => $date->getTimestamp(),
            'size' => $fileResponse['bytes'],
            'mimetype' => $fileResponse['content_type'],
        ];
        $fileInfoToTest = $service->fileInfo($file);
        ksort($fileInfo);
        ksort($fileInfoToTest);

        $this->assertSame($fileInfo, $fileInfoToTest);
    }

    /**
     * Проверяет, что объект возвращает информацию о папке.
     */
    public function testFileInfoForDir()
    {
        $date = new DateTime();
        $file = '/path//to\\';
        $fileNormalized = 'path/to';
        $fileResponse = [
            'name' => $fileNormalized,
            'last_modified' => $date->format('Y-m-d\TH:i:s.u'),
            'bytes' => 10,
            'content_type' => 'application/octet-stream',
        ];

        $config = $this->createConfig();
        $transport = $this->createTransport($config, [
            [
                'method' => 'get',
                'url' => $config->getApiHost() . '/v1/SEL_' . $config->getAccountId() . '/' . $config->getContainer(),
                'options' => [
                    RequestOptions::HEADERS => ['X-Auth-Token' => 'token'],
                    RequestOptions::QUERY => [
                        'format' => 'json',
                        'prefix' => $fileNormalized,
                    ],
                ],
                'response' => new Psr7\Response(200, [], json_encode([$fileResponse, $fileResponse])),
            ],
        ]);

        $service = new SelectelService($config, $transport);
        $fileInfo = [
            'type' => 'dir',
            'path' => $fileNormalized,
        ];
        $fileInfoToTest = $service->fileInfo($file);
        ksort($fileInfo);
        ksort($fileInfoToTest);

        $this->assertSame($fileInfo, $fileInfoToTest);
    }

    /**
     * Проверяет, что объект выбосит исключени, если пришел неверный статус ответа.
     */
    public function testFileInfoBadResponseException()
    {
        $file = '/path//to\from.txt';
        $fileNormalized = 'path/to/from.txt';

        $config = $this->createConfig();
        $transport = $this->createTransport($config, [
            [
                'method' => 'get',
                'url' => $config->getApiHost() . '/v1/SEL_' . $config->getAccountId() . '/' . $config->getContainer(),
                'options' => [
                    RequestOptions::HEADERS => ['X-Auth-Token' => 'token'],
                    RequestOptions::QUERY => [
                        'format' => 'json',
                        'prefix' => $fileNormalized,
                    ],
                ],
                'response' => new Psr7\Response(500),
            ],
        ]);

        $service = new SelectelService($config, $transport);

        $this->expectException(UnexpectedResponseException::class);
        $fileInfoToTest = $service->fileInfo($file);
    }

    /**
     * Строит объект транспорта по указанному массиву.
     */
    protected function createTransport(ConfigInterface $config, array $requests): ClientInterface
    {
        $requests = array_merge([
            'auth' => [
                'method' => 'post',
                'url' => $config->getApiHost() . '/v3/auth/tokens',
                'options' => [
                    RequestOptions::JSON => [
                        'auth' => [
                            'identity' => [
                                'methods' => ['password'],
                                'password' => [
                                    'user' => [
                                        'id' => $config->getUserId(),
                                        'password' => $config->getUserPassword(),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'response' => new Psr7\Response(
                    200,
                    ['X-Subject-Token' => ['token']],
                    json_encode(['token' => ['expires_at' => (new DateTime('+1 hour'))->format('Y-m-d\TH:i:s.uP')]])
                ),
            ],
        ], $requests);

        $transport = $this->getMockBuilder(ClientInterface::class)->getMock();
        $transport->expects($this->atLeastOnce())->method('request')->will($this->returnCallback(function ($method, $url, $options) use ($requests, $config) {
            $response = null;
            $method = strtolower($method);
            $url = strtolower(trim($url, '/ '));
            ksort($options);
            foreach ($requests as $request) {
                $requestMethod = strtolower($request['method']);
                $requestUrl = strtolower(trim($request['url'], '/ '));
                $requestOptions = $request['options'];
                ksort($requestOptions);
                if ($method === $requestMethod && $url === $requestUrl && $options === $requestOptions) {
                    $response = $request['response'];
                    break;
                }
            }

            if (!$response) {
                throw new InvalidArgumentException('Bad request');
            }

            return $response;
        }));

        return $transport;
    }

    /**
     * Задает объект конфига перед тестированием.
     */
    protected function createConfig(): ConfigInterface
    {
        $accountId = '345345';

        return new MemoryConfig(
            $accountId,
            "{$accountId}_" . $this->createFakeData()->word,
            $this->createFakeData()->word,
            $this->createFakeData()->word,
            'https://test.test'
        );
    }
}
