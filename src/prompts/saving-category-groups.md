# Saving Category Groups

When saving or updating a Category Group, the JSON must follow this structure:

```json
{
  "id" : null, // Required if updating an existing category group
  "structureId" : null, // Required
  "name" : "Example Category Group",
  "handle" : "exampleCategory Group",
  "maxLevels" : null,
  "defaultPlacement" : "end",
  "uid" : null // Required if updating an existing category group
}
```

## Category Group - Site Settings

When saving or updating a Category Group's site settings, the JSON must follow this structure (indexed by site ID):

```json
{
  1: { // Indexed by the site ID
    "siteId": 1,
    "hasUrls": true,
    "uriFormat" : "{slug}",
    "template" : "example/_entry"
  },
  5: { // Indexed by the site ID
    "siteId": 5,
    "hasUrls": true,
    "uriFormat" : "{slug}",
    "template" : "example/_entry"
  }
}
```

If you do not already have an awareness of which sites exist (and their respective UIDs), you MUST first fetch a list of existing sites.
