<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\DrupaleasyRepositories;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\drupaleasy_repositories\Annotation\DrupaleasyRepositories;

/**
 * DrupaleasyRepositories plugin manager.
 */
final class DrupaleasyRepositoriesPluginManager extends DefaultPluginManager {

  /**
   * Constructs DrupaleasyRepositoriesPluginManager object.
   *
   * @param \Traversable<mixed> $namespaces
   *   An object that implements \Traversable with contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/DrupaleasyRepositories', $namespaces, $module_handler, DrupaleasyRepositoriesInterface::class, DrupaleasyRepositories::class);
    $this->alterInfo('drupaleasy_repositories_info');
    $this->setCacheBackend($cache_backend, 'drupaleasy_repositories_plugins');
  }

}
