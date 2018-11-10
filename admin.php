<?php
function wp_supercache_generate_service_workers_admin()
{
    global $valid_nonce;

    if (isset($_POST['generate-service-worker']) && $valid_nonce) {
        generate_sw_files();
    }
    $id = 'service-worker-section';
    ?>
    <fieldset id="<?php echo $id; ?>" class="options">
    <h4>Service workers</h4>
    <form name="wp_manager" action="" method="post">
    <input type="hidden" name="generate-service-worker" value="1" />
    <div class="submit"><input class="button-primary" type="submit" value="<?php _e(
        'Update',
        'wp-super-cache'
    ); ?>" /></div>
    <?php wp_nonce_field('wp-cache'); ?>
  </form>
  </fieldset>
  <?php
}

if (isset($_GET['page']) && 'wpsupercache' === $_GET['page']) {
    add_cacheaction(
        'cache_admin_page',
        'wp_supercache_generate_service_workers_admin'
    );
}
