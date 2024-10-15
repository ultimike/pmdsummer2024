<?php

declare(strict_types=1);

namespace Drupal\Tests\drupaleasy_repositories\Unit;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories\Github;
use Drupal\key\KeyInterface;
use Drupal\key\KeyRepositoryInterface;
use Drupal\Tests\UnitTestCase;
use Github\Api\Repo;
use Github\Client;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test description.
 *
 * @group drupaleasy_repositories
 */
final class GithubTest extends UnitTestCase {

  /**
   * The Github plugin.
   *
   * @var \Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories\Github
   */
  protected Github $github;

  /**
   * Mocked Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected MessengerInterface|MockObject $messenger;

  /**
   * Mocked Key repository service.
   *
   * @var \Drupal\key\KeyRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected KeyRepositoryInterface|MockObject $keyRepository;

  /**
   * Mocked Key entity.
   *
   * @var \Drupal\key\KeyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected KeyInterface|MockObject $key;

  /**
   * Mocked Github client.
   *
   * @var \Github\Client|\PHPUnit\Framework\MockObject\MockObject
   */
  protected Client|MockObject $client;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the main dependencies for the Github class.
    $this->messenger = $this->createMock(MessengerInterface::class);
    $this->keyRepository = $this->createMock(KeyRepositoryInterface::class);

    // Mock the Key class and associated methods to fake getting credentials.
    // This is necessary because the setAuthentication() calls these methods.
    // The credentials do not matter because the GitHub API isn't actually
    // ever called.
    $this->key = $this->createMock(KeyInterface::class);
    $this->keyRepository->expects($this->any())
      ->method('getKey')
      ->will($this->returnValue($this->key));
    $this->key->expects($this->any())
      ->method('getKeyValues')
      ->will($this->returnValue(['username' => 'blah', 'personal_access_token' => 'asdfasdfads']));

    // Mock the Github client and repo class and relevant methods.
    $repo_test_array = [
      'full_name' => 'ddev/ddev',
      'name' => 'ddev',
      'description' => 'This is the ddev repository.',
      'open_issues_count' => 6,
      'html_url' => 'https://github.com/ddev/ddev',
    ];

    $repo = $this->createMock(Repo::class);
    $repo->expects($this->any())
      ->method('show')
      ->will($this->returnValue($repo_test_array));

    $this->client = $this->createMock(Client::class);
    $this->client->expects($this->any())
      ->method('api')
      ->will($this->returnValue($repo));

    // Instantiate the Github plugin using all the fakery.
    $this->github = new Github([], 'github', [], $this->messenger, $this->keyRepository);
  }

  /**
   * Data provider for testValidate().
   *
   * @return array<int, array<int, string|bool>>
   *   The values to test and their expected result.
   */
  public function validateProvider(): array {
    return [
      [
        'A test string',
        FALSE,
      ],
      [
        'http://www.mysite.com/anything.yml',
        FALSE,
      ],
      [
        'https://github.com/vendor/name',
        TRUE,
      ],
      [
        'https://www.github.com/vendor/name',
        FALSE,
      ],
      [
        'https://github.com/vendor',
        FALSE,
      ],
    ];
  }

  /**
   * Test that the URL validator works.
   *
   * @param string $test_string
   *   The string to test.
   * @param bool $expected
   *   The expected result.
   *
   * @dataProvider validateProvider
   * @covers Github::validate
   * @test
   */
  public function testValidate(string $test_string, bool $expected): void {
    self::assertEquals($expected, $this->github->validate($test_string), "Validation of '{$test_string}' does not return '{$expected}'.");
    // This is just an example, it is not testing anything at all.
    $this->keyRepository->getKey('blah')->getKeyValues();
  }

  /**
   * Test that a github repository data processing.
   *
   * This tests that the Github plugin can properly process data from the Github
   * API (assuming good data is returned.)
   *
   * @covers Github::getRepo
   * @test
   */
  public function testGetRepo(): void {
    // Call the getRepo method - the first parameter doesn't matter because
    // we're not actually calling the API. The second parameter **must** be the
    // mocked client we created above.
    $repo = $this->github->getRepo('https://github.com/vendor/name', $this->client);
    $machine_name = array_key_first($repo);
    self::assertEquals('ddev/ddev', $machine_name, "The expected machine name does not match what was provided: '{$machine_name}'.");
    $repo = reset($repo);
    self::assertEquals('ddev', $repo['label'], "The expected label does not match what was provided: '{$repo['label']}'.");
    self::assertEquals('This is the ddev repository.', $repo['description'], "The expected description does not match what was provided: '{$repo['description']}'.");
    self::assertEquals('github', $repo['source'], "The expected source does not match what was provided: '{$repo['source']}'.");
    self::assertEquals('6', $repo['num_open_issues'], "The expected number of open issues does not match what was provided: '{$repo['num_open_issues']}'.");
    self::assertEquals('https://github.com/ddev/ddev', $repo['url'], "The expected URL does not match what was provided: '{$repo['url']}'.");
  }

}
