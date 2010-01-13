<?php

/**
Extension Name: Wordbooker Options 
Extension URI: http://blogs.canalplan.org.uk/steve
Version: 1.5
Description: Advanced Options for the WordBooker Plugin
Author: Steve Atty
*/

function wbs_is_hash_valid($form_hash) {
	$ret = false;
	$saved_hash = wbs_retrieve_hash();
	if ($form_hash === $saved_hash) {
		$ret = true;
	}
	return $ret;
}

function wbs_generate_hash() {
	return md5(uniqid(rand(), TRUE));
}

function wbs_store_hash($generated_hash) {
	return update_option('wordbook_token',$generated_hash,'WordBook Security Hash');
}

function wbs_retrieve_hash() {
	$ret = get_option('wordbook_token');
	return $ret;
}


function wordbook_option_manager() {
	global $ol_flash, $wordbook_settings, $_POST, $wp_rewrite,$user_ID,$wpdb, $table_prefix;
	//Set some defaults:
	$wordbook_settings =get_option('wordbook_settings'); 
	// If no default author set, lets set it
	if (! isset($wordbook_settings["wordbook_default_author"])){ $wordbook_settings["wordbook_default_author"]=$user_ID;}
	// If no default republish time frame set, then set it.
	if (! isset($wordbook_settings["wordbook_republish_time_frame"])){ $wordbook_settings["wordbook_republish_time_frame"]=10;}
	// If no attribute set, then set it.
	if (! isset($wordbook_settings["wordbook_attribute"])){ $wordbook_settings["wordbook_attribute"]="Posted a new post on their blog";}
	// If no Status line text, then set it 
	if (! isset($wordbook_settings["wordbook_status_update_text"])){ $wordbook_settings["wordbook_status_update_text"]="New Blog Post:";}
	// No Share link set, then set it
	if (! isset($wordbook_settings["wordbook_actionlink"])){ $wordbook_settings["wordbook_actionlink"]=300;}
	// If the extract length isn't set the we know the user hasn't been here before so lets set up a few things:
 	if (! isset($wordbook_settings['wordbook_extract_length'])) {
		// Comment scraping is done once an hour by a cron job. So lets set it up.
		$dummy=wp_schedule_event(time(), 'hourly', 'wb_cron_job');
		// And they wont have the extract length so lets set that so we don't run this again!
		$wordbook_settings['wordbook_extract_length']=256;
	}
	// Now lets write those setting back.
	update_option('wordbook_settings',$wordbook_settings);
		echo '<div class="wrap">';
		#var_dump($_POST);
		// Easiest test to see if we have been submitted to
		if(isset($_POST['token'])) {
			// Now we check the hash, to make sure we are not getting CSRF
			if(wbs_is_hash_valid($_POST['token'])) {
				if (isset($_POST['wordbook_extract_length'])) { 
					$wordbook_settings['wordbook_default_author'] = $_POST['wordbook_default_author'];
					$wordbook_settings['wordbook_extract_length'] = $_POST['wordbook_extract_length'];
				        $wordbook_settings['wordbook_publish_default'] = $_POST['wordbook_publish_default'];
				        $wordbook_settings['wordbook_publish_override'] = $_POST['wordbook_publish_override'];
				        $wordbook_settings['wordbook_republish_time_frame'] = $_POST['wordbook_republish_time_frame'];
				        $wordbook_settings['wordbook_republish_time_obey'] = $_POST['wordbook_republish_time_obey'];
	 				$wordbook_settings['wordbook_attribute'] = $_POST['wordbook_attribute'];
					$wordbook_settings['wordbook_comment_get'] = $_POST['wordbook_comment_get'];
					$wordbook_settings['wordbook_comment_push'] = $_POST['wordbook_comment_push'];
					$wordbook_settings['wordbook_comment_approve'] = $_POST['wordbook_comment_approve'];
					$wordbook_settings["wordbook_permit"]=$_POST['wordbook_permit'];
					$wordbook_settings["status_update_permit"]=$_POST['status_update_permit'];
					$wordbook_settings["wordbook_status_update_text"]=$_POST['wordbook_status_update_text'];
					$wordbook_settings["wordbook_status_update"]=$_POST['wordbook_status_update'];
					$wordbook_settings["wordbook_comment_poll"]=$_POST['wordbook_comment_poll'];
					$wordbook_settings["wordbook_pages"]=$_POST['wordbook_pages'];
					$wordbook_settings["wordbook_actionlink"]=$_POST['wordbook_actionlink'];
					$wordbook_settings["wordbook_search_this_header"]=$_POST['wordbook_search_this_header'];
				        update_option('wordbook_settings',$wordbook_settings);
			        }
		        $ol_flash = "Your settings have been saved.";
			} else {
				// Invalid form hash, possible CSRF attempt
				$ol_flash = "Security hash missing.";
			} // endif wbs_is_hash_valid
		} 
	
	if ($ol_flash != '') echo '<div id="message" class="updated fade"><p>' . $ol_flash . '</p></div>';
	wordbook_option_notices();
	$sql="select user_ID from ".WORDBOOKER_USERDATA." where user_ID=".$user_ID;
	$result = $wpdb->get_results($sql);
	$wbuser = wordbook_get_userdata($result[0]->user_ID);
	if ($wbuser->session_key) {
		$temp_hash = wbs_generate_hash();
		wbs_store_hash($temp_hash);
		$checked_flag=array('on'=>'checked','off'=>'');
		echo '<div class="wrap">';
		echo '<h2>WordBooker Plugin</h2><p><h3>Customisation</h3>';
		echo'<form action="" name="wboptions" method="post">
		<input type="hidden" name="redirect" value="true" />
		<input type="hidden" name="wordbook_permit" value="'.$wordbook_settings["wordbook_permit"].'" />
		<input type="hidden" name="status_update_permit" value="'.$wordbook_settings["status_update_permit"].'" />
		<input type="hidden" name="token" value="' . wbs_retrieve_hash() . '" />';
		$sql="select wpu.ID,wpu.display_name from $wpdb->users wpu,".WORDBOOKER_USERDATA." wud where wpu.ID=wud.user_id and wud.use_facebook=1;";
		$wb_users = $wpdb->get_results($sql); 
		echo 'Unless changed, Posts will be published on the Facebook belonging to : <select name="wordbook_default_author" ><option value=0>Select Default Facebook User</option>';
		$option="";
  		foreach ($wb_users as $wb_user) {	
			if ($wb_user->ID==$wordbook_settings["wordbook_default_author"] ) {$option .= '<option selected="yes" value='.$wb_user->ID.'>';} else {
        		$option .= '<option value='.$wb_user->ID.'>';}
        		$option .= $wb_user->display_name;
        		$option .= '</option>';
		}
		echo $option;
		echo '</select><br>
                <label for="wb_extract_length">Length of Extract :
		<select id="wordbook_extract_length" name="wordbook_extract_length"  >';
        
	        $arr = array(200=> "200",  250=> "250", 256=>"256 (Default) ", 270=>"270", 300=>"300", 350 => "350",400 => "400");
         
                foreach ($arr as $i => $value) {
                        if ($i==$wordbook_settings['wordbook_actionlink']){ print '<option selected="yes" value="'.$i.'" >'.$arr[$i].'</option>';}
                       else {print '<option value="'.$i.'" >'.$arr[$i].'</option>';}}
                echo "</select><br>";

		echo '<label for="wb_publish_default">Default Publish Post to Facebook : ';
		echo '<INPUT TYPE=CHECKBOX NAME="wordbook_publish_default" '.$checked_flag[$wordbook_settings["wordbook_publish_default"]].' ></P><br>';
		echo '<label for="wb_attribute">Post Attribute : ';
		echo '<INPUT NAME="wordbook_attribute" size=50 maxlength=50 value="'.stripslashes($wordbook_settings["wordbook_attribute"]).'"></P><br>';
		echo '<label for="wb_publish_timeframe">Republish Post if edited more than  : ';
		echo '<INPUT NAME="wordbook_republish_time_frame" size=3 maxlength=3 value='.$wordbook_settings["wordbook_republish_time_frame"].'> days ago <INPUT TYPE=CHECKBOX NAME="wordbook_republish_time_obey" '.$checked_flag[$wordbook_settings["wordbook_republish_time_obey"]].' ><br>';
		echo '<label for="wb_publish_republicaro">Override Re-Publication window : ';
		echo '<INPUT TYPE=CHECKBOX NAME="wordbook_publish_override" '.$checked_flag[$wordbook_settings["wordbook_publish_override"]].' > ( Force Re-Publish Post to Facebook on Edit )</P><br>';
		echo '<label for="wb_status_update">Update Facebook Status  : ';
		echo '<INPUT TYPE=CHECKBOX NAME="wordbook_status_update" '.$checked_flag[$wordbook_settings["wordbook_status_update"]].' > <INPUT NAME="wordbook_status_update_text" size=50 maxlength=50 value="'.stripslashes($wordbook_settings["wordbook_status_update_text"]).'">';
echo '</select></P><br>
               <label for="wb_action_link">Action Link Option :
		         <select id="wordbook_actionlink" name="wordbook_actionlink"  >';	
       $arr = array(100=> "None ",  200=> "Share Link ", 300=>"Read Full Article");
                foreach ($arr as $i => $value) {
                        if ($i==$wordbook_settings['wordbook_actionlink']){ print '<option selected="yes" value="'.$i.'" >'.$arr[$i].'</option>';}
                       else {print '<option value="'.$i.'" >'.$arr[$i].'</option>';}}
                echo "<</select></P><br>";
		echo '<label for="wordbook_search_this_header">Enable Extended description for Share Link : ';
		echo '<INPUT TYPE=CHECKBOX NAME="wordbook_search_this_header" '.$checked_flag[$wordbook_settings["wordbook_search_this_header"]].'></P><br><br>';
		echo '<label for="wb_publish_comment_approve">Import Comments from Facebook for Wordbook Posts : ';
		echo '<INPUT TYPE=CHECKBOX NAME="wordbook_comment_get" '.$checked_flag[$wordbook_settings["wordbook_comment_get"]].'> ( Next Scheduled fetch is at : '.date("H:i:s",wp_next_scheduled('wb_cron_job')).' ) </P><br>';
		echo '<label for="wb_publish_comment_approve">Auto Approve imported comments : ';
		echo '<INPUT TYPE=CHECKBOX NAME="wordbook_comment_approve" '.$checked_flag[$wordbook_settings["wordbook_comment_approve"]].'></P><br>';
		echo '<label for="wb_publish_comment_push">Push Comments up to Facebook : ';
		echo '<INPUT TYPE=CHECKBOX NAME="wordbook_comment_push" '.$checked_flag[$wordbook_settings["wordbook_comment_push"]].'></P><br>';
		echo '<label for="wb_comment_poll">Force Poll for Comments when visiting this screen : ';
		echo '<INPUT TYPE=CHECKBOX NAME="wordbook_comment_poll" '.$checked_flag[$wordbook_settings["wordbook_comment_poll"]].'></P>';
		$fbclient = wordbook_fbclient($wbuser);
		# obtain a list of pages which the current user is an admin for.
		$result=$fbclient->fql_query('SELECT page_id FROM page_admin WHERE uid ='.$fbclient->users_getLoggedInUser());
		if (is_array($result)) {
			foreach($result as $res){
				$fan_pages[]=$res['page_id'];
			}
			$comma_separated = implode(",", $fan_pages);
			$result=$fbclient->pages_getInfo($comma_separated,'name','','');
			echo '<br><br>You can also post to the following Facebook pages : ';
			$fbpages="";		
			foreach($result as $fbpage) { 
			$fbpages.=" ".$fbpage['name'].",";
			}
			$fbpages=trim($fbpages," ,");
			echo $fbpages."</p><br>";	
			$serialed=serialize($result);
			echo "<input type='hidden' name='wordbook_pages' value='".$serialed."' />";
		}
		echo '<br><br><p><input type="submit" value="Save Options" class="button-primary"  /></p></form><br><hr>';
		wordbook_option_status($wbuser);
		wordbook_render_errorlogs();
        } else {
		wordbook_option_setup($wbuser);
	}
	// Lets poll if they want to
	if ( isset($wordbook_settings["wordbook_comment_poll"])){
		$dummy=wordbook_poll_facebook();
	}
	?>
	<br><br><hr><br><h3>Donate</h3>
	If you've found this extension useful then please feel free to donate to its support and future development<br><br>
	  
	<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
	<input type="hidden" name="cmd" value="_s-xclick">
	<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
	<input type="image" src="https://www.paypal.com/en_US/GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online.">
	<img alt="" border="0" src="https://www.paypal.com/en_GB/i/scr/pixel.gif" width="1" height="1">
	</form>
	<br>
	<?php
	echo "<hr>";
	wordbook_option_support();
	echo "</div>";
}

