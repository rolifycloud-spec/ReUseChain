<?php
/**
 * templates loader view
 */
?>
<div class="elementor-library-error">
	<div class="elementor-library-error-message"><?php
		esc_html_e( 'Template couldn\'t be loaded. Please activate you license key before.', 'jet-theme-core' );
	?></div>
	<div class="elementor-library-error-link"><?php
		printf(
			'<a class="template-library-activate-license" href="%1$s" target="_blank">%2$s %3$s</a>',
			esc_url( \Jet_Theme_Core\Utils::active_license_link() ),
			'<i class="fa fa-external-link" aria-hidden="true"></i>',
			esc_html__( 'Activate license', 'jet-theme-core' )
		);
	?></div>
</div>
