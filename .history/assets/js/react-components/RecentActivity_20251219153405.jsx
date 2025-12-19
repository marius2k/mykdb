/**
 * RecentActivity Component
 * 
 * Displays recent activity timeline feed with auto-scroll carousel
 * @param {Object} props
 * @param {Array} props.activities - Recent activities
 */

const RecentActivity = ({ activities }) => {
    const [currentIndex, setCurrentIndex] = React.useState(0);
    const [isPaused, setIsPaused] = React.useState(false);

    React.useEffect(() => {
        if (activities.length <= 1 || isPaused) return;

        const interval = setInterval(() => {
            setCurrentIndex((prevIndex) => 
                prevIndex === activities.length - 1 ? 0 : prevIndex + 1
            );
        }, 3000); // Change item every 3 seconds

        return () => clearInterval(interval);
    }, [activities.length, isPaused]);

    const goToNext = () => {
        setCurrentIndex((prevIndex) => 
            prevIndex === activities.length - 1 ? 0 : prevIndex + 1
        );
    };

    const goToPrevious = () => {
        setCurrentIndex((prevIndex) => 
            prevIndex === 0 ? activities.length - 1 : prevIndex - 1
        );
    };

    if (activities.length === 0) {
        return React.createElement('div', { className: 'activity-feed-container' },
            React.createElement('div', { className: 'activity-feed-single' },
                React.createElement('div', { className: 'no-activity' }, 
                    '📭 No recent activity'
                )
            )
        );
    }

    const currentActivity = activities[currentIndex];

    return React.createElement('div', { 
        className: 'activity-feed-container',
        onMouseEnter: () => setIsPaused(true),
        onMouseLeave: () => setIsPaused(false)
    },
        React.createElement('div', { className: 'activity-feed-single' },
            React.createElement('div', { 
                key: currentIndex,
                className: 'activity-item-single'
            },
                React.createElement('div', { className: 'activity-icon-wrapper' },
                    React.createElement('span', { className: 'activity-type-icon' }, currentActivity.icon)
                ),
                React.createElement('div', { className: 'activity-content' },
                    React.createElement('div', { className: 'activity-text' },
                        React.createElement('strong', null, currentActivity.username),
                        ' ',
                        currentActivity.message
                    ),
                    React.createElement('div', { className: 'activity-time' }, currentActivity.timeAgo)
                )
            )
        )
    );
};

// Export for use in other files
if (typeof window !== 'undefined') {
    window.RecentActivity = RecentActivity;
}
