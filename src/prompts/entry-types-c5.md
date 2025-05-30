# Entry Types (Craft 5+)

In Craft 5, Entry Types were disassociated from their respective Sections and are now managed independently. This allows for greater flexibility in how you structure your content.

Instead of defining Entry Types within a Section, you create them as standalone entities. This means that you can reuse Entry Types across different Sections, providing a more modular approach to content management.

Entry Types can be used in both Sections and Matrix Fields (where they were previously known as Block Types).

## Example Config

```json
{
  "name": "Example Entry Type",
  "handle": "exampleEntryType",
  "hasTitleField": true,
  "showSlugField": true,
  "showStatusField": true,
  "titleFormat": null,
  "titleTranslationMethod": "site",
  "slugTranslationMethod": "site",
  "icon": "newspaper", // From the Font Awesome 6 icon set
  "color": "blue",
  "fieldLayout": {
    // See the section on "Field Layouts" for complete details
  }
}
```
