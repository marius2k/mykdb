// Button Component - Plain JavaScript (no JSX)
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

  return React.createElement(
    'button',
    { 
      className: buttonClass, 
      onClick: handleClick, 
      disabled: disabled 
    },
    loading 
      ? React.createElement('span', { className: 'button-spinner' }, '⟳')
      : React.createElement(
          React.Fragment,
          null,
          icon && React.createElement('span', { className: 'button-icon' }, icon),
          text
        )
  );
};

// Make Button available globally
window.Button = Button;
