<?php

namespace Drupal\infofinland_common\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\infofinland_common\Controller\OAuth2TokenController;
use Symfony\Component\Routing\RouteCollection;

/**
 * Intercept token auth in local environment.
 */
class OAuthRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection): void {
    if ($route = $collection->get('oauth2_token.token')) {
      $route->setDefaults([
        '_controller' => OAuth2TokenController::class . '::token',
      ]);
    }
  }

}
