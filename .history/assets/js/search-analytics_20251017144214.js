/**
 * Search Analytics Tracker
 * 
 * This script tracks user search behavior in the knowledge base
 * It captures search queries, results, and user interactions with search results
 */

const SearchAnalytics = {
    // Configuration
    config: {
        debounceTime: 3000, // ms to wait after typing before recording a search (prevents recording every keystroke)
    },
    
    // State variables
    state: {
        lastSearchQuery: null,
        lastSearchTime: null,
        searchResultCount: 0,
        debounceTimer: null,
        searchId: null, // Unique ID for correlating search with result clicks
    },
    
    /**
     * Initialize search analytics
     * @param {object} options - Configuration options
     */
    init: function(options = {}) {
        // Merge user options with defaults
        Object.assign(this.config, options);
        
        console.debug('Search Analytics initialized');
        
        // Automatically attach to search forms on page
        this.attachToSearchForms();
    },
    
    /**
     * Find and attach handlers to search forms
     */
    attachToSearchForms: function() {
        // Find search forms or inputs - customize selectors based on your HTML structure
        const searchForms = document.querySelectorAll('form[role="search"], .search-form, form:has(input[type="search"])');
        const searchInputs = document.querySelectorAll('input[type="search"], input[name*="search"], input[name*="query"]');
        
        // Attach to forms
        searchForms.forEach(form => {
            form.addEventListener('submit', (e) => {
                const input = form.querySelector('input[type="search"], input[name*="search"], input[name*="query"]');
                if (input && input.value) {
                    this.trackSearch(input.value);
                }
            });
        });
        
        // Attach to inputs (for auto-search as-you-type interfaces)
        searchInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                // Debounce to avoid tracking every keystroke
                clearTimeout(this.state.debounceTimer);
                
                this.state.debounceTimer = setTimeout(() => {
                    if (input.value && input.value.length > 2) { // Only track searches with at least 3 chars
                        this.trackSearch(input.value);
                    }
                }, this.config.debounceTime);
            });
        });
        
        // Add click tracking to search results
        document.addEventListener('click', (e) => {
            // Find closest search result link - customize selector based on your HTML structure
            const resultLink = e.target.closest('.search-result-link, .search-result a, .search-results a, .kb-search-result a');
            
            if (resultLink && this.state.lastSearchQuery) {
                const articleId = resultLink.dataset.resultId || this.extractArticleIdFromUrl(resultLink.getAttribute('href'));
                const position = resultLink.dataset.position || this.getPositionInResults(resultLink);
                
                this.trackSearchResultClick(
                    resultLink.getAttribute('href'),
                    resultLink.textContent.trim(),
                    position,
                    articleId
                );
            }
        });
    },
    
    /**
     * Track a search query
     * @param {string} query - The search query
     * @param {number} resultCount - Number of results (if available)
     */
    trackSearch: function(query, resultCount = null) {
        // Don't track duplicate searches or empty searches
        if (!query || (query === this.state.lastSearchQuery && Date.now() - this.state.lastSearchTime < 2000)) {
            return;
        }
        
        this.state.lastSearchQuery = query;
        this.state.lastSearchTime = Date.now();
        this.state.searchId = this.generateSearchId();
        
        // Wait briefly to collect result count if not provided
        setTimeout(() => {
            // If resultCount wasn't provided, try to determine it from the page
            if (resultCount === null) {
                resultCount = this.detectSearchResultCount();
            }
            
            this.state.searchResultCount = resultCount;
            
            const searchData = {
                query: query,
                result_count: resultCount,
                search_id: this.state.searchId
            };
            
            console.debug('Tracking search:', searchData);
            
            // Send data to server
            this.sendData('search_query', searchData);
        }, 100);
    },
    
    /**
     * Track when user clicks on a search result
     * @param {string} url - The URL of the clicked result
     * @param {string} title - The title of the clicked result
     * @param {number} position - Position of the result in the list (1-based)
     * @param {number} articleId - The article ID (if available)
     */
    trackSearchResultClick: function(url, title, position, articleId = null) {
        if (!this.state.lastSearchQuery || !this.state.searchId) {
            return;
        }
        
        const clickData = {
            search_id: this.state.searchId,
            query: this.state.lastSearchQuery,
            result_url: url,
            result_title: title,
            position: position,
            article_id: articleId,
            time_to_click: Date.now() - this.state.lastSearchTime
        };
        
        console.debug('Tracking search result click:', clickData);
        
        // Send data to server
        this.sendData('search_result_click', clickData);
    },
    
    /**
     * Extract article ID from a URL
     * @param {string} url - The URL to extract from
     * @returns {number|null} - The article ID or null
     */
    extractArticleIdFromUrl: function(url) {
        if (!url) return null;
        
        // Try to match patterns like: ?id=123 or /articles/123
        const match = url.match(/[?&]id=(\d+)/);
        if (match) {
            return parseInt(match[1], 10);
        }
        
        const match2 = url.match(/\/articles\/(\d+)/);
        if (match2) {
            return parseInt(match2[1], 10);
        }
        
        return null;
    },
    
    /**
     * Generate a unique ID for a search session
     */
    generateSearchId: function() {
        return Date.now().toString(36) + Math.random().toString(36).substring(2);
    },
    
    /**
     * Try to detect how many search results are shown
     */
    detectSearchResultCount: function() {
        // Customize these selectors based on your HTML structure
        const selectors = [
            '.search-results .search-result',
            '.search-results > li',
            '.search-results > div',
            '.kb-search-results .kb-search-result'
        ];
        
        for (const selector of selectors) {
            const results = document.querySelectorAll(selector);
            if (results.length > 0) {
                return results.length;
            }
        }
        
        // Try to find a count display element
        const countElements = document.querySelectorAll('.search-count, .results-count');
        for (const el of countElements) {
            const text = el.textContent;
            const match = text.match(/\d+/);
            if (match) {
                return parseInt(match[0], 10);
            }
        }
        
        return 0;
    },
    
    /**
     * Get the position of a result link in the search results
     * @param {Element} element - The clicked element
     * @returns {number} - The 1-based position
     */
    getPositionInResults: function(element) {
        // Find the result item container
        const resultItem = element.closest('.search-result, li, .kb-search-result');
        
        if (!resultItem) return 0;
        
        // Find all siblings of the same type
        const container = resultItem.parentNode;
        const results = Array.from(container.children);
        
        // Find position (1-based)
        return results.indexOf(resultItem) + 1;
    },
    
    /**
     * Send data to the server
     * @param {string} actionType - Type of action being tracked
     * @param {object} data - Data to send
     */
    sendData: function(actionType, data) {
        // Determine the base URL
        let baseUrl;
        if (typeof APP_URL !== 'undefined' && APP_URL) {
            baseUrl = APP_URL;
        } else {
            baseUrl = window.location.origin + '/';
        }
        
        // Remove any trailing slashes and add our own
        baseUrl = baseUrl.replace(/\/+$/, '') + '/';
        
        // Add action type to data
        data.action_type = actionType;
        
        // Send data to server
        fetch(baseUrl + 'public/api/bkd_search_analytics.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.debug(`Search analytics (${actionType}) recorded successfully`);
            } else {
                console.warn(`Failed to record search analytics (${actionType}):`, data.error);
            }
        })
        .catch(error => {
            console.error(`Error recording search analytics (${actionType}):`, error);
        });
    }
};

// Auto-initialize
document.addEventListener('DOMContentLoaded', function() {
    SearchAnalytics.init();
});