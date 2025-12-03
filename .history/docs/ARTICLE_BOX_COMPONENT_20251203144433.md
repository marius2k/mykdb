# ArticleBox React Component

## Overview
The ArticleBox component displays an article card with the following sections:
- **Header**: Icon, Title, and Bookmark button
- **Tags**: Article tags (if available)
- **Body**: Article content preview with "Read more" link
- **Footer**: Meta information (author, category, dates) and interaction buttons (views, comments, likes, dislikes)

## Component Structure

```
┌─────────────────────────────────────────────────┐
│ <ICON>  <TITLE>                     <F>         │  ← Header
├─────────────────────────────────────────────────┤
│ <TAGS>                                          │  ← Tags
├─────────────────────────────────────────────────┤
│                                                 │
│ <BODY>                                          │  ← Body/Content
│                                                 │
├─────────────────────────────────────────────────┤
│ <META INFO: Author, Category, Published...>    │  ← Footer
│ <VIEWS, COMMENTS, LIKES, DISLIKES>             │
└─────────────────────────────────────────────────┘
```

## Props

### Required Props

- **article**: Object containing article data
  ```javascript
  {
    id: number,           // Article ID
    title: string,        // Article title
    icon: string,         // Category icon (emoji or file path)
    catid: number,        // Category ID
    category: string,     // Category name
    tags: string[],       // Array of tags
    shortText: string,    // Article preview text
    username: string,     // Author username
    publishedAt: string,  // Published date (formatted)
    updatedAt: string,    // Updated date (formatted)
    version: number,      // Article version
    viewsCount: number,   // Number of views
    commentsCount: number,// Number of comments
    likes: number,        // Number of likes
    dislikes: number      // Number of dislikes
  }
  ```

- **onBookmarkToggle**: Function to handle bookmark toggle
  ```javascript
  (articleId) => { /* Handle bookmark */ }
  ```

- **onVote**: Function to handle voting (like/dislike)
  ```javascript
  (articleId, voteType) => { /* Handle vote */ }
  ```

### Optional Props

- **isBookmarked**: Boolean (default: false) - Whether article is bookmarked
- **currentVote**: String ('like' | 'dislike' | null) - User's current vote
- **appUrl**: String (default: '/mykdb/') - Base application URL

## Usage Example

### 1. Include React and Component Files

```html
<!-- React CDN -->
<script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>

<!-- ArticleBox Component -->
<script src="<?= APP_URL ?>assets/js/react-components-dist/ArticleBox.js"></script>
```

### 2. Prepare Article Data

```javascript
const articleData = {
    id: 123,
    title: "How to Use React Components",
    icon: "react-icon.svg",
    catid: 5,
    category: "Programming",
    tags: ["React", "JavaScript", "Frontend"],
    shortText: "Learn how to create and use React components...",
    username: "JohnDoe",
    publishedAt: "2025-12-02",
    updatedAt: "2025-12-02",
    version: 1,
    viewsCount: 150,
    commentsCount: 12,
    likes: 45,
    dislikes: 2
};
```

### 3. Render the Component

```javascript
// Get container element
const container = document.getElementById('article-container');
const root = ReactDOM.createRoot(container);

// Handler functions
const handleBookmarkToggle = async (articleId) => {
    try {
        const response = await fetch(`${window.APP_URL}public/api/toggle_bookmark.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ article_id: articleId })
        });
        const data = await response.json();
        if (data.success) {
            // Update UI or state
            console.log('Bookmark toggled');
        }
    } catch (error) {
        console.error('Error toggling bookmark:', error);
    }
};

