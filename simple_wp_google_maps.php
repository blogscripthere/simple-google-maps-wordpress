<?php
/**
 * @package Share_Link_On_Social_Media
 * @version 1.0
 */
/*
Plugin Name: ScriptHere's Simple Google Maps with Rich Snippets.
Plugin URI: https://github.com/blogscripthere/simple-google-maps-wordpress
Description: Simple Google Maps to WordPress posts, pages and text widgets.
Author: Narendra Padala
Author URI: https://in.linkedin.com/in/narendrapadala
Text Domain: shgm
Version: 1.0
Last Updated: 03/03/2018
*/

/**
 * author site url if any queries ?
 */
echo "<span id='shgm' style='display: none;'><a href='http://scripthere.com/'>ScriptHere's Simple Google Maps with Rich Snippets.</a></span>";
/**
 * define google maps api key
 */
define('GOOGLE_MAPS_API_KEY','AIzaSyDxAuM_Z9ZPwM-OctCelnOhTwU0w50fEXA');

/**
 * Enqueue required javascript libraries to implement google maps integration callback
 */
function sh_maps_load_enqueue_scripts(){

    //check if jquery not loaded already your plugins /theme load it due to google maps libraries are jQuery dependents
    if(!wp_script_is('jquery')) {
        wp_enqueue_script('jquery', '//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js', array(), '1.0.0', true);
    }
    //load maps
    wp_enqueue_script('maps-jquery', '//maps.google.com/maps/api/js?sensor=false&key='.GOOGLE_MAPS_API_KEY, array('jquery'), '1.0.0', false);
}

/**
 * Enqueue required javascript libraries to implement google maps integration hook
 */
add_action('wp_enqueue_scripts', 'sh_maps_load_enqueue_scripts');
add_action( 'admin_enqueue_scripts', 'sh_maps_load_enqueue_scripts' );

/**
 * Adding google maps option meta box at the post or page edit screen hooks.
 */
add_action( 'add_meta_boxes' ,          'sh_register_google_maps_meta_box');
/**
 * Register google maps option meta box at the post or page edit screen
 */
function sh_register_google_maps_meta_box() {
    //search and generate short code with latitude & longitude
    add_meta_box('google-maps-meta-box-id', __('Google Maps', 'shgm'), 'sh_google_maps_display_callback', array('post', 'page'));
}


/**
 * Google maps option meta box at the post or page edit screen callback
 */
function sh_google_maps_display_callback(){
    //search form
    echo '<form class="class_maps" action="" name="mapsFrm" id="mapsFrm">
      <input type="text" placeholder="Search.." name="mapsSearch" id="mapsSearch"> 
      <button type="button" name="loadMap" id="loadMap">Search</button>
    </form>';

    //load default map
    echo "<div id='map_container'>".sh_display_google_map()."</div>";

    //ajax url
    $ajax_url = admin_url('admin-ajax.php');

    //add ajax script
    echo "<script type='text/javascript'>        
    /* Ajax functions */
    jQuery(document).ready(function() {
        /* process onclick search button */
        jQuery('#loadMap').on('click', function(e) {
            /* get search term*/
            var search = jQuery('#mapsSearch').val();
            /* int ajax url*/
            var ajaxurl ='{$ajax_url}';
            /* ajax call*/
            jQuery.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    search: search,
                    action: 'load_google_map'    
                },
                error: function(response) {
                    console.log(response);
                },
                success: function(response) {
                    jQuery('#map_container').html(response);
                    //console.log(response);
                }
            });
        });
    });        
    </script>";
}

/*
 * search load google maps ajax hooks
 */
add_action('wp_ajax_nopriv_load_google_map', 'load_custom_google_map');
add_action('wp_ajax_load_google_map', 'load_custom_google_map');

/*
 * search load google maps ajax callback
 */
