---
layout: page
title: Getting started
permalink: /getting-started/
---

## Requirements

- PHP 8.2 or later
- A 64-bit PHP build (`PHP_INT_SIZE === 8`)

Composer enforces the architecture requirement through the `php-64bit` platform package.

## Installation

```bash
composer require lxr/binary-data
```

## Read and write a file

`FileBinaryStream` opens an existing file in `r+b` mode. Create the file before opening it.

```php
<?php

use Lxr\BinaryData\Enums\ByteOrder;
use Lxr\BinaryData\Enums\LengthPrefixSize;
use Lxr\BinaryData\Reader;
use Lxr\BinaryData\Streams\FileBinaryStream;
use Lxr\BinaryData\Writer;

$path = __DIR__ . '/message.bin';
file_put_contents($path, '');

$stream = new FileBinaryStream($path);

try {
    $writer = new Writer($stream, ByteOrder::BIG_ENDIAN);
    $writer->writeUInt16(0xcafe);
    $writer->writeInt32(-42);
    $writer->writeLengthPrefixedString('hello', LengthPrefixSize::UINT8);
    $writer->writeNullTerminatedString('done');

    $reader = new Reader($stream, ByteOrder::BIG_ENDIAN);
    $reader->setPosition(0);

    $marker = $reader->readUInt16();
    $number = $reader->readInt32();
    $message = $reader->readLengthPrefixedString(LengthPrefixSize::UINT8);
    $status = $reader->readNullTerminatedString();
} finally {
    $stream->close();
}
```

Reader and Writer do not own the stream. The code that creates a stream is responsible for
closing it.

## Byte order

Pass a byte order to Reader and Writer when constructing them:

```php
$reader = new Reader($stream, ByteOrder::LITTLE_ENDIAN);
$writer = new Writer($stream, ByteOrder::BIG_ENDIAN);
```

`ByteOrder::NATIVE` is the default. Byte order affects multi-byte integers and floating
point values, but not raw bytes or strings.

Continue with the [usage guide]({{ '/guide/' | relative_url }}).
