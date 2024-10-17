<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'drupaleasy_repositories_repository_node_updater' queue worker.
 *
 * @QueueWorker(
 *   id = "drupaleasy_repositories_repository_node_updater",
 *   title = @Translation("Repository node updater"),
 *   cron = {"time" = 60},
 * )
 */
final class RepositoryNodeUpdater extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): RepositoryNodeUpdater {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('drupaleasy_repositories.service'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Constructs a RepositoryNodeUpdater object.
   *
   * @param array<mixed> $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService $repositoriesService
   *   The DrupalEasy repositories custom service class.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The core entity type manager service.
   */
  final public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected DrupaleasyRepositoriesService $repositoriesService,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if (isset($data['uid'])) {
      // Load the full user entity from the uid.
      $user = $this->entityTypeManager->getStorage('user')->load($data['uid']);
      if (isset($user)) {
        // Call our custom service method.
        $this->repositoriesService->updateRepositories($user);
      }
    }
  }

}
