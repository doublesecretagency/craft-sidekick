---
title: "Custom Skills | Sidekick plugin for Craft CMS"
description: "In addition to the native skills that come with Sidekick, you can also create your own custom skills."
---

# Custom Skills

In addition to the [native skills](/native-skills/) that come with Sidekick, you can also create your own custom skills. This allows you to give Sidekick new powers which it doesn't inherently have out-of-the-box.

One of the most powerful features is the ability to extend Sidekick's functionality through the `AddSkillsEvent`.

**There is virtually no limit to what you can trigger with custom skills!** As long as it can be wrapped in PHP, it can be triggered via the Sidekick chat window.

See some practical examples of what's possible with custom skills:

- [Add to Calendar](/custom-skills/examples/add-to-calendar)
- [Send an Email](/custom-skills/examples/send-an-email)

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
    function(AddSkillsEvent $event) {
        // Append your custom skill sets
        $event->skills[] = MyCustomSkills::class;
        $event->skills[] = MyOtherCustomSkills::class; // Add as many as you want
    }
);
```

Add each new skill set class to the existing `$event->skills` array. You can add as many different skill sets as you'd like.

## Define the new Skills

Create a class for each of the skill sets you want to add. It will be composed primarily of `public static` methods.

```php
namespace modules\mymodule\skills;

use doublesecretagency\sidekick\models\SkillResponse;

/**
 * @category My Skills Category
 */
class MyCustomSkills
{
   /**
    * A custom function to be triggered via the Sidekick chat window.
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

## Detailed Breakdown

Each important component is described below (listed in order of appearance)...

### `@category`

For self-documenting purposes only. See the results in the "What can Sidekick do?" [slideout](/native-skills/).

Specify a category (aka group) for your custom skills. If you are building multiple custom skills classes, each class can have a unique or common `@category` value. Classes sharing categories will be grouped together.

### `public static`

Each individual "skill" must be defined as a `public static` method. This is how Sidekick will recognize and call each function.

### `docblock`

A comprehensive docblock is absolutely critical. Be sure to include a thorough docblock for each method, providing a detailed description of the function and its parameters.

**The method description teaches Sidekick how to use your function!** Be as descriptive as you need to be for Sidekick to understand the function's purpose. Mention any restrictions or requirements to be aware of.

### `@param`

Each `@param` must be defined as a string. If necessary, find a way to convert the value to a string.

### `@return`

The return type must be `SkillResponse`. Sidekick uses this special class to determine the success or failure of the operation.

### `SkillResponse`

Regardless of whether the method succeeds or fails, it must return an instance of `SkillResponse`. To indicate the outcome of the operation, pass an array to the constructor with the following keys:

- _bool_ `success`: A boolean indicating whether the operation was successful.
- _string_ `message`: A string containing the success or error message.
- _string_ `response`: (optional) Any additional information you want to send back to the API for further processing. For complex data, you may send a JSON stringified array. This data will not be displayed to the end user.

## Class Name + Method Name = Max 56 Characters

To compile each skill (aka: tool) name for the OpenAI thread, we are restricted to a maximum length of 64 total characters. Within the Sidekick plugin, we then use eight of those characters to help organize and identify tools. Which leaves you with a grand total of **56 characters maximum** to work with.

In other words, the total length of the **class name** plus the **method name** may not exceed 56 characters.