function load_custom_google_map(){
    //get
    $search = $_REQUEST['search'];
    //sanitize search value
    $address =  sanitize_text_field($search);
    //get latitude and longitude for search location
    $response = sh_get_latitude_and_longitude_google_map_callback($address);
    //check
    if($response['status']=='success'){
        //set unique map id
        $map_id = sh_genarate_unique_map_id();
        //get latitude
        $latitude = $response['latitude'];
        //get longitude
        $longitude = $response['longitude'];
        //display message
        echo "<span style='color: red;'><i>Note: Use following short code to genarate map on post,page and text widget..!</i></span><br/>";
        echo '<span style="color:crimson;"><i><b>Short Code: [display_map lat="'.$latitude.'" lng="'.$longitude.'" map_id="'.$map_id.'" width="100%" height="50%" zoom="8"]</b></i></span><br/>';
        //display desired map
        echo sh_display_google_map(array('lat'=>$latitude,'lng'=>$longitude)); exit;
    }else{
        //display default map with error messge
        echo "<span style='color: red;'>Address not found</span><br/>".sh_display_google_map(); exit;
    }
}

/**
 * Get google maps latitude and longitude search location callback
 * @param string $address
 * @return array .
 */
function sh_get_latitude_and_longitude_google_map_callback($address=''){
    //check
    if(!empty($address)) {
        //get geocode from maps api
        $geo_code = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&sensor=true_or_false&key='.GOOGLE_MAPS_API_KEY);
        $response = json_decode($geo_code);
        //get latitude and longitude
        $latitude  = (isset($response->results[0]->geometry->location->lat))? $response->results[0]->geometry->location->lat : false;
        $longitude = (isset($response->results[0]->geometry->location->lng))? $response->results[0]->geometry->location->lng : false;
        //check
        if($latitude && $longitude) {
            //return
            return array('status'=>'success','latitude'=>$latitude,'longitude'=>$longitude);
        }else{
            //return
            return array('status'=>'error','msg'=>'Location not found ..!');
        }
    }else{
        //return
        return array('status'=>'error','msg'=>'Location details should not be empty ..!');
    }
}

/**
 * Genarate google maps -display_map short code callback
 * @param array $args map map_id,width,height,lat,lng,zoom
 * @return html google map.
 */
function sh_display_google_map($args = array()){

    //init unique map id
    $map_id= (@$args['map_id'])? $args['map_id'] :"map";
    //set width
    $width = (@$args['width'])? $args['width'] :"100%";
    //set height
    $height = (@$args['height'])? $args['height'] :"50%";
    //set latitude
    $latitude = (@$args['lat'])? $args['lat'] :"12.9715987";
    //set longitude
    $longitude = (@$args['lng'])? $args['lng'] :"77.5945627";
    //map zoom
    $zoom = (@$args['zoom'])? $args['zoom'] :6;

    //init
    $html = "";
    //set styles
    $html .= "<style>#{$map_id}{width: {$width};height: {$height};</style>";
    //display container
    $html .= "<div id='{$map_id}'></div>";

    /* init display map*/
    $html .= "<script type='text/javascript'>
        /* create unique method for each map*/
        function {$map_id}_display_map() {
            /* set positions*/
            var position = {lat: {$latitude}, lng: {$longitude}};
            /* set map */
            var display_map = new google.maps.Map(document.getElementById('{$map_id}'), {
                zoom: {$zoom},
                center: position
            });
            /* position marker*/
            var marker = new google.maps.Marker({
                position: position,
                map: display_map
            });
        }
        /* unique method callback*/
        {$map_id}_display_map();
    </script>";
    //return
    return  $html;
}

/**
 * Genarate google maps -display_map short code hook
 * @usage : [display_map lat="12.9715987" lng="77.5945627" map_id="map" width="100%" height="50%" zoom="9"]
 * @Note : if you want use multple maps on same page set "map_id" and it should be unique.
 */
add_shortcode('display_map','sh_display_google_map');

/**
 * Generate unique map id to use different google maps on same page.
 * @return string  $unique_map_id
 */
function sh_genarate_unique_map_id(){
    //init
    $input_str = str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
    //shuffle string
    shuffle($input_str);
    //init default
    $unique_map_id = 'map';
    //loop
    foreach (array_rand($input_str, 6) as $k) {
        //Generate unique string
        $unique_map_id .= $input_str[$k];
    }
    //return
    return $unique_map_id;
}
