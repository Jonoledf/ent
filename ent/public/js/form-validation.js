// form-validation.js - Système de validation pour l'ENT Yvelines

document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des validateurs pour tous les formulaires
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        initFormValidation(form);
    });

    // Validation en temps réel pour les champs sensibles
    setupLiveValidation();
});

/**
 * Initialise la validation pour un formulaire spécifique
 */
function initFormValidation(form) {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Désactiver le bouton submit pendant le traitement
        const submitBtn = form.querySelector('[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner"></span> Traitement...';

        // Valider tous les champs
        const isValid = validateAllFields(form);
        
        if (isValid) {
            try {
                // Soumettre le formulaire via AJAX
                const response = await submitForm(form);
                
                // Gérer la réponse
                if (response.success) {
                    showSuccessMessage(form, response.message);
                    
                    // Redirection si spécifiée
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    }
                } else {
                    showFormError(form, response.message || 'Une erreur est survenue');
                }
            } catch (error) {
                showFormError(form, 'Erreur réseau: ' + error.message);
                console.error('Erreur:', error);
            }
        }
        
        // Réactiver le bouton
        submitBtn.disabled = false;
        submitBtn.textContent = submitBtn.dataset.originalText || 'Envoyer';
    });

    // Stocker le texte original du bouton
    const submitBtn = form.querySelector('[type="submit"]');
    if (submitBtn) {
        submitBtn.dataset.originalText = submitBtn.textContent;
    }
}

/**
 * Validation en temps réel pour les champs
 */
function setupLiveValidation() {
    // Validation de l'email/matricule
    document.querySelectorAll('[data-validate="username"]').forEach(input => {
        input.addEventListener('input', function() {
            validateUsername(this);
        });
    });

    // Validation du mot de passe
    document.querySelectorAll('[data-validate="password"]').forEach(input => {
        input.addEventListener('input', function() {
            validatePassword(this);
            updatePasswordStrength(this.value);
        });
    });

    // Validation des champs requis
    document.querySelectorAll('[required]').forEach(input => {
        input.addEventListener('blur', function() {
            validateRequired(this);
        });
    });
}

/**
 * Valide tous les champs d'un formulaire
 */
function validateAllFields(form) {
    let isValid = true;
    
    // Valider chaque champ avec des attributs data-validate
    form.querySelectorAll('[data-validate]').forEach(field => {
        const fieldType = field.dataset.validate;
        let fieldValid = false;
        
        switch(fieldType) {
            case 'username':
                fieldValid = validateUsername(field);
                break;
            case 'password':
                fieldValid = validatePassword(field);
                break;
            case 'email':
                fieldValid = validateEmail(field);
                break;
            default:
                fieldValid = validateRequired(field);
        }
        
        if (!fieldValid) isValid = false;
    });
    
    return isValid;
}

/**
 * Validation spécifique pour les identifiants (email ou matricule)
 */
function validateUsername(input) {
    const value = input.value.trim();
    const errorElement = getErrorElement(input);
    
    // Pattern pour email ou matricule (6 chiffres)
    const usernameRegex = /^(?:[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}|\d{6})$/;
    
    if (!value) {
        showError(input, errorElement, 'Ce champ est requis');
        return false;
    }
    
    if (!usernameRegex.test(value)) {
        showError(input, errorElement, 'Doit être un email valide ou un matricule à 6 chiffres');
        return false;
    }
    
    clearError(input, errorElement);
    return true;
}

/**
 * Validation du mot de passe
 */
function validatePassword(input) {
    const value = input.value;
    const errorElement = getErrorElement(input);
    
    if (!value) {
        showError(input, errorElement, 'Ce champ est requis');
        return false;
    }
    
    // Au moins 8 caractères, une majuscule, un chiffre et un caractère spécial
    const strongRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
    
    if (!strongRegex.test(value)) {
        showError(input, errorElement, 
            'Le mot de passe doit contenir au moins 8 caractères, une majuscule, un chiffre et un caractère spécial');
        return false;
    }
    
    clearError(input, errorElement);
    return true;
}

/**
 * Met à jour l'indicateur de force du mot de passe
 */
function updatePasswordStrength(password) {
    const strengthIndicator = document.getElementById('password-strength');
    if (!strengthIndicator) return;
    
    // Calcul de la force
    let strength = 0;
    if (password.length >= 8) strength += 1;
    if (/[A-Z]/.test(password)) strength += 1;
    if (/\d/.test(password)) strength += 1;
    if (/[@$!%*?&]/.test(password)) strength += 1;
    
    // Mise à jour visuelle
    strengthIndicator.className = 'password-strength';
    strengthIndicator.classList.add(
        strength < 2 ? 'strength-weak' : 
        strength < 4 ? 'strength-medium' : 'strength-strong'
    );
}

/**
 * Validation des champs requis
 */
function validateRequired(input) {
    const value = input.value.trim();
    const errorElement = getErrorElement(input);
    
    if (input.required && !value) {
        showError(input, errorElement, 'Ce champ est requis');
        return false;
    }
    
    clearError(input, errorElement);
    return true;
}

/**
 * Soumission AJAX du formulaire
 */
async function submitForm(form) {
    const formData = new FormData(form);
    const action = form.getAttribute('action') || window.location.pathname;
    const method = form.getAttribute('method') || 'POST';
    
    try {
        const response = await fetch(action, {
            method: method,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        return await response.json();
    } catch (error) {
        throw new Error('Erreur réseau');
    }
}

/**
 * Gestion des erreurs de champ
 */
function showError(input, errorElement, message) {
    input.classList.add('input-error');
    errorElement.textContent = message;
    errorElement.style.display = 'block';
}

function clearError(input, errorElement) {
    input.classList.remove('input-error');
    errorElement.style.display = 'none';
}

function getErrorElement(input) {
    return input.nextElementSibling?.classList.contains('error-message') 
        ? input.nextElementSibling 
        : document.getElementById(`${input.id}-error`);
}

/**
 * Affichage des messages globaux
 */
function showFormError(form, message) {
    const errorContainer = form.querySelector('.form-error-message') || createMessageContainer(form);
    errorContainer.className = 'form-error-message error-message';
    errorContainer.innerHTML = `<p>${message}</p>`;
    errorContainer.style.display = 'block';
    
    // Défilement vers l'erreur
    errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function showSuccessMessage(form, message) {
    const successContainer = form.querySelector('.form-success-message') || createMessageContainer(form);
    successContainer.className = 'form-success-message success-message';
    successContainer.innerHTML = `<p>${message}</p>`;
    successContainer.style.display = 'block';
}

function createMessageContainer(form) {
    const container = document.createElement('div');
    container.style.display = 'none';
    form.prepend(container);
    return container;
}

/**
 * Fonction exportable pour validation externe
 */
window.validateField = function(field) {
    const fieldType = field.dataset.validate;
    
    switch(fieldType) {
        case 'username': return validateUsername(field);
        case 'password': return validatePassword(field);
        case 'email': return validateEmail(field);
        default: return validateRequired(field);
    }
};