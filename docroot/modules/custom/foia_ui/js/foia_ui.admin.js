(function ($, drupalSettings) {
  Drupal.behaviors.foia_ui_admin = {
    attach: function attach() {
      this.listenForToggleWeight();
    },

    listenForToggleWeight: function() {
      var that = this,
          initialDisplayValue = localStorage.getItem('Drupal.tableDrag.showWeight') || false;

      this.toggleDisplayWeightColumn(initialDisplayValue);

      $('button.link.tabledrag-toggle-weight').once('ToggleDisplayWeightColumns').on('click', function(event) {
        that.toggleDisplayWeightColumn(localStorage.getItem('Drupal.tableDrag.showWeight') || false);
      });
    },

    toggleDisplayWeightColumn: function(displayWeight) {
      var $tables = $('table').findOnce('tabledrag');

      if (displayWeight) {
        $tables.find('> colgroup > col:last-child').css('display', '');
      }
      else {
        $tables.find('> colgroup > col:last-child').css('display', 'none');
      }
    }
  }
})(jQuery, drupalSettings);
