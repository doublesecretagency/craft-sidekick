---
title: "Add Prompts | Sidekick plugin for Craft CMS"
description: "To adjust how Sidekick interacts with users, or provide complex directives on a given subject, you can apply custom prompts to guide the AI."
---

# Add Prompts

To adjust how Sidekick interacts with users, or provide complex directives on a given subject, you can apply custom prompts to guide the AI.

Here's a complete guide for adding your own custom prompts to Sidekick...

## Listen to the Event

In your plugin or module, listen for the `Sidekick::EVENT_ADD_PROMPTS` event.

```php
use doublesecretagency\sidekick\events\AddPromptsEvent;
use doublesecretagency\sidekick\Sidekick;
use yii\base\Event;

// Add prompts to the Sidekick AI
Event::on(
    Sidekick::class,
    Sidekick::EVENT_ADD_PROMPTS,
    static function (AddPromptsEvent $event) {

        // Get path to the module or plugin
        $path = Craft::getAlias('@modules/mycustommodule');

        // Append your custom prompts
        $event->prompts[] = "{$path}/prompts/my-custom-prompt.md";
        $event->prompts[] = "{$path}/prompts/my-other-custom-prompt.md"; // Add as many as you want

    }
);
```

Add each new prompt file to the existing `$event->prompts` array. You can add as many different prompts as you'd like.

:::tip Markdown Recommended
You can technically use any text-based file to provide prompts, but Markdown files are preferred.
:::

## Write the new Prompts

Create a Markdown file for each of the prompts you want to add, loading them via the `AddPromptsEvent` shown above.

The prompt files can technically be stored anywhere, but we recommend storing them in a `prompts` folder within your module or plugin.

Within the context of your prompt file, you can give the AI any instructions that you want.

```markdown
# My Custom Prompt

This is a custom prompt that will be loaded into the AI.
It can give the AI any additional instructions it might need.

Within this file, you can specify things like:

- The AI's personality
- How to handle certain situations
- How to respond to certain types of questions
- How to understand certain architectural patterns
- And more!

This file can be as elaborate as you need it to be.

If you have a lot of custom instructions, you can even break them up
into multiple files and load them all via the `AddPromptsEvent` (shown above).
```
