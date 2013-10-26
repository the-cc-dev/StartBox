<?php
/**
 * Creates Theme Options page and enqueues all necessary scripts
 *
 * @since 2.2.8
 */
function sb_admin_init() {

		// Setup our global admin variable
		global $sb_admin;

		// Create our settings page and add it to the menu
		$sb_admin = add_menu_page( __( 'StartBox Options', 'startbox' ), __( 'SBX', 'startbox'), 'edit_theme_options', 'sb_admin', 'sb_admin_page', '', '59' );

		// Register our custom settings field
		register_setting( 'sb_admin', THEME_OPTIONS, 'sb_sanitize');


		// Reset our theme options back to default
		if ( sb_get_option( 'reset' ) ) { 
			sb_set_default_options(); 
			wp_redirect( admin_url( 'themes.php?page=sb_admin&reset=true' ) ); 
		}

		// Load in our custom JS and help content
		add_action( 'load-' . $sb_admin, 'sb_admin_help' );

}
add_action( 'admin_menu', 'sb_admin_init' );


/**
 * Styles & Scripts
 */
function sb_admin_scripts() {

	global $sb_admin;

	// Admin styles
	//wp_enqueue_style( 'sb-admin', SB_CSS . '/admin.css' );
	wp_enqueue_style( 'sb-admin', SB_CSS . '/admin.min.css' );

	// Required scripts
	wp_enqueue_script( 'common' );
	wp_enqueue_script( 'wp-lists' );
	wp_enqueue_script( 'postbox' );

}
add_action( 'admin_enqueue_scripts', 'sb_admin_scripts' );


/**
 * Add a menu item for Theme Options to the admin bar
 */
function sb_admin_bar_init() {
	global $wp_admin_bar;

	$wp_admin_bar->add_menu(
		array(
			'id' => 'theme-options',
			'parent' => 'appearance',
			'title' => __( 'SBX Settings', 'startbox' ),
			'href' => admin_url( 'admin.php?page=sb_admin' )
			)
	);

}
add_action( 'wp_before_admin_bar_render', 'sb_admin_bar_init' );


/**
 * Adds contextual help for all StartBox Options
 */
function sb_admin_help() {
	global $sb_admin, $wp_version;

	// Make sure we're on at least WP3.3
	if ( version_compare( $wp_version, '3.3', '>=' ) ) {
		$screen = get_current_screen();

		// Don't add help tab if screen is not sb_admin
		if ( $screen->id != $sb_admin ) return;

		// Grab our theme options
		global $sb_settings_factory;
		$defaults = $theme_options = get_option( THEME_OPTIONS );
		$settings = $sb_settings_factory->settings;

		// Add our generic helper text no matter what
		$screen->add_help_tab( array(
			'id'		=> 'sb_need_help',
			'title'		=> __( 'Additional Resources', 'startbox' ),
			'content'	=> __( '<h3>Additional Resources</h3>', 'startbox' ) . '<p>' . sprintf( __( 'For more information, try the %s or %s.', 'startbox' ), '<a href="' . apply_filters( 'sb_theme_docs', 'http://docs.wpstartbox.com' ) . '" target="_blank">' . __( 'Theme Documentation', 'startbox') . '</a>',  '<a href="' . apply_filters( 'sb_theme_support', 'http://wpstartbox.com/support/' ) . '" target="_blank" >' . __( 'Support Forum', 'startbox' ) . '</a>' ) . '</p>'
		) );

		// Loop through each option panel
		foreach ( $settings as $setting ) {

			// Only include options panels that have a description set
			if ( isset($setting->description) ) {
				$output = '';
				$output .= '<h3>' . $setting->name . '</h3>';
				$output .= '<p>' . $setting->description . '</p>';

				// loop through each individual option to find help text, include it in output if found
				$options = $setting->options;
				foreach( $options as $option_id => $option ) {
					if ( isset( $option['help'] ) )
						$output .= '<p style="padding:8px 0; margin:0; border-top:1px solid #eee;"><strong>' .  rtrim( $option['label'], ':' ) . '</strong> &ndash; ' . $option['help'] . '</p>';
				}

				// Add the help tab
				$screen->add_help_tab( array(
					'id'		=> $setting->slug,
					'title'		=> $setting->name,
					'content'	=> $output,
				) );
			} // end if isset
		} // end foreach
	} // end if version compare

}


