<?php
/*
  File Name: widget-display-setting.php
  Description: widget display filter plugin Settings
  Author: enomoto@celtislab
*/
defined( 'ABSPATH' ) || exit;

class Widget_display_setting {
    static $filter = array();

    public function __construct($filter) {
        self::$filter = $filter;        
        load_plugin_textdomain('wdfilter', false, basename( dirname( __FILE__ ) ).'/languages' );

        add_action('admin_init', array($this, 'action_posts')); 
        add_action('admin_menu', array($this, 'my_option_menu')); 
        add_action('admin_print_styles-appearance_page_widget_display_filter_manage_page', array($this, 'admin_styles') );
        add_action('admin_print_scripts-appearance_page_widget_display_filter_manage_page', array($this, 'admin_scripts') );
        add_action('wp_ajax_Widget_filter_postid',    array($this, 'widget_filter_postid'));
        add_action('wp_ajax_Widget_filter_category',  array($this, 'widget_filter_category'));
        add_action('wp_ajax_Widget_filter_post_tag',  array($this, 'widget_filter_post_tag'));           
    }
    
    //Notice Message display
    public function widget_filter_notice() {
        $notice = get_transient('widget_display_filter_notice');
        if(!empty($notice)){
            echo "<div class='message error'><p>Widget Load Filter : $notice</p></div>";
            delete_transient('widget_display_filter_notice');
        }        
    }

    //Add Appearance submenu : Widget Load Filter
    public function my_option_menu() {
        add_theme_page( 'Widget Display Filter', __('Widget Display Filter', 'wdfilter'), 'manage_options', 'widget_display_filter_manage_page', array($this,'widget_display_filter_option_page'));
    }

    //widget-display-filter admin page Notice message & css file 
    function admin_styles() {
        add_action( 'admin_notices', array($this, 'widget_filter_notice'));
        wp_enqueue_style("wp-jquery-ui-dialog");
        $base_dir = dirname( __FILE__ , 3);
        $base_name = basename( $base_dir );   // maybe wp-content
        $path = __DIR__ . '/widget-display-filter.css';
        wp_enqueue_style( 
            'widget-display-filter-css',
            content_url() . str_replace('\\' ,'/', substr( $path, stripos($path, $base_name) + strlen($base_name))),
            array(),
            filemtime( $path )
        );                
    }

    //widget-display-filter admin page js file
    function admin_scripts() {
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-widget' );
        wp_enqueue_script( 'jquery-ui-tabs' );
        wp_enqueue_script( 'jquery-ui-dialog' );        

        $base_dir = dirname( __FILE__ , 3);
        $base_name = basename( $base_dir );   // maybe wp-content
        $path = __DIR__ . '/widget-display-filter.js';
        wp_enqueue_script( 
            'widget-display-filter',
            content_url() . str_replace('\\' ,'/', substr( $path, stripos($path, $base_name) + strlen($base_name))),
            array('jquery', 'jquery-ui-tabs'),
            filemtime( $path )
        );                
    }
    
    //Ajax : wp_ajax_Widget_filter_postid
    public function widget_filter_postid() {
        check_ajax_referer( 'widget_display_filter' );
        $filter = get_option('widget_display_filter');
        if(isset($_POST['hashtag'])){
            $tag = trim(stripslashes($_POST['hashtag']));
            //The temporary update Post ID filter settings Once tags exist
            if(!empty($tag) && isset($filter['display'][$tag]) && isset($_POST['in_postid'])){
                $option = $filter['display'][$tag];
                $option["in_postid"] = $_POST['in_postid'];
                $arpid = array();
                if(isset($_POST['postid'])){
                    $arpid = array_map('trim', explode(',', $_POST['postid']));
                }
                $erflg = false;
                $option["postid"] = array();
                foreach ($arpid as $pid) {
                    if($pid === ""){
                        $option["postid"][] = "";
                        break;
                    } elseif(ctype_digit($pid)){
                        $pd = get_post($pid);
                        if(!empty($pd)){
                            $option["postid"][] = $pid;
                        } else {
                            $erflg = true;
                        }
                    } else { 
                        $erflg = true;
                    }
                }
                if($erflg){
                    $notice = __('It contains invalid Post ID.','wdfilter');
                    wp_send_json_error($notice);
                } else {
                    $lists = '';
                    $inpval = self::filter_stat_list($tag, 'postid', $option['in_postid'], $option["postid"], $lists);
                    $response = array();
                    $response['success'] = true;
                    $response['data']    = $inpval;
                    $response['lists']   = $lists;
                    wp_send_json( $response );
                }
            }
            exit;    
        }
    }

