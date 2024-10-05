# Actions for Sidekick Assistant

This document outlines all the supported actions that the Sidekick assistant can generate in response to user instructions. Each action includes a description, required parameters, and example usage.

---

## **Supported Actions**

### **updateElement**

- **Description:** Update the content of a specific HTML element within a Twig template.

- **Parameters:**
  - **action**: `"updateElement"`
  - **file**: The path to the target Twig file.
  - **element**: The HTML tag or Twig block to update.
  - **newValue**: The new content to insert.

- **User Instruction Example:**

  "Change the `<h1>` in `index.twig` to 'Welcome to Our Site'"

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "updateElement",
      "file": "/templates/index.twig",
      "element": "h1",
      "newValue": "Welcome to Our Site"
    }
  ]
}
```

---

### **createFile**

- **Description:** Create a new Twig template file with specified content.

- **Parameters:**
  - **action**: `"createFile"`
  - **file**: The path to the new file.
  - **content**: The content to include in the file.

- **User Instruction Example:**

  "Create a new template called `about.twig` with basic HTML structure."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "createFile",
      "file": "/templates/about.twig",
      "content": "<!DOCTYPE html>\n<html>\n<head>\n    <title>About Us</title>\n</head>\n<body>\n    <!-- Content goes here -->\n</body>\n</html>"
    }
  ]
}
```

---

### **deleteFile**

- **Description:** Delete an existing Twig template file.

- **Parameters:**
  - **action**: `"deleteFile"`
  - **file**: The path to the file to delete.

- **User Instruction Example:**

  "Delete the `old_layout.twig` file."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "deleteFile",
      "file": "/templates/old_layout.twig"
    }
  ]
}
```

---

### **insertContent**

- **Description:** Insert specific content into a file at a specified location.

- **Parameters:**
  - **action**: `"insertContent"`
  - **file**: The path to the target Twig file.
  - **location**: The reference point for insertion (e.g., `"afterElement": "header"`).
  - **content**: The content to insert.

- **User Instruction Example:**

  "Insert a navigation menu after the header in `index.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "insertContent",
      "file": "/templates/index.twig",
      "location": {
        "afterElement": "header"
      },
      "content": "<nav>...navigation menu...</nav>"
    }
  ]
}
```

---

### **replaceContent**

- **Description:** Replace a specific block of content in a file.

- **Parameters:**
  - **action**: `"replaceContent"`
  - **file**: The path to the target Twig file.
  - **target**: The content or element to replace.
  - **newContent**: The new content to insert.

- **User Instruction Example:**

  "Replace the footer section in `base.twig` with new content."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "replaceContent",
      "file": "/templates/base.twig",
      "target": "footer",
      "newContent": "<footer>...new footer content...</footer>"
    }
  ]
}
```

---

### **appendContent**

- **Description:** Add content to the end of a file.

- **Parameters:**
  - **action**: `"appendContent"`
  - **file**: The path to the target Twig file.
  - **content**: The content to append.

- **User Instruction Example:**

  "Append a script tag to the end of `layout.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "appendContent",
      "file": "/templates/layout.twig",
      "content": "<script src='app.js'></script>"
    }
  ]
}
```

---

### **prependContent**

- **Description:** Add content to the beginning of a file.

- **Parameters:**
  - **action**: `"prependContent"`
  - **file**: The path to the target Twig file.
  - **content**: The content to prepend.

- **User Instruction Example:**

  "Add a comment at the top of `index.twig` noting the last update date."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "prependContent",
      "file": "/templates/index.twig",
      "content": "{# Last updated on 2023-10-05 #}\n"
    }
  ]
}
```

---

### **updateVariable**

- **Description:** Change the value of a variable within the template.

- **Parameters:**
  - **action**: `"updateVariable"`
  - **file**: The path to the target Twig file.
  - **variable**: The name of the variable to update.
  - **newValue**: The new value for the variable.

