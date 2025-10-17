/**
 * PDF Download Tracking Utility
 * 
 * Track when users download article PDFs
 */

const PdfTracker = {
    /**
     * Initialize PDF tracking
     */
    init: function() {
        // Find PDF download links on the page
        this.attachToDownloadLinks();
        
        console.debug('PDF Tracker initialized');
    },
    
    /**
     * Find and attach handlers to PDF download links
     */
    attachToDownloadLinks: function() {
        // Find potential PDF download links - customize selectors based on your HTML structure
        const pdfLinks = document.querySelectorAll('a[href$=".pdf"], a.pdf-download, a[download], a[data-type="pdf"]');
        
        pdfLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                // Get article ID from metadata, link attribute, or page context
                const articleId = this.getArticleIdForLink(link);
                
                if (articleId) {
                    this.trackPdfDownload(articleId);
                }
            });
        });
    },
    
    /**
     * Get the article ID associated with a download link
     * @param {Element} link - The clicked link element
     * @returns {string|null} - Article ID or null if not found
     */
    getArticleIdForLink: function(link) {
        // Try to get article ID from link's data attribute
        if (link.dataset.articleId) {
            return link.dataset.articleId;
        }
        
        // Try to extract from href
        const href = link.getAttribute('href');
        if (href) {
            // Extract from URL params like ?id=123
            const match = href.match(/[?&]id=([^&]+)/);
            if (match) {
                return match[1];
            }
            
            // Extract from URL path like /articles/123/download.pdf
            const pathMatch = href.match(/\/articles\/([^\/]+)/);
            if (pathMatch) {
                return pathMatch[1];
            }
        }
        
        // Try to get from page metadata
        const articleIdMeta = document.querySelector('meta[name="article-id"]');
        if (articleIdMeta) {
            return articleIdMeta.getAttribute('content');
        }
        
        return null;
    },
    
    /**
     * Track a PDF download
     * @param {string} articleId - ID of the article being downloaded
     */
    trackPdfDownload: function(articleId) {
        console.debug(`Tracking PDF download for article: ${articleId}`);
        
        // Determine the base URL
        let baseUrl;
        if (typeof APP_URL !== 'undefined' && APP_URL) {
            baseUrl = APP_URL;
        } else {
            baseUrl = window.location.origin + '/';
        }
        
        // Remove any trailing slashes and add our own
        baseUrl = baseUrl.replace(/\/+$/, '') + '/';
        
        // Send tracking data to server
        const formData = new FormData();
        formData.append('article_id', articleId);
        
        fetch(baseUrl + 'public/api/bkd_pdf_download.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.debug('PDF download tracked successfully');
            } else {
                console.warn('Failed to track PDF download:', data.error);
            }
        })
        .catch(error => {
            console.error('Error tracking PDF download:', error);
        });
        
        // Also track via the user analytics system if available
        if (typeof UserAnalytics !== 'undefined') {
            UserAnalytics.trackAction('save_pdf', {
                articleId: articleId,
                actionValue: 1
            });
        }
    }
};

// Auto-initialize
document.addEventListener('DOMContentLoaded', function() {
    PdfTracker.init();
});