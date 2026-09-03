---
layout: page
title: API reference
permalink: /api/
---

## Reader

| Method | Return value | Description |
| --- | --- | --- |
| `readBytes(int $length)` | `string` | Read exactly `$length` raw bytes |
| `readUntil(string $delimiter)` | `string` | Read through a delimiter or end of stream |
| `readInt8()` | `int` | Read a signed 8-bit integer |
| `readInt16()` | `int` | Read a signed 16-bit integer |
| `readInt32()` | `int` | Read a signed 32-bit integer |
| `readInt64()` | `int` | Read a signed 64-bit integer |
| `readUInt8()` | `int` | Read an unsigned 8-bit integer |
| `readUInt16()` | `int` | Read an unsigned 16-bit integer |
| `readUInt32()` | `int` | Read an unsigned 32-bit integer |
| `readUInt64()` | `UInt64` | Read a full-range unsigned 64-bit integer |
| `readFloat()` | `float` | Read a single-precision value |
| `readDouble()` | `float` | Read a double-precision value |
| `readLengthPrefixedString(LengthPrefixSize $prefixSize = LengthPrefixSize::UINT32)` | `string` | Read a bounded length-prefixed string |
| `readNullTerminatedString()` | `string` | Read a strictly null-terminated string |
| `getPosition()` | `int` | Return the current stream position |
| `setPosition(int $position)` | `void` | Move to an absolute stream position |

## Writer

| Method | Return value | Description |
| --- | --- | --- |
| `writeBytes(string $data)` | `void` | Write all raw bytes |
| `writeInt8(int $value)` | `void` | Write a signed 8-bit integer |
| `writeInt16(int $value)` | `void` | Write a signed 16-bit integer |
| `writeInt32(int $value)` | `void` | Write a signed 32-bit integer |
| `writeInt64(int $value)` | `void` | Write a signed 64-bit integer |
| `writeUInt8(int $value)` | `void` | Write an unsigned 8-bit integer |
| `writeUInt16(int $value)` | `void` | Write an unsigned 16-bit integer |
| `writeUInt32(int $value)` | `void` | Write an unsigned 32-bit integer |
| `writeUInt64(UInt64 $value)` | `void` | Write a full-range unsigned 64-bit integer |
| `writeFloat(float $value)` | `int` | Write a float and return its encoded size |
| `writeDouble(float $value)` | `int` | Write a double and return its encoded size |
| `writeLengthPrefixedString(string $value, LengthPrefixSize $prefixSize = LengthPrefixSize::UINT32)` | `int` | Write a string and return total encoded size |
| `writeNullTerminatedString(string $value)` | `int` | Write a string and return total encoded size |
| `getPosition()` | `int` | Return the current stream position |
| `setPosition(int $position)` | `void` | Move to an absolute stream position |

Writer methods either complete their full write or throw.

## Enums

### `ByteOrder`

- `ByteOrder::NATIVE`
- `ByteOrder::LITTLE_ENDIAN`
- `ByteOrder::BIG_ENDIAN`

### `LengthPrefixSize`

- `LengthPrefixSize::UINT8`
- `LengthPrefixSize::UINT16`
- `LengthPrefixSize::UINT32`

## `UInt64`

| Method | Description |
| --- | --- |
| `__construct(string $bigEndianBytes)` | Create a value from exactly eight big-endian bytes |
| `toInt()` | Convert values through `PHP_INT_MAX` to a native integer |
| `toDecimalString()` | Return the complete unsigned decimal representation |
| `toHex()` | Return 16 lowercase hexadecimal digits |
| `toBigEndianBytes()` | Return canonical big-endian bytes |
| `toLittleEndianBytes()` | Return little-endian bytes |

See the source contracts for the complete exception guarantees:
[`ReaderContract`](https://github.com/PandaXR/binary-data/blob/main/src/Contracts/ReaderContract.php),
[`WriterContract`](https://github.com/PandaXR/binary-data/blob/main/src/Contracts/WriterContract.php), and
[`StreamContract`](https://github.com/PandaXR/binary-data/blob/main/src/Contracts/StreamContract.php).
