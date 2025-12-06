/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // Define cores baseadas em variáveis CSS
        primary: 'var(--color-primary)',
        secondary: 'var(--color-secondary)',
        footer: {
          bg: 'var(--color-footer-bg)',
          text: 'var(--color-footer-text)',
        }
      }
    },
  },
  plugins: [],
}