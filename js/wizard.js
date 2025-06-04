/**
 * Installation Wizard JavaScript
 * Handles form interactions and dynamic behavior
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeWizard();
});

/**
 * Initialize wizard functionality
 */
function initializeWizard() {
    // Initialize choice selection
    initializeChoiceSelection();
    
    // Initialize method selection
    initializeMethodSelection();
    
    // Initialize database credential toggles
    initializeDbCredentialToggles();
    
    // Initialize conditional field display
    initializeConditionalFields();
    
    // Apply default values on load
    applyDefaultValues();
}

/**
 * Initialize choice selection (radio button cards)
 */
function initializeChoiceSelection() {
    const choiceOptions = document.querySelectorAll('.choice-option');
    
    choiceOptions.forEach(option => {
        option.addEventListener('click', function() {
            const radioInput = this.querySelector('input[type="radio"]');
            if (radioInput) {
                radioInput.checked = true;
                updateChoiceSelection(radioInput);
                triggerConditionalFields();
            }
        });
    });
    
    // Handle direct radio input changes
    const radioInputs = document.querySelectorAll('.choice-option input[type="radio"]');
    radioInputs.forEach(input => {
        input.addEventListener('change', function() {
            updateChoiceSelection(this);
            triggerConditionalFields();
        });
    });
}

/**
 * Update choice selection visual state
 */
function updateChoiceSelection(selectedInput) {
    const fieldName = selectedInput.name;
    const allOptions = document.querySelectorAll(`input[name="${fieldName}"]`).forEach(input => {
        const option = input.closest('.choice-option');
        if (option) {
            option.classList.remove('selected');
        }
    });
    
    const selectedOption = selectedInput.closest('.choice-option');
    if (selectedOption) {
        selectedOption.classList.add('selected');
    }
}

/**
 * Global function for choice selection (called from onclick)
 */
function selectChoice(fieldName, value) {
    const input = document.querySelector(`input[name="${fieldName}"][value="${value}"]`);
    if (input) {
        input.checked = true;
        updateChoiceSelection(input);
        triggerConditionalFields();
    }
}

/**
 * Initialize method selection (accordion)
 */
function initializeMethodSelection() {
    const methodHeaders = document.querySelectorAll('.method-header');
    
    methodHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const radioInput = this.querySelector('input[type="radio"]');
            if (radioInput) {
                radioInput.checked = true;
                updateMethodSelection(radioInput.value);
            }
        });
    });
    
    // Handle direct radio input changes
    const methodRadios = document.querySelectorAll('.method-header input[type="radio"]');
    methodRadios.forEach(input => {
        input.addEventListener('change', function() {
            updateMethodSelection(this.value);
        });
    });
}

/**
 * Update method selection (accordion behavior)
 */
function updateMethodSelection(selectedMethod) {
    // Close all method bodies and remove active states
    document.querySelectorAll('.method-accordion').forEach(accordion => {
        const header = accordion.querySelector('.method-header');
        const body = accordion.querySelector('.method-body');
        
        header.classList.remove('active');
        body.classList.remove('active');
    });
    
    // Open selected method
    const selectedBody = document.getElementById(selectedMethod + '-body');
    if (selectedBody) {
        const selectedHeader = selectedBody.previousElementSibling;
        if (selectedHeader) {
            selectedHeader.classList.add('active');
        }
        selectedBody.classList.add('active');
    }
}

/**
 * Global function for method selection (called from onclick)
 */
function selectMethod(methodKey) {
    const input = document.querySelector(`input[name="connection_method"][value="${methodKey}"]`);
    if (input) {
        input.checked = true;
        updateMethodSelection(methodKey);
    }
}

/**
 * Initialize database credential toggles
 */
function initializeDbCredentialToggles() {
    const useDefaultCheckboxes = document.querySelectorAll('input[id$="_use_default"]');
    
    useDefaultCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const fieldId = this.id.replace('_use_default', '');
            toggleDbCredentials(fieldId);
        });
    });
}

/**
 * Toggle database credentials visibility
 */
