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
 */

const CardInfoBox = ({ 
    icon = null,
    title = '',
    body = '',
    className = '',
    onClick = null,
    style = {}
}) => {
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

    return (
        <div 
            className={cardClasses}
            onClick={onClick}
            style={style}
        >
            {/* Top Section: Icon on left, Title on right */}
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
            </div>

            {/* Body Section */}
            {body && (
                <div className="card-info-body">
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
