document.addEventListener('DOMContentLoaded', function () {
	'use strict';

	const wrappers = document.querySelectorAll('.dienstenoverzicht-container');
	const config = window.dienstenoverzichtSettings || {};

	wrappers.forEach(function (wrapper) {
		const results = wrapper.querySelector('.dienstenoverzicht');
		const filterForm = wrapper.querySelector('.faceted-filter');
		const header = wrapper.querySelector('.dienstenoverzicht-header');

		if (!results || !header) {
			return;
		}

		const searchInput = document.createElement('input');
		searchInput.type = 'search';
		searchInput.className = 'search-bar';
		searchInput.placeholder = config.searchPlaceholder || 'Zoek diensten...';
		searchInput.setAttribute('aria-label', config.searchLabel || 'Zoek diensten');
		header.appendChild(searchInput);

		const noResultsMessage = document.createElement('p');
		noResultsMessage.className = 'no-results dienstenoverzicht-local-no-results';
		noResultsMessage.textContent = config.noResultsText || 'Geen diensten gevonden.';
		noResultsMessage.hidden = true;
		noResultsMessage.setAttribute('aria-live', 'polite');
		results.appendChild(noResultsMessage);

		function filterVisibleCards() {
			const searchTerm = searchInput.value.trim().toLowerCase();
			let found = false;

			results.querySelectorAll('.dienst').forEach(function (item) {
				const title = item.querySelector('.dienst-title');
				const titleText = title ? title.textContent.toLowerCase() : '';
				const isVisible = !searchTerm || titleText.includes(searchTerm);

				item.hidden = !isVisible;

				if (isVisible) {
					found = true;
				}
			});

			noResultsMessage.hidden = found;
		}

		function setResultsHtml(html) {
			results.innerHTML = html;
			results.appendChild(noResultsMessage);
			filterVisibleCards();
		}

		searchInput.addEventListener('input', filterVisibleCards);

		if (!filterForm) {
			return;
		}

		filterForm.addEventListener('change', function () {
			if (!config.ajaxUrl || !config.nonce) {
				return;
			}

			const formData = new FormData(filterForm);
			formData.append('action', 'dienstenoverzicht_filter');
			formData.append('nonce', config.nonce);
			results.setAttribute('aria-busy', 'true');

			fetch(config.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData
			})
				.then(function (response) {
					if (!response.ok) {
						throw new Error('Request failed');
					}

					return response.json();
				})
				.then(function (payload) {
					if (!payload || !payload.success || !payload.data || typeof payload.data.html !== 'string') {
						throw new Error('Invalid response');
					}

					setResultsHtml(payload.data.html);
				})
				.catch(function () {
					results.textContent = '';
					const error = document.createElement('p');
					error.className = 'no-results';
					error.textContent = config.errorText || 'Er ging iets mis bij het filteren.';
					results.appendChild(error);
				})
				.finally(function () {
					results.setAttribute('aria-busy', 'false');
				});
		});
	});
});