    //Ajax : wp_ajax_Widget_filter_category
    public function widget_filter_category() {
        check_ajax_referer( 'widget_display_filter' );
        $filter = get_option('widget_display_filter');
        if(isset($_POST['hashtag'])){
            $tag = trim(stripslashes($_POST['hashtag']));
            //The temporary update categorys filter settings Once tags exist
            if(!empty($tag) && isset($filter['display'][$tag]) && isset($_POST['in_category'])){
                $option = $filter['display'][$tag];
                $option["in_category"] = $_POST['in_category'];
                $option["category"] = array();
                if(isset($_POST['category'])){
                    $option["category"] = explode(",", $_POST['category']);
                }
        		$categories = (array) get_terms('category', array('get' => 'all'));
                $categoryname = array();
                foreach( $categories as $k=>$v ) {
                    if (!empty($option['category']) && in_array( $categories[$k]->term_id, $option['category']) ) {
                        $categoryname[] = $categories[$k]->name;
                    }
                }
                $lists = '';
                $inpval = self::filter_stat_list($tag, 'category', $option['in_category'], $categoryname, $lists);
                $response = array();
                $response['success'] = true;
                $response['data']    = $inpval;
                $response['lists']   = $lists;
                wp_send_json( $response );
            }
            exit;    
        }
    }

    //Ajax : wp_ajax_Widget_filter_post_yag
    public function widget_filter_post_tag() {
        check_ajax_referer( 'widget_display_filter' );
        $filter = get_option('widget_display_filter');
        if(isset($_POST['hashtag'])){
            $tag = trim(stripslashes($_POST['hashtag']));
            //The temporary update categorys filter settings Once tags exist
            if(!empty($tag) && isset($filter['display'][$tag]) && isset($_POST['in_post_tag'])){
                $option = $filter['display'][$tag];
                $option["in_post_tag"] = $_POST['in_post_tag'];
                $option["post_tag"] = array();
                if(isset($_POST['post_tag'])){
                    $option["post_tag"] = explode(",", $_POST['post_tag']);
                }
        		$post_tags = (array) get_terms('post_tag', array('get' => 'all'));
                $tagnames = array();
                foreach( $post_tags as $k=>$v ) {
                    if (!empty($option['post_tag']) && in_array( $post_tags[$k]->term_id, $option['post_tag']) ) {
                        $tagnames[] = $post_tags[$k]->name;
                    }
                }
                $lists = '';
                $inpval = self::filter_stat_list($tag, 'post_tag', $option['in_post_tag'], $tagnames, $lists);
                $response = array();
                $response['success'] = true;
                $response['data']    = $inpval;
                $response['lists']   = $lists;
                wp_send_json( $response );
            }
            exit;    
        }
    }
    
    static function tab_select() {
        $edittab = '0';
        $tab = get_transient('widget_display_filter_tab');
        if(!empty($tab)){
            $edittab = '1';
            delete_transient('widget_display_filter_tab');
        }
        return $edittab;
    }
    
