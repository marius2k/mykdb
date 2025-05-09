const input = document.getElementById('liveSearch');
const results = document.getElementById('searchResults');
const defaultContent = document.getElementById('defaultContent');

// Debounce function
function debounce(func, delay = 300) {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), delay);
    };
}

// Search logic
const searchArticles = async () => {
    const query = input.value.trim();
    if (query.length < 2) {
        results.innerHTML = '';
        defaultContent.style.display = 'block';
        return;
    }

    const res = await fetch('search_articles.php?q=' + encodeURIComponent(query));
    const data = await res.json();

    defaultContent.style.display = 'none'; // HIDE DEFAULT CONTENT

    results.innerHTML = data.map(article => `
        <div style="border:1px solid #ccc; padding:10px; margin-bottom:5px;">
            <h4>${article.title}</h4>
            <p><em>Autor: ${article.username} | Categorie: ${article.category} | ${new Date(article.created_at).toLocaleDateString()}</em></p>
            <p>${article.content.substring(0, 200)}...</p>
        </div>
    `).join('') || '<p>Nu s-au găsit articole.</p>';
};

input.addEventListener('input', debounce(searchArticles, 300));


function togglePasswordVisibility(inputId) {
    const passwordInput = document.getElementById(inputId);
    //const passwordInput = document.getElementById("password");
    const toggleBtn = document.querySelector(".toggle-password");

    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleBtn.textContent = "🙈";
    } else {
        passwordInput.type = "password";
        toggleBtn.textContent = "👁️";
    }
}


/**
 * Inițializează tooltip-urile Bootstrap
 */
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            trigger: 'hover'
        });
    });
}

/**
 * Inițializează popover-urile Bootstrap
 */
function initPopovers() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl, {
            trigger: 'focus'
        });
    });
}

/**
 * Buton "Back to Top"
 */
function initBackToTop() {
    const backToTopButton = document.getElementById('back-to-top');
    
    if (backToTopButton) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.add('show');
            } else {
                backToTopButton.classList.remove('show');
            }
        });
        
        backToTopButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
}



// Function to toggle password visibility


// Function to handle the form submission