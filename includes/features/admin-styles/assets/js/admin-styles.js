/**
 * Admin Styles JavaScript for PublishPress Capabilities
 *
 * @package PublishPress\Capabilities
 * @since 2.30.0
 */

(function ($) {
  'use strict';

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

      // Ensure PublishPress Custom is first in the list
      this.reorderColorSchemes();

      // If custom scheme is selected on page load, update preview
      if (this.currentScheme === 'publishpress-custom') {
          this.updateCustomSchemePreview();
      }
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
              PP_Admin_Styles.updateCustomSchemePreview($input);

              // Update custom scheme URL with new timestamp
              if (PP_Admin_Styles.currentScheme === 'publishpress-custom') {
                PP_Admin_Styles.updateCustomSchemeUrl();
              }
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
        if (target === 'admin_logo') {
          PP_Admin_Styles.showLogoPreview(attachment.url);
        }
      });

      // Open the media library
      frame.open();
    },

    /**
     * Show logo preview
     */
    showLogoPreview: function (url) {
      // Remove existing preview
      $('.logo-preview').remove();

      // Add new preview
      var $preview = $('<p class="cme-subtext logo-preview" style="margin-top: 5px;">' +
        '<img src="' + url + '" style="max-width: 20px; max-height: 20px; vertical-align: middle; margin-right: 5px;">' +
        ppCapabilitiesAdminStyles.labels.currentLogoPreview +
        '</p>');

      $('#admin_logo').after($preview);
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
        $('.logo-preview').remove();
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
    handleSchemeSelection: function() {
    var $option = $(this);
    var scheme = $option.find('input[type="radio"]').val();

    // If already selected, do nothing
    if ($option.hasClass('selected')) {
        return;
    }

    // Helper to ensure we only use safe stylesheet URLs
    function isSafeCssUrl(url) {
        if (typeof url !== 'string') {
            return false;
        }

        url = url.trim();
        if (!url) {
            return false;
        }

        // Disallow javascript: and other explicit dangerous schemes
        var lower = url.toLowerCase();
        if (lower.indexOf('javascript:') === 0 || lower.indexOf('data:') === 0) {
            return false;
        }

        // Allow relative URLs, protocol-relative URLs, and http/https URLs
        return (
            lower.indexOf('http://') === 0 ||
            lower.indexOf('https://') === 0 ||
            lower.indexOf('//') === 0 ||
            lower.charAt(0) === '/' ||
            // simple relative path or filename (no scheme part before colon)
            lower.indexOf(':') === -1
        );
    }

    // Update selected state
    $('.color-option').removeClass('selected');
    $('.color-checkbox').removeClass('checked').empty();
    $option.addClass('selected');
    $option.find('.color-checkbox').addClass('checked').html('<span class="dashicons dashicons-yes"></span>');

    // Update hidden input
    $('#admin_color_scheme').val(scheme);

    // Load the colors stylesheet
    // The default color scheme won't have one, so we'll need to create an element
    if (PP_Admin_Styles.$stylesheet.length === 0) {
        PP_Admin_Styles.$stylesheet = $('<link rel="stylesheet" />').appendTo('head');
    }

    // Set the stylesheet URL
    var cssUrl = $option.children('.css_url').val();
    if (cssUrl && isSafeCssUrl(cssUrl)) {
      // Add cache busting timestamp
      var newUrl = cssUrl;
      if (this.currentScheme === 'publishpress-custom') {
        newUrl = cssUrl.replace(/(\?|&)ver=\d+/, '') + '&ver=' + Date.now();
      }

      PP_Admin_Styles.$stylesheet.attr('href', newUrl);
    }

    // Repaint icons if wp.svgPainter exists (WordPress 5.7+)
    if (typeof wp !== 'undefined' && wp.svgPainter) {
        try {
            var colors = JSON.parse($option.children('.icon_colors').val());
        } catch (error) {}

        if (colors) {
            wp.svgPainter.setColors(colors.icons);
            wp.svgPainter.paint();
        }
    }

    // Show/hide custom scheme editor
    if (scheme === 'publishpress-custom') {
        $('.custom-scheme-editor').slideDown(300);
        PP_Admin_Styles.updateCustomSchemePreview();
    } else {
        $('.custom-scheme-editor').slideUp(300);
    }
    PP_Admin_Styles.currentScheme = scheme;
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
     * Handle custom color change
     */
    handleCustomColorChange: function () {
      var $input = $(this);
      PP_Admin_Styles.updateCustomSchemePreview($input);

      // If custom scheme is selected, update the CSS URL
      if (PP_Admin_Styles.currentScheme === 'publishpress-custom') {
        PP_Admin_Styles.updateCustomSchemeUrl();
      }
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