    //widget conditions data (add, update,delete)
    public function action_posts() {
        if (current_user_can( 'activate_plugins' )) {
            if( isset($_POST['entry_display_filter']) || isset($_POST['save_display_filter']) ){
                check_admin_referer( 'widget_display_filter' );
                $checkbox = array('desktop','mobile','home','archive','search','attach','page','post','post-image','post-gallery','post-video','post-audio','post-aside','post-quote','post-link','post-status','post-chat');
                $post_types = get_post_types( array('public' => true, '_builtin' => false) );                    
                foreach ( $post_types as $cptype ) {
                    $checkbox[] = $cptype;
                }
                if( isset($_POST['entry_display_filter']) ) {   
                    //display filter hashtag add
                    if(is_string($_POST['widget_display_filter']['hashtag'])){
                        $tag = '';
                        if(preg_match('/\A#?([a-zA-Z0-9_\-]+)(\Z|\s)/u', $_POST['widget_display_filter']['hashtag'], $match)){
                            $tag = $match[1];;
                            if(empty(self::$filter['display'][$tag])){
                                $option = array();
                                $option['type'] = 'display';
                                $option['hashtag'] = $tag;
                                foreach ( $checkbox as $type ) {
                                    $option[$type] = true;
                                }
                                $option["in_postid"] = 'include';
                                $option["postid"] = '';
                                $option["in_category"] = 'include';
                                $option["category"] = '';
                                $option["in_post_tag"] = 'include';
                                $option["post_tag"] = '';
                                self::$filter['display'][$tag] = $option;
                                update_option('widget_display_filter', self::$filter );
                            }
                        }
                        if(empty($tag)){
                            $notice = __('There are invalid characters in Hashtag. Use characters include Alphanumeric, Hyphens, Underscores.','wdfilter');
                            set_transient('widget_display_filter_notice', $notice, 30);
                        }
                    }                    
                } elseif( isset($_POST['save_display_filter']) ) {   
                    //Save display options
                    $categories = (array) get_terms('category', array('get' => 'all'));
                    $post_tags = (array) get_terms('post_tag', array('get' => 'all'));
                    foreach($_POST['widget_display_filter'] as $tag=>$opt) { 
                        if(!empty(self::$filter['display'][$tag])){
                            $option = array();
                            $option['type'] = 'display';
                            $option['hashtag'] = $tag;
                            foreach ( $checkbox as $type ) {
                                $option[$type] = (! isset($_POST['widget_display_filter'][$tag][$type])) ? false : (bool) $_POST['widget_display_filter'][$tag][$type];
                            }
                            
                            $option["in_postid"] = (! isset($_POST['widget_display_filter'][$tag]['in_postid'])) ? 'include' : $_POST['widget_display_filter'][$tag]['in_postid'];
                            $ids = array();
                            if(!empty($_POST['widget_display_filter'][$tag]['postid'])){
                                $ids = array_map('trim', explode(',', $_POST['widget_display_filter'][$tag]['postid']));
                            }
                            $option["postid"] = $ids;

                            $option["in_category"] = (! isset($_POST['widget_display_filter'][$tag]['in_category'])) ? 'include' : $_POST['widget_display_filter'][$tag]['in_category'];
                            $cat = array();
                            if(!empty($_POST['widget_display_filter'][$tag]['category'])){
                                $catname = array_map('trim', explode(',', $_POST['widget_display_filter'][$tag]['category']));
                                foreach( $categories as $cv ) {
                                    if (in_array( $cv->name, $catname) ) {
                                        $cat[] = $cv->term_id;
                                    }
                                }
                            }
                            $option["category"] = $cat;

                            $option["in_post_tag"] = (! isset($_POST['widget_display_filter'][$tag]['in_post_tag'])) ? 'include' : $_POST['widget_display_filter'][$tag]['in_post_tag'];
                            $ptag = array();
                            if(!empty($_POST['widget_display_filter'][$tag]['post_tag'])){
                                $tagname = array_map('trim', explode(',', $_POST['widget_display_filter'][$tag]['post_tag']));
                                foreach( $post_tags as $tv ) {
                                    if (in_array( $tv->name, $tagname) ) {
                                        $ptag[] = $tv->term_id;
                                    }
                                }
                            }
                            $option["post_tag"] = $ptag;
                            self::$filter['display'][$tag] = $option;
                        }                        
                    }
                    //post format type exclude option
                    if(isset($_POST['pformat'])){
                        $exclude = array();
                        foreach ( $_POST['pformat'] as $ft => $v ) {
                            if(!empty($v))
                                $exclude[$ft] = true;
                        }
                        self::$filter['pformat'] = $exclude;
                    } else {
                        self::$filter['pformat'] = array();
                    }
                    update_option('widget_display_filter', self::$filter );                    
                }
                wp_safe_redirect(admin_url('themes.php?page=widget_display_filter_manage_page'));
                exit;                
            } elseif( isset($_POST['entry_register_filter']) ) {   
                //hidden widget
                check_admin_referer( 'widget_display_filter' );
                if(isset($_POST['widget_display_filter']['class'])){
                    $widget = stripslashes($_POST['widget_display_filter']['class']);
                    $option['type'] = 'register';
                    $option['class'] = $widget;
                    self::$filter['register'][$widget] = $option;
                    update_option('widget_display_filter', self::$filter );
                }
                set_transient('widget_display_filter_tab', '1', 60);
                wp_safe_redirect(admin_url('themes.php?page=widget_display_filter_manage_page'));
                exit;                
            } elseif (!empty($_GET['page']) && $_GET['page'] === 'widget_display_filter_manage_page' && !empty($_GET['action'])) {
                if( $_GET['action']=='del_widget_display') {
                    check_admin_referer( 'widget_display_filter' );
                    if( !empty( $_GET['hashtag']) && isset(self::$filter['display'][$_GET['hashtag']])){
                        unset(self::$filter['display'][$_GET['hashtag']]);
                        update_option('widget_display_filter', self::$filter );
                    }
                    wp_safe_redirect(admin_url('themes.php?page=widget_display_filter_manage_page'));
                    exit;                    
                } elseif( $_GET['action']=='del_widget_register') {
                    check_admin_referer( 'widget_display_filter' );
                    if( !empty( $_GET['class']) && isset(self::$filter['register'][$_GET['class']])){
                        unset(self::$filter['register'][$_GET['class']]);
                        update_option('widget_display_filter', self::$filter );
                    }
                    set_transient('widget_display_filter_tab', '1', 60);
                    wp_safe_redirect(admin_url('themes.php?page=widget_display_filter_manage_page'));
                    exit;                    
                }
            }
        }
    }
	static function checkbox($name, $value, $label = '') {
        return "<label><input type='checkbox' name='$name' value='1' " . checked( $value, 1, false ).  "/> $label</label>";
	}
    static function altcheckbox($name, $value, $label = '') {
	    return "<label><input type='checkbox' name='$name' class='altcheckbox' value='1' " . checked( $value, 1, false ).  "/> $label</label>";
    }    
    
