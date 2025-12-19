/**
 * DashboardStats Component
 * 
 * Displays quick stats cards
 * @param {Object} props
 * @param {Object} props.stats - Statistics data
 */

const DashboardStats = ({ stats }) => {
    return React.createElement('div', { className: 'quick-stats-container' },
        // Total Articles Card
        React.createElement('div', { className: 'stat-card stat-card-blue' },
            React.createElement('div', { className: 'stat-icon' }, '📊'),
            React.createElement('div', { className: 'stat-content' },
                React.createElement('div', { className: 'stat-label' }, 'Total Articles'),
                React.createElement('div', { className: 'stat-value' }, stats.totalArticles),
                stats.articlesTrend && React.createElement('div', { 
                    className: `stat-trend ${stats.articlesTrend > 0 ? 'trend-up' : 'trend-down'}` 
                }, 
                    stats.articlesTrend > 0 ? '↑' : '↓',
                    ' ',
                    Math.abs(stats.articlesTrend),
                    '% this week'
                )
            )
        ),

        // Active Users Card
        React.createElement('div', { className: 'stat-card stat-card-green' },
            React.createElement('div', { className: 'stat-icon' }, '👥'),
            React.createElement('div', { className: 'stat-content' },
                React.createElement('div', { className: 'stat-label' }, 'Active Users'),
                React.createElement('div', { className: 'stat-value' }, stats.activeUsers),
                React.createElement('div', { className: 'stat-sublabel' }, 'last 7 days')
            )
        ),

        // Comments This Week Card
        React.createElement('div', { className: 'stat-card stat-card-orange' },
            React.createElement('div', { className: 'stat-icon' }, '💬'),
            React.createElement('div', { className: 'stat-content' },
                React.createElement('div', { className: 'stat-label' }, 'Comments'),
                React.createElement('div', { className: 'stat-value' }, stats.commentsThisWeek),
                stats.commentsComparison && React.createElement('div', { className: 'stat-sublabel' }, 
                    stats.commentsComparison > 0 ? '+' : '',
                    stats.commentsComparison,
                    ' vs last week'
                )
            )
        ),

        // Views This Week Card
        React.createElement('div', { className: 'stat-card stat-card-purple' },
            React.createElement('div', { className: 'stat-icon' }, '👁️'),
            React.createElement('div', { className: 'stat-content' },
                React.createElement('div', { className: 'stat-label' }, 'Article Views'),
                React.createElement('div', { className: 'stat-value' }, stats.viewsThisWeek),
                React.createElement('div', { className: 'stat-sublabel' }, 'this week')
            )
        )
    );
};

// Export for use in other files
if (typeof window !== 'undefined') {
    window.DashboardStats = DashboardStats;
}
