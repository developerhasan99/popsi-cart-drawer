<?php
/**
 * Main class for Popsi Cart Drawer plugin.
 *
 * @package Popsi_Cart_Drawer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main class for Popsi Cart Drawer.
 *
 * @package Popsi_Cart_Drawer
 */
class Popsi_Cart_Drawer {

	/**
	 * Settings cache.
	 *
	 * @var array|null
	 */
	private static ?array $settings_cache = null;

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public function init() {
		add_shortcode( 'popsi_cart', array( $this, 'cart_icon_shortcode' ) );
		add_shortcode( 'popsi_cart_icon', array( $this, 'cart_icon_shortcode' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'output_cart_drawer' ) );

		add_action( 'wp_ajax_popsi_cart_add_to_cart', array( $this, 'ajax_add_to_cart' ) );
		add_action( 'wp_ajax_nopriv_popsi_cart_add_to_cart', array( $this, 'ajax_add_to_cart' ) );
		add_action( 'wp_ajax_popsi_cart_get_cart', array( $this, 'ajax_get_cart' ) );
		add_action( 'wp_ajax_nopriv_popsi_cart_get_cart', array( $this, 'ajax_get_cart' ) );

		add_action( 'wp_ajax_popsi_cart_update_item', array( $this, 'ajax_update_item' ) );
		add_action( 'wp_ajax_nopriv_popsi_cart_update_item', array( $this, 'ajax_update_item' ) );

		add_action( 'wp_ajax_popsi_cart_apply_coupon', array( $this, 'ajax_apply_coupon' ) );
		add_action( 'wp_ajax_nopriv_popsi_cart_apply_coupon', array( $this, 'ajax_apply_coupon' ) );
		add_action( 'wp_ajax_popsi_cart_remove_coupon', array( $this, 'ajax_remove_coupon' ) );
		add_action( 'wp_ajax_nopriv_popsi_cart_remove_coupon', array( $this, 'ajax_remove_coupon' ) );

		add_action( 'wp_ajax_popsi_cart_save_settings', array( $this, 'ajax_save_settings' ) );

		add_filter( 'wp_nav_menu_items', array( $this, 'append_cart_icon_to_menu' ), 10, 2 );
		add_action( 'woocommerce_add_to_cart', array( $this, 'set_just_added_cookie' ), 10, 6 );
	}

	/**
	 * Set just added cookie.
	 *
	 * @return void
	 */
	public function set_just_added_cookie() {
		if ( ! wp_doing_ajax() && ! headers_sent() ) {
			setcookie( 'popsi_cart_just_added', '1', time() + 30, '/' );
		}
	}

	/**
	 * Append cart icon to menu.
	 *
	 * @param string   $items Menu items HTML.
	 * @param stdClass $args  Menu arguments.
	 * @return string Modified menu items HTML.
	 */
	public function append_cart_icon_to_menu( string $items, stdClass $args ) {
		$settings  = $this->get_settings();
		$placement = $settings['menu_placement'] ?? 'none';

		if ( 'none' === $placement ) {
			return $items;
		}

		$menu_slug = '';
		if ( isset( $args->menu ) && is_object( $args->menu ) ) {
			$menu_slug = $args->menu->slug;
		} elseif ( isset( $args->menu ) ) {
			$term = get_term_by( 'id', $args->menu, 'nav_menu' );
			if ( $term ) {
				$menu_slug = $term->slug;
			}
		}

		if ( $menu_slug === $placement || ( isset( $args->theme_location ) && $args->theme_location === $placement ) ) {
			$items .= '<li class="menu-item popsi-cart-menu-item">' . $this->cart_icon_shortcode() . '</li>';
		}

		return $items;
	}


	/**
	 * Output cart icon shortcode.
	 *
	 * @return void
	 */
	public function cart_icon_shortcode_output() {
		include POPSI_CART_PATH . 'templates/cart-icon.php';
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return;
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return;
		}

		wp_enqueue_style( 'popsi-cart-style', POPSI_CART_URL . 'assets/css/popsi-cart.css', array(), POPSI_CART_VERSION );

		wp_enqueue_script(
			'popsi-cart-script',
			POPSI_CART_URL . 'assets/js/popsi-cart.js',
			array( 'jquery' ),
			POPSI_CART_VERSION,
			array(
				'strategy' => 'defer',
			)
		);

