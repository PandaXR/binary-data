<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Traits;

use Lxr\BinaryData\Enums\BytesOrder;

/**
 * Common functionality for handling byte order in binary data reading and writing.
 * This trait provides methods to apply the specified byte order to binary data,
 * ensuring that multi-byte values are correctly interpreted or written according to the
 * desired endianness.
 *
 * @property BytesOrder $bytesOrder The byte order to apply (e.g., little-endian, big-endian, native)
 */
trait ByteOrderTrait
{
    private BytesOrder $bytesOrder = BytesOrder::NATIVE;

    /**
     * Apply the specified byte order to the given data.
     *
     * @param string $data Data to apply byte order to
     * @return string Data with applied byte order
     */
    private function applyByteOrder(string $data): string
    {
        if ($this->bytesOrder === BytesOrder::NATIVE) {
            return $data;
        }

        $nativeOrder = BytesOrder::NATIVE->resolve();

        return $this->bytesOrder === $nativeOrder
            ? $data
            : strrev($data);
    }
}
