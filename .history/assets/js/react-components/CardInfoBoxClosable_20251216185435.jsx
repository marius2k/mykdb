/**
 * CardInfoBoxClosable Component
 * 
 * A reusable card component with a close (X) button instead of collapse/expand.
 * Used for dismissible content like article details.
 * 
 * Props:
 * - icon: Icon element or image to display (can be FontAwesome, emoji, or image URL)
 * - title: Card title/heading
 * - body: Card body content (can be text, HTML, or React elements)
 * - className: Optional additional CSS classes
 * - onClick: Optional click handler for the entire card
 * - style: Optional inline styles
 * - onClose: Function to call when the X button is clicked (required)
 */

const CardInfoBoxClosable = ({ 
    icon = null,
    title = '',
    body = '',
    className = '',
    onClick = null,
    style = {},
    onClose = null
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

    const handleClose = (e) => {
        e.stopPropagation();
        if (onClose) {
            onClose();
        }
    };

    return (
        <div 
            className={cardClasses}
            onClick={onClick}
            style={style}
        >
            {/* Top Section: Icon on left, Title on right, Close button */}
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
                {onClose && (
                    <button 
                        className="card-info-close"
                        onClick={handleClose}
                        aria-label="Close"
                    >
                        <svg 
                            className="close-icon"
                            width="24" 
                            height="24" 
                            viewBox="0 0 24 24" 
                            fill="none" 
                            stroke="currentColor" 
                            strokeWidth="2" 
                            strokeLinecap="round" 
                            strokeLinejoin="round"
                        >
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                )}
            </div>

            {/* Body Section - Always visible (no collapse) */}
            <div className="card-info-body open">
                <div className="card-info-body-inner">
                    {typeof body === 'string' ? (
                        <div dangerouslySetInnerHTML={{ __html: body }} />
                    ) : (
                        body
                    )}
                </div>
            </div>
        </div>
    );
};
