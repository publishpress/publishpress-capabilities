jQuery(document).ready(function ($) {
    // Store original tab order
    var originalTabOrder = [];
    $('.ppc-capabilities-tabs > ul > li').each(function () {
        originalTabOrder.push($(this));
    });

    var globalSearchTimeout;
    $('#pp-global-capability-search').on('input', function () {
        clearTimeout(globalSearchTimeout);
        var searchTerm = $(this).val().toLowerCase().trim();

        if (searchTerm.length < 2) {
            clearGlobalSearch();
            return;
        }

        globalSearchTimeout = setTimeout(function () {
            performGlobalSearch(searchTerm);
        }, 300);
    });

    function performGlobalSearch(searchTerm) {
        var searchTerms = searchTerm.split(' ');
        var tabCounts = {};
        var firstTabWithMatch = null;
        var currentTab = $('.ppc-capabilities-tab-active').attr('data-slug');
        var tabsWithResults = [];

        // Hide individual filter inputs when global search is active
        if (searchTerm.length >= 2) {
            $('.ppc-filter-wrapper').hide();
        } else {
            $('.ppc-filter-wrapper').show();
        }

        // Search through all tabs
        $('.ppc-capabilities-tabs li').each(function () {
            var tabSlug = $(this).attr('data-slug');
            // Handle special cases for tab content IDs
            var tabContentId;
            if (tabSlug === 'taxonomies') {
                tabContentId = 'cme-cap-type-tables-' + tabSlug + '-taxonomy';
            } else if (tabSlug === 'revision-status') {
                tabContentId = 'cme-cap-type-tables-' + tabSlug;
            } else {
                tabContentId = 'cme-cap-type-tables-' + tabSlug;
            }
            var tabContent = $('#' + tabContentId);
            var matchCount = 0;

            if (tabContent.length > 0) {
                // Hide all rows first
                tabContent.find('tr').hide();
                tabContent.find('thead tr').show(); // Keep header visible
                tabContent.find('tr.cme-bulk-select').hide(); // Hide bulk rows

                // Search through all capability rows in this tab
                tabContent.find('tr').each(function () {
                    var $row = $(this);
                    var capabilityLabel = '';
                    var capabilityName = '';

                    // Handle different structures for different tabs
                    if (tabSlug === 'revision-status') {
                        // Special handling for revision status tab
                        capabilityLabel = $row.find('.status-label a').text().toLowerCase();
                        capabilityName = $row.find('.tool-tip-text strong').text().toLowerCase();
                    } else {
                        // Standard handling for other tabs
                        capabilityLabel = $row.find('.cap_type').text().toLowerCase();
                        capabilityName = $row.find('span:not(.ppc-tool-tip)').text().toLowerCase();
                    }

                    var capabilityText = capabilityLabel + ' ' + capabilityName;

                    // Skip empty rows and bulk rows
                    if (capabilityText.trim() === '' || $row.hasClass('cme-bulk-select')) {
                        return;
                    }

                    // Check if all search terms match (with underscore handling)
                    var allTermsMatch = true;
                    for (var i = 0; i < searchTerms.length; i++) {
                        var term = searchTerms[i];
                        var termWithSpaces = term.replace(/_/g, ' ');
                        var termWithUnderscores = term.replace(/ /g, '_');

                        // Check original term, spaces->underscores, and underscores->spaces
                        if (capabilityText.indexOf(term) === -1 &&
                            capabilityText.indexOf(termWithSpaces) === -1 &&
                            capabilityText.indexOf(termWithUnderscores) === -1) {
                            allTermsMatch = false;
                            break;
                        }
                    }

                    if (allTermsMatch) {
                        $row.show();
                        matchCount++;
                    }
                });

                // Show/hide no results message
                var noResultsDiv = tabContent.siblings('.ppc-filter-no-results');
                if (matchCount === 0) {
                    noResultsDiv.show();
                } else {
                    noResultsDiv.hide();
                }
            }

            tabCounts[tabSlug] = matchCount;
            updateTabWithCount($(this), matchCount);

            if (matchCount > 0) {
                tabsWithResults.push($(this));
                if (firstTabWithMatch === null) {
                    firstTabWithMatch = tabSlug;
                }
            }
        });

        reorderTabs(tabsWithResults);

        if (firstTabWithMatch !== null && tabCounts[currentTab] === 0) {
            setTimeout(function () {
                $('.ppc-capabilities-tabs li[data-slug="' + firstTabWithMatch + '"]').trigger('click');
            }, 50);
        }

        updateSearchSummary(tabCounts, searchTerm);
    }

    function reorderTabs(tabsWithResults) {
        var $tabList = $('.ppc-capabilities-tabs > ul');
        var allTabs = [];

        // Collect all tabs as DOM elements
        $tabList.children('li').each(function () {
            allTabs.push(this);
        });

        // Convert tabsWithResults to DOM elements for comparison
        var tabsWithResultsDOM = tabsWithResults.map(function ($tab) {
            return $tab[0];
        });

        // Separate tabs with results and without
        var tabsWithoutResults = allTabs.filter(function (tab) {
            return tabsWithResultsDOM.indexOf(tab) === -1;
        });

        // Clear and rebuild tab list
        $tabList.empty();

        // Add tabs with results first
        tabsWithResultsDOM.forEach(function (tab) {
            $tabList.append(tab);
        });

        // Add tabs without results
        tabsWithoutResults.forEach(function (tab) {
            $tabList.append(tab);
        });
    }

    function updateTabWithCount($tab, count) {
        var originalText = $tab.text().replace(/\s*\(\d+\)$/, '').replace(/\s*\d+$/, '');

        // Remove any existing count
        $tab.find('.search-count').remove();

        if (count > 0) {
            // Add colored count badge
            $tab.append('<span class="search-count">' + count + '</span>');
        }
    }

    function updateSearchSummary(tabCounts, searchTerm) {
        var totalMatches = Object.values(tabCounts).reduce((a, b) => a + b, 0);
        var $summary = $('#pp-search-results-summary');

        if (totalMatches > 0) {
            var tabsWithMatches = Object.keys(tabCounts).filter(key => tabCounts[key] > 0).length;
            $summary.text('Found ' + totalMatches + ' match' + (totalMatches !== 1 ? 'es' : '') +
                ' in ' + tabsWithMatches + ' tab' + (tabsWithMatches !== 1 ? 's' : ''));
        } else {
            $summary.text('No matches found for "' + searchTerm + '"');
        }
    }

    function clearGlobalSearch() {
        // Clear all tab counts
        $('.ppc-capabilities-tabs li').each(function () {
            updateTabWithCount($(this), 0);
        });

        // Clear search summary
        $('#pp-search-results-summary').text('');

        // Show individual filter inputs
        $('.ppc-filter-wrapper').show();

        // Restore original tab order
        var $tabList = $('.ppc-capabilities-tabs > ul');
        $tabList.empty();
        originalTabOrder.forEach(function (tab) {
            $tabList.append(tab);
        });

        // Show all rows in all tabs
        $('.ppc-capabilities-content table tr').show();
        $('.ppc-filter-no-results').hide();

        // Clear all filters
        $('.ppc-filter-text').val('').trigger('input');
    }
});