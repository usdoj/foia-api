(function ($, drupalSettings) {
  Drupal.behaviors.foia_change_report_agency = {
    attach: function attach() {
      this.triggerNodeRefreshOnUpdate();
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
    triggerNodeRefreshOnUpdate: function() {
      drupalSettings.foiaReportAgencyInitialValue = $('#edit-field-agency-0-target-id').val();

      $('#edit-field-agency-0-target-id').once('foia-trigger-agency-change').blur(function(event) {
        if ($(this).val() !== drupalSettings.foiaReportAgencyInitialValue) {
          $('#edit-field-agency-0-target-id').trigger('change.agency');
        }
      });
    }
  }
})(jQuery, drupalSettings);
