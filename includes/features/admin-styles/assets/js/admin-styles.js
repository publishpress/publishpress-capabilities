/**
 * Admin Styles JavaScript for PublishPress Capabilities
 *
 * @package PublishPress\Capabilities
 * @since 2.30.0
 */

(function ($) {
  'use strict';

  /**
   * Sanitize and normalize a CSS stylesheet URL before assigning it to the DOM.
   *
   * @param {string} cssUrl        Raw URL read from the DOM.
   * @param {string} currentScheme Currently selected color scheme.
   * @param {Function|null} isSafe Optional existing validator; if provided, it must return true for safe URLs.
   * @returns {string|null}        A safe URL string or null if the URL should not be used.
   */
  function sanitizeCssUrl(cssUrl, currentScheme, isSafe) {
    if (!cssUrl || typeof cssUrl !== 'string') {
      return null;
    }

    var trimmed = cssUrl.trim();
    if (!trimmed || trimmed === 'false' || trimmed === 'undefined') {
      return null;
    }

    // Optional additional safety check using existing validator, if provided.
    if (typeof isSafe === 'function' && !isSafe(trimmed)) {
      return null;
    }

    // Reject obvious dangerous protocols (e.g., javascript:, data:, vbscript:).
    var lower = trimmed.toLowerCase();
    if (lower.indexOf('javascript:') === 0 ||
      lower.indexOf('data:') === 0 ||
      lower.indexOf('vbscript:') === 0) {
      return null;
    }

    // If URL API is available, ensure protocol is http/https or relative.
    try {
      if (typeof URL === 'function') {
        var parsed = new URL(trimmed, window.location.origin);
        var protocol = parsed.protocol.toLowerCase();
        if (protocol !== 'http:' && protocol !== 'https:') {
          return null;
        }
      }
    } catch (e) {
      // If parsing fails, fall back to basic checks and continue.
    }

    return trimmed;
  }

  var PP_Admin_Styles = {
    currentScheme: null,
    $colorpicker: null,
    $stylesheet: null,

    /**
     * Initialize Admin Styles functionality
     */
    init: function () {
      this.currentScheme = $('#admin_color_scheme').val();
      this.$colorpicker = $('#ppc-admin-color-schemes');
      this.$stylesheet = $('#colors-css');

      this.bindEvents();
      this.initColorPickers();
      this.initCustomFormColorPickers();
      this.initCheckboxes();
      this.initHiddenInputs();
      this.initColorPickerCloseBehavior();
      this.fixIrisPaletteLinks();
      this.registerEventsActions();

      // If user custom style is selected on page load, show the form
      if (this.currentScheme && this.currentScheme.startsWith('ppc-custom-style-')) {
        // Find the selected color option
        var $selectedOption = $('.color-option.selected');
        if ($selectedOption.length) {
          var $editIcon = $selectedOption.find('.custom-style-edit-icon');
          if ($editIcon.length) {
            var styleSlug = $editIcon.data('style');
            var styleName = $editIcon.data('name');
            // Populate and show form for this style
            var scheme = (ppCapabilitiesAdminStyles.colorSchemes && ppCapabilitiesAdminStyles.colorSchemes[styleSlug]) ? ppCapabilitiesAdminStyles.colorSchemes[styleSlug] : null;
            if (scheme) {
              this.populateCustomStyleForm(scheme, styleSlug, styleName, false);
            } else {
              this.showCustomStyleForm(styleSlug, styleName, false);
            }
          }
        }
      }
    },

    /**
     * Populate the custom style form inputs
     */
    populateCustomStyleForm: function (scheme, styleSlug, styleName, scroll) {
      var customStyle = null;
      if (ppCapabilitiesAdminStyles.customStyles && ppCapabilitiesAdminStyles.customStyles[styleSlug]) {
        customStyle = ppCapabilitiesAdminStyles.customStyles[styleSlug];
      }

      if (!customStyle && !scheme) return;

      $('#custom-style-error').hide().html('');

      $('#custom_style_name').val(styleName);
      $('#custom_style_slug').val(styleSlug);
      $('input[name="custom_style_action"]').val('');

      $('.custom-style-color').each(function () {
        var $input = $(this);
        var category = $input.data('category');
        var tab = $input.data('tab');
        var colorKey = $input.data('color-key');
        var value = '';

        if (category === 'general') {
          if (customStyle && customStyle[colorKey]) {
            value = customStyle[colorKey];
          }
        } else {
          if (customStyle && customStyle.element_colors && customStyle.element_colors[tab] && typeof customStyle.element_colors[tab][colorKey] !== 'undefined') {
            value = customStyle.element_colors[tab][colorKey];
          }
        }

        if (typeof value !== 'undefined' && value !== null) {
          $input.val(value);
          var $picker = $input.closest('.wp-picker-container');
          if ($picker.length) {
            if (value) {
              $picker.find('.wp-color-result').css('background-color', value);
            } else {
              $picker.find('.wp-color-result').css('background-color', '');
            }
            if (typeof $input.wpColorPicker === 'function') {
              try { $input.wpColorPicker('color', value || ''); } catch (err) { }
            }
          }
        } else {
          $input.val('');
          var $picker = $input.closest('.wp-picker-container');
          if ($picker.length) {
            $picker.find('.wp-color-result').css('background-color', '');
          }
        }
      });

      // Reset to General tab
      this.resetCustomStyleTabToGeneral();

      // Show form
      this.showCustomStyleForm(styleSlug, styleName, typeof scroll === 'boolean' ? scroll : true);

      // Load the custom style's stylesheet so element colors are applied
      if (ppCapabilitiesAdminStyles.colorSchemes && ppCapabilitiesAdminStyles.colorSchemes[styleSlug]) {
        var schemeUrl = ppCapabilitiesAdminStyles.colorSchemes[styleSlug].url;
        if (schemeUrl && schemeUrl !== 'false') {
          if (this.$stylesheet.length === 0) {
            this.$stylesheet = $('<link rel="stylesheet" id="colors-css" />').appendTo('head');
          }
          this.$stylesheet.attr('href', schemeUrl);
        }
      }

      // Apply preview for this custom style
      var previewColors = {
        base: (customStyle && customStyle.custom_scheme_base) ? customStyle.custom_scheme_base : '',
        text: (customStyle && customStyle.custom_scheme_text) ? customStyle.custom_scheme_text : '',
        highlight: (customStyle && customStyle.custom_scheme_highlight) ? customStyle.custom_scheme_highlight : '',
        notification: (customStyle && customStyle.custom_scheme_notification) ? customStyle.custom_scheme_notification : '',
        background: (customStyle && customStyle.custom_scheme_background) ? customStyle.custom_scheme_background : '',
        element_colors: (customStyle && customStyle.element_colors) ? customStyle.element_colors : {}
      };
      this.applyCustomStylePreview(previewColors);
    },

    /**
     * Register event actions
     */
    registerEventsActions: function () {
      $('#ppc-admin-styles-form input, #ppc-admin-styles-form select').on('keydown', function (e) {
        if (e.keyCode === 13 || e.which === 13) {
          // Check if this is a submit button
          if (!$(this).is(':submit') && !$(this).is('button[type="submit"]')) {
            e.preventDefault();
            return false;
          }
        }
      });
    },

    /**
   * Clear custom style preview when form is hidden
   */
    clearCustomStylePreview: function () {
      $('#ppc-admin-area-preview').remove();

      // If a custom style is currently selected, restore its preview
      if (this.currentScheme && this.currentScheme !== 'fresh') {
        var customStyle = this.getCustomStyleData(this.currentScheme);
        if (customStyle) {
          this.applyCustomStylePreview(customStyle.colors);
        }
      }
    },

    /**
     * Initialize checkboxes
     */
    initCheckboxes: function () {
      // Add checkboxes to color options
      $('.color-option').each(function () {
        var $option = $(this);
        var isChecked = $option.hasClass('selected');

        // Create checkbox element
        var $checkbox = $('<span class="color-checkbox"></span>');
        if (isChecked) {
          $checkbox.addClass('checked');
          $checkbox.html('<span class="dashicons dashicons-yes"></span>');
        }

        // Add checkbox to option
        $option.prepend($checkbox);
      });
    },

    /**
   * Initialize hidden inputs for CSS URLs
   */
    initHiddenInputs: function () {
      // Add hidden inputs for CSS URLs and icon colors
      $('.color-option').each(function () {
        var $option = $(this);
        var scheme = $option.find('input[type="radio"]').val();
        var schemeData = ppCapabilitiesAdminStyles.colorSchemes[scheme];

        if (schemeData && schemeData.url && schemeData.url !== 'false' && schemeData.url !== 'undefined') {
          // Add CSS URL input
          $option.append(
            '<input type="hidden" class="css_url" value="' + schemeData.url + '" />'
          );

          // Add icon colors input
          if (schemeData.icon_colors) {
            $option.append(
              '<input type="hidden" class="icon_colors" value=\'' + JSON.stringify({ icons: schemeData.icon_colors }) + '\' />'
            );
          }
        }
      });
    },

    /**
   * Bind event handlers
   */
    bindEvents: function () {
      // Custom style form tab navigation
      $(document).on('click', '.custom-style-tab', this.handleCustomStyleTabClick);

      // Image upload
      $(document).on('click', '.pp-capabilities-upload-button', this.handleImageUpload);

      // Remove image
      $(document).on('click', '.pp-capabilities-remove-button', this.handleRemoveImage);

      // Role change
      $(document).on('change', '.ppc-admin-styles-role', this.handleRoleChange);

      // Color scheme selection
      $(document).on('click', '.color-option', this.handleSchemeSelection);

      // Custom scheme color change
      $(document).on('change', '.custom-style-color', function () {
        PP_Admin_Styles.handleCustomFormColorChange($(this));
      });

      // Custom style save button
      $(document).on('click', 'input[name="save_custom_style"]', function (e) {
        PP_Admin_Styles.handleSaveCustomStyle(e);
      });

      // Custom style events
      $(document).on('click', '.custom-styles-button', function (e) {
        PP_Admin_Styles.handleCustomStylesButton(e);
      });

      $(document).on('click', '.custom-style-edit-icon', function (e) {
        PP_Admin_Styles.handleEditCustomStyle(e);
      });

      $(document).on('click', '.cancel-custom-style', function (e) {
        PP_Admin_Styles.handleCancelCustomStyle(e);
      });

      $(document).on('click', 'input[name="delete_custom_style"]', function (e) {
        PP_Admin_Styles.handleDeleteCustomStyle(e);
      });

      $(document).on('input', '#custom_style_name', function () {
        PP_Admin_Styles.handleCustomStyleNameChange();
      });

      $(document).on('change', '.custom-style-color', function () {
        PP_Admin_Styles.handleCustomFormColorChange($(this));
      });

      $(document).on('submit', '#ppc-admin-styles-form', function () {
        var $overlay = $('#ppc-admin-styles-overlay');
        if ($overlay.length) {
          $overlay.addClass('is-active').attr('aria-hidden', 'false');
        }
      });
    },

    /**
     * Initialize color pickers
     */
    initColorPickers: function () {
      if (typeof $.fn.wpColorPicker !== 'undefined') {
        $('.pp-capabilities-color-picker').wpColorPicker({
          change: function (event, ui) {
            var $input = $(this);
            if ($input.data('preview')) {
              // Immediate preview update
              var color = ui.color.toString();
              $input.val(color);

              // Apply admin-area inline preview only when appropriate
              if (PP_Admin_Styles.currentScheme && PP_Admin_Styles.currentScheme.indexOf('ppc-custom-style-') === 0) {
                var colors = PP_Admin_Styles.getCustomFormPreviewColors();
                PP_Admin_Styles.applyCustomStylePreview(colors);
              }
            }
          },
          clear: function () {
            var $input = $(this);
            if ($input.data('preview')) {
              // Clear the preview styles
              $('#ppc-admin-area-preview').remove();
            }
          }
        });
      }
    },

    /**
     * Read general custom form colors and return preview object
     */
    getCustomFormPreviewColors: function () {
      var keys = ['custom_scheme_base', 'custom_scheme_text', 'custom_scheme_highlight', 'custom_scheme_notification', 'custom_scheme_background'];
      var result = {
        base: '',
        text: '',
        highlight: '',
        notification: '',
        background: '',
        element_colors: {}
      };

      for (var i = 0; i < keys.length; i++) {
        var key = keys[i];
        var $input = $('.custom-style-color[data-category="general"][data-color-key="' + key + '"]');
        if ($input.length && $input.val()) {
          var val = $input.val();
          if (i === 0) result.base = val;
          else if (i === 1) result.text = val;
          else if (i === 2) result.highlight = val;
          else if (i === 3) result.notification = val;
          else if (i === 4) result.background = val;
        }
      }

      // Collect element colors from form
      var elementColorTabs = ['links', 'tables', 'forms', 'buttons', 'admin_menu', 'admin_bar', 'dashboard_widgets'];
      elementColorTabs.forEach(function (tab) {
        result.element_colors[tab] = {};
        $('.custom-style-color[data-category="element_colors"][data-tab="' + tab + '"]').each(function () {
          var $input = $(this);
          var colorKey = $input.data('color-key');
          var val = $input.val();
          if (val) {
            result.element_colors[tab][colorKey] = val;
          }
        });
      });

      return result;
    },

    /**
     * Handle custom style tab click
     */
    handleCustomStyleTabClick: function (e) {
      e.preventDefault();

      var $tab = $(this);
      var tabTarget = $tab.data('tab');

      // Update active tab
      $('.custom-style-tab').removeClass('nav-tab-active');
      $tab.addClass('nav-tab-active');

      // Show target content, hide others
      $('.custom-style-tab-content').removeClass('active').hide();
      $('#' + tabTarget).addClass('active').show();

      // Initialize color pickers for newly visible color fields
      var $newContent = $('#' + tabTarget);
      $newContent.find('.pp-capabilities-color-picker:not(.wp-picker-container)').each(function () {
        if (typeof $.fn.wpColorPicker !== 'undefined' && !$(this).hasClass('wp-picker-container')) {
          $(this).wpColorPicker({
            change: function (event, ui) {
              PP_Admin_Styles.handleCustomFormColorChange($(this));
            }
          });
        }
      });
    },

    /**
     * Handle image upload using WordPress media library
     */
    handleImageUpload: function (e) {
      e.preventDefault();

      var $button = $(this);
      var target = $button.data('target');

      // Create media frame
      var frame = wp.media({
        title: ppCapabilitiesAdminStyles.labels.selectImage,
        button: {
          text: ppCapabilitiesAdminStyles.labels.useImage
        },
        multiple: false,
        library: {
          type: 'image'
        }
      });

      // When an image is selected
      frame.on('select', function () {
        var attachment = frame.state().get('selection').first().toJSON();
        $('#' + target).val(attachment.url);

        // Trigger change event for any listeners
        $('#' + target).trigger('change');

        // If it's a logo, show preview
        PP_Admin_Styles.showLogoPreview(attachment.url, target);
      });

      // Open the media library
      frame.open();
    },

    /**
     * Show logo preview
     */
    showLogoPreview: function (url, target) {
      if (target === 'admin_logo') {
        // Add new preview
        var $preview = $('<img src="' + url + '" style="max-width: 20px; max-height: 20px; vertical-align: middle; margin-right: 5px;"/>');
        $('.logo-preview').empty();
        $('.logo-preview').append($preview);
      } else if (target === 'admin_favicon') {
        // Add new preview
        var $preview = $('<img src="' + url + '" style="max-width: 20px; max-height: 20px; vertical-align: middle; margin-right: 5px;"/>');
        $('.favicon-preview').empty();
        $('.favicon-preview').append($preview);
      }
    },

    /**
     * Handle remove image
     */
    handleRemoveImage: function (e) {
      e.preventDefault();

      var target = $(this).data('target');
      $('#' + target).val('').trigger('change');

      // Remove logo preview if applicable
      if (target === 'admin_logo') {
        $('.logo-preview').empty();
      } else if (target === 'admin_favicon') {
        $('.favicon-preview').empty();
      }
    },

    /**
     * Handle custom styles button click
     */
    handleCustomStylesButton: function (e) {
      if (e) {
        e.preventDefault();
        e.stopPropagation();
      }

      // Clear error message
      $('#custom-style-error').hide().html('');

      // Reset form for new style
      $('#custom_style_name').val('');
      $('#custom_style_slug').val('new');
      $('input[name="custom_style_action"]').val('');
      $('.custom-link-delete').hide();

      // Determine selected template
      var templateId = $('#ppc-custom-style-template').val();
      var templates = ppCapabilitiesAdminStyles.styleTemplates || {};
      var template = (templateId && templateId !== 'blank' && templates[templateId]) ? templates[templateId] : null;
      var templateName = (template && template.name) ? template.name : '';

      if (templateName) {
        $('#custom_style_name').val(templateName);
      }

      // Reset general color pickers to defaults (empty for Default template)
      var generalDefaults = {
        'custom_scheme_base': '',
        'custom_scheme_text': '',
        'custom_scheme_highlight': '',
        'custom_scheme_notification': '',
        'custom_scheme_background': ''
      };

      var templateColors = template ? this.buildTemplateColors(template) : null;

      // Populate general tab inputs
      $.each(generalDefaults, function (key, value) {
        if (templateColors && templateColors[key]) {
          value = templateColors[key];
        }
        var $input = $('#custom_style_' + key);
        if ($input.length) {
          $input.val(value);
          var $picker = $input.closest('.wp-picker-container');
          if ($picker.length) {
            // Only update color picker visuals if value is not empty
            if (value) {
              $picker.find('.wp-color-result').css('background-color', value);
              // If wpColorPicker instance exists, also update its internal color
              if (typeof $input.wpColorPicker === 'function') {
                try { $input.wpColorPicker('color', value); } catch (err) { }
              }
            } else {
              // Clear the color picker for empty values
              $picker.find('.wp-color-result').css('background-color', '');
              if (typeof $input.wpColorPicker === 'function') {
                try { $input.wpColorPicker('color', ''); } catch (err) { }
              }
            }
          }
        }
      });

      // Populate element-specific inputs
      $('.custom-style-color[data-category="element_colors"]').each(function () {
        var $el = $(this);
        var tab = $el.data('tab');
        var colorKey = $el.data('color-key');
        var value = '';
        if (templateColors && templateColors.element_colors && templateColors.element_colors[tab] && templateColors.element_colors[tab][colorKey]) {
          value = templateColors.element_colors[tab][colorKey];
        }
        $el.val(value);
        var $picker = $el.closest('.wp-picker-container');
        if ($picker.length) {
          $picker.find('.wp-color-result').css('background-color', value);
          if (value && typeof $el.wpColorPicker === 'function') {
            try { $el.wpColorPicker('color', value); } catch (err) { }
          }
        }
      });

      // Reset to General tab
      this.resetCustomStyleTabToGeneral();

      // Remove any inline preview and clear custom stylesheet href so preview is clean
      $('#ppc-admin-area-preview').remove();
      if (PP_Admin_Styles.$stylesheet && PP_Admin_Styles.$stylesheet.length) {
        PP_Admin_Styles.$stylesheet.removeAttr('href');
      }

      // Apply initial preview only if template has colors
      if (templateColors && templateColors.custom_scheme_base) {
        var defaultPreviewColors = {
          base: templateColors.custom_scheme_base,
          text: templateColors.custom_scheme_text || '',
          highlight: templateColors.custom_scheme_highlight || '',
          notification: templateColors.custom_scheme_notification || '',
          background: templateColors.custom_scheme_background || '',
          element_colors: templateColors.element_colors || {}
        };
        PP_Admin_Styles.applyCustomStylePreview(defaultPreviewColors);
      }

      // Show the custom style form
      PP_Admin_Styles.showCustomStyleForm('new', templateName || '');
    },

    /**
     * Build full template colors (general + element colors)
     */
    buildTemplateColors: function (template) {
      if (!template || !template.palette) {
        return null;
      }

      var palette = $.extend({}, template.palette);
      if (!palette.base_dark) {
        palette.base_dark = this.darkenColor(palette.base, 10);
      }
      if (!palette.highlight_dark) {
        palette.highlight_dark = this.darkenColor(palette.highlight, 10);
      }

      var mapping = {
        links: {
          link_default: 'accent',
          link_hover: 'highlight',
          link_delete: 'danger',
          link_trash: 'danger',
          link_spam: 'danger',
          link_inactive: 'muted'
        },
        tables: {
          table_header_bg: 'surface',
          table_header_text: 'text',
          table_row_bg: 'surface',
          table_row_color: 'text',
          table_row_hover_bg: 'surface_alt',
          table_border: 'border',
          table_alt_row_bg: 'surface_alt',
          table_alt_row_color: 'text'
        },
        forms: {
          input_border: 'border',
          input_focus_border: 'highlight',
          input_background: 'surface',
          input_text: 'text',
          input_placeholder: 'muted'
        },
        buttons: {
          button_primary_bg: 'highlight',
          button_primary_text: 'text',
          button_primary_hover_bg: 'highlight_dark',
          button_secondary_bg: 'surface',
          button_secondary_text: 'text',
          button_secondary_hover_bg: 'surface_alt'
        },
        admin_menu: {
          menu_bg: 'base',
          menu_text: 'text',
          menu_icon: 'text',
          menu_hover_bg: 'highlight',
          menu_hover_text: 'text',
          menu_current_bg: 'highlight',
          menu_current_text: 'text',
          menu_submenu_bg: 'base_dark',
          menu_submenu_text: 'text'
        },
        admin_bar: {
          adminbar_bg: 'base',
          adminbar_text: 'text',
          adminbar_icon: 'text',
          adminbar_hover_bg: 'highlight',
          adminbar_hover_text: 'text'
        },
        dashboard_widgets: {
          widget_bg: '',
          widget_border: '',
          widget_header_bg: '',
          widget_title_text: '',
          widget_body_text: '',
          widget_link: '',
          widget_link_hover: ''
        }
      };

      var elementColors = {};
      $.each(mapping, function (tab, tabMap) {
        elementColors[tab] = {};
        $.each(tabMap, function (key, paletteKey) {
          elementColors[tab][key] = palette[paletteKey] || '';
        });
      });

      if (elementColors.buttons) {
        elementColors.buttons.button_primary_text = this.getReadableTextColor(elementColors.buttons.button_primary_bg);
        elementColors.buttons.button_secondary_text = this.getReadableTextColor(elementColors.buttons.button_secondary_bg);
      }

      if (elementColors.forms) {
        elementColors.forms.input_text = this.getReadableTextColor(elementColors.forms.input_background);
        elementColors.forms.input_placeholder = this.getMutedTextColor(elementColors.forms.input_background);
      }

      if (elementColors.tables) {
        elementColors.tables.table_header_text = this.getReadableTextColor(elementColors.tables.table_header_bg);
      }

      if (elementColors.admin_menu) {
        var menuText = this.getReadableTextColor(elementColors.admin_menu.menu_bg);
        elementColors.admin_menu.menu_text = menuText;
        elementColors.admin_menu.menu_icon = menuText;
        elementColors.admin_menu.menu_hover_text = this.getReadableTextColor(elementColors.admin_menu.menu_hover_bg);
        elementColors.admin_menu.menu_current_text = this.getReadableTextColor(elementColors.admin_menu.menu_current_bg);
        elementColors.admin_menu.menu_submenu_text = this.getReadableTextColor(elementColors.admin_menu.menu_submenu_bg);
      }

      if (elementColors.admin_bar) {
        var adminBarText = this.getReadableTextColor(elementColors.admin_bar.adminbar_bg);
        elementColors.admin_bar.adminbar_text = adminBarText;
        elementColors.admin_bar.adminbar_icon = adminBarText;
      }

      if (elementColors.dashboard_widgets) {
        if (elementColors.dashboard_widgets.widget_header_bg || elementColors.dashboard_widgets.widget_bg) {
          elementColors.dashboard_widgets.widget_title_text = this.getReadableTextColor(elementColors.dashboard_widgets.widget_header_bg || elementColors.dashboard_widgets.widget_bg);
        }
        if (elementColors.dashboard_widgets.widget_bg) {
          elementColors.dashboard_widgets.widget_body_text = this.getReadableTextColor(elementColors.dashboard_widgets.widget_bg);
        }
      }

      return {
        custom_scheme_base: palette.base,
        custom_scheme_text: palette.text,
        custom_scheme_highlight: palette.highlight,
        custom_scheme_notification: palette.notification,
        custom_scheme_background: palette.background,
        element_colors: elementColors
      };
    },

    /**
    * Handle edit custom style
    */
    handleEditCustomStyle: function (e) {
      if (e) {
        e.preventDefault();
        e.stopPropagation();
      }

      var $icon = $(e.currentTarget || e.target);
      var styleSlug = $icon.data('style');
      var styleName = $icon.data('name');

      $('#color-' + styleSlug).prop('checked', true);

      // Trigger the color option click to update UI
      $('#color-' + styleSlug).closest('.color-option').trigger('click');

      var scheme = (ppCapabilitiesAdminStyles.colorSchemes && ppCapabilitiesAdminStyles.colorSchemes[styleSlug]) ? ppCapabilitiesAdminStyles.colorSchemes[styleSlug] : null;
      if (scheme) {
        PP_Admin_Styles.populateCustomStyleForm(scheme, styleSlug, styleName, true);
      }
    },

    /**
     * Handle cancel custom style
     */
    handleCancelCustomStyle: function (e) {
      if (e) {
        e.preventDefault();
      }

      // Clear custom style preview
      PP_Admin_Styles.clearCustomStylePreview();

      // Hide form
      $('#custom-style-form').slideUp();

      // Clear error message
      $('#custom-style-error').hide().html('');

      // Reset form
      $('#custom_style_name').val('');
      $('#custom_style_slug').val('new');
      $('input[name="custom_style_action"]').val('');
      $('.custom-link-delete').hide();

      // Scroll back to custom styles button
      $('html, body').animate({
        scrollTop: $('.custom-styles-button').offset().top - 100
      }, 300);
    },

    /**
     * Reset custom style form to General tab
     */
    resetCustomStyleTabToGeneral: function () {
      // Find the first custom style tab (General tab)
      var $firstTab = $('.custom-style-tab').first();
      if ($firstTab.length) {
        var tabTarget = $firstTab.data('tab');

        // Remove active from all tabs and content
        $('.custom-style-tab').removeClass('nav-tab-active');
        $('.custom-style-tab-content').removeClass('active').hide();

        // Add active to first tab and show its content
        $firstTab.addClass('nav-tab-active');
        if (tabTarget) {
          $('#' + tabTarget).addClass('active').show();
        }
      }
    },

    /**
   * Show custom style form
   */
    showCustomStyleForm: function (slug, name, scroll = true) {

      var promoForm = false;
      if (slug === 'new' && Number(ppCapabilitiesAdminStyles.proInstalled) === 0 && ppCapabilitiesAdminStyles.customStylesCounts > 0) {
        $('.pp-promo-overlay-row').show();
        $('#custom-style-form').addClass('promo-form');
        promoForm = true
      } else {
        $('.pp-promo-overlay-row').hide();
        $('#custom-style-form').removeClass('promo-form');
      }

      // Set form values
      $('#custom_style_name').val(name);
      $('#custom_style_slug').val(slug);
      $('input[name="custom_style_action"]').val('');

      // Show form
      $('#custom-style-form').slideDown();

      // Update form title
      var $formTitle = $('#custom-style-form h4');
      if (slug === 'new') {
        $formTitle.html('<span class="dashicons dashicons-plus"></span> ' + ppCapabilitiesAdminStyles.labels.addCustomStyle);
      } else {
        $formTitle.html('<span class="dashicons dashicons-edit"></span> ' + ppCapabilitiesAdminStyles.labels.editCustomStyle + ': ' + name);
      }

      // Show/hide delete button, style name field, and save/cancel buttons based on style type
      if (promoForm && slug === 'new') {
        $('#custom-style-delete-button').hide();
        $('#custom-style-name-row').show();
        $('#style-name-label').text(ppCapabilitiesAdminStyles.labels.styleName);
        $('#style-name-required').show();
          $('#style-name-description').text(ppCapabilitiesAdminStyles.labels.styleNameDescription);
        $('#custom-style-save-row').hide();
        $('input[name="save_custom_style"]').hide();
        $('.cancel-custom-style').hide();
      } else if (slug === 'new') {
        $('#custom-style-delete-button').hide();
        $('#custom-style-name-row').show();
        $('#style-name-label').text(ppCapabilitiesAdminStyles.labels.styleName);
        $('#style-name-required').show();
        $('#style-name-description').text(ppCapabilitiesAdminStyles.labels.styleNameDescription);
        // Show save and cancel buttons
        $('#custom-style-save-row').show();
        $('input[name="save_custom_style"]').show();
        $('.cancel-custom-style').show();
      } else {
        // Show delete button for other existing user custom styles
        $('#custom-style-delete-button').show();
        $('#custom-style-name-row').show();
        $('#style-name-label').text(ppCapabilitiesAdminStyles.labels.styleName);
        $('#style-name-required').show();
        $('#style-name-description').text(ppCapabilitiesAdminStyles.labels.styleNameDescription);
        // Show save and cancel buttons
        $('#custom-style-save-row').show();
        $('input[name="save_custom_style"]').show();
        $('.cancel-custom-style').show();
      }

      if (scroll) {
        $('html, body').animate({
          scrollTop: $('#custom-style-form').offset().top - 100
        }, 300);
      }
    },

    /**
    * Handle custom color change in custom form
    */
    handleCustomFormColorChange: function ($input) {
      // If $input is passed, use it, otherwise get it from selector
      if (!$input || !$input.jquery) {
        // This shouldn't happen with our binding, but handle it
        return;
      }

      var color = $input.val();
      var inputId = $input.attr('id');

      // Update the color picker button
      var $picker = $input.closest('.wp-picker-container');
      if ($picker.length) {
        $picker.find('.wp-color-result').css('background-color', color);
      }

      // Apply live preview if custom form is visible
      if ($('#custom-style-form').is(':visible')) {
        var colors = PP_Admin_Styles.getCustomFormPreviewColors();
        PP_Admin_Styles.applyCustomStylePreview(colors);
      }
    },

    /**
    * Initialize color pickers for custom form
    */
    initCustomFormColorPickers: function () {
      if (typeof $.fn.wpColorPicker !== 'undefined') {
        $('#custom-style-form .custom-style-color').wpColorPicker({
          change: function (event, ui) {
            var $input = $(this);
            var color = ui.color.toString();
            $input.val(color);

            // Use setTimeout to avoid recursion issues
            setTimeout(function () {
              // Trigger custom change event
              $input.trigger('change');

              // If this custom form is visible, update preview
              if ($('#custom-style-form').is(':visible')) {
                // Call the handler with the input element
                PP_Admin_Styles.handleCustomFormColorChange($input);
              }
            }, 0);
          }
        });
      }
    },

    /**
     * Auto-generate slug from custom style name
     */
    handleCustomStyleNameChange: function () {
      // Get the input value directly by selector
      var name = $('#custom_style_name').val();
      var $slugInput = $('#custom_style_slug');

      // Only auto generate if it's a new style or empty
      if ($slugInput.val() === 'new' || $slugInput.val() === '') {
        if (name && typeof name === 'string') {
          var slug = name.toLowerCase()
            .replace(/[^\w ]+/g, '')
            .replace(/ +/g, '-')
            .replace(/^-+|-+$/g, '');

          // Add prefix if not already there
          if (!slug.startsWith('ppc-custom-style-')) {
            slug = 'ppc-custom-style-' + slug;
          }

          $slugInput.val(slug);
        }
      }
    },

    /**
     * Handle delete custom style confirmation
     */
    handleDeleteCustomStyle: function (e) {
      e.preventDefault();

      var styleName = $('#custom_style_name').val();
      if (!styleName) {
        styleName = ppCapabilitiesAdminStyles.labels.thisCustomStyle;
      }

      $('input[name="custom_style_action"]').val('delete');
      $('#ppc-admin-styles-form').submit();
    },

    /**
   * Handle save custom style
   */
    handleSaveCustomStyle: function (e) {
      e.preventDefault();

      var styleName = $('#custom_style_name').val().trim();
      var styleSlug = $('#custom_style_slug').val();
      var $errorDiv = $('#custom-style-error');
      var baseColor = $('#custom_style_custom_scheme_base').val().trim();

      // Validate style name
      if (!styleName) {
        $errorDiv.html(ppCapabilitiesAdminStyles.labels.styleNameRequired).show();
        $('#custom_style_name').focus();
        return false;
      }

      // Validate Main Admin Color (required)
      if (!baseColor) {
        $errorDiv.html(ppCapabilitiesAdminStyles.labels.mainAdminColorRequired).show();
        $('#custom_style_custom_scheme_base').focus();
        return false;
      }

      // Clear error message if validation passes
      $errorDiv.hide().html('');

      // Set the color scheme to this custom style if it's new
      if (styleSlug === 'new' || !styleSlug) {
        // Generate a temporary slug for selection
        var tempSlug = 'ppc-custom-styles-' + Date.now();
        $('#custom_style_slug').val(tempSlug);
        // Also update the color scheme selection
        $('input[name="settings[admin_color_scheme]"]').val(tempSlug);
      } else {
        // Update the color scheme selection to this custom style
        $('input[name="settings[admin_color_scheme]"]').val(styleSlug);
      }

      // Set action to save
      $('input[name="custom_style_action"]').val('save');

      // Submit the form
      $('#ppc-admin-styles-form').submit();

      return false;
    },

    /**
     * Handle role change
     */
    handleRoleChange: function () {
      var $select = $(this);
      var selectedRole = $select.val();

      if (!selectedRole) {
        return;
      }

      // Disable select during navigation
      $select.prop('disabled', true);

      // Show loading
      $('#pp-capability-menu-wrapper').hide();
      $('.publishpress-caps-manage img.loading').show();
      $('.editor-features-footer-meta').hide();
      $('.pp-capabilities-submit-top').hide();

      // Navigate to role specific URL
      window.location.href = ppCapabilitiesAdminStyles.adminUrl + '&role=' + encodeURIComponent(selectedRole);
    },

    /**
   * Handle color scheme selection
   */
    handleSchemeSelection: function () {
      var $option = $(this);
      var scheme = $option.find('input[type="radio"]').val();

      // Check if this is a user custom style
      var isUserCustomStyle = scheme.startsWith('ppc-custom-style-');

      // Hide custom style form if showing and selecting non-custom style
      if ($('#custom-style-form').is(':visible') && !isUserCustomStyle) {
        $('#custom-style-form').slideUp();
      }

      // If already selected, do nothing (except show form for custom styles)
      if ($option.hasClass('selected')) {
        // If it's a user custom style and form is not visible, show it
        if (isUserCustomStyle && !$('#custom-style-form').is(':visible')) {
          var $editIcon = $option.find('.custom-style-edit-icon');
          if ($editIcon.length) {
            var styleSlug = $editIcon.data('style');
            var styleName = $editIcon.data('name');
            var scheme = (ppCapabilitiesAdminStyles.colorSchemes && ppCapabilitiesAdminStyles.colorSchemes[styleSlug]) ? ppCapabilitiesAdminStyles.colorSchemes[styleSlug] : null;
            if (scheme) {
              PP_Admin_Styles.populateCustomStyleForm(scheme, styleSlug, styleName, true);
            } else {
              PP_Admin_Styles.showCustomStyleForm(styleSlug, styleName, true);
            }
          }
        }
        return;
      }

      // Update selected state
      $('.color-option').removeClass('selected');
      $('.color-checkbox').removeClass('checked').empty();
      $option.addClass('selected');
      $option.find('.color-checkbox').addClass('checked').html('<span class="dashicons dashicons-yes"></span>');

      // Update hidden input
      $('#admin_color_scheme').val(scheme);

      // Set current scheme early so colorpicker handlers behave correctly
      PP_Admin_Styles.currentScheme = scheme;

      // Remove any admin area preview styles
      $('#ppc-admin-area-preview').remove();

      // Load the colors stylesheet
      if (PP_Admin_Styles.$stylesheet.length === 0) {
        PP_Admin_Styles.$stylesheet = $('<link rel="stylesheet" />').appendTo('head');
      }

      // Set the stylesheet URL
      var cssUrl = $option.children('.css_url').val();
      var newUrl = sanitizeCssUrl(cssUrl, scheme, typeof isSafeCssUrl === 'function' ? isSafeCssUrl : null);

      if (newUrl && newUrl !== 'false' && newUrl !== '') {
        PP_Admin_Styles.$stylesheet.attr('href', newUrl);
      } else {
        // For default scheme or invalid URL, remove the href attribute
        PP_Admin_Styles.$stylesheet.removeAttr('href');
      }

      // Show/hide editors based on selection
      if (isUserCustomStyle) {

        // Get the edit icon data and show custom style form
        var $editIcon = $option.find('.custom-style-edit-icon');
        if ($editIcon.length) {
          var styleSlug = $editIcon.data('style');
          var styleName = $editIcon.data('name');
          var schemeObj = (ppCapabilitiesAdminStyles.colorSchemes && ppCapabilitiesAdminStyles.colorSchemes[styleSlug]) ? ppCapabilitiesAdminStyles.colorSchemes[styleSlug] : null;
          if (schemeObj) {
            PP_Admin_Styles.populateCustomStyleForm(schemeObj, styleSlug, styleName, false);
          } else {
            PP_Admin_Styles.showCustomStyleForm(styleSlug, styleName, false);
          }
        }
      } else {
        // Hide editor for non-custom styles
        if ($('#custom-style-form').is(':visible')) {
          $('#custom-style-form').slideUp();
        }
      }

      PP_Admin_Styles.currentScheme = scheme;
    },

    /**
     * Initialize color picker close behavior
     */
    initColorPickerCloseBehavior: function () {
      var self = this;
      var $document = $(document);

      // Use event capturing to close pickers when clicking outside
      $document.on('click', function (e) {
        var $target = $(e.target);

        // If clicking on a color picker button, let WordPress handle it
        if ($target.hasClass('wp-color-result') ||
          $target.closest('.wp-color-result').length ||
          $target.closest('.iris-picker').length ||
          $target.hasClass('wp-picker-clear')) {
          return;
        }

        // Check if clicking on iris palette
        if ($target.hasClass('iris-palette') || $target.closest('.iris-palette').length) {
          return; // Let our fixIrisPaletteLinks handler handle this
        }

        // Close all open color pickers
        $('.wp-picker-holder:visible').each(function () {
          var $holder = $(this);
          var $container = $holder.closest('.wp-picker-container');

          // Only close if click is outside this picker
          if (!$target.closest($container).length) {
            $holder.hide();
            $container.find('.wp-color-result').attr('aria-expanded', 'false');
            $container.find('.wp-picker-input-wrap').addClass('hidden');
          }
        });
      });

      // Handle escape key
      $document.on('keyup', function (e) {
        if (e.keyCode === 27) { // Escape key
          $('.wp-picker-holder:visible').hide();
          $('.wp-color-result').attr('aria-expanded', 'false');
          $('.wp-picker-input-wrap').addClass('hidden');
        }
      });
    },


    /**
     * Fix iris palette links to prevent page jumps
     */
    fixIrisPaletteLinks: function () {
      var self = this;

      // Remove href="#" and handle clicks properly
      $('.iris-palette').each(function () {
        var $palette = $(this);

        // Remove href to prevent page jumps
        $palette.removeAttr('href');

        // Store the color
        var bgColor = $palette.css('background-color');
        $palette.data('color', bgColor);

        // Handle clicks
        $palette.on('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          var color = $(this).data('color');
          if (color) {
            // Find the color picker input
            var $picker = $(this).closest('.wp-picker-container');
            var $input = $picker.find('.pp-capabilities-color-picker');

            if ($input.length) {
              // Convert RGB to hex if needed
              if (color.indexOf('rgb') === 0) {
                color = self.rgbToHex(color);
              }

              // Update the input
              $input.val(color).trigger('change');

              // Update the button color
              $picker.find('.wp-color-result').css('background-color', color);

            }
          }

          return false;
        });

        // Handle double-click to just close the picker
        $palette.on('dblclick', function (e) {
          e.preventDefault();
          e.stopPropagation();

          var $picker = $(this).closest('.wp-picker-container');
          $picker.find('.wp-picker-holder').hide();
          $picker.find('.wp-color-result').attr('aria-expanded', 'false');

          return false;
        });
      });
    },


    /**
     * Get custom style data from registered schemes
     */
    getCustomStyleData: function (slug) {
      if (ppCapabilitiesAdminStyles.colorSchemes && ppCapabilitiesAdminStyles.colorSchemes[slug]) {
        var scheme = ppCapabilitiesAdminStyles.colorSchemes[slug];
        return {
          colors: {
            base: scheme.colors[0] || '',
            text: scheme.colors[1] || '',
            highlight: scheme.colors[2] || '',
            notification: scheme.colors[3] || '',
            background: scheme.colors[4] || ''
          }
        };
      }
      return null;
    },

    /**
     * Apply custom style preview
     */
    applyCustomStylePreview: function (colors) {
      // Remove any existing preview
      $('#ppc-admin-area-preview').remove();

      // If base color is empty, don't apply preview
      if (!colors || !colors.base || colors.base === '') {
        return;
      }

      // Create new preview style and insert after stylesheet so it overrides where possible
      var $previewStyle = $('<style id="ppc-admin-area-preview"></style>');
      if (PP_Admin_Styles.$stylesheet && PP_Admin_Styles.$stylesheet.length) {
        PP_Admin_Styles.$stylesheet.after($previewStyle);
      } else {
        $('head').append($previewStyle);
      }

      // Generate CSS similar to built-in custom preview
      var css = this.generatePreviewCSS(colors);
      $previewStyle.text(css);
    },

    /**
     * Generate preview CSS for custom styles
     */
    generatePreviewCSS: function (colors) {
      var css = '/* Instant admin area preview */\n';

      // Only generate CSS for colors that are defined

      // Admin menu background
      if (colors.base) {
        css += `
      #adminmenuback,
      #adminmenuwrap,
      #adminmenu {
        background-color: ${colors.base} !important;
      }\n`;
      }

      // Admin menu text
      if (colors.text) {
        css += `
      #adminmenu a {
        color: ${colors.text} !important;
      }\n`;
      }

      // Admin menu hover
      if (colors.highlight || colors.text) {
        var hoverCss = '#adminmenu li.menu-top:hover,\n      #adminmenu li.opensub > a.menu-top,\n      #adminmenu li > a.menu-top:focus {';
        if (colors.highlight) {
          hoverCss += `\n        background-color: ${colors.highlight} !important;`;
        }
        if (colors.text) {
          hoverCss += `\n        color: ${colors.text} !important;`;
        }
        hoverCss += '\n      }\n';
        css += hoverCss;
      }

      // Admin menu submenu background
      if (colors.base) {
        var lighterBase = this.lightenColor(colors.base, 20);
        if (lighterBase) {
          css += `
      #adminmenu .wp-submenu,
      #adminmenu .wp-has-submenu:hover .wp-submenu,
      #adminmenu .wp-has-submenu:focus-within .wp-submenu,
      #adminmenu .wp-has-current-submenu .wp-submenu,
      #adminmenu .wp-has-current-submenu.opensub .wp-submenu {
        background-color: ${lighterBase} !important;
      }\n`;
        }
      }

      // Admin menu submenu text with rgba
      if (colors.text) {
        var textRgb = this.hexToRgb(colors.text);
        if (textRgb) {
          css += `
      #adminmenu .wp-submenu a {
        color: rgba(${textRgb}, 0.8) !important;
      }\n`;
        }
      }

      // Admin menu submenu hover
      if (colors.text) {
        css += `
      #adminmenu .wp-submenu a:hover,
      #adminmenu .wp-submenu a:focus {
        color: ${colors.text} !important;
      }\n`;
      }

      // Admin menu current item
      if (colors.highlight || colors.text) {
        var currentCss = '#adminmenu li.current a.menu-top,\n      #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu {';
        if (colors.highlight) {
          currentCss += `\n        background-color: ${colors.highlight} !important;`;
        }
        if (colors.text) {
          currentCss += `\n        color: ${colors.text} !important;`;
        }
        currentCss += '\n      }\n';
        css += currentCss;
      }

      // Admin bar
      if (colors.base) {
        css += `
      #wpadminbar {
        background-color: ${colors.base} !important;
      }\n`;
      }

      if (colors.text) {
        css += `
      #wpadminbar .ab-item,
      #wpadminbar a.ab-item {
        color: ${colors.text} !important;
      }\n`;

        var textRgb = this.hexToRgb(colors.text);
        if (textRgb) {
          css += `
      #wpadminbar .ab-icon:before,
      #wpadminbar .ab-item:before,
      #wpadminbar .ab-item:after {
        color: rgba(${textRgb}, 0.8) !important;
      }\n`;
        }
      }

      // Admin bar hover
      if (colors.highlight || colors.text) {
        var barHoverCss = '#wpadminbar:not(.mobile) .ab-top-menu > li:hover > .ab-item,\n      #wpadminbar:not(.mobile) .ab-top-menu > li > .ab-item:focus {';
        if (colors.highlight) {
          barHoverCss += `\n        background-color: ${colors.highlight} !important;`;
        }
        if (colors.text) {
          barHoverCss += `\n        color: ${colors.text} !important;`;
        }
        barHoverCss += '\n      }\n';
        css += barHoverCss;
      }

      // Admin bar submenu
      if (colors.base) {
        var lighterBase = this.lightenColor(colors.base, 20);
        if (lighterBase) {
          css += `
      #wpadminbar .menupop .ab-sub-wrapper {
        background-color: ${lighterBase} !important;
      }\n`;
        }
      }

      // Primary buttons
      if (colors.base || colors.text) {
        var btnCss = '.wp-core-ui .button-primary {';
        if (colors.base) {
          btnCss += `\n        background: ${colors.base} !important;`;
          var darkerBase = this.darkenColor(colors.base, 15);
          if (darkerBase) {
            btnCss += `\n        border-color: ${darkerBase} !important;`;
          }
        }
        if (colors.text) {
          btnCss += `\n        color: ${colors.text} !important;`;
        }
        btnCss += '\n      }\n';
        css += btnCss;
      }

      // Primary buttons hover
      if (colors.highlight || colors.text) {
        var btnHoverCss = '.wp-core-ui .button-primary:hover,\n      .wp-core-ui .button-primary:focus {';
        if (colors.highlight) {
          btnHoverCss += `\n        background: ${colors.highlight} !important;`;
          btnHoverCss += `\n        border-color: ${colors.highlight} !important;`;
        }
        if (colors.text) {
          btnHoverCss += `\n        color: ${colors.text} !important;`;
        }
        btnHoverCss += '\n      }\n';
        css += btnHoverCss;
      }

      // Notifications
      if (colors.notification || colors.text) {
        var notifCss = '#adminmenu .awaiting-mod,\n      #adminmenu .update-plugins {';
        if (colors.notification) {
          notifCss += `\n        background-color: ${colors.notification} !important;`;
        }
        if (colors.text) {
          notifCss += `\n        color: ${colors.text} !important;`;
        }
        notifCss += '\n      }\n';
        css += notifCss;
      }

      // Background
      if (colors.background) {
        css += `
      body.wp-admin {
        background-color: ${colors.background} !important;
      }\n`;
      }

      // Add element-specific color CSS if element_colors exist
      if (colors.element_colors) {
        var elementCss = this.generateElementColorsCss(colors.element_colors);
        css += '\n' + elementCss;
      }

      return css;

    },

    /**
     * Generate CSS for element-specific colors
     */
    generateElementColorsCss: function (elementColors) {
      var css = '/* Element-specific colors */\n';
      var tableScope = 'body:not(.capabilities_page_pp-capabilities-admin-styles)';

      // Links colors
      if (elementColors.links && Object.keys(elementColors.links).length > 0) {
        if (elementColors.links.link_default) {
          css += `a { color: ${elementColors.links.link_default} !important; }\n`;
        }
        if (elementColors.links.link_hover) {
          css += `a:hover, a:focus { color: ${elementColors.links.link_hover} !important; }\n`;
        }
        if (elementColors.links.link_delete) {
          css += `.delete { color: ${elementColors.links.link_delete} !important; }\n`;
        }
        if (elementColors.links.link_trash) {
          css += `.trash { color: ${elementColors.links.link_trash} !important; }\n`;
        }
        if (elementColors.links.link_spam) {
          css += `.spam { color: ${elementColors.links.link_spam} !important; }\n`;
        }
        if (elementColors.links.link_inactive) {
          css += `.inactive { color: ${elementColors.links.link_inactive} !important; }\n`;
        }
      }

      // Tables colors
      if (elementColors.tables && Object.keys(elementColors.tables).length > 0) {
        if (elementColors.tables.table_header_bg) {
          css += `${tableScope} table thead th { background-color: ${elementColors.tables.table_header_bg} !important; }\n`;
        }
        if (elementColors.tables.table_header_text) {
          css += `${tableScope} table thead th { color: ${elementColors.tables.table_header_text} !important; }\n`;
        }
        if (elementColors.tables.table_row_bg) {
          css += `${tableScope} table tbody tr { background-color: ${elementColors.tables.table_row_bg} !important; }\n`;
        }
        if (elementColors.tables.table_row_color) {
          css += `${tableScope} table tbody tr td { color: ${elementColors.tables.table_row_color} !important; }\n`;
        }
        if (elementColors.tables.table_row_hover_bg) {
          css += `${tableScope} table tbody tr:hover { background-color: ${elementColors.tables.table_row_hover_bg} !important; }\n`;
        }
        if (elementColors.tables.table_border) {
          css += `${tableScope} table { border-color: ${elementColors.tables.table_border} !important; } ${tableScope} table td, ${tableScope} table th { border-color: ${elementColors.tables.table_border} !important; }\n`;
        }
        if (elementColors.tables.table_alt_row_bg) {
          css += `${tableScope} table tbody tr:nth-child(odd) { background-color: ${elementColors.tables.table_alt_row_bg} !important; }\n`;
        }
        if (elementColors.tables.table_alt_row_color) {
          css += `${tableScope} table tbody tr:nth-child(odd) td { color: ${elementColors.tables.table_alt_row_color} !important; }\n`;
        }
      }

      // Forms colors
      if (elementColors.forms && Object.keys(elementColors.forms).length > 0) {
        if (elementColors.forms.input_background) {
          css += `input[type="text"], input[type="email"], input[type="password"], input[type="number"], input[type="url"], input[type="tel"], input[type="search"], input[type="date"], input[type="time"], input[type="datetime-local"], input[type="month"], input[type="week"], input[type="file"], textarea, select, .wp-core-ui select { background-color: ${elementColors.forms.input_background} !important; }\n`;
        }
        if (elementColors.forms.input_border) {
          css += `input[type="text"], input[type="email"], input[type="password"], input[type="number"], input[type="url"], input[type="tel"], input[type="search"], input[type="date"], input[type="time"], input[type="datetime-local"], input[type="month"], input[type="week"], input[type="file"], textarea, select, .wp-core-ui select { border-color: ${elementColors.forms.input_border} !important; }\n`;
        }
        if (elementColors.forms.input_focus_border) {
          css += `input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus, input[type="number"]:focus, input[type="url"]:focus, input[type="tel"]:focus, input[type="search"]:focus, input[type="date"]:focus, input[type="time"]:focus, input[type="datetime-local"]:focus, input[type="month"]:focus, input[type="week"]:focus, input[type="file"]:focus, textarea:focus, select:focus, .wp-core-ui select:focus { border-color: ${elementColors.forms.input_focus_border} !important; }\n`;
        }
        if (elementColors.forms.input_text) {
          css += `input[type="text"], input[type="email"], input[type="password"], input[type="number"], input[type="url"], input[type="tel"], input[type="search"], input[type="date"], input[type="time"], input[type="datetime-local"], input[type="month"], input[type="week"], input[type="file"], textarea, select, .wp-core-ui select { color: ${elementColors.forms.input_text} !important; }\n`;
        }
        if (elementColors.forms.input_placeholder) {
          css += `input::placeholder, textarea::placeholder, .wp-core-ui select { color: ${elementColors.forms.input_placeholder} !important; }\n`;
        }
      }

      // Buttons colors
      if (elementColors.buttons && Object.keys(elementColors.buttons).length > 0) {
        if (elementColors.buttons.button_primary_bg) {
          css += `.wp-core-ui .button-primary:not(.wp-picker-container .button-primary), input[type="submit"].button-primary:not(.wp-picker-container input) { background-color: ${elementColors.buttons.button_primary_bg} !important; }\n`;
        }
        if (elementColors.buttons.button_primary_text) {
          css += `.wp-core-ui .button-primary:not(.wp-picker-container .button-primary), input[type="submit"].button-primary:not(.wp-picker-container input) { color: ${elementColors.buttons.button_primary_text} !important; }\n`;
        }
        if (elementColors.buttons.button_primary_hover_bg) {
          css += `.wp-core-ui .button-primary:hover:not(.wp-picker-container .button-primary), input[type="submit"].button-primary:hover:not(.wp-picker-container input) { background-color: ${elementColors.buttons.button_primary_hover_bg} !important; }\n`;
        }
        if (elementColors.buttons.button_secondary_bg) {
          css += `.wp-core-ui .button:not(.wp-picker-container .button), input[type="submit"]:not(.wp-picker-container input) { background-color: ${elementColors.buttons.button_secondary_bg} !important; }\n`;
        }
        if (elementColors.buttons.button_secondary_text) {
          css += `.wp-core-ui .button:not(.wp-picker-container .button), input[type="submit"]:not(.wp-picker-container input) { color: ${elementColors.buttons.button_secondary_text} !important; }\n`;
        }
        if (elementColors.buttons.button_secondary_hover_bg) {
          css += `.wp-core-ui .button:hover:not(.wp-picker-container .button), input[type="submit"]:hover:not(.wp-picker-container input) { background-color: ${elementColors.buttons.button_secondary_hover_bg} !important; }\n`;
        }
      }

      // Admin menu colors
      if (elementColors.admin_menu && Object.keys(elementColors.admin_menu).length > 0) {
        if (elementColors.admin_menu.menu_bg) {
          css += `#adminmenu, #adminmenuback, #adminmenuwrap { background-color: ${elementColors.admin_menu.menu_bg} !important; }\n`;
        }
        if (elementColors.admin_menu.menu_text) {
          css += `#adminmenu a { color: ${elementColors.admin_menu.menu_text} !important; }\n`;
        }
        if (elementColors.admin_menu.menu_icon) {
          css += `#adminmenu .dashicons, #adminmenu .dashicons-before:before { color: ${elementColors.admin_menu.menu_icon} !important; }\n`;
        }

        if (elementColors.admin_menu.menu_hover_bg) {
          css += `#adminmenu li:hover > a, #adminmenu li.menu-top:hover { background-color: ${elementColors.admin_menu.menu_hover_bg} !important; }\n`;
        }
        if (elementColors.admin_menu.menu_hover_text) {
          css += `#adminmenu li:hover > a { color: ${elementColors.admin_menu.menu_hover_text} !important; }\n`;
        }
        if (elementColors.admin_menu.menu_current_bg) {
          css += `#adminmenu li.current a.menu-top, #adminmenu li.wp-has-current-submenu > a.wp-has-current-submenu { background-color: ${elementColors.admin_menu.menu_current_bg} !important; }\n`;
        }
        if (elementColors.admin_menu.menu_current_text) {
          css += `#adminmenu li.current a.menu-top, #adminmenu li.wp-has-current-submenu > a.wp-has-current-submenu { color: ${elementColors.admin_menu.menu_current_text} !important; }\n`;
        }
        if (elementColors.admin_menu.menu_submenu_bg) {
          css += `#adminmenu .wp-submenu, #adminmenu .wp-has-submenu:hover .wp-submenu, #adminmenu .wp-has-submenu:focus-within .wp-submenu, #adminmenu .wp-has-current-submenu .wp-submenu, #adminmenu .wp-has-current-submenu.opensub .wp-submenu { background-color: ${elementColors.admin_menu.menu_submenu_bg} !important; }\n`;
        }
        if (elementColors.admin_menu.menu_submenu_text) {
          css += `#adminmenu .wp-submenu a, #adminmenu .wp-has-current-submenu .wp-submenu a, #adminmenu .wp-has-current-submenu.opensub .wp-submenu a, #adminmenu .wp-submenu a:hover, #adminmenu .wp-submenu a:focus { color: ${elementColors.admin_menu.menu_submenu_text} !important; }\n`;
        }
      }

      // Admin bar colors
      if (elementColors.admin_bar && Object.keys(elementColors.admin_bar).length > 0) {
        if (elementColors.admin_bar.adminbar_bg) {
          css += `#wpadminbar { background-color: ${elementColors.admin_bar.adminbar_bg} !important; }\n`;
        }
        if (elementColors.admin_bar.adminbar_text) {
          css += `#wpadminbar .ab-item, #wpadminbar a.ab-item, #wpadminbar > #wp-toolbar a, #wpadminbar > #wp-toolbar span, #wpadminbar > #wp-toolbar span.ab-label, #wpadminbar .ab-submenu .ab-item, #wpadminbar .quicklinks .ab-submenu a, #wpadminbar .quicklinks .menupop ul li a { color: ${elementColors.admin_bar.adminbar_text} !important; }\n`;
        }
        if (elementColors.admin_bar.adminbar_icon) {
          css += `#wpadminbar .ab-icon:before, #wpadminbar .ab-item:before, #wpadminbar .dashicons { color: ${elementColors.admin_bar.adminbar_icon} !important; }\n`;
        }
        if (elementColors.admin_bar.adminbar_hover_bg) {
          css += `#wpadminbar .ab-top-menu > li:hover > .ab-item { background-color: ${elementColors.admin_bar.adminbar_hover_bg} !important; }\n`;
        }
      }

      // Dashboard widget colors
      if (elementColors.dashboard_widgets && Object.keys(elementColors.dashboard_widgets).length > 0) {
        if (elementColors.dashboard_widgets.widget_bg) {
          css += `#dashboard-widgets .postbox { background-color: ${elementColors.dashboard_widgets.widget_bg} !important; }\n`;
        }
        if (elementColors.dashboard_widgets.widget_border) {
          css += `#dashboard-widgets .postbox { border-color: ${elementColors.dashboard_widgets.widget_border} !important; }\n`;
        }
        if (elementColors.dashboard_widgets.widget_header_bg) {
          css += `#dashboard-widgets .postbox-header, #dashboard-widgets .postbox .hndle { background-color: ${elementColors.dashboard_widgets.widget_header_bg} !important; }\n`;
        }
        if (elementColors.dashboard_widgets.widget_title_text) {
          css += `#dashboard-widgets .postbox-header h2, #dashboard-widgets .postbox .hndle { color: ${elementColors.dashboard_widgets.widget_title_text} !important; }\n`;
        }
        if (elementColors.dashboard_widgets.widget_body_text) {
          css += `#dashboard-widgets .postbox .inside, #dashboard-widgets .postbox .inside p, #dashboard-widgets .postbox .inside li { color: ${elementColors.dashboard_widgets.widget_body_text} !important; }\n`;
        }
        if (elementColors.dashboard_widgets.widget_link) {
          css += `#dashboard-widgets .postbox .inside a { color: ${elementColors.dashboard_widgets.widget_link} !important; }\n`;
        }
        if (elementColors.dashboard_widgets.widget_link_hover) {
          css += `#dashboard-widgets .postbox .inside a:hover, #dashboard-widgets .postbox .inside a:focus { color: ${elementColors.dashboard_widgets.widget_link_hover} !important; }\n`;
        }
      }

      return css;
    },

    /**
     * Convert hex to RGB
     */
    hexToRgb: function (hex) {
      if (!hex || hex === '') {
        return null;
      }

      hex = hex.replace('#', '');

      if (hex.length === 3) {
        hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
      }

      if (hex.length !== 6) {
        return null;
      }

      var r = parseInt(hex.substring(0, 2), 16);
      var g = parseInt(hex.substring(2, 4), 16);
      var b = parseInt(hex.substring(4, 6), 16);

      if (isNaN(r) || isNaN(g) || isNaN(b)) {
        return null;
      }

      return r + ', ' + g + ', ' + b;
    },

    rgbToHex: function (rgb) {
      // If already hex, return it
      if (rgb.indexOf('#') === 0) return rgb;

      // Convert rgb(r, g, b) or rgba(r, g, b, a) to hex
      var rgbArray = rgb.match(/\d+/g);
      if (rgbArray && rgbArray.length >= 3) {
        var r = parseInt(rgbArray[0]).toString(16).padStart(2, '0');
        var g = parseInt(rgbArray[1]).toString(16).padStart(2, '0');
        var b = parseInt(rgbArray[2]).toString(16).padStart(2, '0');
        return '#' + r + g + b;
      }
      return rgb;
    },

    /**
     * Lighten color
     */
    lightenColor: function (color, percent) {
      if (!color || color === '') {
        return null;
      }

      var num = parseInt(color.replace('#', ''), 16);
      if (isNaN(num)) {
        return null;
      }

      var amt = Math.round(2.55 * percent),
        R = (num >> 16) + amt,
        G = (num >> 8 & 0x00FF) + amt,
        B = (num & 0x0000FF) + amt;

      return '#' + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
        (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
        (B < 255 ? B < 1 ? 0 : B : 255)).toString(16).slice(1);
    },

    /**
     * Darken color
     */
    darkenColor: function (color, percent) {
      if (!color || color === '') {
        return null;
      }

      var num = parseInt(color.replace('#', ''), 16);
      if (isNaN(num)) {
        return null;
      }

      var amt = Math.round(2.55 * percent) * -1,
        R = (num >> 16) + amt,
        G = (num >> 8 & 0x00FF) + amt,
        B = (num & 0x0000FF) + amt;

      return '#' + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
        (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
        (B < 255 ? B < 1 ? 0 : B : 255)).toString(16).slice(1);
    },

    /**
     * Pick readable text color based on background
     */
    getReadableTextColor: function (color) {
      if (!color || typeof color !== 'string') {
        return '#111827';
      }

      var hex = this.rgbToHex(color);
      if (!hex || hex.indexOf('#') !== 0 || hex.length < 7) {
        return '#111827';
      }

      var r = parseInt(hex.slice(1, 3), 16);
      var g = parseInt(hex.slice(3, 5), 16);
      var b = parseInt(hex.slice(5, 7), 16);
      var luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

      return luminance > 0.6 ? '#111827' : '#f9fafb';
    },

    /**
     * Pick muted text color based on background
     */
    getMutedTextColor: function (color) {
      var readable = this.getReadableTextColor(color);
      return readable === '#111827' ? '#6b7280' : '#cbd5e1';
    },

    /**
     * Determine if a color is light
     */
    isLightColor: function (color) {
      if (!color || typeof color !== 'string') {
        return false;
      }

      var hex = this.rgbToHex(color);
      if (!hex || hex.indexOf('#') !== 0 || hex.length < 7) {
        return false;
      }

      var r = parseInt(hex.slice(1, 3), 16);
      var g = parseInt(hex.slice(3, 5), 16);
      var b = parseInt(hex.slice(5, 7), 16);
      var luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

      return luminance > 0.6;
    },

  };
  // Initialize when document is ready
  $(document).ready(function () {
    PP_Admin_Styles.init();
  });

})(jQuery);