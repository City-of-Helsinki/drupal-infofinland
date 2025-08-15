<?php

declare(strict_types=1);

namespace Drupal\infofinland_common\EventSubscriber;

use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for CSP policy alteration.
 *
 * @package Drupal\infofinland_common\EventSubscriber
 */
class CspEventSubscriber implements EventSubscriberInterface {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];

    if (class_exists(CspEvents::class)) {
      $events[CspEvents::POLICY_ALTER] = 'policyAlter';
    }

    return $events;
  }

  /**
   * Alter CSP policies.
   *
   * @param \Drupal\csp\Event\PolicyAlterEvent $event
   *   The policy alter event.
   */
  public function policyAlter(PolicyAlterEvent $event): void {
    // Add frontend domain to allow iframe embedding.
    $frontend_url = $this->configFactory->get('next.next_site.infofinland_ui')->get('base_url');
    if ($frontend_url) {
      $policy = $event->getPolicy();
      if ($policy->hasDirective('frame-src')) {
        $policy->appendDirective('frame-src', $frontend_url);
      }
    }
  }

}
