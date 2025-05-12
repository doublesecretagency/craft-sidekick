---
title: "Add Skills | Sidekick plugin for Craft CMS"
description: "One of Sidekick's most powerful features is the ability to extend chat functionality via custom skills."
---

# Add Skills

One of Sidekick's most powerful features is the ability to extend chat functionality via [custom skills](/chat/custom-skills).

Here's a complete guide for adding your own custom skills to Sidekick...

## Listen to the Event

In your plugin or module, listen for the `Sidekick::EVENT_ADD_SKILLS` event.

```php
use doublesecretagency\sidekick\events\AddSkillsEvent;
use doublesecretagency\sidekick\Sidekick;
use modules\mymodule\skills\MyCustomSkills;      // Your custom skills
use modules\mymodule\skills\MyOtherCustomSkills; // Your other custom skills
use yii\base\Event;

// Add skills to the Sidekick AI
Event::on(
    Sidekick::class,
    Sidekick::EVENT_ADD_SKILLS,
    static function (AddSkillsEvent $event) {
    
        // Append your custom skill sets
        $event->skills[] = MyCustomSkills::class;
        $event->skills[] = MyOtherCustomSkills::class; // Add as many as you want
        
    }
);
```

Add each new skill class to the existing `$event->skills` array. You can add as many different skill sets as you'd like.

## Define the new Skills

Create a class for each of the skill sets you want to add, loading them via the `AddSkillsEvent` shown above.

The skill classes can technically be stored anywhere, but we recommend storing them in a `skills` folder within your module or plugin.

Within the class, every `public static` method will be **automatically detected** and loaded as a separate "skill".

```php
namespace modules\mymodule\skills;

use doublesecretagency\sidekick\models\SkillResponse;
use doublesecretagency\sidekick\skills\BaseSkillSet;

/**
 * @category My Skills Category
 */
class MyCustomSkills extends BaseSkillSet
{
   /**
    * A custom function to be triggered via the Sidekick chat window.
    * 
    * This is where you will put the complete instructions
    * for interacting with the custom skill function.
    * 
    * Your instructions here will be passed along to the AI API
    * so it correctly knows how to use this function. 
    *
    * @param string $foo A parameter for the custom function.
    * @param string $bar Another parameter for the custom function.
    * @return SkillResponse A success or error message.
    */
   public static function mySkillFunction(string $foo, string $bar): SkillResponse
   {
       /**
        * Your custom tool function can do whatever you want.
        * 
        * It should return an error message if the operation fails,
        * or a success message if it was successful.
        */
       
        // If validation fails
        if (!$valid) {
            // Return error message
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to {$foo} with {$bar}."
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Successfully performed {$foo} with {$bar}.",
//            'response' => '(any data you want to send back to the API for further processing)'
        ]);
   }
}
```

## The Class

### `extends BaseSkillSet`

You will need to extend `doublesecretagency\sidekick\skills\BaseSkillSet` for full compatibility.

:::tip Restricted Methods
Extending `BaseSkillSet` gives you access to `restrictedMethods` for managing which skills are available under certain conditions. See the [Restricting Methods](#restricting-methods) section below for more details.
:::

### `@category`

Optional, for self-documentation purposes only. Skill set will be categorized in the "What can Sidekick do?" [slideout](/chat/native-skills).

Specify a category (aka group) for your custom skills. If you are building multiple custom skills classes, each class can have a unique or common `@category` value. Classes sharing categories will be grouped together.

:::warning Class Name + Method Name = Max 56 Characters
To compile each skill (aka: tool) name for the OpenAI thread, we are restricted to a maximum length of 64 total characters. Within the Sidekick plugin, we then use eight of those characters to help organize and identify tools. Which leaves you with a grand total of **56 characters maximum** to work with.

In other words, the total length of the **class name** plus the **method name** may not exceed 56 characters.
:::

## The Method

### `public static`

Each individual "skill" must be defined as a `public static` method. This is how Sidekick will recognize and call each function.

### `docblock`

A comprehensive docblock is absolutely critical. Be sure to include a thorough docblock for each method, providing a detailed description of the function and its parameters.

**The method description teaches Sidekick how to use your function!** Be as descriptive as you need to be for Sidekick to understand the function's purpose. Mention any restrictions or requirements to be aware of.

The first line of the docblock will be shown in the [slideout](/chat/native-skills) as the skill description.

### `@param`

Each `@param` must be defined as a string. If necessary, find a way to convert the value to a string.

## Response

### `@return`

The return type must be `SkillResponse`. Sidekick uses this special class to determine the success or failure of the operation.

### `SkillResponse`

Regardless of whether the method succeeds or fails, it must return an instance of `SkillResponse`. To indicate the outcome of the operation, pass an array to the constructor with the following keys:

- _bool_ `success`: A boolean indicating whether the operation was successful.
- _string_ `message`: A string containing the success or error message.
- _string_ `response`: (optional) Any additional information you want to send back to the API for further processing. For complex data, you may send a JSON stringified array. This data will not be displayed to the end user.

## Restricting Methods

To restrict certain skills under specific conditions, use `restrictedMethods` to determine which `public static` methods are available for Sidekick to use.

You may need to restrict a method to **prevent destructive changes** or to **limit access to certain features**.

Within the context of this function, you can restrict a method for any reason you want.

```php
protected function restrictedMethods(): array
{
    // All methods available by default
    $restrictedMethods = [];

    // Get the general config settings
    $config = Craft::$app->getConfig()->getGeneral();

    // Methods unavailable when `allowAdminChanges` is false
    if (!$config->allowAdminChanges) {
        $restrictedMethods[] = 'destructiveMethod';
        $restrictedMethods[] = 'anotherDestructiveMethod';
    }
    
    /**
     * Restrict any other methods for any other reasons.
     */

    // Return list of restricted methods
    return $restrictedMethods;
}
```

:::tip Possible Reasons for Restricting Methods
You can restrict methods for any reason you want, for example:

- Craft version
- Craft edition
- Plugin version
- Environment (dev, staging, production)
- User permissions
- etc.
:::
