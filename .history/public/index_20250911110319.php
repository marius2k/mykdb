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

<?php include APP_ROOT . 'includes/header.php'; ?>




<br>

<div class="main" style="display: flex; flex-direction: row; gap: 20px;">


    <!-- 🔹 Stânga: articole + rezultate -->
    <div class="defaultContent" style="width: 75%;">
        <div id="searchResults"></div>
        <div id="defaultContent">
                
                
                <?php if (count($articles) === 0): ?>
                        <p><?= lang('lang_no_articles') ?></p>
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
                                    //$shortText = shortenText($textOnly, 500);
                                    $shortText = shortenText(strip_tags(html_entity_decode($a['content'])), 500);

                                    $preview = truncateHtmlWithImages($a['content'], 100);
                                    ?>
                                    <div class="article-card">
                                          <div style="display: flex; justify-content: space-between;">
                                              <div>
                                                <h3 class="article-title">
                                                    <?php if (!empty($a['icon'])): ?>
                                                            <?php if (str_starts_with($a['icon'], 'http') || str_ends_with($a['icon'], '.png') || str_ends_with($a['icon'], '.svg')): ?>
                                                                <a href="index.php?fcategory=<?=$a['catid']?>" title="<?=$a['category']?>"><img src="<?=APP_URL?>assets/icons/categories/<?= $a['icon'] ?>" alt="icon" class="me-1" style="width: 35px; vertical-align: middle;"></a>
                                                                <?php else: ?>
                                                                <span class="me-1"><?= htmlspecialchars($a['icon']) ?></span>
                                                            <?php endif; ?>
                                                    <?php endif; ?><?= escape($a['title']) ?>
                                                </h3>
                                              </div>
                                              <div>
                                                <?php if(is_article_bookmarked($a['id'], $_SESSION['user']['id'] ?? null)): ?>
                                                    <img id="bookmark-img" src="<?=APP_URL?>assets/icons/icon-bookmark-full.svg" alt="Bookmark" class="bookmark-icon"  style="cursor:pointer; width:30px; height:auto;" onclick="toggleBookmark(<?= $a['id'] ?>, this)" title="<?=lang('lang_favorites_remove')?>" >
                                                <?php else: ?>
                                                    <img id="bookmark-img" src="<?=APP_URL?>assets/icons/icon-bookmark-empty.svg" alt="Bookmark" class="bookmark-icon"  style="cursor:pointer; width:30px; height:auto;" onclick="toggleBookmark(<?= $a['id'] ?>, this)" title="<?=lang('lang_favorites_add')?>" >
                                                <?php endif; ?>
                                              </div>
                                          </div>

                                          <div>
                                                <?php if (!empty($a['tags'])): ?>
                                                    <div class="article-tags" style="margin: 6px 10px; padding-bottom: 10px;">
                                                        <?php foreach ($a['tags'] as $tag): ?>
                                                            <span class="tag-badge-1"><a href="articles_by_tag.php?tag=<?= urlencode($tag) ?>"><?= htmlspecialchars($tag) ?></a></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                          </div>
                                          <div class="article-body" id="article<?=$a['id']?>">

                                            
                                                <p><?= nl2br($shortText) ?><a href="view_article.php?id=<?= (int)$a['id'] ?>"><img width="24" height="auto" src="<?=APP_URL?>assets/icons/icon-read-more.svg" title="<?= lang('lang_read_more') ?>"> </a></p>                                            
                                          </div>
                                            
                                            <div class="article-footer">
                                                <div>
                                                    <span class="article-meta"><?= lang('lang_article_author') ?>:<?=escape($a['username']) ?> | <?= lang('lang_article_category') ?>:<?=escape($a['category']) ?> | <?= lang('lang_article_published') ?>:<?=formatDate($a['publish_at']) ?> | <?= lang('lang_article_updated') ?>:<?=formatDate($a['updated_at'])?></span>
                                                </div>
                                                <div class="vote-buttons-container"> 
                                                            <div class="vote-buttons" id="meta-<?=$a['id']?>">
                                                                <a href="<?=APP_URL?>public/view_article.php?id=<?= (int)$a['id'] ?>">
                                                                <img src="<?=APP_URL?>assets/images/icon-view.png" title="<?= lang('lang_article_views') ?>" class="vote-icon"></a>
                                                                <span class="view-count"><?= getArticleViewsCount($a['id']) ?></span>

                                                                <a href="<?=APP_URL?>public/view_article.php?id=<?= (int)$a['id'] ?>#comments">
                                                                <img src="<?=APP_URL?>assets/images/icon-comm.png" title="<?= lang('lang_article_add_comments') ?>" class="vote-icon"></a>
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
                                                                    <img src="<?=APP_URL?>assets/images/icon-like.png" class="vote-icon <?= $currentVote === 'like' ? 'active' : '' ?>" width="20" high="auto" title="<?= lang('lang_article_like') ?>">  
                                                                </a>
                                                                <span class="like-count"><?= $votes['like'] ?></span>
                                                                
                                                                <!-- DISLIKE -->
                                                                <a href="#" onclick="voteArticle(<?= $a['id'] ?>, 'dislike', this); return false; updateArticleMeta(<?=$a['id']?>);">
                                                                    <img src="<?=APP_URL?>assets/images/icon-dlike.png" class="vote-icon <?= $currentVote === 'dislike' ? 'active' : '' ?>" width="20" height="auto" title="<?= lang('lang_article_dislike') ?>">    
                                                                
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
                        <div>
                          <img src="<?=APP_URL?>assets/icons/icon-search.svg" width="20" height="auto">&nbsp&nbsp; <?= lang('lang_search') ?>
                        </div>
                        <div class="search-form-header-right">  
                            <button id="toggleAdvancedBtn" title="<?= lang('lang_adv_search') ?>" style="background: none; border: none;">
                                <img id="icon-search-open" src="<?= APP_URL ?>assets/icons/icon-arrow-down.svg" width="24px" style="display: inline;">
                                <img id="icon-search-close" src="<?= APP_URL ?>assets/icons/icon-arrow-up.svg" width="24px" style="display: none;">
                            </button>
                        </div>
                    </div>
                    

                    <!-- 🔧 ADVANCED SEARCH -->
                   <div id="advancedSearchForm" style="display: none; padding: 20px; background-color: white;">
                          <div style="display: grid; grid-template-columns: 100px 1fr; gap: 12px; align-items: center;">
                            <!-- Rând 1: Câmpul principal -->
                            <label for="liveSearch" style="text-align: right;"><?= lang('lang_text') ?></label>
                            <input type="text" id="liveSearch" placeholder="<?= lang('lang_search_placeholder') ?>" autocomplete="off">

                            <!-- Rând 2: Autor -->
                            <label for="searchAuthor" style="text-align: right;"><?= lang('lang_art_author') ?>:</label>
                            <input type="text" id="searchAuthor" name="author">

                            <!-- Rând 3: Categorie -->
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
            </div>


            <!-- 🧮 FILTER FORM -->
            <div class="search-form">
                <div class="search-form-header">
                  <div>
                    <img src="<?=APP_URL?>assets/icons/icon-filter.svg" width="25" height="auto">&nbsp&nbsp; <?= lang('lang_filter') ?>
                  </div>
                  <div class="search-form-header-right">
                        <button id="toggleFilterBtn" title="<?= lang('lang_adv_search') ?>" style="background: none; border: none;">
                              <img id="icon-filter-open" src="<?= APP_URL ?>assets/icons/icon-arrow-down.svg" width="24px" style="display: inline;">
                              <img id="icon-filter-close" src="<?= APP_URL ?>assets/icons/icon-arrow-up.svg" width="24px" style="display: none;">
                        </button>
                  </div>
                </div>
                <!-- Category filter -->
                  <div id="advancedFilterForm" style="display: none; padding: 20px; background-color: white;">
                   
                    <form id="filterOnCategory" width="100%">
                        <label for="filterCategory" style="text-align: right;"><?= lang('lang_art_category') ?>:</label>
                        <select id="filterCategory" name="fcategory" class="select2-category" style="width: 100%">
                            <option value=""></option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" data-img="<?= APP_URL ?>assets/icons/categories/<?= $cat['icon'] ?>" <?= (isset($_GET['fcategory']) && $_GET['fcategory'] == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(($cat['name'])) ?>
                                </option> 
                            <?php endforeach; ?>
                        </select>
                    </form>
                  </div>
            </div>
            
            <!-- 🧮 Most viewed articles (MVA)  -->
            <div class="search-form">
                <div class="search-form-header">
                  <div>
                    <img src="<?=APP_URL?>assets/icons/icon-top-view.svg" width="25" height="auto">&nbsp&nbsp; <?= lang('lang_article_top_view') ?>
                  </div>
                  <div class="search-form-header-right">
                        <button id="toggleMVABtn" title="<?= lang('lang_adv_search') ?>" style="background: none; border: none;">
                              <img id="icon-mva-open" src="<?= APP_URL ?>assets/icons/icon-arrow-down.svg" width="24px" style="display: none;">
                              <img id="icon-mva-close" src="<?= APP_URL ?>assets/icons/icon-arrow-up.svg" width="24px" style="display: inline;">
                        </button>

                  </div>
                </div>
                <!-- most viewed articles list -->
                <div id="advancedMVAForm" style="padding: 20px; background-color: white;display: block;">
                   
                    <?php echo getTopViewedArticles(5); ?>
                    
                </div>
            </div>
             <!-- 🧮 Top liked articles  -->
            <div class="search-form">
                <div class="search-form-header">
                    <div >
                        <img src="<?=APP_URL?>assets/icons/icon-top-like.svg" width="25" height="auto">&nbsp&nbsp; <?= lang('lang_article_top_like') ?>
                    </div>
                    <div class="search-form-header-right">
                          <button id="toggleMLABtn" title="<?= lang('lang_adv_search') ?>" style="background: none; border: none;">
                                <img id="icon-mla-open" src="<?= APP_URL ?>assets/icons/icon-arrow-down.svg" width="24px" style="display: none;">
                                <img id="icon-mla-close" src="<?= APP_URL ?>assets/icons/icon-arrow-up.svg" width="24px" style="display: inline;">
                          </button>

                    </div>
                </div>
                <!-- most liked articles list -->
              <div id="advancedMLAForm" style="padding: 20px; background-color: white;display: block;">
                   
                    <?php echo getTopLikedArticles(5); ?>
                    
              </div>
            </div>
    </div>
