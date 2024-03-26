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

// Hardcoded simple_oauth keys for local development.
if (getenv('APP_ENV') === 'local') {
  $config['simple_oauth.settings']['public_key'] = '/app/conf/local-keys/public.key';
  $config['simple_oauth.settings']['private_key'] = '/app/conf/local-keys/private.key';
}

