<?php

/**
 * @file
 * Batch-related functions for DrupalEasy repositories module.
 */

declare(strict_types=1);

/**
 * Operation function for updating user repositories in a Batch.
 *
 * @param int $uid
 *   The user ID whose repositories will be updated.
 * @param array<mixed>|\ArrayAccess<string, array<mixed>> $context
 *   The Batch API context.
 */
function drupaleasy_update_repositories_batch_operation(int $uid, array|\ArrayAccess &$context): void {
  /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesBatch $drupaleasy_repositories_batch */
  $drupaleasy_repositories_batch = \Drupal::service('drupaleasy_repositories.batch');
  $drupaleasy_repositories_batch->updateRepositoriesBatch($uid, $context);
}

/**
 * This is the "finished" function for our Batch API process.
 *
 * @param bool $success
 *   Did the batch finish successfully.
 * @param array<string, array<int, int>> $results
 *   The results array from $context['results'].
 * @param array<int, array<int, string|array<int, int>>> $operations
 *   The array of failed operations.
 */
function drupaleasy_update_all_repositories_finished(bool $success, array $results, array $operations): void {
  /** @var \Drupal\Core\Messenger\MessengerInterface $messenger */
  $messenger = \Drupal::service('messenger');
  $count = count($results['uids']);
  if ($success) {
    if ($count > 0) {
      $translation = \Drupal::translation();
      $messenger->addStatus($translation->formatPlural($count, 'Updated repository nodes for one user.', 'Updated repository nodes for @count users.'));
    }
    else {
      $messenger->addWarning(t('Zero repositories updated.'));
    }
  }
  else {
    $error_operation = reset($operations);
    $message = t('An error occurred while processing %error_operation with arguments: @arguments', [
      '%error_operation' => $error_operation[0],
      '@arguments' => $error_operation[1],
    ]);
    $messenger->addError($message);
  }
}
