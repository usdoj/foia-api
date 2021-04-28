/**
 * @file
 */

(function ($, drupalSettings) {
  Drupal.behaviors.foia_change_report_agency = {
    attach: function attach() {
      this.triggerNodeRefreshOnUpdate();
      this.addPopulateComponentsButton();
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

    addPopulateComponentsButton: function() {
      var fieldWrapperSelector = '#edit-field-quarterly-component-data-wrapper',
          addMoreSelector = 'input[name="field_quarterly_component_data_quarterly_component_data_add_more"]',
          existingComponentSelector = '#edit-field-quarterly-component-data-wrapper tbody tr',
          checkedComponentSelector = '#edit-field-agency-components input:checked',
          componentDropdownSelector = '#edit-field-quarterly-component-data-wrapper table tr:last-child .field--name-field-agency-component select';
      $(fieldWrapperSelector).once('foia-add-populate-button').each(function() {
        var $button = $('<button class="button">Add placeholders for component data below</button>');
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
