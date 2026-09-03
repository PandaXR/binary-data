<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Exceptions;

/**
 * Thrown when an argument does not satisfy a binary-data API contract.
 */
class InvalidArgumentException extends \InvalidArgumentException implements BinaryDataException
{
}
