(function ($) {
    'use strict';

    $(function () {
        var $form = $('#ppc-admin-columns-form');

        $form.on('click', '.ppc-capabilities-tabs li', function () {
            var $tab = $(this);
            var contentId = $tab.data('content');

            $tab.addClass('ppc-capabilities-tab-active').siblings().removeClass('ppc-capabilities-tab-active');
            $form.find('.admin-columns-content > div').hide();
            $('#' + contentId).show();
            $form.find('input[name="pp_caps_tab"]').val($tab.data('slug'));
        });

        $form.on('change', '.check-all-admin-columns', function () {
            $(this).closest('table').find('tbody input.check-item').prop('checked', this.checked);
        });

        $form.on('change', '.ppc-admin-columns-role', function () {
            var role = $(this).val();
            var tab = $form.find('input[name="pp_caps_tab"]').val();

            $form.find('.ppc-admin-columns-role').prop('disabled', true);
            $form.find('.ppc-admin-columns-submit').hide();
            $form.find('.loading').show();

            window.location = ppCapabilitiesAdminColumns.pageUrl
                + '&role=' + encodeURIComponent(role)
                + '&pp_caps_tab=' + encodeURIComponent(tab);
        });
    });
}(jQuery));
