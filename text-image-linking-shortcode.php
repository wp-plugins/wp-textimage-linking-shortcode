<?php /*
Plugin Name: LCT Text/Image Linking Shortcode
Plugin URI: http://lookclassy.com/wordpress-plugins/linking-shortcode/
Version: 4.2.2
Text Domain: wp-textimage-linking-shortcode
Author: Look Classy Technologies
Author URI: http://lookclassy.com/
License: GPLv3 (http://opensource.org/licenses/GPL-3.0)
Description: Use linking short codes to save you time and eliminate stress when restructuring your site pages & post.
Also Available in lct-useful-shortcodes-functions
Copyright 2014 Look Classy Technologies  (email : info@lookclassy.com)
*/

/*
Copyright (C) 2014 Look Classy Technologies

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
*/


//PLUGIN PREFIX: lwtils - NOT set yet


if( ! function_exists( 'is_plugin_active' ) ) { include_once( ABSPATH . '/wp-admin/includes/plugin.php' ); }
if( ! is_plugin_active( 'lct-useful-shortcodes-functions/lct-useful-shortcodes-functions.php' ) ) {


	define('SHORTCODE_TEXTIMAGE', 'link');

	add_shortcode(SHORTCODE_TEXTIMAGE, 'shortcode_textimage');
	function shortcode_textimage($t){
		foreach( $t as $k=>$v ){
			$t[$k] = do_shortcode( str_replace( array( "{", "}" ), array( "[", "]" ), $v ) );
		}
		extract(shortcode_atts(array(
			'id'			=> null,
			'text'			=> null,
			'class'			=> null,

			'alt'			=> null,
			'esc_html'		=> null,
			'rel'			=> null,
			'src'			=> null,
			'style'			=> null,
			'title'			=> null,
			'imagetext'		=> null,
			'textimage'		=> null,
			'anchor'			=> null
		),$t));

		//initialize atts
		$id						= intval($id);
		$text					= trim($text);
		!empty($class)			? $class = ' class="'. esc_attr(trim($class)) .'"' : $class = '';

		!empty($alt)			? $alt = trim($alt) : $alt = '';
		!empty($esc_html)		? $esc_html = trim($esc_html) : $esc_html = 'true';
		!empty($rel)			? $rel = ' rel="'. esc_attr(trim($rel)) .'"' : $rel = '';
		!empty($src)			? $src = esc_attr(trim($src)) : $src = '';
		!empty($style)			? $style = ' style="'. esc_attr(trim($style)) .'"' : $style = '';
		!empty($title)			? $title = ' title="'. esc_attr(trim($title)) .'"' : $title = '';
		!empty($anchor)			? $anchor = '#'. trim($anchor) : $anchor = '';

		if(strstr($anchor, "?"))
			$anchor = str_replace("#", "", $anchor);

		if(empty($id))
			return $text;

		$url = get_permalink($id) . $anchor;

		if(empty($text))
			$text = get_the_title($id);

		if($esc_html=='true'){
			$src = esc_html($src);
			$text = esc_html($text);
		}

		if(!$alt)
			$alt = $text;

		if($src){
			if($imagetext || $textimage){
				if($imagetext)
					return '<a href="'. $url .'"'. $class . $rel . $title .'><img src="'. $src .'"'. $style .' alt="'. $alt .'" />'. $text .'</a>';
				else
					return '<a href="'. $url .'"'. $class . $rel . $title .'>'. $text .'<img src="'. $src .'"'. $style .' alt="'. $alt .'" /></a>';
			}else
				return '<a href="'. $url .'"'. $class . $rel . $title .'><img src="'. $src .'"'. $style .' alt="'. $alt .'" /></a>';
		}else
			return '<a href="'. $url .'"'. $class . $rel . $style . $title .'>'. $text .'</a>';
	}


	add_action('init', 'wptisc_request_handler');
	function wptisc_request_handler(){
		if(!empty($_GET['tisc_action'])){
			switch ($_GET['tisc_action']){
				case 'wptisc_id_lookup':
					wptisc_id_lookup();
					break;
				case 'wptisc_admin_js':
					wptisc_admin_js();
					break;
				case 'wptisc_admin_css':
					wptisc_admin_css();
					die();
					break;
			}
		}
	}


	function wptisc_id_lookup(){
		global $wpdb;
		$title = stripslashes($_GET['post_title']);
		$wild = '%'. $wpdb->escape($title) .'%';
		$posts = $wpdb->get_results("
			SELECT *
			FROM $wpdb->posts
			WHERE (
				post_title LIKE '$wild'
				OR post_name LIKE '$wild'
			)
			AND post_status = 'publish'
			ORDER BY post_title
			LIMIT 25
		");
		if (count($posts)) {
			$output = '<ul>';
			foreach ($posts as $post){
				if($post->post_type != 'page')
					$post_type = ' - <strong>('. esc_html($post->post_type) .')</strong>';
				else
					$post_type = '';
				$output .= '<li class="'. $post->ID. '" title="['. SHORTCODE_TEXTIMAGE .' id=\''. $post->ID .'\' text=\'\']">'. esc_html($post->post_title) .' - ID:'. esc_html($post->ID) . $post_type .'</li>';
			}
			$output .= '</ul>';
		}
		else {
			$output = '<ul><li>'. __('Sorry, no matches.', 'textimage-shortcode') .'</li></ul>';
		}
		echo $output;
		die();
	}


	function wptisc_admin_js(){
		header('Content-type: text/javascript');
	?>
	wptisc_show_shortcode = function($elem) {
		if ($elem.find('input').size() == 0) {
			$elem.append('<input type="text" value="' + $elem.attr('title') + '" />').find('input').keydown(function(e) {
				switch (e.which) {
					case 13: // enter
						return false;
						break;
					case 27: // esc
						jQuery('#wptisc_post_title').focus();
						break;
				}
			}).focus().select();
		}
	};
	jQuery(function($) {
		$('#wptisc_meta_box a.wptisc_help').click(function() {
			$('#wptisc_meta_box div.wptisc_readme').slideToggle(function() {
				$('#wptisc_post_title').css('background', '#fff');
			});
			return false;
		});
		$('#wptisc_search_box').click(function() {
			$('#wptisc_post_title').focus().css('background', '#ffc');
			return false;
		});
		$('#wptisc_post_title').keyup(function(e) {
			form = $('#wptisc_meta_box');
			term = $(this).val();
	// catch everything except up/down arrow
			switch (e.which) {
				case 27: // esc
					form.find('.live_search_results ul').remove();
					break;
				case 13: // enter
				case 38: // up
				case 40: // down
					break;
				default:
					if (term == '') {
						form.find('.live_search_results ul').remove();
					}
					if (term.length > 2) {
						$.get(
							'<?php echo admin_url('index.php'); ?>',
							{
								tisc_action: 'wptisc_id_lookup',
								post_title: term
							},
							function(response) {
								$('#wptisc_meta_box div.live_search_results').html(response).find('li').click(function() {
									$('#wptisc_meta_box .active').removeClass('active');
									$(this).addClass('active');
									wptisc_show_shortcode($(this));
									return false;
								});
							},
							'html'
						);
					}
					break;
			}
		}).keydown(function(e) {
	// catch arrow up/down here
			form = $('#wptisc_meta_box');
			if (form.find('.live_search_results ul li').size()) {
				switch (e.which) {
					case 13: // enter
						active = form.find('.live_search_results ul li.active');
						if (active.size()) {
							wptisc_show_shortcode(active);
						}
						return false;
						break;
					case 40: // down
						if (!form.find('.live_search_results ul li.active').size()) {
							form.find('.live_search_results ul li:first-child').addClass('active');
						}
						else {
							form.find('.live_search_results ul li.active').next('li').addClass('active').prev('li').removeClass('active');
						}
						return false;
						break;
					case 38: // up
						if (!form.find('.live_search_results ul li.active').size()) {
							form.find('.live_search_results ul li:last-child').addClass('active');
						}
						else {
							form.find('.live_search_results ul li.active').prev('li').addClass('active').next('li').removeClass('active');
						}
						return false;
						break;
				}
			}
		});
	});
	<?php
		die();
	}


	if(is_admin()){
		wp_enqueue_script('wptisc_admin_js', trailingslashit(get_bloginfo('url')).'?tisc_action=wptisc_admin_js', array('jquery'));
	}


	function wptisc_admin_css(){
		header('Content-type: text/css');
	?>
	#wptisc_meta_box fieldset a.wptisc_help {
		background: #f5f5f5;
		border-radius: 6px;
		-moz-border-radius: 6px;
		-webkit-border-radius: 6px;
		color: #666;
		display: block;
		font-size: 11px;
		float: right;
		padding: 4px 6px;
		text-decoration: none;
	}
	#wptisc_meta_box fieldset label {
		display: none;
	}
	#wptisc_meta_box fieldset input {
		width: 235px;
	}
	#wptisc_meta_box .live_search_results {
		position: relative;
		z-index: 500;
	}
	#wptisc_meta_box .live_search_results ul {
		background: #fff;
		list-style: none;
		margin: 0 0 0 1px;
		padding: 0 2px 3px;
		position: absolute;
		width: 230px;
	}
	#wptisc_meta_box .live_search_results ul li {
		border: 1px solid #eee;
		border-top: 0;
		cursor: pointer;
		line-height: 100%;
		margin: 0;
		overflow: hidden;
		padding: 5px;
	}
	#wptisc_meta_box .live_search_results ul li.active,
	#wptisc_meta_box .live_search_results ul li:hover {
		background: #e0edf5;
		font-weight: bold;
	}
	#wptisc_meta_box .live_search_results input {
		width: 200px;
	}
	#wptisc_meta_box div.wptisc_readme {
		display: none;
	}
	#wptisc_meta_box div.wptisc_readme li {
		margin: 0 10px 10px;
	}
	<?php
		die();
	}


	add_action('admin_print_styles', 'wptisc_admin_head');
	function wptisc_admin_head(){
		echo '<link rel="stylesheet" type="text/css" href="'.trailingslashit(get_bloginfo('url')).'?tisc_action=wptisc_admin_css" />';
	}


	function wptisc_meta_box(){
	?>
	<fieldset>
		<a href="#" class="wptisc_help"><?php _e('?', 'textimage-shortcode'); ?></a>
		<label for="wptisc_post_title"><?php _e('Page / Post Title:', 'textimage-shortcode'); ?></label>
		<input type="text" name="wptisc_post_title" id="wptisc_post_title" autocomplete="off" />
		<div class="live_search_results"></div>
		<div class="wptisc_readme">
			<h4><?php _e('Shortcode Syntax / Customization', 'textimage-shortcode'); ?></h4>
			<p><?php _e('There are several different ways that you can enter the shortcode:', 'textimage-shortcode'); ?></p>
			<ul>
				<li><code>[link id='123']</code> = <code>&lt;a href="{<?php _e('url of post/page #123', 'textimage-shortcode'); ?>}">{<?php _e('title of post/page #123', 'textimage-shortcode'); ?>}&lt;/a></code></li>
				<li><code>[link id='123' text='<b><?php _e('my link text', 'textimage-shortcode'); ?></b>']</code> = <code>&lt;a href="{<?php _e('url of post/page #123', 'textimage-shortcode'); ?>}"><b><?php _e('my link text', 'textimage-shortcode'); ?></b>&lt;/a></code></li>
			</ul>
			<p><?php _e('You can also add a <code>class</code> or <code>rel</code> attribute to the shortcode, and it will be included in the resulting <code>&lt;a></code> tag:', 'textimage-shortcode'); ?></p>
			<ul>
				<li><code>[link id='123' text='<?php _e('my link text', 'textimage-shortcode'); ?>' class='my-class' rel='external']</code> = <code>&lt;a href="{<?php _e('url of post/page #123', 'textimage-shortcode'); ?>}" class="my-class" rel="external"><?php _e('my link text', 'textimage-shortcode'); ?>&lt;/a></code></li>
			</ul>
			<h4><?php _e('Usage', 'textimage-shortcode'); ?></h4>
			<p><?php _e('Type into the <a href="#" id="wptisc_search_box">search box</a> and posts whose title matches your search will be returned so that you can grab an internal link shortcode for them for use in the content of a post / page.', 'textimage-shortcode'); ?></p>
			<p><?php _e('The shortcode to link to a page looks something like this:', 'textimage-shortcode'); ?></p>
			<p><code>[link id='123']</code></p>
			<p><?php _e('Add this to the content of a post or page and when the post or page is displayed, this would be replaced with a link to the post or page with the id of 123.', 'textimage-shortcode'); ?></p>
			<p><?php _e('These internal links are site reorganization-proof, the links will change automatically to reflect the new location or name of a post or page when it is moved.', 'textimage-shortcode'); ?></p>
		</div>
	</fieldset>
	<?php
	}


	add_action('admin_init', 'wptisc_add_meta_box');
	function wptisc_add_meta_box() {
		add_meta_box('wptisc_meta_box', __('Link Shortcode Lookup', 'textimage-shortcode'), 'wptisc_meta_box', 'post', 'side');
		add_meta_box('wptisc_meta_box', __('Link Shortcode Lookup', 'textimage-shortcode'), 'wptisc_meta_box', 'page', 'side');

		// Public non built in post types
		$args=array(
			'public'   => true,
			'_builtin' => false
		);

		$output = 'names';
		$post_types = get_post_types($args,$output);
		if (count($post_types)) {
			foreach ($post_types as $post_type) {
				add_meta_box('wptisc_meta_box', __('Link Shortcode Lookup', 'textimage-shortcode'), 'wptisc_meta_box', $post_type, 'side');
			}
		}
	}
}
