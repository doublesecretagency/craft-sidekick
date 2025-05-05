# Matrix Fields (Craft 4)

## Functional Matrix Creation (Craft 4)

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
            "name": "Text Block",
            "handle": "textBlock",
            "fields": [
                {
                    "type": "craft\\fields\\PlainText",
                    "name": "Plain Text Field",
                    "handle": "plainTextField"
                }
            ]
        },
        {
            "name": "Image Block",
            "handle": "imageBlock",
            "fields": [
                {
                    "type": "craft\\fields\\Assets",
                    "name": "Assets Field",
                    "handle": "assetsField"
                }
            ]
        }
    ]
}
```
