import { twMerge } from 'tailwind-merge';

export const Button = ({ children, variant = 'primary', className, isLoading, ...props }) => {
  const baseStyles = "px-6 py-3 rounded-lg font-bold transition-all transform active:scale-95 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed";
  
  const variants = {
    primary: "bg-blue-600 hover:bg-blue-700 text-white shadow-lg hover:shadow-blue-500/30",
    success: "bg-green-500 hover:bg-green-600 text-white shadow-lg hover:shadow-green-500/30",
    danger: "bg-red-500 hover:bg-red-600 text-white",
    ghost: "bg-transparent hover:bg-gray-100 text-gray-700",
    outline: "border-2 border-white text-white hover:bg-white hover:text-blue-900"
  };

  return (
    <button 
      className={twMerge(baseStyles, variants[variant], className)} 
      disabled={isLoading}
      {...props}
    >
      {isLoading ? <span className="animate-spin h-5 w-5 border-2 border-white border-t-transparent rounded-full"/> : children}
    </button>
  );
};