<?php

declare(strict_types=1);

namespace Youtool\SelectelBundle\Exception;

use Exception;

/**
 * Исключение, которое выбрасывается, если ожидался иной статус ответа от сервиса.
 */
class UnexpectedResponseException extends Exception
{
}
