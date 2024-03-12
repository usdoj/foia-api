/**
 * @file
 */

(function ($, drupalSettings) {
  Drupal.behaviors.foia_change_report_agency = {
    attach: function attach() {
      this.addPopulateComponentsButton();
    },

    addPopulateComponentsButton: function() {
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
          var $components = $(checkedComponentSelector),
              numComponents = $components.length,
              currentComponent = 0,
              singleComponent = $(existingComponentSelector).length === 1,
              blankComponent = singleComponent && $(componentDropdownSelector).val() === '_none';
          if (numComponents === 0) {
            alert('First select the components you want using the checkboxes above.');
          }
          else if ($(existingComponentSelector).length > 0 && !blankComponent) {
            alert('Placeholders cannot be added while there are existing entries. Please remove all entries and try again.');
          }
          else {
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
            if (blankComponent) {
              populateNextComponent();
            }
            else {
              clickAddMoreButton();
            }
          }
        });
      });
    }
  }
})(jQuery, drupalSettings);
