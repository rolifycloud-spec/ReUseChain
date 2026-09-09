<?php
/**
 * Login Link template
 */
if ( ! $settings['show_login_link'] ) {
	return;
}

if ( is_user_logged_in() && ! jet_blocks_integration()->in_elementor() ) {
	return;
}

$url = $this->__get_url( $settings, 'login_link_url' );

?>
<div class="jet-auth-links__section jet-auth-links__login">
	<?php $this->__html( 'login_prefix', '<div class="jet-auth-links__prefix">%s</div>' ); ?>
	<a class="jet-auth-links__item" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( ! empty( $settings['login_link_text'] ) ? wp_strip_all_tags( $settings['login_link_text'] ) : esc_html__( 'Login', 'jet-blocks' ) ); ?>"><?php
		$this->__icon( 'login_link_icon', '<span class="jet-auth-links__item-icon jet-blocks-icon">%s</span>' );
		$this->__html( 'login_link_text', '<span class="jet-auth-links__item-text">%s</span>' );
	?></a>
</div>