		wp_localize_script(
			'popsi-cart-script',
			'popsiCartData',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'popsi-cart-nonce' ),
				'settings' => $this->get_settings(),
			)
		);
	}

	/**
	 * Get default settings.
	 *
	 * @return array Default settings array.
	 */
	public static function get_default_settings() {
		return array(
			'enable_cart_drawer'            => false,
			'auto_open_cart'                => true,
			'menu_placement'                => 'bottom',
			'progress_bars'                 => array(
				array(
					'type'           => 'subtotal',
					'away_text'      => "You're only {amount} away from {goal}",
					'completed_text' => '🎉 Congratulations! You have unlocked all rewards.',
					'show_labels'    => true,
					'checkpoints'    => array(
						array(
							'threshold' => '50',
							'label'     => 'Free Shipping',
							'icon'      => 'truck',
						),
						array(
							'threshold' => '100',
							'label'     => '10% Discount',
							'icon'      => 'tag',
						),
					),
				),
			),
			'primary_color'                 => '#000000',
			'enable_coupon'                 => true,
			'enable_badges'                 => true,
			'enable_rewards_bar'            => true,
			'show_rewards_on_empty'         => true,
			'rewards_bar_bg'                => '#E2E2E2',
			'rewards_bar_fg'                => '#93D3FF',
			'rewards_complete_icon_color'   => '#4D4949',
			'rewards_incomplete_icon_color' => '#4D4949',
			'rewards_bars_layout'           => 'column',
			'inherit_fonts'                 => true,
			'show_strikethrough'            => true,
			'enable_subtotal_line'          => true,
			'bg_color'                      => '#FFFFFF',
			'accent_color'                  => '#f6f6f7',
			'text_color'                    => '#000000',
			'savings_text_color'            => '#2ea818',
			'btn_radius'                    => '5px',
			'btn_color'                     => '#000000',
			'btn_text_color'                => '#FFFFFF',
			'btn_hover_color'               => '#333333',
			'btn_hover_text_color'          => '#e9e9e9',
			'cart_icon_type'                => 'bag-1',
			'cart_icon_color'               => '#000000',
			'cart_icon_size'                => '24',
			'cart_bubble_bg'                => '#000000',
			'cart_bubble_text'              => '#ffffff',
			'show_cart_count'               => true,
			'cart_title'                    => 'Your Cart',
			'show_announcement'             => false,
			'announcement_text'             => 'Your products are reserved for {timer}!',
			'announcement_bg'               => '#fffbeb',
			'announcement_text_color'       => '#92400e',
			'announcement_font_size'        => '13px',
			'announcement_bar_size'         => 'medium',
			'timer_duration'                => '15',
			'show_item_images'              => true,
			'show_savings'                  => true,
			'trans_savings_prefix'          => 'Save',
			'show_upsells'                  => true,
			'show_upsells_on_empty'         => true,
			'upsell_title'                  => 'You might also like...',
			'upsell_max'                    => 3,
			'upsell_source'                 => 'best_sellers',
			'upsell_category'               => '',
			'upsell_btn_text'               => 'Add to Cart',
			'show_trust_badges'             => true,
			'trust_badge_image'             => POPSI_CART_URL . 'assets/img/payment-badge.svg',
			'trans_checkout_btn'            => 'Checkout',
			'trans_continue_shopping'       => 'Continue Shopping',
			'trans_empty_cart'              => 'Your cart is currently empty.',
			'trans_subtotal'                => 'Subtotal',
			'trans_coupon_placeholder'      => 'Coupon code',
			'trans_coupon_apply_btn'        => 'Apply',
			'trans_discounts'               => 'Discounts',
			'trans_coupon_accordion_title'  => 'Have a Coupon?',
			'show_shipping_notice'          => true,
			'shipping_notice_text'          => 'Shipping and taxes will be calculated at checkout.',
			'show_subtotal_on_checkout'     => true,
			'enable_total_line'             => true,
			'trans_total'                   => 'Total',
		);
	}

	/**
	 * Get plugin settings.
	 *
	 * @return array Plugin settings array.
	 */
	public function get_settings() {
		if ( null !== self::$settings_cache ) {
			return self::$settings_cache;
		}

		$saved    = get_option( 'popsi_cart_settings', array() );
		$defaults = self::get_default_settings();

		self::$settings_cache = wp_parse_args( $saved, $defaults );

		return self::$settings_cache;
	}

	/**
	 * Output cart drawer HTML.
	 *
	 * @return void
	 */
	public function output_cart_drawer() {
		$settings = $this->get_settings();
		if ( ! ( $settings['enable_cart_drawer'] ?? false ) ) {
			return;
		}

		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return;
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return;
		}

		include POPSI_CART_PATH . 'templates/cart-drawer.php';
	}

	/**
	 * AJAX handler for getting cart.
	 *
	 * @return void
	 */
	public function ajax_get_cart() {
		check_ajax_referer( 'popsi-cart-nonce', 'security' );
		ob_start();
		$this->render_cart_content();
		$html = ob_get_clean();

		wp_send_json_success(
			array(
				'html'     => $html,
				'count'    => WC()->cart->get_cart_contents_count(),
				'subtotal' => WC()->cart->get_cart_subtotal(),
			)
		);
	}

	/**
	 * AJAX handler for updating cart item.
	 *
	 * @return void
	 */
	public function ajax_update_item() {
		check_ajax_referer( 'popsi-cart-nonce', 'security' );

		if ( ! isset( $_POST['cart_item_key'] ) ) {
			wp_send_json_error();
		}

		$cart_item_key = sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) );
		$quantity      = isset( $_POST['quantity'] ) ? absint( wp_unslash( $_POST['quantity'] ) ) : 0;

		if ( ! WC()->cart ) {
			wp_send_json_error();
		}

		if ( 0 === $quantity ) {
			WC()->cart->remove_cart_item( $cart_item_key );
		} else {
			WC()->cart->set_quantity( $cart_item_key, $quantity );
		}

		WC()->cart->calculate_totals();

		$this->ajax_get_cart();
	}

	/**
	 * AJAX handler for applying coupon.
	 *
	 * @return void
	 */
	public function ajax_apply_coupon() {
		check_ajax_referer( 'popsi-cart-nonce', 'security' );
		$coupon_code = isset( $_POST['coupon'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon'] ) ) : '';

		if ( ! empty( $coupon_code ) ) {
			WC()->cart->apply_coupon( $coupon_code );
		}

		WC()->cart->calculate_totals();
		$this->ajax_get_cart();
	}

	/**
	 * AJAX handler for removing coupon.
	 *
	 * @return void
	 */
	public function ajax_remove_coupon() {
		check_ajax_referer( 'popsi-cart-nonce', 'security' );
		$coupon_code = isset( $_POST['coupon'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon'] ) ) : '';

		if ( ! empty( $coupon_code ) ) {
			WC()->cart->remove_coupon( $coupon_code );
		}

		WC()->cart->calculate_totals();
		$this->ajax_get_cart();
	}

	/**
	 * AJAX handler for adding to cart.
	 *
	 * @return void
	 */
	public function ajax_add_to_cart() {
		check_ajax_referer( 'popsi-cart-nonce', 'security' );

		$product_id   = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : ( isset( $_POST['add-to-cart'] ) ? absint( wp_unslash( $_POST['add-to-cart'] ) ) : 0 );
		$quantity     = empty( $_POST['quantity'] ) ? 1 : absint( wp_unslash( $_POST['quantity'] ) );
		$variation_id = isset( $_POST['variation_id'] ) ? absint( wp_unslash( $_POST['variation_id'] ) ) : '';

		$variation = array();
		foreach ( $_POST as $key => $value ) {
			if ( strpos( $key, 'attribute_' ) === 0 ) {
				$variation[ $key ] = wc_clean( $value );
			}
		}

		$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variation ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		if ( $passed_validation && WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation ) ) {
			if ( isset( $_POST['add-to-cart'] ) ) {
				do_action( 'woocommerce_ajax_added_to_cart', $product_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			}
			$this->ajax_get_cart();
		} else {
			// Capture WooCommerce errors and notices
			$error_messages = array();
			
			// Get all WooCommerce notices
			if ( function_exists( 'wc_get_notices' ) ) {
				$notices = wc_get_notices( 'error' );
				if ( ! empty( $notices ) ) {
					foreach ( $notices as $notice ) {
						if ( isset( $notice['notice'] ) ) {
							$error_messages[] = $notice['notice'];
						}
					}
					// Clear the notices after capturing them
					wc_clear_notices();
				}
			}
			
			// If no specific errors captured, try to get validation errors
			if ( empty( $error_messages ) && ! $passed_validation ) {
				$error_messages[] = 'Product validation failed. Please check your selection.';
			}
			
			// If still no errors, provide a generic message
			if ( empty( $error_messages ) ) {
				$error_messages[] = 'Unable to add product to cart. Please try again.';
			}
			
			wp_send_json_error( array( 
				'error' => true,
				'messages' => $error_messages 
			) );
		}
	}

	/**
	 * AJAX handler for saving settings.
	 *
	 * @return void
	 */
	public function ajax_save_settings() {
		check_ajax_referer( 'popsi-cart-admin-nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'popsi-cart-drawer' ), 403 );
		}

		$raw  = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : '{}'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			wp_send_json_error( __( 'Invalid settings data.', 'popsi-cart-drawer' ) );
		}

		$clean = $this->sanitize_settings( $data );

		update_option( 'popsi_cart_settings', $clean, false );

		self::$settings_cache = null;

		wp_send_json_success( __( 'Settings saved.', 'popsi-cart-drawer' ) );
	}

	/**
	 * Sanitize settings array.
	 *
	 * @param array $raw Raw settings data.
	 * @return array Sanitized settings data.
	 */
	private function sanitize_settings( array $raw ): array {
		$defaults = self::get_default_settings();
		$allowed  = array_keys( $defaults );
		$clean    = array();

		$bool_keys = array(
			'enable_cart_drawer',
			'auto_open_cart',
			'enable_coupon',
			'enable_badges',
			'enable_rewards_bar',
			'show_rewards_on_empty',
			'inherit_fonts',
			'show_strikethrough',
			'enable_subtotal_line',
			'show_announcement',
			'show_item_images',
			'show_savings',
			'show_upsells',
			'show_upsells_on_empty',
			'show_trust_badges',
			'show_cart_count',
			'show_shipping_notice',
			'show_subtotal_on_checkout',
			'enable_total_line',
		);

		$color_keys = array(
			'primary_color',
			'bg_color',
			'accent_color',
			'text_color',
			'savings_text_color',
			'btn_color',
			'btn_text_color',
			'btn_hover_color',
			'btn_hover_text_color',
			'cart_icon_color',
			'cart_bubble_bg',
			'cart_bubble_text',
			'rewards_bar_bg',
			'rewards_bar_fg',
			'rewards_complete_icon_color',
			'rewards_incomplete_icon_color',
			'announcement_bg',
			'announcement_text_color',
		);

		$int_keys = array( 'upsell_max', 'cart_icon_size', 'timer_duration' );
		$url_keys = array( 'trust_badge_image' );

		foreach ( $allowed as $key ) {
			if ( ! array_key_exists( $key, $raw ) ) {
				continue; // Leave missing keys to wp_parse_args defaults.
			}

			if ( in_array( $key, $bool_keys, true ) ) {
				$clean[ $key ] = (bool) $raw[ $key ];
			} elseif ( in_array( $key, $color_keys, true ) ) {
				$val = sanitize_hex_color( (string) $raw[ $key ] );
				if ( $val ) {
					$clean[ $key ] = $val;
				}
			} elseif ( in_array( $key, $int_keys, true ) ) {
				$clean[ $key ] = absint( $raw[ $key ] );
			} elseif ( in_array( $key, $url_keys, true ) ) {
				$clean[ $key ] = esc_url_raw( (string) $raw[ $key ] );
			} elseif ( 'progress_bars' === $key ) {
				$clean[ $key ] = $this->sanitize_progress_bars( $raw[ $key ] );
			} elseif ( 'rewards_bars_layout' === $key ) {
				$layout        = (string) $raw[ $key ];
				$clean[ $key ] = in_array( $layout, array( 'row', 'column' ), true ) ? $layout : 'column';
			} else {
				$clean[ $key ] = sanitize_text_field( (string) $raw[ $key ] );
			}
		}

		return $clean;
	}

	/**
	 * Sanitize progress bars array.
	 *
	 * @param array $input Raw progress bars data.
	 * @return array Sanitized progress bars data.
	 */
	private function sanitize_progress_bars( array $input ): array {
		$sanitized = array();
		if ( is_array( $input ) ) {
			foreach ( $input as $bar ) {
				$sanitized_bar = array(
					'type'           => isset( $bar['type'] ) ? sanitize_text_field( $bar['type'] ) : 'subtotal',
					'away_text'      => isset( $bar['away_text'] ) ? sanitize_text_field( $bar['away_text'] ) : '',
					'completed_text' => isset( $bar['completed_text'] ) ? sanitize_text_field( $bar['completed_text'] ) : '',
					'show_labels'    => isset( $bar['show_labels'] ) ? (bool) $bar['show_labels'] : true,
					'checkpoints'    => array(),
				);

				if ( isset( $bar['checkpoints'] ) && is_array( $bar['checkpoints'] ) ) {
					foreach ( $bar['checkpoints'] as $checkpoint ) {
						$sanitized_bar['checkpoints'][] = array(
							'threshold' => isset( $checkpoint['threshold'] ) ? sanitize_text_field( $checkpoint['threshold'] ) : '0',
							'label'     => isset( $checkpoint['label'] ) ? sanitize_text_field( $checkpoint['label'] ) : '',
							'icon'      => isset( $checkpoint['icon'] ) ? sanitize_text_field( $checkpoint['icon'] ) : 'truck',
						);
					}
				}
				$sanitized[] = $sanitized_bar;
			}
		}
		return $sanitized;
	}

	/**
	 * Get cart upsell cache key.
	 *
	 * @param array  $cart_items Cart items.
	 * @param string $upsell_source Upsell source.
	 * @param int    $upsell_max Maximum upsell items.
	 * @param string $upsell_category Upsell category.
	 * @return string Cache key.
	 */
	private function get_cart_upsell_cache_key( array $cart_items, string $upsell_source, int $upsell_max, string $upsell_category ): string {
		$cart_signature = array();

		foreach ( $cart_items as $cart_item ) {
			$product_id       = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
			$variation_id     = isset( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : 0;
			$cart_signature[] = $product_id . ':' . $variation_id;
		}

		sort( $cart_signature, SORT_STRING );

		return 'bc_upsells_' . md5(
			wp_json_encode(
				array(
					'cart'     => $cart_signature,
					'source'   => $upsell_source,
					'max'      => $upsell_max,
					'category' => $upsell_category,
				)
			)
		);
	}

	/**
	 * Collect product relation IDs.
	 *
	 * @param array  $cart_items Cart items.
	 * @param string $relation Relation type ('upsells' or 'cross_sells').
	 * @return array Product relation IDs.
	 */
	private function collect_product_relation_ids( array $cart_items, string $relation ): array {
		$collected_ids = array();

		foreach ( $cart_items as $cart_item ) {
			if ( empty( $cart_item['data'] ) || ! is_object( $cart_item['data'] ) ) {
				continue;
			}

			$product      = $cart_item['data'];
			$relation_ids = array();

			if ( 'upsells' === $relation ) {
				$relation_ids = $product->get_upsell_ids();
			} elseif ( 'cross_sells' === $relation ) {
				$relation_ids = $product->get_cross_sell_ids();
			}

			foreach ( $relation_ids as $relation_id ) {
				$relation_id = (int) $relation_id;
				if ( $relation_id > 0 ) {
					$collected_ids[ $relation_id ] = $relation_id;
				}
			}
		}

		return array_values( $collected_ids );
	}

	/**
	 * Collect related product IDs.
	 *
	 * @param array $cart_items Cart items.
	 * @param int   $limit Maximum number of related products.
	 * @return array Related product IDs.
	 */
	private function collect_related_product_ids( array $cart_items, int $limit ): array {
		$related_ids = array();

		foreach ( $cart_items as $cart_item ) {
			$product_id = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
			if ( $product_id <= 0 ) {
				continue;
			}

			$product_related_ids = wc_get_related_products( $product_id, $limit );
			foreach ( $product_related_ids as $related_id ) {
				$related_id = (int) $related_id;
				if ( $related_id > 0 ) {
					$related_ids[ $related_id ] = $related_id;
				}
			}
		}

		return array_values( $related_ids );
	}

	/**
	 * Get upsell query IDs.
	 *
	 * @param array  $cart_items Cart items.
	 * @param string $upsell_source Upsell source.
	 * @param int    $upsell_max Maximum upsell items.
	 * @param string $upsell_category Upsell category.
	 * @return array Upsell query IDs.
	 */
	public function get_upsell_query_ids( array $cart_items, string $upsell_source, int $upsell_max, string $upsell_category = '' ): array {
		if ( $upsell_max < 1 ) {
			return array();
		}

		$cache_key        = $this->get_cart_upsell_cache_key( $cart_items, $upsell_source, $upsell_max, $upsell_category ) . '_filtered';
		$upsell_query_ids = get_transient( $cache_key );

		if ( false !== $upsell_query_ids && is_array( $upsell_query_ids ) ) {
			return $upsell_query_ids;
		}

		$excluded_ids = array();
		foreach ( $cart_items as $cart_item ) {
			$product_id = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
			if ( $product_id > 0 ) {
				$excluded_ids[ $product_id ] = $product_id;
			}
		}
		$excluded_ids = array_values( $excluded_ids );

		$query_args = array(
			'limit'  => $upsell_max + count( $excluded_ids ),
			'status' => 'publish',
			'return' => 'ids',
		);

		if ( 'best_sellers' === $upsell_source ) {
			$query_args['orderby'] = 'popularity';
			$query_args['order']   = 'DESC';
		} elseif ( 'newest' === $upsell_source ) {
			$query_args['orderby'] = 'date';
			$query_args['order']   = 'DESC';
		} elseif ( 'category' === $upsell_source && ! empty( $upsell_category ) ) {
			$query_args['category'] = array( $upsell_category );
			$query_args['orderby']  = 'date';
			$query_args['order']    = 'DESC';
		} elseif ( 'upsells' === $upsell_source ) {
			$upsell_ids = $this->collect_product_relation_ids( $cart_items, 'upsells' );
			if ( ! empty( $upsell_ids ) ) {
				$query_args['include'] = array_diff( $upsell_ids, $excluded_ids );
				if ( empty( $query_args['include'] ) ) {
					// Fallback to popularity if no specific upsells found.
					unset( $query_args['include'] );
					$query_args['orderby'] = 'popularity';
				} else {
					$query_args['orderby'] = 'include';
				}
			} else {
				$query_args['orderby'] = 'popularity';
			}
		} elseif ( 'cross_sells' === $upsell_source ) {
			$cross_sell_ids = $this->collect_product_relation_ids( $cart_items, 'cross_sells' );
			if ( ! empty( $cross_sell_ids ) ) {
				$query_args['include'] = array_diff( $cross_sell_ids, $excluded_ids );
				if ( empty( $query_args['include'] ) ) {
					unset( $query_args['include'] );
					$query_args['orderby'] = 'popularity';
				} else {
					$query_args['orderby'] = 'include';
				}
			} else {
				$query_args['orderby'] = 'popularity';
			}
		} elseif ( 'related' === $upsell_source ) {
			$related_ids = $this->collect_related_product_ids( $cart_items, $upsell_max + count( $excluded_ids ) );
			if ( ! empty( $related_ids ) ) {
				$query_args['include'] = array_diff( $related_ids, $excluded_ids );
				if ( empty( $query_args['include'] ) ) {
					unset( $query_args['include'] );
					$query_args['orderby'] = 'popularity';
				} else {
					$query_args['orderby'] = 'include';
				}
			} else {
				$query_args['orderby'] = 'popularity';
			}
		} else {
			$query_args['orderby'] = 'popularity';
		}

		$ids = wc_get_products( $query_args );

		// Additional filtering to exclude external, affiliate, and grouped products.
		$filtered_ids = array();
		foreach ( $ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				// Exclude external, affiliate, and grouped product types using WooCommerce's built-in methods.
				if ( ! $product->is_type( 'external' ) && ! $product->is_type( 'affiliate' ) && ! $product->is_type( 'grouped' ) ) {
					$filtered_ids[] = $product_id;
				}
			}
		}

		// Filter out excluded IDs (items already in cart) and slice to max.
		$filtered_ids     = array_diff( $filtered_ids, $excluded_ids );
		$upsell_query_ids = array_slice( $filtered_ids, 0, $upsell_max );

		set_transient( $cache_key, $upsell_query_ids, 600 );

		return $upsell_query_ids;
	}

	// -------------------------------------------------------------------------

	/**
	 * Render cart content.
	 *
	 * @return void
	 */
	public function render_cart_content() {
		include POPSI_CART_PATH . 'templates/cart-items.php';
	}

	/**
	 * Cart icon shortcode callback.
	 *
	 * @return string Cart icon HTML.
	 */
	public function cart_icon_shortcode() {
		ob_start();
		include POPSI_CART_PATH . 'templates/cart-icon.php';
		return ob_get_clean();
	}

	/**
	 * Helper to get SVG icon from template.
	 * Captured output ensures literal HTML is used where possible,
	 * but still available as a string for JS localization.
	 *
	 * @param string $icon_name Icon name.
	 * @param string $icon_class Icon CSS class.
	 * @return string SVG icon HTML.
	 */
	public static function get_svg_icon( string $icon_name, string $icon_class = '' ) {
		// Set variables expected by icons.php template.
		$popsi_cart_icon_name  = $icon_name;
		$popsi_cart_icon_class = $icon_class;

		ob_start();
		include POPSI_CART_PATH . 'templates/icons.php';
		return ob_get_clean();
	}
}
