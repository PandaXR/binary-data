<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Exceptions;

/**
 * Thrown when binary input does not contain a value in the expected form.
 */
class UnexpectedValueException extends \UnexpectedValueException implements BinaryDataException
{
}
