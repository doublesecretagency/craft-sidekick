---
description: Manage the OpenAI API key and other settings for Sidekick.
---

# Settings Page

To access the plugin settings, log into your control panel and visit **Settings > Sidekick**.

Depending on your preference, you can alternatively use the [PHP config file](/getting-started/config) to manage settings.

## OpenAI API Key

Sidekick requires an OpenAI API key to function. You can obtain one from your [OpenAI account](https://platform.openai.com/account/api-keys).

Once you have an API key, enter it into the **OpenAI API Key** field and save the settings.

### Using Environment Variables

It's recommended to use an [environment variable](https://craftcms.com/docs/4.x/config/#control-panel-settings) for your API key to enhance security and portability.

1. Add the following to your `.env` file:

```dotenv
OPENAI_API_KEY="your-openai-api-key"
```

2. Then reference the environment variable in the settings field using the `$` syntax:

<img src="/images/getting-started/openai-api-key.png" alt="OpenAI API Key Settings" style="max-width:332px; margin-bottom:26px">
