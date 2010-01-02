<?php
/*
Plugin Name: Wordbooker
Plugin URI: http://blogs.canalplan.org.uk/steve/wordbook/
Description: Provides integration between your blog and your Facebook account. Navigate to <a href="admin.php?page=wordbook">Settings &rarr; Wordbook</a> for configuration.
Author: Steve Atty 
Author URI: http://blogs.canalplan.org.uk/steve/
Version: 1.1
*/

 /*
 * Based on the Wordbook plugin by Robert Tsai (http://www.tsaiberspace.net/projects/wordpress/wordbook/ )
 *
 *
 * Copyright 2010 Steve Atty (email : posty@tty.org.uk)
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc., 51
 * Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

global $table_prefix, $wp_version;
require_once(ABSPATH . WPINC . '/pluggable.php');

$wordbook_settings = get_option('wordbook_settings'); 
if (! isset($wordbook_settings['wordbook_extract_length'])) $wordbook_settings['wordbook_extract_length']=256;

define('WORDBOOK_DEBUG', true);
define('WORDBOOK_TESTING', false);

$facebook_config['debug'] = WORDBOOK_TESTING && !$_POST['action'];

define('WORDBOOK_FB_APIKEY', '0cbf13c858237f5d74ef0c32a4db11fd');
define('WORDBOOK_FB_SECRET', 'df04f22f3239fb75bf787f440e726f31');
define('WORDBOOK_FB_APIVERSION', '1.0');
define('WORDBOOK_FB_DOCPREFIX','http://wiki.developers.facebook.com/index.php/');
define('WORDBOOK_FB_MAXACTIONLEN', 60);
define('WORDBOOK_FB_PUBLISH_STREAM', 'publish_stream');
define('WORDBOOK_FB_READ_STREAM', 'read_stream');
define('WORDBOOK_SETTINGS', 'wordbook_settings');
define('WORDBOOK_OPTION_SCHEMAVERS', 'schemavers');

define('WORDBOOK_ERRORLOGS', $table_prefix . 'wordbook_errorlogs');
define('WORDBOOK_POSTLOGS', $table_prefix . 'wordbook_postlogs');
define('WORDBOOK_USERDATA', $table_prefix . 'wordbook_userdata');
define('WORDBOOK_POSTCOMMENTS', $table_prefix . 'wordbook_postcomments');

define('WORDBOOK_EXCERPT_SHORTSTORY', $wordbook_settings['wordbook_extract_length']); 
define('WORDBOOK_EXCERPT_WIDEBOX', 96);
define('WORDBOOK_EXCERPT_NARROWBOX', 40);

define('WORDBOOK_MINIMUM_ADMIN_LEVEL', 2);	/* Author role or above. */
define('WORDBOOK_SETTINGS_PAGENAME', 'wordbooker');
define('WORDBOOK_SETTINGS_URL', 'admin.php?page=' . WORDBOOK_SETTINGS_PAGENAME);

define('WORDBOOK_SCHEMA_VERSION', 1);

$wordbook_wp_version_tuple = explode('.', $wp_version);
define('WORDBOOK_WP_VERSION', $wordbook_wp_version_tuple[0] * 10 +
	$wordbook_wp_version_tuple[1]);

if (function_exists('json_encode')) {
	define('WORDBOOK_JSON_ENCODE', 'PHP');
} else {
	define('WORDBOOK_JSON_ENCODE', 'Wordbook');
}

if (function_exists('simplexml_load_string')) {
	define('WORDBOOK_SIMPLEXML', 'PHP');
} else {
	define('WORDBOOK_SIMPLEXML', 'Facebook');
}

if (substr(phpversion(), 0, 2) == '5.') {
	define('FACEBOOK_PHP_API', 'PHP5');
} else {
	define('FACEBOOK_PHP_API', 'PHP4');
}

function wordbook_debug($message) {
	if (WORDBOOK_DEBUG) {
		$fp = fopen('/tmp/wb.log', 'a');
		$date = date('D M j, g:i:s a');
		fwrite($fp, "$date: $message");
		fclose($fp);
	}
}

function wordbook_load_apis() {
	if (defined('WORDBOOK_APIS_LOADED')) {
		return;
	}
	if (WORDBOOK_JSON_ENCODE == 'Wordbook') {
		function json_encode($var) {
			if (is_array($var)) {
				$encoded = '{';
				$first = true;
				foreach ($var as $key => $value) {
					if (!$first) {
						$encoded .= ',';
					} else {
						$first = false;
					}
					$encoded .= "\"$key\":"
						. json_encode($value);
				}
				$encoded .= '}';
				return $encoded;
			}
			if (is_string($var)) {
				return "\"$var\"";
			}
			return $var;
		}
	}

		if (!class_exists('Facebook')) {
			/* Defend against other plugins. */
			require_once('facebook-platform/php/facebook.php');
		}


	define('WORDBOOK_APIS_LOADED', true);
}


function wordbook_rest_client($secret, $session_key) {
	return new FacebookRestClient(WORDBOOK_FB_APIKEY, $secret,
		$session_key);
}

function wordbook_fbclient_setfbml_impl($fbclient, $text) {
	try {
		$result = $fbclient->profile_setFBML(null, null, $text,
			null, null, $text);
		$error_code = null;
		$error_msg = null;
	} catch (Exception $e) {
		$result = null;
		$error_code = $e->getCode();
		$error_msg = $e->getMessage();
	}
	return array($result, $error_code, $error_msg);
}

function wordbook_fbclient_publishaction_impl($fbclient, $post_data) {
	try {
		$method = 'stream.publish';
		$message=$_POST["wordbook_attribution"];
		$post_data['post_excerpt']=str_replace('&#8220;', '"',$post_data['post_excerpt']);
		$post_data['post_excerpt']=str_replace('&#8221;', '"',$post_data['post_excerpt']);
		$post_data['post_excerpt']=str_replace('&#8243;', '"',$post_data['post_excerpt']);
		$post_data['post_excerpt']=str_replace('&#8216;', "'",$post_data['post_excerpt']);
		$post_data['post_excerpt']=str_replace('&#8217;', "'",$post_data['post_excerpt']);
		$post_data['post_excerpt']=str_replace('&#8242;', "'",$post_data['post_excerpt']);
		$post_data['post_excerpt']=str_replace('&#8211;', "-",$post_data['post_excerpt']);
		$post_data['post_excerpt']=str_replace('&#8230;', "...",$post_data['post_excerpt']);

		$post_data['post_title']=str_replace('&#8220;', '"',$post_data['post_title']);
		$post_data['post_title']=str_replace('&#8220;', '"',$post_data['post_title']);
		$post_data['post_title']=str_replace('&#8243;', '"',$post_data['post_title']);
		$post_data['post_title']=str_replace('&#8216;', "'",$post_data['post_title']);
		$post_data['post_title']=str_replace('&#8217;', "'",$post_data['post_title']);
		$post_data['post_title']=str_replace('&#8242;', "'",$post_data['post_title']);
		$post_data['post_title']=str_replace('&#8211;', "-",$post_data['post_title']);
		$post_data['post_title']=str_replace('&#8230;', "...",$post_data['post_title']);
		
		# The following handle some character conversions which might be needed for some people.		
		#$post_data['post_excerpt']=mb_convert_encoding($post_data['post_excerpt'], 'UTF-8', 'HTML-ENTITIES');
		#$post_data['post_title']=mb_convert_encoding($post_data['post_title'], 'UTF-8', 'HTML-ENTITIES');
		
		# Converts latin diacritics into two letters. This includes umlauted letters.
    		#$post_data['post_excerpt']= preg_replace(array('/&szlig;/','/&(..)lig;/','/&([aouAOU])uml;/','/&(.)[^;]*;/'),array('ss',"$1","$1".'e',"$1"), $post_data['post_excerpt']);

		$attachment =  array(
      		  'name' => $post_data['post_title'],
      		  'href' => $post_data['post_link'],
      		  'description' => $post_data['post_excerpt'],
       		  'media' => $post_data['media']
		);
		$action_links = array(array(
			'text' => 'Read entire article',
			'href' => $post_data['post_link']
			));
		if ($_POST["wordbook_page_post"]==-100) {
			$result = $fbclient->stream_publish($message,json_encode($attachment), json_encode($action_links));
		} else {
			#$result = $fbclient->stream_publish($message, json_encode($attachment), json_encode($action_links), $_POST["wordbook_page_post"],$_POST["wordbook_page_post"]);
			$result = $fbclient->stream_publish($message, json_encode($attachment), json_encode($action_links), $_POST["wordbook_page_post"]);
		}

	} catch (Exception $e) {
		$error_code = $e->getCode();
		$error_msg = $e->getMessage();
	}
	return array($result, $error_code, $error_msg, $method);
}

