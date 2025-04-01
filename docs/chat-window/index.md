---
description: Sidekick offers a powerful chat window directly within the Craft control panel, allowing you to interact with an AI assistant to perform complex operations like manipulating Twig and module files.
---

# Chat Window

In the main sidebar navigation of the control panel, click the **Sidekick** link to open the chat window.

<img class="dropshadow" src="/images/chat-window/chat-window-example.png" alt="Example of the Chat Window in use" style="max-width:737px; margin:20px 0 26px;">

Sidekick offers a powerful chat window directly within the Craft control panel, allowing you to interact with an AI assistant to perform complex operations like manipulating Twig and module files.

## How it Works

- **Sending Messages**: Type your message and **press Enter** to send.
- **New Lines**: Press **Shift+Enter** to add a new line without sending the message.

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
