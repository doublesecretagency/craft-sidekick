// Define the SidekickChat object
// noinspection JSVoidFunctionReturnValueUsed
const SidekickChat = {
    // Properties
    chatWindow: document.getElementById('chat-window'),
    chatForm: document.getElementById('chat-form'),
    chatInput: document.getElementById('chat-input'),
    clearButton: document.getElementById('clear-conversation-button'),
    loader: document.getElementById('chat-loading'),
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

        // Activate skills slideout
        new this.ListSkills();

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
                event.preventDefault();
                this.sendMessage();
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

    showLoader: function () {
        this.loader.classList.add('visible');
        this.loader.setAttribute('aria-hidden', 'false');
        this.sendButton.disabled = true;
        this.clearButton.disabled = true;
        this.chatInput.disabled = true;
    },

    hideLoader: function () {
        this.loader.classList.remove('visible');
        this.loader.setAttribute('aria-hidden', 'true');
        this.sendButton.disabled = false;
        this.clearButton.disabled = false;
        this.chatInput.disabled = false;
        this.chatInput.focus(); // Refocus the input
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

                    // Loop through all messages
                    messagesToDisplay.forEach((message) => {
                        // Display message in the chat window
                        this.appendMessage(
                            message.role,
                            message.message
                        );
                    });
                } else {
                    const message = (data.message || 'Unable to load the conversation.');
                    this.appendMessage(
                        this.ROLE.ERROR,
                        message
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

        // Get the message from the input
        const message = this.chatInput.value.trim();

        // If the message is empty, do nothing
        if (!message) {
            return;
        }

        // If the message is too long, alert the user
        if (message.length > this.MAX_MESSAGE_LENGTH) {
            alert('Your message is too long. Please shorten it.');
            return;
        }

        // Append the user's message to the chat window
        this.appendMessage(
            this.ROLE.USER,
            message
        );

        // Pass this object into the event listeners
        const that = this;

        // Show the loader and disable inputs
        this.showLoader();

        // Clear the input
        this.chatInput.value = '';

        // Get the greeting message
        const greeting = (this.greeting ? this.greeting.message : null);

        // Convert parameters to a query string
        const params = new URLSearchParams({message, greeting});

        // console.log('Opening the connection.');

        // Create an event source
        const eventSource = new EventSource(`/actions/sidekick/chat/send-message?${params.toString()}`);

        // Close the connection when instructed
        eventSource.addEventListener('close', function(event) {
            // console.log('Closing the connection.');
            eventSource.close();
            // Hide the loader
            that.hideLoader();
        });

        // Listen for messages from the server
        eventSource.onmessage = function(event) {

            // Get the data from the event
            const data = JSON.parse(event.data);

            // If role or message are missing
            if (!data.role || !data.message) {
                // Log warning
                console.warn('Incomplete message:', data, event);
                // Display the error message
                that.appendMessage(
                    that.ROLE.ERROR,
                    'Sorry, an unexpected error occurred.'
                );
                // Bail
                return;
            }

            // Display the message
            that.appendMessage(
                data.role,
                data.message
            );

            // Reset the greeting
            that.greeting = null;
        };

        // If there's an error
        eventSource.onerror = function(error) {


            console.error('SSE encountered an error:', error);

            // Log the connection state (0: CONNECTING, 1: OPEN, 2: CLOSED)
            const readyState = error.target.readyState;
            if (readyState === EventSource.CONNECTING) {
                console.warn('EventSource is reconnecting (readyState = CONNECTING)...');
            } else if (readyState === EventSource.CLOSED) {
                console.error('EventSource connection closed. ' +
                    'This might be due to a server-side error, network issues, or PHP misconfiguration.');
            } else {
                console.error('Unexpected EventSource state:', readyState);
            }

            // // Log the URL for further inspection
            // console.log('EventSource URL:', error.target.url);

            // // OPTIONAL: Attempt a HEAD request to the same URL to check HTTP status.
            // // This may provide hints about server-side issues (e.g., 500, 404, CORS issues).
            // fetch(error.target.url, { method: 'HEAD' })
            //     .then(response => {
            //         console.log('Fetch status for SSE endpoint:', response.status, response.statusText);
            //     })
            //     .catch(fetchError => {
            //         console.error('Fetch error while checking SSE endpoint:', fetchError);
            //     });

            // Display the error message
            that.appendMessage(
                that.ROLE.ERROR,
                'An unknown connection error occurred.'
            );
            // Hide the loader
            that.hideLoader();
        };

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
            // Show the loader and disable inputs
            this.showLoader();

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
                    // Hide the loader
                    this.hideLoader();

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
                        const message = (data.message || 'Failed to clear the conversation.');
                        this.appendMessage(
                            this.ROLE.ERROR,
                            message
                        );
                        // Wait for .1 second before alerting the user
                        setTimeout(() => {
                            alert(message);
                        }, 100);
                    }
                })
                .catch((error) => {
                    // Hide the loader
                    this.hideLoader();

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

    // Activate skills slideout
    ListSkills: Garnish.Base.extend({
        init: function() {
            // Open slideout when the button is clicked
            $('#sidekick-list-skills').on('click', $.proxy(this, 'onClick'));
        },
        onClick: function() {
            // Render and open the slideout
            const slideout = new Craft.CpScreenSlideout('sidekick/chat/list-skills');
            slideout.open();
        },
    }),

};

// Initialize the SidekickChat object when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    SidekickChat.init();
});
