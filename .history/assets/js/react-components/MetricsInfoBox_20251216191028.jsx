/**
 * MetricsInfoBox Component
 * 
 * A card component for displaying metric information with optional top/bottom text
 * and a colored left border indicator.
 * 
 * @param {string} topText - Optional text displayed at the top of the card
 * @param {string} counter - The main metric value (displayed prominently)
 * @param {string} bottomText - Text label displayed below the counter
 * @param {string} color - Border color for the left indicator (e.g., '#3498db', '#2ecc71')
 * @param {function} onClick - Optional click handler
 * @param {string} className - Optional additional CSS classes
 * @param {object} style - Optional inline styles
 */

const MetricsInfoBox = ({ 
    topText = '', 
    counter, 
    bottomText, 
    color = '#3498db',
    onClick,
    className = '',
    style = {}
}) => {
    return (
        <div 
            className={`metrics-info-box ${className}`}
            style={{ ...style, borderLeftColor: color }}
            onClick={onClick}
        >
            {topText && (
                <div className="metrics-top-text">
                    {topText}
                </div>
            )}
            
            <div className="metrics-counter">
                {counter}
            </div>
            
            <div className="metrics-bottom-text">
                {bottomText}
            </div>
        </div>
    );
};
