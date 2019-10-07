(function ($, drupalSettings, Drupal) {
  Drupal.behaviors.foia_ui_validation = {
    attach: function attach() {
      jQuery.validator.setDefaults({
        ignore: ".ignore-validation",
        onsubmit: false
      });

      /**
       * Treat "N/A", "n/a", and "<1" values as zero
       */
      function convertSpecialToZero(value) {
        switch (String(value).toLowerCase()) {
          case "n/a":
          case "<1":
            return Number(0);
            break;
          default:
            return value;
        }
      }

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
        var target = convertSpecialToZero($( param ).val());
        value = convertSpecialToZero(value);
        return value <= Number(target);
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

      // notNegative
      $.validator.addMethod( "notNegative", function( value, element, param ) {
        return value >= 0;
      }, "Please enter zero or a positive number." );

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

      // lessThanEqualSumComp
      jQuery.validator.addMethod("lessThanEqualSumComp", function(value, element, params) {
        value = convertSpecialToZero(value);
        var sum = 0;
        var elementAgencyComponent = $(element).parents('.paragraphs-subform').find("select[name*='field_agency_component']").val();
        for (var i = 0; i < params.length; i++){
          for (var j = 0; j < params[i].length; j++){
            var paramAgencyComponent = $(params[i][j]).parents('.paragraphs-subform').find("select[name*='field_agency_component']").val();
            if (paramAgencyComponent == elementAgencyComponent) {
              sum += Number(convertSpecialToZero($( params[i][j] ).val()));
            }
          }
        }
        return this.optional(element) || value <= sum;
      }, "Must be less than or equal to a field.");

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
        value = convertSpecialToZero(value);
        var elementAgencyComponent = $(element).parents('.paragraphs-subform').find("select[name*='field_agency_component']").val();
        for (var i = 0; i < params.length; i++){
          var paramAgencyComponent = $(params[i]).parents('.paragraphs-subform').find("select[name*='field_agency_component']").val();
          if (paramAgencyComponent == elementAgencyComponent) {
            var target = Number(convertSpecialToZero($( params[i] ).val()));
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

      jQuery.validator.addMethod("greaterThanEqualSumComp", function(value, element, params) {
        var elementAgencyComponent = $(element).parents('.paragraphs-subform').find("select[name*='field_agency_component']").val();
        var sum = 0;
        for (var i = 0; i < params.length; i++) {
          var paramAgencyComponent = $(params[i]).parents('.paragraphs-subform').find("select[name*='field_agency_component']").val();
          if (paramAgencyComponent == elementAgencyComponent) {
            sum += Number($( params[i] ).val());
          }
        }
        return this.optional(element) || value >= sum;
      }, "Must be greater than or equal to sum of the fields.");

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

      // vb1matchDispositionComp: hard-coded for V.B.(1)
      jQuery.validator.addMethod("vb1matchDispositionComp", function(value, element, params) {
        var allReqProcessedYr = $( "input[name*='field_foia_requests_va']").filter("input[name*='field_req_processed_yr']");
        var elementAgencyComponent = $(element).parents('.paragraphs-subform').find("select[name*='field_agency_component']").val();
        var reqProcessedYr = null;
        var otherField = null;
        var sumVIICTotals = 0;

        for (var i = 0; i < allReqProcessedYr.length; i++){
          var paramAgencyComponent = $(allReqProcessedYr[i]).parents('.paragraphs-subform').find("select[name*='field_agency_component']").val();
          if (paramAgencyComponent == elementAgencyComponent) {
            var reqProcessedYr = Number($( allReqProcessedYr[i] ).val());
          }
        }

        for (var i = 0; i < params.viicn.length; i++){
          var paramAgencyComponent = $(params.viicn[i]).parents('.paragraphs-subform').find("select[name*='field_agency_component']").val();
          if (paramAgencyComponent == elementAgencyComponent) {
            sumVIICTotals += Number($( params.viicn[i] ).val());
          }
        }

        for (var i = 0; i < params.otherField.length; i++){
          var paramAgencyComponent = $(params.otherField[i]).parents('.paragraphs-subform').find("select[name*='field_agency_component']").val();
          if (paramAgencyComponent == elementAgencyComponent) {
            otherField = Number($( params.otherField[i] ).val());
          }
        }

        // reqProcessedYr == sumVIICTotals - Improper Request for Other - Records Not Reasonably Described
        return (reqProcessedYr == sumVIICTotals - Number(value) - otherField);

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
      $('body').append('<div id="validation-overlay"' +
          ' class="validation-overlay hidden">' +
          '<div class="ajax-progress ajax-progress-fullscreen">' +
          '<img src="/core/misc/loading-small.gif" />' +
          '</div></div>');
      $('input#edit-validate-button').on('click', function(event) {
        event.preventDefault();

        $('.validation-overlay').removeClass('hidden');

        // To validate select drop-downs as required, they must have an
        // empty machine value.
        $("select > option[value='_none']").val('');


        // Allow some time for the overlay to render.
        setTimeout(function() {
          // Validate form
          $(drupalSettings.foiaUI.foiaUISettings.formID).valid();

          $('.validation-overlay').addClass('hidden');

          // Empty drop-downs can still be submitted though, so restore
          // Drupal's default empty drop-down value to avoid "An illegal
          // choice has been detected" error in that scenario.
          $("select > option[value='']").val('_none');

          // Enable form Save button
          $('input#edit-submit').prop('disabled', false);
        }, 100);
      });

      /**
       * Validation rules
       *
       * Note: All validation rules can be bypassed on submit.
       */
      // Require all Annual Report fields.
      $(".form-text, .form-textarea, .form-select, .form-number, .form-date").not('#edit-revision-log-0-value').not('[readonly]').each(function() {
        $(this).rules( "add", {
        required: true,
        });
      });

       // V.A. FOIA Requests
      $( "input[name*='field_foia_requests_va']").filter("input[name*='field_req_processed_yr']").each(function() {
        $(this).rules( "add", {
          equalToComp: $( "input[name*='field_foia_requests_vb1']").filter("input[name*='field_total']"),
          greaterThanEqualSumComp: $( "input[name*='field_proc_req_viic1']").filter("input[name*='field_total']")
            .add( "input[name*='field_proc_req_viic2']").filter("input[name*='field_total']")
            .add( "input[name*='field_proc_req_viic3']").filter("input[name*='field_total']"),
          messages: {
            equalToComp: "Must match corresponding agency V.B.(1) Total",
            greaterThanEqualSumComp: "Must be greater than or equal to sum of all of the Totals of VII.C.1, 2, and 3 for the corresponding agency/component"
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

      // V.B.(1) Records Not Reasonably Described
      $( "input[name*='field_foia_requests_vb1']").filter("input[name*='field_rec_not_desc']").each(function() {
        $(this).rules( "add", {
          vb1matchDispositionComp: {
            viicn: $( "input[name*='field_proc_req_viic1']").filter("input[name*='field_total']")
              .add( "input[name*='field_proc_req_viic2']").filter("input[name*='field_total']")
              .add( "input[name*='field_proc_req_viic3']").filter("input[name*='field_total']"),
            otherField: $( "input[name*='field_foia_requests_vb1']").filter("input[name*='field_imp_req_oth_reason']"),
          },
          messages: {
            vb1matchDispositionComp: "Should equal V.A. Requests Processed less sum of Total of VII.C.1, 2, and 3. less Improper FOIA Request for Other Reason"
          }
        });
      });

      // V.B.(1) Improper FOIA Request for Other Reason
      $( "input[name*='field_foia_requests_vb1']").filter("input[name*='field_imp_req_oth_reason']").each(function() {
        $(this).rules( "add", {
          vb1matchDispositionComp: {
            viicn: $( "input[name*='field_proc_req_viic1']").filter("input[name*='field_total']")
              .add( "input[name*='field_proc_req_viic2']").filter("input[name*='field_total']")
              .add( "input[name*='field_proc_req_viic3']").filter("input[name*='field_total']"),
            otherField: $( "input[name*='field_foia_requests_vb1']").filter("input[name*='field_rec_not_desc']"),
          },
          messages: {
            vb1matchDispositionComp: "Should equal V.A. Requests Processed less sum of Total of VII.C.1, 2, and 3. less Records Not Reasonably Described"
          }
        });
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

      // V.B. (2) (Component) Number of Times "Other" Reason Was Relied Upon
      $( "#edit-field-foia-requests-vb2-0-subform-field-foia-req-vb2-info-0-subform-field-num-relied-upon-0-value").rules( "add", {
        notNegative: true,
        messages: {
          notNegative: "Must be a zero or a positive number."
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

      // VI.C.(3). Agency Overall Total
      $( "#edit-field-overall-vic3-total-0-value").rules( "add", {
        equalTo: "#edit-field-overall-vic2-oth-0-value",
        messages: {
          equalTo: "Must match VI.C.(2) \"Agency Overall Other\""
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
          lessThanEqualToNA: "This should be less than the number of days for \"3rd\"."
        }
      });

      // VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS / 3rd
      $( "#edit-field-overall-vic5-num-day-3-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-vic5-num-day-2-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"2nd\"."
        }
      });

      // VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS / 2nd
      $( "#edit-field-overall-vic5-num-day-2-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-vic5-num-day-1-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"Overall\"."
        }
      });

      // VI.C. (5) - Agency Components
      // For the next 9 rules, each is comparing the value to the one lower
      // than it ( i.e., field 10 is less than field 9, field 9 is less than
      // field 8, etc).  Unlike the above group, this is for the agency
      // component part of the form.
      // VI.C.(5). (Component) TEN OLDEST PENDING ADMINISTRATIVE APPEALS / 10th
      $( "#edit-field-admin-app-vic5-0-subform-field-num-days-10-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-admin-app-vic5-0-subform-field-num-days-9-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"9th\"."
        }
      });

      // VI.C.(5). (Component) TEN OLDEST PENDING ADMINISTRATIVE APPEALS / 9th
      $( "#edit-field-admin-app-vic5-0-subform-field-num-days-9-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-admin-app-vic5-0-subform-field-num-days-8-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"8th\"."
        }
      });

      // VI.C.(5). (Component) TEN OLDEST PENDING ADMINISTRATIVE APPEALS / 8th
      $( "#edit-field-admin-app-vic5-0-subform-field-num-days-8-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-admin-app-vic5-0-subform-field-num-days-7-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"7th\"."
        }
      });

      // VI.C.(5). (Component) TEN OLDEST PENDING ADMINISTRATIVE APPEALS / 7th
      $( "#edit-field-admin-app-vic5-0-subform-field-num-days-7-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-admin-app-vic5-0-subform-field-num-days-6-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"6th\"."
        }
      });

      // VI.C.(5). (Component) TEN OLDEST PENDING ADMINISTRATIVE APPEALS / 6th
      $( "#edit-field-admin-app-vic5-0-subform-field-num-days-6-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-admin-app-vic5-0-subform-field-num-days-5-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"5th\"."
        }
      });

      // VI.C.(5). (Component) TEN OLDEST PENDING ADMINISTRATIVE APPEALS / 5th
      $( "#edit-field-admin-app-vic5-0-subform-field-num-days-5-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-admin-app-vic5-0-subform-field-num-days-4-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"4th\"."
        }
      });

      // VI.C.(5). (Component) TEN OLDEST PENDING ADMINISTRATIVE APPEALS / 4th
      $( "#edit-field-admin-app-vic5-0-subform-field-num-days-4-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-admin-app-vic5-0-subform-field-num-days-3-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"3rd\"."
        }
      });

      // VI.C.(5). (Component) TEN OLDEST PENDING ADMINISTRATIVE APPEALS / 3rd
      $( "#edit-field-admin-app-vic5-0-subform-field-num-days-3-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-admin-app-vic5-0-subform-field-num-days-2-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"2nd\"."
        }
      });

      // VI.C.(5). (Component) TEN OLDEST PENDING ADMINISTRATIVE APPEALS / 2nd
      $( "#edit-field-admin-app-vic5-0-subform-field-num-days-2-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-admin-app-vic5-0-subform-field-num-days-1-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"Oldest\"."
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


      // XII.C. FOIA Requests and Administrative Appeals
      $("input[name*='field_foia_xiic']").filter("input[name*='field_num_days_1']").each(function() {
        $(this).rules( "add", {
          ifGreaterThanZeroComp: $("input[name*='field_foia_xiib']").filter("input[name*='field_pend_end_yr']"),
          messages: {
            ifGreaterThanZeroComp: "If there are consultations pending at end of year for XII.B, there must be entries in this section for that component.",
          }
        });
      });

      // XII.A. Number of Backlogged Requests as of End of Fiscal Year
      $( "input[name*='field_foia_xiia']").filter("input[name*='field_back_req_end_yr']").each(function() {
        $(this).rules( "add", {
          lessThanEqualSumComp: [
            $("input[name*='field_pending_requests_vii_d_']").filter("input[name*='field_sim_pend']"),
            $("input[name*='field_pending_requests_vii_d_']").filter("input[name*='field_comp_pend']"),
            $("input[name*='field_pending_requests_vii_d_']").filter("input[name*='field_exp_pend']"),
          ],
          messages: {
            lessThanEqualSumComp: "Must be equal to or less than the corresponding sum total of Simple, Complex, and Expedited pending requests from VII.D."
          }
        });
      });

      // XII.A. Number of Backlogged Appeals as of End of Fiscal Year
      $( "input[name*='field_foia_xiia']").filter("input[name*='field_back_app_end_yr']").each(function() {
        $(this).rules( "add", {
          lessThanEqualComp: $("input[name*='field_admin_app_via']").filter("input[name*='field_app_pend_end_yr']"),
          messages: {
            lessThanEqualComp: "Must be equal to or less than VI.A.(1). corresponding Number of Appeals Pending as of End of Fiscal Year"
          }
        });
      });

      // XII.A. Agency Overall Number of Backlogged Requests as of End of Fiscal Year
      $( "#edit-field-overall-xiia-back-req-end-0-value").rules( "add", {
        lessThanEqualSum: [
          "#edit-field-overall-viid-sim-pend-0-value",
          "#edit-field-overall-viid-comp-pend-0-value",
          "#edit-field-overall-viid-exp-pend-0-value",
        ],
        messages: {
          lessThanEqualSum: "Must be equal to or less than the sum total of overall Simple, Complex, and Expedited pending requests from VII.D."
        }
      });

      // XII.A. Agency Overall Number of Backlogged Appeals as of End of Fiscal Year
      $( "#edit-field-overall-xiia-back-app-end-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-via-app-pend-endyr-0-value",
        messages: {
          lessThanEqualToNA: "Must be equal to or less than VI.A.(1). Agency Overall Number of Appeals Pending as of End of Fiscal Year",
        }
      });

      // For the next 9 rules, each is comparing the value to the one lower
      // than it ( i.e., field 10 is less than field 9, field 9 is less than
      // field 8, etc).
      // XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY / 10th
      $( "#edit-field-overall-xiic-num-days-10-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-xiic-num-days-9-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"9th\"."
        }
      });

      // XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY / 9th
      $( "#edit-field-overall-xiic-num-days-9-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-xiic-num-days-8-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"8th\"."
        }
      });

      // XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY / 8th
      $( "#edit-field-overall-xiic-num-days-8-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-xiic-num-days-7-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"7th\"."
        }
      });

      // XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY / 7th
      $( "#edit-field-overall-xiic-num-days-7-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-xiic-num-days-6-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"6th\"."
        }
      });

      // XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY / 6th
      $( "#edit-field-overall-xiic-num-days-6-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-xiic-num-days-5-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"5th\"."
        }
      });

      // XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY / 5th
      $( "#edit-field-overall-xiic-num-days-5-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-xiic-num-days-4-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"4th\"."
        }
      });

      // XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY / 4th
      $( "#edit-field-overall-xiic-num-days-4-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-xiic-num-days-3-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"3d\"."
        }
      });

      // XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY / 3d
      $( "#edit-field-overall-xiic-num-days-3-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-xiic-num-days-2-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"2d\"."
        }
      });

      // XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY / 2d
      $( "#edit-field-overall-xiic-num-days-2-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-overall-xiic-num-days-1-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"Overall\"."
        }
      });

      // For the next 9 rules, each is comparing the value to the one lower
      // than it ( i.e., field 10 is less than field 9, field 9 is less than
      // field 8, etc).  This is the agency component part of the form.
      // XII.C. (Agency Components) CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY / 10th
      $( "#edit-field-foia-xiic-0-subform-field-num-days-10-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-foia-xiic-0-subform-field-num-days-9-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"9th\"."
        }
      });

      // XII.C. (Agency Components) CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY / 9th
      $( "#edit-field-foia-xiic-0-subform-field-num-days-9-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-foia-xiic-0-subform-field-num-days-8-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"8th\"."
        }
      });

      // XII.C. (Agency Components) CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY / 8th
      $( "#edit-field-foia-xiic-0-subform-field-num-days-8-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-foia-xiic-0-subform-field-num-days-7-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"7th\"."
        }
      });

      // XII.C. (Agency Components) CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY / 7th
      $( "#edit-field-foia-xiic-0-subform-field-num-days-7-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-foia-xiic-0-subform-field-num-days-6-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"6th\"."
        }
      });

      // XII.C. (Agency Components) CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY / 6th
      $( "#edit-field-foia-xiic-0-subform-field-num-days-6-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-foia-xiic-0-subform-field-num-days-5-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"5th\"."
        }
      });

      // XII.C. (Agency Components) CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY / 5th
      $( "#edit-field-foia-xiic-0-subform-field-num-days-5-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-foia-xiic-0-subform-field-num-days-4-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"4th\"."
        }
      });

      // XII.C. (Agency Components) CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY / 4th
      $( "#edit-field-foia-xiic-0-subform-field-num-days-4-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-foia-xiic-0-subform-field-num-days-3-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"3d\"."
        }
      });

      // XII.C. (Agency Components) CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY / 3d
      $( "#edit-field-foia-xiic-0-subform-field-num-days-3-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-foia-xiic-0-subform-field-num-days-2-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"2d\"."
        }
      });

      // XII.C. (Agency Components) CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY / 2d
      $( "#edit-field-foia-xiic-0-subform-field-num-days-2-0-value").rules( "add", {
        lessThanEqualToNA: "#edit-field-foia-xiic-0-subform-field-num-days-1-0-value",
        messages: {
          lessThanEqualToNA: "This should be less than the number of days for \"Overall\"."
        }
      });

      // XII.D.(1). Number Received During Fiscal Year from Current Annual Report
      $( "input[name*='field_foia_xiid1']").filter("input[name*='field_received_cur_yr']").each(function() {
        $(this).rules( "add", {
          equalToComp: $( "input[name*='field_foia_requests_va']").filter("input[name*='field_req_received_yr']"),
          messages: {
            equalToComp: "Must match V.A.(1). Number of Requests Received in Fiscal Year for corresponding agency/component"
          }
        });
      });

      // XII.D.(1). Number Processed During Fiscal Year from Current Annual Report
      $( "input[name*='field_foia_xiid1']").filter("input[name*='field_proc_cur_yr']").each(function() {
        $(this).rules( "add", {
          equalToComp: $( "input[name*='field_foia_requests_va']").filter("input[name*='field_req_processed_yr']"),
          messages: {
            equalToComp: "Must match V.A.(1). Number of Requests Processed in Fiscal Year for corresponding agency/component"
          }
        });
      });

      // XII.D.(1). Agency Overall Number Received During Fiscal Year from Current Annual Report
      $( "#edit-field-overall-xiid1-received-cur-0-value").rules( "add", {
        equalTo: "#edit-field-overall-req-received-yr-0-value",
        messages: {
          equalTo: "Must match V.A.(1). Agency Overall Number of Requests Received in Fiscal Year",
        }
      });

      // XII.D.(1). Agency Overall Number Processed During Fiscal Year from Current Annual Report
      $( "#edit-field-overall-xiid1-proc-cur-yr-0-value").rules( "add", {
        equalTo: "#edit-field-overall-req-processed-yr-0-value",
        messages: {
          equalTo: "Must match V.A.(1). Agency Overall Number of Requests Processed in Fiscal Year",
        }
      });

      // XII.E.(1). Number Received During Fiscal Year from Current Annual Report
      $( "input[name*='field_foia_xiie1']").filter("input[name*='field_received_cur_yr']").each(function() {
        $(this).rules( "add", {
          equalToComp: $( "input[name*='field_admin_app_via']").filter("input[name*='field_app_received_yr']"),
          messages: {
            equalToComp: "Must match V.A.(1). Number of Requests Received in Fiscal Year for corresponding agency/component"
          }
        });
      });

      // XII.E.(1). Number Processed During Fiscal Year from Current Annual Report
      $( "input[name*='field_foia_xiie1']").filter("input[name*='field_proc_cur_yr']").each(function() {
        $(this).rules( "add", {
          equalToComp: $( "input[name*='field_admin_app_via']").filter("input[name*='field_app_processed_yr']"),
          messages: {
            equalToComp: "Must match V.A.(1). Number of Requests Processed in Fiscal Year for corresponding agency/component"
          }
        });
      });

      // XII.D.(2). Number of Backlogged Requests as of End of the Fiscal Year from Current Annual Report
      $( "input[name*='field_foia_xiid2']").filter("input[name*='field_back_cur_yr']").each(function() {
        $(this).rules( "add", {
          equalToComp: $( "input[name*='field_foia_xiia']").filter("input[name*='field_back_app_end_yr']"),
          messages: {
            equalToComp: "Must match XII.A. Number of Backlogged Requests as of End of Fiscal Year",
          }
        });
      });

      // XII.E.(1). Agency Overall Number Received During Fiscal Year from Current Annual Report
      $( "#edit-field-overall-xiie1-received-cur-0-value").rules( "add", {
        equalTo: "#edit-field-overall-via-app-recd-yr-0-value",
        messages: {
          equalTo: "Must match VI.A.(1). Agency Overall Number of Requests Received in Fiscal Year",
        }
      });

      // XII.E.(1). Agency Overall Number Processed During Fiscal Year from Current Annual Report
      $( "#edit-field-overall-xiie1-proc-cur-yr-0-value").rules( "add", {
        equalTo: "#edit-field-overall-via-app-proc-yr-0-value",
        messages: {
          equalTo: "Must match VI.A.(1). Agency Overall Number of Requests Processed in Fiscal Year",
        }
      });
      
      // XII.D.(2). Agency Overall Number of Backlogged Requests as of End of the Fiscal Year from Current Annual Report
      $( "#edit-field-overall-xiid2-back-cur-yr-0-value").rules( "add", {
        equalTo: "#edit-field-overall-xiia-back-req-end-0-value",
        messages: {
          equalTo: "Must match XII.A. Agency Overall Number of Backlogged Requests as of End of Fiscal Year",
        }
      });

      // XII.E.(2). Agency Overall Number of Backlogged Appeals as of End of the Fiscal Year from Current Annual Report
      $( "#edit-field-overall-xiie2-back-cur-yr-0-value").rules( "add", {
        equalTo: "#edit-field-overall-xiia-back-app-end-0-value",
        messages: {
          equalTo: "Must match XII.A. Agency Overall Number of Backlogged Appeals as of End of Fiscal Year",
        }
      });
    }
  };

})(jQuery, drupalSettings, Drupal);
