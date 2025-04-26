document.addEventListener('DOMContentLoaded', function() {
    // Gestion de la déconnexion
    document.getElementById('logout-btn').addEventListener('click', function(e) {
        e.preventDefault();
        logoutUser();
    });
    
    // Menu mobile
    setupMobileMenu();
    
    // Initialiser les tooltips
    initTooltips();
});

function logoutUser() {
    fetch('/api/auth/logout', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': getCSRFToken()
        }
    })
    .then(response => {
        if (response.ok) {
            window.location.href = '/login.html';
        }
    })
    .catch(error => {
        console.error('Erreur de déconnexion:', error);
    });
}

function setupMobileMenu() {
    const menuToggle = document.createElement('button');
    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
    menuToggle.className = 'menu-toggle';
    document.querySelector('.header-left').appendChild(menuToggle);
    
    menuToggle.addEventListener('click', function() {
        document.querySelector('.dashboard-sidebar').classList.toggle('active');
    });
}

function initTooltips() {
    // Initialisation des tooltips si nécessaire
}