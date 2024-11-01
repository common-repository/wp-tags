<?php get_header(); ?>

	<div id="content" class="narrowcolumn">

		<?php if (have_posts() && !is_tags_list()) : ?>

		 <?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
<?php /* If this is a tags archive */ if (is_tags()): ?>
		<h2 class="pagetitle">Tagged: <?php echo tags_title(); ?></h2>
		
 	  <?php endif;  ?>

		<div class="navigation">
			<div class="alignleft"><?php posts_nav_link('','','&laquo; Previous Entries') ?></div>
			<div class="alignright"><?php posts_nav_link('','Next Entries &raquo;','') ?></div>
		</div>

		<?php while (have_posts()) : the_post(); ?>
		<div class="post">
				<h3 id="post-<?php the_ID(); ?>"><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>"><?php the_title(); ?></a></h3>
				<small><?php the_time('l, F jS, Y') ?> ( <?php echo the_tags(); ?> )</small>
				
				<div class="entry">
					<?php the_content() ?>
				</div>
		
				<p class="postmetadata"><?php edit_post_link('Edit','','<strong>|</strong>'); ?>  <?php comments_popup_link('No Comments &#187;', '1 Comment &#187;', '% Comments &#187;'); ?></p> 
				
				<!--
				<?php trackback_rdf(); ?>
				-->
			</div>
	
		<?php endwhile; ?>

		<div class="navigation">
			<div class="alignleft"><?php posts_nav_link('','','&laquo; Previous Entries') ?></div>
			<div class="alignright"><?php posts_nav_link('','Next Entries &raquo;','') ?></div>
		</div>
	
	<?php else : ?>

		<h2 class="center"><?php _e('Tags List'); ?></h2>
		<p><?php list_tags(); ?></p>

	<?php endif; ?>
		
	</div>

<?php get_sidebar(); ?>

<?php get_footer(); ?>
