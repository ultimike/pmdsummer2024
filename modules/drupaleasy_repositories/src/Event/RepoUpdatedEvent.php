<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\node\NodeInterface;

/**
 * Event that is fired when a repository is created/updated/deleted.
 */
class RepoUpdatedEvent extends Event {

  /**
   * Constructor for the event.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node begin added, updated, or deleted.
   * @param string $action
   *   The action happening on the node.
   */
  public function __construct(
    public NodeInterface $node,
    public string $action,
  ) {

  }

}
