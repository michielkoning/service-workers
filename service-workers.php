<?php

function generate_sw_files() {
    if (!is_admin()) return;
    $dir = WP_CONTENT_DIR . '/sw/';
    $dir = '/var/www/html/';

    // Create the directory structure
    if (!file_exists($dir)) {
        @mkdir($dir, 0755, true);
    }

    // Generate Service Worker .js file
    save_file($dir . 'service-worker.js', get_sw_contents());
}

function get_sw_contents() {

    // $sw_template has the path to the service-worker template
    $sw_template = dirname(__FILE__).'/sw-template.js';
    $contents = file_get_contents($sw_template);
    foreach (get_sw_configuration() as $key => $replacement) {
        $value = json_encode($replacement);
        $contents = str_replace($key, $value, $contents);
    }
    return $contents;
}

function get_sw_version() {
  return time();
}

function get_sw_assets() {
  $dir = get_template_directory_uri();
  return [
    $dir . '/assets/css/style.css',
    $dir . '/assets/scripts/main.js'
  ];
}



function get_sw_configuration() {
    $configuration = array();
    $configuration['$version'] = get_sw_version();
    $configuration['$assets'] = get_sw_assets();
    return $configuration;
}


function save_file($file, $contents) {

    // Open the file, write content and close it
    $handle = fopen($file, "wb");
    $numbytes = fwrite($handle, $contents);
    fclose($handle);
    return $file;
}


add_action('init', 'generate_sw_files');
