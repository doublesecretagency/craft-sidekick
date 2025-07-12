# Saving Fields

When saving or updating a field, please put all settings in the **top level** of the JSON config. Do not nest them under a `settings` key, they belong at the top level.

## Special Considerations for Nested Assets Fields

When creating an Assets field nested within a Matrix block, ensure that you include the asset-specific configurations. Here's an example:

```json
{
    "type": "craft\\fields\\Assets",
    "name": "Example Assets Field",
    "handle": "exampleAssetsField",
    "allowMultipleSources": false,
    "sources": [],
    "defaultUploadLocationSource": "folder",
    "defaultUploadLocationSubpath": "uploads",
    "singleUploadLocationSource": "folder",
    "singleUploadLocationSubpath": "uploads"
}
```
