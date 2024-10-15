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
 *   description = @Translation("Remote .yml file that includes repository metadata."),
 *   url_help_text = @Translation("https://anything.anything/anything/anything.yml (or http or yaml)")
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
  public function getRepo(string $uri, Object $client = NULL): array {
    // Temporarily set the PHP error handler to this custom one. If there are
    // any E_WARNINGs, then TRUE to disable default PHP error handler.
    // This is basically telling PHP that we are going handle errors of type
    // E_WARNING until we say otherwise.
    set_error_handler(function () {
      // If FALSE is returned, then the default PHP error handler is run.
      return TRUE;
    },
    E_WARNING
    );

    // The file_exists PHP function doesn't work with files over http.
    // If $uri doesn't exist, file() will throw a PHP E_WARNING.
    if (file($uri)) {
      restore_error_handler();
      if ($repo_info = file_get_contents($uri)) {
        $repo_info = Yaml::decode($repo_info);
        $machine_name = array_key_first($repo_info);
        $repo = reset($repo_info);
        return $this->mapToCommonFormat($machine_name, $repo['label'], $repo['description'], $repo['num_open_issues'], $uri);
      }
    }
    restore_error_handler();
    return [];
  }

}