- **User Instruction Example:**

  "Set the `siteTitle` variable to 'My New Site' in `config.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "updateVariable",
      "file": "/templates/config.twig",
      "variable": "siteTitle",
      "newValue": "My New Site"
    }
  ]
}
```

---

### **addBlock**

- **Description:** Add a new Twig block to a template.

- **Parameters:**
  - **action**: `"addBlock"`
  - **file**: The path to the target Twig file.
  - **blockName**: The name of the new block.
  - **content**: The content of the new block.

- **User Instruction Example:**

  "Add a new block called `sidebar` to `base.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "addBlock",
      "file": "/templates/base.twig",
      "blockName": "sidebar",
      "content": "{% block sidebar %}\n<!-- Sidebar content here -->\n{% endblock %}"
    }
  ]
}
```

---

### **removeBlock**

- **Description:** Remove a Twig block from a template.

- **Parameters:**
  - **action**: `"removeBlock"`
  - **file**: The path to the target Twig file.
  - **blockName**: The name of the block to remove.

- **User Instruction Example:**

  "Remove the `advertisement` block from `layout.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "removeBlock",
      "file": "/templates/layout.twig",
      "blockName": "advertisement"
    }
  ]
}
```

---

### **removeElement**

- **Description:** Remove an HTML element and its content from a Twig template.

- **Parameters:**
  - **action**: `"removeElement"`
  - **file**: The path to the target Twig file.
  - **element**: The HTML tag to remove.

- **User Instruction Example:**

  "Remove the `<h1>` tag from `index.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "removeElement",
      "file": "/templates/index.twig",
      "element": "h1"
    }
  ]
}
```

---

### **wrapContent**

- **Description:** Wrap existing content within a new element or block.

- **Parameters:**
  - **action**: `"wrapContent"`
  - **file**: The path to the target Twig file.
  - **targetContent**: The content or selector to wrap.
  - **wrapper**: The new element or block to wrap around the target content.

- **User Instruction Example:**

  "Wrap the main content of `index.twig` in a `<div class='container'>`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "wrapContent",
      "file": "/templates/index.twig",
      "targetContent": "main",
      "wrapper": "<div class='container'>{{ content }}</div>"
    }
  ]
}
```

---

### **modifyAttribute**

- **Description:** Change attributes of HTML elements within the template.

- **Parameters:**
  - **action**: `"modifyAttribute"`
  - **file**: The path to the target Twig file.
  - **element**: The HTML tag or selector to modify.
  - **attribute**: The attribute to change.
  - **newValue**: The new value for the attribute.

- **User Instruction Example:**

  "Change the class of the `<body>` tag to `homepage` in `index.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "modifyAttribute",
      "file": "/templates/index.twig",
      "element": "body",
      "attribute": "class",
      "newValue": "homepage"
    }
  ]
}
```

---

### **duplicateFile**

- **Description:** Create a copy of an existing template file.

- **Parameters:**
  - **action**: `"duplicateFile"`
  - **sourceFile**: The path to the existing file.
  - **destinationFile**: The path for the new duplicate file.

- **User Instruction Example:**

  "Duplicate `index.twig` and name the new file `landing.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "duplicateFile",
      "sourceFile": "/templates/index.twig",
      "destinationFile": "/templates/landing.twig"
    }
  ]
}
```

---

### **renameFile**

- **Description:** Rename an existing template file.

- **Parameters:**
  - **action**: `"renameFile"`
  - **oldFile**: The current path to the file.
  - **newFile**: The new path for the file.

- **User Instruction Example:**

  "Rename `old_home.twig` to `home.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "renameFile",
      "oldFile": "/templates/old_home.twig",
      "newFile": "/templates/home.twig"
    }
  ]
}
```

---

### **searchAndReplace**

- **Description:** Search for a specific string in a file and replace it with another string.

- **Parameters:**
  - **action**: `"searchAndReplace"`
  - **file**: The path to the target Twig file.
  - **search**: The string to search for.
  - **replace**: The string to replace it with.

- **User Instruction Example:**

  "In `base.twig`, replace all instances of `oldBrand` with `newBrand`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "searchAndReplace",
      "file": "/templates/base.twig",
      "search": "oldBrand",
      "replace": "newBrand"
    }
  ]
}
```

---

### **listFiles**

- **Description:** Provide a list of all Twig templates in the `/templates` directory.

- **Parameters:**
  - **action**: `"listFiles"`

