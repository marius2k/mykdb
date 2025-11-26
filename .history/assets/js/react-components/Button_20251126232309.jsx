/**
 * Reusable Button Component
 * 
 * Props:
 * - text: Button text to display (required)
 * - onClick: Function to call when button is clicked (required)
 * - variant: Button style - 'primary', 'danger', 'warning', 'success' (default: 'primary')
 * - disabled: Whether button is disabled (default: false)
 * - icon: Optional icon/emoji to display before text
 * - size: Button size - 'small', 'medium', 'large' (default: 'medium')
 * - loading: Show loading spinner (default: false)
 * - className: Additional CSS classes
 */

const Button = ({ 
  text, 
  onClick, 
  variant = 'primary', 
  disabled = false, 
  icon = null,
  size = 'medium',
  loading = false,
  className = ''
}) => {
  // Handle click with disabled and loading state check
  const handleClick = (e) => {
    if (disabled || loading) {
      e.preventDefault();
      return;
    }
    onClick(e);
  };

  // Build CSS classes
  const buttonClasses = [
    'react-button',
    `react-button--${variant}`,
    `react-button--${size}`,
    disabled && 'react-button--disabled',
    loading && 'react-button--loading',
    className
  ].filter(Boolean).join(' ');

  return (
    <button 
      className={buttonClasses}
      onClick={handleClick}
      disabled={disabled || loading}
      type="button"
    >
      {loading ? (
        <>
          <span className="button-spinner">⏳</span>
          <span>Loading...</span>
        </>
      ) : (
        <>
          {icon && <span className="button-icon">{icon}</span>}
          <span>{text}</span>
        </>
      )}
    </button>
  );
};

// Make Button available globally for use in other scripts
window.Button = Button;

// Make it available globally
window.Button = Button;
