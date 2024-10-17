<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Service class for DrupalEasy Repositories module.
 */
final class DrupaleasyRepositoriesService {

  use StringTranslationTrait;

  /**
   * The constructor.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManagerDrupaleasyRepositories
   *   Our plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Drupal core config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param bool $dryRun
   *   When set to "true", no nodes are created, updated, or deleted.
   */
  public function __construct(
    protected PluginManagerInterface $pluginManagerDrupaleasyRepositories,
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected bool $dryRun = FALSE,
  ) {}

  /**
   * Get help text from enabled plugins.
   *
   * @return string
   *   Concatenated help strings.
   */
  public function getValidatorHelpText(): string {
    $repository_plugins = [];

    // Get the enabled list of our plugins from the Drupal config system.
    // Use Null Coalesce Operator in case no repositories are enabled.
    // See https://wiki.php.net/rfc/isset_ternary
    $repositories_plugin_ids = $this->configFactory->get('drupaleasy_repositories.settings')
      ->get('repositories_plugins') ?? [];

    // Loop around all plugins, and instantiate the enabled ones.
    foreach ($repositories_plugin_ids as $repositories_plugin_id) {
      if (!empty($repositories_plugin_id)) {
        $repository_plugins[] = $this->pluginManagerDrupaleasyRepositories->createInstance($repositories_plugin_id);
      }
    }

    $help = [];

    /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesInterface $repository_plugin */
    foreach ($repository_plugins as $repository_plugin) {
      $help[] = $repository_plugin->validateHelpText();
    }

    return implode(' ', $help);
  }

  /**
   * Validate repository URLs.
   *
   * Validate the URLs are valid based on the enabled plugins and ensure they
   * haven't been added by another user.
   *
   * @param array<mixed> $urls
   *   The urls to be validated.
   * @param int $uid
   *   The user id of the user submitting the URLs.
   *
   * @return string
   *   Errors reported by plugins.
   */
  public function validateRepositoryUrls(array $urls, int $uid): string {
    $errors = [];
    $repository_plugins = [];

    // Get IDs all DrupaleasyRepository plugins (enabled or not).
    $repository_plugin_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories_plugins') ?? [];

    // Instantiate each enabled DrupaleasyRepository plugin (and confirm that
    // at least one is enabled).
    $atLeastOne = FALSE;
    foreach ($repository_plugin_ids as $repository_plugin_id) {
      if (!empty($repository_plugin_id)) {
        $atLeastOne = TRUE;
        $repository_plugins[] = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_plugin_id);
      }
    }
    if (!$atLeastOne) {
      return 'There are no enabled repository plugins';
    }

    // Loop around each Repository URL and attempt to validate.
    foreach ($urls as $url) {
      if (is_array($url)) {
        if ($uri = trim($url['uri'])) {
          $is_valid_url = FALSE;
          // Check to see if the URI is valid for any enabled plugins.
          /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesInterface $repository_plugin */
          foreach ($repository_plugins as $repository_plugin) {
            if ($repository_plugin->validate($uri)) {
              $is_valid_url = TRUE;
              $repo_metadata = $repository_plugin->getRepo($uri);
              if ($repo_metadata) {
                if (!$this->isUnique($repo_metadata, $uid)) {
                  $errors[] = $this->t('Another user has already added the repository from %uri to this site.', ['%uri' => $uri]);
                }
              }
              else {
                $errors[] = $this->t('The repository at the url %uri was not found.', ['%uri' => $uri]);
              }
            }
          }
          if (!$is_valid_url) {
            $errors[] = $this->t('The repository url %uri is not valid.', ['%uri' => $uri]);
          }
        }
      }
    }

