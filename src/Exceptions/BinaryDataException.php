<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Exceptions;

/**
 * Marks exceptions intentionally raised by the binary-data package.
 *
 * Native engine errors such as TypeError and ValueError do not implement this interface.
 */
interface BinaryDataException extends \Throwable
{
}
