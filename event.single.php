<?php $theme->display ( 'header'); ?>
<!-- event.single -->
  <div class="single">
   <div id="primary">
	<div class="navigation">
		<?php if ( $previous = $post->descend() ): ?>
		<div class="left"> &laquo; <a href="<?php echo $previous->permalink ?>" title="<?php echo $previous->slug ?>"><?php echo $previous->title ?></a></div>
		<?php endif; ?>
		<?php if ( $next = $post->ascend() ): ?>
		<div class="right"><a href="<?php echo $next->permalink ?>" title="<?php echo $next->slug ?>"><?php echo $next->title ?></a> &raquo;</div>
		<?php endif; ?>

		<div class="clear"></div>
	</div>

	<?php if($user->can('super_user', 'read')): ?><p>Please note this is just a default template, which should be replaced according to the installation instructions.</p><?php endif; ?>

    <div id="post-<?php echo $post->id; ?>" class="<?php echo $post->statusname; ?>">

     <div class="entry-head">
      <h3 class="entry-title">Event: <a href="<?php echo $post->permalink; ?>" title="<?php echo $post->title; ?>"><?php echo $post->title_out; ?></a></h3>
      <small class="entry-meta">
       <span class="chronodata"><abbr class="published"><?php $post->pubdate->out(); ?></abbr></span> <?php if ( $show_author ) { _e( 'by %s', array( $post->author->displayname ) );  } ?>
       <span class="commentslink"><a href="<?php echo $post->permalink; ?>#comments" title="<?php _e('Comments to this post'); ?>"><?php echo $post->comments->approved->count; ?>
	<?php echo _n( 'Comment', 'Comments', $post->comments->approved->count ); ?></a></span>
<?php if ( $loggedin ) { ?>
       <span class="entry-edit"><a href="<?php echo $post->editlink; ?>" title="<?php _e('Edit event'); ?>"><?php _e('Edit'); ?></a></span>
<?php } ?>
<?php if ( is_array( $post->tags ) ) { ?>
       <span class="entry-tags"><?php echo $post->tags_out; ?></span>
<?php } ?>
      </small>
     </div>

     <div class="entry-content">
      <?php echo $post->content_out; ?>

     </div>

    </div>
<?php $theme->display ('comments'); ?>
   </div>

   <hr>
   <div class="clear"></div>
  </div>
<!-- /event.single -->
<?php $theme->display ( 'footer' ); ?>
