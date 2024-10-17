<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;

/**
 * DrupalEasy repositories batch service class to integrate with Batch API.
 */
final class DrupaleasyRepositoriesBatch {

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
    $operations = [];

    // Get all active users with repository URL data.
    $users = $this->drupaleasyRepositoriesService->getUserUpdateList();

    // Create a Batch API operation for each user.
    foreach ($users as $uid => $user) {
      $operations[] = ['drupaleasy_update_repositories_batch_operation', [$uid]];
    }

    $batch = [
      'operations' => $operations,
      'file' => $this->extensionListModule->getPath('drupaleasy_repositories') . '/drupaleasy_repositories.batch.inc',
      'finished' => 'drupaleasy_update_all_repositories_finished',
    ];

    // Submit the batch for processing.
    batch_set($batch);
  }

}