/**
 * Admin Metaboxes
 */
function sb_admin_page() { 

	global $sb_admin; ?>

	<div class="wrap sbx-metaboxes">
		<form method="post" action="options.php" id="sb_options">
		<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
		<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
		<?php settings_fields( 'sb_admin' ); ?>
		<?php 
			// Notification nag
			if ( isset( $_REQUEST['settings-updated'] ) && $_REQUEST['settings-updated'] == true ) {
				echo '<div id="message" class="updated fade"><p>' . THEME_NAME . ' ' . __( 'Options Updated.', 'startbox' ) . '</p></div>';
			}
		?>
		<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
		<p class="buttons"><?php sb_admin_buttons(); ?></p>

		<div class="metabox-holder">
			<div id="postbox-container-1" class="postbox-container">
				<?php do_meta_boxes( $sb_admin, 'primary', null ); ?>
			</div><!-- .postbox-container -->
			<div id="postbox-container-2" class="postbox-container">
				<?php do_meta_boxes( $sb_admin, 'secondary', null ); ?>
			</div><!-- .postbox-container -->
		</div><!-- .metabox-holder -->

		<p class="buttons"><?php sb_admin_buttons(); ?></p>

		</form><!-- #sb_options -->
	</div><!-- .sbx-metaboxes -->
	<script type="text/javascript">
		//<![CDATA[
		jQuery(document).ready(function($) {
			// close postboxes that should be closed
			$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
			// postboxes setup
			postboxes.add_postbox_toggles('toplevel_page_sbx_admin');
		});
		//]]>
	</script>
<?php }


/**
 * Save & Reset Buttons
 */
function sb_admin_buttons() { ?>
	<input type="submit" name="Submit" value="<?php _e( 'Save All Settings', 'startbox' ) ?>" class="button-primary" />
	<input type="submit" name="<?php echo esc_attr( THEME_OPTIONS . '[reset]' ); ?>" value="<?php _e( 'Reset All Settings', 'startbox' ); ?>" class="button" onclick="if( confirm( 'Reset All Theme Settings?' ) ) return true; else return false;" />
<?php }


/**
 * Perform some basic sanitization to our options on save
 */
function sb_sanitize($inputs) {

	global $sb_settings_factory;

	$settings = $sb_settings_factory->settings;

	foreach ( $settings as $setting ) {
		$options = $setting->options;
		foreach ( $options as $option_id => $option ) {

			// Forcefully set unchecked checkboxes to false so they retain a vailue when unchecked
			if ($option['type'] == 'checkbox' && !isset($inputs[$option_id])) {
				$inputs[$option_id] = false;
			}
			// Basic KSES sanitization
			if ( isset( $option['kses'] ) ) {
				if ( true === $option['kses'] || ( is_string( $option['kses'] ) && !current_user_can( $option['kses'] ) ) )
					$inputs[$option_id] = wp_kses_post( $inputs[$option_id] );
			}
			// Sanitize untrusted textual inputs. Defaults to true. Set 'sanitize' => false for no satitization, or use 'sanitize' => array( 'allowed_html' => '', 'allowed_protocols' => '' ) to allow specific tags.
			if ( ( $option['type'] == 'text' || $option['type'] == 'textarea') && ( isset($option['sanitize']) && $option['sanitize'] != false ) ) {
				$inputs[$option_id] = wp_kses( $inputs[$option_id], $option['sanitize']['allowed_html'], ( empty ( $option['sanitize']['allowed_protocols']) ? array() : $option['sanitize']['allowed_protocols'] ) );
			}

		}
	}

	return $inputs;
}