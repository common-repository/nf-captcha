<?php if ( ! defined( 'ABSPATH' ) ) exit;
   /*
   Plugin Name: NF Captcha
   Plugin URI: http://macnetic-labs.de
   Description: A plugin to add Really Simple CAPTCHA. Includes the option for custom background-color.
   Text Domain: nf-captcha
   Domain Path: /lang/
   Version: 1.0
   Author: Jens Brunnert
   Author URI: http://macnetic-labs.de
   License: GPL2
   */

function nf_captcha_dependencies() {
	if ( !is_plugin_active( 'ninja-forms/ninja-forms.php' )) {
		add_action("admin_notices", function() {
			echo '<div class="error fade"><p>'. _e("Plugin 'NF Captcha' deactivated, because it requires the Ninja Forms plugin to be installed and active", "nf-captcha").'</p></div>';
		});
		deactivate_plugins( plugin_basename( __FILE__ ) );
		unset($_GET['activate']);
	} else if(!is_plugin_active( 'really-simple-captcha/really-simple-captcha.php' )) {
		add_action("admin_notices", function() {
			echo '<div class="error fade"><p>'. _e("Plugin 'NF Captcha' deactivated, because it requires the Really Simple CAPTCHA plugin to be installed and active", "nf-captcha").'</p></div>';
		});
		deactivate_plugins( plugin_basename( __FILE__ ) );
		unset($_GET['activate']);
	}
}
add_action( 'admin_init', 'nf_captcha_dependencies' );

function nf_captcha_extend_setup_license() {
	if ( class_exists( 'NF_Extension_Updater' ) ) {
		new NF_Extension_Updater( 'NF Captcha', '1.0', 'Jens Brunnert', __FILE__, 'option_prefix' );
	}
}
add_action( 'admin_init', 'nf_captcha_extend_setup_license' );

function nf_captcha_load_lang() {
	$textdomain = 'nf-captcha';
	$locale = apply_filters( 'plugin_locale', get_locale(), $textdomain );

	load_textdomain( $textdomain, WP_LANG_DIR . '/nf-captcha/' . $textdomain . '-' . $locale . '.mo' );

	load_plugin_textdomain( $textdomain, FALSE, dirname(plugin_basename(__FILE__)) . '/lang/' );
}
add_action( 'init', 'nf_captcha_load_lang');

function nf_captcha_scripts() {
	wp_enqueue_style( 'wp-color-picker' );

	wp_enqueue_script( 'nf-captcha-script', plugins_url('js/nf-captcha-script.js', __FILE__), array( 'wp-color-picker' ), false, true );
}
add_action('admin_enqueue_scripts', 'nf_captcha_scripts');

function nf_register_field_reallysimplecaptcha() {

	$args = array(
		'name' => __( 'really simple CAPTCHA', 'nf-captcha' ),
		'sidebar' => 'template_fields',
		'edit_function' => '',
		'display_function' => 'nf_captcha_display',
		'save_function' => '',
		'group' => 'standard_fields',
		'default_label' => __( 'Confirm that you are not a bot', 'nf-captcha' ),
		'edit_label' => true,
		'req' => true,
		'edit_label_pos' => true,
		'edit_req' => false,
		'edit_custom_class' => true,
		'edit_help' => true,
		'edit_meta' => false,
		'sidebar' => 'template_fields',
		'edit_conditional' => true,
		'conditional' => array(
			'value' => array(
				'type' => 'text',
			),
		),
		'edit_options' => array(
			array(
				'name' => 'spam_error',
				'type' => 'text',
				'label' => __( 'Error message', 'nf-captcha' ),
				'class' => 'widefat'
			),
			array(
				'name' => 'text_color',
				'type' => 'text',
				'label' => __( 'Textcolor', 'nf-captcha' ),
				'class' => 'color-field',
				'default' => '#000000',
			),
			array(
				'name' => 'background_color',
				'type' => 'text',
				'label' => __( 'Backgroundcolor', 'nf-captcha' ),
				'class' => 'color-field',
				'default' => '#ffffff',
			),
			array(
				'name' => 'chars',
				'type' => 'text',
				'label' => __( 'Chars', 'nf-captcha' ),
				'class' => 'widefat',
			),
			array(
				'name' => 'char_length',
				'type' => 'text',
				'label' => __( 'Char length', 'nf-captcha' ),
			),
			array(
				'name' => 'image_width',
				'type' => 'text',
				'label' => __( 'Image width', 'nf-captcha' ),
			),
			array(
				'name' => 'image_height',
				'type' => 'text',
				'label' => __( 'Image height', 'nf-captcha' ),
			),
			array(
				'name' => 'font_size',
				'type' => 'text',
				'label' => __( 'Font size', 'nf-captcha' ),
			),
			array(
				'name' => 'font_char_width',
				'type' => 'text',
				'label' => __( 'Font char witdh', 'nf-captcha' ),
			),
			array(
				'name' => 'image_type',
				'type' => 'select',
				'label' => __( 'Image type', 'nf-captcha' ),
				'options' => array(
					array('name' => 'png', 'value' => 'png'),
					array('name' => 'jpg/jpeg', 'value' => 'jpg'),
				),
			),
		),
		'limit' => 1,
		'display_label' => true,
		'process_field' => false,
		'pre_process' => 'nf_captcha_pre_process',

	);
	// show recaptcha field in admin only if site and secret key exists.
	if ( function_exists( 'ninja_forms_register_field' ) && class_exists('ReallySimpleCaptcha') ) {
		ninja_forms_register_field( '_reallysimplecaptcha', $args );
	}
}

