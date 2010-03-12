<?php
/*
Plugin Name: Wordbooker
Plugin URI: http://blogs.canalplan.org.uk/steve/wordbooker/
Description: Provides integration between your blog and your Facebook account. Navigate to <a href="options-general.php?page=wordbooker">Settings &rarr; Wordbooker</a> for configuration.
Author: Steve Atty 
Author URI: http://blogs.canalplan.org.uk/steve/
Version: 1.7.4
*/

 /*
 * Based on the Wordbook plugin by Robert Tsai (http://www.tsaiberspace.net/projects/wordpress/wordbook/ )
 * All Credit to him for working out the basics of getting it working.
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

# Consider uisng global $table_prefix,$db_prefix; This would allow us to drop down to one set of tables per DB 

$wordbooker_settings = wordbooker_options(); 
if (! isset($wordbooker_settings['wordbook_extract_length'])) $wordbooker_settings['wordbook_extract_length']=256;

define('WORDBOOKER_DEBUG', false);
define('WORDBOOKER_TESTING', false);

# For Troubleshooting posting issues. Do not leave this set to true as it will slow posting down and will fill up the error log!
define('ADVANCED_DEBUG', false);

$facebook_config['debug'] = WORDBOOKER_TESTING && !$_POST['action'];

define('WORDBOOKER_FB_APIKEY', '0cbf13c858237f5d74ef0c32a4db11fd');
define('WORDBOOKER_FB_SECRET', 'df04f22f3239fb75bf787f440e726f31');
define('WORDBOOKER_FB_APIVERSION', '1.0');
define('WORDBOOKER_FB_DOCPREFIX','http://wiki.developers.facebook.com/index.php/');
define('WORDBOOKER_FB_MAXACTIONLEN', 60);
define('WORDBOOKER_FB_PUBLISH_STREAM', 'publish_stream');
define('WORDBOOKER_FB_READ_STREAM', 'read_stream');
define('WORDBOOKER_FB_STATUS_UPDATE',"status_update");
define('WORDBOOKER_SETTINGS', 'wordbooker_settings');
define('WORDBOOKER_OPTION_SCHEMAVERS', 'schemavers');

define('WORDBOOKER_ERRORLOGS', $table_prefix . 'wordbook_errorlogs');
define('WORDBOOKER_POSTLOGS', $table_prefix . 'wordbook_postlogs');
define('WORDBOOKER_USERDATA', $table_prefix . 'wordbook_userdata');
define('WORDBOOKER_POSTCOMMENTS', $table_prefix . 'wordbook_postcomments');

define('WORDBOOKER_EXCERPT_SHORTSTORY', $wordbooker_settings['wordbook_extract_length']); 
define('WORDBOOKER_EXCERPT_WIDEBOX', 96);
define('WORDBOOKER_EXCERPT_NARROWBOX', 40);

define('WORDBOOKER_MINIMUM_ADMIN_LEVEL', 'edit_posts');	/* Contributor role or above. */
define('WORDBOOKER_SETTINGS_PAGENAME', 'wordbooker');
define('WORDBOOKER_SETTINGS_URL', 'admin.php?page=' . WORDBOOKER_SETTINGS_PAGENAME);

define('WORDBOOKER_SCHEMA_VERSION', 2);

$wordbook_wp_version_tuple = explode('.', $wp_version);
define('WORDBOOKER_WP_VERSION', $wordbook_wp_version_tuple[0] * 10 + $wordbook_wp_version_tuple[1]);

if (function_exists('json_encode')) {
	define('WORDBOOKER_JSON_ENCODE', 'PHP');
} else {
	define('WORDBOOKER_JSON_ENCODE', 'Wordbook');
}

define('WORDBOOKER_SIMPLEXML', 'PHP');
define('FACEBOOK_PHP_API', 'PHP5');


function wordbooker_debug($message) {
	if (WORDBOOKER_DEBUG) {
		$fp = fopen('/tmp/wb.log', 'a');
		$date = date('D M j, g:i:s a');
		fwrite($fp, "$date: $message");
		fclose($fp);
	}
}

