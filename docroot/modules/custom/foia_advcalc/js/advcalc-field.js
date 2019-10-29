(function ($, drupalSettings, Drupal) {
  Drupal.behaviors.advcalc_field = {
    attach: function attach() {

      /**
       * Converts value to number and "N/A", "n/a", and "<1" values to 0.
       *
       * @param value
       * @returns {number}
       */
      function specialNumber(value) {
        switch (String(value).toLowerCase()) {
          case "n/a":
            return Number(0);
            break;
          case "<1":
            return Number(0.1);
            break;
          default:
            return Number(value);
        }
      }

     /**
       * Converts number back to "<1" if between 0 and 1.
       *
       * @param {number}
       * @returns {value}
       */
      function displayLessThan(number) {
        if (number > 0 && number < 1) {
          return "<1";
        }
        else {
          return number;
        }
      }

      /**
       * Calculates overall value for a given component.
       *
       * @param {string} componentId
       *    The node edit paragraph component HTML fragment ID.
       * @param {string} componentFieldName
       *    The paragraph component field machine name.
       * @param {string} overallFieldID
       *    The calculated overall field HTML fragment ID.
       * @param {string} operator
       *    The operation to be performed, "<", ">", or "+".
       */
      function calcOverall(componentId, componentFieldName, overallFieldID, operator) {
        var ops = {
          '<': function(a, b) { return a < b },
          '>': function(a, b) { return a > b },
          '+': function(a, b) { return a + b },
        }
        var fields = $("input[id^='" + componentId + "']").filter("input[name*='" + componentFieldName + "']");
        fields.each(function() {
          $(this).once('advCalcOverall').on('change', {overallFieldID: overallFieldID, operator: operator}, function(event) {
            var output = null;
            fields.each(function () {
              value = $(this).val();
              if(value != '' && String(value).toLowerCase() != 'n/a') {
                if (output == null && value !== null) {
                  output = displayLessThan(specialNumber(value));
                }
                else if(value != undefined && ops[event.data.operator](specialNumber(value), specialNumber(output))) {
                  output = displayLessThan(specialNumber(value));
                }
              }
            });
            // Clear overall value if output is "NaN"
            if(output !== output) {
              $('#' + event.data.overallFieldID).val('');
            }
            else {
              $('#' + event.data.overallFieldID).val(output);
            }
          });
        });
      }

      // Fields from sections IX and X to calculate overall_x_perc_costs.
      $("#edit-field-overall-ix-proc-costs-0-value, #edit-field-overall-x-total-fees-0-value").change(function() {
        var overall_x_total_fees = Number($("#edit-field-overall-x-total-fees-0-value").val());
        if ( overall_x_total_fees > 0 ) {
          var overall_ix_proc_costs = Number($("#edit-field-overall-ix-proc-costs-0-value").val());
          var overall_x_perc_costs =  overall_x_total_fees /overall_ix_proc_costs;
          var overall_x_perc_costs = Math.round(overall_x_perc_costs * 10000) / 100; // Percent rounded to 2 decimal places
          $('#edit-field-overall-x-perc-costs-0-value').val(overall_x_perc_costs);
        }
      });

      // Fields from section VI A to calculate app_pend_start_yr.
      var via = $('input[id^="edit-field-admin-app-via"]');
      via.change(function() {
        var via_count = $("table[id^='field-admin-app-via-values'] tbody" + " tr");
        var via_vals = [];

        for (var i = 0; i < via_count.length; i++) {
          var edit_pend_start_name = "[data-drupal-selector='edit-field-admin-app-via-" + i + "-subform-field-app-pend-start-yr-0-value'";
          var edit_rec_name = "[data-drupal-selector='edit-field-admin-app-via-" + i + "-subform-field-app-received-yr-0-value'";
          var edit_proc_name = "[data-drupal-selector='edit-field-admin-app-via-" + i + "-subform-field-app-processed-yr-0-value'";
          var edit_pend_end_name = "[data-drupal-selector='edit-field-admin-app-via-" + i + "-subform-field-app-pend-end-yr-0-value'";
          via_vals[i] = {
            appPendStartYr: Number($(edit_pend_start_name).val()),
            appReceivedYr: Number($(edit_rec_name).val()),
            appProcessedYr: Number($(edit_proc_name).val()),
            appPendEndYr: function() {
              return this.appPendStartYr + this.appReceivedYr - this.appProcessedYr;
            }
          };
          $(edit_pend_end_name).val(via_vals[i].appPendEndYr());
        }

        $("#edit-field-overall-via-app-pend-start-0-value, #edit-field-overall-via-app-recd-yr-0-value, #edit-field-overall-via-app-proc-yr-0-value").change(function() {
          var overall_app_pend_start_yr = Number($("#edit-field-overall-via-app-pend-start-0-value").val());
          var overall_app_received_yr = Number($("#edit-field-overall-via-app-recd-yr-0-value").val());
          var overall_app_processed_yr = Number($("#edit-field-overall-via-app-proc-yr-0-value").val());
          var overall_app_pend_end_yr = overall_app_pend_start_yr + overall_app_received_yr - overall_app_processed_yr;
          $('#edit-field-overall-via-app-pend-endyr-0-value').val(overall_app_pend_end_yr);
        });

      });

      // Fields from section VI.C.(4) to calculate Lowest Number of Days.
      calcOverall('edit-field-admin-app-vic4', 'field_low_num_days', 'edit-field-overall-vic4-low-num-days-0-value', '<');
      // Fields from section VI.C.(4) to calculate Highest Number of Days.
      calcOverall('edit-field-admin-app-vic4', 'field_high_num_days', 'edit-field-overall-vic4-high-num-days-0-value', '>');

      // Fields from section VII.A. to calculate Lowest Number of Days.
      calcOverall('edit-field-proc-req-viia', 'field_sim_low', 'edit-field-overall-viia-sim-low-0-value', '<');
      // Fields from section VII.A. to calculate Highest Number of Days.
      calcOverall('edit-field-proc-req-viia', 'field_sim_high', 'edit-field-overall-viia-sim-high-0-value', '>');

      // Fields from section VII.A. to calculate Lowest Number of Days (complex).
      calcOverall('edit-field-proc-req-viia', 'field_comp_low', 'edit-field-overall-viia-comp-low-0-value', '<');
      // Fields from section VII.A. to calculate Highest Number of Days (complex).
      calcOverall('edit-field-proc-req-viia', 'field_comp_high', 'edit-field-overall-viia-comp-high-0-value', '>');

      // Fields from section VII.A. to calculate Lowest Number of Days (expedited).
      calcOverall('edit-field-proc-req-viia', 'field_exp_low', 'edit-field-overall-viia-exp-low-0-value', '<');
      // Fields from section VII.A. to calculate Highest Number of Days (expedited).
      calcOverall('edit-field-proc-req-viia', 'field_exp_high', 'edit-field-overall-viia-exp-high-0-value', '>');

      // Fields from section VII.B. to calculate Lowest Number of Days.
      calcOverall('edit-field-proc-req-viib', 'field_sim_low', 'edit-field-overall-viib-sim-low-0-value', '<');
      // Fields from section VII.B. to calculate Highest Number of Days.
      calcOverall('edit-field-proc-req-viib', 'field_sim_high', 'edit-field-overall-viib-sim-high-0-value', '>');

      // Fields from section VII.B. to calculate Lowest Number of Days (complex).
      calcOverall('edit-field-proc-req-viib', 'field_comp_low', 'edit-field-overall-viib-comp-low-0-value', '<');
      // Fields from section VII.B. to calculate Highest Number of Days (complex).
      calcOverall('edit-field-proc-req-viib', 'field_comp_high', 'edit-field-overall-viib-comp-high-0-value', '>');

      // Fields from section VII.B. to calculate Lowest Number of Days (expedited).
      calcOverall('edit-field-proc-req-viib', 'field_exp_low', 'edit-field-overall-viib-exp-low-0-value', '<');
      // Fields from section VII.B. to calculate Highest Number of Days (expedited).
      calcOverall('edit-field-proc-req-viib', 'field_exp_high', 'edit-field-overall-viib-exp-high-0-value', '>');

      // Fields from section VIII.A. to calculate Overall Number Adjudicated Within Ten Calendar Days.
      calcOverall('edit-field-req-viiia', 'field_num_jud_w10', 'edit-field-overall-viiia-num-jud-w10-0-value', '+');

      // Section V A automatically calculate field_req_pend_end_yr.
      // req_pend_start_yr + req_received_yr - req_processed_yr = req_pend_end_yr
      var via = $('input[id^="edit-field-foia-requests-va"]');
      via.change(function() {
        var va_count = $("table[id^='field-foia-requests-va-values'] tbody" + " tr");
        var va_vals = [];

        for (var i = 0; i < va_count.length; i++) {
          var edit_pend_start_name = "[data-drupal-selector='edit-field-foia-requests-va-" + i + "-subform-field-req-pend-start-yr-0-value'";
          var edit_rec_name = "[data-drupal-selector='edit-field-foia-requests-va-" + i + "-subform-field-req-received-yr-0-value'";
          var edit_proc_name = "[data-drupal-selector='edit-field-foia-requests-va-" + i + "-subform-field-req-processed-yr-0-value'";
          var edit_pend_end_name = "[data-drupal-selector='edit-field-foia-requests-va-" + i + "-subform-field-req-pend-end-yr-0-value'";
          va_vals[i] = {
            appPendStartYr: Number($(edit_pend_start_name).val()),
            appReceivedYr: Number($(edit_rec_name).val()),
            appProcessedYr: Number($(edit_proc_name).val()),
            appPendEndYr: function() {
                return this.appPendStartYr + this.appReceivedYr - this.appProcessedYr;
            }
          };
          $(edit_pend_end_name).val(va_vals[i].appPendEndYr());
        }

        $("#edit-field-overall-req-pend-start-yr-0-value, #edit-field-overall-req-received-yr-0-value, #edit-field-overall-req-processed-yr-0-value").change(function() {
            var overall_app_pend_start_yr = Number($("#edit-field-overall-req-pend-start-yr-0-value").val());
            var overall_app_received_yr = Number($("#edit-field-overall-req-received-yr-0-value").val());
            var overall_app_processed_yr = Number($("#edit-field-overall-req-processed-yr-0-value").val());
            var overall_app_pend_end_yr = overall_app_pend_start_yr + overall_app_received_yr - overall_app_processed_yr;
            $('#edit-field-overall-req-pend-end-yr-0-value').val(overall_app_pend_end_yr);
        });

      });

      // Section XII B automatically calculate field_pend_end_yr.
      // pend_start_yr + con_during_yr - proc_start_yr = pend_end_yr
      var xiib = $('input[id^="edit-field-foia-xiib"]');
      xiib.change(function() {
        var xiib_count = $("table[id^='field-foia-xiib-values'] tbody" + " tr");
        var xiib_vals = [];

        for (var i = 0; i < xiib_count.length; i++) {
          var edit_pend_start_name = "[data-drupal-selector='edit-field-foia-xiib-" + i + "-subform-field-pend-start-yr-0-value'";
          var edit_rec_name = "[data-drupal-selector='edit-field-foia-xiib-" + i + "-subform-field-con-during-yr-0-value'";
          var edit_proc_name = "[data-drupal-selector='edit-field-foia-xiib-" + i + "-subform-field-proc-start-yr-0-value'";
          var edit_pend_end_name = "[data-drupal-selector='edit-field-foia-xiib-" + i + "-subform-field-pend-end-yr-0-value'";
          xiib_vals[i] = {
              appPendStartYr: Number($(edit_pend_start_name).val()),
              appReceivedYr: Number($(edit_rec_name).val()),
              appProcessedYr: Number($(edit_proc_name).val()),
              appPendEndYr: function() {
                  return this.appPendStartYr + this.appReceivedYr - this.appProcessedYr;
              }
          };
          $(edit_pend_end_name).val(xiib_vals[i].appPendEndYr());
        }

        $("#edit-field-overall-xiib-pend-start-yr-0-value, #edit-field-overall-xiib-con-during-yr-0-value, #edit-field-overall-xiib-proc-start-yr-0-value").change(function() {
          var overall_pend_start_yr = Number($("#edit-field-overall-xiib-pend-start-yr-0-value").val());
          var overall_con_during_yr = Number($("#edit-field-overall-xiib-con-during-yr-0-value").val());
          var overall_processed_yr = Number($("#edit-field-overall-xiib-proc-start-yr-0-value").val());
          var overall_pend_end_yr = overall_pend_start_yr + overall_con_during_yr - overall_processed_yr;
          $('#edit-field-overall-xiib-pend-end-yr-0-value').val(overall_pend_end_yr);
        });
      });

      // Fields from IX and X to calculate field_perc_costs per agency.
      //FOIA Personnel and Costs IX. proc_costs / Fees X. total_fees  = Fees X. perc_costs
      // If section IX proc_costs field changes.
      $( "input[name*='field_foia_pers_costs_ix']").filter("input[name*='field_proc_costs']").each(function() {
        $(this).change(function() {
          var proc_costs_agency_val = getAgencyComponent($(this));

          if(proc_costs_agency_val != '_none') {
            var total_fees = getFieldByAgency('x_total_fees', proc_costs_agency_val);
            var target = getFieldByAgency('x_perc_costs', proc_costs_agency_val);
            calcPercCosts($(this), total_fees, target);
          }
        });
      });

      // If section X total_fees field changes.
      $( "input[name*='field_fees_x']").filter("input[name*='field_total_fees']").each(function() {
        $(this).change(function() {
          var total_fees_agency_val = getAgencyComponent($(this));

          if(total_fees_agency_val != '_none') {
            var proc_costs = getFieldByAgency('ix_proc_costs', total_fees_agency_val);
            var target = getFieldByAgency('x_perc_costs', total_fees_agency_val);
            calcPercCosts(proc_costs, $(this), target);
          }
        });
      });

      // Calculates perc_costs from total_fees divided by proc_costs.
      function calcPercCosts(proc_costs, total_fees, perc_costs) {
        var perc_costs_val;
        if(total_fees.val() > 0) {
          //set value of target field
          perc_costs_val = total_fees.val() / proc_costs.val();
          perc_costs_val = Math.round(perc_costs_val * 10000) / 100; // Percent rounded to 2 decimal places
          $(perc_costs).val(perc_costs_val);
          return perc_costs;
        }
      }

      // Gets agency_component field for given field.
      function getAgencyComponent(changed) {
        return $(changed).parents('.paragraphs-subform').find("select[name*='field_agency_component']").val();
      }

      // Get input field based on changed field ID and agency_component value.
      function getFieldByAgency(field, agency) {
        var result;
        var element;
        var element_agency;
        switch (field) {
          case 'x_total_fees':
            element = $( "input[name*='field_fees_x']").filter("input[name*='field_total_fees']");
            $(element).each(function() {
              element_agency = getAgencyComponent($(this));
              if (agency == element_agency) {
                result = $(this);
              }
            });
            break;
          case 'ix_proc_costs':
            element = $( "input[name*='field_foia_pers_costs_ix']").filter("input[name*='field_proc_costs']");
            $(element).each(function() {
              element_agency = getAgencyComponent($(this));
              if (agency == element_agency) {
                result = $(this);
              }
            });
            break;
          case 'x_perc_costs':
            element = $( "input[name*='field_fees_x']").filter("input[name*='field_perc_costs']");
            $(element).each(function() {
              element_agency = getAgencyComponent($(this));
              if (agency == element_agency) {
                result = $(this);
              }
            });
            break;
          default:
            result = false;
        }
        return $(result);
      }
    }
  }
})(jQuery, drupalSettings, Drupal);
