<?php
if (!version_compare(phpversion(), "5.0.0", ">=")) {
	die('<h1>Wordbook requires PHP version 5 or grater. Your PHP version is '.phpversion().'</h1>');
} 
if (!class_exists('Services_JSON')) {
    require_once('json.php');
}
require_once('filecache.php');
require_once('functions.php');
require_once('bbcode.php');

class wpfbg {
	var $api_key = '0dfe6192ef3b071d8e32f702242ce36b';
	var $app_url = 'http://d9a640bd.fb.joyent.us/app/';
	var $cachetimeout = 60;
	var $debug = 1;
  var $mf_tags = array('%posturl%','%postname%','%category%','%blogname%','%postauthor%', '%commentauthor%');

	function wpfbg  ($ajax=false) {
		//hooks 
		if ($ajax==true) return true;
		add_action('admin_menu', array($this,'ConfigureMenu'));
		add_action('admin_head', array($this,'print_adminjs'));
		add_action('wp_print_scripts', array($this,'print_blogjs'));

		if(get_option('facebook_minifeed_posts')=='Y' || get_option('facebook_minifeed_edit_posts')=='Y') {
			add_action('transition_post_status',array($this,'send2feed'),9,3);
			//add_action('publish_post',array($this,'send2feed'),9,3);
		}

		if(get_option('facebook_minifeed_comments')=='Y') {
			add_action('comment_post', array($this,'comment2minifeed'),9,2);
			add_action('wp_set_comment_status', array($this,'commentApproved'),9,2);
		}		

    		add_action("plugins_loaded", array($this,'init_widgets'));
		add_action("deactivate_ferdinand-wordbook/index.php", array($this,'uninstall'));

		if (get_option('facebook_photo_cache')!=$this->cachetimeout && get_option('facebook_photo_cache')) {
			$this->cachetimeout = get_option('facebook_photo_cache');
		}
		
		return true;
	}
	
	function init_widgets() {
		register_sidebar_widget('Wordbook FB Photos', array($this,'fb_galleryWidget'));
		//register_sidebar_widget('Wordbook FB Status', array($this,'fb_statusWidget'));	
	}

	function get_token() {
		$token = str_replace('"', '', trim($this->curl_get_contents("create_token.php")));
		if (!get_option('facebook_token')) 
		{
			add_option("facebook_token", $token, '', 'yes');
		} else {
			update_option("facebook_token", $token, '', 'yes');
		}
	}

	function ConfigureMenu() {
		add_menu_page('Facebook on Wordpress', 'Wordbook', 1, 'ferdinand-wordbook', array($this,'wpfbg_identify'));
		if ($this->check_fbaccount()) {
		add_submenu_page('ferdinand-wordbook', 'Mini-Feed', 'Mini-Feed', 1, 'wpfbg_minifeed', array($this,'wpfbg_minifeed'));
		//add_submenu_page('wpfbg', 'Friends Comment', 'Friends Comment', 1, 'wpfbg_friendscomment', array($this,'wpfbg_friendscomment'));
		add_submenu_page('ferdinand-wordbook', 'Widget Gallery', 'Widget Gallery', 1, 'wpfbg_widget_gallery', array($this,'wpfbg_widget_gallery')); 
		//add_submenu_page('wpfbg', 'Facebook Status', 'Facebook Status', 1, 'wpfbg_status', array($this,'wpfbg_status'));
		add_submenu_page('ferdinand-wordbook', 'Facebook Template Functions', 'Template Functions', 1, 'wpfbg_mics', array($this,'wpfbg_mics'));		
		}
	}
	
	function uninstall() {
		global $wpdb;
		// expected_slashed ($name)
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'facebook_%'" );
		$alloptions = wp_load_alloptions();
		if ( isset( $alloptions[$name] ) ) {
			unset( $alloptions[$name] );
			wp_cache_set( 'alloptions', $alloptions, 'options' );
		}
		return true;

	}
	function install() {

		add_option("facebook_minifeed_posts", 'N', '', 'yes');
		add_option("facebook_minifeed_edit_posts", 'N', '', 'yes');
		add_option("facebook_minifeed_comments", 'N', '', 'yes');
		add_option("facebook_minifeed_postformat", 'posted [url=%posturl%]%postname%[/url] to %blogname% in category %category%.', '', 'yes');
		add_option("facebook_minifeed_commentformat", 'has new comment on his %blogname% blog, made by %commentauthor% on [url=%posturl%]%postname%[/url].', '', 'yes');
		add_option("facebook_minifeed_edit_postformat", 'edited [url=%posturl%]%postname%[/url] in %blogname% blog in category %category%.', '', 'yes');
				
		add_option("facebook_gallery_widget_colums", '2', '', 'yes');
		add_option("facebook_gallery_widget_rows", '5', '', 'yes');
		add_option("facebook_gallery_widget_spacing", '1', '', 'yes');
		add_option("facebook_gallery_widget_padding", '1', '', 'yes');
		add_option("facebook_gallery_widget_title", 'My Facebook Photos', '', 'yes');
		add_option("facebook_gallery_widget_class", '', '', 'yes');
		add_option("facebook_gallery_widget_height", '75', '', 'yes');
		add_option("facebook_gallery_widget_width", '75', '', 'yes');
		add_option("facebook_photo_cache", '60', '', 'yes');

		//add_option("facebook_friendscomment", 'N', '', 'yes');
	}

  function print_head($description='Facebook on Wordpress', $title='Wordbook') {
    ?>
		<div class="wrap">
		<table>
		<tr><td valign="top" style="padding:0 10px 0 0;">
		<h2><?=$title?></h2>
		<p><?=$description?></p>
		<?php
  }
  
  function check_fbaccount() {
    if (!get_option('facebook_session_key')) return false;
    else return true;
  }
  
