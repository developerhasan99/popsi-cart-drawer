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

					$popsi_cart_prices_json        = '';
					$popsi_cart_product_variations = array();
					if ( $product->is_type( 'variable' ) ) {
						$popsi_cart_p_map              = array();
						$popsi_cart_product_variations = $product->get_available_variations();
						foreach ( $popsi_cart_product_variations as $popsi_cart_v ) {
							$popsi_cart_p_map[ $popsi_cart_v['variation_id'] ] = wp_strip_all_tags( wc_price( $popsi_cart_v['display_price'] ) );
						}
						$popsi_cart_prices_json = wp_json_encode( $popsi_cart_p_map );
					}
					?>
					<div class="bc-upsell-item" data-id="<?php echo esc_attr( get_the_ID() ); ?>" 
					<?php
					if ( $popsi_cart_prices_json ) {
						echo ' data-prices="' . esc_attr( $popsi_cart_prices_json ) . '"';}
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
										<div class="bc-upsell-select-wrap">
											<select class="bc-upsell-select" data-product-id="<?php echo esc_attr( get_the_ID() ); ?>">
												<?php 
												// Debug: Check product attributes
												$product_attributes = $product->get_variation_attributes();
												echo '<!-- Product attributes: ' . print_r( $product_attributes, true ) . ' -->';
												
												// Work directly with existing variations
												foreach ( $popsi_cart_product_variations as $variation ) :
													// Get the attributes for this variation
													$variation_attributes = $variation['attributes'];
													
													// Prepare display attributes and option data
													$display_attributes = array();
													$option_attributes = array();
													
													foreach ( $variation_attributes as $attr_key => $attr_value ) {
														// For display, include non-empty values
														if ( ! empty( $attr_value ) ) {
															$display_attributes[] = $attr_value;
														}
														
														// For data attributes, include all attributes (even empty ones)
														// This ensures required fields like Logo are properly handled
														$option_attributes[$attr_key] = $attr_value;
													}
													
													// Skip if we don't have any display attributes
													if ( empty( $display_attributes ) ) {
														continue;
													}
													
													$matching_variation_id = $variation['variation_id'];
													
													if ( $matching_variation_id ) :
														?>
														<option value="<?php echo esc_attr( $matching_variation_id ); ?>" 
															data-attributes="<?php echo esc_attr( wp_json_encode( $option_attributes ) ); ?>">
															<?php 
															echo esc_html( implode( ' / ', $display_attributes ) );
															?>
														</option>
														<?php
													endif;
												endforeach;
												?>
											</select>
											<span class="bc-upsell-select-icon">
												<?php
												$popsi_cart_icon_name  = 'chevron-down';
												$popsi_cart_icon_class = '';
												include POPSI_CART_PATH . 'templates/icons.php';
												?>
											</span>
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

<!-- TODO: fix variable product selection for any combination -->