function wordbooker_load_apis() {
	if (defined('WORDBOOKER_APIS_LOADED')) {
		return;
	}
	if (WORDBOOKER_JSON_ENCODE == 'Wordbook') {
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
	define('WORDBOOKER_APIS_LOADED', true);
}

function wordbooker_fbclient_publishaction_impl($fbclient, $post_data) {
	global $wordbooker_post_options;

	try {
		$method = 'stream.publish';
		$message=$post_data['post_attribute'];
		#$post_data['post_excerpt']=html_entity_decode($post_data['post_excerpt']);
		#$post_data['post_excerpt']=html_entity_decode($post_data['post_excerpt'],ENT_QUOTES,'UTF-8');
		#$post_data['post_title']=html_entity_decode($post_data['post_title']);
		#$post_data['post_title']=html_entity_decode($post_data['post_title'],ENT_QUOTES,'UTF-8');
		
		# The following handle some character conversions which might be needed for some people. I think this is down to what character set the DB tables are set to use 			#$post_data['post_excerpt']=mb_convert_encoding($post_data['post_excerpt'], 'UTF-8', 'HTML-ENTITIES');
		#$post_data['post_title']=mb_convert_encoding($post_data['post_title'], 'UTF-8', 'HTML-ENTITIES');

		# Converts latin diacritics into two letters. This includes umlauted letters.
    		#$post_data['post_excerpt']= preg_replace(array('/&szlig;/','/&(..)lig;/','/&([aouAOU])uml;/','/&(.)[^;]*;/'),array('ss',"$1","$1".'e',"$1"), $post_data['post_excerpt']);

		$attachment =  array(
      		  'name' => $post_data['post_title'],
      		  'href' => $post_data['post_link'],
      		  'description' => $post_data['post_excerpt'],
       		  'media' => $post_data['media']
		);
		
		if ($wordbooker_post_options['wordbook_actionlink']==100) {
		// No action link
		wordbooker_debugger("No action link being used","",1000) ;
		}
		if ($wordbooker_post_options['wordbook_actionlink']==200) {
		// Share This
			wordbooker_debugger("Share Link being used","",1000) ;
			$action_links = array(array('text' => 'Share','href' => 'http://www.facebook.com/share.php?u='.urlencode($post_data['post_link'])));
		}
		if ($wordbooker_post_options['wordbook_actionlink']==300) {
		// Read Full
			wordbooker_debugger("Read Full link being used","",1000) ;
		$action_links = array(array('text' => 'Read entire article','href' => $post_data['post_link']));
		}
		// User has chosen to publish to Profile as well as a fan page
		if ($wordbooker_post_options["wordbook_orandpage"]>1) {
		wordbooker_debugger("posting to fan wall and personal wall (if available)","",1000) ;;
			if ($wordbooker_post_options['wordbook_actionlink']==100) {
			// No action link
				$result = $fbclient->stream_publish($message,json_encode($attachment), null);
			} else
			{
				$result = $fbclient->stream_publish($message,json_encode($attachment), json_encode($action_links));
			 }
			if ( $wordbooker_post_options["wordbook_page_post"]== -100) { wordbooker_debugger("No Fan Wall post","",1000) ; } else {
				wordbooker_debugger("Also posting to Fan wall",$wordbooker_post_options["wordbook_page_post"],1000) ;
				if ($wordbooker_post_options['wordbook_actionlink']==100) {
				// No action link
				$result = $fbclient->stream_publish($message,json_encode($attachment), null,null,$wordbooker_post_options["wordbook_page_post"]);
				} else
				{
				$result = $fbclient->stream_publish($message, json_encode($attachment),json_encode($action_links),null,$wordbooker_post_options["wordbook_page_post"]);
				}
			}

		} else {
			# If they actually have a page to post to then we post to it
			
			if ( $wordbooker_post_options["wordbook_page_post"]== -100) { wordbooker_debugger("No Fan Wall post","",1000) ; } else {
				wordbooker_debugger("Only posting to Fan wall",$wordbooker_post_options["wordbook_page_post"],1000) ;
				if ($wordbooker_post_options['wordbook_actionlink']==100) {
				// No action link
				$result = $fbclient->stream_publish($message,json_encode($attachment), null,null,$wordbooker_post_options["wordbook_page_post"]);
				} else
				{
				$result = $fbclient->stream_publish($message, json_encode($attachment),json_encode($action_links),null,$wordbooker_post_options["wordbook_page_post"]);
				}
			}
		}

	} catch (Exception $e) {
		$error_code = $e->getCode();
		$error_msg = $e->getMessage();
	}
	return array($result, $error_code, $error_msg, $method);
}

function wordbooker_fbclient_getinfo($fbclient, $fields) {
	try {
		$uid = $fbclient->users_getLoggedInUser();
		$users = $fbclient->users_getInfo($uid, $fields);
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

function wordbooker_fbclient_has_app_permission($fbclient, $ext_perm) {
	try {
		$uid = $fbclient->users_getLoggedInUser();
		$has_permission = $fbclient->call_method('facebook.users.hasAppPermission', array('uid' => $uid,'ext_perm' => $ext_perm,));
		$error_code = null;
		$error_msg = null;
	} catch (Exception $e) {
		$has_permission = null;
		$error_code = $e->getCode();
		$error_msg = $e->getMessage();
	}
	return array($has_permission, $error_code, $error_msg);
}

function wordbooker_fbclient_getsession($fbclient, $token) {
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

function wordbooker_options() {
	return get_option(WORDBOOKER_SETTINGS);
}

function wordbooker_set_options($options) {
	update_option(WORDBOOKER_SETTINGS, $options);
}

function wordbooker_get_option($key) {
	$options = wordbooker_options();
	return isset($options[$key]) ? $options[$key] : null;
}

function wordbooker_set_option($key, $value) {
	$options = wordbooker_options();
	$options[$key] = $value;	
	wordbooker_set_options($options);
}

function wordbooker_delete_option($key) {
	$options = wordbooker_options();
	unset($options[$key]);
	update_option(WORDBOOKER_SETTINGS, $options);
}

/******************************************************************************
 * Plugin deactivation - tidy up database.
 */

function wordbooker_deactivate() {
	global $wpdb;
	$errors = array();
	foreach (array(
			WORDBOOKER_ERRORLOGS,
			WORDBOOKER_POSTLOGS,
			WORDBOOKER_USERDATA,
			WORDBOOKER_POSTCOMMENTS,
			) as $tablename) {
		$result = $wpdb->query("
			DROP TABLE IF EXISTS $tablename
			");
		if ($result === false)
			$errors[] = "Failed to drop $tablename";
	}
	delete_option(WORDBOOKER_SETTINGS);
	delete_option('wordbook_settings');
	wp_clear_scheduled_hook('wb_cron_job');

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

function wordbooker_activate() {
	global $wpdb, $table_prefix;
	wp_cache_flush();
	$errors = array();
	$result = $wpdb->query('
		CREATE TABLE IF NOT EXISTS ' . WORDBOOKER_POSTLOGS . ' (
			`postid` BIGINT(20) NOT NULL
			, `timestamp` TIMESTAMP
		)
		');
	if ($result === false)
		$errors[] = 'Failed to create ' . WORDBOOKER_POSTLOGS;

	$result = $wpdb->query('
		CREATE TABLE IF NOT EXISTS ' . WORDBOOKER_ERRORLOGS . ' (
			`timestamp` TIMESTAMP
			, `user_ID` BIGINT(20) UNSIGNED NOT NULL
			, `method` VARCHAR(255) NOT NULL
			, `error_code` INT NOT NULL
			, `error_msg` VARCHAR(80) NOT NULL
			, `postid` BIGINT(20) NOT NULL
		)
		');
	if ($result === false)
		$errors[] = 'Failed to create ' . WORDBOOKER_ERRORLOGS;

	$result = $wpdb->query('
		CREATE TABLE IF NOT EXISTS ' . WORDBOOKER_USERDATA . ' (
			  `user_ID` bigint(20) unsigned NOT NULL,
			  `use_facebook` tinyint(1) NOT NULL default 1,
			  `onetime_data` longtext NOT NULL,
			  `facebook_error` longtext NOT NULL,
			  `secret` varchar(80) NOT NULL,
			  `session_key` varchar(80) NOT NULL,
			  `facebook_id` varchar(40) NOT NULL,
			  `name` varchar(250) NOT NULL,
			  `status` varchar(1024) default NULL,
			  `updated` int(20) NOT NULL,
			  `url` varchar(250) default NULL,
			  `pic` varchar(250) default NULL,
			  `pages` varchar(2048) default NULL,
			  `auths_needed` int(1) NOT NULL,
			  `blog_id` bigint(20) NOT NULL,
			  PRIMARY KEY  (`user_ID`),
			  KEY `facebook_idx` (`facebook_id`)
		)
		');
	if ($result === false)
		$errors[] = 'Failed to create ' . WORDBOOKER_USERDATA;

	$result = $wpdb->query('
		CREATE TABLE IF NOT EXISTS ' . WORDBOOKER_POSTCOMMENTS . ' (
		  `fb_post_id` varchar(40) NOT NULL,
		  `comment_timestamp` int(20) NOT NULL,
		  `wp_post_id` int(11) NOT NULL,
		   UNIQUE KEY `fb_comment_id` (`fb_post_id`,`wp_post_id`)
		)
		');
	if ($result === false)
		$errors[] = 'Failed to create ' . WORDBOOKER_POSTCOMMENTS;

	if ($errors) {
		echo '<div id="message" class="updated fade">' . "\n";
		foreach ($errors as $errormsg) {
			_e("$errormsg<br />\n");
		}
		echo "</div>\n";
		return;
	}
	wordbooker_set_option(WORDBOOKER_OPTION_SCHEMAVERS, 2);
	$wordbooker_settings=wordbooker_options();
	#Setup the cron. We clear it first in case someone did a dirty de-install.
	$dummy=wp_clear_scheduled_hook('wb_cron_job');
	$dummy=wp_schedule_event(time(), 'hourly', 'wb_cron_job');
}

function wordbooker_upgrade() {
	global $wpdb, $table_prefix,$blog_id;
	$errors = array();
	# We use this to make changes to Schema versions. We need to get the current schema version the user is using and then "upgrade" the various tables.
	$wordbooker_settings=wordbooker_options();
;
	if (! isset($wordbooker_settings[WORDBOOKER_OPTION_SCHEMAVERS])) {$wordbooker_settings[WORDBOOKER_OPTION_SCHEMAVERS]=1;}
	if ($wordbooker_settings[WORDBOOKER_OPTION_SCHEMAVERS]< WORDBOOKER_SCHEMA_VERSION ) { 
		echo "Database changes being applied";
	} else {
		return;
	}
	if ($wordbooker_settings[WORDBOOKER_OPTION_SCHEMAVERS]==1 ) {
		$result = $wpdb->query('
			ALTER TABLE ' . WORDBOOKER_USERDATA . ' 
				ADD `facebook_id` VARCHAR( 40 ) NOT NULL ,
				ADD `name` VARCHAR( 250 ) NOT NULL ,
				ADD `status` VARCHAR( 1024 ) default NULL ,
				ADD `updated` INT( 20 ) NOT NULL ,
				ADD `url` VARCHAR( 250 ) default NULL ,
				ADD `pic` VARCHAR( 250 ) default NULL ,
				ADD `pages` VARCHAR( 2048 ) default NULL,
				ADD `auths_needed` int(1) NOT NULL,
				ADD  `blog_id` bigint(20) NOT NULL
			');
		if ($result === false)  $errors[] = 'Failed to update ' . WORDBOOKER_USERDATA;

		$result = $wpdb->query('ALTER TABLE ' . WORDBOOKER_USERDATA . ' ADD PRIMARY KEY ( `user_ID` ) ');
		if ($result === false)  $errors[] = 'Failed to update ' . WORDBOOKER_USERDATA;
	
		$result = $wpdb->query('ALTER TABLE ' . WORDBOOKER_USERDATA . ' ADD INDEX `facebook_idx` ( `facebook_id` ) ');
		if ($result === false)  $errors[] = 'Failed to update ' . WORDBOOKER_USERDATA;

		$result = $wpdb->query('update ' . WORDBOOKER_USERDATA . ' set blog_id ='.$blog_id);

		if ($errors) {
			echo '<div id="message" class="updated fade">' . "\n";
			foreach ($errors as $errormsg) {
				_e("$errormsg<br />\n");
			}
			echo "</div>\n";
		}
		# All done, set the schemaversion to version 2. NOT the current version, as this allow us to string updates.
		wordbooker_set_option(WORDBOOKER_OPTION_SCHEMAVERS, 2);
	}

	# Clear and re-instate the cron - just to be tidy.
	$dummy=wp_clear_scheduled_hook('wb_cron_job');
	$dummy=wp_schedule_event(time(), 'hourly', 'wb_cron_job');
	wp_cache_flush();
}

function wordbooker_delete_user($user_id) {
	global $wpdb;
	$errors = array();
	foreach (array(
			WORDBOOKER_USERDATA,
			WORDBOOKER_ERRORLOGS,
			) as $tablename) {
		$result = $wpdb->query('DELETE FROM ' . $tablename . ' WHERE user_ID = ' . $user_id . '
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

function wordbooker_get_userdata($user_id) {
	global $wpdb;
	$sql='SELECT onetime_data,facebook_error,secret,session_key,user_ID FROM ' . WORDBOOKER_USERDATA . ' WHERE user_ID = ' . $user_id ;
	$rows = $wpdb->get_results($sql);
	if ($rows) {
		$rows[0]->onetime_data = unserialize($rows[0]->onetime_data);
		$rows[0]->facebook_error = unserialize($rows[0]->facebook_error);
		$rows[0]->secret = unserialize($rows[0]->secret);
		$rows[0]->session_key = unserialize($rows[0]->session_key);
		return $rows[0];
	}
	return null;
}

function wordbooker_set_userdata($onetime_data, $facebook_error,$secret, $session_key) {
	global $user_ID, $wpdb,$blog_id;
	wordbooker_delete_userdata();
	$result = $wpdb->query("
		INSERT INTO " . WORDBOOKER_USERDATA . " (
			user_ID
			, onetime_data
			, facebook_error
			, secret
			, session_key
			, blog_id
		) VALUES (
			" . $user_ID . "
			, '" . serialize($onetime_data) . "'
			, '" . serialize($facebook_error) . "'
			, '" . serialize($secret) . "'
			, '" . serialize($session_key) . "'
			, " . $blog_id . "
		)
		");
}

function wordbooker_set_userdata2( $onetime_data, $facebook_error, $secret, $session_key,$user_ID) {
	global $wpdb;
	$sql= "Update " . WORDBOOKER_USERDATA . " set
 			  onetime_data =  '" . serialize($onetime_data) . "'
			, facebook_error = '" . serialize($facebook_error) . "'
			, secret = '" . serialize($secret) . "'
			, session_key = '" . serialize($session_key) . "'
		 where user_id=".$user_ID;
	$result = $wpdb->query($sql);
}


function wordbooker_update_userdata($wbuser) {

	return wordbooker_set_userdata2(
		$wbuser->onetime_data, $wbuser->facebook_error, $wbuser->secret,
		$wbuser->session_key,$wbuser->user_ID);
}

function wordbooker_set_userdata_facebook_error($wbuser, $method, $error_code,
		$error_msg, $postid) {
	$wbuser->facebook_error = array(
		'method' => $method,
		'error_code' => mysql_real_escape_string ($error_code),
		'error_msg' => mysql_real_escape_string ($error_msg),
		'postid' => $postid,
		);
	wordbooker_update_userdata($wbuser);
	wordbooker_appendto_errorlogs($method, $error_code, $error_msg, $postid);
}

function wordbooker_clear_userdata_facebook_error($wbuser) {
	$wbuser->facebook_error = null;
	return wordbooker_update_userdata($wbuser);
}

function wordbooker_delete_userdata() {
	global $user_ID;
	wordbooker_delete_user($user_ID);
}

/******************************************************************************
 * Post logs - record time of last post to Facebook
 */

function wordbooker_trim_postlogs() {
	/* Forget that something has been posted to Facebook if it's been there
	 * more than a year. We need to do this to stop posts getting deleted by accident if people ramp down the repost window */
	global $wpdb;
	#$wordbooker_settings =wordbooker_options(); 
	#if (! isset($wordbooker_settings['wordbook_republish_time_frame'])) $wordbooker_settings['wordbook_republish_time_frame']='10';
	$result = $wpdb->query('
		DELETE FROM ' . WORDBOOKER_POSTLOGS . '
		WHERE timestamp < DATE_SUB(CURDATE(), INTERVAL 365 DAY)
		');
}

function wordbooker_postlogged($postid) {
	global $wpdb,$wordbooker_post_options;
	// See if the user has overridden the repost on edit - i.e. they want to publish and be damned!
	if (isset ($wordbooker_post_options["wordbooker_publish_override"])) { return false;}
	$wordbooker_settings =wordbooker_options(); 
	// Does the user want us to ever publish on Edit? If not then return true
	if ( (! isset($wordbooker_settings["wordbook_republish_time_obey"])) && ($_POST['original_post_status']=='publish')) { return true;}
	if (! isset($wordbooker_settings['wordbook_republish_time_frame'])) $wordbooker_settings['wordbook_republish_time_frame']='10';
	$rows = $wpdb->get_results('
		SELECT *
		FROM ' . WORDBOOKER_POSTLOGS . '
		WHERE postid = ' . $postid . '
		AND timestamp > DATE_SUB(CURDATE(), INTERVAL '.$wordbooker_settings["wordbook_republish_time_frame"].' DAY)
		');
	return $rows ? true : false;
}

function wordbooker_insertinto_postlogs($postid) {
	global $wpdb;
	wordbooker_deletefrom_postlogs($postid);
	if (!WORDBOOKER_TESTING) {
		$result = $wpdb->query('
			INSERT INTO ' . WORDBOOKER_POSTLOGS . ' (
				postid
			) VALUES (
				' . $postid . '
			)
			');
	}
}

function wordbooker_deletefrom_postlogs($postid) {
	global $wpdb;
	$result = $wpdb->query('
		DELETE FROM ' . WORDBOOKER_POSTLOGS . '
		WHERE postid = ' . $postid . '
		');
}

function wordbooker_deletefrom_commentlogs($postid) {
	global $wpdb;
	$result = $wpdb->query('
		DELETE FROM ' . WORDBOOKER_POSTCOMMENTS . '
		WHERE wp_post_id = ' . $postid . '
		');
}

/******************************************************************************
 * Error logs - record errors
 */

function wordbooker_hyperlinked_method($method) {
	return '<a href="'
		. WORDBOOKER_FB_DOCPREFIX . $method . '"'
		. ' title="Facebook API documentation" target="facebook"'
		. '>'
		. $method
		. '</a>';
}

function wordbooker_trim_errorlogs() {
	global $wpdb;
	$result = $wpdb->query('
		DELETE FROM ' . WORDBOOKER_ERRORLOGS . '
		WHERE timestamp < DATE_SUB(CURDATE(), INTERVAL 7 DAY) and user_ID > 0 ');
}

function wordbooker_clear_errorlogs() {
	global $user_ID, $wpdb;
	$result = $wpdb->query('
		DELETE FROM ' . WORDBOOKER_ERRORLOGS . '
		WHERE user_ID = ' . $user_ID . '
		');
	if ($result === false) {
		echo '<div id="message" class="updated fade">';
		_e('Failed to clear error logs.');
		echo "</div>\n";
	}
}

function wordbooker_appendto_errorlogs($method, $error_code, $error_msg,$postid) {
	global $user_ID, $wpdb;
	if ($postid == null) {
		$postid = 0;
		$user_id = $user_ID;
	} else {
		$post = get_post($postid);
		$user_id = $post->post_author;
	}
		$result = $wpdb->insert(WORDBOOKER_ERRORLOGS,
			array('user_ID' => $user_id,
				'method' => $method,
				'error_code' => $error_code,
				'error_msg' => $error_msg,
				'postid' => $postid,
				),
			array('%d', '%s', '%d', '%s', '%d')
			);
	 
}

function wordbooker_deletefrom_errorlogs($postid) {
	global $wpdb;
	$result = $wpdb->query('
		DELETE FROM ' . WORDBOOKER_ERRORLOGS . '
		WHERE postid = ' . $postid . ' and user_ID > 0
		');
}

function wordbooker_render_errorlogs() {
	global $user_ID, $wpdb;

	$rows = $wpdb->get_results('
		SELECT *
		FROM ' . WORDBOOKER_ERRORLOGS . '
		WHERE user_ID = ' . $user_ID . '
		ORDER BY timestamp
		');
	if ($rows) {
?>

	<h3><?php _e('Errors'); ?></h3>
	<div class="wordbook_errors">

	<p>
	Your blog is OK, but Wordbooker was unable to update your Facebook account:
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
				wordbooker_hyperlinked_method($row->method);
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

	<form action="<?php echo WORDBOOKER_SETTINGS_URL; ?>" method="post">
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
 * Wordbooker setup and administration.
 */

function wordbooker_admin_load() {
	if (!$_POST['action'])
		return;

	switch ($_POST['action']) {

	case 'one_time_code':
		$token = $_POST['one_time_code'];
		$fbclient = wordbooker_fbclient(null);
		list($result, $error_code, $error_msg) = wordbooker_fbclient_getsession($fbclient, $token);
		if ($result) {
			wordbooker_clear_errorlogs();
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
		$facebook_error = null;
		wordbooker_set_userdata( $onetime_data,$facebook_error, $secret, $session_key);
		wp_redirect(WORDBOOKER_SETTINGS_URL);
		break;

	case 'delete_userdata':
		# Catch if they got here using the perm_save/cache refresh
		if ( ! isset ($_POST["perm_save"])) {
			wordbooker_delete_userdata();
		}
		wp_redirect(WORDBOOKER_SETTINGS_URL);
		break;

	case 'clear_errorlogs':
		wordbooker_clear_errorlogs();
		wp_redirect(WORDBOOKER_SETTINGS_URL);
		break;

	case 'no_facebook':
		wordbooker_set_userdata(false, null, null, null,null);
		wp_redirect('/wp-admin/index.php');
		break;
	}

	exit;
}

function wordbooker_admin_head() {
?>
	<style type="text/css">
	.wordbook_setup { margin: 0 3em; }
	.wordbook_notices { margin: 0 3em; }
	.wordbooker_status { margin: 0 3em; }
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

function wordbooker_option_notices() {
	global $user_ID, $wp_version;
	wordbooker_upgrade();
	wordbooker_trim_postlogs();
	wordbooker_trim_errorlogs();
	$errormsg = null;
	if (WORDBOOKER_WP_VERSION < 27) {
		$errormsg = sprintf(__('Wordbooker requires <a href="%s">WordPress</a>-2.7 or newer (you appear to be running version %s).'),'http://wordpress.org/download/', $wp_version);
	} else if (!($options = wordbooker_options()) ||
			!($wbuser = wordbooker_get_userdata($user_ID)) ||
			( !$wbuser->session_key)) {
		$errormsg="Wordbooker needs to be set up";
	} else if ($wbuser->facebook_error) {
		$method = $wbuser->facebook_error['method'];
		$error_code = $wbuser->facebook_error['error_code'];
		$error_msg = $wbuser->facebook_error['error_msg'];
		$postid = $wbuser->facebook_error['postid'];
		$suffix = '';
		if ($postid != null && ($post = get_post($postid))) {
			wordbooker_deletefrom_postlogs($postid);
			$suffix = ' for <a href="'. get_permalink($postid) . '">'. get_the_title($postid) . '</a>';
		}
		$errormsg = sprintf(__("<a href='%s'>Wordbooker</a> failed to communicate with Facebook" . $suffix . ": method = %s, error_code = %d (%s). Your blog is OK, but Facebook didn't get the update."), WORDBOOKER_SETTINGS_URL,wordbooker_hyperlinked_method($method),$error_code,$error_msg);
		wordbooker_clear_userdata_facebook_error($wbuser);
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

function wordbooker_option_setup($wbuser) {
?>

	<h3><?php _e('Setup'); ?></h3>
	<div class="wordbook_setup">

	<p>Wordbooker needs to be linked to your Facebook account. This link will be used to publish your WordPress blog updates to your wall and to pull comments from your wall, and will not be used for any other purpose.</p>

	<p>To do this we need to get a code from Facebook to link the two accounts. If you click on the Facebook Login button below it will open a new window where you will be prompted to login to Facebook (assuming that you are not already logged in).</p>

	<p>Once you've logged in you will be asked to generate a "one time code". Generate the code and copy it, and the come back to this page.</p>

	<div style="text-align: center;"><a href="http://www.facebook.com/code_gen.php?v=<?php echo WORDBOOKER_FB_APIVERSION; ?>&api_key=<?php echo WORDBOOKER_FB_APIKEY; ?>" target="facebook"><img src="http://static.ak.facebook.com/images/devsite/facebook_login.gif" /></a></div>

	<form action="<?php echo WORDBOOKER_SETTINGS_URL; ?>" method="post">
		<p>Next, enter the one-time code obtained in the previous step:</p>
		<div style="text-align: center;">
		<input type="text" name="one_time_code" id="one_time_code"
			value="<?php echo $wbuser->onetime_data['onetimecode']; ?>" size="9" />
		</div>
		<input type="hidden" name="action" value="one_time_code" />

<?php
		if ($wbuser) {
			wordbooker_render_onetimeerror($wbuser);
			$wbuser->onetime_data = null;
			wordbooker_update_userdata($wbuser);
			
		}
?>
		<p style="text-align: center;"><input type="submit" value="<?php _e('Submit &raquo;'); ?>" /></p>
	</form>
	</div>

<?php
}

function wordbooker_status($user_id)
{
	echo '<h3>'.__('Status').'</h3>';
	global  $wpdb, $user_ID,$table_prefix,$blog_id;
	$wordbook_user_settings_id="wordbookuser".$blog_id;
	$wordbookuser=get_usermeta($user_ID,$wordbook_user_settings_id);
	if ($wordbookuser['wordbook_disable_status']=='on') {return;}
	global $shortcode_tags;
	$result = wordbooker_get_cache($user_id);
?>		

	<div class="wordbooker_status">
	<div class="facebook_picture">
		<a href="<?php echo $result->url; ?>" target="facebook">
		<img src="<?php echo $result->pic; ?>" /></a>
		</div>
		<p>
		<a href="<?php echo $result->url; ?>"><?php echo $result->name; ?></a><br><br>
		<i><?php echo $result->status; ?></i><br>
		(<?php echo date('D M j, g:i a', $result->updated); ?>).
		<br><br>
<?php

}

function wordbooker_option_status($wbuser) {
global  $wpdb,$user_ID;

	$fbclient = wordbooker_fbclient($wbuser);
	# Go to the cache and try to pull details
	$fb_info=wordbooker_get_cache($user_ID,'use_facebook,facebook_id');
	# If we're missing stuff lets kick the cache.
	if (! isset($fb_info->facebook_id)) {
		 wordbooker_cache_refresh ($user_ID,$fbclient);
		$fb_info=wordbooker_get_cache($user_ID,'use_facebook,facebook_id'); 
	}
	if (isset($fbclient->secret)){
		if ($fb_info->use_facebook==1) {
			echo "<p>Wordbooker appears to be configured and working just fine.</p>";
			wordbooker_check_permissions($wbuser,$user);	
			echo "<p>If you like, you can start over from the beginning (this does not delete your posting and comment history):</p>";
		} 
		else 
		{
			echo '<p>Wordbooker is able to connect to Facebook.</p>';
			echo '<p>Next, add the <a href="http://www.facebook.com/apps/application.php?id=254577506873" target="facebook">Wordbooker</a> application to your Facebook profile:</p>';
			echo '<div style="text-align: center;"><a href="http://www.facebook.com/add.php?api_key=<?php echo WORDBOOKER_FB_APIKEY; ?>" target="facebook"><img src="http://static.ak.facebook.com/images/devsite/facebook_login.gif" /></a></div>';
			echo '<p>Or, you can start over from the beginning:</p>';

		} 
	}
	 else
	 {

		echo '<p>Wordbooker is configured and working, but <a href="http://developers.facebook.com/documentation.php?v=1.0&method=users.getInfo" target="facebook">facebook.users.getInfo</a>';
		echo 'failed (no Facebook user for uid '.$fb_info->facebook_id.').</p> <p>This may be a transitory error but it if persists you could try resetting the configuration</p>';

	}

	echo'<form action="" method="post">';

	echo '<p style="text-align: center;"><input type="submit"  class="button-primary" name="reset_user_config"  value="'._('Reset Configuration').'" />';
	echo '&nbsp;&nbsp;<input type="submit" name="perm_save" class="button-primary" value="'. __('Refresh Status').'" /></p>';
	echo '</form> </div>';

}

function wordbooker_version_ok($currentvers, $minimumvers) {
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


function wordbooker_option_support() {
	global $wp_version;
	$wordbooker_settings=wordbooker_options();
?>
	<h3><?php _e('Support'); ?></h3>
	<div class="wordbook_support">
	For feature requests, bug reports, and general support:
	<ul>	
	<li>Check the <a href="/wp-content/plugins/wordbooker/wordbooker_user_guide.pdf" target="wordpress">User Guide</a>.</li>
	<li>Check the <a href="http://wordpress.org/extend/plugins/wordbooker/other_notes/" target="wordpress">WordPress.org Notes</a>.</li>
	<li>Try the <a href="http://www.facebook.com/apps/application.php?v=app_2373072738&id=254577506873" target="facebook">Wordbooker Discussion Board</a>.</li>
	<li>Consider upgrading to the <a href="http://wordpress.org/download/">latest stable release</a> of WordPress.</li>
	<li>Read the release notes for Wordbooker on the <a href="http://blogs.canalplan.org.uk/steve/wordbooker/">Wordbooker</a> page.</li>
	</ul>
	<br>
	Please provide the following information about your installation:
	<ul>
<?php
	$active_plugins = get_option('active_plugins');
	$plug_info=get_plugins();
	$phpvers = phpversion();
	$mysqlvers = function_exists('mysql_get_client_info') ?
		 mysql_get_client_info() :
		 'Unknown';
	$info = array(	
		'Wordbooker' => $plug_info['wordbooker/wordbooker.php']['Version'],
		'Wordbooker Schema' => $wordbooker_settings[WORDBOOKER_OPTION_SCHEMAVERS],
		'Facebook PHP API' => FACEBOOK_PHP_API,
		'JSON library' => WORDBOOKER_JSON_ENCODE,
		'SimpleXML library' => WORDBOOKER_SIMPLEXML,
		'WordPress' => $wp_version,
		'PHP' => $phpvers,
		'MySQL' => $mysqlvers,
		);
	$version_errors = array();
	$phpminvers = '5.0';
	$mysqlminvers = '4.0';
	if (!wordbooker_version_ok($phpvers, $phpminvers)) {
		$version_errors['PHP'] = $phpminvers;
	}
	if ($mysqlvers != 'Unknown' && !wordbooker_version_ok($mysqlvers, $mysqlminvers)) {
		$version_errors['MySQL'] = $mysqlminvers;
	}

	foreach ($info as $key => $value) {
		$suffix = '';
		if (($minvers = $version_errors[$key])) {
			$suffix = " <span class=\"wordbook_errorcolor\">" . " (need $key version $minvers or greater)" . " </span>";
		}
		echo "<li>$key: <b>$value</b>$suffix</li>";
	}
	if (!function_exists('simplexml_load_string')) {
		echo "<li>XML: your PHP is missing <code>simplexml_load_string()</code></li>";
	}

	if (!method_exists( 'FacebookRestClient', 'stream_publish' ) ){
		echo "<li>Facebook API: <b>Your client library is missing <code>stream_publish</code>. Please check for other Facebook plugins</b></li>";
	}
	echo "<li> Server : <b>".$_SERVER['SERVER_SOFTWARE']."</b></li>";
	echo "<li> Active Plugins : <b></li>";	
	 foreach($active_plugins as $name) {
		if ( $plug_info[$name]['Title']!='Wordbooker') {
		echo $plug_info[$name]['Title']." ( ".$plug_info[$name]['Version']." ) <br>";}
	}
	echo "</b>";

	#if (ADVANCED_DEBUG) { phpinfo(INFO_MODULES);}
?>
	</ul>

<?php
	if ($version_errors) {
?>

	<div class="wordbook_errorcolor">
	Your system does not meet the <a href="http://wordpress.org/about/requirements/">WordPress minimum reqirements</a>. Things are unlikely to work.
	</div>

<?php
	} else if ($mysqlvers == 'Unknown') {
?>

	<div>
	Please ensure that your system meets the <a href="http://wordpress.org/about/requirements/">WordPress minimum reqirements</a>.
	</div>

<?php
	}
?>
	</div>

<?php
}



function wordbooker_admin_menu() {
	
	if (!current_user_can(WORDBOOKER_MINIMUM_ADMIN_LEVEL)) { return; }

	$hook = add_options_page('Wordbook Option Manager', 'Wordbooker',WORDBOOKER_MINIMUM_ADMIN_LEVEL, WORDBOOKER_SETTINGS_PAGENAME,'wordbooker_option_manager');
	add_action("load-$hook", 'wordbooker_admin_load');
	add_action("admin_head-$hook", 'wordbooker_admin_head');
}

/******************************************************************************
 * One-time code (Facebook)
 */

function wordbooker_render_onetimeerror($wbuser) {
	$result = $wbuser->onetime_data;
	if (($result = $wbuser->onetime_data)) {
		?>
		<p>There was a problem with the one-time code "<?php echo $result['onetimecode']; ?>": <a href="http://wiki.developers.facebook.com/index.php/Auth.getSession" target="facebook">error_code = <?php echo $result['error_code']; ?> (<?php echo $result['error_msg']; ?>)</a>. Try re-submitting it, or try generating a new one-time code.</p>
		<?php
	}
}

/******************************************************************************
 * Facebook API wrappers.
 */

function wordbooker_fbclient($wbuser) {
	wordbooker_load_apis();
	$secret = null;
	$session_key = null;
	if ($wbuser) {
		$secret = $wbuser->secret;
		$session_key = $wbuser->session_key;
	}
	if (!$secret) $secret = WORDBOOKER_FB_SECRET;
	if (!$session_key) $session_key = '';
	return new FacebookRestClient(WORDBOOKER_FB_APIKEY, $secret,$session_key);
}

function wordbooker_fbclient_facebook_finish($wbuser, $result, $method,$error_code, $error_msg, $postid) 
{	
wordbooker_debugger("All done","",1000) ;
	if ($error_code) {
		wordbooker_set_userdata_facebook_error($wbuser, $method, $error_code, $error_msg, $postid);
	} else {
		wordbooker_clear_userdata_facebook_error($wbuser);
	}
	return $result;
}

function wordbooker_fbclient_publishaction($wbuser, $fbclient,$postid) 
{	
	global $wordbooker_post_options;
	#var_dump($wordbooker_post_options);
	$post = get_post($postid);
	$post_link = get_permalink($postid);
	$post_title = get_the_title($postid);
	$post_content = $post->post_content;
	# Grab the content of the post once its been filter for display - this converts app tags into HTML so we can grab gallery images etc.
	$processed_content ="!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!".apply_filters('the_content', $post_content);

	preg_match_all('/<img \s+ ([^>]*\s+)? src \s* = \s* "(.*?)"/ix',$processed_content, $matches);
	$images = array();
	foreach ($matches[2] as $ii => $imgsrc) {
		if ($imgsrc) {
			if (stristr(substr($imgsrc, 0, 8), '://') ===false) {
				/* Fully-qualify src URL if necessary. */
				$scheme = $_SERVER['HTTPS'] ? 'https' : 'http';
				$new_imgsrc = "$scheme://". $_SERVER['SERVER_NAME'];
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
			preg_match_all('/<wpg2>(.*?)</ix', $processed_content,
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

	$post_content=html_entity_decode($post_content);
	$post_content=html_entity_decode($post_content,ENT_QUOTES,'UTF-8');

	$post_title=html_entity_decode($post_title);
	$post_title=html_entity_decode($post_title,ENT_QUOTES,'UTF-8');

	# We need to strip out any graphics coming from other plugins that we dont want.
#	foreach ($images as $rawpic) {
#		$images2[]=strip_images($rawpic);
#	}	
#	$images=$images2;
	$images=array_filter($images, "strip_images");
	$wordbooker_settings =wordbooker_options(); 

	# We take the raw post data for the extract.
	$post_content=wordbooker_post_excerpt($post_content,$wordbooker_post_options['wordbook_extract_length']);

	# this is getting and setting the post attributes
	$post_attribute=parse_wordbooker_attributes(stripslashes($wordbooker_post_options["wordbook_attribute"]),$postid,strtotime($post->post_date));
	$post_data = array(
		'media' => $images,
		'post_link' => $post_link,
		'post_title' => $post_title,
		'post_excerpt' => $post_content,
		'post_attribute' => $post_attribute
		);

	list($result, $error_code, $error_msg, $method) = wordbooker_fbclient_publishaction_impl($fbclient, $post_data);

	return wordbooker_fbclient_facebook_finish($wbuser, $result,$method, $error_code, $error_msg, $postid);
}

function strip_images($var)
{
	$strip_array= array ('addthis.com','gravatar.com');
	foreach ($strip_array as $strip_domain) {
	#echo "looking for ".$strip_domain." in ".$var['src']." <br>";
 	  if (stripos($var['src'],$strip_domain)) {return;}
	}
	return $var;
}

function parse_wordbooker_attributes($attribute_text,$post_id,$timestamp) {
	# Changes various "tags" into their WordPress equivalents.
	$post = get_post($post_id);
	$user_id=$post->post_author; 
	$title=$post->post_title;
	$perma=get_permalink($post->ID);
	$user_info = get_userdata($user_id);
	$blog_url= get_bloginfo('url');
	$wp_url= get_bloginfo('wpurl');
	$blog_name = get_bloginfo('name');
	$author_nice=$user_info->display_name;
	$author_nick=$user_info->nickname;
	$author_first=$user_info->first_name;
	$author_last=$user_info->last_name;

	# Format date and time to the blogs preferences.
	$date_info=date_i18n(get_option('date_format'),$timestamp);
	$time_info=date_i18n(get_option('time_format'),$timestamp);

	# Now do the replacements
	$attribute_text=str_ireplace( '%author%',$author_nice,$attribute_text );
	$attribute_text=str_ireplace( '%first%',$author_first,$attribute_text );
	$attribute_text=str_ireplace( '%wpurl%',$wp_url,$attribute_text );
	$attribute_text=str_ireplace( '%burl%',$blog_url,$attribute_text );
	$attribute_text=str_ireplace( '%last%',$author_last,$attribute_text );
	$attribute_text=str_ireplace( '%nick%',$author_nick,$attribute_text );
	$attribute_text=str_ireplace( '%title%',$title,$attribute_text );
	$attribute_text=str_ireplace( '%link%',$perma,$attribute_text );
	$attribute_text=str_ireplace( '%date%', $date_info ,$attribute_text);
	$attribute_text=str_ireplace( '%time%', $time_info,$attribute_text );

	return $attribute_text;
}


function wordbooker_header($blah){
	# This puts the Meta Description tag into the header and populates it with some text.
	$wordbooker_settings = wordbooker_options(); 
	if ( !isset($wordbooker_settings['wordbook_search_this_header']) ||($wordbooker_settings['wordbook_search_this_header']==100) ) {	
		return;
	}
	if (is_single() || is_page()) {
	$post = get_post($post->ID);
	 	$description = str_replace('"','&quot;',$post->post_content);
		$excerpt = wordbooker_post_excerpt($description,350);	
		$meta_string = sprintf("<meta name=\"description\" content=\"%s\"/>", $excerpt);	
	echo $meta_string;	
	} 
	return $blah;
}

function wordbooker_get_cache($user_id,$field=null) {
	global $wpdb;
	$query_fields='facebook_id,name,url,pic,status,updated,auths_needed';
	if (isset($field)) {$query_fields=$field;}
	$query="select ".$query_fields." from ".WORDBOOKER_USERDATA." where user_ID=".$user_id;
	$result = $wpdb->get_row($query);
	return $result;
}


function wordbooker_check_permissions($wbuser,$user) {
	global $user_ID;
	$perm_miss=wordbooker_get_cache($user_ID,'auths_needed');
	if ($perm_miss->auths_needed==0) { return;}
	$fbclient = wordbooker_fbclient($wbuser);
	$opurl="http://www.facebook.com/connect/prompt_permissions.php?v=";
	$opurlt="&fbconnect=true"."&display=popup"."&extern=1&enable_profile_selector=1";
	$perms_to_check= array(WORDBOOKER_FB_PUBLISH_STREAM,WORDBOOKER_FB_STATUS_UPDATE,WORDBOOKER_FB_READ_STREAM);
	$perm_messages= array('publish content to your Wall/Fan pages','update your status','read your News Feed and Wall');
	$preamble="Wordbooker requires authorization to ";
	$postamble=" on Facebook. Click on the following link to grant permission";
	echo "<br>";
	foreach(array_keys($perms_to_check) as $key){
		# Bit map check to put out the right text for the missing permissions.
		if (pow(2,$key) & $perm_miss->auths_needed ) {
		       $url = $opurl. WORDBOOKER_FB_APIVERSION . "&api_key=". WORDBOOKER_FB_APIKEY ."&ext_perm=" . $perms_to_check[$key] .$opurlt;
		       echo '<p>'.$preamble.$perm_messages[$key].$postamble.'</p><div style="text-align: center;"><a href="'.$url.'" target="facebook"> <img src="http://static.ak.facebook.com/images/devsite/facebook_login.gif" /></a><br></div>';}
		}
		echo "and then save your settings<br>";
		echo '<form action="'.WORDBOOKER_SETTINGS_URL.'" method="post"> <input type="hidden" name="action" value="" />';
		echo '<p style="text-align: center;"><input type="submit" name="perm_save" class="button-primary" value="'. __('Save Configuration').'" /></p></form>';
}

function wordbooker_contributed() {
	global $user_ID;
	$facebook_id=wordbooker_get_cache($user_ID,'facebook_id');
	$contributors=array('100000589976474','892645194','100000384338372','100000818019269','39203171');
	return in_array($facebook_id->facebook_id,$contributors);
}

/******************************************************************************
 * WordPress hooks: update Facebook when a blog entry gets published.
 */

function wordbooker_post_excerpt($excerpt, $maxlength) {
	if (function_exists('strip_shortcodes')) {
		$excerpt = strip_shortcodes($excerpt);
	}
	$excerpt = strip_tags($excerpt);
	# Now lets strip any tags which dont have balanced ends
	$open_tags="[simage,[[CP";
	$close_tags="],]]";
	$open_tag=explode(",",$open_tags);
	$close_tag=explode(",",$close_tags);
	foreach (array_keys($open_tag) as $key) {
		if (preg_match_all('/' . preg_quote($open_tag[$key]) . '(.*?)' . preg_quote($close_tag[$key]) .'/i',$excerpt,$matches)) {
			$excerpt=str_replace($matches[0],"fred" , $excerpt);
		 }
	}
	if (strlen($excerpt) > $maxlength) {
		$excerpt=current(explode("SJA26666AJS", wordwrap($excerpt, $maxlength, "SJA26666AJS")))." ...";	
	}
	return $excerpt;
}

function wordbooker_publish_action($post) {
	global $user_ID, $user_identity, $user_login, $wpdb,$wordbooker_post_options;
	$x = get_post_meta($post->ID, 'wordbooker_options', true); 
		if ($post->post_password != '') {
		/* Don't publish password-protected posts to news feed. */
		return 27;
	}

	# Get the settings from the post_meta.
	foreach (array_keys($x) as $key ) {
		if (substr($key,0,8)=='wordbook') {
			$wordbooker_post_options[$key]=str_replace( array('&amp;','&quot;','&#039;','&lt;','&gt;','&nbsp;&nbsp;'),array('&','"','\'','<','>',"\t"),$x[$key]);
		}
	}

	

	foreach (array_keys($wordbooker_post_options) as $key){
		wordbooker_debugger("Post option : ".$key,$wordbooker_post_options[$key],1000) ;
	}

	$wpuserid=$wordbooker_post_options["wordbook_default_author"];
	if (!($wbuser = wordbooker_get_userdata($wpuserid)) || !$wbuser->session_key) {
		return 28;
	}
	wordbooker_debugger("Posting as user : ",$wpuserid,1000) ;
	/* If publishing a new blog post, update text in "Wordbook" box. */
	$fbclient = wordbooker_fbclient($wbuser);
	if (!wordbooker_postlogged($post->ID)) {
		# Lets see if they want to update their status. We do it this way so you can update your status without publishing!
		if( $wordbooker_post_options["wordbooker_status_update"]=="on") {
			wordbooker_debugger("Setting status_text",$wordbooker_post_options['wordbooker_status_update_text'],1000) ; 
			$status_text = parse_wordbooker_attributes(stripslashes($wordbooker_post_options['wordbooker_status_update_text']),$post->ID,strtotime($post->post_date)); 
			try {
				$fbclient->users_setStatus($status_text);
			    }
			catch (Exception $e) {
				$error_code = $e->getCode();
				$error_msg = $e->getMessage();
				wordbooker_set_userdata_facebook_error($wbuser, 'users_setStatus', $error_code, $error_msg, $post->ID);
		}

		}
		// User has unchecked the publish to facebook option so lets just give up and go home
		#$wbpda=$wordbooker_post_options["wordbooker_publish_default"];
		if ($wordbooker_post_options["wordbooker_publish_default"]!="on") {
			wordbooker_debugger("Publish Default is not Set, Giving up ",$wpuserid,1000) ;
		 	return;
		}
		$results=wordbooker_fbclient_publishaction($wbuser, $fbclient, $post->ID);
		wordbooker_insertinto_postlogs($post->ID);
		$fb_post_id=$results;
		// Has the user decided to collect comments for this post?
		if( isset($wordbooker_post_options["wordbook_comment_get"])){	
			$tstamp=time();
		}
		else
		{	
			$tstamp= time() + (1000 * 7 * 24 * 60 * 60);
		}
		$sql=	' INSERT INTO ' . WORDBOOKER_POSTCOMMENTS . ' (fb_post_id,comment_timestamp,wp_post_id) VALUES ("'.$fb_post_id.'",'.$tstamp.','.$post->ID.')';;
		$result = $wpdb->query($sql);

	}

	return 30;
}

function wordbooker_transition_post_status($newstatus, $oldstatus, $post) {

	if ($newstatus == 'publish') {
		return wordbooker_publish_action($post);
	}

	return 31;	
}

function wordbooker_delete_post($postid) {
	wordbooker_deletefrom_errorlogs($postid);
	wordbooker_deletefrom_postlogs($postid);
	wordbooker_deletefrom_commentlogs($postid);
}


function wordbooker_publish($postid) {
	global $user_ID, $user_identity, $user_login, $wpdb;
	$post = get_post($postid);
        if ( get_post_type($postid) == 'page' ) {return ;}
	if (!current_user_can(WORDBOOKER_MINIMUM_ADMIN_LEVEL)) { return; }
	wordbooker_deletefrom_errorlogs($postid);
	wordbooker_debugger("commence "," ",1000) ; 
	if  ($wordbooker_settings["wordbook_default_author"] == 0 ) {$wb_user_id=$user_ID;} else {$wb_user_id=$wordbooker_settings["wordbook_default_author"];}
	# If the referer is press-this then the user hasn't used the full edit post form so we need to get the blog/user level settings.
	if ( stripos($_POST["_wp_http_referer"],'press-this')) {
		# Get the blog level settings
		$wordbooker_settings = wordbooker_options();
		
		// then get the user level settings and override the blog level settings.
		$wordbook_user_settings_id="wordbookuser".$blog_id;
		$wordbookuser=get_usermeta($wb_user_id,$wordbook_user_settings_id);
		# If we have user settings then lets go through and override the blog level defaults.
		if(is_array($wordbookuser)) {
			foreach (array_keys($wordbookuser) as $key) {
				if ((strlen($wordbookuser[$key])>0) && ($wordbookuser[$key]!="0") ) {
					$wordbooker_settings[$key]=$wordbookuser[$key];
				} 
			}

		}
	
		# Then populate the post array.
		foreach (array_keys($wordbooker_settings) as $key ) {
			if (substr($key,0,8)=='wordbook') {
				$_POST[$key]=str_replace( array('&amp;','&quot;','&#039;','&lt;','&gt;','&nbsp;&nbsp;'),array('&','"','\'','<','>',"\t"),$wordbooker_settings[$key]);
			}
		}
	}

	if ($_POST["wordbook_default_author"]== 0 ) { $_POST["wordbook_default_author"]=$post->post_author; }
	// If soupy isn't set then its a future post so we need to get the meta data
	if (! isset($_POST['soupy'])) {
		# Get the blog level and then the user level settings - just in case this post predates the install.
		$wordbooker_settings = wordbooker_options();

		// then get the user level settings and override the blog level settings.
		$wordbook_user_settings_id="wordbookuser".$blog_id;
		$wordbookuser=get_usermeta($wb_user_id,$wordbook_user_settings_id);
		# If we have user settings then lets go through and override the blog level defaults.
		if(is_array($wordbookuser)) {
			foreach (array_keys($wordbookuser) as $key) {
				if ((strlen($wordbookuser[$key])>0) && ($wordbookuser[$key]!="0") ) {
					$wordbooker_settings[$key]=$wordbookuser[$key];
				} 
			}

		}
		#Now push these into the $_POST array.
		foreach (array_keys($wordbooker_settings) as $key ) {
		if (substr($key,0,8)=='wordbook') {
			$_POST[$key]=str_replace( array('&amp;','&quot;','&#039;','&lt;','&gt;','&nbsp;&nbsp;'),array('&','"','\'','<','>',"\t"),$x[$key]);
		}
	}	

		# now lets get the post meta
		$x = get_post_meta($postid, 'wordbooker_options', true); 
		if(is_array($x)) {
			foreach (array_keys($x) as $key ) {
				if (substr($key,0,8)=='wordbook') {
					$_POST[$key]=str_replace( array('&amp;','&quot;','&#039;','&lt;','&gt;','&nbsp;&nbsp;'),array('&','"','\'','<','>',"\t"),$x[$key]);
				}
			}
		}

	}		

	# Now put the $_POST data into an array
	foreach (array_keys($_POST) as $key ) {
		if (substr($key,0,8)=='wordbook') {
			$wb_params[$key]=str_replace(array('&','"','\'','<','>',"\t",), array('&amp;','&quot;','&#039;','&lt;','&gt;','&nbsp;&nbsp;'),$_POST[$key]);
		}
	}
	$encoded_wb_params=str_replace('\\','\\\\',serialize($wb_params));

	# And write that into the post_meta
	update_post_meta($postid, 'wordbooker_options', $wb_params); 

	$retcode=wordbooker_transition_post_status('publish', null, $post);
	return $retcode;
}

function wordbooker_publish_remote($postid) {
	$post = get_post($postid);
	if( get_post_type($postid) == 'page' ) {return ;}
	if (!current_user_can(WORDBOOKER_MINIMUM_ADMIN_LEVEL)) { return;}
	# Get the blog level settings
	$wordbooker_settings = wordbooker_options();

	// then get the user level settings and override the blog level settings.
	if  ($wordbooker_settings["wordbook_default_author"] == 0 ) {$wb_user_id=$user_ID;} else {$wb_user_id=$wordbooker_settings["wordbook_default_author"];}
	$wordbook_user_settings_id="wordbookuser".$blog_id;
	$wordbookuser=get_usermeta($wb_user_id,$wordbook_user_settings_id);
	# If we have user settings then lets go through and override the blog level defaults.
	if(is_array($wordbookuser)) {
		foreach (array_keys($wordbookuser) as $key) {
			if ((strlen($wordbookuser[$key])>0) && ($wordbookuser[$key]!="0") ) {
				$wordbooker_settings[$key]=$wordbookuser[$key];
			} 
		}

	}
	
	# Then populate the post array.
	foreach (array_keys($wordbooker_settings) as $key ) {
		if (substr($key,0,8)=='wordbook') {
			$_POST[$key]=str_replace( array('&amp;','&quot;','&#039;','&lt;','&gt;','&nbsp;&nbsp;'),array('&','"','\'','<','>',"\t"),$wordbooker_settings[$key]);
		}
	}

	if ($_POST["wordbook_default_author"]== 0 ) { $_POST["wordbook_default_author"]=$post->post_author; }

	# Wrap up the parameters and put them into post_meta;
	foreach (array_keys($_POST) as $key ) {
		if (substr($key,0,8)=='wordbook') {
			$wb_params[$key]=str_replace(array('&','"','\'','<','>',"\t",), array('&amp;','&quot;','&#039;','&lt;','&gt;','&nbsp;&nbsp;'),$_POST[$key]);
		}
	}
	$encoded_wb_params=str_replace('\\','\\\\',serialize($wb_params));
	update_post_meta($postid, 'wordbooker_options', $wb_params); 

	$retcode=wordbooker_transition_post_status('publish', null, $post);
	return $retcode;
} 

function wordbooker_future_post($newstatus, $oldstatus=null, $post=null) {
	global $user_ID, $user_identity, $user_login, $wpdb;
	if (!current_user_can(WORDBOOKER_MINIMUM_ADMIN_LEVEL)) { return;}
	// If this is a future post we need to grab the parameters they've passed in and store them in the database
	if ($newstatus=="future") {
		foreach (array_keys($_POST) as $key ) {
			if (substr($key,0,8)=='wordbook') {
				$wb_params[$key]=str_replace(array('&','"','\'','<','>',"\t",), array('&amp;','&quot;','&#039;','&lt;','&gt;','&nbsp;&nbsp;'),$_POST[$key]);
			}
		}
		$encoded_wb_params=str_replace('\\','\\\\',serialize($wb_params));
		update_post_meta($post->ID, 'wordbooker_options', $wb_params); 
	}
} 


function wordbooker_post_comment($commentid) {
	$wordbooker_settings = wordbooker_options(); 
	if ( !isset($wordbooker_settings['wordbook_comment_push'])) {	
		return;
	}
	global  $wpdb, $user_id,$table_prefix;
	define ('DEBUG', false);
	$debug_file='/tmp/wordbook_'.$table_prefix.'comment_debug';
	if (DEBUG) {
		$fp = fopen($debug_file, 'a');
		$debug_string=date("Y-m-d H:i:s",time())." :  Start \n";
		fwrite($fp, $debug_string);
	}	
	$comment= get_comment($commentid); 
	$cpid = $comment->comment_post_ID;
	$cstatus=$comment->comment_approved;
	$ctext=$comment->comment_content;
	$ctype=$comment->comment_type;
	$caemail=$comment->comment_author_email;
	$cauth=$comment->comment_author;
	$cuid=$comment->user_id;
	$real_comment=true;
	if (($cuid==0) && ($caemail==get_bloginfo( 'admin_email' ))) {$real_comment=false;}
	if ($real_comment) {
		if (DEBUG) {
			$debug_string="FBID : ".$cpid."  stat:".$cstatus."text:".$ctext." type:".$ctype."!!\n";
			fwrite($fp, $debug_string);
		}	
		if ($cstatus==1) {
			$post = get_post($cpid);
			if (DEBUG) {
				$debug_string="Comment author: ".$post->post_author."\n";
				fwrite($fp, $debug_string);
			}
$ctextblock = <<<CODEBLOX

Name : $cauth
Comment: [from blog ] : $ctext

CODEBLOX;

			if (($wbuser = wordbooker_get_userdata($post->post_author)) && $wbuser->session_key) {
				$fbclient = wordbooker_fbclient($wbuser);
				# WE NEED TO CHECK THAT THE FB POST ACTUALLY EXISTS BEFORE WE POST OR it blows up.
				$sql='Select fb_post_id from ' . WORDBOOKER_POSTCOMMENTS . ' where wp_post_id ='.$cpid;
				if (DEBUG) {$debug_string="Comment sql: ".$sql."\n";
				fwrite($fp, $debug_string);}	
				$rows = $wpdb->get_results($sql);
				if (DEBUG) {$debug_string="Comment count: ".count($rows)."\n";
				fwrite($fp, $debug_string);}	
				if (count($rows)>0) {
					foreach ($rows as $comdata_row) {
						$fb_post_id=$comdata_row->fb_post_id;
						#$result2=$fbclient->stream_addComment(null,'54577506873', $fb_post_id , $ctextblock.' ');
						$result2=$fbclient->stream_addComment($fb_post_id , $ctextblock.' ');
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



function wordbooker_debugger($method,$error_msg,$post_id) {
	if (ADVANCED_DEBUG) { 
	global $user_ID,$post_ID,$wpdb;
	$sql=	"INSERT INTO " . WORDBOOKER_ERRORLOGS . " (
				user_id
				, method
				, error_code
				, error_msg
				, postid
			) VALUES (  
				" . $user_ID . "
				, '" . $method . "'
				, -1
				, '" . $error_msg . "'
				, " . $post_ID . "
			)";
	$result = $wpdb->query($sql);
	#echo $sql." - ".$result."<br>";
	usleep(1000000);
	}
}

/******************************************************************************
 * Register hooks with WordPress.
 */

/* Plugin maintenance. */
register_activation_hook(__FILE__, 'wordbooker_activate');
register_deactivation_hook(__FILE__, 'wordbooker_deactivate');
add_action('delete_user', 'wordbooker_delete_user');

define('WORDBOOKER_HOOK_PRIORITY', 10);	/* Default; see add_action(). */

/* Post/page maintenance and publishing hooks. */
add_action('delete_post', 'wordbooker_delete_post');
add_action('xmlrpc_publish_post', 'wordbooker_publish_remote');
add_action('publish_phone', 'wordbooker_publish_remote');
add_action('publish_post', 'wordbooker_publish');
add_action('wb_cron_job', 'wordbooker_poll_facebook');
add_action('delete_post', 'wordbooker_delete_post');
add_action('comment_post', 'wordbooker_post_comment');
add_action('wp_head', 'wordbooker_header');
add_action('transition_post_status', 'wordbooker_future_post',WORDBOOK_HOOK_PRIORITY, 3);

include("wb_widget.php");
include("wordbooker_options.php");
include("wordbooker_cron.php");
?>
