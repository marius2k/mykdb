<?php 
include_once '../config/bootstrap.php';
include '../includes/header.php'; 

// Get article ID and version from URL parameters
$articleId = (int)($_GET['id'] ?? 0);
$version = (int)($_GET['version'] ?? 1);
$isOnline = (int)($_GET['isonline'] ?? 0);


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!$articleId) {
    $_SESSION['flash'] = "Article ID missing";
    header("Location: index.php");
    exit;
}

// Increment view counter doar dacă versiunea este online
if ($articleId && $isOnline==1){
    try {
        // Detect view source based on GET parameter and referer
        $viewSource = 'public'; // default
        
        if (isset($_GET['admin_source'])) {
            // Explicit admin source parameter
            $viewSource = $_GET['admin_source'];
        } else {
            // Detect based on referer
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            if (strpos($referer, '/admin/') !== false || strpos($referer, '/public/admin/') !== false) {
                $viewSource = 'admin_preview';
            }
        }
        
        // Track the view in the existing log system
        logArticleView($articleId, $viewSource);
        
        // Also track in new analytics system if not an admin preview
        if ($viewSource !== 'admin_preview' && isset($_SESSION['user']['id'])) {
            // We'll add JavaScript tracking for this on the page for better user experience
        }
        
        //error_log("View incremented for article ID: $articleId with source: $viewSource");
    } catch (Exception $e) {
        error_log("Error in logArticleView: " . $e->getMessage());
    }
}


// Verifică articolul și permisiunile
$db = new Database();
$article = null;
$userId = $_SESSION['user']['id'] ?? 0;
$userRole = $_SESSION['user']['role'] ?? 'guest';