  function print_foot() {
    ?>
 	</td><td style="width:200px" valign="top">
<div class="submitbox" id="submitpost">

<div id="previewview" style="color:#fff;font-size:1.4em">
Need Developer?
</div>

<div class="inside" style="padding:0 0 0 5px;">
<script>
function fb_project_type(sb) {
	if (sb[sb.selectedIndex].value=='Other') {
		$('other_hidden').show();
	} else {
		$('other_hidden').hide();
	}
	if (sb[sb.selectedIndex].value=='Opensource CMS customization') {
		$('cms_hidden').show();
	} else {
		$('cms_hidden').hide();
	}
}
</script>

<p>
	<label for="project_type">Project Type:</label><br />
	<select name="project_type" id="project_type" onchange="fb_project_type(this);" style="font-size:0.8em">
		<option value="Wordpress customization" selected="selected">Wordpress customization</option>
		<option value="PHP/MySQL Project from scratch">PHP/MySQL Project from scratch</option>
		<option value="Javacript/Ajax">Javacript/Ajax</option>
		<option value="Facebook application">Facebook application</option>
		<option value="Opensource CMS customization">Opensource CMS customization</option>
		<option value="Development consulting">Development consulting</option>
		<option value="Other">Other</option>
	</select>
	<div id="other_hidden" style="display:none;margin-top:-10px;">
		<label>Please be more specific:</label><br />
		<input type="text" name="other_msg" id="other_msg">
	</div>
	<div id="cms_hidden" style="display:none;">
		<label>Please specify CMS:</label><br />
		<input type="text" name="cms_msg" id="cms_msg">
	</div>
	<div>
		<label>Project deadline:</label><br />
		<input type="text" name="deadline" id="deadline">
	</div>
	<div>
		<label>Project details:</label><br />
		<textarea name="details" id="details"></textarea>
	</div>
	<div>
		<label>Full name:</label><br />
		<input type="text" name="name" id="name">
	</div>
	<div>
		<label>Your E-Mail:</label><br />
		<input type="text" name="email" id="email">
	</div>
</p>

</div>

<p class="submit">
<span id="fbloader_rquote" style="display:none"><img src="images/loading.gif"></span>	
	<input value="Request a quote" type="button" name="rquote" id="rquote" class="button button-highlighted" onclick="request_quote()">
</p>
</div></div>
<div>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_donations">
<input type="hidden" name="business" value="vcvetic@hazaah.com">
<input type="hidden" name="item_name" value="Wordbook">
<span style="padding-left:10px;position:relative;top:-10px;"><input type="text" name="amount" value="1" style="width:36px;"> usd</span>
<input type="hidden" name="no_shipping" value="0">
<input type="hidden" name="no_note" value="1">
<input type="hidden" name="currency_code" value="USD">
<input type="hidden" name="tax" value="0">
<input type="hidden" name="lc" value="GB">
<input type="hidden" name="bn" value="PP-DonationsBF">
<input type="image" src="https://www.paypalobjects.com/WEBSCR-540-20080911-2/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online.">
<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
</div>
	</td></tr>
	</table>
    </div>
    <?php  
  }
  
  function wpfbg_status() {
	$this->print_head('Status stuff', 'Facebook Status'); 
	$session_key = get_option('facebook_session_key');
	$uid = get_option('facebook_uid');
	$secret = get_option('facebook_secret');
	$response = $this->curl_get_contents("getUserInfo.php?session_key=$session_key&uid=$uid&secret=$secret");
	$json = new Services_JSON();
	$ob = $json->decode($response);	
	?>
      <form method="post" action="../wp-content/plugins/ferdinand-wordbook/glw_action.php">
      <input name="_wp_http_referer" value="../../../wp-admin/admin.php?page=wpfbg_widget_gallery" type="hidden">
      <table class="form-table">
      <tbody>
        <tr valign="top">
          <th scope="row">Facebook Status</th>
          <td><input type="text" name="fbstatus" id="fbstatus" value="<?=$ob->status->message?>"> <span><?=ucwords(ezDate($ob->status->time)).' Ago';?></span></td>
        </tr>
	</tbody>
	</table>
	<p class="submit">
		<input name="Submit" value="Change Status" type="button" onclick="fb_changeStatus();">
	</p>
	</form>
	<?php
	$this->print_foot();
  }

	/*function wpfbg_friendscomment() {
		$this->print_head('Here you can set to allow your Facebook friends to comment on your blog posts without moderation, even if they are not registered on your blog!<br />
					Simply check below to enable this function, or uncheck to disable it.', 'Friends Comment'); 
		?>
    <form method="post" action="../wp-content/plugins/ferdinand-wordbook/fc_action.php">
      <input name="_wp_http_referer" value="../../../wp-admin/admin.php?page=wpfbg_friendscomment" type="hidden">
      <table class="form-table">
      <tbody>
        <tr valign="top">
          <th scope="row">Enable Friends Comment</th>
          <td><input type="checkbox" name="friend_comment" <?if(get_option('facebook_friendscomment')=='Y'){?>checked="checked"<?}?>></td>
        </tr>
      </tbody>
      </table>
      <p class="submit">
      <input name="Submit" value="Save Changes" type="submit">
      </p>
    </form>
		<?php
		$this->print_foot();
	}*/

