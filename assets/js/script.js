document.addEventListener('DOMContentLoaded', function () {
    var toggleBtn = document.getElementById('themeToggle');
    if (!toggleBtn) return;

    var icon = toggleBtn.querySelector('i');

    function updateIcon() {
        var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        icon.classList.toggle('bi-moon-stars-fill', !isDark);
        icon.classList.toggle('bi-sun-fill', isDark);
    }

    updateIcon();

    toggleBtn.addEventListener('click', function () {
        var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        if (isDark) {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
        } else {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
        }
        updateIcon();
    });
});