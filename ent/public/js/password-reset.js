document.addEventListener('DOMContentLoaded', function() {
    const passwordResetForm = document.getElementById('passwordResetForm');
    const formMessages = document.getElementById('formMessages');
    const successMessage = document.getElementById('successMessage');

    passwordResetForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Reset messages
        formMessages.style.display = 'none';
        formMessages.innerHTML = '';
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.classList.add('is-loading');
        
        // Get form data
        const email = document.getElementById('email').value.trim();
        
        try {
            // Send request to server
            const response = await fetch('/api/auth/password-reset', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email })
            });
            
            const data = await response.json();
            
            if (response.ok) {
                // Show success message
                passwordResetForm.style.display = 'none';
                successMessage.style.display = 'block';
            } else {
                // Show error message
                formMessages.style.display = 'block';
                formMessages.innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        ${data.message || 'Une erreur est survenue'}
                    </div>
                `;
                
                // Scroll to message
                formMessages.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } catch (error) {
            console.error('Error:', error);
            formMessages.style.display = 'block';
            formMessages.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    Erreur réseau. Veuillez réessayer.
                </div>
            `;
        } finally {
            submitBtn.classList.remove('is-loading');
        }
    });
});