    static function filter_stat_list( $hashtag, $item, $sw, $arstat, &$lists) {
        if(is_array($arstat)){
            $stat = implode(",", $arstat);
        } else{
            $arstat = array();
            $stat = '';
        }
        $hid1 = "<input type='hidden' name='widget_display_filter[$hashtag][in_$item]' value='$sw' />";
        $hid2 = "<input type='hidden' name='widget_display_filter[$hashtag][$item]' value='$stat' />";
        $str = $hid1 . $hid2 . '<span class="widget_display_filter dashicons dashicons-plus"></span>';
        //item list dom 
        if(empty($sw) || $sw === 'exclude'){
            $lists = '<ul class="color-exclude" style="margin:0;">';
        } else {
            $lists = '<ul class="color-include" style="margin:0;">';
        }
        if($item === 'postid'){
            foreach ($arstat as $id) {
                if(!empty($id)){
                    $url = get_permalink( $id );
                    if(!empty($url)){
                        $lists .= "<li class='postid-list'><a target='_blank' rel='noopener' href='$url'>$id</a></li>";
                    } else {
                        $lists .= "<li class='postid-list'>$id</li>";
                    }
                }
            }
        } else {
            foreach ($arstat as $val) {
                if(!empty($val)){
                    $lists .= "<li class='taxonomy-list'>$val</li>";
                }
            }
        }
        $lists .= '</ul>';
        return $str;
    }  
    
    static function filter_checkmark($tag, $type, $opt, $class='wrap_checkbox' ) {
        $name = "widget_display_filter[$tag][$type]";
        $checked = (empty($opt[$type]))? false : true;
        $str = "<td class='$class'>" . self::altcheckbox($name, $checked, '<span class="dashicons dashicons-yes"></span>') . '</td>';
        return $str;
    }   

