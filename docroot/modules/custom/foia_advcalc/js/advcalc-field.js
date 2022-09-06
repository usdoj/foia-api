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
      // Needed to keep everything from firing twice
      once('advcalcField', 'html', context).forEach( function (element) {

        /**
         * Alias Drupal.FoiaUI utility functions
         */
        var specialNumber = Drupal.FoiaUI.specialNumber;
        var getAgencyComponent = Drupal.FoiaUI.getAgencyComponent;



        // node-annual-foia-report-data-annual-report-x-fees-collected-for-processing-requests-form
        //let splitForm  = $("#node-annual-foia-report-data-annual-report-x-fees-collected-for-processing-requests-form").length;
        // now a class???
        let splitForm  = $(".node-annual-foia-report-data-annual-report-x-fees-collected-for-processing-requests-form").length;
        let fieldFeesElement  = $("input[name*='field_fees_x']");

        const util = {
          _ajax_helper: function (url) {
            const splitPath = window.location.pathname.split("/")
            console.log("splitPath", window.location.protocol + "//" + window.location.host + url + splitPath[2]);
            $.ajax({
              type: "GET",
              dataType:"json",
              url: window.location.protocol + "//" + window.location.host + url + splitPath[2] + "/1,2,3",
              success : function(response)
              {
                console.log("response", response);
                return response;
              },
              failed : function(e)
              {
                console.log("e", e);
                return e;
              }
            });
          },
          /**
           * Converts number back to "<1" if between 0 and 1.
           *
           * @param {number} number
           *
           * @returns {string}
           */
          displayLessThan: function(number) {
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
          calculateOverallPendEndYr: function(start, received, processed, end) {
            var startVal = Number($(start).val());
            var receivedVal = Number($(received).val());
            var processedVal = Number($(processed).val());
            var endVal = startVal + receivedVal - processedVal;
            // console.log("calculateOverallPendEndYr start: ", start, startVal)
            // //console.log("calculateOverallPendEndYr startVal: ", startVal)
            // console.log("calculateOverallPendEndYr receivedVal: ", received, receivedVal)
            //
            // console.log("calculateOverallPendEndYr processedVal: ", processed, processedVal)
            // console.log("calculateOverallPendEndYr endVal: ", end, endVal)


            // TODO: if calculation is wrong, then nan instead of 0 in field value is input here
            $(end).val(endVal);
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
            //console.log("fields", fields.attr('value'));
            //console.log("componentId", componentId);
            //console.log("componentFieldName", componentFieldName);
            if (!util.fieldIsInitialized('#' + overallFieldID)) {
              var value = calculate.calculateBoundaryOfSet(fields, operator);
              //console.log("value", value);
              $('#' + overallFieldID).val(value);
              util.markFieldInitialized('#' + overallFieldID);
            }

            fields.each(function () {
              $(this).once('advCalcOverall').on('change', {overallFieldID: overallFieldID, operator: operator}, function (event) {
                var output = calculate.calculateBoundaryOfSet(fields, event.data.operator);
                $('#' + event.data.overallFieldID).val(output);
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
          calculatePendEndYr: function(element, start, received, processed, end) {
            var component = $(element).parents('.paragraphs-subform');
            var startVal = Number(component.find("input[name*='" + start + "']").val());
            var receivedVal = Number(component.find("input[name*='" + received + "']").val());
            var processedVal = Number(component.find("input[name*='" + processed + "']").val());
            var endVal = startVal + receivedVal - processedVal;

            // console.log("calculatePendEndYr element", element);
            // console.log("calculatePendEndYr component", component);
            // console.log("calculatePendEndYr startVal", startVal);
            // console.log("calculatePendEndYr receivedVal", receivedVal);
            // console.log("calculatePendEndYr processedVal", processedVal);
            // console.log("calculatePendEndYr endVal", endVal);
            component.find("input[name*='" + end + "']").val(endVal);
          },
          /**
           * Calculate X. Percentage of Total Costs
           * On Change
           * @param {string} agency
           *   String representing an agency/component option value.
           */
          calculatePercentTotalCosts: function(agency) {

            let procCostsElements = $("input[name*='field_foia_pers_costs_ix']").filter("input[name*='field_proc_costs']");
            let totalFeesElements = fieldFeesElement.filter("input[name*='field_total_fees']");
            // if(splitForm) {
            //
            //   // should now have same name as non split
            //   procCostsElements = $("input[name*='field_foia_pers_costs_ix']").filter("input[name*='field_proc_costs']");
            //   totalFeesElements = fieldFeesElement.filter("input[name*='field_total_fees']");
            //
            //
            //   // How to only calculate if there is no value for field_proc_costs
            //   // TODO: what happens when this function runs on update
            // } else {
            //   procCostsElements = $("input[name*='field_foia_pers_costs_ix']").filter("input[name*='field_proc_costs']");
            //   totalFeesElements = fieldFeesElement.filter("input[name*='field_total_fees']");
            // }

            fieldFeesElement.filter("input[name*='field_perc_costs']")
              .each(function () {
                let elementAgency = getAgencyComponent($(this));

                if (agency === elementAgency) {

                  let procCosts;
                  let totalFees;
                  // TODO: PROBLEM IS THAT IN SPLIT FORM, AGENCY IS NOT IN procCostsElements
                  if(splitForm) {
                    procCosts = advcalcX.getElementValByAgencySplitForm(procCostsElements, agency, 'proc');
                    console.log("splitForm: procCosts in fieldFeesElement",procCosts);
                    totalFees = advcalcX.getElementValByAgencySplitForm(totalFeesElements, agency, 'fees');
                    console.log("totalFees",totalFees);
                  } else {
                    procCosts = advcalcX.getElementValByAgency(procCostsElements, agency);
                    totalFees = advcalcX.getElementValByAgency(totalFeesElements, agency);
                    console.log("NO SPLIT: procCosts in fieldFeesElement",procCosts);
                  }

                  let percentCosts = 0;
                  if (totalFees > 0) {
                    // Convert to decimal format rounded to 4 places.
                    percentCosts = Math.round(totalFees / procCosts * 10000) / 100;
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

            // DONE: From foia_ui.validation.js
            // custom/foia_ui/js/foia_ui.validation.js:618
            // V.A. FOIA Requests.
            // loop through Number of Requests Processed in Fiscal Year


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

            // Initialize on load: DONE
            // V.A. Agency Overall Number of Requests Pending as of End of Fiscal Year.

            // due to issue with ID's having --2 appended, may need to use name
            //
            // input[name^='field_overall_req_pend_end_yr[0][value]']


            // calculate.calculateOverallPendEndYr(
            //   "input[name^='field_overall_req_pend_start_yr[0][value]']",
            //   "input[name^='field_overall_req_received_yr[0][value]']",
            //   "input[name^='field_overall_req_processed_yr[0][value]']",
            //   "input[name^='field_overall_req_pend_end_yr[0][value]']"
            // );
            // util.markFieldInitialized("input[name^='field_overall_req_pend_end_yr[0][value]']");


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

            // Initialize on load: TODO:
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

            // console.log("V.A. Number of Requests Pending as of End of Fiscal Year.");

            // V.A. Number of Requests Pending as of End of Fiscal Year.
            $("input[name*='field_foia_requests_va']")
              .filter("input[name*='field_req_pend_start_yr'], input[name*='field_req_received_yr'], input[name*='field_req_processed_yr']")
              .each(function () {
                console.log();
                $(this).once('advCalcVAReqPendEndYr').change(function () {
                  calculate.calculatePendEndYr(this, 'field_req_pend_start_yr', 'field_req_received_yr', 'field_req_processed_yr', 'field_req_pend_end_yr');
                });
              });

            // console.log("V.A. Agency Overall Number of Requests Pending as of End of Fiscal Year.")

            // Calculate on change:
            // V.A. Agency Overall Number of Requests Pending as of End of Fiscal Year.
            $("#edit-field-overall-req-pend-start-yr-0-value, #edit-field-overall-req-received-yr-0-value, #edit-field-overall-req-processed-yr-0-value")
              .once('advCalcVAOverallReqPendEndYr')
              .change(function () {
                calculate.calculateOverallPendEndYr(
                  '#edit-field-overall-req-pend-start-yr-0-value',
                  '#edit-field-overall-req-received-yr-0-value',
                  '#edit-field-overall-req-processed-yr-0-value',
                  '#edit-field-overall-req-pend-end-yr-0-value'
                );
              });


            // console.log("VI.A. Number of Appeals Pending as of End of Fiscal Year.: ")
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
            $("#edit-field-overall-req-pend-start-yr-0-value, #edit-field-overall-req-received-yr-0-value, #edit-field-overall-req-processed-yr-0-value")
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
           */
          calcPercentageLoad: function () {

            // Initialize on load only on full form:
            if(!splitForm) {

              fieldFeesElement
                .once('initAdvCalcIXPercCosts')
                .filter("input[name*='field_perc_costs']")
                .each(function () {
                  if (!util.fieldIsInitialized(this)) {
                    var percCostsAgency = getAgencyComponent($(this));
                    // TODO: if split form, do not init on load
                    if (percCostsAgency !== '_none') {
                      calculate.calculatePercentTotalCosts(percCostsAgency);
                    }
                    util.markFieldInitialized(this);
                  }
                });
            }
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
                    // calculate.calculatePercentTotalCosts(processingCostsAgency);
                    calculate.calculatePercentTotalCosts(processingCostsAgency);
                    // calculate.calculatePercentTotalCostsOG(processingCostsAgency);
                  }
                });
              });
          },
          calcOverallLoad: function () {

            // Initialize on load:
            // X. Agency Overall Percentage of Total Costs.
            // TODO: no reason to check for split form
            // if (!splitForm) {
            //   if (!fieldIsInitialized('#edit-field-overall-x-perc-costs-0-value')) {
            //     advcalcX.calculateOverallPercentCosts();
            //     markFieldInitialized('#edit-field-overall-x-perc-costs-0-value');
            //   }
            // }
            if (!util.fieldIsInitialized('#edit-field-overall-x-perc-costs-0-value')) {
              advcalcX.calculateOverallPercentCosts();
              util.markFieldInitialized('#edit-field-overall-x-perc-costs-0-value');
            }
          },

          // Calculate on change:
          // X. Agency Overall Percentage of Total Costs.
          calcOverallChange: function () {
            $("#edit-field-overall-ix-proc-costs-0-value, #edit-field-overall-x-total-fees-0-value")
              .once('advCalcOverallXPercCosts')
              .change(function () {
                // console.log("calculateOverallPercentCosts");
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
              // console.log("elementAgency in getElementValByAgency", elementAgency);
              // console.log("agency in getElementValByAgency", agency);


              if (agency === elementAgency) {
                result = Number($(this).val());
              } else {
                console.log("********** PROBLEM: elements", elements);
                console.log("********** PROBLEM: agency does not equal elementAgency elementAgency", elementAgency);
                console.log("********** PROBLEM: agency ", agency);
              }
            });
            return result;
          },
          getElementValByAgencySplitForm: function (elements, agency, field) {
            var result = null;
            // TODO: get agency from different depending on fields
            $(elements).each(function () {
              let elementAgency;
              if(field === 'fees') {
                elementAgency = getAgencyComponent($(this));
              } else {
                elementAgency = $(this).data("agency");
              }

              console.log("getElementValByAgencySplitForm elementAgency: ", elementAgency); // proc_cost_hidden

              // Get this from the hidden field
              // console.log("getElementValByAgencySplitForm elementAgency: ", elementAgency); // proc_cost_hidden
              // console.log("getElementValByAgencySplitForm agency: ", agency); // proc_cost_hidden
              if (parseInt(agency) === parseInt(elementAgency)) {
                result = Number($(this).val());
                console.log("getElementValByAgencySplitForm result ", result);
              } else {
                console.log("agency does not equal elementAgency");

                console.log("getElementValByAgencySplitForm agency: ", agency); // proc_cost_hidden
              }
            });
            console.log("ENDING: result ", result);
            return result;
          },
          /**
           * Calculate X. Agency Overall Percentage of Total Costs
           * TODO: for split form, need #edit-field-overall-ix-proc-costs-0-value
           */
          calculateOverallPercentCosts: function () {

            if (util.fieldIsInitialized("input[name*='field_foia_requests_va']")) {
              console.log("input[name*='field_foia_requests_va'] INIT");
            }
            let overallTotalFees = Number($("#edit-field-overall-x-total-fees-0-value").val());

            if(overallTotalFees) {
              console.log("overallTotalFees ", overallTotalFees);

              let overallPercentCosts = 0;
              if (overallTotalFees > 0) {
                let overallProcCosts = Number($("#edit-field-overall-ix-proc-costs-0-value").val());
                overallPercentCosts = overallTotalFees / overallProcCosts;
                // Convert to decimal format rounded to 4 places.
                overallPercentCosts = Math.round(overallPercentCosts * 10000) / 100;
              }
              $('#edit-field-overall-x-perc-costs-0-value').val(overallPercentCosts);
            }

          }
        };

        // VA.
        advcalcV.calcOnLoad();
        advcalcV.calcOnChange();

        // IX. calculations

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

        // Initialize on load:
        // XII.B. Number of Consultations Received from Other Agencies that were
        // Pending at the Agency as of End of the Fiscal Year.
        $("input[name*='field_foia_xiib']")
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
        $("input[name*='field_foia_xiib']")
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
        if (!util.fieldIsInitialized('#edit-field-overall-xiib-pend-end-yr-0-value')) {
          calculate.calculateOverallPendEndYr(
            '#edit-field-overall-xiib-pend-start-yr-0-value',
            '#edit-field-overall-xiib-con-during-yr-0-value',
            '#edit-field-overall-xiib-proc-start-yr-0-value',
            '#edit-field-overall-xiib-pend-end-yr-0-value'
          );
          util.markFieldInitialized('#edit-field-overall-xiib-pend-end-yr-0-value');
        }

        // Calculate on change: XII.B. Agency Overall Number of Consultations
        // Received from Other Agencies that were Pending at the Agency as of End
        // of the Fiscal Year.
        $("#edit-field-overall-xiib-pend-start-yr-0-value, #edit-field-overall-xiib-con-during-yr-0-value, #edit-field-overall-xiib-proc-start-yr-0-value")
          .once('advCalcXIIBOverallAppPendEndYr')
          .change(function () {
            calculate.calculateOverallPendEndYr(
              '#edit-field-overall-xiib-pend-start-yr-0-value',
              '#edit-field-overall-xiib-con-during-yr-0-value',
              '#edit-field-overall-xiib-proc-start-yr-0-value',
              '#edit-field-overall-xiib-pend-end-yr-0-value'
            );
          });
      })
    }
  };
})(jQuery, drupalSettings, Drupal, once);
