<?php

declare(strict_types=1);

namespace Drupal\Tests\drupaleasy_repositories\Functional;

use Drupal\drupaleasy_repositories\Traits\RepositoryContentTypeTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Test description.
 *
 * @group drupaleasy_repositories
 */
final class AddYmlRepoTest extends BrowserTestBase {
  use RepositoryContentTypeTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['drupaleasy_repositories'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Configure the yml_remote plugin to be enabled.
    $config = $this->config('drupaleasy_repositories.settings');
    $config->set('repositories_plugins', ['yml_remote' => 'yml_remote']);
    $config->save();

    // Create and login as a Drupal user with permission to access the
    // DrupalEasy Repositories Settings page. This is UID=2 because UID=1 is
    // created by
    // web/core/lib/Drupal/Core/Test/FunctionalTestSetupTrait::initUserSession().
    // This root user can be accessed via $this->rootUser.
    $admin_user = $this->drupalCreateUser(['configure drupaleasy repositories']);
    $this->drupalLogin($admin_user);

    // Add the repository content type.
    // $this->createRepositoryContentType();
    // Add the Repository URL field to the user entity.
    // $this->createUserRepositoryUrlField();
    // Ensure that the Repository URL field is visible in the existing user
    // entity form mode. This is necessary when importing config/install.
    /** @var \Drupal\Core\Entity\EntityDisplayRepository $entity_display_repository */
    $entity_display_repository = \Drupal::service('entity_display.repository');
    $entity_display_repository->getFormDisplay('user', 'user', 'default')
      ->setComponent('field_repository_url', ['type' => 'link_default'])
      ->save();
  }

  /**
   * Test that the settings page can be reached and works as expected.
   *
   * This tests that an admin user can access the settings page, select a
   * plugin to enable, and submit the page successfully.
   *
   * @return void
   *   Returns nothing.
   *
   * @test
   */
  public function testSettingsPage(): void {
    // Get a handle on the browsing session.
    $session = $this->assertSession();

    // Navigate to the settings page.
    $this->drupalGet('/admin/config/services/repositories');

    // Confirm that page loads without error.
    $session->statusCodeEquals(200);

    // Set the value of form elements to be submitted.
    $edit = ['edit-repositories-plugins-yml-remote' => 'yml_remote'];

    // Submit the form.
    $this->submitForm($edit, 'Save configuration');
    $session->statusCodeEquals(200);

    // Ensure the confirmation message appears.
    $session->responseContains('The configuration options have been saved.');

    $session->checkboxChecked('edit-repositories-plugins-yml-remote');
    $session->checkboxNotChecked('edit-repositories-plugins-github');
  }

  /**
   * Test that the settings page cannot be reached without permission.
   *
   * @return void
   *   Returns nothing.
   *
   * @test
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testUnprivilegedSettingsPage(): void {
    $session = $this->assertSession();
    $authenticated_user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($authenticated_user);
    $this->drupalGet('/admin/config/services/repositories');
    // Test to ensure that the page loads without error.
    // See https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
    $session->statusCodeEquals(403);
  }

  /**
   * Test callback.
   *
   * @test
   */
  public function testSomething(): void {
    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);
    $this->drupalGet('/admin/config/system/site-information');
    $this->assertSession()->elementExists('xpath', '//h1[text() = "Basic site settings"]');
  }

}
