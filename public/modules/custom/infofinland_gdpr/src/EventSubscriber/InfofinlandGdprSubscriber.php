<?php

namespace Drupal\Infofinland_gdpr\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Drupal\user\Entity\User;

/**
 * Class for logging when sensitive user information is being viewed or edited.
 */
class InfofinlandGdprSubscriber implements EventSubscriberInterface {

  public function checkForUserPage(RequestEvent $event) {
    if ($event->getRequest()->get('_route') == 'entity.user.canonical') {
      $uid = $event->getRequest()->get('user')->uid->value;
      $account = User::load($uid);
      $name = $account->getAccountName();

      // Logs a notice when users page is visited.
      \Drupal::logger('infofinland_gdpr')->notice('user %name visited.', ['%name' => $name]);
    }

    if ($event->getRequest()->get('_route') == 'entity.user.edit_form') {
      $uid = $event->getRequest()->get('user')->uid->value;
      $account = User::load($uid);
      $name = $account->getAccountName();

      // Logs a notice when users edit page is visited.
      \Drupal::logger('infofinland_gdpr')->notice('user %name edit visited.', ['%name' => $name]);
    }

    if ($event->getRequest()->get('_route') == 'entity.user.collection') {
      // Logs a notice when users admin page is visited.
      \Drupal::logger('infofinland_gdpr')->notice('Users admin page visited');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkForUserPage'];

    return $events;
  }

}