try {
    // Debug: afișează parametrii primiți
    //error_log("view_article.php - Received params: articleId=$articleId, version=$version, isOnline=$isOnline");
    
    if ($isOnline == 1) {
        //error_log("view_article.php - Looking for ONLINE version");
        // Pentru versiuni online, încearcă mai întâi în articles
        $article = $db->fetchSingle("
            SELECT a.*, u.username, c.name as category_name, c.icon as category_icon,
                   'articles' as source_table, 0 as is_version, a.version as version_number
            FROM articles a 
            JOIN users u ON a.user_id = u.id 
            LEFT JOIN categories c ON a.category_id = c.id 
            WHERE a.id = ? AND a.status = 'approved'
        ", [$articleId]);
        
        if (!$article) {
            //error_log("view_article.php - Not found in articles, trying article_versions with is_online=1");
            // Fallback: caută versiunea online în article_versions
            $article = $db->fetchSingle("
                SELECT av.*, av.author_id as user_id, u.username, c.name as category_name, c.icon as category_icon,
                       'article_versions' as source_table, 1 as is_version
                FROM article_versions av 
                JOIN users u ON av.author_id = u.id 
                LEFT JOIN categories c ON av.category_id = c.id 
                WHERE av.article_id = ? AND av.version_number = ? AND av.is_online = 1
            ", [$articleId, $version]);
        }
    } else {
        //error_log("view_article.php - Looking for NON-ONLINE version in article_versions");
        // Pentru versiuni non-online, caută în article_versions
        $article = $db->fetchSingle("
            SELECT av.*, av.author_id as user_id, u.username, c.name as category_name, c.icon as category_icon,
                   'article_versions' as source_table, 1 as is_version
            FROM article_versions av 
            JOIN users u ON av.author_id = u.id 
            LEFT JOIN categories c ON av.category_id = c.id 
            WHERE av.article_id = ? AND av.version_number = ?
        ", [$articleId, $version]);
    }

    /*
    if ($article) {
        error_log("view_article.php - Article found: " . json_encode([
            'id' => $article['id'] ?? $article['article_id'],
            'title' => $article['title'],
            'status' => $article['status'],
            'is_online' => $article['is_online'] ?? 'N/A',
            'source' => $article['source_table']
        ]));
    } else {
        error_log("view_article.php - Article NOT found");
    }
    */

    if (!$article) {
        $_SESSION['flash'] = "Articol inexistent";
        //error_log("view_article.php - Redirecting to index.php - article not found");
        //header("Location: index.php");
        exit;
    }

    // Verifică permisiunile de vizualizare
    $isOwner = (int)$article['user_id'] === (int)$userId;
    $canView = false;

    switch ($userRole) {
        case 'contributor':
            $canView = in_array($article['status'], ['approved', 'disabled']) || 
                       ($isOwner && in_array($article['status'], ['draft', 'pending']));
            break;
            
        case 'editor':
        case 'moderator':
        case 'admin':
        case 'superadmin':
            $canView = true;
            break;
            
        default:
            $canView = $article['status'] === 'approved';
            break;
    }

    if (!$canView) {
        $_SESSION['flash'] = "Articol inexistent sau neaprobat";
        header("Location: index.php");
        exit;
    }

    // Track article view for logged-in users
    if ($userId) {
        try {
            $tracker = new AnalyticsTracker();
            $tracker->trackView($userId, $articleId);
        } catch (Exception $e) {
            // Silent fail - don't break the page if tracking fails
            error_log("Failed to track article view: " . $e->getMessage());
        }
    }

} catch (Exception $e) {
    //error_log("Error in view_article.php: " . $e->getMessage());
    $_SESSION['flash'] = "Eroare la încărcarea articolului";
    header("Location: index.php");
    exit;
}

?>
<script>
    window.CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?>";
    const articleId = <?= $articleId ?>;
    const requestedVersion = <?= $version ?>;
    const isOnlineParam = <?= $isOnline ?>;

    // Translation state
    let translationCache = {};  // Cache multiple translations by language
    let originalTitle = '';
    let originalContent = '';
    let translationServiceReady = false;

    // Check translation service status on page load
    async function checkTranslationServiceStatus() {
        const statusEl = document.getElementById('translation-status');
        const langSelect = document.getElementById('translate-lang-select');
        
        if (!statusEl || !langSelect) return;
        
        try {
            statusEl.textContent = '⏳ Checking...';
            statusEl.style.color = '#888';
            statusEl.style.display = 'inline';
            
            const response = await fetch('<?= APP_URL ?>public/api/bkd_translate_article.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ article_id: articleId, target_lang: 'en' }),
                signal: AbortSignal.timeout(5000) // 5 second timeout
            });
            
            let data = null;
            try {
                const text = await response.text();
                if (text) {
                    data = JSON.parse(text);
                }
            } catch (parseError) {
                console.error('Failed to parse response:', parseError);
            }
            
            // Check if service is starting up (503 error)
            if (response.status === 503) {
                statusEl.textContent = '⏳ Translation service starting up... Please wait';
                statusEl.style.color = '#f39c12';
                langSelect.disabled = true;
                
                // Retry every 10 seconds
                setTimeout(checkTranslationServiceStatus, 10000);
            } else if (response.status === 200 || response.status === 400) {
                // Service is ready (200 = success, 400 = bad request means service responded)
                statusEl.textContent = '✓ Ready';
                statusEl.style.color = '#27ae60';
                langSelect.disabled = false;
                translationServiceReady = true;
                
                // Hide status after 3 seconds
                setTimeout(() => { statusEl.textContent = ''; }, 3000);
            } else {
                // Other errors - assume service is available
                statusEl.textContent = '';
                langSelect.disabled = false;
                translationServiceReady = true;
            }
        } catch (error) {
            console.error('Error checking translation service:', error);
            // Don't block the UI - assume service might work
            statusEl.textContent = '';
            langSelect.disabled = false;
            translationServiceReady = true;
        }
    }

    // Call after page content has loaded (delayed to not block content)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Delay translation check by 1 second to allow content to load first
            setTimeout(checkTranslationServiceStatus, 1000);
        });
    } else {
        // Page already loaded, still delay slightly
        setTimeout(checkTranslationServiceStatus, 500);
    }

    // Handle translation dropdown change
    async function handleTranslationChange() {
        const langSelect = document.getElementById('translate-lang-select');
        const selectedValue = langSelect.value;
        const titleEl = document.getElementById('article-title');
        const contentEl = document.getElementById('article-content');
        const statusEl = document.getElementById('translation-status');
        
        // Save original content on first translation
        if (!originalTitle && selectedValue && selectedValue !== 'original') {
            originalTitle = titleEl.innerHTML;
            originalContent = contentEl.innerHTML;
        }
        
        // Handle restore original
        if (selectedValue === 'original' || selectedValue === '') {
            if (originalTitle) {
                titleEl.innerHTML = originalTitle;
                contentEl.innerHTML = originalContent;
            }
            langSelect.selectedIndex = 0; // Reset to "Translate..."
            statusEl.textContent = '';
            return;
        }
        
        // Don't translate separator option
        if (selectedValue === '──────────') {
            langSelect.selectedIndex = 0;
            return;
        }
        
        // Translate to selected language
        langSelect.disabled = true;
        statusEl.textContent = '⏳ Translating...';
        statusEl.style.color = '#3498db';
        
        try {
            // Check if we already have this translation cached
            if (!translationCache[selectedValue]) {
                const response = await fetch('<?= APP_URL ?>public/api/bkd_translate_article.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',  // Include cookies in the request
                    body: JSON.stringify({
                        article_id: articleId,
                        target_lang: selectedValue
                    })
                });
                
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned invalid response. Check console for details.');
                }
                
                const data = await response.json();
                
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Translation failed');
                }
                
                translationCache[selectedValue] = data;
            }
            
            const translation = translationCache[selectedValue];
            
            if (translation.success) {
                // Update with translated content
                titleEl.textContent = translation.title;
                contentEl.innerHTML = translation.content;
                statusEl.textContent = '✓ Translated';
                statusEl.style.color = '#27ae60';
                
                // Hide status after 2 seconds
                setTimeout(() => { statusEl.textContent = ''; }, 2000);
            } else {
                throw new Error(translation.message || 'Translation failed');
            }
        } catch (error) {
            console.error('Translation error:', error);
            statusEl.textContent = '✗ ' + error.message;
            statusEl.style.color = '#e74c3c';
            
            // Show error in alert as well
            alert('Translation failed: ' + error.message);
            
            // Reset to original
            if (originalTitle) {
                titleEl.innerHTML = originalTitle;
                contentEl.innerHTML = originalContent;
            }
            langSelect.selectedIndex = 0;
        } finally {
            langSelect.disabled = false;
        }
    }

    // Export current page content to PDF (including translations)
    async function exportToPDF() {
        try {
            // Get current page content (may be translated)
            const title = document.getElementById('article-title').innerText;
            const content = document.getElementById('article-content').innerHTML;
            const metaEl = document.getElementById('article-meta');
            
            // Extract metadata from page
            let author = '';
            let category = '';
            let created_at = '';
            let updated_at = '';
            
            if (metaEl && metaEl.innerText) {
                const metaText = metaEl.innerText;
                // Parse meta info (format: "Autor: Name | Categorie: Cat | Data: Date")
                const authorMatch = metaText.match(/Autor[:\s]+([^|]+)/i);
                const categoryMatch = metaText.match(/Categorie[:\s]+([^|]+)/i);
                const dateMatch = metaText.match(/Data[:\s]+([^|]+)/i);
                
                author = authorMatch ? authorMatch[1].trim() : '';
                category = categoryMatch ? categoryMatch[1].trim() : '';
                created_at = dateMatch ? dateMatch[1].trim() : '';
            }
            
            // Determine language (check if translated)
            const langSelect = document.getElementById('translate-lang-select');
            const currentLang = langSelect ? langSelect.value : '';
            const language = currentLang && currentLang !== 'original' && currentLang !== '' 
                ? currentLang.toUpperCase() 
                : '';
            
            // Prepare data to send
            const data = {
                article_id: articleId,
                title: title,
                content: content,
                author: author,
                category: category,
                created_at: created_at,
                updated_at: updated_at,
                language: language
            };
            
            // Send POST request to generate PDF
            const response = await fetch('<?= APP_URL ?>public/api/bkd_export_article.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                throw new Error('Failed to generate PDF');
            }
            
            // Get PDF blob and download it
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'article_' + articleId + (language ? '_' + language : '') + '.pdf';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
        } catch (error) {
            console.error('PDF export error:', error);
            alert('Failed to export PDF: ' + error.message);
        }
    }