    if ($errors) {
      return implode(' ', $errors);
    }
    // No errors found.
    return '';
  }

  /**
   * Check to see if the repository is unique.
   *
   * @param array<string, array<string, string|int>> $repo_info
   *   The repository array.
   * @param int $uid
   *   The uid of the user trying to add the repository to their profile.
   *
   * @return bool
   *   TRUE if unique.
   */
  protected function isUnique(array $repo_info, int $uid): bool {
    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    $machine_name = array_key_first($repo_info);

    $query = $node_storage->getQuery();
    $query->condition('type', 'repository')
      ->condition('uid', $uid, '<>')
      ->condition('field_machine_name', $machine_name);
    $results = $query->accessCheck(FALSE)->execute();

    return !count($results);
  }

  /**
   * Update the repository nodes for a given account.
   *
   * @param \Drupal\Core\Entity\EntityInterface $account
   *   The user account whose repositories to update.
   *
   * @return bool
   *   TRUE if successful.
   */
  public function updateRepositories(EntityInterface $account): bool {
    $repos_metadata = [];
    $repository_plugin_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories_plugins') ?? [];

    foreach ($repository_plugin_ids as $repository_plugin_id) {
      if (!empty($repository_plugin_id)) {
        /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesInterface $repository_plugin */
        $repository_plugin = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_plugin_id);
        // Loop through repository URLs.
        foreach ($account->field_repository_url ?? [] as $url) {
          // Check if the URL validates for this repository.
          if ($repository_plugin->validate($url->uri)) {
            // Confirm the repository exists and get metadata.
            if ($repo_metadata = $repository_plugin->getRepo($url->uri)) {
              $repos_metadata += $repo_metadata;
            }
          }
        }
      }
    }
    $repos_updated = $this->updateRepositoryNodes($repos_metadata, $account);
    $repos_deleted = $this->deleteRepositoryNodes($repos_metadata, $account);
    return $repos_updated || $repos_deleted;
  }

  /**
   * Update repository nodes for a given user.
   *
   * @param array<string, array<string, string|int>> $repos_info
   *   Repository info from API call.
   * @param \Drupal\Core\Entity\EntityInterface $account
   *   The user account whose repositories to update.
   *
   * @return bool
   *   TRUE if successful.
   */
  protected function updateRepositoryNodes(array $repos_info, EntityInterface $account): bool {
    if (!$repos_info) {
      return TRUE;
    }
    // Prepare the storage and query stuff.
    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    foreach ($repos_info as $key => $repo_info) {
      // Calculate hash value.
      $hash = md5(serialize($repo_info));

      // Look for repository nodes from this user with matching
      // machine_name.
      $query = $node_storage->getQuery();
      $query->condition('type', 'repository')
        ->condition('uid', $account->id())
        ->condition('field_machine_name', $key)
        ->condition('field_source', $repo_info['source']);
      $results = $query->accessCheck(FALSE)->execute();

      if ($results) {
        /** @var \Drupal\node\Entity\Node $node */
        $node = $node_storage->load(reset($results));

        if ($hash != $node->get('field_hash')->value) {
          // Something changed, update node.
          $node->setTitle($repo_info['label']);
          $node->set('field_description', $repo_info['description']);
          $node->set('field_machine_name', $key);
          $node->set('field_number_of_issues', $repo_info['num_open_issues']);
          $node->set('field_source', $repo_info['source']);
          $node->set('field_url', $repo_info['url']);
          $node->set('field_hash', $hash);
          if (!$this->dryRun) {
            $node->save();
            // $this->repoUpdated($node, 'updated');
          }
        }
      }
      else {
        // Repository node doesn't exist - create a new one.
        /** @var \Drupal\node\NodeInterface $node */
        $node = $node_storage->create([
          'uid' => $account->id(),
          'type' => 'repository',
          'title' => $repo_info['label'],
          'field_description' => $repo_info['description'],
          'field_machine_name' => $key,
          'field_number_of_issues' => $repo_info['num_open_issues'],
          'field_source' => $repo_info['source'],
          'field_url' => $repo_info['url'],
          'field_hash' => $hash,
        ]);
        if (!$this->dryRun) {
          $node->save();
          // $this->repoUpdated($node, 'created');
        }
      }
    }
    return TRUE;
  }

  /**
   * Delete repository nodes deleted from the source for a given user.
   *
   * @param array<string, array<string, string>> $repos_info
   *   Repository info from API call.
   * @param \Drupal\Core\Entity\EntityInterface $account
   *   The user account whose repositories to update.
   *
   * @return bool
   *   TRUE if successful.
   */
  protected function deleteRepositoryNodes(array $repos_info, EntityInterface $account): bool {
    // Prepare the storage and query stuff.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $node_storage->getQuery();
    $query->condition('type', 'repository')
      ->condition('uid', $account->id())
      ->accessCheck(FALSE);
    // We can't chain this above because $repos_info might be empty.
    if ($repos_info) {
      $query->condition('field_machine_name', array_keys($repos_info), 'NOT IN');
    }
    $results = $query->execute();
    if ($results) {
      $nodes = $node_storage->loadMultiple($results);
      /** @var \Drupal\node\Entity\Node $node */
      foreach ($nodes as $node) {
        if (!$this->dryRun) {
          $node->delete();
          // $this->repoUpdated($node, 'deleted');
        }
      }
    }
    return TRUE;
  }

  /**
   * Returns a list of users whose repositories should be updated.
   *
   * @return array<int, string>
   *   User IDs.
   */
  public function getUserUpdateList(): array {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $query = $user_storage->getQuery();
    $query->condition('status', 1);
    $query->condition('field_repository_url', NULL, 'IS NOT NULL');
    return $query->accessCheck(FALSE)->execute();
  }

}
