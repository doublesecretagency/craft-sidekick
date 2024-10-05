# Actions for Sidekick Assistant

This document outlines all the supported actions that the Sidekick assistant can generate in response to user instructions. Each action includes a description, required parameters, and example usage.

---

## **Supported Actions**

### **1. update_element**

- **Description:** Update the content of a specific HTML element within a Twig template.

- **Parameters:**
  - **action**: `"update_element"`
  - **file**: The path to the target Twig file.
  - **element**: The HTML tag or Twig block to update.
  - **new_value**: The new content to insert.

- **User Instruction Example:**

  "Change the `<h1>` in `index.twig` to 'Welcome to Our Site'"

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "update_element",
      "file": "/templates/index.twig",
      "element": "h1",
      "new_value": "Welcome to Our Site"
    }
  ]
}
```

---

### **2. create_file**

- **Description:** Create a new Twig template file with specified content.

- **Parameters:**
  - **action**: `"create_file"`
  - **file**: The path to the new file.
  - **content**: The content to include in the file.

- **User Instruction Example:**

  "Create a new template called `about.twig` with basic HTML structure."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "create_file",
      "file": "/templates/about.twig",
      "content": "<!DOCTYPE html>\n<html>\n<head>\n    <title>About Us</title>\n</head>\n<body>\n    <!-- Content goes here -->\n</body>\n</html>"
    }
  ]
}
```

---

### **3. delete_file**

- **Description:** Delete an existing Twig template file.

- **Parameters:**
  - **action**: `"delete_file"`
  - **file**: The path to the file to delete.

- **User Instruction Example:**

  "Delete the `old_layout.twig` file."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "delete_file",
      "file": "/templates/old_layout.twig"
    }
  ]
}
```

---

### **4. insert_content**

- **Description:** Insert specific content into a file at a specified location.

- **Parameters:**
  - **action**: `"insert_content"`
  - **file**: The path to the target Twig file.
  - **location**: The reference point for insertion (e.g., `"after_element": "header"`).
  - **content**: The content to insert.

- **User Instruction Example:**

  "Insert a navigation menu after the header in `index.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "insert_content",
      "file": "/templates/index.twig",
      "location": {
        "after_element": "header"
      },
      "content": "<nav>...navigation menu...</nav>"
    }
  ]
}
```

---

### **5. replace_content**

- **Description:** Replace a specific block of content in a file.

- **Parameters:**
  - **action**: `"replace_content"`
  - **file**: The path to the target Twig file.
  - **target**: The content or element to replace.
  - **new_content**: The new content to insert.

- **User Instruction Example:**

  "Replace the footer section in `base.twig` with new content."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "replace_content",
      "file": "/templates/base.twig",
      "target": "footer",
      "new_content": "<footer>...new footer content...</footer>"
    }
  ]
}
```

---

### **6. append_content**

- **Description:** Add content to the end of a file.

- **Parameters:**
  - **action**: `"append_content"`
  - **file**: The path to the target Twig file.
  - **content**: The content to append.

- **User Instruction Example:**

  "Append a script tag to the end of `layout.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "append_content",
      "file": "/templates/layout.twig",
      "content": "<script src='app.js'></script>"
    }
  ]
}
```

---

### **7. prepend_content**

- **Description:** Add content to the beginning of a file.

- **Parameters:**
  - **action**: `"prepend_content"`
  - **file**: The path to the target Twig file.
  - **content**: The content to prepend.

- **User Instruction Example:**

  "Add a comment at the top of `index.twig` noting the last update date."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "prepend_content",
      "file": "/templates/index.twig",
      "content": "{# Last updated on 2023-10-05 #}\n"
    }
  ]
}
```

---

### **8. update_variable**

- **Description:** Change the value of a variable within the template.

- **Parameters:**
  - **action**: `"update_variable"`
  - **file**: The path to the target Twig file.
  - **variable**: The name of the variable to update.
  - **new_value**: The new value for the variable.

