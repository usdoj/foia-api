(function ($, drupalSettings, Drupal) {
  Drupal.behaviors.foia_ui_validation = {
    attach: function attach() {
      jQuery.validator.setDefaults({
        debug: true,
        success: "valid"
      });

      jQuery.validator.addMethod("lessThanEqualSum", function(value, element, params) {
        var sum = 0;
        params.forEach(function(param) {
          sum += Number($(param).val());
        });
        return this.optional(element) || value <= sum;
      }, "Must equal less than equal a sum of other fields.");

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
          var containerPaneID = $(element).parents("details.vertical-tabs__pane").eq(1).attr('id');
          var parentVerticalTabMenuItem = $(element).parents(".vertical-tabs").last().children('.vertical-tabs__menu').find('a[href="#' + containerPaneID + '"]').parent();
          if(parentVerticalTabMenuItem.attr('data-invalid')) {
            if(parentVerticalTabMenuItemDataInvalid.indexOf($(element).attr('id')) === -1) {
              parentVerticalTabMenuItemDataInvalid = parentVerticalTabMenuItem.attr('data-invalid') + ',' + $(element).attr('id');
            }
          }
          else {
            parentVerticalTabMenuItemDataInvalid = $(element).attr('id');
          }
          parentVerticalTabMenuItem.attr('data-invalid', parentVerticalTabMenuItemDataInvalid);
          parentVerticalTabMenuItem.addClass('has-validation-error');
        },

        unhighlight: function(element, errorClass, validClass) {
          $(element).removeClass(errorClass).addClass(validClass);
          var containerPaneID = $(element).parents("details.vertical-tabs__pane").eq(1).attr('id');
          var parentVerticalTabMenuItem = $(element).parents(".vertical-tabs").last().children('.vertical-tabs__menu').find('a[href="#' + containerPaneID + '"]').parent();
          parentVerticalTabMenuItemDataInvalid = parentVerticalTabMenuItem.attr('data-invalid');
          if( parentVerticalTabMenuItemDataInvalid && parentVerticalTabMenuItemDataInvalid.indexOf($(element).attr('id')) > -1) {
            var dataInvalidArr = parentVerticalTabMenuItem.attr('data-invalid').split(',');
            var index = dataInvalidArr.indexOf($(element).attr('id'));
            if (index > -1) {
              dataInvalidArr.splice(index, 1);
            }
            var dataInvalid = dataInvalidArr.join();
            parentVerticalTabMenuItem.attr('data-invalid', dataInvalid);
            parentVerticalTabMenuItem.removeClass('has-validation-error');
          }
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
          "field_foia_requests_vb1[0][subform][field_full_denials_ex][0][value]": {
            lessThanEqualSum: [
              "#edit-field-overall-vb3-ex-1-0-value",
              "#edit-field-overall-vb3-ex-2-0-value",
              "#edit-field-overall-vb3-ex-3-0-value",
              "#edit-field-overall-vb3-ex-4-0-value",
              "#edit-field-overall-vb3-ex-5-0-value",
              "#edit-field-overall-vb3-ex-6-0-value",
              "#edit-field-overall-vb3-ex-7-a-0-value",
              "#edit-field-overall-vb3-ex-7-b-0-value",
              "#edit-field-overall-vb3-ex-7-c-0-value",
              "#edit-field-overall-vb3-ex-7-d-0-value",
              "#edit-field-overall-vb3-ex-7-e-0-value",
              "#edit-field-overall-vb3-ex-7-f-0-value",
              "#edit-field-overall-vb3-ex-8-0-value",
              "#edit-field-overall-vb3-ex-9-0-value"
            ]
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
          "field_foia_requests_vb1[0][subform][field_full_denials_ex][0][value]": {
            lessThanEqualSum: "This field should be no more than the sum of the fields overall_vb3_ex_1 through overall_vb3_ex_9."
          }
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
