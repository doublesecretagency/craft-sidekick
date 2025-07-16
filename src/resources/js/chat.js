// Define the SidekickChat object
// noinspection JSVoidFunctionReturnValueUsed
const SidekickChat = {
    // Properties
    chatContainer: document.getElementById('chat-container'),
    chatWindow: document.getElementById('chat-window'),
    chatResizer: document.getElementById('chat-resizer'),
    chatUserPrompt: document.getElementById('chat-user-prompt'),
    chatForm: document.getElementById('chat-form'),
    chatInput: document.getElementById('chat-input'),
    clearButton: document.getElementById('clear-conversation-button'),
    loader: document.getElementById('chat-loading'),
    aiModelSelect: document.getElementById('ai-model-select'),
    sendButton: null,
    greeting: null,
    slideout: null,
    ROLE: {
        ASSISTANT: 'assistant',
        USER: 'user',
        SYSTEM: 'system',
        TOOL: 'tool',
        ERROR: 'error',
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

        // // Load selected AI model
        // this.loadSelectedModel();

        // Initialize height of chat window
        const startH = this.chatWindow.getBoundingClientRect().height;
        this.chatWindow.style.height = startH + 'px';

        // Bind "this" for each handler
        this._onResizeStart = this._onResizeStart.bind(this);
        this._onResizing    = this._onResizing.bind(this);
        this._onResizeEnd   = this._onResizeEnd.bind(this);

        // Initialize the resizer
        this.chatResizer.addEventListener('mousedown', this._onResizeStart);

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

    // ========================================================================= //

    // When grabbing the resizer handle
    _onResizeStart(e) {

        // Prevent default behavior to avoid text selection
        e.preventDefault();

        // Store the initial mouse position and chat window height
        this.startY      = e.clientY;
        this.startHeight = this.chatWindow.getBoundingClientRect().height;

        // Add event listeners for mouse movement and release
        document.addEventListener('mousemove', this._onResizing);
        document.addEventListener('mouseup',   this._onResizeEnd, { once: true });
    },

    // While resizing the chat window
    _onResizing(e) {

        // Get the difference in mouse position
        const delta = e.clientY - this.startY;
        let   newH  = this.startHeight + delta;

        // Minimum height for the chat window
        const minHeight = 150;

        // Calculate the new height
        // ensuring it doesn't go below the minimum
        newH = Math.max(newH, minHeight);

        // Set the new height of the chat window
        this.chatWindow.style.height = `${newH}px`;
    },

    // When finished resizing
    _onResizeEnd() {
        // Remove the event listeners
        document.removeEventListener('mousemove', this._onResizing);
    },

    // ========================================================================= //

    // Bind event listeners
    bindEvents: function () {
        // Handle keydown events for "Enter" and "Shift + Enter" in the message input
        if (this.chatInput) {
            this.chatInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    this.sendMessage();
                }
            });
        }

        // Handle form submission when the user sends a message
        if (this.chatForm) {
            this.chatForm.addEventListener('submit', (event) => {
                event.preventDefault();
                this.sendMessage();
            });
        }

        // // Event listener for model selection change
        // if (this.aiModelSelect) {
        //     this.aiModelSelect.addEventListener('change', () => {
        //         this.setSelectedModel();
        //     });
        // }

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

    // ========================================================================= //

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

    // ========================================================================= //

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

    // Format a timestamp for display
    formatTimestamp: function (date = new Date()) {
        // If the date is not a Date object, convert it
        const options = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: 'numeric',
            minute: '2-digit',
            second: '2-digit',
            hour12: true,
            timeZoneName: 'short',
        };

        // Use Intl.DateTimeFormat to format the date
        const parts = new Intl.DateTimeFormat(undefined, options).formatToParts(date);

        // Extract parts from the formatted date
        const get = (type) => parts.find(p => p.type === type)?.value || '';
        const tz = get('timeZoneName');

        // Return formatted timestamp
        return `${get('year')}-${get('month')}-${get('day')} [${get('hour')}:${get('minute')}:${get('second')} ${get('dayPeriod')}]`;
    },

    // Send message to the server
    sendMessage: function () {

        // Get the message from the input
        const message = this.chatInput.value.trim();

        // If the message is empty, do nothing
        if (!message) {
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

        // FOR TESTING PURPOSES ONLY
        // const eventSource = new EventSource(`/actions/sidekick/chat/test-stream`);

        // Create an event source
        const eventSource = new EventSource(`/actions/sidekick/chat/send-message?${params.toString()}`);

        // How long to wait before checking
        // whether the connection is still CONNECTING
        const patience = 5; // seconds

        // Wait for a moment before checking the connection state
        setTimeout(() => {
            // If the connection is still in CONNECTING state
            if (eventSource.readyState === EventSource.CONNECTING) {
                console.warn(`[SSE] Still CONNECTING after ${patience} seconds... might be stuck.`);
            }
        }, (patience * 1000));

        // Close the connection when instructed
        eventSource.addEventListener('close', function(event) {
            // Log the resolution of the connection
            // console.log(`${that.formatTimestamp()} SSE connection resolved`);
            // Close the EventSource connection
            eventSource.close();
            // Hide the loader
            that.hideLoader();
        });

        // Store the start time of the SSE connection
        window._sseStartTime = Date.now();

        // Log the start time
        eventSource.onopen = function () {
            // console.log(`${that.formatTimestamp()} SSE connection established`);
        };

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

        // Handle errors from the EventSource
        eventSource.onerror = function(error) {

            // Initialize the error object
            const target = error?.target || {};
            const readyState = target.readyState;
            const stateLabel = ['CONNECTING', 'OPEN', 'CLOSED'][readyState] || 'UNKNOWN';
            const url = target.url || '[unknown]';

            // Calculate the current time and duration since connection started
            const now = new Date();
            const connectedAt = window._sseStartTime
                ? that.formatTimestamp(new Date(window._sseStartTime))
                : '[unknown start time]';
            const duration = window._sseStartTime
                ? Math.round((Date.now() - window._sseStartTime) / 1000)
                : '?';

            // Log the error details
            console.groupCollapsed(`%c[SSE ERROR] State: ${stateLabel} — ${duration}s after connect`, 'color: red; font-weight: bold');
            console.error(error);
            console.log('ReadyState:', readyState, `(${stateLabel})`);
            console.log('EventSource URL:', url);
            console.log('Connected at:', connectedAt);
            console.log('Disconnected at:', that.formatTimestamp(now));
            console.groupEnd();

            // Display the error message
            that.appendMessage(
                that.ROLE.ERROR,
                '⚠️ Connection interrupted'
            );

            // Hide the loader
            that.hideLoader();

            // If the URL is available and fetch is supported
            if (url && typeof fetch === 'function') {
                // Attempt a HEAD request for server-side diagnostics
                fetch(url, { method: 'HEAD' })
                    .then(res => {
                        // Log the response status and headers
                        console.groupCollapsed(`[SSE Diagnostic] HEAD ${res.status} ${res.statusText}`);
                        for (const [header, value] of res.headers.entries()) {
                            console.log(`${header}: ${value}`);
                        }
                        console.groupEnd();
                    })
                    .catch(fetchError => {
                        // Log any errors from the HEAD request
                        console.error('[SSE Diagnostic] HEAD request failed:', fetchError);
                    });
            }
        };

    },

    // // Load the selected model from the server
    // loadSelectedModel: function () {
    //     fetch('/actions/sidekick/chat/get-selected-model', {
    //         headers: {
    //             'X-CSRF-Token': Craft.csrfTokenValue,
    //             'Accept': 'application/json',
    //         },
    //     })
    //         .then((response) => response.json())
    //         .then((data) => {
    //             if (data.success) {
    //                 this.aiModelSelect.value = data.selectedModel;
    //             }
    //         });
    // },

    // // Set the selected AI model on the server
    // setSelectedModel: function () {
    //     const selectedModel = this.aiModelSelect.value;
    //
    //     // Update session on the server
    //     fetch('/actions/sidekick/chat/set-selected-model', {
    //         method: 'POST',
    //         headers: {
    //             'Content-Type': 'application/json',
    //             'X-CSRF-Token': Craft.csrfTokenValue,
    //         },
    //         body: JSON.stringify({ selectedModel }),
    //     });
    // },

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

    // ========================================================================= //

    // Activate skills slideout
    ListSkills: Garnish.Base.extend({
        init: function () {
            $('#sidekick-list-skills').on('click', $.proxy(this, 'open'));
        },

        // Open the slideout
        open: function () {
            // Get the HTML and open the slideout
            const html = document.getElementById('sidekick-list-skills-content').innerHTML;
            this.slideout = new Craft.Slideout(html);
            this.slideout.open();

            // Bind the Close button in the new slideout container
            this.slideout.$container
                .find('.so-footer button')
                .on('click', $.proxy(this, 'close'));
        },

        // Close the slideout
        close: function () {
            this.slideout.close();
        },
    }),

};

// Initialize the SidekickChat object when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    SidekickChat.init();
});