</script>

<style>
.icon-pdf-export {
    transition: transform 0.2s cubic-bezier(.4,2,.3,1), box-shadow 0.2s;
}
.icon-pdf-export:hover {
    transform: translateY(-4px) scale(1.08);
    box-shadow: 0 4px 12px rgba(0,0,0,0.18);
}
</style>
    

       <div class="article-view" style="max-width:80%; margin:20px auto;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="flex: 1; text-align: left;">
                    <h2 class="article-title" id="article-title" style="text-align: left;"></h2>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <!-- Translation status message (on the left) -->
                    <span id="translation-status" style="font-size: 0.85rem; color: #888; min-width: 120px; text-align: right;"></span>
                    <!-- Translation dropdown -->
                    <select id="translate-lang-select" 
                            style="padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc; background: white; cursor: pointer; font-size: 0.9rem;"
                            onchange="handleTranslationChange()">
                        <option value="">🌍 Translate...</option>
                        <option value="original">🔄 Original</option>
                        <option disabled>──────────</option>
                        <option value="en">🇬🇧 English</option>
                        <option value="ro">🇷🇴 Romanian</option>
                        <option value="es">🇪🇸 Spanish</option>
                        <option value="fr">🇫🇷 French</option>
                        <option value="de">🇩🇪 German</option>
                    </select>
                    <div id="bookmark-img"></div>
                </div>              
            </div>
            <div class="article-header-flex" style="display: flex; justify-content: space-between; align-items: center;">
                <div class="article-meta" id="article-meta"></div>
                <div id="article-header-flex"></div>
            </div>
             <div class="article-header-flex" style="display: flex; justify-content: space-between; align-items: center;">
                <div class="article-tags" style="text-align: left;" id="article-tags"></div>
                <div class="article-status" style="text-align: right;" id="article-status"></div>

            </div>
            <br>
            <div style="height: 0.5px; background-color: #ccc; width: 100%;"></div>
            <br>
            <div id="article-content"></div>
            <br>
            <div style="height: 0.5px; background-color: #ccc; width: 100%;"></div>
            <br>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div class="vote-buttons" id="article-votes"></div>
                <div class="article-useful" id="article-useful" cstyle="margin-top:8px;"></div>
            </div>
        </div>

        <!-- Related Articles Section -->
        <div id="related-articles-section" class="article-view" style="max-width:80%; margin:20px auto;">
            <h4 style="color: #048eb1; margin-bottom: 20px;">
                <i class="fas fa-link" style="margin-right: 8px;"></i>
                Related Articles
            </h4>
            <div id="related-articles-container" class="row related-articles-row">
                <!-- Articolele relacionate vor fi încărcate aici -->
            </div>
        </div>

        <div id="comments" class="article-view" style="max-width:80%; margin:20px auto;">
            <h4 id="comments-title"><?= lang ('lang_view_article_comments');?></h4>
            <div id="comments-list"></div>
        </div>


        <div class="article-view" style="max-width:80%;margin:20px auto;">
            <form id="comment-form" class="mb-4">
                <input type="hidden" name="article_id" id="article_id">
                <textarea name="content" id="comment-content" class="form-control" required rows="3" placeholder="<?= lang('lang_view_article_write_a_comment'); ?>"></textarea>
                <button type="submit" class="btn btn-primary mt-2"><?= lang ('lang_view_article_comment_send');?></button>
                <div id="comment-feedback" class="mt-2 text-success d-none"><?= lang ('lang_view_article_comment_sent');?></div>
            </form>
        </div>
   