    //Display filter table display
    public function display_filter_table( $default) {
        $post_types = get_post_types( array('public' => true, '_builtin' => false) );                    
		$categories = (array) get_terms('category', array('get' => 'all'));
        $post_tags   = (array) get_terms('post_tag', array('get' => 'all'));
        $ptype      = array('home','archive','search','attach','page','post');
        $pformat    = array('image', 'gallery', 'video', 'audio', 'aside', 'status', 'quote', 'link', 'chat' );
		$ajax_nonce = wp_create_nonce( 'widget_display_filter' );
    ?>
    <div id="wrap_wdfilter-activation-table">
        <table id="wdfilter-activation-table" class="widefat">
          <thead>
            <tr>
              <th class="hash-name"><?php _e('Hashtag', 'wdfilter'); ?></th>
              <th class="device-type"><span title="<?php _e('Desktop Device', 'wdfilter'); ?>" class="dashicons dashicons-desktop"></span><br /><span style="font-size:xx-small">Desktop</span></th>
              <th class="device-type"><span title="<?php _e('Mobile Device', 'wdfilter'); ?>" class="dashicons dashicons-smartphone"></span><br /><span style="font-size:xx-small">Mobile</span></th>
              <th class="pids m-size"><span title="<?php _e('Singular Post ID', 'wdfilter'); ?>" class="dashicons dashicons-id"></span><br /><span style="font-size:xx-small">Post ID</span></th>
              <th class="ckbox-type s-size"><span title="<?php _e('Home/Front-page', 'wdfilter'); ?>" class="dashicons dashicons-admin-home"></span><br /><span style="font-size:xx-small">Home</span></th>
              <th class="ckbox-type s-size"><span title="<?php _e('Archive page', 'wdfilter'); ?>" class="dashicons dashicons-list-view"></span><br /><span style="font-size:xx-small">Archive</span></th>
              <th class="ckbox-type s-size"><span title="<?php _e('Search page', 'wdfilter'); ?>" class="dashicons dashicons-search"></span><br /><span style="font-size:xx-small">Search</span></th>
              <th class="ckbox-type s-size"><span title="<?php _e('Attachment page', 'wdfilter'); ?>" class="dashicons dashicons-media-default"></span><br /><span style="font-size:xx-small">Attach</span></th>
              <th class="ckbox-type s-size"><span title="<?php _e('Static Page', 'wdfilter'); ?>" class="dashicons dashicons-admin-page"></span><br /><span style="font-size:xx-small">Page</span></th>
              <th class="pformat s-size"><span title="<?php _e('Post : Standard', 'wdfilter'); ?>" class="dashicons dashicons-admin-post"></span><br /><span style="font-size:xx-small">Post</span></th>
              <?php
                $exclude = array();
                if(!empty(self::$filter['pformat'])){
                    foreach ( self::$filter['pformat'] as $type => $v) {
                        if(!empty($v)){
                            $exclude[] = $type;
                        }
                    }
                }
                foreach ( $pformat as $type) {
                    if(!in_array($type, $exclude)){
                        $title = __('Post : ', 'wdfilter') . $type;
                        $icon  = ($type === "link")? "dashicons-admin-links" : "dashicons-format-$type";
                        echo '<th class="pformat s-size"><span title="' . $title . '" class="dashicons ' . $icon .'"></span><br /><span style="font-size:xx-small">' . $type .'</span></th>';
                    }
                }              
              ?>
              <th class="pgroup l-size"><span title="<?php _e('Post Category', 'wdfilter'); ?>" class="dashicons dashicons-category"></span><br /><span style="font-size:xx-small">Category</span></th>
              <th class="pgroup l-size"><span title="<?php _e('Post Tag', 'wdfilter'); ?>" class="dashicons dashicons-tag"></span><br /><span style="font-size:xx-small">Tag</span></th>
              <?php
                foreach ( $post_types as $cptype ) {
                    $title = __('Custom Post : ', 'wdfilter') . $cptype;
                    echo "<th class='tmpl-custom s-size'><span title='$title' style='font-size:xx-small'>$cptype</span></th>";
                }
              ?>
              <th colspan="1">&nbsp;</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if(!empty(self::$filter['display'])){
                ?>
                <script type="text/javascript">
                  var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
                  var widget_filter = new Object();
                </script>
                <?php                
                foreach( self::$filter['display'] as $id ) {
                    $opt = wp_parse_args( (array) $id,  $default);
                    $hashtag = $opt['hashtag'];
                    if(empty($hashtag)){
                        continue;
                    }
                    ?>
                    <script type="text/javascript">
                      widget_filter["<?php echo $hashtag; ?>"] = <?php echo json_encode( $opt ); ?>
                    </script>
                    <?php                
                    echo '<tr>';
                    echo '<td class="hash-name">#'.$hashtag.'</td>';                    
                    echo self::filter_checkmark($hashtag, 'desktop', $opt, 'device-type');
                    echo self::filter_checkmark($hashtag, 'mobile', $opt, 'device-type');

                    $lists = '';
                    $editpid = "<span id='widget-filter-edit-postid-$hashtag'>" . self::filter_stat_list($hashtag, 'postid', $opt['in_postid'], $opt['postid'], $lists) . '</span>';
                    echo '<td><div class="hide-if-no-js"><a href="#wpbody-content" onclick="WidgetFilterPostid(\'' . $ajax_nonce . '\',\'' . $hashtag . '\');return false;" >'."$editpid</a><div id='widget-filter-postid-$hashtag'>$lists</div></div></td>";

                    $chklist = $ptype;
                    foreach ( $pformat as $type) {
                        if(!in_array($type, $exclude)){
                            $chklist[] = "post-$type";
                        }
                    }
                    foreach($chklist as $type){
                        echo self::filter_checkmark($hashtag, $type, $opt);
                    }
                    
                    $categoryname = array();
                    foreach( $categories as $k=>$v ) {
                        if (!empty($opt['category']) && in_array( $categories[$k]->term_id, $opt['category']) ) {
                            $categoryname[] = $categories[$k]->name;
                        }
                    }
                    $lists = '';
                    $editcat = "<span id='widget-filter-edit-category-$hashtag'>" . self::filter_stat_list($hashtag, 'category', $opt['in_category'], $categoryname, $lists) . '</span>';
                    echo '<td><div class="hide-if-no-js"><a href="#wpbody-content" onclick="WidgetFilterCategory(\'' . $ajax_nonce . '\',\'' . $hashtag . '\');return false;" >'."$editcat</a><div id='widget-filter-category-$hashtag'>$lists</div></div></td>";
                    
                    $tagname = array();
                    foreach( $post_tags as $k=>$v ) {
                        if (!empty($opt['post_tag']) && in_array( $post_tags[$k]->term_id, $opt['post_tag']) ) {
                            $tagname[] = $post_tags[$k]->name;
                        }
                    }
                    $lists = '';
                    $edittag = "<span id='widget-filter-edit-post_tag-$hashtag'>" . self::filter_stat_list($hashtag, 'post_tag', $opt['in_post_tag'], $tagname, $lists) . '</span>';
                    echo '<td><div class="hide-if-no-js"><a href="#wpbody-content" onclick="WidgetFilterPosttag(\'' . $ajax_nonce . '\',\'' . $hashtag . '\');return false;" >'. "$edittag</a><div id='widget-filter-post_tag-$hashtag'>$lists</div></div></td>";

                    foreach ( $post_types as $cptype) {
                        if(isset($opt[ $cptype])){
                            echo self::filter_checkmark($hashtag, $cptype, $opt);
                        }
                    }
                    //Delete link
                    $url = wp_nonce_url( "themes.php?page=widget_display_filter_manage_page&amp;action=del_widget_display&amp;hashtag=$hashtag", "widget_display_filter" ); 
                    echo "<td><a class='delete' href='$url'>" . __( 'Delete', 'wdfilter' ) . "</a></td>";
                    echo "</tr>";
                }
            }
            ?>
          </tbody>
        </table>
    </div>
    <?PHP
    }
    
