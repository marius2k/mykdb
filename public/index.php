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

    $totalStmt = $db->query("SELECT COUNT(*) FROM articles WHERE status = 'approved' AND publish_at <= NOW() AND category_id = ?", [$filterCatId]);
    $totalRows = $totalStmt->fetchColumn();
    $totalPages = ceil($totalRows / $perPage);



    $sql = "SELECT a.*, u.username, c.name AS category, c.icon, c.id AS catid 
            FROM articles a 
            JOIN users u ON a.user_id = u.id 
            LEFT JOIN categories c ON a.category_id = c.id 
            WHERE a.status = 'approved' AND a.category_id = :cid
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

    $totalStmt = $db->query("SELECT COUNT(*) FROM articles WHERE status = 'approved' AND publish_at <= NOW()");
    $totalRows = $totalStmt->fetchColumn();
    $totalPages = ceil($totalRows / $perPage);

    $sql = "SELECT a.*, u.username, c.name AS category, c.icon, c.id AS catid 
            FROM articles a 
            JOIN users u ON a.user_id = u.id 
            LEFT JOIN categories c ON a.category_id = c.id 
            WHERE a.status = 'approved'
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
    
        
   

if (isset($_SESSION['user']['id'])) {

    // user is logged in

    $db = new Database();

    $userId = $_SESSION['user']['id'];
    $settings = new UserSettings($db);
    $currentSettings = $settings->getAll($userId);
    $lang = $currentSettings['language'] ?? 'en';
    $theme = $currentSettings['theme'] ?? 'light';
    $_SESSION['settings'] = $currentSettings;

}else {

    // user not logged in
    $lang = 'en';
    $theme = 'light';
}


?>

<?php include APP_ROOT . 'includes/header.php'; ?>




<br>