<script>
// Funcție pentru escape HTML
function escapeHtml(txt) {
    const div = document.createElement('div');
    div.textContent = txt;
    return div.innerHTML;
}

function loadArticle() {
     // Construiește URL-ul cu versiunea și parametrii
    let url = 'api/bkd_view_article.php?id=' + articleId;
    
    // Pentru articole care există doar în article_versions, forțează version=1 dacă nu este specificat
    if (requestedVersion && requestedVersion > 0) {
        url += '&version=' + requestedVersion;
    } else if (isOnlineParam === 0) {
        // Dacă nu este online și nu avem versiune specificată, încearcă cu versiunea 1
        url += '&version=1';
    }

    if (isOnlineParam !== null) {
        url += '&isonline=' + isOnlineParam;
    }
    
    //console.log('Loading article with URL:', url); // Debug temporar

    fetch(url)
        .then(res => res.json())
        .then(data => {

            //console.log('Article data received:', data); // Debug temporar

            if (data.error) {
                document.getElementById('article-title').textContent = data.error;
                return;
            }
            
            // Setează article_id pentru form-ul de comentarii
            document.getElementById('article_id').value = data.id || articleId;

            // Icon categorie
            let iconHtml = '';
            if (data.icon) {
                if (data.icon.startsWith('http') || data.icon.endsWith('.png') || data.icon.endsWith('.svg')) {
                    iconHtml = `<img src="<?=APP_URL?>assets/icons/categories/${escapeHtml(data.icon)}" alt="icon" class="me-1" style="width: 45px; vertical-align: middle;">`;
                } else {
                    iconHtml = `<span class="me-1">${escapeHtml(data.icon)}</span>`;
                }
            }
            
            // Afișează titlul
            let titleHtml = iconHtml + escapeHtml(data.title);
            document.getElementById('article-title').innerHTML = titleHtml;
            
            // Bookmark + export to PDF
            let bookmarkHtml = '';
            bookmarkHtml += `<a href="javascript:void(0)" onclick="exportToPDF()" title="Export to PDF" style="margin-left:20px; display:inline-block;">
                        <img src="<?=APP_URL?>assets/icons/icon-pdf.png" alt="Export PDF" style="height:35px; vertical-align:middle; cursor:pointer;">
                    </a>&nbsp;&nbsp;&nbsp;&nbsp;`;

            if (data.is_bookmarked) {
                bookmarkHtml += `<img id="bookmark-img" src="<?=APP_URL?>assets/icons/icon-bookmark-full.svg" alt="Bookmark" class="bookmark-icon" style="cursor:pointer; width:30px; height:auto;" onclick="toggleBookmark(${data.id}, this)" title="<?=lang('lang_favorites_remove')?>" >`;
            } else {
                bookmarkHtml += `<img id="bookmark-img" src="<?=APP_URL?>assets/icons/icon-bookmark-empty.svg" alt="Bookmark" class="bookmark-icon" style="cursor:pointer; width:30px; height:auto;" onclick="toggleBookmark(${data.id}, this)" title="<?=lang('lang_favorites_add')?>" >`;
            }

            document.getElementById('bookmark-img').innerHTML = bookmarkHtml;

            // Meta informații
            let metaHtml = `<em><?= lang ('lang_view_article_author');?>: ${escapeHtml(data.username)} | <?= lang('lang_view_article_category');?>: ${escapeHtml(data.category)} | <?= lang('lang_view_article_published');?>: ${escapeHtml(data.created_at)} | <?= lang('lang_view_article_updated');?>: ${escapeHtml(data.updated_at)}</em>`;
            
            // Adaugă change_note dacă există și este o versiune specifică
            if (data.is_version && data.change_note) {
                metaHtml += `<br><small style="color: #7f8c8d; font-style: italic;"><strong>Notă modificare:</strong> ${escapeHtml(data.change_note)}</small>`;
            }
            
            document.getElementById('article-meta').innerHTML = metaHtml;

            // Status și versiune
            let statusHtml = '<strong>Info: </strong>';
            if (data.is_version && data.version_number) {
                statusHtml += ` <small style="background: #e4e3e3ff; color: #05646bff; padding: 5px; border-radius: 4px; font-size: 0.8em; font-weight: normal;"><?=lang('lang_art_version')?>: ${data.version_number}</small>`;
            }
            
            statusHtml += ` <small style="background: ${getStatusColor(data.status)};  color: white; padding: 5px; border-radius: 4px; font-size: 0.8em; font-weight: normal;"> ${data.status}</small>`;

            if (data.is_online == 1) {
                statusHtml += ` <small style="background: #c70606ff; color: white; padding: 5px; border-radius: 4px; font-size: 0.8em; font-weight: normal;">ONLINE</small>`;
            }

            document.getElementById('article-status').innerHTML = statusHtml;

            // Tags
            let tagsHtml = '';
            if (data.tags && data.tags.length > 0) {
                tagsHtml = data.tags.map(tag => 
                    `<a href="articles_by_tag.php?tag=${encodeURIComponent(tag)}" class="tag-badge">${escapeHtml(tag)}</a>`
                ).join('');
            }
            document.getElementById('article-tags').innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div><strong>Tags:</strong> ${tagsHtml.length > 0 ? tagsHtml : '(no tags)'}</div>
                </div>
            `;

            // Rating stars
            const articleStars = `
                <div class="article-rating" data-article-id="${data.id}">
                    <span class="star" data-value="1">&#9733;</span>
                    <span class="star" data-value="2">&#9733;</span>
                    <span class="star" data-value="3">&#9733;</span>
                    <span class="star" data-value="4">&#9733;</span>
                    <span class="star" data-value="5">&#9733;</span>
                    <span id="rating-average" style="margin-left:10px; color:#048eb1; font-size:0.9rem;"></span>
                </div>
            `;
            document.getElementById('article-header-flex').innerHTML = articleStars;
            initRatingStars(data.id);

            // Content
            document.getElementById('article-content').innerHTML = data.content;
            
            // Useful feedback
            const usefulHtml = `
                <span style="margin-right:10px;"><?= lang('lang_view_article_useful');?></span>
                <button id="btn-useful-yes" class="btn-useful-grey" type="button"><?= lang('lang_view_article_useful_yes');?></button>
                <button id="btn-useful-no" class="btn-useful-grey" type="button"><?= lang('lang_view_article_useful_no');?></button>
                <span id="useful-feedback" style="margin-left:10px; color:#048eb1; font-size:0.9rem;"></span>           
            `;
            document.getElementById('article-useful').innerHTML = usefulHtml;
            initUsefulFeedback(data.id);

            // Vote buttons
            document.getElementById('article-votes').innerHTML = `
                <a href="#" onclick="voteArticle(${data.id}, 'like', this); return false;">
                    <img src="<?=APP_URL?>assets/images/icon-like.png" class="vote-icon" width="20" height="auto" title="Like">
                </a>
                <span class="like-count">${data.likes || 0}</span>
                <a href="#" onclick="voteArticle(${data.id}, 'dislike', this); return false;">
                    <img src="<?=APP_URL?>assets/images/icon-dlike.png" class="vote-icon" width="20" height="auto" title="Dislike">
                </a>
                <span class="dislike-count">${data.dislikes || 0}</span>
            `;

            // Comments
            let commentsHtml = '';
            if (data.comments && data.comments.length) {
                data.comments.forEach(function(c) {
                    commentsHtml += `
                    <div id="comment-${c.id}" class="comment border rounded p-2 mb-2">
                        <strong>${escapeHtml(c.username)}</strong>
                        <small class="text-muted">${escapeHtml(c.created_at)}</small>
                        <p class="mb-0" style="padding: 20px;">${escapeHtml(c.content).replace(/\n/g, '<br>')}</p>
                        <div class="comment-footer">
                            <div>
                                <a href="#" onclick="voteComment(${c.id}, 'like'); return false;">
                                    <img width="20" height="auto" src="<?= APP_URL ?>assets/images/icon-like.png"
                                        id="like-icon-${c.id}"
                                        class="vote-icon"
                                        title="Like">
                                    <span class="like-count" id="like-count-${c.id}">${c.likes || 0}</span>
                                </a>
                                <a href="#" onclick="voteComment(${c.id}, 'dislike'); return false;">
                                    <img width="20" height="auto" src="<?= APP_URL ?>assets/images/icon-dlike.png"
                                        id="dislike-icon-${c.id}"
                                        class="vote-icon"
                                        title="Dislike">
                                    <span class="dislike-count" id="dislike-count-${c.id}">${c.dislikes || 0}</span>
                                </a>
                            </div>
                        </div>
                    </div>`;
                });
            } else {
                commentsHtml = '<p><?= lang('lang_view_article_no_comments');?></p>';
            }
            document.getElementById('comments-list').innerHTML = commentsHtml;

            // Scroll la comentarii dacă există hash
            if (window.location.hash === '#comments') {
                const commentsDiv = document.getElementById('comments');
                if (commentsDiv) {
                    commentsDiv.scrollIntoView({behavior: "smooth"});
                }
            }

            // Încarcă articolele relationate
            loadRelatedArticles(data.id);
        })
        .catch(error => {
            console.error('Error loading article:', error);
            document.getElementById('article-title').textContent = 'Eroare la încărcarea articolului';
        });
}

function getStatusColor(status) {
    switch(status) {
        case 'approved': return '#27ae60';
        case 'pending': return '#e67e22';
        case 'draft': return '#95a5a6';
        case 'disabled': return '#e74c3c';
        default: return '#bdc3c7';
    }
}

function voteArticle2(articleId, vote, el) {
    fetch('api/bkd_vote.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `type=article&id=${encodeURIComponent(articleId)}&vote=${encodeURIComponent(vote)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            el.parentNode.querySelector('.like-count').textContent = data.likes;
            el.parentNode.querySelector('.dislike-count').textContent = data.dislikes;
            
            // Track via client-side analytics too
            if (typeof UserAnalytics !== 'undefined') {
                UserAnalytics.trackVote(articleId, vote === 'like' ? 1 : -1, 'article');
            }
        } else {
            alert(data.error || 'Eroare la vot!');
        }
    });
}

