<?php

declare(strict_types=1);

namespace Drupal\Tests\drupaleasy_repositories\Functional;

use Drupal\drupaleasy_repositories\Traits\RepositoryContentTypeTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;

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
  protected static $modules = [
    'drupaleasy_repositories',
  ];

  /**
   * The authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $authenticatedUser;

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

    $this->authenticatedUser = $this->drupalCreateUser(['access content']);

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
    $this->drupalLogin($this->authenticatedUser);
    $this->drupalGet('/admin/config/services/repositories');
    // Test to ensure that the page loads without error.
    // See https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
    $session->statusCodeEquals(403);
  }

  /**
   * Test that a yml repo can be added to a user profile.
   *
   * This tests that a yml-based repo can be added to a user's profile and that
   * a repository node is successfully created upon saving the profile.
   *
   * @test
   */
  public function testAddYmlRepo(): void {
    $this->drupalLogin($this->authenticatedUser);

    // Get a handle on the browsing session.
    $session = $this->assertSession();

    // Navigate to the user profile edit page.
    $this->drupalGet('/user/' . $this->authenticatedUser->id() . '/edit');
    $session->statusCodeEquals(200);

    // Get the full path of the .yml file.
    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = \Drupal::service('module_handler');
    $module = $module_handler->getModule('drupaleasy_repositories');
    $module_full_path = \Drupal::request()->getUri() . $module->getPath();

    // Populate the edit array for the user profile form.
    $edit = ['field_repository_url[0][uri]' => $module_full_path . '/tests/assets/batman-repo.yml'];

    // Submit the form.
    $this->submitForm($edit, 'Save');
    $session->statusCodeEquals(200);
    // Ensure the confirmation message appears.
    $session->responseContains('The changes have been saved.');

    // We can't check for the following message unless we also have the future
    // drupaleasy_notify module enabled.
    //$session->responseContains('The repo named <em class="placeholder">The Batman repository</em> has been created');
    // Find the new repository node.
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'repository');
    $results = $query->accessCheck(FALSE)->execute();
    $session->assert(count($results) === 1, 'Either 0 or more than 1 repository nodes were found.');

    $entity_type_manager = \Drupal::entityTypeManager();
    $node_storage = $entity_type_manager->getStorage('node');
    /** @var \Drupal\node\Entity\Node $node */
    $node = $node_storage->load(reset($results));

    $session->assert($node->field_machine_name->value == 'batman-repo', 'Machine name does not match.');
    $session->assert($node->field_description->value == 'This is where Batman keeps all his crime-fighting code.', 'Description does not match.');
    $session->assert($node->field_number_of_issues->value == '6', 'Number of issues does not match.');
    $session->assert($node->title->value == 'The Batman repository', 'Title name does not match.');
    $session->assert($node->field_source->value == 'yml_remote', 'Source does not match.');
  }

  /**
   * Test that a yml repo can be removed from a user profile.
   *
   * This tests that a yml-based repo can be removed from a user's profile and
   * that the corresponding repository node is deleted.
   *
   * @test
   */
  public function testRemoveYmlRepo(): void {
    $this->drupalLogin($this->authenticatedUser);

    // Get a handle on the browsing session.
    $session = $this->assertSession();

    // Navigate to the user profile edit page.
    $this->drupalGet('/user/' . $this->authenticatedUser->id() . '/edit');
    $session->statusCodeEquals(200);

    // Get the full path of the .yml file.
    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = \Drupal::service('module_handler');
    $module = $module_handler->getModule('drupaleasy_repositories');
    $module_full_path = \Drupal::request()->getUri() . $module->getPath();

    // Populate the edit array for the user profile form.
    $edit = ['field_repository_url[0][uri]' => $module_full_path . '/tests/assets/batman-repo.yml'];

    // Submit the form.
    $this->submitForm($edit, 'Save');
    $session->statusCodeEquals(200);
    // Ensure the confirmation message appears.
    $session->responseContains('The changes have been saved.');

    // Unpopulate the edit array for the user profile form.
    $edit = ['field_repository_url[0][uri]' => ''];

    // Submit the form.
    $this->submitForm($edit, 'Save');
    $session->statusCodeEquals(200);

    // We can't check for the following message unless we also have the future
    // drupaleasy_notify module enabled.
    // $session->responseContains('The repo named <em class="placeholder">The Batman repository</em> has been deleted');.
    // Find the new repository node.
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'repository');
    $results = $query->accessCheck(FALSE)->execute();
    $session->assert(count($results) === 0, 'The repository node does not appear to have been deleted.');
  }

}
