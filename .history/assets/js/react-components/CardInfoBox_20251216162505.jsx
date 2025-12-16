/**
 * CardInfoBox Component
 * 
 * A reusable card component for displaying information with icon, title, and body content.
 * 
 * Props:
 * - icon: Icon element or image to display (can be FontAwesome, emoji, or image URL)
 * - title: Card title/heading
 * - body: Card body content (can be text, HTML, or React elements)
 * - className: Optional additional CSS classes
 * - onClick: Optional click handler for the entire card
 * - style: Optional inline styles
 * - collapsible: Whether the card can be collapsed (default: true)
 * - defaultOpen: Whether the card is open by default (default: true)
 */

const CardInfoBox = ({ 
    icon = null,
    title = '',
    body = '',
    className = '',
    onClick = null,
    style = {},
    collapsible = true,
    defaultOpen = true
}) => {
    const [isOpen, setIsOpen] = React.useState(defaultOpen);
    
    // Helper to check if icon is an image URL
    const isImageUrl = (icon) => {
        if (typeof icon === 'string') {
            return icon.startsWith('http') || 
                   icon.startsWith('/') || 
                   icon.endsWith('.png') || 
                   icon.endsWith('.svg') || 
                   icon.endsWith('.jpg') || 
                   icon.endsWith('.jpeg');
        }
        return false;
    };

    const cardClasses = `card-info-box ${className} ${onClick ? 'clickable' : ''}`.trim();

    const handleToggle = (e) => {
        e.stopPropagation();
        setIsOpen(!isOpen);
    };

    return (
        <div 
            className={cardClasses}
            onClick={onClick}
            style={style}
        >
            {/* Top Section: Icon on left, Title on right, Toggle button */}
            <div className="card-info-top">
                {icon && (
                    <div className="card-info-icon">
                        {isImageUrl(icon) ? (
                            <img src={icon} alt="icon" />
                        ) : (
                            <span>{icon}</span>
                        )}
                    </div>
                )}
                <div className="card-info-title">
                    {title}
                </div>
                {collapsible && (
                    <button 
                        className="card-info-toggle"
                        onClick={handleToggle}
                        aria-label={isOpen ? 'Collapse' : 'Expand'}
                    >
                        <svg 
                            className={`toggle-icon ${isOpen ? 'open' : 'closed'}`}
                            width="24" 
                            height="24" 
                            viewBox="0 0 24 24" 
                            fill="none" 
                            stroke="currentColor" 
                            strokeWidth="2" 
                            strokeLinecap="round" 
                            strokeLinejoin="round"
                        >
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                )}
            </div>

            {/* Body Section */}
            {body && (
                <div className={`card-info-body ${isOpen ? 'open' : 'closed'}`}>
                    {typeof body === 'string' ? (
                        <div dangerouslySetInnerHTML={{ __html: body }} />
                    ) : (
                        body
                    )}
                </div>
            )}
        </div>
    );
};

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CardInfoBox;
}
