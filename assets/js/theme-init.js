(function () {
    try {
        var savedTheme = window.localStorage ? window.localStorage.getItem("systemMonitoringTheme") : null;

        if (savedTheme === "dark") {
            document.documentElement.classList.add("dark-theme");
        }

        var savedSidebar = window.localStorage ? window.localStorage.getItem("systemMonitoringSidebar") : null;
        if (savedSidebar === "collapsed") {
            document.documentElement.classList.add("sidebar-collapsed");
        }
    } catch (error) {
    }
}());
