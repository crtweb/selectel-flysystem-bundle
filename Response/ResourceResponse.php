<?php

declare(strict_types=1);

namespace Creative\SelectelBundle\Response;

use InvalidArgumentException;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Объект ответа, который выводит файл из resource.
 */
class ResourceResponse extends Response
{
    /**
     * @var resource|null
     */
    protected $resource;
    /**
     * @var bool
     */
    private $headersSent = false;
    /**
     * @var bool
     */
    private $streamed = false;

    /**
     * @param mixed $resource
     *
     * @throws InvalidArgumentException
     */
    public function __construct($resource = null, int $status = 200, array $headers = [])
    {
        parent::__construct(null, $status, $headers);

        if (null !== $resource) {
            $this->setResource($resource);
        }
        $this->headersSent = false;
        $this->streamed = false;
    }

    /**
     * @param ?string $content
     * @param int     $status
     * @param array   $headers
     *
     * @return static
     *
     * @throws InvalidArgumentException
     * @psalm-suppress UnsafeInstantiation
     */
    public static function create(?string $content = null, $status = 200, $headers = [])
    {
        return new ResourceResponse($content, $status, $headers);
    }

    /**
     * @param mixed $resource
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function setResource($resource)
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException('Response awaits valid php resource as response content');
        }

        $this->resource = $resource;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    public function sendHeaders()
    {
        if ($this->headersSent) {
            return $this;
        }

        $this->headersSent = true;

        return parent::sendHeaders();
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     *
     * @throws LogicException
     * @psalm-suppress InvalidPropertyAssignmentValue
     */
    public function sendContent()
    {
        if ($this->streamed) {
            return $this;
        }

        $this->streamed = true;

        if (null === $this->resource) {
            throw new LogicException('The Response resource must not be null.');
        }

        fseek($this->resource, 0);

        while (!feof($this->resource)) {
            echo fread($this->resource, 1024);
        }

        fclose($this->resource);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     *
     * @throws LogicException
     */
    public function setContent($content)
    {
        if (null !== $content) {
            throw new LogicException('The content cannot be set on a StreamedResponse instance.');
        }

        $this->streamed = true;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getContent()
    {
        return '';
    }
}
