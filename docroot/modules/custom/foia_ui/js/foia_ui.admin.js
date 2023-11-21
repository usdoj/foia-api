/**
 * @file
 */

(function ($, drupalSettings, once) {
  Drupal.behaviors.foia_ui_admin = {
    attach: function attach() {
      this.listenForToggleWeight();
    },

    listenForToggleWeight: function () {
      var that = this,
          initialDisplayValue = localStorage.getItem('Drupal.tableDrag.showWeight') || false;

      this.toggleDisplayWeightColumn(initialDisplayValue);

      $(once('ToggleDisplayWeightColumns', 'button.link.tabledrag-toggle-weight')).on('click', function (event) {
        that.toggleDisplayWeightColumn(localStorage.getItem('Drupal.tableDrag.showWeight') || false);
      });
    },

    toggleDisplayWeightColumn: function (displayWeight) {
      var $tables = $(once.filter('tabledrag', 'table'));

      if (displayWeight) {
        $tables.find('> colgroup > col:last-child').css('display', '');
      }
      else {
        $tables.find('> colgroup > col:last-child').css('display', 'none');
      }
    }
  }
})(jQuery, drupalSettings, once);
