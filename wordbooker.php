<?php
/*
Plugin Name: Wordbooker
Plugin URI: http://blogs.canalplan.org.uk/steve/wordbooker/
Description: Provides integration between your blog and your Facebook account. Navigate to <a href="options-general.php?page=wordbooker">Settings &rarr; Wordbooker</a> for configuration.
Author: Steve Atty 
Author URI: http://blogs.canalplan.org.uk/steve/
Version: 1.8.12
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

global $table_prefix, $wp_version,$wpdb,$db_prefix;

# Consider uisng global $table_prefix,$db_prefix; This would allow us to drop down to one set of tables per DB 

$wordbooker_settings = wordbooker_options(); 
if (! isset($wordbooker_settings['wordbook_extract_length'])) $wordbooker_settings['wordbook_extract_length']=256;

define('WORDBOOKER_DEBUG', false);
define('WORDBOOKER_TESTING', false);
define('WORDBOOKER_CODE_RELEASE','1.8.12.r00');

# For Troubleshooting 
define('ADVANCED_DEBUG',false);

$facebook_config['debug'] = WORDBOOKER_TESTING && !$_POST['action'];
#Wordbooker2 - Dev
#define('WORDBOOKER_FB_APIKEY', '0138375357c1eb1257ed9970ec1a274c');
#define('WORDBOOKER_FB_SECRET', '4310b484ec5236694cfa4b94166aca61');
#define('WORDBOOKER_FB_ID', '111687885534181');

# Wordbooker - live
define('WORDBOOKER_FB_APIKEY', '0cbf13c858237f5d74ef0c32a4db11fd');
define('WORDBOOKER_FB_SECRET', 'df04f22f3239fb75bf787f440e726f31');
define('WORDBOOKER_FB_ID', '254577506873');

define('WORDBOOKER_FB_APIVERSION', '1.0');
define('WORDBOOKER_FB_DOCPREFIX','http://wiki.developers.facebook.com/index.php/');
define('WORDBOOKER_FB_MAXACTIONLEN', 60);
define('WORDBOOKER_FB_PUBLISH_STREAM', 'publish_stream');
define('WORDBOOKER_FB_READ_STREAM', 'read_stream');
define('WORDBOOKER_FB_STATUS_UPDATE',"status_update");
define('WORDBOOKER_FB_CREATE_NOTE',"create_note");
define('WORDBOOKER_SETTINGS', 'wordbooker_settings');
define('WORDBOOKER_OPTION_SCHEMAVERS', 'schemavers');

$old_wb_table_prefix=$table_prefix;
$new_wb_table_prefix=$table_prefix;

#if (substr($wp_version,0,1)=="2") {
#	if (isset ($db_prefix) ) { $new_wb_table_prefix=$db_prefix;}
#}
#if (substr($wp_version,0,1)=="3") {
#	 $new_wb_table_prefix=$wpdb->get_blog_prefix(1);}


define('OLD_WORDBOOKER_ERRORLOGS', $old_wb_table_prefix . 'wordbooker_errorlogs');
define('OLD_WORDBOOKER_POSTLOGS', $old_wb_table_prefix . 'wordbooker_postlogs');
define('OLD_WORDBOOKER_USERDATA', $old_wb_table_prefix . 'wordbooker_userdata');
define('OLD_WORDBOOKER_POSTCOMMENTS', $old_wb_table_prefix . 'wordbooker_postcomments');

define('WORDBOOKER_ERRORLOGS', $new_wb_table_prefix . 'wordbook_errorlogs');
define('WORDBOOKER_POSTLOGS', $new_wb_table_prefix . 'wordbook_postlogs');
define('WORDBOOKER_USERDATA', $new_wb_table_prefix . 'wordbook_userdata');
define('WORDBOOKER_POSTCOMMENTS', $new_wb_table_prefix . 'wordbook_postcomments');


define('WORDBOOKER_EXCERPT_SHORTSTORY', $wordbooker_settings['wordbook_extract_length']); 
define('WORDBOOKER_EXCERPT_WIDEBOX', 96);
define('WORDBOOKER_EXCERPT_NARROWBOX', 40);

define('WORDBOOKER_MINIMUM_ADMIN_LEVEL', 'edit_posts');	/* Contributor role or above. */
define('WORDBOOKER_SETTINGS_PAGENAME', 'wordbooker');
define('WORDBOOKER_SETTINGS_URL', 'options-general.php?page=' . WORDBOOKER_SETTINGS_PAGENAME);

define('WORDBOOKER_SCHEMA_VERSION', 8);

$wordbook_wp_version_tuple = explode('.', $wp_version);
define('WORDBOOKER_WP_VERSION', $wordbook_wp_version_tuple[0] * 10 + $wordbook_wp_version_tuple[1]);

if (function_exists('json_encode')) {
	define('WORDBOOKER_JSON_ENCODE', 'PHP');
} else {
	define('WORDBOOKER_JSON_ENCODE', 'Wordbook');
}

if (function_exists('json_decode')) {
	define('WORDBOOKER_JSON_DECODE', 'PHP');
} else {
	define('WORDBOOKER_JSON_DECODE', 'Wordbook');
}

define('WORDBOOKER_SIMPLEXML', 'PHP');
define('FACEBOOK_PHP_API', 'PHP5');



