# Define Extra Tools Event

One of Sidekick's most powerful features is the ability to extend its functionality through the **Define Extra Tools** event.

## What Is the Define Extra Tools Event?

- It allows developers to **add custom tools** that Sidekick can use to perform actions.
- These tools can be defined in your plugins or modules and made available to the AI assistant.
- This opens up endless possibilities for integrating Sidekick with your custom workflows.

## Benefits

- **Customization**: Tailor Sidekick to fit your specific needs.
- **Integration**: Seamlessly integrate with other plugins or custom code.
- **Extendability**: Add as many tools as you require, enhancing Sidekick's capabilities.

## How to Use It

1. **Listen to the Event**: In your plugin or module, listen for the `EVENT_DEFINE_EXTRA_TOOLS` event.

```php
use doublesecretagency\sidekick\events\DefineExtraToolsEvent;
use doublesecretagency\sidekick\services\OpenAIService;
use yii\base\Event;

// Define extra tools for the Sidekick AI
Event::on(
    OpenAIService::class,
    OpenAIService::EVENT_DEFINE_EXTRA_TOOLS,
    function(DefineExtraToolsEvent $event) {
        // Add your custom tools to the Sidekick AI
        $event->extraTools[] = MyCustomTools::class;
    }
);
```

2. **Define Your Tools**: Create a class with static methods for each of the tools you want to add.

::: warning The docblock is critical!
Make sure to include a thorough docblock for each method, providing a description of the tool, its parameters, and its return value. **This documentation teaches Sidekick how to use your tool.**
:::

```php
namespace modules\mymodule\tools;

class MyCustomTools
{
   /**
    * A custom tool function to be triggered via the Sidekick chat window.
    *
    * @param string $foo A parameter for the custom tool function.
    * @param string $bar Another parameter for the custom tool function.
    * @return array A success or error message.
    */
   public static function myToolFunction(string $foo, string $bar): array
   {
       /**
        * Your custom tool function can do whatever you want.
        * It should return an array with a success message if the operation was successful,
        * or an error message if the operation failed.
        */
       
        // If validation fails
        if (!$valid) {
            // Return error message
            return [
                'success' => false,
                'message' => "Unable to {$foo} with {$bar}."
            ];
        }

        // Return success message
        return [
            'success' => true,
            'message' => "Successfully performed {$foo} with {$bar}."
        ];
   }
}
```

The method must return an array with two keys:
- `success`: A boolean indicating whether the operation was successful.
- `message`: A string containing a message to either:
  - **On error:** Display in the chat window. 
  - **On success:** Send back to the API for further processing.

To see what's possible, check out some of the [Custom Tools](/examples/) examples.

**There is virtually no limit to what you can trigger with custom tools!** As long as it can be wrapped in PHP, it can be triggered via the Sidekick chat window.
