# Knowledge Base### Search Analytics
- Tracks user search behavior and search result interactions
- Records search queries, results clicked, and search abandonment
- Provides insights into content discoverability
- Implementation files:
  - Frontend: `assets/js/search-analytics.js` - Client-side tracking of search behavior
  - Backend: `public/api/bkd_search_analytics.php` - API endpoint for storing search data
  - Database: Tables created by `scripts/add_search_analytics.php`
- Data Collection Process:
  1. User performs a search on any search-enabled page
  2. JavaScript collector records query, timestamps, and results count
  3. If user clicks a result, click and position are recorded
  4. If user abandons search, abandonment is recorded
  5. Data is sent to API endpoint and stored in database
  6. Analytics dashboard displays aggregated search data

### PDF Download Tracking
- Monitors which articles users download as PDFs
- Tracks download frequency, user segments, and popular content
- Implementation files:
  - Frontend: `assets/js/pdf-tracker.js` - Client-side tracking of PDF downloads
  - Backend: `public/api/bkd_pdf_download.php` - API endpoint for storing download data
  - Database: Tables created by `scripts/add_file_downloads_table.php`
- Data Collection Process:
  1. User clicks on a PDF download link
  2. JavaScript collector captures article ID and download context
  3. Data is sent to API endpoint and recorded in database
  4. Analytics dashboard shows most downloaded articles and trendsm Documentation

## Overview
This documentation covers the complete analytics system implemented in the knowledge base application. The system tracks various user interactions to provide insights into how users engage with the platform.

## Components

### 1. User Analytics
- Tracks general user activity and article interactions
- Records page views, time spent on articles
- Monitors user engagement metrics
- Implementation files: 
  - Backend: `classes/analytics.php`
  - Frontend: `assets/js/analytics.js`
  - Database: Various columns in the articles and users tables

### 2. Search Analytics
- Tracks user search behavior and search result interactions
- Records search queries, results clicked, and search abandonment
- Provides insights into content discoverability
- Implementation files:
  - Frontend: `assets/js/search-analytics.js`
  - Backend: `public/api/bkd_search_analytics.php`
  - Database: Tables created by `scripts/add_search_analytics.php`

### 3. PDF Download Tracking
- Monitors which articles users download as PDFs
- Tracks download frequency, user segments, and popular content
- Implementation files:
  - Frontend: `assets/js/pdf-tracker.js`
  - Backend: `public/api/bkd_pdf_download.php`
  - Database: Tables created by `scripts/add_file_downloads_table.php`

## Database Schema

### Search Analytics Tables
- `search_queries`: Stores all search queries with metadata
  - `id` - Unique identifier
  - `user_id` - User who performed the search (null if anonymous)
  - `query` - The actual search text
  - `results_count` - Number of results returned
  - `timestamp` - When the search was performed
  - `ip_address` - User's IP address

- `search_results_clicks`: Tracks which results users clicked on
  - `id` - Unique identifier
  - `search_query_id` - Foreign key to search_queries
  - `article_id` - Article that was clicked
  - `position` - Position in search results (1st, 2nd, etc.)
  - `timestamp` - When the click occurred

### File Downloads Table
- `file_downloads`: Records file download activity
  - `id` - Unique identifier
  - `user_id` - User who downloaded the file (null if anonymous)
  - `article_id` - Article associated with the download
  - `filename` - Name of the downloaded file
  - `file_type` - Type of file (PDF, DOCX, etc.)
  - `timestamp` - When the download occurred
  - `ip_address` - User's IP address

## Implementation

### Frontend Analytics Collection
The application uses JavaScript to track user interactions in real-time:

1. Page load and navigation events are captured automatically
2. Search interactions are tracked when users:
   - Enter search queries
   - View search results
   - Click on search results
   - Abandon searches

3. Download events are tracked when users:
   - Click on download links
   - Successfully download files

### Backend Processing
All analytics data is sent to backend API endpoints which:
1. Validate the incoming data
2. Sanitize user inputs
3. Record the data in appropriate database tables
4. Handle any required follow-up actions

### Integration with APP_URL Pattern
All API endpoints follow the existing `APP_URL` variable pattern to ensure compatibility with different deployment environments.

## Running Migrations

To set up the analytics database tables, run:

```bash
# If running directly on the host
php scripts/add_search_analytics.php
php scripts/add_file_downloads_table.php

# If using Docker
bash scripts/docker_run_analytics_migrations.sh
```

## Accessing Analytics Data

Analytics data can be accessed through:
1. The admin dashboard (for authorized users)
2. Direct database queries for custom reporting
3. Export functionality for external analysis

## Privacy Considerations

The analytics system has been designed with privacy in mind:
- All data collection is anonymous by default unless users are logged in
- IP addresses are hashed for storage when appropriate
- No personally identifiable information is stored beyond what's necessary
- Data retention policies should be established based on organizational needs

## Extending the Analytics System

The analytics system can be extended by:
1. Adding new data collection points in frontend JavaScript
2. Creating corresponding backend API endpoints
3. Adding new database tables or columns as needed
4. Updating the dashboard to display new analytics data