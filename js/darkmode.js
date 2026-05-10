/**
 * darkmode.js — Dark mode via cookies
 * Le bouton #dm-switch doit être présent dans le HTML (navbar.php)
 */

const DarkMode = (() => {
  const COOKIE_NAME = 'darkmode';
  const COOKIE_DAYS = 365;

  function getCookie(name) {
    const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : null;
  }

  function setCookie(name, value, days) {
    const expires = new Date(Date.now() + days * 864e5).toUTCString();
    document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/; SameSite=Lax`;
  }

  function applyTheme(dark, animate = false) {
    if (!animate) document.documentElement.classList.add('no-transition');
    document.documentElement.classList.toggle('dark-mode', dark);
    if (!animate) {
      requestAnimationFrame(() => requestAnimationFrame(() => {
        document.documentElement.classList.remove('no-transition');
      }));
    }
    const checkbox = document.getElementById('dm-switch');
    if (checkbox) checkbox.checked = dark;
  }

  function bindButton() {
    const checkbox = document.getElementById('dm-switch');
    if (!checkbox) return;

    checkbox.addEventListener('change', (e) => {
      const dark = e.target.checked;
      applyTheme(dark, true);
      setCookie(COOKIE_NAME, dark ? '1' : '0', COOKIE_DAYS);
    });
  }

  function init() {
    // Appliquer le thème immédiatement (avant DOMContentLoaded)
    const saved = getCookie(COOKIE_NAME);
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const dark = saved !== null ? saved === '1' : prefersDark;
    applyTheme(dark, false);

    // Brancher le listener dès que le DOM est prêt
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', bindButton);
    } else {
      bindButton();
    }
  }

  return { init };
})();

DarkMode.init();