function voteComment2(commentId, vote) {
    fetch('api/bkd_vote.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `type=comment&id=${encodeURIComponent(commentId)}&vote=${encodeURIComponent(vote)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('like-count-' + commentId).textContent = data.likes;
            document.getElementById('dislike-count-' + commentId).textContent = data.dislikes;
            
            // Track via client-side analytics too - using article ID from hidden input
            if (typeof UserAnalytics !== 'undefined') {
                const articleId = document.getElementById('article_id').value;
                UserAnalytics.trackVote(articleId, vote === 'like' ? 1 : -1, 'comment', commentId);
            }
        } else {
            alert(data.error || 'Eroare la vot!');
        }
    });
}

document.getElementById('comment-form').onsubmit = function(e) {
    e.preventDefault();
    const content = document.getElementById('comment-content').value;
    const articleId = document.getElementById('article_id').value;
    fetch('api/bkd_add_comment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `article_id=${encodeURIComponent(articleId)}&content=${encodeURIComponent(content)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('comment-feedback').classList.remove('d-none');
            document.getElementById('comment-content').value = '';
            
            // Track via client-side analytics too
            if (typeof UserAnalytics !== 'undefined') {
                UserAnalytics.trackComment(articleId);
            }
            
            setTimeout(() => {
                loadArticle(); // Reîncarcă articolul pentru a afișa noul comentariu
            }, 1000);
        } else {
            alert(data.error || 'Eroare la trimitere!');
        }
    });
    return false;
};

