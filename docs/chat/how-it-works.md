---
title: "Chat Window | Sidekick plugin for Craft CMS"
description: "Sidekick adds an AI chat window to the Craft control panel for editing Twig files and managing entries, fields, and sections."
head:
  - - meta
    - property: "og:type"
      content: "website"
  - - meta
    - property: "og:url"
      content: "https://plugins.doublesecretagency.com/sidekick/chat/how-it-works"
  - - meta
    - property: "og:title"
      content: "Chat Window | Sidekick plugin for Craft CMS"
  - - meta
    - property: "og:description"
      content: "Sidekick adds an AI chat window to the Craft control panel for editing Twig files and managing entries, fields, and sections."
  - - meta
    - property: "og:image"
      content: "https://plugins.doublesecretagency.com/sidekick/images/chat-window/chat-window-example.png"
  - - meta
    - name: "twitter:card"
      content: "summary_large_image"
  - - meta
    - name: "twitter:url"
      content: "https://plugins.doublesecretagency.com/sidekick/chat/how-it-works"
  - - meta
    - name: "twitter:title"
      content: "Chat Window | Sidekick plugin for Craft CMS"
  - - meta
    - name: "twitter:description"
      content: "Sidekick adds an AI chat window to the Craft control panel for editing Twig files and managing entries, fields, and sections."
  - - meta
    - name: "twitter:image"
      content: "https://plugins.doublesecretagency.com/sidekick/images/chat-window/chat-window-example.png"
---

# Chat Window

Sidekick adds an AI chat window to the Craft control panel for editing Twig files and managing entries, fields, and sections. In the main sidebar navigation of the control panel, click the **Sidekick** link to open the chat window.

<img class="dropshadow" src="/images/chat-window/chat-window-example.png" alt="Example of the Chat Window in use" style="max-width:737px; margin:20px 0 26px;">

## Message Types

The chat window displays different types of messages, each color-coded for clarity:

| Color                                   | Message Type           | Description                           |
|-----------------------------------------|------------------------|---------------------------------------|
| Black                                   | **User Messages**      | Your own inputs.                      |
| Black                                   | **Assistant Messages** | Responses from Sidekick and OpenAI.   |
| <span style="color:#127fbf">Blue</span> | **System Messages**    | System actions performed by Sidekick. |
| <span style="color:#d81e23">Red</span>  | **Error Messages**     | Alerts when something goes wrong.     |

## Clearing the Conversation

When you want to start a new topic, use the **Clear Conversation** button to reset the chat.

- Removes the current conversation history.
- Starts a new session with the assistant.
- Applies the new GPT model (if it has been switched).

<img class="dropshadow" src="/images/chat-window/chat-window-buttons.png" alt="Screenshot of buttons above the Chat Window" style="max-width:314px; margin-top:22px;">

## Switching GPT Models

To switch between models, simply click the model button and select your preferred GPT model.

:::warning You must "Clear Conversation" to apply the new model
The new model will not take effect until you clear the conversation.
:::
