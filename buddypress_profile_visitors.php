<?php
/**
 * Plugin Name: Buddypress Profile Visitors
 * Author: Venu Gopal Chaladi
 * Author URI:http://dhrusya.com
 * Version:1.9.5
 * Plugin URI:http://dhrusya.com/products
 * Description: Show number of profile views count by other members and recent visitors of member profile. And also show who is visiting the perticual member most on mouse over.
 * License: GPL
 * Tested with Buddypress 1.5+
 * Date: 15th October 2014
 * Updated : 2nd November 2020
 */

//ini_set( 'display_errors', true );
//error_reporting( E_ALL );


// function ds_register_session(){
//     if( !session_id() )
//         session_start();
// }
// add_action('init','ds_register_session');


function bp_profile_visitors_init() {
	// require( dirname( __FILE__ ) . '/includes/bp_views_core.php' );
	if (bp_is_user()) {
	global $bp, $wpdb;
	$duser_id = bp_displayed_user_id();
	$logged_id = bp_loggedin_user_id();
	$table_name = $wpdb->prefix . "bp_profile_visitors";
	$makecount=false;
	$vdate=date("Y-m-d h:i:s");
	// echo  "<div>".$duser_id." - ". $logged_id."</div>";

	if ($duser_id!=0  and $duser_id!= $logged_id and $logged_id!=0) {
			$profile_visitors = get_transient('profile_visitors');
			// print_r($profile_visitors);
			if ($profile_visitors){
				if(isset($profile_visitors[$logged_id])) {
					// this code runs when there is no valid transient set
					// echo  "<div>User Logged</div>";

					if (in_array($duser_id, $profile_visitors[$logged_id])) {
						$makecount=false;
					}else{
						array_push($profile_visitors[$logged_id],$duser_id);
						set_transient('profile_visitors', $profile_visitors, 12 * 60 * 60);
						// print_r($profile_visitors[$logged_id]);
						$makecount=true;
					}
				}else{
					$profile_visitors[$logged_id]=array($duser_id);
					set_transient('profile_visitors', $profile_visitors, 12 * 60 * 60);

					$makecount=true;
				}

			} else {
					//if transient set
					$makecount=true;
					set_transient('profile_visitors', array($logged_id=>[$duser_id]), 12 * 60 * 60);
			}


	}

	$sql="select id from $table_name where userid=$duser_id and viewerid=$logged_id";
	$view_count = $wpdb->get_var($sql);
	//  $view_count=$view_count?$view_count:0;
	// echo "Makecount: $makecount - $view_count";
	if ($makecount) {
			if (!$view_count) {
					$sqli="insert into $table_name values(NULL, $duser_id, $logged_id, '$vdate', 1)";
					$wpdb->query($sqli);
			} else {
				  $sqlu="update $table_name set  vdate='$vdate', vviews=vviews+1 where id=$view_count";
					$wpdb->query($sqlu);
			}
	}
	}
}
add_action( 'bp_members_screen_display_profile', 'bp_profile_visitors_init' );

add_action("bp_after_member_header", "bp_profile_visitors_count");


function bp_profile_visitors_count()
{
    global $bp, $wpdb;
    $user_id = bp_displayed_user_id();
    $logged_id = bp_loggedin_user_id();
    $table_name = $wpdb->prefix . "bp_profile_visitors";
    //display stats here
    $sqlw="select sum(vviews) from $table_name where userid=$user_id";
    $totalviews = $wpdb->get_var($sqlw);
    $totalviews =$totalviews?$totalviews:0;
    echo "<div style=\"clear:both\"><strong>Total Profile Views:</strong>
			<span style=\"color:#339\"><strong>$totalviews</strong></span></div>";


    $sqlv="select viewerid, vviews from $table_name where userid=$user_id order by vdate desc limit 5";
    $visitors = $wpdb->get_results($sqlv);
    if ($visitors) {
        //
        echo "<div style=\"clear:both\"><strong>Recent Profile Visitors:</strong></div>";
        echo 	"<div class=\"bp_dhrusya_visitors\">
			<dl>
				<dt>";
        foreach ($visitors as $v) {
            $usr=get_userdata($v->viewerid);
            $bplink=bp_core_get_user_domain($v->viewerid);
            echo "<a href=\"$bplink\" title=\"$usr->display_name\">".get_avatar($v->viewerid, 32)."</a>";
        }
        echo "<span class=\"icon-caret-down\"></span>";
        echo "<dt>";
        echo "<dd>";
        echo "<p><strong>Top Visitors</strong></p>";
        // $sql="select viewerid, vviews from $table_name where userid=$user_id order by vviews desc limit 5";
        // $visitors = $wpdb->get_results($sql);
        //print_r($visitors);
        foreach ($visitors as $v) {
            $usr=get_userdata($v->viewerid);
            $bplink=bp_core_get_user_domain($v->viewerid);
            echo "<div><a href=\"$bplink\" title=\"$usr->display_name\">".get_avatar($v->viewerid, 32)." $usr->display_name</a> ($v->vviews)</div>";
        }
        echo "</dd>";
        echo "</dl></div>";
    } else {
        echo "<div style=\"clear:both\"><strong>Recent Profile Visitors:</strong> None</div>";
    }
}

function bp_profile_visitors_install() {
   global $wpdb;

   $table_name = $wpdb->prefix . "bp_profile_visitors";
	$sql="CREATE TABLE IF NOT EXISTS $table_name (
  	`id` int(10) NOT NULL AUTO_INCREMENT,
  	`userid` int(10) NOT NULL,
	`viewerid` int(10) NOT NULL,
  	`vdate` datetime NOT NULL,
	`vviews` int(10) NOT NULL DEFAULT '0',
  	PRIMARY KEY (`id`)
	) ;";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
   dbDelta($sql);
}


register_activation_hook(__FILE__,'bp_profile_visitors_install');


if ( function_exists('register_uninstall_hook') )
	register_uninstall_hook(__FILE__, 'bp_profile_visitors_uninstall');

function bp_profile_visitors_uninstall() {
   global $wpdb;
	$table_name = $wpdb->prefix . "bp_profile_visitors";

	$sql="DROP TABLE $table_name;";
   	$wpdb->query($sql);
}


function load_bp_profile_visitors_css_scripts() {
		 wp_register_style('bp_profile_visitors-css', plugins_url( 'includes/style.css' , __FILE__ ));
		 wp_enqueue_style('bp_profile_visitors-css');
}
add_action('init', 'load_bp_profile_visitors_css_scripts');
