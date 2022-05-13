<?php

namespace Drupal\foia_agency_component\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Requires an agency component's abbreviation to be unique within its agency.
 *
 * @Constraint(
 *   id = "AgencyComponentUniqueAbbreviation",
 *   label = @Translation("Unique component abbreviation within agency", context = "Validation"),
 *   type = "string"
 * )
 */
class AgencyComponentUniqueAbbreviation extends Constraint {

  /**
   * Error message for unique abbreviations.
   *
   * @var string
   */
  public $notUnique = 'The abbreviation %abbreviation is already being used by another component within the agency. Please try a different abbreviation.';

}