function initRatingStars(articleId) {
    const stars = document.querySelectorAll('.article-rating .star');
    const ratingAvg = document.getElementById('rating-average');
    let userRating = 0;

    function highlightStars(val) {
        stars.forEach(star => {
            star.classList.toggle('selected', parseInt(star.dataset.value) <= val);
        });
    }

    stars.forEach(star => {
        star.addEventListener('mouseenter', function() {
            highlightStars(this.dataset.value);
            stars.forEach(s => s.classList.toggle('hovered', parseInt(s.dataset.value) <= this.dataset.value));
        });
        star.addEventListener('mouseleave', function() {
            highlightStars(userRating);
            stars.forEach(s => s.classList.remove('hovered'));
        });
        star.addEventListener('click', function() {
            userRating = this.dataset.value;
            highlightStars(userRating);
            sendRating(userRating, articleId, ratingAvg);
        });
    });

    getAverageRating(articleId, ratingAvg, stars, function(val) { userRating = val; });
}

function sendRating(val, articleId, ratingAvg) {
    fetch('api/bkd_article_rating.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `article_id=${encodeURIComponent(articleId)}&stars=${encodeURIComponent(val)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            getAverageRating(articleId, ratingAvg, document.querySelectorAll('.article-rating .star'));
            
            // Track via client-side analytics too
            if (typeof UserAnalytics !== 'undefined') {
                UserAnalytics.trackRating(articleId, val);
            }
        } else {
            alert(data.error || "Eroare la rating!");
        }
    });
}

