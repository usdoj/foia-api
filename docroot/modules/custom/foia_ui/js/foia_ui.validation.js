(function ($, drupalSettings, Drupal) {
  Drupal.behaviors.foia_ui_validation = {
    attach: function attach() {
      jQuery.validator.setDefaults({
        ignore: ".ignore-validation",
        onsubmit: false
      });

      /**
       * Custom validation methods
       */
      // lessThanEqualTo
      $.validator.addMethod( "lessThanEqualTo", function( value, element, param ) {
        var target = $( param );
        return value <= Number(target.val());
      }, "Please enter a lesser value." );

      // lessThanEqualToNA
      $.validator.addMethod( "lessThanEqualToNA", function( value, element, param ) {
        var target = $( param );
        // Treat N/A like 0.
        if ( String(value).toLowerCase() == "n/a" ) {
          value = 0;
        }
        if ( String(target).toLowerCase() == "n/a" ) {
          target = 0;
        }
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

      // ifGreaterThanZeroComp
      jQuery.validator.addMethod("ifGreaterThanZeroComp", function(value, element, params) {
        var elementAgencyComponent = $(element).parents('.paragraphs-subform').find("select[name*='field_agency_component']").val();
        for (var i = 0; i < params.length; i++){
          var paramAgencyComponent = $(params[i]).parents('.paragraphs-subform').find("select[name*='field_agency_component']").val();
          if (paramAgencyComponent == elementAgencyComponent) {
            var target = Number($( params[i] ).val());
          }
        }
        if (target > 0 ) {
          return this.optional(element) || value > 0;
        }
        else {
          return  this.optional(element) || true;
        }
      }, "Must be greater than or equal to a field.");

      // equalSumComp
      jQuery.validator.addMethod("equalSumComp", function(value, element, params) {
        var sum = 0;
        for (var i = 0; i < params.length; i++){
          sum += Number($( params[i] ).val());
        }
        return this.optional(element) || value == sum;
      }, "Must equal sum of fields.");

      // lessThanEqualSum
      jQuery.validator.addMethod("lessThanEqualSum", function(value, element, params) {
        var sum = 0;
        params.forEach(function(param) {
          sum += Number($(param).val());
        });
        return this.optional(element) || value <= sum;
      }, "Must equal less than equal a sum of other fields.");

      // equalToComp
      jQuery.validator.addMethod("equalToComp", function(value, element, params) {
        var elementAgencyComponent = $(element).parents('.paragraphs-subform').find("select[name*='field_agency_component']").val();
        for (var i = 0; i < params.length; i++){
          var paramAgencyComponent = $(params[i]).parents('.paragraphs-subform').find("select[name*='field_agency_component']").val();
          if (paramAgencyComponent == elementAgencyComponent) {
            var target = Number($( params[i] ).val());
            return this.optional(element) || value == target;
          }
        }
      }, "Must be equal to a field.");

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
        return this.optional(element) || (value >= min) && (value <= max);
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
      $(drupalSettings.foiaUI.foiaUISettings.formID).validate({

        // Display aggregate field validation popup message.
        invalidHandler: function(event, validator) {
          var errors = validator.numberOfInvalids();
          if (errors) {
            var message = errors == 1 ? '1 field is invalid and has been highlighted.' : '' + errors + ' fields are invalid and have been highlighted.';
            // alert(message);
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
        $(drupalSettings.foiaUI.foiaUISettings.formID).valid();
        $('input#edit-submit').prop('disabled', false);
        event.preventDefault();
      });

      /**
       * Validation rules
       */
      // V.A. FOIA Requests
      $( "input[name*='field_foia_requests_va']").filter("input[name*='field_req_processed_yr']").each(function() {
        $(this).rules( "add", {
          equalToComp: $( "input[name*='field_foia_requests_vb1']").filter("input[name*='field_total']"),
          messages: {
            equalToComp: "Must match corresponding agency V.B.(1) Total"
          }
        });
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
      $( "input[name*='field_admin_app_vib']").filter("input[name*='field_closed_oth_app']").each(function() {
        $(this).rules( "add", {
          lessThanEqualComp: $("input[name*='field_admin_app_vic2']").filter("input[name*='field_oth']"),
          messages: {
            lessThanEqualComp: "Must be less than or equal to the total # of reasons for denial in VI.C.(2)"
          }
        });
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
          "#edit-field-overall-vic2-no-rec-0-value",
          "#edit-field-overall-vic2-rec-refer-ini-0-value",
          "#edit-field-overall-vic2-req-withdrawn-0-value",
          "#edit-field-overall-vic2-fee-rel-reas-0-value",
          "#edit-field-overall-vic2-rec-not-desc-0-value",
          "#edit-field-overall-vic2-imp-req-oth-0-value",
          "#edit-field-overall-vic2-not-agency-re-0-value",
          "#edit-field-overall-vic2-dup-req-0-value",
          "#edit-field-overall-vic2-req-in-lit-0-value",
          "#edit-field-overall-vic2-app-denial-ex-0-value",
          "#edit-field-overall-vic2-oth-0-value"
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

      // For the next 9 rules, each is comparing the value to the one lower
      // than it ( i.e., field 10 is less than field 9, field 9 is less than
      // field 8, etc).
      // VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS / 10th
      $( "#edit-field-overall-vic5-num-day-10-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-vic5-num-day-9-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"9th\"."
        }
      });

      // VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS / 9th
      $( "#edit-field-overall-vic5-num-day-9-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-vic5-num-day-8-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"8th\"."
        }
      });

      // VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS / 8th
      $( "#edit-field-overall-vic5-num-day-8-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-vic5-num-day-7-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"7th\"."
        }
      });

      // VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS / 7th
      $( "#edit-field-overall-vic5-num-day-7-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-vic5-num-day-6-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"6th\"."
        }
      });

      // VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS / 6th
      $( "#edit-field-overall-vic5-num-day-6-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-vic5-num-day-5-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"5th\"."
        }
      });

      // VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS / 5th
      $( "#edit-field-overall-vic5-num-day-5-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-vic5-num-day-4-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"4th\"."
        }
      });

      // VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS / 4th
      $( "#edit-field-overall-vic5-num-day-4-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-vic5-num-day-3-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"3d\"."
        }
      });

      // VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS / 3d
      $( "#edit-field-overall-vic5-num-day-3-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-vic5-num-day-2-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"2d\"."
        }
      });

      // VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS / 2d
      $( "#edit-field-overall-vic5-num-day-2-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-vic5-num-day-1-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"Overall\"."
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

      // VII.B. Simple - Agency Overall Median Number of Days
      $( "#edit-field-overall-viib-sim-med-0-value").rules( "add", {
        betweenMinMaxComp: $("input[name*='field_proc_req_viib']").filter("input[name*='field_sim_med']"),
        notAverageComp: $("input[name*='field_proc_req_viib']").filter("input[name*='field_sim_med']"),
        messages: {
          betweenMinMaxComp: "This field should be between the largest and smallest values of Median Number of Days",
          notAverageComp: "Warning: should not equal to the average Median Number of Days."
        }
      });

      // VII.B. Simple - Agency Overall Lowest Number of Days
      $( "#edit-field-overall-viib-sim-low-0-value").rules( "add", {
        equalToLowestComp: $("input[name*='field_proc_req_viib']").filter("input[name*='field_sim_low']"),
        messages: {
          equalToLowestComp: "Must equal smallest value of Lowest number of days."
        }
      });

      // VII.B. Simple - Agency Overall Highest Number of Days
      $( "#edit-field-overall-viib-sim-high-0-value").rules( "add", {
        equalToHighestComp: $("input[name*='field_proc_req_viib']").filter("input[name*='field_sim_high']"),
        messages: {
          equalToHighestComp: "Must equal largest value of Highest number of days."
        }
      });

      // VII.B. Complex - Agency Overall Median Number of Days
      $( "#edit-field-overall-viib-comp-med-0-value").rules( "add", {
        betweenMinMaxComp: $("input[name*='field_proc_req_viib']").filter("input[name*='field_comp_med']"),
        notAverageComp: $("input[name*='field_proc_req_viib']").filter("input[name*='field_comp_med']"),
        messages: {
          betweenMinMaxComp: "This field should be between the largest and smallest values of Median Number of Days",
          notAverageComp: "Warning: should not equal to the average Median Number of Days."
        }
      });

      // VII.B. Complex - Agency Overall Lowest Number of Days
      $( "#edit-field-overall-viib-comp-low-0-value").rules( "add", {
        equalToLowestComp: $("input[name*='field_proc_req_viib']").filter("input[name*='field_comp_low']"),
        messages: {
          equalToLowestComp: "Must equal smallest value of Lowest number of days."
        }
      });

      // VII.B. Complex - Agency Overall Highest Number of Days
      $( "#edit-field-overall-viib-comp-high-0-value").rules( "add", {
        equalToHighestComp: $("input[name*='field_proc_req_viib']").filter("input[name*='field_comp_high']"),
        messages: {
          equalToHighestComp: "Must equal largest value of Highest number of days."
        }
      });

      // VII.B. Expedited Processing - Agency Overall Median Number of Days
      $( "#edit-field-overall-viib-exp-med-0-value").rules( "add", {
        betweenMinMaxComp: $("input[name*='field_proc_req_viib']").filter("input[name*='field_exp_med']"),
        notAverageComp: $("input[name*='field_proc_req_viib']").filter("input[name*='field_exp_med']"),
        messages: {
          betweenMinMaxComp: "This field should be between the largest and smallest values of Median Number of Days",
          notAverageComp: "Warning: should not equal to the average Median Number of Days."
        }
      });

      // VII.B. Expedited Processing - Agency Overall Lowest Number of Days
      $( "#edit-field-overall-viib-exp-low-0-value").rules( "add", {
        equalToLowestComp: $("input[name*='field_proc_req_viib']").filter("input[name*='field_exp_low']"),
        messages: {
          equalToLowestComp: "Must equal smallest value of Lowest number of days."
        }
      });

      // VII.B. Expedited Processing - Agency Overall Highest Number of Days
      $( "#edit-field-overall-viib-exp-high-0-value").rules( "add", {
        equalToHighestComp: $("input[name*='field_proc_req_viib']").filter("input[name*='field_exp_high']"),
        messages: {
          equalToHighestComp: "Must equal largest value of Highest number of days."
        }
      });

      // VII.D. Simple - Number Pending
      $( "#edit-field-overall-viid-sim-pend-0-value").rules( "add", {
        equalSumComp: $("input[name*='field_pending_requests_vii_d_']").filter("input[name*='field_sim_pend']"),
        messages: {
          equalSumComp: "Must equal sum of Number Pending."
        }
      });

      // VII.D. Simple - Agency Overall Median Number of Days
      $( "#edit-field-overall-viid-sim-med-0-value").rules( "add", {
        betweenMinMaxComp: $("input[name*='field_pending_requests_vii_d_']").filter("input[name*='field_sim_med']"),
        notAverageComp: $("input[name*='field_pending_requests_vii_d_']").filter("input[name*='field_sim_med']"),
        messages: {
          betweenMinMaxComp: "This field should be between the largest and smallest values of Median Number of Days",
          notAverageComp: "Warning: should not equal to the average Median Number of Days."
        }
      });

      // VII.D. Complex - Number Pending
      $( "#edit-field-overall-viid-comp-pend-0-value").rules( "add", {
        equalSumComp: $("input[name*='field_pending_requests_vii_d_']").filter("input[name*='field_comp_pend']"),
        messages: {
          equalSumComp: "Must equal sum of Number Pending."
        }
      });

      // VII.D. Complex - Agency Overall Median Number of Days
      $( "#edit-field-overall-viid-comp-med-0-value").rules( "add", {
        betweenMinMaxComp: $("input[name*='field_pending_requests_vii_d_']").filter("input[name*='field_comp_med']"),
        notAverageComp: $("input[name*='field_pending_requests_vii_d_']").filter("input[name*='field_comp_med']"),
        messages: {
          betweenMinMaxComp: "This field should be between the largest and smallest values of Median Number of Days",
          notAverageComp: "Warning: should not equal to the average Median Number of Days."
        }
      });

      // VII.D. Expedited Processing - Number Pending
      $( "#edit-field-overall-viid-exp-pend-0-value").rules( "add", {
        equalSumComp: $("input[name*='field_pending_requests_vii_d_']").filter("input[name*='field_exp_pend']"),
        messages: {
          equalSumComp: "Must equal sum of Number Pending."
        }
      });

      // VII.D. Expedited - Agency Overall Median Number of Days
      $( "#edit-field-overall-viid-exp-med-0-value").rules( "add", {
        betweenMinMaxComp: $("input[name*='field_pending_requests_vii_d_']").filter("input[name*='field_exp_med']"),
        notAverageComp: $("input[name*='field_pending_requests_vii_d_']").filter("input[name*='field_exp_med']"),
        messages: {
          betweenMinMaxComp: "This field should be between the largest and smallest values of Median Number of Days",
          notAverageComp: "Warning: should not equal to the average Median Number of Days."
        }
      });

      // For the next 9 rules, each is comparing the value to the one lower
      // than it ( i.e., field 10 is less than field 9, field 9 is less than
      // field 8, etc).
      // VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS / 10th
      $( "#edit-field-overall-viie-num-days-10-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-viie-num-days-9-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"9th\"."
        }
      });

      // VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS / 9th
      $( "#edit-field-overall-viie-num-days-9-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-viie-num-days-8-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"8th\"."
        }
      });

      // VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS / 8th
      $( "#edit-field-overall-viie-num-days-8-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-viie-num-days-7-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"7th\"."
        }
      });

      // VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS / 7th
      $( "#edit-field-overall-viie-num-days-7-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-viie-num-days-6-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"6th\"."
        }
      });

      // VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS / 6th
      $( "#edit-field-overall-viie-num-days-6-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-viie-num-days-5-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"5th\"."
        }
      });

      // VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS / 5th
      $( "#edit-field-overall-viie-num-days-5-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-viie-num-days-4-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"4th\"."
        }
      });

      // VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS / 4th
      $( "#edit-field-overall-viie-num-days-4-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-viie-num-days-3-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"3d\"."
        }
      });

      // VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS / 3d
      $( "#edit-field-overall-viie-num-days-3-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-viie-num-days-2-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"2d\"."
        }
      });

      // VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS / 2d
      $( "#edit-field-overall-viie-num-days-2-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-viie-num-days-1-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"Overall\"."
        }
      });

      // VIII.A. Agency Overall Median Number of Days to Adjudicate
      $( "#edit-field-overall-viiia-med-days-jud-0-value").rules( "add", {
        betweenMinMaxComp: $("input[name*='field_req_viiia']").filter("input[name*='field_med_days_jud']"),
        notAverageComp: $("input[name*='field_req_viiia']").filter("input[name*='field_med_days_jud']"),
        messages: {
          betweenMinMaxComp: "This field should be between the largest and smallest values of Median Number of Days",
          notAverageComp: "Warning: should not equal to the average Median Number of Days."
        }
      });

      // VIII.A. Agency Overall Number Adjudicated Within Ten Calendar Days
      $( "#edit-field-overall-viiia-num-jud-w10-0-value").rules( "add", {
        lessThanEqualSum: [
          "#edit-field-overall-viiia-num-grant-0-value",
          "#edit-field-overall-viiia-num-denied-0-value"
        ],
        messages: {
          lessThanEqualSum: "This field should be should be equal to or less than the # granted + # denied.",
        }
      });

      // VIII.B. Agency Overall Median Number of Days to Adjudicate
      $( "#edit-field-overall-viiib-med-days-jud-0-value").rules( "add", {
        betweenMinMaxComp: $("input[name*='field_req_viiib']").filter("input[name*='field_med_days_jud']"),
        notAverageComp: $("input[name*='field_req_viiib']").filter("input[name*='field_med_days_jud']"),
        messages: {
          betweenMinMaxComp: "This field should be between the largest and smallest values of Median Number of Days",
          notAverageComp: "Warning: should not equal to the average Median Number of Days."
        }
      });

      // IX. Total Number of "Full-Time FOIA Staff"
      $("input[name*='field_foia_pers_costs_ix']").filter("input[name*='field_total_staff']").each(function() {
        $(this).rules( "add", {
          ifGreaterThanZeroComp: $("input[name*='field_foia_requests_vb1']").filter("input[name*='field_total']"),
          messages: {
            ifGreaterThanZeroComp: "If requests were processed in V.B.(1), the total number of full-time FOIA staff must be greater than 0",
          }
        });
      });

      // IX. Agency Overall Total Number of "Full-Time FOIA Staff"
      // IX. Agency Overall Processing Costs
      $( "#edit-field-overall-ix-total-staff-0-value, #edit-field-overall-ix-total-costs-0-value").each(function() {
        $(this).rules( "add", {
          greaterThanZero: {
            depends: function() {
              return Number($("#edit-field-overall-vb1-total-0-value").val()) > 0;
            }
          },
          messages: {
            greaterThanZero: "Should be greater than zero, if requests were processed in V.B.(1).",
          }
        });
      });
    }
  };

})(jQuery, drupalSettings, Drupal);
