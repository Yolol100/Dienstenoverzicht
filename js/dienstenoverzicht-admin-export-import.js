document.addEventListener('DOMContentLoaded', function () {
	'use strict';

	const search = document.getElementById('diensten-search');
	const selectAll = document.getElementById('select-all-dienstenoverzicht');
	const checkboxes = Array.from(document.querySelectorAll('.dienst-checkbox'));
	const submitButton = document.getElementById('export-selected-button-dienstenoverzicht');

	function updateSubmitState() {
		if (submitButton) {
			submitButton.disabled = !checkboxes.some(function (checkbox) {
				return checkbox.checked;
			});
		}
	}

	function visibleRows() {
		return Array.from(document.querySelectorAll('.dienstenoverzicht-table-dienstenoverzicht tbody tr')).filter(function (row) {
			return !row.hidden;
		});
	}

	if (search) {
		search.addEventListener('input', function () {
			const filter = search.value.trim().toLowerCase();

			document.querySelectorAll('.dienstenoverzicht-table-dienstenoverzicht tbody tr').forEach(function (row) {
				const title = row.querySelector('.dienst-title');
				const titleText = title ? title.textContent.toLowerCase() : '';
				row.hidden = Boolean(filter && !titleText.includes(filter));
			});

			if (selectAll) {
				selectAll.checked = visibleRows().length > 0 && visibleRows().every(function (row) {
					const checkbox = row.querySelector('.dienst-checkbox');
					return checkbox && checkbox.checked;
				});
			}

			updateSubmitState();
		});
	}

	if (selectAll) {
		selectAll.addEventListener('change', function () {
			checkboxes.forEach(function (checkbox) {
				const row = checkbox.closest('tr');

				if (row && !row.hidden) {
					checkbox.checked = selectAll.checked;
				}
			});

			updateSubmitState();
		});
	}

	checkboxes.forEach(function (checkbox) {
		checkbox.addEventListener('change', updateSubmitState);
	});

	updateSubmitState();
});