<div class="main" style="display: flex; flex-direction: row; gap: 20px;">


    <!-- 🔹 Stânga: articole + rezultate -->
    <div class="defaultContent" style="width: 75%;">
        <div id="searchResults"></div>
        <div id="defaultContent">
                
                
                <?php if (count($articles) === 0): ?>
                        <p><?=lang_no_articles?></p>
                <?php else: ?>
                    
                    
                <div class="article-grid">
                            <?php 
                            foreach ($articles as $a): 
                            

                                    $content = $a['content'];

                                    // Extragem prima imagine (dacă există)
                                    preg_match('/<img[^>]+src="([^">]+)"/i', $content, $matches);
                                    $image = $matches[1] ?? null;
                                    
                                    // Extragem textul fără HTML
                                    $textOnly = strip_tags($content);
                                    
                                    // Scurtăm textul
                                    $shortText = shortenText($textOnly, 500);
                                    

                                    $preview = truncateHtmlWithImages($a['content'], 100);
                                    ?>

                                    <div class="article-card">
                                        
                                            <h3 class="article-title">
                                                <?php if (!empty($a['icon'])): ?>
                                                        <?php if (str_starts_with($a['icon'], 'http') || str_ends_with($a['icon'], '.png') || str_ends_with($a['icon'], '.svg')): ?>
                                                            <a href="index.php?fcategory=<?=$a['catid']?>" title="<?=$a['category']?>"><img src="<?=APP_URL?>assets/icons/categories/<?= $a['icon'] ?>" alt="icon" class="me-1" style="width: 45px; vertical-align: middle;"></a>
                                                            <?php else: ?>
                                                            <span class="me-1"><?= htmlspecialchars($a['icon']) ?></span>
                                                        <?php endif; ?>
                                                <?php endif; ?><?= escape($a['title']) ?></h3>
                                            <div class="article-body" id="article<?=$a['id']?>">
                                                <p><?= nl2br(escape($shortText)) ?><a href="view_article.php?id=<?= (int)$a['id'] ?>"><img width="24" height="auto" src="<?=APP_URL?>assets/icons/icon-read-more.svg" title="<?= lang_read_more; ?>"> </a></p>
                                            </div>
                                            
                                            <div class="article-footer">
                                                <div>
                                                    <span class="article-meta"><?=lang_article_author?>:<?=escape($a['username']) ?> | <?=lang_article_category?>:<?=escape($a['category']) ?> | <?=lang_article_published?>:<?=formatDate($a['created_at']) ?>| <?=lang_article_updated?>:<?=formatDate($a['updated_at'])?></span>
                                                </div>
                                                <div class="vote-buttons-container"> 
                                                            <div class="vote-buttons" id="meta-<?=$a['id']?>">
                                                                <a href="<?=APP_URL?>public/view_article.php?id=<?= (int)$a['id'] ?>">
                                                                <img src="<?=APP_URL?>assets/images/icon-view.png" title="<?=lang_article_views?>" class="vote-icon"></a>
                                                                <span class="view-count"><?= escape($a['views']) ?></span>

                                                                <a href="<?=APP_URL?>public/view_article.php?id=<?= (int)$a['id'] ?>#comments">
                                                                <img src="<?=APP_URL?>assets/images/icon-comm.png" title="<?=lang_article_add_comments?>" class="vote-icon"></a>
                                                                <span class="comments-count"><?=getCommentCount($a['id'])?></span>
                                                            </div>
                                                            
                                                            <div class="vote-buttons">
                                                                
                                                                <?php
                                                                    $votes = getArticleLikesDislikes($a['id']);
                                                                    $currentVote = $userId ? getUserVote($a['id'], $userId) : null;
                                                                    //echo "crt vote:".$currentVote;

                                                                ?>

                                                                <!-- LIKE -->
                                                                <a href="#" onclick="voteArticle(<?= $a['id'] ?>, 'like', this); return false; updateArticleMeta(<?=$a['id']?>);">
                                                                    <img src="<?=APP_URL?>assets/images/icon-like.png" class="vote-icon <?= $currentVote === 'like' ? 'active' : '' ?>" width="20" high="auto" title="<?=lang_article_like?>">  
                                                                </a>
                                                                <span class="like-count"><?= $votes['like'] ?></span>
                                                                
                                                                <!-- DISLIKE -->
                                                                <a href="#" onclick="voteArticle(<?= $a['id'] ?>, 'dislike', this); return false; updateArticleMeta(<?=$a['id']?>);">
                                                                    <img src="<?=APP_URL?>assets/images/icon-dlike.png" class="vote-icon <?= $currentVote === 'dislike' ? 'active' : '' ?>" width="20" height="auto" title="<?=lang_article_dislike?>">    
                                                                
                                                                </a>
                                                                <span class="dislike-count"><?= $votes['dislike'] ?></span>

                                                            </div>
                                                </div>                               

                                                
                                                
                                            </div>
                                    </div>


                            <?php endforeach; ?>
                </div>

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


            <!-- 🔍 SEARCH FORM -->
            <div class="search-form">
                    <div class="search-form-header">
                    <img src="<?=APP_URL?>assets/icons/icon-search.svg" width="20" height="auto">&nbsp&nbsp; <?= lang_search ?>
                    </div>
                    <div style="display: flex; gap: 6px; align-items: center; padding: 20px;">
                        <input type="text" id="liveSearch" placeholder="<?= lang_search_placeholder ?>" autocomplete="off" style="flex: 1;">
                        <button id="toggleAdvancedBtn" title="<?= lang_adv_search ?>" style="background: none; border: none;">
                            <img id="icon-open" src="<?= APP_URL ?>assets/icons/icon-arrow-down.svg" width="24px" style="display: inline;">
                            <img id="icon-close" src="<?= APP_URL ?>assets/icons/icon-arrow-up.svg" width="24px" style="display: none;">
                        </button>
                    </div>

                    <!-- 🔧 ADVANCED SEARCH -->
                    <div id="advancedSearchForm" style="display: none; padding: 20px; background-color: white;">
                        <form id="advancedSearchFields" onsubmit="return triggerAdvancedSearch();">
                        <label>Autor:</label>
                        <input type="text" id="searchAuthor" name="author" style="width: 100%;">

                        <label style="margin-top: 6px;">Categorie:</label>
                        <select id="searchCategory" name="category" class="select2-category" style="width: 100%;">
                            <option value=""></option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['name']) ?>" data-img="/mykdb/assets/icons/categories/<?= $cat['icon'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <div style="margin-top: 10px; padding: 20px;">
                            <button type="submit" class="btn-outline-grey"><?= lang_search ?></button>
                        </div>
                        </form>
                    </div>
            </div>


            <!-- 🧮 FILTER FORM -->
            <div class="search-form">
                <div class="search-form-header">
                    <img src="<?=APP_URL?>assets/icons/icon-filter.svg" width="25" height="auto">&nbsp&nbsp; <?=lang_filter?>
                </div>
                <!-- Category filter -->
                <div style="display: flex; gap: 6px; align-items: center; padding: 20px; width: 100%;">
                   
                    <form id="filterOnCategory"  width="100%">
                        <select id="filterCategory" name="fcategory" class="select2-category" style="width: 100%">
                            <option value=""></option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" data-img="/mykdb/assets/icons/categories/<?= $cat['icon'] ?>" <?= (isset($_GET['fcategory']) && $_GET['fcategory'] == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
            
            <!-- 🧮 Most viewed articles  -->
            <div class="search-form">
                <div class="search-form-header">
                    <img src="<?=APP_URL?>assets/icons/icon-top-view.svg" width="25" height="auto">&nbsp&nbsp; <?=lang_article_top_view?>
                </div>
                <!-- most viewed articles list -->
                <div style="display: flex; gap: 6px; align-items: center; padding: 20px; width: 100%;">
                   
                    <?php echo getTopViewedArticles(5); ?>
                    
                </div>
            </div>
             <!-- 🧮 Top liked articles  -->
            <div class="search-form">
                <div class="search-form-header">
                    <img src="<?=APP_URL?>assets/icons/icon-top-like.svg" width="25" height="auto">&nbsp&nbsp; <?=lang_article_top_like?>
                </div>
                <!-- most viewed articles list -->
                <div style="display: flex; gap: 6px; align-items: center; padding: 20px; width: 100%;">
                   
                    <?php echo getTopLikedArticles(5); ?>
                    
                </div>
            </div>
    </div>
</div>



<script>

// Initializare Select2 pentru selectoare

$(document).ready(function () {
  function formatWithIcon(option) {
    if (!option.id) return option.text;

    const img = $(option.element).data('img');
    return $(
      `<span><img src="${img}" class="select2-option-img" width="20" style="margin-right:8px;" />${option.text}</span>`
    );
  }


  // Search by CATEGORY select
  $('#searchCategory').select2({
    placeholder: "<?=lang_cat_select?>",
    templateResult: formatWithIcon,
    templateSelection: formatWithIcon,
    allowClear: true
  });

  // Filter by CATEGORY select
  $('#filterCategory').select2({
    placeholder: "<?=lang_cat_select?>",
    width: '250px',
    templateResult: formatWithIcon,
    templateSelection: formatWithIcon,
    allowClear: true
  });
});

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




const input = document.getElementById('liveSearch');
const results = document.getElementById('searchResults');
const defaultContent = document.getElementById('defaultContent');
const advForm = document.getElementById('advancedSearchForm');
const advToggle = document.getElementById('toggleAdvancedBtn');
const iconOpen = document.getElementById('icon-open');
const iconClose = document.getElementById('icon-close');




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

  const res = await fetch('search_articles.php?q=' + encodeURIComponent(query));
  const data = await res.json();

  defaultContent.style.display = 'none';
  results.innerHTML = data.map(article => {
    const title = highlightText(article.title, query);
    const content = highlightHtmlContent(article.content, 200);
    return `
      <div style="border:1px solid #ccc; padding:10px; margin-bottom:5px;">
        <h4>${title}</h4>
        <p><em>Autor: ${article.username} | Categorie: ${article.category} | ${new Date(article.created_at).toLocaleDateString()}</em></p>
        <p>${content}</p>
      </div>`;
  }).join('') || '<p>Nu s-au găsit articole.</p>';
};
input.addEventListener('input', debounce(searchArticles, 300));