</div>



<script>


        


// Initializare Select2 pentru selectoare



$(document).ready(function () {
        
      $('#searchCategory').select2(); // init dacă nu e deja

        
        
        function truncateText(text, maxLength = 20) {
          if (typeof text !== 'string') return '';
          const trimmed = text.trim();
          if (trimmed.length === 0) return '';
          return trimmed.length > maxLength ? trimmed.slice(0, maxLength - 1) + '…' : trimmed;
        }


        function formatWithIcon(state) {
          if (!state.id) return '';

          const element = state.element;
          const rawText = (typeof state.text === 'string' && state.text.trim())
            ? state.text
            : (element && element.textContent.trim()) || '';

          const label = truncateText(rawText);
          //const label = rawText;
          const img = element?.dataset?.img;

          const container = document.createElement('span');
          container.style.display = 'flex';
          container.style.alignItems = 'center';
          container.title = rawText;

          if (img) {
            const image = document.createElement('img');
            image.src = img;
            image.style.width = '20px';
            image.style.height = '20px';
            image.style.marginRight = '8px';
            container.appendChild(image);
          }

          const textNode = document.createTextNode(label);
          container.appendChild(textNode);

          return container;
        }



        function formatWithIcon2(option) {
          if (!option.id) return option.text;

          const img = $(option.element).data('img');
          return $(
            `<span><img src="${img}" class="select2-option-img" width="20" style="margin-right:8px;" />${option.text}</span>`
          );
        }

        
        // Search by CATEGORY select
        $('#searchCategory').select2({
          placeholder: "<?= lang('lang_cat_select') ?>",
          allowClear: true,
          templateResult: formatWithIcon,
          templateSelection: formatWithIcon          
        });

        $('#searchCategory').on('change', triggerAdvancedSearch);

        // Filter by CATEGORY select
        $('#filterCategory').select2({
          placeholder: "<?= lang('lang_cat_select') ?>",
          width: '250px',
          allowClear: true,
          templateResult: formatWithIcon,
          templateSelection: formatWithIcon
        });


});



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


