<?php

/**
 * @file
 * Batch processing functions file.
 */

/**
 * The processing function.
 *
 * @param object $obj
 *   Batch object.
 * @param object $state
 *   Target state.
 * @param object $context
 *   Batch context.
 */
function update_moderation_state($obj, $state, &$context) {
  try {
    $entity = \Drupal::entityTypeManager()->getStorage('node')->createRevision($obj, $obj->isDefaultRevision());
    $entity->set('moderation_state', $state);

    if (method_exists($entity, 'setRevisionUserId')) {
      $entity->setRevisionCreationTime(REQUEST_TIME);
      $entity->setRevisionLogMessage('VBO Changed moderation state to published, time:' . date('d/m/Y - h:i', REQUEST_TIME));
      $entity->setRevisionUserId(\Drupal::service('current_user')->id());
    }
    $entity->save();
    $context['results'][] = $entity->id();
    $context['message'] = t('Moderation state transition on @nid', ['@nid' => $entity->id()]);
  }
  catch (Exception $e) {
    watchdog_exception('VBO type', $e);
  }
}

/**
 * The processing finished function.
 *
 * @param object $success
 *   Success flag.
 * @param object $results
 *   Batch finished result.
 * @param object $operations
 *   Batch finished operations.
 */
function process_batch_finished($success, $results, $operations) {
  // The 'success' parameter means no fatal PHP errors were detected. All
  // other error management should be handled using 'results'.
  if ($success) {
    $message = count($results) . ' item(s) has processed.';
  }
  else {
    $message = t('Finished with an error.');
  }
  \Drupal::messenger()->addMessage($message);
}