- **User Instruction Example:**

  "Set the `siteTitle` variable to 'My New Site' in `config.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "update_variable",
      "file": "/templates/config.twig",
      "variable": "siteTitle",
      "new_value": "My New Site"
    }
  ]
}
```

---

### **9. add_block**

- **Description:** Add a new Twig block to a template.

- **Parameters:**
  - **action**: `"add_block"`
  - **file**: The path to the target Twig file.
  - **block_name**: The name of the new block.
  - **content**: The content of the new block.

- **User Instruction Example:**

  "Add a new block called `sidebar` to `base.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "add_block",
      "file": "/templates/base.twig",
      "block_name": "sidebar",
      "content": "{% block sidebar %}\n<!-- Sidebar content here -->\n{% endblock %}"
    }
  ]
}
```

---

### **10. remove_block**

- **Description:** Remove a Twig block from a template.

- **Parameters:**
  - **action**: `"remove_block"`
  - **file**: The path to the target Twig file.
  - **block_name**: The name of the block to remove.

- **User Instruction Example:**

  "Remove the `advertisement` block from `layout.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "remove_block",
      "file": "/templates/layout.twig",
      "block_name": "advertisement"
    }
  ]
}
```

---

### **11. wrap_content**

- **Description:** Wrap existing content within a new element or block.

- **Parameters:**
  - **action**: `"wrap_content"`
  - **file**: The path to the target Twig file.
  - **target_content**: The content or selector to wrap.
  - **wrapper**: The new element or block to wrap around the target content.

- **User Instruction Example:**

  "Wrap the main content of `index.twig` in a `<div class='container'>`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "wrap_content",
      "file": "/templates/index.twig",
      "target_content": "main",
      "wrapper": "<div class='container'>{{ content }}</div>"
    }
  ]
}
```

---

### **12. modify_attribute**

- **Description:** Change attributes of HTML elements within the template.

- **Parameters:**
  - **action**: `"modify_attribute"`
  - **file**: The path to the target Twig file.
  - **element**: The HTML tag or selector to modify.
  - **attribute**: The attribute to change.
  - **new_value**: The new value for the attribute.

- **User Instruction Example:**

  "Change the class of the `<body>` tag to `homepage` in `index.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "modify_attribute",
      "file": "/templates/index.twig",
      "element": "body",
      "attribute": "class",
      "new_value": "homepage"
    }
  ]
}
```

---

### **13. duplicate_file**

- **Description:** Create a copy of an existing template file.

- **Parameters:**
  - **action**: `"duplicate_file"`
  - **source_file**: The path to the existing file.
  - **destination_file**: The path for the new duplicate file.

- **User Instruction Example:**

  "Duplicate `index.twig` and name the new file `landing.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "duplicate_file",
      "source_file": "/templates/index.twig",
      "destination_file": "/templates/landing.twig"
    }
  ]
}
```

---

### **14. rename_file**

- **Description:** Rename an existing template file.

- **Parameters:**
  - **action**: `"rename_file"`
  - **old_file**: The current path to the file.
  - **new_file**: The new path for the file.

- **User Instruction Example:**

  "Rename `old_home.twig` to `home.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "rename_file",
      "old_file": "/templates/old_home.twig",
      "new_file": "/templates/home.twig"
    }
  ]
}
```

---

### **15. search_and_replace**

- **Description:** Search for a specific string in a file and replace it with another string.

- **Parameters:**
  - **action**: `"search_and_replace"`
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
      "action": "search_and_replace",
      "file": "/templates/base.twig",
      "search": "oldBrand",
      "replace": "newBrand"
    }
  ]
}
```

---

### **16. list_files**

- **Description:** Provide a list of all Twig templates in the `/templates` directory.

- **Parameters:**
  - **action**: `"list_files"`