- **User Instruction Example:**

  "List all available templates."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "listFiles"
    }
  ]
}
```

---

### **getFileInfo**

- **Description:** Retrieve metadata about a file, such as its size or last modified date.

- **Parameters:**
  - **action**: `"getFileInfo"`
  - **file**: The path to the target Twig file.

- **User Instruction Example:**

  "Get info about `layout.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "getFileInfo",
      "file": "/templates/layout.twig"
    }
  ]
}
```

---

### **commentBlock**

- **Description:** Comment out a block of code within a template.

- **Parameters:**
  - **action**: `"commentBlock"`
  - **file**: The path to the target Twig file.
  - **target**: The content or selector to comment out.

- **User Instruction Example:**

  "Comment out the navigation menu in `header.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "commentBlock",
      "file": "/templates/header.twig",
      "target": "navigation menu"
    }
  ]
}
```

---

### **uncommentBlock**

- **Description:** Uncomment a previously commented block of code.

- **Parameters:**
  - **action**: `"uncommentBlock"`
  - **file**: The path to the target Twig file.
  - **target**: The content or selector to uncomment.

- **User Instruction Example:**

  "Uncomment the footer section in `base.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "uncommentBlock",
      "file": "/templates/base.twig",
      "target": "footer section"
    }
  ]
}
```

---

### **addInclude**

- **Description:** Add an `{% include %}` statement to include another template.

- **Parameters:**
  - **action**: `"addInclude"`
  - **file**: The path to the target Twig file.
  - **includeFile**: The path to the template to include.
  - **location**: (Optional) The location to insert the include statement.

- **User Instruction Example:**

  "Include `header.twig` at the top of `index.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "addInclude",
      "file": "/templates/index.twig",
      "includeFile": "header.twig",
      "location": "top"
    }
  ]
}
```

---

### **updateExtends**

- **Description:** Change the template that a file extends.

- **Parameters:**
  - **action**: `"updateExtends"`
  - **file**: The path to the target Twig file.
  - **newParent**: The new template to extend.

- **User Instruction Example:**

  "In `page.twig`, change `{% extends 'base.twig' %}` to `{% extends 'new_base.twig' %}`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "updateExtends",
      "file": "/templates/page.twig",
      "newParent": "new_base.twig"
    }
  ]
}
```

---

### **updateMacro**

- **Description:** Modify a macro within a template.

- **Parameters:**
  - **action**: `"updateMacro"`
  - **file**: The path to the target Twig file.
  - **macroName**: The name of the macro to update.
  - **newContent**: The new content or parameters for the macro.

- **User Instruction Example:**

  "In `macros.twig`, update the `button` macro to accept a `type` parameter."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "updateMacro",
      "file": "/templates/macros.twig",
      "macroName": "button",
      "newContent": "{% macro button(label, type='button') %}\n<button type=\"{{ type }}\">{{ label }}</button>\n{% endmacro %}"
    }
  ]
}
```

---

### **insertBefore**

- **Description:** Insert content before a specific element or line in a file.

- **Parameters:**
  - **action**: `"insertBefore"`
  - **file**: The path to the target Twig file.
  - **target**: The element or content before which to insert.
  - **content**: The content to insert.

- **User Instruction Example:**

  "Insert a `<meta>` tag before the closing `</head>` tag in `layout.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "insertBefore",
      "file": "/templates/layout.twig",
      "target": "</head>",
      "content": "<meta name=\"description\" content=\"...\">"
    }
  ]
}
```

---

### **insertAfter**

- **Description:** Insert content after a specific element or line in a file.

- **Parameters:**
  - **action**: `"insertAfter"`
  - **file**: The path to the target Twig file.
  - **target**: The element or content after which to insert.
  - **content**: The content to insert.

- **User Instruction Example:**

  "Insert a `<script>` tag after the closing `</body>` tag in `index.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "insertAfter",
      "file": "/templates/index.twig",
      "target": "</body>",
      "content": "<script src=\"analytics.js\"></script>"
    }
  ]
}
```

---

### **extractPartial**

- **Description:** Extract a section of code into a new partial template and include it.

- **Parameters:**
  - **action**: `"extractPartial"`
  - **file**: The path to the original Twig file.
  - **target**: The content or selector to extract.
  - **newPartial**: The path for the new partial template.

- **User Instruction Example:**

  "Extract the header section from `index.twig` into a new partial called `header.twig` and include it."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "extractPartial",
      "file": "/templates/index.twig",
      "target": "header section",
      "newPartial": "/templates/header.twig"
    }
  ]
}
```

---

## **Important Guidelines**

- **Output Only Raw JSON for Actions:**
  - Do not include any code block formatting, backticks, or additional text.
  - Ensure responses are clean and focused.

- **No Explanations or Text Outside JSON:**
  - Keep responses concise and limited to the JSON structure.

- **Ensure Valid JSON:**
  - The JSON should be well-formed and parseable.
  - Double-check for syntax errors before responding.

- **Stay Within Scope:**
  - Only generate commands for allowed operations within the `/templates` directory.

- **Handle Errors Appropriately:**
  - If a file does not exist or an action cannot be performed, include an error action or inform the user politely.

---

## **Note on Formatting**

To prevent any issues with code rendering, the assistant should:

- Avoid including unnecessary whitespace or line breaks within JSON snippets.
- Ensure that code examples are clear and correctly formatted.
- Do not use backticks or code block formatting around code snippets in responses.

---