  function wpfbg_mics() {
	$this->print_head('Mics template functions. Using only copy/paste you can display almost any Facebook info on your blog. Simply copy Wordpress Template Code and paste it in your wordpress template.', 'Template Functions'); 
	$session_key = get_option('facebook_session_key');
	$uid = get_option('facebook_uid');
	$secret = get_option('facebook_secret');
	$response = $this->curl_get_contents("getUserInfo.php?session_key=$session_key&uid=$uid&secret=$secret");
	$json = new Services_JSON();
	$ob = $json->decode($response);	
	?>
		<table class="widefat">
			<thead>
			<tr>
				<th scope="col">Name</th>
				<th scope="col">Wordpress Template Code</th>
				<th scope="col">Current Value</th>
				<th scope="col">Description</th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td scope="row">about_me</td>
				<td scope="row">&lt;?=facebook::userInfo('about_me')?&gt;</td>
				<td scope="row"><?=$ob->about_me?></td>
				<td scope="row">text element corresponding to Facebook 'About Me' profile section. May be blank.</td>
			</tr>
			<tr>
				<td scope="row">activities</td>
				<td scope="row">&lt;?=facebook::userInfo('activities')?&gt;</td>
				<td scope="row"><?=$ob->activities?></td>
				<td scope="row">User-entered "Activities" profile field. No guaranteed formatting.</td>
			</tr>
			<tr>
				<td scope="row">birthday</td>
				<td scope="row">&lt;?=facebook::userInfo('birthday')?&gt;</td>
				<td scope="row"><?=$ob->birthday?></td>
				<td scope="row">User-entered "Birthday" profile field. No guaranteed formatting. </td>
			</tr>
			<tr>
				<td scope="row">books</td>
				<td scope="row">&lt;?=facebook::userInfo('books')?&gt;</td>
				<td scope="row"><?=$ob->books?></td>
				<td scope="row">User-entered "Favorite Books" profile field. No guaranteed formatting. </td>
			</tr>
			<tr>
				<td scope="row">first_name</td>
				<td scope="row">&lt;?=facebook::userInfo('first_name')?&gt;</td>
				<td scope="row"><?=$ob->first_name?></td>
				<td scope="row">is generated from the user-entered "Name" profile field.  </td>
			</tr>
			<tr>
				<td scope="row">interests</td>
				<td scope="row">&lt;?=facebook::userInfo('interests')?&gt;</td>
				<td scope="row"><?=$ob->interests?></td>
				<td scope="row">interests </td>
			</tr>
			<tr>
				<td scope="row">last_name</td>
				<td scope="row">&lt;?=facebook::userInfo('last_name')?&gt;</td>
				<td scope="row"><?=$ob->last_name?></td>
				<td scope="row">is generated from the user-entered "Name" profile field.  </td>
			</tr>
			<tr>
				<td scope="row">locale</td>
				<td scope="row">&lt;?=facebook::userInfo('locale')?&gt;</td>
				<td scope="row"><?=$ob->locale?></td>
				<td scope="row">is the current locale code in which the user has chosen to browse Facebook. The basic format is LL_CC, where LL is a two-letter language code, and CC is a two-letter country code. For instance, 'en_US' represents US English.  </td>
			</tr>
			<tr>
				<td scope="row">movies</td>
				<td scope="row">&lt;?=facebook::userInfo('movies')?&gt;</td>
				<td scope="row"><?=$ob->movies?></td>
				<td scope="row">User-entered "Favorite Movies" profile field. No guaranteed formatting. </td>
			</tr>
			<tr>
				<td scope="row">music</td>
				<td scope="row">&lt;?=facebook::userInfo('music')?&gt;</td>
				<td scope="row"><?=$ob->music?></td>
				<td scope="row">User-entered "Favorite Music" profile field. No guaranteed formatting. </td>
			</tr>
			<tr>
				<td scope="row">name</td>
				<td scope="row">&lt;?=facebook::userInfo('name')?&gt;</td>
				<td scope="row"><?=$ob->name?></td>
				<td scope="row">User-entered "Name" profile field. May not be blank. </td>
			</tr>
			<tr>
				<td scope="row">notes_count</td>
				<td scope="row">&lt;?=facebook::userInfo('notes_count')?&gt;</td>
				<td scope="row"><?=$ob->notes_count?></td>
				<td scope="row">Total number of notes written by the user. </td>
			</tr>
			<tr>
				<td scope="row">pic</td>
				<td scope="row">&lt;?=facebook::userInfo('pic')?&gt;</td>
				<td scope="row"><?=$ob->pic?></td>
				<td scope="row">URL of user profile picture, with max width 100px and max height 300px. May be blank. </td>
			</tr>
			<tr>
				<td scope="row">pic_big</td>
				<td scope="row">&lt;?=facebook::userInfo('pic_big')?&gt;</td>
				<td scope="row"><?=$ob->pic_big?></td>
				<td scope="row">URL of user profile picture, with max width 200px and max height 600px. May be blank. </td>
			</tr>
			<tr>
				<td scope="row">pic_small</td>
				<td scope="row">&lt;?=facebook::userInfo('pic_small')?&gt;</td>
				<td scope="row"><?=$ob->pic_small?></td>
				<td scope="row">URL of user profile picture, with max width 50px and max height 150px. May be blank. </td>
			</tr>
			<tr>
				<td scope="row">pic_square</td>
				<td scope="row">&lt;?=facebook::userInfo('pic_square')?&gt;</td>
				<td scope="row"><?=$ob->pic_square?></td>
				<td scope="row">URL of a square section of the user profile picture, with width 50px and height 50px. May be blank. </td>
			</tr>
			<tr>
				<td scope="row">political</td>
				<td scope="row">&lt;?=facebook::userInfo('political')?&gt;</td>
				<td scope="row"><?=$ob->political?></td>
				<td scope="row">User-entered "Political View" profile field. It's a free-form text field. </td>
			</tr>
			<tr>
				<td scope="row">profile_update_time</td>
				<td scope="row">&lt;?=facebook::userInfo('profile_update_time')?&gt;</td>
				<td scope="row"><?=$ob->profile_update_time?></td>
				<td scope="row">Time (in seconds since epoch) that the user's profile was last updated. If the user's profile was not updated recently, 0 is returned. </td>
			</tr>
			<tr>
				<td scope="row">quotes</td>
				<td scope="row">&lt;?=facebook::userInfo('quotes')?&gt;</td>
				<td scope="row"><?=$ob->quotes?></td>
				<td scope="row">User-entered "Favorite Quotes" profile field. No guaranteed formatting. </td>
			</tr>
			<tr>
				<td scope="row">relationship_status</td>
				<td scope="row">&lt;?=facebook::userInfo('relationship_status')?&gt;</td>
				<td scope="row"><?=$ob->relationship_status?></td>
				<td scope="row">User-entered "Relationship Status" profile field. Is either blank or one of the following strings: Single, In a Relationship, In an Open Relationship, Engaged, Married, It's Complicated. </td>
			</tr>
			<tr>
				<td scope="row">religion</td>
				<td scope="row">&lt;?=facebook::userInfo('religion')?&gt;</td>
				<td scope="row"><?=$ob->religion?></td>
				<td scope="row">User-entered "Religious Views" profile field. No guaranteed formatting. </td>
			</tr>
			<tr>
				<td scope="row">sex</td>
				<td scope="row">&lt;?=facebook::userInfo('sex')?&gt;</td>
				<td scope="row"><?=$ob->sex?></td>
				<td scope="row">User-entered "Sex" profile file. Either "male", "female", or left blank.  </td>
			</tr>
			<tr>
				<td scope="row">significant_other</td>
				<td scope="row">&lt;?=facebook::userInfo('significant_other')?&gt;</td>
				<td scope="row"><?=$ob->significant_other?></td>
				<td scope="row">the name of the person the user is in a relationship with. Only shown if both people in the relationship are users of the application making the request. </td>
			</tr>
			<tr>
				<td scope="row">status</td>
				<td scope="row">&lt;?=facebook::userInfo('status')?&gt;</td>
				<td scope="row"><?=$ob->status->message?></td>
				<td scope="row">Facebook status message.</td>
			</tr>
			<tr>
				<td scope="row">timezone</td>
				<td scope="row">&lt;?=facebook::userInfo('timezone')?&gt;</td>
				<td scope="row"><?=$ob->timezone?></td>
				<td scope="row">offset from GMT (e.g. Belgrade is +1). </td>
			</tr>
			<tr>
				<td scope="row">tv</td>
				<td scope="row">&lt;?=facebook::userInfo('tv')?&gt;</td>
				<td scope="row"><?=$ob->tv?></td>
				<td scope="row">User-entered "Favorite TV Shows" profile field. No guaranteed formatting.  </td>
			</tr>
			<tr>
				<td scope="row">wall_count</td>
				<td scope="row">&lt;?=facebook::userInfo('wall_count')?&gt;</td>
				<td scope="row"><?=facebook::userInfo('wall_count')?></td>
				<td scope="row">Total number of posts to the user's wall. </td>
			</tr>
			</tbody>
		</table>
	<?php
	$this->print_foot();
  }

	static function userInfo($keyword) {
		$session_key = get_option('facebook_session_key');
		$uid = get_option('facebook_uid');
		$secret = get_option('facebook_secret');
		$fb = new facebook();
		$response = $fb->curl_get_contents("getUserInfo.php?session_key=$session_key&uid=$uid&secret=$secret");
		$json = new Services_JSON();
		$ob = $json->decode($response);
		if ($keyword=='status') return $ob->status->message;

		return $ob->$keyword;
	}


