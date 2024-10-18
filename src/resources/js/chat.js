// Define the SidekickChat object
const SidekickChat = {
    // Properties
    chatWindow: document.getElementById('chat-window'),
    chatForm: document.getElementById('chat-form'),
    chatMessage: document.getElementById('chat-message'),
    clearButton: document.getElementById('clear-conversation-button'),
    spinner: document.getElementById('chat-spinner'),
    aiModelSelect: document.getElementById('ai-model-select'),
    sendButton: null,
    greeting: null,
    MAX_MESSAGE_LENGTH: 1000, // Adjust this limit as needed

    // Initialize the object
    init: function () {
        // Reference to the send button within the form
        this.sendButton = this.chatForm.querySelector('button[type="submit"]');

        // Bind event listeners
        this.bindEvents();

        // Load existing conversation
        this.loadConversation();

        // Focus the message input
        if (this.chatMessage) {
            this.chatMessage.focus();
        }

        // Load selected AI model
        this.loadSelectedModel();
    },

    // Bind event listeners
    bindEvents: function () {
        // Handle keydown events for Enter and Shift + Enter in the message input
        this.chatMessage.addEventListener('keydown', (event) => {
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
        this.chatMessage.disabled = true;
    },

    // Hide the spinner and refocus the message input
    hideSpinner: function () {
        this.spinner.style.display = 'none';
        this.spinner.setAttribute('aria-hidden', 'true');
        this.sendButton.disabled = false;
        this.clearButton.disabled = false;
        this.chatMessage.disabled = false;
        this.chatMessage.focus(); // Refocus the message input
    },

    // Escape HTML characters to prevent XSS attacks
    escapeHtml: function (text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
        };
        return text.replace(/[&<>"]/g, (m) => map[m]);
    },

    // Append a message to the chat window
    appendMessage: function (sender, message, role) {
        const messageElement = document.createElement('div');
        messageElement.classList.add('chat-message');

        // Escape the message content
        const escapedMessage = this.escapeHtml(message).replace(/\n/g, '<br>');

        // If system message
        if (role === 'system') {
            // Display stylized system messages
            messageElement.classList.add('system-message');
            messageElement.innerHTML = `${escapedMessage}`;
        } else if (role === 'error') {
            // Display error messages
            messageElement.classList.add('error-message');
            messageElement.innerHTML = `<strong>${sender}:</strong> ${escapedMessage}`;
        } else {
            // Display sender's name in bold
            messageElement.innerHTML = `<strong>${sender}:</strong> ${escapedMessage}`;
        }

        // If chatWindow is empty
        if (this.chatWindow.children.length === 0) {
            // Set the greeting message to the first message
            this.greeting = escapedMessage;
        }

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
                    const MAX_MESSAGES_DISPLAYED = 100;
                    const messagesToDisplay = data.conversation.slice(-MAX_MESSAGES_DISPLAYED);
                    messagesToDisplay.forEach((message) => {
                        this.appendMessage(
                            message.role === 'user' ? 'You' : 'Sidekick',
                            message.content,
                            message.role
                        );
                    });
                }
            })
            .catch((error) => {
                console.error('Error loading conversation:', error);
            });
    },

    // Send message to the server
    sendMessage: function () {
        const message = this.chatMessage.value.trim();
        if (!message) return;

        if (message.length > this.MAX_MESSAGE_LENGTH) {
            alert('Your message is too long. Please shorten it.');
            return;
        }

        this.appendMessage('You', message, 'user');
        this.chatMessage.value = '';

        // Show the spinner and disable inputs
        this.showSpinner();

        fetch('/actions/sidekick/chat/send-message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': Craft.csrfTokenValue,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ message, greeting: this.greeting }),
        })
            .then((response) => response.json())
            .then((data) => {
                // Hide the spinner
                this.hideSpinner();

                if (data.success) {
                    // Display action messages if any
                    if (data.actionMessages && Array.isArray(data.actionMessages)) {
                        data.actionMessages.forEach((systemMessage) => {
                            this.appendMessage('Sidekick', systemMessage, 'system');
                        });
                    }

                    // Display file content if present
                    if (data.content) {
                        // Format the content appropriately, e.g., within a code block
                        const formattedContent = `<pre>${this.escapeHtml(data.content)}</pre>`;
                        this.appendMessage('Sidekick', formattedContent, 'assistant');
                    }

                    // Then display the assistant's final message
                    if (data.message) {
                        this.appendMessage('Sidekick', data.message, 'assistant');
                    }
                } else {
                    this.appendMessage('Error', data.error || 'An error occurred.', 'error');
                }
            })
            .catch((error) => {
                // Hide the spinner
                this.hideSpinner();

                console.error('Error sending message:', error);
                this.appendMessage('Error', 'A network error occurred. Please check your connection and try again.', 'error');
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
                        this.chatMessage.focus();
                    } else {
                        alert(data.message || 'Failed to clear the conversation.');
                    }
                })
                .catch((error) => {
                    // Hide the spinner
                    this.hideSpinner();

                    console.error('Error clearing conversation:', error);
                    alert('An error occurred while clearing the conversation.');
                });
        }
    },
};

// Initialize the SidekickChat object when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    SidekickChat.init();
});
