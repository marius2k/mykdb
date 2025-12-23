<?php

//loadConfig();

require_once '../config/bootstrap.php';

//echo "App ROOT" . APP_ROOT;

//require_once APP_ROOT.'config/config.php';
//require_once APP_ROOT.'config/db.php';

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
if (!isset($_SESSION['user'])) {

    initGuestSession();
    
}


?>

<?php

$filter = '';
$params= [];


// Optional category filter
if (isset($_GET['fcategory']) && $_GET['fcategory'] !== '') {
    if ($_GET['fcategory'] <> '0'){
        $filter = "AND category_id = ?";
        $params[] = $_GET['fcategory'];
        $cid = $_GET['fcategory'];
        //echo $_GET['category'];
        //echo $filter;
    }
}else {
    $filter = '';
    $params = [];
    $cid= null;

}



$db = new Database();

// Fetch categories for dropdown
$stmt1 = $db->query("SELECT * FROM categories WHERE is_active = 1");
//$stmt1 -> execute($params);
$categories = $stmt1->fetchAll();

// Fetch approved articles from the active categories



$filterCatId = $_GET['fcategory'] ?? '0';

//echo "Filter Category ID: " . $filterCatId . "<br>";


// Total articles for pagination

$perPage = 4; // articole pe pagină
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;

//$cid= $filterCatId;


//echo "CID: ".$cid . "<br>";


if (isset($filterCatId) && $filterCatId > 0) {

    $totalStmt = $db->query("SELECT COUNT(*) FROM articles WHERE status = 'published' AND publish_at <= NOW() AND category_id = ?", [$filterCatId]);
    $totalRows = $totalStmt->fetchColumn();
    $totalPages = ceil($totalRows / $perPage);



    $sql = "SELECT a.*, u.username, c.name AS category, c.icon, c.id AS catid 
            FROM articles a 
            JOIN users u ON a.user_id = u.id 
            LEFT JOIN categories c ON a.category_id = c.id 
            WHERE a.status = 'published' AND a.category_id = :cid
            AND (a.publish_at IS NULL OR a.publish_at <= NOW())
            AND c.is_active = 1
            ORDER BY a.created_at DESC
            LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':cid', $cid, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        
        $stmt->execute();    

} else {

    $totalStmt = $db->query("SELECT COUNT(*) FROM articles WHERE status = 'published' AND publish_at <= NOW()");
    $totalRows = $totalStmt->fetchColumn();
    $totalPages = ceil($totalRows / $perPage);

    $sql = "SELECT a.*, u.username, c.name AS category, c.icon, c.id AS catid 
            FROM articles a 
            JOIN users u ON a.user_id = u.id 
            LEFT JOIN categories c ON a.category_id = c.id 
            WHERE a.status = 'published'
            AND (a.publish_at IS NULL OR a.publish_at <= NOW())
            AND c.is_active = 1
            ORDER BY a.created_at DESC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    
    $stmt->execute();

}




