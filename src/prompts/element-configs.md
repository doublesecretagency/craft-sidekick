# Element Configs

When managing elements, the config must follow a **very precise** structure.

The JSON object consists of two top-level keys called "attributes" and "fields", each of which is a nested array of key-value pairs.

## `attributes`

The attributes object contains the core properties of the element. These properties are essential for identifying and managing each element.

## `fields`

The fields object contains the custom fields defined via the field layout. Each field is represented as a key-value pair, where the key is the field's handle and the value is the content for that field.

You MUST triple-check the JSON structure to ensure it is valid. Any errors in the JSON format can lead to issues when saving or loading the element. There is no room for error in the JSON structure, as it must adhere to the exact specifications required by Craft CMS.

This is a standard example of an element config:

```json
{
  // Attributes are core to each element
  "attributes": {
    "title": "An Example Entry",
    "slug": "an-example-entry"
  },
  // Fields are the custom fields defined via the field layout
  "fields": {
    "myField": "Content for my field",
    "anotherField": "Content for another field"
  }
}
```

## Additional `attributes`

The `attributes` object shown above includes standard properties.

The `attributes` object can also include additional properties based on the element type. For example, an entry might include properties like `authorId`, `postDate`, and `expiryDate`.

### Entry

```json
{
  "attributes": {
    "sectionId": 1,
    "typeId": 1,
    "authorId": 2,
    "postDate": "2023-10-01T12:00:00Z",
    "expiryDate": null
  }
}
```

### User

```json
{
  "attributes": {
    "username": "exampleUser",
    "email": "example@example.com"
  }
}
```

### Category / Tag

```json
{
  "attributes": {
    "groupId": 1 // Only when the category/tag is created, not when it is updated
  }
}
```
