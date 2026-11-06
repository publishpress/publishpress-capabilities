jQuery(document).ready(function ($) {

  let notice_html = '';
  let initialize_notice = false;

  // Initialize
  updateNoticePanel();

  // Expose toolbar toggle state to assistive technology
  $('#wp-admin-bar-ppc-admin-notices-panel > a').attr({
    'aria-controls': 'ppc-admin-notices-panel',
    'aria-expanded': 'false'
  });

  // Add overlay to the DOM
  $('body').append('<div id="ppc-admin-notices-overlay"></div>');


  // Close panel when clicking overlay
  $('#ppc-admin-notices-overlay').on('click', function () {
    togglePanel();
  });

  // Close panel when pressing Escape key (dismiss an open help tooltip first)
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape') {
      let $open_tips = $('.ppc-tool-tip').filter(function () {
        return $(this).find('.tool-tip-text').is(':visible');
      });
      if ($open_tips.length) {
        $open_tips.addClass('is-dismissed');
        return;
      }
      if ($('#ppc-admin-notices-panel').hasClass('open')) {
        togglePanel();
      }
    }
  });

  // Allow a dismissed help tooltip to show again on the next hover/focus
  $(document).on('mouseleave focusout', '.ppc-tool-tip', function () {
    $(this).removeClass('is-dismissed');
  });

  // Toggle panel
  $(document).on('click', '#wp-admin-bar-ppc-admin-notices-panel a', function (e) {
    e.preventDefault();
    togglePanel();
  });


  // -------------------------------------------------------------
  //   Admin notices tab
  // -------------------------------------------------------------
  $(document).on("change", ".admin-notices-tab .admin-notices-button-group input", function () {
    var current_button = $(this).closest('label');
    var target_value = $(this).val();
    var button_group = current_button.closest('.admin-notices-button-group');
    var hide_selector = button_group.attr('data-hide-selector');

    //remove active class
    button_group.find('label.selected').removeClass('selected');
    //hide all selector
    $(hide_selector).addClass('hidden-element');
    //display current select
    $(target_value).removeClass('hidden-element');
    //add active class to current select
    current_button.addClass('selected');
  });

  // Whitelust/Blacklist action button
  $(document).on('click', '.ppc-panel-notice-item .ppc-notice-action a, .ppc-shown-notice-item .ppc-notice-action a', function (e) {
    e.preventDefault();
    let button = $(this);
    let action_type = button.hasClass('whitelist') ? 'whitelist' : 'blacklist';
    let action_option = button.hasClass('undo') ? 'undo' : 'default';
    let notice_item = button.closest('.ppc-panel-notice-item');
    if (action_type == 'whitelist' && action_option == 'undo') {
      notice_item = button.closest('.ppc-shown-notice-item');
    }
    let notice_id = notice_item.attr('data-notice-id');
    let next_items = notice_item.nextAll('.ppc-panel-notice-item');
    let prev_items = notice_item.prevAll('.ppc-panel-notice-item');
    let status_message = ppcAdminNoticesData.hidden_status;
    if (action_type == 'whitelist' && action_option == 'default') {
      status_message = ppcAdminNoticesData.whitelist_status;
    } else if (action_type == 'blacklist' && action_option == 'default') {
      status_message = ppcAdminNoticesData.blacklist_status;
    }

    notice_item.fadeOut(300);
    // move notice to the right tab
    notice_item.removeClass('active-notices-item whitelisted-notices-item blacklisted-notices-item');
    if (action_type == 'whitelist') {
      if (action_option == 'undo') {
        notice_item.find('.ppc-notice-action .whitelist').removeClass('undo');
        notice_item.find('.ppc-notice-action .whitelist').html(ppcAdminNoticesData.whitelist_label);
        notice_item.removeClass('ppc-shown-notice-item');
        notice_item.wrap('<div class="ppc-panel-notice-item active-notices-item hidden-element" data-notice-id="' + notice_id + '"></div>');
        $('.ppc-admin-notices-panel-content').prepend(notice_item.closest('.ppc-panel-notice-item').prop('outerHTML'));
        notice_item.closest('.ppc-panel-notice-item').remove();
      } else {
        notice_item.find('.ppc-notice-action .whitelist').addClass('undo');
        notice_item.find('.ppc-notice-action .whitelist').html(ppcAdminNoticesData.remove_whitelist_label);
        notice_item.addClass('whitelisted-notices-item hidden-element');
        var new_item = notice_item.clone().children();
        new_item.addClass('ppc-shown-notice-item');
        new_item.attr('data-notice-id', notice_id);
        $('.ppc-admin-notices-selector').prepend(new_item);
        notice_item.remove();
      }
    } else {
      if (action_option == 'undo') {
        notice_item.find('.ppc-notice-action .blacklist').removeClass('undo');
        notice_item.find('.ppc-notice-action .blacklist').html(ppcAdminNoticesData.blacklist_label);
        notice_item.addClass('active-notices-item hidden-element');
      } else {
        notice_item.find('.ppc-notice-action .blacklist').addClass('undo');
        notice_item.find('.ppc-notice-action .blacklist').html(ppcAdminNoticesData.remove_blacklist_label);
        notice_item.addClass('blacklisted-notices-item hidden-element');
      }
    }
    notice_item.fadeIn(300);
    // update all tabs and notice counts
    updateAdminNoticesCounts();

    // announce the result and move focus to the next available notice action
    announceStatus(status_message);
    let $panel = $('#ppc-admin-notices-panel');
    if ($panel.hasClass('open')) {
      let $focus_target = getActionFocusTarget(next_items) || getActionFocusTarget(prev_items);
      ($focus_target || $panel).trigger('focus');
    } else {
      $('#wp-admin-bar-ppc-admin-notices-panel > a').trigger('focus');
    }

    $.post(ajaxurl, {
      'action': 'ppc_admin_notice_action',
      'nonce': ppcAdminNoticesData.nonce,
      'action_type': action_type,
      'action_option': action_option,
      'notice_id': notice_id,
    });
  });

  // Parse notices from hidden container
  function parseNotices() {
    const notices = $('div[id^="message"], div[class*="notice"], div[class*="updated"], div[class*="error"], div[class*="warning"], div[class*="info"]');

    return notices;
  }

  // Update notice count and panel
  function updateNoticePanel() {
    const notices = parseNotices();
    const remove_types = ppcAdminNoticesData.admin_notice_options.notice_type_remove ? ppcAdminNoticesData.admin_notice_options.notice_type_remove : [];
    const panel_types = ppcAdminNoticesData.admin_notice_options.notice_type_display ? ppcAdminNoticesData.admin_notice_options.notice_type_display : [];
    const whitelist_notices = ppcAdminNoticesData.admin_notice_data.whitelist_notices ? ppcAdminNoticesData.admin_notice_data.whitelist_notices : [];
    const blacklist_notices = ppcAdminNoticesData.admin_notice_data.blacklist_notices ? ppcAdminNoticesData.admin_notice_data.blacklist_notices : [];

    if (notices.length === 0 || remove_types == '' || remove_types.length === 0) {
      // show our wrapped notices
      $('.ppc-admin-notices-selector').show();
      // Simply return if no notice type is set for removal or no notice on the screen
      return;
    }

    let success_count = error_count = warning_count = info_count = active_notices_count = blacklisted_notices_count = 0;
    let active_notices = [];
    let whitelisted_notices = [];
    let blacklisted_notices = [];
    let panel_notice_ids = [];

    // Loop all notices on the page to group into active, whitelisted notices
    notices.each(function (index, element) {
      let $el = $(element);
      let notice_id = simpleHashId($el.text().trim());
      let notice_whitelist = whitelist_notices.includes(notice_id);
      let notice_blacklist = blacklist_notices.includes(notice_id);

      if (
        $el.is('.hidden, .hide-if-js, .update-message, [aria-hidden="true"], .woocommerce-message')
        || $el.css('display') === 'none'
      ) {
        // an excluded notice or notice with hidden class or attribute should be skipped cos it could be a dynamic notice
        return true;
      } else if (notice_whitelist) {
        // add whitelist notices to the right list
        whitelisted_notices.push(element);
        panel_notice_ids.push(notice_id);
        return true;
      } else if (notice_blacklist) {
        // add blacklist notices to the right list
        blacklisted_notices.push(element);
        blacklisted_notices_count++;
        panel_notice_ids.push(notice_id);
        return true;
      } else if ($el.is('.notice-success, .updated') && remove_types.includes('success')) {
        // success notice
        active_notices.push(element);
        success_count++;
        // add notice ID if notice type is configured to be display in noice center
        if (panel_types.includes('success')) {
          panel_notice_ids.push(notice_id)
        }
        return true;
      } else if ($el.is('.notice-error, .error') && remove_types.includes('error')) {
        // error notice
        active_notices.push(element);
        error_count++;
        // add notice ID if notice type is configured to be display in noice center
        if (panel_types.includes('error')) {
          panel_notice_ids.push(notice_id)
        }
        return true;
      } else if ($el.is('.notice-warning') && remove_types.includes('warning')) {
        // warning notice
        active_notices.push(element);
        warning_count++;
        // add notice ID if notice type is configured to be display in noice center
        if (panel_types.includes('warning')) {
          panel_notice_ids.push(notice_id)
        }
        return true;
      } else if ($el.is('.notice-info') && remove_types.includes('info')) {
        // info notice
        active_notices.push(element);
        info_count++;
        // add notice ID if notice type is configured to be display in noice center
        if (panel_types.includes('info')) {
          panel_notice_ids.push(notice_id)
        }
        return true;
      } else if ($el.is('#message') && remove_types.includes('success')) {
        // Add other success notice without class but #message id
        active_notices.push(element);
        success_count++;
        // add notice ID if notice type is configured to be display in noice center
        if (panel_types.includes('success')) {
          panel_notice_ids.push(notice_id)
        }
        return true;
      } else if ($el.is('.notice') && remove_types.includes('success')) {
        // let categorize any remaining notice as success notice
        active_notices.push(element);
        success_count++;
        // add notice ID if notice type is configured to be display in noice center
        if (panel_types.includes('success')) {
          panel_notice_ids.push(notice_id)
        }
        return true;
      }
    });

    if (!initialize_notice) {
      var active_tab = false;
      var additional_class = '';
      // add active notices to panel if not empty
      if (active_notices.length > 0) {
        additional_class = active_tab ? 'hidden-element' : '';
        $.each(active_notices, function (index, element) {
          let notice_id = simpleHashId($(element).text().trim());

          if (panel_notice_ids.includes(notice_id)) {
            //if ($(element).is('.notice'))
            {
              let notice_action_html = '<div class="ppc-notice-action">';
              notice_action_html += '<div class="action-item-wrap">' + helpTooltipHtml(ppcAdminNoticesData.whitelist_note, notice_id + '-whitelist') + '<a href="#" class="whitelist">' + ppcAdminNoticesData.whitelist_label + '</a></div>';
              notice_action_html += '<div class="action-item-wrap">' + helpTooltipHtml(ppcAdminNoticesData.blacklist_note, notice_id + '-blacklist') + '<a href="#" class="blacklist">' + ppcAdminNoticesData.blacklist_label + '</a></div>';
              notice_action_html += '</div>';
              $(element).append(notice_action_html);
            }
            notice_html += '<div class="ppc-panel-notice-item active-notices-item ' + additional_class + '" data-notice-id="' + notice_id + '">';
            notice_html += $(element).prop('outerHTML')
            notice_html += '</div>';
          }
          active_notices_count++;
          // all noitces that reached this place needed to be removed even if not included in the notice panel
          $(element).remove();
        });
        active_tab = true;
      }

      // add all whitelisted notices to panel so they can be undo in their tab
      if (whitelisted_notices.length > 0) {
        $.each(whitelisted_notices, function (index, element) {
          let notice_id = simpleHashId($(element).text().trim());

          //if ($(element).is('.notice'))
          {
            let notice_action_html = '<div class="ppc-notice-action">';
            notice_action_html += '<div class="action-item-wrap">' + helpTooltipHtml(ppcAdminNoticesData.whitelist_note, notice_id + '-whitelist') + '<a href="#" class="whitelist undo">' + ppcAdminNoticesData.remove_whitelist_label + '</a></div>';
            notice_action_html += '<div class="action-item-wrap">' + helpTooltipHtml(ppcAdminNoticesData.blacklist_note, notice_id + '-blacklist') + '<a href="#" class="blacklist">' + ppcAdminNoticesData.blacklist_label + '</a></div>';
            notice_action_html += '</div>';
            $(element).addClass('ppc-shown-notice-item').attr('data-notice-id', notice_id);
            $(element).append(notice_action_html);
          }
        });
      }

      // add all blacklisted notices to panel so they can be undo in their tab
      if (blacklisted_notices.length > 0) {
        additional_class = active_tab ? 'hidden-element' : '';
        $.each(blacklisted_notices, function (index, element) {
          let notice_id = simpleHashId($(element).text().trim());

          //if ($(element).is('.notice'))
          {
            let notice_action_html = '<div class="ppc-notice-action">';
            notice_action_html += '<div class="action-item-wrap">' + helpTooltipHtml(ppcAdminNoticesData.whitelist_note, notice_id + '-whitelist') + '<a href="#" class="whitelist">' + ppcAdminNoticesData.whitelist_label + '</a></div>';
            notice_action_html += '<div class="action-item-wrap">' + helpTooltipHtml(ppcAdminNoticesData.blacklist_note, notice_id + '-blacklist') + '<a href="#" class="blacklist undo">' + ppcAdminNoticesData.remove_blacklist_label + '</a></div>';
            notice_action_html += '</div>';
            $(element).append(notice_action_html);
          }
          notice_html += '<div class="ppc-panel-notice-item blacklisted-notices-item ' + additional_class + '" data-notice-id="' + notice_id + '">';
          notice_html += $(element).prop('outerHTML')
          notice_html += '</div>';
          blacklisted_notices_count++;
          $(element).remove();
        });
        active_tab = true;
      }
    }

    updateAdminNoticesCounts(success_count, error_count, warning_count, info_count, active_notices_count, blacklisted_notices_count);
  }

  function updateAdminNoticesCounts(success_count = false, error_count = false, warning_count = false, info_count = false, active_notices_count = false, blacklisted_notices_count = false) {
    // Update toolbar count
    if (!success_count) {
      success_count = $('.ppc-panel-notice-item.active-notices-item .notice-success, .ppc-panel-notice-item.active-notices-item .updated').length;
    }
    if (!error_count) {
      error_count = $('.ppc-panel-notice-item.active-notices-item .notice-error, .ppc-panel-notice-item.active-notices-item .error').length;
    }
    if (!warning_count) {
      warning_count = $('.ppc-panel-notice-item.active-notices-item .notice-warning, .ppc-panel-notice-item.active-notices-item .warning').length;
    }
    if (!info_count) {
      info_count = $('.ppc-panel-notice-item.active-notices-item .notice-info, .ppc-panel-notice-item.active-notices-item .info').length;
    }

    let success_count_html = '<span class="success-counter">' + success_count + '<span class="screen-reader-text"> ' + ppcAdminNoticesData.success_label + '</span></span>';
    let error_count_html = '<span class="error-counter">' + error_count + '<span class="screen-reader-text"> ' + ppcAdminNoticesData.error_label + '</span></span>';
    let warning_count_html = '<span class="warning-counter">' + warning_count + '<span class="screen-reader-text"> ' + ppcAdminNoticesData.warning_label + '</span></span>';
    let info_count_html = '<span class="info-counter">' + info_count + '<span class="screen-reader-text"> ' + ppcAdminNoticesData.info_label + '</span></span>';
    $('#wp-admin-bar-ppc-admin-notices-panel .ppc-admin-notices-count').html(success_count_html + error_count_html + warning_count_html + info_count_html);
    if (success_count > 0) {
      $('.ppc-admin-notices-count .success-counter').show();
    } else {
      $('.ppc-admin-notices-count .success-counter').hide();
    }
    if (error_count > 0) {
      $('.ppc-admin-notices-count .error-counter').show();
    } else {
      $('.ppc-admin-notices-count .error-counter').hide();
    }
    if (warning_count > 0) {
      $('.ppc-admin-notices-count .warning-counter').show();
    } else {
      $('.ppc-admin-notices-count .warning-counter').hide();
    }
    if (info_count > 0) {
      $('.ppc-admin-notices-count .info-counter').show();
    } else {
      $('.ppc-admin-notices-count .info-counter').hide();
    }

    // update tab count/display
    var active_notices_counter = active_notices_count;
    if (!active_notices_count) {
      active_notices_count = $('.ppc-panel-notice-item.active-notices-item').length;
      active_notices_counter = ' (' + active_notices_count + ')';
    }
    var blacklisted_notices_counter = blacklisted_notices_count;
    if (!blacklisted_notices_count) {
      blacklisted_notices_count = $('.ppc-panel-notice-item.blacklisted-notices-item').length;
      blacklisted_notices_counter = ' (' + blacklisted_notices_count + ')';
    }
    $('.admin-notices-tab .active-notices .tab-notice-count').html(active_notices_counter);
    $('.admin-notices-tab .blacklisted-notices .tab-notice-count').html(blacklisted_notices_counter);

    // show/or hide tabs if atleast 2 tabs has notices
    let tab_counts = 0;

    var active_tab = false;
   // $('.admin-notices-tab label').removeClass('selected');
    // active tab show/hide
    if (active_notices_count > 0) {
      $('.admin-notices-tab .active-notices').show();
      if (!active_tab) {
       // $('.admin-notices-tab .active-notices').trigger('click');
        active_tab = true;
      }
      tab_counts++;
    }
    // blacklisted tab show/hide
    if (blacklisted_notices_count > 0) {
      $('.admin-notices-tab .blacklisted-notices').show();
      if (!active_tab) {
        //$('.admin-notices-tab .blacklisted-notices').trigger('click');
        active_tab = true;
      }
      tab_counts++;
    }
    // all tab show/hide
    if (tab_counts > 1 || tab_counts > 0 && active_notices_count === 0) {
      $('.admin-notices-tab').show();
      $('.ppc-admin-notices-panel-none').hide();
    } else if (tab_counts === 0) {
      $('.ppc-admin-notices-panel-none').show();
    } else {
      $('.ppc-admin-notices-panel-none').hide();
    }

    if (!active_tab) {
      $('.admin-notices-tab').hide();
    }

    // show our wrapped notices
    $('.ppc-admin-notices-selector').show();
  }

  // Toggle panel with animation
  function togglePanel() {
    const $panel = $('#ppc-admin-notices-panel');
    const $overlay = $('#ppc-admin-notices-overlay');

    const $toggle = $('#wp-admin-bar-ppc-admin-notices-panel > a');

    if ($panel.hasClass('open')) {
      $panel.removeClass('open');
      $overlay.fadeOut(200);
      $toggle.attr('aria-expanded', 'false').trigger('focus');
    } else {
      if (!initialize_notice) {
        if (notice_html && notice_html !== '') {
          $('#ppc-admin-notices-panel .ppc-admin-notices-panel-content').html(notice_html);
          $('.ppc-admin-notices-panel-none').hide();
          updateAdminNoticesCounts();
        }
        initialize_notice = true;
      }
      $panel.addClass('open');
      $overlay.fadeIn(200);
      $toggle.attr('aria-expanded', 'true');
      $panel.trigger('focus');
    }
  }

  // Help tooltip markup: keyboard focusable trigger described by the tooltip text
  function helpTooltipHtml(note, id_suffix) {
    let tip_id = 'ppc-notice-tip-' + id_suffix;
    return '<div class="ppc-tool-tip down-notice">'
      + '<button type="button" class="ppc-tool-tip-button" aria-describedby="' + tip_id + '">'
      + '<span class="dashicons dashicons-editor-help" aria-hidden="true"></span>'
      + '<span class="screen-reader-text">' + ppcAdminNoticesData.help_label + '</span>'
      + '</button>'
      + '<div class="tool-tip-text" id="' + tip_id + '" role="tooltip"><p>' + note + '</p><i></i></div>'
      + '</div>';
  }

  // First visible notice action link within the given panel items
  function getActionFocusTarget($items) {
    let $target = null;
    $items.each(function () {
      if ($(this).hasClass('hidden-element')) {
        return true;
      }
      let $link = $(this).find('.ppc-notice-action a').filter(':visible').first();
      if ($link.length) {
        $target = $link;
        return false;
      }
    });
    return $target;
  }

  // Announce a message through the polite live region
  function announceStatus(message) {
    let $status = $('.ppc-admin-notices-status');
    $status.text('');
    setTimeout(function () {
      $status.text($('<div>').html(message).text());
    }, 100);
  }

  // Simple Hash Function for Unique ID for admin notices
  function simpleHashId(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
      hash = (hash << 5) - hash + str.charCodeAt(i);
      hash |= 0;
    }
    return Math.abs(hash).toString(16);
  }
});