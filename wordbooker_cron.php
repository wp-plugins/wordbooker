<?php

/**
Extension Name: Wordbooker Cron
Extension URI: http://blogs.canalplan.org.uk/steve
Version: 1.5
Description: Code to pull comments back from Facebook
Author: Steve Atty
*/

function wordbook_poll_facebook() {
	global  $wpdb, $user_id,$table_prefix;
	define('WORDBOOKER_USERDATA', $table_prefix . 'wordbook_userdata');
	define ('DEBUG', false);
	$wordbook_settings =get_option('wordbook_settings'); 
	$debug_file='/tmp/wordbook_'.$table_prefix.'debug';
	if (DEBUG) {
		$fp = fopen($debug_file, 'a');
		// Has the user set the option to get their comments?
		$debug_string=date("Y-m-d H:i:s",time())." : Cron Running\n";
		fwrite($fp, $debug_string);
	}

	// Here we need to check if they have any future published posts that we need to handle

	if ( !isset($wordbook_settings['wordbook_comment_get'])) {
		if (DEBUG) {
			$debug_string=date("Y-m-d H:i:s",time())." : Comment Scrape not active. Cron Finished\n";
			fwrite($fp, $debug_string);
 			fclose($fp); 
		}
		return;
	}

	// Yes they have so lets get to work. We have to get the FB user associated with this blog
	// We can support multiple users by removing the limit 1 and putting another for loop round this block, so it can pick up everything.
        $sql="Select user_id from ".WORDBOOKER_USERDATA;
        $wb_users = $wpdb->get_results($sql);
	foreach ($wb_users as $wb_user){
		if (DEBUG) {
			$debug_string="Processing data for user ".$wb_user->user_id."\n";
			fwrite($fp, $debug_string);
		}	
		$wbuser = wordbook_get_userdata($wb_user->user_id);
		$fbclient = wordbook_fbclient($wbuser);
		#sleep(5);
		// Now we need to check if they've set Auto Approve on comments.
		$comment_approve=0;
		if (isset($wordbook_settings['wordbook_comment_approve'])) {$comment_approve=1;}
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
				$fbsql='select time,text,fromid from comment where time >'.$comdata_row->comment_timestamp." and post_id='".$comdata_row->fb_post_id."'";
				$fbcomments=$fbclient->fql_query($fbsql);
				#sleep(5);
				if (is_array($fbcomments)) {
					if (DEBUG) {
						$debug_string="Number of comments to process for post ".$comdata_row->fb_post_id." is ".count($fbcomments) ."\n";
						fwrite($fp, $debug_string);
					}	
					foreach ($fbcomments as $comment) {
						if (DEBUG) {
							$debug_string="incoming comment time is ".$comment[time]." and the last recorded comment time stamp was ".$comdata_row->comment_timestamp."\n";
							fwrite($fp, $debug_string);
						}
						// If the comment has a later timestamp than the one we currently have recorded then lets get some more information 
						if ($comment[time]>$comdata_row->comment_timestamp) {
							$fbuserinfo=$fbclient->users_getInfo($comment[fromid],'name,profile_url');
							#sleep(5);
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
								'comment_agent' => 'Wordbook Interface to Facebook',
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
