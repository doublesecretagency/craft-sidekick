# Send An Email

<div class="chat-window">
  <div class="user-message"><strong>You:</strong> Add an event titled "Team Meeting" to the calendar on October 15th at 10 AM.</div>
  <div class="system-message">The event "Team Meeting" has been added to the calendar for October 15, 2024 at 10:00 AM.</div>
  <div class="assistant-message"><strong>Sidekick:</strong> I've added the event 'Team Meeting' to your calendar on October 15th at 10 AM.</div>
</div>

```php
/**
* Send an email message to a specified User.
*
* @param string $user Name of the user to send the email to.
* @param string $subject Subject line for the outgoing email.
* @param string $body Message body for the outgoing email.
* @return array
*/
public static function sendEmailMessage(string $user, string $subject, string $body): array
{
   // Your implementation to send an email message
   // to a specified User with the provided details.
}
```