function wordbook_fbclient_getinfo($fbclient, $fields) {
	try {
		$uid = $fbclient->users_getLoggedInUser();
		#echo "UID = ".$uid." !! <br>";
		$users = $fbclient->users_getInfo(array($uid), $fields);
		$error_code = null;
		$error_msg = null;
	} catch (Exception $e) {
		$uid = null;
		$users = null;
		$error_code = $e->getCode();
		$error_msg = $e->getMessage();
	}
	return array($uid, $users, $error_code, $error_msg);
}

function wordbook_fbclient_has_app_permission($fbclient, $ext_perm) {
	try {
		$uid = $fbclient->users_getLoggedInUser();
		$has_permission = $fbclient->call_method(
			'facebook.users.hasAppPermission', array(
				'uid' => $uid,
				'ext_perm' => $ext_perm,
			));
		$error_code = null;
		$error_msg = null;
	} catch (Exception $e) {
		$has_permission = null;
		$error_code = $e->getCode();
		$error_msg = $e->getMessage();
	}
	return array($has_permission, $error_code, $error_msg);
}

function wordbook_fbclient_getsession($fbclient, $token) {
	try {
		$result = $fbclient->auth_getSession($token);
		$error_code = null;
		$error_msg = null;
	} catch (Exception $e) {
		$result = null;
		$error_code = $e->getCode();
		$error_msg = $e->getMessage();
	}
	return array($result, $error_code, $error_msg);
}


/******************************************************************************
 * Wordbook options.
 */

function wordbook_options() {
	return get_option(WORDBOOK_SETTINGS);
}

function wordbook_set_options($options) {
	update_option(WORDBOOK_SETTINGS, $options);
}

function wordbook_get_option($key) {
	$options = wordbook_options();
	return isset($options[$key]) ? $options[$key] : null;
}

function wordbook_set_option($key, $value) {
	$options = wordbook_options();
	$options[$key] = $value;
	wordbook_set_options($options);
}

function wordbook_delete_option($key) {
	$options = wordbook_options();
	unset($options[$key]);
	update_option(WORDBOOK_SETTINGS, $options);
}

/******************************************************************************
 * Plugin deactivation - tidy up database.
 */

