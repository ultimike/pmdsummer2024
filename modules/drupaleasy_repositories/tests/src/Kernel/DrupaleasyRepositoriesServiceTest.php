<?php

declare(strict_types=1);

namespace Drupal\Tests\drupaleasy_repositories\Kernel;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService;
use Drupal\drupaleasy_repositories\Traits\RepositoryContentTypeTrait;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Test description.
 *
 * @group drupaleasy_repositories
 */
final class DrupaleasyRepositoriesServiceTest extends KernelTestBase {

  use RepositoryContentTypeTrait;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The admin user property.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $adminUser;

  /**
   * The drupaleasy_repositories service.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService
   */
  protected DrupaleasyRepositoriesService $drupaleasyRepositoriesService;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'drupaleasy_repositories',
    'node',
    'field',
    'user',
    'system',
    // For text_long field types.
    'text',
    // For link field types.
    'link',
    'key',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Mock necessary services here.
    $this->drupaleasyRepositoriesService = $this->container->get('drupaleasy_repositories.service');
    $this->createRepositoryContentType();
    $this->moduleHandler = $this->container->get('module_handler');

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    $aquaman_repo = $this->getTestRepo('aquaman');
    $repo = reset($aquaman_repo);

    $this->adminUser = User::create(['name' => $this->randomString()]);
    $this->adminUser->save();

    $node = Node::create([
      'type' => 'repository',
      'title' => $repo['label'],
      'field_machine_name' => array_key_first($aquaman_repo),
      'field_url' => $repo['url'],
      'field_hash' => 'c8f3fd6cd928e6a1e62239a7fea461e7',
      'field_number_of_issues' => $repo['num_open_issues'],
      'field_source' => $repo['source'],
      'field_description' => $repo['description'],
      'uid' => $this->adminUser->id(),
    ]);
    $node->save();

    // Enable the .yml repository plugin.
    $config = $this->config('drupaleasy_repositories.settings');
    $config->set('repositories_plugins', ['yml_remote' => 'yml_remote']);
    $config->save();

  }

  /**
   * Data provider for testIsUnique().
   *
   * @return array<int, array<int, bool|array<mixed>>>
   *   Test data and expected results.
   */
  public static function providerTestIsUnique(): array {
    $aquaman = [
      'aquaman-repository' => [
        'label' => 'The Aquaman repository',
        'description' => 'This is where Aquaman keeps all his crime-fighting code.',
        'num_open_issues' => 6,
        'source' => 'yml_remote',
        'url' => 'http://example.com/aquaman-repo.yml',
      ],
    ];

    $superman = [
      'superman-repository' => [
        'label' => 'The Superman repository',
        'description' => 'This is where Superman keeps all his fortress of solitude code.',
        'num_open_issues' => 0,
        'source' => 'yml_remote',
        'url' => 'http://example.com/superman-repo.yml',
      ],
    ];

    return [
      [TRUE, $aquaman, 1],
      [FALSE, $aquaman, 999],
      [TRUE, $superman, 1],
      [TRUE, $superman, 999],
    ];
  }

  /**
   * Test the ability for the service to ensure repositories are unique.
   *
   * @param bool $expected
   *   The expected value.
   * @param array<mixed> $repo
   *   The repository to be tested.
   * @param int $uid
   *   The User ID to test.
   *
   * @covers \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService::isUnique
   * @dataProvider providerTestIsUnique
   * @test
   */
  public function testIsUnique(bool $expected, array $repo, int $uid): void {
    // Use reflection to make isUnique() "public".
    $reflection_is_unique = new \ReflectionMethod($this->drupaleasyRepositoriesService, 'isUnique');
    // Not necessary for PHP 8.1 or later.
    $reflection_is_unique->setAccessible(TRUE);
    $actual = $reflection_is_unique->invokeArgs(
      $this->drupaleasyRepositoriesService,
      [$repo, $uid]
    );

    $repo = reset($repo);
    $this->assertEquals($expected, $actual, "The {$repo['label']}'s uniqueness does not match the expected value.");
  }

  /**
   * Data provider for testValidateRepositoryUrls().
   *
   * @return array<mixed>
   *   Test data and expected results.
   */
  public static function providerValidateRepositoryUrls(): array {
    // This is run before setup() and other things so $this->container
    // isn't available here!
    return [
      ['', [['uri' => '/tests/assets/batman-repo.yml']]],
      ['is not valid', [['uri' => '/tests/assets/batman-repo.ym']]],
    ];
  }

  /**
   * Test the ability for the service to ensure repositories are valid.
   *
   * @param string $expected
   *   The expected value.
   * @param array<mixed> $urls
   *   The repository to be tested.
   *
   * @covers \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService::validateRepositoryUrls
   * @dataProvider providerValidateRepositoryUrls
   * @test
   */
  public function testValidateRepositoryUrls(string $expected, array $urls): void {
    // Get the full path to the test .yml file.
    /** @var \Drupal\Core\Extension\Extension $module */
    $module = $this->moduleHandler->getModule('drupaleasy_repositories');
    $module_full_path = \Drupal::request()->getUri() . $module->getPath();

    foreach ($urls as $key => $url) {
      if (isset($url['uri'])) {
        $urls[$key]['uri'] = $module_full_path . $url['uri'];
      }
    }

    $actual = $this->drupaleasyRepositoriesService->validateRepositoryUrls($urls, 999);
    if ($expected) {
      $this->assertTrue((bool) mb_stristr($actual, $expected), "The URLs' validation does not match the expected value. Actual: {$actual}, Expected: {$expected}");
    }
    else {
      $this->assertEquals($expected, $actual, "The URLs' validation does not match the expected value. Actual: {$actual}, Expected: {$expected}");
    }
  }

}