- **User Instruction Example:**

  "List all available templates."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "list_files"
    }
  ]
}
```

---

### **17. get_file_info**

- **Description:** Retrieve metadata about a file, such as its size or last modified date.

- **Parameters:**
  - **action**: `"get_file_info"`
  - **file**: The path to the target Twig file.

- **User Instruction Example:**

  "Get info about `layout.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "get_file_info",
      "file": "/templates/layout.twig"
    }
  ]
}
```

---

### **18. comment_block**

- **Description:** Comment out a block of code within a template.

- **Parameters:**
  - **action**: `"comment_block"`
  - **file**: The path to the target Twig file.
  - **target**: The content or selector to comment out.

- **User Instruction Example:**

  "Comment out the navigation menu in `header.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "comment_block",
      "file": "/templates/header.twig",
      "target": "navigation menu"
    }
  ]
}
```

---

### **19. uncomment_block**

- **Description:** Uncomment a previously commented block of code.

- **Parameters:**
  - **action**: `"uncomment_block"`
  - **file**: The path to the target Twig file.
  - **target**: The content or selector to uncomment.

- **User Instruction Example:**

  "Uncomment the footer section in `base.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "uncomment_block",
      "file": "/templates/base.twig",
      "target": "footer section"
    }
  ]
}
```

---

### **20. add_include**

- **Description:** Add an `{% include %}` statement to include another template.

- **Parameters:**
  - **action**: `"add_include"`
  - **file**: The path to the target Twig file.
  - **include_file**: The path to the template to include.
  - **location**: (Optional) The location to insert the include statement.

- **User Instruction Example:**

  "Include `header.twig` at the top of `index.twig`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "add_include",
      "file": "/templates/index.twig",
      "include_file": "header.twig",
      "location": "top"
    }
  ]
}
```

---

### **21. update_extends**

- **Description:** Change the template that a file extends.

- **Parameters:**
  - **action**: `"update_extends"`
  - **file**: The path to the target Twig file.
  - **new_parent**: The new template to extend.

- **User Instruction Example:**

  "In `page.twig`, change `{% extends 'base.twig' %}` to `{% extends 'new_base.twig' %}`."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "update_extends",
      "file": "/templates/page.twig",
      "new_parent": "new_base.twig"
    }
  ]
}
```

---

### **22. update_macro**

- **Description:** Modify a macro within a template.

- **Parameters:**
  - **action**: `"update_macro"`
  - **file**: The path to the target Twig file.
  - **macro_name**: The name of the macro to update.
  - **new_content**: The new content or parameters for the macro.

- **User Instruction Example:**

  "In `macros.twig`, update the `button` macro to accept a `type` parameter."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "update_macro",
      "file": "/templates/macros.twig",
      "macro_name": "button",
      "new_content": "{% macro button(label, type='button') %}\n<button type=\"{{ type }}\">{{ label }}</button>\n{% endmacro %}"
    }
  ]
}
```

---

### **23. insert_before**

- **Description:** Insert content before a specific element or line in a file.

- **Parameters:**
  - **action**: `"insert_before"`
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
      "action": "insert_before",
      "file": "/templates/layout.twig",
      "target": "</head>",
      "content": "<meta name=\"description\" content=\"...\">"
    }
  ]
}
```

---

### **24. insert_after**

- **Description:** Insert content after a specific element or line in a file.

- **Parameters:**
  - **action**: `"insert_after"`
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
      "action": "insert_after",
      "file": "/templates/index.twig",
      "target": "</body>",
      "content": "<script src=\"analytics.js\"></script>"
    }
  ]
}
```

---

### **25. extract_partial**

- **Description:** Extract a section of code into a new partial template and include it.

- **Parameters:**
  - **action**: `"extract_partial"`
  - **file**: The path to the original Twig file.
  - **target**: The content or selector to extract.
  - **new_partial**: The path for the new partial template.

- **User Instruction Example:**

  "Extract the header section from `index.twig` into a new partial called `header.twig` and include it."

- **Assistant JSON Response:**

```json
{
  "actions": [
    {
      "action": "extract_partial",
      "file": "/templates/index.twig",
      "target": "header section",
      "new_partial": "/templates/header.twig"
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
