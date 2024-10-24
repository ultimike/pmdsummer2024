<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesBatch;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService;
use Drupal\queue_ui\QueueUIBatchInterface;
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
   * @param \Drupal\queue_ui\QueueUIBatchInterface $queueUiBatch
   *   The Drupal Queue UI module batch service.
   */
  public function __construct(
    protected DrupaleasyRepositoriesService $repositoriesService,
    protected DrupaleasyRepositoriesBatch $repositoriesBatch,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected QueueUIBatchInterface $queueUiBatch,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('drupaleasy_repositories.service'),
      $container->get('drupaleasy_repositories.batch'),
      $container->get('entity_type.manager'),
      $container->get('queue_ui.batch')
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
      //$this->repositoriesBatch->updateAllRepositories();
      // Update all user repositories - use queue and Queue UI module.
      // No custom Batch API stuff!
      $this->repositoriesService->createQueueItems();
      // $this->messenger()->addMessage($this->t('Queue items have been created, please go to <a href=":url">Queue Manager</a> to process them.', [
      //   ':url' => '/admin/config/system/queue-ui',
      // ]));
      // Call Queue UI batch manager directly to run all queue items as a batch.
      $this->queueUiBatch->batch(['drupaleasy_repositories_repository_node_updater']);
    }
  }

}
