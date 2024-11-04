# Chat Window

Sidekick offers a powerful chat window directly within the Craft CMS control panel, allowing you to interact with an AI assistant to help with various tasks, including complex operations like manipulating Twig files and editing the DOM.

## Accessing the Chat Window

1. In the control panel, navigate to **Sidekick > Chat**.
2. Start typing your message in the input field.

   [//]: # (   <img src="/images/chat/chat-window.png" alt="Chat Window Interface" style="width:750px; margin-top:10px">)

## How the Chat Window Works

- **Sending Messages**: Type your message and **press Enter** to send.
- **New Lines**: Press **Shift+Enter** to add a new line without sending the message.

## Message Types

The chat window displays different types of messages, each color-coded for clarity:

| Color | Message Type           | Description                           |
|-------|------------------------|---------------------------------------|
| Black | **User Messages**      | Your own inputs.                      |
| Black | **Assistant Messages** | Responses from Sidekick and OpenAI.   |
| Blue  | **System Messages**    | System actions performed by Sidekick. |
| Red   | **Error Messages**     | Alerts when something goes wrong.     |

## Utilizing the Toolset in Complex Ways

The chat window allows you to leverage a suite of tools for complex tasks. For example:

- **Manipulating Twig Templates**: Ask Sidekick to create or modify Twig files.
- **Editing the DOM**: Request changes to your site's HTML structure.

### Examples

**Creating a Two-Column Layout**

**You:** "Please create a two-column layout for my homepage, with recent blog posts on the left and upcoming events on the right."

**Sidekick:** "I've generated a Twig template for a two-column layout with the requested content."

*Sidekick then provides the code and can optionally save it to your templates directory.*

**Adding a Navigation Menu**

**You:** "Add a navigation menu to my site that includes links to Home, About, Services, and Contact."

**Sidekick:** "I've added a navigation menu with the specified links to your site's layout template."

## Switching Between AI Models

- You can switch between different AI models to suit your needs.
- To change the model, go to the [Settings](../getting-started/settings.md) page.
- **Note**: The change will take effect **only when you start a new conversation**.

## Clearing the Conversation

- Use the **Clear Conversation** button to reset the chat.
- This will:
  - Remove the current conversation history.
  - Start a new session with the assistant.
  - Apply any changes to settings, such as a new AI model.

## What You Can Do

- **Develop Templates**: Generate or modify Twig templates for your site.
- **Update Content**: Ask Sidekick to create or edit entries, assets, or other content.
- **Modify Site Structure**: Request changes to your site's navigation or layout.
- **Perform Actions**: Execute predefined actions within Craft CMS using Sidekick's tools.
- **Get Craft CMS Tips**: Ask questions about configuring or using Craft CMS features.

---

By utilizing the chat window effectively, you can streamline your development process, automate tasks, and enhance your Craft CMS experience with the power of AI.