//echo "SQL: ".$sql;

        

        //$stmt->execute( $params);
        $articles = $stmt->fetchAll();
    
        

        // Preluare tag-uri pentru toate articolele
        $articleIds = array_column($articles, 'id');
        $tagsByArticle = [];
        if ($articleIds) {
            $in = implode(',', array_fill(0, count($articleIds), '?'));
            $tagsRows = $db->fetchAll("
                SELECT at.article_id, t.name
                FROM article_tags at
                JOIN tags t ON at.tag_id = t.id
                WHERE at.article_id IN ($in)
            ", $articleIds);

            foreach ($tagsRows as $row) {
                $tagsByArticle[$row['article_id']][] = $row['name'];
            }
        }
        
        // Adaugă tag-urile la fiecare articol
        foreach ($articles as &$a) {
            $a['tags'] = $tagsByArticle[$a['id']] ?? [];
        }
        unset($a);
        

if (isset($_SESSION['user']['id'])) {

    // user is logged in

    $db = new Database();

    $userId = $_SESSION['user']['id'];
    $settings = new UserSettings($db);
    $currentSettings = $settings->getAll($userId);
    $lang = $currentSettings['language'] ?? 'en';
    $theme = $currentSettings['theme'] ?? 'light';
    $_SESSION['settings'] = $currentSettings;

    if ($lang === 'ro') {
        $tz = 'ro-RO';
    } elseif ($lang === 'en') {
        $tz = 'en-US';
    }

}else {

    // user not logged in
    $lang = 'en';
    $theme = 'light';
    $tz='en-US';
}


?>
<script>window.TZ = "<?= $tz ?>";</script>

<?php 
include APP_ROOT . 'includes/header.php';

// Get quick stats for the breadcrumb area
$totalArticlesStmt = $db->query("SELECT COUNT(*) FROM articles WHERE status = 'published' AND publish_at <= NOW()");
$totalArticlesCount = $totalArticlesStmt->fetchColumn();

$newTodayStmt = $db->query("SELECT COUNT(*) FROM articles WHERE status = 'published' AND DATE(created_at) = CURDATE()");
$newTodayCount = $newTodayStmt->fetchColumn();

// Get username for welcome message
$username = isset($_SESSION['user']['username']) ? htmlspecialchars($_SESSION['user']['username']) : lang('lang_guest');
?>

<script>
// Set APP_URL for JavaScript modules
var APP_URL = '<?= APP_URL ?>';
</script>

<!-- ArticleBox Component Styles -->
<link rel="stylesheet" href="<?= APP_URL ?>assets/css/components/ArticleBox.css">

<!-- React CDN -->
<script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>

<!-- ArticleBox Component -->
<script src="<?= APP_URL ?>assets/js/react-components-dist/ArticleBox.js"></script>

<!-- CardInfoBox Component -->
<link rel="stylesheet" href="<?= APP_URL ?>assets/css/components/CardInfoBox.css">
<script src="<?= APP_URL ?>assets/js/react-components-dist/CardInfoBox.js"></script>

<div class="breadcrumb-filter-section" style="display: flex; justify-content: space-between; align-items: center; width: 100vw; padding: 6px 20px; margin-top: 0; margin-bottom: 0; margin-left: calc(-50vw + 50%);">
    <div style="display: flex; gap: 15px; align-items: center; margin-left: 20px; color: #666; font-size: 14px;">
        <span>👋 <?= lang('lang_welcome') ?>, <strong><?= $username ?></strong></span>
        <span>|</span>
        <span>📚 <?= $totalArticlesCount ?> <?= lang('lang_articles') ?></span>
        <span>|</span>
        <span>🆕 <?= $newTodayCount ?> <?= lang('lang_new_today') ?></span>
    </div>
    <div style="display: flex; gap: 20px; align-items: center; margin-right: 20px;">
        <div id="user-location" style="color: #666; font-size: 14px;"></div>
        <div id="weather-info" style="color: #666; font-size: 14px; display: flex; align-items: center; gap: 5px;"></div>
        <div id="current-datetime" style="color: #666; font-size: 14px;"></div>
    </div>
</div>
<script>
// Get user's locale from PHP
const userLocale = '<?= $tz ?>'; // This is set based on user's language preference

function updateDateTime() {
    const now = new Date();
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit',
        second: '2-digit'
    };
    document.getElementById('current-datetime').textContent = now.toLocaleString(userLocale, options);
}
updateDateTime();
setInterval(updateDateTime, 1000);

// Get user location and weather
async function getUserLocationAndWeather() {
    try {
        // Get user's IP-based location
        const locationResponse = await fetch('https://ipapi.co/json/');
        const locationData = await locationResponse.json();
        
        const city = locationData.city || 'Unknown';
        const country = locationData.country_name || '';
        document.getElementById('user-location').textContent = `📍 ${city}${country ? ', ' + country : ''}`;
        
        // Get weather data using lat/lon
        if (locationData.latitude && locationData.longitude) {
            const weatherResponse = await fetch(
                `https://api.open-meteo.com/v1/forecast?latitude=${locationData.latitude}&longitude=${locationData.longitude}&current_weather=true`
            );
            const weatherData = await weatherResponse.json();
            
            if (weatherData.current_weather) {
                const temp = Math.round(weatherData.current_weather.temperature);
                const weatherCode = weatherData.current_weather.weathercode;
                const weatherIcon = getWeatherIcon(weatherCode);
                
                document.getElementById('weather-info').innerHTML = `${weatherIcon} ${temp}°C`;
            }
        }
    } catch (error) {
        console.error('Error fetching location/weather:', error);
        document.getElementById('user-location').textContent = '📍 Location unavailable';
    }
}

