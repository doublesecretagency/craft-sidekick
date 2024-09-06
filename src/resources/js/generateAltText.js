console.log('generateAltText.js loaded');

// Find the alt field
const altField = document.querySelector('[name="alt"]');

if (altField) {
    console.log('Found the alt field:', altField);

    // Inject the Twig template
    fetch('/cp/sidekick/generateAltText')
        .then(response => response.text())
        .then(html => {
            // Inject the Twig template into the DOM
            altField.insertAdjacentHTML('afterend', html);

            console.log('Generate Alt Text button successfully injected');

            // Handle button click
            const generateAltTextButton = document.getElementById('generate-alt-text-button');

            if (generateAltTextButton) {
                generateAltTextButton.addEventListener('click', async () => {
                    console.log('Generate Alt Text button clicked');

                    const assetId = document.querySelector('[name="elementId"]').value;
                    console.log('Asset ID:', assetId);

                    try {
                        // Send a request to generate alt text
                        const response = await fetch('/actions/sidekick/alt-text/generate', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': Craft.csrfTokenValue,
                            },
                            body: JSON.stringify({ assetId }),
                        });

                        const data = await response.json();

                        const errorMessageDiv = document.getElementById('generate-alt-text-error-message');

                        if (data.success) {
                            console.log('Alt text generated successfully:', data.results);

                            // Update the alt text field with the generated result
                            altField.value = data.results;

                            // Clear any previous error message
                            if (errorMessageDiv) {
                                errorMessageDiv.innerText = '';
                            }
                        } else {
                            console.error('Failed to generate alt text:', data.message);

                            // Display the error message
                            if (errorMessageDiv) {
                                errorMessageDiv.innerText = data.message;
                            }
                        }
                    } catch (error) {
                        console.error('Error generating alt text:', error);

                        // Display the error message
                        if (errorMessageDiv) {
                            errorMessageDiv.innerText = 'An unexpected error occurred while generating alt text.';
                        }
                    }
                });
            } else {
                console.error('Generate Alt Text button not found');
            }
        })
        .catch(error => {
            console.error('Error injecting the Twig template:', error);
        });
} else {
    console.error('Alt field not found');
}
