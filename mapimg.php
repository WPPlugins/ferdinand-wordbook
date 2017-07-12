<?php
define('WP_ADMIN', TRUE);
require_once('../../../wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/admin.php');
require_once('json.php');
require_once('lib.php');
$json = new Services_JSON();
$var = stripslashes(urldecode($_GET['v']));
//print_r($var);
$var_arr = $json->decode($var);
//print_r($var_arr);
$img = $var_arr->imgsrc;
$xs = $var_arr->xcoord;
$ys = $var_arr->ycoord;
$caption = $var_arr->caption;
$caption2 = implode(', ',$var_arr->caption);
$s = @getimagesize($img);
$id = $var_arr->id;
$wpfbg = new wpfbg();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?=$caption2?></title>
<?php $wpfbg->print_blogjs(false); ?>
<style>
body{margin:0;padding:0;}
</style>
</head>
<body>

<?php
$wpfbg->map_Start($img, $id);
if (is_array($xs)) {
	foreach($xs as $key=>$x) {
		$xn = round(($x / 100) * $s[0]);
		$yn = round(($ys[$key] / 100) * $s[1]);
		$wpfbg->map_Entry($caption[$key], $xn, $yn, 150, 150, $id);
	}
} else {
		$wpfbg->map_Entry($caption, $x, $y, 150, 150, $id);
}
$wpfbg->map_End(); 
?>
</body>
</html>