function getWeatherIcon(code) {
    // WMO Weather interpretation codes
    if (code === 0) return '☀️';
    if (code <= 3) return '⛅';
    if (code <= 48) return '🌫️';
    if (code <= 67) return '🌧️';
    if (code <= 77) return '🌨️';
    if (code <= 82) return '🌦️';
    if (code <= 86) return '❄️';
    if (code <= 99) return '⛈️';
    return '🌤️';
}

getUserLocationAndWeather();
</script>
<br>

<div class="main" style="display: flex; flex-direction: row; gap: 20px;">


    <!-- 🔹 Stânga: articole + rezultate -->
    <div class="defaultContent" style="width: 75%;">
        <div id="searchResults"></div>
        <div id="defaultContent">
                
                
                <?php if (count($articles) === 0): ?>
                        <p><?= lang('lang_no_articles') ?></p>
                <?php else: ?>
                    
                    <?php
                    // Prepare articles data for React
                    $articlesData = [];
                    $userId = $_SESSION['user']['id'] ?? null;
                    
                    foreach ($articles as $a) {
                        $content = $a['content'];
                        $textOnly = strip_tags($content);
                        $shortText = shortenText(strip_tags(html_entity_decode($a['content'])), 500);
                        
                        $votes = getArticleLikesDislikes($a['id']);
                        $currentVote = $userId ? getUserVote($a['id'], $userId) : null;
                        $isBookmarked = is_article_bookmarked($a['id'], $userId);
                        
                        $articlesData[] = [
                            'id' => $a['id'],
                            'title' => $a['title'],
                            'icon' => $a['icon'] ?? null,
                            'catid' => $a['catid'],
                            'category' => $a['category'],
                            'username' => $a['username'],
                            'shortText' => $shortText,
                            'tags' => $a['tags'] ?? [],
                            'publishedAt' => formatDate($a['publish_at']),
                            'updatedAt' => formatDate($a['updated_at']),
                            'version' => $a['version'],
                            'viewsCount' => getArticleViewsCount($a['id']),
                            'commentsCount' => getCommentCount($a['id']),
                            'likes' => $votes['like'],
                            'dislikes' => $votes['dislike'],
                            'currentVote' => $currentVote,
                            'isBookmarked' => $isBookmarked
                        ];
                    }
                    ?>
                    
                    <!-- React will render articles here -->
                    <div id="articles-container" class="article-grid"></div>
                    
                    <script>
                    // Articles data from PHP
                    const articlesData = <?= json_encode($articlesData) ?>;
                    const appUrl = '<?= APP_URL ?>';
                    
                    // Language translations
                    const langTranslations = {
                        author: '<?= lang('lang_article_author') ?>',
                        category: '<?= lang('lang_article_category') ?>',
                        published: '<?= lang('lang_article_published') ?>',
                        updated: '<?= lang('lang_article_updated') ?>'
                    };
                    
                    // Render all articles using React
                    const container = document.getElementById('articles-container');
                    const root = ReactDOM.createRoot(container);
                    
                    // Handle bookmark toggle
                    function handleBookmarkToggle(articleId) {
                        fetch('api/bkd_toggle_bookmark.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: 'article_id=' + encodeURIComponent(articleId)
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                // Find and update the article in our data
                                const articleIndex = articlesData.findIndex(a => a.id === articleId);
                                if (articleIndex !== -1) {
                                    articlesData[articleIndex].isBookmarked = data.bookmarked;
                                    // Re-render
                                    renderArticles();
                                }
                            }
                        });
                    }
                    
                    // Handle vote
                    function handleVote(articleId, voteType) {
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
                                // Update article data
                                const articleIndex = articlesData.findIndex(a => a.id === articleId);
                                if (articleIndex !== -1) {
                                    articlesData[articleIndex].likes = data.likes;
                                    articlesData[articleIndex].dislikes = data.dislikes;
                                    articlesData[articleIndex].currentVote = voteType;
                                    // Re-render
                                    renderArticles();
                                }
                            } else {
                                alert(data.message || 'Error voting!');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            alert('AJAX Error!');
                        });
                    }
                    
                    // Render function
                    function renderArticles() {
                        root.render(
                            React.createElement(React.Fragment, null,
                                articlesData.map(article =>
                                    React.createElement(ArticleBox, {
                                        key: article.id,
                                        article: article,
                                        onBookmarkToggle: handleBookmarkToggle,
                                        onVote: handleVote,
                                        isBookmarked: article.isBookmarked,
                                        currentVote: article.currentVote,
                                        appUrl: appUrl,
                                        lang: langTranslations
                                    })
                                )
                            )
                        );
                    }
                    
                    // Initial render
                    renderArticles();
                    </script>

                <div id="pagination-results">
                        <?php 
                        // Pagination logic                              
                            echo renderPagination($currentPage, $totalPages,['category' => $filterCatId]);
                                        
                        ?>
                </div>
            <?php endif; ?>

        </div>
    </div>


    <div class="rightSide" style="width: 25%; display: flex; flex-direction: column; gap: 20px;">
        <!-- Search Box using CardInfoBox -->
        <div id="search-box-container"></div>
        
        <!-- Trending Topics Box using CardInfoBox -->
        <div id="trending-box-container"></div>
        
        <!-- Top View Articles Box using CardInfoBox -->
        <div id="top-view-box-container"></div>
        
        <!-- Top Like Articles Box using CardInfoBox -->
        <div id="top-like-box-container"></div>
    </div>
    
    <!-- Back to Top Button -->
    <button id="backToTop" title="Back to top" style="
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background-color: var(--primary-color, #4e73df);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        font-size: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
    ">
        ↑
    </button>
    
    <!-- Hidden content for React boxes -->
    <div style="display: none;">
        <div id="search-content">
            <div style="display: grid; grid-template-columns: 100px 1fr; gap: 12px; align-items: center; padding-bottom: 20px;">
                <label for="liveSearch" style="text-align: right;"><?= lang('lang_text') ?></label>
                <input type="text" id="liveSearch" placeholder="<?= lang('lang_search_placeholder') ?>" autocomplete="off">

                <label for="searchAuthor" style="text-align: right;"><?= lang('lang_art_author') ?>:</label>
                <input type="text" id="searchAuthor" name="author">

                <label for="searchCategory" style="text-align: right;"><?= lang('lang_art_category') ?>:</label>
                <select id="searchCategory" name="category" class="select2-category" style="width: 100%;">
                    <option value=""></option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['name']) ?>" data-img="<?= APP_URL ?>assets/icons/categories/<?= $cat['icon'] ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div id="trending-content">
            <?php echo getTrendingArticles(5, 7); ?>
        </div>
        
        <div id="top-view-content">
            <?php echo getTopViewedArticles(5); ?>
        </div>
        
        <div id="top-like-content">
            <?php echo getTopLikedArticles(5); ?>
        </div>
    </div>
