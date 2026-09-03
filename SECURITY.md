# Security Policy

## Supported versions

Only the latest published release receives security fixes. During the `0.x` series, a
security fix may include backward-incompatible changes when they are necessary to make
the API safe. Users should upgrade to the newest release before reporting an issue that
may already be resolved.

## Reporting a vulnerability

Do not open a public issue for a suspected vulnerability.

Use [GitHub's private vulnerability reporting](https://github.com/PandaXR/binary-data/security/advisories/new)
when it is available, or email `devrnd@mail.ru` with the subject
`binary-data security report`.

Include as much of the following information as possible:

- The affected package version and PHP version
- The operating system and architecture
- A minimal reproducer or malformed binary sample
- The expected and observed behavior
- The potential impact and realistic attack scenario
- Any suggested mitigation or fix

Do not include production secrets, personal data, or other sensitive material that is
not required to reproduce the issue.

The project aims to acknowledge reports within seven days. The report will be assessed,
a remediation plan will be prepared when necessary, and disclosure timing will be
coordinated with the reporter. Please allow a reasonable remediation period before
publishing details.

## Security considerations

- Configure the Reader's `maximumStringLength` constructor argument appropriately when
  parsing untrusted input. The default limit is 16 MiB.
- Treat file paths as trusted application input. `FileBinaryStream` does not provide
  path sandboxing or access-control checks.
- The package parses and serializes data; it does not authenticate, encrypt, or validate
  the semantic trustworthiness of a binary format.
- Catch `BinaryDataException` for package failures, but allow native PHP errors to remain
  visible during development and monitoring.
