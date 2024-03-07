<?php

namespace Drupal\infofinland_common\Repositories;

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
  public function getClientEntity($clientIdentifier, $grantType = NULL, $clientSecret = NULL, $mustValidateSecret = TRUE) {
    // Do not validate client secret in local development environment.
    // This makes it easier to set up frontend since passwords stored
    // in ContentEntities are not validated.
    if (getenv('APP_ENV') === 'local') {
      $mustValidateSecret = FALSE;
    }

    return $this
      ->original_service
      ->getClientEntity($clientIdentifier, $grantType, $clientSecret, $mustValidateSecret);
  }

}
