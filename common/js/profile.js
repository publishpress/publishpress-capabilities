jQuery(function ($) {

    /**
     * "Advanced Custom Fields: Extended" plugin changes
     * profile UI completely removing the #profile-page selector.
     */
    var acf_modified_ui        = false;
    var profile_wrapper_div    = '';
    var fields_parent_selector = 'form';
    if ($('#profile-page').length > 0) {
        profile_wrapper_div = '#profile-page';
    } else {
      profile_wrapper_div    = '.wrap';
    }

    if ($('div[class*="acf-column"]').length > 0) {
      acf_modified_ui        = true;
      fields_parent_selector = 'form div[class*="acf-column-"]';
    }

  //we need to add class to headers without class
  $(profile_wrapper_div + ' form :header').each(function () {
    if (!$(this).attr("class")) {
      var new_header_class = cleanUpStrings($(this).text(), '-');
      $(this).addClass(new_header_class);
    }
  });

  //we need to add class to table tr without class
  $(profile_wrapper_div + ' form tr').each(function () {
    if (!$(this).attr("class")) {
      var find_first_title    = $(this).find('th').text();
      var find_second_title  = $(this).find('label').text();
      var class_prefix       = $(this).closest('table').attr('id');
      var new_tr_class      = '';
      if (find_first_title) {
        new_tr_class = cleanUpStrings(find_first_title, '-');
      } else if (find_second_title) {
        new_tr_class = cleanUpStrings(find_second_title, '-');
      }
      if (new_tr_class) {
        if (class_prefix) {
          new_tr_class = class_prefix + '-' + new_tr_class;
          new_tr_class = new_tr_class.toLowerCase();
        }
        $(this).addClass(new_tr_class);
      }
    }
  });

  /**
   * Only run the below code if it's profile element update request
   */
  if (window.location.href.indexOf("ppc_profile_element") > -1 && Number(getUrlParameter('ppc_profile_element')) === 1) {

    //add spinner
    $(profile_wrapper_div).after('<div class="ppc-profile-fullpage-loader"></div>');
    //get all page elements
    var element_label_title = '',
        element_th_title    = '',
        single_element      = '',
        element_label       = '',
        page_elements       = [],
        parent_this         = '',
        child_this          = '';

    //add profile page title
    single_element = profile_wrapper_div + ' .wp-heading-inline';
    page_elements[cleanUpStrings(single_element)] =
    {
      'label': ppCapabilitiesProfileData.profile_page_title,
      'elements': single_element,
      'element_type': 'header'
    };

    // add acf nickname and permalink field
    if (acf_modified_ui) {
      single_element = profile_wrapper_div + ' #titlediv #titlewrap input';
      page_elements[cleanUpStrings(single_element)] =
      {
        'label': ucWords($(single_element).attr('name')),
        'elements': single_element,
        'element_type': 'header'
      };
      single_element = profile_wrapper_div + ' #titlediv #edit-slug-box';
      page_elements[cleanUpStrings(single_element)] =
      {
        'label': ucWords($(single_element + ' strong').html()),
        'elements': single_element,
        'element_type': 'header'
      };
    }

    //loop through all profile form parents
    $(profile_wrapper_div + ' ' + fields_parent_selector).children().each(function () {
      parent_this = $(this);

      var processTableRows = function(table_container) {
        table_container.find('tr').each(function () {
          child_this = $(this);
          single_element = cleanTextWhiteSpace(child_this.attr("class"), '.');
          if (single_element) {
            single_element      = profile_wrapper_div + ' .' + single_element;
            element_th_title    = child_this.find('th').first().text();
            element_label_title = child_this.find('label').first().text();
            if (element_th_title) {
              element_label = element_th_title;
            } else if (element_label_title) {
              element_label = element_label_title;
            } else {
              element_label = single_element;
            }
            element_label = element_label.trim();
            if (element_label !== '' && typeof element_label !== 'undefined') {
              page_elements[cleanUpStrings(single_element)] =
              {
                'label': element_label,
                'elements': single_element,
                'element_type': 'field'
              };
            }
          }
        });
      };

      //Make direct entry for page headers
      if (parent_this.is("h1,h2,h3,h4,h5,h6")) {
        //we already added class to all headers for efficiency
        single_element = profile_wrapper_div + ' .'  + cleanTextWhiteSpace(parent_this.attr("class"), '.');
        element_label  = $(single_element).html();
        //add header element
        page_elements[cleanUpStrings(single_element)] =
        {
          'label': element_label,
          'elements': single_element,
          'element_type': 'header'
        };
      } else if (parent_this.is("table")) {
        processTableRows(parent_this);
      } else if (parent_this.is("div, fieldset, section")) {
        //process parent div
        var parent_class = cleanTextWhiteSpace(parent_this.attr("class"), '.');
        var parent_id = cleanTextWhiteSpace(parent_this.attr("id"));
        if (parent_class) {
          single_element = profile_wrapper_div + ' .'  + parent_class;
        } else if (parent_id) {
          single_element = profile_wrapper_div + ' #' + parent_id;
        } else {
          single_element = '';
        }

        element_label  = parent_this.find(':header').first().html();
        if (!element_label) {
          element_label = parent_this.find('legend').first().text();
        }

        if (single_element && element_label !== '' && typeof element_label !== 'undefined') {
          //add whole div element
          page_elements[cleanUpStrings(single_element)] =
          {
            'label': element_label,
            'elements': single_element,
            'element_type': 'section'
          };
        }

        // capture nested table rows (e.g. Two-Factor fieldset rows)
        parent_this.find('table').each(function () {
          processTableRows($(this));
        });

        /**
         * We have two problems
         * 1. Rank math is adding new tr via javascript
         * (/plugins/seo-by-rank-math/includes/admin/class-admin.php)
         * before twitter div and  i can't think of a better solution
         * to handle this yet.
         *
         * 2. The new tr been added via javascript doesn't
         * have class leading to an extra problem as we can't
         * delay our class generation till they load their
         * inline script since the code need to run as early
         * as possible so user doesn't see hidden items for
         * seconds.
         *
         * For now, i'll implement a not so neat solution
         * while we try to improve on user feedback/think
         * of a better solution
         */
        if (single_element && single_element.indexOf('rank-math-metabox-wrap') >= 0) {
          single_element = profile_wrapper_div + ' tr.user-url-wrap + tr';
          element_label = ppCapabilitiesProfileData.rankmath_title;//we can't find the title as it's loading after this function
          page_elements[cleanUpStrings(single_element)] =
          {
            'label': element_label,
            'elements': single_element,
            'element_type': 'header'
          };
        }
      }
    });

    //add update profile button
    single_element = profile_wrapper_div + ' input[name=submit]';
    element_label  = $(single_element).val();
    page_elements[cleanUpStrings(single_element)] =
    {
      'label': element_label,
      'elements': single_element,
      'element_type': 'button'
    };

    //make ajax request to update profile fields elements
    page_elements = Object.assign({}, page_elements);

    var data = {
      'action': 'ppc_update_profile_features_element_by_ajax',
      'security': ppCapabilitiesProfileData.nonce,
      'page_elements': page_elements,
    };

    $.post(ajaxurl, data, function (response) {
      if (response.redirect) {
        window.location.href =  response.redirect;
      }else {
        $('.ppc-profile-fullpage-loader').remove();
      }
    });

  }

  /**
   * Get url parameter value
   * @param {*} sParam
   * @returns
   */
  function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1),
    sURLVariables = sPageURL.split('&'),
    sParameterName,
    i;
   for (i = 0; i < sURLVariables.length; i++) {
     sParameterName = sURLVariables[i].split('=');
     if (sParameterName[0] === sParam) {
       return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
     }
   }
   return false;
  }

  /**
   * Clean up text whitespace
   * @param {*} string
   * @param {*} replacement
   * @returns
   */
  function cleanTextWhiteSpace(string, replacement = '') {

    //return original string if empty
    if (!string || string.length === 0) {
      return string;
    }
    //trim both side whitespace
    string = string.trim();
    //replace multiple space with single space
    string = string.replace(/\s\s+/g, ' ');
    //replace remaining space
    string = string.replace(/ /g, replacement);

    return string;
  }

  /**
   * Clean strings
   * @param {*} string
   * @returns
   */
  function cleanUpStrings(string) {

    string = cleanTextWhiteSpace(string);
    string = string.replace(/\W/g, '');
    return string;
  }

  /**
   * PHP equivalet of ucword
   * @param {*} str
   * @returns
   */
  function ucWords(str) {
    return str.split(' ').map(function(word) {
      return word.charAt(0).toUpperCase() + word.slice(1);
    }).join(' ');
  }

  /**
   * The below codes only apply to multi role edit and
   * when role field is available
   */
  if ($('.user-role-wrap select#role, #createuser select#role').length && Number(ppCapabilitiesProfileData.multi_roles) > 0) {

    var $field = $('.user-role-wrap select#role, #createuser select#role'),
      $newField = $field.clone();

    $newField.attr('name', 'pp_roles[]');
    $newField.attr('id', 'pp_roles');
    $field.after($newField);
    $field.hide();

    // Convert the roles field into multiselect
    $newField.prop('multiple', true);
    $newField.after('<p class="description">' + ppCapabilitiesProfileData.role_description + '</p>');

    // $newField.attr('name', 'role[]');

    // Select additional roles
    $newField.find('option').each(function (i, option) {
      $option = $(option);

      $.each(ppCapabilitiesProfileData.selected_roles, function (i, role) {
        if ($option.val() === role) {
          $option.prop('selected', true);
        }
      });
    });

    /**
     * loop role options and change selected role position
     */
    $.each(ppCapabilitiesProfileData.selected_roles.reverse(), function (i, role) {
      var options = $('#pp_roles option');
      var position = $("#pp_roles option[value='" + role + "']").index();
      $(options[position]).insertBefore(options.eq(0));
    });

    //add hidden option as first option to enable sorting selection
    $("#pp_roles").prepend('<option style="display:none;"></option>');

    //init chosen.js
    $newField.chosen({
      'width': '25em'
    });

    /**
     * Make role sortable
     */
    $(".user-role-wrap .chosen-choices, #createuser .chosen-choices").sortable();

    /**
     * Force role option re-order before profile form submission
     */
    $('form#your-profile, form#createuser').submit(function () {
      var options = $('#pp_roles option');
      $(".user-role-wrap .chosen-choices .search-choice .search-choice-close, #createuser .chosen-choices .search-choice .search-choice-close").each(function () {
        var select_position = $(this).attr('data-option-array-index');
        $(options[select_position]).insertBefore(options.eq(0));
      });
    });

    /**
     * Add class to chosen container on choice click
     */
    $(document).on('mousedown', '.user-role-wrap .chosen-choices .search-choice, #createuser .chosen-choices .search-choice', function () {
      $(this).closest('.chosen-container').addClass('chosen-choice-click');
    });

    /**
     * Remove chosen container class on click inside input
     */

    $(document).on('mousedown', '.user-role-wrap .chosen-choices, #createuser .chosen-choices', function (e) {
      if (!e.target.parentElement.classList.contains('search-choice')) {
        $(this).closest('.chosen-container').removeClass('chosen-choice-click');
      }
    });
  }

});
