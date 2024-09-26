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
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The dry-run parameter.
   *
   * When set to "true", no nodes are created, updated, or deleted.
   *
   * @var bool
   */
  protected bool $dryRun = FALSE;

  /**
   * The constructor.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManagerDrupaleasyRepositories
   *   Our plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Drupal core config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity_type.manager service.
   */
  public function __construct(
    protected PluginManagerInterface $pluginManagerDrupaleasyRepositories,
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entity_type_manager,
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
   * @param array $urls
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
        /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesInterface $repository_location */
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
    return $this->updateRepositoryNodes($repos_metadata, $account);

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
        ->condition('field_source', $repo_info['source'])
        ->accessCheck(FALSE);
      $results = $query->execute();

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
    }
    return TRUE;
  }

}