</div>



<script>


        


// Initializare Select2 pentru selectoare

// Helper function for Select2 formatting with truncation
window.formatCategoryWithIcon = function(option) {
  if (!option.id) {
    return option.text;
  }

  const $option = $(option.element);
  const imgSrc = $option.data('img');
  let text = (option.text || '').trim();
  const fullText = text; // Keep original for tooltip
  
  // Always truncate to 20 characters
  if (text.length > 18) {
    text = text.substring(0, 18) + '…';
  }

  if (imgSrc) {
    return $('<span title="' + fullText + '"><img src="' + imgSrc + '" style="width:20px;height:20px;margin-right:8px;" />' + text + '</span>');
  }
  
  return $('<span title="' + fullText + '">' + text + '</span>');
}



// 🔧 Funcția care generează icon + label


/*
$('#filterCategory').on('change', function () {
  const value = this.value;
  const url = 'index.php?fcategory=' + encodeURIComponent(value);

  fetch(url)
    .then(res => res.json())
    .then(data => {
      console.log(data); // afișează rezultatele
    });
});
*/


const results = document.getElementById('searchResults');
const defaultContent = document.getElementById('defaultContent');

// Initialize React CardInfoBox components
document.addEventListener('DOMContentLoaded', function() {
    // Search Box
    const searchContainer = document.getElementById('search-box-container');
    const searchContent = document.getElementById('search-content');
    const searchRoot = ReactDOM.createRoot(searchContainer);
    
    const searchIcon = React.createElement('img', {
        src: '<?= APP_URL ?>assets/icons/icon-search.svg',
        width: '20',
        height: 'auto'
    });
    
    searchRoot.render(
        React.createElement(CardInfoBox, {
            icon: searchIcon,
            title: '<?= lang('lang_search') ?>',
            body: searchContent.innerHTML,
            defaultOpen: false,
            collapsible: true,
            height: "90px",
            style: { marginBottom: '2px' }
        })
    );
    
    // Initialize Select2 for search category after React renders
    setTimeout(() => {
        const $select = $('#searchCategory');
        
        // Destroy existing Select2 if present
        if ($select.data('select2')) {
            $select.select2('destroy');
        }
        
        $select.select2({
            placeholder: "<?= lang('lang_cat_select') ?>",
            allowClear: true,
            width: '100%',
            escapeMarkup: function(markup) { return markup; },
            templateResult: window.formatCategoryWithIcon,
            templateSelection: window.formatCategoryWithIcon
        });
        
        $select.on('change', triggerAdvancedSearch);
    }, 200);
    
    // Add event listeners for Text and Author fields
    setTimeout(() => {
        const searchTextInput = document.getElementById('liveSearch');
        const searchAuthorInput = document.getElementById('searchAuthor');
        
        if (searchTextInput) {
            searchTextInput.addEventListener('input', debounce(triggerAdvancedSearch, 300));
        }
        
        if (searchAuthorInput) {
            searchAuthorInput.addEventListener('input', debounce(triggerAdvancedSearch, 300));
        }
    }, 250);
    
    // Trending Topics Box
    const trendingContainer = document.getElementById('trending-box-container');
    const trendingContent = document.getElementById('trending-content');
    const trendingRoot = ReactDOM.createRoot(trendingContainer);
    
    const trendingIcon = React.createElement('img', {
        src: '<?= APP_URL ?>assets/icons/icon-performance.svg',
        width: '25',
        height: 'auto'
    });
    
    trendingRoot.render(
        React.createElement(CardInfoBox, {
            icon: trendingIcon,
            title: '<?= lang('lang_article_trending') ?>',
            body: trendingContent.innerHTML,
            defaultOpen: true,
            collapsible: true,
            height: "90px",
            style: { marginBottom: '4px' }
        })
    );
    
    // Top View Articles Box
    const topViewContainer = document.getElementById('top-view-box-container');
    const topViewContent = document.getElementById('top-view-content');
    const topViewRoot = ReactDOM.createRoot(topViewContainer);
    
    const topViewIcon = React.createElement('img', {
        src: '<?= APP_URL ?>assets/icons/icon-top-view.svg',
        width: '25',
        height: 'auto'
    });
    
    topViewRoot.render(
        React.createElement(CardInfoBox, {
            icon: topViewIcon,
            title: '<?= lang('lang_article_top_view') ?>',
            body: topViewContent.innerHTML,
            defaultOpen: true,
            collapsible: true,
            height: "90px",
            style: { marginBottom: '2px' }
        })
    );
    
    // Top Like Articles Box
    const topLikeContainer = document.getElementById('top-like-box-container');
    const topLikeContent = document.getElementById('top-like-content');
    const topLikeRoot = ReactDOM.createRoot(topLikeContainer);
    
    const topLikeIcon = React.createElement('img', {
        src: '<?= APP_URL ?>assets/icons/icon-top-like.svg',
        width: '25',
        height: 'auto'
    });
    
    topLikeRoot.render(
        React.createElement(CardInfoBox, {
            icon: topLikeIcon,
            title: '<?= lang('lang_article_top_like') ?>',
            body: topLikeContent.innerHTML,
            defaultOpen: true,
            collapsible: true,
            height: "90px",
            style: { marginBottom: '2px' }
        })
    );
});

