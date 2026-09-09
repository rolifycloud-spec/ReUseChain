<?php
/**
 * Register Link template
 */
if ( ! $settings['show_register_link'] ) {
	return;
}

if ( is_user_logged_in() && ! jet_blocks_integration()->in_elementor() ) {
	return;
}

$url = $this->__get_url( $settings, 'register_link_url' );

?>
<div class="jet-auth-links__section jet-auth-links__register">
	<?php $this->__html( 'register_prefix', '<div class="jet-auth-links__prefix">%s</div>' ); ?>
	<a class="jet-auth-links__item" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( ! empty( $settings['register_link_text'] ) ? wp_strip_all_tags( $settings['register_link_text'] ) : esc_html__( 'Register', 'jet-blocks' ) ); ?>"><?php
		$this->__icon( 'register_link_icon', '<span class="jet-auth-links__item-icon jet-blocks-icon">%s</span>' );
		$this->__html( 'register_link_text', '<span class="jet-auth-links__item-text">%s</span>' );
	?></a>
</div>
