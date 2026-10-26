<?php

namespace Drupal\foia_raw_data_to_report\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks agency membership and unique components on raw data reports.
 *
 * @Constraint(
 *   id = "RawDataUploads",
 *   label = @Translation("Raw data component uploads", context = "Validation")
 * )
 */
class RawDataUploads extends Constraint {}
