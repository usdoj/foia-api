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
      $('#edit-field-quarterly-component-data-wrapper').once('foia-add-populate-button').each(function() {
        var $button = $('<button class="button">Add placeholders for component data below</button>');
        $(this).prepend($button);
        $button.click(function(evt) {
          var $rows = $('#field-quarterly-component-data-values tbody tr');
          var $components = $('#edit-field-agency-components input:checked');
          if ($rows.length > 0) {
            alert('Placeholders cannot be added while there are existing entries. Please remove all entries and try again.');
          }
          else {
            console.log($components.length);
          }
          evt.preventDefault();
        });
      });

    }
  }
})(jQuery, drupalSettings);
