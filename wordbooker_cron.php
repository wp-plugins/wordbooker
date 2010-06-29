<?php

/**
Extension Name: Wordbooker Cron
Extension URI: http://blogs.canalplan.org.uk/steve
Version: 1.8.9
Description: Collection of processes that are often handled by wp_cron scheduled jobs
Author: Steve Atty
*/

function wordbooker_cache_refresh ($user_id,$fbclient) {
	global $blog_id,$wpdb,$table_prefix;
	wordbooker_debugger("Cache Refresh Commence ",$user_id,0) ; 
	$result = $wpdb->get_row("select facebook_id from ".WORDBOOKER_USERDATA." where user_ID=".$user_id);
	$uid=$result->facebook_id;
	$wordbooker_settings =get_option('wordbooker_settings'); 
	$debug_file='/tmp/wordbook_cache_'.$table_prefix.'debug';
	#$fp = fopen($debug_file, 'a');
	$debug_string=date("Y-m-d H:i:s",time())." : Processing for ".$uid."\n";
	wordbooker_debugger("Cache Refresh for ",$uid,0) ; 
	#fwrite($fp, $debug_string);
	# If we've not got the ID from the table lets try to get it from the logged in user
	if (strlen($uid)==0) {
		try {
			$uid=$fbclient->users_getLoggedInUser();
		}
		catch (Exception $e) {
			$error_code = $e->getCode();
			$error_msg = $e->getMessage();
			wordbooker_debugger($error_msg," ",0) ;
			unset($uid);
		}
	}
	
	# If we now have a uid lets go and do a few things.
	if (strlen($uid)>0){
		wordbooker_debugger("Cache processing for user : ",$uid,0) ;
		wordbooker_debugger("Getting Permisions for : ",$uid,0) ;
		# Check that the user has permission to publish to all their fan pages. All we need to know is if one or more is missing permissions - FB will do the rest for us
	#	$query = "SELECT page_id FROM page_admin WHERE uid=$uid and page_id in (select page_id from page_fan where uid=$uid)";

	#	echo $query."<br>";
	#	$result1 = $fbclient->fql_query($query);
	#	var_dump($result1);

		#$query="SELECT page_id FROM page_admin WHERE uid=$uid and ) page_id in (select page_id from page_fan where uid=$uid ) or page_id IN (SELECT page_id FROM page_admin WHERE uid=$uid))";
		#echo $query;
		$query = "SELECT page_id FROM page_admin WHERE uid = $uid";
		#echo "<br>".$query."<br>";
		try {
			$result2 = $fbclient->fql_query($query);
		}
		catch (Exception $e) {
			# We don't have a good session so
			#echo "woops";
			wordbooker_delete_user($user_id);
		return;

	}
	#	echo "<br>";
	#var_dump($result2);
	#if ((is_array($result1)) && (is_array($result2)) ) {$result=array_unique(array_merge($result1,$result2));} 
	#if ((is_array($result1)) && (!is_array($result2)) ) {$result=$result1;} 
	#if ((!is_array($result1)) && (is_array($result2)) ) {$result=$result2;} 

	$result=$result2;

		$add_auths=0;
		if (is_array($result)){
			foreach ($result as $page) {
				wordbooker_debugger("Checking permissions for page  : ",$page['page_id'],0) ;
				try {
					$permy = $fbclient->users_hasAppPermission("publish_stream",$page['page_id']);
					$error_code = null;
					if($permy==0) {$add_auths=1;wordbooker_debugger("Page needs permissions for ".$page['page_id']," ",0) ;} 
					else { wordbooker_debugger("No permissions needed for page : ".$page['page_id']," ",0);}
					
					$error_msg = null;
				} catch (Exception $e) {
					wordbooker_debugger("Page needs permissions for ".$page['page_id'],$page['page_id'],0);
					$users = null;
					$add_auths=1;
				}
			}
		}
		
		# Now lets check over the over permissions and build up the bit mask
		$perms_to_check= array(WORDBOOKER_FB_PUBLISH_STREAM,WORDBOOKER_FB_STATUS_UPDATE,WORDBOOKER_FB_READ_STREAM,WORDBOOKER_FB_CREATE_NOTE);
		foreach(array_keys($perms_to_check) as $key){
	 		if (! $fbclient->users_hasAppPermission($perms_to_check[$key],$uid)) { $add_auths = $add_auths | pow(2,$key);}
		}
		# And update the table. We do this here just in case the FQL_Multi fails.
		wordbooker_debugger("Additional Permissions needed : ",$add_auths,0) ;
		$sql="update ".WORDBOOKER_USERDATA." set auths_needed=".$add_auths." where user_ID=".$user_id;
		$result = $wpdb->get_results($sql);

		# Lets get the person/page this user wants to get the status for. We get this from the user_meta
		$wordbook_user_settings_id="wordbookuser".$blog_id;
		$wordbookuser=get_usermeta($user_id,$wordbook_user_settings_id);
		$suid=$uid;
	
		if (isset($wordbookuser['wordbook_status_id'])  && $wordbookuser['wordbook_status_id']!=-100) {$suid=$wordbookuser['wordbook_status_id'];}
		wordbooker_debugger("Getting Status for : ",$suid,0) ;

		try {
			$query="SELECT uid,time,message FROM status WHERE uid= $suid limit 1";
			$fb_status_info = $fbclient->fql_query($query);
		}
		catch (Exception $e) {
			wordbooker_debugger("Failed to get Status information from FB"," ",0);
		}
		#echo "<br><br>";
		$fb_status_info=$fb_status_info[0];
		#var_dump($fb_status_info );
		#echo "<br><br>";
		try {
			$query="SELECT name, url, pic FROM profile WHERE id=$suid ";
			$fb_profile_info = $fbclient->fql_query($query);
		} 
		catch (Exception $e) {
			wordbooker_debugger("Failed to get user information from FB"," ",0);
		}
		$fb_profile_info=$fb_profile_info[0];
		#var_dump($fb_profile_info);
		#echo "<br><br>";
		try {
			$query="SELECT name,page_id FROM page WHERE page_id IN (SELECT page_id FROM page_admin WHERE uid= $uid )";
		#$query="SELECT name,page_id FROM page WHERE page_id IN (SELECT page_id FROM page_admin WHERE uid=$uid and page_id in (select page_id from page_fan where uid=$uid )) or page_id IN (SELECT page_id FROM page_admin WHERE uid=$uid)";
			$fb_page_info = $fbclient->fql_query($query);
		} 
		catch (Exception $e) 
		{
		wordbooker_debugger("Failed to get page information from FB"," ",0);
		}
		#$fb_page_info=$fb_page_info[0];
		#var_dump($fb_page_info);
		#echo "<br><br>";
		try {
			$query="Select is_app_user FROM user where uid=$uid";
			$fb_app_info = $fbclient->fql_query($query);
		} 
		catch (Exception $e) 
		{
			wordbooker_debugger("Failed to get app_user information from FB"," ",0);
		}
		$fb_app_info=$fb_app_info[0];
		#var_dump($fb_app_info);
		#echo "<br><br>";

	#	$resultx=$fbclient->fql_multiquery('{  "status_info":"SELECT uid,time,message FROM status WHERE uid='.$suid.' limit 1", "profile_info":"SELECT name, url, pic FROM profile WHERE id='.$suid.'",  "page_names":"SELECT name,page_id FROM page WHERE page_id IN (SELECT page_id FROM page_admin WHERE uid='.$uid.')","woot":"Select is_app_user FROM user where uid='.$uid.'"}');
		#var_dump($resultx);
	#	if (is_array($resultx)) {
		$all_pages=array();
			if (is_array($fb_page_info)) { 
	#		var_dump($fb_page_info);
			if (is_array($fb_page_info)) { $encoded_names=str_replace('\\','\\\\',serialize($fb_page_info));}
				 foreach ( $fb_page_info as $pageinfo ) {	
		#		var_dump($pageinfo);
				$pages["page_id"]=$pageinfo["page_id"];
				if (function_exists('mb_convert_encoding')) {
					$pages["name"]=mb_convert_encoding($pageinfo["name"],'UTF-8');
				}
				else
				{
					$pages["name"]=$pageinfo["name"];
				}
				$all_pages[]=$pages;

					wordbooker_debugger("Page info for page ID ".$pageinfo["page_id"],mysql_real_escape_string($pageinfo["name"]),0) ;
	
				}
			#var_dump($all_pages);
			#$encoded_names=str_replace('\\','\\\\',serialize($all_pages));
			#echo $encodes_names;
			} else {wordbooker_debugger("Failed to get page information from FB"," ",0); }
			wordbooker_debugger("Setting name as  : ",mysql_real_escape_string($fb_profile_info["name"]),0) ;
			$sql="update ".WORDBOOKER_USERDATA." set name='".mysql_real_escape_string($fb_profile_info["name"])."'";
			if (is_array($fb_status_info)) {
				wordbooker_debugger("Setting status as  : ",mysql_real_escape_string($fb_status_info["message"]),0) ;
				$sql.=", status='".mysql_real_escape_string($fb_status_info["message"])."'";
				$sql.=", updated=".mysql_real_escape_string($fb_status_info["time"]);
			} else {wordbooker_debugger("Failed to get Status information from FB"," ",0); }
			if (is_array($fb_profile_info)) {
				wordbooker_debugger("Setting URL as  : ",mysql_real_escape_string($fb_profile_info["url"]),0) ;
				$sql.=", url='".mysql_real_escape_string($fb_profile_info["url"])."'";
				$sql.=", pic='".mysql_real_escape_string($fb_profile_info["pic"])."'";
			}	else {wordbooker_debugger("Failed to get Image information from FB"," ",0); }
			$sql.=", facebook_id='".$uid."'";
			$sql.=", pages= '".mysql_real_escape_string($encoded_names)."'";
			if (is_array($fb_app_info)) {
				$sql.=", use_facebook=".$fb_app_info["is_app_user"];
			}
			$sql.="  where user_ID=".$user_id;
			#echo $sql;
			$result = $wpdb->get_results($sql);

		#}
	}
#fclose($fp);
	wordbooker_debugger("Cache Refresh Complete for user",$uid,0) ; 
}

