<?php

/**
Extension Name: Wordbooker Cron
Extension URI: http://blogs.canalplan.org.uk/steve
Version: 2.0.0
Description: Collection of processes that are often handled by wp_cron scheduled jobs
Author: Steve Atty
*/

function wordbooker_cache_refresh ($user_id) {
	global $blog_id,$wpdb,$table_prefix,$wordbooker_user_settings_id;
	$result = $wpdb->query(' DELETE FROM ' . WORDBOOKER_ERRORLOGS . ' WHERE   blog_id ='.$blog_id.' and (user_ID='.$user_id.' or user_ID=0 ) and post_id=0');
	wordbooker_debugger("Cache Refresh Commence ",$user_id,0) ; 
	$result = $wpdb->get_row("select facebook_id from ".WORDBOOKER_USERDATA." where user_ID=".$user_id);
	$uid=$result->facebook_id;
	$wbuser2= wordbooker_get_userdata($user_id);
	$wordbooker_settings =get_option('wordbooker_settings'); 
	wordbooker_debugger("Cache Refresh for ",$uid,0) ;
	wordbooker_debugger("UID length : ",strlen($uid),0) ;  
	# If we've not got the ID from the table lets try to get it from the logged in user
	if (strlen($uid)==0) {
		wordbooker_debugger("No Cache record for user - getting Logged in user ",$uid,0) ; 
		try {
			$x=wordbooker_get_fb_id($wbuser2->access_token);
			$uid=$x->id;
		}
		catch (Exception $e) {
			$error_code = $e->getCode();
			$error_msg = $e->getMessage();
			wordbooker_debugger($error_msg," ",0) ;
			#unset($uid);
		}
	}
	# If we now have a uid lets go and do a few things.
	if (strlen($uid)>0){
		wordbooker_debugger("Cache processing for user : ",$uid,0) ;
		wordbooker_debugger("Getting Permisions for : ",$uid,0) ;
		$ret=wordbooker_fb_pemissions($wbuser2->facebook_id,$wbuser2->access_token);
		$add_auths=0;
		$permlist= array(WORDBOOKER_FB_PUBLISH_STREAM,WORDBOOKER_FB_STATUS_UPDATE,WORDBOOKER_FB_READ_STREAM,WORDBOOKER_FB_CREATE_NOTE,WORDBOOKER_FB_PHOTO_UPLOAD,WORDBOOKER_FB_VIDEO_UPLOAD,WORDBOOKER_FB_MANAGE_PAGES,WORDBOOKER_FB_READ_FRIENDS);
		$key=0;
		foreach($permlist as $perm){
		try {
			$permy=$ret->data[0]->$perm;
			$error_code = null;
	
			if($permy!=1) {
				wordbooker_debugger("User is missing permssion : ",$perm,0) ;
				$add_auths = $add_auths | pow(2,$key);
			} 
			else {
				wordbooker_debugger("User has permssion : ",$perm,0) ;
			}
			$error_msg = null;
		} catch (Exception $e) {
			$error_msg = $e->getMessage();
			wordbooker_debugger("Permissions may be corrupted  ",$error_message,0);
			$users = null;
			$add_auths=1;
		}
			$key=$key+1;
		}

		wordbooker_debugger("Additional Permissions needed : ",$add_auths,0) ;
		$sql="update ".WORDBOOKER_USERDATA." set auths_needed=".$add_auths." where user_ID=".$user_id;
		$result = $wpdb->get_results($sql);

		# Lets get the person/page this user wants to get the status for. We get this from the user_meta
		$wordbooker_user_settings_id="wordbookuser".$blog_id;
		$wordbookuser_setting=get_usermeta($user_id,$wordbooker_user_settings_id);
		$suid="PW:".$uid;
		if ( isset ($wordbookuser_setting['wordbooker_status_id']) && $wordbookuser_setting['wordbooker_status_id']!=-100) {$suid=$wordbookuser_setting['wordbooker_status_id'];}
		$x=explode(":",$suid);
		$suid=$x[1];
		wordbooker_debugger("Getting Status for : ",$suid,0) ;

		try {
			$query="SELECT uid,time,message FROM status WHERE uid= $suid limit 1";
			$fb_status_info=wordbooker_fql_query($query,$wbuser2->access_token);
		}
		catch (Exception $e) {
			$error_msg = $e->getMessage();
			wordbooker_debugger("Failed to get Status : ",$error_msg,0);;
		}

		try {
			$query="SELECT name, url, pic FROM profile WHERE id=$suid ";
			$fb_profile_info = wordbooker_fql_query($query,$wbuser2->access_token);
			$fb_profile_info=$fb_profile_info[0];
		} 
		catch (Exception $e) {
			$error_msg = $e->getMessage();
			wordbooker_debugger("Failed to get user info : ",$error_msg,0);
		}
				
		try {
			$query="SELECT name, page_url, page_id FROM page WHERE page_id IN (SELECT page_id FROM page_admin WHERE uid= $uid )";
			$fb_page_info = wordbooker_fql_query($query,$wbuser2->access_token);
		} 
		catch (Exception $e) 
		{
		$error_msg = $e->getMessage();
		wordbooker_debugger("Failed to get page info : ",$error_msg,0);
		}
		try {
		$ret_code=wordbooker_me($wbuser2->access_token);
		}
		catch (Exception $e) 
		{
		$error_msg = $e->getMessage();
		wordbooker_debugger("Failed to get page tokens : ".$error_msg," ",0);
		}
		if (isset($ret_code->data)){
		foreach($ret_code->data as $page_access) {
			$page_token[$page_access->id]=$page_access->access_token;
		}
		$all_pages=array();
		if (is_array($fb_page_info)) { 
			$encoded_names=str_replace('\\','\\\\',serialize($fb_page_info));
			 foreach ( $fb_page_info as $pageinfo ) {	
				$pages["id"]="FW:".trim($pageinfo->page_id,',');
				if(strlen($pageinfo->name)>1){
					if (function_exists('mb_convert_encoding')) {
						$pages["name"]=mb_convert_encoding($pageinfo->name,'UTF-8');
					}
					else
					{
						$pages["name"]=$pageinfo->name;
					}
					$pages['url']=$pageinfo->page_url;
					$pages["access_token"]=$page_token[$pageinfo->page_id];
					$all_pages[]=$pages;
					wordbooker_debugger("Page info for page ID ".$pageinfo->page_id,$pageinfo->name,0) ;
				}

			}
		}
		 else {
			wordbooker_debugger("Failed to get page information from FB"," ",0);
		 }
		}
	$fb_group_list=array();
	$all_groups=array();
		try {
			$query="Select positions, gid from group_member where uid=$uid";
			$fb_groups= wordbooker_fql_query($query,$wbuser2->access_token);
			if(is_array($fb_groups)){
				foreach($fb_groups as $fb_group){
					# Check to see if there are any positions. If not then the user is only a member of the group and thus we dont want it in the list.
					if(count($fb_group->positions)>0) {
						$query="Select name,gid from group where gid =".$fb_group->gid;
						$fb_group_info= wordbooker_fql_query($query,$wbuser2->access_token);
						$fb_group_list[]= (array) $fb_group_info;
					}			
				}
			}
		} 

		catch (Exception $e) 
		{
			$error_msg = $e->getMessage();
			wordbooker_debugger("Failed to get group info : ",$error_msg,0);
		}

			if (is_array($fb_group_list)) {
		$encoded_names=str_replace('\\','\\\\',serialize($fb_group_list));
		 foreach ( $fb_group_list as $groupinfo ) {
			$groupinfo = (array) $groupinfo;
			$groups["page_id"]=trim($groupinfo[0]->gid,',');	
			$groups["id"]="GW:".trim($groupinfo[0]->gid,',');
			if (function_exists('mb_convert_encoding')) {
				$groups["name"]=mb_convert_encoding($groupinfo[0]->name,'UTF-8');
			}
			else
			{
				$groups["name"]=$groupinfo[0]->name;
			}
			$groups["access_token"]="dummy access token";
			$all_groups[]=$groups;
			wordbooker_debugger("Group info for group ID ".$groupinfo[0]->gid,$groupinfo[0]->name,0) ;
			}
		}
		 else {
			wordbooker_debugger("Failed to get group information from FB"," ",0);
		 }

		
		
		try {
			$query="Select is_app_user FROM user where uid=$uid";
			$fb_app_info = wordbooker_fql_query($query,$wbuser2->access_token);
		} 
		catch (Exception $e) 
		{
			$error_msg = $e->getMessage();
			wordbooker_debugger("Failed to get app_user inf : ",$error_msg,0);
		}

		
		$all_pages_groups=array_merge($all_pages,$all_groups);
		$encoded_names=str_replace('\\','\\\\',serialize($all_pages_groups));
/*

		try {
			$query="SELECT flid, owner, name FROM friendlist WHERE owner=$uid";
			$fb_friend_lists= wordbooker_fql_query($query,$wbuser2->access_token);
		if (is_array($fb_friend_lists)) {
		$sql="Delete from ".WORDBOOKER_FB_FRIEND_LISTS." where user_id=".$user_id;
		$result = $wpdb->get_results($sql);
		foreach ($fb_friend_lists as $friend_list) {
			if (function_exists('mb_convert_encoding')) {
				$friend_list->name=mb_convert_encoding($friend_list->name,'UTF-8');
			}
		$sql="replace into ".WORDBOOKER_FB_FRIEND_LISTS." (user_id, flid,  owner, name) values (".$user_id.",'".$friend_list->flid."','".$friend_list->owner."','".$friend_list->name."')";
		$result = $wpdb->get_results($sql);
		}
		}	

		}
		catch (Exception $e) 
		{
			$error_msg = $e->getMessage();
			wordbooker_debugger("Failed to get friend lists : ",$error_msg,0);
		}

 
		try {
			$query="Select name,uid from user where uid in (Select uid from friendlist_member where flid='10150839623220195')";
			$fb_friends_info = wordbooker_fql_query($query,$wbuser2->access_token);
		} 
		catch (Exception $e) 
		{
			$error_msg = $e->getMessage();
			wordbooker_debugger("Failed to get friends : ",$error_msg,0);
		}
			if (is_array($fb_friends_info) ) {
			$sql="delete from ".WORDBOOKER_FB_FRIENDS." where user_id=".$user_id;
			$result = $wpdb->get_results($sql);
			foreach ($fb_friends_info as $friend_info) {
				if (function_exists('mb_convert_encoding')) {
					$friend_info->name=mb_convert_encoding($friend_info->name,'UTF-8');
				}
			$sql="insert into ".WORDBOOKER_FB_FRIENDS." (user_id, facebook_id, name, blog_id) values (".$user_id.",'".$friend_info->uid."','".$friend_info->name."',".$blog_id.")";
			$result = $wpdb->get_results($sql);
			}
		}
*/

		wordbooker_debugger("Setting name as  : ",mysql_real_escape_string($fb_profile_info->name),0) ;
		$sql="insert into ".WORDBOOKER_USERSTATUS." set name='".mysql_real_escape_string($fb_profile_info->name)."'";
		$fb_status_info=$fb_status_info[0];
			if (isset($fb_status_info->time)) {
				if (stristr($fb_status_info->message,"[[PV]]")) {
					wordbooker_debugger("Found [[PV]] - not updating status"," ",0);
				} 
				else {
					wordbooker_debugger("Setting status as  : ",mysql_real_escape_string($fb_status_info->message),0) ;
					$sql.=", status='".mysql_real_escape_string($fb_status_info->message)."'";
					$sql.=", updated=".mysql_real_escape_string($fb_status_info->time);
				}
		} else {wordbooker_debugger("Failed to get Status information from FB"," ",0); }
		if (isset($fb_profile_info->url)) {
			wordbooker_debugger("Setting URL as  : ",mysql_real_escape_string($fb_profile_info->url),0) ;
			$sql.=", url='".mysql_real_escape_string($fb_profile_info->url)."'";
			$sql.=", pic='".mysql_real_escape_string($fb_profile_info->pic)."'";
		}	else {wordbooker_debugger("Failed to get Image information from FB"," ",0); }
		$sql.=", facebook_id='".$uid."'";
		$sql.=",user_ID=".$user_id;
		$sql.=",blog_id=".$blog_id;
		$sql.=" on duplicate key update name='".mysql_real_escape_string($fb_profile_info->name)."'";
		if (isset($fb_status_info->message)) {
			if (stristr($fb_status_info->message,"[[PV]]")) {
			} 
			else {
				$sql.=", status='".mysql_real_escape_string($fb_status_info->message)."'";
				$sql.=", updated=".mysql_real_escape_string($fb_status_info->time);
			}
		}
		if (isset($fb_profile_info->url)) {
			$sql.=", url='".mysql_real_escape_string($fb_profile_info->url)."'";
			$sql.=", pic='".mysql_real_escape_string($fb_profile_info->pic)."'";
		}
	#	var_dump($sql);
		$result = $wpdb->get_results($sql);
		wordbooker_debugger("Setting name as  : ",mysql_real_escape_string($fb_profile_info->name),0) ;
		$sql="update ".WORDBOOKER_USERDATA." set name='".mysql_real_escape_string($fb_profile_info->name)."'";
			
		$sql.=", facebook_id='".$uid."'";
		$sql.=", pages= '".mysql_real_escape_string($encoded_names)."'";
		#var_dump((integer) $fb_app_info[0]->is_app_user);
		if (is_array($fb_app_info)) {
			$sql.=", use_facebook=".(integer) $fb_app_info[0]->is_app_user;
		}
		$sql.="  where user_ID=".$user_id." and blog_id=".$blog_id;

		
		$result = $wpdb->get_results($sql);
	}
#fclose($fp);
	wordbooker_debugger("Cache Refresh Complete for user",$uid,0) ; 
}


