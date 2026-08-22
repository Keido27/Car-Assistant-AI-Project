/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,jsx}'],
  theme: {
    extend: {
      colors: {
        // "Service bay" palette: steel bay for nav/chrome, warm signal amber
        // for anything needing human attention (handoff, low stock), a quiet
        // teal for confirmed/converted states. Deliberately not the generic
        // cream/terracotta admin-dashboard look.
        bay: {
          950: '#14171B',
          900: '#1B1F24',
          800: '#242930',
          700: '#333A43',
          600: '#4A525D',
          400: '#8A94A0',
          200: '#D7DBE0',
          100: '#EEF0F2',
          50: '#F7F8F9',
        },
        signal: {
          DEFAULT: '#D9822B',
          light: '#F2A65A',
          dim: '#8A5A24',
        },
        confirm: {
          DEFAULT: '#2A6F77',
          light: '#4C9099',
        },
      },
      fontFamily: {
        sans: ['"Inter"', 'system-ui', 'sans-serif'],
        mono: ['"IBM Plex Mono"', 'ui-monospace', 'monospace'],
      },
    },
  },
  plugins: [],
};