function getAverageRating(articleId, ratingAvg, stars, setUserRatingCb) {
    fetch('api/bkd_article_rating.php?article_id=' + encodeURIComponent(articleId))
        .then(res => res.json())
        .then(data => {
            if (data.average) {
                if (ratingAvg) ratingAvg.textContent = `(${data.average.toFixed(2)} / 5, ${data.count} <?= lang('lang_view_article_rating_votes');?>)`;
                if (stars) {
                    stars.forEach(star => {
                        star.classList.toggle('selected', parseInt(star.dataset.value) <= (data.user_rating || 0));
                    });
                }
                if (setUserRatingCb) setUserRatingCb(data.user_rating || 0);
            } else {
                if (ratingAvg) ratingAvg.textContent = "<?= lang('lang_view_article_no_ratings')?>";
            }
        });
}

function initUsefulFeedback(articleId) {
    const btnYes = document.getElementById('btn-useful-yes');
    const btnNo = document.getElementById('btn-useful-no');
    const feedback = document.getElementById('useful-feedback');

    if (!btnYes || !btnNo) return;

    btnYes.onclick = function() { sendUsefulFeedback(articleId, 1, feedback); };
    btnNo.onclick = function() { sendUsefulFeedback(articleId, 0, feedback); };

    getUsefulStats(articleId, feedback);
}

function sendUsefulFeedback(articleId, wasHelpful, feedback) {
    fetch('api/bkd_article_rating.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `article_id=${encodeURIComponent(articleId)}&was_helpful=${encodeURIComponent(wasHelpful)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            feedback.textContent = "<?= lang('lang_view_article_rating_thanks');?>";
            getUsefulStats(articleId, feedback);
            
            // Track via client-side analytics too
            if (typeof UserAnalytics !== 'undefined') {
                UserAnalytics.trackUsefulnessRating(articleId, wasHelpful === 1);
            }
        } else {
            feedback.textContent = data.error || "Eroare la feedback!";
            feedback.style.color = "red";
        }
    });
}

function getUsefulStats(articleId, feedback) {
    fetch('api/bkd_article_rating.php?article_id=' + encodeURIComponent(articleId) + '&useful_stats=1')
        .then(res => res.json())
        .then(data => {
            if (data.useful_percent !== undefined) {
                feedback.textContent = `<?= lang('lang_view_article_util');?>: ${data.useful_percent}% (${data.useful_yes} <?= lang('lang_view_article_from');?> ${data.useful_total} <?= lang('lang_view_article_rating_votes');?>)`;
            }
        });
}

function toggleBookmark(articleId, img) {
    // Disable the image during the request to prevent multiple clicks
    img.style.opacity = "0.5";
    img.style.pointerEvents = "none";
    
    console.log('Toggling bookmark for article ID:', articleId);
    
    // Use the full bookmark toggle API with CSRF protection and analytics tracking
    fetch('api/bkd_toggle_bookmark.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `article_id=${encodeURIComponent(articleId)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    })
    .then(res => {
        // First check if we get a valid JSON response even if status is not 200
        const contentType = res.headers.get("content-type");
        if (contentType && contentType.includes("application/json")) {
            return res.json().then(data => {
                if (!res.ok) {
                    // We have JSON data with error details
                    return Promise.reject(data.error || `Server error: ${res.status}`);
                }
                return data;
            });
        } else if (!res.ok) {
            // Not JSON and not OK
            throw new Error(`HTTP error! Status: ${res.status}`);
        }
        // Not JSON but OK (shouldn't happen)
        return res.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error("Invalid response format");
            }
        });
    })
    .then(data => {
        // Re-enable the image
        img.style.opacity = "1";
        img.style.pointerEvents = "auto";
        
        if (data.success) {
            // Update UI
            if (data.bookmarked) {
                img.src = '<?=APP_URL?>assets/icons/icon-bookmark-full.svg';
                img.title = "<?=lang('lang_favorites_remove')?>";
            } else {
                img.src = '<?=APP_URL?>assets/icons/icon-bookmark-empty.svg';
                img.title = "<?=lang('lang_favorites_add')?>";
            }
            
            // Track via client-side analytics too
            if (typeof UserAnalytics !== 'undefined') {
                UserAnalytics.trackBookmark(articleId, data.bookmarked);
            }
        } else if (data.error) {
            console.error("Error toggling bookmark:", data.error);
            alert(data.error);
        }
    })
    .catch(error => {
        // Re-enable the image
        img.style.opacity = "1";
        img.style.pointerEvents = "auto";
        
        console.error("Error toggling bookmark:", error);
        alert("An error occurred while toggling the bookmark. Please try again.");
    });
}

document.addEventListener('DOMContentLoaded', loadArticle);
</script>

