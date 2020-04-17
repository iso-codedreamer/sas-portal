<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/admin
 * @author     Your Name <email@example.com>
 */
class SAS_Portal_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/sas-portal-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/sas-portal-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * custom option and settings
	 */
	public static function sasportal_settings_init() {
		// register a new setting for "SAS Portal" page
		register_setting( 'sasportal', 'sasportal_options' );

		// register a new section in the "sasportal" page
		add_settings_section(
			'sasportal_section_developers',
			__( 'Main portal configuration', 'sasportal' ),
			'SAS_Portal_Admin::sasportal_section_developers_cb',
			'sasportal'
		);

		// register a new field in the "sasportal_section_developers" section, inside the "sasportal" page
		add_settings_field(
			'senderid',
			__( 'SMS SenderId', 'sasportal' ),
			'SAS_Portal_Admin::sasportal_textfield_cb',
			'sasportal',
			'sasportal_section_developers',
			[
				'label_for' => 'senderid',
				'class' => 'sasportal_row',
				'sasportal_custom_data' => 'custom',
			]
		);
		add_settings_field(
			'auth',
			__( 'API Authentication', 'sasportal' ),
			'SAS_Portal_Admin::sasportal_textfield_cb',
			'sasportal',
			'sasportal_section_developers',
			[
				'label_for' => 'auth',
				'class' => 'sasportal_row',
				'sasportal_custom_data' => 'custom',
			]
		);
		add_settings_field(
			'apiurl',
			__( 'API URL', 'sasportal' ),
			'SAS_Portal_Admin::sasportal_textfield_cb',
			'sasportal',
			'sasportal_section_developers',
			[
				'label_for' => 'apiurl',
				'class' => 'sasportal_row',
				'sasportal_custom_data' => 'custom',
			]
		);
		add_settings_field(
			'expiry',
			__( 'Password expiry (mins)', 'sasportal' ),
			'SAS_Portal_Admin::sasportal_textfield_cb',
			'sasportal',
			'sasportal_section_developers',
			[
				'label_for' => 'expiry',
				'class' => 'sasportal_row',
				'sasportal_custom_data' => 'custom',
			]
		);
		add_settings_field(
			'template',
			__( 'SMS template #pass', 'sasportal' ),
			'SAS_Portal_Admin::sasportal_textfield_cb',
			'sasportal',
			'sasportal_section_developers',
			[
				'label_for' => 'template',
				'class' => 'sasportal_row',
				'sasportal_custom_data' => 'custom',
			]
		);

	}

	public static function sasportal_section_developers_cb( $args ) {
		return;
		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Follow the white rabbit.', 'sasportal' ); ?></p>
		<?php
	}

	public static function sasportal_textfield_cb( $args ) {
		// get the value of the setting we've registered with register_setting()
		$options = get_option( 'sasportal_options' );
		if(empty($options)) $options = array();
		// output the field
		?>
		<input type="text" name="sasportal_options[<?php echo esc_attr($args['label_for']); ?>]"
		       value="<?php echo esc_attr($options[$args['label_for']]); ?>"/>
		<?php
	}

	public static function sasportal_options_page() {
		// add top level menu page
		add_menu_page(
			'SAS Portal Options',
			'SAS Portal',
			'manage_options',
			'sasportal',
			'SAS_Portal_Admin::sasportal_options_page_html'
		);
	}



	/**
	 * top level menu:
	 * callback functions
	 */
	public static function sasportal_options_page_html() {
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// add error/update messages

		// check if the user have submitted the settings
		// wordpress will add the "settings-updated" $_GET parameter to the url
		if ( isset( $_GET['settings-updated'] ) ) {
			// add settings saved message with the class of "updated"
			add_settings_error( 'sasportal_messages', 'sasportal_message', __( 'Settings Saved', 'sasportal' ), 'updated' );
		}

		// show error/update messages
		settings_errors( 'sasportal_messages' );
		?>
		<div class="wrap">
 <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
 <form action="options.php" method="post">
 <?php
 // output security fields for the registered setting "sasportal"
 settings_fields( 'sasportal' );
 // output setting sections and their fields
 // (sections are registered for "sasportal", each field is registered to a specific section)
 do_settings_sections( 'sasportal' );
 // output save settings button
 submit_button( 'Save Settings' );
 ?>
 </form>
 </div>
		<?php
	}


}
