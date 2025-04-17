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

When saving or updating a Section's site settings, the JSON must follow this structure (indexed by site ID):

```json
{
  1: { // Indexed by the site ID
    "siteId": 1,
    "enabledByDefault": true,
    "hasUrls": true,
    "uriFormat" : "{slug}",
    "template" : "example/_entry"
  },
  5: { // Indexed by the site ID
    "siteId": 5,
    "enabledByDefault": true,
    "hasUrls": true,
    "uriFormat" : "{slug}",
    "template" : "example/_entry"
  }
}
```

If you do not already have an awareness of which sites exist (and their respective UIDs), you MUST first fetch a list of existing sites.
