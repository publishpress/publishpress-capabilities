(function () {
    'use strict';

    function initGrantedCapabilitySearch() {
        var search = document.getElementById('ppc-granted-capability-search');
        var list = document.getElementById('ppc-granted-capability-list');
        var noResults = document.getElementById('ppc-granted-capability-no-results');

        if (!search || !list || !noResults) {
            return;
        }

        var items = list.querySelectorAll('li[data-ppc-capability-search]');

        search.addEventListener('input', function () {
            var searchTerm = search.value.toLowerCase().trim();
            var visibleCount = 0;

            Array.prototype.forEach.call(items, function (item) {
                var matches = !searchTerm || item.textContent.toLowerCase().indexOf(searchTerm) !== -1;

                item.hidden = !matches;

                if (matches) {
                    visibleCount++;
                }
            });

            noResults.hidden = !searchTerm || visibleCount > 0;
        });
    }

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', initGrantedCapabilitySearch);
    } else {
        initGrantedCapabilitySearch();
    }
}());
