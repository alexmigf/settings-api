<?php
/**
 * Main Class file for creating a settings page.
 *
 * @package Eighteen73
 */

namespace Eighteen73\SettingsApi;

/**
 * Main Class file for creating a settings page.
 */
class SettingsApi {

	/**
	 * Page title for the settings page.
	 *
	 * @var string
	 */
	private $page_title;

	/**
	 * Menu title for the settings page.
	 *
	 * @var string
	 */
	private $menu_title;

	/**
	 * Capability for the settings page.
	 *
	 * @var string
	 */
	private $capability;

	/**
	 * Slug for the menu page.
	 *
	 * @var string
	 */
	private $icon_url = '';

	/**
	 * Slug for the settings page.
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * Custom callback for the settings page.
	 *
	 * @var array
	 */
	private $callback;

	/**
	 * Menu position for the settings page.
	 *
	 * @var int|null
	 */
	private $position;

	/**
	 * If it's a top level menu.
	 *
	 * @var bool
	 */
	private $top_level;

	/**
	 * Submenus array.
	 *
	 * @var array
	 */
	private $submenus_array = [];

	/**
	 * Sections array.
	 *
	 * @var array
	 */
	private $sections_array = [];

	/**
	 * Fields array.
	 *
	 * @var array
	 */
	private $fields_array = [];

	/**
	 * Constructor.
	 *
	 * @param string   $page_title  Page title for the settings page.
	 * @param string   $menu_title  Menu title for the settings page.
	 * @param string   $capability  Capability for the settings page.
	 * @param string   $slug        Slug for the settings page.
	 * @param int|null $position    Menu position for the settings page.
	 * @param bool     $top_level   If it's a top level menu.
	 */
	public function __construct( $page_title, $menu_title, $capability, $slug, $callback = [], $position = null, $top_level = false, $icon_url = '' ) {

		// Set variables.
		$this->page_title = esc_attr( $page_title );
		$this->menu_title = esc_attr( $menu_title );
		$this->capability = esc_attr( $capability );
		$this->slug       = esc_attr( $slug );
		$this->callback   = $callback;
		$this->position   = ! empty( $position ) ? intval( $position ) : null;
		$this->top_level  = $top_level;
		$this->icon_url   = esc_attr( $icon_url );

		// Enqueue the admin scripts.
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );

		// Hook it up.
		add_action( 'admin_init', [ $this, 'admin_init' ] );

