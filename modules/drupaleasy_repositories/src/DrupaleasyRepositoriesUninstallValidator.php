<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Ensures that no Repository URL data exists prior to uninstall.
 */
final class DrupaleasyRepositoriesUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * Constructs the object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function validate($module): array {
    $reasons = [];
    if ($module === 'drupaleasy_repositories') {
      /** @var \Drupal\user\UserStorageInterface $user_storage */
      $user_storage = $this->entityTypeManager->getStorage('user');
      $query = $user_storage->getQuery();
      $query->condition('field_repository_url', NULL, 'IS NOT NULL');
      if (count($query->accessCheck(FALSE)->execute())) {
        $reasons[] = $this->t('Data exists in the Repository URL field.');
      }

      /** @var \Drupal\node\NodeStorageInterface $node_storage */
      $node_storage = $this->entityTypeManager->getStorage('node');
      $query = $node_storage->getQuery();
      $query->condition('type', 'repository');
      if (count($query->accessCheck(FALSE)->execute())) {
        $reasons[] = $this->t('Repository nodes still exist.');
      }
    }
    return $reasons;
  }

}
