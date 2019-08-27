<?php

declare(strict_types=1);

namespace Youtool\SelectelBundle\Service;

use Youtool\SelectelBundle\Exception\TransportException;
use Youtool\SelectelBundle\Exception\AuthException;
use Youtool\SelectelBundle\Exception\UnexpectedResponseException;
use InvalidArgumentException;

/**
 * Интерфейс для объекта, который совершает запросы к API selectel.
 */
interface ServiceInterface
{
    /**
     * Загружает содержимое файла на сервер.
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function write(string $file, string $contents): void;

    /**
     * Загружает файл из потока на сервер.
     *
     * @param string   $file
     * @param resource $resource
     *
     * @throws TransportException
     * @throws AuthException
     * @throws InvalidArgumentException
     * @throws UnexpectedResponseException
     */
    public function writeStream(string $file, $resource): void;

    /**
     * Скачивает содержимое файла с сервера.
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function read(string $file): string;

    /**
     * Скачивает содержимое файла с сервера как поток.
     *
     * @return resource
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function readStream(string $file);

    /**
     * Удаляет файлы с сервера.
     *
     * @param string[] $files
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function delete(array $files): void;

    /**
     * Копирует файл.
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function copy(string $from, string $to): void;

    /**
     * Возвращает информацию об указанном файле.
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function fileInfo(string $file): ?array;

    /**
     * Возвращает список файлов, которые начинаются с указанной строки.
     *
     * @return string[][]
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function listMatched(string $matching): array;
}