  function wpfbg_widget_gallery() {
    $this->print_head('Here you can configure Facebook Gallery Widget.<br />After you finish with configuration head to Wordpress Widgets settings (<a href="widgets.php">here</a>) and activate widget "Wordbook FB Photos"... all done!', 'Widget Gallery'); 
    $selected_albums = array();
    if (get_option('facebook_albums') && trim(get_option('facebook_albums'))!='' && get_option('facebook_albums')!='null') {
    $selected_albums = get_option('facebook_albums');
    $json = new Services_JSON();
    $selected_albums = $json->decode($selected_albums);
    }
     ?>
    <form method="post" action="../wp-content/plugins/ferdinand-wordbook/glw_action.php">
      <input name="_wp_http_referer" value="../../../wp-admin/admin.php?page=wpfbg_widget_gallery" type="hidden">
      <table class="form-table">
      <tbody>
        <tr valign="top">
          <th scope="row">Sidebar Title</th>
          <td><input type="text" name="fbtitle" value="<?php if(!get_option('facebook_gallery_widget_title')){?>My Facebook Photos<?}else{ echo get_option('facebook_gallery_widget_title'); }?>"></td>
        </tr>
        <tr valign="top">
          <th scope="row">CSS Table Class</th>
          <td><input type="text" name="fbclass" value="<?php if(!get_option('facebook_gallery_widget_class')){?><?}else{ echo get_option('facebook_gallery_widget_class'); }?>"></td>
        </tr>
        <tr valign="top">
          <th scope="row">Rows</th>
          <td><input type="text" name="fbgl_rows" value="<?php if(!get_option('facebook_gallery_widget_rows')){?>5<?}else{ echo get_option('facebook_gallery_widget_rows'); }?>"></td>
        </tr>
        <tr valign="top">
          <th scope="row">Columns</th>
          <td><input type="text" name="fbgl_colums" value="<?php if(!get_option('facebook_gallery_widget_colums')){?>2<?}else{ echo get_option('facebook_gallery_widget_colums'); }?>"></td>
        </tr>
        <tr valign="top">
          <th scope="row">Cell Spacing</th>
          <td><input type="text" name="fbspacing" value="<?php if(!get_option('facebook_gallery_widget_spacing')){?>1<?}else{ echo get_option('facebook_gallery_widget_spacing'); }?>"></td>
        </tr>
        <tr valign="top">
          <th scope="row">Cell Padding</th>
          <td><input type="text" name="fbpadding" value="<?php if(!get_option('facebook_gallery_widget_padding')){?>1<?}else{ echo get_option('facebook_gallery_widget_padding'); }?>"></td>
        </tr>
        <tr valign="top">
          <th scope="row">Thumb Size</th>
          <td><input type="text" name="fbwidth" value="<?php if(!get_option('facebook_gallery_widget_width')){?>1<?}else{ echo get_option('facebook_gallery_widget_width'); }?>" size="3" >x<input size="3" type="text" name="fbheight" value="<?php if(!get_option('facebook_gallery_widget_height')){?>1<?}else{ echo get_option('facebook_gallery_widget_height'); }?>">px</td>
        </tr>
        <tr valign="top">
          <th scope="row">Cache expiry time</th>
          <td><input type="text" name="fbcache" value="<?php if(!get_option('facebook_photo_cache')){?>60<?}else{ echo get_option('facebook_photo_cache'); }?>" size="3" >sec</td>
        </tr>
      </tbody>
      </table>
      <p class="submit">
      <input name="Submit" value="Save Changes" type="submit">
      </p>
    </form>
     <table>
      <tr>
        <td width="420" valign="top">
        <h2>Not Selected</h2>
        <span id="fb_notselected">
     <?php
			$session_key = get_option('facebook_session_key');
			$uid = get_option('facebook_uid');
			$secret = get_option('facebook_secret');  
			$response = $this->curl_get_contents("listAlbums.php?session_key=$session_key&uid=$uid&secret=$secret");
			$json = new Services_JSON();
			$ob = $json->decode($response);
      //die($response);            
      foreach($ob as $aid=>$album) {
        $fbselected = 'N';
        foreach($selected_albums as $selected_album) {
            if ("a".$selected_album=="a".$aid) {
                $fbselected = 'Y';
            }
        }
        if ($fbselected != 'Y') {
        $fbselected = 'N';
        ?>
        <span id="album-<?=$aid?>">
                <table width="440" cellspacing="0" cellpadding="2">
                  <tr>
                    <td width="150">
                      <img class="gallerypic" src="<?=$album->photos->src?>">
                    </td>
                    <td valign="top" width="290">
                      <table cellspacing="0" cellpadding="2">
                          <tr>
                              <td><strong><?=$album->name?></strong></td>
                          </tr>
                          <tr>
                              <td><strong>Created:</strong> <?=date("F j, Y, g:i a",$album->created)?></td>
                          </tr>
                          <tr>
                              <td><strong>Modified:</strong> <?=date("F j, Y, g:i a",$album->modified)?></td>
                          </tr>
                          <tr>
                              <td><strong>Total Photos:</strong> <?=$album->size?></td>
                          </tr>
                          <tr>
                              <td>
                              <input id="action-<?=$aid?>" type="button" value="Add this album" onclick="fb_selectAlbum('<?=$aid?>');" class="button">
                              <input type="button" value="Remove this album" style="display:none" id="raction-<?=$aid?>" onclick="fb_removeAlbum('<?=$aid?>');" class="button">
                              </td>
                          </tr>
                      </table>
                    </td>
                  </tr>
                </table>
        </span>
        <?php
        }
      }
      ?>
          </span>
          </td>

            <td width="440" valign="top">
            <h2>Selected</h2>
            <span id="fb_selected">
            <?php
                $y=0;
                foreach($ob as $aid=>$album) {
                $fbselected = 'N';
                foreach($selected_albums as $selected_album) {
                    if ("a".$selected_album=="a".$aid) {
                        $fbselected = 'Y';
                    }
                }
                if ($fbselected == 'Y') {
                $fbselected = 'N';
                $y++
                ?>
                <span id="album-<?=$aid?>">
                        <table width="440" cellspacing="0" cellpadding="2">
                          <tr>
                            <td width="150">
                              <img class="gallerypic" src="<?=$album->photos->src?>">
                            </td>
                            <td valign="top" width="290">
                              <table cellspacing="0" cellpadding="2">
                                  <tr>
                                      <td><strong><?=$album->name?></strong></td>
                                  </tr>
                                  <tr>
                                      <td><strong>Created:</strong> <?=date("F j, Y, g:i a",$album->created)?></td>
                                  </tr>
                                  <tr>
                                      <td><strong>Modified:</strong> <?=date("F j, Y, g:i a",$album->modified)?></td>
                                  </tr>
                                  <tr>
                                      <td><strong>Total Photos:</strong> <?=$album->size?></td>
                                  </tr>
                                  <tr>
                                      <td>
                                      <input type="button" value="Add this album" style="display:none" id="action-<?=$aid?>" onclick="fb_selectAlbum('<?=$aid?>');" class="button">
                                      <input type="button" value="Remove this album" id="raction-<?=$aid?>" onclick="fb_removeAlbum('<?=$aid?>');" class="button">
                                      </td>
                                  </tr>
                              </table>
                            </td>
                          </tr>
                        </table>
                </span>
                <?php
                }}
                if ($y==0) {
                ?>
                <p><strong>No albums selected.</strong> If you don't select any albums, all albums will be included.</p>
                <?php
                }
                ?>         
            </span>
            </td>
        </tr>
      </table>
      <?php
    $this->print_foot();
  }
  
