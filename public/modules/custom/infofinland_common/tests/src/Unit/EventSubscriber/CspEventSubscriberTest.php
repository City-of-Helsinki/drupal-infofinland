<?php

declare(strict_types=1);

namespace Drupal\Tests\infofinland_common\Unit;

use DG\BypassFinals;
use Drupal\csp\Csp;
use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Drupal\infofinland_common\EventSubscriber\CspEventSubscriber;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Unit tests for CspEventSubscriber.
 *
 * @group infofinland_common
 * @coversDefaultClass \Drupal\infofinland_common\EventSubscriber\CspEventSubscriber
 */
class CspEventSubscriberTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The CspEventSubscriber.
   *
   * @var \Drupal\infofinland_common\EventSubscriber\CspEventSubscriber
   */
  protected CspEventSubscriber $cspEventSubscriber;

  /**
   * The Event.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $event;

  /**
   * The Csp policy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $policy;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    BypassFinals::enable();

    $nextConfig = $this->prophesize(ImmutableConfig::class);
    $nextConfig->get('base_url')->willReturn('https://www.infofinland.fi');
    $config = $this->prophesize(ConfigFactoryInterface::class);
    $config->get('next.next_site.infofinland_ui')->willReturn($nextConfig->reveal());

    $this->event = $this->prophesize(PolicyAlterEvent::class);
    $this->policy = $this->prophesize(Csp::class);
    $this->event->getPolicy()->willReturn($this->policy->reveal());

    $this->cspEventSubscriber = new CspEventSubscriber($config->reveal());
  }

  /**
   * Tests the getSubscribedEvents method.
   *
   * @covers ::getSubscribedEvents
   */
  public function testGetSubscribedEvents(): void {
    $this->assertEquals([CspEvents::POLICY_ALTER => 'policyAlter'], CspEventSubscriber::getSubscribedEvents());
  }

  /**
   * Tests policy alteration.
   *
   * @covers ::policyAlter
   */
  public function testPolicyAlterWithLocalEnvironment(): void {
    $this->policy->hasDirective('frame-src')->willReturn(TRUE);
    $this->policy->appendDirective('frame-src', 'https://www.infofinland.fi')->shouldBeCalled();

    $this->cspEventSubscriber->policyAlter($this->event->reveal());
  }

}
