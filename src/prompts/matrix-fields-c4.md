# Matrix Fields (Craft 4)

## Functional Matrix Creation (Craft 4)

When adding an Assets field within matrix blocks, make sure to specify essential asset settings.

```json
{
    "groupId": 5,
    "name": "Example Matrix Field",
    "handle": "exampleMatrixField",
    "instructions": "",
    "required": false,
    "translationMethod": "none",
    "translationKeyFormat": null,
    "minBlocks": null,
    "maxBlocks": null,
    "blockTypes": [
        {
            "name": "Example Text Block",
            "handle": "exampleTextBlock",
            "fields": [
                {
                    "type": "craft\\fields\\PlainText",
                    "name": "Example Plain Text Field",
                    "handle": "examplePlainTextField"
                }
            ]
        },
        {
            "name": "Example Image Block",
            "handle": "exampleImageBlock",
            "fields": [
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
            ]
        }
    ]
}
```
