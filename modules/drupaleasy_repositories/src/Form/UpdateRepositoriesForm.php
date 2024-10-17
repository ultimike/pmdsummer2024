<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesBatch;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a DrupalEasy Repositories form.
 */
final class UpdateRepositoriesForm extends FormBase {

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService $repositoriesService
   *   The DrupalEasy repositories custom service class.
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesBatch $repositoriesBatch
   *   The DrupalEasy repositories custom service class for batch processing.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal core entity type manager service class.
   */
  public function __construct(
    protected DrupaleasyRepositoriesService $repositoriesService,
    protected DrupaleasyRepositoriesBatch $repositoriesBatch,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('drupaleasy_repositories.service'),
      $container->get('drupaleasy_repositories.batch'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drupaleasy_repositories_update_repositories';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['uid'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#selection_settings' => [
        'include_anonymous' => FALSE,
      ],
      '#title' => $this->t('Username'),
      '#description' => $this->t('Leave blank to update all repository nodes for all users.'),
      '#required' => FALSE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Go'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if ($uid = $form_state->getValue('uid')) {
      // Load user object using entity type manager core service class.
      $user = $this->entityTypeManager->getStorage('user')->load($uid);
      // Call updateRepositories() from our custom service class.
      if ($this->repositoriesService->updateRepositories($user)) {
        $this->messenger()->addMessage($this->t('Repositories updated.'));
      }
      else {
        $this->messenger()->addError($this->t('Repositories NOT updated.'));
      }
    }
    else {
      // Update all user repositories - use magic Batch stuff.
      $this->repositoriesBatch->updateAllRepositories();
    }
  }

}
