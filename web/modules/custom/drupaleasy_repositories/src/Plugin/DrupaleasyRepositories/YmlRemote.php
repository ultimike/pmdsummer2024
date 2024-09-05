<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories;

use Drupal\Component\Serialization\Yaml;
use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase;

/**
 * Plugin implementation of the drupaleasy_repositories.
 *
 * @DrupaleasyRepositories(
 *   id = "yml_remote",
 *   label = @Translation("Remote .yml file"),
 *   description = @Translation("Remote .yml file that includes repository metadata.")
 * )
 */
final class YmlRemote extends DrupaleasyRepositoriesPluginBase {

  /**
   * {@inheritdoc}
   */
  public function validate(string $uri): bool {
    $pattern = '|^https?://[a-zA-Z0-9.\-]+/[a-zA-Z0-9_\-.%/]+\.ya?ml$|';
    return preg_match($pattern, $uri) === 1;
  }

  /**
   * {@inheritdoc}
   */
  public function validateHelpText(): string {
    return 'https://anything.anything/anything/anything.yml (or http or yaml)';
  }

  /**
   * {@inheritdoc}
   */
  public function getRepo(string $uri): array {
    if ($repo_info = file_get_contents($uri)) {
      $repo_info = Yaml::decode($repo_info);
      $machine_name = array_key_first($repo_info);
      $repo = reset($repo_info);
      return $this->mapToCommonFormat($machine_name, $repo['label'], $repo['description'], $repo['num_open_issues'], $uri);
    }
    return [];
  }

}
