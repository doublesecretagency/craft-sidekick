---
title: "Custom Skills | Sidekick plugin for Craft CMS"
description: "In addition to the native skills that come with Sidekick, you can also create your own custom skills."
---

# Custom Skills

In addition to the [native skills](/chat/native-skills) that come with Sidekick, you can also create your own custom skills. This allows you to give Sidekick new powers which it doesn't inherently have out-of-the-box.

:::warning Automatically Added to Skills List
When you add custom skills, they will automatically be added to the list of available skills.
:::

## Adding Custom Skills

Generally speaking, you will:

1. Create a custom class (ie: `MyCustomSkills`).
2. Add each individual "skill" as a `public static` function.
3. Load the class via the `AddSkillsEvent`.

Check out the [**Add Skills**](/customize/add-skills) page for complete detailed instructions.

## What can I do with Custom Skills?

**There is virtually no limit to what you can trigger with custom skills!** As long as it can be wrapped in PHP, it can be triggered via the Sidekick chat window.

### Some hypothetical examples
- [Add an event to a calendar](/customize/examples/add-to-calendar)
- [Create a custom report](/customize/examples/create-report)
- [Send an email](/customize/examples/send-an-email)
