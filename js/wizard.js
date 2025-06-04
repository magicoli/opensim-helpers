/**
 * Installation Wizard JavaScript
 * Handles form interactions and dynamic behavior
 */

// Wizard interaction functions

/**
 * Select choice and show/hide sub-fields
 */
function selectChoice(fieldId, choiceValue) {
    // Update hidden input for select-nested fields
    const hiddenInput = document.getElementById(fieldId);
    if (hiddenInput && hiddenInput.type === 'hidden') {
        hiddenInput.value = choiceValue;
    } else {
        // Fallback for radio button fields
        const radioInput = document.querySelector(`input[name="${fieldId}"][value="${choiceValue}"]`);
        if (radioInput) {
            radioInput.checked = true;
        }
    }
    
    // Update visual selection using Bootstrap classes
    document.querySelectorAll(`[onclick*="selectChoice('${fieldId}'"]`).forEach(option => {
        if (option.onclick.toString().includes(`'${choiceValue}'`)) {
            // Selected card
            option.classList.remove('border-secondary');
            option.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
        } else {
            // Unselected cards
            option.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
            option.classList.add('border-secondary');
        }
    });
    
    // Show/hide sub-fields using Bootstrap classes
    document.querySelectorAll(`[id^="${fieldId}_"][id$="_fields"]`).forEach(subFields => {
        if (subFields.id === `${fieldId}_${choiceValue}_fields`) {
            subFields.classList.remove('d-none');
        } else {
            subFields.classList.add('d-none');
        }
    });
}

/**
 * Select connection method and show/hide method body
 */
function selectMethod(methodKey) {
    // Update radio button
    const radioInput = document.querySelector(`input[name="connection_method"][value="${methodKey}"]`);
    if (radioInput) {
        radioInput.checked = true;
    }
    
    // Update all method headers and bodies using Bootstrap classes
    document.querySelectorAll('.method-accordion').forEach(accordion => {
        const header = accordion.querySelector('.method-header');
        const body = accordion.querySelector('.method-body');
        const radio = header.querySelector('input[type="radio"]');
        
        if (radio && radio.value === methodKey) {
            // Active method
            header.classList.remove('bg-light', 'border-secondary');
            header.classList.add('bg-primary', 'bg-opacity-10', 'border-primary');
            body.classList.remove('border-secondary', 'd-none');
            body.classList.add('border-primary');
        } else {
            // Inactive methods
            header.classList.remove('bg-primary', 'bg-opacity-10', 'border-primary');
            header.classList.add('bg-light', 'border-secondary');
            body.classList.remove('border-primary');
            body.classList.add('border-secondary', 'd-none');
        }
    });
}

/**
 * Toggle database credentials fields
 */
function toggleDbCredentials(fieldId) {
    const checkbox = document.getElementById(`${fieldId}_use_default`);
    const fieldsContainer = document.getElementById(`${fieldId}_fields`);
    
    if (checkbox && fieldsContainer) {
        if (checkbox.checked) {
            fieldsContainer.style.display = 'none';
        } else {
            fieldsContainer.style.display = 'block';
        }
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Set up initial state for checked radio buttons
    document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
        if (radio.name === 'config_method') {
            selectChoice(radio.name, radio.value);
        } else if (radio.name === 'connection_method') {
            selectMethod(radio.value);
        }
    });
});
