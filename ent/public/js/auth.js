document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const errorMessage = document.getElementById('errorMessage');
    const rememberCheckbox = document.getElementById('remember');

    // Gestion de la soumission du formulaire
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const rememberMe = rememberCheckbox.checked;
        
        try {
            const response = await fetch('/api/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': await getCSRFToken()
                },
                body: JSON.stringify({
                    username,
                    password,
                    remember: rememberMe
                })
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Erreur de connexion');
            }
            
            // Redirection après connexion réussie
            if (data.requiresPasswordChange) {
                window.location.href = '/change-password.html';
            } else {
                window.location.href = '/dashboard.html';
            }
            
        } catch (error) {
            errorMessage.textContent = error.message;
            errorMessage.style.display = 'block';
            
            // Réessayer après 3 secondes
            setTimeout(() => {
                errorMessage.style.display = 'none';
            }, 5000);
        }
    });
    
    // Vérification des exigences de mot de passe
    document.getElementById('password').addEventListener('input', function() {
        validatePasswordStrength(this.value);
    });
});

async function getCSRFToken() {
    const response = await fetch('/api/auth/csrf-token');
    const data = await response.json();
    return data.token;
}

function validatePasswordStrength(password) {
    // Au moins 8 caractères, une majuscule, un chiffre et un caractère spécial
    const strongRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
    return strongRegex.test(password);
}