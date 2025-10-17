/**
 * User Analytics Tracking Utility
 * 
 * This file contains utilities for tracking user interactions with the knowledge base.
 */

const UserAnalytics = {
    /**
     * Track a user action
     * 
     * @param {string} actionType - Type of action (view, bookmark, comment, rating, etc.)
     * @param {object} params - Parameters for the action
     * @param {number} params.articleId - Article ID (required for most actions)
     * @param {number} params.commentId - Comment ID (required for comment-related actions)
     * @param {number|string} params.actionValue - Value of the action (e.g., rating value)
     * @param {object} params.actionMetadata - Additional metadata for the action
     * @returns {Promise} Promise resolving to the response from the server
     */
    trackAction: function(actionType, params = {}) {
        // Required parameters validation
        if (!actionType) {
            console.error('Action type is required');
            return Promise.reject(new Error('Action type is required'));
        }
        
        if (!params.articleId && !params.commentId) {
            console.error('Either article ID or comment ID is required');
            return Promise.reject(new Error('Either article ID or comment ID is required'));
        }
        
        // Prepare the request payload
        const payload = {
            action_type: actionType,
            article_id: params.articleId || null,
            comment_id: params.commentId || null
        };
        
        // Add optional parameters if provided
        if (params.actionValue !== undefined) {
            payload.action_value = params.actionValue;
        }
        
        if (params.actionMetadata !== undefined) {
            payload.action_metadata = params.actionMetadata;
        }
        
        // Determine the base URL
        let baseUrl;
        
        // First try to use APP_URL if defined
        if (typeof APP_URL !== 'undefined' && APP_URL) {
            baseUrl = APP_URL;
        } else {
            // Fallback to window.location.origin
            baseUrl = window.location.origin + '/';
        }
        
        // Remove any trailing slashes and add our own
        baseUrl = baseUrl.replace(/\/+$/, '') + '/';
        
        // Make the request to the analytics endpoint
        return fetch(baseUrl + 'public/api/bkd_user_analytics.php?action=track_user_action', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            if (!response.ok) {
                // First try to parse as JSON in case the server returned error details
                return response.json().catch(() => {
                    // If it's not JSON, throw a generic error
                    throw new Error(`Analytics request failed with status ${response.status}`);
                }).then(data => {
                    // If we got JSON but status is not ok, it's probably an error message
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    // Otherwise throw a generic error
                    throw new Error(`Analytics request failed with status ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.debug(`Action tracked: ${actionType}`, data);
            return data;
        })
        .catch(error => {
            console.error(`Failed to track action: ${actionType}`, error);
            // We don't want tracking failures to break the UI, so just log it
            return { success: false, error: error.message };
        });
    },
    
    /**
     * Track an article view
     * @param {number} articleId - Article ID
     * @returns {Promise}
     */
    trackArticleView: function(articleId) {
        return this.trackAction('view', { articleId });
    },
    
    /**
     * Track article bookmark/unbookmark
     * @param {number} articleId - Article ID
     * @param {boolean} bookmarked - Whether the article was bookmarked (true) or unbookmarked (false)
     * @returns {Promise}
     */
    trackBookmark: function(articleId, bookmarked = true) {
        return this.trackAction('bookmark', { 
            articleId, 
            actionValue: bookmarked ? 1 : 0 
        });
    },
    
    /**
     * Track article star rating
     * @param {number} articleId - Article ID
     * @param {number} rating - Rating value (1-5)
     * @returns {Promise}
     */
    trackRating: function(articleId, rating) {
        return this.trackAction('rating', { 
            articleId, 
            actionValue: rating 
        });
    },
    
    /**
     * Track article usefulness rating
     * @param {number} articleId - Article ID
     * @param {boolean} wasUseful - Whether the article was useful
     * @returns {Promise}
     */
    trackUsefulnessRating: function(articleId, wasUseful) {
        return this.trackAction('usefulness_rating', { 
            articleId, 
            actionValue: wasUseful ? 1 : 0 
        });
    },
    
    /**
     * Track article comment
     * @param {number} articleId - Article ID
     * @returns {Promise}
     */
    trackComment: function(articleId) {
        return this.trackAction('comment', { articleId });
    },
    
    /**
     * Track an article vote (like/dislike)
     * @param {number} articleId - Article ID
     * @param {string} voteType - Vote type ('like' or 'dislike')
     * @returns {Promise}
     */
    trackArticleVote: function(articleId, voteType) {
        return this.trackAction('vote', { 
            articleId, 
            actionValue: voteType === 'like' ? 1 : -1,
            actionMetadata: { type: 'article', vote_type: voteType }
        });
    },
    
    /**
     * Track a comment vote (like/dislike)
     * @param {number} articleId - Article ID
     * @param {number} commentId - Comment ID
     * @param {string} voteType - Vote type ('like' or 'dislike')
     * @returns {Promise}
     */
    trackCommentVote: function(articleId, commentId, voteType) {
        return this.trackAction('vote', { 
            articleId,
            commentId,
            actionValue: voteType === 'like' ? 1 : -1,
            actionMetadata: { type: 'comment', vote_type: voteType }
        });
    },
    
    /**
     * Check if analytics tables exist in the database
     * @returns {Promise} Promise resolving to boolean indicating if tables exist
     */
    checkTablesExist: function() {
        // Determine the base URL
        let baseUrl;
        
        // First try to use APP_URL if defined
        if (typeof APP_URL !== 'undefined' && APP_URL) {
            baseUrl = APP_URL;
        } else {
            // Fallback to window.location.origin
            baseUrl = window.location.origin + '/';
        }
        
        // Remove any trailing slashes and add our own
        baseUrl = baseUrl.replace(/\/+$/, '') + '/';
        
        return fetch(baseUrl + 'public/api/bkd_user_analytics.php?action=check_tables_exist')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Request failed with status ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                return data.tables_exist === true;
            })
            .catch(error => {
                console.error('Failed to check if analytics tables exist:', error);
                return false;
            });
    }
};

// Auto-initialize by checking if tables exist
document.addEventListener('DOMContentLoaded', function() {
    UserAnalytics.checkTablesExist()
        .then(tablesExist => {
            if (!tablesExist) {
                console.warn('Analytics tables do not exist. Analytics tracking will be disabled.');
            } else {
                console.debug('Analytics tables exist and ready for tracking.');
            }
        });
});
