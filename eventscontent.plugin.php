<?php

class EventsContent extends Plugin
{ 
	/**
	* Add update beacon support
	**/
	public function action_update_check()
	{
		Update::add( $this->info->name, 'c330c3fe-3f34-47ff-b5c7-51b2269cfaed', $this->info->version );
	}
	
	/**
	 * Register content type
	 **/
	public function action_plugin_activation( $plugin_file )
	{
		// add the content type.
		Post::add_new_type( 'event' );
		
		// Give anonymous users access
		$group = UserGroup::get_by_name('anonymous');
		$group->grant('post_event', 'read');
	}
	
	public function action_plugin_deactivation( $plugin_file )
	{
		Post::deactivate_post_type( 'event' );
	}
	
	/**
	 * Register templates
	 **/
	public function action_init()
	{		
		// Create templates
		// $this->add_template('event.single', dirname(__FILE__) . '/event.single.php');
	}
	
	/**
	 * Create name string. This is where you make what it displays pretty.
	 **/
	public function filter_post_type_display($type, $foruse) 
	{ 
		$names = array( 
			'event' => array(
				'singular' => _t('Event'),
				'plural' => _t('Events'),
			)
		); 
 		return isset($names[$type][$foruse]) ? $names[$type][$foruse] : $type; 
	}
	
	/**
	 * Format the unix timestamp as readable date.
	 **/
	public static function format_date_out($value)
	{
		if(isset($value) && !empty($value))
		{
			// Get the user's preferred format or, if none set, the system's default format
			$user_format = User::identify()->info->locale_date_format;
			if(!isset($user_format) || empty($user_format)) $user_format = Options::get('dateformat');
			
			if(strstr($value, ";"))
			{ // Output formatting for dates with start and end
				list($start, $end) = explode(";", $value, 2);
				$startformatter = HabariDateTime::date_create($start);
				$endformatter = HabariDateTime::date_create($end);
				(isset($user_format) && !empty($user_format)) ? $out = $startformatter->format($user_format) . " - " . $endformatter->format($user_format) : $out = $startformatter->format() . " - " . $endformatter->format();
			}
			else
			{ // Output formatting for single dates
				$formatter = HabariDateTime::date_create($value);
				(isset($user_format) && !empty($user_format)) ? $out = $formatter->format($user_format) : $out = $formatter->format();
			}
			
			return $out;
		}
		else return $value;
	}
	
	/**
	 * Format the unix timestamp as readable time.
	 **/
	public static function format_time_out($value)
	{
		if(isset($value) && !empty($value))
		{
			// Get the user's preferred format or, if none set, the system's default format
			$user_format = User::identify()->info->locale_time_format;
			if(!isset($user_format) || empty($user_format)) $user_format = Options::get('timeformat');
			
			// Format the time
			$hdteventtime = HabariDateTime::date_create($value);
			if(isset($user_format) && !empty($user_format))
				$out = $hdteventtime->format($user_format);
			else
				$out = $hdteventtime->format();
			return $out;
		}
		else return $value;
	}
	
	/**
	 * Modify publish form. We're going to add the custom 'eventdate' field, as we like
	 * to hold our events at specific dates. We'll also add fields for the time, the location
	 * and a field called "eventtag" which will help us group multiple posts for the same event.
	 * We store the time separately to let the user decide in his theme what he wants to display.
	 * Also, if we stored both together, the time would be 00:00 for events with no specific time.
	 **/
	public function action_form_publish($form, $post, $context)
	{
		// only edit the form if it's an event
		if ($form->content_type->value == Post::type('event'))
		{
			// add text fields
			$form->insert('tags', 'text', 'eventtag', 'null:null', _t('Event Tag (for event grouping)'), 'admincontrol_textArea');
			$form->insert('tags', 'text', 'location', 'null:null', _t('Event Location'), 'admincontrol_textArea');
			$form->insert('tags', 'text', 'eventdate', 'null:null', _t('Event Date'), 'admincontrol_textArea');
			$form->insert('tags', 'text', 'eventtime', 'null:null', _t('Event Time'), 'admincontrol_textArea');
			// load values and display the fields
			$form->eventtag->value = $post->info->eventtag;
			$form->eventtag->template = 'admincontrol_text';
			$form->location->value = $post->info->location;
			$form->location->template = 'admincontrol_text';
			// use the same function for displaying the date we use for displaying the date in the theme
			$form->eventdate->value = EventsContent::format_date_out($post->info->eventdate);
			$form->eventdate->template = 'admincontrol_text';
			// the same for the time
			$form->eventtime->value = EventsContent::format_time_out($post->info->eventtime);
			$form->eventtime->template = 'admincontrol_text';
		}
	}
	
	/**
	 * Save our data to the database
	 **/
	public function action_publish_post( $post, $form )
	{
		if ($post->content_type == Post::type('event'))
		{
			$post->info->eventtag = $form->eventtag->value;
			$post->info->location = $form->location->value;
			// Save date and time as unix timestamp so we can order by date.
			// Multiday events are supported by the following function, but first we do some formatting preparation
			// This actually reverts what format_date_out() did before
			$eventdate = $form->eventdate->value;
			if(!empty($eventdate))
			{
				$post->info->eventdate = EventsContent::eventdate_to_unix($eventdate);
			}
			else $post->info->eventdate = "";
			// For the time, the reverting is simpler as multiple times are not yet supported
			// If the field is empty, we remove the time from the database
			// That's necessary because otherwise format_time_out would use the current time
			$eventtime = $form->eventtime->value;
			if(isset($eventtime) && !empty($eventtime))
			{
				$post->info->eventtime = HabariDateTime::date_create($eventtime)->int;
			}
			else
			{
				unset($post->info->eventtime);
			}
		}
	}
	
