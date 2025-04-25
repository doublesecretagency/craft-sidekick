/**
 * Clear an AI Summary field.
 *
 * @param label
 * @param namespace
 */
function sidekickClear(label, namespace) {

    // Get the textarea element
    $textarea = $(`textarea[name="${namespace}"]`);

    // If the field is already empty, bail
    if (!$textarea.val()) {
        return;
    }

    // Confirmation question
    const question = `Are you sure you want to clear the "${label}" field?`;

    // If not confirmed, bail
    if (!confirm(question)) {
        return;
    }

    // Clear the field
    $textarea.val('');

}

/**
 * Regenerate an AI Summary field.
 *
 * @param label
 * @param namespace
 * @param fieldId
 * @param elementId
 */
function sidekickRegenerate(label, namespace, fieldId, elementId) {

    // Get the textarea element
    const $textarea = $(`textarea[name="${namespace}"]`);

    // If a value exists
    if ($textarea.val()) {

        // Confirmation question
        const question = `Are you sure you want to regenerate the "${label}" field?`;

        // If not confirmed, bail
        if (!confirm(question)) {
            return;
        }

    }

    // Get the regenerating message element
    const $regenerating = $textarea
        .closest('.input')
        .find('.ai-waiting');

    // Show the regenerating message
    $regenerating.addClass('visible');

    // Generate a fresh summary
    Craft.sendActionRequest('POST', 'sidekick/ai-summary/generate', {
        data: {
            'fieldId': fieldId,
            'elementId': elementId
        }
    }).then((response) => {

        // Hide the regenerating message
        $regenerating.removeClass('visible');

        // If the request was successful
        if (response.data.success) {
            // Update the field value based on the response
            $textarea.val(response.data.content);
        } else {
            // Something went wrong, alert an error message
            alert(response.data.message);
        }

    }).catch((error) => {

        // Hide the regenerating message
        $regenerating.removeClass('visible');

        // Something went wrong, log an error message
        console.error('Request failed:', error);

    });

}
