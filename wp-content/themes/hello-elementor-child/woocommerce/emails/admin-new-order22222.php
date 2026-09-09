<?php
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php printf( esc_html__( 'You’ve received the following order from %s:', 'woocommerce' ), $order->get_formatted_billing_full_name() ); ?></p>

<?php
// Get product authors' emails
$author_emails = [];

foreach ( $order->get_items() as $item ) {
    $product_id = $item->get_product_id();
    $author_id = get_post_field( 'post_author', $product_id );
    $author_email = get_the_author_meta( 'user_email', $author_id );

    if ( !empty($author_email) && !in_array($author_email, $author_emails) ) {
        $author_emails[] = $author_email;
    }
}

// Convert array to string
$recipients = implode(', ', $author_emails);

// Display the recipient in the email (for debugging)
if (!empty($recipients)) {
    echo '<p><strong>' . esc_html__('Order Notification Sent To:', 'woocommerce') . '</strong> ' . esc_html($recipients) . '</p>';
}

// DEBUGGING: Log email sending
error_log('New Order Email should be sent to: ' . print_r($recipients, true));

/*
 * Send the email directly (force send)
 */
if (!empty($author_emails)) {
    wp_mail($author_emails, 'New Order Received', 'You have a new order. Please check your dashboard.');
}

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
