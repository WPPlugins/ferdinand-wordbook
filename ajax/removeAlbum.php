<?php
define('WP_ADMIN', TRUE);
require_once('../../../../wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/admin.php');
require_once('../lib.php');

$wpfbg = new wpfbg(true);
echo $wpfbg->removeAlbum($_GET['aid']);
?>