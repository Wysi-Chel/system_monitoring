(function () {
    try {
        var savedTheme = window.localStorage
            ? window.localStorage.getItem("micei-theme") || window.localStorage.getItem("systemMonitoringTheme")
            : null;
        var prefersDark = window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;
        var theme = savedTheme === "dark" || savedTheme === "light"
            ? savedTheme
            : (prefersDark ? "dark" : "light");

        document.documentElement.classList.toggle("dark-theme", theme === "dark");
        document.documentElement.dataset.theme = theme;
        document.documentElement.style.colorScheme = theme;

        var savedSidebar = window.localStorage ? window.localStorage.getItem("systemMonitoringSidebar") : null;
        if (savedSidebar === "collapsed") {
            document.documentElement.classList.add("sidebar-collapsed");
        }
    } catch (error) {
    }
}());