<?php
// JavaScript pentru tracking citirea articolului
if (isset($_SESSION['user']['id'])) {
    echo '<script>
    let readStartTime = Date.now();
    let hasAwarded = false;
    
    setTimeout(function() {
        if (!hasAwarded) {
            fetch("api/bkd_award_reading_points.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: "article_id=' . $articleId . '&csrf_token=' . $_SESSION['csrf_token'] . '"
            });
            hasAwarded = true;
        }
    }, 30000);
    
    window.addEventListener("scroll", function() {
        if (!hasAwarded && (window.innerHeight + window.scrollY) >= document.body.offsetHeight - 100) {
            fetch("api/bkd_award_reading_points.php", {
                method: "POST", 
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: "article_id=' . $articleId . '&csrf_token=' . $_SESSION['csrf_token'] . '"
            });
            hasAwarded = true;
        }
    });
    </script>';
}
?>

<script>
// JavaScript pentru articolele relacionate
async function loadRelatedArticles(articleId) {
    try {
        const response = await fetch(`<?= APP_URL ?>public/api/bkd_related_articles.php?id=${articleId}&limit=6`);
        const data = await response.json();
        
        if (data.related_articles && data.related_articles.length > 0) {
            displayRelatedArticles(data.related_articles);
        } else {
            console.log('Nu s-au găsit articole relacionate');
        }
    } catch (error) {
        console.error('Eroare la încărcarea articolelor relacionate:', error);
    }
}

function displayRelatedArticles(articles) {
    const container = document.getElementById('related-articles-container');
    if (!container) return;
    
    if (!articles || articles.length === 0) {
        document.getElementById('related-articles-section').style.display = 'none';
        return;
    }

    let articlesHtml = '';
    articles.forEach(article => {
        let iconHtml = '';
        if (article.category_icon) {
            iconHtml = `<img src="<?= APP_URL ?>assets/icons/categories/${article.category_icon}" alt="icon" style="width: 20px; margin-right: 5px;">`;
        }

        let tagsHtml = '';
        if (article.tags && article.tags.length > 0) {
            const limitedTags = article.tags.slice(0, 3);
            tagsHtml = limitedTags.map(tag => 
                `<span class="badge bg-secondary me-1" style="font-size: 0.7em;">${tag}</span>`
            ).join('');
            if (article.tags.length > 3) {
                tagsHtml += `<span class="badge bg-light text-dark" style="font-size: 0.7em;">+${article.tags.length - 3}</span>`;
            }
        }

        articlesHtml += `
            <div class="col-md-6 col-lg-4 related-article-col">
                <div class="card related-article-card">
                    <div class="card-body related-article-card-body">
                        <div class="d-flex align-items-center mb-1">
                            ${iconHtml}
                            <small class="text-muted">${article.category_name}</small>
                        </div>
                        
                        <h6 class="card-title related-article-title">
                            <a href="view_article.php?id=${article.id}&version=1&isonline=1" class="text-decoration-none related-article-link">
                                ${article.title}
                            </a>
                        </h6>
                        
                        <div class="related-article-content">
                            <div class="related-article-tags">
                                ${tagsHtml}
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center related-article-footer">
                                <small class="text-muted">
                                    <i class="fas fa-user"></i> ${article.username}
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-eye"></i> ${article.views}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = articlesHtml;
}
</script>

<!-- Reading Time Tracker -->
<script src="<?= APP_URL ?>assets/js/reading-tracker.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize reading time tracker only for online articles
    <?php if ($isOnline == 1 && $article): ?>
    <?php
    // Detect view source for reading tracker (same logic as for views)
    $readingViewSource = 'public';
    if (isset($_GET['admin_source'])) {
        $readingViewSource = $_GET['admin_source'];
    } else {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strpos($referer, '/admin/') !== false || strpos($referer, '/public/admin/') !== false) {
            $readingViewSource = 'admin_preview';
        }
    }
    ?>
    const tracker = new ReadingTimeTracker(<?= $articleId ?>, '<?= APP_URL ?>', '<?= $readingViewSource ?>');
    console.log('Reading tracker initialized for article <?= $articleId ?> with source <?= $readingViewSource ?>');
    <?php endif; ?>
});
</script>

<!-- Load Analytics Tracking Script -->
<script src="<?= APP_URL ?>assets/js/user-analytics.js"></script>

<script>
// Set the APP_URL for the UserAnalytics module
var APP_URL = '<?= APP_URL ?>';

document.addEventListener('DOMContentLoaded', function() {
    <?php if ($isOnline == 1 && $article && $readingViewSource == 'public' && isset($_SESSION['user']['id'])): ?>
    // Track article view in analytics system
    if (typeof UserAnalytics !== 'undefined') {
        UserAnalytics.trackArticleView(<?= $articleId ?>)
            .then(response => {
                if (response.success) {
                    console.log('Article view tracked successfully in analytics');
                }
            });
    } else {
        console.warn('UserAnalytics module not loaded');
    }
    <?php endif; ?>
});
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>