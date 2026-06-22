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

	function escapeHtml(value) {
		return String(value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;');
	}

	function highlightCode() {
		document.querySelectorAll('.code-shell code').forEach(function (node) {
			if (node.dataset.nervHighlighted) {
				return;
			}
			var source = node.textContent || '';
			var pattern = /(\/\*[\s\S]*?\*\/|\/\/[^\n]*|#[^\n]*)|(".*?"|'.*?'|`.*?`)|\b(function|return|const|let|var|if|else|for|foreach|while|class|new|public|private|protected|static|true|false|null|array|echo|import|from|export|async|await)\b|\b(\d+(?:\.\d+)?)\b/g;
			var html = escapeHtml(source).replace(pattern, function (match, comment, string, keyword, number) {
				if (comment) {
					return '<span class="token comment">' + comment + '</span>';
				}
				if (string) {
					return '<span class="token string">' + string + '</span>';
				}
				if (keyword) {
					return '<span class="token keyword">' + keyword + '</span>';
				}
				if (number) {
					return '<span class="token number">' + number + '</span>';
				}
				return match;
			});
			node.innerHTML = html;
			node.dataset.nervHighlighted = '1';
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
	highlightCode();
	setInterval(updateClock, 1000);
})();
