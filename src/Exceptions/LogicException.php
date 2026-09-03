<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Exceptions;

/**
 * Thrown when the package reaches an invalid or internally inconsistent state.
 */
class LogicException extends \LogicException implements BinaryDataException
{
}
