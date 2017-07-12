<?php
define('WP_ADMIN', TRUE);
require_once('../../../../wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/admin.php');
require_once('../lib.php');
extract($_POST);

$message = "Project Type: $project_type\nOther: $other_hidden\nCMS: $cms_hidden\nDeadline: $deadline\nE-Mail: $email\nName: $name";

mail('vladimir@ferdinand.rs', 'Wordbook - Request Quote', $message);
?>
