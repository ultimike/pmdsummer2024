<?php

declare(strict_types=1);

namespace Drupal\Tests\drupaleasy_repositories\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager;

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
  protected static $modules = [
    'drupaleasy_repositories',
    'key',
  ];

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

    $this->assertArrayHasKey('url_help_text', $plugin_def, 'URL help text not present in annotation.');
    $this->assertTrue($plugin_def['url_help_text'] == 'https://anything.anything/anything/anything.yml (or http or yaml)', 'URL help text in annotation does not match.');
  }

  /**
   * Tests to ensure the GitHub plugin is instantiated properly.
   */
  public function testGithubInstance(): void {
    $github_instance = $this->manager->createInstance('github');
    /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase $github_instance */
    $plugin_def = $github_instance->getPluginDefinition();

    $this->assertInstanceOf('\Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase', $github_instance, 'Plugin parent class does not match.');
    $this->assertInstanceOf('\Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories\Github', $github_instance, 'Plugin type does not match.');

    $this->assertArrayHasKey('label', $plugin_def, 'Label not present in annotation.');
    $this->assertTrue($plugin_def['label'] == 'GitHub', 'Label in annotation does not match.');

    $this->assertArrayHasKey('url_help_text', $plugin_def, 'URL help text not present in annotation.');
    $this->assertTrue($plugin_def['url_help_text'] == 'https://github.com/vendor/name', 'URL help text in annotation does not match.');
  }

}
