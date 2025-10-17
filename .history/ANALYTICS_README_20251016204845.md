# Knowledge Base Analytics Enhancement

This update introduces comprehensive analytics tracking to the knowledge base system to provide better insights into user behavior and content effectiveness.

## New Features

### 1. Search Analytics System
- Tracks user search queries and interactions
- Records which search results users click on
- Identifies zero-result searches and search abandonment
- Provides data for improving search relevance and content discoverability

### 2. PDF Download Tracking
- Monitors which articles users download as PDFs
- Tracks download frequency and user segments
- Identifies high-value content based on download patterns
- Helps understand offline content consumption

## Data Collectors

### Search Analytics Collector
The search analytics collector automatically tracks:

- **Search queries**: Every time a user searches, the query is recorded along with metadata
- **Search results count**: Number of results returned for each search
- **Result clicks**: When a user clicks on a search result, the position and article are recorded
- **Search abandonment**: Detects when users leave without clicking any results
- **Search patterns**: Can identify patterns in user search behavior

The collector is implemented in `assets/js/search-analytics.js` and is automatically included on pages with search functionality.

### PDF Download Collector
The PDF download collector automatically tracks:

- **Download events**: When users download article PDFs
- **Download metadata**: Article ID, user ID, timestamp, and download context
- **File types**: Primarily PDFs, but extensible to other file types

The collector is implemented in `assets/js/pdf-tracker.js` and is automatically included on article pages.

## Installation

To install these enhancements in your Docker environment:

```bash
# Make the script executable if needed
chmod +x scripts/docker_run_analytics_migrations.sh

# Run the migrations
./scripts/docker_run_analytics_migrations.sh
```

For non-Docker installations:

```bash
# Run the migration scripts directly
php scripts/add_search_analytics.php
php scripts/add_file_downloads_table.php
```

## Documentation

For detailed information about the analytics system:

- [Analytics System Documentation](docs/analytics_system.md) - Technical implementation details
- [Interpreting Analytics Data](docs/interpreting_analytics_data.md) - Guide to using analytics data effectively

## Files Added

### Scripts
- `scripts/add_search_analytics.php` - Adds search analytics tables
- `scripts/add_file_downloads_table.php` - Adds file download tracking table
- `scripts/run_analytics_migrations.sh` - In-container migration script
- `scripts/docker_run_analytics_migrations.sh` - Host-side Docker execution script

### Frontend
- `assets/js/search-analytics.js` - Client-side search tracking
- `assets/js/pdf-tracker.js` - Client-side PDF download tracking

### Backend
- `public/api/bkd_search_analytics.php` - API endpoint for search analytics
- `public/api/bkd_pdf_download.php` - API endpoint for PDF download tracking

### Documentation
- `docs/analytics_system.md` - Technical documentation
- `docs/interpreting_analytics_data.md` - Analytics interpretation guide

## Integration with Existing System

These enhancements integrate with the existing analytics infrastructure and follow all established patterns:
- Uses the same `APP_URL` variable pattern
- Compatible with existing authentication system
- Follows the same coding standards and practices