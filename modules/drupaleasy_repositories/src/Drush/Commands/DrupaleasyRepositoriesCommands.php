<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesBatch;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * A Drush commandfile.
 */
final class DrupaleasyRepositoriesCommands extends DrushCommands {

  use AutowireTrait;

  /**
   * Our command file constructor.
   *
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService $repositoriesService
   *   The custom DrupalEasy repositories service class.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal core entity type manager service.
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesBatch $batch
   *   The custom DrupalEasy batch service.
   */
  public function __construct(
    // Via https://www.drupal.org/node/3396179
    #[Autowire(service: 'drupaleasy_repositories.service')]
    protected DrupaleasyRepositoriesService $repositoriesService,
    #[Autowire(service: 'entity_type.manager')]
    protected EntityTypeManagerInterface $entityTypeManager,
    #[Autowire(service: 'drupaleasy_repositories.batch')]
    protected DrupaleasyRepositoriesBatch $batch,
  ) {
    parent::__construct();
  }

  /**
   * Update user repositories.
   *
   * This command will update all user repositories or all repositories for a
   * single user.
   *
   * @param array<string, null|int> $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   */
  #[CLI\Command(name: 'der:update-repositories', aliases: ['der:ur'])]
  #[CLI\Option(name: 'uid', description: 'The user ID of the user to update.')]
  #[CLI\Help(description: 'Update user repositories', synopsis: 'This command
  will update all user repositories or all repositoreis for a single user.')]
  #[CLI\Usage(name: 'der:update-repositories --uid=2', description: 'Update a user\'s repositories')]
  #[CLI\Usage(name: 'der:update-repositories', description: 'Update all user repositories')]
  #[CLI\Usage(name: 'der:uur', description: 'Update all user repositories')]
  public function updateRepositories(array $options = ['uid' => NULL]): void {
    if (!empty($options['uid'])) {
      $account = $this->entityTypeManager->getStorage('user')->load($options['uid']);
      if ($account) {
        if ($this->repositoriesService->updateRepositories($account)) {
          $this->logger()->notice(dt('Repositories updated.'));
        }
        else {
          $this->logger()->alert(dt('Repositories NOT updated.'));
        }
      }
      else {
        $this->logger()->alert(dt('User does not exist.'));
      }
    }
    else {
      if (!is_null($options['uid'])) {
        $this->logger()->alert(dt('You may not select the Anonymous user.'));
        return;
      }
      // Update all user repositories. ("TRUE" for using Drush.)
      $this->batch->updateAllRepositories(TRUE);
    }
  }

}
