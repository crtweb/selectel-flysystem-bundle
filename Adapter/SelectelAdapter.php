<?php

declare(strict_types=1);

namespace Creative\SelectelBundle\Adapter;

use Creative\SelectelBundle\Service\ServiceInterface;
use Creative\SelectelBundle\Exception\TransportException;
use Creative\SelectelBundle\Exception\AuthException;
use Creative\SelectelBundle\Exception\UnexpectedResponseException;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Util;

/**
 * Адаптер selectel для flysystem.
 */
class SelectelAdapter implements AdapterInterface
{
    /**
     * Сервис для доступа к selectel.
     *
     * @var ServiceInterface
     */
    protected $service;

    public function __construct(ServiceInterface $service)
    {
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function write($path, $contents, Config $config)
    {
        $this->service->write($path, $contents);

        return [
            'type' => 'file',
            'size' => Util::contentSize($contents),
            'contents' => $contents,
            'path' => $path,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function writeStream($path, $resource, Config $config)
    {
        $this->service->writeStream($path, $resource);

        return [
            'type' => 'file',
            'size' => Util::getStreamSize($resource),
            'path' => $path,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * {@inheritdoc}
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * {@inheritdoc}
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function rename($path, $newpath)
    {
        $this->service->copy($path, $newpath);
        $this->service->delete([$path]);

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function copy($path, $newpath)
    {
        $this->service->copy($path, $newpath);

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function delete($path)
    {
        $this->service->delete([$path]);

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function deleteDir($path)
    {
        $list = $this->service->listMatched($path);

        $toDelete = [];
        foreach ($list as $li) {
            $toDelete[] = $li['path'];
        }

        $this->service->delete($toDelete);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function createDir($dirname, Config $config)
    {
        return ['path' => $dirname, 'type' => 'dir'];
    }

    /**
     * @inheritdoc
     */
    public function setVisibility($path, $visibility)
    {
        return ['path' => $path, 'visibility' => $visibility];
    }

    /**
     * {@inheritdoc}
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function has($path)
    {
        return $this->service->fileInfo($path) !== null;
    }

    /**
     * {@inheritdoc}
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function read($path)
    {
        return [
            'contents' => $this->service->read($path),
            'type' => 'file',
            'path' => $path,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function readStream($path)
    {
        return [
            'stream' => $this->service->readStream($path),
            'type' => 'file',
            'path' => $path,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function listContents($directory = '', $recursive = false)
    {
        return $this->service->listMatched($directory);
    }

    /**
     * {@inheritdoc}
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function getMetadata($path)
    {
        return $this->service->fileInfo($path) ?: false;
    }

    /**
     * {@inheritdoc}
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     *
     * @throws TransportException
     * @throws AuthException
     * @throws UnexpectedResponseException
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * @inheritdoc
     */
    public function getVisibility($path)
    {
        return false;
    }
}
