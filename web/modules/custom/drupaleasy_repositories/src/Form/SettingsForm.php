<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure DrupalEasy Repositories settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface|null $typedConfigManager
   *   The typed config manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $config_factory,
    protected DrupaleasyRepositoriesPluginManager $drupaleasy_repositories_manager,
    protected $typedConfigManager = NULL,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.drupaleasy_repositories'),
      $container->get('config.typed')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drupaleasy_repositories_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['drupaleasy_repositories.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Get the current config.
    $repositories_config = $this->config('drupaleasy_repositories.settings')
      ->get('repositories_plugins') ?? [];

    $form['repositories_plugins'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Repository plugins'),
      '#options' => [
        'yml_remote' => $this->t('Yml remote'),
        'github' => $this->t('GitHub'),
      ],
      '#default_value' => $repositories_config,
    ];

    $form['#config-target'] = 'drupaleasy_repositories.settings:repositories_plugins';
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('drupaleasy_repositories.settings')
      ->set('repositories_plugins', $form_state->getValue('repositories_plugins'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
