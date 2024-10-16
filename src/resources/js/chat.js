// Get references to DOM elements
const chatWindow = document.getElementById('chat-window');
const chatForm = document.getElementById('chat-form');
const chatMessage = document.getElementById('chat-message');
const clearButton = document.getElementById('clear-conversation-button');
const spinner = document.getElementById('chat-spinner');

// Maximum allowed message length
const MAX_MESSAGE_LENGTH = 1000; // Adjust this limit as needed

// Select buttons
const sendButton = chatForm.querySelector('button[type="submit"]');

// Initialize the greeting message
let greeting = null;

// Show the spinner and disable inputs
function showSpinner() {
    spinner.style.display = 'inline-block';
    spinner.setAttribute('aria-hidden', 'false');
    sendButton.disabled = true;
    clearButton.disabled = true;
    chatMessage.disabled = true;
}

// Hide the spinner and refocus the message input
function hideSpinner() {
    spinner.style.display = 'none';
    spinner.setAttribute('aria-hidden', 'true');
    sendButton.disabled = false;
    clearButton.disabled = false;
    chatMessage.disabled = false;
    chatMessage.focus(); // Refocus the message input
}

// Escape HTML characters to prevent XSS attacks
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
    };
    return text.replace(/[&<>"]/g, function (m) { return map[m]; });
}

// Append a message to the chat window
function appendMessage(sender, message, role) {
    const messageElement = document.createElement('div');
    messageElement.classList.add('chat-message');

    // Escape the message content
    const escapedMessage = escapeHtml(message).replace(/\n/g, '<br>');

    // If system message
    if (role === 'system') {
        // Display stylized system messages
        messageElement.classList.add('system-message');
        messageElement.innerHTML = `${escapedMessage}`;
    } else {
        // Display sender's name in bold
        messageElement.innerHTML = `<strong>${sender}:</strong> ${escapedMessage}`;
    }

    // If chatWindow is empty
    if (chatWindow.children.length === 0) {
        // Set the greeting message to the first message
        greeting = escapedMessage;
    }

    // Add the message to the chat window
    chatWindow.appendChild(messageElement);

    // Scroll to the bottom of the chat window
    chatWindow.scrollTop = chatWindow.scrollHeight;
}

// Load existing conversation
function loadConversation() {
    fetch('/actions/sidekick/chat/get-conversation', {
        headers: {
            'X-CSRF-Token': Craft.csrfTokenValue,
            'Accept': 'application/json', // Ensure the request accepts JSON
        },
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const MAX_MESSAGES_DISPLAYED = 100;
                const messagesToDisplay = data.conversation.slice(-MAX_MESSAGES_DISPLAYED);
                messagesToDisplay.forEach(message => {
                    appendMessage(message.role === 'user' ? 'You' : 'Sidekick', message.content, message.role);
                });
            }
        })
        .catch(error => {
            console.error('Error loading conversation:', error);
        });
}

// Handle keydown events for Enter and Shift + Enter in the message input
chatMessage.addEventListener('keydown', function (event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault(); // Prevent newline
        chatForm.dispatchEvent(new Event('submit')); // Submit form
    }
});

// Handle form submission when the user sends a message
chatForm.addEventListener('submit', function (event) {
    event.preventDefault();

    const message = chatMessage.value.trim();
    if (!message) return;

    if (message.length > MAX_MESSAGE_LENGTH) {
        alert('Your message is too long. Please shorten it.');
        return;
    }

    appendMessage('You', message, 'user');
    chatMessage.value = '';

    // Show the spinner and disable inputs
    showSpinner();

    fetch('/actions/sidekick/chat/send-message', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': Craft.csrfTokenValue,
            'Accept': 'application/json', // Ensure the request accepts JSON
        },
        body: JSON.stringify({ message, greeting }),
    })
        .then(response => response.json())
        .then(data => {
            // Hide the spinner
            hideSpinner();

            if (data.success) {
                // Display action messages if any
                if (data.actionMessages && Array.isArray(data.actionMessages)) {
                    data.actionMessages.forEach(systemMessage => {
                        appendMessage('Sidekick', systemMessage, 'system');
                    });
                }

                // Display file content if present
                if (data.content) {
                    // Format the content appropriately, e.g., within a code block
                    const formattedContent = `<pre>${escapeHtml(data.content)}</pre>`;
                    appendMessage('Sidekick', formattedContent, 'assistant');
                }

                // Then display the assistant's final message
                if (data.message) {
                    appendMessage('Sidekick', data.message, 'assistant');
                }
            } else {
                appendMessage('Error', data.error || 'An error occurred.', 'error');
            }
        })
        .catch(error => {
            // Hide the spinner
            hideSpinner();

            console.error('Error sending message:', error);
            appendMessage('Error', 'A network error occurred. Please check your connection and try again.', 'error');
        });
});

// Handle Clear Conversation Button Click
if (clearButton) {
    clearButton.addEventListener('click', () => {
        const confirmation = confirm('Are you sure you want to delete the entire conversation?');
        if (confirmation) {
            // Show the spinner and disable inputs
            showSpinner();

            fetch('/actions/sidekick/chat/clear-conversation', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': Craft.csrfTokenValue,
                    'Accept': 'application/json', // Ensure the request accepts JSON
                },
            })
                .then(response => response.json())
                .then(data => {
                    // Hide the spinner
                    hideSpinner();

                    if (data.success) {
                        // Clear the chat window
                        chatWindow.innerHTML = '';
                        // Load the existing conversation
                        loadConversation();
                        // Focus the message input
                        chatMessage.focus();
                    } else {
                        alert(data.message || 'Failed to clear the conversation.');
                    }
                })
                .catch(error => {
                    // Hide the spinner
                    hideSpinner();

                    console.error('Error clearing conversation:', error);
                    alert('An error occurred while clearing the conversation.');
                });
        }
    });
}

// Focus the message input when the page loads
window.addEventListener('DOMContentLoaded', (event) => {
    // Load the existing conversation
    loadConversation();
    // Focus the message input
    if (chatMessage) {
        chatMessage.focus();
    }
});
