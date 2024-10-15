<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\DrupaleasyRepositories;

/**
 * Interface for drupaleasy_repositories plugins.
 */
interface DrupaleasyRepositoriesInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The label as a string.
   */
  public function label(): string;

  /**
   * URL validator.
   *
   * @param string $uri
   *   The uri to validate.
   *
   * @return bool
   *   TRUE if valid.
   */
  public function validate(string $uri): bool;

  /**
   * Returns help text for the plugin's required URL pattern.
   *
   * @return string
   *   The help text.
   */
  public function validateHelpText(): string;

  /**
   * Queries the repository source for info about the repository.
   *
   * @param string $uri
   *   The repository URL.
   * @param Object $client
   *   The repository client helper class.
   *
   * @return array<string, array<string, string|int>>
   *   The repository's metadata.
   */
  public function getRepo(string $uri, Object $client = NULL): array;

}
