<?php
/**
 * Cart upsells template for Popsi Cart Drawer.
 *
 * @package Popsi_Cart_Drawer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Initialize required variables.
$popsi_cart_settings             = ( new Popsi_Cart_Drawer() )->get_settings();
$popsi_cart_show_upsells         = $popsi_cart_settings['show_upsells'] ?? true;
$popsi_cart_cart                 = WC()->cart;
$popsi_cart_is_empty             = $popsi_cart_cart ? $popsi_cart_cart->is_empty() : true;
$popsi_cart_items                = $popsi_cart_cart ? $popsi_cart_cart->get_cart() : array();
$popsi_cart_text_color           = $popsi_cart_settings['text_color'] ?? '#000000';
$popsi_cart_btn_color            = $popsi_cart_settings['btn_color'] ?? '#000000';
$popsi_cart_btn_text_color       = $popsi_cart_settings['btn_text_color'] ?? '#FFFFFF';
$popsi_cart_btn_radius           = $popsi_cart_settings['btn_radius'] ?? '4px';
$popsi_cart_btn_hover_color      = $popsi_cart_settings['btn_hover_color'] ?? '#333333';
$popsi_cart_btn_hover_text_color = $popsi_cart_settings['btn_hover_text_color'] ?? '#FFFFFF';

$popsi_cart_show_on_empty = $popsi_cart_settings['show_upsells_on_empty'] ?? true;
if ( $popsi_cart_show_upsells && ( ! $popsi_cart_is_empty || $popsi_cart_show_on_empty ) ) :
	$popsi_cart_upsell_title     = $popsi_cart_settings['upsell_title'] ?? 'Product Recommendations';
	$popsi_cart_upsell_max       = max( 1, (int) ( $popsi_cart_settings['upsell_max'] ?? 3 ) );
	$popsi_cart_upsell_source    = $popsi_cart_settings['upsell_source'] ?? 'best_sellers';
	$popsi_cart_upsell_category  = $popsi_cart_settings['upsell_category'] ?? '';
	$popsi_cart_upsell_query_ids = $this->get_upsell_query_ids(
		is_array( $popsi_cart_items ) ? $popsi_cart_items : array(),
		$popsi_cart_upsell_source,
		$popsi_cart_upsell_max,
		$popsi_cart_upsell_category
	);

	$popsi_cart_upsell_query = new WP_Query(
		array(
			'post_type'           => 'product',
			'post__in'            => ! empty( $popsi_cart_upsell_query_ids ) ? $popsi_cart_upsell_query_ids : array( 0 ),
			'orderby'             => 'post__in',
			'posts_per_page'      => $popsi_cart_upsell_max,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		)
	);

	if ( $popsi_cart_upsell_query->have_posts() ) :
		?>
		<div class="bc-upsells" style="background-color: <?php echo esc_attr( $popsi_cart_settings['accent_color'] ?? '#f9fafb' ); ?>;">
			<h3 class="bc-upsells-title" style="color: <?php echo esc_attr( $popsi_cart_text_color ); ?>;"><?php echo esc_html( $popsi_cart_upsell_title ); ?></h3>

			<div class="bc-upsells-list">
				<?php
				while ( $popsi_cart_upsell_query->have_posts() ) :
					$popsi_cart_upsell_query->the_post();
					global $product;
					$popsi_cart_upsell_img = wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' );

					$popsi_cart_prices_json          = '';
					$popsi_cart_variations_json      = '';
					$popsi_cart_product_variations   = array();
					$popsi_cart_attribute_selects    = array();
					$popsi_cart_default_attributes   = array();
					if ( $product->is_type( 'variable' ) ) {
						$popsi_cart_p_map               = array();
						$popsi_cart_variations_payload  = array();
						$popsi_cart_product_variations  = $product->get_available_variations();
						$popsi_cart_parent_attributes   = $product->get_attributes();
						$popsi_cart_variation_values    = $product->get_variation_attributes();
						$popsi_cart_default_attributes  = $product->get_default_attributes();

						foreach ( $popsi_cart_parent_attributes as $popsi_cart_parent_attribute ) {
							if ( ! $popsi_cart_parent_attribute->get_variation() ) {
								continue;
							}

							$popsi_cart_attr_name    = $popsi_cart_parent_attribute->get_name();
							$popsi_cart_attr_key     = 'attribute_' . sanitize_title( $popsi_cart_attr_name );
							$popsi_cart_attr_options = $popsi_cart_variation_values[ $popsi_cart_attr_name ] ?? array();
							$popsi_cart_select_items = array();

							foreach ( $popsi_cart_attr_options as $popsi_cart_attr_option ) {
								$popsi_cart_option_label = $popsi_cart_attr_option;

								if ( taxonomy_exists( $popsi_cart_attr_name ) ) {
									$popsi_cart_attr_term = get_term_by( 'slug', $popsi_cart_attr_option, $popsi_cart_attr_name );
									if ( $popsi_cart_attr_term && ! is_wp_error( $popsi_cart_attr_term ) ) {
										$popsi_cart_option_label = $popsi_cart_attr_term->name;
									}
								}

								$popsi_cart_select_items[] = array(
									'value' => $popsi_cart_attr_option,
									'label' => $popsi_cart_option_label,
								);
							}

							$popsi_cart_attribute_selects[] = array(
								'key'     => $popsi_cart_attr_key,
								'name'    => $popsi_cart_attr_name,
								'label'   => wc_attribute_label( $popsi_cart_attr_name, $product ),
								'options' => $popsi_cart_select_items,
							);
						}

						foreach ( $popsi_cart_product_variations as $popsi_cart_v ) {
							$popsi_cart_p_map[ $popsi_cart_v['variation_id'] ] = wp_strip_all_tags( wc_price( $popsi_cart_v['display_price'] ) );

							if ( empty( $popsi_cart_v['variation_id'] ) || empty( $popsi_cart_v['attributes'] ) ) {
								continue;
							}

							$popsi_cart_variations_payload[] = array(
								'id'         => (int) $popsi_cart_v['variation_id'],
								'attributes' => $popsi_cart_v['attributes'],
							);
						}
						$popsi_cart_prices_json     = wp_json_encode( $popsi_cart_p_map );
						$popsi_cart_variations_json = wp_json_encode( $popsi_cart_variations_payload );
					}
					?>
					<div class="bc-upsell-item" data-id="<?php echo esc_attr( get_the_ID() ); ?>" 
					<?php
					if ( $popsi_cart_prices_json ) {
						echo ' data-prices="' . esc_attr( $popsi_cart_prices_json ) . '"';}
					if ( $popsi_cart_variations_json ) {
						echo ' data-variations="' . esc_attr( $popsi_cart_variations_json ) . '"';}
					?>
					>
						<div class="bc-upsell-img-wrap">
							<a href="<?php echo esc_url( get_permalink() ); ?>" class="bc-upsell-link">
								<?php if ( $popsi_cart_upsell_img ) : ?>
									<img src="<?php echo esc_url( $popsi_cart_upsell_img ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>">
								<?php else : ?>
									<?php
									$popsi_cart_icon_name  = 'format-image';
									$popsi_cart_icon_class = 'bc-placeholder-icon';
									include POPSI_CART_PATH . 'templates/icons.php';
									?>
								<?php endif; ?>
							</a>
						</div>
						<div class="bc-upsell-details">
							<h5 class="bc-upsell-title">
								<a href="<?php echo esc_url( get_permalink() ); ?>" style="color: <?php echo esc_attr( $popsi_cart_text_color ); ?>; text-decoration: none;">
									<?php the_title(); ?>
								</a>
							</h5>
							<div class="bc-upsell-prices">
								<span class="bc-upsell-price" style="color: <?php echo esc_attr( $popsi_cart_text_color ); ?>;">
									<?php echo wp_kses_post( $product->get_price_html() ); ?>
								</span>
							</div>
							<div class="bc-upsell-actions">
								<?php
								if ( $product->is_type( 'variable' ) ) :
									if ( ! empty( $popsi_cart_product_variations ) ) :
										?>
										<div class="bc-upsell-variation-selects">
											<?php foreach ( $popsi_cart_attribute_selects as $popsi_cart_attribute_select ) : ?>
												<div class="bc-upsell-select-wrap">
													<select
														class="bc-upsell-select"
														data-product-id="<?php echo esc_attr( get_the_ID() ); ?>"
														data-attribute-key="<?php echo esc_attr( $popsi_cart_attribute_select['key'] ); ?>"
													>
														<option value=""><?php echo esc_html( sprintf( '-- %s --', $popsi_cart_attribute_select['label'] ) ); ?></option>
														<?php foreach ( $popsi_cart_attribute_select['options'] as $popsi_cart_attribute_option ) : ?>
															<option
																value="<?php echo esc_attr( $popsi_cart_attribute_option['value'] ); ?>"
																<?php selected( $popsi_cart_default_attributes[ sanitize_title( $popsi_cart_attribute_select['name'] ) ] ?? '', $popsi_cart_attribute_option['value'] ); ?>
															>
																<?php echo esc_html( $popsi_cart_attribute_option['label'] ); ?>
															</option>
														<?php endforeach; ?>
													</select>
													<span class="bc-upsell-select-icon">
														<?php
														$popsi_cart_icon_name  = 'chevron-down';
														$popsi_cart_icon_class = '';
														include POPSI_CART_PATH . 'templates/icons.php';
														?>
													</span>
												</div>
											<?php endforeach; ?>
										</div>
										<?php
								endif;
								endif;
								?>
								<button class="bc-upsell-add"
									onmouseenter="this.style.backgroundColor = '<?php echo esc_attr( $popsi_cart_btn_hover_color ); ?>'; this.style.color = '<?php echo esc_attr( $popsi_cart_btn_hover_text_color ); ?>'"
									onmouseleave="this.style.backgroundColor = '<?php echo esc_attr( $popsi_cart_btn_color ); ?>'; this.style.color = '<?php echo esc_attr( $popsi_cart_btn_text_color ); ?>'"
									style="background-color: <?php echo esc_attr( $popsi_cart_btn_color ); ?>; color: <?php echo esc_attr( $popsi_cart_btn_text_color ); ?>; border-radius: <?php echo esc_attr( $popsi_cart_btn_radius ); ?>;"
									data-id="<?php echo esc_attr( get_the_ID() ); ?>">
									<?php echo esc_html( $popsi_cart_settings['upsell_btn_text'] ?? 'Add' ); ?>
								</button>
							</div>
						</div>
					</div>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</div>
	<?php endif; ?>
<?php endif; ?>
