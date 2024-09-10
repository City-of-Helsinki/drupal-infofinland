<?php

/**
 * @file
 * Contains site specific overrides.
 */

if (getenv('ELASTIC_URL')) {
  $config['elasticsearch_connector.cluster.infofinland']['url'] = getenv('ELASTIC_URL');

  if (getenv('ELASTIC_USER') && getenv('ELASTIC_PASSWORD')) {
    $config['elasticsearch_connector.cluster.infofinland']['options']['use_authentication'] = '1';
    $config['elasticsearch_connector.cluster.infofinland']['options']['authentication_type'] = 'Basic';
    $config['elasticsearch_connector.cluster.infofinland']['options']['username'] = getenv('ELASTIC_USER');
    $config['elasticsearch_connector.cluster.infofinland']['options']['password'] = getenv('ELASTIC_PASSWORD');
  }
}

if (getenv('INFOFINLAND_UI_URL')) {
  $config['next.next_site.infofinland_ui']['base_url'] = getenv('INFOFINLAND_UI_URL');
  $config['next.next_site.infofinland_ui']['preview_url'] = getenv('INFOFINLAND_UI_PREVIEW_URL');
  $config['next.next_site.infofinland_ui']['preview_secret'] = getenv('DRUPAL_PREVIEW_SECRET');
}

$config['simple_oauth.settings']['public_key'] = '/var/www/html/keys/public.key';
$config['simple_oauth.settings']['private_key'] = '/var/www/html/keys/private.key';

// Hardcoded simple_oauth keys for local development.
if (getenv('APP_ENV') === 'local') {
  $config['simple_oauth.settings']['public_key'] = '/app/conf/local-keys/public.key';
  $config['simple_oauth.settings']['private_key'] = '/app/conf/local-keys/private.key';
}

$additionalEnvVars = [
  'PROJECT_NAME',
  'REDIS_HOST',
  'REDIS_PORT',
  'SENTRY_DSN',
  'SENTRY_ENVIRONMENT',
  // Project specific variables.
  'DRUPAL_PREVIEW_SECRET',
  'ELASTIC_URL',
  'ELASTIC_USER',
  'ELASTIC_PASSWORD',
  'INFOFINLAND_UI_PREVIEW_URL',
  'INFOFINLAND_UI_URL'
];
foreach ($additionalEnvVars as $var) {
  $preflight_checks['environmentVariables'][] = $var;
}

/**
 * The UI for environment indicator is broken, so we have to do this instead.
 * See more info, https://www.drupal.org/project/environment_indicator/issues/2224983
 */
if (isset($_SERVER['HTTP_HOST'])) {
  switch ($_SERVER['HTTP_HOST']) {
    case 'edit.infofinland.fi':
      $config['environment_indicator.indicator']['bg_color'] = '#ffffff';
      $config['environment_indicator.indicator']['fg_color'] = '#000000';
      $config['environment_indicator.indicator']['name'] = 'Production';
      break;
    case 'edit-infofinland.stage.hel.ninja':
      $config['environment_indicator.indicator']['bg_color'] = '#3584e4';
      $config['environment_indicator.indicator']['fg_color'] = '#000000';
      $config['environment_indicator.indicator']['name'] = 'Stage';
      break;
    case 'edit-infofinland.test.hel.ninja':
      $config['environment_indicator.indicator']['bg_color'] = '#ed333b';
      $config['environment_indicator.indicator']['fg_color'] = '#000000';
      $config['environment_indicator.indicator']['name'] = 'Test';
      break;
    case 'edit-infofinland.dev.hel.ninja':
      $config['environment_indicator.indicator']['bg_color'] = '#33d17a';
      $config['environment_indicator.indicator']['fg_color'] = '#000000';
      $config['environment_indicator.indicator']['name'] = 'Development';
      break;
  }
}
