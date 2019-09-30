<?php
/*
Plugin Name: Page Template Plugin : Faculty and Staff
Plugin URI: http://www.wpexplorer.com/wordpress-page-templates-plugin/
Version: 1.1.0
Author: WPExplorer
Author URI: http://www.wpexplorer.com/
*/

class PageTemplater {
	/**
	 * A reference to an instance of this class.
	 */
	private static $instance;
	/**
	 * The array of templates that this plugin tracks.
	 */
	protected $templates;
	/**
	 * Returns an instance of this class.
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new PageTemplater();
		}
		return self::$instance;
	}
	/**
	 * Initializes the plugin by setting filters and administration functions.
	 */
	private function __construct() {
		$this->templates = array();
		// Add a filter to the attributes metabox to inject template into the cache.
		if ( version_compare( floatval( get_bloginfo( 'version' ) ), '4.7', '<' ) ) {
			// 4.6 and older
			add_filter(
				'page_attributes_dropdown_pages_args',
				array( $this, 'register_project_templates' )
			);
		} else {
			// Add a filter to the wp 4.7 version attributes metabox
			add_filter(
				'theme_page_templates', array( $this, 'add_new_template' )
			);
		}
		// Add a filter to the save post to inject out template into the page cache
		add_filter(
			'wp_insert_post_data',
			array( $this, 'register_project_templates' )
		);
		// Add a filter to the template include to determine if the page has our
		// template assigned and return it's path
		add_filter(
			'template_include',
			array( $this, 'view_project_template')
		);
		// Add your templates to this array.
		$this->templates = array(
			'faculty-staff.php' => 'Facuty Staff Page Template',
		);
		
		add_action( 'wp_enqueue_scripts', array( $this, 'add_css' ),50 );
		add_shortcode( 'print_NSCM_Dept_Staff', array($this,'NSCM_Dept_Staff'));
	}
	
	
	
	/**
	 * Adds our template to the page dropdown for v4.7+
	 *
	 */
	public function add_new_template( $posts_templates ) {
		$posts_templates = array_merge( $posts_templates, $this->templates );
		return $posts_templates;
	}
	/**
	 * Adds our template to the pages cache in order to trick WordPress
	 * into thinking the template file exists where it doens't really exist.
	 */
	public function register_project_templates( $atts ) {
		// Create the key used for the themes cache
		$cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );
		
		
		// Retrieve the cache list.
		// If it doesn't exist, or it's empty prepare an array
		$templates = wp_get_theme()->get_page_templates();
		if ( empty( $templates ) ) {
			$templates = array();
		}
		// New cache, therefore remove the old one
		wp_cache_delete( $cache_key , 'themes');
		// Now add our template to the list of templates by merging our templates
		// with the existing templates array from the cache.
		$templates = array_merge( $templates, $this->templates );
		// Add the modified cache to allow WordPress to pick it up for listing
		// available templates
		wp_cache_add( $cache_key, $templates, 'themes', 1800 );
		return $atts;
	}
	
	public function NSCM_Dept_Staff($atts){
	
		require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes'. DIRECTORY_SEPARATOR . 'db-config.php';
		require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes'.DIRECTORY_SEPARATOR . 'functions.php';
		
	
	$atts = shortcode_atts( array(
		'sub_dept' => 0
	), $atts);
	
	$sub_dept = intval($atts['sub_dept']);
	
	$result = NSCM_staff(37,$sub_dept);
	
	return print_staff($result,1);
	
}
	
	public static function add_css() {
			wp_enqueue_style( 'faculty_list_style', plugins_url( '/css/style.css' , __FILE__ ) );
			//wp_enqueue_script('faculty_list-script', '//ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js', array('jquery'), null, true);
		}
	
	/**
	 * Checks if the template is assigned to the page
	 */
	public function view_project_template( $template ) {
		// Return the search template if we're searching (instead of the template for the first result)
		if ( is_search() ) {
			return $template;
		}
		// Get global post
		global $post;
		// Return template if post is empty
		if ( ! $post ) {
			return $template;
		}
		// Return default template if we don't have a custom one defined
		if ( ! isset( $this->templates[get_post_meta(
			$post->ID, '_wp_page_template', true
		)] ) ) {
			return $template;
		}
		// Allows filtering of file path
		$filepath = apply_filters( 'page_templater_plugin_dir_path', plugin_dir_path( __FILE__ ) );

		$file =  $filepath . get_post_meta(
			$post->ID, '_wp_page_template', true
		);
		// Just to be safe, we check if the file exist first
		if ( file_exists( $file ) ) {
			return $file;
		} else {
			echo $file;
		}
		// Return template
		return $template;
	}
}
add_action( 'plugins_loaded', array( 'PageTemplater', 'get_instance' ) );





