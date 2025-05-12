---
title: "Example: Create a Report | Sidekick plugin for Craft CMS"
description: "An example of a custom skill which creates a report with specified parameters."
---

# Example: Create a Report

<div class="chat-window" style="margin-top:23px">
    <div class="chat-message user-message">
        <div class="sender-column">You:</div>
        <div class="content-column"><p>create a report about Jeff's sales last month</p></div>
    </div>
    <div class="chat-message tool-message">
        <div class="sender-column"></div>
        <div class="content-column"><p>A sales report for Jeff for last month has been generated.</p></div>
    </div>
    <div class="chat-message assistant-message">
        <div class="sender-column">Sidekick:</div>
        <div class="content-column"><p>The new report has been generated. Feel free to download it at your convenience!</p></div>
    </div>
</div>

```php
/**
 * Create a report with specified parameters.
 *
 * Write detailed instructions for the AI to learn
 * how to create a report with the specified parameters.
 *
 * @param string $person Name of the person to create the report for.
 * @param string $topic Topic of the report (ie: "sales").
 * @param string $timeframe Timeframe for the report to include.
 * @return SkillResponse
 */
public static function createReport(string $person, string $topic, string $timeframe): SkillResponse
{
    /**
     * Write your custom implementation for creating a report.
     * 
     * For example, determine which user is being referenced
     * and generate a relevant report spanning the given timeframe.
     */

    // If validation fails
    if (!$valid) {
        // Return error message
        return new SkillResponse([
            'success' => false,
            'message' => "Unable to generate a {$topic} report about {$person} spanning {$timeframe}."
        ]);
    }

    // Return success message
    return new SkillResponse([
        'success' => true,
        'message' => "Successfully generated a {$topic} report about {$person} spanning {$timeframe}.",
//        'response' => '(any data you want to send back to the API for further processing)'
    ]);
}
```

## How to Add Skills

The snippet above is just an example (obviously). See the [`AddSkillsEvent`](/customize/add-skills) for more detailed instructions.