		// Menu.
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );

		// Submenus.
		add_action( 'admin_menu', [ $this, 'admin_submenus' ] );
	}

	/**
	 * Admin Scripts.
	 *
	 * @return void
	 */
	public function admin_scripts() {
		// jQuery is needed.
		wp_enqueue_script( 'jquery' );

		// Color Picker.
		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NoExplicitVersion
		wp_enqueue_script(
			'iris',
			admin_url( 'js/iris.min.js' ),
			[ 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ],
			false,
			true,
		);

		// Media Uploader.
		wp_enqueue_media();
	}

	/**
	 * Set Sections.
	 *
	 * @param array $sections The settings page sections.
	 */
	public function set_sections( $sections ) {
		// Bail if not array.
		if ( ! is_array( $sections ) ) {
			return false;
		}

		// Assign to the sections array.
		$this->sections_array = $sections;

		return $this;
	}

	/**
	 * Add a single section.
	 *
	 * @param array $section The settings page sections.
	 */
	public function add_section( $section ) {
		// Bail if not array.
		if ( ! is_array( $section ) ) {
			return false;
		}

		// Assign the section to sections array.
		$this->sections_array[] = $section;

		return $this;
	}

	/**
	 * Set Fields.
	 *
	 * @param array $fields The settings page fields.
	 */
	public function set_fields( $fields ) {
		// Bail if not array.
		if ( ! is_array( $fields ) ) {
			return false;
		}

		// Assign the fields.
		$this->fields_array = $fields;

		return $this;
	}

	/**
	 * Add a single field.
	 *
	 * @param string $section The settings page section to add the field to.
	 * @param array  $field_array Options to pass for the field.
	 */
	public function add_field( $section, $field_array ) {
		// Set the defaults
		$defaults = [
			'id'   => '',
			'name' => '',
			'desc' => '',
			'type' => 'text',
		];

		// Combine the defaults with user's arguements.
		$arg = wp_parse_args( $field_array, $defaults );

		// Each field is an array named against its section.
		$this->fields_array[ $section ][] = $arg;

		return $this;
	}

	/**
	 * Initialize API.
	 *
	 * Initializes and registers the settings sections and fields.
	 * Usually this should be called at `admin_init` hook.
	 */
	public function admin_init() {
		/**
		 * Register the sections.
		 *
		 * Sections array is like this:
		 *
		 * $sections_array = [
		 *   $section_array,
		 *   $section_array,
		 *   $section_array,
		 * ];
		 *
		 * Section array is like this:
		 *
		 * $section_array = [
		 *   'id'    => 'section_id',
		 *   'title' => 'Section Title'
		 * ];
		 */
		foreach ( $this->sections_array as $section ) {
			if ( false === get_option( $section['id'] ) ) {
				// Add a new field as section ID.
				add_option( $section['id'] );
			}

			// Deals with sections description.
			if ( isset( $section['desc'] ) && ! empty( $section['desc'] ) ) {
				// Build HTML.
				$section['desc'] = '<div class="inside">' . $section['desc'] . '</div>';

				// Create the callback for description.
				$callback = function() use ( $section ) {
					echo str_replace( '"', '\"', $section['desc'] );
				};

			} elseif ( isset( $section['callback'] ) ) {
				$callback = $section['callback'];
			} else {
				$callback = null;
			}

			/**
			 * Add a new section to a settings page.
			 *
			 * @param string $id
			 * @param string $title
			 * @param callable $callback
			 * @param string $page | Page is same as section ID.
			 */
			add_settings_section( $section['id'], $section['title'], $callback, $section['id'] );
		} // foreach ended.

		/**
		 * Register settings fields.
		 *
		 * Fields array is like this:
		 *
		 * $fields_array = [
		 *   $section => $field_array,
		 *   $section => $field_array,
		 *   $section => $field_array,
		 * ];
		 *
		 *
		 * Field array is like this:
		 *
		 * $field_array = [
		 *   'id'   => 'id',
		 *   'name' => 'Name',
		 *   'type' => 'text',
		 * ];
		 */
		foreach ( $this->fields_array as $section => $field_array ) {
			foreach ( $field_array as $field ) {
				// ID.
				$id = isset( $field['id'] ) ? $field['id'] : false;

				// Type.
				$type = isset( $field['type'] ) ? $field['type'] : 'text';

				// Name.
				$name = isset( $field['name'] ) ? $field['name'] : 'No Name Added';

				// Label for.
				$label_for = "{$section}[{$field['id']}]";

				// Description.
				$description = isset( $field['desc'] ) ? $field['desc'] : '';

				// Size.
				$size = isset( $field['size'] ) ? $field['size'] : null;

				// Options.
				$options = isset( $field['options'] ) ? $field['options'] : '';

				// Standard default value.
				$default = isset( $field['default'] ) ? $field['default'] : '';

				// Standard default placeholder.
				$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';

				// Sanitize Callback.
				$sanitize_callback = isset( $field['sanitize_callback'] ) ? $field['sanitize_callback'] : '';

				$args = [
					'id'                => $id,
					'type'              => $type,
					'name'              => $name,
					'label_for'         => $label_for,
					'desc'              => $description,
					'section'           => $section,
					'size'              => $size,
					'options'           => $options,
					'std'               => $default,
					'placeholder'       => $placeholder,
					'sanitize_callback' => $sanitize_callback,
				];

				/**
				 * Add a new field to a section of a settings page.
				 *
				 * @param string   $id
				 * @param string   $title
				 * @param callable $callback
				 * @param string   $page
				 * @param string   $section = 'default'
				 * @param array    $args = [)
				 */

				// @param string 	$id
				$field_id = $section . '[' . $field['id'] . ']';

				add_settings_field(
					$field_id,
					$name,
					[ $this, 'callback_' . $type ],
					$section,
					$section,
					$args
				);
			} // foreach ended.
		} // foreach ended.

		// Creates our settings in the fields table.
		foreach ( $this->sections_array as $section ) {
			/**
			 * Registers a setting and its sanitization callback.
			 *
			 * @param string $field_group   | A settings group name.
			 * @param string $field_name    | The name of an option to sanitize and save.
			 * @param callable  $sanitize_callback = ''
			 */
			register_setting( $section['id'], $section['id'], [ $this, 'sanitize_fields' ] );
		} // foreach ended.

	} // admin_init() ended.

	/**
	 * Sanitize callback for Settings API fields.
	 *
	 * @param array $fields The fields array to sanitize.
	 *
	 * @return array
	 */
	public function sanitize_fields( $fields ) {
		foreach ( $fields as $field_slug => $field_value ) {
			$sanitize_callback = $this->get_sanitize_callback( $field_slug );

			// If callback is set, call it.
			if ( $sanitize_callback ) {
				$fields[ $field_slug ] = call_user_func( $sanitize_callback, $field_value );
				continue;
			}
		}

		return $fields;
	}

	/**
	 * Get sanitization callback for given option slug
	 *
	 * @param string $slug option slug.
	 * @return mixed string | bool false
	 */
	public function get_sanitize_callback( $slug = '' ) {
		if ( empty( $slug ) ) {
			return false;
		}

		// Iterate over registered fields and see if we can find proper callback.
		foreach ( $this->fields_array as $section => $field_array ) {
			foreach ( $field_array as $field ) {
				if ( $field['name'] !== $slug ) {
					continue;
				}

				// Return the callback name.
				return isset( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ? $field['sanitize_callback'] : false;
			}
		}

		return false;
	}

	/**
	 * Get field description for display
	 *
	 * @param array $args settings field args
	 *
	 * @return string
	 */
	public function get_field_description( $args ) {
		if ( ! empty( $args['desc'] ) ) {
			$desc = sprintf(
				'<p class="description">%s</p>',
				is_callable( $args['desc'] )
					? call_user_func( $args['desc'] )
					: $args['desc']
			);
		} else {
			$desc = '';
		}

		return $desc;
	}

	/**
	 * Displays a title field for a settings field
	 *
	 * @param array $args settings field args
	 *
	 * @return void
	 */
	public function callback_title( $args ) {
		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		if ( '' !== $args['name'] ) {
			$name = $args['name'];
		}
		$type = isset( $args['type'] ) ? $args['type'] : 'title';

		$html = '';

		echo $html;
	}

	/**
	 * Displays a text field for a settings field
	 *
	 * @param array $args settings field args
	 */
	public function callback_text( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'], $args['placeholder'] ) );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
		$type  = isset( $args['type'] ) ? $args['type'] : 'text';

		$html  = sprintf( '<input type="%1$s" class="%2$s-text" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"placeholder="%6$s"/>', $type, $size, $args['section'], $args['id'], $value, $args['placeholder'] );
		$html .= $this->get_field_description( $args );

		echo $html;
	}

	/**
	 * Displays a url field for a settings field
	 *
	 * @param array $args settings field args
	 */
	public function callback_url( $args ) {
		$this->callback_text( $args );
	}

	/**
	 * Displays an email field for a settings field
	 *
	 * @param array $args settings field args
	 */
	public function callback_email( $args ) {
		$this->callback_text( $args );
	}

	/**
	 * Displays a number field for a settings field
	 *
	 * @param array $args settings field args
	 */
	public function callback_number( $args ) {
		$this->callback_text( $args );
	}

	/**
	 * Displays a checkbox for a settings field
	 *
	 * @param array $args settings field args
	 */
	public function callback_checkbox( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );

		$html  = '<fieldset>';
		$html .= sprintf( '<label for="' . $this->slug . '%1$s[%2$s]">', $args['section'], $args['id'] );
		$html .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="off" />', $args['section'], $args['id'] );
		$html .= sprintf( '<input type="checkbox" class="checkbox" id="' . $this->slug . '-%1$s[%2$s]" name="%1$s[%2$s]" value="on" %3$s />', $args['section'], $args['id'], checked( $value, 'on', false ) );
		$html .= sprintf( '%1$s</label>', $args['desc'] );
		$html .= '</fieldset>';

		echo $html;
	}

	/**
	 * Displays a multicheckbox a settings field
	 *
	 * @param array $args settings field args
	 */
	public function callback_multicheck( $args ) {

		$value = $this->get_option( $args['id'], $args['section'], $args['std'] );

		$html = '<fieldset>';
		foreach ( $args['options'] as $key => $item ) {
			$value   = $this->get_option( $args['id'], $args['section'], $args['std'] );
			$label   = is_array( $item ) ? $item['label'] : $item;
			$checked = isset( $value[ $key ] ) ? $value[ $key ] : '0';

			$html   .= sprintf( '<label for="' . $this->slug . '-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key );
			$html   .= sprintf( '<input type="checkbox" class="checkbox" id="' . $this->slug . '-%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $checked, $key, false ) );
			$html   .= sprintf( '%1$s</label><br>', $label );
			$html   .= $this->get_field_description( $item );
		}
		$html .= $this->get_field_description( $args );
		$html .= '</fieldset>';

		echo $html;
	}

	/**
	 * Displays a multicheckbox a settings field
	 *
	 * @param array $args settings field args
	 */
	public function callback_radio( $args ) {

		$value = $this->get_option( $args['id'], $args['section'], $args['std'] );

		$html = '<fieldset>';
		foreach ( $args['options'] as $key => $label ) {
			$html .= sprintf( '<label for="' . $this->slug . '-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key );
			$html .= sprintf( '<input type="radio" class="radio" id="' . $this->slug . '-%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $value, $key, false ) );
			$html .= sprintf( '%1$s</label><br>', $label );
		}
		$html .= $this->get_field_description( $args );
		$html .= '</fieldset>';

		echo $html;
	}

	/**
	 * Displays a selectbox for a settings field
	 *
	 * @param array $args settings field args
	 */
	public function callback_select( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

		$html = sprintf( '<select class="%1$s" name="%2$s[%3$s]" id="%2$s[%3$s]">', $size, $args['section'], $args['id'] );
		foreach ( $args['options'] as $key => $label ) {
			$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value, $key, false ), $label );
		}
		$html .= sprintf( '</select>' );
		$html .= $this->get_field_description( $args );

		echo $html;
	}

	/**
	 * Displays a textarea for a settings field
	 *
	 * @param array $args settings field args
	 */
	public function callback_textarea( $args ) {

		$value = esc_textarea( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

		$html  = sprintf( '<textarea rows="5" cols="55" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]">%4$s</textarea>', $size, $args['section'], $args['id'], $value );
		$html .= $this->get_field_description( $args );

		echo $html;
	}

	/**
	 * Displays a textarea for a settings field
	 *
	 * @param array $args settings field args.
	 *
	 * @return void
	 */
	public function callback_html( $args ) {
		echo $this->get_field_description( $args );
	}

	/**
	 * Displays a rich text textarea for a settings field
	 *
	 * @param array $args settings field args.
	 */
	public function callback_wysiwyg( $args ) {

		$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : '500px';

		echo '<div style="max-width: ' . esc_attr( $size ) . ';">';

		$editor_settings = [
			'teeny'         => true,
			'textarea_name' => $args['section'] . '[' . $args['id'] . ']',
			'textarea_rows' => 10,
		];
		if ( isset( $args['options'] ) && is_array( $args['options'] ) ) {
			$editor_settings = array_merge( $editor_settings, $args['options'] );
		}

		wp_editor( $value, $args['section'] . '-' . $args['id'], $editor_settings );

		echo '</div>';

		echo $this->get_field_description( $args );
	}

	/**
	 * Displays a file upload field for a settings field
	 *
	 * @param array $args settings field args.
	 */
	public function callback_file( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
		$id    = $args['section'] . '[' . $args['id'] . ']';
		$label = isset( $args['options']['button_label'] ) ?
		$args['options']['button_label'] :
		__( 'Choose File' );

		$html  = sprintf( '<input type="text" class="%1$s-text eighteen73-url" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
		$html .= '<input type="button" class="button eighteen73-browse" value="' . $label . '" />';
		$html .= $this->get_field_description( $args );

		echo $html;
	}

	/**
	 * Displays an image upload field with a preview
	 *
	 * @param array $args settings field args.
	 */
	public function callback_image( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
		$id    = $args['section'] . '[' . $args['id'] . ']';
		$label = isset( $args['options']['button_label'] ) ?
		$args['options']['button_label'] :
		__( 'Choose Image' );

		$html  = sprintf( '<input type="text" class="%1$s-text eighteen73-url" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
		$html .= '<input type="button" class="button eighteen73-browse" value="' . $label . '" />';
		$html .= $this->get_field_description( $args );
		$html .= '<p class="eighteen73-image-preview"><img src=""/></p>';

		echo $html;
	}

	/**
	 * Displays a password field for a settings field
	 *
	 * @param array $args settings field args
	 */
	public function callback_password( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

		$html  = sprintf( '<input type="password" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
		$html .= $this->get_field_description( $args );

		echo $html;
	}

	/**
	 * Displays a color picker field for a settings field
	 *
	 * @param array $args settings field args
	 */
	public function callback_color( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'], $args['placeholder'] ) );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

		$html  = sprintf( '<input type="text" class="%1$s-text color-picker" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s" data-default-color="%5$s" placeholder="%6$s" />', $size, $args['section'], $args['id'], $value, $args['std'], $args['placeholder'] );
		$html .= $this->get_field_description( $args );

		echo $html;
	}

	/**
	 * Displays a separator field for a settings field
	 *
	 * @param array $args settings field args
	 */
	public function callback_separator( $args ) {
		$type = isset( $args['type'] ) ? $args['type'] : 'separator';

		$html  = '';
		$html .= '<div class="eighteen73-settings-separator"></div>';
		echo $html;
	}

	/**
	 * Get the value of a settings field
	 *
	 * @param string $option  settings field name.
	 * @param string $section the section name this field belongs to.
	 * @param string $default default text if it's not found.
	 * @return string
	 */
	public function get_option( $option, $section, $default = '' ) {

		$options = get_option( $section );

		if ( isset( $options[ $option ] ) ) {
			return $options[ $option ];
		}

		return $default;
	}

	/**
	 * Adds menu/submenu page.
	 */
	public function admin_menu() {
		if ( $this->top_level ) {
			add_menu_page(
				$this->page_title,
				$this->menu_title,
				$this->capability,
				$this->slug,
				[ $this, 'admin_page' ],
				$this->icon_url,
				$this->position,
			);
		} else {
			add_options_page(
				$this->page_title,
				$this->menu_title,
				$this->capability,
				$this->slug,
				[ $this, 'admin_page' ],
				$this->position,
			);
		}
	}

	/**
	 * Sets a submenu.
	 *
	 * @param string    $page_title
	 * @param string    $menu_title
	 * @param string    $menu_slug
	 * @param array     $callback
	 * @param int|null  $position
	 * @param bool      $use_parent_slug
	 */
	public function set_submenu( $page_title, $menu_title, $menu_slug, $callback = [], $position = null, $use_parent_slug = true ) {
		if ( empty( $page_title ) || empty( $menu_title ) || empty( $menu_slug ) ) {
			return;
		}

		$this->submenus_array[] = [
			'parent_slug' => true === $use_parent_slug ? $this->slug : null,
			'page_title'  => esc_attr( $page_title ),
			'menu_title'  => esc_attr( $menu_title ),
			'menu_slug'   => esc_attr( $menu_slug ),
			'callback'    => $callback,
			'position'    => ! empty( $position ) ? intval( $position ) : null,
		];
	}

	/**
	 * Adds submenus.
	 */
	public function admin_submenus() {
		if ( ! $this->top_level ) {
			return;
		}

		if ( empty( $this->submenus_array ) || ! is_array( $this->submenus_array ) ) {
			return;
		}

		foreach ( $this->submenus_array as $submenu ) {
			add_submenu_page(
				$submenu['parent_slug'],
				$submenu['page_title'],
				$submenu['menu_title'],
				$this->capability,
				$submenu['menu_slug'],
				[ $this, 'submenu_page' ],
				$submenu['position']
			);
		}
	}

	/**
	 * Renders the admin page.
	 *
	 * @return void
	 */
	public function admin_page() {
		echo '<div class="wrap '.$this->slug.'-wrap admin-page">';
		echo '<h1 id="'.$this->slug.'-title">' . $this->page_title . '</h1>';
		if ( ! empty( $this->callback ) && is_callable( $this->callback ) ) {
			call_user_func( $this->callback );
		} else {
			$this->show_navigation();
			$this->show_forms();
		}
		echo '</div>';
	}

	/**
	 * Renders a submenu page.
	 *
	 * @return void
	 */
	public function submenu_page() {
		$submenu = $this->get_current_submenu();
		if ( ! empty( $submenu ) ) {
			echo '<div class="wrap '.$submenu['menu_slug'].'-wrap submenu-page">';
			echo '<h1 id="'.$submenu['menu_slug'].'-title">' . $submenu['menu_title'] . '</h1>';
			if ( ! empty( $submenu['callback'] ) && is_callable( $submenu['callback'] ) ) {
				call_user_func( $submenu['callback'] );
			} else {
				$this->show_navigation( $submenu );
				$this->show_forms( $submenu );
			}
			echo '</div>';
		}
	}

	/**
	 * Show navigations as tab
	 *
	 * Shows all the settings section labels as tabs.
	 *
	 * @param array $submenu
	 *
	 * @return void
	 */
	public function show_navigation( $submenu = [] ) {
		$html = '<h2 class="nav-tab-wrapper '.$this->slug.'-nav">';

		$sections = ! empty( $submenu ) ? $this->get_submenu_sections( $submenu ) : $this->sections_array;

		foreach ( $sections as $section ) {
			if ( empty( $submenu ) && ! empty( $section['submenu'] ) ) {
				continue;
			}
			$html .= sprintf( '<a href="#%1$s" class="nav-tab" id="%1$s-tab">%2$s</a>', $section['id'], $section['title'] );
		}

		$html .= '</h2>';

		echo $html;
	}

	/**
	 * Show the section settings forms.
	 *
	 * This function displays every sections in a different form.
	 *
	 * @param array $submenu
	 *
	 * @return void
	 */
	public function show_forms( $submenu = [] ) {
		$default = [
			'label_submit' => null,
			'submit_type'  => 'primary',
			'wrap'         => true,
			'attributes'   => null,
		];

		$sections = ! empty( $submenu ) ? $this->get_submenu_sections( $submenu ) : $this->sections_array;
		?>

		<div class="metabox-holder">
			<?php foreach ( $sections as $section ) : ?>
				<?php
					if ( empty( $submenu ) && ! empty( $section['submenu'] ) ) {
						continue;
					}
					$section = wp_parse_args( $section, $default );
				?>
				<!-- style="display: none;" -->
				<div id="<?php echo esc_attr( $section['id'] ); ?>" class="group" >
					<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
						<?php
						do_action( 'eighteen73_form_top_' . $section['id'], $section );
						settings_fields( $section['id'] );
						do_settings_sections( $section['id'] );
						do_action( 'eighteen73_form_bottom_' . $section['id'], $section );
						submit_button( $section['label_submit'], $section['submit_type'], 'submit_' . $section['id'], $section['wrap'], $section['attributes'] );
						?>
					</form>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		$this->script();
	}

	/**
	 * Get the current submenu.
	 *
	 * @return array
	 */
	public function get_current_submenu() {
		$screen          = get_current_screen();
		$current_submenu = [];

		if ( empty( $this->submenus_array ) ) {
			return $current_submenu;
		}

		foreach ( $this->submenus_array as $submenu ) {
			$submenu_page_id = "_page_{$submenu['menu_slug']}";
			if ( strpos( $screen->id, $submenu_page_id ) !== false ) {
				$current_submenu = $submenu;
			}
		}

		return $current_submenu;
	}

	/**
	 * Get a submenu sections.
	 *
	 * @param  array $submenu
	 *
	 * @return array
	 */
	public function get_submenu_sections( $submenu ) {
		$sections = [];

		if ( empty( $submenu ) ) {
			return $sections;
		}

		foreach ( $this->sections_array as $section ) {
			if ( isset( $section['submenu'] ) && $section['submenu'] == $submenu['menu_slug'] ) {
				$sections[] = $section;
			}
		}

		return $sections;
	}

	/**
	 * Tabbable JavaScript codes & Initiate Color Picker.
	 *
	 * This code uses localstorage for displaying active tabs.
	 *
	 * @return void
	 */
	public function script() {
		?>
			<script>
				jQuery( document ).ready( function( $ ) {

				//Initiate Color Picker.
				$('.color-picker').iris();

				// Switches option sections
				$( '.group' ).hide();
				var activetab = '';
				if ( 'undefined' !== typeof localStorage ) {
					activetab = localStorage.getItem( 'activetab' );
				}
				if ( '' !== activetab && $( activetab ).length ) {
					$( activetab ).show();
				} else {
					$( '.group:first' ).show();
				}
				$( '.group .collapsed' ).each( function() {
					$( this )
						.find( 'input:checked' )
						.parent()
						.parent()
						.parent()
						.nextAll()
						.each( function() {
							if ( $( this ).hasClass( 'last' ) ) {
								$( this ).removeClass( 'hidden' );
								return false;
							}
							$( this )
								.filter( '.hidden' )
								.removeClass( 'hidden' );
						});
				});

				if ( '' !== activetab && $( activetab + '-tab' ).length ) {
					$( activetab + '-tab' ).addClass( 'nav-tab-active' );
				} else {
					$( '.nav-tab-wrapper a:first' ).addClass( 'nav-tab-active' );
				}
				$( '.nav-tab-wrapper a' ).click( function( evt ) {
					$( '.nav-tab-wrapper a' ).removeClass( 'nav-tab-active' );
					$( this )
						.addClass( 'nav-tab-active' )
						.blur();
					var clicked_group = $( this ).attr( 'href' );
					if ( 'undefined' != typeof localStorage ) {
						localStorage.setItem( 'activetab', $( this ).attr( 'href' ) );
					}
					$( '.group' ).hide();
					$( clicked_group ).show();
					evt.preventDefault();
				});

				$( '.eighteen73-browse' ).on( 'click', function( event ) {
					event.preventDefault();

					var self = $( this );

					// Create the media frame.
					var file_frame = ( wp.media.frames.file_frame = wp.media({
						title: self.data( 'uploader_title' ),
						button: {
							text: self.data( 'uploader_button_text' )
						},
						multiple: false
					}) );

					file_frame.on( 'select', function() {
						attachment = file_frame
							.state()
							.get( 'selection' )
							.first()
							.toJSON();

						self
							.prev( '.eighteen73-url' )
							.val( attachment.url )
							.change();
					});

					// Finally, open the modal
					file_frame.open();
				});

				$( 'input.eighteen73-url' )
					.on( 'change keyup paste input', function() {
						var self = $( this );
						self
							.next()
							.parent()
							.children( '.eighteen73-image-preview' )
							.children( 'img' )
							.attr( 'src', self.val() );
					})
					.change();
			});
			</script>

			<style type="text/css">
				#wpbody-content .metabox-holder {
					padding-top: 5px;
				}

				.eighteen73-image-preview img {
					height: auto;
					max-width: 70px;
				}

				.eighteen73-settings-separator {
					background: #ccc;
					border: 0;
					color: #ccc;
					height: 1px;
					position: absolute;
					left: 0;
					width: 99%;
				}

				.group .form-table input.color-picker {
					max-width: 100px;
				}

				/**
				 * Space for multi checkbox and multi radio fields with their own descriptions.
				 */
				.group .form-table .description + label {
					margin-top: 20px !important;
				}
			</style>
		<?php
	}
}