const input = document.getElementById('liveSearch');



// debounce()
function debounce(func, delay = 300) {
  let timeout;
  return (...args) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => func(...args), delay);
  };
}

// Escape & Highlight
function escapeHtml(txt) {
  const div = document.createElement('div');
  div.textContent = txt;
  return div.innerHTML;
}

function highlightHtmlContent(html, keyword) {
  if (!keyword || keyword.length < 2) return html;

  const container = document.createElement('div');
  container.innerHTML = html;

  const walk = (node) => {
    if (node.nodeType === 3) { // text node
      const regex = new RegExp(`(${keyword})`, 'gi');
      const frag = document.createDocumentFragment();
      let lastIndex = 0;
      const text = node.nodeValue;
      let match;

      while ((match = regex.exec(text)) !== null) {
        const before = text.slice(lastIndex, match.index);
        const highlight = document.createElement('span');
        highlight.className = 'highlight';
        highlight.textContent = match[1];
        if (before) frag.appendChild(document.createTextNode(before));
        frag.appendChild(highlight);
        lastIndex = regex.lastIndex;
      }

      const after = text.slice(lastIndex);
      if (after) frag.appendChild(document.createTextNode(after));
      node.replaceWith(frag);
    } else if (node.nodeType === 1 && node.childNodes.length > 0) {
      node.childNodes.forEach(walk);
    }
  };

  container.childNodes.forEach(walk);
  return container.innerHTML;
}




