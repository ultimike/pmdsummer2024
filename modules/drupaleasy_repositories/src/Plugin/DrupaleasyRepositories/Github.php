<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories;

use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase;
use Github\AuthMethod;
use Github\Client;

/**
 * Plugin implementation of the drupaleasy_repositories.
 *
 * @DrupaleasyRepositories(
 *   id = "github",
 *   label = @Translation("GitHub"),
 *   description = @Translation("GitHub.com"),
 *   url_help_text = @Translation("https://github.com/vendor/name")
 * )
 */
final class Github extends DrupaleasyRepositoriesPluginBase {

  /**
   * The GitHub client object used for making API calls.
   *
   * @var \Github\Client
   */
  protected Client $client;

  /**
   * {@inheritdoc}
   */
  public function validate(string $uri): bool {
    $pattern = '|^https://github.com/[a-zA-Z0-9_\-]+/[a-zA-Z0-9_\-]+$|';
    return preg_match($pattern, $uri) === 1;
  }

  /**
   * {@inheritdoc}
   */
  public function getRepo(string $uri): array {
    // Get the repository vendor and name from the $uri parameter.
    $all_parts = parse_url($uri);
    $path_parts = explode('/', $all_parts['path']);

    // Set up API authentication.
    $this->setAuthentication();

    // Make the API call to get the repository metadata.
    try {
      // Try this code.
      /** @var \Github\Api\Repo $repo */
      $repo = $this->client->api('repo');
      $repo_metadata = $repo->show($path_parts[1], $path_parts[2]);
    }
    catch (\Throwable $th) {
      // If an exception is thrown, then run this code.
      $this->messenger->addMessage($this->t('GitHub error: @error', [
        '@error' => $th->getMessage(),
      ]));
      return [];
    }

    // Map it to a common format.
    return $this->mapToCommonFormat(
      $repo_metadata['full_name'],
      $repo_metadata['name'],
      $repo_metadata['description'],
      $repo_metadata['open_issues_count'],
      $repo_metadata['html_url']);
  }

  /**
   * Authenticate with GitHub.
   */
  protected function setAuthentication(): void {
    $this->client = new Client();

    // Get access to the credentials from the Key module.
    $github_key = $this->keyRepository->getKey('github')->getKeyValues();

    // The authenticate() method does not actually call the GitHub API,
    // rather it only stores the authentication info in $this->client for use
    // when $this->client makes an API call that requires authentication.
    $this->client->authenticate($github_key['username'], $github_key['personal_access_token'], AuthMethod::CLIENT_ID);
  }

}
