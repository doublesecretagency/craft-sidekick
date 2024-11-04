---
description: Manage the OpenAI API key and other settings for Sidekick.
---

# Settings Page

To access the plugin settings, log into your control panel and visit **Settings > Sidekick**.

## OpenAI API Key

Sidekick requires an OpenAI API key to function. You can obtain one from your [OpenAI account](https://platform.openai.com/account/api-keys).

1. Enter your OpenAI API key in the **API Key** field.

[//]: # (   <img src="/images/settings/openai-api-key.png" alt="OpenAI API Key Settings" style="width:650px; margin-top:10px">)

2. **Save** the settings.

### Using Environment Variables

It's recommended to use an [environment variable](https://craftcms.com/docs/4.x/config/#control-panel-settings) for your API key to enhance security and portability.

1. Add the following to your `.env` file:

```dotenv
OPENAI_API_KEY="your-openai-api-key"
```

2. Reference the environment variable in the settings field using `$` syntax:

```
$OPENAI_API_KEY
```

## AI Model Selection

- Choose between different AI models depending on your requirements.
- **Note**: Changing the model will take effect **only after starting a new conversation**.

## Additional Settings

- **Timeouts**: Configure request timeouts as needed.
- **Logging**: While Sidekick does not maintain a separate logging system, it utilizes Craft’s native logging.
