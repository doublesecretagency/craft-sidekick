---
description: An example of a custom skill which adds an event to the calendar.
---

# Add to Calendar

## Sample Conversation

<div class="chat-window">
    <div class="chat-message user-message">
        <div class="sender-column">You:</div>
        <div class="content-column"><p>add Team Meeting to the calendar on Oct 15th at 10</p></div>
    </div>
    <div class="chat-message tool-message">
        <div class="sender-column"></div>
        <div class="content-column"><p>The event "Team Meeting" has been added to the calendar for October 15, 2025 at 10:00 AM.</p></div>
    </div>
    <div class="chat-message assistant-message">
        <div class="sender-column">Sidekick:</div>
        <div class="content-column"><p>I've scheduled the Team Meeting for October 15th at 10:00 am. Can help with anything else?</p></div>
    </div>
</div>

## Sample Code

```php
/**
 * Adds an event to the calendar.
 *
 * @param string $event Description of the event.
 * @param string $datetime Datetime of the event.
 * @return SkillResponse
 */
public static function addCalendarEvent(string $event, string $datetime): SkillResponse
{
    /**
     * Your custom implementation for adding an event to the calendar.
     * 
     * For example, create a new entry in the 'events' section
     * with the provided details.
     */
       
    // If validation fails
    if (!$valid) {
        // Return error message
        return new SkillResponse([
            'success' => false,
            'message' => "Unable to add {$event} at {$datetime}."
        ]);
    }

    // Return success message
    return new SkillResponse([
        'success' => true,
        'message' => "Successfully added {$event} at {$datetime}.",
//        'response' => '(any data you want to send back to the API for further processing)'
    ]);
}
```
