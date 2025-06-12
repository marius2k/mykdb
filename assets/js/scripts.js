
function escapeHtml(text) {
    if (typeof text !== 'string') return '';
    var map = {
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

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

function submitComment1() {
  const articleId = document.getElementById('article_id').value;
  const content = document.getElementById('comment-content').value.trim();

  if (!content) {
    alert('Comentariul nu poate fi gol.');
    return false;
  }

  const data = new URLSearchParams();
  data.append('article_id', articleId);
  data.append('content', content);

  fetch('add_comment.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: data.toString()
  })
  .then(res => res.json())
  .then(json => {
    if (json.status === 'ok') {
      document.getElementById('comment-feedback').classList.remove('d-none');
      document.getElementById('comment-content').value = '';
      updateArticleMeta(articleId);
    } else {
      alert(json.message || 'Eroare la trimiterea comentariului.');
    }
  })
  .catch(() => {
    alert('Eroare AJAX!');
  });

  return false; // prevenim reload
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


function voteComment1(commentId, type) {
  fetch('vote_comment.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `comment_id=${commentId}&type=${type}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'ok') {
      document.getElementById('like-count-' + commentId).textContent = data.likes;
      document.getElementById('dislike-count-' + commentId).textContent = data.dislikes;

      // aplică stilul
      document.getElementById('like-icon-' + commentId).classList.toggle('voted', type === 'like');
      document.getElementById('dislike-icon-' + commentId).classList.toggle('voted', type === 'dislike');
    }
  })
  .catch(err => {
    console.error(err);
    alert('Eroare la vot!');
  });
}
// used to disable a comment in view_article.php

function disableComment1(commentId) {
  if (!confirm('Sigur vrei să dezactivezi comentariul?')) return;

  fetch('manage_comment.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=disable&comment_id=${commentId}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'ok') {
      const commBox = document.getElementById('comment-' + commentId);
      if (commBox) commBox.remove(); // sau ascunde
    } else {
      alert(data.message || 'Eroare la dezactivare.');
    }
  });
}

// used to delete a comment in view_article.php

function deleteComment1(commentId) {
  if (!confirm('Sigur vrei să ștergi comentariul?')) return;

  fetch('manage_comment.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=delete&comment_id=${commentId}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'ok') {
      const commBox = document.getElementById('comment-' + commentId);
      if (commBox) commBox.remove();
    } else {
      alert(data.message || 'Eroare la ștergere.');
    }
  });
}


function archiveLog1(logId) {
  if (!confirm('Arhivezi acest log?')) return;

  fetch('log_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=archive&id=${logId}`
  }).then(res => res.json()).then(data => {
    if (data.status === 'ok') location.reload();
    else alert(data.message);
  });
}

function deleteLog1(logId) {
  if (!confirm('Sigur vrei să ștergi acest log?')) return;

  fetch('log_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=delete&id=${logId}`
  }).then(res => res.json()).then(data => {
    if (data.status === 'ok') location.reload();
    else alert(data.message);
  });
}

function toggleAllLogs(master) {
  const checkboxes = document.querySelectorAll('input[name="log_ids[]"]');
  checkboxes.forEach(cb => cb.checked = master.checked);
}

function submitBulkLogs1(action) {
  const selected = [...document.querySelectorAll('input[name="log_ids[]"]:checked')]
                   .map(cb => cb.value);

  if (selected.length === 0) {
    alert("Selectează cel puțin un log.");
    return;
  }

  if (!confirm(`Sigur vrei să ${action === 'archive' ? 'arhivezi' : 'ștergi'} ${selected.length} log(uri)?`)) return;

  fetch('log_action_bulk.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action, log_ids: selected })
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'ok') {
      alert(data.message || "Operație efectuată.");
      location.reload();
    } else {
      alert(data.message || "Eroare.");
    }
  });
}


