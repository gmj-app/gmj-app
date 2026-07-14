<script>
    (() => {
        const accountTheme = @js($accountTheme);
        const storedTheme = localStorage.getItem('theme');
        const browserTheme = storedTheme === 'light' || storedTheme === 'dark' ? storedTheme : null;
        const theme = accountTheme || browserTheme || 'dark';

        document.documentElement.classList.toggle('dark', theme === 'dark');
        document.documentElement.dataset.theme = theme;
    })();
</script>
