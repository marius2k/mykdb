# Related Articles Feature

## 📋 Overview

The Related Articles feature provides intelligent article recommendations to users when they are reading an article. It enhances user engagement by suggesting relevant content based on multiple similarity criteria.

## 🎯 Purpose

- **Increase user engagement** by keeping users on the platform longer
- **Improve content discovery** by surfacing relevant articles
- **Enhance user experience** with personalized recommendations
- **Boost article views** through intelligent cross-referencing

## 🔧 Technical Implementation

### Backend API

**File:** `/public/api/bkd_related_articles.php`

The recommendation engine uses a **multi-criteria scoring system** to find the most relevant articles:

#### Scoring Algorithm

| Criteria | Points | Description |
|----------|--------|-------------|
| **Shared Tags** | 10 points/tag | Articles with common tags (highest priority) |
| **Same Category** | 5 points | Articles from the same category |
| **Same Author** | 3 points | Other articles by the same author |
| **Popularity** | 1 point | Popular articles (fallback mechanism) |

#### Algorithm Flow

1. **Tag Matching** - Find articles with shared tags
2. **Category Similarity** - Look for articles in the same category
3. **Author Relations** - Include other articles by the same author
4. **Popularity Fallback** - Add popular articles if needed to reach limit
5. **Score Calculation** - Combine all criteria into final score
6. **Sorting & Limiting** - Return top 6 articles by score

#### Example Score Calculation
```
Article A: 2 shared tags + same category + different author
Score = (2 × 10) + 5 + 0 = 25 points

Article B: 1 shared tag + different category + same author  
Score = (1 × 10) + 0 + 3 = 13 points
```

### Frontend Integration

**File:** `/public/view_article.php`

#### JavaScript Implementation
- **Asynchronous loading** using `fetch()` API
- **Error handling** for API failures
- **Responsive card generation** with Bootstrap grid
- **Smooth integration** without page reload

#### HTML Structure
```html
<div id="related-articles-section" class="article-view">
    <h4>Related Articles</h4>
    <div id="related-articles-container" class="row related-articles-row">
        <!-- Articles loaded dynamically via JavaScript -->
    </div>
</div>
```

#### Card Layout Features
- **Fixed height cards** (140px) for uniform appearance
- **Responsive grid** (3 columns on large screens, 2 on medium, 1 on small)
- **Tag display** with badge styling
- **Author and view count** information
- **Click-through links** to full articles

## 🎨 Visual Design

### Multi-Theme Support

The feature supports all three application themes:

#### Light Theme
- **Background:** Light gray (#f8f9fa) with subtle white cards
- **Hover effect:** Blue border (#048eb1) with elevated shadow
- **Colors:** Professional blue and gray palette

#### Gray Theme  
- **Background:** Medium gray (#5a5a5a) cards
- **Hover effect:** Blue accent with enhanced contrast
- **Colors:** Balanced gray tones with blue highlights

#### Dark Theme
- **Background:** Dark gray (#2d3748) cards  
- **Hover effect:** Blue glow with stronger shadows
- **Colors:** Dark palette with vibrant blue accents

### Interactive Effects

- **Hover animations:** Smooth border color change and shadow enhancement
- **Card elevation:** 2px lift effect on hover with `translateY(-2px)`
- **Transition timing:** 0.3s ease for smooth interactions
- **Visual feedback:** Clear indication of interactive elements

### Spacing System

Perfect mathematical spacing achieved through CSS Grid:
- **Horizontal spacing:** 1rem (16px) between columns
- **Vertical spacing:** 1rem (16px) between rows
- **Equal gaps:** Consistent spacing in all directions

## 📍 Positioning

The Related Articles section is strategically positioned:
- **Location:** Immediately after article content, before comments section
- **Rationale:** Captures user attention at content completion
- **Flow:** Natural reading progression from article to related content

## 🔄 User Experience Flow

1. **User reads article** → Scrolls to end of content
2. **Related Articles appear** → Automatic loading via JavaScript
3. **Visual scanning** → User sees relevant recommendations  
4. **Hover interaction** → Cards highlight with visual feedback
5. **Click-through** → User navigates to related article
6. **Engagement loop** → Process repeats for continuous discovery

## 📊 Performance Considerations

### Backend Optimization
- **Efficient queries** with proper JOIN operations
- **Indexed lookups** on article_tags relationships
- **Limited results** (6 articles max) for fast loading
- **Approved content only** filter applied

### Frontend Optimization  
- **Asynchronous loading** doesn't block page rendering
- **CSS-based styling** instead of inline styles
- **Responsive images** and optimized layouts
- **Minimal JavaScript** footprint

## 🔧 Configuration

### Customizable Parameters

```php
// In bkd_related_articles.php
$limit = 6; // Number of related articles to return

// Scoring weights (easily adjustable)
$tagScore = 10;      // Points per shared tag
$categoryScore = 5;   // Points for same category  
$authorScore = 3;     // Points for same author
$popularityScore = 1; // Points for popular articles
```

### Theme Customization

CSS classes available for styling customization:
- `.related-article-col` - Column container
- `.related-article-card` - Individual article card
- `.related-article-title` - Article title styling
- `.related-article-link` - Link styling
- `.related-articles-row` - Row container with spacing

## 📝 Database Requirements

### Required Tables
- `articles` - Main articles table
- `tags` - Tags definition
- `article_tags` - Many-to-many relationship
- `categories` - Article categories
- `users` - User information

### Required Fields
```sql
-- Articles must have
articles.status = 'approved'  -- Only show approved content
articles.category_id          -- For category matching
articles.user_id             -- For author matching
articles.views               -- For popularity sorting

-- Article-tags relationship
article_tags.article_id      -- Link to article
article_tags.tag_id          -- Link to tag
```

## 🚀 Future Enhancements

### Potential Improvements
1. **Machine Learning** - Use reading patterns for better recommendations
2. **User Preferences** - Personalized recommendations based on user history
3. **A/B Testing** - Test different scoring algorithms
4. **Analytics** - Track click-through rates and engagement metrics
5. **Caching** - Cache popular article relationships
6. **Content-Based Analysis** - Analyze article content similarity

### Advanced Features
- **Trending Articles** - Time-based popularity algorithms
- **Collaborative Filtering** - "Users who read this also read..."
- **Seasonal Relevance** - Date-aware recommendations
- **Reading Time** - Match articles of similar length

## 📈 Success Metrics

### Key Performance Indicators
- **Click-through rate** on related articles
- **Time spent on site** increase
- **Pages per session** improvement  
- **User engagement** metrics
- **Article view distribution** across content

## 🛠️ Maintenance

### Regular Tasks
- **Monitor performance** of recommendation queries
- **Update scoring weights** based on user behavior
- **Review popular articles** for quality
- **Clean up orphaned** article-tag relationships

### Troubleshooting
- **No recommendations showing** - Check article approval status
- **Poor recommendations** - Review tag assignments
- **Slow loading** - Optimize database queries
- **Layout issues** - Verify CSS theme consistency

---

**Feature Status:** ✅ Complete and Production Ready  
**Last Updated:** August 21, 2025  
**Version:** 1.0  
**Developer:** GitHub Copilot & Marius  
**Documentation:** Complete Technical & User Guide
