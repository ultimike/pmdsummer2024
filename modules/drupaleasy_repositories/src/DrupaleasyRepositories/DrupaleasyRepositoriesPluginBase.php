<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\DrupaleasyRepositories;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\key\KeyRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for drupaleasy_repositories plugins.
 */
abstract class DrupaleasyRepositoriesPluginBase extends PluginBase implements DrupaleasyRepositoriesInterface, ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('messenger'),
      $container->get('key.repository')
    );
  }

  /**
   * Constructs a DrupaleasyRepositoriesPluginBase object.
   *
   * @param array<mixed> $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The Drupal core messenger service.
   * @param \Drupal\key\KeyRepositoryInterface $keyRepository
   *   The Drupal Key module repository service.
   */
  final public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected MessengerInterface $messenger,
    protected KeyRepositoryInterface $keyRepository,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateHelpText(): string {
    return $this->getPluginDefinition()['url_help_text']->__toString();
  }

  /**
   * {@inheritdoc}
   */
  public function validate(string $uri): bool {
    return FALSE;
  }

  /**
   * Build an array of a single repository's metadata.
   *
   * @param string $machine_name
   *   The machine name.
   * @param string $label
   *   The friendly name of the repository.
   * @param string|null $description
   *   The repository description.
   * @param int $num_open_issues
   *   The number of open issues for the repository.
   * @param string $url
   *   The url for the repository as entered by the Drupal user.
   *
   * @return array<string, array<string, string|int>>
   *   The repository metadata in a common format.
   */
  protected function mapToCommonFormat(string $machine_name, string $label, string|null $description, int $num_open_issues, string $url): array {
    $repo_info[$machine_name] = [
      'label' => $label,
      'description' => $description,
      'num_open_issues' => $num_open_issues,
      'source' => $this->getPluginId(),
      'url' => $url,
    ];
    return $repo_info;
  }

}
