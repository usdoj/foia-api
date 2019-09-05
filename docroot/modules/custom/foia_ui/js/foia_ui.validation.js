(function ($, drupalSettings, Drupal) {
  Drupal.behaviors.foia_ui_validation = {
    attach: function attach() {
      jQuery.validator.setDefaults({
        debug: true,
        success: "valid"
      });

      $('#node-annual-foia-report-data-form').validate({

        invalidHandler: function(event, validator) {
          var errors = validator.numberOfInvalids();
          if (errors) {
            var message = errors == 1 ? 'You missed 1 field. It has been highlighted.' : 'You missed ' + errors + ' fields.  They have been highlighted.';
            alert(message);
          }
        },

        highlight: function(element, errorClass, validClass) {
          $(element).addClass(errorClass).removeClass(validClass);
          var parentVerticalTabs = $(element).parents("details.vertical-tabs__pane");
          var containerPaneID = parentVerticalTabs.eq(1).attr('id');
          var parentVerticalTabMenu = $(element).parents(".vertical-tabs").last();
          var parentVerticalTabMenuItem = parentVerticalTabMenu.children('.vertical-tabs__menu').find('a[href="#' + containerPaneID + '"]').parent();
          parentVerticalTabMenuItem.addClass('has-validation-error');
        },

        unhighlight: function(element, errorClass, validClass) {
          $(element).removeClass(errorClass).addClass(validClass);
          var test = $(element).closest("details.vertical-tabs__pane");
          test.css('background-color', '');
        },

        rules: {
          // V.A. FOIA Requests V. A.
          "field_foia_requests_va[0][subform][field_req_processed_yr][0][value]" : {
            equalTo: "#edit-field-foia-requests-vb1-0-subform-field-total-0-value"
          },
          // V.A. Agency Overall Number of Requests Processed in Fiscal Year
          "field_overall_req_processed_yr[0][value]" : {
            equalTo: "#edit-field-overall-vb1-total-0-value"
          },
          // V.B.(1) Agency Overall Number of Full Denials Based on Exemptions
          "field_overall_xiie1_received_cur[0][value]": {
            min: 2,
            max: 4
          }
        },

        messages: {
          // V.A. FOIA Requests V. A.
          "field_foia_requests_va[0][subform][field_req_processed_yr][0][value]": {
              equalTo: "Must match corresponding agency V.B.(1) Total"
          },
          // V.A. Agency Overall Number of Requests Processed in Fiscal Year
          "field_overall_req_processed_yr[0][value]" : {
            equalTo: "Must match V.B.(1) Agency Overall Total"
          },
        }
      });
      $('input#edit-submit').prop('disabled', true);
      $('input#edit-validate-button').on('click', function(event) {
        $('#node-annual-foia-report-data-form').valid();
        $('input#edit-submit').prop('disabled', false);
        event.preventDefault();
      });
    }
  };

})(jQuery, drupalSettings, Drupal);