function initializeCustomBox(boxElement, pageBgColor) {
    const cornerLabel = boxElement.querySelector('.corner-label');

    if (!cornerLabel) {
      console.warn('Element .corner-label not found inside custom-box:', boxElement);
      return;
    }

    const actualPageBackgroundColor = pageBgColor || window.getComputedStyle(document.body).backgroundColor;
    boxElement.style.setProperty('--page-background-color', actualPageBackgroundColor);

    function updateBorderCutout() {
        const originalDisplay = cornerLabel.style.display;
        cornerLabel.style.display = 'inline-block';
        const labelWidth = cornerLabel.offsetWidth; 
        cornerLabel.style.display = originalDisplay;

        const labelLeftPosition = parseInt(window.getComputedStyle(cornerLabel).left); 
        const extraPaddingForCutout = 2;

        const cutoutWidth = labelWidth + (2 * extraPaddingForCutout);
        const cutoutLeft = labelLeftPosition - extraPaddingForCutout;

        boxElement.style.setProperty('--cutout-width', `${cutoutWidth}px`);
        boxElement.style.setProperty('--cutout-left', `${cutoutLeft}px`);

        const labelHeight = cornerLabel.offsetHeight;
        const cutoutCenterY = -0.5;
        cornerLabel.style.top = `${cutoutCenterY - (labelHeight / 2)}px`;
    }

    updateBorderCutout();
    // Nu mai adăugăm listener de resize AICI pentru fiecare box,
    // ci vom reface Masonry layout și vom apela updateBorderCutout pentru toate boxurile după resize.
  }

/**
   * Inițializează un box personalizat cu o etichetă în colț care întrerupe bordura.
   * @param {HTMLElement} boxElement Elementul .custom-box.
   * @param {string} [pageBgColor] Culoarea de fundal a paginii (opțional).
   * Dacă nu este specificată, se va prelua de la document.body.
   */
  function initializeCustomBox1(boxElement, pageBgColor) {
    const cornerLabel = boxElement.querySelector('.corner-label-1');

    if (!cornerLabel) {
      console.warn('Element .corner-label-1 not found inside custom-box:', boxElement);
      return; // Ieși dacă eticheta nu există
    }

    // Obținem culoarea de fundal a paginii, fie din argument, fie de la body
    const actualPageBackgroundColor = pageBgColor || window.getComputedStyle(document.body).backgroundColor;

    // Setăm o variabilă CSS pe custom-box care să conțină culoarea fundalului paginii
    boxElement.style.setProperty('--page-background-color', actualPageBackgroundColor);

    function updateBorderCutout1() {
        // Asigurăm că label-ul este vizibil (chiar dacă este transparent) pentru a calcula lățimea sa
        // Setăm direct display none/block pentru a evita reflow-uri vizuale în timpul calculului
        const originalDisplay = cornerLabel.style.display;
        cornerLabel.style.display = 'inline-block'; // Asigură că primește lățimea corectă
        const labelWidth = cornerLabel.offsetWidth; // Lățimea reală a textului cu padding
        cornerLabel.style.display = originalDisplay; // Restabilim display-ul original


        const labelLeftPosition = parseInt(window.getComputedStyle(cornerLabel).left); // Poziția "left" a etichetei
        
        // paddingAroundText în CSS este `padding: 0 5px;`
        // Dacă vrei 10px în plus înainte și după text, trebuie să adaugi 20px la `labelTextWidth`.
        const extraPaddingForCutout = 1; // Extra padding de 10px înainte și după text

        // Calculăm lățimea totală a zonei pe care vrem să o "tăiem"
        // `labelTextWidth` include deja padding-ul de 5px de pe `corner-label`.
        // Deci, `cutoutWidth` va fi lățimea etichetei + 2 * `extraPaddingForCutout`
        const cutoutWidth = labelWidth + (2 * extraPaddingForCutout);

        // Calculăm poziția de la care începe "tăietura"
        const cutoutLeft = labelLeftPosition - extraPaddingForCutout;

        // Setăm variabilele CSS pe boxElement, pe care pseudoelementul ::before le va folosi
        boxElement.style.setProperty('--cutout-width', `${cutoutWidth}px`);
        boxElement.style.setProperty('--cutout-left', `${cutoutLeft}px`);

        // Ajustăm poziția top a etichetei pentru a o centra pe "tăietură"
        // `height` pentru `::before` este 1px.
        // `top` pentru `::before` este -1px.
        // Centrul vertical al `::before` este la `-1px + (1px / 2) = -0.5px`.
        // Vrem ca centrul vertical al etichetei să fie la această poziție.
        const labelHeight = cornerLabel.offsetHeight;
        const cutoutCenterY = -0.5; // Centrul vertical al pseudoelementului de mascare
        cornerLabel.style.top = `${cutoutCenterY - (labelHeight / 2)}px`;
    }

    // Apelăm funcția de update inițial
    updateBorderCutout1();

    // Apelăm funcția și la redimensionarea ferestrei
    window.addEventListener('resize', updateBorderCutout1);
  }

