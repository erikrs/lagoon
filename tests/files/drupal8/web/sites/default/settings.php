<?php

/**
 * @file
 * amazee.io Drupal 8 configuration file.
 *
 * You should not edit this file, please use environment specific files!
 * They are loaded in this order:
 * - all.settings.php
 *   For settings that should be applied to all environments (dev, prod, staging, docker, etc).
 * - all.services.yml
 *   For services that should be applied to all environments (dev, prod, staging, docker, etc).
 * - production.settings.php
 *   For settings only for the production environment.
 * - production.services.yml
 *   For services only for the production environment.
 * - development.settings.php
 *   For settings only for the development environment (devevlopment sites, docker).
 * - development.services.yml
 *   For services only for the development environment (devevlopment sites, docker).
 * - settings.local.php
 *   For settings only for the local environment, this file will not be commited in GIT!
 * - services.local.yml
 *   For services only for the local environment, this file will not be commited in GIT!
 *
 */

### amazee.io Database connection
if(getenv('LAGOON_PROJECT')){
  $databases['default']['default'] = array(
    'driver' => 'mysql',
    'database' => getenv('LAGOON_PROJECT'),
    'username' => getenv('DB_USERNAME'),
    'password' => getenv('DB_PASSWORD'),
    'host' => getenv('DB_HOST'),
    'port' => getenv('DB_PORT'),
    'prefix' => '',
  );
}

### amazee.io Solr connection
// WARNING: you have to create a search_api server having "solr" machine name at
// /admin/config/search/search-api/add-server to make this work.
if (getenv('SOLR_HOST') && getenv('SOLR_PORT')) {
  $config['search_api.server.solr']['backend_config']['connector_config']['host'] = getenv('SOLR_HOST');
  $config['search_api.server.solr']['backend_config']['connector_config']['path'] = '/solr/';
  $config['search_api.server.solr']['backend_config']['connector_config']['core'] = getenv('SOLR_CORE') ?: getenv('LAGOON_PROJECT');
  $config['search_api.server.solr']['backend_config']['connector_config']['port'] = getenv('SOLR_PORT');
  $config['search_api.server.solr']['backend_config']['connector_config']['http_user'] = (getenv('SOLR_USER') ?: '');
  $config['search_api.server.solr']['backend_config']['connector_config']['http']['http_user'] = (getenv('SOLR_USER') ?: '');
  $config['search_api.server.solr']['backend_config']['connector_config']['http_pass'] = (getenv('SOLR_PASSWORD') ?: '');
  $config['search_api.server.solr']['backend_config']['connector_config']['http']['http_pass'] = (getenv('SOLR_PASSWORD') ?: '');
  $config['search_api.server.solr']['name'] = 'AmazeeIO Solr - Environment: ' . getenv('LAGOON_ENVIRONMENT_TYPE');
}

### amazee.io Varnish & Reverse proxy settings
if (getenv('VARNISH_HOSTS') && getenv('VARNISH_SECRET')) {
  $varnish_hosts = explode(',', getenv('VARNISH_HOSTS'));
  array_walk($varnish_hosts, function(&$value, $key) { $value .= ':6082'; });

  $settings['reverse_proxy'] = TRUE;
  $settings['reverse_proxy_addresses'] = array_merge(explode(',', getenv('VARNISH_HOSTS')), array('127.0.0.1'));

  $config['varnish.settings']['varnish_control_terminal'] = implode($varnish_hosts, " ");
  $config['varnish.settings']['varnish_control_key'] = getenv('VARNISH_SECRET');
  $config['varnish.settings']['varnish_version'] = 4;
}

### Trusted Host Patterns, see https://www.drupal.org/node/2410395 for more information.
### If your site runs on multiple domains, you need to add these domains here
//$settings['trusted_host_patterns'] = array(
//  '^' . str_replace('.', '\.', getenv('AMAZEEIO_SITE_URL')) . '$',
//);

### Temp directory
  $config['system.file']['path']['temporary'] = '/tmp';

### Hash Salt
if (getenv('HASH_SALT')) {
  $settings['hash_salt'] = getenv('HASH_SALT');
}

// Settings for all environments
if (file_exists(__DIR__ . '/all.settings.php')) {
  include __DIR__ . '/all.settings.php';
}

// Services for all environments
if (file_exists(__DIR__ . '/all.services.yml')) {
  $settings['container_yamls'][] = __DIR__ . '/all.services.yml';
}

if(getenv('LAGOON_ENVIRONMENT_TYPE')){
  // Environment specific settings files.
  if (file_exists(__DIR__ . '/' . getenv('LAGOON_ENVIRONMENT_TYPE') . '.settings.php')) {
    include __DIR__ . '/' . getenv('LAGOON_ENVIRONMENT_TYPE') . '.settings.php';
  }

  // Environment specific services files.
  if (file_exists(__DIR__ . '/' . getenv('LAGOON_ENVIRONMENT_TYPE') . '.services.yml')) {
    $settings['container_yamls'][] = __DIR__ . '/' . getenv('LAGOON_ENVIRONMENT_TYPE') . '.services.yml';
  }
}

// Last: this servers specific settings files.
if (file_exists(__DIR__ . '/settings.local.php')) {
  include __DIR__ . '/settings.local.php';
}
// Last: This server specific services file.
if (file_exists(__DIR__ . '/services.local.yml')) {
  $settings['container_yamls'][] = __DIR__ . '/services.local.yml';
}
