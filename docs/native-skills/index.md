---
title: "Native Skills | Sidekick plugin for Craft CMS"
description: "Sidekick currently boasts a small but powerful set of skills, like managing templates, entries, and sections."
head:
  - - meta
    - property: "og:type"
      content: "website"
  - - meta
    - property: "og:url"
      content: "https://plugins.doublesecretagency.com/sidekick/fields/native-skills/"
  - - meta
    - property: "og:title"
      content: "Native Skills | Sidekick plugin for Craft CMS"
  - - meta
    - property: "og:description"
      content: "Sidekick currently boasts a small but powerful set of skills, like managing templates, entries, and sections."
  - - meta
    - property: "og:image"
      content: "https://plugins.doublesecretagency.com/sidekick/images/chat-window/skills-slideout.png"
  - - meta
    - name: "twitter:card"
      content: "summary_large_image"
  - - meta
    - name: "twitter:url"
      content: "https://plugins.doublesecretagency.com/sidekick/fields/native-skills/"
  - - meta
    - name: "twitter:title"
      content: "Native Skills | Sidekick plugin for Craft CMS"
  - - meta
    - name: "twitter:description"
      content: "Sidekick currently boasts a small but powerful set of skills, like managing templates, entries, and sections."
  - - meta
    - name: "twitter:image"
      content: "https://plugins.doublesecretagency.com/sidekick/images/chat-window/skills-slideout.png"
---

# Native Skills

Sidekick boasts a small but powerful set of skills available via the chat window. If you need something beyond what is shown here, it's very easy to create your own [custom skills](/custom-skills/).

:::warning See the complete list
For a comprehensive list of what Sidekick can do, click the "i" icon above the chat window. A slideout will reveal the complete list of available skill sets.
:::

<img class="dropshadow" src="/images/chat-window/skills-slideout.png" alt="Screenshot of slideout revealing the complete list of available skill sets" style="max-width:832px">

## Permissions & Capabilities

Sidekick's editing permissions depend on your `allowAdminChanges` setting:

- **`allowAdminChanges` = `false` (Production):** Sidekick primarily has **read-only** access.
- **`allowAdminChanges` = `true` (Local Development):** Sidekick can **create**, **update**, and **delete** content.

### Templates

- **Production:** Read-only access to the `templates` folder.
- **Local Development:** Full access—create, update, and delete text-based files and folders.

### Entries

- Full CRUD (Create, Read, Update, Delete) functionality.
- Deletion actions require confirmation.

### Fields

- **Production:** View existing fields.
- **Local Development:** Full management capabilities—create, update, delete, and configure fields.

### Sections

- **Production:** View existing sections.
- **Local Development:** Full management capabilities—create, update, delete, and configure sections, entry types, and field layouts.

:::tip Need More?
You can always expand on this basic set of skills with your own [custom skills](/custom-skills/). This is a great way to add functionality that is specific to your project or workflow.

Any additional skills provided by a separate plugin or module will automatically be included in the slideout list of skills.
:::
