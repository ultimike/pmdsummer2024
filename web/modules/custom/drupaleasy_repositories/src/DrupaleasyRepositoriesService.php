<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * @todo Add class description.
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
    // Get module config.
    $repositories_config = $this->configFactory->get('drupaleasy_repositories.settings')
      ->get('repositories_plugins') ?? [];
    // $enabled_plugins = ....
    // Get our plugin manager.
    // $this->manager = $this->container->get('plugin.manager.drupaleasy_repositories');.
    // Loop around enabled plugin, and instantiate them.
    // foreach ($enabled_plugins as $plugin) {
    //   $my_plugin = $this->manager->createInstance($plugin['id']);
    //   $help_text .= $my_plugin->validateHelpText();
    // }
    return $help_text;
  }

}
