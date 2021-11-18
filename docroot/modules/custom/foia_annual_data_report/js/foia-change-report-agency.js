/**
 * @file
 */

(function ($, drupalSettings) {
  Drupal.behaviors.foia_change_report_agency = {
    attach: function attach() {
      this.triggerNodeRefreshOnUpdate();

      var sections = [
        {
          field: 'field_admin_app_vib',
          paragraph: 'admin_app_vib',
        },
        {
          field: 'field_admin_app_vic1',
          paragraph: 'admin_app_vic1',
        },
        {
          field: 'field_admin_app_vic2',
          paragraph: 'admin_app_vic2',
        },
        {
          field: 'field_admin_app_vic3',
          paragraph: 'admin_app_vic3',
        },
        {
          field: 'field_admin_app_vic4',
          paragraph: 'admin_app_vic4',
        },
        {
          field: 'field_admin_app_vic5',
          paragraph: 'oldest_days',
        },
        {
          field: 'field_admin_app_via',
          paragraph: 'admin_app_via',
        },
        {
          field: 'field_fees_x',
          paragraph: 'fees_x',
        },
        {
          field: 'field_foia_pers_costs_ix',
          paragraph: 'foia_pers_costs_ix',
        },
        {
          field: 'field_foia_xiia',
          paragraph: 'foia_xiia',
        },
        {
          field: 'field_foia_xiib',
          paragraph: 'foia_xiib',
        },
        {
          field: 'field_foia_xiic',
          paragraph: 'oldest_days',
        },
        {
          field: 'field_foia_xiid1',
          paragraph: 'foia_xii_received_proc',
        },
        {
          field: 'field_foia_xiid2',
          paragraph: 'foia_xii_backlogged',
        },
        {
          field: 'field_foia_xiie1',
          paragraph: 'foia_xii_received_proc',
        },
        {
          field: 'field_foia_xiie2',
          paragraph: 'foia_xii_backlogged',
        },
        {
          field: 'field_foia_requests_va',
          paragraph: 'foia_req_va',
        },
        {
          field: 'field_foia_requests_vb2',
          paragraph: 'foia_req_vb2',
        },
        {
          field: 'field_foia_requests_vb3',
          paragraph: 'admin_app_vic1',
        },
        {
          field: 'field_foia_requests_vb1',
          paragraph: 'foia_req_vb1',
        },
        {
          field: 'field_pending_requests_vii_d_',
          paragraph: 'pending_requests_viid',
        },
        {
          field: 'field_admin_app_viie',
          paragraph: 'oldest_days',
        },
        {
          field: 'field_proc_req_viia',
          paragraph: 'processed_requests_vii',
        },
        {
          field: 'field_proc_req_viib',
          paragraph: 'processed_requests_vii',
        },
        {
          field: 'field_proc_req_viic1',
          paragraph: 'proc_req_viic',
        },
        {
          field: 'field_proc_req_viic2',
          paragraph: 'proc_req_viic',
        },
        {
          field: 'field_proc_req_viic3',
          paragraph: 'proc_req_viic',
        },
        {
          field: 'field_req_viiib',
          paragraph: 'req_viiib',
        },
        {
          field: 'field_req_viiia',
          paragraph: 'req_viiia',
        },
        {
          field: 'field_statute_iv',
          paragraph: 'statute',
        },
        {
          field: 'field_sub_xia',
          paragraph: 'sub_xia',
        },
        {
          field: 'field_sub_xib',
          paragraph: 'sub_xib',
        },
      ];
      for (var i = 0; i < sections.length; i++) {
        this.addPopulateComponentsButton(sections[i]);
      }

      this.addClearDataButton();
    },

    /**
     * Triggers the change.agency event which is listened for by the form element's ajax handler.
     *
     * The change event doesn't fire for the autocomplete field in IE11.  To work around this,
     * this method listens for the blur event on the field, checks if the field value has
     * changed, and triggers a refresh in all browsers if it has.
     *
     * @see foia_annual_data_report_ajax_existing_node()
     * @see foia_annual_data_report_ajax_new_node()
     */
    triggerNodeRefreshOnUpdate: function () {
      drupalSettings.foiaReportAgencyInitialValue = $('#edit-field-agency-0-target-id').val();

      $('#edit-field-agency-0-target-id').once('foia-trigger-agency-change').blur(function (event) {
        if ($(this).val() !== drupalSettings.foiaReportAgencyInitialValue) {
          $('#edit-field-agency-0-target-id').trigger('change.agency');
        }
      });
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
          existingComponentSelector = fieldWrapperSelector + ' tbody tr',
          checkedComponentSelector = '#edit-field-agency-components input:checked',
          getComponentDropdownName = function(index) { return section.field + '[' + index + '][subform]' };
      $(fieldWrapperSelector).once('foia-add-populate-button').each(function() {
        $(this).prepend('<div class="description">Use this button when starting a new report, to quickly add placeholders for all of the components that you have selected in the checkboxes above.</div>');
        var $button = $('<button class="button component-placeholder-button">Add placeholders for component data below</button>');
        $(this).prepend($button);
        $button.click(function(evt) {
          evt.preventDefault();
          if ($(existingComponentSelector).length > 0) {
            alert('Placeholders cannot be added while there are existing entries. Please remove all entries and try again.');
            return;
          }
          var $components = $(checkedComponentSelector),
              numComponents = $components.length;
          if (numComponents === 0) {
            alert('First select the components you want using the checkboxes above.');
            return;
          }
          var currentComponent = 0;
          function clickAddMoreButton() {
            $(addMoreSelector).trigger('mousedown');
          }
          function populateNextComponent() {
            var componentNodeId = $components.eq(currentComponent).val();
            var componentDropdownName = getComponentDropdownName(currentComponent);
            var componentDropdownSelector = 'select[name^="' + componentDropdownName + '"]';
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
          // Kick things off with the first click.
          clickAddMoreButton();
        });
      });
    }
  }
})(jQuery, drupalSettings);
