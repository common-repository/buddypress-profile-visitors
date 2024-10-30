<?php

add_action("bp_after_member_header", "bp_profile_visitors_count");


function bp_profile_visitors_count()
{
    global $bp, $wpdb;
    $user_id = bp_displayed_user_id();
    $logged_id = bp_loggedin_user_id();
    $table_name = $wpdb->prefix . "bp_profile_visitors";
    $makecount=false;
    $vdate=date("Y-m-d h:i:s");
    echo  "<div>".bp_displayed_user_id()." - ". bp_loggedin_user_id()."</div>";

    if ($user_id!=0  and $user_id!= $logged_id and $logged_id!=0) {
        $profile_visitors = get_transient('profile_visitors');
        if ($profile_visitors){
					if(isset($profile_visitors[$logged_id])) {
            // this code runs when there is no valid transient set
						if (in_array($user_id, $profile_visitors[$logged_id])) {
							$makecount=false;
						}else{
							array_push($profile_visitors[$logged_id],$user_id);
							$makecount=true;
						}
					}else{
						array_push($profile_visitors[$logged_id],$user_id);
						$makecount=true;
					}

        } else {
            //if transient set
            $makecount=true;
            set_transient('profile_visitors', array($viewer_id=>[$user_id]), 12 * 60 * 60);
        }

        $sql="select count(id) from $table_name where userid=$user_id and viewerid=$logged_id";
        $view_count = $wpdb->get_var($sql);
        $view_count=$view_count?$view_count:0;

				echo "Makecount: $makecount - $view_count";
        if ($makecount) {
            if ($view_count<1) {
                $view_count=1;
                $sqli="insert into $table_name values(NULL, $user_id, $logged_id, '$vdate', 1)";
                $wpdb->query($sqli);
            } else {
                $view_count++;
              echo  $sqlu="update $table_name set  vdate='$vdate', vviews=vviews+1 where userid=$user_id and viewerid=$logged_id";
                $wpdb->query($sqlu);
            }
        }
    }

    //display stats here
    $sqlw="select sum(vviews) from $table_name where userid=$user_id";
    $totalviews = $wpdb->get_var($sqlw);
    $totalviews =$totalviews?$totalviews:0;
    echo "<div style=\"clear:both\"><strong>Total Profile Views:</strong>
			<span style=\"color:#339\"><strong>$totalviews</strong></span></div>";


    $sqlv="select viewerid from $table_name where userid=$user_id order by vdate desc limit 5";
    $visitors = $wpdb->get_col($sqlv);
    if ($visitors) {
        //
        echo "<div style=\"clear:both\"><strong>Recent Profile Visitors:</strong></div>";
        echo 	"<div class=\"bp_dhrusya_visitors\">
			<dl>
				<dt>";
        foreach ($visitors as $v) {
            $usr=get_userdata($v);
            $bplink=bp_core_get_user_domain($v);
            echo "<a href=\"$bplink\" title=\"$usr->display_name\">".get_avatar($v, 32)."</a>";
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