function wordbooker_poll_facebook($single_user=null) {
	global  $wpdb, $user_id,$table_prefix,$blog_id;
	# If a user ID has been passed in then restrict to that single user.
	$limit_user="";
	if (isset($single_user)) {$limit_user=" where user_id=".$single_user." limit 1";}
	$wordbooker_settings =get_option('wordbooker_settings'); 
	
	# This runs through the Cached users and refreshes them
      	$sql="Select user_id from ".WORDBOOKER_USERDATA.$limit_user;
        $wb_users = $wpdb->get_results($sql);
	if (is_array($wb_users)) {
		wordbooker_debugger("Batch Cache Refresh Commence "," ",0) ; 
		foreach ($wb_users as $wb_user){	
			wordbooker_debugger("Calling Cache refresh for  :  ",$wb_user->user_id,0) ;	
			$wbuser = wordbooker_get_userdata($wb_user->user_id);
		#	$fbclient = wordbooker_fbclient($wbuser);
			wordbooker_cache_refresh($wb_user->user_id);
		}
		wordbooker_debugger("Batch Cache Refresh completed "," ",0) ; 
	}

	if ( !isset($wordbooker_settings['wordbooker_comment_get'])) {
		wordbooker_debugger("Comment Scrape not active. Cron Finished "," ",0) ; 
		return;
	}

	// Yes they have so lets get to work. We have to get the FB users associated with this blog
        $sql="Select user_id from ".WORDBOOKER_USERDATA." where blog_id=".$blog_id." ".$limit_user;
        $wb_users = $wpdb->get_results($sql);
	if (!is_array($wb_users)) {
		return;
	}
	return;
	# Comment handling has been de-activated for the initial release of V2
	foreach ($wb_users as $wb_user){	
		$wbuser = wordbooker_get_userdata($wb_user->user_id);
	#	$fbclient = wordbooker_fbclient($wbuser);
		// Now we need to check if they've set Auto Approve on comments.
		$comment_approve=0;
		if (isset($wordbooker_settings['wordbooker_comment_approve'])) {$comment_approve=1;}
		# What we need to do is got to the POSTCO
		// Go the postcomments table - this contains a list of FB post_ids, the wp post_id that corresponds to it and the timestamps of the last FB comment pulled.
		$sql='Select fb_post_id,comment_timestamp,wp_post_id from ' . WORDBOOKER_POSTCOMMENTS . ' where user_id = '.$wb_user->user_id.' and blog_id='.$blog_id.' order by fb_post_id desc ';	
		$rows = $wpdb->get_results($sql);
		// For each FB post ID we find we go out to the stream on Facebook and grab the comments.
		if (count($rows)>0) {
			foreach ($rows as $comdata_row) {
				$fbsql='select time,text,fromid,xid from comment where time >'.$comdata_row->comment_timestamp." and post_id='".$comdata_row->fb_post_id."'";
				$fbcomments=$fbclient->fql_query($fbsql);
				if (is_array($fbcomments)) {
					foreach ($fbcomments as $comment) {
						// If the comment has a later timestamp than the one we currently have recorded then lets get some more information 
						if ($comment[time]>$comdata_row->comment_timestamp) {
							$fbuserinfo=$fbclient->users_getInfo($comment[fromid],'name,profile_url');
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
}
?>
