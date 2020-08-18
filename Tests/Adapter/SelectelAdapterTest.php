<?php

declare(strict_types=1);

namespace Creative\SelectelBundle\Tests\Adapter;

use Creative\SelectelBundle\Adapter\SelectelAdapter;
use Creative\SelectelBundle\Service\ServiceInterface;
use Creative\SelectelBundle\Tests\BaseCase;
use League\Flysystem\Config;

/**
 * Набор тестов для адаптера selectel для flysystem.
 */
class SelectelAdapterTest extends BaseCase
{
    /**
     * Проверяет, что адаптер записывает содержимое файла.
     */
    public function testWrite()
    {
        $path = '/' . $this->createFakeData()->word . '.txt';
        $content = 'test text';
        $config = $this->getMockBuilder(Config::class)->getMock();

        $service = $this->getMockBuilder(ServiceInterface::class)->getMock();
        $service->expects($this->once())->method('write')->with(
            $this->equalTo($path),
            $this->equalTo($content)
        );

        $adapter = new SelectelAdapter($service);

        $expected = ['type' => 'file', 'size' => 9, 'contents' => $content, 'path' => $path];
        $test = $adapter->write($path, $content, $config);
        ksort($expected);
        ksort($test);

        $this->assertSame($expected, $test);
    }

    /**
     * Проверяет, что адаптер записывает файл из потока.
     */
    public function testWriteStream()
    {
        $path = '/' . $this->createFakeData()->word . '.txt';
        $stream = tmpfile();
        fwrite($stream, 'test text');
        fseek($stream, 0);
        $config = $this->getMockBuilder(Config::class)->getMock();

        $service = $this->getMockBuilder(ServiceInterface::class)->getMock();
        $service->expects($this->once())->method('writeStream')->with(
            $this->equalTo($path),
            $this->equalTo($stream)
        );

        $adapter = new SelectelAdapter($service);

        $expected = ['type' => 'file', 'size' => 9, 'path' => $path];
        $test = $adapter->writeStream($path, $stream, $config);
        ksort($expected);
        ksort($test);

        $this->assertSame($expected, $test);

        fclose($stream);
    }

    /**
     * Проверяет, что адаптер обновляет содержимое файла.
     */
    public function testUpdate()
    {
        $path = '/' . $this->createFakeData()->word . '.txt';
        $content = 'test text';
        $config = $this->getMockBuilder(Config::class)->getMock();

        $service = $this->getMockBuilder(ServiceInterface::class)->getMock();
        $service->expects($this->once())->method('write')->with(
            $this->equalTo($path),
            $this->equalTo($content)
        );

        $adapter = new SelectelAdapter($service);

        $expected = ['type' => 'file', 'size' => 9, 'contents' => $content, 'path' => $path];
        $test = $adapter->update($path, $content, $config);
        ksort($expected);
        ksort($test);

        $this->assertSame($expected, $test);
    }

    /**
     * Проверяет, что адаптер обновляет файл из потока.
     */
    public function testUpdateStream()
    {
        $path = '/' . $this->createFakeData()->word . '.txt';
        $stream = tmpfile();
        fwrite($stream, 'test text');
        fseek($stream, 0);
        $config = $this->getMockBuilder(Config::class)->getMock();

        $service = $this->getMockBuilder(ServiceInterface::class)->getMock();
        $service->expects($this->once())->method('writeStream')->with(
            $this->equalTo($path),
            $this->equalTo($stream)
        );

        $adapter = new SelectelAdapter($service);

        $expected = ['type' => 'file', 'size' => 9, 'path' => $path];
        $test = $adapter->updateStream($path, $stream, $config);
        ksort($expected);
        ksort($test);

        $this->assertSame($expected, $test);

        fclose($stream);
    }

    /**
     * Проверяет, что адаптер переименовывает файл.
     */
    public function testRename()
    {
        $path = '/' . $this->createFakeData()->word . '.txt';
        $newPath = '/new_' . $this->createFakeData()->word . '.txt';
        $config = $this->getMockBuilder(Config::class)->getMock();

        $service = $this->getMockBuilder(ServiceInterface::class)->getMock();
        $service->expects($this->once())->method('copy')->with(
            $this->equalTo($path),
            $this->equalTo($newPath)
        );
        $service->expects($this->once())->method('deleteOne')->with($this->equalTo($path));

        $adapter = new SelectelAdapter($service);

        $this->assertTrue($adapter->rename($path, $newPath));
    }

    /**
     * Проверяет, что адаптер копирует файл.
     */
    public function testCopy()
    {
        $path = '/' . $this->createFakeData()->word . '.txt';
        $newPath = '/new_' . $this->createFakeData()->word . '.txt';
        $config = $this->getMockBuilder(Config::class)->getMock();

        $service = $this->getMockBuilder(ServiceInterface::class)->getMock();
        $service->expects($this->once())->method('copy')->with(
            $this->equalTo($path),
            $this->equalTo($newPath)
        );

        $adapter = new SelectelAdapter($service);

        $this->assertTrue($adapter->copy($path, $newPath));
    }

