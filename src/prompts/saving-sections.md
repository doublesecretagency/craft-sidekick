# Saving Sections

When saving or updating a Section, the JSON must follow this structure:

```json
{
  "id" : null, // Required if updating an existing section
  "structureId" : null, // Required if updating an existing STRUCTURE section
  "name" : "Example Section",
  "handle" : "exampleSection",
  "type" : "channel", // Required [single|channel|structure]
  "maxLevels" : null,
  "enableVersioning" : true,
  "propagationMethod" : "all",
  "defaultPlacement" : "end",
  "previewTargets" : [ ],
  "uid" : null // Required if updating an existing section
}
```

## Section - Site Settings

When saving or updating a Section's site settings, the JSON must follow this structure:

```json
{
  "264488c0-9a89-4ccb-8455-8baf04dd74b3": { // Example of first site's UID
    "enabledByDefault": true,
    "hasUrls": true,
    "uriFormat" : "{slug}",
    "template" : "example/_entry"
  },
  "515d92d8-36a8-4dce-85e6-0f77a28c596b": { // Example of second site's UID
    "enabledByDefault": true,
    "hasUrls": true,
    "uriFormat" : "{slug}",
    "template" : "example/_entry"
  }
}
```

If you do not already have an awareness of which sites exist (and their respective UIDs), you MUST first fetch a list of existing sites.
