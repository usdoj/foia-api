/**
 * @file
 */

(function ($, drupalSettings) {
  console.log("drupalSettings");
  let fieldAgency = $('#edit-field-agency-0-target-id');
  Drupal.behaviors.foia_change_report_agency = {
    attach: function attach() {
      this.triggerNodeRefreshOnUpdate();
      this.addPopulateComponentsButton();
      this.modalButton();

    },
    /**
     * Clears out Agency field on modal close
     * @see foia_quarterly_data_report_create_node()
     */
    modalButton: function () {
      $( ".agency-dialog" ).on( "dialogbeforeclose", function( e, ui ) {
        fieldAgency.val('');
      });
      $('.agency-back').click(function(e) {
        fieldAgency.val('');
        $('.ui-icon-closethick').trigger('click');
        e.preventDefault();
      });
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
      console.log("triggerNodeRefreshOnUpdate");
      drupalSettings.foiaReportAgencyInitialValue = fieldAgency.val();

      fieldAgency.once('foia-trigger-agency-change').blur(function (event) {
        console.log("#edit-field-agency-0-target-id value", $(this).val())
        if ($(this).val() !== drupalSettings.foiaReportAgencyInitialValue) {
          console.log("triggerNodeRefreshOnUpdate trigger: change.agency")
          fieldAgency.trigger('change.agency');
        }
      });
    },

    addPopulateComponentsButton: function() {
      console.log("addPopulateComponentsButton")
      var fieldWrapperSelector = '#edit-field-quarterly-component-data-wrapper',
        addMoreSelector = 'input[name="field_quarterly_component_data_quarterly_component_data_add_more"]',
        existingComponentSelector = '#edit-field-quarterly-component-data-wrapper tbody tr',
        checkedComponentSelector = '#edit-field-agency-components input:checked',
        componentDropdownSelector = '#edit-field-quarterly-component-data-wrapper table tr:last-child .field--name-field-agency-component select';
      $(fieldWrapperSelector).once('foia-add-populate-button').each(function() {
        $(this).prepend('<div class="description">Use this button when starting a new report, to quickly add placeholders for all of the components that you have selected in the checkboxes above.</div>');
        var $button = $('<button class="button component-placeholder-button">Add placeholders for component data below</button>');
        $(this).prepend($button);
        $button.click(function(evt) {
          evt.preventDefault();
          if ($(existingComponentSelector).length > 0) {
            //$(existingComponentSelector).remove();
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
