<?php

namespace Drupal\foia_api\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformSubmissionForm;

/**
 * Represents webform submissions as a resource.
 *
 * @RestResource(
 *   id = "webform_submission",
 *   label = @Translation("Webform submission"),
 *   uri_paths = {
 *     "https://www.drupal.org/link-relations/create" = "/api/webform/submit",
 *   },
 * )
 */
class WebformSubmissionResource extends ResourceBase {

  public function post(array $data) {

    $values = [
      'webform_id' => 'basic_request_submission_form',
      'data' => NULL,
    ];

    // Check webform is open.
    $webform = Webform::load($values['webform_id']);
    $is_open = WebformSubmissionForm::isOpen($webform);

    if ($is_open === TRUE) {
      // Validate submission.
      $errors = WebformSubmissionForm::validateValues($values);

      // Check there are no validation errors.
      if (!empty($errors)) {
        $errors = ['error' => $errors];
        return new ModifiedResourceResponse($errors);
      }
      else {
        // Submit values and get submission ID.
        $webform_submission = WebformSubmissionForm::submitValues($values);
        return new ModifiedResourceResponse($webform_submission->id());
      }
    }

  }

}
