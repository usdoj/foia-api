/**
 * @file
 */

(function ($, drupalSettings) {
  Drupal.behaviors.foia_change_report_agency = {
    attach: function attach() {
      var clearAllReportData = function (containId, overWriteFields = null) {
        // Clear all sections data,
        // clear individual section field will be in section clear function.
        $('#' + containId + ' table tbody tr input').each(function () {
          if ($(this).is('[type=text]') && !$(this).attr('readonly')) {
            $(this).val('N/A');
            if (overWriteFields !== null) {
              for (var i = 0; i < overWriteFields.length; i++) {
                var FieldIdPattern = overWriteFields[i].field;
                var id = $(this).attr('id');
                if (id.match(FieldIdPattern)) {
                  $(this).val(overWriteFields[i].value);
                  break;
                }
              }
            }
          }
          if ($(this).is('[type=number]') && (typeof $(this).attr('readonly') === "undefined")) {
            $(this).val('0').trigger("change");
          }
        });
        $('#' + containId + ' table tbody tr textarea').each(function () {
          if (typeof $(this).attr('readonly') === "undefined") {
            $(this).val('N/A');
          }
        });
      };
      var sections = [
        {
          field: 'field_admin_app_vib',
          paragraph: 'admin_app_vib',   // VI.B
          section: {
            name: 'vib',
            containerWrapper: 'edit-field-admin-app-vib-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_admin_app_vic1',
          paragraph: 'admin_app_vic1',   // VI.C.1
          section: {
            name: 'vic1',
            containerWrapper: 'edit-field-admin-app-vic1-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_admin_app_vic2',
          paragraph: 'admin_app_vic2',    // VI.C.2
          section: {
            name: 'vic2',
            containerWrapper: 'edit-field-admin-app-vic2-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_admin_app_vic3',
          paragraph: 'admin_app_vic3',   // VI.C.3
          section: {
            name: 'vic3',
            containerWrapper: 'edit-field-admin-app-vic3-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_admin_app_vic4',
          paragraph: 'admin_app_vic4',   // VI.C.4
          section: {
            name: 'vic4',
            containerWrapper: 'edit-field-admin-app-vic4-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_admin_app_vic5',
          paragraph: 'oldest_days',     // VI.C.5
          section: {
            name: 'vic5',
            containerWrapper: 'edit-field-admin-app-vic5-wrapper',
            fnt: function () {
              var fields = [
                {
                  field: '-subform-field-date-',
                  value: 'N/A',
                },
                {
                  field: 'subform-field-num-days-',
                  value: '0',
                },
              ];
              clearAllReportData(this.containerWrapper, fields);
            },
          },
        },
        {
          field: 'field_admin_app_via',
          paragraph: 'admin_app_via',    // VI.A
          section: {
            name: 'via',
            containerWrapper: 'edit-field-admin-app-via-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_fees_x',
          paragraph: 'fees_x',    // X
          section: {
            name: 'x',
            containerWrapper: 'edit-field-fees-x-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_foia_pers_costs_ix',
          paragraph: 'foia_pers_costs_ix',   // IX
          section: {
            name: 'ix',
            containerWrapper: 'edit-field-foia-pers-costs-ix-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_foia_xiia',
          paragraph: 'foia_xiia',   // XII.A
          section: {
            name: 'xiia',
            containerWrapper: 'edit-field-foia-xiia-wrapper',
            fnt: function () {
              var fields = [
                {
                  field: '-subform-field-back-app-end-yr-',
                  value: '0',
                },
              ];
              clearAllReportData(this.containerWrapper, fields);
            },
          },
        },
        {
          field: 'field_foia_xiib',
          paragraph: 'foia_xiib',   // XII.B
          section: {
            name: 'xiib',
            containerWrapper: 'edit-field-foia-xiib-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_foia_xiic',
          paragraph: 'oldest_days',   // XII.C
          section: {
            name: 'xiic',
            containerWrapper: 'edit-field-foia-xiic-wrapper',
            fnt: function () {
              var fields = [
                {
                  field: '-subform-field-num-days-',
                  value: '0',
                },
                {
                  field: '-subform-field-date-',
                  value: 'N/A',
                },
              ];
              clearAllReportData(this.containerWrapper, fields);
            },
          },
        },
        {
          field: 'field_foia_xiid1',
          paragraph: 'foia_xii_received_proc',   // XII.D.1
          section: {
            name: 'xiid1',
            containerWrapper: 'edit-field-foia-xiid1-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_foia_xiid2',
          paragraph: 'foia_xii_backlogged',    // XII.D.2
          section: {
            name: 'xiid2',
            containerWrapper: 'edit-field-foia-xiid2-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_foia_xiie1',
          paragraph: 'foia_xii_received_proc',   // XII.E.1
          section: {
            name: 'xiie1',
            containerWrapper: 'edit-field-foia-xiie1-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_foia_xiie2',
          paragraph: 'foia_xii_backlogged',   // XII.E.2
          section: {
            name: 'xiie2',
            containerWrapper: 'edit-field-foia-xiie2-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_foia_requests_va',
          paragraph: 'foia_req_va',   // V.A
          section:
          {
            name: 'va',
            containerWrapper: 'edit-field-foia-requests-va-wrapper',
            fnt: function () {
             clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_foia_requests_vb2',
          paragraph: 'foia_req_vb2',   // V.B.2
          section: {
            name: 'vb2',
            containerWrapper: 'edit-field-foia-requests-vb2-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_foia_requests_vb3',
          paragraph: 'admin_app_vic1',   // V.B.3
          section: {
            name: 'vb3',
            containerWrapper: 'edit-field-foia-requests-vb3-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_foia_requests_vb1',
          paragraph: 'foia_req_vb1',   // V.B.1
          section: {
            name: 'vb1',
            containerWrapper: 'edit-field-foia-requests-vb1-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_pending_requests_vii_d_',
          paragraph: 'pending_requests_viid',   // VII.D
          section: {
            name: 'vii-d-',
            containerWrapper: 'edit-field-pending-requests-vii-d-wrapper',
            fnt: function () {
              var fields = [
                {
                  field: '-subform-field-sim-pend-',
                  value: '0',
                },
                {
                  field: '-subform-field-comp-med-',
                  value: 'N/A',
                },
                {
                  field: '-subform-field-comp-avg-',
                  value: 'N/A',
                },
                {
                  field: '-subform-field-sim-med-',
                  value: 'N/A',
                },
                {
                  field: '-subform-field-sim-avg-',
                  value: 'N/A',
                },
                {
                  field: '-subform-field-exp-med-',
                  value: 'N/A',
                },
                {
                  field: '-subform-field-exp-avg-',
                  value: 'N/A',
                },
              ];
              clearAllReportData(this.containerWrapper, fields);
            },
          },
        },
        {
          field: 'field_admin_app_viie',
          paragraph: 'oldest_days',   // VII.E
          section: {
            name: 'viie',
            containerWrapper: 'edit-field-admin-app-viie-wrapper',
            fnt: function () {
              var fields = [
                {
                  field: '-subform-field-date-',
                  value: 'N/A',
                },
                {
                  field: '-subform-field-num-days-',
                  value: '0',
                },
              ];
              clearAllReportData(this.containerWrapper, fields);
            },
          },
        },
        {
          field: 'field_proc_req_viia',
          paragraph: 'processed_requests_vii',   // VII.A
          section: {
            name: 'viia',
            containerWrapper: 'edit-field-proc-req-viia-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_proc_req_viib',
          paragraph: 'processed_requests_vii',   // VII.B
          section: {
            name: 'viib',
            containerWrapper: 'edit-field-proc-req-viib-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_proc_req_viic1',
          paragraph: 'proc_req_viic',   // VII.C.1
          section: {
            name: 'viic1',
            containerWrapper: 'edit-field-proc-req-viic1-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_proc_req_viic2',
          paragraph: 'proc_req_viic',   // VII.C.2
          section: {
            name: 'viic2',
            containerWrapper: 'edit-field-proc-req-viic2-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_proc_req_viic3',
          paragraph: 'proc_req_viic',   // VII.C.3
          section: {
            name: 'viic3',
            containerWrapper: 'edit-field-proc-req-viic3-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_req_viiib',
          paragraph: 'req_viiib',   // VIII.B
          section: {
            name: 'viiib',
            containerWrapper: 'edit-field-req-viiib-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_req_viiia',
          paragraph: 'req_viiia',   // VIII.A
          section: {
            name: 'viiia',
            containerWrapper: 'edit-field-req-viiia-wrapper',
            fnt: function () {
              var fields = [
                {
                  field: '-subform-field-num-jud-w',
                  value: '0',
                },
              ];
              clearAllReportData(this.containerWrapper, fields);
            },
          },
        },
        {
          field: 'field_statute_iv',
          paragraph: 'statute',   // IV
          section: {
            name: 'iv',
            containerWrapper: 'edit-field-statute-iv-wrapper',
            fnt: function(){
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_sub_xia',
          paragraph: 'sub_xia',   // XI.A
          section: {
            name: 'xia',
            containerWrapper: 'edit-field-sub-xia-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
        {
          field: 'field_sub_xib',
          paragraph: 'sub_xib',   // XI.B
          section: {
            name: 'xib',
            containerWrapper: 'edit-field-sub-xib-wrapper',
            fnt: function () {
              clearAllReportData(this.containerWrapper);
            },
          },
        },
      ];
      for (var i = 0; i < sections.length; i++) {
        this.addPopulateComponentsButton(sections[i]);
      }

      this.addClearDataButton();
    },

    addClearDataButton: function() {
      function clearData() {
        var form = '#node-annual-foia-report-data-edit-form';
        var exempt = '#edit-group-agency-info';
        var elements = [
          'textarea',
          'input[type="number"]',
          'input[type="text"]',
        ]
        for (var i = 0; i < elements.length; i++) {
          $(form + ' ' + elements[i]).not(exempt + ' ' + elements[i]).val('');
        }
        alert('All data has been cleared from the form. Click "Save" to finalize.');
      }
      $('#edit-actions').once('foia-clear-data-button').each(function() {
        var $button = $('<button class="button clear-data-button">Clear all data</button>');
        $(this).append($button);
        $button.click(function(evt) {
          evt.preventDefault();
          if (confirm('Are you sure you want to clear all data from this report?')) {
            clearData();
          }
        });
      });
    },

    addPopulateComponentsButton: function(section) {
      // Anomaly - if a field ends in an underscore needs to be removed for the wrapper but maintained for other uses.
      var wrapperVar = section.field.replace(/_$/, '');
      var fieldWrapperId = 'edit-' + wrapperVar.replace(/_/g, '-') + '-wrapper',
          addMoreName = section.field + '_' + section.paragraph + '_add_more',
          fieldWrapperSelector = '#' + fieldWrapperId,
          addMoreSelector = 'input[name="' + addMoreName + '"]',
          existingComponentSelector = fieldWrapperSelector + ' tbody tr:visible',
          checkedComponentSelector = '#edit-field-agency-components input:checked',
          getComponentDropdownName = function(index) { return section.field + '[' + index + '][subform]' };

      $(fieldWrapperSelector).once('foia-add-populate-button').each(function() {
        // Build buttons contain.
        var sectionBtns = $('<div class="section-button-group" style="display: flex;padding-top: .5rem;gap: 1rem;"><div class="component-placeholder-button-div"><div class="description">Use this button when starting a new report, to quickly add placeholders for all of the components that you have selected in the checkboxes above.</div></div><div class="no-data-to-report-div"><div class="description">Use this button to quickly fill 0 or N/A for components do not apply.</div></div></div>');
        $(this).prepend(sectionBtns);


        ComponentPlaceholderButtonDiv = $(this).find('.section-button-group .component-placeholder-button-div');
        NoDataToReportDiv = $(this).find('.section-button-group .no-data-to-report-div');
        // Build component placeholder button.
        var $PlaceholderButton = $('<button class="button component-placeholder-button">Add placeholders for component data below</button>');
        ComponentPlaceholderButtonDiv.prepend($PlaceholderButton);
        // Build no data report button.
        var $NoDataButton = $('<button class="button no-data-report-button">No data to report for this section</button>');
        NoDataToReportDiv.prepend($NoDataButton);

        $NoDataButton.click(function (evt) {
          evt.preventDefault();
          section.section.fnt();
        });
        $PlaceholderButton.click(function(evt) {
          evt.preventDefault();
          var $components = $(checkedComponentSelector),
              numComponents = $components.length,
              currentComponent = 0,
              singleComponent = $(existingComponentSelector).length === 1,
              componentDropdownName = getComponentDropdownName(currentComponent),
              componentDropdownSelector = 'select[name^="' + componentDropdownName + '"]',
              blankComponent = singleComponent && $(componentDropdownSelector).val() === '_none';
          if (numComponents === 0) {
            alert('First select the components you want using the checkboxes above.');
          }
          else if ($(existingComponentSelector).length > 0 && !blankComponent) {
            alert('Placeholders cannot be added while there are existing entries. Please remove all entries and try again.');
          }
          else {
            function clickAddMoreButton() {
              $(addMoreSelector).trigger('mousedown');
            }
            function populateNextComponent() {
              var componentNodeId = $components.eq(currentComponent).val();
              componentDropdownName = getComponentDropdownName(currentComponent);
              componentDropdownSelector = 'select[name^="' + componentDropdownName + '"]';
              $(componentDropdownSelector).val(componentNodeId);

              currentComponent += 1;
              if (currentComponent < numComponents) {
                clickAddMoreButton();
              }
              else {
                alert('Finished adding placeholders.');
              }
            }
            $(document).on('ajaxStop', function() {
              if (currentComponent < numComponents) {
                populateNextComponent(currentComponent);
              }
            });
            if (blankComponent) {
              populateNextComponent();
            }
            else {
              clickAddMoreButton();
            }
          }
        });
      });
    }
  };
})(jQuery, drupalSettings);
