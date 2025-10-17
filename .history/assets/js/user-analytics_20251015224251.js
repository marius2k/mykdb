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
        
        // Make the API request
        return fetch(APP_URL + 'public/api/bkd_user_analytics.php?action=track_user_action', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Unknown error');
            }
            return data;
        })
        .catch(error => {
            // Log the error but don't break the user experience
            console.error('Analytics tracking error:', error);
            // Return a resolved promise to prevent breaking the caller's chain
            return Promise.resolve({ success: false, error: error.message });
        });
    },
    
    /**
     * Track article view
     * 
     * @param {number} articleId - Article ID
     * @returns {Promise}
     */
    trackArticleView: function(articleId) {
        return this.trackAction('view', { articleId });
    },
    
    /**
     * Track article bookmark
     * 
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
     * Track article rating
     * 
     * @param {number} articleId - Article ID
     * @param {number} rating - Rating value (typically 1-5)
     * @returns {Promise}
     */
    trackRating: function(articleId, rating) {
        return this.trackAction('rating', {
            articleId,
            actionValue: rating
        });
    },
    
    /**
     * Track article usefulness
     * 
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
     * Track comment creation
     * 
     * @param {number} articleId - Article ID
     * @returns {Promise}
     */
    trackComment: function(articleId) {
        return this.trackAction('comment', {
            articleId,
            actionValue: 1
        });
    },
    
    /**
     * Track vote (article or comment)
     * 
     * @param {number} articleId - Article ID
     * @param {number} voteValue - Vote value (1 for like, -1 for dislike)
     * @param {string} voteType - Type of vote ('article' or 'comment')
     * @param {number|null} commentId - Comment ID (if voting on a comment)
     * @returns {Promise}
     */
    trackVote: function(articleId, voteValue, voteType = 'article', commentId = null) {
        const params = {
            articleId,
            actionValue: voteValue,
            actionMetadata: {
                type: voteType,
                vote_type: voteValue > 0 ? 'like' : 'dislike'
            }
        };
        
        if (commentId && voteType === 'comment') {
            params.commentId = commentId;
        }
        
        return this.trackAction('vote', params);
    },
    
    /**
     * Track comment action
     * 
     * @param {number} articleId - Article ID
     * @param {number} commentId - Comment ID
     * @param {string} actionType - Action type (comment, vote)
     * @param {number|string|null} value - Action value
     * @returns {Promise}
     */
    trackCommentAction: function(articleId, commentId, actionType, value = null) {
        return this.trackAction(actionType, {
            articleId,
            commentId,
            actionValue: value
        });
    },
    
    /**
     * Track PDF save/download
     * 
     * @param {number} articleId - Article ID
     * @returns {Promise}
     */
    trackPdfSave: function(articleId) {
        return this.trackAction('save_pdf', { articleId });
    }
};

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UserAnalytics;
}