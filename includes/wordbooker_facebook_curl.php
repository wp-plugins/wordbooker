<?php

/*
Description:  Wordbooker Facebook Interface functions  - using Curl.
Author: Stephen Atty
Author URI: http://wordbooker.tty.org.uk
Version: 2.0.8
*/

/*
 * Copyright 2011 Steve Atty (email : posty@tty.org.uk)
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


function wordbooker_fb_stream_pubish($data,$target) {
	$url='https://graph.facebook.com/'.$target.'/feed';
	$x=wordbooker_make_curl_post_call($url,$data);
	 return($x);
}

function wordbooker_fb_status_update($data,$target) {
	$url='https://graph.facebook.com/'.$target.'/feed';
	$x=wordbooker_make_curl_post_call($url,$data);
        return($x);
}

function wordbooker_fb_note_publish($data,$target){
	$url='https://graph.facebook.com/'.$target.'/notes';
	$x=wordbooker_make_curl_post_call($url,$data);
        return($x);
}


function wordbooker_fql_query($query,$access_token) {
        $url = 'https://api.facebook.com/method/fql.query?access_token='.$access_token.'&query='.rawurlencode($query).'&format=JSON-STRINGS';
	$x=wordbooker_make_curl_call($url);
        return($x);
}

function wordbooker_me($access_token) {
        $url = 'https://graph.facebook.com/me/accounts?access_token='.$access_token.'&format=JSON-STRINGS';
	$x=wordbooker_make_curl_call($url);
        return($x);
}

function wordbooker_get_fb_id($access_token) {
        $url = 'https://graph.facebook.com/me?fields=id,name&access_token='.$access_token.'&format=JSON';
		$x=wordbooker_make_curl_call($url);
        return($x);
}


function wordbooker_me_status($fb_id,$access_token) {
	if (!isset($fb_id)){$fb_id='me';}
        $url = 'https://graph.facebook.com/'.$fb_id.'?access_token='.$access_token.'&format=JSON';
	$x=wordbooker_make_curl_call($url);
        return($x);
}

function wordbooker_fb_pemissions($fb_id,$access_token) {
	if (!isset($fb_id)){$fb_id='me';}
        $url = 'https://graph.facebook.com/'.$fb_id.'/permissions?access_token='.$access_token.'&format=JSON-STRINGS';
	$x=wordbooker_make_curl_call($url);
        return($x);
}

function wordbooker_make_curl_call($url) {
 	$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/fb_ca_chain_bundle.crt');
        $response = curl_exec($ch);
	$err_no=curl_errno($ch);
        curl_close($ch);
	#var_dump($response);
#	$response=preg_replace('/"gid":(\d+)/', '"gid":"$1"', $response );
#	$x=json_decode( preg_replace('/"page_id":(\d+)/', '"page_id":"$1"', $response ) );
	$x=json_decode( $response);
	if (isset($x->message)) { 
		throw new Exception ($x->message);
	}
	 return( $x);
}


function wordbooker_make_curl_post_call($url,$data) {
 	$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
   	curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/fb_ca_chain_bundle.crt');
   	 curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
	$err_no=curl_errno($ch);
        curl_close($ch);
	$x=json_decode($response);
	if (isset($x->error->message)) { 
		throw new Exception ($x->error->message);
	}
	 return($x);
}
?>