const input = document.getElementById('liveSearch');
const results = document.getElementById('searchResults');
const defaultContent = document.getElementById('defaultContent');

const advFormSearch = document.getElementById('advancedSearchForm');
const advToggleSearch = document.getElementById('toggleAdvancedBtn');
const iconOpenSearch = document.getElementById('icon-search-open');
const iconCloseSearch = document.getElementById('icon-search-close');

const advFormFilter = document.getElementById('advancedFilterForm');
const advToggleFilter = document.getElementById('toggleFilterBtn');
const iconOpenFilter = document.getElementById('icon-filter-open');
const iconCloseFilter = document.getElementById('icon-filter-close');

const advFormMVA = document.getElementById('advancedMVAForm');
const advToggleMVA = document.getElementById('toggleMVABtn');
const iconOpenMVA = document.getElementById('icon-mva-open');
const iconCloseMVA = document.getElementById('icon-mva-close');

const advFormMLA = document.getElementById('advancedMLAForm');
const advToggleMLA = document.getElementById('toggleMLABtn');
const iconOpenMLA = document.getElementById('icon-mla-open');
const iconCloseMLA = document.getElementById('icon-mla-close');



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
  results.innerHTML = data.map(article => {
    const title = highlightText(article.title, query);
    
    //DEBUG: Log the highlighted title
    //console.log('Highlighted title:', title);

    const content = highlightHtmlContent(article.content, 200);
    return `
      <div style="border:1px solid #ccc; padding:10px; margin-bottom:5px;">
        <h4>${title}</h4>
        <p class="article-meta">Autor: ${article.username} | Categorie: ${article.category} | ${new Date(article.created_at).toLocaleDateString()}</p>
        <p>${content}</p>
      </div>`;
  }).join('') || '<p>Nu s-au găsit articole.</p>';
};
input.addEventListener('input', debounce(searchArticles, 300));



