<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\Traits;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;

/**
 * Provides a helper method for creating a repository content type with fields.
 */
trait RepositoryContentTypeTrait {

  /**
   *
   */
  protected function createUserRepositoryUrlField(): void {
    // Add the Repository URL field to the user entity.
    FieldStorageConfig::create([
      'field_name' => 'field_repository_url',
      'type' => 'link',
      'entity_type' => 'user',
      'cardinality' => -1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_repository_url',
      'entity_type' => 'user',
      'bundle' => 'user',
      'label' => 'Repository URL',
    ])->save();

    // Ensure that the Repository URL field is visible in the existing user
    // entity form mode.
    /** @var \Drupal\Core\Entity\EntityDisplayRepository $entity_display_repository */
    $entity_display_repository = \Drupal::service('entity_display.repository');
    $entity_display_repository->getFormDisplay('user', 'user', 'default')
      ->setComponent('field_repository_url', ['type' => 'link_default'])
      ->save();
  }

  /**
   * Creates a repository content type with fields.
   */
  protected function createRepositoryContentType(): void {
    NodeType::create(['type' => 'repository', 'name' => 'Repository'])->save();

    // Create Description field.
    FieldStorageConfig::create([
      'field_name' => 'field_description',
      'type' => 'text_long',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_description',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'Description',
    ])->save();

    // Create Hash field.
    FieldStorageConfig::create([
      'field_name' => 'field_hash',
      'type' => 'string',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_hash',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'Hash',
    ])->save();

    // Create Machine name field.
    FieldStorageConfig::create([
      'field_name' => 'field_machine_name',
      'type' => 'string',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_machine_name',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'Machine name',
    ])->save();

    // Create Number of issues field.
    FieldStorageConfig::create([
      'field_name' => 'field_number_of_issues',
      'type' => 'integer',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_number_of_issues',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'Number of issues',
    ])->save();

    // Create Source field.
    FieldStorageConfig::create([
      'field_name' => 'field_source',
      'type' => 'string',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_source',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'Source',
    ])->save();

    // Create URL field.
    FieldStorageConfig::create([
      'field_name' => 'field_url',
      'type' => 'link',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_url',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'URL',
    ])->save();

  }

}