function wordbooker_poll_facebook($single_user=null) {
	global  $wpdb, $user_id,$table_prefix;
	# If a user ID has been passed in then restrict to that single user.
	$limit_user="";
	if (isset($single_user)) {$limit_user=" where user_id=".$single_user." limit 1";}
	define ('DEBUG', false);
	$wordbooker_settings =get_option('wordbooker_settings'); 
	$debug_file='/tmp/wordbook_'.$table_prefix.'debug';
	if (DEBUG) {
		$fp = fopen($debug_file, 'a');
		$debug_string=date("Y-m-d H:i:s",time())." : Cron Running\n";
		fwrite($fp, $debug_string);
	}

	# This runs through the Cached users and refreshes them
      	$sql="Select user_id from ".WORDBOOKER_USERDATA.$limit_user;
        $wb_users = $wpdb->get_results($sql);
	if (is_array($wb_users)) {
		wordbooker_debugger("Batch Cache Refresh Commence "," ",0) ; 
		foreach ($wb_users as $wb_user){
			if (DEBUG) {
				$debug_string="Processing cache data for user ".$wb_user->user_id."\n";
				fwrite($fp, $debug_string);
			}	
			wordbooker_debugger("Calling Cache refresh for  :  ",$wb_user->user_id,0) ;	
			$wbuser = wordbooker_get_userdata($wb_user->user_id);
			$fbclient = wordbooker_fbclient($wbuser);
			wordbooker_cache_refresh($wb_user->user_id,$fbclient);
		}
		wordbooker_debugger("Batch Cache Refresh completed "," ",0) ; 
	}

	if ( !isset($wordbooker_settings['wordbook_comment_get'])) {
		if (DEBUG) {
			$debug_string=date("Y-m-d H:i:s",time())." : Comment Scrape not active. Cron Finished\n";
			fwrite($fp, $debug_string);
 			fclose($fp); 
		}
		return;
	}

	// Yes they have so lets get to work. We have to get the FB users associated with this blog
        $sql="Select user_id from ".WORDBOOKER_USERDATA.$limit_user;
        $wb_users = $wpdb->get_results($sql);
	if (!is_array($wb_users)) {
		return;
	}
	foreach ($wb_users as $wb_user){
		if (DEBUG) {
			$debug_string="Processing data for user ".$wb_user->user_id."\n";
			fwrite($fp, $debug_string);
		}	
		$wbuser = wordbooker_get_userdata($wb_user->user_id);
		$fbclient = wordbooker_fbclient($wbuser);
		#sleep(5);
		// Now we need to check if they've set Auto Approve on comments.
		$comment_approve=0;
		if (isset($wordbooker_settings['wordbook_comment_approve'])) {$comment_approve=1;}
		if (DEBUG) {
			$debug_string=date("Y-m-d H:i:s",time())." : Checking to see if we have any posts to check for comments\n";
			fwrite($fp, $debug_string);
		}
		// Go the postcomments table - this contains a list of FB post_ids, the wp post_id that corresponds to it and the timestamps of the last FB comment pulled.
		$sql='Select fb_post_id,comment_timestamp,wp_post_id from ' . WORDBOOKER_POSTCOMMENTS . ' where fb_post_id like "'.$fbclient->users_getLoggedInUser().'%" order by fb_post_id desc ';	
		$rows = $wpdb->get_results($sql);
		// For each FB post ID we find we go out to the stream on Facebook and grab the comments.
		if (count($rows)>0) {
			foreach ($rows as $comdata_row) {
				$fbsql='select time,text,fromid,xid from comment where time >'.$comdata_row->comment_timestamp." and post_id='".$comdata_row->fb_post_id."'";
				$fbcomments=$fbclient->fql_query($fbsql);
				if (is_array($fbcomments)) {
					if (DEBUG) {
						$debug_string="Number of comments to process for post ".$comdata_row->fb_post_id." is ".count($fbcomments) ."\n";
						fwrite($fp, $debug_string);
					}	
					foreach ($fbcomments as $comment) {
						if (DEBUG) {
							$debug_string="incoming comment time is ".$comment[xid]." and the last recorded comment time stamp was ".$comdata_row->comment_timestamp."\n";
							fwrite($fp, $debug_string);
						}
						// If the comment has a later timestamp than the one we currently have recorded then lets get some more information 
						if ($comment[time]>$comdata_row->comment_timestamp) {
							$fbuserinfo=$fbclient->users_getInfo($comment[fromid],'name,profile_url');
							if (DEBUG) {
								$debug_string="Comment : ".$comment[text]." was made at ".date("Y-m-d H:i:s",$comment[time])." by ".$fbuserinfo[0][name]." (".$comment[fromid].")\n";
								fwrite($fp, $debug_string);
							}
							$time = date("Y-m-d H:i:s",$comment[time]);
							$data = array(
								'comment_post_ID' => $comdata_row->wp_post_id,
								'comment_author' => $fbuserinfo[0][name],
								'comment_author_email' => get_bloginfo( 'admin_email' ),
								'comment_author_url' => $fbuserinfo[0][profile_url],
								'comment_content' => $comment[text],
								'comment_author_IP' => '127.0.0.1',
								'comment_agent' => 'Wordbooker Interface to Facebook',
								'comment_date' => $time,
								'comment_date_gmt' => $time,
								'comment_approved' => $comment_approve,
							);
							// change this to use wp_new_comment /includes/comment.php for docs
							$pos = strripos($comment[text], "Comment: [from blog ]");
							if ($pos === false) {wp_new_comment($data); }
							$sql='update '. WORDBOOKER_POSTCOMMENTS .' set comment_timestamp='.$comment[time].' where fb_post_id="'.$comdata_row->fb_post_id.'" and wp_post_id='.$comdata_row->wp_post_id;
							$result = $wpdb->query($sql);
						} // end of new comment process	
					} // End of Foreach process
				}  // End of is_array check
				else {
					if (DEBUG) {
						$debug_string=date("Y-m-d H:i:s",time())." : No comments to process for post :".$comdata_row->fb_post_id."\n";
						fwrite($fp, $debug_string);
					}
				}
			} // End of Foreach 
		} // End of if count
	} // end of foreach user
	if (DEBUG) {
		$debug_string=date("Y-m-d H:i:s",time())." : Cron Finished \n";
		fwrite($fp, $debug_string);
	 	fclose($fp);
		}	
}
?>
