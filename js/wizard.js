function selectChoice(fieldName, value) {
    // Update radio button
    document.getElementById(fieldName + '_' + value).checked = true;
    
    // Update visual selection
    document.querySelectorAll('.choice-option').forEach(option => {
        option.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
}

function selectMethod(method) {
    // Update radio button
    document.querySelector(`input[name="connection_method"][value="${method}"]`).checked = true;
    
    // Update headers
    document.querySelectorAll('.method-header').forEach(header => {
        header.classList.remove('active');
    });
    event.currentTarget.classList.add('active');
    
    // Update bodies
    document.querySelectorAll('.method-body').forEach(body => {
        body.classList.remove('active');
        // Remove required from hidden fields
        body.querySelectorAll('[required]').forEach(field => {
            field.removeAttribute('required');
        });
    });
    
    const activeBody = document.getElementById(method + '-body');
    if (activeBody) {
        activeBody.classList.add('active');
        // Add required to visible fields
        activeBody.querySelectorAll('input[data-required="true"]').forEach(field => {
            field.setAttribute('required', 'required');
        });
    }
}

function confirmReset() {
    return confirm('Are you sure you want to reset the wizard? All entered data will be lost.');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set initial selection states
    const selectedChoice = document.querySelector('input[name="config_choice"]:checked');
    if (selectedChoice) {
        selectedChoice.closest('.choice-option').classList.add('selected');
    }
    
    const selectedMethod = document.querySelector('input[name="connection_method"]:checked');
    if (selectedMethod) {
        const method = selectedMethod.value;
        document.querySelector(`.method-header input[value="${method}"]`).closest('.method-header').classList.add('active');
        const activeBody = document.getElementById(method + '-body');
        if (activeBody) {
            activeBody.classList.add('active');
        }
    }
    
    // Disable form validation when clicking Previous or Reset
    const previousBtn = document.querySelector('button[value="previous"]');
    const resetBtn = document.querySelector('button[value="reset"]');
    
    if (previousBtn) {
        previousBtn.addEventListener('click', function() {
            document.querySelectorAll('[required]').forEach(field => {
                field.removeAttribute('required');
            });
        });
    }
    
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            document.querySelectorAll('[required]').forEach(field => {
                field.removeAttribute('required');
            });
        });
    }
});