/* Use the admin_menu action to define the custom boxes. Dont do this unless we have options set */
if(get_option('wordbook_settings')) {
	add_action('admin_menu', 'wordbook_add_custom_box');
}

/* Adds a custom section to the "advanced" Post edit screens */
function wordbook_add_custom_box() {
    add_meta_box( 'wordbook_sectionid', __( 'WordBooker Options', 'wordbook_textdomain' ),'wordbook_inner_custom_box', 'post', 'advanced' );
}
   
/* Prints the inner fields for the custom post/page section */
function wordbook_inner_custom_box() {
	echo '<input type="hidden" name="wordbook_noncename" id="wordbook_noncename" value="' . 
	wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
	global $wpdb;
	$wordbook_settings=get_option('wordbook_settings'); 
	$checked_flag=array('on'=>'checked','off'=>'');
	echo "The following options override the defaults set on the options page<br><br>";
	$sql="select wpu.ID,wpu.display_name from $wpdb->users wpu,".WORDBOOKER_USERDATA." wud where wpu.ID=wud.user_id and wud.use_facebook=1;";
	$wb_users = $wpdb->get_results($sql);
	echo 'Posts will be published on the Facebook belonging to : <select name="wordbook_default_author_override" >';
	foreach ($wb_users as $wb_user) {	
		if ($wb_user->ID==$wordbook_settings["wordbook_default_author"] ) {$option = '<option selected="yes" value='.$wb_user->ID.'>';} else {
		$option = '<option value='.$wb_user->ID.'>';}
		$option .= " ".$wb_user->display_name."&nbsp;&nbsp;";
		$option .= '</option>';
		echo $option;
	}
	echo '</select><br>';
	echo '<input type="hidden" name="wordbook_page_post" value="-100" />';
	if (strlen($wordbook_settings['wordbook_pages']) > 0 ){
		echo ' Or post to the following fan page :  <select name="wordbook_page_post" ><option selected="yes" value=-100>Select Fan Page&nbsp;&nbsp;</option>';
		$fanpages=unserialize(stripslashes($wordbook_settings['wordbook_pages']));
		foreach ($fanpages as $fan_page) {
			$option = '<option value='.$fan_page[page_id].'>';
			$option .= $fan_page[name]."&nbsp;&nbsp;";
			$option .= '</option>';
			echo $option;
		}
		echo '</select><br>'; 
	}

	echo 'Action Link Option :<select id="wordbook_actionlink" name="wordbook_actionlink_overide"  >';	
       $arr = array(100=> "None ",  200=> "Share Link ", 300=>"Read Full Article ");
                foreach ($arr as $i => $value) {
                        if ($i==$wordbook_settings['wordbook_actionlink']){ print '<option selected="yes" value="'.$i.'" >'.$arr[$i].'</option>';}
                       else {print '<option value="'.$i.'" >'.$arr[$i].'</option>';}}
                echo "<</select></P><br><br>";

	echo '<input type="hidden" name="soupy" value="twist" />';
	echo '<INPUT TYPE=CHECKBOX NAME="wordbook_publish_default_action" '.$checked_flag[$wordbook_settings["wordbook_publish_default"]].' > Publish Post to Facebook</P><br>';
	echo '<INPUT TYPE=CHECKBOX NAME="wordbook_publish_overridden" '.$checked_flag[$wordbook_settings["wordbook_publish_override"]].' > Force Re-Publish Post to Facebook on Edit (overrides republish window)</P><br>';
	echo '<INPUT TYPE=CHECKBOX NAME="wordbook_comment_overridden" '.$checked_flag[$wordbook_settings["wordbook_comment_get"]].' > Fetch comments from Facebook for this post</P><br>';
	echo 'Facebook Post Attribute line: <INPUT NAME="wordbook_attribution" size=50 maxlength=50 value="'.stripslashes($wordbook_settings["wordbook_attribute"]).'"></P><br>';	
	echo '<INPUT TYPE=CHECKBOX NAME="wordbook_status_update_override" '.$checked_flag[$wordbook_settings["wordbook_status_update"]].' > &nbsp;Facebook Status Update&nbsp;: <INPUT NAME="wordbook_status_update_text_override" size=50 maxlength=50 value="'.stripslashes($wordbook_settings["wordbook_status_update_text"]).'"><br>';

}
?>
