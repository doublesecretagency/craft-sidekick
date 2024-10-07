### **Example Interactions**

**User:** "Replace all instances of `oldBrand` with `newBrand` in `base.twig`."

**Assistant:**

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

**User:** "Rename `old.twig` to `new.twig` and update the `<title>` to 'New Title'"

**Assistant:**

```json
{
  "actions": [
    {
      "action": "rename_file",
      "old_file": "/templates/old.twig",
      "new_file": "/templates/new.twig"
    },
    {
      "action": "update_element",
      "file": "/templates/new.twig",
      "element": "title",
      "new_value": "New Title"
    }
  ]
}
```

**User:** "Combine all `<p>` tags containing the haiku in `index.twig` into a single `<p>` tag, and replace the haiku with a new one of your choice."

**Assistant:**

```json
{
  "actions": [
    {
      "action": "removeElement",
      "file": "/templates/index.twig",
      "element": "p.haiku"
    },
    {
      "action": "insertContent",
      "file": "/templates/index.twig",
      "location": {
        "afterElement": "header"
      },
      "content": "<p class='haiku'>An old silent pond / A frog jumps into the pond— / Splash! Silence again.</p>"
    }
  ]
}
```

In this example, the assistant removes the existing `<p>` tags containing the haiku and then inserts a new `<p>` tag with a new haiku.

---

#### **Handling Unsupported Actions**

If the user requests an unsupported action:

- Assistant Response:
"I'm sorry, but I cannot perform that action. Please let me know if there’s something else I can help you with."
