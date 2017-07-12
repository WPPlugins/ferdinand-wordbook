<?php
define('WP_ADMIN', TRUE);
require_once('../../../wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/admin.php');
require_once('lib.php');

$friend_comment = $_POST['friend_comment'];
$friend_comment = (isset($friend_comment) && $friend_comment == "on") ? 'Y' : 'N';



			if (!get_option('facebook_friendscomment')) 
			{
				add_option("facebook_friendscomment", $friend_comment, '', 'yes');
			} else {
				update_option("facebook_friendscomment", $friend_comment, '', 'yes');
			}	


header('Location:'.$_POST['_wp_http_referer']);		
?>
