document.addEventListener('DOMContentLoaded', () => {
    // Selecteer relevante DOM-elementen
    const dienstContainer = document.querySelector('.dienstenoverzicht');
    if (!dienstContainer) return; // Stop als de container niet bestaat

    const headerContainer = document.querySelector('.dienstenoverzicht-header');
    const dropArea = document.getElementById('drop-area');
    const { ajaxurl, security } = window.dienstenoverzicht || {};

    if (!ajaxurl || !security) {
        console.error('AJAX URL or security nonce is not defined.');
        return;
    }

    // Maak en voeg de "Geen resultaten" melding toe
    const noResultsMessage = createNoResultsMessage();
    dienstContainer.appendChild(noResultsMessage);

    // Voeg hover effecten toe aan dienstenitems
    addHoverEffectsToDiensten(dienstContainer);

    // Voeg zoekbalk toe aan de header en implementeer filter functionaliteit
    const filterInput = createSearchBar();
    headerContainer.appendChild(filterInput);
    addFilterFunctionality(filterInput, dienstContainer, noResultsMessage);

    // Implementeer faceted search met AJAX filtering
    addFacetedSearchFiltering();

    // Voeg loader toe tijdens pagina lading
    const loader = createLoader();
    dienstContainer.prepend(loader);
    hideLoaderOnLoad(loader);

    // Implementeer drag-and-drop functionaliteit
    if (dropArea) {
        setupDragAndDrop(dropArea);
    }
});

// Functie om de "Geen resultaten" melding te creëren
const createNoResultsMessage = () => {
    const message = document.createElement('p');
    message.className = 'no-results';
    message.textContent = 'Geen diensten gevonden.';
    message.style.display = 'none'; // Verberg de melding standaard
    return message;
};

// Functie om hover effecten toe te voegen aan dienstenitems
const addHoverEffectsToDiensten = (container) => {
    const dienstItems = container.querySelectorAll('.dienst');
    dienstItems.forEach(item => {
        item.addEventListener('mouseenter', () => item.classList.add('hovered'));
        item.addEventListener('mouseleave', () => item.classList.remove('hovered'));
    });
};

// Functie om de zoekbalk te creëren
const createSearchBar = () => {
    const input = document.createElement('input');
    input.type = 'text';
    input.placeholder = 'Zoek diensten...';
    input.className = 'search-bar';
    input.setAttribute('aria-label', 'Zoek diensten'); // Toegankelijkheid
    return input;
};

// Functie om filter functionaliteit toe te voegen aan de zoekbalk
const addFilterFunctionality = (filterInput, container, noResultsMessage) => {
    const debounce = (func, wait) => {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), wait);
        };
    };

    const filterItems = debounce(() => {
        const searchTerm = filterInput.value.trim().toLowerCase();
        let foundAny = false;

        const dienstItems = container.querySelectorAll('.dienst');
        dienstItems.forEach(item => {
            const titleElement = item.querySelector('h3');
            const title = titleElement ? titleElement.textContent.toLowerCase() : '';
            const isVisible = title.includes(searchTerm);

            // Gebruik CSS classes in plaats van inline styles voor betere prestaties
            item.classList.toggle('hidden', !isVisible);
            if (isVisible) {
                foundAny = true;
            }
        });

        // Toon of verberg de geen resultaten melding
        noResultsMessage.style.display = foundAny ? 'none' : 'block';
    }, 300); // Debounce delay in milliseconds

    filterInput.addEventListener('input', filterItems);
};

// Functie om faceted search filtering toe te voegen
const addFacetedSearchFiltering = () => {
    const facetedFilters = document.querySelectorAll('.faceted-filter input, .faceted-filter select');
    facetedFilters.forEach(filter => {
        filter.addEventListener('change', performAjaxFiltering);
    });
};

// Functie om AJAX filtering uit te voeren
const performAjaxFiltering = () => {
    const form = document.querySelector('.faceted-filter');
    if (!form) {
        console.error('Faceted filter form not found.');
        return;
    }

    const formData = new FormData(form);
    formData.append('action', 'dienstenoverzicht_filter');

    const { ajaxurl, security } = window.dienstenoverzicht || {};
    if (security) {
        formData.append('security', security);
    } else {
        console.error('Security nonce is missing.');
        return;
    }

    fetch(ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(data => {
        const resultsContainer = document.getElementById('dienstenoverzicht-results');
        if (resultsContainer) {
            resultsContainer.innerHTML = data;
            // Herinitialiseer eventuele dynamische elementen indien nodig
            reinitializeDynamicElements();
        } else {
            console.warn('Results container not found.');
        }
    })
    .catch(error => {
        console.error('Error during AJAX filtering:', error);
    });
};

// Functie om de loader te creëren
const createLoader = () => {
    const loader = document.createElement('div');
    loader.className = 'dienst-loader';
    loader.textContent = 'Laden...';
    return loader;
};

// Functie om de loader te verbergen wanneer de pagina volledig is geladen
const hideLoaderOnLoad = (loader) => {
    window.addEventListener('load', () => {
        loader.style.display = 'none';
    });
};

// Functie om drag-and-drop functionaliteit in te stellen
const setupDragAndDrop = (dropArea) => {
    const events = ['dragenter', 'dragover', 'dragleave', 'drop'];
    events.forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });

    dropArea.addEventListener('dragenter', () => dropArea.classList.add('hovered'), false);
    dropArea.addEventListener('dragover', () => dropArea.classList.add('hovered'), false);
    dropArea.addEventListener('dragleave', () => dropArea.classList.remove('hovered'), false);
    dropArea.addEventListener('drop', (e) => {
        dropArea.classList.remove('hovered');
        handleDrop(e);
    }, false);
};

// Functie om standaardgedrag te voorkomen
const preventDefaults = (e) => {
    e.preventDefault();
    e.stopPropagation();
};

// Functie om het drop event te verwerken
const handleDrop = (e) => {
    const dt = e.dataTransfer;
    const files = dt.files;
    handleFiles(files);
};

// Functie om bestanden te verwerken
const handleFiles = (files) => {
    // Implement file handling logic here
    [...files].forEach(uploadFile);
};

// Functie om een bestand te uploaden (voorbeeldimplementatie)
const uploadFile = (file) => {
    const { ajaxurl, security } = window.dienstenoverzicht || {};
    const formData = new FormData();
    formData.append('action', 'dienstenoverzicht_upload');
    formData.append('security', security);
    formData.append('file', file);

    fetch(ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('File uploaded successfully:', data);
            // Update UI or give feedback to the user
        } else {
            console.error('File upload failed:', data);
        }
    })
    .catch(error => {
        console.error('Error uploading file:', error);
    });
};

// Functie om dynamische elementen te herinitialiseren na AJAX call
const reinitializeDynamicElements = () => {
    // Herinitialiseer event listeners of andere dynamische elementen
    const dienstContainer = document.querySelector('.dienstenoverzicht');
    if (dienstContainer) {
        addHoverEffectsToDiensten(dienstContainer);
    }
};

// IIFE voor jQuery-specifieke code om de globale scope niet te vervuilen
(function($) {
    $(document).ready(function() {
        $('.color-field').wpColorPicker();
    });
})(jQuery);