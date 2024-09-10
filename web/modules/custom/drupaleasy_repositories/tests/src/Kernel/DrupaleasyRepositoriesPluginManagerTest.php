<?php

declare(strict_types=1);

namespace Drupal\Tests\drupaleasy_repositories\Kernel;

use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel test for our plugin manager.
 *
 * @group drupaleasy_repositories
 */
final class DrupaleasyRepositoriesPluginManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<int, string>
   */
  protected static $modules = ['drupaleasy_repositories'];

  /**
   * Our plugin manager.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager
   */
  protected DrupaleasyRepositoriesPluginManager $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->manager = $this->container->get('plugin.manager.drupaleasy_repositories');
  }

  /**
   * Tests to ensure the YmlRemote plugin is instantiated properly.
   */
  public function testYmlRemoteInstance(): void {
    $yml_remote_instance = $this->manager->createInstance('yml_remote');
    /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase $yml_remote_instance */
    $plugin_def = $yml_remote_instance->getPluginDefinition();

    $this->assertInstanceOf('\Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase', $yml_remote_instance, 'Plugin parent class does not match.');
    $this->assertInstanceOf('\Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories\YmlRemote', $yml_remote_instance, 'Plugin type does not match.');

    $this->assertArrayHasKey('label', $plugin_def, 'Label not present in annotation.');
    $this->assertTrue($plugin_def['label'] == 'Remote .yml file', 'Label in annotation does not match.');
  }

}