    //Unregist filter table display
    public function register_filter_table( $default) {
    ?>
    <table class="widefat">
    <thead>
      <tr>
        <th><?php _e('Legacy Widget', 'wdfilter'); ?></th>
        <th><?php _e('Description', 'wdfilter'); ?></th>
        <th>&nbsp;</th>
      </tr>
    </thead>
    <tbody>
    <?php
        if(!empty(self::$filter['register'])){
            global $wp_widget_factory;
            foreach( self::$filter['register'] as $id ) {
                $opt = wp_parse_args( (array) $id,  $default);
                $widget_name = '';
                foreach ( $wp_widget_factory->widgets as $widget_class => $widget ) {
                    if ( $widget_class == $opt['class'] ) {
                        $widget_name = $widget->name;
                        $widget_doc = $widget->widget_options['description'];
                        if(!empty($widget_doc)){
                            if(mb_strlen($widget_doc) > 90)
                                $widget_doc = mb_substr($widget_doc, 0, 90). "…"; 
                        }
                        break;
                    }
                }
                if(!empty($widget_name)){
                    echo '<tr id="load_filter_' .$opt['class']. '">';
                    echo '<td>'.$widget_name.'</td>';
                    echo '<td>'.$widget_doc.'</td>';
                    //Restore link
                    $url = wp_nonce_url( "themes.php?page=widget_display_filter_manage_page&amp;action=del_widget_register&amp;class={$opt['class']}", "widget_display_filter" ); 
                    echo "<td><a class='delete' href='$url'>" . __( 'Restore', 'wdfilter' ) . "</a></td>";
                    echo "</tr>";
                }
            }
        }
    ?>
    </tbody>
    </table>
    <?PHP
    }
        
