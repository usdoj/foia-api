/**
 * @file
 * Utility functions for FOIA admin UI.
 */

(function ($, drupalSettings) {

  'use strict';

  /**
   * Converts value to number and "N/A", "n/a", and "<1" values to 0.
   *
   * @param value
   * @returns {number}
   */
  Drupal.FoiaUI = {
    specialNumber: function (value) {
      switch (String(value).toLowerCase()) {
        case "n/a":
          return Number(0);
          break;
        case "<1":
          return Number(0.1);
          break;
        default:
          return Number(value);
      }
    },

    /**
     * Gets Agency/Component field value for a given field.
     *
     * @param {jQuery} changed
     *   A jQuery object of the changed field
     * @returns {string}
     *   The related select value of field_agency_component.
     */
    getAgencyComponent: function (changed) {
      return $(changed).parents('.paragraphs-subform').find("select[name*='field_agency_component']").val();
    },

    /**
     * Checks whether value is empty, null, or undefined.
     *
     * @param value
     *   A string or numeric variable.
     * @returns {boolean}
     */
    isEmpty: function (value) {
      if ( typeof value == 'undefined' || !value || value === 0 || value.length == 0 ) {
        return true;
      }
      else {
        return false;
      }
    },

    /**
     * Checks whether selected Agency Component is ' - None - '.
     *
     * @param agencyComponentVal
     *   A string or numeric value provided as the Agency Component.
     * @returns {boolean}
     */
    hasAgencyComponent: function (agencyComponentVal) {
      if ( Drupal.FoiaUI.isEmpty(agencyComponentVal) || agencyComponentVal == '_none') {
        return false;
      }
      else {
        return true;
      }
    }

  }

})(jQuery, drupalSettings);
