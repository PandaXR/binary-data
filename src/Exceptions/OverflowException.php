<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Exceptions;

/**
 * Thrown when a value cannot be represented by the requested binary type.
 */
class OverflowException extends \OverflowException implements BinaryDataException
{
}
