/**
 * ScrollablePanel Component
 * 
 * A general-purpose component that cycles through multiple sections with their own titles and items
 * @param {Object} props
 * @param {Array} props.sections - Array of section objects: [{ title, items, renderItem, emptyMessage }]
 * @param {number} props.itemScrollInterval - Time to display each item in milliseconds (default: 3000)
 * @param {number} props.sectionTransitionDelay - Extra delay when moving to a new section (default: 1000)
 * @param {string} props.leftSideColor - Background color for the left side label (default: '#c1dfdf')
 * @param {string} props.leftSideTextColor - Text color for the left side label (default: '#1f2937')
 * @param {string} props.backgroundColor - Background color for the container (default: '#fff')
 * @param {string} props.borderColor - Border color (default: '#e5e7eb')
 * @param {number} props.minHeight - Minimum height in pixels (default: 80)
 * @param {number} props.borderRadius - Border radius in pixels (default: 12)
 */

const ScrollablePanel = ({ 
    sections = [],
    itemScrollInterval = 3000,
    sectionTransitionDelay = 1000,
    leftSideColor = '#ddf0f0ff',
    leftSideTextColor = '#1f2937',
    backgroundColor = '#fff',
    borderColor = '#e5e7eb',
    minHeight = 80,
    borderRadius = 12
}) => {
    const [currentSectionIndex, setCurrentSectionIndex] = React.useState(0);
    const [currentItemIndex, setCurrentItemIndex] = React.useState(0);
    const [isPaused, setIsPaused] = React.useState(false);

    React.useEffect(() => {
        if (sections.length === 0 || isPaused) return;

        const currentSection = sections[currentSectionIndex];
        const items = currentSection?.items || [];
        
        if (items.length === 0) {
            // If no items in current section, move to next section after delay
            const timeout = setTimeout(() => {
                setCurrentSectionIndex((prev) => (prev + 1) % sections.length);
                setCurrentItemIndex(0);
            }, sectionTransitionDelay);
            return () => clearTimeout(timeout);
        }

        const interval = setInterval(() => {
            setCurrentItemIndex((prevItemIndex) => {
                const nextItemIndex = prevItemIndex + 1;
                
                // If we've shown all items in current section, move to next section
                if (nextItemIndex >= items.length) {
                    setTimeout(() => {
                        setCurrentSectionIndex((prev) => (prev + 1) % sections.length);
                        setCurrentItemIndex(0);
                    }, sectionTransitionDelay);
                    return prevItemIndex; // Keep showing last item during transition
                }
                
                return nextItemIndex;
            });
        }, itemScrollInterval);

        return () => clearInterval(interval);
    }, [currentSectionIndex, currentItemIndex, sections, isPaused, itemScrollInterval, sectionTransitionDelay]);

    const containerStyle = {
        backgroundColor: backgroundColor,
        minHeight: minHeight + 'px',
        borderRadius: borderRadius + 'px'
    };

    const labelStyle = {
        backgroundColor: leftSideColor,
        color: leftSideTextColor,
        borderRight: '1px solid ' + borderColor
    };

    if (sections.length === 0) {
        return React.createElement('div', { 
            className: 'scrollable-panel-container',
            style: containerStyle
        },
            React.createElement('div', { 
                className: 'scrollable-panel-label',
                style: labelStyle
            }, 'No Data'),
            React.createElement('div', { className: 'scrollable-panel-content' },
                React.createElement('div', { className: 'scrollable-panel-empty' }, 
                    'No sections available'
                )
            )
        );
    }

    const currentSection = sections[currentSectionIndex];
    const items = currentSection?.items || [];
    const title = currentSection?.title || 'Section';
    const emptyMessage = currentSection?.emptyMessage || 'No items available';
    const renderItem = currentSection?.renderItem;

    // If current section has no items, show empty message
    if (items.length === 0) {
        return React.createElement('div', { 
            className: 'scrollable-panel-container',
            style: containerStyle,
            onMouseEnter: () => setIsPaused(true),
            onMouseLeave: () => setIsPaused(false)
        },
            React.createElement('div', { 
                className: 'scrollable-panel-label',
                style: labelStyle
            }, title),
            React.createElement('div', { className: 'scrollable-panel-content' },
                React.createElement('div', { className: 'scrollable-panel-empty' }, 
                    emptyMessage
                )
            )
        );
    }

    const currentItem = items[currentItemIndex];

    return React.createElement('div', { 
        className: 'scrollable-panel-container',
        style: containerStyle,
        onMouseEnter: () => setIsPaused(true),
        onMouseLeave: () => setIsPaused(false)
    },
        React.createElement('div', { 
            className: 'scrollable-panel-label',
            style: labelStyle
        }, title),
        React.createElement('div', { className: 'scrollable-panel-content' },
            React.createElement('div', { 
                key: `${currentSectionIndex}-${currentItemIndex}`,
                className: 'scrollable-panel-item'
            },
                renderItem ? renderItem(currentItem, currentItemIndex) : 
                React.createElement('div', null, JSON.stringify(currentItem))
            )
        )
    );
};

// Export for use in other files
if (typeof window !== 'undefined') {
    window.ScrollablePanel = ScrollablePanel;
}

