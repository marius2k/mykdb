// Button Component - JSX version
const Button = ({ text, onClick, variant = 'primary', disabled = false, icon = null, size = 'medium', loading = false, className = '' }) => {
  const handleClick = (e) => {
    if (disabled || loading) {
      return;
    }
    onClick(e);
  };

  const buttonClass = [
    'react-button',
    `react-button--${variant}`,
    `react-button--${size}`,
    disabled && 'react-button--disabled',
    loading && 'react-button--loading',
    className
  ].filter(Boolean).join(' ');

  // Determine if icon is a file path or emoji/text
  const isIconFile = icon && (icon.endsWith('.svg') || icon.endsWith('.png') || icon.endsWith('.jpg'));
  const iconPath = isIconFile ? `${window.APP_URL || '/mykdb/'}assets/icons/${icon}` : null;

  return (
    <button className={buttonClass} onClick={handleClick} disabled={disabled}>
      {loading ? (
        <>
          <span className="button-spinner">⟳</span>
          {text}
        </>
      ) : (
        <>
          {icon && (
            <span className="button-icon">
              {isIconFile ? (
                <img src={iconPath} alt="" style={{ width: '1.2em', height: '1.2em', verticalAlign: 'middle' }} />
              ) : (
                icon
              )}
            </span>
          )}
          {text}
        </>
      )}
    </button>
  );
};

// Make Button available globally
window.Button = Button;
