<?php

declare(strict_types=1);

namespace Drupal\Tests\drupaleasy_repositories\Unit;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories\YmlRemote;
use Drupal\key\KeyInterface;
use Drupal\key\KeyRepositoryInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test description.
 *
 * @group drupaleasy_repositories
 */
final class YmlRemoteTest extends UnitTestCase {

  /**
   * The Yml Remote plugin.
   *
   * @var \Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories\YmlRemote
   */
  protected YmlRemote $ymlRemote;

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->messenger = $this->createMock(MessengerInterface::class);
    $this->keyRepository = $this->createMock(KeyRepositoryInterface::class);

    // Sample code that could be used in a Github plugin unit test for faking
    // getting credentials from the Key module.
    // $this->key = $this->createMock(KeyInterface::class);
    // $this->keyRepository->expects($this->any())
    //   ->method('getKey')
    //   ->will($this->returnValue($this->key));
    // $this->key->expects($this->any())
    //   ->method('getKeyValues')
    //   ->will($this->returnValue(['username' => 'blah', 'personal_access_token' => 'asdfasdfads']));
    $this->ymlRemote = new YmlRemote([], 'yml_remote', [], $this->messenger, $this->keyRepository);
  }

  /**
   * Test that the help text retuns as expected.
   *
   * @covers YmlRemote::validateHelpText
   * @test
   */
  // Moved to DrupalEasyRepositoriesPluginManagerText when changed to annotation.
  // public function testValidateHelpText(): void {
  //   self::assertEquals('https://anything.anything/anything/anything.yml (or http or yaml)', $this->ymlRemote->validateHelpText(), 'Help text does not match.');
  // }.

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
        TRUE,
      ],
      [
        'https://www.mysite.com/anything.yml',
        TRUE,
      ],
      [
        'https://www.mysite.com/anything.yaml',
        TRUE,
      ],
      [
        '/var/www/html/anything.yaml',
        FALSE,
      ],
      [
        'https://www.mysite.com/some%20directory/anything.yml',
        TRUE,
      ],
      [
        'https://www.my-site.com/some%20directory/anything.yaml',
        TRUE,
      ],
      [
        'https://localhost/some%20directory/anything.yaml',
        TRUE,
      ],
      [
        'https://dev.www.mysite.com/anything.yml',
        TRUE,
      ],
      [
        'http://3000.com/my-repository.yml',
        TRUE,
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
   * @covers YmlRemote::validate
   * @test
   */
  public function testValidate(string $test_string, bool $expected): void {
    self::assertEquals($expected, $this->ymlRemote->validate($test_string), "Validation of '{$test_string}' does not return '{$expected}'.");
    // This is just an example, it is not testing anything at all.
    //$this->keyRepository->getKey('blah')->getKeyValues();
  }

  /**
   * Test that a yml repository can be read properly.
   *
   * @covers YmlRemote::getRepo
   * @test
   */
  public function testGetRepo(): void {
    $file_path = __DIR__ . '/../../assets/batman-repo.yml';
    $repo = $this->ymlRemote->getRepo($file_path);
    $machine_name = array_key_first($repo);
    self::assertEquals('batman-repo', $machine_name, "The expected machine name does not match what was provided: '{$machine_name}'.");
    $repo = reset($repo);
    self::assertEquals('The Batman repository', $repo['label'], "The expected label does not match what was provided: '{$repo['label']}'.");
    self::assertEquals('This is where Batman keeps all his crime-fighting code.', $repo['description'], "The expected description does not match what was provided: '{$repo['description']}'.");
    self::assertEquals('yml_remote', $repo['source'], "The expected source does not match what was provided: '{$repo['source']}'.");
    self::assertEquals('6', $repo['num_open_issues'], "The expected number of open issues does not match what was provided: '{$repo['num_open_issues']}'.");
    self::assertEquals($file_path, $repo['url'], "The expected URL does not match what was provided: '{$repo['url']}'.");
  }

}
