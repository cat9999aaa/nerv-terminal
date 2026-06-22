(function () {
	'use strict';

	function pad(value) {
		return String(value).padStart(2, '0');
	}

	function updateClock() {
		var now = new Date();
		var hms = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
		var hm = pad(now.getHours()) + ':' + pad(now.getMinutes());

		document.querySelectorAll('[data-nerv-clock]').forEach(function (node) {
			node.textContent = hms;
		});
		document.querySelectorAll('[data-nerv-clock-short]').forEach(function (node) {
			node.textContent = hm;
		});
		document.querySelectorAll('[data-nerv-log-time]').forEach(function (node) {
			var offset = Number(node.getAttribute('data-offset') || 0);
			var date = new Date(now.getTime() - offset * 180000);
			node.textContent = pad(date.getHours()) + ':' + pad(date.getMinutes()) + ':' + pad(date.getSeconds());
		});
	}

	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	if (!reduceMotion && 'startViewTransition' in document) {
		document.addEventListener('click', function (event) {
			var link = event.target.closest('.nerv-mobile-tabs a');
			if (!link || link.origin !== location.origin || event.defaultPrevented) {
				return;
			}
			event.preventDefault();
			document.startViewTransition(function () {
				location.href = link.href;
			});
		});
	}

	updateClock();
	setInterval(updateClock, 1000);
})();
