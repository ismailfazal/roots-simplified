<?php
/**
 * Advanced functions
 */
 
function roots_rewrites() {
  /**
   * Define helper constants
   */
  $get_theme_name = explode('/themes/', get_template_directory());

  define('RELATIVE_PLUGIN_PATH',  str_replace(home_url() . '/', '', plugins_url()));
  define('RELATIVE_CONTENT_PATH', str_replace(home_url() . '/', '', content_url()));
  define('THEME_NAME',            next($get_theme_name));
  define('THEME_PATH',            RELATIVE_CONTENT_PATH . '/themes/' . THEME_NAME);

  /**
   * Rewrites do not happen for multisite installations or child themes
   *
   * Rewrite:
   *   /wp-content/themes/themename/assets/css/ to /assets/css/
   *   /wp-content/themes/themename/assets/js/  to /assets/js/
   *   /wp-content/themes/themename/assets/img/ to /assets/img/
   *   /wp-content/plugin                     to /resources
   *
   * If you aren't using Apache, Nginx configuration settings can be found in the README
   */
  function roots_add_rewrites($content) {
    global $wp_rewrite;
    $roots_new_non_wp_rules = array(
      'assets/(.*)'          => THEME_PATH . '/assets/$1',
      'resources/(.*)'         => RELATIVE_PLUGIN_PATH . '/$1'
    );
    $wp_rewrite->non_wp_rules = array_merge($wp_rewrite->non_wp_rules, $roots_new_non_wp_rules);
    return $content;
  }

  function roots_clean_urls($content) {
    if (strpos($content, RELATIVE_PLUGIN_PATH) > 0) {
      return str_replace('/' . RELATIVE_PLUGIN_PATH,  '/resources', $content);
    } else {
      return str_replace('/' . THEME_PATH, '', $content);
    }
  }

  if (!is_multisite() && !is_child_theme()) {
    add_action('generate_rewrite_rules', 'roots_add_rewrites');

    if (!is_admin()) {
      $tags = array(
        'plugins_url',
        'bloginfo',
        'stylesheet_directory_uri',
        'template_directory_uri',
        'script_loader_src',
        'style_loader_src'
      );

      add_filters($tags, 'roots_clean_urls');
    }
  }
}
add_action('after_setup_theme', 'roots_rewrites'); 
 
// remove wp version param from any enqueued scripts
function vc_remove_wp_ver_css_js( $src ) {
    if ( strpos( $src, 'ver=' ) )
        $src = remove_query_arg( 'ver', $src );
    return $src;
}
add_filter( 'style_loader_src', 'vc_remove_wp_ver_css_js', 9999 );
add_filter( 'script_loader_src', 'vc_remove_wp_ver_css_js', 9999 );

// hide admin bar frontend
show_admin_bar(false);

// hide url box from comment system
function disable_comment_url($fields)
{  
   unset($fields['url']); 
   return $fields;
}
add_filter('comment_form_default_fields','disable_comment_url');

// auto set featured image
function autoset_featured() {
          global $post;
          $already_has_thumb = has_post_thumbnail($post->ID);
              if (!$already_has_thumb)  {
              $attached_image = get_children( "post_parent=$post->ID&post_type=attachment&post_mime_type=image&numberposts=1" );
                          if ($attached_image) {
                                foreach ($attached_image as $attachment_id => $attachment) {
                                set_post_thumbnail($post->ID, $attachment_id);
                                }
                           }
                        }
      }
add_action('the_post', 'autoset_featured');
add_action('save_post', 'autoset_featured');
add_action('draft_to_publish', 'autoset_featured');
add_action('new_to_publish', 'autoset_featured');
add_action('pending_to_publish', 'autoset_featured');
add_action('future_to_publish', 'autoset_featured');

// change pagination slug to view
	add_action( 'init', 'page_to_view' );
 
	function page_to_view()
	{
		$GLOBALS['wp_rewrite']->pagination_base = 'view';
	}
// No Author Base
// The first part //
add_filter('author_rewrite_rules', 'no_author_base_rewrite_rules');
function no_author_base_rewrite_rules($author_rewrite) {
    global $wpdb;
    $author_rewrite = array();
    $authors = $wpdb->get_results("SELECT user_nicename AS nicename from $wpdb->users");   
    foreach($authors as $author) {
        $author_rewrite["({$author->nicename})/view/?([0-9]+)/?$"] = 'index.php?author_name=$matches[1]&paged=$matches[2]';
        $author_rewrite["({$author->nicename})/?$"] = 'index.php?author_name=$matches[1]';
    }  
    return $author_rewrite;
}
 
// The second part //
add_filter('author_link', 'no_author_base', 1000, 2);
function no_author_base($link, $author_id) {
    $link_base = trailingslashit(get_option('home'));
    $link = preg_replace("|^{$link_base}author/|", '', $link);
    return $link_base . $link;
}
