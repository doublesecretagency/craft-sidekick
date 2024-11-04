# Chat Window

In the main navigation of the control panel, click the **Sidekick** link to open the chat window.

<div class="chat-window">
  <div class="assistant-message"><strong>Sidekick:</strong> Hey there! I'm Sidekick, ready to assist with your Craft templates and modules.</div>
  <div class="user-message"><strong>You:</strong> On the homepage, create a 2-column layout. In the left column, show a list of recent Blog articles. In the right column, write a paragraph about why dogs are awesome.</div>
  <div class="assistant-message"><strong>Sidekick:</strong> To accomplish this, I'll need to create a new Twig template file. Here’s the plan:
    <ol>
      <li><strong>Create a new Twig template</strong> for the homepage (if it doesn't already exist).</li>
      <li><strong>Set up a 2-column layout</strong> in this template.</li>
      <li><strong>Fetch and display the recent blog articles</strong> in the left column.</li>
      <li><strong>Add a paragraph about why dogs are awesome</strong> in the right column.</li>
    </ol>
    <p>Shall I proceed?</p>
  </div>
  <div class="user-message"><strong>You:</strong> Go for it!</div>
  <div class="system-message">Successfully created templates/_columns.twig</div>
  <div class="assistant-message"><strong>Sidekick:</strong> The homepage template with a 2-column layout has been successfully created in the `templates` directory. The left column displays recent blog articles, and the right column contains a paragraph about why dogs are awesome. Let me know if there's anything else you'd like to add or modify!</div>
</div>

Sidekick offers a powerful chat window directly within the Craft CMS control panel, allowing you to interact with an AI assistant to help with various tasks, including complex operations like manipulating Twig files and editing the DOM.

## How it Works

- **Sending Messages**: Type your message and **press Enter** to send.
- **New Lines**: Press **Shift+Enter** to add a new line without sending the message.

## Clearing the Conversation

- Use the **Clear Conversation** button to reset the chat.
- This will:
  - Remove the current conversation history.
  - Start a new session with the assistant.
  - Apply any changes to settings, such as a new AI model.

## Message Types

The chat window displays different types of messages, each color-coded for clarity:

| Color                                   | Message Type           | Description                           |
|-----------------------------------------|------------------------|---------------------------------------|
| Black                                   | **User Messages**      | Your own inputs.                      |
| Black                                   | **Assistant Messages** | Responses from Sidekick and OpenAI.   |
| <span style="color:#127fbf">Blue</span> | **System Messages**    | System actions performed by Sidekick. |
| <span style="color:#d81e23">Red</span>  | **Error Messages**     | Alerts when something goes wrong.     |

[//]: # (## Utilizing the Toolset in Complex Ways)

[//]: # ()
[//]: # (The chat window allows you to leverage a suite of tools for complex tasks. For example:)

[//]: # ()
[//]: # (- **Manipulating Twig Templates**: Ask Sidekick to create or modify Twig files.)

[//]: # (- **Editing the DOM**: Request changes to your site's HTML structure.)

[//]: # (### Examples)

[//]: # ()
[//]: # (**Creating a Two-Column Layout**)

[//]: # ()
[//]: # (<div class="chat-window">)

[//]: # (  <div class="user-message"><strong>You:</strong> Please create a two-column layout for my homepage, with recent blog posts on the left and upcoming events on the right.</div>)

[//]: # (  <div class="system-message">The `index.twig` file has been updated.</div>)

[//]: # (  <div class="assistant-message"><strong>Sidekick:</strong> I've generated a Twig template for a two-column layout with the requested content.</div>)

[//]: # (</div>)

[//]: # ()
[//]: # (**Adding a Navigation Menu**)

[//]: # ()
[//]: # (<div class="chat-window">)

[//]: # (  <div class="user-message"><strong>You:</strong> Add a navigation menu to my site that includes links to Home, About, Services, and Contact.</div>)

[//]: # (  <div class="assistant-message"><strong>Sidekick:</strong> I've added a navigation menu with the specified links to your site's layout template.</div>)

[//]: # (</div>)

## Switching Between AI Models

- You can switch between different AI models to suit your needs.
- To change the model, go to the [Settings](../getting-started/settings.md) page.
- **Note**: The change will take effect **only when you start a new conversation**.

[//]: # (## What You Can Do)

[//]: # ()
[//]: # (- **Develop Templates**: Generate or modify Twig templates for your site.)

[//]: # (- **Update Content**: Ask Sidekick to create or edit entries, assets, or other content.)

[//]: # (- **Modify Site Structure**: Request changes to your site's navigation or layout.)

[//]: # (- **Perform Actions**: Execute predefined actions within Craft CMS using Sidekick's tools.)

[//]: # (- **Get Craft CMS Tips**: Ask questions about configuring or using Craft CMS features.)

[//]: # ()
[//]: # (---)

[//]: # ()
[//]: # (By utilizing the chat window effectively, you can streamline your development process, automate tasks, and enhance your Craft CMS experience with the power of AI.)
