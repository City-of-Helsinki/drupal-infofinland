<?php

/**
 * @file
 * Contains site specific overrides.
 */

$settings['hash_salt'] = getenv('DRUPAL_HASH_SALT') ?: 'CHANGE-ME-IN-ENVIRONMENT-SETTINGS';

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

// Automatically generated include for settings managed by ddev.
$ddev_settings = dirname(__FILE__) . '/settings.ddev.php';
if (getenv('IS_DDEV_PROJECT') == 'true' && is_readable($ddev_settings)) {
  require_once $ddev_settings; // TODO Check this if something stops working, `require`.
}

// Environment indicator hack, see the file for more info
$env_indicator_settings = dirname(__FILE__) . '/env.indicator.settings.php';
if (file_exists($env_indicator_settings)) {
  require_once $env_indicator_settings;
}
