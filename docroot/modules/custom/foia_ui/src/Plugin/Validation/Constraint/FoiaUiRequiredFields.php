<?php

namespace Drupal\foia_ui\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks required fields base on Submission Type.
 *
 * @Constraint(
 *   id = "foia_ui_required_fields",
 *   label = @Translation("FOIA UI Required Fields", context = "Validation"),
 * )
 */
class FoiaUiRequiredFields extends Constraint {

  /**
   * Error message if Submission format is Email and Submission Email is blank.
   *
   * @var string
   */
  public $emailRequired = 'Submission Email is required if Portal Submission Format is set to Email';

  /**
   * Error message if Submission format is API and Submission API URL is blank.
   *
   * @var string
   */
  public $urlRequired = 'Submission API URL is required if Portal Submission Format is set to API';

  /**
   * Error message if API URL is not valid.
   *
   * @var string
   */
  public $apiUrlNotValid = '%url is not a valid URL.';

  /**
   * Error message if format is API and Submission API Shared Secret is blank.
   *
   * @var string
   */
  public $secretRequired = 'Submission API Shared Secret is required if Portal Submission Format is set to API';

}
