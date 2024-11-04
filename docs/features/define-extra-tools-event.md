# Define Extra Tools Event

One of Sidekick's most powerful features is the ability to extend its functionality through the **Define Extra Tools** event.

## What Is the Define Extra Tools Event?

- It allows developers to **add custom tools** that Sidekick can use to perform actions.
- These tools can be defined in your plugins or modules and made available to the AI assistant.
- This opens up endless possibilities for integrating Sidekick with your custom workflows.

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

```php
namespace mynamespace;

class MyCustomTools
{
   /**
    * Send an email message to a specified User.
    *
    * @param string $user Name of the user to send the email to.
    * @param string $subject Subject line for the outgoing email.
    * @param string $body Message body for the outgoing email.
    * @return array
    */
   public static function sendEmailMessage(array $args): array
   {
        // Get parameters
        $user    = $args['user']    ?? null;
        $subject = $args['subject'] ?? null;
        $body    = $args['body']    ?? null;

       // Your implementation to send an email message
       // to a specified User with the provided details.
   }

   /**
    * Adds an event to the calendar.
    *
    * @param string $event Description of the event.
    * @param string $datetime Datetime of the event.
    * @return array
    */
   public static function addCalendarEvent(array $args): array
   {
        $event    = $args['event']    ?? null;
        $datetime = $args['datetime'] ?? null;
        
       // Your implementation to add an event to the calendar
       // For example, create a new entry in the 'events' section
       // with the provided details.
   }
}
```

3. **Utilize in Chat**: You can now instruct Sidekick to perform actions using your custom tool.

**You:** "Add an event titled 'Team Meeting' to the calendar on October 15th at 10 AM."

**Sidekick:** "I've added the event 'Team Meeting' to your calendar on October 15th at 10 AM."

## Benefits

- **Customization**: Tailor Sidekick to fit your specific needs.
- **Integration**: Seamlessly integrate with other plugins or custom code.
- **Extendability**: Add as many tools as you require, enhancing Sidekick's capabilities.
