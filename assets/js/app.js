$(function() {
	"use strict";
	new PerfectScrollbar(".app-container"),
	new PerfectScrollbar(".header-message-list"),
	new PerfectScrollbar(".header-notifications-list"),

	// Check dark mode preference on page load
	$(document).ready(function() {
		// Check if dark mode was previously enabled
		if (localStorage.getItem('darkMode') === 'enabled') {
			$("html").attr("class", "dark-theme");
			$(".dark-mode-icon i").attr("class", "bx bx-sun");
		} else {
			$("html").attr("class", "light-theme");
			$(".dark-mode-icon i").attr("class", "bx bx-moon");
		}
	});

	$(".mobile-search-icon").on("click", function() {
		$(".search-bar").addClass("full-search-bar")
	}),

	$(".search-close").on("click", function() {
		$(".search-bar").removeClass("full-search-bar")
	}),

	$(".mobile-toggle-menu").on("click", function() {
		$(".wrapper").addClass("toggled")
	}),
	
	// UPDATED DARK MODE TOGGLE - Now saves preference
	$(".dark-mode").on("click", function() {
		if($(".dark-mode-icon i").attr("class") == 'bx bx-sun') {
			// Switch to light mode
			$(".dark-mode-icon i").attr("class", "bx bx-moon");
			$("html").attr("class", "light-theme");
			localStorage.setItem('darkMode', 'disabled');
		} else {
			// Switch to dark mode
			$(".dark-mode-icon i").attr("class", "bx bx-sun");
			$("html").attr("class", "dark-theme");
			localStorage.setItem('darkMode', 'enabled');
		}
	}), 

	$(".toggle-icon").click(function() {
		$(".wrapper").hasClass("toggled") ? ($(".wrapper").removeClass("toggled"), $(".sidebar-wrapper").unbind("hover")) : ($(".wrapper").addClass("toggled"), $(".sidebar-wrapper").hover(function() {
			$(".wrapper").addClass("sidebar-hovered")
		}, function() {
			$(".wrapper").removeClass("sidebar-hovered")
		}))
	}),
	
	$(document).ready(function() {
		$(window).on("scroll", function() {
			$(this).scrollTop() > 300 ? $(".back-to-top").fadeIn() : $(".back-to-top").fadeOut()
		}),
		$(".back-to-top").on("click", function() {
			return $("html, body").animate({
				scrollTop: 0
			}, 600), !1
		})
	}),
	
	$(function() {
		for (var e = window.location, o = $(".metismenu li a").filter(function() {
			return this.href == e
		}).addClass("").parent().addClass("mm-active"); o.is("li");) o = o.parent("").addClass("mm-show").parent("").addClass("mm-active")
	}),
	
	$(function() {
		$("#menu").metisMenu()
	}),
	
	$(".chat-toggle-btn").on("click", function() {
		$(".chat-wrapper").toggleClass("chat-toggled")
	}),
	
	$(".email-toggle-btn").on("click", function() {
		$(".email-wrapper").toggleClass("email-toggled")
	}),
	
	$(".compose-mail-btn").on("click", function() {
		$(".compose-mail-popup").show()
	}),
	
	$(".compose-mail-close").on("click", function() {
		$(".compose-mail-popup").hide()
	})
});