function highlightText(txt, keyword, max = null) {
  if (!keyword) return escapeHtml(txt);
  const safe = escapeHtml(txt);
  const regex = new RegExp(`(${keyword})`, 'gi');
  const highlighted = safe.replace(regex, '<span class="highlight">$1</span>');
  return max ? highlighted.substring(0, max) + '...' : highlighted;
}

// Live search
const searchArticles = async () => {
  const query = input.value.trim();
  if (query.length < 2) {
    results.innerHTML = '';
    defaultContent.style.display = 'block';
    return;
  }

  const res = await fetch('api/bkd_search_articles.php?q=' + encodeURIComponent(query));
  const data = await res.json();

  defaultContent.style.display = 'none';
  
  // Track the search in analytics
  if (typeof SearchAnalytics !== 'undefined') {
    SearchAnalytics.trackSearch(query, data.length);
  }
  
  results.innerHTML = data.map((article, index) => {
    const title = highlightText(article.title, query);
    
    //DEBUG: Log the highlighted title
    //console.log('Highlighted title:', title);

    const content = highlightHtmlContent(article.content, 200);
    const articleUrl = `view_article.php?id=${article.id}&version=${article.version || 1}&isonline=1`;
    
    return `
      <div class="search-result kb-search-result" style="border:1px solid #ccc; padding:10px; margin-bottom:5px;">
        <h4><a href="${articleUrl}" class="search-result-link" data-result-id="${article.id}" data-position="${index + 1}">${title}</a></h4>
        <p class="article-meta">Autor: ${article.username} | Categorie: ${article.category} | ${new Date(article.created_at).toLocaleDateString()}</p>
        <p>${content}</p>
        <a href="${articleUrl}" class="btn btn-sm btn-primary search-result-link" data-result-id="${article.id}" data-position="${index + 1}">View Article</a>
      </div>`;
  }).join('') || '<p>Nu s-au găsit articole.</p>';
};
// OLD search removed - now using triggerAdvancedSearch instead
// input.addEventListener('input', debounce(searchArticles, 300));



function clearSearchForm() {
  document.getElementById('searchAuthor').value = '';
  document.getElementById('searchCategory').value = '';
  document.getElementById('liveSearch').value = '';
}

// Trigger advanced search
function triggerAdvancedSearch() {
  const query = document.getElementById('liveSearch').value.trim();
  const author = document.getElementById('searchAuthor').value.trim();
  const category = document.getElementById('searchCategory').value;

  const hasFilters = query || author || category;

  if (!hasFilters) {
    // Toate goale ⇒ revenim la conținutul inițial
    defaultContent.style.display = 'block';
    searchResults.innerHTML = '';
    return;
  }

  const params = new URLSearchParams();
  if (query) params.append('q', query);
  if (author) params.append('author', author);
  if (category) params.append('category', category);

  fetch('api/bkd_search_articles.php?' + params.toString())
    .then(res => {
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        return res.text(); // Mai întâi citește ca text
    })
    .then(text => {
        console.log('Raw response:', text); // Debug
        try {
            const data = JSON.parse(text);
            defaultContent.style.display = 'none';
            
            if (data.length === 0) {
                // Show empty state with suggestions
                fetch('api/bkd_empty_state.php?' + params.toString())
                    .then(res => res.text())
                    .then(html => {
                        searchResults.innerHTML = html;
                    })
                    .catch(() => {
                        searchResults.innerHTML = '<p class="no-results">No articles found. Try adjusting your search criteria.</p>';
                    });
            } else {
                searchResults.innerHTML = data.map(article => `
                    <div class="article-card">
                      <h4><img src="<?=APP_URL?>/assets/icons/categories/${article.icon}" width="40" height="auto">&nbsp;&nbsp;&nbsp;&nbsp;${highlightQuery(article.title, query)}</h4>
                      <p class="article-meta" style="font-size: 0.9rem;"><em><?=lang('lang_art_author')?>: ${article.username} | <?=lang('lang_art_category')?>: ${article.category} | <?=lang('lang_art_publish_at')?>: ${new Date(article.publish_at).toLocaleDateString(window.TZ)}</em></p>
                      <p>${highlightQuery(article.content, query)}</p>
                      <p><a href="view_article.php?id=${article.id}&version=${article.version}">Read more...</a></p>
                    </div>
                  `).join('');
            }
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Response was:', text);
            searchResults.innerHTML = '<p>Eroare la căutare. Încercați din nou.</p>';
        }
    })
    .catch(err => {
        console.error('[AJAX ERROR]', err);
        searchResults.innerHTML = '<p>Eroare de conectare. Încercați din nou.</p>';
    });
}