const handleVote = async (articleId, voteType) => {
    try {
        const response = await fetch(`${window.APP_URL}public/api/vote_article.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                article_id: articleId, 
                vote_type: voteType 
            })
        });
        const data = await response.json();
        if (data.success) {
            // Update vote counts
            console.log('Vote recorded');
        }
    } catch (error) {
        console.error('Error voting:', error);
    }
};

// Render component
root.render(
    React.createElement(ArticleBox, {
        article: articleData,
        onBookmarkToggle: handleBookmarkToggle,
        onVote: handleVote,
        isBookmarked: true,
        currentVote: 'like',
        appUrl: window.APP_URL || '/mykdb/'
    })
);
```

### 4. Multiple Articles Example

```javascript
const articles = [
    { id: 1, title: "Article 1", ... },
    { id: 2, title: "Article 2", ... },
    { id: 3, title: "Article 3", ... }
];

const container = document.getElementById('articles-list');
const root = ReactDOM.createRoot(container);

root.render(
    React.createElement(React.Fragment, null,
        articles.map(article =>
            React.createElement(ArticleBox, {
                key: article.id,
                article: article,
                onBookmarkToggle: handleBookmarkToggle,
                onVote: handleVote,
                isBookmarked: bookmarkedArticles.includes(article.id),
                currentVote: userVotes[article.id] || null,
                appUrl: window.APP_URL
            })
        )
    )
);
```

## PHP Integration Example

```php
<?php
// Fetch articles from database
$articles = $db->query("SELECT * FROM articles")->fetchAll();

foreach ($articles as $article) {
    // Prepare article data
    $articleData = [
        'id' => $article['id'],
        'title' => $article['title'],
        'icon' => $article['icon'],
        'catid' => $article['catid'],
        'category' => $article['category'],
        'tags' => json_decode($article['tags']),
        'shortText' => substr($article['content'], 0, 200),
        'username' => $article['username'],
        'publishedAt' => formatDate($article['publish_at']),
        'updatedAt' => formatDate($article['updated_at']),
        'version' => $article['version'],
        'viewsCount' => getArticleViewsCount($article['id']),
        'commentsCount' => getCommentCount($article['id']),
        'likes' => $article['likes'],
        'dislikes' => $article['dislikes']
    ];
    ?>
    <div id="article-<?= $article['id'] ?>"></div>
    <script>
        (function() {
            const container = document.getElementById('article-<?= $article['id'] ?>');
            const root = ReactDOM.createRoot(container);
            root.render(React.createElement(ArticleBox, {
                article: <?= json_encode($articleData) ?>,
                onBookmarkToggle: handleBookmarkToggle,
                onVote: handleVote,
                isBookmarked: <?= is_article_bookmarked($article['id'], $_SESSION['user']['id'] ?? null) ? 'true' : 'false' ?>,
                currentVote: <?= json_encode(getUserVote($article['id'], $_SESSION['user']['id'] ?? null)) ?>,
                appUrl: '<?= APP_URL ?>'
            }));
        })();
    </script>
    <?php
}
?>
```

## CSS Classes Used

The component uses these CSS classes (should already exist in your theme CSS):
- `.article-card` - Main card container
- `.article-title` - Article title
- `.article-tags` - Tags container
- `.tag-badge-1` - Individual tag badge
- `.article-body` - Article content area
- `.article-footer` - Footer container
- `.article-meta` - Meta information text
- `.vote-buttons-container` - Voting buttons wrapper
- `.vote-buttons` - Individual vote button group
- `.vote-icon` - Vote icon styling
- `.vote-icon.active` - Active vote state
- `.bookmark-icon` - Bookmark icon
- `.view-count`, `.comments-count`, `.like-count`, `.dislike-count` - Counter displays

## Features

✅ Icon support (emoji or image file)
✅ Tag navigation
✅ Bookmark toggle
✅ Like/Dislike voting
✅ View and comment counts
✅ Read more link
✅ Responsive layout
✅ Theme-aware (uses existing CSS classes)
✅ Accessible (proper alt text, titles)

## Notes

- The component expects existing CSS classes from your theme
- All icons and images should be available in the specified paths
- The component is stateless - state management is handled by parent
- Compiled version is available at `assets/js/react-components-dist/ArticleBox.js`