function wordboker_generateSignature($params, $secret) {
	ksort($params);
	$base_string = '';
	foreach($params as $key => $value) {
		$base_string .= $key . '=' . $value;
	}
	$base_string .= $secret;
	return md5($base_string);
}


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
	if (WORDBOOKER_JSON_DECODE == 'Wordbook') {
	function json_decode($json)
	{ 
	    $comment = false;
	    $out = '$x=';
	   
	    for ($i=0; $i<strlen($json); $i++)
	    {
		if (!$comment)
		{
		    if ($json[$i] == '{')        $out .= ' array(';
		    else if ($json[$i] == '}')    $out .= ')';
		    else if ($json[$i] == ':')    $out .= '=>';
		    else                         $out .= $json[$i];           
		}
		else $out .= $json[$i];
		if ($json[$i] == '"')    $comment = !$comment;
	    }
	    eval($out . ';');
	    return $x;
	} 
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
	$method = 'stream.publish';
	#$message=$post_data['post_attribute']."@[2870211117049:1:One ] @[2870211117049:1:two ] @[2870211117049:1:three] @[2870211117049:1:four ]";
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
	
	wordbooker_debugger("Post Titled : ".$post_data['post_title'],"",$post->ID) ;
	wordbooker_debugger("Post URL : ".$post_data['post_link'],"",$post->ID) ;
	#var_dump($post_data['post_excerpt']);
	
	if ($wordbooker_post_options['wordbook_actionlink']==100) {
		// No action link
		wordbooker_debugger("No action link being used","",$post->ID) ;
	}
	if ($wordbooker_post_options['wordbook_actionlink']==200) {
		// Share This
		wordbooker_debugger("Share Link being used"," ",$post->ID) ;
		$action_links = array(array('text' => __('Share'),'href' => 'http://www.facebook.com/share.php?u='.urlencode($post_data['post_link'])));
	}
	if ($wordbooker_post_options['wordbook_actionlink']==300) {
		// Read Full
		wordbooker_debugger("Read Full link being used"," ",$post->ID) ;
		$action_links = array(array('text' => __('Read entire article'),'href' => $post_data['post_link']));
	}
	#try {
#		$result = $fbclient->notes_create($post_data['post_title'],$post_data['post_excerpt'],100000384338372);
#			} 	
#				catch (Exception $e) {
#					$error_code = $e->getCode();
#					$error_msg = $e->getMessage();
#				}

	// User has chosen to publish to Profile as well as a fan page
	if ($wordbooker_post_options["wordbook_orandpage"]>1) {
		wordbooker_debugger("posting to personal wall and fan wall (if available)","",$post->ID) ;
		if ($wordbooker_post_options['wordbook_actionlink']==100) {
			wordbooker_debugger("posting to personal wall with no action line","",$post->ID) ;
			// No action link
			try {
				$result = $fbclient->stream_publish($message,json_encode($attachment), null);
				wordbooker_debugger("Publish to Personal wall result : ",$result,$post->ID) ;
			} 	
			catch (Exception $e) {
				$error_code = $e->getCode();
				$error_msg = $e->getMessage();
			}
		} else
		{
			wordbooker_debugger("posting to personal wall with action link","",$post->ID) ;
			try {
				$result = $fbclient->stream_publish($message,json_encode($attachment), json_encode($action_links));
				wordbooker_debugger("Publish to Personal wall result : ",$result,$post->ID) ;
			}
			catch (Exception $e) {
				$error_code = $e->getCode();
				$error_msg = $e->getMessage();
				wordbooker_debugger("PW publish fail : ".$error_msg,$error_code,$post->ID) ;
		
			}
		 }
		if ( $wordbooker_post_options["wordbook_page_post"]== -100) { wordbooker_debugger("No Fan Wall post"," ",$post->ID) ; } else {
			wordbooker_debugger("Also posting to Fan wall",$wordbooker_post_options["wordbook_page_post"],$post->ID) ;
			if ($wordbooker_post_options['wordbook_actionlink']==100) {
				// No action link	
				wordbooker_debugger("posting to fan wall with no action link","",$post->ID) ;
				try {
					$result2 = $fbclient->stream_publish($message,json_encode($attachment), null,null,$wordbooker_post_options["wordbook_page_post"]);	
					wordbooker_debugger("Publish to Fan wall result : ",$result2,$post->ID) ;
				}
				catch (Exception $e) {
					$error_code2 = $e->getCode();
					$error_msg2 = $e->getMessage();
					wordbooker_debugger("FW publish fail : ".$error_msg2,$error_code2,$post->ID) ;
				}
			} else
			{
				wordbooker_debugger("posting to fan wall with action link","",$post->ID) ;
				try {
					$result2 = $fbclient->stream_publish($message, json_encode($attachment),json_encode($action_links),null,$wordbooker_post_options["wordbook_page_post"]);
					wordbooker_debugger("Publish to Fan wall result : ",$result2,$post->ID) ;
				}
				catch (Exception $e) {
					$error_code2 = $e->getCode();
					$error_msg2 = $e->getMessage();
					wordbooker_debugger("FW publish fail : ".$error_msg2,$error_code2,$post->ID) ;
				}
			}
		}

	} else {
		# If they actually have a page to post to then we post to it
		
		if ( $wordbooker_post_options["wordbook_page_post"]== -100) { wordbooker_debugger("No Fan Wall post"," ",$post->ID) ; } else {
			wordbooker_debugger("Only posting to Fan wall",$wordbooker_post_options["wordbook_page_post"],$post->ID) ;
			if ($wordbooker_post_options['wordbook_actionlink']==100) {
				// No action link
				wordbooker_debugger("posting to fan wall with no action link","",$post->ID) ;
			try {
				$result2 = $fbclient->stream_publish($message,json_encode($attachment), null,null,$wordbooker_post_options["wordbook_page_post"]);
				wordbooker_debugger("Publish to Fan wall result : ",$result2,$post->ID) ;
			}
			catch (Exception $e) {
				$error_code2 = $e->getCode();
				$error_msg2 = $e->getMessage();
				wordbooker_debugger("FW publish fail : ".$error_msg2,$error_code2,$post->ID) ;
			}
			} else
			{
				wordbooker_debugger("posting to fan wall with action link","",$post->ID) ;
				try {
					$result2 = $fbclient->stream_publish($message, json_encode($attachment),json_encode($action_links),null,$wordbooker_post_options["wordbook_page_post"]);
					wordbooker_debugger("Publish to Fan wall result : ",$result2,$post->ID) ;
				}
				catch (Exception $e) {
					$error_code2 = $e->getCode();
					$error_msg2 = $e->getMessage();
					wordbooker_debugger("FW publish fail : ".$error_msg2,$error_code2,$post->ID) ;
				}
			}
		}
	}
	return array($result, $error_code, $error_msg, $method,$result2,$error_code2,$error_msg2);
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
		wordbooker_debugger("Called Get Session - returns  : ","XX".$result."XX",0) ;

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
	delete_option(WORDBOOKER_SETTINGS);
	delete_option('wordbook_settings');
	wp_clear_scheduled_hook('wb_cron_job');
	foreach (array(
				OLD_WORDBOOKER_ERRORLOGS,
				OLD_WORDBOOKER_POSTLOGS,
				OLD_WORDBOOKER_USERDATA,
				OLD_WORDBOOKER_POSTCOMMENTS,
				WORDBOOKER_ERRORLOGS,
				WORDBOOKER_POSTLOGS,
				WORDBOOKER_USERDATA,
				WORDBOOKER_POSTCOMMENTS,
				) as $tablename) {
			$result = $wpdb->query("DROP TABLE IF EXISTS $tablename");
		}

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
			`post_id` BIGINT(20) NOT NULL
			, `timestamp` TIMESTAMP
		)
		');
	if ($result === false)
		$errors[] = __('Failed to create ') . WORDBOOKER_POSTLOGS;

	$result = $wpdb->query('
		CREATE TABLE IF NOT EXISTS ' . WORDBOOKER_ERRORLOGS . ' (
			`timestamp` TIMESTAMP
			, `user_ID` BIGINT(20) UNSIGNED NOT NULL
			, `method` VARCHAR(255) NOT NULL
			, `error_code` INT NOT NULL
			, `error_msg` VARCHAR(80) NOT NULL
			, `post_id` BIGINT(20) NOT NULL
		)
		');
	if ($result === false)
		$errors[] = __('Failed to create ') . WORDBOOKER_ERRORLOGS;

	$result = $wpdb->query('
		CREATE TABLE IF NOT EXISTS ' . WORDBOOKER_USERDATA . ' (
			  `user_ID` bigint(20) unsigned NOT NULL,
			  `use_facebook` tinyint(1) NULL default 1,
			  `onetime_data` longtext NULL,
			  `facebook_error` longtext NULL,
			  `secret` varchar(80)  NULL,
			  `session_key` varchar(80) NULL,
			  `facebook_id` varchar(40) NULL,
			  `name` varchar(250) NULL,
			  `status` varchar(1024) default NULL,
			  `updated` int(20) NULL,
			  `url` varchar(250)  NULL,
			  `pic` varchar(250)  NULL,
			  `pages` varchar(10240)  NULL,
			  `auths_needed` int(1) NULL,
			  `blog_id` bigint(20)  NULL,
			  PRIMARY KEY  (`user_ID`),
			  KEY `facebook_idx` (`facebook_id`)
		)
		');
	if ($result === false)
		$errors[] = __('Failed to create ') . WORDBOOKER_USERDATA;

	$result = $wpdb->query('
		CREATE TABLE IF NOT EXISTS ' . WORDBOOKER_POSTCOMMENTS . ' (
		  `fb_post_id` varchar(40) NOT NULL,
		  `comment_timestamp` int(20) NOT NULL,
		  `wp_post_id` int(11) NOT NULL,
		   UNIQUE KEY `fb_comment_id` (`fb_post_id`,`wp_post_id`)
		)
		');
	if ($result === false)
		$errors[] = __('Failed to create ') . WORDBOOKER_POSTCOMMENTS;

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
	if (! isset($wordbooker_settings[WORDBOOKER_OPTION_SCHEMAVERS])) {$wordbooker_settings[WORDBOOKER_OPTION_SCHEMAVERS]=1;}
	if ($wordbooker_settings[WORDBOOKER_OPTION_SCHEMAVERS]< WORDBOOKER_SCHEMA_VERSION ) { 
		 _e("Database changes being applied");
	} else {
		return;
	}
	# Tidy up old data
	$sql=	' DELETE FROM  ' . WORDBOOKER_POSTCOMMENTS . ' where fb_post_id="" ';
	$result = $wpdb->query($sql);	

	if ($wordbooker_settings[WORDBOOKER_OPTION_SCHEMAVERS]==1 ) {
		$result = $wpdb->query('
			ALTER TABLE ' . WORDBOOKER_USERDATA . ' 
				ADD `facebook_id` VARCHAR( 40 ) NULL ,
				ADD `name` VARCHAR( 250 )  NULL ,
				ADD `status` VARCHAR( 1024 ) default NULL ,
				ADD `updated` INT( 20 ) NULL ,
				ADD `url` VARCHAR( 250 ) default NULL ,
				ADD `pic` VARCHAR( 250 ) default NULL ,
				ADD `pages` VARCHAR( 2048 ) default NULL,
				ADD `auths_needed` int(1) NULL,
				ADD  `blog_id` bigint(20) NULL
			');
		if ($result === false)  $errors[] = __('Failed to update ') . WORDBOOKER_USERDATA;

		$result = $wpdb->query('ALTER TABLE ' . WORDBOOKER_USERDATA . ' ADD PRIMARY KEY ( `user_ID` ) ');
		if ($result === false)  $errors[] = __('Failed to update ') . WORDBOOKER_USERDATA;
	
		$result = $wpdb->query('ALTER TABLE ' . WORDBOOKER_USERDATA . ' ADD INDEX `facebook_idx` ( `facebook_id` ) ');
		if ($result === false)  $errors[] = __('Failed to update ') . WORDBOOKER_USERDATA;

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

	if ($wordbooker_settings[WORDBOOKER_OPTION_SCHEMAVERS]==2 ) {
		$result = $wpdb->query('
			ALTER TABLE ' . WORDBOOKER_USERDATA . ' 
				CHANGE `onetime_data` `onetime_data` LONGTEXT NULL ,
				CHANGE `facebook_error` `facebook_error` LONGTEXT  NULL ,
				CHANGE `session_key` `session_key` VARCHAR( 80 )  NULL ,
				CHANGE `facebook_id` `facebook_id` VARCHAR( 40 )  NULL ,
				CHANGE `name` `name` VARCHAR( 250 )  NULL ,
				CHANGE `updated` `updated` INT( 20 ) NULL ,
				CHANGE `auths_needed` `auths_needed` INT( 1 ) NULL ,
				CHANGE `blog_id` `blog_id` BIGINT( 20 ) NULL
			');
		if ($result === false)  $errors[] = __('Failed to update ') . WORDBOOKER_USERDATA;
		if ($errors) {
			echo '<div id="message" class="updated fade">' . "\n";
			foreach ($errors as $errormsg) {
				_e("$errormsg<br />\n");
			}
			echo "</div>\n";
		}

		# All done, set the schemaversion to version 3. NOT the current version, as this allow us to string updates.
		wordbooker_set_option(WORDBOOKER_OPTION_SCHEMAVERS, 3);
	}

	if ($wordbooker_settings[WORDBOOKER_OPTION_SCHEMAVERS]==3 ) {	

	foreach (array(
				OLD_WORDBOOKER_ERRORLOGS,
				OLD_WORDBOOKER_POSTLOGS,
				OLD_WORDBOOKER_USERDATA,
				OLD_WORDBOOKER_POSTCOMMENTS,
				) as $tablename) {
			$result = $wpdb->query("DROP TABLE IF EXISTS $tablename");;
		}

		# All done, set the schemaversion to version 4. NOT the current version, as this allow us to string updates.
		wordbooker_set_option(WORDBOOKER_OPTION_SCHEMAVERS, 4);
	}

	if ($wordbooker_settings[WORDBOOKER_OPTION_SCHEMAVERS]==4 ) {
		$result = $wpdb->query('DROP TABLE IF EXISTS '. WORDBOOKER_ERRORLOGS);	
		$result = $wpdb->query('
				CREATE TABLE IF NOT EXISTS ' . WORDBOOKER_ERRORLOGS . ' (
					`timestamp` TIMESTAMP
					, `user_ID` BIGINT(20) UNSIGNED NOT NULL
					, `method` VARCHAR(255) NOT NULL
					, `error_code` INT NOT NULL
					, `error_msg` VARCHAR(80) NOT NULL
					, `post_id` BIGINT(20) NOT NULL
				)
				');

	# All done, set the schemaversion to version 5. NOT the current version, as this allow us to string updates.
		wordbooker_set_option(WORDBOOKER_OPTION_SCHEMAVERS, 5);
	}

	if ($wordbooker_settings[WORDBOOKER_OPTION_SCHEMAVERS]== 5 ) {
	# Do nothing - this is a Cron fix
	# All done, set the schemaversion to version 5. NOT the current version, as this allow us to string updates.

		wordbooker_set_option(WORDBOOKER_OPTION_SCHEMAVERS, 6);
	}

	if ($wordbooker_settings[WORDBOOKER_OPTION_SCHEMAVERS]== 6 ) {
	# Do nothing - this is a Cron fix
	# All done, set the schemaversion to version 7. NOT the current version, as this allow us to string updates.

		wordbooker_set_option(WORDBOOKER_OPTION_SCHEMAVERS, 7);
	}
	# Clear and re-instate the cron - just to be tidy.


	if ($wordbooker_settings[WORDBOOKER_OPTION_SCHEMAVERS]==7 ) {
		$sql='ALTER TABLE '. WORDBOOKER_USERDATA.  ' CHANGE `pages` `pages` VARCHAR( 10240 )  ';
		$result = $wpdb->query($sql);

	# All done, set the schemaversion to version 5. NOT the current version, as this allow us to string updates.
		wordbooker_set_option(WORDBOOKER_OPTION_SCHEMAVERS, 8);
	}

	$dummy=wp_clear_scheduled_hook('wb_cron_job');
	$dummy=wp_schedule_event(time(), 'hourly', 'wb_cron_job');
	wp_cache_flush();
}

function wordbooker_delete_user($user_id) {
	global $wpdb;
	$errors = array();
	foreach (array(WORDBOOKER_USERDATA,WORDBOOKER_ERRORLOGS,) as $tablename) {
		$result = $wpdb->query('DELETE FROM ' . $tablename . ' WHERE user_ID = ' . $user_id . '');
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
	return wordbooker_set_userdata2( $wbuser->onetime_data, $wbuser->facebook_error, $wbuser->secret, $wbuser->session_key,$wbuser->user_ID);
}

function wordbooker_set_userdata_facebook_error($wbuser, $method, $error_code, $error_msg, $postid) {
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
	$result = $wpdb->query('
		DELETE FROM ' . WORDBOOKER_POSTLOGS . '
		WHERE timestamp < DATE_SUB(CURDATE(), INTERVAL 365 DAY)
		');
}

function wordbooker_postlogged($postid) {
	global $wpdb,$wordbooker_post_options;
	// See if the user has overridden the repost on edit - i.e. they want to publish and be damned!
	if (isset ($wordbooker_post_options["wordbooker_publish_override"])) { 
		wordbooker_debugger("Publish override in force: "," ",$postid) ;
		return false;
	}
	$wordbooker_settings =wordbooker_options(); 
	// Does the user want us to ever publish on Edit? If not then return true
	if ( (! isset($wordbooker_settings["wordbook_republish_time_obey"])) && ($_POST['original_post_status']=='publish')) { 
		return true;
	}
	if (! isset($wordbooker_settings['wordbook_republish_time_frame'])) $wordbooker_settings['wordbook_republish_time_frame']='10';
	$rows = $wpdb->get_results('
		SELECT *
		FROM ' . WORDBOOKER_POSTLOGS . '
		WHERE post_id = ' . $postid . '
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
				post_id
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
		WHERE post_id = ' . $postid . '
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
	return '<a href="'. WORDBOOKER_FB_DOCPREFIX . $method . '"'. ' title="Facebook API documentation" target="facebook"'. '>'. $method. '</a>';
}

function wordbooker_trim_errorlogs() {
	global $wpdb;
	$result = $wpdb->query('
		DELETE FROM ' . WORDBOOKER_ERRORLOGS . '
		WHERE timestamp < DATE_SUB(CURDATE(), INTERVAL 7 DAY) ');
}

function wordbooker_clear_errorlogs() {
	global $user_ID, $wpdb;
	$result = $wpdb->query('
		DELETE FROM ' . WORDBOOKER_ERRORLOGS . '
		WHERE user_ID = ' . $user_ID . ' and error_code > -1');
	if ($result === false) {
		echo '<div id="message" class="updated fade">';
		_e('Failed to clear error logs.');
		echo "</div>\n";
	}
}


function wordbooker_clear_diagnosticlogs() {
	global $user_ID, $wpdb;
	$result = $wpdb->query('
		DELETE FROM ' . WORDBOOKER_ERRORLOGS . '
		WHERE  error_code <= -1');
	if ($result === false) {
		echo '<div id="message" class="updated fade">';
		_e('Failed to clear Diagnostic logs.');
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
			'post_id' => $postid,
			),
		array('%d', '%s', '%d', '%s', '%d')
		);	 
}

function wordbooker_deletefrom_errorlogs($postid) {
	global $wpdb;
	$result = $wpdb->query('DELETE FROM ' . WORDBOOKER_ERRORLOGS . ' WHERE post_id = ' . $postid );
}

function wordbooker_render_errorlogs() {
	global $user_ID, $wpdb;

	$rows = $wpdb->get_results('SELECT * FROM ' . WORDBOOKER_ERRORLOGS . ' WHERE user_ID = ' . $user_ID . ' and error_code > -1  ORDER BY timestamp');
	if ($rows) {
?>
	<h3><?php _e('Errors'); ?></h3>
	<div class="wordbook_errors">
	<p>
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
			$hyperlinked_post = '<a href="'. get_permalink($row->postid) . '">'. apply_filters('the_title',get_the_title($row->postid)) . '</a>';
		}
		$hyperlinked_method= wordbooker_hyperlinked_method($row->method);
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
	<hr>
<?php
	}
}

function wordbooker_render_diagnosticlogs() {
	global $user_ID, $wpdb;
	$rows = $wpdb->get_results('SELECT * FROM ' . WORDBOOKER_ERRORLOGS . ' WHERE error_code <= -1 order by error_code desc');
	if ($rows) {
?>
	<h3><?php _e('Diagnostic Messages'); ?></h3>
	<div class="wordbook_errors">
	<p>
	</p>
	<table class="wordbook_errorlogs">
		<tr>
			<th>Timestamp</th>
			<th>Post</th>
			<th>Diagnostic Message</th>
			<th>Diagnostic Data</th>
		</tr>

<?php
	foreach ($rows as $row) {
		$hyperlinked_post = '';
		if (($post = get_post($row->postid))) {
			$hyperlinked_post = '<a href="'
				. get_permalink($row->postid) . '">'
				. $post->post_title . '</a>';
		}
?>

		<tr>
			<td><?php echo $row->timestamp; ?></td>
			<td><?php echo $hyperlinked_post; ?></td>
			<td><?php echo $row->method; ?></td>
			<td><?php echo $row->error_msg; ?></td>
		</tr>

<?php
		}
?>

	</table>
	<form action="<?php echo WORDBOOKER_SETTINGS_URL; ?>" method="post">
		<input type="hidden" name="action" value="clear_diagnosticlogs" />
		<p class="submit" style="text-align: center;">
		<input type="submit" value="<?php _e('Clear Diagnostic Messages'); ?>" />
		</p>
	</form>
	</div>
	<hr>
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

	case 'clear_diagnosticlogs':
		wordbooker_clear_diagnosticlogs();
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

	if (!function_exists('curl_init')) {
	#$errormsg .=  'Wordbooker needs the CURL PHP extension to work. Please install / enable it and try again <br />';
	}
	if (!function_exists('json_decode')) {
	 	$errormsg .=  'Wordbooker needs the JSON PHP extension.  Please install / enable it and try again <br />';
	}

	if (!function_exists('simplexml_load_string')) {
		$errormsg .=  "Your PHP install is missing <code>simplexml_load_string()</code> <br />";
	}

	if (WORDBOOKER_WP_VERSION < 27) {
		$errormsg .= sprintf(__('Wordbooker requires <a href="%s">WordPress</a>-2.7 or newer (you appear to be running version %s).'),'http://wordpress.org/download/', $wp_version)."<b /r>";
	} else if (!($options = wordbooker_options()) ||
			!($wbuser = wordbooker_get_userdata($user_ID)) ||
			( !$wbuser->session_key)) {
		$errormsg .=__("Wordbooker needs to be set up")."<br />";
	} else if ($wbuser->facebook_error) {
		$method = $wbuser->facebook_error['method'];
		$error_code = $wbuser->facebook_error['error_code'];
		$error_msg = $wbuser->facebook_error['error_msg'];
		$postid = $wbuser->facebook_error['postid'];
		$suffix = '';
		if ($postid != null && ($post = get_post($postid))) {
			wordbooker_deletefrom_postlogs($postid);
			$suffix = __('for').' <a href="'. get_permalink($postid) . '">'. get_the_title($postid) . '</a>';
		}
		$errormsg .= sprintf(__("<a href='%s'>Wordbooker</a> failed to communicate with Facebook" . $suffix . ": method = %s, error_code = %d (%s). Your blog is OK, but Facebook didn't get the update."), WORDBOOKER_SETTINGS_URL,wordbooker_hyperlinked_method($method),$error_code,$error_msg)."<br />";
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




function get_check_session(){
	global $facebook2,$user_ID;

	# This function basically checks for a stored session and if we have one it returns it, If we have no stored session then it gets one and stores it
	# OK lets go to the database and see if we have a session stored
	wordbooker_debugger("Getting Userdata "," ",0) ;
	$session=wordbooker_get_userdata($user_ID);
	if (is_array($session)) {
		wordbooker_debugger("Session found. Check validity "," ",0) ;
		# We have a session ID so lets not get a new one
		# Put some session checking in here to make sure its valid
		try {
		wordbooker_debugger("Calling GRAPH API : get current user "," ",0) ;
		$attachment =  array('access_token' => $session_id[0],);
		$ret_code=$facebook2->api('/me', 'GET', $attachment);
		}
		catch (Exception $e) {
		# We don't have a good session so
		wordbooker_debugger("User Session invalid - clear down data "," ",0) ;
		wordbooker_delete_user($user_ID);
		return;

	}
		return $session[0];
	} 
	else 
	{
		# Are we coming back from a login with a session set? 
		#echo $_GET['check']."<br>";
		#echo $_GET['session']."<br>";
		#echo get_magic_quotes_gpc();
		$_GET['session']=stripslashes($_GET['session']);
		if (strpos($_GET['session'],"{")===false) {$_GET['session']="{".$_GET['session'];}
		if (!strpos($_GET['session'],"}")) {$_GET['session']=$_GET['session']."}";}

		# What we are doing here is cheating and rebuilding the sig in case it has got corrupted.
        	$session = (array) json_decode($_GET['session']);
    		$session_without_sig = $session;
      		unset($session_without_sig['sig']);

      		$expected_sig = wordboker_generateSignature($session_without_sig,WORDBOOKER_FB_SECRET);

		$session['sig']=$expected_sig ;
		$_GET['session']=json_encode($session);

		wordbooker_debugger("Checking session (2) "," ",0) ;
		$session = $facebook2->getSession();
		if (is_array($session)) {
		wordbooker_debugger("Session found. Store it "," ",0) ;
			# Yes! so lets store it!y)
		wordbooker_set_userdata($onetime_data, $facebook_error, $secret,$session['session_key']);
			return $session['access_token'];
		}
		
	} 
}




function wordbooker_option_setup($wbuser) {
?>

	<h3><?php _e('Setup'); ?></h3>
	<div class="wordbook_setup">
<?php

	require("fb2/facebook.php");	

	// Create our Application instance.
	global $facebook2;
	$facebook2 = new Facebook2(array(
	  'appId'  => WORDBOOKER_FB_ID,
	  'secret' => WORDBOOKER_FB_SECRET,
	  'cookie' => false,
	));

	# Lets set up the permissions we need and set the login url in case we need it.
	$par['req_perms'] = "publish_stream,offline_access,user_status,read_stream,email,user_groups,manage_pages";
	$par['next'] = "http://ccgi.pemmaquid.plus.com/cgi-bin/index.php?br=".urlencode(get_bloginfo('wpurl')."");
	$par['cancel_url']= "http://ccgi.pemmaquid.plus.com/cgi-bin/index.php";
	$loginUrl = $facebook2->getLoginUrl($par);
	wordbooker_debugger("Checking Session "," ",0) ;
	$access_token=get_check_session();
	# If we've not got an access_token we need to login

	if ( is_null($access_token) ) {
	wordbooker_debugger("No session found - lets login and authorise "," ",0) ;
			echo "We need to authorise Wordbooker with your Facebook Account. Please click on the following link and follow the instructions <br / ><br />";
			echo '<a href="'. $loginUrl.'"> <img src="http://static.ak.fbcdn.net/rsrc.php/zB6N8/hash/4li2k73z.gif" alt="Facebook Login Button" /> </a><br />';
	}
	 else  {
		wordbooker_debugger("Everything looks good so lets ask them to refresh "," ",0) ;
			echo'Wordbooker should now be authorised. Please click on the Reload Page Button <br> <form action="options-general.php?page=wordbooker" method="post">';
		echo '<p style="text-align: center;"><input type="submit" name="perm_save" class="button-primary" value="'. __('Reload Page').'" /></p>';
		echo '</form> '; 
	}
	echo "</div></div>";
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
		<a href="<?php echo $result->url; ?>"><?php echo $result->name; ?></a> ( <?php echo $result->facebook_id; ?> )<br /><br />
		<i><?php echo $result->status; ?></i><br />
		(<?php echo date('D M j, g:i a', $result->updated); ?>).
		<br /><br />


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
			echo"<p>".__('Wordbooker appears to be configured and working just fine').".</p>";
			wordbooker_check_permissions($wbuser,$user);	
			echo "<p>".__("If you like, you can start over from the beginning (this does not delete your posting and comment history)").":</p>";
		} 
		else 
		{
			echo "<p>".__('Wordbooker is able to connect to Facebook').'</p>';
			echo "<p>".__('Next, add the').' <a href="http://www.facebook.com/apps/application.php?id=254577506873" target="facebook">Wordbooker</a> '.__("application to your Facebook profile").':</p>';
			echo '<div style="text-align: center;"><a href="http://www.facebook.com/add.php?api_key=<?php echo WORDBOOKER_FB_APIKEY; ?>" target="facebook"><img src="http://static.ak.facebook.com/images/devsite/facebook_login.gif" /></a></div><p>';
			_e( 'Or, you can start over from the beginning');

		} 
	}
	 else
	 {

		echo "<p>".__('Wordbooker is configured and working, but').' <a href="http://developers.facebook.com/documentation.php?v=1.0&method=users.getInfo" target="facebook">facebook.users.getInfo</a>'. __('failed (no Facebook user for uid)'). ' :' .$fb_info->facebook_id.'</p> <p>'.__('This may be a transitory error but it if persists you could try resetting the configuration').'</p>';

	}

	echo'<form action="" method="post">';

	echo '<p style="text-align: center;"><input type="submit"  class="button-primary" name="reset_user_config"  value="'._('Reset Configuration').'" />';
	echo '&nbsp;&nbsp;<input type="submit" name="perm_save" class="button-primary" value="'. __('Refresh Status').'" /></p>';
	echo '</form> </div>';



    $description=__("Recent Facebook Activity for this site");
    #$iframe='<iframe src="http://www.facebook.com/plugins/activity.php?site='.get_bloginfo('url').'&amp;width=600&amp;height=400&amp;header=true&amp;colorscheme=light" scrolling="yes" frameborder="0" allowTransparency="true" style="border:none; overflow:hidden; width:600px; height:400px"></iframe>';
    $iframe='<iframe src="http://www.facebook.com/plugins/activity.php?site='.get_bloginfo('url').'&amp;width=600&amp;height=400&amp;header=true&amp;colorscheme=light" scrolling="yes" frameborder="0" style="border:none; overflow:hidden; width:600px; height:400px"></iframe>';
    $activity="<hr><div class='wrap'<h3>".__("Recent Facebook Activity for this site")."</h3><p>$iframe</p></div>";
$options = wordbooker_options();
#var_dump($options);
   if (isset($options["wordbooker_like_button_show"])) { echo $activity; }




}

function wordbooker_version_ok($currentvers, $minimumvers) {
	#Lets strip out the text and any other bits of crap so all we're left with is numbers.
	$currentvers=trim(preg_replace("/[^0-9.]/ ", "", $currentvers ));
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
	global $wp_version,$wpdb;
	$wordbooker_settings=wordbooker_options();
?>
	<h3><?php _e('Support'); ?></h3>
	<div class="wordbook_support">
	<?php _e('For feature requests, bug reports, and general support :'); ?>
	<ul>	
	<li><?php _e('Check the '); ?><a href="../wp-content/plugins/wordbooker/wordbooker_user_guide.pdf" target="wordpress"><?php _e('User Guide'); ?></a>.</li>
	<li><?php _e('Check the '); ?><a href="http://wordpress.org/extend/plugins/wordbooker/other_notes/" target="wordpress"><?php _e('WordPress.org Notes'); ?></a>.</li>
	<li><?php _e('Try the '); ?><a href="http://www.facebook.com/apps/application.php?v=app_2373072738&id=254577506873" target="facebook"><?php _e('Wordbooker Discussion Board'); ?></a>.</li>
		<li><?php _e('Enhancement requests can be made at the '); ?><a href="http://code.google.com/p/wordbooker/" target="facebook"><?php _e('Wordbooker Project on Google Code'); ?></a>.</li>
	<li><?php _e('Consider upgrading to the '); ?><a href="http://wordpress.org/download/"><?php _e('latest stable release'); ?></a> <?php _e(' of WordPress. '); ?></li>
	<li><?php _e('Read the release notes for Wordbooker on the '); ?><a href="http://blogs.canalplan.org.uk/steve/wordbooker/">Wordbooker</a> <?php _e('page. '); ?></li>
	</ul>
	<br />
	<?php _e('Please provide the following information about your installation:'); ?>
	<ul>
<?php
	$active_plugins = get_option('active_plugins');
	$plug_info=get_plugins();
	$phpvers = phpversion();
	$jsonvers=phpversion('json');
	$mysqlvers = function_exists('mysql_get_client_info') ? mysql_get_client_info() :  'Unknown';
	# If we dont have the function then lets go and get the version the old way
	if ($mysqlvers=="Unknown") {
		$t=mysql_query("select version() as ve");
		$r=mysql_fetch_object($t);
		$mysqlvers =  $r->ve;
	}
	$http_coding="No Multibyte support";
	$int_coding="No Multibyte support";
	$mb_language="No Multibyte support";
	#$t=mysql_query("show variables like 'character%'");
	if (function_exists('mb_convert_encoding')) {
		$http_coding=mb_http_output();
		$int_coding=mb_internal_encoding();
		$mb_language=mb_language();
	}

	if ($fp = fopen('http://api.facebook.com/restserver.php', 'r')) {
	   $content = '';
	   while ($line = fread($fp, 1024)) {
	      $content.=$line;
	   }
	}
	$fopenstat="Fopen cannot access Facebook ";

	if (strlen($content)> 6) {$fopenstat="Fopen can access Facebook - All is OK";} 
	$curlcontent="Curl is not installed";
	if (function_exists('curl_init')) {
	   $ch = curl_init();
	   curl_setopt($ch, CURLOPT_URL, 'http://api.facebook.com/restserver.php');
	   curl_setopt($ch, CURLOPT_HEADER, 0);
	   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	   curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');
	   $curlcontent = curl_exec($ch);
	   curl_close($ch);
	}
	$curlstatus="Curl cannot access Facebook";
	if (strlen($curlcontent)>6) {$curlstatus="Curl can access Facebook - All is OK";}

	$info = array(	
		'Wordbooker' => $plug_info['wordbooker/wordbooker.php']['Version'],
		'Wordbooker Schema' => $wordbooker_settings[WORDBOOKER_OPTION_SCHEMAVERS],
		'WordPress' => $wp_version,
		 'PHP' => $phpvers,
		'JSON Encode' => WORDBOOKER_JSON_ENCODE,
		'JSON Decode' => WORDBOOKER_JSON_DECODE,
		'Curl Status' => $curlstatus,
		'Fopen Status' => $fopenstat,
		'JSON Version' => $jsonvers,
		'SimpleXML library' => WORDBOOKER_SIMPLEXML,
		'HTTP Output Character Encoding'=>$http_coding,
		'Internal PHP Character Encoding'=>$int_coding,
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
		_e("<li>XML: your PHP is missing <code>simplexml_load_string()</code></li>");
	}
	if (class_exists('Facebook')) {
		if (!method_exists( 'FacebookRestClient', 'stream_publish' ) ){
			echo "<li>Facebook API: <b>".__('Your client library is missing <code>stream_publish</code>. Please check for other Facebook plugins')."</b></li>";
		}
	}
	$rows = $wpdb->get_results("show variables like 'character_set%'");
	foreach ($rows as $chardata){
		echo "<li> Database ". $chardata->Variable_name ." : <b> ".$chardata->Value ."</b></li>";
	}
	$rows = $wpdb->get_results("show variables like 'collation%'");
	foreach ($rows as $chardata){
		echo "<li> Database ". $chardata->Variable_name ." : <b> ".$chardata->Value ."</b></li>";
	}
	echo "<li> Server : <b>".$_SERVER['SERVER_SOFTWARE']."</b></li>";
	_e("<li> Active Plugins : <b></li>");	
	 foreach($active_plugins as $name) {
		if ( $plug_info[$name]['Title']!='Wordbooker') {
		echo "&nbsp;&nbsp;&nbsp;".$plug_info[$name]['Title']." ( ".$plug_info[$name]['Version']." ) <br />";}
	}
	echo "</b>";

	if (ADVANCED_DEBUG) { phpinfo(INFO_MODULES);}
?>
	</ul>

<?php
	if ($version_errors) {
?>

	<div class="wordbook_errorcolor">
	<?php _e('Your system does not meet the'); ?> <a href="http://wordpress.org/about/requirements/"><?php _e('WordPress minimum requirements'); ?></a>. <?php _e('Things are unlikely to work.'); ?>
	</div>

<?php
	} else if ($mysqlvers == 'Unknown') {
?>

	<div>
	<?php _e('Please ensure that your system meets the'); ?> <a href="http://wordpress.org/about/requirements/"><?php _e('WordPress minimum requirements'); ?></a>.
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
		<p><?php _e('There was a problem with the one-time code'); ?> " <?php echo $result['onetimecode']; ?>": <a href="http://wiki.developers.facebook.com/index.php/Auth.getSession" target="facebook">error_code = <?php echo $result['error_code']; ?> (<?php echo $result['error_msg']; ?>)</a>. <?php _e('Try re-submitting it, or try generating a new one-time code.'); ?></p>
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

function wordbooker_fbclient_facebook_finish($wbuser, $result, $method,$error_code, $error_msg, $postid,$result2, $error_code2, $error_msg2) 
{	
wordbooker_debugger("All done","",1000) ;
	if ($error_code) {
		wordbooker_set_userdata_facebook_error($wbuser, $method, $error_code, $error_msg, $postid);
	}

	if ($error_code2) {
		wordbooker_set_userdata_facebook_error($wbuser, $method, $error_code2, $error_msg2, $postid);
	}
 If ((! $error_code) && (! $error_code2)) 
 	{
		wordbooker_clear_userdata_facebook_error($wbuser);
	}
	# We should put the post logging into here, just makes it neater 

	return array($result,$result2);
}

function wordbooker_fbclient_publishaction($wbuser, $fbclient,$postid) 
{	
	global $wordbooker_post_options;
	$post = get_post($postid);
	$post_link = get_permalink($postid);
#	if (stripos($post_link,get_bloginfo('url')===false)) {
#		$post_link=get_bloginfo('url').$post_link;
#	}
	$post_title=$post->post_title;
	$post_content = $post->post_content;
	# Grab the content of the post once its been filter for display - this converts app tags into HTML so we can grab gallery images etc.
	$processed_content ="!!!".apply_filters('the_content', $post_content);

	

	if ( function_exists( 'get_the_post_thumbnail' ) ) { 
		wordbooker_debugger("Getting the thumnail image"," ",$post->ID) ;
		preg_match_all('/<img \s+ ([^>]*\s+)? src \s* = \s* [\'"](.*?)[\'"]/ix',get_the_post_thumbnail($postid), $matches_tn); 
	}
	
	preg_match_all('/<img \s+ ([^>]*\s+)? src \s* = \s* [\'"](.*?)[\'"]/ix',get_post_meta($post->ID, 'image', TRUE), $matches_ct); 
	$matches=$matches_ct;
	if ( function_exists( 'get_the_post_thumbnail' ) ) { 
		$matches=array_merge($matches_tn,$matches_ct);
	}
   

	$yturls = array();
	# If the user only wants the thumbnail then we can simply not do the skim over the processed images
	if (! isset($wordbooker_post_options["wordbook_thumb_only"]) ) {
		wordbooker_debugger("Getting the rest of the images "," ",$post->ID) ;
		preg_match_all('/<img \s+ ([^>]*\s+)? src \s* = \s* [\'"](.*?)[\'"]/ix',$processed_content, $matched);

		$x=strip_shortcodes($post_content);
		preg_match_all( '#http://(www.youtube|youtube|[A-Za-z]{2}.youtube)\.com/(watch\?v=|w/\?v=|\?v=)([\w-]+)(.*?)#i', $x, $matches3 );
		if (is_array($matches3[3])) {
			foreach ($matches3[3] as $key ) {
				$yturls[]='http://img.youtube.com/vi/'.$key.'/0.jpg';
			}
		}

		
	}
	if ( function_exists( 'get_the_post_thumbnail' ) ) {
		# If the thumb only is set then pulled images is just matches
		if (! isset($wordbooker_post_options["wordbook_thumb_only"]) ) {
			wordbooker_debugger("setting image array to be both thumb and the post images "," ",$post->ID) ;
		 	$pulled_images=array_merge($matches[2],$matched[2],$yturls);
		}
		else {
			wordbooker_debugger("setting image array to be just thumb "," ",$post->ID) ;
			$pulled_images=$matches[2];
		} 
	}
	else {
		wordbooker_debugger("setting image array to be only post images. "," ",$post->ID) ;
		if (is_array($matched[2]) && is_array($yturls)) {$pulled_images=array_merge($matched[2],$yturls);}
		if (is_array($matched[2])) {$pulled_images=$matched[2];}
		if (is_array($yturls)) {$pulled_images=$yturls;}
	}

	
	$images = array();
	if (is_array($pulled_images)) {
		foreach ($pulled_images as $ii => $imgsrc) {
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

	
	# Strip images from various plugins
	$images=array_filter($images, "strip_images");
	# And limit it to 5 pictures to keep Facebook happy.
	$images = array_slice($images, 0, 5);

	if (is_array($images)){
		foreach ($images as $key){
			wordbooker_debugger("Post Images : ".$key['src'],'',$post->ID) ;
		}
	}

	$wordbooker_settings =wordbooker_options(); 

	wordbooker_debugger("Getting the Excerpt"," ",$post->ID) ;
	if (isset($wordbooker_post_options["wordbook_use_excerpt"])  && (strlen($post->post_excerpt)>3)) { 
		$post_content=$post->post_excerpt; }
	else {	$post_content=wordbooker_post_excerpt($post_content,$wordbooker_post_options['wordbook_extract_length']);}

	# this is getting and setting the post attributes
	$post_attribute=parse_wordbooker_attributes(stripslashes($wordbooker_post_options["wordbook_attribute"]),$postid,strtotime($post->post_date));
	$post_data = array(
		'media' => $images,
		'post_link' => $post_link,
		'post_title' => $post_title,
		'post_excerpt' => $post_content,
		'post_attribute' => $post_attribute
		);
	wordbooker_debugger("Calling wordbooker_fbclient_publishaction_impl"," ",$post->ID) ;
	list($result, $error_code, $error_msg, $method,$result2, $error_code2, $error_msg2) = wordbooker_fbclient_publishaction_impl($fbclient, $post_data);

	return wordbooker_fbclient_facebook_finish($wbuser, $result,$method, $error_code, $error_msg, $postid,$result2, $error_code2, $error_msg2);
}

function strip_images($var)
{

	$strip_array= array ('addthis.com','gravatar.com','zemanta.com','wp-includes','plugins','favicon.ico','facebook.com');
	foreach ($strip_array as $strip_domain) {
	wordbooker_debugger("looking for ".$strip_domain." in ".$var['src']," ",$post->ID) ;
 	  if (stripos($var['src'],$strip_domain)) {wordbooker_debugger("Found a match so dump the string"," ",$post->ID) ; return;}
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


function wordbooker_footer($blah)
{

if (is_404()) {return;}
$efb_script = <<< EOGS
 <div id="fb-root"></div>
     <script type="text/javascript">
      window.fbAsyncInit = function() {
	FB.init({
	 appId  : '254577506873',
	  status : true, // check login status
	  cookie : true, // enable cookies to allow the server to access the session
	  xfbml  : true  // parse XFBML
	});
      };

      (function() {
	var e = document.createElement('script');


EOGS;

$wplang="en_US";
if (strlen(WPLANG) > 2) {$wplang=WPLANG;}
$efb_script.= "e.src = document.location.protocol + '//connect.facebook.net/".$wplang."/all.js';";
$efb_script.= <<< EOGS
	e.async = true;
	document.getElementById('fb-root').appendChild(e);
      }());
    </script>
    

EOGS;
	$wordbooker_settings = wordbooker_options(); 
	if  (isset($wordbooker_settings['wordbooker_like_button_show']) || isset($wordbooker_settings['wordbooker_like_share_too'])) 
		{
		echo $efb_script;
		 if ( isset($wordbooker_settings['wordbooker_iframe'])) {
			echo '<script src="http://static.ak.fbcdn.net/connect.php/js/FB.Share" type="text/javascript"></script>';
		}
	}
	echo "\n<!-- Wordbooker code revision : ".WORDBOOKER_CODE_RELEASE." -->\n";
return $blah;
}


function wordbooker_header($blah){
	if (is_404()) {return;}
	global $post;
	$wordbooker_settings = wordbooker_options(); 
	if ( (isset($wordbooker_settings['wordbooker_like_button_show']) || isset($wordbooker_settings['wordbooker_like_share_too']))) {
		$x = get_post_meta($post->ID, 'wordbooker_options', true); 
		if (is_array($x)){
			foreach (array_keys($x) as $key ) {
				if (substr($key,0,8)=='wordbook') {
					$wordbooker_post_options[$key]=str_replace( array('&amp;','&quot;','&#039;','&lt;','&gt;','&nbsp;&nbsp;'),array('&','"','\'','<','>',"\t"),$x[$key]);
				}
			}
		}
		$wpuserid=$post->post_author;
		if (is_array($wordbooker_post_options)){
			if  ($wordbooker_post_options["wordbook_default_author"] > 0 ) {$wpuserid=$wordbooker_post_options["wordbook_default_author"];}
		}
		if (is_single() || is_page()) {
			
		$post_link = get_permalink($post->ID);
			$post_title=$post->post_title;
			$blog_name=get_bloginfo('name');
			$xxx=wordbooker_get_cache( $wpuserid,facebook_id);
			if (is_null($xxx->facebook_id)) {
				echo '<meta property = "fb:app_id" content = "'.WORDBOOKER_FB_ID.'" />';
			} else {
				echo '<meta property="fb:admins" content="'.$xxx->facebook_id.'"/>';
			}
			#echo '<!-- Wordbooker created FB tags --> <meta property="og:title" content="'.htmlspecialchars($post_title,ENT_QUOTES).'"/>';
			echo '<!-- Wordbooker created FB tags --> <meta property="og:title" content="'.htmlspecialchars(strip_tags($post_title),ENT_QUOTES).'"/>';
			echo '<meta property="og:site_name" content="'.$blog_name.'"/>';
			echo '<meta property="og:url" content="'.$post_link.'"/>';
			echo '<meta property="og:type" content="article"/>';
			if ( function_exists( 'get_the_post_thumbnail' ) ) { 	
				if (get_the_post_thumbnail($post->ID)) {  
					preg_match_all('/<img \s+ ([^>]*\s+)? src \s* = \s* [\'"](.*?)[\'"]/ix',get_the_post_thumbnail($post->ID), $matches); 
					echo '<meta property="og:image" content="'.$matches[2][0].'" />';
				}
			}
		} 
		else
		{ # Not a single post so we need a different set of tags
			$blog_name=get_bloginfo('name');
			$xxx=wordbooker_get_cache( $wpuserid,facebook_id);
			if (is_null($xxx->facebook_id)) {
				echo '<meta property = "fb:app_id" content = "'.WORDBOOKER_FB_ID.'" />';
			} else {
				echo '<meta property="fb:admins" content="'.$xxx->facebook_id.'"/>';
			}
			echo '<!-- Wordbooker created FB tags --> <meta property="og:site_name" content="'.$blog_name.'"/>';
			echo '<meta property="og:type" content="blog"/>';
	
		}
	}
	if ($meta_length = wordbooker_get_option('wordbook_description_meta_length')) {
		if (is_single() || is_page()) {
			$post = get_post($post->ID);
			$description = str_replace('"','&quot;',$post->post_content);
			$excerpt = wordbooker_post_excerpt($description,$meta_length);
			$excerpt = preg_replace('/(\r|\n)+/',' ',$excerpt);
			$excerpt = preg_replace('/\s\s+/',' ',$excerpt);

			# If we've got an excerpt use that instead
			if (strlen($post->post_excerpt)>3) { 
				$excerpt=$post->post_excerpt; 
			} 	
			# Now if we've got something put the meta tag out.
			if (isset($excerpt)){ 
				$meta_string = sprintf("<meta name=\"description\" content=\"%s\"/>", htmlspecialchars($excerpt,ENT_QUOTES));
				echo $meta_string;
			}
		}
		else
		{		
				$meta_string = sprintf("<meta name=\"description\" content=\"%s\"/>", get_bloginfo('description'));
				echo $meta_string;
		}
	}
	return $blah;
}

function wordbooker_append_post($post_cont) {
	global $post;
	$do_like=0;
	$wordbooker_settings = wordbooker_options(); 
	if (!isset($wordbooker_settings['wordbooker_like_button_show']) && !isset($wordbooker_settings['wordbooker_like_share_too'])) {return $post_cont;}
	if (isset($wordbooker_settings['wordbooker_like_button_post']) && is_single()) {$do_like=1;}
	if (isset($wordbooker_settings['wordbooker_like_button_page']) && is_page()) {$do_like=1;}
	if (isset($wordbooker_settings['wordbooker_like_button_frontpage']) && !is_single() && !is_page()) {$do_like=1;}
	if (isset($wordbooker_settings['wordbooker_share_button_frontpage']) && !is_single() && !is_page()) {$do_like=1;}	
	$post_cont2=$post_cont;
	if ($do_like==1){

		$post_link = get_permalink($post->ID);
		$share_code="";
		$btype="button";
		if (is_single() || is_page()) {
		$btype="button_count";
		}
		if ( (isset($wordbooker_settings['wordbooker_like_share_too']) && (is_single() || is_page())) || (isset($wordbooker_settings['wordbooker_share_button_frontpage']) && !is_single() && !is_page() && isset($wordbooker_settings['wordbooker_like_share_too'])) ){
			if (isset($wordbooker_settings['wordbooker_iframe'])) {
				 $share_code='<!-- Wordbooker created FB tags --> <a name="fb_share" type="'.$btype.'" share_url="'.$post_link.'"></a>';
			}
			else {
				$share_code='<!-- Wordbooker created FB tags --> <fb:share-button class="meta" type="'.$btype.'" href="'.$post_link.'" > </fb:share-button>';
			}
	
			if ($wordbooker_settings['wordbook_fblike_location']=='bottom'){
				$post_cont2= "<p><br /></p>".$share_code."<br /><br />".$post_cont2; 
			} 
			else {
				$post_cont2=$post_cont2."<br /><br />".$share_code;
			}

		}
		if (isset($wordbooker_settings['wordbooker_like_button_show'])){
			if (isset($wordbooker_settings['wordbooker_iframe'])) { 
				$px=35;
				if ($wordbooker_settings['wordbook_fblike_faces']=='true') {$px=80;}
				$like_code='<!-- Wordbooker created FB tags --> <iframe src="http://www.facebook.com/plugins/like.php?href='.$post_link.'&amp;layout='.$wordbooker_settings['wordbook_fblike_button'].'&amp;show_faces='.$wordbooker_settings['wordbook_fblike_faces'].'&amp;width='.$wordbooker_settings["wordbooker_like_width"].'&amp;action='.$wordbooker_settings['wordbook_fblike_action'].'&amp;colorscheme='.$wordbooker_settings['wordbook_fblike_colorscheme'].'&amp;font='.$wordbooker_settings['wordbook_fblike_font'].'&amp;height='.$px.'px" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:'.$wordbooker_settings["wordbooker_like_width"].'px; height:'.$px.'px;" allowTransparency="true"></iframe>';
	
			}
			else {
			$like_code='<!-- Wordbooker created FB tags --> <fb:like layout="'.$wordbooker_settings['wordbook_fblike_button'] .'" show_faces="'.$wordbooker_settings['wordbook_fblike_faces'].'" action="'.$wordbooker_settings['wordbook_fblike_action'].'" font="'.$wordbooker_settings['wordbook_fblike_font'].'" colorscheme="'.$wordbooker_settings['wordbook_fblike_colorscheme'].'"  href="'.$post_link.'" width="'.$wordbooker_settings["wordbooker_like_width"].'"></fb:like> ';}
			if ($wordbooker_settings['wordbook_fblike_location']=='bottom'){
				$post_cont2= $post_cont2.'<br /><br/>'.$like_code.'<br />'; 
			} 
			else {
				$post_cont2= "<p><br /><br />".$like_code.'<br /><br /></p>'.$post_cont2;
			}
		}

		if ($wordbooker_settings['wordbook_fblike_location']==$wordbooker_settings['wordbook_fbshare_location']){
			if ($wordbooker_settings['wordbook_fblike_location']=='bottom'){
				$post_cont2=$post_cont.'<div>'.$like_code.'<div style="float:right;">'.$share_code.'</div></div>'; 
			} 
			else {
				$post_cont2= '<p><br /></p><div>'.$like_code.'<div style="float:right;">'.$share_code.'</div></div>'.$post_cont; 
			}
		}

		
	}
	return $post_cont2;
}

function wordbooker_get_cache($user_id,$field=null) {
	global $wpdb;
	if (!isset($user_id)) {return;}
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
	$opurlt="&fbconnect=true"."&display=popup"."&extern=1&enable_profile_selector=1&next=http%3A%2F%2Fccgi.pemmaquid.plus.com%2Fcgi-bin%2Findex.php%3Fbr=".urlencode(get_bloginfo('wpurl'));
	#$opurlt="&fbconnect=true"."&display=popup"."&extern=1&next=http%3A%2F%2Fccgi.pemmaquid.plus.com%2Fcgi-bin%2Findex.php%3Fbr=".urlencode(get_bloginfo('wpurl'));
	$perms_to_check= array(WORDBOOKER_FB_PUBLISH_STREAM,WORDBOOKER_FB_STATUS_UPDATE,WORDBOOKER_FB_READ_STREAM);
	$perm_messages= array( __('publish content to your Wall/Fan pages'), __('update your status'), __('read your News Feed and Wall'),__('create notes'));
	$preamble= __("Wordbooker requires authorization to ");
	$postamble= __(" on Facebook. Click on the following link to grant permission");
	echo "<br />";

	if(is_array($perms_to_check)) {
		foreach(array_keys($perms_to_check) as $key){
			# Bit map check to put out the right text for the missing permissions.
			if (pow(2,$key) & $perm_miss->auths_needed ) {
			       $url = $opurl. WORDBOOKER_FB_APIVERSION . "&api_key=". WORDBOOKER_FB_APIKEY ."&ext_perm=" . $perms_to_check[$key] .$opurlt;
			       echo '<p>'.$preamble.$perm_messages[$key].$postamble.'</p><div style="text-align: center;"><a href="'.$url.'" > <img src="http://static.ak.facebook.com/images/devsite/facebook_login.gif"  alt="Facebook Login Button" /></a><br /></div>';
			}
		}
	}
	echo "and then save your settings<br />";
	echo '<form action="'.WORDBOOKER_SETTINGS_URL.'" method="post"> <input type="hidden" name="action" value="" />';
	echo '<p style="text-align: center;"><input type="submit" name="perm_save" class="button-primary" value="'. __('Save Configuration').'" /></p></form>';
}

function wordbooker_contributed($url=0) {
	global $user_ID;
	if ($url==0){
		$contributors=array('100000818019269','39203171','666800299','500073624','711830142','503549492','100000589976474','254577506873','1567300610','701738627','100000442094620','754015348','29404010','748636937','676888540','768354692');
		$facebook_id=wordbooker_get_cache($user_ID,'facebook_id');
		return in_array($facebook_id->facebook_id,$contributors);
	}
	if ($url==1){
		$blogs=array("Steve's Blog"=>'blogs.canalplan.org.uk/steve',"Powered by Dan!"=>'dangarion.com',"Kathryn's Comments"=>'www.kathrynhuxtable.org/blog',"Luke Writes"=>'www.lukewrites.com',"It's Nature"=>'www.itsnature.org',"Eat in OC"=>'eatinoc.com',"Christian Albert Muller"=>'christian-albert-mueller.com/blog/',"[overcrooked|de]"=>'blog.overcrooked.de/',"Jesus is My Buddy"=>'www.jesusismybuddy.com',"Shirts of Bamboo"=>'www.shirtsofbamboo.com', "What's that bug?"=>'www.whatsthatbug.com',"Philip Bussman"=>'www.philipbussmann.com',"PhantaNews"=>'phantanews.de/wp/', "HKMacs"=>'hkmacs.com/Blog', "Techerator"=>'www.techerator.com', "Mosalar.com"=>'www.mosalar.com/',"Exasperated Machine"=>'blog.noelacosta.com/',"Chart Porn"=>'www.chartporn.org',"Pawesome"=>'www.pawesome.net');
		$keys = array_keys($blogs);
		shuffle($keys);
	
		foreach ( $keys as $key) {
			echo "<a href='http://".htmlspecialchars($blogs[$key])."' target='_new'>".htmlspecialchars($key)."</a>,&nbsp;";
		}
		# And then put canalplan on the end of it - saves us having to do clever things to remove commas
		echo "<a href='http://www.canalplan.org.uk/' target='_new' >CanalPlan AC</a><br />";
	}
}
/******************************************************************************
 * WordPress hooks: update Facebook when a blog entry gets published.
 */

 function remove_HTML($s , $keep = '' , $expand = 'script|style|noframes|select|option'){
        /**///prep the string
        $s = ' ' . $s;
       
        /**///initialize keep tag logic
        if(strlen($keep) > 0){
            $k = explode('|',$keep);
            for($i=0;$i<count($k);$i++){
                $s = str_replace('<' . $k[$i],'[{(' . $k[$i],$s);
                $s = str_replace('</' . $k[$i],'[{(/' . $k[$i],$s);
            }
        }
       
        //begin removal
        /**///remove comment blocks
        while(stripos($s,'<!--') > 0){
            $pos[1] = stripos($s,'<!--');
            $pos[2] = stripos($s,'-->', $pos[1]);
            $len[1] = $pos[2] - $pos[1] + 3;
            $x = substr($s,$pos[1],$len[1]);
            $s = str_replace($x,'',$s);
        }
       
        /**///remove tags with content between them
        if(strlen($expand) > 0){
            $e = explode('|',$expand);
            for($i=0;$i<count($e);$i++){
                while(stripos($s,'<' . $e[$i]) > 0){
                    $len[1] = strlen('<' . $e[$i]);
                    $pos[1] = stripos($s,'<' . $e[$i]);
                    $pos[2] = stripos($s,$e[$i] . '>', $pos[1] + $len[1]);
                    $len[2] = $pos[2] - $pos[1] + $len[1];
                    $x = substr($s,$pos[1],$len[2]);
                    $s = str_replace($x,'',$s);
                }
            }
        }
       
        /**///remove remaining tags
        while(stripos($s,'<') > 0){
            $pos[1] = stripos($s,'<');
            $pos[2] = stripos($s,'>', $pos[1]);
            $len[1] = $pos[2] - $pos[1] + 1;
            $x = substr($s,$pos[1],$len[1]);
            $s = str_replace($x,'',$s);
        }
       
        /**///finalize keep tag
        for($i=0;$i<count($k);$i++){
            $s = str_replace('[{(' . $k[$i],'<' . $k[$i],$s);
            $s = str_replace('[{(/' . $k[$i],'</' . $k[$i],$s);
        }
       
        return trim($s);
    }

function wordbooker_post_excerpt($excerpt, $maxlength,$doyoutube=1) {
	if (function_exists('strip_shortcodes')) {
		$excerpt = strip_shortcodes($excerpt);
	}
	#$excerpt = strip_tags($excerpt);
	$excerpt = remove_HTML($excerpt);
	$excerpt = trim($excerpt);
	# Now lets strip any tags which dont have balanced ends
	#  Need to put NGgallery tags in there - there are a lot of them and they are all different.
	$open_tags="[simage,[[CP,[gallery,[imagebrowser,[slideshow,[tags,[albumtags,[singlepic,[album";
	$close_tags="],]],],],],],],],]";
	$open_tag=explode(",",$open_tags);
	$close_tag=explode(",",$close_tags);
	foreach (array_keys($open_tag) as $key) {
		if (preg_match_all('/' . preg_quote($open_tag[$key]) . '(.*?)' . preg_quote($close_tag[$key]) .'/i',$excerpt,$matches)) {
			$excerpt=str_replace($matches[0],"" , $excerpt);
		 }
	}

	# Now lets strip off the youtube stuff.
	preg_match_all( '#http://(www.youtube|youtube|[A-Za-z]{2}.youtube)\.com/(watch\?v=|w/\?v=|\?v=)([\w-]+)(.*?)player_embedded#i', $excerpt, $matches );
	$excerpt=str_replace($matches[0],"" , $excerpt);
	preg_match_all( '#http://(www.youtube|youtube|[A-Za-z]{2}.youtube)\.com/(watch\?v=|w/\?v=|\?v=)([\w-]+)(.*?)#i', $excerpt, $matches );
	$excerpt=str_replace($matches[0],"" , $excerpt);
	
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
	if (is_array($x)){
		foreach (array_keys($x) as $key ) {
			if (substr($key,0,8)=='wordbook') {
				$wordbooker_post_options[$key]=str_replace( array('&amp;','&quot;','&#039;','&lt;','&gt;','&nbsp;&nbsp;'),array('&','"','\'','<','>',"\t"),$x[$key]);
			}
		}
	}
	
		if (is_array($wordbooker_post_options)){
		foreach (array_keys($wordbooker_post_options) as $key){
			wordbooker_debugger("Post option : ".$key,$wordbooker_post_options[$key],$post->ID) ;
		}
	}

	# If the user_ID is set then lets use that, if not get the user_id from the post
	if (isset($user_ID)) {$whichuser=$user_ID;} else {$whichuser=$post->post_author;}

	# If the default user is set to 0 then we use the current user (or the author of the post if that isn't set - i.e. if this is a scheduled post)
	if  ($wordbooker_post_options["wordbook_default_author"] == 0 ) {$wpuserid=$whichuser;} else {$wpuserid=$wordbooker_post_options["wordbook_default_author"];}
	
	
	if (!($wbuser = wordbooker_get_userdata($wpuserid)) || !$wbuser->session_key) {
		wordbooker_debugger("Unable to get FB session for : ",$wpuserid,$post->ID) ;
		return 28;
	}
	wordbooker_debugger("Posting as user : ",$wpuserid,$post->ID) ;
	/* If publishing a new blog post, update text in "Wordbook" box. */
	$fbclient = wordbooker_fbclient($wbuser);

	if (wordbooker_postlogged($post->ID)) {
			wordbooker_debugger("This post falls within the don\'t republish window : "," ",$post->ID) ;
	}
	if (!wordbooker_postlogged($post->ID)) {
		# Lets see if they want to update their status. We do it this way so you can update your status without publishing!
		if( $wordbooker_post_options["wordbooker_status_update"]=="on") {
	
			wordbooker_debugger("Setting status_text".$wordbooker_post_options['wordbooker_status_update_text']," ",$post->ID) ; 
			$status_text = parse_wordbooker_attributes(stripslashes($wordbooker_post_options['wordbooker_status_update_text']),$post->ID,strtotime($post->post_date)); 
		$status_text = wordbooker_post_excerpt($status_text,420); 			
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
		if ($wordbooker_post_options["wordbooker_publish_default"]=="200") { $wordbooker_post_options["wordbooker_publish_default"]='on';}
		if ($wordbooker_post_options["wordbooker_publish_default"]!="on") {
			wordbooker_debugger("Publish Default is not Set, Giving up ",$wpuserid,$post->ID) ;
		 	return;
		}
		wordbooker_debugger("Calling wordbooker_fbclient_publishaction"," ",$post->ID) ;
		list($result1,$result2)=wordbooker_fbclient_publishaction($wbuser, $fbclient, $post->ID);
		wordbooker_insertinto_postlogs($post->ID);
		wordbooker_debugger("Call made : returns : ",$result1." - ".$result2,$post->ID) ;
		$fb_post_id=$result1;
		// Has the user decided to collect comments for this post?
		if( isset($wordbooker_post_options["wordbook_comment_get"])){	
			$tstamp=time();
		}
		else
		{	
			$tstamp= time() + (1000 * 7 * 24 * 60 * 60);
		}
		$sql=	' INSERT INTO ' . WORDBOOKER_POSTCOMMENTS . ' (fb_post_id,comment_timestamp,wp_post_id) VALUES ("'.$result1.'",'.$tstamp.','.$post->ID.')';
		if ( isset($result1) ) {$result = $wpdb->query($sql);}
		$sql=	' INSERT INTO ' . WORDBOOKER_POSTCOMMENTS . ' (fb_post_id,comment_timestamp,wp_post_id) VALUES ("'.$result2.'",'.$tstamp.','.$post->ID.')';
		if ( isset($result2) ) {$result = $wpdb->query($sql);}

		$sql=	' DELETE FROM  ' . WORDBOOKER_POSTCOMMENTS . ' where fb_post_id="" ';
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
	global $user_ID, $user_identity, $user_login, $wpdb, $blog_id;
	$post = get_post($postid);
	if ((isset($user_ID) && $user_ID>0) &&  (!current_user_can(WORDBOOKER_MINIMUM_ADMIN_LEVEL))) { return; }
	wordbooker_deletefrom_errorlogs($postid);
	wordbooker_debugger("Commence Local Publish "," ",$post->ID) ; 
	$wordbooker_settings = wordbooker_options();
	# If there is no user row for this user then set the user id to the default author. If the default author is set to 0 (i.e current logged in user) then only blog level settings apply.
	if (! wordbooker_get_userdata($post->post_author)) { $wb_user_id=$wordbooker_settings["wordbook_default_author"];}
	if  ($wordbooker_settings["wordbook_default_author"] == 0 ) {$wb_user_id=$post->post_author;} else {$wb_user_id=$wordbooker_settings["wordbook_default_author"];}
	# If we've no FB user associated with this ID and the blog owner hasn't overridden then we give up.
	
	# If the referer is press-this then the user hasn't used the full edit post form so we need to get the blog/user level settings.
	# Also check for a missing user_id (i,e, user is not a wordbooker user), or if the extract_length is empty (wp-o-matic does that)
	if ( (stripos($_POST["_wp_http_referer"],'press-this')) || ( stripos($_POST["_wp_http_referer"],'index.php'))   ) {
		wordbooker_debugger("Inside the press this / quick press block "," ",$post->ID) ;
		# New get the user level settings from the DB
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
		if (is_array($wordbooker_settings)) {
			foreach (array_keys($wordbooker_settings) as $key ) {
				if (substr($key,0,8)=='wordbook') {
					$_POST[$key]=str_replace( array('&amp;','&quot;','&#039;','&lt;','&gt;','&nbsp;&nbsp;'),array('&','"','\'','<','>',"\t"),$wordbooker_settings[$key]);
				}
			}
		}
	# Then we set soupy to stop things being blown away by the post meta.
	$_POST['soupy']="twist";
	}
	
	else 

	{	if ((! wordbooker_get_userdata($post->post_author))  && ( !isset($wordbooker_settings['wordbook_publish_no_user'])))  { wordbooker_debugger("Not a WB user (".$post->post_author.") and no overide - give up "," ",$post->ID) ; return;}
		if ((! wordbooker_get_userdata($wb_user_id))  && ( !isset($wordbooker_settings['wordbook_publish_no_user'])))  {wordbooker_debugger("Author (".$post->post_author.") not a WB user and no overide- give up "," ",$post->ID) ;  return;}
	}

	if ($_POST["wordbook_default_author"]== 0 ) { $_POST["wordbook_default_author"]=$post->post_author; }
	
	// If soupy isn't set then its either a future post or a post inherting another users options so we need to get the meta data rather than rely on post data
	if (! isset($_POST['soupy'])) {
		wordbooker_debugger("Need to get options from the Meta and not the POST  "," ",$post->ID) ;
		# Get the blog level and then the user level settings - just in case this post predates the install.
		$wordbooker_settings = wordbooker_options();
		wordbooker_debugger("Getting settings for user : ",$wb_user_id,$post->ID) ; 
		if (! wordbooker_get_userdata($wb_user_id)) { $wb_user_id=$wordbooker_settings["wordbook_default_author"];}
		// then get the user level settings and override the blog level settings.

		$wordbook_user_settings_id="wordbookuser".$blog_id;
		$wordbookuser=get_usermeta($wb_user_id,$wordbook_user_settings_id);
		# If we have user settings then lets go through and override the blog level defaults.
;
		if(is_array($wordbookuser)) {
			foreach (array_keys($wordbookuser) as $key) {
				if ((strlen($wordbookuser[$key])>0) && ($wordbookuser[$key]!="0") ) {
					$wordbooker_settings[$key]=$wordbookuser[$key];
				} 
			}

		}

		#Now push these into the $_POST array.
		if(is_array($wordbooker_settings)) {
			foreach (array_keys($wordbooker_settings) as $key ) {
				if (substr($key,0,8)=='wordbook') {
					$_POST[$key]=str_replace( array('&amp;','&quot;','&#039;','&lt;','&gt;','&nbsp;&nbsp;'),array('&','"','\'','<','>',"\t"),$wordbooker_settings[$key]);
				}
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
	wordbooker_debugger("Options Set - call transition  "," ",$post->ID) ;
	$retcode=wordbooker_transition_post_status('publish', null, $post);
	return $retcode;
}


function wordbooker_publish_remote($postid) {
	global $blog_id;
	$post = get_post($postid);
	if ((isset($user_ID) && $user_ID>0) &&  (!current_user_can(WORDBOOKER_MINIMUM_ADMIN_LEVEL))) { return; }
	wordbooker_deletefrom_errorlogs($postid);
	wordbooker_debugger("Commence Remote publish "," ",$post->ID) ; 
	$wordbooker_settings = wordbooker_options();
	# If there is no user row for this user then set the user id to the default author. If the default author is set to 0 (i.e current logged in user) then only blog level settings apply.
	if (! wordbooker_get_userdata($post->post_author)) { $wb_user_id=$wordbooker_settings["wordbook_default_author"];}
	if  ($wordbooker_settings["wordbook_default_author"] == 0 ) {$wb_user_id=$post->post_author;} else {$wb_user_id=$wordbooker_settings["wordbook_default_author"];}
	# If we've no FB user associated with this ID and the blog owner hasn't overridden then we give up.

	# If we've no FB user associated with this ID and the blog owner hasn't overridden then we give up.
	if ((! wordbooker_get_userdata($user_ID))  && ( !isset($wordbooker_settings['wordbook_publish_no_user'])))  { return;}
	# Get the blog level settings
	$wordbooker_settings = wordbooker_options();
	// then get the user level settings and override the blog level settings.
	if  ($wordbooker_settings["wordbook_default_author"] == 0 ) {$wb_user_id=$post->post_author;} else {$wb_user_id=$wordbooker_settings["wordbook_default_author"];}
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
	
	# Then populate the post array
	if(is_array($wordbookuser)) {
		foreach (array_keys($wordbookuser) as $key ) {
			if (substr($key,0,8)=='wordbook') {
				$_POST[$key]=str_replace( array('&amp;','&quot;','&#039;','&lt;','&gt;','&nbsp;&nbsp;'),array('&','"','\'','<','>',"\t"),$wordbooker_settings[$key]);
			}
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
	global $user_ID, $user_identity, $user_login, $wpdb, $blog_id;
	

	// If this is a future post we need to grab the parameters they've passed in and store them in the database
	if ($newstatus=="future") {
		if (!current_user_can(WORDBOOKER_MINIMUM_ADMIN_LEVEL)) { return;}
		# If the user isn't a wordbooker user and the blog admin hasn't allowed non wordbooker users to publish then we go home.
		if ((! wordbooker_get_userdata($user_ID))  && ( !isset($wordbooker_settings['wordbook_publish_no_user'])))  { return;}
		# If we dont have and Wordbooker settings for this user then we need to get some from the stsete,
		if (! wordbooker_get_userdata($user_ID)) {

			$wordbooker_settings = wordbooker_options();
			$wb_user_id=$wordbooker_settings["wordbook_default_author"];
			# New get the user level settings from the DB
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
				if(is_array($wordbookuser)) {
				foreach (array_keys($wordbooker_settings) as $key ) {
					if (substr($key,0,8)=='wordbook') {
						$_POST[$key]=str_replace( array('&amp;','&quot;','&#039;','&lt;','&gt;','&nbsp;&nbsp;'),array('&','"','\'','<','>',"\t"),$wordbooker_settings[$key]);
					}
				}
			}
		}
	
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
			$debug_string="FBID : ".$cpid."  stat:".$cstatus."  text:".$ctext." type:".$ctype."!!\n";
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
						   # @param string $xid   external id associated with the comments
						    #@param string $text  text of the comment
						   # @param int    $uid   user adding the comment (def: session user)
						   # @param string $title optional title for the stream story
						   # @param string $url   optional url for the stream story
						   # @param bool   $publish_to_stream publish a feed story about this comment?
						   #                      a link will be generated to title/url in the story
						#$result2=$fbclient->comments_add('100000384338372_105295439493267', $ctextblock.' ');
						# This returns the comment ID so we should store this as a "made" comment for the post in question so we can exclude it when pulling comments down from facebook.
						try {
						$result2=$fbclient->stream_addComment($fb_post_id , $ctextblock.' ');
						}
						catch (Exception $e) {
							$error_code = $e->getCode();
							$error_msg = $e->getMessage();
							wordbooker_debugger($error_msg," ",0) ;
						}
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



function wordbooker_debugger($method,$error_msg,$post_id,$level=10) {
	#echo $method." - ".$error_msg."<br>";
	if (wordbooker_get_option('wordbook_advanced_diagnostics')) { 
		# Check the level we are logging errors to and give up if needed.
		#if (wordbooker_get_option('wordbook_advanced_diagnostics_level']) > $level) {return;}
		global $user_ID,$post_ID,$wpdb;
		$usid=1;
		# Get the error_code last used - allows us to order in a subsecond timestamp
		$result = $wpdb->get_results("select min(error_code) as ec from ". WORDBOOKER_ERRORLOGS);
		$rowid=$result[0]->ec;
		if (!isset($rowid)) {$rowid=-1;}
		if ($rowid>=0) {$rowid=-1;}
		$rowid=$rowid-1;
		if (post_id<=1) {$post_id=$post_ID;}
		if (!isset($post_id)) {$post_id=$post_ID;}
		if (!isset($post_id)) {$post_id=1;}
		if (isset($user_ID)) {$usid=$user_ID;}
		if (!isset($usid)) {$usid=wordbooker_get_option('wordbook_default_author');}
		if (!isset($usid)) {$usid=1;}
		$error_msg="( ".$error_msg." )";
		$sql=	"INSERT INTO " . WORDBOOKER_ERRORLOGS . " (
					user_id
					, method
					, error_code
					, error_msg
					, post_id
				) VALUES (  
					" . $usid . "
					, '" . $method . "'
					, $rowid
					, '" . $error_msg . "'
					, " . $post_id . "
				)";
		$result = $wpdb->query($sql);
	}
}

/******************************************************************************
 * Register hooks with WordPress.
 */

/* Plugin maintenance. */
register_activation_hook(__FILE__, 'wordbooker_activate');
register_deactivation_hook(__FILE__, 'wordbooker_deactivate');
add_action('delete_user', 'wordbooker_delete_user');


add_action ('init', 'wordbooker_init');
 
function wordbooker_init () {
	#load_plugin_textdomain( 'my-plugin', false, dirname( plugin_basename( __FILE__ ) ) )
	#$plugin_dir = basename(dirname(__FILE__));
	#  load_plugin_textdomain ('wordbooker',false,basename(dirname(__FILE__)).'/languages');
}

function wordbooker_schema($attr) {
        $attr .= " xmlns:fb=\"http://www.facebook.com/2008/fbml\" xmlns:og=\"http://opengraphprotocol.org/schema/\" ";
        return $attr;
}

define('WORDBOOKER_HOOK_PRIORITY', 10);	/* Default; see add_action(). */

/* Post/page maintenance and publishing hooks. */
add_action('delete_post', 'wordbooker_delete_post');
add_action('xmlrpc_publish_post', 'wordbooker_publish_remote');
#add_action('publish_phone', 'wordbooker_publish_remote');
add_action('publish_post', 'wordbooker_publish');
add_action('publish_page', 'wordbooker_publish');
add_action('wb_cron_job', 'wordbooker_poll_facebook');
add_action('delete_post', 'wordbooker_delete_post');
add_action('comment_post', 'wordbooker_post_comment');
add_action('wp_head', 'wordbooker_header');
add_action('wp_footer', 'wordbooker_footer');
add_filter( 'the_content', 'wordbooker_append_post',1);
add_action('transition_post_status', 'wordbooker_future_post',WORDBOOK_HOOK_PRIORITY, 3);
add_filter('language_attributes', 'wordbooker_schema');

include("wb_widget.php");
include("fb_widget.php");
include("wordbooker_options.php");
include("wordbooker_cron.php");

?>