add_action( 'init', 'nf_register_field_reallysimplecaptcha' );

function nf_captcha_display( $field_id, $data, $form_id = '' ) {
	$field_class = ninja_forms_get_field_class( $field_id, $form_id );
	if ( class_exists('ReallySimpleCaptcha') ) {
		$captcha_instance = new ReallySimpleCaptcha();
		$captcha_instance->cleanup();
		if(isset($data["background_color"]) && !empty($data["background_color"])) {
			$captcha_instance->bg = hex2rgb($data["background_color"]);
		}
		if(isset($data["text_color"]) && !empty($data["text_color"])) {
			$captcha_instance->fg = hex2rgb($data["text_color"]);
		}
		if(isset($data["chars"]) && !empty($data["chars"])) {
			$captcha_instance->chars = $data["chars"];
		}
		if(isset($data["char_length"]) && !empty($data["char_length"]) && is_numeric($data["char_length"])) {
			$captcha_instance->char_length = $data["char_length"];
		}
		if(isset($data["image_width"]) && !empty($data["image_width"]) && is_numeric($data["image_width"])) {
			$captcha_instance->img_size = array($data["image_width"], $captcha_instance->img_size[1]);
		}
		if(isset($data["image_height"]) && !empty($data["image_height"]) && is_numeric($data["image_height"])) {
			$captcha_instance->img_size = array($captcha_instance->img_size[0], $data["image_height"]);
		}
		if(isset($data["font_size"]) && !empty($data["font_size"]) && is_numeric($data["font_size"])) {
			$captcha_instance->font_size = $data["font_size"];
		}
		if(isset($data["font_char_width"]) && !empty($data["font_char_width"]) && is_numeric($data["font_char_width"])) {
			$captcha_instance->font_char_width = $data["font_char_width"];
		}
		if(isset($data["image_type"]) && !empty($data["image_type"])) {
			$captcha_instance->img_type = $data["image_type"];
		}
		$word = $captcha_instance->generate_random_word();
		$prefix = mt_rand();
		$image= $captcha_instance->generate_image( $prefix, $word );

		?>

		<input type="hidden" value="<?php echo serialize($prefix); ?>" id="nf_field_<?php echo $field_id;?>_prefix" name="ninja_forms_field_<?php echo $field_id;?>[prefix]" rel="<?php echo $field_id;?>_prefix" />
		<img id="nf_field_<?php echo $field_id;?>_captcha_image" src="<?php echo plugins_url("really-simple-captcha/tmp/".$image); ?>" alt="" />
		<input id="nf_field_<?php echo $field_id;?>" name="ninja_forms_field_<?php echo $field_id;?>[value]" type="text" class="<?php echo $field_class;?>" value="" rel="<?php echo $field_id;?>" />
		<?php
	}
}

function hex2rgb($colour, $default = "#fff") {
	if (strlen($colour) > 0 && $colour[0] == '#' ) {
		$colour = substr( $colour, 1 );
	}
	if ( strlen( $colour ) == 6 ) {
		list( $r, $g, $b ) = array( $colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5] );
	} elseif ( strlen( $colour ) == 3 ) {
		list( $r, $g, $b ) = array( $colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2] );
	} else {
		return hex2rgb($default);
	}
	$r = hexdec( $r );
	$g = hexdec( $g );
	$b = hexdec( $b );

	return array($r, $g, $b);
}

function nf_captcha_pre_process( $field_id, $user_value  ) {
	global $ninja_forms_processing;

	if ( class_exists('ReallySimpleCaptcha') ) {
		$captcha_instance = new ReallySimpleCaptcha();

		$field_row = ninja_forms_get_field_by_id($field_id);
		$field_data = $field_row['data'];
		$spam_error = $field_data['spam_error'];

		if ($ninja_forms_processing->get_action() != 'save' AND $ninja_forms_processing->get_action() != 'mp_save' AND !isset($_POST['_wp_login']) AND $captcha_instance->check(unserialize($user_value["prefix"]), $user_value["value"]) == FALSE) {
			if (is_object($ninja_forms_processing)) {
				if ($user_value != '') {
					$ninja_forms_processing->add_error('spam-general', $spam_error, 'general');
					$ninja_forms_processing->add_error('spam-' . $field_id, $spam_error, $field_id);
				}
			}
		}
		$captcha_instance->remove( $user_value["prefix"] );
	}
}