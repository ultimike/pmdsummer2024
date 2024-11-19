<?php

declare(strict_types=1);

namespace Drupal\Tests\drupaleasy_repositories\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests custom block cache values.
 *
 * @group drupaleasy_repositories
 */
final class CustomBlockCacheValuesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'drupaleasy_repositories',
    'block',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock(
      'drupaleasy_repositories_my_repositories_stats',
      [
        'region' => 'content',
        'id' => 'drupaleasy_repositories_my_repositories_stats',
      ]);
  }

  /**
   * Test to ensure custom cache values are present on the page response.
   *
   * @test
   */
  public function testCustomCacheValues(): void {
    $this->drupalGet('');

    $this->assertSession()->responseHeaderContains('cache-control', 'must-revalidate, no-cache, private');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Max-Age', '-1');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Contexts', 'user');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Contexts', 'timezone');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'drupaleasy_repositories');
  }

}
