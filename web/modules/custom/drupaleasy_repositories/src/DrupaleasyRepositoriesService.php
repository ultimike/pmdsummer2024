<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service class for DrupalEasy Repositories module.
 */
final class DrupaleasyRepositoriesService {

  /**
   * The constructor.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManagerDrupaleasyRepositories
   *   Our plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Drupal core config factory service.
   */
  public function __construct(
    protected PluginManagerInterface $pluginManagerDrupaleasyRepositories,
    protected ConfigFactoryInterface $configFactory,
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

}
