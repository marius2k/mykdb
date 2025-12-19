/**
 * ScrollablePanel Component
 * 
 * A general-purpose component with a fixed title section and scrollable content area
 * @param {Object} props
 * @param {string} props.title - Title text to display in the left panel
 * @param {Array} props.items - Array of items to scroll through
 * @param {Function} props.renderItem - Function to render each item (receives item, index)
 * @param {string} props.emptyMessage - Message to display when items array is empty
 * @param {number} props.scrollInterval - Auto-scroll interval in milliseconds (default: 3000)
 */

const ScrollablePanel = ({ 
    title = 'Title', 
    items = [], 
    renderItem = null,
    emptyMessage = 'No items available',
    scrollInterval = 3000 
}) => {
    const [currentIndex, setCurrentIndex] = React.useState(0);
    const [isPaused, setIsPaused] = React.useState(false);

    React.useEffect(() => {
        if (items.length <= 1 || isPaused) return;

        const interval = setInterval(() => {
            setCurrentIndex((prevIndex) => 
                prevIndex === items.length - 1 ? 0 : prevIndex + 1
            );
        }, scrollInterval);

        return () => clearInterval(interval);
    }, [items.length, isPaused, scrollInterval]);

    if (items.length === 0) {
        return React.createElement('div', { className: 'scrollable-panel-container' },
            React.createElement('div', { className: 'scrollable-panel-label' },
                title
            ),
            React.createElement('div', { className: 'scrollable-panel-content' },
                React.createElement('div', { className: 'scrollable-panel-empty' }, 
                    emptyMessage
                )
            )
        );
    }

    const currentItem = items[currentIndex];

    return React.createElement('div', { 
        className: 'scrollable-panel-container',
        onMouseEnter: () => setIsPaused(true),
        onMouseLeave: () => setIsPaused(false)
    },
        React.createElement('div', { className: 'scrollable-panel-label' },
            title
        ),
        React.createElement('div', { className: 'scrollable-panel-content' },
            React.createElement('div', { 
                key: currentIndex,
                className: 'scrollable-panel-item'
            },
                renderItem ? renderItem(currentItem, currentIndex) : 
                React.createElement('div', null, JSON.stringify(currentItem))
            )
        )
    );
};

// Export for use in other files
if (typeof window !== 'undefined') {
    window.ScrollablePanel = ScrollablePanel;
}
