<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_notify\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\drupaleasy_repositories\Event\RepoUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for repository update events.
 */
final class DrupaleasyNotifySubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * Constructs a DrupaleasyNotifySubscriber object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The Drupal core messenger service.
   */
  public function __construct(
    private readonly MessengerInterface $messenger,
  ) {}

  /**
   * Repository updated event handler.
   *
   * @param \Drupal\drupaleasy_repositories\Event\RepoUpdatedEvent $event
   *   The DrupalEasy repository updated event object.
   */
  public function onRepoUpdated(RepoUpdatedEvent $event): void {
    $this->messenger->addStatus($this->t('The repo named %repo_name has been @action (@repo_url). The repo node is owned by @author_name (@author_id).', [
      '%repo_name' => $event->node->getTitle(),
      '@action' => $event->action,
      '@repo_url' => $event->node->toLink()->getUrl()->toString(),
      '@author_name' => $event->node->uid->entity->name->value,
      '@author_id' => $event->node->uid->target_id,
      ])
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      RepoUpdatedEvent::class => ['onRepoUpdated'],
    ];
  }

}
