(function($) {
    'use strict';

    const AI_SmartSearch = {
        init: function() {
            this.searchInput = $('#ai-smartsearch-input');
            this.searchResults = $('#ai-smartsearch-results');
            this.searchOverlay = $('#ai-smartsearch-overlay');
            this.searchTimeout = null;
            this.minSearchLength = 2;

            this.bindEvents();
        },

        bindEvents: function() {
            // Show search overlay when clicking search icon
            $('.ai-smartsearch-trigger').on('click', this.showSearchOverlay.bind(this));

            // Hide search overlay when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.ai-smartsearch-overlay-content').length) {
                    AI_SmartSearch.hideSearchOverlay();
                }
            });

            // Handle search input
            this.searchInput.on('input', this.handleSearch.bind(this));

            // Handle keyboard navigation
            this.searchInput.on('keydown', this.handleKeyboardNavigation.bind(this));
        },

        showSearchOverlay: function(e) {
            e.preventDefault();
            this.searchOverlay.fadeIn(200);
            this.searchInput.focus();
        },

        hideSearchOverlay: function() {
            this.searchOverlay.fadeOut(200);
            this.searchResults.empty();
            this.searchInput.val('');
        },

        handleSearch: function() {
            const searchTerm = this.searchInput.val().trim();

            // Clear previous timeout
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }

            // Hide results if search term is too short
            if (searchTerm.length < this.minSearchLength) {
                this.searchResults.empty();
                return;
            }

            // Set new timeout
            this.searchTimeout = setTimeout(() => {
                this.performSearch(searchTerm);
            }, 300);
        },

        performSearch: function(searchTerm) {
            $.ajax({
                url: aiSmartSearch.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ai_search',
                    search_term: searchTerm,
                    nonce: aiSmartSearch.nonce
                },
                beforeSend: function() {
                    AI_SmartSearch.searchResults.html('<div class="ai-smartsearch-loading">Searching...</div>');
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        AI_SmartSearch.displayResults(response.data);
                    } else {
                        AI_SmartSearch.searchResults.html('<div class="ai-smartsearch-no-results">No products found</div>');
                    }
                },
                error: function() {
                    AI_SmartSearch.searchResults.html('<div class="ai-smartsearch-error">An error occurred</div>');
                }
            });
        },

        displayResults: function(results) {
            let html = '<ul class="ai-smartsearch-results-list">';
            
            results.forEach(function(product) {
                html += `
                    <li>
                        <a href="${product.url}" class="ai-smartsearch-result-item">
                            <div class="ai-smartsearch-result-image">
                                <img src="${product.image}" alt="${product.title}">
                            </div>
                            <div class="ai-smartsearch-result-content">
                                <h3>${product.title}</h3>
                                <div class="ai-smartsearch-result-price">${product.price}</div>
                            </div>
                        </a>
                    </li>
                `;
            });

            html += '</ul>';

            // Add pro features notice
            if (typeof aiSmartSearch.proEnabled === 'undefined' || !aiSmartSearch.proEnabled) {
                html += `
                    <div class="ai-smartsearch-pro-notice">
                        Upgrade to Pro for advanced AI-powered search with semantic understanding
                    </div>
                `;
            }

            this.searchResults.html(html);
        },

        handleKeyboardNavigation: function(e) {
            const results = this.searchResults.find('li');
            const currentIndex = results.index(results.filter('.active'));

            switch (e.keyCode) {
                case 40: // Down arrow
                    e.preventDefault();
                    if (currentIndex < results.length - 1) {
                        results.removeClass('active');
                        results.eq(currentIndex + 1).addClass('active');
                    }
                    break;

                case 38: // Up arrow
                    e.preventDefault();
                    if (currentIndex > 0) {
                        results.removeClass('active');
                        results.eq(currentIndex - 1).addClass('active');
                    }
                    break;

                case 13: // Enter
                    e.preventDefault();
                    const activeResult = results.filter('.active').find('a');
                    if (activeResult.length) {
                        window.location.href = activeResult.attr('href');
                    }
                    break;

                case 27: // Escape
                    e.preventDefault();
                    this.hideSearchOverlay();
                    break;
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        AI_SmartSearch.init();
    });

})(jQuery); 