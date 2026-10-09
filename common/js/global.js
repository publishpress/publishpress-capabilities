jQuery(function ($) {

    /**
     * Focus on input when admin bar button is clicked
     */
    $(document).on('click', '#wp-admin-bar-pp_capabilities_test_user > a', function (e) {
      $('.ppc-test-user-admin-bar-form .search-test-user').trigger("focus");
      return false;
    });

    /**
     * Keep user suggestions visible while a search is entered.
     */
    $(document).on('input change', '.ppc-test-user-admin-bar-form .search-test-user', function () {
      $(this).closest('#wp-admin-bar-pp_capabilities_test_user')
        .toggleClass('ppc-user-search-active', $.trim($(this).val()).length > 0);
    });

    /**
     * Search for user
     */
    $(document).on('click', '.ppc-test-user-admin-bar-form .test-user-btn', function (event) {
      event.preventDefault();
      var button = $(this);

      button.prop('disabled', true);
      button.find('.ppc-test-user-search-spinner').addClass('is-active').show();
      button.find('.search-text').hide();
      $('.ppc-test-user-admin-bar-form .ppc-test-user-search-response').html('');

      var data = {
        'action': 'ppc_search_test_user_by_ajax',
        'search_text': $('.ppc-test-user-admin-bar-form .search-test-user').val(),
        'security': ppCapabilitiesGlobalData.nonce,
      };
      $.post(ajaxurl, data, function (response) {
        button.prop('disabled', false);
        button.find('.ppc-test-user-search-spinner').removeClass('is-active').hide();
        button.find('.search-text').show();
        if (response.content !== '') {
          $('.ppc-test-user-admin-bar-form .ppc-test-user-search-response').html(response.content);
        } else {
          $('.ppc-test-user-admin-bar-form .ppc-test-user-search-response').html(response.message);
        }
      });
    });
    
});
