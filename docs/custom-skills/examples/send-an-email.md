---
description: An example of a custom skill which sends an email to a specified user.
---

# Send an Email

<div class="chat-window">
  <div class="user-message"><strong>You:</strong> Send an email to Bob reminding him about tomorrow's meeting.</div>
  <div class="system-message">An email has been sent to bob@example.com with the subject "Reminder about tomorrow's meeting".</div>
  <div class="assistant-message"><strong>Sidekick:</strong> I've sent a reminder email to Bob.</div>
</div>

```php
/**
 * Send an email message to a specified User.
 *
 * @param string $user Name of the user to send the email to.
 * @param string $subject Subject line for the outgoing email.
 * @param string $body Message body for the outgoing email.
 * @return SkillResponse
 */
public static function sendEmailMessage(string $user, string $subject, string $body): SkillResponse
{
    /**
     * Your custom implementation for sending an email message.
     * 
     * For example, determine which user is being referenced
     * and send them an email with the provided subject and body.
     */

    // If validation fails
    if (!$valid) {
        // Return error message
        return new SkillResponse([
            'success' => false,
            'message' => "Unable send an email to {$user} with the subject {$subject}."
        ]);
    }

    // Return success message
    return new SkillResponse([
        'success' => true,
        'message' => "Successfully sent an email to {$user} with the subject {$subject}.",
//        'response' => '(any data you want to send back to the API for further processing)'
    ]);
}
```
