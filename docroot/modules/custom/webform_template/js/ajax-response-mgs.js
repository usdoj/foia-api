(function ($, drupalSettings, Drupal) {
  Drupal.behaviors.on_page_load = {
    attach: function (context, settings) {
      console.log(drupalSettings.var.templated);
      $('#templated').prop('checked', drupalSettings.var.templated);
    }
  }
})(jQuery, drupalSettings, Drupal, once);
