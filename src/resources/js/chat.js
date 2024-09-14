const chatWindow = document.getElementById('chat-window');
const chatForm = document.getElementById('chat-form');
const chatMessage = document.getElementById('chat-message');
const MAX_MESSAGE_LENGTH = 1000; // Set an appropriate limit

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
            if (data.success) {
                appendMessage('Sidekick', data.message);
            } else {
                appendMessage('Error', data.error || 'An error occurred.');
            }
        })
        .catch(error => {
            // Optionally log error in development
            // if (isDevelopmentEnvironment) {
            //     console.error('Network Error:', error);
            // }
            appendMessage('Error', 'A network error occurred. Please check your connection and try again.');
        });
});

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
