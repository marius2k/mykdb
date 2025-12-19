/**
 * RecentActivity Component
 * 
 * Displays recent activity timeline feed
 * @param {Object} props
 * @param {Array} props.activities - Recent activities
 */

const RecentActivity = ({ activities }) => {
    const [showAllActivities, setShowAllActivities] = React.useState(false);

    const displayActivities = showAllActivities ? activities : activities.slice(0, 5);

    return React.createElement('div', { className: 'activity-feed-container' },
        React.createElement('div', { className: 'activity-feed-header' },
            React.createElement('h3', { className: 'activity-feed-title' },
                React.createElement('span', { className: 'activity-icon' }, '📰'),
                ' Recent Activity'
            ),
            activities.length > 5 && React.createElement('button', {
                className: 'toggle-activities-btn',
                onClick: () => setShowAllActivities(!showAllActivities)
            }, showAllActivities ? 'Show Less' : `Show All (${activities.length})`)
        ),
        React.createElement('div', { className: 'activity-feed-list' },
            displayActivities.length > 0 
                ? displayActivities.map((activity, index) => 
                    React.createElement('div', { 
                        key: index, 
                        className: 'activity-item'
                    },
                        React.createElement('div', { className: 'activity-icon-wrapper' },
                            React.createElement('span', { className: 'activity-type-icon' }, activity.icon)
                        ),
                        React.createElement('div', { className: 'activity-content' },
                            React.createElement('div', { className: 'activity-text' },
                                React.createElement('strong', null, activity.username),
                                ' ',
                                activity.message
                            ),
                            React.createElement('div', { className: 'activity-time' }, activity.timeAgo)
                        )
                    )
                )
                : React.createElement('div', { className: 'no-activity' }, 
                    '📭 No recent activity'
                )
        )
    );
};

// Export for use in other files
if (typeof window !== 'undefined') {
    window.RecentActivity = RecentActivity;
}
