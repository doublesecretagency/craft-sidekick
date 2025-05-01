---
description: An example of a custom skill which sends an email to a specified user.
---

# Send an Email

## Sample Conversation

<div class="chat-window">
    <div class="chat-message user-message">
        <div class="sender-column">You:</div>
        <div class="content-column"><p>email Doug to remind him about tomorrow's meeting</p></div>
    </div>
    <div class="chat-message tool-message">
        <div class="sender-column"></div>
        <div class="content-column"><p>Email sent to doug@example.com with the subject "Reminder about tomorrow's meeting".</p></div>
    </div>
    <div class="chat-message assistant-message">
        <div class="sender-column">Sidekick:</div>
        <div class="content-column"><p>I've sent a reminder email to Doug. Would you like assistance with anything else?</p></div>
    </div>
</div>

## Sample Code

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
