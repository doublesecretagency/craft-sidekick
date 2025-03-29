# Add Skills

One of Sidekick's most powerful features is the ability to extend its functionality through the **Add Skills** event.

## What Is the Add Skills Event?

- It allows developers to **add custom functions** which Sidekick can then use to perform actions.
- These functions can be defined in your plugins or modules and made available to the AI assistant.
- This opens up endless possibilities for integrating Sidekick with your custom workflows.

## Benefits

- **Customization**: Tailor Sidekick to fit your specific needs.
- **Integration**: Seamlessly integrate with other plugins or custom code.
- **Extendability**: Add as many tools as you require, enhancing Sidekick's capabilities.

## How to Use It

1. **Listen to the Event**: In your plugin or module, listen for the `EVENT_ADD_SKILLS` event.

```php
use doublesecretagency\sidekick\events\AddSkillsEvent;
use doublesecretagency\sidekick\Sidekick;
use yii\base\Event;

// Define extra tools for the Sidekick AI
Event::on(
    Sidekick::class,
    Sidekick::EVENT_ADD_SKILLS,
    function(AddSkillsEvent $event) {
        // Add your custom tools to the Sidekick AI
        $event->skills[] = MyCustomSkills::class;
    }
);
```

2. **Define Your Tools**: Create a class with static methods for each of the skill sets you want to add.

Each method has a maximum name length. The total length of the **class name** plus the **method name** may not exceed 56 characters.

::: warning The docblock is critical!
Make sure to include a thorough docblock for each method, providing a description of the function, its parameters, and its return value. **This documentation teaches Sidekick how to use your function!**
:::

```php
namespace modules\mymodule\skills;

use doublesecretagency\sidekick\models\SkillResponse;

class MyCustomSkills
{
   /**
    * A custom function to be triggered via the Sidekick chat window.
    *
    * @param string $foo A parameter for the custom function.
    * @param string $bar Another parameter for the custom function.
    * @return array A success or error message.
    */
   public static function mySkillFunction(string $foo, string $bar): array
   {
       /**
        * Your custom tool function can do whatever you want.
        * It should return an array with a success message if the operation was successful,
        * or an error message if the operation failed.
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

Regardless of whether the method succeeds or fails, it must return an instance of `SkillResponse`. Pass an array to the constructor with the following keys to indicate the outcome of the operation:

The method must return an array with two keys:
- _bool_ `success`: A boolean indicating whether the operation was successful.
- _string_ `message`: A string containing the success or error message.
- _string_ `response`: (optional) Any additional information you want to send back to the API for further processing. For complex data, you may send a JSON stringified array.

To see what's possible, check out some of the [Custom Skills](/examples/) examples.

**There is virtually no limit to what you can trigger with custom tools!** As long as it can be wrapped in PHP, it can be triggered via the Sidekick chat window.

