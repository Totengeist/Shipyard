# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

*Stay tuned!*

## [0.1.2] - 2025-3-27

Progressing towards stability. Releases will no longer be marked as alpha or pre-release.

### Added

- Added version information to About page.
- Functionality needed for future admin dashboard.
- Allow deleting Thumbnail images along with the database record.

### Changed

- Allowed registering directly via Discord.
- Improved file handling.
- Improved screenshot control buttons.
- Update to stable [IVParsers][iv-parsers] package.
- Moved from jasmine/karma to jest for frontend tests.
- Improved testing across the board.
- Paginate permissions index.

### Fixed

- Ignore false upload from Uppy used to bypass the requirement to upload a file to submit.
- Let admins view all items, regardless of status.

## [0.1.1-alpha] - 2025-01-11

### Changed

- Fixed Steam and Discord integrations

## [0.1.0-alpha] - 2025-01-06

### Added

- API and frontend for sharing ships, saves, and mods for The Last Starship.
- Descriptive [README](./README.md), valid [LICENSE](LICENSE), and this CHANGELOG.

[iv-parsers]: https://github.com/Totengeist/IVParsers

[unreleased]: https://github.com/Totengeist/Shipyard/compare/v0.1.2...HEAD
[0.1.2]: https://github.com/Totengeist/Shipyard/releases/tag/v0.1.2
[0.1.1-alpha]: https://github.com/Totengeist/Shipyard/releases/tag/v0.1.1-alpha
[0.1.0-alpha]: https://github.com/Totengeist/Shipyard/releases/tag/v0.1.0-alpha