    /**
     * Проверяет, что адаптер удаляет файл.
     */
    public function testDelete()
    {
        $path = '/' . $this->createFakeData()->word . '.txt';
        $config = $this->getMockBuilder(Config::class)->getMock();

        $service = $this->getMockBuilder(ServiceInterface::class)->getMock();
        $service->expects($this->once())->method('deleteOne')->with($this->equalTo($path));

        $adapter = new SelectelAdapter($service);

        $this->assertTrue($adapter->delete($path));
    }

    /**
     * Проверяет, что адаптер удаляет папку.
     */
    public function testDeleteDir()
    {
        $path = '/' . $this->createFakeData()->word;
        $files = [
            '/1_' . $this->createFakeData()->word . '.txt',
            '/2_' . $this->createFakeData()->word . '.txt',
            '/3_' . $this->createFakeData()->word . '.txt',
        ];
        $list = [];
        foreach ($files as $file) {
            $list[] = ['path' => $file];
        }
        $config = $this->getMockBuilder(Config::class)->getMock();

        $service = $this->getMockBuilder(ServiceInterface::class)->getMock();
        $service->expects($this->once())->method('listMatched')
            ->with($this->equalTo($path))
            ->will($this->returnValue($list));
        $service->expects($this->once())->method('delete')->with($this->equalTo($files));

        $adapter = new SelectelAdapter($service);

        $this->assertTrue($adapter->deleteDir($path));
    }

    /**
     * Проверяет, что адаптер создает папку.
     */
    public function testCreateDir()
    {
        $path = '/' . $this->createFakeData()->word;
        $config = $this->getMockBuilder(Config::class)->getMock();
        $service = $this->getMockBuilder(ServiceInterface::class)->getMock();

        $adapter = new SelectelAdapter($service);

        $expected = ['type' => 'dir', 'path' => $path];
        $test = $adapter->createDir($path, $config);
        ksort($expected);
        ksort($test);

        $this->assertSame($expected, $test);
    }

    /**
     * Проверяет, что адаптер устанавливает доступность файла.
     */
    public function testSetVisibility()
    {
        $path = '/' . $this->createFakeData()->word;
        $visibility = $this->createFakeData()->word;
        $service = $this->getMockBuilder(ServiceInterface::class)->getMock();

        $adapter = new SelectelAdapter($service);

        $expected = ['visibility' => $visibility, 'path' => $path];
        $test = $adapter->setVisibility($path, $visibility);
        ksort($expected);
        ksort($test);

        $this->assertSame($expected, $test);
    }

    /**
     * Проверяет, что адаптер проверяет наличие файла.
     */
    public function testHas()
    {
        $path1 = '/1_' . $this->createFakeData()->word . '.txt';
        $path2 = '/2_' . $this->createFakeData()->word . '.txt';

        $service = $this->getMockBuilder(ServiceInterface::class)->getMock();
        $service->method('fileInfo')->will($this->returnCallback(function ($path) use ($path1) {
            return $path === $path1 ? ['type' => 'file', 'path' => $path1] : null;
        }));

        $adapter = new SelectelAdapter($service);

        $this->assertTrue($adapter->has($path1));
        $this->assertFalse($adapter->has($path2));
    }

    /**
     * Проверяет, что адаптер читает содержимое файла.
     */
    public function testRead()
    {
        $path = '/' . $this->createFakeData()->word . '.txt';
        $content = 'test text';

        $service = $this->getMockBuilder(ServiceInterface::class)->getMock();
        $service->expects($this->once())->method('read')
            ->with($this->equalTo($path))
            ->will($this->returnValue($content));

        $adapter = new SelectelAdapter($service);

        $expected = ['type' => 'file', 'contents' => $content, 'path' => $path];
        $test = $adapter->read($path);
        ksort($expected);
        ksort($test);

        $this->assertSame($expected, $test);
    }

    /**
     * Проверяет, что адаптер читает содержимое файла как поток.
     */
    public function testReadStream()
    {
        $path = '/' . $this->createFakeData()->word . '.txt';
        $stream = tmpfile();

        $service = $this->getMockBuilder(ServiceInterface::class)->getMock();
        $service->expects($this->once())->method('readStream')
            ->with($this->equalTo($path))
            ->will($this->returnValue($stream));

        $adapter = new SelectelAdapter($service);

        $expected = ['type' => 'file', 'stream' => $stream, 'path' => $path];
        $test = $adapter->readStream($path);
        ksort($expected);
        ksort($test);

        $this->assertSame($expected, $test);

        fclose($stream);
    }

