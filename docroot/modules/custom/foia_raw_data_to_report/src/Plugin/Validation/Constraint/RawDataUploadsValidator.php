<?php

namespace Drupal\foia_raw_data_to_report\Plugin\Validation\Constraint;

use Drupal\foia_raw_data_to_report\UploadAssignments;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the parent report's component upload assignments.
 */
class RawDataUploadsValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint): void {
    foreach (UploadAssignments::validate($items->getEntity()) as $delta => $errors) {
      foreach ($errors as $error) {
        $this->context->buildViolation('Upload @number: @error')
          ->setParameter('@number', (string) ($delta + 1))
          ->setParameter('@error', $error)
          ->atPath((string) $delta)
          ->addViolation();
      }
    }
  }

}
