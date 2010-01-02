<?php

/*
Description: Facebook Widget. Needs Wordbook installing to work.
Author: Stephen Atty
Author URI: http://canalplan.blogdns.com/steve
Version: 1.3
*/

/*
 * Copyright 2009 Steve Atty (email : posty@tty.org.uk)
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


register_sidebar_widget(array("FaceBook Status", 'widgets'), 'widget_facebook');
register_widget_control('FaceBook Status', 'fb_widget_control', '500', '500');

function nicetime($date)
{
   
    $periods         = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
    $lengths         = array("60","60","24","7","4.35","12","10");
   
    $now             = time();
    $unix_date         = $date;
   
       // check validity of date
    if(empty($unix_date)) {   
        return "Bad date";
    }

    // is it future date or past date
    if($now > $unix_date) {   
        $difference     = $now - $unix_date;
        $tense         = "ago";
       
    } else {
        $difference     = $unix_date - $now;
        $tense         = "from now";
    }
   
    for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
        $difference /= $lengths[$j];
    }
   
    $difference = round($difference);
   
    if($difference != 1) {
        $periods[$j].= "s";
    }
   
    return "$difference $periods[$j] {$tense}";
}

function widget_facebook($args) {
	extract($args);
        $fb_widget_options = unserialize(get_option('fb_widget_options'));
	$title = stripslashes($fb_widget_options['title']);
        $dispname = stripslashes($fb_widget_options['dispname']);
        $dformat=$fb_widget_options['df'];
	echo $before_widget . $before_title . $title . $after_title;
        global $wpdb;
        // We need to get the user_id from the userdata table for this blog.
        $sql="Select user_id from ".WORDBOOK_USERDATA." limit 1";
        $result = $wpdb->get_results($sql);
	$wbuser = wordbook_get_userdata($result[0]->user_id);
        $fbclient = wordbook_fbclient($wbuser);
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
                                echo '<div class="facebook_picture" align="center">';
                                echo '<a href="'.$profile_url.'" target="facebook">';
                                echo '<img src="'.$user['pic'].'" /></a>';
                                echo '</div>';
                        }
                        if (!($name = $user['first_name']))  $name = $user['name'];
                        if (strlen($dispname)>0) $name=$dispname; 
                        if ($user['status']['message']) {
                        	echo '<p><a href="'.$profile_url.'">'.$name.'</a>';
                                echo ' <i>'.$user['status']['message'].'</i><br> ';
                                if ($dformat=='fbt') { 
                                 echo '('.nicetime($user['status']['time']).').'; }
                                 else {echo '('.date($dformat, $user['status']['time']).').';}
                                echo '</p>';
                        }
                }
        }
	echo $after_widget;

}

function fb_widget_control() {
  // Check if the option for this widget exists - if it doesnt, set some default values
  // and create the option.
  if(!get_option('fb_widget_options'))
  {
    add_option('fb_widget_options', serialize(array('title'=>'Facebook Status', 'dispname'=>'', 'df'=>'D M j, g:i a')));
  }
  $fb_widget_options = $fb_widget_newoptions = unserialize(get_option('fb_widget_options'));
  
  // Check if new widget options have been posted from the form below - 
  // if they have, we'll update the option values.
  if ($_POST['fb_widget_title']){
    $fb_widget_newoptions['title'] = $_POST['fb_widget_title'];
  }
  if ($_POST['fb_widget_dispname']){
    $fb_widget_newoptions['dispname'] = $_POST['fb_widget_dispname'];
  }
  if ($_POST['fb_widget_dformat']){
    $fb_widget_newoptions['df'] = $_POST['fb_widget_dformat'];
  }
  if($fb_widget_options != $fb_widget_newoptions){
    $fb_widget_options = $fb_widget_newoptions;
    update_option('fb_widget_options', serialize($fb_widget_options));
  }
  // Display html for widget form
  ?>
  <p>
    <label for="fb_widget_title">Widget Title:<br />
      <input
      id="fb_widget_title" 
      name="fb_widget_title" 
      type="text" 
      value="<?php echo stripslashes($fb_widget_options['title']); ?>"/>
    </label>
  </p>
  <p>
    <label for="fb_widget_dispname">Display this name instead of your Facebook name :<br />
      <input
      id="fb_widget_dispname" 
      name="fb_widget_dispname"
      type="text" 
      value="<?php echo stripslashes($fb_widget_options['dispname']); ?>"/>
    </label>
  </p>
  <p>
    <label for="fb_widget_dformat">Date Format :<br />
<select id="fb_widget_dformat" name="fb_widget_dformat"  >
<?php
$ds12=date('D M j, g:i a');
$dl12=date('l F j, g:i a');
$dl24=date('l F j, h:i');
$ds24=date('D M j, h:i');
$drfc=date('r');
$arr = array('D M j, g:i a'=> "Short 12 (".$ds12.") - Default",  'l F j, g:i a'=> "Long 12 (".$dl12.") ", 'D M j, h:i'=>"Short 24 (".$ds24.") ", 'l F j, h:i'=>"Long 24 (".$dl24.")",fbt=>"Facebook Text style", r => "RFC 822 (".$drfc." ) ");
foreach ($arr as $i => $value) {
if ($i==$fb_widget_options['df']){ print '<option selected="yes" value="'.$i.'" >'.$arr[$i].'</option>';}
else {print '<option value="'.$i.'" >'.$arr[$i].'</option>';}

}
?>
</select>
    </label>
  </p>
  <?php
}  
?>
