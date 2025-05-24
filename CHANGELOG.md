# Changelog

## Unreleased

### Added
- Added tag management skills.

### Changed
- Nested details in skills slideout.
- Improved field layout descriptions.

### Fixed
- Fixed bug to prevent templates from being editable in production.

## 1.0.1 - 2025-05-21

### Added
- Added category management skills.
- Added script to generate GitHub releases.

### Changed
- Removed unused requirements.
- Improved field management skills.

### Fixed
- Fixed bug which occurred when updating a site config.

## 1.0.0 - 2025-05-12

### Added
- Released from beta!

### Changed
- Improved a critical error message.

## 0.9.9 - 2025-05-09

### Added
- Added ability to provide [custom prompts](https://plugins.doublesecretagency.com/sidekick/customize/add-prompts) via the `AddPromptsEvent`.

### Changed
- Improved field management skills.
- Locked chat window to the `GPT-4o` model for now.

### Fixed
- Fixed bug preventing Utility view from showing skills list.

## 0.9.8 - 2025-05-05

### Changed
- Improved entry management skills.
- Minor improvements to matrix field management skills.
- Restructured how restricted methods are specified.

## 0.9.7 - 2025-05-02

### Changed
- Updated plugin icons.

### Fixed
- Improved behavior of utility in Craft 5.

## 0.9.6 - 2025-05-02

### Added
- Added the [AI Summary](https://plugins.doublesecretagency.com/sidekick/fields/ai-summary) field type.
- Added setting to override nav link label.
- Added setting to use chat window as a Utility.

### Changed
- Improved slideout which lists available skills.

### Fixed
- Fixed bug which prevented the project config from updating properly.

## 0.9.5 - 2025-04-21

### Added
- Added slideout to list available skills.

### Fixed
- Fixed bug when getting all sections in Craft 5.

## 0.9.4 - 2025-04-17

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
- Display placeholder images `@3x` resolution (aka "Super Retina").
- Replaced spinner circle with transitioning gradient text.
- Improved error message for timeouts.
- Render dynamic copyright dates.

## 0.9.0 - 2025-04-03

### Added
- Integrated with the OpenAI API.
- Added the AI [chat window](https://plugins.doublesecretagency.com/sidekick/chat/how-it-works) interface.
- Added template management skills.
- Added rudimentary entry and section management skills.
- Added ability to provide [custom skills](https://plugins.doublesecretagency.com/sidekick/chat/custom-skills) via the `AddSkillsEvent`.
- Added documentation.

For more information, please see the [complete documentation...](https://plugins.doublesecretagency.com/sidekick/)
