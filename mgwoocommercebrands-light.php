<?php
/*
Plugin Name: Ultimate WooCommerce Brands
Plugin URI: https://magniumthemes.com/
Description: Add Brands taxonomy for products from WooCommerce plugin.
Version: 2.0
Author: MagniumThemes
Author URI: https://magniumthemes.com/
Copyright MagniumThemes.com. All rights reserved.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* Register hook */
if ( ! class_exists( 'mgwoocommercebrands' ) ) {
	require_once WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . "ultimate-woocommerce-brands" . DIRECTORY_SEPARATOR . "mgwoocommercebrands-widget-brands-list.php";
}

class MGWB {

	private $brands_output = '';

	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'ob_install' ) );
		register_deactivation_hook( __FILE__, array( $this, 'ob_uninstall' ) );

		/**
		 * add action of plugin
		 */
		add_action( 'init', array( $this, 'register_brand_taxonomy'));
		add_action( 'init', array( $this, 'init_brand_taxonomy_meta'));

		add_action( 'admin_init', array( $this, 'obScriptInit' ) );
		add_action( 'init', array( $this, 'obScriptInitFrontend' ) );

		add_action( 'woocommerce_before_single_product', array( $this, 'single_product' ) );
		add_action( 'woocommerce_before_shop_loop_item', array( $this, 'categories_product' ) );
		add_action( 'widgets_init', array( $this, 'mgwoocommercebrands_register_widgets' ) );

		/*Setting*/
		add_action( 'plugins_loaded', array( $this, 'init_mgwoocommercebrands' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

	}

	/**
	 * This is an extremely useful function if you need to execute any actions when your plugin is activated.
	 */
	function ob_install() {
		global $wp_version;
		If ( version_compare( $wp_version, "2.9", "<" ) ) {
			deactivate_plugins( basename( __FILE__ ) ); // Deactivate our plugin
			wp_die( "This plugin requires WordPress version 2.9 or higher." );
		}

	}

	/**
	 * This function is called when deactive.
	 */
	function ob_uninstall() {
		//do something
	}

	/**
	 * Function set up include javascript, css.
	 */
	function obScriptInit() {
		wp_enqueue_style( 'mgwb-style-admin', plugin_dir_url( '' ) . basename( dirname( __FILE__ ) ) . '/css/mgwoocommercebrands-admin.css' );
	}

	function obScriptInitFrontend() {
		wp_enqueue_script( 'mgwb-script-frontend', plugin_dir_url( '' ) . basename( dirname( __FILE__ ) ) . '/js/mgwoocommercebrands.js', array(), false, true );
		wp_enqueue_style( 'mgwb-style-frontend', plugin_dir_url( '' ) . basename( dirname( __FILE__ ) ) . '/css/mgwoocommercebrands.css' );
	}

	/**
	 * This function register custom Brand taxonomy
	 */
	function register_brand_taxonomy() {

		$labels = array(
			'name' => esc_html__( 'Brands', 'mgwoocommercebrands' ),
			'singular_name' => esc_html__( 'Brand', 'mgwoocommercebrands' ),
			'search_items' =>  esc_html__( 'Search Brands', 'mgwoocommercebrands' ),
			'all_items' => esc_html__( 'All Brands', 'mgwoocommercebrands' ),
			'parent_item' => esc_html__( 'Parent Brand', 'mgwoocommercebrands' ),
			'parent_item_colon' => esc_html__( 'Parent Brands:', 'mgwoocommercebrands' ),
			'edit_item' => esc_html__( 'Edit Brands', 'mgwoocommercebrands' ),
			'update_item' => esc_html__( 'Update Brands', 'mgwoocommercebrands' ),
			'add_new_item' => esc_html__( 'Add New Brand', 'mgwoocommercebrands' ),
			'new_item_name' => esc_html__( 'New Brand Name', 'mgwoocommercebrands' ),
			'menu_name' => esc_html__( 'Brands', 'mgwoocommercebrands' ),
		);

	    register_taxonomy("product_brand",
	     array("product"),
	     array(
		     'hierarchical' => true,
		     'labels' => $labels,
		   	 'show_ui' => true,
    		 'query_var' => true,
		     'rewrite' => array( 'slug' => 'brands', 'with_front' => true ),
		     'show_admin_column' => true
	     ));
	}

	/**
	 * This function init custom Brand taxonomy meta fields
	 */
	function init_brand_taxonomy_meta() {
		$prefix = 'mgwb_';

		$config = array(
			'id' => 'mgwb_box',          // meta box id, unique per meta box
			'title' => esc_html__('Brands settings', 'mgwoocommercebrands'),          // meta box title
			'pages' => array('product_brand'),        // taxonomy name, accept categories, post_tag and custom taxonomies
			'context' => 'normal',            // where the meta box appear: normal (default), advanced, side; optional
			'fields' => array(),            // list of meta fields (can be added by field arrays)
			'local_images' => false,          // Use local or hosted images (meta box images for add/remove)
			'use_with_theme' => false          //change path if used with theme set to true, false for a plugin or anything else for a custom path(default false).
		);

	}
	/**
	 * This function is run when go to product detail
	 */
	function single_product( $post_ID ) {

		global $post;
		global $wp_query;

		$product_id = $post->ID;

		@$where_show = get_option( 'mgb_where_show' );
		@$ob_show_image = get_option( 'mgb_show_image' );

		if(isset($_GET['ob_show_image'])) {
			$ob_show_image = intval($_GET['mgb_show_image']);
		}

		@$ob_brand_title = get_option( 'mgb_brand_title' );

		if ( $where_show == 1 ) {
			return;
		}
		if ( is_admin() || ! $wp_query->post->ID ) {
			return;
		}

		$brands_list =  wp_get_object_terms($product_id, 'product_brand');

		$brands_list_output = '';
		$brand_image_output = '';
		$brands_list_comma = ', ';
		$i = 0;

		foreach ( $brands_list as $brand ) {

				$brands_list_output .= '<a href="'.get_term_link( $brand->slug, 'product_brand' ).'">'.$brand->name.'</a>';

				if($i < count($brands_list) - 1) {
					$brands_list_output .= $brands_list_comma;
				}

				$i++;

		}

		if(count($brands_list) > 0) {

			if(!empty($ob_brand_title)) {
				$show = '<span class="mg-brand-wrapper mg-brand-wrapper-product"><strong>'.esc_html($ob_brand_title).'</strong> '.wp_kses_post($brands_list_output).'</span>';
			} else {
				$show = '<span class="mg-brand-wrapper mg-brand-wrapper-product">'.wp_kses_post($brands_list_output).'</span>';
			}

			$this->brands_output = $show;

			$brand_position = get_option( 'mgb_detail_position', 0 );

			$brand_position_priority = array(
				'above_title' => 3,
				'below_title' => 7,
				'below_price' => 12,
				'below_excerpt' => 25,
				'below_addtocart' => 35
			);

			if(!empty($brand_position_priority[$brand_position])) {
				add_action( 'woocommerce_single_product_summary', array($this, 'display_brand_title'), $brand_position_priority[$brand_position] );
			}
		}

	}

	/**
	 * Display brand title
	 */
	function display_brand_title($title) {
	    echo wp_kses_post($this->brands_output);
	}

	/**
	 * This function is run on categories pages
	 */
	function categories_product() {
		global $post;

		@$where_show = get_option( 'mgb_where_show' );

		if ( $where_show == 2 ) {
			return;
		}
		if ( is_admin() || ! $post->ID ) {
			return;
		}

		$product_id = $post->ID;

		$brands_list =  wp_get_object_terms($product_id, 'product_brand');

		$brands_list_output = '';
		$brands_list_comma = ', ';
		$i = 0;

		foreach ( $brands_list as $brand ) {

			$brands_list_output .= '<a href="'.get_term_link( $brand->slug, 'product_brand' ).'">'.$brand->name.'</a>';

			if($i < count($brands_list) - 1) {
				$brands_list_output .= $brands_list_comma;
			}

			$i++;
		}

		if(count($brands_list) > 0) {

			$show = '<span class="mg-brand-wrapper mg-brand-wrapper-category">'.wp_kses_post($brands_list_output).'</span>';

			$this->brands_output = $show;

			$brand_position = get_option( 'mgb_category_position', 0 );

			$brand_position_priority = array(
				'above_title' => 8, // woocommerce_shop_loop_item_title
				'below_title' => 12, // woocommerce_shop_loop_item_title
				'below_price' => 12 // woocommerce_after_shop_loop_item_title
			);

			switch ($brand_position) {
				case 'below_price':
					$woocommerce_action = 'woocommerce_after_shop_loop_item_title';
					break;
				default:
					$woocommerce_action = 'woocommerce_shop_loop_item_title';
					break;
			}

			if(!empty($brand_position_priority[$brand_position])) {

				add_action( $woocommerce_action, array($this, 'display_brand_title'), $brand_position_priority[$brand_position] );
			}
		}
	}

	/**
	 * Register widget
	 */
	function mgwoocommercebrands_register_widgets() {
		register_widget( 'mgwoocommercebrands_list_widget' );
	}

	/**
	 * Init when plugin load
	 */
	function init_mgwoocommercebrands() {
		load_plugin_textdomain( 'mgwoocommercebrands' );
		$this->load_plugin_textdomain();
		require_once( 'mgwoocommercebrands-admin.php' );
		$init = new mgwoocommercebrandsadmin();
	}

	/*Load Language*/
	function replace_mgwoocommercebrands_default_language_files() {

		$locale = apply_filters( 'plugin_locale', get_locale(), 'mgwoocommercebrands' );

		return WP_PLUGIN_DIR . "/ultimate-woocommerce-brands/languages/mgwoocommercebrands-$locale.mo";

	}

	/**
	 * Function load language
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'mgwoocommercebrands' );

		// Admin Locale
		if ( is_admin() ) {
			load_textdomain( 'mgwoocommercebrands', WP_PLUGIN_DIR . "/ultimate-woocommerce-brands/languages/mgwoocommercebrands-$locale.mo" );
		}

		// Global + Frontend Locale
		load_textdomain( 'mgwoocommercebrands', WP_PLUGIN_DIR . "/ultimate-woocommerce-brands/languages/mgwoocommercebrands-$locale.mo" );
		load_plugin_textdomain( 'mgwoocommercebrands', false, WP_PLUGIN_DIR . "/ultimate-woocommerce-brands/languages/" );
	}

	/*
	 * Function Setting link in plugin manager
	 */

	public function plugin_action_links( $links ) {
		$action_links = array(
			'settings'	=>	'<a href="admin.php?page=wc-settings&tab=mgwoocommercebrands" title="' . __( 'Settings', 'mgwoocommercebrands' ) . '">' . __( 'Settings', 'mgwoocommercebrands' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}

	public function plugin_row_meta( $links, $file ) {
		if ( $file == plugin_basename( __FILE__ ) ) {
			$row_meta = array(
				'getpro'	=>	'<a href="http://codecanyon.net/item/ultimate-woocommerce-brands-plugin/9433984/?ref=dedalx" target="_blank" style="color: blue;font-weight:bold;">' . __( 'Get PRO version', 'mgwoocommercebrands' ) . '</a>',
				'about'	=>	'<a href="http://magniumthemes.com/" target="_blank" style="color: red;font-weight:bold;">' . __( 'Premium WordPress themes', 'mgwoocommercebrands' ) . '</a>',
			);

			return array_merge( $links, $row_meta );
		}

		return (array) $links;
	}

}

$mgwoocommercebrands = new MGWB();
?>
