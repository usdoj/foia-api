/**
 * @file
 * Automatically calculate total fields with non-trivial summation.
 *
 * See foia_autocalc module for simple sum auto-calculations.
 */

(function ($, drupalSettings, Drupal) {

  'use strict';

  Drupal.behaviors.advcalcField = {

    attach: function attach(context, settings) {

      /**
       * Alias Drupal.FoiaUI utility functions
       */
      let specialNumber = Drupal.FoiaUI.specialNumber;
      let getAgencyComponent = Drupal.FoiaUI.getAgencyComponent;
      let splitForm = $(".node-annual-foia-report-data-annual-report-x-fees-collected-for-processing-requests-form").length;
      let fieldFeesElement = $("input[name*='field_fees_x']");

      const util = {
        /**
         * Converts number back to "<1" if between 0 and 1.
         *
         * @param {number} number
         *
         * @returns {string}
         */
        displayLessThan: function (number) {
          if (number > 0 && number < 1) {
            return "<1";
          }
          else {
            return number;
          }
        },

        /**
         * Check if field has been marked as having been calculated on page load.
         *
         * @param {string} selector
         * @returns {boolean}
         */
        fieldIsInitialized: function (selector) {
          var field = $(selector);

          if (field && field.length > 0) {
            return $(field.get(0)).hasClass('foia-advcalc-is-initialized');
          }

          return false;
        },

        /**
         * Add class to field to mark that it has been calculated on page load.
         *
         * @param selector
         */
        markFieldInitialized: function (selector) {
          $(selector).addClass('foia-advcalc-is-initialized');
        },
      };

      const calculate = {

        /**
         * Calculate V.A., VI.A., XII.B. Overall Number of [type] Pending as of
         * End of Fiscal Year.
         *
         * pend_end_yr = pend_start_yr + received_yr - processed_yr
         *
         * @param {string} start
         *  Partial name attribute for selecting start year field.
         * @param {string} received
         *  Partial name attribute for selecting received year field.
         * @param {string} processed
         *  Partial name attribute for selecting processed year field.
         * @param {string} end
         *  Partial name attribute for selecting calculated end year field.
         */
        calculateOverallPendEndYr: function (start, received, processed, end) {
          let startVal = Number($(start).val());
          if(isNaN(startVal)) {
            startVal = Number($("input[data-drupal-selector='" + start.substring(1) + "']").val());
          }
          let receivedVal = Number($(received).val());
          if(isNaN(receivedVal)) {
            receivedVal = Number($("input[data-drupal-selector='" + received.substring(1) + "']").val());
          }
          let processedVal = Number($(processed).val());
          if(isNaN(processedVal)) {
            processedVal = Number($("input[data-drupal-selector='" + processed.substring(1) + "']").val());
          }
          let endVal = startVal + receivedVal - processedVal;

          if($(end).length > 0) {
            $(end).val(endVal);
          }
          else {
            $("input[data-drupal-selector='" + end.substring(1) + "']").val(endVal);
          }

        },
        /**
         * Calculate overall value for a given component.
         *
         * @param {string} componentId
         *    The node edit paragraph component HTML fragment ID.
         * @param {string} componentFieldName
         *    The paragraph component field machine name.
         * @param {string} overallFieldID
         *    The calculated overall field HTML fragment ID.
         * @param {string} operator
         *    The operation to be performed, "<" or ">".
         */
        calculateOverall: function (componentId, componentFieldName, overallFieldID, operator) {
          // Calculate the initial value of the field.
          var fields = $("input[id^='" + componentId + "']").filter("input[name*='" + componentFieldName + "']");
          let sel, over_flag;
          if($('#' + overallFieldID).length) {
            sel = '#' + overallFieldID;
            over_flag = 1;
          }
          else {
            sel = "input[data-drupal-selector='" + overallFieldID + "']";
            over_flag = 2;
          }
          if (!util.fieldIsInitialized(sel)) {
            var value = calculate.calculateBoundaryOfSet(fields, operator);
            $(sel).val(value);
            util.markFieldInitialized(sel);
          }

          fields.each(function () {
            $(this).once('advCalcOverall').on('change', {overallFieldID: overallFieldID, operator: operator, over_flag: over_flag, sel: sel}, function (event) {
              var output = calculate.calculateBoundaryOfSet(fields, event.data.operator);
              if(over_flag === 1) {
                $('#' + event.data.overallFieldID).val(output);
              }
              else {
                $(event.data.sel).val(output);
              }
            });
          });
        },
        /**
         * Compares the values of a set of fields, returning either the upper or
         * lower bound of the set.
         *
         * @param fields
         *   A jquery object containing a set of fields.
         * @param operator
         *   Either less than or greater than depending on which boundary of the
         *   set should be retrieved.
         * @returns {*}
         */
        calculateBoundaryOfSet: function (fields, operator) {
          var ops = {
            '<': function (a, b) { return a < b; },
            '>': function (a, b) { return a > b; },
          };
          var output = null;
          var isOverallNA = true;
          fields.each(function () {
            var value = $(this).val();
            if (String(value).toLowerCase() !== 'n/a') {
              isOverallNA = false;
              if (value !== '' && value !== null && (output === null || output === "n/a")) {
                // Set output for the first valid value.
                output = util.displayLessThan(specialNumber(value));
              }
              else if (value !== '' && value !== undefined && ops[operator](specialNumber(value), specialNumber(output))) {
                // Override output if operation criterion is met.
                output = util.displayLessThan(specialNumber(value));
              }
            }
          });
          // Clear overall value if output is "NaN".
          if (output !== output) {
            output = '';
          }
          // Set overall value to "N/A" if all fields are "N/A".
          else if (isOverallNA) {
            output = 'N/A';
          }

          return output;
        },
        /**
         * Calculate V.A., VI.A., XII.B. Number of [type] Pending as of End of
         * Fiscal Year per agency/component.
         *
         * pend_end_yr = pend_start_yr + received_yr - processed_yr
         *
         * @param {jquery} element
         *  jQuery object of field to trigger calculations for component.
         * @param {string} start
         *  Partial name attribute for selecting start year field.
         * @param {string} received
         *  Partial name attribute for selecting received year field.
         * @param {string} processed
         *  Partial name attribute for selecting processed year field.
         * @param {string} end
         *  Partial name attribute for selecting calculated end year field.
         */
        calculatePendEndYr: function (element, start, received, processed, end) {
          var component = $(element).parents('.paragraphs-subform');
          var startVal = Number(component.find("input[name*='" + start + "']").val());
          var receivedVal = Number(component.find("input[name*='" + received + "']").val());
          var processedVal = Number(component.find("input[name*='" + processed + "']").val());
          var endVal = startVal + receivedVal - processedVal;
          component.find("input[name*='" + end + "']").val(endVal);
        },
        /**
         * Calculate X. Percentage of Total Costs
         * @param {string} agency
         *     String representing an agency/component option value.
         * @param load
         *     Different behavior depending on load or change
         */
        calculatePercentTotalCosts: function (agency, load) {

          let procCostsElements = $("input[name*='field_foia_pers_costs_ix']").filter("input[name*='field_proc_costs']");
          let totalFeesElements = fieldFeesElement.filter("input[name*='field_total_fees']");

          fieldFeesElement.filter("input[name*='field_perc_costs']")
            .each(function (i) {
              let elementAgency = getAgencyComponent($(this));
              if (agency === elementAgency) {

                let procCosts;
                let totalFees;
                let percentCosts;
                if(splitForm) {
                  procCosts = $(`input[name*='x_temp[${i}]']`).data('proc');
                  // On load, no need to calculate or get X "Total Amount of Fees Collected"
                  if(load === 'load') {
                    percentCosts = $(`input[name*='x_temp[${i}]']`).data('percent');
                  }
                  else {
                    // On change, get X "Total Amount of Fees Collected" and recalculate
                    totalFees = $(`input[name*='field_fees_x[${i}][subform][field_total_fees][0][value]']`).val();
                    if (totalFees > 0) {
                      percentCosts = Math.round(totalFees / procCosts * 10000) / 100;
                    }
                  }
                }
                else {
                  // Calculate normally if not in split form
                  procCosts = advcalcX.getElementValByAgency(procCostsElements, agency);
                  totalFees = advcalcX.getElementValByAgency(totalFeesElements, agency);
                  if (totalFees > 0) {
                    percentCosts = Math.round(totalFees / procCosts * 10000) / 100;
                  }
                }
                $(this).val(percentCosts);
              }
            });
        },
      };

      const advcalcV = {

        calcOnLoad: function() {

          /**
           * V.A. Number of Requests Pending as of End of Fiscal Year
           * per agency/component
           */

          // Initialize V.A. Number of Requests Pending as of End of Fiscal Year.
          // FOIA REQUESTS V. A.  field Number of Requests Pending as of End of Fiscal Year
          $("input[name*='field_foia_requests_va']")
            .once('initAdvCalcVIAppPendEndYr')
            .filter("input[name*='field_req_pend_end_yr']")
            .each(function () {
              if (!util.fieldIsInitialized(this)) {
                calculate.calculatePendEndYr(this, 'field_req_pend_start_yr', 'field_req_received_yr', 'field_req_processed_yr', 'field_req_pend_end_yr');
                util.markFieldInitialized(this);
              }
            });

          /**
           * V.A. Agency Overall Number of Requests Pending as of End of Fiscal Year
           */

          // Initialize on load:
          // V.A. Agency Overall Number of Requests Pending as of End of Fiscal Year.
          calculate.calculateOverallPendEndYr(
            '#edit-field-overall-req-pend-start-yr-0-value',
            '#edit-field-overall-req-received-yr-0-value',
            '#edit-field-overall-req-processed-yr-0-value',
            '#edit-field-overall-req-pend-end-yr-0-value'
          );
          util.markFieldInitialized('#edit-field-overall-req-pend-end-yr-0-value');

          /**
           * VI.A. Number of Requests Pending as of End of Fiscal Year
           * per agency/component
           */

          // Initialize on load:
          // VI.A. Number of Appeals Pending as of End of Fiscal Year.
          $("input[name*='field_admin_app_via']")
            .once('initAdvCalcVIAppPendEndYr')
            .filter("input[name*='field_app_pend_end_yr']")
            .each(function () {
              if (!util.fieldIsInitialized(this)) {
                calculate.calculatePendEndYr(this, 'field_app_pend_start_yr', 'field_app_received_yr', 'field_app_processed_yr', 'field_app_pend_end_yr');
                util.markFieldInitialized(this);
              }
            });

          /**
           * VI.A. Agency Overall Number of Requests Pending as of End of Fiscal Yr
           */
          // Initialize on load:
          // VI.A. Agency Overall Number of Requests Pending as of End of Fiscal Yr.
          if (!util.fieldIsInitialized('#edit-field-overall-via-app-pend-endyr-0-value')) {
            calculate.calculateOverallPendEndYr(
              '#edit-field-overall-via-app-pend-start-0-value',
              '#edit-field-overall-via-app-recd-yr-0-value',
              '#edit-field-overall-via-app-proc-yr-0-value',
              '#edit-field-overall-via-app-pend-endyr-0-value'
            );
            util.markFieldInitialized('#edit-field-overall-via-app-pend-endyr-0-value');
          }

          // VI.C.(4) Lowest Number of Days.
          calculate.calculateOverall('edit-field-admin-app-vic4', 'field_low_num_days', 'edit-field-overall-vic4-low-num-days-0-value', '<');

          // VI.C.(4) Highest Number of Days.
          calculate.calculateOverall('edit-field-admin-app-vic4', 'field_high_num_days', 'edit-field-overall-vic4-high-num-days-0-value', '>');

          // VII.A. Lowest Number of Days.
          calculate.calculateOverall('edit-field-proc-req-viia', 'field_sim_low', 'edit-field-overall-viia-sim-low-0-value', '<');
          // VII.A. Highest Number of Days.
          calculate.calculateOverall('edit-field-proc-req-viia', 'field_sim_high', 'edit-field-overall-viia-sim-high-0-value', '>');

          // VII.A. Lowest Number of Days (complex).
          calculate.calculateOverall('edit-field-proc-req-viia', 'field_comp_low', 'edit-field-overall-viia-comp-low-0-value', '<');

          // VII.A. Highest Number of Days (complex).
          calculate.calculateOverall('edit-field-proc-req-viia', 'field_comp_high', 'edit-field-overall-viia-comp-high-0-value', '>');

          // VII.A. Lowest Number of Days (expedited).
          calculate.calculateOverall('edit-field-proc-req-viia', 'field_exp_low', 'edit-field-overall-viia-exp-low-0-value', '<');
          // VII.A. Highest Number of Days (expedited).
          calculate.calculateOverall('edit-field-proc-req-viia', 'field_exp_high', 'edit-field-overall-viia-exp-high-0-value', '>');

          // VII.B. Lowest Number of Days.
          calculate.calculateOverall('edit-field-proc-req-viib', 'field_sim_low', 'edit-field-overall-viib-sim-low-0-value', '<');

          // VII.B. Highest Number of Days.
          calculate.calculateOverall('edit-field-proc-req-viib', 'field_sim_high', 'edit-field-overall-viib-sim-high-0-value', '>');

          // VII.B. Lowest Number of Days (complex).
          calculate.calculateOverall('edit-field-proc-req-viib', 'field_comp_low', 'edit-field-overall-viib-comp-low-0-value', '<');

          // VII.B. Highest Number of Days (complex).
          calculate.calculateOverall('edit-field-proc-req-viib', 'field_comp_high', 'edit-field-overall-viib-comp-high-0-value', '>');

          // VII.B. Lowest Number of Days (expedited).
          calculate.calculateOverall('edit-field-proc-req-viib', 'field_exp_low', 'edit-field-overall-viib-exp-low-0-value', '<');

          // VII.B. Highest Number of Days (expedited).
          calculate.calculateOverall('edit-field-proc-req-viib', 'field_exp_high', 'edit-field-overall-viib-exp-high-0-value', '>');

        },
        calcOnChange: function() {

          // V.A. Number of Requests Pending as of End of Fiscal Year.
          $("input[name*='field_foia_requests_va']")
            .filter("input[name*='field_req_pend_start_yr'], input[name*='field_req_received_yr'], input[name*='field_req_processed_yr']")
            .each(function () {
              $(this).once('advCalcVAReqPendEndYr').change(function () {
                calculate.calculatePendEndYr(this, 'field_req_pend_start_yr', 'field_req_received_yr', 'field_req_processed_yr', 'field_req_pend_end_yr');
              });
            });

          // Calculate on change:
          // V.A. Agency Overall Number of Requests Pending as of End of Fiscal Year.
          let via_calcs;
          if($('#edit-field-overall-req-pend-start-yr-0-value').length > 0) {
            via_calcs = $("#edit-field-overall-req-pend-start-yr-0-value, #edit-field-overall-req-received-yr-0-value, #edit-field-overall-req-processed-yr-0-value")
          } else {
            via_calcs = $("input[data-drupal-selector='edit-field-overall-req-pend-start-yr-0-value'], input[data-drupal-selector='edit-field-overall-req-received-yr-0-value'], input[data-drupal-selector='edit-field-overall-req-processed-yr-0-value']");
          }
          via_calcs
            .once('advCalcVAOverallReqPendEndYr')
            .change(function () {
              calculate.calculateOverallPendEndYr(
                '#edit-field-overall-req-pend-start-yr-0-value',
                '#edit-field-overall-req-received-yr-0-value',
                '#edit-field-overall-req-processed-yr-0-value',
                '#edit-field-overall-req-pend-end-yr-0-value'
              );
            });

          via_calcs
            .once('advCalcVAOverallReqPendEndYr')
            .change(function () {
              calculate.calculateOverallPendEndYr(
                '#edit-field-overall-req-pend-start-yr-0-value',
                '#edit-field-overall-req-received-yr-0-value',
                '#edit-field-overall-req-processed-yr-0-value',
                '#edit-field-overall-req-pend-end-yr-0-value'
              );
            });

          // Calculate on change:
          // VI.A. Number of Appeals Pending as of End of Fiscal Year.
          $("input[name*='field_admin_app_via']")
            .filter("input[name*='field_app_pend_start_yr'], input[name*='field_app_received_yr'], input[name*='field_app_processed_yr']")
            .each(function () {
              $(this).once('advCalcVIAppPendEndYr')
                .change(function () {
                  calculate.calculatePendEndYr(this, 'field_app_pend_start_yr', 'field_app_received_yr', 'field_app_processed_yr', 'field_app_pend_end_yr');
                });
            });

          // Calculate on change:
          // VI.A. Agency Overall Number of Requests Pending as of End of Fiscal Yr.
          via_calcs
            .once('advCalcVIOverallAppPendEndYr')
            .change(function () {
              calculate.calculateOverallPendEndYr(
                '#edit-field-overall-via-app-pend-start-0-value',
                '#edit-field-overall-via-app-recd-yr-0-value',
                '#edit-field-overall-via-app-proc-yr-0-value',
                '#edit-field-overall-via-app-pend-endyr-0-value'
              );
            });

        }
      };

      const advcalcX = {
        /**
         * X. Percentage of Total Costs.
         * Initialize on load only on full form:
         */
        calcPercentageLoad: function () {

          fieldFeesElement
            .once('initAdvCalcIXPercCosts')
            .filter("input[name*='field_perc_costs']")
            .each(function () {

              if (!util.fieldIsInitialized(this)) {
                var percCostsAgency = getAgencyComponent($(this));
                if (percCostsAgency !== '_none') {
                  calculate.calculatePercentTotalCosts(percCostsAgency, 'load');
                }
                util.markFieldInitialized(this);
              }
            });
        },
        /**
         * X. Percentage of Total Costs per agency/component.
         *   Loops through Fees from X and adds change event
         * Fields from IX and X to calculate field_perc_costs per agency.
         * FOIA Personnel and Costs IX. proc_costs / Fees X. total_fees  = Fees X. perc_costs
         */
        calcPercentageChange: function () {

          // Calculate on change: X. Percentage of Total Costs per agency/component.
          let processingCostsElements = $("input[name*='field_foia_pers_costs_ix']").filter("input[name*='field_proc_costs']");
          fieldFeesElement
            .filter("input[name*='field_total_fees']")
            .add(processingCostsElements)
            .each(function () {
              // If X. Total Fees or IX. Processing Costs change, calculate % costs.
              $(this).once('advCalcXPercCosts').change(function () {
                var processingCostsAgency = getAgencyComponent($(this));
                if (processingCostsAgency !== '_none') {
                  calculate.calculatePercentTotalCosts(processingCostsAgency, 'change');
                }
                else {
                  alert("Please pick an agency first");
                }
              });
            });
        },
        /**
         * On Load
         * X. Agency Overall Percentage of Total Costs.
         */
        calcOverallLoad: function () {
          // Initialize on load:
          if (!util.fieldIsInitialized("input[data-drupal-selector='edit-field-overall-x-perc-costs-0-value']")) {
            advcalcX.calculateOverallPercentCosts();
            util.markFieldInitialized("input[data-drupal-selector='edit-field-overall-x-perc-costs-0-value']");
          }
        },

        // Calculate on change:
        // X. Agency Overall Percentage of Total Costs.
        calcOverallChange: function () {
          $("input[data-drupal-selector='edit-field-overall-ix-proc-costs-0-value'], input[data-drupal-selector='edit-field-overall-x-total-fees-0-value']")
            .once('advCalcOverallXPercCosts')
            .change(function () {
              advcalcX.calculateOverallPercentCosts();
            });
        },
        /**
         * Get input field based on changed field ID and Agency/Component value.
         *
         * @param {jquery} elements
         *   jQuery element collection for field selectors.
         * @param {string} agency
         *   String representation of Agency/Component value, e.g. "8706".
         *
         * @returns {number}
         *   Numeric value of element field.
         */
        getElementValByAgency: function (elements, agency) {
          var result = null;
          $(elements).each(function () {
            var elementAgency = getAgencyComponent($(this));
            if (agency === elementAgency) {
              result = Number($(this).val());
            }
          });
          return result;
        },
        /**
         * Calculate X. Agency Overall Percentage of Total Costs
         */
        calculateOverallPercentCosts: function () {
          let overallProcCosts = Number($("#edit-field-overall-ix-proc-costs-0-value").val());
          let overallTotalFees = Number($("input[data-drupal-selector='edit-field-overall-x-total-fees-0-value']").val());
          if(overallTotalFees) {
            let overallPercentCosts = 0;
            let overallDivide;
            if (overallTotalFees > 0) {
              overallDivide = overallTotalFees / overallProcCosts;
              overallPercentCosts = Math.round(overallDivide * 10000) / 100;
            }
            $("input[data-drupal-selector='edit-field-overall-x-perc-costs-0-value']").val(overallPercentCosts);
          }
        }
      };

      // VA.
      advcalcV.calcOnLoad();
      advcalcV.calcOnChange();

      // X. calculations
      advcalcX.calcOverallLoad();
      advcalcX.calcOverallChange();
      advcalcX.calcPercentageLoad();
      advcalcX.calcPercentageChange();

      // XI. calculations

      /**
       * XII.B. Number of Consultations Received from Other Agencies that were
       * Pending at the Agency as of End of the Fiscal Year
       */

      let field_foia_xiib = $("input[name*='field_foia_xiib']");

      // Initialize on load:
      // XII.B. Number of Consultations Received from Other Agencies that were
      // Pending at the Agency as of End of the Fiscal Year.
      field_foia_xiib
        .once('initAdvCalcXIIBConPendEndYr')
        .filter("input[name*='field_pend_end_yr']")
        .each(function () {
          if (!util.fieldIsInitialized(this)) {
            calculate.calculatePendEndYr(this, 'field_pend_start_yr', 'field_con_during_yr', 'field_proc_start_yr', 'field_pend_end_yr');
            util.markFieldInitialized(this);
          }
        });

      // Calculate on change:
      // XII.B. Number of Consultations Received from Other Agencies that were
      // Pending at the Agency as of End of the Fiscal Year.
      field_foia_xiib
        .filter("input[name*='field_pend_start_yr'], input[name*='field_con_during_yr'], input[name*='field_proc_start_yr']")
        .each(function () {
          $(this).once('advCalcXIIBConPendEndYr')
            .change(function () {
              calculate.calculatePendEndYr(this, 'field_pend_start_yr', 'field_con_during_yr', 'field_proc_start_yr', 'field_pend_end_yr');
            });
        });

      /**
       * XII.B. Agency Overall Number of Consultations Received from Other
       * Agencies that were Pending at the Agency as of End of the Fiscal Year
       */

      // Initialize on load: XII.B. Agency Overall Number of Consultations
      // Received from Other Agencies that were Pending at the Agency as of End
      // of the Fiscal Year.
      let xiib_load_field;
      if($("#edit-field-overall-xiib-pend-end-yr-0-value").length) {
        xiib_load_field = "#edit-field-overall-xiib-pend-end-yr-0-value";
      }
      else {
        xiib_load_field = "input[data-drupal-selector='edit-field-overall-xiib-pend-end-yr-0-value']";
      }
      if (!util.fieldIsInitialized(xiib_load_field)) {
        calculate.calculateOverallPendEndYr(
          '#edit-field-overall-xiib-pend-start-yr-0-value',
          '#edit-field-overall-xiib-con-during-yr-0-value',
          '#edit-field-overall-xiib-proc-start-yr-0-value',
          '#edit-field-overall-xiib-pend-end-yr-0-value'
        );
        util.markFieldInitialized(xiib_load_field);
      }

      // Calculate on change: XII.B. Agency Overall Number of Consultations
      // Received from Other Agencies that were Pending at the Agency as of End
      // of the Fiscal Year.
      let viib_calcs;
      if($('#edit-field-overall-xiib-pend-start-yr-0-value').length > 0) {
        viib_calcs = $("#edit-field-overall-xiib-pend-start-yr-0-value, #edit-field-overall-xiib-con-during-yr-0-value, #edit-field-overall-xiib-proc-start-yr-0-value");
      } else {
        viib_calcs = $("input[data-drupal-selector='edit-field-overall-xiib-pend-start-yr-0-value'], input[data-drupal-selector='edit-field-overall-xiib-con-during-yr-0-value'], input[data-drupal-selector='edit-field-overall-xiib-proc-start-yr-0-value']");
      }
      viib_calcs
        .once('advCalcXIIBOverallAppPendEndYr')
        .change(function () {
          calculate.calculateOverallPendEndYr(
            '#edit-field-overall-xiib-pend-start-yr-0-value',
            '#edit-field-overall-xiib-con-during-yr-0-value',
            '#edit-field-overall-xiib-proc-start-yr-0-value',
            '#edit-field-overall-xiib-pend-end-yr-0-value'
          );
        });
    }
  };
})(jQuery, drupalSettings, Drupal, once);
