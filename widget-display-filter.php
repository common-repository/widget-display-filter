<?php
/*
  Plugin Name: Widget Display Filter
  Description: Set the display condition for each widget. Appearance -> Widget Display Filter menu added to the management page.
  Version: 2.0.0
  Plugin URI: https://celtislab.net/en/wp_widget_display_filter
  Author: enomoto@celtislab
  Author URI: https://celtislab.net/
  Requires at least: 5.9
  Tested up to: 5.9
  Requires PHP: 7.3
  License: GPLv2
  Text Domain: wdfilter
  Domain Path: /languages
*/
defined( 'ABSPATH' ) || exit;

add_action( 'init', function() { new Widget_display_filter(); }, 0);

/***************************************************************************
 * plugin uninstall
 **************************************************************************/
if(is_admin()){ 
    function widget_display_filter_uninstall() {
        if ( !is_multisite()) {
            delete_option('widget_display_filter' );
        } else {
            global $wpdb;
            $current_blog_id = get_current_blog_id();
            $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
            foreach ( $blog_ids as $blog_id ) {
                switch_to_blog( $blog_id );
                delete_option('widget_display_filter' );
            }
            switch_to_blog( $current_blog_id );
        }        
    }
    register_uninstall_hook(__FILE__, 'widget_display_filter_uninstall');
}

class Widget_display_filter {    
    static $filter = array();

    public function __construct() {
        self::$filter = get_option('widget_display_filter', array());

        add_action('widgets_init', array($this, 'widget_unregist'), 99 );
        add_filter('widget_display_callback', array($this, 'widget_instance_filter'), 10, 3);

        if(is_admin()){
            require_once ( __DIR__ . '/widget-display-setting.php' );            
            new Widget_display_setting( self::$filter );  
        }
    }

    public function widget_unregist() {
        global $wp_widget_factory;
        if (!empty(self::$filter['register']) ) {
            if(isset($_SERVER['REQUEST_URI']) && stripos($_SERVER['REQUEST_URI'], 'widget_display_filter_manage_page' ) !== false ){
                //exclude page
            } else {
                //hidden widget (There be excluded temporarily from the widget list by disabling)
                foreach( self::$filter['register'] as $unreg_class => $unreg ) {
                    foreach ( $wp_widget_factory->widgets as $widget_class => $widget ) {
                        if ( $widget_class == $unreg_class ) {
                            unregister_widget($widget_class);
                            break;
                        }
                    }
                }
            }
        }
    }
    
