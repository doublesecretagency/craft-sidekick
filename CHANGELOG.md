# Changelog

## Unreleased

### Added
- Added site and site group management skills.

### Changed
- Improved field and section management skills.

### Fixed
- Fixed overzealous namespace hash translations.

## 0.9.3 - 2025-04-09

### Changed
- Separated skills based on whether `allowAdminChanges` is enabled.
- When the chat encounters an error, it will continue trying based on the error response.
- Improved field management by adding explicit instructions for field layout configs.

### Fixed
- Fixed compatibility issues with Firefox.

## 0.9.2 - 2025-04-07

### Added
- Added field management skills.

### Changed
- Improved system prompt.
- Improved entry and section management skills.

## 0.9.1 - 2025-04-05

### Added
- Added a welcome message.

### Changed
- Display placeholder images `@3x` resolution (aka “Super Retina”).
- Replaced spinner circle with transitioning gradient text.
- Improved error message for timeouts.
- Render dynamic copyright dates.

## 0.9.0 - 2025-04-03

### Added
- Integrated with the OpenAI API.
- Added the AI [chat window](https://plugins.doublesecretagency.com/sidekick/chat-window/) interface.
- Added template management skills.
- Added rudimentary entry and section management skills.
- Added ability to provide [custom skills](https://plugins.doublesecretagency.com/sidekick/custom-skills/) via the `AddSkillsEvent`.
- Added documentation.

For more information, please see the [complete documentation...](https://plugins.doublesecretagency.com/sidekick/)
