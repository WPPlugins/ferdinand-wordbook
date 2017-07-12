<?php
define('WP_ADMIN', TRUE);
require_once('../../../wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/admin.php');
require_once('lib.php');

$mfposts = $_POST['mfposts'];
$mfposts = (isset($mfposts) && $mfposts == "on") ? 'Y' : 'N';

$mfeposts = $_POST['mfeposts'];
$mfeposts = (isset($mfeposts) && $mfeposts == "on") ? 'Y' : 'N';

$mfcomment = $_POST['mfcomment'];
$mfcomment = (isset($mfcomment) && $mfcomment == "on") ? 'Y' : 'N';

			if (!get_option('facebook_minifeed_posts')) 
			{
				add_option("facebook_minifeed_posts", $mfposts, '', 'yes');
			} else {
				update_option("facebook_minifeed_posts", $mfposts, '', 'yes');
			}	

			if (!get_option('facebook_minifeed_edit_posts')) 
			{
				add_option("facebook_minifeed_edit_posts", $mfeposts, '', 'yes');
			} else {
				update_option("facebook_minifeed_edit_posts", $mfeposts, '', 'yes');
			}	

			if (!get_option('facebook_minifeed_comments')) 
			{
				add_option("facebook_minifeed_comments", $mfcomment, '', 'yes');
			} else {
				update_option("facebook_minifeed_comments", $mfcomment, '', 'yes');
			}				

$postformat = $_POST['post-format'];
			if (!get_option('facebook_minifeed_postformat')) 
			{
				add_option("facebook_minifeed_postformat", $postformat, '', 'yes');
			} else {
				update_option("facebook_minifeed_postformat", $postformat, '', 'yes');
			}

$epostformat = $_POST['edit-post-format'];
			if (!get_option('facebook_minifeed_edit_postformat')) 
			{
				add_option("facebook_minifeed_edit_postformat", $epostformat, '', 'yes');
			} else {
				update_option("facebook_minifeed_edit_postformat", $epostformat, '', 'yes');
			}


$commentformat = $_POST['comment-format'];
			if (!get_option('facebook_minifeed_commentformat')) 
			{
				add_option("facebook_minifeed_commentformat", $commentformat, '', 'yes');
			} else {
				update_option("facebook_minifeed_commentformat", $commentformat, '', 'yes');
			}
      

header('Location:'.$_POST['_wp_http_referer']);		
?>
