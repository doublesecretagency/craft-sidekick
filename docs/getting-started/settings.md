---
title: "Settings Page | Sidekick plugin for Craft CMS"
description: "Manage the OpenAI API key and other settings for Sidekick."
---

# Settings Page

To access the plugin settings, log into your control panel and visit **Settings > Sidekick**.

Depending on your preference, you can alternatively use the [PHP config file](/getting-started/config) to manage settings.

## OpenAI API Key

Sidekick requires an OpenAI API key to function. You can obtain one from your [OpenAI account](https://platform.openai.com/account/api-keys).

Once you have an API key, enter it into the **OpenAI API Key** field and save the settings.

<img class="dropshadow" src="/images/settings/openai-api-key.png" alt="OpenAI API key field" style="max-width:485px">

:::warning Use an environment variable!
For security reasons, it is _very highly recommended_ to store your API key in an environment variable. (see below)
:::

### Using Environment Variables

It's recommended to use an [environment variable](https://craftcms.com/docs/4.x/config/#control-panel-settings) for your API key to enhance security and portability.

1. Add the following to your `.env` file:

```dotenv
OPENAI_API_KEY="your-openai-api-key"
```

2. Then simply reference the environment variable in the settings field using the `$` syntax:

<img class="dropshadow" src="/images/settings/openai-api-key-env-var.png" alt="OpenAI API key field using an environment variable" style="max-width:485px; margin-bottom:30px">

That's it! After saving the settings, Sidekick will use your API key via the environment variable.