    /**
     * Проверяет, что адаптер возращает содержимое папку.
     */
    public function testListContents()
    {
        $path = '/' . $this->createFakeData()->word;
        $files = [
            ['type' => 'file', 'path' => '/1_' . $this->createFakeData()->word . '.txt'],
            ['type' => 'file', 'path' => '/2_' . $this->createFakeData()->word . '.txt'],
            ['type' => 'file', 'path' => '/3_' . $this->createFakeData()->word . '.txt'],
        ];

        $service = $this->getMockBuilder(ServiceInterface::class)->getMock();
        $service->expects($this->once())->method('listMatched')
            ->with($this->equalTo($path))
            ->will($this->returnValue($files));

        $adapter = new SelectelAdapter($service);

        $this->assertSame($files, $adapter->listContents($path));
    }

    /**
     * Проверяет, что адаптер возвращает метаинформацию о файле.
     */
    public function testGetMetadata()
    {
        $path1 = '/1_' . $this->createFakeData()->word . '.txt';
        $path2 = '/2_' . $this->createFakeData()->word . '.txt';
        $metadata = [
            'type' => 'file',
            'path' => $path1,
            'size' => 123,
            'mimeinfo' => 'application/xml',
            'timestamp' => time(),
        ];

        $service = $this->getMockBuilder(ServiceInterface::class)->getMock();
        $service->method('fileInfo')->will($this->returnCallback(function ($path) use ($path1, $metadata) {
            return $path === $path1 ? $metadata : null;
        }));

        $adapter = new SelectelAdapter($service);
        $metadataToTest = $adapter->getMetadata($path1);
        ksort($metadata);
        ksort($metadataToTest);

        $this->assertSame($metadata, $metadataToTest);
        $this->assertFalse($adapter->getMetadata($path2));
    }

    /**
     * Проверяет, что адаптер возвращает метаинформацию о размере файла.
     */
    public function testGetSize()
    {
        $path1 = '/1_' . $this->createFakeData()->word . '.txt';
        $path2 = '/2_' . $this->createFakeData()->word . '.txt';
        $metadata = [
            'type' => 'file',
            'path' => $path1,
            'size' => 123,
            'mimeinfo' => 'application/xml',
            'timestamp' => time(),
        ];

        $service = $this->getMockBuilder(ServiceInterface::class)->getMock();
        $service->method('fileInfo')->will($this->returnCallback(function ($path) use ($path1, $metadata) {
            return $path === $path1 ? $metadata : null;
        }));

        $adapter = new SelectelAdapter($service);
        $metadataToTest = $adapter->getSize($path1);
        ksort($metadata);
        ksort($metadataToTest);

        $this->assertSame($metadata, $metadataToTest);
        $this->assertFalse($adapter->getSize($path2));
    }

    /**
     * Проверяет, что адаптер возвращает метаинформацию о mime файла.
     */
    public function testGetMimetype()
    {
        $path1 = '/1_' . $this->createFakeData()->word . '.txt';
        $path2 = '/2_' . $this->createFakeData()->word . '.txt';
        $metadata = [
            'type' => 'file',
            'path' => $path1,
            'size' => 123,
            'mimeinfo' => 'application/xml',
            'timestamp' => time(),
        ];

        $service = $this->getMockBuilder(ServiceInterface::class)->getMock();
        $service->method('fileInfo')->will($this->returnCallback(function ($path) use ($path1, $metadata) {
            return $path === $path1 ? $metadata : null;
        }));

        $adapter = new SelectelAdapter($service);
        $metadataToTest = $adapter->getMimetype($path1);
        ksort($metadata);
        ksort($metadataToTest);

        $this->assertSame($metadata, $metadataToTest);
        $this->assertFalse($adapter->getMimetype($path2));
    }

    /**
     * Проверяет, что адаптер возвращает метаинформацию о времени создания файла.
     */
    public function testGetTimestamp()
    {
        $path1 = '/1_' . $this->createFakeData()->word . '.txt';
        $path2 = '/2_' . $this->createFakeData()->word . '.txt';
        $metadata = [
            'type' => 'file',
            'path' => $path1,
            'size' => 123,
            'mimeinfo' => 'application/xml',
            'timestamp' => time(),
        ];

        $service = $this->getMockBuilder(ServiceInterface::class)->getMock();
        $service->method('fileInfo')->will($this->returnCallback(function ($path) use ($path1, $metadata) {
            return $path === $path1 ? $metadata : null;
        }));

        $adapter = new SelectelAdapter($service);
        $metadataToTest = $adapter->getTimestamp($path1);
        ksort($metadata);
        ksort($metadataToTest);

        $this->assertSame($metadata, $metadataToTest);
        $this->assertFalse($adapter->getTimestamp($path2));
    }

    /**
     * Проверяет, что адаптер возвращает метаинформацию о правах на файл.
     */
    public function testGetVisibility()
    {
        $path = '/1_' . $this->createFakeData()->word . '.txt';
        $service = $this->getMockBuilder(ServiceInterface::class)->getMock();

        $adapter = new SelectelAdapter($service);

        $this->assertFalse($adapter->getVisibility($path));
    }
}
