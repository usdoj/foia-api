(function ($, drupalSettings, Drupal) {
  Drupal.behaviors.foia_ui_validation = {
    attach: function attach() {
      jQuery.validator.setDefaults({
        debug: true,
        success: "valid"
      });

      var regionHeight = ($('.layout-region-node-main').height() + 1600) + 'px';
      $('.layout-region-node-secondary').css('position', 'relative').css('height', regionHeight);
      $('.layout-region-node-secondary').append('<div class="error" style="position: -webkit-sticky; position: sticky; top: 100px;"><span></span></div>');

      $('#node-annual-foia-report-data-form').validate({

        invalidHandler: function(event, validator) {
          var errors = validator.numberOfInvalids();
          if (errors) {
            var message = errors == 1 ? 'You missed 1 field. It has been highlighted.' : 'You missed ' + errors + ' fields.  They have been highlighted.';
            $("div.error span").html(message);
            $("div.error").show();
          }
          else {
            $("div.error").hide();
          }
        },

        rules: {
          // FOIA Requests V. A.
          "field_foia_requests_va[0][subform][field_req_processed_yr][0][value]" : {
            equalTo: "#edit-field-foia-requests-vb1-0-subform-field-total-0-value"
          },
          // Agency Overall Number of Requests Processed in Fiscal Year
          "field_overall_req_processed_yr[0][value]" : {
            equalTo: "#edit-field-overall-vb1-total-0-value"
          },
          "field_overall_xiie1_received_cur[0][value]": {
            min: 2,
            max: 4
          }
        },

        messages: {
          // FOIA Requests V. A.
          "field_foia_requests_va[0][subform][field_req_processed_yr][0][value]": {
              equalTo: "Must match corresponding agency V.B.(1) Total"
          },
          // Agency Overall Number of Requests Processed in Fiscal Year
          "field_overall_req_processed_yr[0][value]" : {
            equalTo: "Must match V.B.(1) Agency Overall Total"
          },
        }
      });
      $('input#edit-submit').prop('disabled', true);
      $('input#edit-validate-button').on('click', function(event) {
        $('#node-annual-foia-report-data-form').valid();
        $('input#edit-submit').prop('disabled', false);
        event.preventDefault();
      });
    }
  };

})(jQuery, drupalSettings, Drupal);
