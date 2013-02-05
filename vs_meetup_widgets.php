<?php 
/**
 * VsMeetWidget
 * All widget info for VsMeet (including widgets themselves)
 */

class VsMeetWidget extends VsMeet{
	// private $req_url = 'http://api.meetup.com/oauth/request/';
	// private $authurl = 'http://www.meetup.com/authorize/';
	// private $acc_url = 'http://api.meetup.com/oauth/access/';
	// private $api_url = 'http://api.meetup.com/';
	// private $callback_url = '';
		
	private $key = '';
	private $secret = '';	
	protected $api_key = "";
	
	public function __construct() {
		$options = get_option('vs_meet_options');
		// $this->key = $options['vs_meetup_key'];
		// $this->secret = $options['vs_meetup_secret'];
		$this->api_key = $options['vs_meetup_api_key'];
		// $this->callback_url = admin_url( 'admin-ajax.php' ) .'?action=meetup_event';
		
		parent::__construct();
		
		// add login function to ajax requests
		// add_action( 'wp_ajax_nopriv_meetup_event', array($this, 'meetup_event_popup') );
		// add_action( 'wp_ajax_meetup_event', array($this, 'meetup_event_popup') );
	}
	
	/**
	 * 
	 * @param string $id Meetup ID or URL name
	 * @param string $limit number of events to display, default 10.
	 * @param string $filter not used
 	 * @return string Event list formatted for display in widget
	 */
	public function get_list_events( $id, $limit = 10, $filter = '' ){
		$options = get_option('vs_meet_options');
		$this->api_key = $options['vs_meetup_api_key'];
		if (!empty($this->api_key)) {
			$out = '';
            // split $id and loop this
            $ids = explode(',', $id);
            foreach ($ids as $oneid) {
                if ( preg_match('/[a-zA-Z]/', $oneid ) )
                    $event_response = wp_remote_get( "http://api.meetup.com/2/events.json/?group_urlname=$oneid&status=upcoming&page=$limit&key=". $this->api_key );
                else
                    $event_response = wp_remote_get( "http://api.meetup.com/2/events.json/?group_id=$oneid&status=upcoming&page=$limit&key=". $this->api_key );
        
                if( is_wp_error( $event_response ) ) {
                    if (WP_DEBUG){
                        echo 'Something went wrong!';
                        var_dump($event_response);
                    }
                } else {
                    $events = json_decode($event_response['body'])->results;
                    # add group name
                    //$out .= "<p class='meetup_title'>".$events[1]->group->name."</p>";
                    $out .= "<ul class='meetup_list'>";
                    foreach ($events as $event) { 
                        $out .= "<li><strong>".$event->group->name."</strong>: <a href='".$event->event_url."'>".$event->name."</a>; ".date('M d',intval($event->time/1000 + $event->utc_offset/1000))."</li>";
                        // add ", g:ia" back to date() to get time back
                    }
                    $out .= '</ul>';
                    //$out .= '<pre>'.print_r($events,true).'</pre>';
                }
            }
			
		} else {
			$out = '<p><a href="'.admin_url('options-general.php').'">Please enter an API key</a></p>';
		}
		return $out;
	}
}


/**
 * VsMeetList extends the widget class to create an event list for a set of meetup groups.
 */
class VsMeetListWidget extends WP_Widget {
    /** constructor */
    function VsMeetListWidget() {
        parent::WP_Widget(false, $name = __('Meetup List Event','vsmeet_domain'), array('description' => __("Display a list of events.",'vsmeet_domain')));	
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {		
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        $id = $instance['id']; // meetup IDs or URL names
        $limit = intval($instance['limit']); 
        
        echo $before_widget;
        if ( $title ) echo $before_title . $title . $after_title;
        if ( $id ) {
        	$vsm = new VsMeetWidget();
	        echo $vsm->get_list_events($id,$limit);
	    }
        echo $after_widget;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['id'] = strip_tags($new_instance['id']);
        $instance['limit'] = intval($new_instance['limit']); 
        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
        if ( $instance ) {
			$title = esc_attr($instance['title']);
			$id = esc_attr($instance['id']); // -> it's a name if it contains any a-zA-z, otherwise ID
			$limit = intval($instance['limit']); 
        } else {
			$title = '';
			$id = '';
			$limit = 10;
        }
        ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>">
            <?php _e('Title:','vsmeet_domain'); ?>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </label></p>
        <p><label for="<?php echo $this->get_field_id('id'); ?>">
		    <?php _e('Group IDs, comma-separated:','vsmeet_domain'); ?>
		    <input class="widefat" id="<?php echo $this->get_field_id('id'); ?>" name="<?php echo $this->get_field_name('id'); ?>" type="text" value="<?php echo $id; ?>" />
        </label></p>
        <p>
        	<label for="<?php echo $this->get_field_id('limit'); ?>">
            	<?php _e('Number of events to show:','vsmeet_domain');?>
            </label>
            <input id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="text" value="<?php echo $limit; ?>" size='3' />
		</p>
    <?php }
} // class VsMeetListWidget
