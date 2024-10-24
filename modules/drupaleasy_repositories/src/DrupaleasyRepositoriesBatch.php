<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * DrupalEasy repositories batch service class to integrate with Batch API.
 */
final class DrupaleasyRepositoriesBatch {
  use StringTranslationTrait;

  /**
   * Constructs a DrupaleasyRepositoriesBatch object.
   */
  public function __construct(
    private readonly DrupaleasyRepositoriesService $drupaleasyRepositoriesService,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ModuleExtensionList $extensionListModule,
  ) {}

  /**
   * Updates all user repositories using the Batch API.
   */
  public function updateAllRepositories(): void {
    // $operations = [];
    // Get all active users with repository URL data.
    $users = $this->drupaleasyRepositoriesService->getUserUpdateList();

    // Create a Batch API operation for each user.
    // foreach ($users as $uid => $user) {
    //   $operations[] = ['drupaleasy_update_repositories_batch_operation', [$uid]];
    // }.
    // $batch = [
    //   'operations' => $operations,
    //   'file' => $this->extensionListModule->getPath('drupaleasy_repositories') . '/drupaleasy_repositories.batch.inc',
    //   'finished' => 'drupaleasy_update_all_repositories_finished',
    // ];.
    $batch_builder = (new BatchBuilder())
      ->setTitle($this->t('Processing Batch'))
      ->setFile($this->extensionListModule->getPath('drupaleasy_repositories') . '/drupaleasy_repositories.batch.inc')
      ->setFinishCallback('drupaleasy_update_all_repositories_finished')
      ->setInitMessage($this->t('Batch is starting'))
      ->setProgressMessage($this->t('Processed @current out of @total.'))
      ->setErrorMessage($this->t('Batch has encountered an error.'));
    foreach ($users as $uid => $user) {
      $batch_builder->addOperation('drupaleasy_update_repositories_batch_operation', [$uid]);
    }
    batch_set($batch_builder->toArray());

    // Submit the batch for processing.
    // batch_set($batch);
  }

  /**
   * Method for updating user repositories in a Batch.
   *
   * @param int $uid
   *   The user ID whose repositories will be updated.
   * @param array<mixed>|\ArrayAccess<string, array<mixed>> $context
   *   The Batch API context.
   */
  public function updateRepositoriesBatch(int $uid, array|\ArrayAccess &$context): void {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $account = $user_storage->load($uid);
    if ($this->drupaleasyRepositoriesService->updateRepositories($account)) {
      $context['results']['uids'][] = $uid;
      $context['message'] = $this->t('Updated repositories belonging to "@username".', ['@username' => $account->label()]);
    }
  }

}
