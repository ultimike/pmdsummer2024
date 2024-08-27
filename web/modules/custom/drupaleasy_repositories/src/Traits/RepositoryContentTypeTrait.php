<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\Traits;

use Drupal\node\Entity\NodeType;

/**
 * Provides a helper method for creating a repository content type with fields.
 */
trait RepositoryContentTypeTrait {

  /**
   * Creates a repository content type with fields.
   */
  protected function createRepositoryContentType(): void {
    NodeType::create(['type' => 'repository', 'name' => 'Repository'])->save();
  }

}
