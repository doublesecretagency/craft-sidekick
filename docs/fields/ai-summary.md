---
title: "AI Summary Field Type | Sidekick plugin for Craft CMS"
description: "Using an AI Summary field, you can generate concise and accurate overviews of your entries. Great for SEO descriptions!"
head:
  - - meta
    - property: "og:type"
      content: "website"
  - - meta
    - property: "og:url"
      content: "https://plugins.doublesecretagency.com/sidekick/fields/ai-summary"
  - - meta
    - property: "og:title"
      content: "AI Summary Field Type | Sidekick plugin for Craft CMS"
  - - meta
    - property: "og:description"
      content: "Using an AI Summary field, you can generate concise and accurate overviews of your entries. Great for SEO descriptions!"
  - - meta
    - property: "og:image"
      content: "https://plugins.doublesecretagency.com/sidekick/images/fields/ai-summary-example.png"
  - - meta
    - name: "twitter:card"
      content: "summary_large_image"
  - - meta
    - name: "twitter:url"
      content: "https://plugins.doublesecretagency.com/sidekick/fields/ai-summary"
  - - meta
    - name: "twitter:title"
      content: "AI Summary Field Type | Sidekick plugin for Craft CMS"
  - - meta
    - name: "twitter:description"
      content: "Using an AI Summary field, you can generate concise and accurate overviews of your entries. Great for SEO descriptions!"
  - - meta
    - name: "twitter:image"
      content: "https://plugins.doublesecretagency.com/sidekick/images/fields/ai-summary-example.png"
---

# "AI Summary" Field Type

Quickly generate a summary of your content via the magic of AI...

<img class="dropshadow" src="/images/fields/ai-summary-instructions.png" alt="Example of instructions given to the AI for processing the field" style="max-width:566px">

There are a wide variety of things you may want to summarize, such as:

- A short description of an entry.
- Bullet points of an entry's key features.
- A list of key names or phrases in an article.
- Suggestions for further research.

Use your imagination, the field can summarize just about anything!

:::tip Flexible Formats
You can also generate values in JSON or Markdown, just by specifying the format in the instructions.
:::

### Useful for SEO

⭐️ **Full support for SEOmatic, SEOMate, Ether SEO, and custom SEO meta fields** ⭐️

As you may have guessed, this field pairs extremely well with your existing SEO configuration. Simply use an AI Summary field as your SEO description field (or even another for SEO keywords) and let the description be generated for you automatically.

<img class="dropshadow" src="/images/fields/ai-summary-example.png" alt="Example of the AI Summary field being used for an SEO description" style="max-width:558px">

## How does it work?

### Automatically Generate a Summary

Much like the [Preparse](https://plugins.craftcms.com/preparse-field) field type, an AI Summary field can automatically generate a summary of your content when the entry is saved. This behavior is optional, and **disabled by default**.

If you've opted to have the summary be generated automatically, you'll also need to specify whether existing values should be replaced on each save.

<img class="dropshadow" src="/images/fields/ai-summary-auto-generate.png" alt="Two fields relevant to automatically generating summaries" style="max-width:552px">

:::warning Auto Generating can be a Time-Consuming Process
Be wary, automatically generating a summary can lead to notable lag time. The request must be sent to the AI service, while your server waits patiently for a response.
:::

### Manually Generate a Summary

By default, automatic summary generation is disabled, allowing you full control to manually generate a summary at your convenience.

Simply click the **Regenerate** button below the field to generate a new summary. It will send a request to the AI service and update your field with the new summary.

:::tip Summary Instructions
To see the field's summary instructions (the directives for the AI service), hover over the "i" icon beneath the field.
:::