function wordbook_deactivate() {
	global $wpdb;
	#wp_cache_flush();
	$errors = array();
	foreach (array(
			WORDBOOK_ERRORLOGS,
			WORDBOOK_POSTLOGS,
			WORDBOOK_USERDATA,
			WORDBOOK_POSTCOMMENTS,
			) as $tablename) {
		$result = $wpdb->query("
			DROP TABLE IF EXISTS $tablename
			");
		if ($result === false)
			$errors[] = "Failed to drop $tablename";
	}
	delete_option(WORDBOOK_SETTINGS);
	delete_option('wordbook_settings');
	wp_clear_scheduled_hook('wb_cron_job');

	#wp_cache_flush();

	if ($errors) {
		echo '<div id="message" class="updated fade">' . "\n";
		foreach ($errors as $errormsg) {
			_e("$errormsg<br />\n");
		}
		echo "</div>\n";
	}
wp_cache_flush();
}

/******************************************************************************
 * DB schema.
 */

function wordbook_upgrade() {
	global $wpdb, $table_prefix;

	$options = wordbook_options();

	if ($options && isset($options[WORDBOOK_OPTION_SCHEMAVERS]) &&
			$options[WORDBOOK_OPTION_SCHEMAVERS] ==
			WORDBOOK_SCHEMA_VERSION) {
		return;
	}

	wp_cache_flush();

	if (!$options || !isset($options[WORDBOOK_OPTION_SCHEMAVERS]) ||
			$options[WORDBOOK_OPTION_SCHEMAVERS] < 1) {
		$errors = array();

		foreach (array(
				WORDBOOK_ERRORLOGS,
				WORDBOOK_POSTLOGS,
				WORDBOOK_USERDATA,
				WORDBOOK_POSTCOMMENTS,
				$table_prefix . 'wordbook_onetimecode',
				) as $tablename) {
		#	echo "Drop ".$tablename."<br>";
			$result = $wpdb->query("
				DROP TABLE IF EXISTS $tablename
				");
			if ($result === false)
				$errors[] = "Failed to drop $tablename";
		}

		$result = $wpdb->query('
			CREATE TABLE ' . WORDBOOK_POSTLOGS . ' (
				`postid` BIGINT(20) NOT NULL
				, `timestamp` TIMESTAMP
			)
			');
		if ($result === false)
			$errors[] = 'Failed to create ' . WORDBOOK_POSTLOGS;

		$result = $wpdb->query('
			CREATE TABLE ' . WORDBOOK_ERRORLOGS . ' (
				`timestamp` TIMESTAMP
				, `user_ID` BIGINT(20) UNSIGNED NOT NULL
				, `method` VARCHAR(255) NOT NULL
				, `error_code` INT NOT NULL
				, `error_msg` VARCHAR(80) NOT NULL
				, `postid` BIGINT(20) NOT NULL
			)
			');
		if ($result === false)
			$errors[] = 'Failed to create ' . WORDBOOK_ERRORLOGS;

		$result = $wpdb->query('
			CREATE TABLE ' . WORDBOOK_USERDATA . ' (
				`user_ID` BIGINT(20) UNSIGNED NOT NULL
				, `use_facebook` TINYINT(1) NOT NULL DEFAULT 1
				, `onetime_data` LONGTEXT NOT NULL
				, `facebook_error` LONGTEXT NOT NULL
				, `secret` VARCHAR(80) NOT NULL
				, `session_key` VARCHAR(80) NOT NULL
			)
			');
		if ($result === false)
			$errors[] = 'Failed to create ' . WORDBOOK_USERDATA;

		$result = $wpdb->query('
			CREATE TABLE ' . WORDBOOK_POSTCOMMENTS . ' (
			  `fb_post_id` varchar(40) NOT NULL,
  			  `comment_timestamp` int(20) NOT NULL,
  			  `wp_post_id` int(11) NOT NULL,
  			   UNIQUE KEY `fb_comment_id` (`fb_post_id`,`wp_post_id`)
			)
			');
		if ($result === false)
			$errors[] = 'Failed to create ' . WORDBOOK_POSTCOMMENTS;

		if ($errors) {
			echo '<div id="message" class="updated fade">' . "\n";
			foreach ($errors as $errormsg) {
				_e("$errormsg<br />\n");
			}
			echo "</div>\n";
			return;
		}

		$options = array(
			WORDBOOK_OPTION_SCHEMAVERS => 1,
			);
	}

	wordbook_set_options($options);
	wp_cache_flush();
}

function wordbook_delete_user($user_id) {
	global $wpdb;
	$errors = array();
	foreach (array(
			WORDBOOK_USERDATA,
			WORDBOOK_ERRORLOGS,
			) as $tablename) {
		$result = $wpdb->query('
			DELETE FROM ' . $tablename . '
			WHERE user_ID = ' . $user_id . '
			');
		if ($result === false)
			$errors[] = "Failed to remove user $user_id from $tablename";
	}
	if ($errors) {
		echo '<div id="message" class="updated fade">' . "\n";
		foreach ($errors as $errormsg) {
			_e("$errormsg<br />\n");
		}
		echo "</div>\n";
	}
}

/******************************************************************************
 * Wordbook user data.
 */

function wordbook_get_userdata($user_id) {
	global $wpdb;

	$rows = $wpdb->get_results('
		SELECT *
		FROM ' . WORDBOOK_USERDATA . '
		WHERE user_ID = ' . $user_id . '
		');
	if ($rows) {
		$rows[0]->onetime_data = unserialize($rows[0]->onetime_data);
		$rows[0]->facebook_error =
			unserialize($rows[0]->facebook_error);
		$rows[0]->secret = unserialize($rows[0]->secret);
		$rows[0]->session_key = unserialize($rows[0]->session_key);
		return $rows[0];
	}
	return null;
}

function wordbook_set_userdata($use_facebook, $onetime_data, $facebook_error,
		$secret, $session_key) {
	global $user_ID, $wpdb;
	wordbook_delete_userdata();
	$result = $wpdb->query("
		INSERT INTO " . WORDBOOK_USERDATA . " (
			user_ID
			, use_facebook
			, onetime_data
			, facebook_error
			, secret
			, session_key
		) VALUES (
			" . $user_ID . "
			, " . ($use_facebook ? 1 : 0) . "
			, '" . serialize($onetime_data) . "'
			, '" . serialize($facebook_error) . "'
			, '" . serialize($secret) . "'
			, '" . serialize($session_key) . "'
		)
		");
}

function wordbook_set_userdata2($use_facebook, $onetime_data, $facebook_error,
		$secret, $session_key) {
	global $user_ID, $wpdb;
	#wordbook_delete_userdata();
	$sql= "Update " . WORDBOOK_USERDATA . " set
		          use_facebook = " . ($use_facebook ? 1 : 0) . "
			, onetime_data =  '" . serialize($onetime_data) . "'
			, facebook_error = '" . serialize($facebook_error) . "'
			, secret = '" . serialize($secret) . "'
			, session_key = '" . serialize($session_key) . "'
		 where user_id=".$user_ID;
	#echo "!!".$sql."KK";
$result = $wpdb->query($sql);
}


function wordbook_update_userdata($wbuser) {
	return wordbook_set_userdata2($wbuser->use_facebook,
		$wbuser->onetime_data, $wbuser->facebook_error, $wbuser->secret,
		$wbuser->session_key);
}

function wordbook_set_userdata_facebook_error($wbuser, $method, $error_code,
		$error_msg, $postid) {
	$wbuser->facebook_error = array(
		'method' => $method,
		'error_code' => mysql_real_escape_string ($error_code),
		'error_msg' => mysql_real_escape_string ($error_msg),
		'postid' => $postid,
		);
	wordbook_update_userdata($wbuser);
	wordbook_appendto_errorlogs($method, $error_code, $error_msg, $postid);
}

function wordbook_clear_userdata_facebook_error($wbuser) {
	$wbuser->facebook_error = null;
	return wordbook_update_userdata($wbuser);
}

function wordbook_delete_userdata() {
	global $user_ID;
	wordbook_delete_user($user_ID);
}

/******************************************************************************
 * Post logs - record time of last post to Facebook
 */

function wordbook_trim_postlogs() {
	/* Forget that something has been posted to Facebook if it's been
	 * longer than some delta of time. */
	global $wpdb;
	$wordbook_settings =get_option('wordbook_settings'); 
	if (! isset($wordbook_settings['wordbook_republish_time_frame'])) $wordbook_settings['wordbook_republish_time_frame']='10';
	$result = $wpdb->query('
		DELETE FROM ' . WORDBOOK_POSTLOGS . '
		WHERE timestamp < DATE_SUB(CURDATE(), INTERVAL '.$wordbook_settings["wordbook_republish_time_frame"].' DAY)
		');
}

function wordbook_postlogged($postid) {
	global $wpdb;
	#var_dump($_POST);
	// See if the user has overridden the repost on edit - i.e. they want to publish and be damned!
	if (isset ($_POST["wordbook_publish_overridden"])) { return false;}
	$wordbook_settings =get_option('wordbook_settings'); 
	#var_dump($wordbook_settings);
	// Does the user want us to ever publish on Edit? If not then return ture
	if ( (! isset($wordbook_settings["wordbook_republish_time_obey"])) && ($_POST['original_post_status']=='publish')) { return true;}
	#echo "Past the republish check";
	if (! isset($wordbook_settings['wordbook_republish_time_frame'])) $wordbook_settings['wordbook_republish_time_frame']='10';
	$rows = $wpdb->get_results('
		SELECT *
		FROM ' . WORDBOOK_POSTLOGS . '
		WHERE postid = ' . $postid . '
			AND timestamp > DATE_SUB(CURDATE(), INTERVAL '.$wordbook_settings["wordbook_republish_time_frame"].' DAY)
		');
	return $rows ? true : false;
}

function wordbook_insertinto_postlogs($postid) {
	global $wpdb;
	wordbook_deletefrom_postlogs($postid);
	if (!WORDBOOK_TESTING) {
		$result = $wpdb->query('
			INSERT INTO ' . WORDBOOK_POSTLOGS . ' (
				postid
			) VALUES (
				' . $postid . '
			)
			');
	}
}

function wordbook_deletefrom_postlogs($postid) {
	global $wpdb;
	$result = $wpdb->query('
		DELETE FROM ' . WORDBOOK_POSTLOGS . '
		WHERE postid = ' . $postid . '
		');
}

function wordbook_deletefrom_commentlogs($postid) {
	global $wpdb;
	$result = $wpdb->query('
		DELETE FROM ' . WORDBOOK_POSTCOMMENTS . '
		WHERE wp_post_id = ' . $postid . '
		');
}

/******************************************************************************
 * Error logs - record errors
 */

function wordbook_hyperlinked_method($method) {
	return '<a href="'
		. WORDBOOK_FB_DOCPREFIX . $method . '"'
		. ' title="Facebook API documentation" target="facebook"'
		. '>'
		. $method
		. '</a>';
}

function wordbook_trim_errorlogs() {
	global $wpdb;
	$result = $wpdb->query('
		DELETE FROM ' . WORDBOOK_ERRORLOGS . '
		WHERE timestamp < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
		');
}

function wordbook_clear_errorlogs() {
	global $user_ID, $wpdb;
	$result = $wpdb->query('
		DELETE FROM ' . WORDBOOK_ERRORLOGS . '
		WHERE user_ID = ' . $user_ID . '
		');
	if ($result === false) {
		echo '<div id="message" class="updated fade">';
		_e('Failed to clear error logs.');
		echo "</div>\n";
	}
}

function wordbook_appendto_errorlogs($method, $error_code, $error_msg,
		$postid) {
	global $user_ID, $wpdb;
	if ($postid == null) {
		$postid = 0;
		$user_id = $user_ID;
	} else {
		$post = get_post($postid);
		$user_id = $post->post_author;
	}
	#echo "!!".$error_code."!!<br>";
	#echo "LLL".$error_msg."KKK<br>";
		$result = $wpdb->insert(WORDBOOK_ERRORLOGS,
			array('user_ID' => $user_id,
				'method' => $method,
				'error_code' => $error_code,
				'error_msg' => $error_msg,
				'postid' => $postid,
				),
			array('%d', '%s', '%d', '%s', '%d')
			);
	 
}

function wordbook_deletefrom_errorlogs($postid) {
	global $wpdb;
	$result = $wpdb->query('
		DELETE FROM ' . WORDBOOK_ERRORLOGS . '
		WHERE postid = ' . $postid . '
		');
}

function wordbook_render_errorlogs() {
	global $user_ID, $wpdb;

	$rows = $wpdb->get_results('
		SELECT *
		FROM ' . WORDBOOK_ERRORLOGS . '
		WHERE user_ID = ' . $user_ID . '
		ORDER BY timestamp
		');
	if ($rows) {
?>

	<h3><?php _e('Errors'); ?></h3>
	<div class="wordbook_errors">

	<p>
	Your blog is OK, but Wordbook was unable to update your Mini-Feed:
	</p>

	<table class="wordbook_errorlogs">
		<tr>
			<th>Timestamp</th>
			<th>Post</th>
			<th>Method</th>
			<th>Error Code</th>
			<th>Error Message</th>
		</tr>

<?php
		foreach ($rows as $row) {
			$hyperlinked_post = '';
			if (($post = get_post($row->postid))) {
				$hyperlinked_post = '<a href="'
					. get_permalink($row->postid) . '">'
					. get_the_title($row->postid) . '</a>';
			}
			$hyperlinked_method=
				wordbook_hyperlinked_method($row->method);
?>

		<tr>
			<td><?php echo $row->timestamp; ?></td>
			<td><?php echo $hyperlinked_post; ?></td>
			<td><?php echo $hyperlinked_method; ?></td>
			<td><?php echo $row->error_code; ?></td>
			<td><?php echo $row->error_msg; ?></td>
		</tr>

<?php
		}
?>

	</table>

	<form action="<?php echo WORDBOOK_SETTINGS_URL; ?>" method="post">
		<input type="hidden" name="action" value="clear_errorlogs" />
		<p class="submit" style="text-align: center;">
		<input type="submit" value="<?php _e('Clear Errors'); ?>" />
		</p>
	</form>

	</div>

<?php
	}
}
/******************************************************************************
 * Wordbook setup and administration.
 */

function wordbook_admin_load() {
	if (!$_POST['action'])
		return;

	switch ($_POST['action']) {

	case 'one_time_code':
		$token = $_POST['one_time_code'];
		$fbclient = wordbook_fbclient(null);
		list($result, $error_code, $error_msg) =
			wordbook_fbclient_getsession($fbclient, $token);
		if ($result) {
			wordbook_clear_errorlogs();
			$onetime_data = null;
			$secret = $result['secret'];
			$session_key = $result['session_key'];
		} else {
			$onetime_data = array(
				'onetimecode' => $token,
				'error_code' => $error_code,
				'error_msg' => $error_msg,
				);
			$secret = null;
			$session_key = null;
		}
		$use_facebook = true;
		$facebook_error = null;
		wordbook_set_userdata($use_facebook, $onetime_data,
			$facebook_error, $secret, $session_key);
		wp_redirect(WORDBOOK_SETTINGS_URL);
		break;

	case 'delete_userdata':
		wordbook_delete_userdata();
		wp_redirect(WORDBOOK_SETTINGS_URL);
		break;

	case 'clear_errorlogs':
		wordbook_clear_errorlogs();
		wp_redirect(WORDBOOK_SETTINGS_URL);
		break;

	case 'no_facebook':
		wordbook_set_userdata(false, null, null, null);
		wp_redirect(WORDBOOK_SETTINGS_URL);
		break;
	}

	exit;
}

function wordbook_admin_head() {
?>
	<style type="text/css">
	.wordbook_setup { margin: 0 3em; }
	.wordbook_notices { margin: 0 3em; }
	.wordbook_status { margin: 0 3em; }
	.wordbook_errors { margin: 0 3em; }
	.wordbook_thanks { margin: 0 3em; }
	.wordbook_thanks ul { margin: 1em 0 1em 2em; list-style-type: disc; }
	.wordbook_support { margin: 0 3em; }
	.wordbook_support ul { margin: 1em 0 1em 2em; list-style-type: disc; }
	.facebook_picture {
		float: right;
		border: 1px solid black;
		padding: 2px;
		margin: 0 0 1ex 2ex;
	}
	.wordbook_errorcolor { color: #c00; }
	table.wordbook_errorlogs { text-align: center; }
	table.wordbook_errorlogs th, table.wordbook_errorlogs td {
		padding: 0.5ex 1.5em;
	}
	table.wordbook_errorlogs th { background-color: #999; }
	table.wordbook_errorlogs td { background-color: #f66; }
	</style>
<?php
}

function wordbook_option_notices() {
	global $user_ID, $wp_version;
	wordbook_upgrade();
	wordbook_trim_postlogs();
	wordbook_trim_errorlogs();
	$errormsg = null;
	if (WORDBOOK_WP_VERSION < 27) {
		$errormsg = sprintf(__('Wordbooker requires'
			. ' <a href="%s">WordPress</a>-2.7'
			. ' or newer (you appear to be running version %s).'),
			'http://wordpress.org/download/', $wp_version);
	} else if (!($options = wordbook_options()) ||
			!isset($options[WORDBOOK_OPTION_SCHEMAVERS]) ||
			$options[WORDBOOK_OPTION_SCHEMAVERS] <
			WORDBOOK_SCHEMA_VERSION ||
			!($wbuser = wordbook_get_userdata($user_ID)) ||
			($wbuser->use_facebook && !$wbuser->session_key)) {
		$errormsg = sprintf(__('<a href="%s">Wordbooker</a>'
			. ' needs to be set up.'),
			WORDBOOK_SETTINGS_URL);
	} else if ($wbuser->facebook_error) {
		$method = $wbuser->facebook_error['method'];
		$error_code = $wbuser->facebook_error['error_code'];
		$error_msg = $wbuser->facebook_error['error_msg'];
		$postid = $wbuser->facebook_error['postid'];
		$suffix = '';
		if ($postid != null && ($post = get_post($postid))) {
			wordbook_deletefrom_postlogs($postid);
			$suffix = ' for <a href="'
				. get_permalink($postid) . '">'
				. get_the_title($postid) . '</a>';
		}
		$errormsg = sprintf(__('<a href="%s">Wordbooker</a>'
			. ' failed to communicate with Facebook' . $suffix . ':'
			. ' method = %s, error_code = %d (%s).'
			. " Your blog is OK, but Facebook didn't get"
			. ' the update.'),
			WORDBOOK_SETTINGS_URL,
			wordbook_hyperlinked_method($method),
			$error_code,
			$error_msg);
		wordbook_clear_userdata_facebook_error($wbuser);
	}

	if ($errormsg) {
?>

	<h3><?php _e('Notices'); ?></h3>

	<div class="wordbook_notices" style="background-color: #f66;">
	<p><?php echo $errormsg; ?></p>
	</div>

<?php
	}
}

function wordbook_option_setup($wbuser) {
?>

	<h3><?php _e('Setup'); ?></h3>
	<div class="wordbook_setup">

	<p>Wordbooker needs to be linked to your Facebook account. This link will be used to publish your WordPress blog updates to your wall, and will not be used for any other purpose.</p>

	<p>First, log in to your Facebook account to generate a one-time code. Record the one-time code and return to this page:</p>

	<div style="text-align: center;"><a href="http://www.facebook.com/code_gen.php?v=<?php echo WORDBOOK_FB_APIVERSION; ?>&api_key=<?php echo WORDBOOK_FB_APIKEY; ?>" target="facebook"><img src="http://static.ak.facebook.com/images/devsite/facebook_login.gif" /></a></div>

	<form action="<?php echo WORDBOOK_SETTINGS_URL; ?>" method="post">
		<p>Next, enter the one-time code obtained in the previous step:</p>
		<div style="text-align: center;">
		<input type="text" name="one_time_code" id="one_time_code"
			value="<?php echo $wbuser->onetime_data['onetimecode']; ?>" size="9" />
		</div>
		<input type="hidden" name="action" value="one_time_code" />

<?php
		if ($wbuser) {
			wordbook_render_onetimeerror($wbuser);
			$wbuser->onetime_data = null;
			wordbook_update_userdata($wbuser);
		}
?>

		<p style="text-align: center;"><input type="submit" value="<?php _e('Submit &raquo;'); ?>" /></p>
	</form>

	<form action="<?php echo WORDBOOK_SETTINGS_URL; ?>" method="post">
		<p>Or, if you don't use Facebook or don't want to post to Facebook:</p>
		<input type="hidden" name="action" value="no_facebook" />
		<p style="text-align: center;"><input type="submit" value="<?php _e('I don\'t want to use Facebook &raquo;'); ?>" /></p>
	</form>

	</div>

<?php
}

function wordbook_option_status($wbuser) {
global  $wpdb;
?>

	<h3><?php _e('Status'); ?></h3>
	<div class="wordbook_status">

<?php

	$show_paypal = false;
	$fbclient = wordbook_fbclient($wbuser);
	#var_dump($wbuser);
	#echo "<br><br><br>";
	#var_dump ($fbclient);
	list($fbuid, $users, $error_code, $error_msg) =
		wordbook_fbclient_getinfo($fbclient, array(
			'is_app_user',
			'first_name',
			'name',
			'status',
			'pic',
			));
	$profile_url = "http://www.facebook.com/profile.php?id=$fbuid"; 

	if ($fbuid) {
		if (is_array($users)) {
			$user = $users[0];

			if ($user['pic']) {
?>

		<div class="facebook_picture">
		<a href="<?php echo $profile_url; ?>" target="facebook">
		<img src="<?php echo $user['pic']; ?>" /></a>
		</div>

<?php
			}

			if (!($name = $user['first_name']))
				$name = $user['name'];

			if ($user['status']['message']) {
?>

		<p>
		<a href="<?php echo $profile_url; ?>"><?php echo $name; ?></a>
		<i><?php echo $user['status']['message']; ?></i>
		(<?php echo date('D M j, g:i a', $user['status']['time']); ?>).
		</p>

<?php
			} else {
?>

		<p>
		Hi,
		<a href="<?php echo $profile_url; ?>"><?php echo $name; ?></a>!
		</p>

<?php
			}


			if ($user['is_app_user']) {
		#		$show_paypal = true;
			#	wordbook_fbclient_setfbml($wbuser, $fbclient,null, null);
?>

		<p>Wordbooker appears to be configured and working just fine.</p>


<?php
				list($has_permission, $error_code, $error_msg) =
					wordbook_fbclient_has_app_permission(
					$fbclient, WORDBOOK_FB_PUBLISH_STREAM);
				if (!$has_permission) {
?>

		<p>
		Wordbooker requires authorization to publish content on Facebook. Click on the following link to grant permission
		</p>
		<div style="text-align: center;">
		<a href="http://www.facebook.com/authorize.php?v=<?php echo WORDBOOK_FB_APIVERSION; ?>&api_key=<?php echo WORDBOOK_FB_APIKEY; ?>&ext_perm=<?php echo WORDBOOK_FB_PUBLISH_STREAM; ?>" target="facebook">
		<img src="http://static.ak.facebook.com/images/devsite/facebook_login.gif" />
		</a><br>
		</div>

<?php
				}


				list($has_permission, $error_code, $error_msg) =
					wordbook_fbclient_has_app_permission(
					$fbclient, "read_stream");
				if (!$has_permission) {
?>

		<p>
		Wordbooker requires authorization to access your News Feed and Wall.. Click on the following link to grant permission
		</p>
		<div style="text-align: center;">

		<a href="http://www.facebook.com/authorize.php?v=<?php echo WORDBOOK_FB_APIVERSION; ?>&api_key=<?php echo WORDBOOK_FB_APIKEY; ?>&ext_perm=<?php echo WORDBOOK_FB_READ_STREAM; ?>" target="facebook">
		<img src="http://static.ak.facebook.com/images/devsite/facebook_login.gif" />
		</a><br>

		</div>

		<form action="<?php echo WORDBOOK_SETTINGS_URL; ?>" method="post">
			<input type="hidden" name="action" value="" />
			<p style="text-align: center;"><input type="submit" value="<?php _e('Save Configuration'); ?>" /></p>
		</form>

<?php
}
			
?>	
		<p>If you like, you can start over from the beginning (this does not delete your posting and comment history):</p>

<?php
			} else {
?>

		<p>Wordbooker is able to connect to Facebook.</p>

		<p>Next, add the <a href="http://www.facebook.com/apps/application.php?id=254577506873" target="facebook">Wordbooker</a> application to your Facebook profile:</p>

		<div style="text-align: center;"><a href="http://www.facebook.com/add.php?api_key=<?php echo WORDBOOK_FB_APIKEY; ?>" target="facebook"><img src="http://static.ak.facebook.com/images/devsite/facebook_login.gif" /></a></div>

		<p>Or, you can start over from the beginning:</p>

<?php
			}
		} else {
?>

		<p>Wordbooker is configured and working, but <a href="http://developers.facebook.com/documentation.php?v=1.0&method=users.getInfo" target="facebook">facebook.users.getInfo</a> failed (no Facebook user for uid <?php echo $fbuid; ?>).</p>

		<p>Try resetting the configuration:</p>

<?php
		}
	} else {
?>

		<p>Failed to communicate with Facebook: <a href="http://developers.facebook.com/documentation.php?v=1.0&method=users.getLoggedInUser" target="facebook">error_code = <?php echo $error_code; ?> (<?php echo $error_msg; ?>)</a>.</p>
		
		<p>Try resetting the configuration:</p>

<?php
	}
?>

		<form action="<?php echo WORDBOOK_SETTINGS_URL; ?>" method="post">
			<input type="hidden" name="action" value="delete_userdata" />
			<p style="text-align: center;"><input type="submit" value="<?php _e('Reset Configuration'); ?>" /></p>
		</form>

	</div>

<?php
	return array($show_paypal);
}

function wordbook_version_ok($currentvers, $minimumvers) {
	$current = preg_split('/\D+/', $currentvers);
	$minimum = preg_split('/\D+/', $minimumvers);
	for ($ii = 0; $ii < min(count($current), count($minimum)); $ii++) {
		if ($current[$ii] < $minimum[$ii])
			return false;
	}
	if (count($current) < count($minimum))
		return false;
	return true;
}


function wordbook_option_support() {
	global $wp_version;
?>

	<h3><?php _e('Support'); ?></h3>
	<div class="wordbook_support">

	For feature requests, bug reports, and general support:
	
	<ul>
	
		<li>Check the <a
		href="http://wordpress.org/extend/plugins/wordbooker/other_notes/"
		target="wordpress">WordPress.org Notes</a>.</li>
		
		<li>Try the <a
		href="http://www.facebook.com/board.php?uid=254577506873"
		target="facebook">Wordbooker Discussion Board</a>.</li>

		<li>Consider upgrading to the <a
		href="http://wordpress.org/download/">latest stable release</a>
		of WordPress.</li>
		
	</ul>
	
	Please provide the following information about your installation:

	<ul>
<?php

	$wb_version = 'Unknown';
	if (($wordbook_php = file(__FILE__)) &&
			(($versionlines = array_values(preg_grep('/^Version:/',
			$wordbook_php)))) &&
			(($versionstrs = explode(':', $versionlines[0]))) &&
			count($versionstrs) >= 2) {
		$wb_version = trim($versionstrs[1]);
	}

	$phpvers = phpversion();
	$mysqlvers = function_exists('mysql_get_client_info') ?
		 mysql_get_client_info() :
		 'Unknown';

	$info = array(
		'Wordbook' => $wb_version,
		'Facebook PHP API' => FACEBOOK_PHP_API,
		'JSON library' => WORDBOOK_JSON_ENCODE,
		'SimpleXML library' => WORDBOOK_SIMPLEXML,
		'WordPress' => $wp_version,
		'PHP' => $phpvers,
		'MySQL' => $mysqlvers,
		);

	$version_errors = array();
	$phpminvers = '5.0';
	$mysqlminvers = '4.0';
	if (!wordbook_version_ok($phpvers, $phpminvers)) {
		/* PHP-5.0 or greater. */
		$version_errors['PHP'] = $phpminvers;
	}
	if ($mysqlvers != 'Unknown' &&
			!wordbook_version_ok($mysqlvers, $mysqlminvers)) {
		/* MySQL-4.0 or greater. */
		$version_errors['MySQL'] = $mysqlminvers;
	}

	foreach ($info as $key => $value) {
		$suffix = '';
		if (($minvers = $version_errors[$key])) {
			$suffix = " <span class=\"wordbook_errorcolor\">"
				. " (need $key version $minvers or greater)"
				. " </span>";
		}
		echo "<li>$key: <b>$value</b>$suffix</li>";
	}
	if (!function_exists('simplexml_load_string')) {
		echo "<li>XML: your PHP is missing <code>simplexml_load_string()</code></li>";
	}
?>
	</ul>

<?php
	if ($version_errors) {
?>

	<div class="wordbook_errorcolor">
	Your system does not meet the <a
	href="http://wordpress.org/about/requirements/">WordPress minimum
	reqirements</a>. Things are unlikely to work.
	</div>

<?php
	} else if ($mysqlvers == 'Unknown') {
?>

	<div>
	Please ensure that your system meets the <a
	href="http://wordpress.org/about/requirements/">WordPress minimum
	reqirements</a>.
	</div>

<?php
	}
?>
	</div>

<?php
}


function wordbook_admin_menu() {
	$hook = add_options_page('Wordbook Option Manager', 'Wordbooker',
		WORDBOOK_MINIMUM_ADMIN_LEVEL, WORDBOOK_SETTINGS_PAGENAME,
		'wordbook_option_manager');
	add_action("load-$hook", 'wordbook_admin_load');
	add_action("admin_head-$hook", 'wordbook_admin_head');
}

/******************************************************************************
 * One-time code (Facebook)
 */

function wordbook_render_onetimeerror($wbuser) {
$result = $wbuser->onetime_data;
#var_dump($wbuser);
#echo "<br>";
#var_dump($result);

	if (($result = $wbuser->onetime_data)) {
?>

	<p>There was a problem with the one-time code "<?php echo $result['onetimecode']; ?>": <a href="http://developers.facebook.com/documentation.php?v=1.0&method=auth.getSession" target="facebook">error_code = <?php echo $result['error_code']; ?> (<?php echo $result['error_msg']; ?>)</a>. Try re-submitting it, or try generating a new one-time code.</p>

<?php
	}
}

/******************************************************************************
 * Facebook API wrappers.
 */

function wordbook_fbclient($wbuser) {
	wordbook_load_apis();
	$secret = null;
	$session_key = null;
	if ($wbuser) {
		$secret = $wbuser->secret;
		$session_key = $wbuser->session_key;
	}
	if (!$secret)
		$secret = WORDBOOK_FB_SECRET;
	if (!$session_key)
		$session_key = '';
	return wordbook_rest_client($secret, $session_key);
}

function wordbook_fbclient_facebook_finish($wbuser, $result, $method,
		$error_code, $error_msg, $postid) {
	if ($error_code) {
		wordbook_set_userdata_facebook_error($wbuser, $method,
			$error_code, $error_msg, $postid);
	} else {
		wordbook_clear_userdata_facebook_error($wbuser);
	}
	return $result;
}

function wordbook_fbclient_setfbml($wbuser, $fbclient, $postid,
		$exclude_postid) {
	list($result, $error_code, $error_msg) = wordbook_fbclient_setfbml_impl(
		$fbclient, wordbook_fbmltext($exclude_postid));
	return wordbook_fbclient_facebook_finish($wbuser, $result,
		'profile.setFBML', $error_code, $error_msg, $postid);
}

function wordbook_fbclient_publishaction($wbuser, $fbuid, $fbname, $fbclient,
		$postid) {
	$post = get_post($postid);
	$post_link = get_permalink($postid);
	$post_title = get_the_title($postid);
	$post_content = $post->post_content;

/* Pull out images from <img/> tags. */
	preg_match_all('/<img \s+ ([^>]*\s+)? src \s* = \s* "(.*?)"/ix',
		$post_content, $matches);
	$images = array();
	foreach ($matches[2] as $ii => $imgsrc) {
		if ($imgsrc) {
			if (stristr(substr($imgsrc, 0, 8), '://') ===
					false) {
				/* Fully-qualify src URL if necessary. */
				$scheme = $_SERVER['HTTPS'] ? 'https' : 'http';
				$new_imgsrc = "$scheme://"
					. $_SERVER['SERVER_NAME'];
				if ($imgsrc[0] == '/') {
					$new_imgsrc .= $imgsrc;
				}
				$imgsrc = $new_imgsrc;
			}
			$images[] = array(
				'type' => 'image', 
				'src' => $imgsrc,
				'href' => $post_link,
				);
		}
	}

	/* Pull out <wpg2> image tags. */
	$wpg2_g2path = get_option('wpg2_g2paths');
	if ($wpg2_g2path) {
		$g2embeduri = $wpg2_g2path['g2_embeduri'];
		if ($g2embeduri) {
			preg_match_all('/<wpg2>(.*?)</ix', $post_content,
				$wpg_matches);
			foreach ($wpg_matches[1] as $wpgtag) {
				if ($wpgtag) {
					$images[] = array(
						'src' => $g2embeduri
							. '?g2_view='
							. 'core.DownloadItem'
							. "&g2_itemId=$wpgtag",
						'href' => $post_link,
						);
				}
			}
		}
	}
$wordbook_settings =get_option('wordbook_settings'); 

#$media = array_map('wordbook_fbclient_image_as_media', $images);
	$post_data = array(
		'media' => $images,
		'post_link' => $post_link,
		'post_title' => $post_title,
		'post_excerpt' => wordbook_post_excerpt($post_content,
			WORDBOOK_EXCERPT_SHORTSTORY),
		'post_attribute' => $_POST["wordbook_attribution"],
		);
	list($result, $error_code, $error_msg, $method) = wordbook_fbclient_publishaction_impl($fbclient, $post_data);
	return wordbook_fbclient_facebook_finish($wbuser, $result,
		$method, $error_code, $error_msg, $postid);
}

/******************************************************************************
 * WordPress hooks: update Facebook when a blog entry gets published.
 */

function wordbook_post_excerpt($content, $maxlength) {
	
	$excerpt = apply_filters('the_excerpt', $content);
	if (function_exists('strip_shortcodes')) {
		$excerpt = strip_shortcodes($excerpt);
	}
	#$excerpt = strip_tags($excerpt,'<p></p>');
	$excerpt = strip_tags($excerpt);
	if (strlen($excerpt) > $maxlength) {
	$excerpt=current(explode("\r", wordwrap($excerpt, $maxlength, "\r")))." ...";	
	}
	return $excerpt;
}

function wordbook_fbmltext($exclude_postid) {
	/* Set the Wordbook box to contain a summary of the blog front page
	 * (just those posts written by this user). Don't show
	 * password-protected posts. */
	global $user_ID, $user_identity, $user_login, $wpdb;

	$blog_link = get_bloginfo('url');
	$blog_name = get_bloginfo('name');
	$blog_atitle = '';
	if (($blog_description = get_bloginfo('description'))) {
		$blog_atitle = $blog_description;
	} else {
		$blog_atitle = $blog_name;
	}
	$author_link = "$blog_link/author/$user_login/";
	$text = <<<EOM
<style>
  td { vertical-align: top; }
  td.time { text-align: right; padding-right: 1ex; }
</style>
<fb:subtitle>
  Blog posts from <a href="$blog_link" title="$blog_atitle" target="$blog_name">$blog_name</a>
</fb:subtitle>
EOM;

# Backed up copy of the Subtitle   Blog posts from <a href="$author_link" title="$user_identity's posts at $blog_name" target="$blog_name">$user_identity</a> at <a href="$blog_link" title="$blog_atitle" target="$blog_name">$blog_name</a>


	$posts_per_page = get_option('posts_per_page');
	if ($posts_per_page <= 0) {
		$posts_per_page = 10;
	}
	$exclude_postid_selector = $exclude_postid == null ? "" :
		"AND ID != $exclude_postid";
	$author_restriction = " AND post_author =  $user_ID ";
	$postidrows = $wpdb->get_results("
		SELECT ID
		FROM $wpdb->posts
		WHERE post_type = 'post'
			AND post_status = 'publish'
			AND post_password = ''
			$exclude_postid_selector
		ORDER BY post_date DESC
		LIMIT $posts_per_page
		");

	$postid = 0;
        if ($postidrows) {
		$postid = $postidrows[0]->ID;
		$text .= <<<EOM
<div class="minifeed clearfix">
  <table>
EOM;
		foreach ($postidrows as $postidrow) {
			$post = get_post($postidrow->ID);
			$post_link = get_permalink($postidrow->ID);
			$post_title = get_the_title($postidrow->ID);
			$post_date_gmt = strtotime($post->post_date);
			$post_excerpt_wide = wordbook_post_excerpt(
				$post->post_content, WORDBOOK_EXCERPT_WIDEBOX);
			$post_excerpt_narrow = wordbook_post_excerpt(
				$post->post_content,
				WORDBOOK_EXCERPT_NARROWBOX);
			$text .= <<<EOM
    <tr>
      <td class="time">
	<span class="date">
	  <fb:time t="$post_date_gmt" />
	</span>
      </td>
      <td>
	<a href="$post_link" target="$blog_name">$post_title</a>:
	<fb:wide>$post_excerpt_wide</fb:wide>
	<fb:narrow>$post_excerpt_narrow</fb:narrow>
      </td>
    </tr>
EOM;
		}
		$text .= <<<EOM
  </table>
</div>
EOM;
	} else {
		$text .= "I haven't posted anything (yet).";
	}

	return $text;
}

function wordbook_publish_action($post) {
global $user_ID, $user_identity, $user_login, $wpdb;
	wordbook_deletefrom_errorlogs($post->ID);
	if ($post->post_password != '') {
		/* Don't publish password-protected posts to news feed. */
		return 27;
	}
	if (!($wbuser = wordbook_get_userdata($_POST["wordbook_default_author_override"])) ||
			!$wbuser->session_key) {
		return 28;
	}
	/* If publishing a new blog post, update text in "Wordbook" box. */

	$fbclient = wordbook_fbclient($wbuser);
	# This posts to the profile box which will be obosolete from early 2010.
	if ($post->post_type == 'post' && !wordbook_fbclient_setfbml($wbuser,
			$fbclient, $post->ID, null)) {
		return 29;
	}
	if (!wordbook_postlogged($post->ID)) {
		list($fbuid, $users, $error_code, $error_msg) =
			wordbook_fbclient_getinfo($fbclient, array('name'));
		if ($fbuid && is_array($users) && ($user = $users[0])) {
			$fbname = $user['name'];
		} else {
			$fbname = 'A friend';
		}

		# Lets see if they want to update their status. We do it this way so you can update your status without publishing!
		if( $_POST["wordbook_status_update_override"]=="on"){ 
			$status_text = $_POST['wordbook_status_update_text_override']." ".$post->post_title." - ".get_permalink($post->ID)."  ";
			$fbclient->users_setStatus($status_text);
		}
	// User has unchecked the publish to facebook option so lets just give up and go home
	$wbpda=$_POST["wordbook_publish_default_action"];
	if ($wbpda!="on") {return;}
		$results=wordbook_fbclient_publishaction($wbuser, $fbuid, $fbname,$fbclient, $post->ID);
		wordbook_insertinto_postlogs($post->ID);
		$fb_post_id=$results;
		// Has the user decided to collect comments for this post?
		if( isset($_POST["wordbook_comment_overridden"])){	
			$sql=	' INSERT INTO ' . WORDBOOK_POSTCOMMENTS . ' (fb_post_id,comment_timestamp,wp_post_id) VALUES ("'.$fb_post_id.'",'.time().','.$post->ID.')';;
			$result = $wpdb->query($sql);
		}
	}

	return 30;
}

function wordbook_transition_post_status($newstatus, $oldstatus, $post) {
	#echo $newstatus;
	#var_dump($_POST);
	if ($newstatus == 'publish') {
		return wordbook_publish_action($post);
	}

	$postid = $post->ID;
	if (($wbuser = wordbook_get_userdata($post->post_author)) &&
			$wbuser->session_key) {
		$fbclient = wordbook_fbclient($wbuser);
		list($result, $error_code, $error_msg) =
			wordbook_fbclient_setfbml($wbuser, $fbclient, $postid,
			$postid);
	}
return 31;	
}

function wordbook_delete_post($postid) {
	$post = get_post($postid);
	if (($wbuser = wordbook_get_userdata($post->post_author)) &&
			$wbuser->session_key) {
		$fbclient = wordbook_fbclient($wbuser);
		list($result, $error_code, $error_msg) =
			wordbook_fbclient_setfbml($wbuser, $fbclient, $postid,
			$postid);
	}
	wordbook_deletefrom_errorlogs($postid);
	wordbook_deletefrom_postlogs($postid);
	wordbook_deletefrom_commentlogs($postid);
}

/******************************************************************************
 * Register hooks with WordPress.
 */

/* Plugin maintenance. */
register_deactivation_hook(__FILE__, 'wordbook_deactivate');
add_action('delete_user', 'wordbook_delete_user');
if (current_user_can(WORDBOOK_MINIMUM_ADMIN_LEVEL)) {
	add_action('admin_menu', 'wordbook_admin_menu');
}

/* Post/page maintenance and publishing hooks. */
add_action('delete_post', 'wordbook_delete_post');


function wordbook_publish($postid) {
	$post = get_post($postid);
        if( get_post_type($postid) == 'page' ) {return ;}
	$wordbook_settings = get_option('wordbook_settings'); 
	$retcode=wordbook_transition_post_status('publish', null, $post);
	return $retcode;
}

function wordbook_publish_remote($postid) {
	$post = get_post($postid);
	if( get_post_type($postid) == 'page' ) {return ;}
	$wordbook_settings = get_option('wordbook_settings');
	$_POST["wordbook_publish_default_action"]=$wordbook_settings["wordbook_publish_default"];
	$_POST["wordbook_comment_overridden"]=$wordbook_settings["wordbook_comment_get"];
	$_POST["wordbook_default_author_override"]=$wordbook_settings['wordbook_default_author'];
	$_POST["wordbook_attribution"]=$wordbook_settings['wordbook_attribute'];
	$_POST["wordbook_status_update_override"]=$wordbook_settings['wordbook_status_update'];
	$_POST["wordbook_status_update_text_override"]=$wordbook_settings['wordbook_status_update_text'];
	$retcode=wordbook_transition_post_status('publish', null, $post);
	return $retcode;
} 

function wordbook_post_comment($commentid) {
	$wordbook_settings = get_option('wordbook_settings'); 
	if ( !isset($wordbook_settings['wordbook_comment_push'])) {	
		return;
	}
	global  $wpdb, $user_id,$table_prefix;
	define ('DEBUG', false);
	$debug_file='/tmp/wordbook_'.$table_prefix.'comment_debug';
	if (DEBUG) {$fp = fopen($debug_file, 'a');}
	$comment= get_comment($commentid); 
	$cpid = $comment->comment_post_ID;
	$cstatus=$comment->comment_approved;
	$ctext=$comment->comment_content;
	$ctype=$comment->comment_type;
	$caemail=$comment->comment_author_email;
	$cuid=$comment->user_id;
	$real_comment=true;
	if (($cuid==0) && ($caemail==get_bloginfo( 'admin_email' ))) {$real_comment=false;}
	if ($real_comment) {
		if (DEBUG) {$debug_string="FBID : ".$cpid."  stat:".$cstatus."text:".$ctext." type:".$ctype."!!\n";
		fwrite($fp, $debug_string);}	
		if ($cstatus==1) {
			$post = get_post($cpid);
			if (DEBUG) {$debug_string="Comment author: ".$post->post_author."\n";
			fwrite($fp, $debug_string);}	
			if (($wbuser = wordbook_get_userdata($post->post_author)) &&
			$wbuser->session_key) {
				$fbclient = wordbook_fbclient($wbuser);
				$sql='Select fb_post_id from ' . WORDBOOK_POSTCOMMENTS . ' where wp_post_id ='.$cpid;
				if (DEBUG) {$debug_string="Comment sql: ".$sql."\n";
				fwrite($fp, $debug_string);}	
				$rows = $wpdb->get_results($sql);
				if (DEBUG) {$debug_string="Comment count: ".count($rows)."\n";
				fwrite($fp, $debug_string);}	
				if (count($rows)>0) {
					foreach ($rows as $comdata_row) {
						$fb_post_id=$comdata_row->fb_post_id;
						$result2=$fbclient->stream_addComment($fb_post_id , $ctext.'  (imported from my Wordpress blog)');
					} 
				}
			}
		}
	}	
	if (DEBUG) {
		$debug_string=date("Y-m-d H:i:s",time())." :  Finished \n";
		fwrite($fp, $debug_string);
		fclose($fp);
	}	
}

define('WORDBOOK_HOOK_PRIORITY', 10);	/* Default; see add_action(). */
add_action('xmlrpc_publish_post', 'wordbook_publish_remote');
add_action('publish_phone', 'wordbook_publish_remote');
add_action('publish_post', 'wordbook_publish');
add_action('wb_cron_job', 'wordbook_poll_facebook');
add_action('delete_post', 'wordbook_delete_post');
add_action('comment_post', 'wordbook_post_comment');

include("wb_widget.php");
include("wordbooker_options.php");
include("wordbooker_cron.php");
?>
