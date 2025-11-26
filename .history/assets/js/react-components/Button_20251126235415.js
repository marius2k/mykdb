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

  return (
    <button className={buttonClass} onClick={handleClick} disabled={disabled}>
      {loading ? (
        <span className="button-spinner">⟳</span>
      ) : (
        <>
          {icon && <span className="button-icon">{icon}</span>}
          {text}
        </>
      )}
    </button>
  );
};

// Make Button available globally
window.Button = Button;
