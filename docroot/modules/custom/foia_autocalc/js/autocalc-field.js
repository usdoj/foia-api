(function ($, drupalSettings, Drupal) {
  Drupal.behaviors.autocalcFields = {
    attach: function attach() {
      var autocalcSettings = drupalSettings.foiaAutocalc.autocalcSettings;
      Object.keys(autocalcSettings).forEach(function(fieldName) {
        var fieldSettings = autocalcSettings[fieldName];
        fieldSettings.forEach(function(fieldSetting) {
          var fieldSelector = convertToFieldSelector(fieldSetting);
          $(fieldSelector + ' input').each(function(index) {
            $(this).once(fieldSelector + '_' + index).on('change', function() {
              calculateField(fieldName, fieldSettings);
            });
          });
        });
      });
    }
  };

  // Determines the value of the automatically calculated field.
  function calculateField(fieldName, fieldSettings) {
    var totalValues = {};
    var idSelector = '';

    fieldSettings.forEach(function (fieldSetting) {
      $(convertToFieldSelector(fieldSetting) + ' input').each(function() {
        var selectedValue = 0;
        if (isNumeric($(this).val())) {
          selectedValue = Number($(this).val());
        }

        // Get the selector for this field.
        if (fieldSetting.hasOwnProperty('this_entity') && fieldSetting.this_entity) {
          var index = $(this).attr('name').match(/\[(.*?)\]/)[1];
          idSelector = 'edit-' + fieldSetting.field.replace(/_/g, '-') + '-' + index;
        }
        else {
          idSelector = 'all';
        }

        // Add value to the selector.
        if (totalValues.hasOwnProperty(idSelector)) {
          totalValues[idSelector] += selectedValue;
        }
        else {
          totalValues[idSelector] = selectedValue;
        }
      });
    });

    Object.keys(totalValues).forEach(function (selector) {
      if (selector == 'all') {
        $(convertToFieldSelector({ field: fieldName }) + ' input').val(totalValues[selector]);
      }
      else {
        $("div[data-drupal-selector='" + selector + "'] " + convertToFieldSelector({ field: fieldName }) + ' input').val(totalValues[selector]);
      }
    });
  }

  // Converts field autocalc settings to a jQuery selector.
  function convertToFieldSelector(fieldSetting) {
    var selector = '.field--name-' + fieldSetting.field.replace(/_/g, '-');
    if (fieldSetting.hasOwnProperty('subfield')) {
      selector += ' ' + convertToFieldSelector(fieldSetting.subfield);
    }
    return selector;
  }

  function isNumeric(n) {
    return !isNaN(parseFloat(n)) && isFinite(n);
  }

})(jQuery, drupalSettings, Drupal);
