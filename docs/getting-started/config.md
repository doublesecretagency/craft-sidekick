---
title: "PHP Config File | Sidekick plugin for Craft CMS"
description: "Using a PHP config file, you can override the plugin's settings. Find out how to configure the plugin, even across different environments."
---

# PHP Config File

Everything on the plugin's [Settings](settings.md) page can also be managed via PHP in a config file. By setting these values in `config/sidekick.php`, they take precedence over whatever may be set in the control panel.

```shell
# Copy this file...
/vendor/doublesecretagency/craft-sidekick/src/config.php

# To here... (and rename it)
/config/sidekick.php
```

Much like the `db.php` and `general.php` files, `sidekick.php` is [environmentally aware](https://craftcms.com/docs/4.x/config/#multi-environment-configs). You can also pass in environment values using the `getenv` PHP method.

```php
return [
    // OpenAI API Key
    'openAiApiKey' => getenv('OPENAI_API_KEY')
];
```

## Settings Available via Control Panel

The OpenAI API key and other settings can also be managed on the [Settings](/getting-started/settings) page (preferably using `env` values).

### `openAiApiKey`

_string_ - Defaults to `null`.

The OpenAI API key to use for all requests.
