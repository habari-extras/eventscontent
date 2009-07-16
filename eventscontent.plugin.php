<?php

class eventsContent extends Plugin
{ 
	
	/**
	 * Required plugin info() implementation provides info to Habari about this plugin.
	 */ 
	public function info()
	{
		return array (
			'name' => 'EventsContent',
			'url' => 'http://www.squareweave.com.au',
			'author' => 'Luke @ Squareweave',
			'authorurl' => 'http://luke.giuliani.com.au',
			'version' => 0.1,
			'description' => 'Adds the event Content-Type',
			'license' => 'ASL 2.0',
		);
	}

	/**
	 * Create help file
	 */
	public function help() {
		$str= '';
		$str.= '<p>EventsContent adds the event content type to add an event.</p>';
		$str.= '<h3>Installation Instructions</h3>';
		$str.= '<p>Your theme needs to have a <code>event.single</code> template, or a generic <code>single</code> template. If it does not, you can usually copy <code>entry.single</code> to <code>event.single</code> and use it.</p>';
		return $str;
	}

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
		$this->add_template('event.single', dirname(__FILE__) . '/event.single.php');
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
	 * Modify publish form. We're going to add the custom 'address' field, as we like
	 * to hold our events at addresses.
	 */
	public function action_form_publish($form, $post)
	{
	  // only edit the form if it's an event
		if ($post->content_type == Post::type('event')) {
      // just want to add a text field
			$address= $form->append('text', 'url', 'null:null', _t('Event Address'), 'admincontrol_text');
			$address->value= $post->info->address;
			// put it after the content
			$form->move_after($address, $form->content);
		}
	}
	
	/**
	 * Save our data to the database
	 */
	public function action_publish_post( $post, $form )
	{
		if ($post->content_type == Post::type('event')) {
			$this->action_form_publish($form, $post);
			// Address exists because we made it in action_form_publish()
			$post->info->address = $form->address->value;
		}
	}

	/**
	 * Add the 'events' type to the list of templates that we can use.
	 */
	public function filter_template_user_filters($filters) {
		if(isset($filters['content_type'])) {
			$filters['content_type']= Utils::single_array( $filters['content_type'] );
			$filters['content_type'][]= Post::type('event');
		}
		return $filters;
	}
	
}

?>