    static function is_rendering($hashtag){
        global $wp_query;
        $df = true;
        $filter = empty(self::$filter['display'][$hashtag])? false : self::$filter['display'][$hashtag];
        if(!empty($filter) && $filter['type']==='display'){
            $df = false;
            $is_mobile = wp_is_mobile();
            $is_mobile = apply_filters( 'custom_is_mobile' , $is_mobile );

            if($is_mobile){ //device check
                if(!empty($filter['mobile']))
                    $df = true;
            } else {
                if(!empty($filter['desktop']))
                    $df = true;
            }
            if($df) {           //Post Type check
                $df = false;
                if(is_home() || is_front_page()){
                    if(!empty($filter['home']))
                        $df = true;
                } elseif(is_archive()){
                    if(!empty($filter['archive']))
                        $df = true;
                } elseif(is_search()){
                    if(!empty($filter['search']))
                        $df = true;
                } elseif(is_attachment()){
                    if(!empty($filter['attach']))
                        $df = true;
                    if(!empty($filter['postid']) && $filter['in_postid']==='include' && is_attachment($filter['postid']))
                        $df = true;
                    if($df){
                        if(!empty($filter['postid']) && $filter['in_postid']==='exclude' && is_attachment($filter['postid']))
                            $df = false;
                    }
                } elseif(is_page()){
                    if(!empty($filter['page']))
                        $df = true;
                    if(!empty($filter['postid']) && $filter['in_postid']==='include' && is_page($filter['postid']))
                        $df = true;
                    if($df){
                        if(!empty($filter['postid']) && $filter['in_postid']==='exclude' && is_page($filter['postid']))
                            $df = false;
                    }
                } elseif(is_single()){ //Post & Custom Post
                    $type = get_post_type( $wp_query->post);
                    if($type === 'post'){
                        $fmt = get_post_format();
                        if(!empty($filter['post']) && ($fmt == 'standard' || $fmt == false))
                            $df = true;
                        if(!empty($filter['post-image']) && $fmt == 'image')
                            $df = true;
                        if(!empty($filter['post-gallery']) && $fmt == 'gallery')
                            $df = true;
                        if(!empty($filter['post-video']) && $fmt == 'video')
                            $df = true;
                        if(!empty($filter['post-audio']) && $fmt == 'audio')
                            $df = true;
                        if(!empty($filter['post-aside']) && $fmt == 'aside')
                            $df = true;
                        if(!empty($filter['post-quote']) && $fmt == 'quote')
                            $df = true;
                        if(!empty($filter['post-link']) && $fmt == 'link')
                            $df = true;
                        if(!empty($filter['post-status']) && $fmt == 'status')
                            $df = true;
                        if(!empty($filter['post-chat']) && $fmt == 'chat')
                            $df = true;
                        if(!empty($filter['category']) && $filter['in_category']==='include' && in_category($filter['category']))
                            $df = true;
                        if(!empty($filter['post_tag']) && $filter['in_post_tag']==='include' && has_tag($filter['post_tag']))
                            $df = true;
                        if($df){
                            if(!empty($filter['category']) && $filter['in_category']==='exclude' && in_category($filter['category']))
                                $df = false;
                            if(!empty($filter['post_tag']) && $filter['in_post_tag']==='exclude' && has_tag($filter['post_tag']))
                                $df = false;
                        }
                    } else {
                        $post_types = get_post_types( array('public' => true, '_builtin' => false) );   
                        if(!empty($post_types)){
                            foreach ( $post_types as $cptype ) {
                                if(!empty($filter[$cptype]) && $type == $cptype){
                                    $df = true;
                                }
                            }
                        }
                    }
                    if(!empty($filter['postid']) && $filter['in_postid']==='include' && is_single($filter['postid']))
                        $df = true;
                    if($df){
                        if(!empty($filter['postid']) && $filter['in_postid']==='exclude' && is_single($filter['postid']))
                            $df = false;
                    }
                }
            }
        }
        return $df;
    }
    
    //To determine the display condition When the hash tag of title part matches
    public function widget_instance_filter( $instance, $widget, $args ) {
        $hashtag = '';
        $blocktype = '';
        if(!empty($instance['title'])&& preg_match('/(\A|\s)#([a-zA-Z0-9_\-]+)(\Z|\s)/u', $instance['title'], $match)){
            $hashtag = $match[2];
            $instance['title'] = trim( preg_replace('/(\A|\s)#([a-zA-Z0-9_\-]+)(\Z|\s)/u', '', $instance['title']));
        } elseif(!empty($instance['content']) && preg_match('/(<!\-\-\s(wp:[^\s]+?)\s(.+?)\-\->)/su', $instance['content'], $match)) {
            $blocktype = $match[2];
            if($blocktype === 'wp:widget-group'){
                $supportblock = true;
            } else {
                //support block filter for anothe block type
                $supportblock = apply_filters('wdfilter_support_blocktype', false, $blocktype);
            }
            if($supportblock && preg_match('/"title":"[^"]*?#([a-zA-Z0-9_\-]+)[^"]*?"/su', $match[3], $title)){
                $hashtag = $title[1];
                $instance['content'] = preg_replace("/\s?#$hashtag\s?/su", '', $instance['content']);
            }
        }
        if(!empty($hashtag)){
            $lastinstance = $instance;
            if( self::is_rendering($hashtag) === false) {
                $instance = false;
            }
            //widget instance filter for custom logic 
            $instance = apply_filters('wdfilter_widget_instance', $instance, $lastinstance, $hashtag, $blocktype);
        }
        return $instance;
    }        
}
