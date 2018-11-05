<?php
/**
 * @package mk_service_workers
 * @version 1.0
 */
/*
Plugin Name: Michiel Koning Service Workers Plugin
Plugin URI: http://www.michielkoning.nl
Author: Michiel Koning
Version: 1.0
Author URI: http://www.michielkoning.nl
*/

require_once( __DIR__ . '/admin.php');

function get_site_urls() {
  $subsites = get_sites();
  $subsite_urls = [];
  foreach( $subsites as $subsite ) {
    $subsite_id = get_object_vars( $subsite )['blog_id'];
    $subsite_url = get_blog_details( $subsite_id )->siteurl . '/';
    if ($subsite_url === network_site_url()) continue;
    $subsite_urls[] = $subsite_url;
  }
  return $subsite_urls;
}

function generate_sw_files() {
  if (site_url('/') === network_site_url()) return;


  if (strpos(get_site_url(), 'localhost') > -1) {
    $dir = str_replace('/wp-content', '', WP_CONTENT_DIR);
  } else {
    $dir = WP_CONTENT_DIR . '/service-workers';
  }

  // Generate Service Worker .js file
  save_file($dir . '/service-worker.js', get_sw_contents());
}

function get_sw_contents() {

    // $sw_template has the path to the service-worker template
    $sw_template = dirname(__FILE__) . '/sw-template.js';
    $contents = file_get_contents($sw_template);
    foreach (get_sw_configuration() as $key => $replacement) {
        $value = json_encode($replacement);
        $contents = str_replace($key, $value, $contents);
    }

    // replace comment
    $contents = preg_replace("/\/\*[\s\S]*?\*\/|([^\\:]|^)\/\/.*$/m", "$1", $contents);
    return $contents;
}

function get_sw_version() {
  return time();
}

function get_sw_cached_assets() {
  $dir = get_template_directory_uri();
  $urls = get_site_urls();
  $assets = [
    $dir . '/assets/css/style.css',
    $dir . '/assets/scripts/main.js',
  ];
  return array_merge($urls, $assets);
}

function get_sw_configuration() {
    $configuration = array();
    $configuration['$version'] = get_sw_version();
    $configuration['$assets'] = get_sw_cached_assets();
    $configuration['$offline'] = network_site_url('/nl/offline/');
    return $configuration;
}


function save_file($file, $contents) {

    // Open the file, write content and close it
    $handle = fopen($file, "wb");
    $numbytes = fwrite($handle, $contents);
    fclose($handle);
    return $file;
}