	/**
	 * Converts dates provided by the user to unix timestamps
	 * This function is necessary for multiple dates
	 **/
	public static function eventdate_to_unix($eventdatestring)
	{
		$eventdatestring = str_replace("-", ";", $eventdatestring);
		if(strpos($eventdatestring, ";") === false)
			return HabariDateTime::date_create($eventdatestring)->int;
			
		$dates = explode(";", $eventdatestring);
		foreach($dates as $datestring)
		{
			try
			{
				$unixdates[] = HabariDateTime::date_create($datestring)->int;
			} catch(Exception $crap) {return false;}
		}
		
		if(count($unixdates)) return implode(";", $unixdates);
		else return false;
	}

	/**
	 * Add the posts to the blog home and it's pagination pages
	 * Thanks to lildude for the fix included in this function
	 */
	public function filter_template_user_filters( $filters ) 
	{
		// Cater for the home page which uses presets as of d918a831
		if ( isset( $filters['preset'] ) ) {
			$filters['preset'] = 'events';
		} else {		
			// Cater for other pages like /page/1 which don't use presets yet
			if ( isset( $filters['content_type'] ) ) {
				$filters['content_type'] = Utils::single_array( $filters['content_type'] );
				$filters['content_type'][] = Post::type( 'event' );
			}
		}
		return $filters;
	}
	
	/**
	 * Modify output in the rss feed (include post info metadata)
	 **/
    public function action_rss_add_post( $feed_entry, $post )
    {
        $info = $post->info->get_url_args();
        foreach( $info as $key => $value ) {
            if( is_array( $value ) && isset( $value['enclosure'] ) ) {
                $enclosure = $feed_entry->addChild( 'enclosure' );
                $enclosure->addAttribute( 'url', $value['enclosure'] );
                $enclosure->addAttribute( 'length', $value['size'] );
                $enclosure->addAttribute( 'type', 'text' );
            }
        }
    }

	/**
	 * Modify output in the atom feed (include post info metadata)
	 **/
    public function action_atom_add_post( $feed_entry, $post )
    {
//        $info = $post->info->get_url_args();
//        foreach( $info as $key => $value ) {
//            if( is_array( $value ) && isset( $value['enclosure'] ) ) {
//                $enclosure = $feed_entry->addChild( 'link' );
//                $enclosure->addAttribute( 'rel', 'enclosure' );
//                $enclosure->addAttribute( 'href', $value['enclosure'] );
//                $enclosure->addAttribute( 'length', $value['size'] );
//                $enclosure->addAttribute( 'type', 'text' );
//            }
//        }
		if(Post::type("event")==$post->content_type)
			$feed_entry->content[0] = "<strong>Event @ ".$post->info->location.", ".$post->info->eventdate_out.":</strong> ".$feed_entry->content[0];
    }
	
	/**
	 * Add events to the global posts atom feed
	 **/
	public function filter_atom_get_collection_content_type( $content_type )
    {
        $content_type = Utils::single_array( $content_type );
        $content_type[] = Post::type( 'event' );
        return $content_type;
    }
	
	/**
	 * Make posts searchable by locations and eventtags on the admin manage page
	 **/
    public function filter_posts_search_to_get ( $arguments, $flag, $value, $match, $search_string)
    {
        if($flag == 'location') {
            $arguments['info'] = array('location'=>$value);
        }
		else if($flag == 'eventtag') {
			$arguments['info'] = array('eventtag'=>$value);
		}
        return $arguments;
    }
    
	/**
	 * Add rewrite rules to handle locations and eventtags
	 **/
    public function filter_rewrite_rules($rules)
    {
        $rules[] = RewriteRule::create_url_rule('"location"/location_name', 'PluginHandler', 'location');
		$rules[] = RewriteRule::create_url_rule('"eventtag"/eventtag_value', 'PluginHandler', 'eventtag');
        return $rules;
    }
    
    /**
	 * Implement the output for posts by location
	 **/
    public function action_plugin_act_location($handler)
    {
        $handler->theme->act_display_entries(array('info' => array('location' => $handler->handler_vars['location_name'])));
    }
	
	 /**
	 * Implement the output for posts by eventtag
	 **/
    public function action_plugin_act_eventtag($handler)
    {
        $handler->theme->act_display_entries(array('info'=>array('eventtag'=>$handler->handler_vars['eventtag_value'])));
    }
	
	/**
	 * Convert the date to the user's preferred date format when it is requested by the theme with eventdate_out.
	 **/
	public function filter_post_info_eventdate_out($value)
	{
		return EventsContent::format_date_out($value);
	}
	
	/**
	 * Convert the time to the user's preferred time format when it is requested by the theme with eventtime_out.
	 **/
	public function filter_post_info_eventtime_out($value)
	{
		return EventsContent::format_time_out($value);
	}
	
	
}
?>