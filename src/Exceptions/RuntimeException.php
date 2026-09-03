<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Exceptions;

/**
 * Thrown when a binary-data operation fails at runtime.
 */
class RuntimeException extends \RuntimeException implements BinaryDataException
{
}
