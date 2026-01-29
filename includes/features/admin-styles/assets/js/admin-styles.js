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
    if (!trimmed) {
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

    var newUrl = trimmed;
    // Add cache busting timestamp for custom scheme
    if (currentScheme === 'publishpress-custom') {
      // Remove existing ver param if present, then append a new one
      newUrl = trimmed.replace(/([?&])ver=\d+(&?)/, function (match, p1, p2) {
        return p2 ? p1 : '';
      });
      if (newUrl.indexOf('?') === -1) {
        newUrl += '?ver=' + Date.now();
      } else {
        newUrl += '&ver=' + Date.now();
      }
    }

    return newUrl;
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
      this.initCheckboxes();
      this.initHiddenInputs();
      this.initColorPickerCloseBehavior();
      this.fixIrisPaletteLinks();
      this.reorderColorSchemes();
      this.registerEventsActions();

      // If custom scheme is selected on page load, update preview
      if (this.currentScheme === 'publishpress-custom') {
        this.updateCustomSchemePreview();
      }
    },

    /**
     * Register event actions
     */
    registerEventsActions: function () {
      $('#ppc-admin-styles-form input, #ppc-admin-styles-form select').on('keydown', function(e) {
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
     * Reorder color schemes to ensure PublishPress Custom is first
     */
    reorderColorSchemes: function () {
      var $colorOptions = $('.color-options');
      var $publishpressCustom = $colorOptions.find('.ppc-custom-scheme');

      if ($publishpressCustom.length) {
        $publishpressCustom.prependTo($colorOptions);
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

        if (schemeData) {
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
      // Tab navigation
      $(document).on('click', '.admin-styles-tab', this.handleTabClick);

      // Image upload
      $(document).on('click', '.pp-capabilities-upload-button', this.handleImageUpload);

      // Remove image
      $(document).on('click', '.pp-capabilities-remove-button', this.handleRemoveImage);

      // Role change
      $(document).on('change', '.ppc-admin-styles-role', this.handleRoleChange);

      // Update button text when role changes
      $(document).on('change', '.ppc-admin-styles-role', this.updateButtonText);

      // Color scheme selection
      $(document).on('click', '.color-option', this.handleSchemeSelection);

      // Custom scheme color change
      $(document).on('change', '.custom-scheme-color', this.handleCustomColorChange);

      // Custom scheme edit icon click
      $(document).on('click', '.custom-scheme-edit-icon', this.handleEditCustomScheme);
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

              // Update preview in admin area
              PP_Admin_Styles.applyAdminAreaPreview();

              // Also update the small preview area
              PP_Admin_Styles.updateCustomSchemePreview($input);

              // Update custom scheme URL
              if (PP_Admin_Styles.currentScheme === 'publishpress-custom') {
                PP_Admin_Styles.updateCustomSchemeUrl();
              }
            }
          },
          clear: function () {
            var $input = $(this);
            if ($input.data('preview')) {
              // Clear the preview styles
              $('#ppc-admin-area-preview').remove();

              // Update small preview
              setTimeout(function () {
                PP_Admin_Styles.updateCustomSchemePreview($input);
              }, 100);
            }
          }
        });
      }
    },

    /**
     * Handle tab click
     */
    handleTabClick: function (e) {
      e.preventDefault();

      var $tab = $(this);
      var tabTarget = $tab.data('tab');

      // Update active tab
      $('.admin-styles-tab').removeClass('nav-tab-active');
      $tab.addClass('nav-tab-active');

      // Show target content, hide others
      $('.admin-styles-tab-content').removeClass('active').hide();
      $(tabTarget).addClass('active').show();

      // Ensure custom scheme editor visibility is maintained
      if (tabTarget === '#admin-styles-general') {
        var currentScheme = $('#admin_color_scheme').val();
        if (currentScheme === 'publishpress-custom') {
          $('.custom-scheme-editor').show();
        }
      }
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

      // Navigate to role-specific URL
      window.location.href = ppCapabilitiesAdminStyles.adminUrl + '&role=' + encodeURIComponent(selectedRole);
    },

    /**
     * Update button text based on selected role
     */
    updateButtonText: function () {
      var selectedText = $(this).find('option:selected').text();
      if (selectedText && ppCapabilitiesAdminStyles.labels.saveForRole) {
        var buttonText = ppCapabilitiesAdminStyles.labels.saveForRole.replace('%s', selectedText);
        $('input[name="admin-styles-submit"]').val(buttonText);
      }
    },

    /**
     * Handle color scheme selection
     */
    handleSchemeSelection: function () {
      var $option = $(this);
      var scheme = $option.find('input[type="radio"]').val();

      // If already selected, do nothing
      if ($option.hasClass('selected')) {
        return;
      }

      // Update selected state
      $('.color-option').removeClass('selected');
      $('.color-checkbox').removeClass('checked').empty();
      $option.addClass('selected');
      $option.find('.color-checkbox').addClass('checked').html('<span class="dashicons dashicons-yes"></span>');

      // Update hidden input
      $('#admin_color_scheme').val(scheme);

      // Remove any admin area preview styles
      $('#ppc-admin-area-preview').remove();

      // Load the colors stylesheet
      if (PP_Admin_Styles.$stylesheet.length === 0) {
        PP_Admin_Styles.$stylesheet = $('<link rel="stylesheet" />').appendTo('head');
      }

      // Set the stylesheet URL
      var cssUrl = $option.children('.css_url').val();
      var newUrl = sanitizeCssUrl(cssUrl, this.currentScheme, typeof isSafeCssUrl === 'function' ? isSafeCssUrl : null);
      if (newUrl) {
        PP_Admin_Styles.$stylesheet.attr('href', newUrl);
      }

      // Show/hide custom scheme editor
      if (scheme === 'publishpress-custom') {
        $('.custom-scheme-editor').slideDown(300);
        // Apply preview for custom scheme
        PP_Admin_Styles.applyAdminAreaPreview();
      } else {
        $('.custom-scheme-editor').slideUp(300);
      }

      PP_Admin_Styles.currentScheme = scheme;
    },

    handleOutsideClick: function (e) {
      var $target = $(e.target);

      // If click is outside color picker container and not on a color picker input
      // AND not in the admin area preview (since we want to keep preview visible)
      if (!$target.closest('.wp-picker-container').length &&
        !$target.hasClass('wp-color-result') &&
        !$target.parent().hasClass('wp-color-result') &&
        !$target.closest('#adminmenu').length && // Don't close when clicking admin menu
        !$target.closest('#wpadminbar').length) { // Don't close when clicking admin bar

        // Close all open color pickers
        $('.wp-picker-container').each(function () {
          var $picker = $(this);

          if ($picker.find('.wp-picker-holder').is(':visible')) {
            // Hide the picker
            $picker.find('.wp-picker-holder').hide();
          }
        });
      }
    },

    /**
     * Initialize color picker close behavior
     */
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
          return; // Let WordPress handle these clicks
        }

        // Check if clicking on iris palette (we handle this separately)
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

              // Trigger preview updates if needed
              if ($input.data('preview')) {
                setTimeout(function () {
                  self.updateCustomSchemePreview($input);
                  self.applyAdminAreaPreview();
                }, 50);
              }
            }
          }

          return false;
        });

        // Handle double-click - just close the picker
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
     * Update custom scheme URL with new timestamp
     */
    updateCustomSchemeUrl: function () {
      // Generate new URL with current timestamp
      var timestamp = Math.floor(Date.now() / 1000);

      // Get the base URL (path to our CSS file)
      var moduleUrl = ppCapabilitiesAdminStyles.moduleUrl || '';
      var newUrl = moduleUrl + 'admin-styles-css.php?ppc_custom_scheme=1&ver=' + timestamp;

      // Update the custom scheme data
      if (ppCapabilitiesAdminStyles.colorSchemes['publishpress-custom']) {
        ppCapabilitiesAdminStyles.colorSchemes['publishpress-custom'].url = newUrl;
      }

      // Update the hidden input
      $('.color-option.ppc-custom-scheme .css_url').val(newUrl);

      // If custom scheme is currently selected, update the stylesheet
      if (PP_Admin_Styles.currentScheme === 'publishpress-custom') {
        PP_Admin_Styles.$stylesheet.attr('href', newUrl);
      }
    },

    /**
     * Handle custom color change with instant admin area preview
     */
    handleCustomColorChange: function () {
      var $input = $(this);
      var color = $input.val();

      // Update the preview buttons area
      PP_Admin_Styles.updateCustomSchemePreview($input);

      // Apply immediate preview to the entire admin area
      PP_Admin_Styles.applyAdminAreaPreview();

      // Update custom scheme URL
      if (PP_Admin_Styles.currentScheme === 'publishpress-custom') {
        PP_Admin_Styles.updateCustomSchemeUrl();
      }
    },

    /**
     * Apply custom colors as inline styles to the entire admin area
     */
    applyAdminAreaPreview: function () {
      // Get current color values
      var colors = {
        base: $('#custom_scheme_base').val() || '#655997',
        text: $('#custom_scheme_text').val() || '#ffffff',
        highlight: $('#custom_scheme_highlight').val() || '#8a7bb9',
        notification: $('#custom_scheme_notification').val() || '#d63638',
        background: $('#custom_scheme_background').val() || '#f0f0f1'
      };

      // Create or update preview style tag
      var $previewStyle = $('#ppc-admin-area-preview');
      if (!$previewStyle.length) {
        $previewStyle = $('<style id="ppc-admin-area-preview"></style>').appendTo('head');
      }

      // Generate comprehensive CSS for admin area preview
      var css = `
    /* Instant admin area preview */
    #adminmenuback,
    #adminmenuwrap,
    #adminmenu {
      background-color: ${colors.base} !important;
    }

    #adminmenu a {
      color: ${colors.text} !important;
    }

    #adminmenu li.menu-top:hover,
    #adminmenu li.opensub > a.menu-top,
    #adminmenu li > a.menu-top:focus {
      background-color: ${colors.highlight} !important;
      color: ${colors.text} !important;
    }

    /* Admin menu submenu */
    #adminmenu .wp-submenu,
    #adminmenu .wp-has-current-submenu .wp-submenu,
    #adminmenu .wp-has-current-submenu.opensub .wp-submenu {
      background-color: ${this.lightenColor(colors.base, 20)} !important;
    }

    #adminmenu .wp-submenu a {
      color: rgba(${this.hexToRgb(colors.text)}, 0.8) !important;
    }

    #adminmenu .wp-submenu a:hover,
    #adminmenu .wp-submenu a:focus {
      color: ${colors.text} !important;
    }

    /* Admin menu current item */
    #adminmenu li.current a.menu-top,
    #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu {
      background-color: ${colors.highlight} !important;
      color: ${colors.text} !important;
    }

    /* Admin bar */
    #wpadminbar {
      background-color: ${colors.base} !important;
    }

    #wpadminbar .ab-item,
    #wpadminbar a.ab-item {
      color: ${colors.text} !important;
    }

    #wpadminbar .ab-icon:before,
    #wpadminbar .ab-item:before,
    #wpadminbar .ab-item:after {
      color: rgba(${this.hexToRgb(colors.text)}, 0.8) !important;
    }

    #wpadminbar:not(.mobile) .ab-top-menu > li:hover > .ab-item,
    #wpadminbar:not(.mobile) .ab-top-menu > li > .ab-item:focus {
      background-color: ${colors.highlight} !important;
      color: ${colors.text} !important;
    }

    /* Admin bar submenu */
    #wpadminbar .menupop .ab-sub-wrapper {
      background-color: ${this.lightenColor(colors.base, 20)} !important;
    }

    /* Primary buttons */
    .wp-core-ui .button-primary {
      background: ${colors.base} !important;
      border-color: ${this.darkenColor(colors.base, 15)} !important;
      color: ${colors.text} !important;
    }

    .wp-core-ui .button-primary:hover,
    .wp-core-ui .button-primary:focus {
      background: ${colors.highlight} !important;
      border-color: ${colors.highlight} !important;
      color: ${colors.text} !important;
    }

    /* Links */
    a {
      color: ${colors.base} !important;
    }

    a:hover,
    a:focus {
      color: ${colors.highlight} !important;
    }

    /* Notifications and highlights */
    .notice,
    .update-nag,
    #adminmenu .awaiting-mod,
    #adminmenu .update-plugins {
      background-color: ${colors.notification} !important;
      color: ${colors.text} !important;
    }

    /* Background elements */
    body.wp-admin {
      background-color: ${colors.background} !important;
    }
  `;

      $previewStyle.text(css);
    },

    /**
     * Helper: Convert hex to RGB
     */
    hexToRgb: function (hex) {
      hex = hex.replace('#', '');

      if (hex.length === 3) {
        hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
      }

      var r = parseInt(hex.substring(0, 2), 16);
      var g = parseInt(hex.substring(2, 4), 16);
      var b = parseInt(hex.substring(4, 6), 16);

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
     * Helper: Lighten color
     */
    lightenColor: function (color, percent) {
      var num = parseInt(color.replace('#', ''), 16),
        amt = Math.round(2.55 * percent),
        R = (num >> 16) + amt,
        G = (num >> 8 & 0x00FF) + amt,
        B = (num & 0x0000FF) + amt;

      return '#' + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
        (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
        (B < 255 ? B < 1 ? 0 : B : 255)).toString(16).slice(1);
    },

    /**
     * Helper: Darken color
     */
    darkenColor: function (color, percent) {
      var num = parseInt(color.replace('#', ''), 16),
        amt = Math.round(2.55 * percent) * -1,
        R = (num >> 16) + amt,
        G = (num >> 8 & 0x00FF) + amt,
        B = (num & 0x0000FF) + amt;

      return '#' + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
        (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
        (B < 255 ? B < 1 ? 0 : B : 255)).toString(16).slice(1);
    },

    /**
     * Update custom scheme preview
     */
    updateCustomSchemePreview: function ($changedInput) {
      // Get current values from all color inputs
      var cssVars = {
        '--ppc-custom-base': $('#custom_scheme_base').val() || '#655997',
        '--ppc-custom-text': $('#custom_scheme_text').val() || '#ffffff',
        '--ppc-custom-highlight': $('#custom_scheme_highlight').val() || '#8a7bb9',
        '--ppc-custom-notification': $('#custom_scheme_notification').val() || '#d63638',
        '--ppc-custom-background': $('#custom_scheme_background').val() || '#f0f0f1'
      };

      // Update CSS variables for preview area
      var cssString = '';
      $.each(cssVars, function (key, value) {
        cssString += key + ': ' + value + '; ';
      });

      // Apply to preview area
      $('.custom-scheme-preview-area').attr('style', function (i, style) {
        // Keep the existing non-CSS variable styles (border, padding, etc.)
        // The display: none; is to temprarily hide this for now
        var baseStyle = 'display: none;margin-top: 20px; padding: 15px; border-radius: 4px; border: 1px solid #dcdcde; ';
        return baseStyle + cssString;
      });

      // Update preview buttons with inline styles (more reliable than CSS variables)
      $('.custom-scheme-preview-area > div:first-child > div').each(function (index) {
        var $btn = $(this);
        if (index === 0) {
          $btn.css({
            'background': cssVars['--ppc-custom-base'],
            'color': cssVars['--ppc-custom-text']
          });
        } else if (index === 1) {
          $btn.css({
            'background': cssVars['--ppc-custom-highlight'],
            'color': cssVars['--ppc-custom-text']
          });
        } else if (index === 2) {
          $btn.css({
            'background': cssVars['--ppc-custom-notification'],
            'color': cssVars['--ppc-custom-text']
          });
        }
      });

      // Update the custom scheme color palette preview
      if ($('.color-option.ppc-custom-scheme').hasClass('selected')) {
        var baseColor = cssVars['--ppc-custom-base'];
        var textColor = cssVars['--ppc-custom-text'];
        var highlightColor = cssVars['--ppc-custom-highlight'];
        var notificationColor = cssVars['--ppc-custom-notification'];
        var backgroundColor = cssVars['--ppc-custom-background'];

        // Update the color palette preview
        var $palette = $('.color-option.ppc-custom-scheme .color-palette');
        if ($palette.length) {
          $palette.html(''); // Clear existing colors

          // Add the 5 color shades as shown in the palette
          [baseColor, highlightColor, textColor, notificationColor, backgroundColor].forEach(function (color) {
            $('<div>')
              .addClass('color-palette-shade')
              .css('background-color', color)
              .appendTo($palette);
          });
        }

        // Update gradient preview
        $('.custom-scheme-preview').css('background', 'linear-gradient(135deg, ' + baseColor + ' 25%, ' + highlightColor + ' 75%)');
      }
    },

    /**
     * Handle edit custom scheme icon click
     */
    handleEditCustomScheme: function (e) {
      e.stopPropagation();

      // Select custom scheme if not already selected
      if (PP_Admin_Styles.currentScheme !== 'publishpress-custom') {
        $('#color-publishpress-custom').prop('checked', true);
        $('.color-option.ppc-custom-scheme').click();
      }

      // Ensure custom scheme editor is visible
      $('.custom-scheme-editor').slideDown(300);

      // Scroll to editor
      $('html, body').animate({
        scrollTop: $('.custom-scheme-editor').offset().top - 100
      }, 300);
    }
  };

  // Initialize when document is ready
  $(document).ready(function () {
    PP_Admin_Styles.init();
  });

})(jQuery);