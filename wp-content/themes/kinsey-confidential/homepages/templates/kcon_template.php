<div class="row">
	<div class="span12">
		<?php echo $top_three; ?>
	</div>
</div>
<div class="row">
	<div class="span8">
		<?php if ( !dynamic_sidebar( 'Homepage Main Column Top' ) ) { ?>
			<!-- Place some widgets in the "Homepage Main Column Top" widget area, please. This is an excellent place for the podcast player. -->
		<?php } ?>
		
		<p class="podcast-subscribe"><a href="https://itunes.apple.com/us/podcast/kinsey-confidential/id272888845?mt=2"><i class="icon-headphones"></i>Subscribe in iTunes</a></p>
		
		<div class="row">
			<?php echo $home_six; ?>
			<?php echo $homepage_lmp_button; ?>
		</div>

	</div>
	<aside id="sidebar" class="span4">
		<?php if ( !dynamic_sidebar( 'Main Sidebar' ) ) { ?>
			<p>Place some widgets in the "Main Sidebar" widget area, please</p>
		<?php } ?>
		<aside class="widget widget-topic-list">
			<h3 class="widgettitle">Learn About</h3>
			<ul id="topic-nav">
			<?php
				$args = array(
					'theme_location' => 'topics-menu',
					'depth' => 0,
					'container' => false,
					'items_wrap' => '%3$s',
					'menu_class' => 'nav',
					'walker' => new Bootstrap_Walker_Nav_Menu()
				);
				largo_nav_menu( $args );	
			?>
			</ul>
		</aside>
	</aside>
</div>