  function wpfbg_minifeed() {
    $this->print_head('You can use this page to configure what and how do you want to post to your Facebook mini-feed.<br /><br />
Available tags are: %posturl%,%postname%,%category%,%blogname%, %postauthor%, %commentauthor%<br/><br />
You should know that because of Facebook limitations all mini-feed posts must start with your Facebook name, no need to include it... it will be dona automaticly by Facebook<br /><br />
Be warned, you can only send 10 post/comment notifications per 48h to your Facebook mini-feed. If you reach this limit blog will function normally but notifications will not be displayed on your Facebook mini-feed. (this is Facebook limitation)', 'Mini-Feed');
    ?>
    <form method="post" action="../wp-content/plugins/ferdinand-wordbook/mf_action.php">
      <input name="_wp_http_referer" value="../../../wp-admin/admin.php?page=wpfbg_minifeed" type="hidden">
      <table class="form-table">
      <tbody>
        <tr valign="top">
          <th scope="row">Mini-Feed Items</th>
          <td> <fieldset><legend class="hidden">Items</legend>
          <label for="mfposts">
          <input name="mfposts" id="mfposts" type="checkbox" onclick="mfposts_toggle()" <?php if(get_option('facebook_minifeed_posts')=='Y') {?>checked="checked"<?php }?>>
          Send new posts to Mini-Feed</label><br>
          <label for="mfeposts">
          <input name="mfeposts" id="mfeposts" type="checkbox" onclick="mfeposts_toggle()" <?php if(get_option('facebook_minifeed_edit_posts')=='Y') {?>checked="checked"<?php }?>>
          Send edited posts to Mini-Feed</label><br>
          <label for="mfcomment">
          <input name="mfcomment" id="mfcomment" type="checkbox" onclick="mfcomment_toggle()" <?php if(get_option('facebook_minifeed_comments')=='Y') {?>checked="checked"<?php }?>>
          Send approved comments to Mini-Feed</label>
          </fieldset></td>
        </tr>
        <tr valign="top" id="mf-post-format" <?php if(get_option('facebook_minifeed_posts')!='Y') {?>style="display:none"<? }?>>
          <th scope="row"><label for="post-format">Post Format</label></th>
          <td><input name="post-format" id="post-format" value="<?php echo get_option('facebook_minifeed_postformat'); ?>" size="100" type="text"></td>
          </tr>

        <tr valign="top" id="mf-edit-post-format" <?php if(get_option('facebook_minifeed_edit_posts')!='Y') {?>style="display:none"<? }?>>
          <th scope="row"><label for="edit-post-format">Edit Post Format</label></th>
          <td><input name="edit-post-format" id="edit-post-format" value="<?php echo get_option('facebook_minifeed_edit_postformat'); ?>" size="100" type="text"></td>
          </tr>

          <tr valign="top" id="mf-comment-format" <?php if(get_option('facebook_minifeed_comments')!='Y') {?>style="display:none"<? }?>>
          <th scope="row"><label for="comment-format">Comment Format</label></th>
          <td><input name="comment-format" id="comment-format" value="<?php echo get_option('facebook_minifeed_commentformat');?>" size="100" type="text"></td>
        </tr>
      </tbody>
      </table>
      <p class="submit">
      <input name="Submit" value="Save Changes" type="submit">
      </p>
    </form>
    <?php
    $this->print_foot();
  }

	function wpfbg_identify() {
      $this->print_head('Use this page to identify your Facebook account.<br /><br />Go to Facebook and <strong>logout</strong>. Go back here and click "Login to Facebook", popup will appear. Use that window to login to your Facebook account <strong>(be sure to check Save my login info just below password input field)</strong>. After Facebook popup tells you to close the window... do so. You should now see button "Confirm Facebook Login", click it... all done. Now your account is identified.', 'Facebook Account Setup');
			if (!get_option('facebook_session_key'))
			{
		?>
			<?php
				$this->get_token();
				$facebook_token = get_option('facebook_token');
			?>
			<div id="fbloginbtn">
				<input class="button" onclick="open_fbwin('<?=$facebook_token?>');return false;" value="Login to Facebook" type="button">
				<div id="fbloader_fbloginbtn" style="display:none"><img src="images/loading.gif"> Working...</div>			
			</div>			
			<div style="display:none" id="confirmfblogin">
				<p>After you authorize Wordbook application and close the window, click below to confirm.</p>			
				<span id="fbloader_confirmfblogin" style="display:none"><img src="images/loading.gif"></span>
				<input value="Confirm Facebook Login" type="button" class="button" onclick="confirm_fblogin('<?=$facebook_token?>');">
							
			</div>
			</p>
			
		<?php
			} // end if, check for session key
			else
			{
			$session_key = get_option('facebook_session_key');
			$uid = get_option('facebook_uid');
			$secret = get_option('facebook_secret');
			$response = $this->curl_get_contents("getUserInfo.php?session_key=$session_key&uid=$uid&secret=$secret");
			$json = new Services_JSON();
			$ob = $json->decode($response);			
		?>
				<h3>Facebook: <?=$ob->name?></h3>
				<div id="fbuser_accountbox">
				<img class="profilepic" src="<?=$ob->pic?>" alt="<?=$ob->name?> Profile Picture">
				<div>You have identified your Facebook account!</div>
				<div class="buttonbar">
				<span id="fbloader_removeaccount" style="display:none"><img src="images/loading.gif"></span>
					<input type="button" value="Remove account" class="button" onclick="fbaccount_remove();"> 
				</div>
				<div class="clear"></div>
				</div>
		<?php

			}
    		$this->print_foot();
	}
	  
	function print_blogjs($wpheader=true) {
		?>
		<link rel="stylesheet" type="text/css" href="<?=get_option('home')?>/wp-content/plugins/ferdinand-wordbook/lightview/css/lightview.css" />
		<link rel="stylesheet" type="text/css" href="<?=get_option('home')?>/wp-content/plugins/ferdinand-wordbook/prototip/css/prototip.css" />
		<link rel="stylesheet" type="text/css" href="<?=get_option('home')?>/wp-content/plugins/ferdinand-wordbook/common/fbstyle.css" />
		<?php
		if ($wpheader==true)
		{
		//wp_enqueue_script('prototype');
		//wp_enqueue_script('scriptaculous-effects');
		wp_enqueue_script('prototype1602','/wp-content/plugins/ferdinand-wordbook/common/prototype.js');
		wp_enqueue_script('efects','/wp-content/plugins/ferdinand-wordbook/common/scriptaculous.js?load=effects');
		wp_enqueue_script('lightview2','/wp-content/plugins/ferdinand-wordbook/lightview/js/lightview.js');
		wp_enqueue_script('prototip2','/wp-content/plugins/ferdinand-wordbook/prototip/js/prototip.js');
		}
		else 
		{
		?>
		<script type="text/javascript" src="<?=get_option('home')?>/wp-content/plugins/ferdinand-wordbook/common/prototype.js"></script>
		<script type="text/javascript" src="<?=get_option('home')?>/wp-content/plugins/ferdinand-wordbook/common/scriptaculous.js?load=effects"></script>
		<script type="text/javascript" src="<?=get_option('home')?>/wp-content/plugins/ferdinand-wordbook/lightview/js/lightview.js"></script>
		<script type="text/javascript" src="<?=get_option('home')?>/wp-content/plugins/ferdinand-wordbook/prototip/js/prototip.js"></script>
		<?	
		}
	}


	function print_adminjs() {
		?>
		<link rel='stylesheet' href='../wp-content/plugins/ferdinand-wordbook/common/style.css' type='text/css' media='all' />
		<?php
		wp_enqueue_script('prototype1602','/wp-content/plugins/ferdinand-wordbook/common/prototype.js');
		?>
		<script>

		function mfposts_toggle() {
		          if ($('mfposts').checked==true) $('mf-post-format').show();
		          else $('mf-post-format').hide();
		}
		function mfeposts_toggle() {
		          if ($('mfeposts').checked==true) $('mf-edit-post-format').show();
		          else $('mf-edit-post-format').hide();
		}
	      	function mfcomment_toggle() {
		          if ($('mfcomment').checked==true) $('mf-comment-format').show();
		          else $('mf-comment-format').hide();
	      	}
     	
		function open_fbwin(token) {
				window.open('http://www.facebook.com/login.php?api_key=<?=$this->api_key?>&v=1.0&auth_token='+token,null,
				    		"height=400,width=660,status=yes,toolbar=no,menubar=no,location=no");
				$('fbloginbtn').hide();
				login_fbconfirm(token);
			}
			function login_fbconfirm(token) {
				$('fbloginbtn').hide();
				$('confirmfblogin').show();
			}
			function confirm_fblogin(token) {
				$('fbloader_confirmfblogin').show();
				new Ajax.Request('../wp-content/plugins/ferdinand-wordbook/ajax/getSession.php?token='+token, {
				  method:'get',
				  requestHeaders: {Accept: 'application/json'},
				  onSuccess: function(transport){
				    saving = transport.responseText;

				    if (!saving.isJSON()) {
					alert('Error: returned string is not JSON. Script sad:\n'+saving);
				    }

				    var json = transport.responseText.evalJSON(true);

					if (!json.error || json.error=='') {
						$('fbloader_confirmfblogin').hide();
						fbreload_page();
						return true;
					} else {
						$('fbloader_confirmfblogin').hide();
						alert(json.error);
						return false;
					}
				  },
				    onFailure: function(t) {
					alert('Error: '+t.responseText);
				    }

				});
			}
			function fbaccount_remove() {
				if (!confirm("If you remove this account all data associated with it will be removed.\nAre you sure?")) return true;
				$('fbloader_removeaccount').show();
				new Ajax.Request('../wp-content/plugins/ferdinand-wordbook/ajax/fbaccount_remove.php', {
				  method:'get',
				  onSuccess: function(transport){
					fbreload_page();
				  }
				});
			}
			function fbreload_page() {
				location.reload(true);
			}
			function fb_selectAlbum(aid) {
			  var aid = aid;
			  albumSpan = $('album-'+aid).innerHTML;

				new Ajax.Request('../wp-content/plugins/ferdinand-wordbook/ajax/addAlbum.php?aid='+aid, {
				  method:'get',
				  requestHeaders: {Accept: 'application/json'},
				  onSuccess: function(transport){
				  }
				});

			  $('album-'+aid).remove();
			$('fb_selected').innerHTML += '<span id="album-'+aid+'">'+albumSpan+'</span>';
			$('action-'+aid).hide();
			$('raction-'+aid).show();
		      }

			function fb_changeStatus() {
				newStatus = $('fbstatus').value;
				new Ajax.Request('../wp-content/plugins/ferdinand-wordbook/ajax/changeStatus.php?ns='+newStatus, {
				  method:'get',
				  requestHeaders: {Accept: 'application/json'},
				  onSuccess: function(transport){
					fbreload_page();
				  }
				});
			}

		      function fb_removeAlbum(aid) {
		       
			var aid = aid;
					  albumSpan = $('album-'+aid).innerHTML;
						new Ajax.Request('../wp-content/plugins/ferdinand-wordbook/ajax/removeAlbum.php?aid='+aid, {
						  method:'get',
						  requestHeaders: {Accept: 'application/json'},
						  onSuccess: function(transport){
						  }
						});
					  $('album-'+aid).remove();
			$('fb_notselected').innerHTML = '<span id="album-'+aid+'">'+albumSpan+'</span>' + $('fb_notselected').innerHTML;
			$('raction-'+aid).hide();
			$('action-'+aid).show();  
		      }
		     function request_quote() {
			$('fbloader_rquote').show();
			pt = $('project_type');
			ptv = pt[pt.selectedIndex].value;
			var prms = 'project_type='+ptv+'&other_hidden='+$('other_msg').value+'&cms_hidden='+$('cms_msg').value+'&deadline='+$('deadline').value+'&email='+$('email').value+'&name='+$('name').value;
				new Ajax.Request('../wp-content/plugins/ferdinand-wordbook/ajax/rquote.php', {
				  method:'post',
				  postBody: prms,
				  requestHeaders: {Accept: 'application/json'},
				  onSuccess: function(transport){
					$('fbloader_rquote').hide();
				  }
				});			
			}
		</script>
		<?
	}
  function addAlbum($aid) {
      $json = new Services_JSON();
      if (!get_option('facebook_albums')) {
          $albums[] = $aid;
          $albums = $json->encode($albums);          
          add_option("facebook_albums", $albums, '', 'yes');
      } elseif (trim(get_option('facebook_albums'))=='') {
          $albums[] = $aid;
          $albums = $json->encode($albums);          
          update_option("facebook_albums", $albums, '', 'yes');     
      }else {
          $albums = get_option('facebook_albums');
          $albums = $json->decode($albums);
          foreach($albums as $i => $album) {
            if($album==$aid) return false;
          }
          $albums[] = $aid;
          $albums = $json->encode($albums);
          update_option("facebook_albums", $albums, '', 'yes');
      }
  }
  function removeAlbum($aid) {
      $json = new Services_JSON();
      $albums = get_option('facebook_albums');
      $albums = $json->decode($albums);
      foreach($albums as $i => $album) {
        if($album!=$aid) { 
          $albumsx[] = $albums[$i];
        }
      }
      $albums = $json->encode($albumsx);
      update_option("facebook_albums", $albums, '', 'yes');
  }
  

  function fb_statusWidget() {
	$session_key = get_option('facebook_session_key');
	$uid = get_option('facebook_uid');
	$secret = get_option('facebook_secret');
	$response = $this->curl_get_contents("getUserInfo.php?session_key=$session_key&uid=$uid&secret=$secret");
	$json = new Services_JSON();
	$ob = $json->decode($response);	
	if ($ob->status->message=='' || !$ob->status->message) return false;
	?>
		<h3 class="fbstatus_title">Facebook Status</h3>
		<strong class="fbstatus_text"><?=$ob->status->message?></strong>
		<span class="fbstatus_time"><?=ucwords(ezDate($ob->status->time)).' Ago';?></span>
	</ul>
	<?php
  }

  function fb_galleryWidget() {
      $json = new Services_JSON();

      $photos = $json->decode(trim($this->fb_formWidgetPicsArr()));

      $columns = (int)get_option('facebook_gallery_widget_colums');
      $rows = (int)get_option('facebook_gallery_widget_rows');
      $spacing = get_option('facebook_gallery_widget_spacing');
      $padding = get_option('facebook_gallery_widget_padding');
      $title = get_option('facebook_gallery_widget_title');
      $class = get_option('facebook_gallery_widget_class');
      $height = get_option('facebook_gallery_widget_height');
      $width = get_option('facebook_gallery_widget_width');
      $table = '<h3>'.$title.'</h3><table class="'.$class.'" cellspacing="'.$spacing.'" cellpadding="'.$padding.'">';
      $last = -1;

      foreach($photos as $key=>$photo) {
          
          if ($key>$last) {
		$table .= '<tr>';
            for($y=0;$y<$columns;$y++) {
		unset($myarr);
		$myarr['imgsrc']=$photos[$key+$y]->src_big;
		$myarr['id'] = $key+$y;
		$photos[$key+$y]->src_small = get_option('home').'/wp-content/plugins/ferdinand-wordbook/phpthumb/phpThumb.php?src='.$photos[$key+$y]->src.'&w='.$width.'&h='.$height.'&zc=1"';
		if ($photos[$key+$y]->coords){
		foreach($photos[$key+$y]->coords as $coord) {
			$myarr['xcoord'][] = $coord->xcoord;
			$myarr['ycoord'][] = $coord->ycoord;
			$myarr['caption'][] = $coord->text;
		}
		}
		$s = @getimagesize($photos[$key+$y]->src_big);
		$v = urlencode($json->encode($myarr));
		if ($photos[$key+$y]->src) {
                $table .= '
		<td valign="top">
		        <a rel="iframe" title=\''.$photos[$key+$y]->caption.' :: :: width: '.$s[0].', height: '.$s[1].', topclose: true\' href="'.get_option('home').'/wp-content/plugins/ferdinand-wordbook/mapimg.php?v='.$v.'" class="lightview">
			<img class="fbwidget" alt="'.$photos[$key+$y]->caption.'" title="'.$photos[$key+$y]->caption.'" src="'.$photos[$key+$y]->src_small.'">
			</a>           
		</td>';
		} else {
			$table .='<tr></tr>';
		}
                $last = $key+$y;
		
            }
		$table .= '</tr>';
          }
          
      }
      $table .= '</table>';
      echo $table;
  }
  
  function fb_formWidgetPicsArr() {
    if (get_option('facebook_albums')=='null' || !get_option('facebook_albums')) {
        $post_var=-1;
    } else {
      	$post_var = get_option('facebook_albums');
    }
      $limit = (int)get_option('facebook_gallery_widget_colums')*(int)get_option('facebook_gallery_widget_rows');
			$session_key = get_option('facebook_session_key');
			$uid = get_option('facebook_uid');
			$secret = get_option('facebook_secret');   

      $post_vars =array(
            'json' => $post_var,
            'limit'=>$limit,
            'session_key' => $session_key,
            'uid' => $uid,
            'secret' => $secret
          );
      return $this->curl_get_contents("getPhotos.php", $post_vars);
  }
  
	function getSession($token) {
		$json = new Services_JSON();
		//die($this->app_url."getSession.php?token=$token");
		$response = trim($this->curl_get_contents("getSession.php?token=$token"));
		$arr = $json->decode($response);
		if ((int)$arr->expires!=0) {
			return $json->encode(array("error"=>"There has been an error\nPlease fallow these steps:\n1. Logout from Facebook\n2. Click Add FB Photobook in Wordpress Admin Panel\n3. In popup window login to Facebook, IMPORTANT: check checkbox to allow Facebook to remember you\n4. Add FB Photobook\n5. Close Facebook popup window\n6.Click \"Confirm Facebook Login\" button"));
		} else {
			if (!get_option('facebook_session_key')) 
			{
				add_option("facebook_session_key", $arr->session_key, '', 'yes');
			} else {
				update_option("facebook_session_key", $arr->session_key, '', 'yes');
			}
			if (!get_option('facebook_uid')) 
			{
				add_option("facebook_uid", $arr->uid, '', 'yes');
			} else {
				update_option("facebook_uid", $arr->uid, '', 'yes');
			}	
			if (!get_option('facebook_secret')) 
			{
				add_option("facebook_secret", $arr->secret, '', 'yes');
			} else {
				update_option("facebook_secret", $arr->secret, '', 'yes');
			}
			$this->install();
		    return $json->encode($arr);
		}
	}
	
	function post2minifeed($post_id) {
	    global $wpdb;
	      $post = get_post($post_id, OBJECT);

	      $user = get_userdata($post->post_author);
	      $terminfo = $wpdb->get_results("SELECT term_id
		                          FROM ".$wpdb->term_relationships.", ".$wpdb->term_taxonomy."
		                          WHERE object_ID = ".$post_id." 
		                          AND ".$wpdb->term_taxonomy.".term_taxonomy_ID = ".$wpdb->term_relationships.".term_taxonomy_ID
		                          ORDER BY term_ID LIMIT 1");
	      $termrecord = $terminfo[0];
	      $category = get_category($termrecord->term_id,OBJECT);


	       $permalink = get_permalink($post_id);
          
		 $mf_postslug = get_option('facebook_minifeed_postformat');
	     
	      $replacements = array($permalink, $post->post_title, $category->category_nicename, get_bloginfo(), $user->user_nicename, '');
	      $slug = str_replace($this->mf_tags, $replacements, $mf_postslug);
	      
	      $bbcode = new bbcode(); 
	      $bbcode->add_tag(array('Name'=>'link','HasParam'=>true,'HtmlBegin'=>'<a href="%%P%%">','HtmlEnd'=>'</a>')); 
	      $bbcode->add_alias('url','link');
	      $slug_final = $bbcode->parse_bbcode($slug);
	    //  die(print_r($slug_final));
				$session_key = get_option('facebook_session_key');
				$uid = get_option('facebook_uid');
				$secret = get_option('facebook_secret');   

	      	$post_vars =array(
		    'slug' => $slug_final,
		    'session_key' => $session_key,
		    'uid' => $uid,
		    'secret' => $secret
		  );
		$out = $this->base_curl("post2mf.php", $post_vars);	
		//die("Content: ".$out);
  }
	function send2feed($new_status, $old_status, $post) {
		global $wpdb;


		if ($new_status == 'publish' && get_option('facebook_minifeed_posts')=='Y') {
			$this->post2minifeed($post->ID);
			return true;
		} elseif ($new_status == 'inherit' && get_option('facebook_minifeed_edit_posts')=='Y') {
			$this->epost2minifeed($post->ID);
			return true;
		} else {
			return false;
		}
	}

	function epost2minifeed($post_id) {
	    global $wpdb;
	      $post = get_post($post_id, OBJECT);

	      $user = get_userdata($post->post_author);
	      $terminfo = $wpdb->get_results("SELECT term_id
		                          FROM ".$wpdb->term_relationships.", ".$wpdb->term_taxonomy."
		                          WHERE object_ID = ".$post_id." 
		                          AND ".$wpdb->term_taxonomy.".term_taxonomy_ID = ".$wpdb->term_relationships.".term_taxonomy_ID
		                          ORDER BY term_ID LIMIT 1");
	      $termrecord = $terminfo[0];
	      $category = get_category($termrecord->term_id,OBJECT);


	      $permalink = get_permalink($post_id);

		 $mf_postslug = get_option('facebook_minifeed_edit_postformat');

	      $replacements = array($permalink, $post->post_title, $category->category_nicename, get_bloginfo(), $user->user_nicename, '');
	      $slug = str_replace($this->mf_tags, $replacements, $mf_postslug);
	      $bbcode = new bbcode(); 
	      $bbcode->add_tag(array('Name'=>'link','HasParam'=>true,'HtmlBegin'=>'<a href="%%P%%">','HtmlEnd'=>'</a>')); 
	      $bbcode->add_alias('url','link');
	      $slug_final = $bbcode->parse_bbcode($slug);
	      
				$session_key = get_option('facebook_session_key');
				$uid = get_option('facebook_uid');
				$secret = get_option('facebook_secret');   

	      	$post_vars =array(
		    'slug' => $slug_final,
		    'session_key' => $session_key,
		    'uid' => $uid,
		    'secret' => $secret
		  );
		$out = $this->base_curl("post2mf.php", $post_vars);	
		//die("Content: ".$out);
  }
	function comment2minifeed($comment_id, $status) {
	    global $wpdb;

	    if ($status!=1) return false;
	    $comment = get_comment($comment_id);
	    $post_id = $comment->comment_post_ID;
      $post = get_post($post_id, OBJECT);
      $user = get_userdata($post->post_author);

      $terminfo = $wpdb->get_results("SELECT term_id
                                  FROM $wpdb->term_relationships, $wpdb->term_taxonomy
                                  WHERE object_ID = $post_id 
                                  AND $wpdb->term_taxonomy.term_taxonomy_ID = $wpdb->term_relationships.term_taxonomy_ID
                                  ORDER BY term_ID LIMIT 1");
      $termrecord = $terminfo[0];
      $category = get_category($termrecord->term_id,OBJECT);

      $permalink = get_permalink($post_id);
     
      $mf_postslug = get_option('facebook_minifeed_commentformat');
      $replacements = array($permalink, $post->post_title, $category->category_nicename, get_bloginfo(), $user->user_nicename, $comment->comment_author);

      $slug = str_replace($this->mf_tags, $replacements, $mf_postslug);
      $bbcode = new bbcode(); 
      $bbcode->add_tag(array('Name'=>'link','HasParam'=>true,'HtmlBegin'=>'<a href="%%P%%">','HtmlEnd'=>'</a>')); 
      $bbcode->add_alias('url','link');
      $slug_final = $bbcode->parse_bbcode($slug);
      
			$session_key = get_option('facebook_session_key');
			$uid = get_option('facebook_uid');
			$secret = get_option('facebook_secret');   

	$this->curl_get_contents("comment2mf.php", $post_vars);

      $post_vars =array(
            'slug' => $slug_final,
            'session_key' => $session_key,
            'uid' => $uid,
            'secret' => $secret
          );
	$out = $this->base_curl("post2mf.php", $post_vars);
  }

	function commentApproved($comment_id, $status) {
		global $wpdb;
		if ($status == 'approve') {
			$this->comment2minifeed($comment_id, 1);
		}
	}


	/*function friends_comment($comment_id, $status) {
		if ($status==1) return true;

		$comment = get_comment($comment_id);
		$session_key = get_option('facebook_session_key');
		$uid = get_option('facebook_uid');
		$secret = get_option('facebook_secret');   

		$post_vars =array(
            	'email' => $comment_author_email,
            	'session_key' => $session_key,
            	'uid' => $uid,
            	'secret' => $secret
          	);

		echo "?";
		$out = $this->curl_get_contents("is_friend.php", $post_vars);

		print_r($out);
		die();
	}*/

	function curl_get_contents($url, $post_vars=array()) {

		$cache_object = &new file_cache_class;
		$cache_object->path = dirname(__FILE__).'/cache/'.md5($url.implode('',$post_vars)).'.cache';
		$success=$cache_object->updating($updating);
		if (!file_exists($cache_object->path)) {
			$h = fopen($cache_object->path, 'x+');
			fclose($h);
		}
		if($success) {
			if ($updating) {
				$out = $this->base_curl($url, $post_vars);
			} else {
				$success=$cache_object->verifycache($updated);
				if($success) { // is cache free to access
					if($updated) { //is cache up-to-date
						$endofcache=0;
						for(;!$endofcache;)
						{
							$success=$cache_object->retrievefromcache($out,$endofcache);
							if(!($success)) {
								break;
							}
						}
					} else {
						$cache_object->setexpirytime($this->cachetimeout);
						$out = $this->base_curl($url, $post_vars);
						$success=$cache_object->storedata($out,1);
						if (!$success) {
							//echo $cache_object->error;
						}
					}
				}
			}
		}
		if ($cache_object->error && $this->debug==1) {
			echo $cache_object->error;
		}
		return $out;
	}

	function base_curl($url, $post_vars=array()) {
		//echo $this->app_url.$url;
		ob_start();
		      $ch = curl_init();
		      curl_setopt($ch, CURLOPT_URL, $this->app_url.$url);
		if (sizeof($post_vars)!=0) {
		      curl_setopt($ch, CURLOPT_POST, 1);
		      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_vars);
		}
		ob_end_clean();	
		ob_start();
		      
		      curl_exec($ch);
		      $out = ob_get_contents();
		      ob_end_clean();
		      curl_close ($ch);
		      return $out;
	}


	function map_Start($image_filename,$id) {
		echo "<div id=\"lv$id\">";
		echo "<img src='$image_filename' border='0' usemap='#mapname'>\n<map name='mapname'>\n";
	}
	function map_End() {
		echo "</map>\n";
		echo "</div>";
	}

	function map_Entry($text, $x, $y, $width, $height, $id) {
		$text = addslashes($text);
		$x = $x - 70;
		$y = $y - 70;
		$x2 = $x + 150;
		$y2 = $y + 150;
		echo "<area id='$id$x$y' shape='rect' coords='$x,$y,$x2,$y2' href='#' onclick='return false'>\n";
		echo "<script>new Tip('$id$x$y', '$text', {className: 'fbtip'});</script>\n";     

	}
} // end main class
require_once('facebook.php');
?>