    //Option setting screen
    public function widget_display_filter_option_page() {
        $default = array( 'type' => 'register', 'class' => false, 'hashtag' => '', 'desktop' => false, 'mobile' => false, 'home' => false, 'archive' => false, 'search' => false, 'attach' => false, 'page' => false,
            'post' => false, 'post-image' => false, 'post-gallery' => false, 'post-video' => false, 'post-audio' => false, 'post-aside' => false, 'post-quote' => false, 'post-link' => false, 'post-status' => false, 'post-chat' => false,
            'in_postid' => 'include', 'postid' => '', 'in_category' => 'include', 'category' => '', 'in_post_tag' => 'include', 'post_tag' => '');
        $post_types = get_post_types( array('public' => true, '_builtin' => false) );                    
        foreach ( $post_types as $post_type ) {
            $default[$post_type] = false;
        }
        ?>
        <script type='text/javascript' >
          var widget_display_filter_tab = <?php $tab = self::tab_select(); echo $tab; ?>;
        </script>
        
        <h2><?php _e('Widget Display Filter Settings', 'wdfilter'); ?></h2>
        <div class="grid-row">
            <div class="option-summary">
                <div class="summary-hed"><?php _e('Widgets Display Filter', 'wdfilter'); ?></div>
                <div>- <?php _e('Using Hashtag, Make the settings for the display conditions of Legacy Widgets and Widget Group block.', 'wdfilter') ?></div>
                <div class="summary-hed"><?php _e('Hidden Legacy Widgets',  'wdfilter'); ?></div>
                <div>- <?php _e('Registration Legacy widgets will no longer be displayed in Abailable Widgets.', 'wdfilter') ?></div>
            </div>
            <div class="side-info">
                <?php
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
                $plugins = get_plugins();
                if(empty($plugins['realtime-img-optimizer/realtime-img-optimizer.php']) || empty($plugins['plugin-load-filter/plugin-load-filter.php'])){
                ?>
                <div style="background-color: #f0fff0; border:1px solid #70c370; padding:4px 16px; margin:0;" >
                    <p><strong><?php _e('Introduction of plugin', 'wdfilter'); ?></strong></p>
                    <p><?php _e('Thank you for using Widget Display Filter. We offer some nifty plugin.', 'wdfilter'); ?></p>
                    <?php if(empty($plugins['plugin-load-filter/plugin-load-filter.php'])){ ?>
                        <p><a target="_blank" rel="noopener" href="https://celtislab.net/en/wp-plugin-load-filter/"> Plugin Load Filter</a></p>
                    <?php } ?>
                    <?php if(empty($plugins['realtime-img-optimizer/realtime-img-optimizer.php'])){ ?>
                        <p><a target="_blank" rel="noopener" href="https://celtislab.net/en/wp-realtime-image-optimizer/"> Realtime Image Optimizer Plugin</a></p>
                    <?php } ?>
                </div>
                <?php } ?>                
            </div>
        </div>
        <div id="widget-filter-tabs">
          <ul>
            <li><a href="#table-display-tab" ><?php _e('Widgets Display Filter', 'wdfilter'); ?></a></li>
            <li><a href="#table-register-tab" ><?php _e('Hidden Legacy Widgets', 'wdfilter'); ?></a></li>
          </ul>
          <form method="post" autocomplete="off" >
            <?php wp_nonce_field( 'widget_display_filter'); ?>
            <div id="table-display-tab" style="display : none;">
              <?php $this->display_filter_table( $default); ?>
              <table width="100%" cellspacing="2" cellpadding="3" class="editform form-table">
                <tbody>
                  <tr>
                    <th valign="top" scope="row"><label for="hashtag"><?php _e( 'Hashtag ', 'wdfilter' ); ?></label></th>
                    <td>
                      <input id="widget_display_filter[hashtag]" class="medium-text" type="text" name="widget_display_filter[hashtag]" value="" />
                      <input id="hashtag-add" class="button" name="entry_display_filter" type="submit" value="<?php _e('Hashtag Add', 'wdfilter'); ?>" />
                      <p><?php _e('Please enter a string tag for identification.　[Use characters : alphanumeric, hyphen, underscore]','wdfilter'); ?></p>
                    </td>
                  </tr>
                  <tr>
                    <th valign="top" scope="row"><label for="hashtag"><?php _e( 'Exclude Post Format Type ', 'wdfilter' ); ?></label></th>
                    <td>
                    <p><?php _e('If you select an unused post format type, it will be removed from the settings.','wdfilter'); ?></p>
                    <?php
                      $html =  '<div class="exclude-pformat">';
                      $pformat = array('image', 'gallery', 'video', 'audio', 'aside', 'status', 'quote', 'link', 'chat' );
                      foreach ( $pformat as $type ) {
                        $checked = (!empty(self::$filter['pformat'][$type]))? self::$filter['pformat'][$type] : false;
                        $label = "<span>$type</span>";
                        $html .= self::checkbox("pformat[$type]", $checked, $label);
                      }
                      $html .= '</div>';
                      echo $html;
                    ?>
                    </td>
                  </tr>
                </tbody>                
              </table>
              <div class="submit">
                <?php submit_button( __( 'Save Settings', 'wdfilter' ), 'primary', 'save_display_filter', false ); ?>
              </div>
              <p><strong><?php _e('[How to use]', 'wdfilter'); ?></strong></p>
              <ol class="setting-notice">
                <li><?php _e('Display conditions of the widget, set from Appearance -> Widgets menu of the management page.','wdfilter'); ?></li>
                <li><?php _e('Very simple. If you enter Hashtag in Legacy Widget title or Widget Group block title input field, its display condition is enabled.','wdfilter'); ?></li>
                <li><?php _e('Hashtag that can be set for each widget is only one. Between Hashtag and title should be separated by a space.','wdfilter'); ?></li>
                <li><?php _e('Group management by setting the same hashtag to multiple widgets.','wdfilter'); ?></li>
                <li><?php _e('Discrimination of Desktop / Mobile device uses the wp_is_mobile function.','wdfilter'); ?></li>
              </ol>                
            </div>
            <div id="table-register-tab" style="display : none;">
              <?php $this->register_filter_table( $default); ?>
              <table width="100%" cellspacing="2" cellpadding="3" class="editform form-table">
                <tbody>
                  <tr>
                    <th valign="top" scope="row"><label for="widget"><?php _e('Legacy Widget', 'wdfilter'); ?>:</label></th>
                    <td>
                      <select name="widget_display_filter[class]" id="widget_register">
                      <?php
                        global $wp_widget_factory;
                        $inactive = array();
                        if(!empty(self::$filter['register'])){
                            foreach( self::$filter['register'] as $id ) {
                                $inactive[] = $id['class'];
                            }
                        }
                        foreach ( $wp_widget_factory->widgets as $widget_class => $widget ) {
                            if(in_array($widget_class, $inactive) == false){
                                $widget_name = esc_attr($widget->name);
                                echo "\n\t<option value=\"$widget_class\" selected>$widget_name</option>";
                            }
                        }
                      ?>
                      </select>
                      <input type="submit" class="button-primary" name="entry_register_filter" value="<?php _e('Hidden Widget', 'wdfilter'); ?>" />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </form>
        </div>
        <div id="postid-dialog" title="Widget Display Filter" style="display : none;">
          <form>
              <p><?php _e( 'Set the display condition by Post ID.', 'wdfilter' ); ?></p>
              <table class="form-table">
                <tr valign="top">
                  <td>
                    <label><input type="radio" name="widget_display_filter[in_postid]" value="include" /><?php _e('include', 'wdfilter'); ?></label>
                    <label><input type="radio" name="widget_display_filter[in_postid]" value="exclude" /><?php _e('exclude', 'wdfilter'); ?></label>
                    <p></p>
                    <input type="text" size="48" id="filter-postid" name="widget_display_filter[postid]" value=""/>
                    <p><span style="font-size:xx-small"><?php _e( 'Please specify Post ID separated by commas.', 'wdfilter' ) ?></span></p>
                  </td>
                </tr>
              </table>
          </form>
        </div>    
        <div id="category-dialog" title="Widget Display Filter" style="display : none;">
          <form>
              <p><?php _e( 'Set the display conditions by post category.', 'wdfilter' ); ?></p>
              <table class="form-table">
                <tr valign="top">
                  <td>
                    <label><input type="radio" name="widget_display_filter[in_category]" value="include" /><?php _e('include', 'wdfilter'); ?></label>
                    <label><input type="radio" name="widget_display_filter[in_category]" value="exclude" /><?php _e('exclude', 'wdfilter'); ?></label>
                    <ul class="categorychecklist">
                      <?php wp_category_checklist(); ?>
                    </ul>
                  </td>
                </tr>
              </table>
          </form>
        </div>    
        <div id="post_tag-dialog" title="Widget Display Filter" style="display : none;">
          <form>
              <p><?php _e( 'Set the display conditions by post tag.', 'wdfilter' ); ?></p>
              <table class="form-table">
                <tr valign="top">
                  <td>
                    <label><input type="radio" name="widget_display_filter[in_post_tag]" value="include" /><?php _e('include', 'wdfilter'); ?></label>
                    <label><input type="radio" name="widget_display_filter[in_post_tag]" value="exclude" /><?php _e('exclude', 'wdfilter'); ?></label>
                    <ul class="post_tagchecklist">
                      <?php wp_terms_checklist( 0, array( 'taxonomy' => 'post_tag') ); ?>    
                    </ul>
                  </td>
                </tr>
              </table>
            </div>
        </div>    
        <?php
    }
}
    