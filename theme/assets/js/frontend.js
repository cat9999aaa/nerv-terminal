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

	function setupCodeCopy() {
		document.querySelectorAll('.code-shell').forEach(function (shell) {
			var button = shell.querySelector('.code-copy');
			var code = shell.querySelector('code');
			if (!button || !code || button.dataset.nervCopyReady) {
				return;
			}
			button.dataset.nervCopyReady = '1';
			button.addEventListener('click', function () {
				var text = code.textContent || '';
				var done = function () {
					button.textContent = 'COPIED';
					button.classList.add('is-copied');
					window.setTimeout(function () {
						button.textContent = 'COPY';
						button.classList.remove('is-copied');
					}, 1400);
				};
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(text).then(done).catch(function () {});
					return;
				}
				var area = document.createElement('textarea');
				area.value = text;
				area.setAttribute('readonly', 'readonly');
				area.style.position = 'fixed';
				area.style.left = '-9999px';
				document.body.appendChild(area);
				area.select();
				try {
					document.execCommand('copy');
					done();
				} catch (error) {}
				document.body.removeChild(area);
			});
		});
	}

	function setupExternalArticleLinks() {
		document.querySelectorAll('.nerv-entry-content a[href]').forEach(function (link) {
			if (link.dataset.nervExternalReady) {
				return;
			}
			link.dataset.nervExternalReady = '1';
			if (link.protocol === 'http:' || link.protocol === 'https:') {
				if (link.origin !== window.location.origin) {
					link.target = '_blank';
					var rel = (link.getAttribute('rel') || '').split(/\s+/).filter(Boolean);
					['noopener', 'noreferrer'].forEach(function (token) {
						if (rel.indexOf(token) === -1) {
							rel.push(token);
						}
					});
					link.setAttribute('rel', rel.join(' '));
				}
			}
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
	setupCodeCopy();
	setupExternalArticleLinks();
	setInterval(updateClock, 1000);
})();
