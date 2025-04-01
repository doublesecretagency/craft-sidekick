---
description: An example of a custom skill which adds an event to the calendar.
---

# Add to Calendar

<div class="chat-window">
  <div class="user-message"><strong>You:</strong> Add an event titled "Team Meeting" to the calendar on October 15th at 10 AM.</div>
  <div class="system-message">The event "Team Meeting" has been added to the calendar for October 15, 2024 at 10:00 AM.</div>
  <div class="assistant-message"><strong>Sidekick:</strong> I've added the event 'Team Meeting' to your calendar on October 15th at 10 AM.</div>
</div>

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
