const chatWindow = document.getElementById('chat-window');
const chatForm = document.getElementById('chat-form');
const chatMessage = document.getElementById('chat-message');
const clearButton = document.getElementById('clear-conversation-button');
const spinner = document.getElementById('chat-spinner');
const MAX_MESSAGE_LENGTH = 1000; // Set an appropriate limit

// Select buttons
const sendButton = chatForm.querySelector('button[type="submit"]');

// Function to show the spinner and disable buttons
function showSpinner() {
    spinner.style.display = 'inline-block';
    spinner.setAttribute('aria-hidden', 'false');
    sendButton.disabled = true;
    clearButton.disabled = true;
}

// Function to hide the spinner and enable buttons
function hideSpinner() {
    spinner.style.display = 'none';
    spinner.setAttribute('aria-hidden', 'true');
    sendButton.disabled = false;
    clearButton.disabled = false;
}

// Load existing conversation
fetch('/actions/sidekick/chat/get-conversation', {
    headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
    },
})
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const MAX_MESSAGES_DISPLAYED = 100;
            const messagesToDisplay = data.conversation.slice(-MAX_MESSAGES_DISPLAYED);
            messagesToDisplay.forEach(message => {
                appendMessage(message.role === 'user' ? 'You' : 'Sidekick', message.content);
            });
        }
    });

// Handle keydown events for Enter and Shift + Enter
chatMessage.addEventListener('keydown', function (event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault(); // Prevent newline
        chatForm.dispatchEvent(new Event('submit')); // Submit form
    }
});

chatForm.addEventListener('submit', function (event) {
    event.preventDefault();

    const message = chatMessage.value.trim();
    if (!message) return;

    if (message.length > MAX_MESSAGE_LENGTH) {
        alert('Your message is too long. Please shorten it.');
        return;
    }

    appendMessage('You', message);
    chatMessage.value = '';

    // Show the spinner and disable buttons
    showSpinner();

    fetch('/actions/sidekick/chat/send-message', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': Craft.csrfTokenValue,
        },
        body: JSON.stringify({ message }),
    })
        .then(response => response.json())
        .then(data => {
            // Hide the spinner and enable buttons
            hideSpinner();

            if (data.success) {
                appendMessage('Sidekick', data.message);
            } else {
                appendMessage('Error', data.error || 'An error occurred.');
            }
        })
        .catch(error => {
            // Hide the spinner and enable buttons
            hideSpinner();

            appendMessage('Error', 'A network error occurred. Please check your connection and try again.');
        });
});

// Handle Clear Conversation Button Click
if (clearButton) {
    clearButton.addEventListener('click', () => {
        const confirmation = confirm('Are you sure you want to delete the entire conversation?');
        if (confirmation) {
            // Show the spinner and disable buttons
            showSpinner();

            fetch('/actions/sidekick/chat/clear-conversation', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': Craft.csrfTokenValue,
                },
            })
                .then(response => response.json())
                .then(data => {
                    // Hide the spinner and enable buttons
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
                    // Hide the spinner and enable buttons
                    hideSpinner();

                    console.error('Error clearing conversation:', error);
                    alert('An error occurred while clearing the conversation.');
                });
        }
    });
}

// Function to escape HTML characters
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function appendMessage(sender, message) {
    const messageElement = document.createElement('div');
    messageElement.style.marginBottom = '10px';

    // Escape the message content
    const escapedMessage = escapeHtml(message).replace(/\n/g, '<br>');

    // Display sender's name in bold
    messageElement.innerHTML = `<strong>${sender}:</strong> ${escapedMessage}`;
    chatWindow.appendChild(messageElement);
    chatWindow.scrollTop = chatWindow.scrollHeight;
}

// Function to focus the textarea when the page loads
window.addEventListener('DOMContentLoaded', (event) => {
    if (chatMessage) {
        chatMessage.focus();
    }
});
