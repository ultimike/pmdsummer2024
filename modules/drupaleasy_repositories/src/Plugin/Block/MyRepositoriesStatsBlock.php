<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\Plugin\Block;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "my repositories" stats block.
 *
 * @Block(
 *   id = "drupaleasy_repositories_my_repositories_stats",
 *   admin_label = @Translation("My repositories stats"),
 *   category = @Translation("DrupalEasy"),
 * )
 */
final class MyRepositoriesStatsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs the plugin instance.
   *
   * @param array<mixed> $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array<mixed> $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Drupal core entity type manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Drupal core current user service.
   * @param \Drupal\Component\Datetime\TimeInterface $datetimeTime
   *   Drupal core datetime time service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountProxyInterface $currentUser,
    private readonly TimeInterface $datetimeTime,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build['content'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => [
        $this->t('Current user: @name', ['@name' => $this->currentUser->getDisplayName()]),
        $this->t('Current timestamp: @timestamp', ['@timestamp' => $this->datetimeTime->getCurrentTime()]),
        $this->t('Total number of issues in all repository nodes: @all', ['@all' => $this->calculateTotalIssues()]),
        $this->t('Total number of issues in my repository nodes: @my', ['@my' => $this->calculateTotalIssues((int) $this->currentUser->id())]),
      ],
      '#cache' => [
        'contexts' => ['timezone', 'user'],
        'tags' => ['node_list:repository', 'drupaleasy_repositories'],
        'max-age' => Cache::PERMANENT,
      ],
    ];

    return $build;
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheMaxAge(): int {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheTags(): array {
    return ['node_list:repository', 'drupaleasy_repositories'];
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheContexts(): array {
    return ['timezone', 'user'];
  }

  /**
   * Calculates the total number of issues for a user's repositories.
   *
   * @param int|null $uid
   *   An (optional) user to filter on.
   *
   * @return int
   *   The total number of issues.
   */
  protected function calculateTotalIssues(?int $uid = NULL): int {
    usleep(3000000);
    $return = 0;
    $node_storage = $this->entityTypeManager->getStorage('node');
    $query = $node_storage->getQuery();
    $query->condition('type', 'repository')
      ->condition('status', 1)
      ->condition('field_number_of_issues', 0, '>');
    if (!is_null($uid)) {
      $query->condition('uid', $uid);
    }
    $results = $query->accessCheck(FALSE)->execute();

    foreach ($results as $nid) {
      /** @var \Drupal\node\Entity\Node $node */
      $node = $node_storage->load($nid);
      if ($number_of_issues = $node->field_number_of_issues->value) {
        $return += $number_of_issues;
      }
    }

    return $return;
  }

}
