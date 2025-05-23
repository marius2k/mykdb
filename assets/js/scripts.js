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


function togglePasswordVisibility(inputId,btnVisible) {
    const passwordInput = document.getElementById(inputId);
    //const passwordInput = document.getElementById("password");
    //const toggleBtn = document.querySelector(".toggle-password");
    const toggleBtn = document.getElementById(btnVisible);

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



function voteArticle(articleId, voteType, el) {
  fetch('vote_article.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: `aid=${articleId}&vote=${voteType}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'ok') {
      // Găsim ambele span-uri (în același container)
      const container = el.closest('div') || el.parentNode;
      const likeSpan = container.querySelector('.like-count');
      const dislikeSpan = container.querySelector('.dislike-count');

      if (likeSpan) likeSpan.textContent = data.likes;
      if (dislikeSpan) dislikeSpan.textContent = data.dislikes;
        // eliminăm toate clasele "active" locale
        container.querySelectorAll('.vote-icon').forEach(img => img.classList.remove('active'));

        // adăugăm clasa doar pe iconul votat
        if (voteType === 'like') {
                container.querySelector('img[src*="icon-like"]').classList.add('active');
        } else {
                container.querySelector('img[src*="icon-dlike"]').classList.add('active');
        }
    } else {
      alert(data.message || 'Eroare la vot!');
    }
  })
  .catch(err => {
    console.error(err);
    alert('Eroare AJAX!');
  });

 
}

function submitComment() {
  const articleId = document.getElementById('article_id').value;
  const content = document.getElementById('comment-content').value.trim();

  if (!content) {
    alert('Comentariul nu poate fi gol.');
    return false;
  }

  fetch('add_comment.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `article_id=${encodeURIComponent(articleId)}&content=${encodeURIComponent(content)}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'ok') {
      document.getElementById('comment-feedback').classList.remove('d-none');
      document.getElementById('comment-content').value = '';
      updateArticleMeta(articleId); // actualizează contorul 💬
    } else {
      alert(data.message || 'Eroare la trimiterea comentariului.');
    }
  })
  .catch(err => {
    console.error(err);
    alert('Eroare AJAX!');
  });

  return false; // prevenim reload
}

function updateArticleMeta(articleId) {
  fetch('get_article_meta.php?aid=' + articleId)
    .then(res => res.json())
    .then(data => {
      const container = document.querySelector('#meta-' + articleId);
      if (!container) return;

      const views = container.querySelector('.views-count');
      const comments = container.querySelector('.comments-count');

      if (views) views.textContent = data.views;
      if (comments) comments.textContent = data.comments;
    })
    .catch(err => console.error('Eroare la update meta:', err));
}

// face toggle (arata-ascunde) pe un form. Nu tine cont ca in pagina exista un alt form deschis.

function toggleAddForm(formId) {
  const form = document.getElementById(formId);
  const isHidden = form.style.display === 'none' || getComputedStyle(form).display === 'none';
  form.style.display = isHidden ? 'block' : 'none';

  if (isHidden) {
    const input = form.querySelector('input[type="text"], input:not([type])');
    if (input) input.focus();
  }
}


// face toggle pe un form (arata-ascunde) DAR tine cont daca in pagina mai exista alt form deja deschis
// pe care il inchide

function toggleAddFormHide(formId, buttonEl) {
  const allForms = document.querySelectorAll('.form-box');
  const allButtons = document.querySelectorAll('.btn-toggle-form');
  const targetForm = document.getElementById(formId);

  const isCurrentlyVisible = getComputedStyle(targetForm).display !== 'none';

  // 🔁 Ascunde toate formularele
  allForms.forEach(form => form.style.display = 'none');

  // 🔁 Curăță complet toate butoanele
  allButtons.forEach(btn => {
    btn.classList.remove('active-tabs');
    btn.style.backgroundColor = ''; // ✨ eliminăm stilul inline
  });

  if (!isCurrentlyVisible) {
    targetForm.style.display = 'block';

    const formBg = getComputedStyle(targetForm).backgroundColor;

    buttonEl.style.backgroundColor = formBg;
    buttonEl.classList.add('active-tabs');

    const input = targetForm.querySelector('input[type="text"], input:not([type])');
    if (input) input.focus();
  }
}



function formatWithIcon(option) {
  if (!option.id) return option.text;
  const img = $(option.element).data('img');
  return $(`<span><img src="${img}" width="20" style="margin-right:8px;" />${option.text}</span>`);
}
