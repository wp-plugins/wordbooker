<?php

/*
Description: Facebook Interface functions - using Fopen related calls
Author: Stephen Atty
Author URI: http://canalplan.blogdns.com/steve
Version: 2.0
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

global $wp_version;

function wordbooker_make_fopen_call($url) {
 	$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
	$err_no=curl_errno($ch);
        curl_close($ch);
	$x=json_decode($response);
	if (isset($x->error_code)) { 
		throw new Exception ($x->error_msg);
	}
	 return($x);
}



function wordbooker_make_fopen_post_call($url, $params, $ch=null) {
    
    $content = "";
    foreach ($params as $key => $param) {
        $content .= "{$key}=" . urlencode($param) . "&";
    }
    substr($post, 0, strlen($post) - 1);
    
    $user_agent = 'Wordbooker Version 2 (non-curl) ' . phpversion();
    $content_type = 'application/x-www-form-urlencoded';
    
    $content_length = strlen($content);
    $context =
      array('http' =>
              array('method' => 'POST',
                    'user_agent' => $user_agent,
                    'header' => 'Content-Type: ' . $content_type . "\r\n" .
                                'Content-Length: ' . $content_length,
                    'content' => $content));
    $context_id = stream_context_create($context);
    $sock = @fopen($url, 'r', false, $context_id);

    $result = '';
    if ($sock) {
      while (!feof($sock)) {
        $result .= fgets($sock, 4096);
      }
      fclose($sock);
    }

    //error_log("MAKE REQUEST RESULT : " . $result);

    return $result;    
    
  }
?>