function triggerFilterCategory() {
  const categoryId = document.getElementById('filterCategory').value;
  if (!categoryId) return;

  const params = new URLSearchParams({ fcategory: categoryId });

  fetch('api/bkd_search_articles.php?' + params.toString())
    .then(res => res.json())
    .then(data => {
      defaultContent.style.display = 'none';
      searchResults.innerHTML = data.map(article => `
        <div class="article-card">
          <h4><img src="<?=APP_URL?>/assets/icons/categories/${article.icon}" width="40" height="auto">&nbsp;&nbsp;&nbsp;&nbsp;<b>${article.title}</b></h4>
          <p class="article-meta" style="font-size: 0.9rem;"><em><?=lang('lang_art_author')?>: ${article.username} | <?=lang('lang_art_category')?>: ${article.category} | <?=lang('lang_art_publish_at')?>: ${new Date(article.publish_at).toLocaleDateString(window.TZ)}</em></p>
          <p>${article.content}</p>
          <p><a href="view_article.php?id=${article.id}&version=${article.version}">Read more...</a></p>
        </div>
      `).join('') || '<p style="margin-top:10px;">Nu s-au găsit articole în această categorie.</p>';
    })
    .catch(() => {
      alert('Eroare la filtrare. Încearcă din nou.');
    });
}


function highlightQuery(text, query) {
  if (!query) return text;
  const safe = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  return text.replace(new RegExp(safe, 'gi'), match => `<mark>${match}</mark>`);
}


// event-uri pentru căutare
document.getElementById('searchAuthor').addEventListener('input', debounce(triggerAdvancedSearch, 300));
//document.getElementById('searchCategory').addEventListener('change', triggerAdvancedSearch);
document.getElementById('liveSearch').addEventListener('input', debounce(triggerAdvancedSearch, 300));


/*
$(document).ready(function () {
 
  
});
*/
// Event-uri pentru filtrare cu Select2

// Event pentru filtrarea după categorie
$('#filterCategory').on('change', function () {
  const selectedValue = $(this).val();

  if (!selectedValue) {
    // 👉 dacă s-a golit filtrul, afișează conținutul inițial
    defaultContent.style.display = 'block';
    searchResults.innerHTML = '';
    return;
  }

  // 👉 altfel, trimitem filtrarea normală
  triggerFilterCategory();
});

$('#filterCategory').on('select2:clear', function () {
  defaultContent.style.display = 'block';
  searchResults.innerHTML = '';
});

// Back to Top Button functionality
const backToTopButton = document.getElementById('backToTop');

window.addEventListener('scroll', () => {
    if (window.pageYOffset > 300) {
        backToTopButton.style.opacity = '1';
        backToTopButton.style.visibility = 'visible';
    } else {
        backToTopButton.style.opacity = '0';
        backToTopButton.style.visibility = 'hidden';
    }
});

backToTopButton.addEventListener('click', () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

backToTopButton.addEventListener('mouseenter', () => {
    backToTopButton.style.transform = 'scale(1.1)';
});

backToTopButton.addEventListener('mouseleave', () => {
    backToTopButton.style.transform = 'scale(1)';
});

function toggleBookmark(articleId, img) {
    fetch('api/bkd_toggle_bookmark.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'article_id=' + encodeURIComponent(articleId)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (data.bookmarked) {
                img.src = '<?=APP_URL?>assets/icons/icon-bookmark-full.svg';
                img.title = "Elimină din favorite";
            } else {
                img.src = '<?=APP_URL?>assets/icons/icon-bookmark-empty.svg';
                img.title = "Adaugă la favorite";
            }
        }
    });
}

</script>



<?php include APP_ROOT . 'includes/footer.php'; ?>