// Toggle advanced
advToggle.addEventListener('click', () => {
  const isOpen = advForm.style.display === 'block';

  //advForm.style.display = isOpen ? 'none' : 'block';
  iconOpen.style.display = isOpen ? 'inline' : 'none';
  iconClose.style.display = isOpen ? 'none' : 'inline';
  advForm.style.display = (advForm.style.display === 'none') ? 'block' : 'none';
});

// Trigger advanced
function triggerAdvancedSearch() {
  const q = input.value.trim();
  const author = document.getElementById('searchAuthor').value.trim();
  const category = document.getElementById('searchCategory').value;

  const params = new URLSearchParams({ q, author, category });

  fetch('search_articles.php?' + params.toString())
    .then(res => res.json())
    .then(data => {
      defaultContent.style.display = 'none';
      results.innerHTML = data.map(article => {
        const title = highlightText(article.title, q);
        const content = highlightHtmlContent(article.content, 200);

        return `
          <div style="border:1px solid #ccc; padding:10px; margin-bottom:5px;">
            <h4>${title}</h4>
            <p><em>Autor: ${article.username} | Categorie: ${article.category} | ${new Date(article.created_at).toLocaleDateString()}</em></p>
            <p>${content}</p>
          </div>`;
      }).join('') || '<p>Nu s-au găsit articole.</p>';
    });

  return false;
}


function triggerFilterCategory() {
  const categoryId = document.getElementById('filterCategory').value;
  if (!categoryId) return;

  const params = new URLSearchParams({ fcategory: categoryId });

  fetch('search_articles.php?' + params.toString())
    .then(res => res.json())
    .then(data => {
      defaultContent.style.display = 'none';
      searchResults.innerHTML = data.map(article => `
        <div class="article-card">
          <h4><b>${article.title}</b></h4>
          <p><em>${article.username} | ${article.category} | ${new Date(article.created_at).toLocaleDateString()}</em></p>
          <p>${article.content}</p>
        </div>
      `).join('') || '<p style="margin-top:10px;">Nu s-au găsit articole în această categorie.</p>';
    })
    .catch(() => {
      alert('Eroare la filtrare. Încearcă din nou.');
    });
}


// Event-uri pentru filtrare cu Select2

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


</script>


<?php include APP_ROOT . 'includes/footer.php'; ?>