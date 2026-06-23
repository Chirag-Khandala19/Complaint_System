(function() {
    const cookieName = 'preferred_theme';
    const bodyClass = 'dark-mode';
    const btnId = 'themeToggleBtn';
    const dayExpire = 365;

    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    function setCookie(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = `${name}=${value}; expires=${expires.toUTCString()}; path=/`;
    }

    function updateButton(theme) {
        const btn = document.getElementById(btnId);
        if (!btn) return;
        const icon = btn.querySelector('i');
        if (theme === 'dark') {
            btn.classList.remove('btn-outline-dark');
            btn.classList.add('btn-outline-light');
            if (icon) icon.className = 'fas fa-sun';
            btn.title = 'Switch to light theme';
        } else {
            btn.classList.remove('btn-outline-light');
            btn.classList.add('btn-outline-dark');
            if (icon) icon.className = 'fas fa-moon';
            btn.title = 'Switch to dark theme';
        }
    }

    function applyTheme(theme) {
        if (theme === 'dark') {
            document.body.classList.add(bodyClass);
        } else {
            document.body.classList.remove(bodyClass);
        }
        updateButton(theme);
        setCookie(cookieName, theme, dayExpire);
    }

    function initTheme() {
        const savedTheme = getCookie(cookieName);
        if (savedTheme === 'dark' || savedTheme === 'light') {
            applyTheme(savedTheme);
            return;
        }
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyTheme(prefersDark ? 'dark' : 'light');
    }

    function toggleTheme() {
        const current = document.body.classList.contains(bodyClass) ? 'dark' : 'light';
        applyTheme(current === 'dark' ? 'light' : 'dark');
    }

    document.addEventListener('DOMContentLoaded', function() {
        initTheme();
        const btn = document.getElementById(btnId);
        if (btn) btn.addEventListener('click', toggleTheme);
    });
})();
