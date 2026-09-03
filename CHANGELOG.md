# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and
this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- `Reader::readBytes()` now collects partial stream reads until the requested byte count
  is complete and rejects truncated input instead of returning it silently.

## [0.9.0] - 2026-09-03

### Changed

- Renamed `BytesOrder` to `ByteOrder`.
- Renamed length-prefix cases from `BYTE`, `SHORT`, and `INT` to `UINT8`, `UINT16`, and
  `UINT32`.
- Renamed string APIs to `readLengthPrefixedString()`, `writeLengthPrefixedString()`,
  `readNullTerminatedString()`, and `writeNullTerminatedString()`.
- Renamed Reader and Writer constructor parameters to `$stream` and `$byteOrder`.
- Clarified Writer return semantics: fixed-size and raw-byte methods return `void`, while
  floating-point and encoded-string methods return the total encoded byte count.

## [0.8.0] - 2026-09-03

### Added

- Comprehensive README with installation, usage, platform, and API guidance.
- MIT license file.
- Security support and private vulnerability-reporting policy.
- Release changelog.

## [0.7.0] - 2026-09-03

### Added

- Boundary, failure-propagation, and Reader/Writer round-trip tests.
- Round-trip coverage for every byte order, full-range `UInt64`, floating-point special
  values, strings, and real files.

## [0.6.1] - 2026-09-03

### Fixed

- Reader now derives float and double widths from the active PHP `pack()` format instead
  of assuming four-byte floats and eight-byte doubles.

## [0.6.0] - 2026-09-03

### Added

- Explicit Composer requirement for a 64-bit PHP runtime.
- Documentation for integer architecture and `UInt64` conversion behavior.

## [0.5.0] - 2026-09-03

### Added

- Complete-write handling for streams that report partial writes.
- Validation for invalid write counts and streams that make no progress.
- Idempotent file-stream closing and closed-stream state checks.

### Changed

- `FileBinaryStream` now uses `SplFileObject` and wraps file-opening failures in package
  exceptions without replacing the application's error handler.
- High-level fixed-width Writer methods now complete successfully or throw instead of
  exposing low-level byte counts.
- Float, double, and encoded-string Writer methods return their complete encoded sizes.

## [0.4.0] - 2026-09-03

### Added

- Configurable maximum length for variable-length Reader operations, with a 16 MiB
  default.
- Multi-byte delimiter support for `readUntil()`.
- Strict null-terminated parsing and embedded-null validation when writing.

### Fixed

- Length-prefix overflow is rejected before any bytes are written.

## [0.3.0] - 2026-09-03

### Added

- Numeric range validation for signed and unsigned integer Writer methods.
- Boundary tests for every supported native integer width.

## [0.2.0] - 2026-09-03

### Added

- GitHub Actions checks for supported PHP versions, static analysis, coding standards,
  tests, coverage, and dependency auditing.
- Dependabot configuration for Composer and GitHub Actions dependencies.

### Changed

- Development dependencies now use stable release constraints.

## [0.1.0] - 2026-09-03

### Added

- Initial Reader and Writer implementations.
- Native, little-endian, and big-endian integer and floating-point support.
- Byte-backed `UInt64` value object for the complete unsigned 64-bit range.
- Raw, delimited, null-terminated, and length-prefixed string operations.
- File-stream implementation and public Reader, Writer, and Stream contracts.
- Package-specific exception hierarchy.

[Unreleased]: https://github.com/PandaXR/binary-data/compare/v0.9.0...HEAD
[0.9.0]: https://github.com/PandaXR/binary-data/compare/v0.8.0...v0.9.0
[0.8.0]: https://github.com/PandaXR/binary-data/compare/v0.7.0...v0.8.0
[0.7.0]: https://github.com/PandaXR/binary-data/compare/v0.6.1...v0.7.0
[0.6.1]: https://github.com/PandaXR/binary-data/compare/v0.6.0...v0.6.1
[0.6.0]: https://github.com/PandaXR/binary-data/compare/v0.5.0...v0.6.0
[0.5.0]: https://github.com/PandaXR/binary-data/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/PandaXR/binary-data/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/PandaXR/binary-data/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/PandaXR/binary-data/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/PandaXR/binary-data/releases/tag/v0.1.0
