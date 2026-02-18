jQuery(document).ready(function ($) {
  var { __: __, _x: _x, _n: _n, _nx: _nx } = wp.i18n;

  $('a.neg-cap').attr('title', cmeAdmin.negationCaption);
  $('a.neg-type-caps').attr('title', cmeAdmin.typeCapsNegationCaption);
  //$('td.cap-unreg').attr('title',cmeAdmin.typeCapUnregistered);
  $('a.normal-cap').attr('title', cmeAdmin.switchableCaption);
  $('span.cap-x:not([class*="pp-cap-key"])').html(cmeAdmin.capNegated);
  $('table.cme-checklist input[class!="cme-check-all"]').not(':disabled').attr('title', cmeAdmin.chkCaption);

  if ($('.ppc-checkboxes-documentation-link').length > 0) {
    $('.ppc-checkboxes-documentation-link').attr('target', 'blank');
  }
  $('table.cme-checklist a.neg-cap').click(function (e) {
    $(this).closest('td').removeClass('cap-yes').removeClass('cap-no').addClass('cap-neg');

    var cap_name_attr = $(this).parent().find('input[type="checkbox"]').attr('name');
    $(this).after('<input type="hidden" class="cme-negation-input" name="' + cap_name_attr + '" value="" />');

    $('input[name="' + cap_name_attr + '"]').closest('td').removeClass('cap-yes').removeClass('cap-no').addClass('cap-neg');

    if ($(this).closest('tr').hasClass('unfiltered_upload')) {
      $('input[name="caps[upload_files]"]').closest('td').addClass('cap-neg');
      $('input[name="caps[upload_files]"]').closest('td').append('<input type="hidden" class="cme-negation-input" name="caps[upload_files]" value="" />');
      $('input[name="caps[upload_files]"]').parent().next('a.neg-cap:visible').click();
    }

    return false;
  });

  //$('table.cme-typecaps span.cap-x,table.cme-checklist span.cap-x,table.cme-checklist td.cap-neg span').live( 'click', function(e) {
  $(document).on('click', 'table.cme-typecaps span.cap-x,table.cme-checklist span.cap-x,table.cme-checklist td.cap-neg span', function (e) {
    $(this).closest('td').removeClass('cap-neg').removeClass('cap-yes').addClass('cap-no');
    $(this).closest('td').find('input[type="checkbox"]').prop('checked', false);
    $(this).closest('td').find('input.cme-negation-input').remove();

    // Also apply for any other checkboxes with the same name
    var cap_name_attr = $(this).next('input[type="checkbox"]').attr('name');

    if (!cap_name_attr) {
      cap_name_attr = $(this).next('label').find('input[type="checkbox"]').attr('name');
    }

    $('input[name="' + cap_name_attr + '"]').closest('td').removeClass('cap-neg').removeClass('cap-yes').addClass('cap-no');
    $('input[name="' + cap_name_attr + '"]').prop('checked', false).closest('td').find('input.cme-negation-input').remove();

    if ($(this).closest('td').hasClass('capability-checkbox-rotate')) {
      $(this).closest('td').find('input[type="checkbox"]').prop('checked', true);

      if ($(this).closest('td').hasClass('upload_files')) {
        $('tr.unfiltered_upload').find('td').removeClass('cap-neg').removeClass('cap-yes').addClass('cap-no');
        $('tr.unfiltered_upload').find('input[type="checkbox"]').prop('checked', false);
        $('tr.unfiltered_upload').find('input.cme-negation-input').remove();
        $('input[name="caps[unfiltered_upload]"]').closest('td').removeClass('cap-neg').removeClass('cap-yes').addClass('cap-no');
        $('input[name="caps[unfiltered_upload]"]').prop('checked', true).closest('td').find('input.cme-negation-input').remove();
      }
    }

    if ($(this).closest('td').find('input[type="checkbox"]').hasClass('pp-single-action-rotate')) {
      $(this).closest('td').find('input[type="checkbox"]').prop('checked', true);
    }

    if ($(this).closest('tr').hasClass('unfiltered_upload')) {
      $('input[name="caps[upload_files]"]').closest('td').removeClass('cap-neg').removeClass('cap-yes').addClass('cap-no');
      $('input[name="caps[upload_files]"]').prop('checked', true).closest('td').find('input.cme-negation-input').remove();
    }
    return false;
  });

  $("#publishpress_caps_form").bind("keypress", function (e) {
    if (e.keyCode == 13) {
      $(document.activeElement).parent().find('input[type="submit"]').first().click();
      return false;
    }
  });

  $('input.cme-check-all').click(function (e) {
    $(this).closest('table').find('input[type="checkbox"][disabled!="disabled"]:visible').prop('checked', $(this).is(":checked"));
  });

  $('a.cme-neg-all').click(function (e) {
    $(this).closest('table').find('a.neg-cap:visible').click();
    return false;
  });

  $('a.cme-switch-all').click(function (e) {
    $(this).closest('table').find('td.cap-neg span').click();
    return false;
  });

  $('table.cme-typecaps a.neg-type-caps').click(function (e) {
    $(this).closest('tr').find('td[class!="cap-neg"]').filter('td[class!="cap-unreg"]').each(function () {
      $(this).addClass('cap-neg');

      var cap_name_attr = $(this).find('input[type="checkbox"]').attr('name');
      $(this).append('<input type="hidden" class="cme-negation-input" name="' + cap_name_attr + '" value="" />');

      $('input[name="' + cap_name_attr + '"]').parent().next('a.neg-cap:visible').click();
    });

    return false;
  });

  //http://stackoverflow.com/users/803925/nbrooks
  $('table.cme-typecaps th').click(function () {
    var columnNo = $(this).index();

    var check_val = !$(this).prop('checked_all');

    if ($(this).hasClass('term-cap'))
      var class_sel = '[class*="term-cap"]';
    else
      var class_sel = '[class*="post-cap"]';

    var chks = $(this).closest("table")
      .find("tr td" + class_sel + ":nth-child(" + (columnNo + 1) + ') input[type="checkbox"]:visible');

    $(chks).each(function (i, e) {
      $('input[name="' + $(this).attr('name') + '"]').prop('checked', check_val);
    });

    $(this).prop('checked_all', check_val);
  });

  $('a.cme-fix-read-cap').click(function () {
    $('input[name="caps[read]"]').prop('checked', true);
    $('input[name="caps[read]"].cme-negation-input').remove();
    $('input[name="SaveRole"]').trigger('click');
    return false;
  });

  /* Filter Edit, Delete and Read capabilities */

  // Fill the <select> extracting the values and labels from the tables
  $('.ppc-filter-select').each(function () {
    var filter = $(this)
    var options = new Array();
    $(this).parent().siblings('table').find('tbody').find('tr').each(function () {
      options.push({
        value: $(this).attr('class'),
        text: $(this).find('.cap_type').text()
      });
    });
    options.forEach(function (option, index) {
      filter.append($('<option>', {
        value: option.value,
        text: option.text
      }));
    });
  });

  // Reset select filters on load
  $('.ppc-filter-select').prop('selectedIndex', 0);

  $('.ppc-filter-select-reset').click(function () {
    $(this).prev('.ppc-filter-select').prop('selectedIndex', 0);
    $(this).parent().siblings('table').find('tr').show(); // Show all the table rows
  });
  $('.ppc-filter-select').change(function () {
    if ($(this).val()) {
      $(this).parent().siblings('table').find('tr').hide();
      $(this).parent().siblings('table').find('thead tr:first-child').show(); // Show the table heading
      $(this).parent().siblings('table').find('tr.' + $(this).val()).show(); // Show only the filtered row
    } else {
      $(this).parent().siblings('table').find('tr').show(); // No value selected; show all the table rows
    }
  });

  /* Filter WordPress core, WooCommerce, Additional capabilities */

  // Reset text filters on load
  $('.ppc-filter-text').val('');

  $('.ppc-filter-text-reset').click(function () {
    $(this).prev('.ppc-filter-text').val('');
    $(this).parent().siblings('table').find('tr').show(); // Show all the table rows
    $(this).parent().siblings('.ppc-filter-no-results').hide(); // Hide "no results" message
  });

  $('.ppc-filter-text').keyup(function () {
    var search_text = $(this).val();
    var search_class = search_text.trim().replace(/\s+/g, '_');
    $(this).parent().siblings('table').find('tr').hide();
    $(this).parent().siblings('table').find('tr[class*="' + search_class + '"]').show(); // Show only the filtered row
    $(this).parent().siblings('table').find('tr.cme-bulk-select').hide(); // Hide bulk row
    if ($(this).val().length === 0) {
      $(this).parent().siblings('table').find('tr').show(); // Show all the table rows
    }
    // Show / Hide the no-results message
    if ($(this).parent().siblings('table').find('tr:visible').length === 0) {
      $(this).parent().siblings('.ppc-filter-no-results').show(); // Show "no results" message
    } else {
      $(this).parent().siblings('.ppc-filter-no-results').hide(); // Hide "no results" message
    }
  });

  /**
     * Tooltip click toggle
     */
  $(document).on('click', '.ppc-tool-tip.click-tooltip', function (event) {
    event.preventDefault();
    $(this).toggleClass('is-active');
  });

  /**
     * Roles tab toggle
     */
  $(document).on('click', '.ppc-roles-tab li', function (event) {
    event.preventDefault();

    var clicked_tab = $(this).attr('data-tab');

    //remove active class from all tabs
    $('.ppc-roles-tab li').removeClass('active');
    //add active class to current tab
    $(this).addClass('active');

    //hide all tabs contents
    $('.pp-roles-tab-tr').hide();
    //show this current tab contents
    $('.pp-roles-' + clicked_tab + '-tab').show();
  });

  /**
     * Redirects tab toggle
     */
  $(document).on('click', '.ppc-redirects-tab li', function (event) {
    event.preventDefault();

    var clicked_tab = $(this).attr('data-tab');

    //remove active class from all tabs
    $('.ppc-redirects-tab li').removeClass('active');
    //add active class to current tab
    $(this).addClass('active');

    //hide all tabs contents
    $('.pp-redirects-tab-tr').hide();
    //show this current tab contents
    $('.pp-redirects-' + clicked_tab + '-tab').show();
  });

  /**
   * Redirects login redirect options
   */
  $(document).on('change', '.login-redirect-option #referer_redirect', function () {
    $('.login-redirect-option .custom-url-wrapper').hide();
    $('.login-redirect-option #custom_redirect').prop('checked', false);
  });

  /**
   * Redirects login redirect options
   */
  $(document).on('change', '.login-redirect-option #custom_redirect', function (event) {
    if ($(this).prop('checked')) {
      $('.login-redirect-option .custom-url-wrapper').show();
    } else {
      $('.login-redirect-option .custom-url-wrapper').hide();
    }
    $('.login-redirect-option #referer_redirect').prop('checked', false);
  });

  /**
   * Role custom url change syc
   */
  $('.pp-roles-internal-links-wrapper .base-input input').on('keyup', function (e) {
    var current_input = $(this);
    var current_wrapper = current_input.closest('.pp-roles-internal-links-wrapper');
    var current_entry = current_input.val();

    current_wrapper.find('.base-input input')
      .attr('data-base', current_entry)
      .attr('data-entry', current_wrapper.find('.base-input input').attr('data-home_url') + current_entry);
  });
  /**
   * Prevent click on custom url base link
   */
  $('.pp-roles-internal-links-wrapper .base-url a').on('click', function (e) {
    e.preventDefault();
    return false;
  });

  /**
  * Role submit required field validation
  */
  $('.pp-capability-roles-wrapper .submit-role-form').on('click', function (e) {

    let error_message = '';
    let error_report = false;
    $('.role-submit-response').html('');

    //add required custom redirect link error message
    if ($('#custom_redirect').prop('checked') && isEmptyOrSpaces($('#login_redirect').val())) {
      error_report = true;
      error_message += '- ' + $('#login_redirect').attr('data-required_message') + '<br />';
    }

    //add custom url validation warning
    $('.pp-roles-internal-links-wrapper .base-input input').each(function () {
      var base_url = $(this).attr('data-base');
      if (!isEmptyOrSpaces(base_url) && base_url.includes('://')) {
        error_report = true;
        error_message += '- ' + $(this).attr('data-message') + '<br />';
      }
    });

    //add allowed editor option validation
    if ($('.allowed-editor-toggle').prop('checked') && $('#role_editor-select').val().length === 0) {
      error_report = true;
      error_message += '- ' + $('#role_editor-select').attr('data-message') + '<br />';
    }

    if (error_report) {
      e.preventDefault();
      $('.role-submit-response').html(error_message);
    }

  });

  /**
   * Roles capabilities load more button
   */
  $(document).on('click', '.roles-capabilities-load-more', function (event) {
    event.preventDefault();

    $('.roles-capabilities-load-more').hide();

    $('.roles-capabilities-load-less').show();

    $('ul.pp-roles-capabilities li').show();
  });

  /**
   * Capabilities single box click
   */
  $(document).on('change', '.capability-checkbox-rotate input[type="checkbox"]', function (event) {

    let clicked_box = $(this);
    let mark_box_as_x = false;
    let mark_box_as_checked = false;
    let mark_box_as_unchecked = false;

    if (!clicked_box.prop('checked')) {
      mark_box_as_unchecked = true;
    } else if (clicked_box.prop('checked')) {
      mark_box_as_checked = true;
    }

    if (mark_box_as_checked && clicked_box.hasClass('interacted')) {
      mark_box_as_checked = false;
      mark_box_as_unchecked = false;
      mark_box_as_x = true;
    }

    if (mark_box_as_unchecked) {
      clicked_box.prop('checked', false);
      if (clicked_box.closest('td').hasClass('upload_files')) {
        $('tr.unfiltered_upload').find('input[name="caps[unfiltered_upload]"]').prop('checked', false);
      }
    } else if (mark_box_as_checked) {
      clicked_box.prop('checked', true);
      if (clicked_box.closest('td').hasClass('upload_files')) {
        $('tr.unfiltered_upload').find('td').removeClass('cap-neg').removeClass('cap-yes').addClass('cap-no');
        $('tr.unfiltered_upload').find('input[type="checkbox"]').prop('checked', false);
        $('tr.unfiltered_upload').find('input.cme-negation-input').remove();
        $('input[name="caps[unfiltered_upload]"]').parent().closest('td').removeClass('cap-neg').removeClass('cap-yes').addClass('cap-no');
        $('input[name="caps[unfiltered_upload]"]').prop('checked', true).parent().find('input.cme-negation-input').remove();
        $('tr.unfiltered_upload').find('input[name="caps[unfiltered_upload]"]').prop('checked', true);
      }
    } else if (mark_box_as_x) {
      if (clicked_box.closest('td').hasClass('upload_files')) {
        $('tr.unfiltered_upload').find('a.neg-cap').trigger('click');
      }
      clicked_box.prop('checked', false);
      //perform X action if state is blank
      var box_parent = clicked_box.closest('td');
      box_parent.addClass('cap-neg');
      var cap_name_attr = box_parent.find('input[type="checkbox"]').attr('name');
      box_parent.append('<input type="hidden" class="cme-negation-input" name="' + cap_name_attr + '" value="" />');
      $('input[name="' + cap_name_attr + '"]').parent().next('a.neg-cap:visible').click();
    }
    clicked_box.addClass('interacted');
  });


  $(document).on('click', 'input[name="pp_toggle_all"]', function (event) {
    $(this).closest('table.cme-typecaps').find('input[type="checkbox"]:visible').not('.excluded-input').not('.disabled').prop('checked', $(this).prop('checked'));
  });

  /**
   * Capabilities checkmark rotate
   */
  $(document).on('click', '.pp-row-action-rotate', function (event) {
    event.preventDefault();
    let clicked_box = $(this);
    var checked_fields = false;
    var unchecked_fields = false;
    var all_checkbox = 0;
    var negative_checkbox = 0;

    //determine if we should check or uncheck based on current input state
    clicked_box.closest('tr').find('input[type="checkbox"]:not(.disabled)').each(function () {
      if (!$(this).hasClass('excluded-input') && !$(this).prop('checked')) {
        all_checkbox++;
        unchecked_fields = true;
      } else if (!$(this).hasClass('excluded-input') && $(this).prop('checked')) {
        all_checkbox++;
        checked_fields = true;
      }
      if ($(this).closest('td').hasClass('cap-neg')) {
        negative_checkbox++;
      }
    });

    if ((checked_fields && unchecked_fields) || (negative_checkbox >= all_checkbox)) {
      checked_fields = true;
      unchecked_fields = false;
    } else if (!checked_fields && unchecked_fields && !clicked_box.hasClass('interacted')) {
      checked_fields = true;
      unchecked_fields = false;
    } else if (checked_fields && !unchecked_fields) {
      checked_fields = false;
      unchecked_fields = true;
    } else {
      checked_fields = false;
      unchecked_fields = false;
    }


    if (checked_fields) {
      //perform checked action
      clicked_box.closest('tr').find('td').filter('td[class!="cap-unreg"]').each(function () {
        $(this).closest('td').removeClass('cap-neg').removeClass('cap-yes').addClass('cap-no');
        $(this).parent().find('input[type="checkbox"]:not(.disabled)').prop('checked', true);
        $(this).parent().find('input.cme-negation-input').remove();
        // Also apply for any other checkboxes with the same name
        var cap_name_attr = $(this).next('input[type="checkbox"]').attr('name');

        if (!cap_name_attr) {
          cap_name_attr = $(this).next('label').find('input[type="checkbox"]').attr('name');
        }

        $('input[name="' + cap_name_attr + '"]').parent().closest('td').removeClass('cap-neg').removeClass('cap-yes').addClass('cap-no');
        $('input[name="' + cap_name_attr + '"]').prop('checked', true).parent().find('input.cme-negation-input').remove();
        if ($(this).closest('td').hasClass('upload_files')) {
          $('tr.unfiltered_upload').find('td').removeClass('cap-neg').removeClass('cap-yes').addClass('cap-no');
          $('tr.unfiltered_upload').find('input[type="checkbox"]').prop('checked', false);
          $('tr.unfiltered_upload').find('input.cme-negation-input').remove();
          $('input[name="caps[unfiltered_upload]"]').parent().closest('td').removeClass('cap-neg').removeClass('cap-yes').addClass('cap-no');
          $('input[name="caps[unfiltered_upload]"]').prop('checked', true).parent().find('input.cme-negation-input').remove();
          $('tr.unfiltered_upload').find('input[name="caps[unfiltered_upload]"]').prop('checked', true);
        }
      });
    } else if (unchecked_fields) {
      //perform blank action if state is checked
      clicked_box.closest('tr').find('td').filter('td[class!="cap-unreg"]').each(function () {
        $(this).closest('td').removeClass('cap-neg').removeClass('cap-yes').addClass('cap-no');
        $(this).parent().find('input[type="checkbox"]:not(.disabled)').prop('checked', false);
        $(this).parent().find('input.cme-negation-input').remove();
        // Also apply for any other checkboxes with the same name
        var cap_name_attr = $(this).next('input[type="checkbox"]').attr('name');

        if (!cap_name_attr) {
          cap_name_attr = $(this).next('label').find('input[type="checkbox"]').attr('name');
        }

        $('input[name="' + cap_name_attr + '"]').parent().closest('td').removeClass('cap-neg').removeClass('cap-yes').addClass('cap-no');
        $('input[name="' + cap_name_attr + '"]').prop('checked', false).parent().find('input.cme-negation-input').remove();
        if ($(this).closest('td').hasClass('upload_files')) {
          $('tr.unfiltered_upload').find('input[name="caps[unfiltered_upload]"]').prop('checked', false);
        }
      });
    } else {
      //perform X action if state is blank
      clicked_box.closest('tr').find('td[class!="cap-neg"]').filter('td[class!="cap-unreg"]').each(function () {

        var cap_name_attr = $(this).find('input[type="checkbox"]').attr('name');
        if (cap_name_attr) {
          $(this).addClass('cap-neg');
          $(this).append('<input type="hidden" class="cme-negation-input" name="' + cap_name_attr + '" value="" />');

          $('input[name="' + cap_name_attr + '"]:not(.disabled)').parent().next('a.neg-cap:visible').click();
          if ($(this).closest('td').hasClass('upload_files')) {
            $('tr.unfiltered_upload').find('a.neg-cap').trigger('click');
          }
        }
      });
    }

    clicked_box.addClass('interacted');

  });


  /**
   * unfiltered_upload change sync
   */
  $(document).on('change', 'tr.unfiltered_upload input[name="caps[unfiltered_upload]"]', function (event) {

    let clicked_box = $(this);

    if (clicked_box.prop('checked')) {
      $('input[name="caps[upload_files]"]').parent().closest('td').removeClass('cap-neg').removeClass('cap-yes').addClass('cap-no');
      $('input[name="caps[upload_files]"]').prop('checked', true).parent().find('input.cme-negation-input').remove();
    } else if (!clicked_box.prop('checked')) {
      $('input[name="caps[upload_files]"]').parent().closest('td').removeClass('cap-neg').removeClass('cap-yes').addClass('cap-no');
      $('input[name="caps[upload_files]"]').prop('checked', false).parent().find('input.cme-negation-input').remove();
    }

  });

  /**
   * Other capabilities checkmark rotate
   */
  $(document).on('click', '.pp-single-action-rotate', function (event) {

    let clicked_input = $(this);
    var checked_fields = false;
    var unchecked_fields = false;

    //determine if we should check or uncheck based on current input state
    if (clicked_input.prop('checked')) {
      unchecked_fields = true;
    } else if (!clicked_input.prop('checked')) {
      checked_fields = true;
    }

    if ((checked_fields && unchecked_fields)) {
      checked_fields = true;
      unchecked_fields = false;
    } else if (!checked_fields && unchecked_fields && !clicked_input.hasClass('interacted')) {
      checked_fields = true;
      unchecked_fields = false;
    } else if (checked_fields && !unchecked_fields) {
      checked_fields = false;
      unchecked_fields = true;
    } else {
      checked_fields = false;
      unchecked_fields = false;
    }


    if (!checked_fields && !unchecked_fields) {
      //perform X action if state is blank
      event.preventDefault();
      clicked_input.closest('td').find('a.neg-cap').click();
    }

    clicked_input.addClass('interacted');

    if (navigator.userAgent.toLowerCase().indexOf('firefox') > -1) {
      document.getSelection().empty();
    }
  });


  if ($('.pp-capability-menus-wrapper.profile-features').length > 0) {
    /**
     * Make profile features sortable
     */
    $(".pp-capability-menus-wrapper.profile-features table.pp-capability-menus-select tbody").sortable({
      axis: "y",
      update: function (e, ui) {
        var fields_order = [];
        $('.pp-capability-menus-wrapper.profile-features table.pp-capability-menus-select tbody tr.ppc-sortable-row').each(function () {
          var element_key = $(this).attr('data-element_key');
          if (element_key) {
            fields_order.push(element_key);
          }
        });
        $('.capsman_profile_features_elements_order').val(fields_order.join(","));
      }
    });
  }

  /**
   * Toggle capabilities sidebar panel
   */
  $(document).on('click', '.ppc-sidebar-panel .postbox-header', function () {
    if ($(this).closest('.ppc-sidebar-panel').hasClass('closed')) {
      $(this).closest('.ppc-sidebar-panel').find('.metabox-state').val('opened');
      $(this).closest('.ppc-sidebar-panel').toggleClass('closed');
    } else {
      $(this).closest('.ppc-sidebar-panel').find('.metabox-state').val('closed');
      $(this).closest('.ppc-sidebar-panel').toggleClass('closed');
    }
  });


  // -------------------------------------------------------------
  //   Custom styles new entry
  // -------------------------------------------------------------
  $(document).on("click", ".ppc-button-group label", function () {
    var current_button = $(this);
    var target_value = current_button.find('input').val();
    var button_group = current_button.closest('.ppc-button-group');
    var hide_selector = button_group.attr('data-hide-selector');

    //remove active class
    button_group.find('label.selected').removeClass('selected');
    //hide all selector
    $(hide_selector).addClass('hidden-element');
    //display current select
    $(target_value).removeClass('hidden-element');
    //add active class to current select
    current_button.addClass('selected');
    if (target_value === '.frontend-element-styles') {
      $(".ppc-code-editor-refresh-editor").trigger("click");
    }
  });

  // -------------------------------------------------------------
  //   Frontend elements new entry
  // -------------------------------------------------------------
  $(document).on("click", ".frontend-element-form-submit", function (event) {
    event.preventDefault();
    var button = $(this),
      ajax_action = 'ppc_submit_frontend_element_by_ajax';
    custom_label = $('.frontend-element-new-name').val(),
      custom_element_selector = $('.frontend-element-new-element').val(),
      custom_element_styles = $('.frontendelements-form-styles').val(),
      custom_element_bodyclass = $('.frontendelements-form-bodyclass').val(),
      element_pages = $('.frontend-element-new-element-pages').val(),
      element_post_types = $('.frontend-element-new-element-post-types').val(),
      security = $('.frontend-element-form-nonce').val(),
      item_section = $(this).attr('data-section'),
      item_id = $('.' + item_section + '-form').find('.custom-edit-id').val();

    if ($('input[name="frontend_feature_pages"]:checked').val() === '.frontend-element-whole-site') {
      element_pages = ['whole_site'];
    }

    if (custom_label == '' || (custom_element_selector == '' && custom_element_styles == '' && custom_element_bodyclass == '')) {
      button.closest('tr').find('.ppc-post-features-note').html('<div class="ppc-feature-submit-form-error updated notice error"><p>' + button.attr('data-required') + '</p></div>');
      $(".ppc-feature-submit-form-error").delay(2000).fadeOut('slow');
      return;
    }

    $('.ppc-feature-submit-form-error').remove();
    button.attr('disabled', true);
    button.closest('tr').find(".ppc-feature-post-loader").addClass("is-active");

    var data = {
      'action': ajax_action,
      'security': security,
      'custom_label': custom_label,
      'custom_element_selector': custom_element_selector,
      'custom_element_styles': custom_element_styles,
      'custom_element_bodyclass': custom_element_bodyclass,
      'element_pages': element_pages,
      'element_post_types': element_post_types,
      'item_id': item_id,
    };

    $.post(ajaxurl, data, function (response) {

      if (response.status == 'error') {
        button.closest('tr').find('.ppc-post-features-note').html('<div class="ppc-feature-submit-form-error updated notice error"><p>' + response.message + '</p></div>');
        $(".ppc-feature-submit-form-error").delay(2000).fadeOut('slow');
      } else {
        var parent_table = $('table.frontendelements-table');

        $('.frontend-features-save-button-warning').remove();

        $('.frontend-element-new-name').val('');
        $('.frontend-element-new-element').val('');
        $('.frontendelements-form-bodyclass').val('');
        $(".css-new-element-clear").trigger("click");
        $('.frontend-element-new-element-pages').val([]).trigger('chosen:updated');
        $('.frontend-element-new-element-post-types').val([]).trigger('chosen:updated');

        button.closest('tr').find('.ppc-post-features-note').html('<div class="ppc-feature-submit-form-error updated notice notice-success"><p>' + response.message + '</p></div>');
        $(".ppc-feature-submit-form-error").delay(5000).fadeOut('slow');
        setTimeout(function () {
          $('.ppc-menu-overlay-item').removeClass('ppc-menu-overlay-item');
        }, 5000);

        if (item_id !== '') {
          $('.cancel-custom-item-edit').trigger("click");
          $('.custom-item-' + item_id).replaceWith(response.content);
        } else {
          parent_table.find('.custom-items-table tbody').append(response.content);
          parent_table.find('.temporarily.hidden-element').removeClass('temporarily hidden-element');
        }

        if ($('table.frontendelements-table table.custom-items-table tr.custom-item-row').length > 1) {
          $('table.frontendelements-table .custom-item-toggle-row').removeClass('hidden-element');
        } else {
          $('table.frontendelements-table .custom-item-toggle-row').addClass('hidden-element');
        }

      }

      button.closest('form').find('input[type=submit]').attr('disabled', false);
      button.closest('tr').find(".ppc-feature-post-loader").removeClass("is-active");
      button.attr('disabled', false);

    });
  });


  // -------------------------------------------------------------
  //   Delete frontend features item
  // -------------------------------------------------------------
  $(document).on("click", ".frontend-features-delete-item", function (event) {
    if (confirm(cmeAdmin.deleteWarning)) {
      var item = $(this);
      var item_id = item.attr('data-id');
      var security = item.attr('data-delete-nonce');
      var item_section = item.attr('data-section');

      item.closest('.ppc-menu-row').fadeOut(300);

      var data = {
        'action': 'ppc_delete_frontend_feature_item_by_ajax',
        'security': security,
        'item_id': item_id
      };

      $.post(ajaxurl, data, function (response) {
        if (response.status == 'error') {
          item.closest('.ppc-menu-row').show();
          alert(response.message);
        } else {
          item.closest('.ppc-menu-row').remove();
          if ($('table.' + item_section + '-table table.custom-items-table tr.custom-item-row').length > 1) {
            $('table.' + item_section + '-table .custom-item-toggle-row').removeClass('hidden-element');
          } else {
            $('table.' + item_section + '-table .custom-item-toggle-row').addClass('hidden-element');
          }
        }

      });

    }
  });

  // -------------------------------------------------------------
  //   View custom item
  // -------------------------------------------------------------
  $(document).on("click", ".view-custom-item", function (event) {
    event.preventDefault();
    $(this).closest('.custom-item-row').find('.custom-item-output').toggleClass('show');
  });

  // -------------------------------------------------------------
  //   Edit custom item
  // -------------------------------------------------------------
  $(document).on("click", ".edit-custom-item", function (event) {
    event.preventDefault();

    var item = $(this);
    var item_section = item.attr('data-section');
    var item_id = item.attr('data-id');
    var item_label = item.attr('data-label');
    var item_selector = item.attr('data-selector');
    var item_bodyclass = item.attr('data-bodyclass');
    var item_element = item.attr('data-element');
    var item_pages = '';
    var item_post_types = '';
    var item_form = $('.' + item_section + '-form');

    if (item_id == '') {
      return;
    }

    item_form.find('.' + item_section + '-form-label').val(item_label).trigger('change');
    item_form.find('.editing-custom-item').show();
    item_form.find('.cancel-custom-item-edit').attr('style', 'visibility: visible');
    item_form.find('.editing-custom-item .title').html(item_label);
    item_form.find('.submit-button').html(item_form.find('.submit-button').attr('data-edit'));
    item_form.find('.custom-edit-id').val(item_id);


    if (item_section === 'frontendelements') {
      item.closest('.custom-item-row').find('.css-new-element-update').trigger("click");
      item_form.find('.' + item_section + '-form-element').val(item_selector);
      item_form.find('.' + item_section + '-form-bodyclass').val(item_bodyclass);

      if (item_selector !== '') {
        $('.frontend-element-toggle .ppc-button-group label.element-classes').trigger('click');
      } else if (item_bodyclass !== '') {
        $('.frontend-element-toggle .ppc-button-group label.body-class').trigger('click');
      } else {
        $('.frontend-element-toggle .ppc-button-group label.custom-css').trigger('click');
      }

      //update form pages
      item_pages = item.attr('data-pages');
      item_pages = item_pages.split(', ');
      if (item_pages.includes('whole_site')) {
        $('.frontend-element-toggle .ppc-button-group label.whole-site').trigger('click');
      } else {
        $('.frontend-element-toggle .ppc-button-group label.other-pages').trigger('click');
        var page_items = [];
        item_pages.forEach(function (item_page) {
          page_items.push(item_page);
        });
        item_form.find('.' + item_section + '-form-pages').val(page_items).trigger('chosen:updated');
      }

      //update form post types
      item_post_types = item.attr('data-post-types');
      item_post_types = item_post_types.split(', ');
      var post_types_items = [];
      item_post_types.forEach(function (item_post_type) {
        post_types_items.push(item_post_type);
      });
      item_form.find('.' + item_section + '-form-post-types').val(post_types_items).trigger('chosen:updated');
    } else {
      item_form.find('.' + item_section + '-form-element').val(item_element);
    }

    //scroll to the form
    $([document.documentElement, document.body]).animate({
      scrollTop: item_form.offset().top - 50
    }, 'fast');
  });

  // -------------------------------------------------------------
  //   Cancel custom item edit
  // -------------------------------------------------------------
  $(document).on("click", ".cancel-custom-item-edit", function (event) {
    event.preventDefault();
    var item = $(this);
    var item_section = item.attr('data-section');
    var item_form = $('.' + item_section + '-form');

    item_form.find('.' + item_section + '-form-label').val('');
    item_form.find('.editing-custom-item').hide();
    item_form.find('.cancel-custom-item-edit').attr('style', '');
    item_form.find('.submit-button').html(item_form.find('.submit-button').attr('data-add'));
    item_form.find('.custom-edit-id').val('');
    item_form.find('.' + item_section + '-form-element').val('');

    if (item_section === 'frontendelements') {
      $('.css-new-element-clear').trigger("click");
      item_form.find('.' + item_section + '-form-element').val('');
      item_form.find('.' + item_section + '-form-bodyclass').val('');

      item_form.find('.' + item_section + '-form-pages').val([]).trigger('chosen:updated');
      item_form.find('.' + item_section + '-form-post-types').val([]).trigger('chosen:updated');
    }

    item_form.find('.' + item_section + '-form-label').trigger('change');
  });

  // -------------------------------------------------------------
  //   Lock Frontend Features 'Save changes' button if unsaved custom items exist
  // -------------------------------------------------------------
  $(document).on("keyup paste change", ".frontent-form-field", function (event) {
    var lock_button = false;
    $('.frontend-features-save-button-warning').remove();

    $('.frontent-form-field').each(function () {
      if ($(this).val() !== '' && $(this).val().replace(/\s/g, '').length) {
        lock_button = true;
      }
    });

    if (lock_button) {
      $(this).closest('form').find('input[type=submit]').attr('disabled', true).after('<span class="frontend-features-save-button-warning">' + cmeAdmin.saveWarning + '</span>');
    } else {
      $(this).closest('form').find('input[type=submit]').attr('disabled', false);
    }
  });




  // -------------------------------------------------------------
  //   Admin features hide submenu
  // -------------------------------------------------------------
  $(document).on('change', '.admin-features-setting-field', function (event) {
    var hide_submenu = $('.admin-features-setting-field.hide-submenu').is(':checked');
    var nonce = $('#ppc-admin-features-form #_wpnonce').val();

    if (hide_submenu) {
      $('tr.ppc-menu-row.child-menu.child-subitem').hide();
    } else {
      $('tr.ppc-menu-row.child-menu.child-subitem').show();
    }

    var form_data = {
      action: 'ppc_update_admin_feature_settings',
      nonce: nonce,
      hide_submenu: hide_submenu ? 1 : 0
    };

    ppcTimerStatus('info', __("Updating Settings...", "capability-manager-enhanced"));

    $.ajax({
      url: ajaxurl,
      method: 'POST',
      data: form_data,
      success: function (response) {
        ppcTimerStatus(response.status, response.message);
      }
    });
  });


  // -------------------------------------------------------------
  //   Profile Features Settings
  // -------------------------------------------------------------
  $(document).on('change', '.ppc-profile-features-allow-role', function (event) {
    event.preventDefault();

    var $checkbox = $(this);
    var $button = $('.ppc-profile-features-refresh');
    var $warning = $('.ppc-profile-features-warning');
    var $info = $('.ppc-profile-features-info');
    var enabled = $checkbox.is(':checked') ? 1 : 0;
    var role = $checkbox.data('role') || '';
    var nonce = $('#ppc-profile-features-form input[name="_wpnonce"]').val();

    $checkbox.prop('disabled', true);

    var form_data = {
      action: 'ppc_set_profile_features_role',
      security: nonce,
      role: role,
      enabled: enabled
    };

    ppcTimerStatus('info', __("Updating access...", "capability-manager-enhanced"));

    $.ajax({
      url: ajaxurl,
      method: 'POST',
      data: form_data,
      success: function (response) {
        if (response.status === 'success') {
          ppcTimerStatus('success', response.message);
          if (enabled) {
            $button.prop('disabled', false).removeClass('disabled');
            //$warning.hide();
            $info.show();
          } else {
            $button.prop('disabled', true).addClass('disabled');
            //$warning.show();
            $info.hide();
          }
        } else {
          ppcTimerStatus('error', response.message || __("Error updating access.", "capability-manager-enhanced"));
          $checkbox.prop('checked', !enabled);
        }
      },
      error: function () {
        ppcTimerStatus('error', __("Error updating access.", "capability-manager-enhanced"));
        $checkbox.prop('checked', !enabled);
      },
      complete: function () {
        $checkbox.prop('disabled', false);
      }
    });
  });

  // -------------------------------------------------------------
  //   Profile Features Settings
  // -------------------------------------------------------------
  $(document).on('click', '.ppc-profile-features-refresh', function (event) {
    event.preventDefault();

    var $button = $(this);
    if ($button.prop('disabled')) {
      return;
    }

    var refreshUrl = $button.data('refresh-url');
    $button.prop('disabled', true).addClass('disabled');

    if (refreshUrl) {
      window.location = refreshUrl;
    }
  });


  // -------------------------------------------------------------
  //   Settings sub tab change
  // -------------------------------------------------------------
  $(document).on('change', '.ppc-settings-role-subtab', function (e) {
    e.preventDefault();

    var selectedOption = $(this).find(':selected');
    var current_content = selectedOption.data('content');

    $('.ppc-settings-tab-content').addClass('hidden-element');

    if (current_content) {
      $(current_content).removeClass('hidden-element');
    }
  });


  /* Start COPIED FROM PP BLOCKS */

  $('.dashboard-settings-box--disabled, .slider--disabled').bind('click', function (e) {
    e.preventDefault();
    window.open('https://publishpress.com/links/capabilities-menu');
  });

  $(".dashboard-settings-control .slider").bind("click", function (e) {
    try {
      e.preventDefault();
      if ($(this).hasClass("slider--disabled")) {
        return false;
      }
      var checkbox = $(this).parent().find("input");
      var isChecked = checkbox.is(":checked") ? 1 : 0;
      var newState = isChecked == 1 ? 0 : 1;
      var feature = checkbox.data("feature");
      var slider = checkbox.parent().find(".slider");
      $.ajax({
        url: cmeAdmin.ajaxurl,
        method: "POST",
        data: { action: "save_dashboard_feature_by_ajax", feature: feature, new_state: newState, nonce: cmeAdmin.nonce },
        beforeSend: function () {
          slider.css("opacity", 0.5);
        },
        success: function () {
          newState == 1 ? checkbox.prop("checked", true) : checkbox.prop("checked", false);
          slider.css("opacity", 1);
          switch (feature) {
            case "capabilities":
              ppcDynamicSubmenu("pp-" + feature, newState);
              break;
            default:
              ppcDynamicSubmenu("pp-capabilities-" + feature, newState);
          }
          statusMsgNotification = ppcTimerStatus();
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error(jqXHR.responseText);
          statusMsgNotification = ppcTimerStatus("error");
        },
      });
    } catch (e) {
      console.error(e);
    }
  });

    function ppcTimerStatus(type = "success", message = "") {
    setTimeout(function () {
      var uniqueClass = "ppc-floating-msg-" + Math.round(new Date().getTime() + Math.random() * 100);

      if (message == '') {
        message = type === "success" ? __("Changes saved!", "apability-manager-enhanced") : __(" Error: changes can't be saved.", "apability-manager-enhanced");
      }

      var instances = $(".ppc-floating-status").length;
      $("#wpbody-content").after('<span class="ppc-floating-status ppc-floating-status--' + type + " " + uniqueClass + '">' + message + "</span>");
      $("." + uniqueClass)
        .css("bottom", instances * 45)
        .fadeIn(1e3)
        .delay(1e4)
        .fadeOut(1e3, function () {
          $(this).remove();
        });
    }, 500);
  }

  function ppcDynamicSubmenu(slug, newState) {
    var pMenu = $("#toplevel_page_pp-capabilities-dashboard");
    var cSubmenu = $(pMenu).find("li." + slug + "-menu-item");
    if (cSubmenu.length) {
      newState == 1 ? cSubmenu.removeClass("ppc-hide-menu-item").find("a").removeClass("ppc-hide-menu-item") : cSubmenu.addClass("ppc-hide-menu-item").find("a").addClass("ppc-hide-menu-item");
    }
  }
  /* end COPIED FROM PP BLOCKS */

  function isEmptyOrSpaces(str) {
    return str === null || str.match(/^ *$/) !== null;
  }

});
