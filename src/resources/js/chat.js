// Define the SidekickChat object
const SidekickChat = {
    // Properties
    chatWindow: document.getElementById('chat-window'),
    chatForm: document.getElementById('chat-form'),
    chatInput: document.getElementById('chat-input'),
    clearButton: document.getElementById('clear-conversation-button'),
    spinner: document.getElementById('chat-spinner'),
    aiModelSelect: document.getElementById('ai-model-select'),
    sendButton: null,
    greeting: null,
    MAX_MESSAGE_LENGTH: 1000, // Adjust this limit as needed
    ROLE: {
        ASSISTANT: 'assistant',
        USER: 'user',
        SYSTEM: 'system',
        TOOL: 'tool',
        ERROR: 'error',
        SNIPPET: 'snippet',
    },

    // Initialize the object
    init: function () {
        // Reference to the send button within the form
        this.sendButton = this.chatForm.querySelector('button[type="submit"]');

        // Bind event listeners
        this.bindEvents();

        // Load existing conversation
        this.loadConversation();

        // Focus the message input
        if (this.chatInput) {
            this.chatInput.focus();
        }

        // Load selected AI model
        this.loadSelectedModel();

        // Configure marked to use highlight.js
        marked.setOptions({
            highlight: function (code, language) {
                const validLanguage = hljs.getLanguage(language) ? language : 'plaintext';
                return hljs.highlight(code, { language: validLanguage }).value;
            },
        });
    },

    // Bind event listeners
    bindEvents: function () {
        // Handle keydown events for "Enter" and "Shift + Enter" in the message input
        this.chatInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault(); // Prevent newline
                this.chatForm.dispatchEvent(new Event('submit')); // Submit form
            }
        });

        // Handle form submission when the user sends a message
        this.chatForm.addEventListener('submit', (event) => {
            event.preventDefault();
            this.sendMessage();
        });

        // Event listener for model selection change
        this.aiModelSelect.addEventListener('change', () => {
            this.setSelectedModel();
        });

        // Handle Clear Conversation Button Click
        if (this.clearButton) {
            this.clearButton.addEventListener('click', () => {
                this.clearConversation();
            });
        }
    },

    // Show the spinner and disable inputs
    showSpinner: function () {
        this.spinner.style.display = 'inline-block';
        this.spinner.setAttribute('aria-hidden', 'false');
        this.sendButton.disabled = true;
        this.clearButton.disabled = true;
        this.chatInput.disabled = true;
    },

    // Hide the spinner and refocus the message input
    hideSpinner: function () {
        this.spinner.style.display = 'none';
        this.spinner.setAttribute('aria-hidden', 'true');
        this.sendButton.disabled = false;
        this.clearButton.disabled = false;
        this.chatInput.disabled = false;
        this.chatInput.focus(); // Refocus the message input
    },

    // Append a message to the chat window
    appendMessage: function (role, message) {

        // If message is empty
        if (!message) {
            console.warn(`Cannot append an empty message from role: ${role}`);
            return;
        }

        // Create a new message element
        const messageElement = document.createElement('div');
        messageElement.classList.add('chat-message');

        // Initialize
        let sender = 'Unknown';
        let messageClass = null;

        // Configure based on the role
        switch (role) {
            case this.ROLE.ASSISTANT:
                sender = 'Sidekick';
                messageClass = 'assistant-message';
                break;
            case this.ROLE.USER:
                sender = 'You';
                messageClass = 'user-message';
                break;
            case this.ROLE.SYSTEM:
                sender = null;
                messageClass = 'system-message';
                break;
            case this.ROLE.TOOL:
                sender = null;
                messageClass = 'tool-message';
                break;
            case this.ROLE.ERROR:
                sender = 'Error';
                messageClass = 'error-message';
                break;
        }

        // Parse Markdown content
        let messageContent = marked.parse(message);

        // Sanitize the message content
        messageContent = DOMPurify.sanitize(messageContent);

        // Create sender div
        const senderElement = document.createElement('div');
        senderElement.classList.add('sender-column');
        senderElement.textContent = sender ? `${sender}:` : '';

        // Create content div
        const contentElement = document.createElement('div');
        contentElement.classList.add('content-column');
        contentElement.innerHTML = messageContent;

        // Loop through all code snippets
        contentElement.querySelectorAll('pre code').forEach((snippet) => {
            // Apply highlighting to the snippet
            hljs.highlightElement(snippet);
        });

        // If a message class is provided
        if (messageClass) {
            // Add class to the message element
            messageElement.classList.add(messageClass);
        }

        // Append sender and content to the message element
        messageElement.appendChild(senderElement);
        messageElement.appendChild(contentElement);

        // Add the message to the chat window
        this.chatWindow.appendChild(messageElement);

        // Scroll to the bottom of the chat window
        this.chatWindow.scrollTop = this.chatWindow.scrollHeight;
    },

    // Load existing conversation
    loadConversation: function () {
        fetch('/actions/sidekick/chat/get-conversation', {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
                'Accept': 'application/json',
            },
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    // Get the existing greeting message
                    this.greeting = data.greeting;

                    // Don't display more than 100 messages
                    const MAX_MESSAGES_DISPLAYED = 100;

                    // Display the last 100 messages
                    const messagesToDisplay = data.conversation.slice(-MAX_MESSAGES_DISPLAYED);

                    // Log table of messages
                    // console.table(messagesToDisplay);

                    // Loop through all messages
                    messagesToDisplay.forEach((message) => {
                        // Display message in the chat window
                        this.appendMessage(
                            message.role,
                            message.content
                        );
                    });
                } else {
                    const error = (data.error || 'Unable to load the conversation.');
                    this.appendMessage(
                        this.ROLE.ERROR,
                        error
                    );
                }
            })
            .catch((error) => {
                console.error('Error loading conversation:', error);
                this.appendMessage(
                    this.ROLE.ERROR,
                    error
                );
            });
    },

    // Send message to the server
    sendMessage: function () {
        const message = this.chatInput.value.trim();
        if (!message) return;

        if (message.length > this.MAX_MESSAGE_LENGTH) {
            alert('Your message is too long. Please shorten it.');
            return;
        }

        this.appendMessage(
            this.ROLE.USER,
            message
        );
        this.chatInput.value = '';

        // Show the spinner and disable inputs
        this.showSpinner();

        // Get the greeting message
        const greeting = (this.greeting ? this.greeting.content : null);

        fetch('/actions/sidekick/chat/send-message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': Craft.csrfTokenValue,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ message, greeting }),
        })
            .then((response) => response.json())
            .then((data) => {
                // Hide the spinner
                this.hideSpinner();

                // If response was unsuccessful
                if (!data.success) {
                    // Log error
                    console.error('Unable to send message: ', data);
                    // Display the error message
                    this.appendMessage(
                        this.ROLE.ERROR,
                        data.error
                    );
                    // Bail
                    return;
                }

                // If messages are not a valid array
                if (!data.messages || !Array.isArray(data.messages)) {
                    // Log error
                    console.error('Invalid response messages: ', data.messages);
                    // Display error message
                    this.appendMessage(
                        this.ROLE.ERROR,
                        'Invalid response messages.'
                    );
                    // Bail
                    return;
                }

                // Loop through all messages
                for (let i = 0; i < data.messages.length; i++) {
                    // Get the message
                    const message = data.messages[i];
                    // Log invalid message
                    if (!message.role || !message.content) {
                        console.warn('Invalid message:', message);
                        continue;
                    }
                    // Display the assistant message
                    this.appendMessage(
                        message.role,
                        message.content
                    );
                }

                // Reset the greeting
                this.greeting = null;


                // // If message is one or more action message(s)
                // if (data.actionMessages && Array.isArray(data.actionMessages)) {
                //     // Loop through all action messages
                //     data.actionMessages.forEach((systemMessage) => {
                //         // Display the action message
                //         this.appendMessage(
                //             this.ROLE.ACTION,
                //             systemMessage
                //         );
                //     });
                // }

                // // Display file content if present
                // if (data.content) {
                //     // The content is assumed to be a code snippet
                //     this.appendMessage(
                //         this.ROLE.SNIPPET,
                //         `<pre><code>${data.content}</code></pre>`
                //     );
                // }

                // // Then display the assistant's final message
                // if (data.message) {
                //     this.appendMessage(
                //         this.ROLE.ASSISTANT,
                //         data.message
                //     );
                // }


            })
            .catch((error) => {
                // Hide the spinner
                this.hideSpinner();

                console.error('Error sending message:', error);
                this.appendMessage(
                    this.ROLE.ERROR,
                    'A network error occurred. Please check your connection and try again.'
                );
            });
    },

    // Load the selected model from the server
    loadSelectedModel: function () {
        fetch('/actions/sidekick/chat/get-selected-model', {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
                'Accept': 'application/json',
            },
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    this.aiModelSelect.value = data.selectedModel;
                }
            });
    },

    // Set the selected AI model on the server
    setSelectedModel: function () {
        const selectedModel = this.aiModelSelect.value;

        // Update session on the server
        fetch('/actions/sidekick/chat/set-selected-model', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': Craft.csrfTokenValue,
            },
            body: JSON.stringify({ selectedModel }),
        });
    },

    // Clear the conversation
    clearConversation: function () {
        const confirmation = confirm('Are you sure you want to delete the entire conversation?');
        if (confirmation) {
            // Show the spinner and disable inputs
            this.showSpinner();

            fetch('/actions/sidekick/chat/clear-conversation', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': Craft.csrfTokenValue,
                    'Accept': 'application/json',
                },
            })
                .then((response) => response.json())
                .then((data) => {
                    // Hide the spinner
                    this.hideSpinner();

                    if (data.success) {
                        // Clear the chat window
                        this.chatWindow.innerHTML = '';
                        // Reset the greeting
                        this.greeting = null;
                        // Load the existing conversation
                        this.loadConversation();
                        // Focus the message input
                        this.chatInput.focus();
                    } else {
                        const error = (data.error || 'Failed to clear the conversation.');
                        this.appendMessage(
                            this.ROLE.ERROR,
                            error
                        );
                        // Wait for .1 second before alerting the user
                        setTimeout(() => {
                            alert(error);
                        }, 100);
                    }
                })
                .catch((error) => {
                    // Hide the spinner
                    this.hideSpinner();

                    console.error('Error clearing conversation:', error);
                    this.appendMessage(
                        this.ROLE.ERROR,
                        error
                    );

                    // Wait for .1 second before alerting the user
                    setTimeout(() => {
                        alert('An error occurred while clearing the conversation.');
                    }, 100);
                });
        }
    },
};

// Initialize the SidekickChat object when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    SidekickChat.init();
});
