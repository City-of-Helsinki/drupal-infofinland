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
    // Todo This could be simplified, lots of duplicate code here.
    if ($event->getRequest()->get('_route') == 'entity.user.canonical') {
      $uid = $event->getRequest()->get('user')->uid->value;
      $account = User::load($uid);
      $name = $account->getAccountName();

      // Optionally, log the message to a separate file.
      $logFilePath = 'sites/default/files/logs/infofinland_gdpr.log';
      $formattedMessage = date('Y-m-d H:i:s') . ' [notice] ' . 'user ' . $name . " view page visited.\n";
      file_put_contents($logFilePath, $formattedMessage, FILE_APPEND);

      // Logs a notice when users page is visited.
      \Drupal::logger('infofinland_gdpr')->notice('user %name view page visited.', ['%name' => $name]);
    }

    if ($event->getRequest()->get('_route') == 'entity.user.edit_form') {
      $uid = $event->getRequest()->get('user')->uid->value;
      $account = User::load($uid);
      $name = $account->getAccountName();

      // Optionally, log the message to a separate file.
      $logFilePath = 'sites/default/files/logs/infofinland_gdpr.log';
      $formattedMessage = date('Y-m-d H:i:s') . ' [notice] ' . 'user ' . $name . " edit page visited.\n";
      file_put_contents($logFilePath, $formattedMessage, FILE_APPEND);

      // Logs a notice when users edit page is visited.
      \Drupal::logger('infofinland_gdpr')->notice('user %name edit page visited.', ['%name' => $name]);
    }

    if ($event->getRequest()->get('_route') == 'entity.user.collection') {

      // Optionally, log the message to a separate file.
      $logFilePath = 'sites/default/files/logs/infofinland_gdpr.log';
      $formattedMessage = date('Y-m-d H:i:s') . ' [notice] ' . "admin list page of users visited.\n";
      file_put_contents($logFilePath, $formattedMessage, FILE_APPEND);

      // Logs a notice when user list admin page is visited.
      \Drupal::logger('infofinland_gdpr')->notice('admin list page of users visited.');
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
