(function ($, drupalSettings, Drupal) {
  Drupal.behaviors.foia_ui_validation = {
    attach: function attach() {

      $('#node-annual-foia-report-data-form').validate({

        rules: {
          "field_foia_requests_va[0][subform][field_req_processed_yr][0][value]" : {
            equalTo: "#edit-field-foia-requests-vb1-0-subform-field-total-0-value",

          },
          "field_overall_xiie1_received_cur[0][value]": {
            min: 2,
            max: 4
          }
        }
      });
      $('input#edit-submit').prop('disabled', true);
      $('input#edit-validate-button').on('click', function(event) {
          $('input#edit-submit').prop('disabled', false);
          event.preventDefault();        }
      );
    }
  };

})(jQuery, drupalSettings, Drupal);