function toggleDbCredentials(fieldId) {
    const checkbox = document.getElementById(fieldId + '_use_default');
    const fieldsContainer = document.getElementById(fieldId + '_fields');
    
    if (checkbox && fieldsContainer) {
        if (checkbox.checked) {
            fieldsContainer.style.display = 'none';
            // Disable all inputs in the container
            fieldsContainer.querySelectorAll('input').forEach(input => {
                input.disabled = true;
            });
        } else {
            fieldsContainer.style.display = 'block';
            // Enable all inputs in the container
            fieldsContainer.querySelectorAll('input').forEach(input => {
                input.disabled = false;
            });
        }
    }
}

/**
 * Initialize conditional field display
 */
function initializeConditionalFields() {
    // Monitor changes to fields that have conditions
    const conditionalTriggers = document.querySelectorAll('input[name="config_method"]');
    
    conditionalTriggers.forEach(trigger => {
        trigger.addEventListener('change', triggerConditionalFields);
    });
}

/**
 * Show/hide fields based on conditions
 */
function triggerConditionalFields() {
    const configChoice = document.querySelector('input[name="config_method"]:checked');
    
    if (configChoice) {
        const selectedValue = configChoice.value;
        
        // Handle ini_files field
        const iniFilesField = document.querySelector('.ini-files-section');
        if (iniFilesField) {
            const iniFilesContainer = iniFilesField.closest('.form-group, [data-field="ini_files"]');
            if (iniFilesContainer) {
                if (selectedValue === 'ini_import') {
                    iniFilesContainer.style.display = 'block';
                } else {
                    iniFilesContainer.style.display = 'none';
                }
            }
        }
        
        // Handle connection_method field
        const connectionMethodField = document.querySelector('.connection-methods');
        if (connectionMethodField) {
            const connectionMethodContainer = connectionMethodField.closest('.form-group, [data-field="connection_method"]');
            if (connectionMethodContainer) {
                if (selectedValue === 'use_existing' || selectedValue === 'start_fresh') {
                    connectionMethodContainer.style.display = 'block';
                } else {
                    connectionMethodContainer.style.display = 'none';
                }
            }
        }
    }
}

/**
 * Apply default values on page load
 */
function applyDefaultValues() {
    // Apply choice selections
    const selectedChoices = document.querySelectorAll('input[type="radio"]:checked');
    selectedChoices.forEach(input => {
        if (input.closest('.choice-option')) {
            updateChoiceSelection(input);
        }
        if (input.closest('.method-header')) {
            updateMethodSelection(input.value);
        }
    });
    
    // Apply conditional field visibility
    triggerConditionalFields();
    
    // Apply database credential toggles
    const useDefaultCheckboxes = document.querySelectorAll('input[id$="_use_default"]:checked');
    useDefaultCheckboxes.forEach(checkbox => {
        const fieldId = checkbox.id.replace('_use_default', '');
        toggleDbCredentials(fieldId);
    });
}

/**
 * Form validation helper
 */
function validateForm() {
    let isValid = true;
    const requiredFields = document.querySelectorAll('input[required], select[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

/**
 * Handle form submission
 */
function handleFormSubmit(event) {
    if (!validateForm()) {
        event.preventDefault();
        
        // Show error message
        const firstInvalidField = document.querySelector('.is-invalid');
        if (firstInvalidField) {
            firstInvalidField.focus();
            firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        return false;
    }
    
    return true;
}

// Attach form validation to form submission
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.helpers-form');
    if (form) {
        form.addEventListener('submit', handleFormSubmit);
    }
});

/**
 * Reset form to initial state
 */
function resetWizardForm() {
    const form = document.querySelector('.helpers-form');
    if (form) {
        form.reset();
        
        // Reset visual states
        document.querySelectorAll('.choice-option').forEach(option => {
            option.classList.remove('selected');
        });
        
        document.querySelectorAll('.method-header').forEach(header => {
            header.classList.remove('active');
        });
        
        document.querySelectorAll('.method-body').forEach(body => {
            body.classList.remove('active');
        });
        
        // Re-apply defaults
        setTimeout(applyDefaultValues, 100);
    }
}

/**
 * Utility function to get form data as object
 */
function getFormData() {
    const form = document.querySelector('.helpers-form');
    if (!form) return {};
    
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    return data;
}

/**
 * Debug function to log current form state
 */
function debugFormState() {
    console.log('Current form data:', getFormData());
    console.log('Conditional fields state:', {
        configChoice: document.querySelector('input[name="config_method"]:checked')?.value,
        connectionMethod: document.querySelector('input[name="connection_method"]:checked')?.value
    });
}
