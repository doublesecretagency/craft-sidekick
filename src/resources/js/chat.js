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
        "'": '&#039;',
    };
    return text.replace(/[&<>"']/g, function (m) { return map[m]; });
}

// Append a message to the chat window
function appendMessage(sender, message, role) {
    // Do not display file operation commands
    if (message.match(/^\[(CREATE_FILE|UPDATE_FILE|DELETE_FILE).*?\]/s)) {
        return;
    }

    const messageElement = document.createElement('div');
    messageElement.style.marginBottom = '10px';

    // Check if the message is a system file operation message
    if (role === 'system' && message.startsWith('[') && message.endsWith(']')) {
        messageElement.innerHTML = `<span style="color: green;">${message}</span>`;
    } else {
        // Escape the message content
        const escapedMessage = escapeHtml(message).replace(/\n/g, '<br>');
        // Display sender's name in bold
        messageElement.innerHTML = `<strong>${sender}:</strong> ${escapedMessage}`;
    }

    chatWindow.appendChild(messageElement);
    chatWindow.scrollTop = chatWindow.scrollHeight;
}

// Load existing conversation when the page loads
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
        body: JSON.stringify({ message }),
    })
        .then(response => response.json())
        .then(data => {
            // Hide the spinner
            hideSpinner();

            if (data.success) {
                // Check if there's a file operation message to display
                if (data.fileOperation && data.fileOperation.requiresNextChange) {
                    // Do not display the assistant message if it's empty
                    if (data.fileOperation.assistantMessage) {
                        appendMessage('Sidekick', data.fileOperation.assistantMessage, 'assistant');
                    }
                } else {
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
    if (chatMessage) {
        chatMessage.focus();
    }
});
