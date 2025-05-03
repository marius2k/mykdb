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


function togglePasswordVisibility() {
    const passwordInput = document.getElementById("password");
    const toggleBtn = document.querySelector(".toggle-password");

    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleBtn.textContent = "🙈";
    } else {
        passwordInput.type = "password";
        toggleBtn.textContent = "👁️";
    }
}


// Function to toggle password visibility


const password = document.getElementById('password');
const confirmPassword = document.getElementById('confirm_password');
const message = document.getElementById('password-match-msg');
const submitBtn = document.querySelector('button[type="submit"]');

function validatePasswords() {
    if (confirmPassword.value.length === 0) {
        message.style.display = "none";
        submitBtn.disabled = false;
        return;
    }

    if (password.value !== confirmPassword.value) {
        message.style.display = "block";
        message.textContent = "Parolele nu coincid";
        message.style.color = "red";
        submitBtn.disabled = true;
    } else {
        message.style.display = "block";
        message.textContent = "Parolele coincid ✔️";
        message.style.color = "green";
        submitBtn.disabled = false;
    }
}

password.addEventListener('input', validatePasswords);
confirmPassword.addEventListener('input', validatePasswords);
// Function to handle the form submission