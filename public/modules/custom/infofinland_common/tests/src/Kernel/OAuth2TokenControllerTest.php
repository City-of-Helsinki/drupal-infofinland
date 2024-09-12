<?php

declare(strict_types=1);

namespace Drupal\Tests\infofinland_common\Kernel;

use Drupal\consumers\Entity\Consumer;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests OAuth2TokenController.
 *
 * @group helfi_annif
 */
class OAuth2TokenControllerTest extends KernelTestBase {

  use ApiTestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'file',
    'serialization',
    'consumers',
    'image',
    'simple_oauth',
    'infofinland_common',
  ];

  /**
   * {@inheritDoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('consumer');
    $this->installEntitySchema('oauth2_token');

    $user = $this->createUser();
    $this->createRole([], AccountInterface::AUTHENTICATED_ROLE);

    $config = $this->config('simple_oauth.settings');
    $config->set('public_key', '../conf/local-keys/public.key');
    $config->set('private_key', '../conf/local-keys/private.key');
    $config->save();

    $consumer = Consumer::create([
      'label' => 'Test Consumer',
      'client_id' => 'test_client_id',
      'user_id' => $user->id(),
      'is_default' => TRUE,
    ]);
    $consumer->setOwner($user);
    $consumer->save();
  }

  /**
   * Tests OAuth2 token.
   */
  public function testOAuth2Token(): void {
    // league/oauth2-server wants 600 file permissions for key.
    chmod('../conf/local-keys/private.key', 0600);

    putenv('APP_ENV=local');

    $uri = Url::fromRoute('oauth2_token.token')->toString();
    $document = NULL;
    $parameters = [
      'client_id' => 'non-existent',
      'client_secret' => '123',
      'grant_type' => 'client_credentials',
    ];

    // OAuth token endpoint works with ANY credentials if APP_ENV=local.
    $request = Request::create($uri, Request::METHOD_POST, $parameters, [], [], [], $document);
    $response = $this->processRequest($request);

    $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

    putenv('APP_ENV=not-local');

    // OAuth token endpoint works with ANY credentials if APP_ENV=local.
    $request = Request::create($uri, Request::METHOD_POST, $parameters, [], [], [], $document);
    $response = $this->processRequest($request);

    $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
  }

}
