<?php

namespace Drupal\infofinland_common\Controller;

use Drupal\simple_oauth\Controller\Oauth2Token;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller for oauth2 tokens.
 */
class OAuth2TokenController extends Oauth2Token {

  /**
   * {@inheritdoc}
   */
  public function token(ServerRequestInterface $request) {
    // Accept any client id in local environment.
    if (getenv('APP_ENV') === 'local') {
      $body = $request->getParsedBody();

      if (isset($body['client_id'])) {
        $defaultClients = $this
          ->entityTypeManager()
          ->getStorage('consumer')
          ->loadByProperties([
            'is_default' => 1,
          ]);

        if (!empty($defaultClients)) {
          $defaultClient = reset($defaultClients);

          // Override request body.
          $request = $request->withParsedBody(array_merge($body, [
            'client_id' => $defaultClient->getClientId(),
          ]));
        }
      }
    }

    return parent::token($request);
  }

}