function clearSearchForm() {
  document.getElementById('searchAuthor').value = '';
  document.getElementById('searchCategory').value = '';
  document.getElementById('liveSearch').value = '';
}



// Toggle advanced search
advToggleSearch.addEventListener('click', () => {
  const isOpenSearch = advFormSearch.style.display === 'block';

  //advForm.style.display = isOpen ? 'none' : 'block';
  iconOpenSearch.style.display = isOpenSearch ? 'inline' : 'none';
  iconCloseSearch.style.display = isOpenSearch ? 'none' : 'inline';
  advFormSearch.style.display = (advFormSearch.style.display === 'none') ? 'block' : 'none';
  //clearSearchForm();
});

// Toggle Filter
advToggleFilter.addEventListener('click', () => {
  const isOpenFilter = advFormFilter.style.display === 'block';

  //advForm.style.display = isOpen ? 'none' : 'block';
  iconOpenFilter.style.display = isOpenFilter ? 'inline' : 'none';
  iconCloseFilter.style.display = isOpenFilter ? 'none' : 'inline';
  advFormFilter.style.display = (advFormFilter.style.display === 'none') ? 'block' : 'none';
});

// Toggle Most Viewed Articles (MVA)
advToggleMVA.addEventListener('click', () => {
  const isOpenMVA = advFormMVA.style.display === 'block';

  //advForm.style.display = isOpen ? 'none' : 'block';
  iconOpenMVA.style.display = isOpenMVA ? 'inline' : 'none';
  iconCloseMVA.style.display = isOpenMVA ? 'none' : 'inline';
  advFormMVA.style.display = (advFormMVA.style.display === 'block') ? 'none' : 'block';
});

// Toggle Most Liked Articles (MLA)
advToggleMLA.addEventListener('click', () => {
  const isOpenMLA = advFormMLA.style.display === 'block';

  //advForm.style.display = isOpen ? 'none' : 'block';
  iconOpenMLA.style.display = isOpenMLA ? 'inline' : 'none';
  iconCloseMLA.style.display = isOpenMLA ? 'none' : 'inline';
  advFormMLA.style.display = (advFormMLA.style.display === 'block') ? 'none' : 'block';
});



// Trigger advanced
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
            searchResults.innerHTML = data.map(article => `
                <div class="article-card">
                  <h4><img src="<?=APP_URL?>/assets/icons/categories/${article.icon}" width="40" height="auto">&nbsp;&nbsp;&nbsp;&nbsp;${highlightQuery(article.title, query)}</h4>
                  <p class="article-meta" style="font-size: 0.9rem;"><em><?=lang('lang_art_author')?>: ${article.username} | <?=lang('lang_art_category')?>: ${article.category} | <?=lang('lang_art_publish_at')?>: ${new Date(article.publish_at).toLocaleDateString(window.TZ)}</em></p>
                  <p>${highlightQuery(article.content, query)}</p>
                  <p><a href="view_article.php?id=${article.id}&version=${article.version}">Read more...</a></p>
                </div>
              `).join('') || '<p>Nu s-au găsit articole pe baza filtrului.</p>';
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