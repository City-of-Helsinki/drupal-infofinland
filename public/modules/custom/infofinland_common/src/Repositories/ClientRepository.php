<?php

declare(strict_types=1);

namespace Drupal\infofinland_common\Repositories;

use Drupal\simple_oauth\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

/**
 * Decorates simple_oauth ClientRepository service.
 */
final class ClientRepository implements ClientRepositoryInterface {

  public function __construct(
    private ClientRepositoryInterface $original_service
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getClientEntity($clientIdentifier): ?ClientEntityInterface {
    return $this
      ->original_service
      ->getClientEntity($clientIdentifier);
  }

  /**
   * {@inheritDoc}
   */
  public function validateClient($clientIdentifier, $clientSecret, $grantType): bool {
    // Do not validate client secret in local development environment.
    // This makes it easier to set up frontend since passwords stored
    // in ContentEntities are not validated.
    if (getenv('APP_ENV') === 'local') {
      return TRUE;
    }

    return $this
      ->original_service
      ->validateClient($clientIdentifier, $clientSecret, $grantType);
  }

}
