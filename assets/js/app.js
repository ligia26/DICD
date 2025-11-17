$(function () {
    "use strict";

    // Perfect Scrollbars
    new PerfectScrollbar(".app-container");
    new PerfectScrollbar(".header-message-list");
    new PerfectScrollbar(".header-notifications-list");

    // --- START: CONSOLIDATED THEME LOGIC ---
    function applyTheme(isDark) {
        // Apply theme to HTML and BODY
        $("html, body")
            .removeClass("dark-theme light-theme")
            .addClass(isDark ? "dark-theme" : "light-theme");

        // Set Bootstrap’s theme attribute
        $("html").attr("data-bs-theme", isDark ? "dark" : "light");

        // Update the icon
        $(".dark-mode-icon i")
            .removeClass("bx-sun bx-moon")
            .addClass(isDark ? "bx-sun" : "bx-moon");
    }

    // 1. Apply theme on page load
    $(document).ready(function() {
        const isDark = localStorage.getItem('darkMode') === 'enabled';
        applyTheme(isDark);
    });

    // 2. Apply theme on click
    $(".dark-mode").on("click", function() {
        const isDark = $("html").hasClass("dark-theme");
        const newMode = !isDark;

        applyTheme(newMode);
        localStorage.setItem("darkMode", newMode ? "enabled" : "disabled");
    });
    // --- END: CONSOLIDATED THEME LOGIC ---


    // SEARCH BAR MOBILE
    $(".mobile-search-icon").on("click", function () {
        $(".search-bar").addClass("full-search-bar");
    });
    $(".search-close").on("click", function () {
        $(".search-bar").removeClass("full-search-bar");
    });

   
    $(".mobile-toggle-menu").on("click", function () {
        $(".wrapper").addClass("toggled");
    });

    // SIDEBAR TOGGLE
    $(".toggle-icon").click(function () {
        if ($(".wrapper").hasClass("toggled")) {
            // If toggled (open or partially open), close it
            $(".wrapper").removeClass("toggled");
            $(".sidebar-wrapper").unbind("hover");
        } else {
            // If closed, open it
            $(".wrapper").addClass("toggled");
            $(".sidebar-wrapper").hover(
                function () {
                    $(".wrapper").addClass("sidebar-hovered");
                },
                function () {
                    $(".wrapper").removeClass("sidebar-hovered");
                }
            );
        }
    });

    // BACK TO TOP
    $(document).ready(function () {
        $(window).on("scroll", function () {
            $(this).scrollTop() > 300
                ? $(".back-to-top").fadeIn()
                : $(".back-to-top").fadeOut();
        });

        $(".back-to-top").on("click", function () {
            $("html, body").animate({ scrollTop: 0 }, 600);
            return false;
        });
    });

    // ACTIVE MENU ITEM
    $(function () {
        const e = window.location;
        let o = $(".metismenu li a")
            .filter(function () {
                return this.href == e;
            })
            .addClass("")
            .parent()
            .addClass("mm-active");
        for (; o.is("li"); ) o = o.parent("").addClass("mm-show").parent("").addClass("mm-active");
    });

    // METIS MENU INIT
    $(function () {
        $("#menu").metisMenu();
    });

    // CHAT / EMAIL / COMPOSE MAIL HANDLERS
    $(".chat-toggle-btn").on("click", function () {
        $(".chat-wrapper").toggleClass("chat-toggled");
    });

    $(".email-toggle-btn").on("click", function () {
        $(".email-wrapper").toggleClass("email-toggled");
    });

    $(".compose-mail-btn").on("click", function () {
        $(".compose-mail-popup").show();
    });

    $(".compose-mail-close").on("click", function () {
        $(".compose-mail-popup").hide();
    });
});