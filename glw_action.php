<?php
define('WP_ADMIN', TRUE);
require_once('../../../wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/admin.php');
require_once('lib.php');

			if (!get_option('facebook_gallery_widget_colums') && get_option('facebook_gallery_widget_colums')!='') 
			{
				add_option("facebook_gallery_widget_colums", $_POST['fbgl_colums'], '', 'yes');
			} else {
				update_option("facebook_gallery_widget_colums", $_POST['fbgl_colums'], '', 'yes');
			}	

			if (!get_option('facebook_gallery_widget_rows') && get_option('facebook_gallery_widget_rows')!='') 
			{
				add_option("facebook_gallery_widget_rows", $_POST['fbgl_rows'], '', 'yes');
			} else {
				update_option("facebook_gallery_widget_rows", $_POST['fbgl_rows'], '', 'yes');
			}		
 
			if (!get_option('facebook_gallery_widget_spacing') && get_option('facebook_gallery_widget_spacing')!='') 
			{
				add_option("facebook_gallery_widget_spacing", $_POST['fbspacing'], '', 'yes');
			} else {
				update_option("facebook_gallery_widget_spacing", $_POST['fbspacing'], '', 'yes');
			}	
			
			if (!get_option('facebook_gallery_widget_padding') && get_option('facebook_gallery_widget_padding')!='') 
			{
				add_option("facebook_gallery_widget_padding", $_POST['fbpadding'], '', 'yes');
			} else {
				update_option("facebook_gallery_widget_padding", $_POST['fbpadding'], '', 'yes');
			}	

			if (!get_option('facebook_gallery_widget_title') && get_option('facebook_gallery_widget_title')!='') 
			{
				add_option("facebook_gallery_widget_title", $_POST['fbtitle'], '', 'yes');
			} else {
				update_option("facebook_gallery_widget_title", $_POST['fbtitle'], '', 'yes');
			}	

			if (!get_option('facebook_gallery_widget_class') && get_option('facebook_gallery_widget_class')!='') 
			{
				add_option("facebook_gallery_widget_class", $_POST['fbclass'], '', 'yes');
			} else {
				update_option("facebook_gallery_widget_class", $_POST['fbclass'], '', 'yes');
			}	

			if (!get_option('facebook_gallery_widget_height') && get_option('facebook_gallery_widget_height')!='') 
			{
				add_option("facebook_gallery_widget_height", $_POST['fbheight'], '', 'yes');
			} else {
				update_option("facebook_gallery_widget_height", $_POST['fbheight'], '', 'yes');
			}	

			if (!get_option('facebook_gallery_widget_width') && get_option('facebook_gallery_widget_width')!='') 
			{
				add_option("facebook_gallery_widget_width", $_POST['fbwidth'], '', 'yes');
			} else {
				update_option("facebook_gallery_widget_width", $_POST['fbwidth'], '', 'yes');
			}		
	
			if (!get_option('facebook_photo_cache') && get_option('facebook_photo_cache')!='') 
			{
				add_option("facebook_photo_cache", $_POST['fbcache'], '', 'yes');
			} else {
				update_option("facebook_photo_cache", $_POST['fbcache'], '', 'yes');
			}
	
header('Location:'.$_POST['_wp_http_referer']);		
?>
