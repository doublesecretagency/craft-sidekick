# Field Layout Configs

When managing field layouts, the config must follow a **very precise** structure.

The JSON object consists of a top-level key called “tabs”, which is an array. Each item in this array represents a tab in the entry editor interface. Tabs allow you to group related fields together for a cleaner, more organized editing experience.

Each tab can contain multiple elements, which are the fields that will be displayed within that tab. Each element is represented as an object with various properties that define its behavior and appearance.

Each tab and element has a unique identifier (UUID). If a config is being saved for the first time, generate new UUIDs for the tabs and/or elements. If a config is being updated, ensure the UUIDs do not change from their original values. This is crucial for maintaining the integrity of the field layout, as Craft uses these identifiers to track the fields and their relationships.

You MUST triple-check the JSON structure to ensure it is valid. Any errors in the JSON format can lead to issues when saving or loading the field layout. There is no room for error in the JSON structure, as it must adhere to the exact specifications required by Craft CMS.

This is a standard example of a field layout config in Craft 4:

```json
{
    "tabs": [
        // Array of tabs
        {
            "name": "Content", // Required
            "uid": "f4cc37e5-fe91-428c-9edc-b268c921af98", // Required
            "userCondition": null,
            "elementCondition": null,
            "elements": [ // Required
                // Array of field layout elements
                {
                    "type": "craft\\fieldlayoutelements\\entries\\EntryTitleField", // Required
                    "inputType": null,
                    "autocomplete": false,
                    "class": null,
                    "size": null,
                    "name": null,
                    "autocorrect": true,
                    "autocapitalize": true,
                    "disabled": false,
                    "readonly": false,
                    "title": null,
                    "placeholder": null,
                    "step": null,
                    "min": null,
                    "max": null,
                    "requirable": false,
                    "id": null,
                    "containerAttributes": [],
                    "inputContainerAttributes": [],
                    "labelAttributes": [],
                    "orientation": null,
                    "label": null,
                    "instructions": null,
                    "tip": null,
                    "warning": null,
                    "width": 100, // Required [25|50|75|100]
                    "uid": "6cb26f71-fb5b-47c2-92f5-ce02693e1495", // Required - UID of the field
                    "userCondition": null,
                    "elementCondition": null
                }
            ]
        }
    ]
}
```
