(function ($, drupalSettings, Drupal) {
  Drupal.behaviors.foia_ui_validation = {
    attach: function attach() {
      jQuery.validator.setDefaults({
        debug: true,
        success: "valid"
      });

      /**
       * Custom validation methods
       */
      // lessThanEqualTo
      $.validator.addMethod( "lessThanEqualTo", function( value, element, param ) {
        var target = $( param );
        return value <= Number(target.val());
    }, "Please enter a lesser value." );

       // greaterThanEqualTo
      $.validator.addMethod( "greaterThanEqualTo", function( value, element, param ) {
        var target = $( param );
        return value >= Number(target.val());
    }, "Please enter a greater value." );

       // greaterThanZero
       $.validator.addMethod( "greaterThanZero", function( value, element, param ) {
        return value > 0;
    }, "Please enter a value greater than zero." );

    // lessThanEqualSum
      jQuery.validator.addMethod("lessThanEqualSum", function(value, element, params) {
        var sum = 0;
        params.forEach(function(param) {
          sum += Number($(param).val());
        });
        return this.optional(element) || value <= sum;
      }, "Must equal less than equal a sum of other fields.");

      // lessThanEqualComp
      jQuery.validator.addMethod("lessThanEqualComp", function(value, element, params) {
        var elementAgencyComponent = $(element).parents('.paragraphs-subform').find("select[name*='field_agency_component']").val();
        for (var i = 0; i < params.length; i++){
          var paramAgencyComponent = $(params[i]).parents('.paragraphs-subform').find("select[name*='field_agency_component']").val();
          if (paramAgencyComponent == elementAgencyComponent) {
            var target = Number($( params[i] ).val());
            return this.optional(element) || value <= target;
          }
        }
      }, "Must be less than or equal to a field.");

      // greaterThanEqualComp
      jQuery.validator.addMethod("greaterThanEqualComp", function(value, element, params) {
        var elementAgencyComponent = $(element).parents('.paragraphs-subform').find("select[name*='field_agency_component']").val();
        for (var i = 0; i < params.length; i++){
          var paramAgencyComponent = $(params[i]).parents('.paragraphs-subform').find("select[name*='field_agency_component']").val();
          if (paramAgencyComponent == elementAgencyComponent) {
            var target = Number($( params[i] ).val());
            return this.optional(element) || value >= target;
          }
        }
      }, "Must be greater than or equal to a field.");

      // betweenMinMaxComp
      jQuery.validator.addMethod("betweenMinMaxComp", function(value, element, params) {
        var valuesArray = [];
        for (var i = 0; i < params.length; i++){
          valuesArray.push(Number($( params[i] ).val()));
        }
        var min = Math.min.apply(null, valuesArray);
        var max = Math.max.apply(null, valuesArray);
        return this.optional(element) || (value > min) && (value < max);
      }, "Must be between the smallest and largest values.");

      // equalToLowestComp
      jQuery.validator.addMethod("equalToLowestComp", function(value, element, params) {
        var valuesArray = [];
        for (var i = 0; i < params.length; i++){
          valuesArray.push(Number($( params[i] ).val()));
        }
        return this.optional(element) || (value == Math.min.apply(null, valuesArray));
      }, "Must equal the lowest value.");

      // equalToHighestComp
      jQuery.validator.addMethod("equalToHighestComp", function(value, element, params) {
        var valuesArray = [];
        for (var i = 0; i < params.length; i++){
          valuesArray.push(Number($( params[i] ).val()));
        }
        return this.optional(element) || (value == Math.max.apply(null, valuesArray));
      }, "Must equal the highest value.");

      // notAverageComp
      jQuery.validator.addMethod("notAverageComp", function(value, element, params) {
        var sum = 0;
        for (var i = 0; i < params.length; i++){
          sum += Number($( params[i] ).val());
        }
        var average = sum/params.length;
        return this.optional(element) || !(value == average);
      }, "Must not be equal to the average.");

      /**
       * Form validation call
       */
      $('#node-annual-foia-report-data-form').validate({

        // Display aggregate field validation popup message.
        invalidHandler: function(event, validator) {
          var errors = validator.numberOfInvalids();
          if (errors) {
            var message = errors == 1 ? '1 field is invalid and has been highlighted.' : '' + errors + ' fields are invalid and have been highlighted.';
            alert(message);
          }
        },

        // Highlight vertical tabs that contain invalid fields
        highlight: function(element, errorClass, validClass) {
          $(element).addClass(errorClass).removeClass(validClass);
          var containerPaneID = $(element).parents("details.vertical-tabs__pane").last().attr('id');
          var parentVerticalTabMenuItem = $(element).parents(".vertical-tabs").last().children('.vertical-tabs__menu').find('a[href="#' + containerPaneID + '"]').parent();
          if(parentVerticalTabMenuItem.attr('data-invalid')) {
            var parentVerticalTabMenuItemDataInvalid = parentVerticalTabMenuItem.attr('data-invalid');
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

        // Remove highlighting from vertical tabs when field validation passes
        unhighlight: function(element, errorClass, validClass) {
          $(element).removeClass(errorClass).addClass(validClass);
          var containerPaneID = $(element).parents("details.vertical-tabs__pane").last().attr('id');
          var parentVerticalTabMenuItem = $(element).parents(".vertical-tabs").last().children('.vertical-tabs__menu').find('a[href="#' + containerPaneID + '"]').parent();
          var parentVerticalTabMenuItemDataInvalid = parentVerticalTabMenuItem.attr('data-invalid');
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
        }
      });

      // Disable Submit button until Validate button is clicked.
      $('input#edit-submit').prop('disabled', true);
      $('input#edit-validate-button').on('click', function(event) {
        $('#node-annual-foia-report-data-form').valid();
        $('input#edit-submit').prop('disabled', false);
        event.preventDefault();
      });

      /**
       * Validation rules
       */
      // V.A. FOIA Requests V. A.
      $( "#edit-field-foia-requests-va-0-subform-field-req-processed-yr-0-value").rules( "add", {
        equalTo: "#edit-field-foia-requests-vb1-0-subform-field-total-0-value",
        messages: {
          equalTo: "Must match corresponding agency V.B.(1) Total"
        }
      });

      // V.A. Agency Overall Number of Requests Processed in Fiscal Year
      $( "#edit-field-overall-req-processed-yr-0-value").rules( "add", {
        equalTo: "#edit-field-overall-vb1-total-0-value",
        messages: {
          equalTo: "Must match V.B.(1) Agency Overall Total"
        }
      });

      // V.B.(1) Agency Overall Number of Full Denials Based on Exemptions
      $( "#edit-field-overall-vb1-full-denials-e-0-value").rules( "add", {
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
        ],
        messages: {
          lessThanEqualSum: "This field should be no more than the sum of the fields Overall V.B.(3) Ex.1 through V.B.(3) Ex.9."
        }
      });

      // V.B.(1) Agency Overall Other*
      $( "#edit-field-overall-vb1-oth-0-value").rules( "add", {
        equalTo: "#edit-field-overall-vb2-total-0-value",
        messages: {
          equalTo: "Must match V.B.(2) Agency Overall Total"
        }
      });

      // V.B.(1) Agency Overall Total
      $( "#edit-field-overall-vb1-total-0-value").rules( "add", {
        required: true,
        equalTo: "#edit-field-overall-req-processed-yr-0-value",
        messages: {
          equalTo: "Must match V.A. Agency Overall Number of Requests Processed in Fiscal Year"
        }
      });

      // VI.A. Agency Overall Number of Appeals Processed in Fiscal Year
      $( "#edit-field-overall-via-app-proc-yr-0-value").rules( "add", {
        equalTo: "#edit-field-overall-vib-total-0-value",
        messages: {
          equalTo: "Must match VI.B. Agency Overall Total"
        }
      });

      // VI.B. Administrative Appeals
      $( "input[name*='field_admin_app_vib']").filter("input[name*='field_closed_oth_app']").rules( "add", {
        greaterThanEqualComp: $("input[name*='field_admin_app_vic2']").filter("input[name*='field_oth']"),
        messages: {
          greaterThanEqualComp: "Must be greater equal to the # of appeals closed for other reasons in VI.B."
        }
      });

      // VI.C.(3). REASONS FOR DENIAL ON APPEAL -- "OTHER" REASONS
      $( "#edit-field-overall-vic3-num-relied-up-0-value").rules( "add", {
        equalTo: "#edit-field-overall-vic2-oth-0-value",
        messages: {
          equalTo: "Must match VI. C. (2) \"Agency Overall Other\""
        }
      });

      // VI.B. DISPOSITION OF ADMINISTRATIVE APPEALS -- ALL PROCESSED APPEALS
      $( "#edit-field-overall-vib-closed-oth-app-0-value").rules( "add", {
        lessThanEqualSum: [
          "#edit-field-admin-app-vic2-0-subform-field-no-rec-0-value",
          "#edit-field-admin-app-vic2-0-subform-field-rec-refer-initial-0-value",
          "#edit-field-admin-app-vic2-0-subform-field-req-withdrawn-0-value",
          "#edit-field-admin-app-vic2-0-subform-field-fee-related-reason-0-value",
          "#edit-field-admin-app-vic2-0-subform-field-rec-not-desc-0-value",
          "#edit-field-admin-app-vic2-0-subform-field-imp-req-oth-reason-0-value",
          "#edit-field-admin-app-vic2-0-subform-field-not-agency-record-0-value",
          "#edit-field-admin-app-vic2-0-subform-field-dup-req-0-value",
          "#edit-field-admin-app-vic2-0-subform-field-req-in-lit-0-value",
          "#edit-field-admin-app-vic2-0-subform-field-app-denial-exp-0-value",
          "#edit-field-admin-app-vic2-0-subform-field-oth-0-value"
        ],
        messages: {
          lessThanEqualSum: "This field should be no more than the sum of the fields in VI.C.(2)."
        }
      });

      // VI.C.(4) - Administrative Appeals
      $( "input[name*='field_admin_app_vic4']").filter("input[name*='field_low_num_days']").rules( "add", {
        lessThanEqualComp: $( "input[name*='field_admin_app_vic4']").filter("input[name*='field_high_num_days']"),
        greaterThanZero: true,
        messages: {
          lessThanEqualComp: "Must be lower than or equal to the highest number of days."
        }
      });

      // VI.C.(4) - Agency Overall Median Number of Days
      $( "#edit-field-overall-vic4-med-num-days-0-value").rules( "add", {
        betweenMinMaxComp: $("input[name*='field_admin_app_vic4']").filter("input[name*='field_med_num_days']"),
        notAverageComp: $("input[name*='field_admin_app_vic4']").filter("input[name*='field_med_num_days']"),
        messages: {
          betweenMinMaxComp: "This field should be between the largest and smallest values of Median Number of Days",
          notAverageComp: "Warning: should not equal to the average Median Number of Days."
        }
      });

      // VI.C.(4) - Agency Overall Lowest Number of Days
      $( "#edit-field-overall-vic4-low-num-days-0-value").rules( "add", {
        lessThanEqualTo: "#edit-field-overall-vic4-high-num-days-0-value",
        greaterThanZero: true,
        messages: {
          lessThanEqualTo: "Must be lower than or equal to the highest number of days."
        }
      });

      // VI.C.(4) - Agency Overall Highest Number of Days
      $( "#edit-field-overall-vic4-high-num-days-0-value").rules( "add", {
        greaterThanEqualTo: "#edit-field-overall-vic4-low-num-days-0-value",
        greaterThanZero: true,
        messages: {
          greaterThanEqualTo: "Must be greater than or equal to the lowest number of days."
        }
      });

      // VII.A. Simple - Agency Overall Median Number of Days
      $( "#edit-field-overall-viia-sim-med-0-value").rules( "add", {
        betweenMinMaxComp: $("input[name*='field_proc_req_viia']").filter("input[name*='field_sim_med']"),
        notAverageComp: $("input[name*='field_proc_req_viia']").filter("input[name*='field_sim_med']"),
        messages: {
          betweenMinMaxComp: "This field should be between the largest and smallest values of Median Number of Days",
          notAverageComp: "Warning: should not equal to the average Median Number of Days."
        }
      });

      // VII.A. Simple - Agency Overall Lowest Number of Days
      $( "#edit-field-overall-viia-sim-low-0-value").rules( "add", {
        equalToLowestComp: $("input[name*='field_proc_req_viia']").filter("input[name*='field_sim_low']"),
        messages: {
          equalToLowestComp: "Must equal smallest value of Lowest number of days."
        }
      });

      // VII.A. Simple - Agency Overall Highest Number of Days
      $( "#edit-field-overall-viia-sim-high-0-value").rules( "add", {
        equalToHighestComp: $("input[name*='field_proc_req_viia']").filter("input[name*='field_sim_high']"),
        messages: {
          equalToHighestComp: "Must equal largest value of Highest number of days."
        }
      });

      // VII.A. Complex - Agency Overall Median Number of Days
      $( "#edit-field-overall-viia-comp-med-0-value").rules( "add", {
        betweenMinMaxComp: $("input[name*='field_proc_req_viia']").filter("input[name*='field_comp_med']"),
        notAverageComp: $("input[name*='field_proc_req_viia']").filter("input[name*='field_comp_med']"),
        messages: {
          betweenMinMaxComp: "This field should be between the largest and smallest values of Median Number of Days",
          notAverageComp: "Warning: should not equal to the average Median Number of Days."
        }
      });

      // VII.A. Complex - Agency Overall Lowest Number of Days
      $( "#edit-field-overall-viia-comp-low-0-value").rules( "add", {
        equalToLowestComp: $("input[name*='field_proc_req_viia']").filter("input[name*='field_comp_low']"),
        messages: {
          equalToLowestComp: "Must equal smallest value of Lowest number of days."
        }
      });

      // VII.A. Complex - Agency Overall Highest Number of Days
      $( "#edit-field-overall-viia-comp-high-0-value").rules( "add", {
        equalToHighestComp: $("input[name*='field_proc_req_viia']").filter("input[name*='field_comp_high']"),
        messages: {
          equalToHighestComp: "Must equal largest value of Highest number of days."
        }
      });

      // VII.A. Expedited Processing - Agency Overall Median Number of Days
      $( "#edit-field-overall-viia-exp-med-0-value").rules( "add", {
        betweenMinMaxComp: $("input[name*='field_proc_req_viia']").filter("input[name*='field_exp_med']"),
        notAverageComp: $("input[name*='field_proc_req_viia']").filter("input[name*='field_exp_med']"),
        messages: {
          betweenMinMaxComp: "This field should be between the largest and smallest values of Median Number of Days",
          notAverageComp: "Warning: should not equal to the average Median Number of Days."
        }
      });

      // VII.A. Expedited Processing - Agency Overall Lowest Number of Days
      $( "#edit-field-overall-viia-exp-low-0-value").rules( "add", {
        equalToLowestComp: $("input[name*='field_proc_req_viia']").filter("input[name*='field_exp_low']"),
        messages: {
          equalToLowestComp: "Must equal smallest value of Lowest number of days."
        }
      });

      // VII.A. Expedited Processing - Agency Overall Highest Number of Days
      $( "#edit-field-overall-viia-exp-high-0-value").rules( "add", {
        equalToHighestComp: $("input[name*='field_proc_req_viia']").filter("input[name*='field_exp_high']"),
        messages: {
          equalToHighestComp: "Must equal largest value of Highest number of days."
        }
      });
    }
  };

})(jQuery, drupalSettings, Drupal);
