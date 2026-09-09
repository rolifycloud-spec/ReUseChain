<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_CHILD_VERSION', '2.0.0' );

/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function hello_elementor_child_scripts_styles() {

	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		HELLO_ELEMENTOR_CHILD_VERSION
	);

}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20 );

function linkovani_kupci_shortcode() {
    global $wpdb;

    // Get the current post ID and check if it is valid
    $current_post_id = get_the_ID();
    if (!is_int($current_post_id) || $current_post_id <= 0) {
        return ''; // Return nothing if the post ID is invalid
    }

    // Define the custom table name directly, ensure it's properly escaped for database usage
    $zahtjev_preuzimanje_meta_table = $wpdb->prefix . 'zahtjev_preuzimanje_meta';


    // SQL query to get distinct kupci IDs linked to 'zahtjev-preuzimanje' posts
    // where id_oglasa matches the current post and status_zahtjeva is 'preuzeto'
    $query = "
        SELECT DISTINCT zpm.id_kupca
        FROM {$wpdb->posts} AS p
        INNER JOIN {$zahtjev_preuzimanje_meta_table} AS zpm 
            ON p.ID = zpm.object_ID
        WHERE p.post_type = %s
          AND p.post_status = %s
          AND zpm.id_oglasa = %d
          AND zpm.status_zahtjeva = %s
    ";

    // Execute the query with prepared parameters
    $results = $wpdb->get_results(
        $wpdb->prepare($query, 'zahtjev-preuzimanje', 'publish', $current_post_id, 'preuzeto')
    );

    // Check if there are any kompanije; if not, return an empty string to hide the widget
    if (empty($results)) {
        return ''; // No kompanije found, widget remains invisible
    }

    // Collect linked kompanije in an array
    $kupci_links = [];
    foreach ($results as $row) {
        // Sanitize and validate user ID before fetching user data
        $id_kupca = intval($row->id_kupca);
        if ($id_kupca <= 0) {
            continue; // Skip if invalid user ID
        }

        // Fetch user data for the given `id_kupca`
        $user_info = get_userdata($id_kupca);
        if ($user_info) {
            // Get and sanitize `naziv` meta field
            $naziv_kupca = get_user_meta($id_kupca, 'naziv', true);
            $naziv_kupca = esc_html($naziv_kupca);

            // Construct the user profile link safely
			$profile_url = esc_url(home_url("/korisnik/{$id_kupca}/info/"));

            // Add linked kompanija to the array
            $kupci_links[] = "<a href='{$profile_url}'>{$naziv_kupca}</a>";
        }
    }
	
	if (!empty($kupci_links)) {
        return '<b>Oglas preuzeli:</b> ' . implode(', ', $kupci_links);
    }

    return '';
}

// Register the shortcode
add_shortcode('linkovani_kupci', 'linkovani_kupci_shortcode');

function vrijednost_kupljenih_oglasa($atts) {
    global $wpdb;

    // Parse the shortcode attributes, defaulting to 'sum' for operation and 'true' for using current user
    $atts = shortcode_atts(
        array(
            'operation' => 'sum',        // Default operation
            'use_current_user' => 'true', // Whether to use the current user ID (default: true)
            'user_id' => 0,              // If use_current_user is false, use this user ID
        ),
        $atts,
        'calculate_vrijednost_serialized_user'
    );

    // Determine the user ID to use based on the 'use_current_user' attribute
    $user_id = ($atts['use_current_user'] === 'true') ? get_current_user_id() : (int)$atts['user_id'];

    // Validate user ID
    if ($user_id === 0) {
        return number_format(0).' KM';
    }

    // Query to get only 'preuzete_kolicine' from '5p9xtk_zahtjev_preuzimanje_meta' where 'status_zahtjeva' is 'preuzeto'
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT preuzete_kolicine
             FROM {$wpdb->prefix}zahtjev_preuzimanje_meta
             WHERE id_kupca = %d
             AND status_zahtjeva = %s",
            $user_id,
            'preuzeto'
        )
    );

    // Check if any results were returned
    if (empty($results)) {
        return number_format(0).' KM';
    }

    $entry_sums = []; // Array to store the sum of each 'preuzete_kolicine' entry for this user

    // Loop through each row and deserialize 'preuzete_kolicine' data
    foreach ($results as $row) {
        // Attempt to deserialize the data
        $data = maybe_unserialize($row->preuzete_kolicine);

        // Check if deserialization was successful and data is an array
        if (is_array($data)) {
            $current_sum = 0;

            // Sum the 'vrijednost_donirane_hrane_prema_vrsti' values within this entry
            foreach ($data as $item) {
                if (isset($item['vrijednost_zahtjev'])) {
                    $current_sum += (float)$item['vrijednost_zahtjev'];
                }
            }

            // Store the sum of this entry's 'preuzete_kolicine' values
            $entry_sums[] = $current_sum;
        }
    }

    // Perform the desired operation
    $result = 0;
    if ($atts['operation'] === 'average' && count($entry_sums) > 0) {
        // Calculate average of entry sums
        $result = array_sum($entry_sums) / count($entry_sums);
    } else {
        // Default to sum of all entry sums
        $result = array_sum($entry_sums);
    }

    // Return the result, formatted to two decimal places
    return number_format($result, 0).' KM';
}


// Register shortcode
add_shortcode('vrijednost_kupljenih_oglasa', 'vrijednost_kupljenih_oglasa');


// Hook into the 'wp' action to track the last viewed post
function track_last_viewed_oglasi() {
    if (is_singular('product') && is_user_logged_in()) {
        $current_user_id = get_current_user_id();
        $current_post_id = get_the_ID();

        // Retrieve the existing array of last viewed post IDs
        $last_viewed_posts = get_user_meta($current_user_id, 'last_viewed_oglasi', true);

        // Initialize as an array if it's not set or not an array
        if (!is_array($last_viewed_posts)) {
            $last_viewed_posts = [];
        }

        // Remove the post ID if it already exists in the array to avoid duplicates
        if (($key = array_search($current_post_id, $last_viewed_posts)) !== false) {
            unset($last_viewed_posts[$key]);
        }

        // Add the current post ID to the beginning of the array
        array_unshift($last_viewed_posts, $current_post_id);

        // Limit the array to the last 10 entries
        $last_viewed_posts = array_slice($last_viewed_posts, 0, 5);

        // Update user meta with the new array of last viewed post IDs
        update_user_meta($current_user_id, 'last_viewed_oglasi', $last_viewed_posts);

        // Clear the transient cache for this user to show updated data immediately
        delete_transient('last_viewed_oglasi_' . $current_user_id);
    }
}
add_action('wp', 'track_last_viewed_oglasi');


function display_last_viewed_oglasi() {
    if (is_user_logged_in()) {
        $current_user_id = get_current_user_id();
        $transient_key = 'last_viewed_oglasi_' . $current_user_id;

        // Try to get the cached output from the transient
        $output = get_transient($transient_key);

        if ($output === false) {
            $last_viewed_posts = get_user_meta($current_user_id, 'last_viewed_oglasi', true);

            if (is_array($last_viewed_posts) && !empty($last_viewed_posts)) {
                $output = '';

                foreach ($last_viewed_posts as $post_id) {
                    $post = get_post($post_id);
                    if ($post) {
                        $output .= '<a href="' . get_permalink($post_id) . '">' . esc_html($post->post_title) . '</a><br>';
                    }
                }
            } else {
                $output = '<p>Nema pregledanih oglasa.</p>';
            }

            // Cache the output for 5 minutes (300 seconds)
            set_transient($transient_key, $output, 300);
        }

        return $output;
    }
    return '<p>Ulogujte se kako biste vidjeli svoju historiju pregledanih oglasa.</p>';
}
add_shortcode('last_viewed_oglasi', 'display_last_viewed_oglasi');

/* 
function block_wp_admin_access() {
    // Check if the user is not logged in and is trying to access wp-admin or wp-login.php
    if (!is_user_logged_in() && (is_admin() || $GLOBALS['pagenow'] === 'wp-login.php')) {
        // Redirect non-logged-in users to the home page
        wp_redirect(home_url());
        exit;
    }
}
add_action('init', 'block_wp_admin_access'); */


/* 
Purpose: This function restricts access to the WordPress dashboard for users with the roles of 'donator'.

How it works:
It checks if the current user is trying to access the WordPress admin dashboard (is_admin()) and ensures that the request is not an AJAX request (!defined('DOING_AJAX')).

It retrieves the current user's roles using wp_get_current_user().
If the user's role is 'donator', the function redirects them to the homepage (wp_redirect(home_url())) and terminates the script (exit).

Outcome: Users with the roles 'donator' cannot access the WordPress dashboard; they are redirected to the homepage.*/


/* // Restrict access to the WordPress dashboard for Donator and Posrednik roles
function restrict_dashboard_access() {
    if (is_admin() && !defined('DOING_AJAX')) {
        $user = wp_get_current_user();
        if (in_array('donator', (array) $user->roles)) {
            wp_redirect(home_url());
            exit;
        }
    }
}
add_action('admin_init', 'restrict_dashboard_access');  */

// Prevent users from logging in until approved
add_filter('wp_authenticate_user', 'check_user_status', 10, 2);
function check_user_status($user, $password) {
    if (get_user_meta($user->ID, 'is_approved', true) != 1) {
        $error = new WP_Error();
        $error->add('not_approved', __('Vaš korisnički račun je na čekanju. Dobit ćete email kada administrator odobri vaš račun.'));
        return $error;
    }
    return $user;
}

// Function to update the user's approval status and send the appropriate email
function update_user_status($user_id, $status, $rejection_message = '') {
    // Update user meta for approval status
    update_user_meta($user_id, 'is_approved', $status);
    
    if ($status == 2) {
        update_user_meta($user_id, 'rejection_message', sanitize_textarea_field($rejection_message));
        send_user_rejection_email($user_id, $rejection_message);
    } else {
        delete_user_meta($user_id, 'rejection_message');
        if ($status == 1) {
            send_user_approval_email($user_id);
        }
    }
}

// Send email notification to admin on new user registration
add_action('user_register', 'pending_user_approval');
function pending_user_approval($user_id) {
    // Update the 'is_approved' meta to 0 (Pending)
    update_user_meta($user_id, 'is_approved', 0);
    
    // Get user info, including email
    $user_info = get_userdata($user_id);
    $user_email = $user_info->user_email;
    $user_login = $user_info->user_login;

    // Admin email where the notification will be sent
	// List of admin emails
    $admin_emails = [
        'developer@mozaik.ba',
        'miljan@mozaik.ba',
		'ajla@mozaik.ba',
		'ajna@mozaik.ba'
    ];
    // Create the link for user approval
	$approval_link = site_url("/korisnik/{$user_id}/info/");

    // Prepare the email message
    $message = "Novi korisnik se registrovao i čeka odobrenje registracije.\n\n";
    $message .= "Korisničko ime: " . $user_login . "\n";
    $message .= "Email: " . $user_email . "\n";
    $message .= "Odobrite korisnika korištenjem formulara za administratore na linku:\n";
    $message .= $approval_link;

    // Send email notification to the admin
    wp_mail($admin_emails, 'Novi korisnik čeka odobrenje za registraciju', $message);
}


// Custom admin column for approval status
add_filter('manage_users_columns', 'add_approval_column');
function add_approval_column($columns) {
    $columns['user_approval_status'] = 'Approval Status';
    return $columns;
}

add_action('manage_users_custom_column', 'show_approval_status', 10, 3);
function show_approval_status($value, $column_name, $user_id) {
    if ($column_name == 'user_approval_status') {
        // Get the 'is_approved' value from the user meta
        $is_approved = get_user_meta($user_id, 'is_approved', true);

        // Return the appropriate label based on the 'is_approved' value
        if ($is_approved == '1') {
            return 'Approved';
        } elseif ($is_approved == '0') {
            return 'Pending';
        } elseif ($is_approved == '2') {
            return 'Rejected';
        } else {
            return 'N/A'; // Default case for unexpected values
        }
    }
    return $value;
}

// Function to send approval email
function send_user_approval_email($user_id) {
    $user_info = get_userdata($user_id);
    $user_email = $user_info->user_email;

    $subject = 'Vaš korisnički račun je odobren';
    $message = "Poštovani " . $user_info->user_login . ",\n\n";
    $message .= "Vaša prijava na naš portal je odobrena."."\n";
    $message .= "Prijavite se ovdje: " . home_url('/prijava/') . "\n\n";
    $message .= "Srdačan pozdrav,\nReusechain.ba tim";

    wp_mail('azraa.kadric@gmail.com', $subject, $message);
}


// Function to send rejection email
function send_user_rejection_email($user_id, $rejection_message) {
    $user_info = get_userdata($user_id);
    $user_email = $user_info->user_email;

    $subject = 'Vaša prijava je odbijena';
    $message = "Poštovani " . $user_info->user_login . ",\n\n";
    $message .= "Nažalost, vaša prijava je odbijena. Razlog:\n";
    $message .= $rejection_message ? $rejection_message . "\n\n" : "Nije naveden razlog.\n\n";
    $message .= "Srdačan pozdrav,\nVaš Reusechain.ba tim";

    wp_mail('azraa.kadric@gmail.com', $subject, $message);
}

// Approve or reject user manually via admin action
add_action('admin_init', 'approve_user_manually');
function approve_user_manually() {
    if (isset($_GET['action']) && in_array($_GET['action'], ['approve_user', 'reject_user']) && isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);

        if ($_GET['action'] == 'approve_user') {
            update_user_status($user_id, 1); // Approve user
        }

        if ($_GET['action'] == 'reject_user') {
            // Redirect to user profile to input the rejection message
            wp_redirect(admin_url("user-edit.php?user_id={$user_id}&action=reject_user"));
            exit;
        }

        wp_redirect(admin_url('users.php'));
        exit;
    }
}

// Save approval status when updating user profile
add_action('personal_options_update', 'save_approval_status');
add_action('edit_user_profile_update', 'save_approval_status');

function save_approval_status($user_id) {
    if (!current_user_can('manage_options')) {
        return;
    }

    $current_status = get_user_meta($user_id, 'is_approved', true);
    $new_status = intval($_POST['is_approved']);
    $rejection_message = isset($_POST['rejection_message']) ? sanitize_textarea_field($_POST['rejection_message']) : '';

    // Only update if the status has changed
    if ($current_status != $new_status) {
        update_user_status($user_id, $new_status, $rejection_message);
    }
}

// Add approve or reject actions to the user list view
add_filter('user_row_actions', 'add_approve_reject_user_link', 10, 2);
function add_approve_reject_user_link($actions, $user) {
    if (get_user_meta($user->ID, 'is_approved', true) != 1) {
        $actions['approve_user'] = "<a href='" . admin_url("users.php?action=approve_user&user_id={$user->ID}") . "'>Approve</a>";
    }
    return $actions;
}


// Handle the approval or rejection from the user list view
add_action('admin_init', 'handle_user_approval_rejection');
function handle_user_approval_rejection() {
    if (isset($_GET['action']) && in_array($_GET['action'], ['approve_user', 'reject_user']) && isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);

        if ($_GET['action'] == 'approve_user') {
            update_user_meta($user_id, 'is_approved', 1);
            send_user_approval_email($user_id);
        }

        if ($_GET['action'] == 'reject_user') {
            update_user_meta($user_id, 'is_approved', 2);

            // Display a form for entering the rejection message
            wp_redirect(admin_url("user-edit.php?user_id={$user_id}&action=reject_user"));
            exit;
        }

        wp_redirect(admin_url('users.php'));
        exit;
    }
}


// Add approval status field to user profile page
add_action('show_user_profile', 'display_approval_status');
add_action('edit_user_profile', 'display_approval_status');
function display_approval_status($user) {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $is_approved = get_user_meta($user->ID, 'is_approved', true);
    $rejection_message = get_user_meta($user->ID, 'rejection_message', true);
    ?>
    <h3 id="approval_status_section">User Approval Status</h3>
    <table class="form-table">
        <tr>
            <th><label for="is_approved">Approval Status</label></th>
            <td>
                <select name="is_approved" id="is_approved">
                    <option value="0" <?php selected($is_approved, 0); ?>>Pending</option>
                    <option value="1" <?php selected($is_approved, 1); ?>>Approved</option>
                    <option value="2" <?php selected($is_approved, 2); ?>>Odbijeno</option>
                </select>
                <p class="description">Select the approval status for this user.</p>
            </td>
        </tr>
        <tr id="rejection_message_row" style="display: <?php echo ($is_approved == 2) ? 'table-row' : 'none'; ?>;">
            <th><label for="rejection_message">Razlog odbijanja</label></th>
            <td>
                <textarea name="rejection_message" id="rejection_message" rows="5" cols="30"><?php echo esc_textarea($rejection_message); ?></textarea>
                <p class="description">Unesite razlog za odbijanje korisnika.</p>
            </td>
        </tr>
    </table>
    <script type="text/javascript">
        document.getElementById('is_approved').addEventListener('change', function() {
            var rejectionMessageRow = document.getElementById('rejection_message_row');
            if (this.value == '2') { // Show rejection message input when "Odbijeno" is selected
                rejectionMessageRow.style.display = 'table-row';
            } else {
                rejectionMessageRow.style.display = 'none';
            }
        });
    </script>
    <?php
}
/**
 * Restrict visitors, but allow product pages and other selected paths for guests.
 */
function rc_restrict_guests_by_path() {

    // Don't run in admin or AJAX
    if ( is_admin() || wp_doing_ajax() ) {
        return;
    }

    $request_uri  = $_SERVER['REQUEST_URI'] ?? '/';
    $request_path = strtok( $request_uri, '?' );

    // Always allow login page, wp-login, favicon, and REST API
    if (
        strpos( $request_path, '/prijava/' ) === 0 ||
        strpos( $request_path, '/wp-login.php' ) === 0 ||
        $request_path === '/favicon.ico' ||
        strpos( $request_path, '/wp-json/' ) === 0
    ) {
        return;
    }

    // Allow everything for logged-in users
    if ( is_user_logged_in() ) {
        return;
    }

    // Allow WooCommerce category pages (example: /kategorija/...)
    if ( strpos( $request_path, '/kategorija/' ) === 0 ) {
        return;
    }

    // ✅ Allow single WooCommerce product pages
    if ( function_exists( 'is_singular' ) && is_singular( 'product' ) ) {
        return;
    }

    // Allow WooCommerce shop page
    if ( function_exists( 'is_shop' ) && is_shop() ) {
        return;
    }

    // Allow regular blog posts
    if ( is_singular( 'post' ) ) {
        return;
    }

    // Allow specific pages (public)
    $allowed_pages = array(
        13652,16236,16679,13862,13859,13694,14197,
        2501,6611,2585,2377,5567,10178,10173,10180,
        10168,24,7675,2617,10176
    );

    $queried_page_id = get_queried_object_id();

    if ( in_array( $queried_page_id, $allowed_pages, true ) ) {
        return;
    }

    // Redirect all others to login
    $current_url = home_url( add_query_arg( null, null ) );
    $login_url   = get_permalink( 2585 ) . '?redirect_to=' . urlencode( $current_url );

    wp_redirect( $login_url );
    exit;
}
add_action( 'template_redirect', 'rc_restrict_guests_by_path', 1 );


//redirect


add_action('jet-form-builder/custom-action/handle-login-redirect', function($form_data, $form) {
    $login_form_id = 2745;

    if (isset($form_data['__form_id']) && (int) $form_data['__form_id'] === $login_form_id) {
        // Capture the `redirect_to` from the form data
        $redirect_to = $form_data['redirect_to'] ?? '';

        if (!empty($redirect_to)) {
            wp_safe_redirect(esc_url_raw($redirect_to));
            exit;
        } else {
            wp_safe_redirect(home_url());
            exit;
        }
    } else {
    }
}, 10, 2);




/*
wp_logout hook: This hook is triggered when a user logs out of WordPress.
wp_redirect(home_url()): This redirects the user to the homepage after logging out.
exit(): This ensures that the redirection happens immediately and no further code is executed.
*/
function redirect_after_logout() {
    wp_redirect(home_url());
    exit();
}
add_action('wp_logout', 'redirect_after_logout');

function linked_naziv_kompanije($user_id) {
    // 1. Validate the user ID early.
    if (!is_numeric($user_id) || !get_userdata($user_id)) {
        return ''; 
    }

    // 2. For guest (non-logged-in) users, show only a blurred placeholder.
    if (!is_user_logged_in()) {
        // Generate a random placeholder string (8 chars, no special symbols).
        $random_placeholder = wp_generate_password(8, false, false); 
        // Return a dummy "link" with a CSS class you can blur.
        return '<a href="javascript:void(0);" class="blurred-link">' 
                . esc_html($random_placeholder) 
                . '</a>';
    }

    // 3. If the user is logged in, show the real content.
    $user_info = get_userdata($user_id);
    $naziv     = get_user_meta($user_id, 'naziv', true);

    // If no name is set, return empty.
    if (empty($naziv)) {
        return '';
    }

    // Construct the real profile URL only for logged-in users.
    $profile_url = site_url("/korisnik/{$user_id}/info/");

 
 
 // Return the real clickable link with company name.
	return '<a href="' . esc_url($profile_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($naziv) . '</a>';

}

// Make sure your custom callback is recognized by JetEngine.
add_filter('jet-engine/listings/allowed-callbacks', function($callbacks) {
    $callbacks['linked_naziv_kompanije'] = 'linked_naziv_kompanije';
    return $callbacks;
});

function jetengine_limit_gallery() {
    $gallery = get_post_meta(get_the_ID(), 'galerija', true);
    if (!$gallery || !is_array($gallery)) return '';

    $gallery = array_slice($gallery, 0, 1); // Limit to 1 image
    $post_url = get_permalink(); // Get current post URL

    $output = '<div class="jet-limited-gallery">';
    foreach ($gallery as $image) {
        $output .= '<a href="' . esc_url($post_url) . '">'; // Wrap image in a link
        $output .= '<img src="' . esc_url($image['url']) . '" alt="' . esc_attr($image['alt']) . '" style="width:100%; height:200px; object-fit:cover;">';
        $output .= '</a>';
    }
    $output .= '</div>';
    
    return $output;
}
add_shortcode('jet_gallery_limit', 'jetengine_limit_gallery');






function get_user_approval_status_by_listing( $user_id ) {
    // Ensure the user ID is valid
    if (!is_numeric($user_id) || !get_userdata($user_id)) {
        return ''; // Return an empty string if user ID is not valid
    }

    // Retrieve the 'is_approved' meta field for the user
    $is_approved = get_user_meta($user_id, 'is_approved', true);

    // Determine the approval status based on the value of 'is_approved'
    if ($is_approved === '1') {
        return 'Odobren';
    } elseif ($is_approved === '0') {
        return 'Na čekanju';
    } elseif ($is_approved === '2') {
        return 'Odbijen';
    } else {
        return 'N/A'; // Return a default value if no valid status is found
    }
}

// Add the callback to the allowed JetEngine callbacks
add_filter('jet-engine/listings/allowed-callbacks', function($callbacks) {
    $callbacks['get_user_approval_status_by_listing'] = 'Get User Approval Status by Listing';
    return $callbacks;
});


/* Disable WordPress Admin Bar for all users except administrators */

add_filter( 'show_admin_bar', 'restrict_admin_bar' );
 
function restrict_admin_bar( $show ) {
    return current_user_can( 'administrator' ) ? true : false;
}



/**
 * Fetch WooCommerce products from top-level categories and sum their prices.
 */
function get_top_level_category_totals() {
    // Get top-level WooCommerce categories (categories without a parent)
    $top_level_terms = get_terms(array(
        'taxonomy'   => 'product_cat',
        'parent'     => 0, // Get only top-level categories
        'hide_empty' => true, // Only show categories with products
    ));

    // Check if categories were retrieved
    if (empty($top_level_terms) || is_wp_error($top_level_terms)) {
        return rest_ensure_response(array('error' => 'No top-level categories found.'));
    }

    // Extract top-level category IDs
    $top_level_cat_ids = wp_list_pluck($top_level_terms, 'term_id');

    // Query WooCommerce products from these top-level categories
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1, // Get all products
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $top_level_cat_ids, // Only products in top-level categories
                'operator' => 'IN',
            ),
        ),
    );

    $query = new WP_Query($args);
    $category_totals = array(); // Store category price totals

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            // Get WooCommerce product details
            $product_id   = get_the_ID();
            $product      = wc_get_product($product_id);
            $product_price = (float) $product->get_price(); // Convert price to float

            // Get the product categories
            $categories = wp_get_post_terms($product_id, 'product_cat');

            // Find the top-level category or use the first one
            $selected_category = '';
            foreach ($categories as $category) {
                if ($category->parent == 0) { // Check if it's a top-level category
                    $selected_category = $category->name;
                    break;
                }
            }

            // If no top-level category found, use the first assigned category
            if (empty($selected_category) && !empty($categories)) {
                $selected_category = $categories[0]->name;
            }

            // Sum up prices by category
            if (!empty($selected_category)) {
                if (!isset($category_totals[$selected_category])) {
                    $category_totals[$selected_category] = 0;
                }
                $category_totals[$selected_category] += $product_price;
            }
        }
        wp_reset_postdata();
    }

    // Format the response
    $response = array();
    foreach ($category_totals as $category => $total_price) {
        $response[] = array(
            'category' => $category,
            'total_price' => number_format($total_price, 2) . ' KM', // Format as currency
        );
    }

    return rest_ensure_response($response);
}

// Register REST API route
add_action('rest_api_init', function () {
    register_rest_route('wp/v2', '/oglasi-vrste-materijala', array(
        'methods'  => 'GET',
        'callback' => 'get_top_level_category_totals',
    ));
});



// Force all products to be of type "Simple Product" upon saving.
function force_simple_product_type($post_id) {
    if (get_post_type($post_id) === 'product') {
        wp_set_object_terms($post_id, 'simple', 'product_type', false);
    }
}
add_action('save_post', 'force_simple_product_type');

// Restrict the WooCommerce product type selector to only allow "Simple Product".
function restrict_woocommerce_product_types($types) {
    // Keep only "simple" products
    return [
        'simple' => $types['simple'],
    ];
}
add_filter('product_type_selector', 'restrict_woocommerce_product_types');

// Remove unnecessary product data tabs from the product editor.
function remove_unnecessary_product_data_tabs($tabs) {
    unset($tabs['shipping']);   // Shipping options
    unset($tabs['linked_product']); // Upsells & cross-sells
    unset($tabs['attribute']);  // Attributes tab
    return $tabs;
}
add_filter('woocommerce_product_data_tabs', 'remove_unnecessary_product_data_tabs');

// Remove the "Virtual" and "Downloadable" product options from the product editor.
function remove_virtual_downloadable_fields($options) {
    unset($options['virtual']);
    unset($options['downloadable']);
    return $options;
}
add_filter('product_type_options', 'remove_virtual_downloadable_fields');

// Hide the product type dropdown and WooCommerce marketplace suggestions in the admin panel.
function disable_product_type_dropdown() {
    global $post;

    // Only apply the styles on the "Add New Product" screen
    if (!isset($post) || (isset($post->post_type) && $post->post_type === 'product' && $post->post_status === 'auto-draft')) {
        echo '<style>#product-type, .tisdk-suggestions_options, .marketplace-suggestions_options { display: none !important; }</style>';
    }
}
add_action('admin_head', 'disable_product_type_dropdown');


// Hide the Shipping Class dropdown from the product editor.
function remove_shipping_class_box() {
    echo '<style>#product_shipping_class { display: none !important; }</style>';
}
add_action('admin_head', 'remove_shipping_class_box');



// Hide the "Short Description" field from the WooCommerce product editor.
function remove_short_description_admin() {
    echo '<style>#postexcerpt { display: none !important; }</style>';
}
add_action('admin_head', 'remove_short_description_admin');

function display_orders_for_current_product() {
    if (!is_singular('product')) {
        return '<p style="color: red; font-size: 14px;">Ova opcija je dostupna samo na stranici proizvoda.</p>';
    }

    global $product;
    $product_id = get_the_ID();

    if (!$product_id) {
        return '<p style="font-size: 14px;">Proizvod nije pronađen.</p>';
    }

    $product_author_id = get_post_field('post_author', $product_id);

    if (get_current_user_id() !== (int) $product_author_id) {
        return '<p style="color: red; font-size: 14px;">Nemate dozvolu da pregledate narudžbe za ovaj proizvod.</p>';
    }

    ob_start();

    $orders_html = get_product_orders_page($product_id, 1);

    echo '<div id="product-orders-container">';
    echo '<div id="product-orders-list">' . $orders_html . '</div>';

    // Check if there are any orders before showing pagination
    if (strpos($orders_html, 'Nema narudžbi za ovaj proizvod.') === false) {
        echo '<div id="product-orders-pagination">
                <button id="prev-product-orders" class="orders-pagination-btn" data-page="1" disabled>&laquo; Prethodna</button>
                <span id="current-product-page">Stranica 1</span>
                <button id="next-product-orders" class="orders-pagination-btn" data-page="2">Sljedeća &raquo;</button>
              </div>';
    }

    echo '</div>';
    ?>
    <script>
        jQuery(document).ready(function($) {
            $('.orders-pagination-btn').on('click', function() {
                var page = $(this).data('page');
                var productId = <?php echo $product_id; ?>;

                $.ajax({
                    url: '<?php echo admin_url("admin-ajax.php"); ?>',
                    type: 'POST',
                    data: {
                        action: 'load_product_orders',
                        page: page,
                        product_id: productId
                    },
                    beforeSend: function() {
                        $('#product-orders-list').html('<p style="text-align:center;">Učitavanje...</p>');
                    },
                    success: function(response) {
                        $('#product-orders-list').html(response);

                        var totalPages = $('#product-orders-list').data('total-pages');
                        $('#current-product-page').text('Stranica ' + page);

                        $('#prev-product-orders').data('page', page - 1);
                        $('#next-product-orders').data('page', page + 1);

                        if (page <= 1) {
                            $('#prev-product-orders').prop('disabled', true);
                        } else {
                            $('#prev-product-orders').prop('disabled', false);
                        }

                        if (page >= totalPages) {
                            $('#next-product-orders').prop('disabled', true);
                        } else {
                            $('#next-product-orders').prop('disabled', false);
                        }

                        // Hide pagination if no orders exist
                        if ($('#product-orders-list').text().includes("Nema narudžbi za ovaj proizvod")) {
                            $('#product-orders-pagination').hide();
                        } else {
                            $('#product-orders-pagination').show();
                        }
                    }
                });
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('product_orders', 'display_orders_for_current_product');


function get_product_orders_page($product_id, $current_page) {
    $orders_per_page = 5;

    $args = array(
        'limit' => -1,
        'status' => array('completed', 'processing', 'on-hold'),
    );

    $orders = wc_get_orders($args);
    $matching_orders = [];

    foreach ($orders as $order) {
        foreach ($order->get_items() as $item) {
            if ($item->get_product_id() == $product_id) {
                $matching_orders[] = $order;
                break;
            }
        }
    }

    $total_orders = count($matching_orders);
    $total_pages = ceil($total_orders / $orders_per_page);
    $offset = ($current_page - 1) * $orders_per_page;

    $paged_orders = array_slice($matching_orders, $offset, $orders_per_page);

    ob_start();

    // If there are no orders, display a message
    if (empty($matching_orders)) {
        echo '<p style="text-align:center; font-size: 14px; color: red;">Nema narudžbi za ovaj proizvod.</p>';
    } else {
        echo '<table style="width:100%; border-collapse: collapse; margin-top: 10px; font-size: 14px;">
                <tr style="background-color: #f2f2f2; text-align: left; font-size: 14px;">
                    <th style="padding: 10px; border: 1px solid #ddd;">ID</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Status</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Datum</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Ukupno</th>
                </tr>';

        foreach ($paged_orders as $order) {
            $order_url = admin_url('post.php?post=' . $order->get_id() . '&action=edit');

            echo '<tr style="font-size: 14px;">
                    <td style="padding: 10px; border: 1px solid #ddd;">
                        <a href="' . esc_url($order_url) . '" target="_blank" style="text-decoration: none; color: #0073aa;">
                            #' . $order->get_id() . '
                        </a>
                    </td>
                    <td style="padding: 10px; border: 1px solid #ddd;">' . wc_get_order_status_name($order->get_status()) . '</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">' . $order->get_date_created()->format('d-m-Y H:i:s') . '</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">' . wc_price($order->get_total()) . '</td>
                </tr>';
        }

        echo '</table>';
    }

    echo '<script>jQuery("#product-orders-list").data("total-pages", ' . $total_pages . ');</script>';

    return ob_get_clean();

}

// AJAX handler for loading product orders
add_action('wp_ajax_load_product_orders', function() {
    if (!isset($_POST['product_id']) || !isset($_POST['page'])) {
        wp_send_json_error('Missing parameters.');
    }

    $product_id = intval($_POST['product_id']);
    $page = max(1, intval($_POST['page']));

    echo get_product_orders_page($product_id, $page);
    wp_die();
});

// Prevent guests from accessing orders
add_action('wp_ajax_nopriv_load_product_orders', function() {
    wp_send_json_error('Prijava je potrebna.');
});

function display_orders_for_my_products() {
    if (!is_user_logged_in()) {
        return '<p style="font-size: 14px;">Morate biti prijavljeni da biste vidjeli narudžbe.</p>';
    }

    ob_start();
    ?>
    <div id="orders-for-my-products-container">
        <div id="orders-for-my-products-list">
            <?php echo get_orders_for_my_products_page(1); // Load first page ?>
        </div>
        <div id="orders-for-my-products-pagination">
            <button id="prev-orders-page" class="orders-pagination-btn" data-page="1" disabled>&laquo; Prethodna</button>
            <span id="current-orders-page">Stranica 1</span>
            <button id="next-orders-page" class="orders-pagination-btn" data-page="2">Sljedeća &raquo;</button>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('.orders-pagination-btn').on('click', function() {
                var page = $(this).data('page');

                $.ajax({
                    url: '<?php echo admin_url("admin-ajax.php"); ?>',
                    type: 'POST',
                    data: {
                        action: 'load_orders_for_my_products',
                        page: page
                    },
                    beforeSend: function() {
                        $('#orders-for-my-products-list').html('<p style="text-align:center;">Učitavanje...</p>');
                    },
                    success: function(response) {
                        $('#orders-for-my-products-list').html(response);

                        // Update pagination
                        var totalPages = $('#orders-for-my-products-list').data('total-pages');
                        $('#current-orders-page').text('Stranica ' + page);

                        $('#prev-orders-page').data('page', page - 1);
                        $('#next-orders-page').data('page', page + 1);

                        if (page <= 1) {
                            $('#prev-orders-page').prop('disabled', true);
                        } else {
                            $('#prev-orders-page').prop('disabled', false);
                        }

                        if (page >= totalPages) {
                            $('#next-orders-page').prop('disabled', true);
                        } else {
                            $('#next-orders-page').prop('disabled', false);
                        }
                    }
                });
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('orders_for_my_products', 'display_orders_for_my_products');

function display_my_orders() {
    if (!is_user_logged_in()) {
        return '<p style="color: red; font-size: 14px;">Morate biti prijavljeni da biste videli svoje narudžbe.</p>';
    }

    ob_start();
    ?>
    <div id="my-orders-container">
        <div id="my-orders-list">
            <?php echo get_my_orders_page(1); // Load the first page ?>
        </div>
        <div id="my-orders-pagination">
            <button id="prev-page" class="orders-pagination-btn" data-page="1" disabled>&laquo; Prethodna</button>
            <span id="current-page">Stranica 1</span>
            <button id="next-page" class="orders-pagination-btn" data-page="2">Sljedeća &raquo;</button>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('.orders-pagination-btn').on('click', function() {
                var page = $(this).data('page');

                $.ajax({
                    url: '<?php echo admin_url("admin-ajax.php"); ?>',
                    type: 'POST',
                    data: {
                        action: 'load_my_orders',
                        page: page
                    },
                    beforeSend: function() {
                        $('#my-orders-list').html('<p style="text-align:center;">Učitavanje...</p>');
                    },
                    success: function(response) {
                        $('#my-orders-list').html(response);

                        // Update pagination
                        var totalPages = $('#my-orders-list').data('total-pages');
                        $('#current-page').text('Stranica ' + page);

                        $('#prev-page').data('page', page - 1);
                        $('#next-page').data('page', page + 1);

                        if (page <= 1) {
                            $('#prev-page').prop('disabled', true);
                        } else {
                            $('#prev-page').prop('disabled', false);
                        }

                        if (page >= totalPages) {
                            $('#next-page').prop('disabled', true);
                        } else {
                            $('#next-page').prop('disabled', false);
                        }
                    }
                });
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('my_orders', 'display_my_orders');

function get_my_orders_page($current_page) {
    $current_user_id = get_current_user_id();
    $orders_per_page = 5;

    $args = array(
        'limit' => -1, // Get all orders first
        'customer_id' => $current_user_id,
        'status' => array('completed', 'processing', 'on-hold'),
    );

    $orders = wc_get_orders($args);
    $total_orders = count($orders);
    $total_pages = ceil($total_orders / $orders_per_page);
    $offset = ($current_page - 1) * $orders_per_page;

    $paged_orders = array_slice($orders, $offset, $orders_per_page);

    ob_start();

    echo '<table style="width:100%; border-collapse: collapse; margin-top: 10px; font-size: 14px;">';
    echo '<tr style="background-color: #f2f2f2; text-align: left; font-size: 14px;">
            <th style="padding: 10px; border: 1px solid #ddd;">ID</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Status</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Datum</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Ukupno</th>
          </tr>';

    foreach ($paged_orders as $order) {
        $order_url = wc_get_endpoint_url('view-order', $order->get_id(), wc_get_page_permalink('myaccount'));

        echo '<tr style="font-size: 14px;">
                <td style="padding: 10px; border: 1px solid #ddd;">
                    <a href="' . esc_url($order_url) . '" target="_blank" style="text-decoration: none; color: #0073aa;">
                        #' . $order->get_id() . '
                    </a>
                </td>
                <td style="padding: 10px; border: 1px solid #ddd;">' . wc_get_order_status_name($order->get_status()) . '</td>
                <td style="padding: 10px; border: 1px solid #ddd;">' . $order->get_date_created()->format('d-m-Y H:i:s') . '</td>
                <td style="padding: 10px; border: 1px solid #ddd;">' . wc_price($order->get_total()) . '</td>
              </tr>';
    }

    echo '</table>';

    echo '<script>jQuery("#my-orders-list").data("total-pages", ' . $total_pages . ');</script>';

    return ob_get_clean();
}

// AJAX handler for logged-in users
add_action('wp_ajax_load_my_orders', function() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Morate biti prijavljeni.');
    }

    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    echo get_my_orders_page($page);
    wp_die();
});

// AJAX handler for guests (if needed)
add_action('wp_ajax_nopriv_load_my_orders', function() {
    wp_send_json_error('Prijava je potrebna.');
});



function get_orders_for_my_products_page($current_page) {
    if (!is_user_logged_in()) {
        return '<p style="font-size: 14px;">Morate biti prijavljeni da biste vidjeli narudžbe.</p>';
    }

    $current_user_id = get_current_user_id();
    $orders = wc_get_orders(array('limit' => -1)); // Get all orders
    $matching_orders = [];

    // Loop through orders and filter those containing products by the logged-in user
    foreach ($orders as $order) {
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $product_author_id = get_post_field('post_author', $product_id);

            if ($product_author_id == $current_user_id && $order->get_customer_id() != $current_user_id) {
                $matching_orders[] = $order;
                break; // Stop checking other items in this order
            }
        }
    }

    if (empty($matching_orders)) {
        return '<p style="font-size: 14px;">Nema narudžbi za vaše proizvode.</p>';
    }

    $orders_per_page = 5;
    $total_orders = count($matching_orders);
    $total_pages = ceil($total_orders / $orders_per_page);
    $offset = ($current_page - 1) * $orders_per_page;

    $paged_orders = array_slice($matching_orders, $offset, $orders_per_page);

    ob_start();

    echo '<table style="width:100%; border-collapse: collapse; margin-top: 10px; font-size: 13px;">';
    echo '<tr style="background-color: #f2f2f2; text-align: left; font-size: 13px;">
            <th style="padding: 10px; border: 1px solid #ddd;">ID</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Kupac</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Status</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Datum</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Ukupno</th>
          </tr>';

    foreach ($paged_orders as $order) {
        $order_url = wc_get_endpoint_url('view-order', $order->get_id(), wc_get_page_permalink('myaccount'));
        $customer = get_userdata($order->get_customer_id());
        $customer_name = $customer ? esc_html($customer->display_name) : 'Nepoznati kupac';

        echo '<tr style="font-size: 13px;">
                <td style="padding: 10px; border: 1px solid #ddd;">
                    <a href="' . esc_url($order_url) . '" target="_blank" style="text-decoration: none; color: #0073aa;">
                        #' . $order->get_id() . '
                    </a>
                </td>
                <td style="padding: 10px; border: 1px solid #ddd;">' . $customer_name . '</td>
                <td style="padding: 10px; border: 1px solid #ddd;">' . wc_get_order_status_name($order->get_status()) . '</td>
                <td style="padding: 10px; border: 1px solid #ddd;">' . $order->get_date_created()->format('d-m-Y H:i:s') . '</td>
                <td style="padding: 10px; border: 1px solid #ddd;">' . wc_price($order->get_total()) . '</td>
              </tr>';
    }

    echo '</table>';
    echo '<script>jQuery("#orders-for-my-products-list").data("total-pages", ' . $total_pages . ');</script>';

    return ob_get_clean();
}

// AJAX handler for logged-in users
add_action('wp_ajax_load_orders_for_my_products', function() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Morate biti prijavljeni.');
    }

    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    echo get_orders_for_my_products_page($page);
    wp_die();
});

// AJAX handler for guests (if needed)
add_action('wp_ajax_nopriv_load_orders_for_my_products', function() {
    wp_send_json_error('Prijava je potrebna.');
});



add_action('jet-form-builder/custom-action/save-product-to-user', function($form_data, $form) {
    // Set the expected JetForm ID (replace with your actual form ID)
    $expected_form_id = 12876;

    // Check if this is the correct form submission
    if (isset($form_data['__form_id']) && (int) $form_data['__form_id'] === $expected_form_id) {
        error_log('Form submission detected for form ID: ' . $expected_form_id);

        // Get the submitted product ID and user ID
        $product_id = isset($form_data['post_id']) ? intval($form_data['post_id']) : 0;
        $user_id = isset($form_data['user_id']) ? intval($form_data['user_id']) : 0;

        // Log for debugging
        error_log('Product ID: ' . $product_id);
        error_log('User ID: ' . $user_id);

        // Ensure we have valid product and user IDs
        if ($product_id > 0 && $user_id > 0) {
            // Get existing saved products
            $saved_products = get_user_meta($user_id, 'spaseni_proizvodi', true);
            if (!is_array($saved_products)) {
                $saved_products = [];
            }

            // Check if the product is already saved
            if (!in_array($product_id, $saved_products)) {
                $saved_products[] = $product_id;
                update_user_meta($user_id, 'spaseni_proizvodi', $saved_products);
                error_log('Product successfully added to user meta.');
            } else {
                error_log('Product already exists in user meta.');
            }
        } else {
            error_log('Invalid Product ID or User ID.');
        }
    } else {
        error_log('Form submission is not for the expected form ID.');
    }
}, 10, 2);

add_action('jet-form-builder/custom-action/remove_product_from_user', function($form_data, $handler) {
    $form_id = $handler->form_id;
    $expected_form_id = 12900; // Replace with your actual JetForm ID

    if ((int) $form_id !== (int) $expected_form_id) {
        return;
    }

    $product_id = isset($form_data['post_id']) ? intval($form_data['post_id']) : 0;
    $user_id = isset($form_data['user_id']) ? intval($form_data['user_id']) : 0;

    if ($product_id > 0 && $user_id > 0) {
        $saved_products = get_user_meta($user_id, 'spaseni_proizvodi', true);

        if (is_array($saved_products) && in_array($product_id, $saved_products)) {
            // Remove the product from the saved list
            $updated_products = array_diff($saved_products, [$product_id]);

            update_user_meta($user_id, 'spaseni_proizvodi', array_values($updated_products)); // Re-index the array
            error_log('Product successfully removed from user meta.');
        } else {
            error_log('Product was not in saved list.');
        }
    } else {
        error_log('Invalid Product ID or User ID.');
    }
}, 10, 2);


// Empty the cart and add only the newly selected product with its quantity before redirecting to checkout
add_filter('woocommerce_add_to_cart_redirect', 'skip_wc_cart_with_single_product');
function skip_wc_cart_with_single_product($url) {
    if (isset($_REQUEST['add-to-cart'])) {
        $product_id = absint($_REQUEST['add-to-cart']);
        $quantity = isset($_REQUEST['quantity']) ? absint($_REQUEST['quantity']) : 1; // Default to 1 if not set
        
        // Remove all items from cart
        WC()->cart->empty_cart();
        
        // Add the product with the correct quantity
        WC()->cart->add_to_cart($product_id, $quantity);
        
        // Redirect to checkout
        return wc_get_checkout_url();
    }
    
    return $url;
}

add_filter('woocommerce_add_to_cart_validation', 'reset_cart_always_before_add', 0, 3);
function reset_cart_always_before_add($passed, $product_id, $quantity) {
    WC()->cart->empty_cart(); // Clear cart on every new add-to-cart
    return $passed;
}

// Block cart page"
function block_cart_page_access() {
    if (is_cart()) {
        wp_die('<h1>Stranica isključena</h1><p>Ova stranica nije dostupna</p><a href="'.wc_get_checkout_url().'">Nastavite na Checkout</a>');
    }
}
add_action('template_redirect', 'block_cart_page_access');






// Change "Add to Cart" button text to "Kupi proizvod"
add_filter('woocommerce_product_single_add_to_cart_text', 'lw_cart_btn_text');
add_filter('woocommerce_product_add_to_cart_text', 'lw_cart_btn_text');
function lw_cart_btn_text() {
    return __('Naruči proizvod', 'woocommerce');
}

// Remove "has been added to your cart" message
add_filter('wc_add_to_cart_message_html', '__return_false');
function custom_add_to_cart_price_display() {
    global $product;
    if ($product->is_type('simple')) { // Ensure it's only for simple products
        $price = $product->get_price();
        echo '<div id="calculated-total-price" style="font-size: 18px; font-weight: bold; margin-top: 10px;">
                ' . __('Total:', 'woocommerce') . ' <span class="custom-total-price">' . wc_price($price) . '</span>
              </div>';
    }
}
add_action('woocommerce_before_add_to_cart_button', 'custom_add_to_cart_price_display');

function enqueue_custom_price_update_script() {
    if (is_product()) { // Only load on product pages
        wp_enqueue_script(
            'custom-price-update',
            get_stylesheet_directory_uri() . '/custom-price-update.js', // Fix for child theme
            array('jquery'),
            null,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_custom_price_update_script');

function display_product_views_shortcode() {
    if (!is_singular('product')) return ''; 

    global $post;
    if (!$post) return '';

    // Get and increment views
    $views = get_post_meta($post->ID, 'broj_pregleda', true);
    $views = $views ? intval($views) + 1 : 1;
    update_post_meta($post->ID, 'broj_pregleda', $views);

    return '<p style="font-size: 14px; color: #666;">👁️ ' . esc_html($views) . ' pregleda</p>';
}

add_shortcode('product_views', 'display_product_views_shortcode');

// 10 najgledanijih artikala
/**
 * =====================================================
 * SHORTCODE: [najgledaniji_artikli]
 * =====================================================
 * Prikazuje najgledanije WooCommerce proizvode
 */
function rc_najgledaniji_artikli_shortcode($atts) {

    $atts = shortcode_atts([
        'limit' => 10,
    ], $atts);

    $limit = max(1, (int) $atts['limit']);

    $query = new WP_Query([
        'post_type'      => 'product',
        'posts_per_page' => $limit,
        'post_status'    => 'publish',
        'meta_key'       => 'broj_pregleda',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
        'meta_query'     => [
            [
                'key'     => 'broj_pregleda',
                'compare' => 'EXISTS',
            ],
        ],
    ]);

    if (!$query->have_posts()) {
        return '<p>Nema podataka o pregledima.</p>';
    }

    $out  = '<div class="top-artikli-widget">';
    $out .= '<ul class="top-artikli-lista">';

    while ($query->have_posts()) {
        $query->the_post();

        $views = (int) get_post_meta(get_the_ID(), 'broj_pregleda', true);

        $out .= sprintf(
            '<li>
                <a href="%s" class="top-artikal-naziv">%s</a>
                <span class="top-artikal-badge">
                    <span class="top-dot"></span>
                    %s pregleda
                </span>
            </li>',
            esc_url(get_permalink()),
            esc_html(get_the_title()),
            esc_html(number_format_i18n($views))
        );
    }

    wp_reset_postdata();

    $out .= '</ul></div>';

    return $out;
}
add_shortcode('najgledaniji_artikli', 'rc_najgledaniji_artikli_shortcode');

//kraj 10 najgledanijih artikala


//custom quantity plus i minus 

function custom_quantity_buttons() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            function updateQuantityInput(el, increment) {
                var input = el.siblings('input.qty');
                var currentValue = parseInt(input.val());
                var min = parseInt(input.attr('min')) || 1;
                var max = parseInt(input.attr('max')) || 9999;
                var step = parseInt(input.attr('step')) || 1;

                var newValue = currentValue + (increment ? step : -step);
                if (newValue >= min && newValue <= max) {
                    input.val(newValue).trigger('change');
                }
            }

            $('.quantity').each(function() {
                $(this).prepend('<button type="button" class="qty-minus">−</button>');
                $(this).append('<button type="button" class="qty-plus">+</button>');
            });

            $(document).on('click', '.qty-plus', function() {
                updateQuantityInput($(this), true);
            });

            $(document).on('click', '.qty-minus', function() {
                updateQuantityInput($(this), false);
            });
        });
    </script>
    <?php
}
add_action('wp_footer', 'custom_quantity_buttons');

function hide_review_form_for_product_owner() {
    if (is_product()) {
        global $post;
        $current_user_id = get_current_user_id();

        // Check if the current user is the product owner
        if ($current_user_id == $post->post_author) {
            remove_action('woocommerce_review_before_comment_form', 'woocommerce_review_display_comment_form', 10);
            remove_action('comment_form_before', 'woocommerce_review_display_comment_form', 10);
            remove_action('woocommerce_product_tabs', 'woocommerce_product_reviews_tab', 10);
            remove_action('woocommerce_after_single_product_summary', 'comments_template', 50);
        }
    }
}
add_action('wp', 'hide_review_form_for_product_owner');

function add_product_owner_body_class($classes) {
    if (is_product()) {
        global $post;
        $current_user_id = get_current_user_id();

        // Check if the current user is the product owner
        if ($current_user_id == $post->post_author) {
            $classes[] = 'product-owner';
        }
    }
    return $classes;
}
add_filter('body_class', 'add_product_owner_body_class');

//Funkcionalnost prikaza recenzija za korisnika, css ubačen na sam element listinga, a jquery skripta ubačena u stranicu
function get_current_user_woocommerce_reviews() {
    if (!is_user_logged_in()) {
        return '<p class="no-reviews">You must be logged in to view your reviews.</p>';
    }

    $current_user_id = get_current_user_id();
    global $wpdb;

    $reviews = $wpdb->get_results($wpdb->prepare("
        SELECT c.comment_ID, c.comment_post_ID, c.comment_content, c.comment_date, m.meta_value as rating
        FROM {$wpdb->comments} c
        LEFT JOIN {$wpdb->commentmeta} m ON c.comment_ID = m.comment_id AND m.meta_key = 'rating'
        WHERE c.user_id = %d
        AND c.comment_approved = 1
        AND c.comment_post_ID IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product')
        ORDER BY c.comment_date DESC
    ", $current_user_id));

    if (empty($reviews)) {
        return '<p class="no-reviews">Nisu pronađene recenzije.</p>';
    }

    $output = '<div id="user-reviews">';
    $output .= '<ul class="review-list">';

    foreach ($reviews as $review) {
        $product_link = get_permalink($review->comment_post_ID);
        $product_title = get_the_title($review->comment_post_ID);
        $rating = !empty($review->rating) ? intval($review->rating) : 'No rating';

        $output .= '<li class="review-item">';
        $output .= '<strong><a href="' . esc_url($product_link) . '">' . esc_html($product_title) . '</a></strong>';
        $output .= '<p>' . esc_html($review->comment_content) . '</p>';
        $output .= '<small>⭐ Rating: ' . esc_html($rating) . '/5 | 📅 ' . esc_html(date('F j, Y', strtotime($review->comment_date))) . '</small>';
        $output .= '</li>';
    }

    $output .= '</ul>';
    $output .= '<div class="pagination">';
    $output .= '<button id="prev-page">Prethodna</button>';
    $output .= '<span id="page-number">1</span>';
    $output .= '<button id="next-page">Sljedeća</button>';
    $output .= '</div>';
    $output .= '</div>'; 

    return $output;
}

add_shortcode('user_reviews', 'get_current_user_woocommerce_reviews');


//Funkcionalnost statistike za trenutnog korisnika, css ubačen na element
function get_woocommerce_user_statistics() {
    if (!is_user_logged_in()) {
        return '<p class="no-stats">Morate biti ulogovani kako biste vidjeli statistiku svojih proizvoda.</p>';
    }

    global $wpdb;
    $user_id = get_current_user_id();

    // Get all product IDs authored by current user
    $product_ids = $wpdb->get_col($wpdb->prepare("
        SELECT ID FROM {$wpdb->posts}
        WHERE post_type = 'product' AND post_status = 'publish' AND post_author = %d
    ", $user_id));

    if (empty($product_ids)) {
        return '<p class="no-stats">Nemate objavljenih proizvoda.</p>';
    }

    $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));

    // Total orders that include user's products
    $order_ids = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT order_items.order_id
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product_id
            ON order_items.order_item_id = meta_product_id.order_item_id
        WHERE meta_product_id.meta_key = '_product_id'
        AND meta_product_id.meta_value IN ($placeholders)
        AND order_items.order_id IN (
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
            AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
        )
    ", ...$product_ids));

    $total_orders = is_array($order_ids) ? count($order_ids) : 0;

    // Total revenue from user's products
    $total_revenue = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(CAST(meta_total.meta_value AS DECIMAL(10,2)))
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product
            ON items.order_item_id = meta_product.order_item_id AND meta_product.meta_key = '_product_id'
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_total
            ON items.order_item_id = meta_total.order_item_id AND meta_total.meta_key = '_line_total'
        WHERE meta_product.meta_value IN ($placeholders)
        AND items.order_id IN (
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
            AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
        )
    ", ...$product_ids));
    $total_revenue = $total_revenue ? floatval($total_revenue) : 0;

    // Total quantity sold
    $total_units = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(CAST(meta_qty.meta_value AS UNSIGNED))
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product
            ON items.order_item_id = meta_product.order_item_id AND meta_product.meta_key = '_product_id'
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_qty
            ON items.order_item_id = meta_qty.order_item_id AND meta_qty.meta_key = '_qty'
        WHERE meta_product.meta_value IN ($placeholders)
        AND items.order_id IN (
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
            AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
        )
    ", ...$product_ids));
    $total_units = $total_units ? intval($total_units) : 0;

    // Average sale value
    $average_order = $total_orders > 0 ? round($total_revenue / $total_orders, 2) : 0;

    // Last sale date
    $last_order_date = '';
    if (!empty($order_ids)) {
        $order_ids_placeholder = implode(',', array_map('intval', $order_ids));
        $last_order_date = $wpdb->get_var("
            SELECT post_date FROM {$wpdb->posts}
            WHERE ID IN ($order_ids_placeholder)
            ORDER BY post_date DESC LIMIT 1
        ");
        $last_order_date = $last_order_date ? date('F j, Y', strtotime($last_order_date)) : 'Nema narudžbi.';
    } else {
        $last_order_date = 'Nema narudžbi.';
    }

    // Most sold product
    $most_sold_product_id = $wpdb->get_var($wpdb->prepare("
        SELECT meta_product.meta_value
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product
            ON items.order_item_id = meta_product.order_item_id
        WHERE meta_product.meta_key = '_product_id'
        AND meta_product.meta_value IN ($placeholders)
        AND items.order_id IN (
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
            AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
        )
        GROUP BY meta_product.meta_value
        ORDER BY COUNT(*) DESC
        LIMIT 1
    ", ...$product_ids));
    $most_sold_product_name = $most_sold_product_id ? get_the_title($most_sold_product_id) : 'Nema podataka.';

    // Total reviews on user's products
    $total_reviews = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->comments}
        WHERE comment_approved = 1
        AND comment_post_ID IN ($placeholders)
    ", ...$product_ids));
    $total_reviews = $total_reviews ? intval($total_reviews) : 0;

    // Output
    $output = '<div class="woocommerce-user-stats">';
    $output .= '<ul>';
    $output .= '<li>🛒 Narudžbe koje sadrže moje proizvode: <strong>' . $total_orders . '</strong></li>';
    $output .= '<li>📦 Ukupno prodanih jedinica: <strong>' . $total_units . '</strong></li>';
    $output .= '<li>💰 Ukupno prodano: <strong>' . wc_price($total_revenue) . '</strong></li>';
    $output .= '<li>📊 Prosječna narudžba: <strong>' . wc_price($average_order) . '</strong></li>';
    if (!empty($last_order_date)) {
    // Try to convert to timestamp
    $timestamp = strtotime($last_order_date);
    
    if ($timestamp !== false) {
        $formatted_date = wp_date('d.m.Y.', $timestamp);
    } else {
        $formatted_date = esc_html($last_order_date); // fallback to original
    }

    $output .= '<li>📅 Datum zadnje prodaje: <strong>' . esc_html($formatted_date) . '</strong></li>';
}
    $output .= '<li>🏆 Najprodavaniji proizvod: <strong>' . esc_html($most_sold_product_name) . '</strong></li>';
    $output .= '<li>📝 Recenzije mojih  proizvoda: <strong>' . $total_reviews . '</strong></li>';
    $output .= '</ul>';
    $output .= '</div>';

    return $output;
}

add_shortcode('user_woocommerce_stats', 'get_woocommerce_user_statistics');
//Statistika premium

function get_woocommerce_user_statistics_premium() {
    if (!is_user_logged_in()) {
        return '<p class="no-stats">Morate biti ulogovani kako biste vidjeli statistiku svojih proizvoda.</p>';
    }

    global $wpdb;
    $user_id = get_current_user_id();

    // Get all product IDs authored by current user
    $product_ids = $wpdb->get_col($wpdb->prepare("
        SELECT ID FROM {$wpdb->posts}
        WHERE post_type = 'product' AND post_status = 'publish' AND post_author = %d
    ", $user_id));

    if (empty($product_ids)) {
        return '<p class="no-stats">Nemate objavljenih proizvoda.</p>';
    }

    $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));

    // Total orders that include user's products
    $order_ids = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT order_items.order_id
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product_id
            ON order_items.order_item_id = meta_product_id.order_item_id
        WHERE meta_product_id.meta_key = '_product_id'
        AND meta_product_id.meta_value IN ($placeholders)
        AND order_items.order_id IN (
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
            AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
        )
    ", ...$product_ids));

    $total_orders = count($order_ids);

    // Total revenue from user's products
    $total_revenue = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(CAST(meta_total.meta_value AS DECIMAL(10,2)))
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product
            ON items.order_item_id = meta_product.order_item_id AND meta_product.meta_key = '_product_id'
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_total
            ON items.order_item_id = meta_total.order_item_id AND meta_total.meta_key = '_line_total'
        WHERE meta_product.meta_value IN ($placeholders)
        AND items.order_id IN (
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
            AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
        )
    ", ...$product_ids));
    $total_revenue = $total_revenue ? floatval($total_revenue) : 0;

    // Total quantity sold
    $total_units = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(CAST(meta_qty.meta_value AS UNSIGNED))
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product
            ON items.order_item_id = meta_product.order_item_id AND meta_product.meta_key = '_product_id'
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_qty
            ON items.order_item_id = meta_qty.order_item_id AND meta_qty.meta_key = '_qty'
        WHERE meta_product.meta_value IN ($placeholders)
        AND items.order_id IN (
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
            AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
        )
    ", ...$product_ids));
    $total_units = $total_units ? intval($total_units) : 0;

    // Average sale value
    $average_order = $total_orders > 0 ? round($total_revenue / $total_orders, 2) : 0;
    $avg_product_price = $total_units > 0 ? round($total_revenue / $total_units, 2) : 0;

    // Last sale date
    $last_order_date = '';
    $first_order_date = '';
    if (!empty($order_ids)) {
        $order_ids_placeholder = implode(',', array_map('intval', $order_ids));

        $last_order_date = $wpdb->get_var("
            SELECT post_date FROM {$wpdb->posts}
            WHERE ID IN ($order_ids_placeholder)
            ORDER BY post_date DESC LIMIT 1
        ");
        $first_order_date = $wpdb->get_var("
            SELECT post_date FROM {$wpdb->posts}
            WHERE ID IN ($order_ids_placeholder)
            ORDER BY post_date ASC LIMIT 1
        ");

        $last_order_date = $last_order_date ? date('d.m.Y.', strtotime($last_order_date)) : 'Nema narudžbi.';
        $first_order_date = $first_order_date ? date('d.m.Y.', strtotime($first_order_date)) : 'Nema narudžbi.';
    } else {
        $last_order_date = 'Nema narudžbi.';
        $first_order_date = 'Nema narudžbi.';
    }

    // Most sold product
    $most_sold_product_id = $wpdb->get_var($wpdb->prepare("
        SELECT meta_product.meta_value
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product
            ON items.order_item_id = meta_product.order_item_id
        WHERE meta_product.meta_key = '_product_id'
        AND meta_product.meta_value IN ($placeholders)
        AND items.order_id IN (
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
            AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
        )
        GROUP BY meta_product.meta_value
        ORDER BY COUNT(*) DESC
        LIMIT 1
    ", ...$product_ids));
    $most_sold_product_name = $most_sold_product_id ? get_the_title($most_sold_product_id) : 'Nema podataka.';

    // Total reviews
    $total_reviews = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->comments}
        WHERE comment_approved = 1
        AND comment_post_ID IN ($placeholders)
    ", ...$product_ids));
    $total_reviews = $total_reviews ? intval($total_reviews) : 0;

    // Average rating
    $average_rating = $wpdb->get_var($wpdb->prepare("
        SELECT AVG(meta_value)
        FROM {$wpdb->commentmeta}
        WHERE meta_key = 'rating'
        AND comment_id IN (
            SELECT comment_ID FROM {$wpdb->comments}
            WHERE comment_approved = 1 AND comment_post_ID IN ($placeholders)
        )
    ", ...$product_ids));
    $average_rating = $average_rating ? round($average_rating, 2) : 'N/A';

    // Unique customers
    $unique_customers = $wpdb->get_var("
        SELECT COUNT(DISTINCT meta.meta_value)
        FROM {$wpdb->prefix}postmeta AS meta
        WHERE meta.post_id IN ($order_ids_placeholder)
        AND meta.meta_key = '_customer_user'
    ");

    // Highest single order value (for this vendor's items)
    $max_order_value = $wpdb->get_var($wpdb->prepare("
        SELECT MAX(sub.total)
        FROM (
            SELECT SUM(CAST(meta_total.meta_value AS DECIMAL(10,2))) AS total
            FROM {$wpdb->prefix}woocommerce_order_items AS items
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_total
                ON items.order_item_id = meta_total.order_item_id AND meta_total.meta_key = '_line_total'
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product
                ON items.order_item_id = meta_product.order_item_id AND meta_product.meta_key = '_product_id'
            WHERE meta_product.meta_value IN ($placeholders)
            AND items.order_id IN ($order_ids_placeholder)
            GROUP BY items.order_id
        ) AS sub
    ", ...$product_ids));
    $max_order_value = $max_order_value ? wc_price($max_order_value) : 'N/A';

    // Output
    $output = '<div class="woocommerce-user-stats"><ul>';
    $output .= "<li>📋 Objavljenih proizvoda: <strong>" . count($product_ids) . "</strong></li>";
    $output .= "<li>🛒 Narudžbe koje sadrže moje proizvode: <strong>$total_orders</strong></li>";
    $output .= "<li>📦 Ukupno prodanih jedinica: <strong>$total_units</strong></li>";
    $output .= "<li>💳 Najveća pojedinačna narudžba: <strong>$max_order_value</strong></li>";
    $output .= "<li>👤 Unikatnih kupaca: <strong>$unique_customers</strong></li>";
    $output .= "<li>📅 Prva prodaja: <strong>$first_order_date</strong></li>";
    $output .= "<li>📅 Zadnja prodaja: <strong>$last_order_date</strong></li>";
    $output .= "<li>🏆 Najprodavaniji proizvod: <strong>" . esc_html($most_sold_product_name) . "</strong></li>";
    $output .= "<li>📝 Recenzije mojih proizvoda: <strong>$total_reviews</strong></li>";
    $output .= "<li>⭐ Prosječna ocjena: <strong>$average_rating</strong></li>";
	$output .= "<li>💸 Prosječna cijena proizvoda: <strong>" . wc_price($avg_product_price) . "</strong></li>";
	$output .= "<li>📊 Prosječna narudžba: <strong>" . wc_price($average_order) . "</strong></li>";
	$output .= "<li>💰 Ukupno prodano: <strong>" . wc_price($total_revenue) . "</strong></li>";
    $output .= '</ul></div>';

    return $output;
}

add_shortcode('user_woocommerce_stats_premium', 'get_woocommerce_user_statistics_premium');

// Mini statistika freemium
function get_vendor_product_statistics_combined() {
    if (!is_user_logged_in()) {
        return '<p class="no-stats">Morate biti ulogovani kako biste vidjeli statistiku svojih proizvoda.</p>';
    }

    $user_id = get_current_user_id();
    global $wpdb;

    // 1. Total products authored by the user
    $total_products = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->posts}
        WHERE post_type = 'product' AND post_status = 'publish' AND post_author = %d
    ", $user_id));

    // 2. Total units sold from user's products
    $total_sales = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(meta.meta_value) FROM {$wpdb->postmeta} AS meta
        INNER JOIN {$wpdb->posts} AS p ON p.ID = meta.post_id
        WHERE meta.meta_key = 'total_sales' AND p.post_type = 'product' AND p.post_author = %d
    ", $user_id));

    // 3. Total revenue from order items referencing user's products
    $product_ids = $wpdb->get_col($wpdb->prepare("
        SELECT ID FROM {$wpdb->posts}
        WHERE post_type = 'product' AND post_status = 'publish' AND post_author = %d
    ", $user_id));

    $total_revenue = 0;
    if (!empty($product_ids)) {
        $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));

        $query = "
            SELECT SUM(CAST(meta_line_total.meta_value AS DECIMAL(10,2)))
            FROM {$wpdb->prefix}woocommerce_order_items AS items
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product_id
                ON items.order_item_id = meta_product_id.order_item_id
                AND meta_product_id.meta_key = '_product_id'
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_line_total
                ON items.order_item_id = meta_line_total.order_item_id
                AND meta_line_total.meta_key = '_line_total'
            WHERE meta_product_id.meta_value IN ($placeholders)
            AND items.order_id IN (
                SELECT ID FROM {$wpdb->posts}
                WHERE post_type = 'shop_order'
                AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
            )
        ";

        $prepared_query = $wpdb->prepare($query, ...$product_ids);
        $total_revenue = $wpdb->get_var($prepared_query);
        $total_revenue = $total_revenue ? floatval($total_revenue) : 0;
    }

    // Output
    $output = '<div class="vendor-product-stats">';
    $output .= '<ul>';
    $output .= '<li>📦 Objavljenih proizvoda: <strong>' . intval($total_products) . '</strong></li>';
    $output .= '<li>🛒 Prodanih jedinica: <strong>' . intval($total_sales) . '</strong></li>';
    $output .= '<li>💰 Ukupno prodano: <strong>' . wc_price($total_revenue) . '</strong></li>';
    $output .= '</ul>';
    $output .= '</div>';

    return $output;
}
add_shortcode('vendor_product_stats', 'get_vendor_product_statistics_combined');


function get_user_products_this_month() {
    if (!is_user_logged_in()) {
        return 'Morate biti ulogovani da vidite ove informacije.';
    }

    $current_user_id = get_current_user_id();
    $current_month = date('m');
    $current_year = date('Y');

    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'author'         => $current_user_id,
        'date_query'     => array(
            array(
                'year'  => $current_year,
                'month' => $current_month,
            ),
        ),
        'posts_per_page' => -1, // Get all posts
        'fields'         => 'ids', // Only retrieve post IDs for performance
    );

    $query = new WP_Query($args);
    return $query->found_posts; // Return the count
}

function user_products_shortcode() {
    return get_user_products_this_month();
}
add_shortcode('user_products_this_month', 'user_products_shortcode');

function get_global_stats() {
    global $wpdb;

    // Get total users
    $total_users = $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->users}");

    // Get total products
    $total_products = $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'");

    // Get total sales
    $total_sales = $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'shop_order' AND post_status IN ('wc-completed', 'wc-processing')");

    // Get total revenue
    $total_revenue = $wpdb->get_var("SELECT SUM(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = '_order_total'");

   

    // Format output with styles
    $output = "
    <style>
        .global-stats-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            gap: 20px;
            margin: 20px 0;
        }
        .stat-box {
            background: #ffffff;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            padding: 10px;
            text-align: center;
            min-width: 140px;
            flex: 1;
            max-width: 200px;
        }
        .stat-box h3 {
            font-size: 18px;
            margin-bottom: 5px;
            color: #333;
        }
        .stat-box p {
            font-size: 22px;
            font-weight: bold;
            color: #0073aa;
            margin: 0;
        }
    </style>
    
    <div class='global-stats-container'>
        <div class='stat-box'>
            <h3>Ukupno korisnika</h3>
            <p>$total_users</p>
        </div>
        <div class='stat-box'>
            <h3>Ukupno proizvoda</h3>
            <p>$total_products</p>
        </div>
        <div class='stat-box'>
            <h3>Ukupno završeno</h3>
            <p>$total_sales</p>
        </div>
        <div class='stat-box'>
            <h3>Ukupan promet</h3>
            <p>" . number_format($total_revenue, 2) . " KM</p>
        </div>
        
    </div>";

    return $output;
}

// Register shortcode
add_shortcode('global_stats', 'get_global_stats');

// 🔹 Handle AJAX Request to Add/Remove Favorite Users
function add_favorite_user() {
    // Ensure user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to save favorites.']);
        wp_die();
    }

    // Get current user and target user ID
    $current_user_id = get_current_user_id();
    $favorite_user_id = intval($_POST['user_id']);

    if (!$favorite_user_id || $favorite_user_id == $current_user_id) {
        wp_send_json_error(['message' => 'Invalid user selection.']);
        wp_die();
    }

    // Retrieve existing favorites
    $favorites = get_user_meta($current_user_id, 'favorite_users', true);
    $favorites = is_array($favorites) ? $favorites : [];

    if (in_array($favorite_user_id, $favorites)) {
        // Remove from favorites if already added
        $favorites = array_diff($favorites, [$favorite_user_id]);
        $message = "Uklonjeno iz spašenih";
    } else {
        // Add to favorites
        $favorites[] = $favorite_user_id;
        $message = "Dodano u spašene";
    }

    // Save updated list
    update_user_meta($current_user_id, 'favorite_users', $favorites);

    wp_send_json_success(['message' => $message, 'favorites' => $favorites]);
    wp_die();
}
add_action('wp_ajax_add_favorite_user', 'add_favorite_user');
add_action('wp_ajax_nopriv_add_favorite_user', 'add_favorite_user');


//  Shortcode: Display Favorite Button for Post Author
function favorite_user_button_shortcode() {
    if (!is_user_logged_in()) return '';

    global $post;

    // Get the post author's user ID
    $user_id = isset($post->post_author) ? intval($post->post_author) : 0;
    $current_user_id = get_current_user_id();

    if ($user_id == 0 || $user_id == $current_user_id) return '';

    // Check if the user is already a favorite
    $favorites = get_user_meta($current_user_id, 'favorite_users', true);
    $favorites = is_array($favorites) ? $favorites : [];
    $is_favorite = in_array($user_id, $favorites);

    ob_start();
    ?>
    <button class="favorite-user-btn" data-user-id="<?php echo esc_attr($user_id); ?>">
        <?php echo $is_favorite ? '➖👤' : '➕👤'; ?>
    </button>

    <script>
        jQuery(document).ready(function($) {
            $('.favorite-user-btn').on('click', function() {
                var button = $(this);
                var userId = button.data('user-id');

                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'add_favorite_user',
                    user_id: userId
                }, function(response) {
                    if (response.success) {
                        button.text(response.data.message.includes("Added") ? '➖👤' : '➕👤');
                        alert(response.data.message);
                    } else {
                        alert(response.data.message);
                    }
                });
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('favorite_user_button', 'favorite_user_button_shortcode');


function favorite_users_list_shortcode() {
    if (!is_user_logged_in()) return '<p>Morate biti ulogovani!.</p>';

    $current_user_id = get_current_user_id();
    $favorites = get_user_meta($current_user_id, 'favorite_users', true);
    $favorites = is_array($favorites) ? $favorites : [];

    if (empty($favorites)) {
        return '<p>Nemate spašenih korisnika.</p>';
    }

    ob_start();
    echo "<ul class='favorite-users-list'>";
    foreach ($favorites as $user_id) {
        $user_info = get_userdata($user_id);
        if ($user_info) {
            $profile_url = home_url("/korisnik/{$user_id}/info/"); // 🔹 Corrected profile link
            echo "<li><a href='" . esc_url($profile_url) . "'>" . esc_html($user_info->display_name) . "</a></li>";
        }
    }
    echo "</ul>";

    return ob_get_clean();
}
add_shortcode('favorite_users_list', 'favorite_users_list_shortcode');



// 🔹 Add Basic Styles
function favorite_users_styles() {
    echo '<style>
        .favorite-user-btn {
            background-color: #f2f2f2;
            color: white;
            padding: 6px 6px;
            border: none;
            border-radius: 2px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        .favorite-user-btn:hover {
            background-color: #999;
			color: white;
        }
        .favorite-users-list {
            list-style: none;
            padding: 10px;
            background: #f8f8f8;
            border-radius: 2px;
			
        }
        .favorite-users-list li {
            padding: 5px 0;
            font-size: 16px;
			margin: 0 0 4px 0;
			border-bottom: solid 1px #ddd;
        }
        .favorite-users-list li a {
            color: black;
            text-decoration: none;
        }
        .favorite-users-list li a:hover {
            text-decoration: underline;
        }
    </style>';
}
add_action('wp_head', 'favorite_users_styles');

//Checks if user is logged in and its status us premium
/*
function restrict_poruke_page() {
    if (is_page('poruke')) { // Check if the user is on /poruke
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $premium_status = get_user_meta($user_id, 'je_premium', true);

            if ($premium_status === 'false' || !$premium_status) {
                wp_redirect('/premium'); // Redirect to the premium page
                exit();
            }
        } else {
            wp_redirect('/login'); // Redirect non-logged-in users to the login page
            exit();
        }
    }
}
add_action('template_redirect', 'restrict_poruke_page'); // */

// Ograničava URL-ove Woocommerce-a na samo jedna nivo kategorije 
add_filter( 'woocommerce_product_post_type_link_parent_category_only', '__return_true' );

// Logout link with nonce shortcode
function custom_logout_link() {
    return esc_url( wp_nonce_url( wp_logout_url(home_url()), 'log-out' ) );
}
add_shortcode('logout_link', 'custom_logout_link');

// Function to safely extract serialized values
function get_unserialized_value($value) {
    // Always attempt to unserialize (it’s safe with normal strings)
    $value = maybe_unserialize($value);
    
    // If it's an array, return the first element; otherwise, return the value as is
    return (is_array($value) && !empty($value)) ? reset($value) : $value;
}


add_action('profile_update', function($user_id) {
    // Schedule the update to run 3 seconds later
    wp_schedule_single_event(time() + 3, 'delayed_profile_update', [$user_id]);
}, 10, 1);

add_action('delayed_profile_update', function($user_id) {
    // Mapping of user meta keys to billing/shipping meta keys
    $meta_map = [
        'kanton_regija'    => 'state',
        'grad'             => 'city',
        'ulica_i_broj'     => 'address_1',
        'kontakt_telefon'  => 'phone',
        'kontakt_email'    => 'email'
    ];

    // Retrieve updated user meta values
    $meta_values = [];
    foreach ($meta_map as $key => $mapped_key) {
        $meta_values[$mapped_key] = get_unserialized_value(get_user_meta($user_id, $key, true));
    }

    // Update billing and shipping details
    foreach ($meta_values as $field => $value) {
        if (!empty($value)) {
            update_user_meta($user_id, "billing_$field", $value);
            update_user_meta($user_id, "shipping_$field", $value);
        }
    }

    // Get 'naziv' and use it to update name fields
    $naziv = get_unserialized_value(get_user_meta($user_id, 'naziv', true));

    if (!empty($naziv)) {
        update_user_meta($user_id, 'billing_first_name', $naziv);
        update_user_meta($user_id, 'shipping_first_name', $naziv);

        // Update user first name and display name
        wp_update_user([
            'ID'           => $user_id,
            'first_name'   => $naziv,
            'last_name'    => '',
            'display_name' => $naziv
        ]);
    }

    // Remove last name fields from billing/shipping
    delete_user_meta($user_id, 'billing_last_name');
    delete_user_meta($user_id, 'shipping_last_name');

    // Always set shipping country
    update_user_meta($user_id, 'shipping_country', 'BA'); // Bosnia and Herzegovina
});





add_filter('woocommerce_cart_needs_billing_address', '__return_false'); // Prevents WooCommerce from asking for billing address


add_action('jet-form-builder/custom-action/password-reset', function ($form_data, $form) {
    $expected_form_id = 15081; // Replace with the actual ID of your JetForm

     // Ensure the form ID matches
    if (isset($form_data['__form_id']) && (int) $form_data['__form_id'] !== $expected_form_id) {
        return;
    }

    // Get the current user ID
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to change your password.'], 403);
    }

    $user_id = get_current_user_id();
    $current_password = sanitize_text_field($form_data['current_password'] ?? '');
    $new_password = sanitize_text_field($form_data['new_password'] ?? '');
    $confirm_password = sanitize_text_field($form_data['confirm_password'] ?? '');

    // Get user info
    $user = get_user_by('ID', $user_id);

    // Check if the current password is correct
    if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
        wp_send_json_error(['message' => 'Trenutna šifra je pogrešna.'], 403);
    }

    // Ensure new passwords match
    if ($new_password !== $confirm_password) {
        wp_send_json_error(['message' => 'Nove šifre se ne slažu'], 400);
    }

    // Update the password without logging the user out
    wp_set_password($new_password, $user_id);
    
    // Re-authenticate the user after password change
    wp_set_auth_cookie($user_id);

    //wp_send_json_success(['message' => 'Šifra uspješno ažurirana. Možete se vratiti nazad na stranicu.']);
}, 10, 2);

add_action('woocommerce_checkout_update_order_meta', function ($order_id) {
    // Get the order
    $order = wc_get_order($order_id);
    
    // Ensure the order exists
    if (!$order) {
        return;
    }
    
    // Get the items in the order
    $items = $order->get_items();
    
    // Since there's only one product per order, we take the first item
    foreach ($items as $item) {
        $product_id = $item->get_product_id();
        $author_id = get_post_field('post_author', $product_id);
        
        if ($author_id) {
            update_post_meta($order_id, 'vlasnik_proizvoda', $author_id);
        }
        break; // Exit loop since there's only one product
    }
});


function generate_order_received_link($order_id) {
    // 1. Validate the order ID early.
    if (!is_numeric($order_id) || $order_id <= 0) {
        return ''; // Return empty if the order ID is invalid.
    }

    // 2. Get the WooCommerce order.
    $order = wc_get_order($order_id);
    if (!$order) {
        return ''; // Return empty if the order doesn't exist.
    }

    // 3. Get the order key (needed for security).
    $order_key = $order->get_order_key();

    // 4. Construct the order received page URL.
    $order_received_url = site_url("/checkout/order-received/{$order_id}/?key={$order_key}");

    // 5. Return the clickable link.
    return '<a href="' . esc_url($order_received_url) . '" target="_blank">' . esc_html($order_id) . '</a>';
}

// Register the callback in JetEngine.
add_filter('jet-engine/listings/allowed-callbacks', function($callbacks) {
    $callbacks['generate_order_received_link'] = 'generate_order_received_link';
    return $callbacks;
});

// Ova se funkcija moze preraditi da koristi metafield vlasnik_proizvoda iz ordera

function send_new_order_email_to_product_author( $recipient, $order ) {
    if ( ! is_a( $order, 'WC_Order' ) ) {
        return $recipient;
    }

    // Array to store product authors' emails
    $author_emails = [];

    foreach ( $order->get_items() as $item ) {
        $product_id = $item->get_product_id();
        $author_id = get_post_field( 'post_author', $product_id );
        $author_email = get_the_author_meta( 'user_email', $author_id );

        if ( !empty($author_email) && !in_array($author_email, $author_emails) ) {
            $author_emails[] = $author_email;
        }
    }

    // If authors exist, replace the default admin email
    if ( !empty($author_emails) ) {
        $recipient = implode(',', $author_emails);
    }

    return $recipient;
}
add_filter( 'woocommerce_email_recipient_new_order', 'send_new_order_email_to_product_author', 10, 2 );

add_action('template_redirect', function() {
    if (is_page('my-account') || (is_wc_endpoint_url() && !is_wc_endpoint_url('order-received'))) {
        wp_redirect(home_url('/korisnik/')); 
        exit;
    }
});

// This removes all account-related links from WooCommerce.
add_filter('woocommerce_account_menu_items', '__return_empty_array');


function allow_donator_to_update_orders($allcaps, $cap, $args) {
    // Check if we have a capability array with at least one item
    if (isset($cap[0]) && isset($args[2]) && in_array($cap[0], ['edit_shop_order', 'edit_others_shop_orders'])) {
        $order_id = $args[2]; // The order ID
        $user_id = get_current_user_id(); // The logged-in user ID
        $user = get_userdata($user_id);

        // Check if the user has the "donator" role
        if (in_array('donator', (array) $user->roles)) {
            // Get the postmeta value 'vlasnik_proizvoda'
            $owner_id = get_post_meta($order_id, 'vlasnik_proizvoda', true);

            // Allow edit access only if the user ID matches 'vlasnik_proizvoda'
            if ($owner_id == $user_id) {
                $allcaps['edit_shop_order'] = true;
                $allcaps['edit_others_shop_orders'] = true;
            }
        }
    }
    return $allcaps;
}
add_filter('user_has_cap', 'allow_donator_to_update_orders', 10, 3);



add_action('jet-form-builder/custom-action/status-narudzbe', function($form_data, $form) {
    // Očekivani ID forme
    $expected_form_id = 14294;

    // Provjera da li poslani formular odgovara očekivanom ID-u
    if (isset($form_data['__form_id']) && (int)$form_data['__form_id'] === $expected_form_id) {
        
        // Dohvati ID narudžbe iz podataka forme
        $order_id = $form_data['post_id'] ?? 0;
        $new_status = $form_data['statusi'] ?? '';

        // Mapiranje statusa narudžbi u čitljive nazive
        $status_labels = [
            'wc-pending'    => 'Čekanje na uplatu',
            'wc-processing' => 'Procesiranje',
            'wc-on-hold'    => 'Na čekanju',
            'wc-completed'  => 'Završeno',
            'wc-cancelled'  => 'Otkazano',
            'wc-refunded'   => 'Refundirano',
            'wc-failed'     => 'Neuspjelo'
        ];
        
        // Dobivanje čitljivog naziva statusa
        $readable_status = $status_labels[$new_status] ?? $new_status;

        // Provjera da li postoji validan ID narudžbe
        if ($order_id && class_exists('WC_Order')) {
            $order = wc_get_order($order_id);
            
            if ($order) {
                // Dohvati ID kupca (korisnika koji je napravio narudžbu)
                $order_author_id = $order->get_user_id();
                $order_author = get_userdata($order_author_id);
                $order_author_email = $order_author ? $order_author->user_email : '';

                // Dohvati ID vlasnika proizvoda iz meta podataka narudžbe
                $product_owner_id = get_post_meta($order_id, 'vlasnik_proizvoda', true);
                $product_owner = get_userdata($product_owner_id);
                $product_owner_email = $product_owner ? $product_owner->user_email : '';

                // Priprema sadržaja e-maila
                $subject = "Status vaše narudžbe #$order_id je ažuriran";
                $message = "<p>Poštovani,</p>";
                $message .= "<p>Status vaše narudžbe <strong>#$order_id</strong> je promijenjen u: <strong>$readable_status</strong>.</p>";
                $message .= "<p>Detalje vaše narudžbe možete pregledati na sljedećem linku: <a href='https://reusechain.ba/korisnik/#narudzbe'>Korisnički panel</a>.</p>";
                $message .= "<p>Hvala što koristite našu platformu!</p>";

                // E-mail zaglavlja
                $headers = array(
                    'Content-Type: text/html; charset=UTF-8',
                    'From: Reusechain.ba <noreply@reusechain.ba>'
                );

                // Slanje e-mailova kupcu i vlasniku proizvoda
                if (!empty($order_author_email)) {
                    wp_mail($order_author_email, $subject, $message, $headers);
                }

                if (!empty($product_owner_email)) {
                    wp_mail($product_owner_email, $subject, $message, $headers);
                }
            }
        }
    }
}, 10, 2);

// Mail o niskoj zalihi proizvoda se šalje autoru proizvoda umjesto administratoru stranice
function custom_out_of_stock_email_recipient( $recipient, $product ) {
    // Get the product author (creator) ID
    $author_id = get_post_field( 'post_author', $product->get_id() );

    // Get the author's email
    $author_email = get_the_author_meta( 'user_email', $author_id );

    // If the author has an email, use it as the recipient
    if ( !empty($author_email) ) {
        $recipient = $author_email;
    }

    return $recipient;
}


// Primjena na emailove o niskoj i nestaloj zalihi
add_filter( 'woocommerce_email_recipient_low_stock', 'custom_out_of_stock_email_recipient', 10, 2 );
add_filter( 'woocommerce_email_recipient_no_stock', 'custom_out_of_stock_email_recipient', 10, 2 );


add_filter( 'woocommerce_billing_fields', 'remove_billing_address_2_field' );

function remove_billing_address_2_field( $fields ) {
    unset( $fields['billing_address_2'] ); // Remove the Apartment/Suite field
    return $fields;
}

////////////////////////////////////////////////// DATATABLES PLUGIN ZA FILTER TABELA:

function enqueue_datatables_scripts() {
    // Enqueue DataTables CSS
    wp_enqueue_style('datatables-css', '//cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css');

    // Enqueue jQuery
    wp_enqueue_script('jquery');

    // Enqueue DataTables script with jQuery dependency
    wp_enqueue_script('datatables-js', '//cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js', array('jquery'), null, true);

    // Enqueue DataTables Buttons script with DataTables as a dependency
    wp_enqueue_script('datatables-buttons', 'https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js', array('datatables-js'), null, true);

    // Enqueue JSZip for Excel export functionality
    wp_enqueue_script('jszip', 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js', array('datatables-buttons'), null, true);

    // Enqueue pdfmake for PDF export functionality
    wp_enqueue_script('pdfmake', 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js', array('datatables-buttons'), null, true);
    wp_enqueue_script('vfs_fonts', 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js', array('pdfmake'), null, true);

    // Enqueue DataTables Buttons HTML5 export script with JSZip and pdfmake as dependencies
    wp_enqueue_script('buttons-html5', 'https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js', array('datatables-buttons', 'jszip', 'pdfmake'), null, true);

    // Enqueue DataTables Buttons Print button script with DataTables Buttons as a dependency
    wp_enqueue_script('buttons-print', 'https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js', array('datatables-buttons'), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_datatables_scripts');


//Add co2 calculated values sitewide

function calculate_total_co2_saved() {
    if (!class_exists('WooCommerce')) return 'WooCommerce not active.';

    $co2_factors = [
        'Plastika'             => 2.5,
        'Metalni otpad'        => 9.0,
        'Stakleni otpad'       => 0.7,
        'Papirni otpad'        => 1.8,
        'Tekstilni otpad'      => 5.0,
        'Elektronski otpad'    => 20.0,
        'Drvni otpad'          => 4.0,
        'Poljoprivredni otpad' => 3.0,
        'Prehrambeni otpad'    => 1.5,
        'Guma'                 => 7.5,
    ];

    $order_statuses = ['wc-completed', 'wc-processing', 'wc-on-hold'];
    $args = [
        'limit'    => -1,
        'status'   => $order_statuses,
        'return'   => 'ids',
    ];
    $orders = wc_get_orders($args);

    $category_counts = [];

    foreach ($orders as $order_id) {
        $order = wc_get_order($order_id);
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;

            $quantity = $item->get_quantity();
            $categories = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);

            foreach ($categories as $cat) {
                if (isset($co2_factors[$cat])) {
                    if (!isset($category_counts[$cat])) {
                        $category_counts[$cat] = 0;
                    }
                    $category_counts[$cat] += $quantity;
                    break; // Assume one top-level category per product
                }
            }
        }
    }

    $output = '<div class="co2-saved-summary">';
    $output .= '<h3>🌱 Ukupno ušteđenog CO₂ po kategoriji:</h3>';
    $output .= '<ul style="margin-top: 0; padding-left: 20px;">';

    $grand_total = 0;
    foreach ($category_counts as $cat => $qty) {
        $co2 = $qty * $co2_factors[$cat];
        $grand_total += $co2;
        $output .= '<li><strong>' . esc_html($cat) . ':</strong> ' . number_format($co2, 2) . ' kg CO₂</li>';
    }
    $output .= '</ul>';

    $output .= '<p style="background-color: rgba(0, 128, 0, 0.08); padding: 20px 12px 20px 20px; border-radius: 6px; font-size: 16px; margin-top: 14px;">';
    $output .= '<strong>🌍 Ukupno CO₂ ušteđeno:</strong> ' . number_format($grand_total, 2) . ' kg</p>';

    $output .= '</div>';

    return $output;
}
add_shortcode('co2_saved_summary', 'calculate_total_co2_saved');


//Add vendor based co2 calculated statistic
function calculate_vendor_co2_saved() {
    if (!is_user_logged_in()) {
        return '<p>Morate biti ulogovani da biste vidjeli svoju CO₂ statistiku.</p>';
    }

    $current_user_id = get_current_user_id();

    $co2_factors = [
        'Plastika'             => 2.5,
        'Metalni otpad'        => 9.0,
        'Stakleni otpad'       => 0.7,
        'Papirni otpad'        => 1.8,
        'Tekstilni otpad'      => 5.0,
        'Elektronski otpad'    => 20.0,
        'Drvni otpad'          => 4.0,
        'Poljoprivredni otpad' => 3.0,
        'Prehrambeni otpad'    => 1.5,
        'Guma'                 => 7.5,
    ];

    $order_statuses = ['wc-completed', 'wc-processing', 'wc-on-hold'];
    $args = [
        'limit'    => -1,
        'status'   => $order_statuses,
        'return'   => 'ids',
    ];
    $orders = wc_get_orders($args);
    $category_counts = [];

    foreach ($orders as $order_id) {
        $order = wc_get_order($order_id);
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product || $product->get_post_data()->post_author != $current_user_id) continue;

            $quantity = $item->get_quantity();
            $categories = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);

            foreach ($categories as $cat) {
                if (isset($co2_factors[$cat])) {
                    if (!isset($category_counts[$cat])) {
                        $category_counts[$cat] = 0;
                    }
                    $category_counts[$cat] += $quantity;
                    break;
                }
            }
        }
    }

    if (empty($category_counts)) {
        return '<p style="background:#ffe8e8;padding:10px;border-left:4px solid #e02b2b;">Nema prodaja u CO₂ kategorijama za ovog korisnika.</p>';
    }

    $output = '<div class="co2-saved-summary">';
    $output .= '<h3>🌱 Vaša CO₂ ušteda po kategoriji</h3>';
    $output .= '<ul>';

    $grand_total = 0;
    foreach ($category_counts as $cat => $qty) {
        $co2 = $qty * $co2_factors[$cat];
        $grand_total += $co2;
        $output .= '<li><strong>' . esc_html($cat) . ':</strong> ' . number_format($co2, 2) . ' kg CO₂</li>';
    }

    $output .= '</ul>';
    $output .= '<p style="text-align:center;font-weight:bold;margin-top:10px; padding: 20px; background:#dff9ee;">🌍 Ukupno CO₂ ušteđeno: ' . number_format($grand_total, 2) . ' kg</p>';
    $output .= '</div>';

    return $output;
}
add_shortcode('co2_saved_vendor', 'calculate_vendor_co2_saved');




//Total co2 per vendor
/**
 * =====================================================
 * Vendor total CO2 saved (based on orders) - SQL version
 * Shortcode: [co2_saved_vendor_total]
 * - računa samo proizvode gdje je post_author = trenutni user
 * - statusi: completed / processing / on-hold
 * - koristi 10 top-level CO2 kategorija po slugovima
 * - isključuje kategoriju "paketi"
 * =====================================================
 */
function shortcode_co2_saved_vendor_total() {

    if (!class_exists('WooCommerce')) {
        return '<p>WooCommerce nije aktiviran.</p>';
    }

    if (!is_user_logged_in()) {
        return '<p>Morate biti ulogovani da biste vidjeli svoju CO₂ statistiku.</p>';
    }

    global $wpdb;

    $current_user_id = get_current_user_id();

    // 10 CO2 kategorija (slugovi)
    $target_categories = [
        'plastika',
        'metalni-otpad',
        'stakleni-otpad',
        'papirni-otpad',
        'tekstilni-otpad',
        'elektronski-otpad',
        'drvni-otpad',
        'poljoprivredni-otpad',
        'prehrambeni-otpad',
        'guma',
    ];

    // Isključi kategoriju (slug)
    $exclude_category = 'paketi';

    // CO₂ faktori (isti princip kao primjer)
    $co2_factors = [
        'plastika'             => 1.5,
        'metalni-otpad'        => 1.4,
        'stakleni-otpad'       => 0.3,
        'papirni-otpad'        => 1.0,
        'tekstilni-otpad'      => 2.0,
        'elektronski-otpad'    => 10.0,
        'drvni-otpad'          => 0.5,
        'poljoprivredni-otpad' => 0.2,
        'prehrambeni-otpad'    => 0.2,
        'guma'                 => 0.4,
    ];

    // Prosječne težine (kg) (isti princip kao primjer)
    $avg_weights = [
        'plastika'             => 0.5,
        'metalni-otpad'        => 2.0,
        'stakleni-otpad'       => 1.0,
        'papirni-otpad'        => 0.2,
        'tekstilni-otpad'      => 0.4,
        'elektronski-otpad'    => 3.0,
        'drvni-otpad'          => 5.0,
        'poljoprivredni-otpad' => 1.5,
        'prehrambeni-otpad'    => 0.3,
        'guma'                 => 0.4,
    ];

    // Cache per user (10 min)
    $cache_key = 'rc_co2_vendor_total_v1_' . (int) $current_user_id;
    $cached = get_transient($cache_key);

    if (is_array($cached) && isset($cached['total_kg'])) {
        $total_kg = (float) $cached['total_kg'];
    } else {

        $order_statuses = ['wc-completed', 'wc-processing', 'wc-on-hold'];

        $ph_status = implode(',', array_fill(0, count($order_statuses), '%s'));
        $ph_cats   = implode(',', array_fill(0, count($target_categories), '%s'));

        /**
         * Zbir količina po kategoriji (slug) za proizvode čiji je author = trenutni korisnik.
         */
        $sql = "
            SELECT t.slug AS cat_slug, SUM(CAST(qty.meta_value AS DECIMAL(20,6))) AS total_qty
            FROM {$wpdb->posts} o
            INNER JOIN {$wpdb->prefix}woocommerce_order_items oi
                ON oi.order_id = o.ID AND oi.order_item_type = 'line_item'
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta qty
                ON qty.order_item_id = oi.order_item_id AND qty.meta_key = '_qty'
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta pid
                ON pid.order_item_id = oi.order_item_id AND pid.meta_key = '_product_id'
            INNER JOIN {$wpdb->posts} p
                ON p.ID = CAST(pid.meta_value AS UNSIGNED)
            INNER JOIN {$wpdb->term_relationships} tr
                ON tr.object_id = p.ID
            INNER JOIN {$wpdb->term_taxonomy} tt
                ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'product_cat'
            INNER JOIN {$wpdb->terms} t
                ON t.term_id = tt.term_id
            WHERE o.post_type = 'shop_order'
              AND o.post_status IN ($ph_status)
              AND p.post_type = 'product'
              AND p.post_author = %d
              AND t.slug IN ($ph_cats)
              AND t.slug <> %s
            GROUP BY t.slug
        ";

        $params = array_merge($order_statuses, [(int) $current_user_id], $target_categories, [$exclude_category]);
        $rows = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

        $total_kg = 0.0;

        if (!empty($rows)) {
            foreach ($rows as $r) {
                $slug = isset($r['cat_slug']) ? (string) $r['cat_slug'] : '';
                $qty  = isset($r['total_qty']) ? (float) $r['total_qty'] : 0.0;

                if ($qty <= 0 || !isset($co2_factors[$slug])) {
                    continue;
                }

                // Ista logika kao u primjeru (guma poseban slučaj)
                if ($slug === 'guma') {
                    $auto_qty  = $qty / 2;
                    $truck_qty = $qty / 2;
                    $saved_kg  = ($auto_qty * 10 * $co2_factors[$slug]) +
                                 ($truck_qty * 50 * $co2_factors[$slug]);
                } else {
                    $weight   = $avg_weights[$slug] ?? 1;
                    $saved_kg = $qty * $weight * $co2_factors[$slug];
                }

                $total_kg += $saved_kg;
            }
        }

        set_transient($cache_key, ['total_kg' => $total_kg], 10 * MINUTE_IN_SECONDS);
    }

    if ($total_kg <= 0) {
        return '<p style="background:#ffe8e8;padding:10px;border-left:4px solid #e02b2b;">Nema prodaja u CO₂ kategorijama za ovog korisnika.</p>';
    }

    $format_val = function($kg) {
        $kg = (float) $kg;
        return ($kg >= 1000)
            ? round($kg / 1000, 2) . ' t CO₂'
            : round($kg, 2) . ' kg CO₂';
    };

    return '<div class="co2-saved-summary">
        <h3>🌱 Ukupna CO₂ ušteda prodavača</h3>
        <p style="text-align:center;font-size:20px;margin-top:10px;"><strong>' . esc_html($format_val($total_kg)) . '</strong></p>
    </div>';
}
add_shortcode('co2_saved_vendor_total', 'shortcode_co2_saved_vendor_total');
//Kraj total co2 per vendor



//Sitewide total co2 saved
function calculate_sitewide_total_co2_saved() {
    if (!class_exists('WooCommerce')) {
        return '<p>WooCommerce nije aktiviran.</p>';
    }

    $co2_factors = [
        'Plastika'             => 2.5,
        'Metalni otpad'        => 9.0,
        'Stakleni otpad'       => 0.7,
        'Papirni otpad'        => 1.8,
        'Tekstilni otpad'      => 5.0,
        'Elektronski otpad'    => 20.0,
        'Drvni otpad'          => 4.0,
        'Poljoprivredni otpad' => 3.0,
        'Prehrambeni otpad'    => 1.5,
        'Guma'                 => 7.5,
    ];

    $orders = wc_get_orders([
        'limit'  => -1,
        'status' => ['wc-completed', 'wc-processing', 'wc-on-hold'],
        'return' => 'ids',
    ]);

    $total_co2 = 0;

    foreach ($orders as $order_id) {
        $order = wc_get_order($order_id);
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;

            $quantity = $item->get_quantity();
            $categories = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);

            foreach ($categories as $cat) {
                if (isset($co2_factors[$cat])) {
                    $total_co2 += $quantity * $co2_factors[$cat];
                    break;
                }
            }
        }
    }

    if ($total_co2 === 0) {
        return '<p style="font-size:16px;color:#999;">Nema potvrđenih prodaja u CO₂ kategorijama.</p>';
    }

    return '<div style="font-size:22px; font-weight:bold; margin:20px 0; color: black;">
        🌍 Očekivanaaa ušteda CO₂ pri realizaciji aktivnih oglasa: ' . number_format($total_co2, 2) . ' kg
    </div>';
}
add_shortcode('co2_saved_sitewide_total', 'calculate_sitewide_total_co2_saved');

//Total per current year

function shortcode_vendor_co2_chart() {
    if (!is_user_logged_in()) {
        return '<p>Morate biti ulogovani da biste vidjeli svoju CO₂ statistiku.</p>';
    }

    $current_user_id = get_current_user_id();
    $year = date('Y');

    $co2_factors = [
        'Plastika'             => 2.5,
        'Metalni otpad'        => 9.0,
        'Stakleni otpad'       => 0.7,
        'Papirni otpad'        => 1.8,
        'Tekstilni otpad'      => 5.0,
        'Elektronski otpad'    => 20.0,
        'Drvni otpad'          => 4.0,
        'Poljoprivredni otpad' => 3.0,
        'Prehrambeni otpad'    => 1.5,
        'Guma'                 => 7.5,
    ];

    $bosnian_months = ['Januar', 'Februar', 'Mart', 'April', 'Maj', 'Juni', 'Juli', 'August', 'Septembar', 'Oktobar', 'Novembar', 'Decembar'];
    $monthly_data = [];

    for ($month = 1; $month <= 12; $month++) {
        $start_date = date('Y-m-d H:i:s', strtotime("$year-$month-01"));
        $end_date   = date('Y-m-d H:i:s', strtotime("+1 month", strtotime($start_date)));

        $orders = wc_get_orders([
            'limit'        => -1,
            'status'       => ['wc-completed', 'wc-processing', 'wc-on-hold'],
            'return'       => 'ids',
            'date_created' => $start_date . '...' . $end_date,
        ]);

        $monthly_total = 0;

        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) continue;

            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if (!$product || $product->get_post_data()->post_author != $current_user_id) continue;

                $quantity = $item->get_quantity();
                $categories = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);

                foreach ($categories as $cat) {
                    if (isset($co2_factors[$cat])) {
                        $monthly_total += $quantity * $co2_factors[$cat];
                        break;
                    }
                }
            }
        }

        $monthly_data[] = round($monthly_total, 2);
    }

    $chart_id = 'co2Chart_' . uniqid();
    $labels = json_encode($bosnian_months);
    $data = json_encode($monthly_data);

    ob_start();
    ?>
    <canvas id="<?php echo esc_attr($chart_id); ?>" height="100"></canvas>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const ctx = document.getElementById('<?php echo esc_js($chart_id); ?>').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo $labels; ?>,
            datasets: [{
                label: 'CO₂ ušteda po mjesecu (kg)',
                data: <?php echo $data; ?>,
                backgroundColor: 'rgba(0, 128, 197, 0.6)',
                borderColor: 'rgba(0, 128, 197, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'kg CO₂'
                    }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ctx.raw + ' kg CO₂';
                        }
                    }
                }
            }
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('vendor_co2_chart', 'shortcode_vendor_co2_chart');

function shortcode_top5_co2_totals() {
    if (!is_user_logged_in()) {
        return '<p>Morate biti ulogovani da biste vidjeli statistiku.</p>';
    }

    $current_user_id = get_current_user_id();
    $year = date('Y');
    $start_date = "$year-01-01 00:00:00";
    $end_date   = "$year-12-31 23:59:59";

    $co2_factors = [
        'Plastika'             => 2.5,
        'Metalni otpad'        => 9.0,
        'Stakleni otpad'       => 0.7,
        'Papirni otpad'        => 1.8,
        'Tekstilni otpad'      => 5.0,
        'Elektronski otpad'    => 20.0,
        'Drvni otpad'          => 4.0,
        'Poljoprivredni otpad' => 3.0,
        'Prehrambeni otpad'    => 1.5,
        'Guma'                 => 7.5,
    ];

    $user_totals = [];
    $orders = wc_get_orders([
        'limit'        => -1,
        'status'       => ['wc-completed', 'wc-processing', 'wc-on-hold'],
        'return'       => 'ids',
        'date_created' => $start_date . '...' . $end_date,
    ]);

    foreach ($orders as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) continue;

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;

            $product_id = $product->get_id();
            $post = get_post($product_id);
            if (!$post) continue;

            $author_id = (int)$post->post_author;
            $qty = $item->get_quantity();
            $cats = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'names']);
            if (!is_array($cats)) continue;

            foreach ($cats as $cat) {
                if (isset($co2_factors[$cat])) {
                    if (!isset($user_totals[$author_id])) {
                        $user_totals[$author_id] = [
                            'name'  => '', // Will be set later
                            'total' => 0,
                        ];
                    }
                    $user_totals[$author_id]['total'] += $qty * $co2_factors[$cat];
                    break;
                }
            }
        }
    }

    if (empty($user_totals)) {
        return '<p>Nema korisnika sa CO₂ uštedom ove godine.</p>';
    }

    uasort($user_totals, fn($a, $b) => $b['total'] <=> $a['total']);

    // Get top 5 users including current user if not in top 4
    $final_list = [];
    $anonymous_count = 1;
    foreach ($user_totals as $user_id => $data) {
        if ($user_id == $current_user_id) {
            $final_list[$user_id] = [
                'name'  => get_user_meta($current_user_id, 'naziv', true) ?: wp_get_current_user()->display_name . ' (Vi)',
                'total' => $data['total'],
            ];
        } elseif (count($final_list) < 5 || isset($user_totals[$current_user_id])) {
            $final_list[$user_id] = [
                'name'  => 'Konkurent #' . $anonymous_count++,
                'total' => $data['total'],
            ];
        }

        if (count($final_list) >= 5 && isset($final_list[$current_user_id])) {
            break;
        }
    }

    // Chart data
    $labels = [];
    $values = [];
    $colors = [];
    $chart_id = 'co2Chart_' . uniqid();

    foreach ($final_list as $uid => $user) {
        $is_self = ($uid == $current_user_id);
        $labels[] = $user['name'];
        $values[] = round($user['total'], 2);
        $colors[] = $is_self ? 'rgba(0,180,90,0.8)' : 'rgba(0,128,197,0.6)';
    }

    // Chart output
    static $chart_js_loaded = false;
    ob_start();
    ?>
    <div class="top5-co2-chart">
        <canvas id="<?php echo esc_attr($chart_id); ?>" height="100"></canvas>

        <?php if (!$chart_js_loaded): ?>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <?php $chart_js_loaded = true; ?>
        <?php endif; ?>

        <script>
        (function(){
            const ctx = document.getElementById('<?php echo esc_js($chart_id); ?>').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        label: 'CO₂ ušteda (kg)',
                        data: <?php echo json_encode($values); ?>,
                        backgroundColor: <?php echo json_encode($colors); ?>,
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'kg CO₂' }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: ctx => ctx.raw + ' kg CO₂'
                            }
                        },
                        legend: { display: false }
                    }
                }
            });
        })();
        </script>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('top5_co2_totals', 'shortcode_top5_co2_totals');

//Statistika za cijelu stranicu top 5 kompanija
function shortcode_top5_co2_chart_anonymous() {
    if (!is_user_logged_in()) {
        return '<p>Morate biti ulogovani da biste vidjeli statistiku.</p>';
    }

    $current_user_id = get_current_user_id();
    $year = date('Y');
    $start_date = "$year-01-01 00:00:00";
    $end_date   = "$year-12-31 23:59:59";

    $co2_factors = [
        'Plastika'             => 2.5,
        'Metalni otpad'        => 9.0,
        'Stakleni otpad'       => 0.7,
        'Papirni otpad'        => 1.8,
        'Tekstilni otpad'      => 5.0,
        'Elektronski otpad'    => 20.0,
        'Drvni otpad'          => 4.0,
        'Poljoprivredni otpad' => 3.0,
        'Prehrambeni otpad'    => 1.5,
        'Guma'                 => 7.5,
    ];

    $user_totals = [];
    $orders = wc_get_orders([
        'limit'        => -1,
        'status'       => ['wc-completed', 'wc-processing', 'wc-on-hold'],
        'return'       => 'ids',
        'date_created' => $start_date . '...' . $end_date,
    ]);

    foreach ($orders as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) continue;

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;

            $product_id = $product->get_id();
            $post = get_post($product_id);
            if (!$post) continue;

            $author_id = (int)$post->post_author;
            $qty = $item->get_quantity();
            $cats = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'names']);
            if (!is_array($cats)) continue;

            foreach ($cats as $cat) {
                if (isset($co2_factors[$cat])) {
                    if (!isset($user_totals[$author_id])) {
                        $user_totals[$author_id] = 0;
                    }
                    $user_totals[$author_id] += $qty * $co2_factors[$cat];
                    break;
                }
            }
        }
    }

    if (empty($user_totals)) {
        return '<p>Nema kompanija sa CO₂ uštedom ove godine.</p>';
    }

    arsort($user_totals);
    $top5 = array_slice($user_totals, 0, 5, true);

    $labels = [];
    $values = [];
    $colors = [];
    $chart_id = 'co2Chart_' . uniqid();

    $rank = 1;
    foreach ($top5 as $user_id => $total) {
        $labels[] = 'Kompanija #' . $rank++;
        $values[] = round($total, 2);
        $colors[] = 'rgba(0,128,197,0.6)';
    }

    static $chart_js_loaded = false;
    ob_start();
    ?>
    <div class="co2-saved-summary">
        <h3>Top 5 Kompanija po CO₂ uštedi</h3>
        <canvas id="<?php echo esc_attr($chart_id); ?>" height="100"></canvas>

        <?php if (!$chart_js_loaded): ?>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <?php $chart_js_loaded = true; ?>
        <?php endif; ?>

        <script>
        (function(){
            const ctx = document.getElementById('<?php echo esc_js($chart_id); ?>').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        label: 'CO₂ ušteda (kg)',
                        data: <?php echo json_encode($values); ?>,
                        backgroundColor: <?php echo json_encode($colors); ?>,
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'kg CO₂' }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: ctx => ctx.raw + ' kg CO₂'
                            }
                        },
                        legend: { display: false }
                    }
                }
            });
        })();
        </script>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('top5_co2_chart_anonymous', 'shortcode_top5_co2_chart_anonymous');

//Monri integracija 
define( 'MONRI_AUTH_TOKEN',  '8fdc1fd4f69199e1687023c6a12e32a0ba28ecee' );   // test auth token
define( 'MONRI_MERCHANT_KEY','key-5b4dfefafcfd855c506dc29f4a30beef' );   // test merchant key
define( 'MONRI_BASE_URL',    'https://ipgtest.monri.com' );

/*  MONRI TEST KONFIG  */
if ( ! defined( 'MONRI_MODE' ) )        define( 'MONRI_MODE', 'test' );
if ( ! defined( 'MONRI_BASE_URL' ) )    define( 'MONRI_BASE_URL', 'https://ipgtest.monri.com' );
if ( ! defined( 'MONRI_AUTH_TOKEN' ) )  define( 'MONRI_AUTH_TOKEN', 'TEST_AUTH_TOKEN_xxx' );
if ( ! defined( 'MONRI_MERCHANT_KEY' ) )define( 'MONRI_MERCHANT_KEY', 'TEST_MERCHANT_KEY_xxx' );

/*  CREATE PAYMENT ENDPOINT  */
if ( ! function_exists( 'rc_create_monri_payment' ) ) {
	add_action( 'rest_api_init', function () {
		register_rest_route( 'monri/v1', '/create-payment/', [
			'methods'             => 'POST',
			'callback'            => 'rc_create_monri_payment',
			'permission_callback' => '__return_true',
		] );
	} );

	function rc_create_monri_payment( WP_REST_Request $request ) {

		if ( ! is_user_logged_in() ) {
			return new WP_REST_Response( [ 'error' => 'Not logged in' ], 401 );
		}

		$params  = $request->get_json_params();
		$package = isset( $params['package'] ) ? sanitize_key( $params['package'] ) : '';
		$months  = isset( $params['months'] )  ? absint( $params['months'] )       : 0;

		$prices = [ 'standard' => 500, 'premium' => 1000 ];      // cijene u feningama
		if ( ! isset( $prices[ $package ] ) || $months < 1 || $months > 12 ) {
			return new WP_REST_Response( [ 'error' => 'Bad package/months' ], 400 );
		}

		$amount   = $prices[ $package ] * $months;
		$order_id = uniqid( 'rc_' . get_current_user_id() . '_' );

		$body = [
			'amount'           => $amount,
			'order_number'     => $order_id,
			'currency'         => 'BAM',
			'transaction_type' => 'purchase',
			'order_info'       => "ReUseChain {$package} – {$months}m",
			'scenario'         => 'charge',
			'supported_payment_methods' => [ 'card' ],
		];
		$body_json = wp_json_encode( $body );
		$ts        = time();
		$digest    = hash( 'sha512', MONRI_MERCHANT_KEY . $ts . MONRI_AUTH_TOKEN . $body_json );
		error_log( 'RAW: ' . MONRI_MERCHANT_KEY . $ts . MONRI_AUTH_TOKEN . $body_json );
error_log( 'DIGEST: ' . $digest );

		$response = wp_remote_post(
			MONRI_BASE_URL . '/v2/payment/new',
			[
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => "WP3-v2 " . MONRI_AUTH_TOKEN . " {$ts} {$digest}",
				],
				'body'    => $body_json,
				'timeout' => 20,
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response( [ 'error' => $response->get_error_message() ], 500 );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['client_secret'] ) ) {
			return new WP_REST_Response( [ 'error' => 'Monri response', 'details' => $data ], 500 );
		}

		update_user_meta( get_current_user_id(), 'rc_pending_order', [
			'order_id' => $order_id,
			'package'  => $package,
			'months'   => $months,
		] );

		return new WP_REST_Response( [
			'client_secret' => $data['client_secret'],
			'order_id'      => $order_id,
			'auth_token'    => MONRI_AUTH_TOKEN,
		] );
	}
}

/*  WEBHOOK ENDPOINT  */
if ( ! function_exists( 'rc_monri_webhook' ) ) {
	add_action( 'rest_api_init', function () {
		register_rest_route( 'monri/v1', '/webhook/', [
			'methods'             => 'POST',
			'callback'            => 'rc_monri_webhook',
			'permission_callback' => '__return_true',
		] );
	} );

	function rc_monri_webhook( WP_REST_Request $request ) {

		$raw      = $request->get_body();
		$auth_hdr = $request->get_header( 'authorization' );

		if ( ! $auth_hdr || strpos( $auth_hdr, 'WP3-callback ' ) !== 0 ) {
			return new WP_REST_Response( [ 'error' => 'No/Bad auth header' ], 401 );
		}

		$sent = trim( str_replace( 'WP3-callback', '', $auth_hdr ) );
		$calc = hash( 'sha512', MONRI_MERCHANT_KEY . $raw );
		if ( ! hash_equals( $calc, $sent ) ) {
			return new WP_REST_Response( [ 'error' => 'Digest mismatch' ], 401 );
		}

		$payload = json_decode( $raw, true );
		if ( ! $payload || $payload['status'] !== 'approved' ) {
			return new WP_REST_Response( [ 'ignored' => true ] );
		}

		$order = sanitize_text_field( $payload['order_number'] );
		$users = get_users( [
			'meta_key'   => 'rc_pending_order',
			'meta_value' => $order,
			'compare'    => 'LIKE',
			'number'     => 1,
		] );
		if ( ! $users ) {
			return new WP_REST_Response( [ 'error' => 'User not found' ], 404 );
		}

		$user   = $users[0];
		$info   = get_user_meta( $user->ID, 'rc_pending_order', true );
		$months = isset( $info['months'] ) ? absint( $info['months'] ) : 1;

		wp_update_user( [ 'ID' => $user->ID, 'role' => 'premium_user' ] );
		update_user_meta( $user->ID, 'premium_expires',
			date( 'Y-m-d', strtotime( "+{$months} months" ) ) );
		delete_user_meta( $user->ID, 'rc_pending_order' );

		return new WP_REST_Response( [ 'ok' => true ] );
	}
}






//Last online funkcija

add_action('init', function () {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $last_online = get_user_meta($user_id, 'last_online', true);
        $last_time = $last_online ? strtotime($last_online) : 0;
        $now = current_time('timestamp');

        // Update only if more than 5 minutes passed
        if (($now - $last_time) > 300) {
            update_user_meta($user_id, 'last_online', current_time('mysql'));
        }
    }
});


function show_last_online_relative_shortcode($atts) {
    global $post;

    $atts = shortcode_atts([
        'user_id' => null,
    ], $atts);

    // If user_id not provided, use post author (vendor)
    $user_id = $atts['user_id'] ? intval($atts['user_id']) : ($post->post_author ?? null);
    if (!$user_id) {
        return '<span style="font-weight: 500; color: #666; font-size: 14px;">Korisnik nije pronađen.</span>';
    }

    $last_online = get_user_meta($user_id, 'last_online', true);
    if (!$last_online) {
        return '<span style="font-weight: 500; color: #666; font-size: 14px;">Nema podataka o posljednjoj aktivnosti.</span>';
    }

    $last_timestamp = strtotime($last_online);
    $now = current_time('timestamp');
    $diff = $now - $last_timestamp;

    if ($diff < 60) {
        $text = 'Online prije nekoliko sekundi.';
    } elseif ($diff < 3600) {
        $text = 'Online prije ' . floor($diff / 60) . ' minuta.';
    } elseif ($diff < 86400) {
        $text = 'Online prije ' . floor($diff / 3600) . ' sati.';
    } elseif ($diff < 604800) {
        $text = 'Online prije ' . floor($diff / 86400) . ' dana.';
    } else {
        // ✅ Fixed format
       $text = 'Online ' . date_i18n('j. F Y. \u H:i', $last_timestamp);

        // Example: Online 19. juna 2025. u 09:28
    }

    return '<span style="font-weight: 500; color: #666; font-size: 14px;">' . esc_html($text) . '</span>';
}
add_shortcode('last_online', 'show_last_online_relative_shortcode');

//Kraj last online
//aktivni danas
/**
 * =====================================================
 * TRACK LAST ONLINE (frontend only)
 * =====================================================
 */
add_action('init', function () {

    if (!is_user_logged_in()) {
        return;
    }

    // Ignore admin, ajax, cron, REST
    if (
        is_admin() ||
        wp_doing_ajax() ||
        wp_doing_cron() ||
        defined('REST_REQUEST')
    ) {
        return;
    }

    $user_id = get_current_user_id();
    $now = current_time('timestamp');

    $last = get_user_meta($user_id, 'last_online', true);
    if (!is_numeric($last)) {
        $last = strtotime($last);
    }

    // Update only if more than 5 minutes passed
    if (!$last || ($now - $last) > 300) {
        update_user_meta($user_id, 'last_online', $now);
    }
});


/**
 * =====================================================
 * SHORTCODE: [aktivni_danas]
 * =====================================================
 */
function rc_aktivni_danas_shortcode($atts) {

    $atts = shortcode_atts([
        'limit' => 50,
    ], $atts);

    $limit = max(1, (int)$atts['limit']);
    $today_start = strtotime('today', current_time('timestamp'));

    $users = get_users([
        'meta_key' => 'last_online',
        'number'   => 2000,
        'fields'   => ['ID', 'display_name'],
    ]);

    if (!$users) {
        return '<p>Nema aktivnih korisnika danas.</p>';
    }

    $rows = [];

    foreach ($users as $u) {
        $raw = get_user_meta($u->ID, 'last_online', true);
        if (!$raw) continue;

        $ts = is_numeric($raw) ? (int)$raw : strtotime($raw);

        if ($ts >= $today_start) {
            $rows[] = [
                'ID' => $u->ID,
                'name' => $u->display_name,
                'last_online' => $ts
            ];
        }
    }

    if (!$rows) {
        return '<p>Nema aktivnih korisnika danas.</p>';
    }

    usort($rows, fn($a, $b) => $b['last_online'] <=> $a['last_online']);
    $rows = array_slice($rows, 0, $limit);

    // učitaj dashicons
    wp_enqueue_style('dashicons');

    $out = '<div class="online-korisnici-widget"><ul class="aktivni-korisnici-danas">';

    foreach ($rows as $r) {

        $display_ts = $r['last_online'];
        $diff = current_time('timestamp') - $r['last_online'];

        if ($diff < 600) {
            $li_class = 'is-online';
            $status_class = 'online';
            $status_text = 'online';
        } else {
            $li_class = 'is-today';
            $status_class = 'offline';
            $status_text = 'aktivno danas';
        }

        if (date_i18n('Y-m-d', $display_ts) === date_i18n('Y-m-d')) {
            $last_text = 'danas u ' . date_i18n('H:i', $display_ts);
        } else {
            $last_text = date_i18n('j.m.Y. \u H:i', $display_ts);
        }

        $profile_url = home_url('/korisnik/' . $r['ID'] . '/info/');

        $out .= sprintf(
            '<li class="%s">

                <div class="korisnik-info">

                    <span class="dashicons dashicons-admin-users user-icon"></span>

                    <div class="korisnik-info-text">
                        <a href="%s">%s</a>
                        <span class="last-active">Zadnje: %s</span>
                    </div>

                </div>

                <span class="aktivni-status %s">
                    <span class="status-dot"></span>
                    %s
                </span>

            </li>',
            esc_attr($li_class),
            esc_url($profile_url),
            esc_html($r['name']),
            esc_html($last_text),
            esc_attr($status_class),
            esc_html($status_text)
        );
    }

    $out .= '</ul></div>';

    return $out;
}
add_shortcode('aktivni_danas', 'rc_aktivni_danas_shortcode');
//kraj aktivni danas
function upload_error_notice_box_shortcode() {
    ob_start();
    ?>
    <style>
    .modal {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.4);
    }

    .modal-content {
        position: fixed;
        background-color: #fefefe;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        animation: fadeIn 0.5s ease-out forwards;
        width: 30%;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .ok-btn {
      background-color: #388E3C;
      color: #fff;
      border: none;
      padding: 8px 20px;
      font-size: 16px;
      cursor: pointer;
      margin-top: 10px;
    }

    .ok-btn:hover {
      background-color: #000;
    }

    @media (max-width: 1024px) {
      .modal-content { width: 80%; padding: 15px; }
    }

    @media (max-width: 767px) {
      .modal-content { width: 90%; padding: 10px; }
    }
    </style>

    <div id="errorModal" class="modal">
      <div class="modal-content">
        <p id="errorMessage">Error message will appear here...</p>
        <button class="ok-btn">OK</button>
      </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('.ok-btn').on('click', function() {
            $('#errorModal').hide();
            $('.jet-form-builder-file-upload__file-remove').click();
        });

        $('.jet-form-builder-file-upload__input').on('change', function() {
            const input = this;
            const file = input.files[0];

            if (file) {
                const filename = file.name;
                const maxLength = 200; // you can adjust this limit

                if (filename.length > maxLength) {
                    $('#errorMessage').text('Naziv datoteke je predugačak. Molimo preimenujte fajl i pokušajte ponovo.');
                    $('#errorModal').show();
                    input.value = ''; // reset the field to block upload
                    return;
                }
            }

            setTimeout(function() {
                const errorMessageElement = $('.jet-form-builder-file-upload__file-invalid-marker:visible');
                if (errorMessageElement.length) {
                    const message = errorMessageElement.attr('title');
                    $('#errorMessage').text(message);
                    $('#errorModal').show();
                }
            }, 150);
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('upload_error_box', 'upload_error_notice_box_shortcode');

add_action('wp_footer', 'custom_checkout_label_override');
function custom_checkout_label_override() {
    // Only run on the checkout page
    if (!is_checkout()) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('label[for="billing_first_name"]').text('Ime kompanije *');
        $('#billing_first_name').attr('placeholder', 'Unesite ime kompanije');
    });
    </script>
    <?php
}

//aml screening
add_action('wp_enqueue_scripts', function () {
    // ✅ Load jQuery (required)
    wp_enqueue_script('jquery');

    // ✅ Load your custom sanctions-check.js script
    wp_enqueue_script(
        'sanctions-check',
        get_stylesheet_directory_uri() . '/js/sanctions-check.js',
        ['jquery'],
        null,
        true
    );

    // ✅ Pass data to JS
    wp_localize_script('sanctions-check', 'SanctionsCheck', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('sanctions_check_nonce')
    ]);
});


add_action('wp_ajax_sanctions_check', 'handle_sanctions_check_ajax');
add_action('wp_ajax_nopriv_sanctions_check', 'handle_sanctions_check_ajax');

function handle_sanctions_check_ajax() {
    check_ajax_referer('sanctions_check_nonce', 'nonce');

    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    if (!$user_id) {
        wp_send_json_error(['message' => 'Korisnički ID nije validan.']);
    }

    $company_name = trim(get_user_meta($user_id, 'naziv', true));
    $country = trim(get_user_meta($user_id, 'drzava', true));

    if (empty($company_name)) {
        wp_send_json_error(['message' => 'Naziv firme nije unesen.']);
    }

    $match_query = [
        'queries' => [
            'q1' => [
                'schema' => 'Company',
                'properties' => [
                    'name' => [$company_name],
                ],
            ]
        ]
    ];

    if (!empty($country)) {
        $match_query['queries']['q1']['properties']['jurisdiction'] = [$country];
    }

    $api_key = 'f66c98f1b551327c609040ca2d8006de';
    $url = 'https://api.opensanctions.org/match/sanctions?algorithm=best';

    $response = wp_remote_post($url, [
        'headers' => [
            'Authorization' => 'ApiKey ' . $api_key,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'User-Agent'    => 'WordPressPluginSanctionsMatch/1.0'
        ],
        'body'    => wp_json_encode($match_query),
        'timeout' => 15
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => $response->get_error_message()]);
    }

    $decoded = json_decode(wp_remote_retrieve_body($response), true);
    $results = $decoded['responses']['q1']['results'] ?? [];

    if (empty($results)) {
        wp_send_json_success('<p style="font-size: 14px;">🔍 Nema podudaranja na listi sankcioniranih entiteta za: <strong>' . esc_html($company_name) . '</strong></p>');
    }

    ob_start();
    echo '<h3 style="font-size: 16px; margin-bottom: 5px;">📋 Rezultati pretrage za: <strong>' . esc_html($company_name) . '</strong></h3>';
    echo '<p style="font-size: 14px;">✅ Pronađeno podudaranja: <strong>' . count($results) . '</strong></p>';
    echo '<ul style="list-style: none; padding-left: 0; font-size: 13px;">';

    foreach ($results as $entity) {
        $name = esc_html($entity['properties']['name'][0] ?? 'N/A');
        $entity_country = esc_html($entity['properties']['country'][0] ?? 'Nepoznato');
        $dataset = esc_html($entity['dataset'] ?? 'N/A');
        $score = esc_html(number_format($entity['score'] * 100, 2)) . '%';
        $notes = !empty($entity['properties']['notes'][0]) ? esc_html($entity['properties']['notes'][0]) : null;
        $details_link = esc_url($entity['url']);

        echo '<li style="margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 10px;">';
        echo '<p style="margin: 5px 0;"><strong>🏢 Ime:</strong> ' . $name . '</p>';
        echo '<p style="margin: 5px 0;"><strong>🌍 Država:</strong> ' . $entity_country . '</p>';
        echo '<p style="margin: 5px 0;"><strong>🗂️ Dataset:</strong> ' . $dataset . '</p>';
        echo '<p style="margin: 5px 0;"><strong>📈 Score (pouzdanost):</strong> ' . $score . '</p>';
        if ($notes) {
            echo '<p style="margin: 5px 0;"><strong>📝 Napomene:</strong> ' . $notes . '</p>';
        }
        echo '<p style="margin: 5px 0;"><a href="' . $details_link . '" target="_blank">🔗 Detalji na OpenSanctions →</a></p>';
        echo '</li>';
    }

    echo '</ul>';
    $html = ob_get_clean();
    wp_send_json_success($html);
}


function sanctions_check_button_shortcode() {
    global $wp;
    $segments = explode('/', $wp->request);
    $user_id = isset($segments[1]) ? intval($segments[1]) : 0;

    ob_start();
    echo '<button id="sanctions-check-button" data-user-id="' . esc_attr($user_id) . '" style="background-color:#f0f0f0; padding:8px 12px; font-size:13px;">🔍 Provjeri sankcije</button>';
    echo '<div id="sanctions-check-result" style="margin-top:15px;"></div>';
    return ob_get_clean();
}
add_shortcode('sanctions_check_button', 'sanctions_check_button_shortcode');


// kraj screeninga


//aml multi screening
add_shortcode('sanctions_check_all_pending_ui', function () {
    ob_start();
    ?>
    <button id="sanctions-check-all" style="margin-bottom:15px;">🔍 Provjeri sve kompanije na čekanju</button>
    <div id="sanctions-check-results"></div>
    <?php
    return ob_get_clean();
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script('jquery');

    wp_enqueue_script(
        'sanctions-check-all',
        get_stylesheet_directory_uri() . '/js/sanctions-check-all.js',
        ['jquery'],
        null,
        true
    );

    wp_localize_script('sanctions-check-all', 'SanctionsCheck', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('sanctions_check_nonce')
    ]);
});

add_action('wp_ajax_sanctions_check_all_pending', 'handle_sanctions_check_all_pending');
add_action('wp_ajax_nopriv_sanctions_check_all_pending', 'handle_sanctions_check_all_pending');

function handle_sanctions_check_all_pending() {
    check_ajax_referer('sanctions_check_nonce', 'nonce');

    $companies = $_POST['companies'] ?? [];
    if (empty($companies)) {
        wp_send_json_error(['message' => 'Lista kompanija je prazna.']);
    }

    $queries = [];
    foreach ($companies as $index => $company) {
        $id = intval($company['user_id']);
        $name = sanitize_text_field($company['name']);

        if ($id && $name) {
            $queries["u{$id}"] = [
                'schema' => 'Company',
                'properties' => [
                    'name' => [$name]
                ]
            ];
        }
    }

    if (empty($queries)) {
        wp_send_json_error(['message' => 'Nema validnih podataka za provjeru.']);
    }

    $api_key = '7e9eb10fc47b4ad479ff16f9e56c2a49';
    $url = 'https://api.opensanctions.org/match/sanctions?algorithm=best';

    $response = wp_remote_post($url, [
        'headers' => [
            'Authorization' => 'ApiKey ' . $api_key,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json'
        ],
        'body'    => wp_json_encode(['queries' => $queries]),
        'timeout' => 20
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => $response->get_error_message()]);
    }

    $decoded = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($decoded['responses'])) {
        wp_send_json_error(['message' => 'Nema rezultata iz OpenSanctions API-ja.']);
    }

    ob_start();
    echo '<h3>🔎 Rezultati provjere sankcija</h3>';
    echo '<ul style="padding-left: 15px;">';

    foreach ($decoded['responses'] as $key => $result) {
        $matches = $result['results'] ?? [];
        $user_id = intval(ltrim($key, 'u'));
        $name = esc_html($queries["u{$user_id}"]['properties']['name'][0]);
        $profile_link = esc_url("https://reusechain.ba/korisnik/{$user_id}/info/");

        echo "<li style='margin-bottom: 20px;'>";
        echo "<strong>🧾 Kompanija:</strong> <a href='{$profile_link}' target='_blank'>{$name}</a><br>";

        if (empty($matches)) {
            echo "<span style='color: green;'>✅ Nema podudaranja na sankcionim listama.</span>";
        } else {
            echo "<span style='color: red;'>❌ Pronađeno " . count($matches) . " podudaranja:</span>";
            echo "<ul style='margin-top: 5px;'>";
            foreach ($matches as $match) {
                $match_name = esc_html($match['properties']['name'][0] ?? 'Nepoznato');
                $match_country = esc_html($match['properties']['country'][0] ?? 'Nepoznato');
                $match_score = esc_html(number_format($match['score'] * 100, 2)) . '%';
                $match_link = esc_url($match['url']);
                echo "<li>🔍 {$match_name} ({$match_country}) — 📈 {$match_score}<br><a href='{$match_link}' target='_blank'>🔗 Detalji</a></li>";
            }
            echo "</ul>";
        }

        echo "</li>";
    }

    echo '</ul>';
    wp_send_json_success(ob_get_clean());
}

//kraj aml multi screeninga

//co2 za tabelu
/**
 * =====================================================
 * ReUseChain – Woo order items tabela + CO2 (bez JetTables)
 *
 * Shortcode:
 * [rc_orders_table from="2025-09-01" exclude_customer="20" per_page="50" page="1"]
 *
 * - Radi SQL preko $wpdb
 * - Izračun CO2 po stavci: product_id + quantity
 * - Podržava varijacije (kategorije čita s parenta)
 * - Podržava podkategorije (uzima i parent slugove)
 * - Izbacuje proizvode iz kategorije "paketi"
 * =====================================================
 */

/** Format datuma iz WP datetime stringa u d.m.Y H:i */
function rc_fmt_datetime_dmYHi($mysql_datetime) {
    if (empty($mysql_datetime)) return '';
    $ts = strtotime($mysql_datetime);
    if (!$ts) return esc_html($mysql_datetime);
    return date_i18n('d.m.Y H:i', $ts);
}

/** Format iznosa (2 decimale) */
function rc_fmt_money_2($val) {
    if (!is_numeric($val)) return esc_html((string)$val);
    return number_format((float)$val, 2, '.', '');
}

/** CO2 format */
function rc_format_co2_val($kg) {
    $kg = (float) $kg;
    return ($kg >= 1000)
        ? round($kg / 1000, 2) . ' t CO₂'
        : round($kg, 2) . ' kg CO₂';
}

/**
 * CO2 izračun po qty + product_id (robustno za varijacije + parent kategorije)
 */
function rc_get_co2_saved_kg_by_product_qty($product_id, $qty) {

    $product_id = absint($product_id);
    $qty = (float) $qty;

    if (!$product_id || $qty <= 0) {
        return 0.0;
    }

    // 10 top-level kategorija
    $target_categories = [
        'plastika',
        'metalni-otpad',
        'stakleni-otpad',
        'papirni-otpad',
        'tekstilni-otpad',
        'elektronski-otpad',
        'drvni-otpad',
        'poljoprivredni-otpad',
        'prehrambeni-otpad',
        'guma'
    ];

    // CO₂ faktori
    $co2_factors = [
        'plastika'             => 1.5,
        'metalni-otpad'        => 1.4,
        'stakleni-otpad'       => 0.3,
        'papirni-otpad'        => 1.0,
        'tekstilni-otpad'      => 2.0,
        'elektronski-otpad'    => 10.0,
        'drvni-otpad'          => 0.5,
        'poljoprivredni-otpad' => 0.2,
        'prehrambeni-otpad'    => 0.2,
        'guma'                 => 0.4,
    ];

    // Prosječne težine
    $avg_weights = [
        'plastika'             => 0.5,
        'metalni-otpad'        => 2.0,
        'stakleni-otpad'       => 1.0,
        'papirni-otpad'        => 0.2,
        'tekstilni-otpad'      => 0.4,
        'elektronski-otpad'    => 3.0,
        'drvni-otpad'          => 5.0,
        'poljoprivredni-otpad' => 1.5,
        'prehrambeni-otpad'    => 0.3,
        'guma'                 => 0.4,
    ];

    // Ako je varijacija, pređi na parent ID za kategorije
    $resolved_id = $product_id;
    if (function_exists('wc_get_product')) {
        $wc_product = wc_get_product($product_id);
        if ($wc_product && $wc_product->is_type('variation')) {
            $parent_id = (int) $wc_product->get_parent_id();
            if ($parent_id) {
                $resolved_id = $parent_id;
            }
        }
    }

    // Učitaj kategorije (child + parent slugovi)
    $terms = wp_get_post_terms($resolved_id, 'product_cat');
    if (empty($terms) || is_wp_error($terms)) {
        return 0.0;
    }

    $slugs = [];
    foreach ($terms as $t) {
        if (!empty($t->slug)) {
            $slugs[] = $t->slug;
        }
        if (!empty($t->parent)) {
            $parent = get_term((int)$t->parent, 'product_cat');
            if ($parent && !is_wp_error($parent) && !empty($parent->slug)) {
                $slugs[] = $parent->slug;
            }
        }
    }
    $slugs = array_values(array_unique($slugs));

    // Match na jednu od 10
    $matched = '';
    foreach ($target_categories as $slug) {
        if (in_array($slug, $slugs, true)) {
            $matched = $slug;
            break;
        }
    }

    if (!$matched) {
        return 0.0;
    }

    // Izračun
    if ($matched === 'guma') {
        $factor = (float) $co2_factors[$matched];
        $auto_qty  = $qty / 2;
        $truck_qty = $qty / 2;
        return (float)(($auto_qty * 10 * $factor) + ($truck_qty * 50 * $factor));
    }

    $weight = (float) $avg_weights[$matched];
    $factor = (float) $co2_factors[$matched];

    return (float) ($qty * $weight * $factor);
}

/**
 * Shortcode tabela
 */
function rc_shortcode_orders_table($atts = []) {
    global $wpdb;

    $atts = shortcode_atts([
        'from'             => '2025-09-01',
        'exclude_customer' => '20',
        'per_page'         => '50',
        'page'             => '',
        'show_total'       => 'yes', // yes/no – ukupni CO2 i ukupni iznos na vrhu
    ], $atts, 'rc_orders_table');

    $from = preg_replace('/[^0-9\-]/', '', (string)$atts['from']);
    if (empty($from)) {
        $from = '2025-09-01';
    }

    $from_dt = $from . ' 00:00:00';

    $exclude_customer = (string)$atts['exclude_customer'];
    $exclude_customer = ($exclude_customer === '' ? '' : (string)absint($exclude_customer));

    $per_page = max(1, min(200, (int)$atts['per_page']));

    // page: ako nije proslijeđeno atributom, probaj iz query stringa
    $page = (int)$atts['page'];
    if ($page <= 0 && isset($_GET['rc_page'])) {
        $page = (int) $_GET['rc_page'];
    }

    if ($page <= 0) {
        $page = 1;
    }

    $offset = ($page - 1) * $per_page;

    $prefix = $wpdb->prefix;

    // WHERE dio (sa / bez exclude customer)
    $where_excl = '';
    $where_params = [$from_dt];

    if ($exclude_customer !== '') {
        $where_excl = " AND (cust.meta_value IS NULL OR cust.meta_value != %d) ";
        $where_params[] = (int)$exclude_customer;
    }

    /**
     * Filter za izbacivanje kategorije "paketi".
     *
     * Provjerava:
     * - direktnu kategoriju proizvoda,
     * - parent proizvod ako je item varijacija,
     * - parent kategoriju ako je proizvod u podkategoriji kategorije "paketi".
     */
    $exclude_paketi_sql = "
        AND NOT EXISTS (
            SELECT 1
            FROM {$prefix}term_relationships tr_ex
            INNER JOIN {$prefix}term_taxonomy tt_ex
                ON tt_ex.term_taxonomy_id = tr_ex.term_taxonomy_id
                AND tt_ex.taxonomy = 'product_cat'
            INNER JOIN {$prefix}terms t_ex
                ON t_ex.term_id = tt_ex.term_id
            LEFT JOIN {$prefix}term_taxonomy parent_tt_ex
                ON parent_tt_ex.term_taxonomy_id = tt_ex.parent
                AND parent_tt_ex.taxonomy = 'product_cat'
            LEFT JOIN {$prefix}terms parent_t_ex
                ON parent_t_ex.term_id = parent_tt_ex.term_id
            WHERE tr_ex.object_id = COALESCE(NULLIF(parent_product.meta_value, ''), product_id.meta_value)
              AND (
                    t_ex.slug = 'paketi'
                    OR parent_t_ex.slug = 'paketi'
              )
        )
    ";

    // COUNT za paginaciju
    $count_sql = "
        SELECT COUNT(*) FROM (
            SELECT oi.order_item_id
            FROM {$prefix}posts o

            LEFT JOIN {$prefix}woocommerce_order_items oi
                ON oi.order_id = o.ID
                AND oi.order_item_type = 'line_item'

            LEFT JOIN {$prefix}woocommerce_order_itemmeta product_id
                ON product_id.order_item_id = oi.order_item_id
                AND product_id.meta_key = '_product_id'

            LEFT JOIN {$prefix}woocommerce_order_itemmeta variation_id
                ON variation_id.order_item_id = oi.order_item_id
                AND variation_id.meta_key = '_variation_id'

            LEFT JOIN {$prefix}postmeta parent_product
                ON parent_product.post_id = variation_id.meta_value
                AND parent_product.meta_key = '_parent_product_id'

            LEFT JOIN {$prefix}postmeta cust
                ON cust.post_id = o.ID
                AND cust.meta_key = '_customer_user'

            WHERE o.post_type = 'shop_order'
              AND o.post_status = 'wc-completed'
              AND o.post_date >= %s
              {$where_excl}
              {$exclude_paketi_sql}

            GROUP BY oi.order_item_id
        ) t
    ";

    $total_rows = (int) $wpdb->get_var($wpdb->prepare($count_sql, $where_params));

    // Glavni SQL
    $main_sql = "
        SELECT
            oi.order_item_id AS item_id,
            o.ID            AS order_id,
            o.post_date     AS order_date,
            o.post_status,

            MAX(CASE WHEN pm.meta_key = '_order_total' THEN pm.meta_value END) AS total,

            CONCAT(
                MAX(CASE WHEN cu_fn.meta_key = 'first_name' THEN cu_fn.meta_value END),
                ' ',
                MAX(CASE WHEN cu_ln.meta_key = 'last_name'  THEN cu_ln.meta_value END)
            ) AS customer_name,

            oi.order_item_name AS product_name,

            MAX(CASE WHEN qty.meta_key = '_qty' THEN qty.meta_value END) AS quantity,

            MAX(CASE WHEN product_id.meta_key = '_product_id' THEN product_id.meta_value END) AS product_id,

            seller.display_name AS seller_name

        FROM {$prefix}posts o

        LEFT JOIN {$prefix}postmeta pm
            ON pm.post_id = o.ID

        LEFT JOIN {$prefix}woocommerce_order_items oi
            ON oi.order_id = o.ID
            AND oi.order_item_type = 'line_item'

        LEFT JOIN {$prefix}woocommerce_order_itemmeta qty
            ON qty.order_item_id = oi.order_item_id
            AND qty.meta_key = '_qty'

        LEFT JOIN {$prefix}woocommerce_order_itemmeta product_id
            ON product_id.order_item_id = oi.order_item_id
            AND product_id.meta_key = '_product_id'

        LEFT JOIN {$prefix}woocommerce_order_itemmeta variation_id
            ON variation_id.order_item_id = oi.order_item_id
            AND variation_id.meta_key = '_variation_id'

        LEFT JOIN {$prefix}postmeta parent_product
            ON parent_product.post_id = variation_id.meta_value
            AND parent_product.meta_key = '_parent_product_id'

        LEFT JOIN {$prefix}posts p
            ON p.ID = product_id.meta_value

        LEFT JOIN {$prefix}users seller
            ON seller.ID = p.post_author

        LEFT JOIN {$prefix}postmeta cust
            ON cust.post_id = o.ID
            AND cust.meta_key = '_customer_user'

        LEFT JOIN {$prefix}usermeta cu_fn
            ON cu_fn.user_id = cust.meta_value
            AND cu_fn.meta_key = 'first_name'

        LEFT JOIN {$prefix}usermeta cu_ln
            ON cu_ln.user_id = cust.meta_value
            AND cu_ln.meta_key = 'last_name'

        WHERE o.post_type = 'shop_order'
          AND o.post_status = 'wc-completed'
          AND o.post_date >= %s
          {$where_excl}
          {$exclude_paketi_sql}

        GROUP BY
            oi.order_item_id,
            o.ID,
            o.post_date,
            o.post_status,
            oi.order_item_name,
            seller.display_name

        ORDER BY o.post_date DESC
        LIMIT %d OFFSET %d
    ";

    $params = array_merge($where_params, [$per_page, $offset]);
    $rows = $wpdb->get_results($wpdb->prepare($main_sql, $params));

    // HTML
    $table_id = 'rc-orders-' . wp_generate_uuid4();
    $html = '';

    $html .= '<style>
        .rc-orders-wrap{background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:12px;box-shadow:0 6px 18px rgba(0,0,0,.06);padding:14px;overflow:auto}
        .rc-orders-table{width:100%;border-collapse:collapse;min-width:920px}
        .rc-orders-table th,.rc-orders-table td{border-bottom:1px solid rgba(0,0,0,.08);padding:10px 12px;text-align:left;font-size:14px;vertical-align:top;white-space:nowrap}
        .rc-orders-table th{font-weight:600;background:#fafafa}
        .rc-orders-table td.col-product{white-space:normal;min-width:260px}
        .rc-orders-table td.col-customer{white-space:normal;min-width:220px}
        .rc-orders-table td.col-seller{white-space:normal;min-width:180px}
        .rc-orders-top{display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:space-between;margin:0 0 10px 0}
        .rc-orders-top .badge{background:#f3f5f7;border:1px solid rgba(0,0,0,.08);border-radius:999px;padding:6px 10px;font-size:13px}
        .rc-orders-pagination{display:flex;gap:8px;justify-content:flex-end;align-items:center;margin-top:12px}
        .rc-orders-pagination a,.rc-orders-pagination span{padding:6px 10px;border:1px solid rgba(0,0,0,.12);border-radius:8px;font-size:13px;text-decoration:none}
        .rc-orders-pagination span.current{background:#111;color:#fff;border-color:#111}
        .rc-orders-table th.col-num,.rc-orders-table td.col-num{width:56px;text-align:right}
    </style>';

    $html .= '<div class="rc-orders-wrap" id="' . esc_attr($table_id) . '">';

    if (empty($rows)) {
        $html .= '<p>Nema rezultata za odabrani period.</p></div>';
        return $html;
    }

    // Totali na strani (ne global totals po bazi)
    $page_total_amount = 0.0;
    $page_total_co2 = 0.0;

    foreach ($rows as $r) {
        $page_total_amount += is_numeric($r->total) ? (float)$r->total : 0.0;
        $page_total_co2 += rc_get_co2_saved_kg_by_product_qty($r->product_id, $r->quantity);
    }

    if ($atts['show_total'] === 'yes') {
        $html .= '<div class="rc-orders-top">';
        $html .= '<span class="badge">Ukupno CO₂ (ova strana): <strong>' . esc_html(rc_format_co2_val($page_total_co2)) . '</strong></span>';
        $html .= '<span class="badge">Ukupno iznos (ova strana): <strong>' . esc_html(rc_fmt_money_2($page_total_amount)) . '</strong></span>';
        $html .= '</div>';
    }

    $html .= '<table class="rc-orders-table">';
    $html .= '<thead><tr>';
    $html .= '<th class="col-num">#</th>';
    $html .= '<th>Order ID</th>';
    $html .= '<th>Proizvod</th>';
    $html .= '<th>Kupac</th>';
    $html .= '<th>Prodavač</th>';
    $html .= '<th>Vrijeme</th>';
    $html .= '<th>Količina</th>';
    $html .= '<th>Total Iznos</th>';
    $html .= '<th>Ušteda CO₂</th>';
    $html .= '</tr></thead><tbody>';

    // Numeracija: globalno kroz paginaciju
    $row_num = $offset + 1;

    foreach ($rows as $r) {

        $co2_kg = rc_get_co2_saved_kg_by_product_qty($r->product_id, $r->quantity);

        $html .= '<tr>';
        $html .= '<td class="col-num">' . esc_html((string)$row_num) . '.</td>';
        $html .= '<td>' . esc_html($r->order_id) . '</td>';

        $product_link = '';
        if (!empty($r->product_id)) {
            $product_link = get_permalink((int) $r->product_id);
        }

        if ($product_link) {
            $html .= '<td class="col-product"><a href="' . esc_url($product_link) . '" target="_blank" rel="noopener noreferrer">'
                   . esc_html($r->product_name)
                   . '</a></td>';
        } else {
            $html .= '<td class="col-product">' . esc_html($r->product_name) . '</td>';
        }

        $html .= '<td class="col-customer">' . esc_html(trim((string)$r->customer_name)) . '</td>';
        $html .= '<td class="col-seller">' . esc_html((string)$r->seller_name) . '</td>';
        $html .= '<td>' . esc_html(rc_fmt_datetime_dmYHi($r->order_date)) . '</td>';
        $html .= '<td>' . esc_html(rc_fmt_money_2($r->quantity)) . '</td>';
        $html .= '<td>' . esc_html(rc_fmt_money_2($r->total)) . '</td>';
        $html .= '<td>' . esc_html(rc_format_co2_val($co2_kg)) . '</td>';
        $html .= '</tr>';

        $row_num++;
    }

    $html .= '</tbody></table>';

    // Paginacija (simple)
    $total_pages = (int) ceil($total_rows / $per_page);

    if ($total_pages > 1) {
        $base_url = remove_query_arg(
            ['rc_page'],
            (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
        );

        $html .= '<div class="rc-orders-pagination">';

        $prev = max(1, $page - 1);
        $next = min($total_pages, $page + 1);

        if ($page > 1) {
            $html .= '<a href="' . esc_url(add_query_arg('rc_page', $prev, $base_url)) . '">« Prethodna</a>';
        }

        $start = max(1, $page - 3);
        $end   = min($total_pages, $page + 3);

        if ($start > 1) {
            $html .= '<a href="' . esc_url(add_query_arg('rc_page', 1, $base_url)) . '">1</a>';
            if ($start > 2) {
                $html .= '<span>…</span>';
            }
        }

        for ($i = $start; $i <= $end; $i++) {
            if ($i === $page) {
                $html .= '<span class="current">' . esc_html((string)$i) . '</span>';
            } else {
                $html .= '<a href="' . esc_url(add_query_arg('rc_page', $i, $base_url)) . '">' . esc_html((string)$i) . '</a>';
            }
        }

        if ($end < $total_pages) {
            if ($end < $total_pages - 1) {
                $html .= '<span>…</span>';
            }
            $html .= '<a href="' . esc_url(add_query_arg('rc_page', $total_pages, $base_url)) . '">' . esc_html((string)$total_pages) . '</a>';
        }

        if ($page < $total_pages) {
            $html .= '<a href="' . esc_url(add_query_arg('rc_page', $next, $base_url)) . '">Sljedeća »</a>';
        }

        $html .= '</div>';
    }

    $html .= '</div>';

    return $html;
}

add_shortcode('rc_orders_table', 'rc_shortcode_orders_table');
//kraj co2 za tabelu

//Izračun co 2 za par proizvoda

function shortcode_co2_saved_products_total() {

    // 10 CO2 kategorija koje koristi i veliki shortcode
    $target_categories = [
        'plastika',
        'metalni-otpad',
        'stakleni-otpad',
        'papirni-otpad',
        'tekstilni-otpad',
        'elektronski-otpad',
        'drvni-otpad',
        'poljoprivredni-otpad',
        'prehrambeni-otpad',
        'guma'
    ];

    // CO₂ faktori
    $co2_factors = [
        'plastika'             => 1.5,
        'metalni-otpad'        => 1.4,
        'stakleni-otpad'       => 0.3,
        'papirni-otpad'        => 1.0,
        'tekstilni-otpad'      => 2.0,
        'elektronski-otpad'    => 10.0,
        'drvni-otpad'          => 0.5,
        'poljoprivredni-otpad' => 0.2,
        'prehrambeni-otpad'    => 0.2,
        'guma'                 => 0.4,
    ];

    // Prosječne težine
    $avg_weights = [
        'plastika'             => 0.5,
        'metalni-otpad'        => 2.0,
        'stakleni-otpad'       => 1.0,
        'papirni-otpad'        => 0.2,
        'tekstilni-otpad'      => 0.4,
        'elektronski-otpad'    => 3.0,
        'drvni-otpad'          => 5.0,
        'poljoprivredni-otpad' => 1.5,
        'prehrambeni-otpad'    => 0.3,
        'guma'                 => 0.4,
    ];

    $total_kg   = 0;
    $cat_totals = [];

    // Prolazimo kroz svih 10 kategorija
    foreach ($target_categories as $slug) {

        // Query svih aktivnih proizvoda u kategoriji
        $args = [
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'tax_query' => [[
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $slug,
            ]]
        ];

        $query = new WP_Query($args);

        foreach ($query->posts as $p) {

            $product = wc_get_product($p->ID);
            if (!$product) continue;

            $stock = $product->get_stock_quantity();
            if (!$stock || $stock <= 0) continue;

            // CO₂ izračun
            if ($slug === 'guma') {
                $auto_qty  = $stock / 2;
                $truck_qty = $stock / 2;
                $saved_kg  = ($auto_qty * 10 * $co2_factors[$slug]) +
                             ($truck_qty * 50 * $co2_factors[$slug]);
            } else {
                $weight   = $avg_weights[$slug] ?? 1;
                $saved_kg = $stock * $weight * $co2_factors[$slug];
            }

            // Totali
            $total_kg += $saved_kg;
            $cat_totals[$slug] = ($cat_totals[$slug] ?? 0) + $saved_kg;
        }
    }

    if ($total_kg <= 0) {
        return '<p>Nema aktivnih proizvoda.</p>';
    }

    // Format funkcija
    $format_val = function($kg) {
        return ($kg >= 1000)
            ? round($kg / 1000, 2) . ' t CO₂'
            : round($kg, 2) . ' kg CO₂';
    };

    // HTML isti kao tvoj postojeći prikaz
    $html  = '<div class="co2-saved-products">';
    $html .= '<h3>🌍 Očekivana ušteda CO₂ pri realizaciji aktivnih oglasa: <strong>' . $format_val($total_kg) . '</strong></h3>';
    $html .= '<ul>';

    foreach ($cat_totals as $cat => $val) {
        $html .= '<li>' . esc_html($cat) . ': <strong>' . $format_val($val) . '</strong></li>';
    }

    $html .= '</ul></div>';

    return $html;
}
add_shortcode('co2_saved_products_total', 'shortcode_co2_saved_products_total');



//kraj co2 izracuna za par proizvoda

//Zabrana slanja poruka
/** * 🚫 Blokada pokretanja novih razgovora za freemium i standard korisnike * ✅ Dozvoljen reply u postojećim threadovima */ add_filter('rest_pre_dispatch', function( $result, $server, $request ){ $route = $request->get_route(); 
// Hvata REST rutu za pokretanje novih threadova
if( strpos($route, '/better-messages/v1/thread/new') === 0 ){ $user_id = get_current_user_id(); if( $user_id ){ $paket = get_user_meta($user_id, 'paket_korisnika', true); if( in_array($paket, ['freemium', 'standard'], true) ){ return new WP_Error( 'forbidden', '❌ Pokretanje novih konverzacija dostupno je samo Premium korisnicima.', [ 'status' => 403 ] ); } } } return $result; }, 10, 3); 

/** * 🎨 Frontend cleanup — sakrij UI za pokretanje novih konverzacija * i bulk messaging (freemium i standard korisnici) */ add_action('wp_footer', function(){ if( ! is_user_logged_in() ) return; $user_id = get_current_user_id(); $paket = get_user_meta($user_id, 'paket_korisnika', true); if( ! in_array($paket, ['freemium', 'standard'], true) ) return; ?> <script> document.addEventListener('DOMContentLoaded', function () { const hideUI = () => { 
// 🔒 sakrij polje za pretragu korisnika 
			document.querySelectorAll('.bm-search-users, .better-messages-search, input[type="search"]').forEach(el => { el.style.display = 'none'; }); 
			// 🔒 sakrij dugme "New Conversation"
			document.querySelectorAll('a.new-message, [aria-label="New Conversation"]').forEach(el => { el.style.display = 'none'; }); 
			// 🔒 sakrij header u "Start a new conversation" ekranu
			document.querySelectorAll('.chat-header, #bm-new-thread-title').forEach(el => { el.style.display = 'none'; }); 
			// 🔒 sakrij fallback link "Start a new conversation" 
			document.querySelectorAll('p.bpbm-empty-link, p.bpbm-empty-link a[href*="new-conversation"]').forEach(el => { el.style.display = 'none'; }); 
			// 🔒 sakrij "or" tekst ispod praznog inboxa 
			document.querySelectorAll('p.bpbm-empty-or').forEach(el => { el.style.display = 'none'; });
			// 🔒 sakrij bulk messaging dugmad 
			document.querySelectorAll('.mass-message, [href*="send-bulk"]').forEach(el => { el.style.display = 'none'; }); }; hideUI(); 
			// odmah sakrij // posmatraj DOM i re-primijeni ako se UI re-renderuje 
			const observer = new MutationObserver(hideUI); observer.observe(document.body, { childList: true, subtree: true }); }); </script> <?php });
//Kraj zabrane slanja

//Forma za pakete




//Kraj Forme za pakete

//prikaz proizvoda frontend
function rc_lista_artikala_dynamic_counts($atts) {
    ob_start();

    $atts = shortcode_atts([
        'per_page'    => 10,
        'exclude_cat' => 'paketi',
    ], $atts);

    // GET parametri
    $paged       = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $sort        = isset($_GET['sort'])   ? sanitize_text_field($_GET['sort'])   : 'date_desc';
    $filter_cat  = isset($_GET['cat'])    ? sanitize_text_field($_GET['cat'])    : '';
    $filter_user = isset($_GET['firma'])  ? sanitize_text_field($_GET['firma'])  : '';

    // ------------------------------------------------------------
    // 1) TERM ID za "Paketi" da ga možemo isključiti
    // ------------------------------------------------------------
    $exclude_term = get_term_by('slug', $atts['exclude_cat'], 'product_cat');
    $exclude_id   = $exclude_term ? (int) $exclude_term->term_id : 0;

    // ------------------------------------------------------------
    // 2) SVE KATEGORIJE (osim Paketi) za dropdown
    //    Count ćemo računati dinamički, ne koristimo ->count
    // ------------------------------------------------------------
    $all_categories = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'exclude'    => [$exclude_id],
    ]);

    // ------------------------------------------------------------
    // 3) SVE FIRME (autori) koje uopće imaju proizvode van Paketa
    //    (lista ID-eva, a count ćemo računa po trenutnim filterima)
    // ------------------------------------------------------------
    global $wpdb;

    $author_ids = $wpdb->get_col("
        SELECT DISTINCT p.post_author
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
        INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
        WHERE p.post_type = 'product'
          AND p.post_status = 'publish'
          AND tt.taxonomy = 'product_cat'
          AND t.slug != 'paketi'
    ");

    // ------------------------------------------------------------
    // 4) POMOĆNI "BASE" ARGUMENTI ZA BROJANJE (count)
    // ------------------------------------------------------------
    $base_count_args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
    ];

    // ------------------------------------------------------------
    // 5) DINAMIČKI COUNT ZA KATEGORIJE (uz trenutni filter firme)
    // ------------------------------------------------------------
    $category_counts = [];

    foreach ($all_categories as $cat) {
        $args_cat = $base_count_args;

        $tax_query = [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => [$cat->slug],
            ]
        ];

        // ako je odabrana firma, filtriraj i po njoj
        if (!empty($filter_user)) {
            $args_cat['author'] = (int) $filter_user;
        }

        $args_cat['tax_query'] = $tax_query;

        $q_cat = new WP_Query($args_cat);
        $category_counts[$cat->slug] = (int) $q_cat->found_posts;
        wp_reset_postdata();
    }

    // ------------------------------------------------------------
    // 6) DINAMIČKI COUNT ZA FIRME (uz trenutni filter kategorije)
    //    + opet isključujemo "Pakete" gdje treba
    // ------------------------------------------------------------
    $firm_list = [];

    foreach ($author_ids as $aid) {
        $args_user = $base_count_args;
        $args_user['author'] = (int) $aid;

        $tax_query = [];

        if (!empty($filter_cat)) {
            // ako je odabrana kategorija, brojimo samo unutar nje
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => [$filter_cat],
            ];
        } else {
            // nema odabrane kategorije, brojimo sve osim Paketi
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => [$atts['exclude_cat']],
                'operator' => 'NOT IN',
            ];
        }

        if (!empty($tax_query)) {
            $args_user['tax_query'] = $tax_query;
        }

        $q_user = new WP_Query($args_user);
        $count_user = (int) $q_user->found_posts;
        wp_reset_postdata();

        // prikaži firmu samo ako ima barem 1 "aktivni" artikal prema filterima
        if ($count_user > 0) {
            $user = get_userdata($aid);
            if ($user) {
                $firm_list[] = [
                    'id'    => $aid,
                    'name'  => $user->display_name,
                    'count' => $count_user,
                ];
            }
        }
    }

    // ------------------------------------------------------------
    // 7) GLAVNI QUERY ZA LISTU PROIZVODA
    // ------------------------------------------------------------
    $args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => (int) $atts['per_page'],
        'paged'          => $paged,
        'tax_query'      => [],
    ];

    // Filter kategorije
    if (!empty($filter_cat)) {
        $args['tax_query'][] = [
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => [$filter_cat],
        ];
    } else {
        // ako nije odabrana specifična kategorija, izbacujemo Pakete
        $args['tax_query'][] = [
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => [$atts['exclude_cat']],
            'operator' => 'NOT IN',
        ];
    }

    // Filter firme
    if (!empty($filter_user)) {
        $args['author'] = (int) $filter_user;
    }

    // Sortiranje
    switch ($sort) {
        case 'price_asc':
            $args['meta_key'] = '_price';
            $args['orderby']  = 'meta_value_num';
            $args['order']    = 'ASC';
            break;

        case 'price_desc':
            $args['meta_key'] = '_price';
            $args['orderby']  = 'meta_value_num';
            $args['order']    = 'DESC';
            break;

        case 'name_asc':
            $args['orderby']  = 'title';
            $args['order']    = 'ASC';
            break;

        case 'name_desc':
            $args['orderby']  = 'title';
            $args['order']    = 'DESC';
            break;

        case 'date_asc':
            $args['orderby']  = 'date';
            $args['order']    = 'ASC';
            break;

        default:
            $args['orderby']  = 'date';
            $args['order']    = 'DESC';
            break;
    }

    $query = new WP_Query($args);

    // ------------------------------------------------------------
    // 8) FILTERI — jedan red, sa dinamičkim countovima
    // ------------------------------------------------------------
    ?>
    <form method="GET" class="rc-filter-form">
        <div class="rc-filter-row">

            <!-- SORTIRANJE -->
            <div class="rc-filter-box">
                <label>Sortiraj</label>
                <select name="sort" onchange="this.form.submit()">
                    <option value="date_desc"  <?php selected($sort, 'date_desc'); ?>>Najnoviji</option>
                    <option value="date_asc"   <?php selected($sort, 'date_asc'); ?>>Najstariji</option>
                    <option value="price_asc"  <?php selected($sort, 'price_asc'); ?>>Cijena (rast.)</option>
                    <option value="price_desc" <?php selected($sort, 'price_desc'); ?>>Cijena (opad.)</option>
                    <option value="name_asc"   <?php selected($sort, 'name_asc'); ?>>Naziv A–Ž</option>
                    <option value="name_desc"  <?php selected($sort, 'name_desc'); ?>>Naziv Ž–A</option>
                </select>
            </div>

            <!-- KATEGORIJE -->
            <div class="rc-filter-box">
                <label>Kategorija</label>
                <select name="cat" onchange="this.form.submit()">
                    <option value="">Sve kategorije</option>
                    <?php foreach ($all_categories as $cat): 
                        $slug = $cat->slug;
                        $cnt  = isset($category_counts[$slug]) ? $category_counts[$slug] : 0;
                        // prikazujemo i one sa 0, ali možeš ovdje staviti if ($cnt == 0) continue;
                    ?>
                        <option value="<?php echo esc_attr($slug); ?>"
                            <?php selected($filter_cat, $slug); ?>>
                            <?php echo esc_html($cat->name . " (" . $cnt . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- FIRME -->
            <div class="rc-filter-box">
                <label>Firma</label>
                <select name="firma" onchange="this.form.submit()">
                    <option value="">Sve firme</option>
                    <?php foreach ($firm_list as $firm): ?>
                        <option value="<?php echo esc_attr($firm['id']); ?>"
                            <?php selected($filter_user, $firm['id']); ?>>
                            <?php echo esc_html($firm['name'] . " (" . $firm['count'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- RESET FILTERA -->
            <div class="rc-filter-box rc-reset-wrap">
                <label>&nbsp;</label>
                <a href="<?php echo esc_url( strtok($_SERVER['REQUEST_URI'], '?') ); ?>" class="rc-reset-btn">Reset</a>
            </div>

        </div>
    </form>
    <?php

    // ------------------------------------------------------------
    // 9) LISTA PROIZVODA
    // ------------------------------------------------------------
    echo '<div class="rc-artikal-wrapper">';

    if ($query->have_posts()) :
        while ($query->have_posts()) : $query->the_post();
            global $product;

            $img_url   = '';
            $image_id  = $product->get_image_id();
            if ($image_id) {
                $img_src = wp_get_attachment_image_src($image_id, 'large');
                if ($img_src) {
                    $img_url = $img_src[0];
                }
            }

            $autor     = get_the_author();
            $datum     = get_the_date('d.m.Y H:i');
            $kolicina  = $product->get_stock_quantity();
            if ($kolicina === null || $kolicina === '') {
                $kolicina = 'N/A';
            }

            $cats = wp_get_post_terms(get_the_ID(), 'product_cat', [
                'exclude' => $exclude_id ? [$exclude_id] : [],
            ]);
            ?>
            <div class="rc-artikal-card">

                <div class="rc-slika">
                    <a href="<?php the_permalink(); ?>">
                        <?php if ($img_url): ?>
                            <img src="<?php echo esc_url($img_url); ?>" alt="<?php the_title_attribute(); ?>">
                        <?php else: ?>
                            <div style="width:200px;height:120px;background:#eee;border-radius:6px;"></div>
                        <?php endif; ?>
                    </a>
                </div>

                <div class="rc-desni-dio">

                    <h3 class="rc-naslov">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>

                    <div class="rc-kategorije">
                        <?php foreach ($cats as $c): ?>
                            <span class="rc-cat-pill"><?php echo esc_html($c->name); ?></span>
                        <?php endforeach; ?>
                    </div>

                    <div class="rc-info-red">
                        <span><strong>Objavio:</strong> <?php echo esc_html($autor); ?></span>
                        <span><strong>Datum:</strong> <?php echo esc_html($datum); ?></span>
                        <span><strong>Količina:</strong> <?php echo esc_html($kolicina); ?></span>
                    </div>

                    <a class="pregled-btn" href="<?php the_permalink(); ?>">Pregledaj oglas</a>
                </div>

            </div>
            <?php
        endwhile;
    else:
        echo '<p>Nema pronađenih artikala za zadane filtere.</p>';
    endif;

    echo '</div>';

    // ------------------------------------------------------------
    // 10) PAGINACIJA
    // ------------------------------------------------------------
    echo '<div class="rc-pagination">';
    echo paginate_links([
        'total'     => $query->max_num_pages,
        'current'   => $paged,
        'prev_text' => '« Prethodna',
        'next_text' => 'Sljedeća »',
    ]);
    echo '</div>';

    wp_reset_postdata();
    ?>

    <style>
        .rc-filter-form { width: 100%; margin-bottom: 25px; }
        .rc-filter-row { display: flex; gap: 20px; flex-wrap: wrap; align-items: flex-end; }

        .rc-filter-box { display: flex; flex-direction: column; min-width: 180px; }
        .rc-filter-box label {
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 4px;
            color: #1e3d24;
        }

        .rc-filter-box select {
            padding: 8px 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 0.95rem;
            background: #fff;
        }

        .rc-reset-wrap { display: flex; flex-direction: column; justify-content: flex-end; }
        .rc-reset-btn {
            background: #bbb;
            padding: 8px 14px;
            border-radius: 6px;
            color: #fff !important;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s ease;
        }
        .rc-reset-btn:hover { background: #999; }

        .rc-artikal-wrapper { display: flex; flex-direction: column; gap: 20px; }

        .rc-artikal-card {
            display: flex;
            gap: 20px;
            padding: 15px;
            background: #fff;
            border-radius: 12px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        }

        .rc-slika img {
            width: 200px !important;
            height: 120px !important;
            object-fit: cover !important;
            border-radius: 6px;
        }

        .rc-desni-dio { flex: 1; }

        .rc-naslov a {
            color: #1e3d24;
            font-size: 1.25rem;
            text-decoration: none;
            font-weight: 600;
        }

        .rc-kategorije {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin: 6px 0 10px;
        }

        .rc-cat-pill {
            background: #e8f5e9;
            color: #2c974b;
            padding: 3px 10px;
            border-radius: 14px;
            border: 1px solid #c8e6c9;
            font-size: 0.82rem;
        }

        .rc-info-red {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .pregled-btn {
            background: #2c974b;
            padding: 8px 14px;
            border-radius: 8px;
            color: #fff !important;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s ease;
        }
        .pregled-btn:hover { background: #23793b; }

        .rc-pagination { text-align: center; margin-top: 25px; }
    </style>

    <?php

    return ob_get_clean();
}
add_shortcode('rc_lista_artikala7', 'rc_lista_artikala_dynamic_counts');

//kraj prikaza proizvoda

//jednostavna statistika
/* ----------------------------------------------------
   CO₂ – pregled po kategorijama
   Shortcode: [co2_categories_overview]
---------------------------------------------------- */

function shortcode_co2_categories_overview() {

    // 10 kategorija
    $target_categories = [
        'plastika',
        'metalni-otpad',
        'stakleni-otpad',
        'papirni-otpad',
        'tekstilni-otpad',
        'elektronski-otpad',
        'drvni-otpad',
        'poljoprivredni-otpad',
        'prehrambeni-otpad',
        'guma'
    ];

    // Ikonice po kategoriji (emo ge)
    $icons = [
        'plastika'             => '🧴',
        'metalni-otpad'        => '🔩',
        'stakleni-otpad'       => '🧪',
        'papirni-otpad'        => '📄',
        'tekstilni-otpad'      => '👕',
        'elektronski-otpad'    => '💻',
        'drvni-otpad'          => '🪵',
        'poljoprivredni-otpad' => '🌾',
        'prehrambeni-otpad'    => '🍎',
        'guma'                 => '🛞'
    ];

    // CO₂ faktori
    $co2_factors = [
        'plastika'             => 1.5,
        'metalni-otpad'        => 1.4,
        'stakleni-otpad'       => 0.3,
        'papirni-otpad'        => 1.0,
        'tekstilni-otpad'      => 2.0,
        'elektronski-otpad'    => 10.0,
        'drvni-otpad'          => 0.5,
        'poljoprivredni-otpad' => 0.2,
        'prehrambeni-otpad'    => 0.2,
        'guma'                 => 0.4
    ];

    // Prosječne težine
    $avg_weights = [
        'plastika'             => 0.5,
        'metalni-otpad'        => 2.0,
        'stakleni-otpad'       => 1.0,
        'papirni-otpad'        => 0.2,
        'tekstilni-otpad'      => 0.4,
        'elektronski-otpad'    => 3.0,
        'drvni-otpad'          => 5.0,
        'poljoprivredni-otpad' => 1.5,
        'prehrambeni-otpad'    => 0.3,
        'guma'                 => 0.4
    ];

    $results = [];
    $total_all_categories = 0;

    foreach ($target_categories as $slug) {

        // Query
        $args = [
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'tax_query' => [[
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $slug
            ]]
        ];

        $query = new WP_Query($args);
        $products = $query->posts;

        $ads_count = count($products);
        $companies = [];
        $total_saved = 0;

        foreach ($products as $p) {
            $product = wc_get_product($p->ID);
            if (!$product) continue;

            // Unique company authors
            $author_id = $p->post_author;
            if ($author_id) {
                $companies[$author_id] = true;
            }

            // Stock ONLY for CO2
            $stock = $product->get_stock_quantity();
            if (!$stock || $stock <= 0) continue;

            // CO₂
            if ($slug === 'guma') {
                $auto_qty  = $stock / 2;
                $truck_qty = $stock / 2;
                $saved_kg  = ($auto_qty * 10 * $co2_factors[$slug]) + ($truck_qty * 50 * $co2_factors[$slug]);
            } else {
                $weight   = $avg_weights[$slug];
                $saved_kg = $stock * $weight * $co2_factors[$slug];
            }

            $total_saved += $saved_kg;
        }

        $total_all_categories += $total_saved;

        $results[] = [
            'slug'      => $slug,
            'icon'      => $icons[$slug] ?? '♻️',
            'ads'       => $ads_count,
            'companies' => count($companies),
            'saved'     => $total_saved
        ];
    }

    // Format
    $format_val = function($kg) {
        return ($kg >= 1000)
            ? round($kg / 1000, 2) . ' t CO₂'
            : round($kg, 2) . ' kg CO₂';
    };

    ob_start();
    ?>

    <style>
        .co2-total-box {
            padding: 15px 20px;
            border-radius: 12px;
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
            margin-bottom: 25px;
            font-size: 20px;
            font-weight: 600;
            color: #2d7c30;
            text-align: center;
        }
        .co2-cat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
        }
        .co2-card {
            background: #ffffff;
            border: 1px solid #eee;
            padding: 18px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: 0.2s;
        }
        .co2-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .co2-icon {
            font-size: 34px;
            margin-bottom: 10px;
        }
        .co2-card h4 {
            margin: 0 0 10px;
            font-size: 18px;
            font-weight: 600;
        }
        .co2-card .line {
            margin-bottom: 6px;
            color: #444;
        }
        .co2-card strong {
            color: #2d7c30;
        }
    </style>

    <!-- TOTAL ON THE TOP -->
    <div class="co2-total-box">
        🌍 Ukupna potencijalna ušteda CO₂: <strong><?php echo $format_val($total_all_categories); ?></strong>
    </div>

    <div class="co2-cat-grid">
        <?php foreach ($results as $r): ?>
            <div class="co2-card">
                <div class="co2-icon"><?php echo $r['icon']; ?></div>
                <h4><?php echo esc_html(ucwords(str_replace('-', ' ', $r['slug']))); ?></h4>

                <div class="line">Broj oglasa: <strong><?php echo $r['ads']; ?></strong></div>

                <div class="line">Broj kompanija: <strong><?php echo $r['companies']; ?></strong></div>

                <div class="line">CO₂ potencijalna ušteda: <strong><?php echo $format_val($r['saved']); ?></strong></div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php
    return ob_get_clean();
}

add_shortcode('co2_categories_overview', 'shortcode_co2_categories_overview');

// kraj jednostavne statistike



// better messages notifikacija
/* ---------------------------------------------------------
 *  1) Postavi ime i email pošiljaoca
 * ---------------------------------------------------------*/
add_filter('wp_mail_from_name', fn() => 'ReUseChain');
add_filter('wp_mail_from', fn() => 'noreply@reusechain.ba');


add_filter('bp_better_messages_email_content', function($content, $args){

    // dostupne varijable iz plugina
    $user        = $args['user'];
    $messageHtml = $args['messageHtml'];
    $thread_url  = $args['thread_url'];
    $email_subject = $args['email_subject'] ?? 'Nova poruka';

    ob_start();
    include get_stylesheet_directory() . '/reusechain-email-template.php';
    return ob_get_clean();

}, 10, 2);


//kraj better messages notifikacija

//fix za jet form
add_filter('script_loader_src', function ($src) {
    if (is_admin()) {
        $src = remove_query_arg('ver', $src);
    }
    return $src;
}, 999);

// kraj fix za jet form


//default featured slika
/**
 * Set default featured image for all posts (Novosti) that don't have one.
 */
function rc_auto_default_featured_image( $post_id ) {

    // Run only for standard posts
    if ( get_post_type( $post_id ) !== 'post' ) {
        return;
    }

    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }

    if ( has_post_thumbnail( $post_id ) ) {
        return;
    }

    // The default image URL you gave me
    $default_image_url = 'https://reusechain.ba/wp-content/uploads/2025/07/image.jpg';

    // Try to get ID from URL
    $default_image_id = attachment_url_to_postid( $default_image_url );

    // If ID not found (rare case), stop
    if ( ! $default_image_id ) {
        return;
    }

    // Set the featured image
    set_post_thumbnail( $post_id, $default_image_id );
}
add_action( 'save_post', 'rc_auto_default_featured_image' );

//kraj default featured slike

//admin panel
//razlika u broju registrovanih
add_shortcode( 'user_growth_percent', function () {

    global $wpdb;

    $table = $wpdb->users;

    $sql = "
        SELECT
            CASE
                WHEN prev_30.users_prev_30 = 0 THEN 0
                ELSE ROUND(
                    ((last_30.users_last_30 - prev_30.users_prev_30) / prev_30.users_prev_30) * 100,
                    1
                )
            END AS growth_percent
        FROM
            (
                SELECT COUNT(ID) AS users_last_30
                FROM {$table}
                WHERE user_registered >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ) AS last_30,
            (
                SELECT COUNT(ID) AS users_prev_30
                FROM {$table}
                WHERE user_registered < DATE_SUB(NOW(), INTERVAL 30 DAY)
                  AND user_registered >= DATE_SUB(NOW(), INTERVAL 60 DAY)
            ) AS prev_30
    ";

    $row = $wpdb->get_row( $sql );

    if ( ! $row ) {
        return '<span class="user-growth neutral">0%</span>';
    }

    $growth = (float) $row->growth_percent;

    if ( $growth > 0 ) {
        return '<span class="user-growth positive">⬆ +' . $growth . '%</span>';
    }

    if ( $growth < 0 ) {
        return '<span class="user-growth negative">⬇ ' . $growth . '%</span>';
    }

    return '<span class="user-growth neutral">0%</span>';
});


//kraj razlika u broju registrovanih
//kraj admin panela

// pocetak besplatno cijena


//kraj besplatno cijena

//pocetak admin poruke
/**
 * =========================================================
 * ADMIN CHAT – JetEngine CCT
 * - AJAX auto refresh
 * - Paginacija (10+ poruka) sa Prev/Next
 * - Korekcija vremena: trenutno vrijeme (na ispisu)
 *
 * CCT tabela: {$wpdb->prefix}jet_cct_admin_chat
 * Polja: poruka, sender_id, cct_created
 *
 * Shortcode:
 * [admin_chat_feed per_page="10" refresh="15"]
 * refresh = sekunde (0 = bez auto refresha)
 * =========================================================
 */

/**
 * Render feed HTML (koristi se i za shortcode i za AJAX)
 */
function reusechain_admin_chat_feed_render($page = 1, $per_page = 10) {

    if (!is_user_logged_in() || !current_user_can('administrator')) {
        return '<p>Nemaš pristup.</p>';
    }

    global $wpdb;

    $table = $wpdb->prefix . 'jet_cct_admin_chat';

    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if ($exists !== $table) {
        return '<p>CCT tabela nije pronađena.</p>';
    }

    $page = max(1, intval($page));
    $per_page = max(1, intval($per_page));
    $offset = ($page - 1) * $per_page;

    // Ukupan broj poruka
    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    $total_pages = max(1, (int) ceil($total / $per_page));

    // Ako user ode van opsega
    if ($page > $total_pages) {
        $page = $total_pages;
        $offset = ($page - 1) * $per_page;
    }

    // Učitavanje poruka
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT poruka, sender_id, cct_created
             FROM {$table}
             ORDER BY cct_created DESC
             LIMIT %d OFFSET %d",
            $per_page,
            $offset
        )
    );

    ob_start();

    if (empty($rows)) {
        echo '<p>Nema poruka.</p>';
    } else {
        ?>
        <div class="admin-chat-feed">
            <?php foreach ($rows as $row): ?>
                <?php
               // Pošiljalac
$sender_id = (int) ($row->sender_id ?? 0);
$user = $sender_id ? get_user_by('id', $sender_id) : false;
$display_name = $user ? $user->display_name : 'Nepoznat korisnik';

// Vrijeme (iz baze, bez ručnog offseta)
$time_str = !empty($row->cct_created)
    ? date('d.m.Y H:i', strtotime($row->cct_created . ' GMT'))
    : '—';

$msg = isset($row->poruka) ? (string) $row->poruka : '';
                ?>
                <div class="admin-chat-row">
                    <div class="admin-chat-left">
                        <div class="admin-chat-name"><?php echo esc_html($display_name); ?></div>
                        <div class="admin-chat-text"><?php echo nl2br(esc_html($msg)); ?></div>
                    </div>

                    <div class="admin-chat-right">
                        <span class="admin-chat-badge"><?php echo esc_html($time_str); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    // Paginacija (Prev/Next)
    $prev_disabled = ($page <= 1) ? 'disabled' : '';
    $next_disabled = ($page >= $total_pages) ? 'disabled' : '';

    ?>
    <div class="admin-chat-pagination"
         data-current-page="<?php echo esc_attr($page); ?>"
         data-total-pages="<?php echo esc_attr($total_pages); ?>">
        <button type="button" class="admin-chat-page-btn" data-page="<?php echo esc_attr($page - 1); ?>" <?php echo $prev_disabled; ?>>
            ‹ Prethodna
        </button>

        <span class="admin-chat-page-info">
            Stranica <?php echo esc_html($page); ?> / <?php echo esc_html($total_pages); ?>
            <span class="admin-chat-page-total">(ukupno: <?php echo esc_html($total); ?>)</span>
        </span>

        <button type="button" class="admin-chat-page-btn" data-page="<?php echo esc_attr($page + 1); ?>" <?php echo $next_disabled; ?>>
            Sljedeća ›
        </button>
    </div>
    <?php

    return ob_get_clean();
}

/**
 * AJAX handler
 */
function reusechain_admin_chat_feed_ajax() {

    if (!is_user_logged_in() || !current_user_can('administrator')) {
        wp_send_json_error(['html' => '<p>Nemaš pristup.</p>'], 403);
    }

    check_ajax_referer('reusechain_admin_chat_nonce', 'nonce');

    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;

    $html = reusechain_admin_chat_feed_render($page, $per_page);

    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_reusechain_admin_chat_feed', 'reusechain_admin_chat_feed_ajax');

/**
 * Shortcode + JS (paginacija + auto refresh)
 */
add_shortcode('admin_chat_feed', function ($atts) {

    $atts = shortcode_atts([
        'per_page' => 10,
        'refresh'  => 15, // sekunde
    ], $atts);

    $per_page = max(1, intval($atts['per_page']));
    $refresh  = max(0, intval($atts['refresh']));

    // Početna stranica (iz URL-a ako želiš, npr. ?ac_page=2)
    $page = isset($_GET['ac_page']) ? max(1, intval($_GET['ac_page'])) : 1;

    $nonce   = wp_create_nonce('reusechain_admin_chat_nonce');
    $ajax_url = admin_url('admin-ajax.php');

    $wrapper_id = 'admin-chat-feed-wrapper-' . wp_generate_uuid4();

    ob_start();
    ?>
    <style>
        /* Minimalni pagination stil */
        .admin-chat-pagination{
            margin-top: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
        }
        .admin-chat-page-btn{
            border: 0;
            border-radius: 10px;
            padding: 10px 12px;
            font-weight: 700;
            cursor: pointer;
            background: #f1f3f5;
        }
        .admin-chat-page-btn[disabled]{
            opacity: .5;
            cursor: not-allowed;
        }
        .admin-chat-page-info{
            font-size: 13px;
            color: #555;
            font-weight: 600;
            white-space: nowrap;
        }
        .admin-chat-page-total{
            font-weight: 600;
            opacity: .75;
            margin-left: 8px;
        }
    </style>

    <div id="<?php echo esc_attr($wrapper_id); ?>"
         class="admin-chat-feed-wrapper"
         data-page="<?php echo esc_attr($page); ?>"
         data-per-page="<?php echo esc_attr($per_page); ?>">
        <?php echo reusechain_admin_chat_feed_render($page, $per_page); ?>
    </div>

    <script>
        (function(){
            const wrapper = document.getElementById('<?php echo esc_js($wrapper_id); ?>');
            if (!wrapper) return;

            const ajaxUrl  = '<?php echo esc_url($ajax_url); ?>';
            const nonce    = '<?php echo esc_js($nonce); ?>';
            const perPage  = parseInt(wrapper.getAttribute('data-per-page') || '10', 10);

            function fetchPage(page){
                page = Math.max(1, parseInt(page || '1', 10));
                wrapper.setAttribute('data-page', String(page));

                const data = new FormData();
                data.append('action', 'reusechain_admin_chat_feed');
                data.append('nonce', nonce);
                data.append('page', String(page));
                data.append('per_page', String(perPage));

                return fetch(ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: data
                })
                .then(res => res.json())
                .then(res => {
                    if (res && res.success && res.data && res.data.html) {
                        wrapper.innerHTML = res.data.html;
                    }
                })
                .catch(() => {});
            }

            // Klik na Prev/Next
            wrapper.addEventListener('click', function(e){
                const btn = e.target.closest('.admin-chat-page-btn');
                if (!btn) return;
                if (btn.disabled) return;

                const page = btn.getAttribute('data-page');
                fetchPage(page);
            });

            // Auto refresh (reload trenutne stranice)
            const refreshSec = <?php echo (int) $refresh; ?>;
            if (refreshSec > 0){
                setInterval(function(){
                    const current = parseInt(wrapper.getAttribute('data-page') || '1', 10);
                    fetchPage(current);
                }, refreshSec * 1000);
            }
        })();
    </script>
    <?php

    return ob_get_clean();
});


//kraj admin poruke
//admin panel scroll
//admin panel scroll
/**
 * =====================================================
 * Fix anchor scrolling on /kompanije/ (Elementor)
 * Works for hashes like #nacekanju, #odobrene, #sparkasse...
 * =====================================================
 */
add_action('wp_footer', function () {

    if (is_admin()) {
        return;
    }

    // only on /kompanije/
    if (!is_page('kompanije')) {
        return;
    }

    ?>
    <script>
    (function () {

        function scrollToHash() {
            var hash = window.location.hash;
            if (!hash || hash.length < 2) return;

            var id = hash.substring(1);
            var el = document.getElementById(id);
            if (!el) return;

            // smooth scroll to the element
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // On load (delay helps Elementor render)
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(scrollToHash, 300);
        });

        // On hash change (if user clicks another menu item)
        window.addEventListener('hashchange', function () {
            setTimeout(scrollToHash, 50);
        });

    })();
    </script>
    <?php
}, 100);

//kraj admin panel scroll



//statistika 3 boxa
//box1
function rc_top_kompanije_po_aktivnosti_shortcode($atts) {

    if (!current_user_can('manage_options')) {
        return '';
    }

    $atts = shortcode_atts([
        'limit' => 5,
    ], $atts);

    $limit = max(1, (int) $atts['limit']);

    // Dohvati sve proizvode koji imaju preglede
    $query = new WP_Query([
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => [
            [
                'key'     => 'broj_pregleda',
                'compare' => 'EXISTS',
            ],
        ],
    ]);

    if (!$query->have_posts()) {
        return '<p>Nema podataka o aktivnosti kompanija.</p>';
    }

    $companies = [];

    while ($query->have_posts()) {
        $query->the_post();

        $author_id = get_post_field('post_author', get_the_ID());
        $views     = (int) get_post_meta(get_the_ID(), 'broj_pregleda', true);

        if ($views <= 0) {
            continue;
        }

        if (!isset($companies[$author_id])) {
            $companies[$author_id] = [
                'name'  => get_the_author_meta('display_name', $author_id),
                'views' => 0,
            ];
        }

        $companies[$author_id]['views'] += $views;
    }

    wp_reset_postdata();

    if (empty($companies)) {
        return '<p>Nema aktivnosti za prikaz.</p>';
    }

    // Sort po pregledima DESC
    uasort($companies, function ($a, $b) {
        return $b['views'] <=> $a['views'];
    });

    $companies = array_slice($companies, 0, $limit, true);

    // Output
    $out  = '<div class="top-kompanije-widget">';
    $out .= '<ul class="top-kompanije-lista">';

    foreach ($companies as $company) {
        $out .= sprintf(
            '<li>
                <strong class="top-kompanija-naziv">%s</strong>
                <span class="top-kompanija-badge">
                    <span class="top-dot"></span>
                    %s pregleda
                </span>
            </li>',
            esc_html($company['name']),
            esc_html(number_format_i18n($company['views']))
        );
    }

    $out .= '</ul></div>';

    return $out;
}

add_shortcode('top_kompanije_aktivnost', 'rc_top_kompanije_po_aktivnosti_shortcode');


//kraj box 1
//box 2
function rc_top_kategorije_po_pregledima_shortcode($atts) {

    if (!current_user_can('manage_options')) {
        return '';
    }

    $atts = shortcode_atts([
        'limit' => 5,
    ], $atts);

    $limit = max(1, (int) $atts['limit']);

    $query = new WP_Query([
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => [
            [
                'key'     => 'broj_pregleda',
                'compare' => 'EXISTS',
            ],
        ],
    ]);

    if (!$query->have_posts()) {
        return '<p>Nema podataka o kategorijama.</p>';
    }

    $categories = [];

    while ($query->have_posts()) {
        $query->the_post();

        $views = (int) get_post_meta(get_the_ID(), 'broj_pregleda', true);
        if ($views <= 0) {
            continue;
        }

        $terms = get_the_terms(get_the_ID(), 'product_cat');
        if (empty($terms) || is_wp_error($terms)) {
            continue;
        }

        foreach ($terms as $term) {
            if (!isset($categories[$term->term_id])) {
                $categories[$term->term_id] = [
                    'name'  => $term->name,
                    'views' => 0,
                ];
            }
            $categories[$term->term_id]['views'] += $views;
        }
    }

    wp_reset_postdata();

    if (empty($categories)) {
        return '<p>Nema aktivnosti po kategorijama.</p>';
    }

    uasort($categories, function ($a, $b) {
        return $b['views'] <=> $a['views'];
    });

    $categories = array_slice($categories, 0, $limit, true);

    $out  = '<div class="top-kategorije-widget">';
    $out .= '<ul class="top-kategorije-lista">';

    foreach ($categories as $cat) {
        $out .= sprintf(
            '<li>
                <strong class="top-kategorija-naziv">%s</strong>
                <span class="top-kategorija-badge">
                    <span class="top-dot"></span>
                    %s pregleda
                </span>
            </li>',
            esc_html($cat['name']),
            esc_html(number_format_i18n($cat['views']))
        );
    }

    $out .= '</ul></div>';

    return $out;
}

add_shortcode('top_kategorije_pregledi', 'rc_top_kategorije_po_pregledima_shortcode');

//kraj box 2
//box 3
function rc_trending_kategorije_shortcode($atts) {

    // Admin only
    if (!current_user_can('manage_options')) {
        return '';
    }

    $atts = shortcode_atts([
        'limit' => 5,
    ], $atts);

    $limit = max(1, (int) $atts['limit']);

    // Zadnjih 7 dana
    $since = date('Y-m-d H:i:s', strtotime('-30 days'));

    $query = new WP_Query([
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'date_query'     => [
            [
                'after' => $since,
            ],
        ],
        'meta_query'     => [
            [
                'key'     => 'broj_pregleda',
                'compare' => 'EXISTS',
            ],
        ],
    ]);

    if (!$query->have_posts()) {
        return '<p>Nema trending kategorija.</p>';
    }

    $categories = [];

    while ($query->have_posts()) {
        $query->the_post();

        $views = (int) get_post_meta(get_the_ID(), 'broj_pregleda', true);
        if ($views <= 0) {
            continue;
        }

        $terms = get_the_terms(get_the_ID(), 'product_cat');
        if (empty($terms) || is_wp_error($terms)) {
            continue;
        }

        // ✅ UZMI SAMO PRVU (primarnu) KATEGORIJU
        $primary_term = reset($terms);

        if (!isset($categories[$primary_term->term_id])) {
            $categories[$primary_term->term_id] = [
                'name'  => $primary_term->name,
                'views' => 0,
            ];
        }

        $categories[$primary_term->term_id]['views'] += $views;
    }

    wp_reset_postdata();

    if (empty($categories)) {
        return '<p>Nema aktivnosti u zadnjih 30 dana.</p>';
    }

    // Sortiraj po pregledima DESC
    uasort($categories, function ($a, $b) {
        return $b['views'] <=> $a['views'];
    });

    $categories = array_slice($categories, 0, $limit, true);

    // HTML OUTPUT
    $out  = '<div class="trending-kategorije-box">';
        $out .= '<ul class="trending-kategorije-lista">';

    foreach ($categories as $cat) {
        $out .= sprintf(
            '<li>
                <strong>%s</strong>
                <span class="trending-badge">%s pregleda (30d)</span>
            </li>',
            esc_html($cat['name']),
            esc_html(number_format_i18n($cat['views']))
        );
    }

    $out .= '</ul></div>';

    return $out;
}

add_shortcode('trending_kategorije', 'rc_trending_kategorije_shortcode');

//kraj box 3
//kraj statistika 3 boxa

//pocetak poruka za korisnike
function bm_chat_security_warning() {
?>
<style>
.bm-chat-warning{
    background:#fff3cd;
    border-bottom:1px solid #f1d98c;
    padding:10px 15px;
    font-size:13px;
    line-height:1.5;
    text-align:center;
}
.bm-chat-warning strong{
    display:block;
    margin-bottom:4px;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function(){

function insertWarning(){

    const column = document.querySelector(".bp-messages-column");
    if(!column) return;

    if(column.querySelector(".bm-chat-warning")) return;

    const warning = document.createElement("div");
    warning.className = "bm-chat-warning";

    warning.innerHTML = `
    <strong>⚠️ Sigurnosno Upozorenje</strong>
    Ne dijelite povjerljive podatke putem chata (npr. podatke sa kartica, lične identifikacione podatke ili druge privatne informacije).<br>
    Komunikacija putem chata odvija se isključivo između korisnika. Vlasnik platforme i povezana lica ne snose odgovornost za bilo kakve dogovore, razmjenu podataka ili posljedice koje mogu proizaći iz komunikacije između korisnika putem chata.
    `;

    column.prepend(warning);
}

insertWarning();

const observer = new MutationObserver(insertWarning);
observer.observe(document.body,{childList:true,subtree:true});

});
</script>
<?php
}
add_action('wp_footer', 'bm_chat_security_warning');
//kraj poruka za korisnike
//pretraga ciscenje
/**
 * ReUseChain: Hide search results listing until ?s is present
 * Usage: Add CSS class "rc-search-results" to the Listing Grid widget (Elementor > Advanced > CSS Classes)
 */
add_action('wp_enqueue_scripts', function () {

    // CSS: hide by default, show only if body has rc-has-search
    $css = '
    .rc-search-results{display:none;}
    body.rc-has-search .rc-search-results{display:block;}
    ';

    // JS: adds class to body if ?s exists and not empty
    $js = "
    (function(){
      try {
        var params = new URLSearchParams(window.location.search);
        var s = (params.get('q') || '').trim();
        if (s.length > 0) {
          document.body.classList.add('rc-has-search');
        }
      } catch(e) {}
    })();
    ";

    // Attach to WP's main frontend handle
    wp_register_style('rc-search-hide', false);
    wp_enqueue_style('rc-search-hide');
    wp_add_inline_style('rc-search-hide', $css);

    wp_register_script('rc-search-hide', '', [], null, true);
    wp_enqueue_script('rc-search-hide');
    wp_add_inline_script('rc-search-hide', $js);
});

//kraj pretraga ciscenje
//provjera po id
/**
 * ReuseChain - Provjera duplog JIB-a na JetFormBuilder registracijskoj formi.
 *
 * Polje u formi: name="jib"
 * Meta key u bazi: jib
 */


/**
 * Normalizuje JIB tako da ostanu samo brojevi.
 */
function rc_normalize_jib_value($value) {
    $value = is_scalar($value) ? (string) $value : '';
    return preg_replace('/\D+/', '', $value);
}


/**
 * Izvlači JIB iz user meta vrijednosti.
 * Pokriva slučaj kada je vrijednost običan tekst,
 * ali i kada je sačuvana kao serijalizovan array.
 */
function rc_extract_jib_from_meta_value($meta_value) {
    $unserialized = maybe_unserialize($meta_value);

    if (is_array($unserialized)) {
        foreach ($unserialized as $item) {
            if (is_scalar($item) && $item !== '') {
                return (string) $item;
            }
        }

        return '';
    }

    if (is_scalar($unserialized)) {
        return (string) $unserialized;
    }

    return '';
}


/**
 * Pronalazi da li već postoji korisnik/kompanija sa istim JIB-om.
 *
 * @param string $jib JIB koji provjeravamo.
 * @return int ID korisnika ako postoji, 0 ako ne postoji.
 */
function rc_find_existing_company_by_jib($jib) {
    global $wpdb;

    $normalized_jib = rc_normalize_jib_value($jib);

    if (empty($normalized_jib)) {
        return 0;
    }

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "
            SELECT user_id, meta_value
            FROM {$wpdb->usermeta}
            WHERE meta_key = %s
            ",
            'jib'
        )
    );

    if (empty($rows)) {
        return 0;
    }

    foreach ($rows as $row) {
        $existing_jib_raw = rc_extract_jib_from_meta_value($row->meta_value);
        $existing_jib     = rc_normalize_jib_value($existing_jib_raw);

        if (!empty($existing_jib) && $existing_jib === $normalized_jib) {
            return (int) $row->user_id;
        }
    }

    return 0;
}


/**
 * AJAX endpoint za provjeru JIB-a.
 */
add_action('wp_ajax_rc_check_company_jib', 'rc_check_company_jib');
add_action('wp_ajax_nopriv_rc_check_company_jib', 'rc_check_company_jib');

function rc_check_company_jib() {
    check_ajax_referer('rc_check_company_jib_nonce', 'nonce');

    $jib = isset($_POST['jib']) ? sanitize_text_field(wp_unslash($_POST['jib'])) : '';
    $jib = rc_normalize_jib_value($jib);

    if (empty($jib)) {
        wp_send_json_error([
            'exists'  => false,
            'message' => 'Unesite JIB.'
        ]);
    }

    if (strlen($jib) < 13) {
        wp_send_json_error([
            'exists'  => false,
            'message' => 'Unesite svih 13 cifara JIB-a.'
        ]);
    }

    $existing_user_id = rc_find_existing_company_by_jib($jib);

    if ($existing_user_id) {
        wp_send_json_success([
            'exists'  => true,
            'message' => 'Kompanija je već registrovana!'
        ]);
    }

    wp_send_json_success([
        'exists'  => false,
        'message' => 'JIB je dostupan.'
    ]);
}


/**
 * Frontend JS za provjeru JIB-a na JetFormBuilder formi.
 *
 * Napomena:
 * JetFormBuilder maskirano polje nekad ne okida input event odmah,
 * zato se dodatno koristi keyup, change, paste i interval dok je polje fokusirano.
 */
add_action('wp_footer', 'rc_company_jib_check_script');

function rc_company_jib_check_script() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const field = document.querySelector('input[name="jib"]');

        if (!field) {
            return;
        }

        const form = field.closest('form');

        if (!form) {
            return;
        }

        let jibExists = false;
        let isChecking = false;
        let lastCheckedValue = '';
        let lastTypedValue = '';
        let timeout = null;
        let focusInterval = null;

        const message = document.createElement('div');
        message.className = 'rc-jib-check-message';
        message.style.marginTop = '6px';
        message.style.fontSize = '14px';
        message.style.lineHeight = '1.4';

        field.insertAdjacentElement('afterend', message);

        const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');

        function normalizeJib(value) {
            return String(value || '').replace(/\D+/g, '');
        }

        function setSubmitDisabled(disabled) {
            submitButtons.forEach(function (button) {
                button.disabled = disabled;
                button.style.opacity = disabled ? '0.6' : '';
                button.style.cursor = disabled ? 'not-allowed' : '';
            });
        }

        function setMessage(text, type) {
            message.textContent = text || '';

            if (type === 'error') {
                message.style.color = '#cc0000';
            } else if (type === 'success') {
                message.style.color = '#16803c';
            } else {
                message.style.color = '#555555';
            }
        }

        function scheduleCheck() {
            clearTimeout(timeout);

            const normalizedValue = normalizeJib(field.value);

            jibExists = false;

            if (!normalizedValue) {
                lastCheckedValue = '';
                setSubmitDisabled(false);
                setMessage('', '');
                return;
            }

            if (normalizedValue.length < 13) {
                lastCheckedValue = '';
                setSubmitDisabled(false);
                setMessage('Unesite svih 13 cifara JIB-a.', 'error');
                return;
            }

            timeout = setTimeout(function () {
                checkJib(field.value);
            }, 400);
        }

        function checkJib(value) {
            const normalizedValue = normalizeJib(value);

            if (!normalizedValue || normalizedValue.length < 13) {
                jibExists = false;
                lastCheckedValue = '';
                setSubmitDisabled(false);
                setMessage('Unesite svih 13 cifara JIB-a.', 'error');
                return;
            }

            if (isChecking || normalizedValue === lastCheckedValue) {
                return;
            }

            isChecking = true;
            setSubmitDisabled(true);
            setMessage('Provjera JIB-a...', 'neutral');

            const formData = new FormData();
            formData.append('action', 'rc_check_company_jib');
            formData.append('nonce', '<?php echo esc_js(wp_create_nonce('rc_check_company_jib_nonce')); ?>');
            formData.append('jib', normalizedValue);

            fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                isChecking = false;
                lastCheckedValue = normalizedValue;

                if (!data || !data.success || !data.data) {
                    jibExists = false;
                    setSubmitDisabled(false);
                    setMessage('Nije moguće provjeriti JIB. Pokušajte ponovo.', 'error');
                    return;
                }

                if (data.data.exists === true) {
                    jibExists = true;
                    setSubmitDisabled(true);
                    setMessage('Kompanija je već registrovana!', 'error');
                } else {
                    jibExists = false;
                    setSubmitDisabled(false);
                    setMessage('JIB je dostupan.', 'success');
                }
            })
            .catch(function () {
                isChecking = false;
                jibExists = false;
                setSubmitDisabled(false);
                setMessage('Greška prilikom provjere JIB-a.', 'error');
            });
        }

        field.addEventListener('input', scheduleCheck);
        field.addEventListener('keyup', scheduleCheck);
        field.addEventListener('change', scheduleCheck);

        field.addEventListener('paste', function () {
            setTimeout(scheduleCheck, 100);
        });

        field.addEventListener('focus', function () {
            focusInterval = setInterval(function () {
                const currentValue = normalizeJib(field.value);

                if (currentValue !== lastTypedValue) {
                    lastTypedValue = currentValue;
                    scheduleCheck();
                }
            }, 300);
        });

        field.addEventListener('blur', function () {
            clearInterval(focusInterval);
            clearTimeout(timeout);
            checkJib(field.value);
        });

        form.addEventListener('submit', function (e) {
            const currentValue = normalizeJib(field.value);

            if (!currentValue || currentValue.length < 13) {
                e.preventDefault();
                e.stopPropagation();

                setMessage('Unesite svih 13 cifara JIB-a.', 'error');
                field.focus();

                return false;
            }

            if (isChecking) {
                e.preventDefault();
                e.stopPropagation();

                setMessage('Sačekajte da se završi provjera JIB-a.', 'error');
                field.focus();

                return false;
            }

            if (jibExists) {
                e.preventDefault();
                e.stopPropagation();

                setMessage('Kompanija je već registrovana!', 'error');
                field.focus();

                return false;
            }

            if (currentValue !== lastCheckedValue) {
                e.preventDefault();
                e.stopPropagation();

                checkJib(currentValue);

                setTimeout(function () {
                    if (!jibExists && !isChecking && normalizeJib(field.value) === lastCheckedValue) {
                        if (form.requestSubmit) {
                            form.requestSubmit();
                        } else {
                            form.submit();
                        }
                    } else if (jibExists) {
                        setMessage('Kompanija je već registrovana!', 'error');
                        field.focus();
                    }
                }, 900);

                return false;
            }
        });
    });
    </script>
    <?php
}
//kraj provjere po id

//waste type
/**
 * Shortcode: [rc_waste_type_stats]
 * Prikazuje statistiku po tipu otpada iz product meta polja waste_type.
 *
 * waste_type values:
 * - hazardous
 * - non-hazardous
 *
 * Količina se sabira iz WooCommerce meta polja _stock.
 */

if (!function_exists('rc_waste_type_stats_shortcode')) {
    function rc_waste_type_stats_shortcode() {
        global $wpdb;

        $types = array(
            'hazardous' => array(
                'label'       => 'Opasni otpad',
                'description' => 'Oglasi označeni kao opasni otpad',
                'icon'        => '⚠️',
            ),
            'non-hazardous' => array(
                'label'       => 'Bezopasni otpad',
                'description' => 'Oglasi označeni kao bezopasni otpad',
                'icon'        => '♻️',
            ),
        );

        $stats = array();

        foreach ($types as $value => $type_data) {
            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT 
                        COUNT(DISTINCT p.ID) AS broj_oglasa,
                        COALESCE(SUM(
                            CASE 
                                WHEN stock_meta.meta_value REGEXP '^[0-9]+(\\.[0-9]+)?$'
                                THEN CAST(stock_meta.meta_value AS DECIMAL(20,2))
                                ELSE 0
                            END
                        ), 0) AS ukupna_kolicina
                    FROM {$wpdb->posts} p
                    INNER JOIN {$wpdb->postmeta} waste_meta
                        ON waste_meta.post_id = p.ID
                        AND waste_meta.meta_key = 'waste_type'
                        AND waste_meta.meta_value = %s
                    LEFT JOIN {$wpdb->postmeta} stock_meta
                        ON stock_meta.post_id = p.ID
                        AND stock_meta.meta_key = '_stock'
                    WHERE p.post_type = 'product'
                      AND p.post_status = 'publish'
                    ",
                    $value
                )
            );

            $stats[$value] = array(
                'label'           => $type_data['label'],
                'description'     => $type_data['description'],
                'icon'            => $type_data['icon'],
                'broj_oglasa'     => isset($row->broj_oglasa) ? (int) $row->broj_oglasa : 0,
                'ukupna_kolicina' => isset($row->ukupna_kolicina) ? (float) $row->ukupna_kolicina : 0,
            );
        }

        $total_ads = 0;
        $total_quantity = 0;

        foreach ($stats as $item) {
            $total_ads += $item['broj_oglasa'];
            $total_quantity += $item['ukupna_kolicina'];
        }

        ob_start();
        ?>

        <div class="rc-waste-type-panel">

            <div class="rc-waste-type-header">
                <div>
                    <h3>GRI 306 - Raspored oglasa po vrsti otpada</h3>
                    <p>Pregled broja oglasa i ukupne količine prema oznaci opasnosti otpada.</p>
                </div>

                <div class="rc-waste-type-summary">
                    <span>Ukupno oglasa</span>
                    <strong><?php echo esc_html(number_format_i18n($total_ads)); ?></strong>
                </div>
            </div>

            <div class="rc-waste-type-grid">
                <?php foreach ($stats as $item) : ?>
                    <div class="rc-waste-type-card">
                        <div class="rc-waste-type-card-top">
                            <div class="rc-waste-type-icon">
                                <?php echo esc_html($item['icon']); ?>
                            </div>

                            <div>
                                <h4><?php echo esc_html($item['label']); ?></h4>
                                <p><?php echo esc_html($item['description']); ?></p>
                            </div>
                        </div>

                        <div class="rc-waste-type-numbers">

                            <div class="rc-waste-type-number-box">
                                <span>Broj oglasa</span>
                                <strong><?php echo esc_html(number_format_i18n($item['broj_oglasa'])); ?></strong>
                            </div>

                            <div class="rc-waste-type-number-box">
                                <span>Ukupna količina</span>
                                <strong>
                                    <?php echo esc_html(rc_waste_type_format_quantity($item['ukupna_kolicina'])); ?> kg
                                </strong>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>

        <style>
            .rc-waste-type-panel {
                margin: 24px 0;
                padding: 0;
                font-family: inherit;
            }

            .rc-waste-type-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 20px;
                background: linear-gradient(135deg, #eaf7ec 0%, #f7fbf8 100%);
                border: 1px solid #cfe6d2;
                border-radius: 16px;
                padding: 20px 24px;
                margin-bottom: 24px;
                box-shadow: 0 8px 22px rgba(23, 107, 44, 0.07);
            }

            .rc-waste-type-header h3 {
                margin: 0 0 5px;
                color: #176b2c;
                font-size: 21px;
                font-weight: 800;
                line-height: 1.25;
            }

            .rc-waste-type-header p {
                margin: 0;
                color: #5f6f64;
                font-size: 14px;
                line-height: 1.45;
            }

            .rc-waste-type-summary {
                min-width: 155px;
                background: #ffffff;
                border: 1px solid #dcebdd;
                border-radius: 14px;
                padding: 13px 17px;
                text-align: center;
                box-shadow: 0 6px 16px rgba(0, 0, 0, 0.05);
            }

            .rc-waste-type-summary span {
                display: block;
                color: #6b7280;
                font-size: 13px;
                font-weight: 500;
                margin-bottom: 4px;
            }

            .rc-waste-type-summary strong {
                display: block;
                color: #176b2c;
                font-size: 26px;
                font-weight: 850;
                line-height: 1.1;
            }

            .rc-waste-type-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 22px;
            }

            .rc-waste-type-card {
                position: relative;
                overflow: hidden;
                background: #ffffff;
                border: 1px solid #e4ece5;
                border-radius: 16px;
                padding: 24px;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
                transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
            }

            .rc-waste-type-card::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 5px;
                background: linear-gradient(90deg, #176b2c, #6fcf7f);
            }

            .rc-waste-type-card:hover {
                transform: translateY(-2px);
                border-color: #c8e3c8;
                box-shadow: 0 12px 30px rgba(23, 107, 44, 0.11);
            }

            .rc-waste-type-card-top {
                display: flex;
                align-items: flex-start;
                gap: 15px;
                margin-bottom: 22px;
            }

            .rc-waste-type-icon {
                width: 52px;
                height: 52px;
                min-width: 52px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #f0f8f1;
                border: 1px solid #d7ead9;
                border-radius: 15px;
                font-size: 27px;
                line-height: 1;
            }

            .rc-waste-type-card h4 {
                margin: 0 0 6px;
                color: #1f2937;
                font-size: 20px;
                font-weight: 800;
                line-height: 1.25;
            }

            .rc-waste-type-card p {
                margin: 0;
                color: #6b7280;
                font-size: 14px;
                line-height: 1.45;
            }

            .rc-waste-type-numbers {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 14px;
            }

            .rc-waste-type-number-box {
                background: #f8faf8;
                border: 1px solid #edf1ed;
                border-radius: 13px;
                padding: 15px 16px;
            }

            .rc-waste-type-number-box span {
                display: block;
                color: #4b5563;
                font-size: 14px;
                font-weight: 600;
                margin-bottom: 8px;
            }

            .rc-waste-type-number-box strong {
                display: block;
                color: #176b2c;
                font-size: 24px;
                font-weight: 850;
                line-height: 1.2;
                white-space: nowrap;
            }

            @media (max-width: 900px) {
                .rc-waste-type-header {
                    flex-direction: column;
                    align-items: stretch;
                }

                .rc-waste-type-summary {
                    text-align: left;
                }

                .rc-waste-type-grid {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 520px) {
                .rc-waste-type-header {
                    padding: 17px;
                    border-radius: 14px;
                }

                .rc-waste-type-card {
                    padding: 21px;
                }

                .rc-waste-type-card-top {
                    flex-direction: column;
                }

                .rc-waste-type-numbers {
                    grid-template-columns: 1fr;
                }

                .rc-waste-type-number-box strong {
                    font-size: 22px;
                }
            }
        </style>

        <?php
        return ob_get_clean();
    }

    add_shortcode('rc_waste_type_stats', 'rc_waste_type_stats_shortcode');
}


if (!function_exists('rc_waste_type_format_quantity')) {
    function rc_waste_type_format_quantity($quantity) {
        $quantity = (float) $quantity;

        if (floor($quantity) == $quantity) {
            return number_format_i18n($quantity, 0);
        }

        return number_format_i18n($quantity, 2);
    }
}
//kraj waste type
//esg tips
/**
 * ReUseChain – ESG / GRI 306 Tips
 *
 * Shortcode:
 * [reusechain_esg_tips]
 */

add_shortcode('reusechain_esg_tips', function () {

    $tips = [

        [
            'icon'  => '♻️',
            'title' => 'GRI 306-4',
            'text'  => 'GRI 306-4 prati otpad koji je preusmjeren od odlaganja kroz ponovnu upotrebu, reciklažu i druge oblike upotrebe.'
        ],

        [
            'icon'  => '🌱',
            'title' => 'ESG',
            'text'  => 'ESG obuhvata okolišne, društvene i upravljačke aspekte poslovanja.'
        ],

        [
            'icon'  => '🔄',
            'title' => 'Ponovna upotreba',
            'text'  => 'Ponovna upotreba produžava životni vijek proizvoda i smanjuje potrebu za novim sirovinama.'
        ],

        [
            'icon'  => '🗑️',
            'title' => 'Prevencija otpada',
            'text'  => 'Najefikasniji način upravljanja otpadom je spriječiti njegov nastanak prije nego što postane otpad.'
        ],

        [
            'icon'  => '📊',
            'title' => 'GRI 306',
            'text'  => 'GRI 306 razlikuje opasni i neopasni otpad, zato je važno pravilno evidentirati vrstu i količinu otpada.'
        ],

        [
            'icon'  => '🌍',
            'title' => 'Cirkularna ekonomija',
            'text'  => 'Cilj cirkularne ekonomije je zadržati proizvode i materijale u upotrebi što je duže moguće.'
        ],

        [
            'icon'  => '📦',
            'title' => 'Otpad kao resurs',
            'text'  => 'Materijal koji jednoj kompaniji više nije potreban može postati vrijedan resurs za drugu kompaniju.'
        ],

        [
            'icon'  => '📈',
            'title' => 'Mjerenje',
            'text'  => 'Redovno praćenje količine otpada omogućava kompanijama da mjere napredak i postavljaju ciljeve smanjenja.'
        ],

        [
            'icon'  => '🔍',
            'title' => 'Transparentnost',
            'text'  => 'Dokumentovani tokovi otpada povećavaju vjerodostojnost ESG izvještavanja i olakšavaju verifikaciju podataka.'
        ],

        [
            'icon'  => '🔧',
            'title' => 'Produženje vijeka',
            'text'  => 'Popravka proizvoda može spriječiti nastanak otpada i produžiti vrijeme tokom kojeg proizvod ostaje u upotrebi.'
        ],

        [
            'icon'  => '♻️',
            'title' => 'Reciklaža',
            'text'  => 'Reciklaža je važna, ali ponovna upotreba i prevencija nastanka otpada često imaju još veći cirkularni efekat.'
        ],

        [
            'icon'  => '🏭',
            'title' => 'Industrijska simbioza',
            'text'  => 'Industrijska simbioza povezuje kompanije tako da nusproizvod ili otpad jedne postane resurs druge.'
        ],

        [
            'icon'  => '⚖️',
            'title' => 'GRI izvještavanje',
            'text'  => 'Precizne količine otpada predstavljaju osnovu za kvalitetno i provjerljivo GRI 306 izvještavanje.'
        ],

        [
            'icon'  => '🌿',
            'title' => 'Manje primarnih resursa',
            'text'  => 'Ponovna upotreba postojećih materijala može smanjiti potrebu za vađenjem i proizvodnjom novih sirovina.'
        ],

        [
            'icon'  => '💡',
            'title' => 'ESG savjet',
            'text'  => 'Mali koraci poput boljeg razdvajanja, praćenja i ponovne upotrebe otpada mogu imati mjerljiv ESG efekat.'
        ],

        [
            'icon'  => '🔁',
            'title' => 'Zatvaranje kruga',
            'text'  => 'Cirkularni sistem nastoji vratiti materijale u novi proizvodni ciklus umjesto da ih trajno odloži.'
        ],

        [
            'icon'  => '📋',
            'title' => 'Evidencija',
            'text'  => 'Dobra evidencija treba pokazati šta je nastalo kao otpad, koliko ga je bilo i šta se s njim dalje dogodilo.'
        ],

        [
            'icon'  => '🌱',
            'title' => 'Održivo poslovanje',
            'text'  => 'Smanjenje otpada može istovremeno smanjiti okolišni uticaj i troškove nabavke novih materijala.'
        ],

    ];

    shuffle($tips);

    $id = 'rc-esg-' . wp_rand(10000, 999999);

    ob_start();
    ?>

    <!-- =========================================
         MALA ESG KARTICA
    ========================================== -->

    <div
        id="<?php echo esc_attr($id); ?>"
        class="rc-esg-wrapper"
        data-tips="<?php echo esc_attr(wp_json_encode($tips)); ?>"
    >

        <div
            class="rc-esg-card"
            role="button"
            tabindex="0"
            aria-label="Otvori ESG savjete"
        >

            <div class="rc-esg-label">
                <span>💡</span>
                <span>ESG TIP</span>
            </div>

            <div class="rc-esg-content">

                <div class="rc-esg-heading">

                    <span class="rc-esg-icon">
                        <?php echo esc_html($tips[0]['icon']); ?>
                    </span>

                    <span class="rc-esg-title">
                        <?php echo esc_html($tips[0]['title']); ?>
                    </span>

                </div>

                <div class="rc-esg-text">
                    <?php echo esc_html($tips[0]['text']); ?>
                </div>

            </div>

            <div class="rc-esg-open-hint">
                Klikni za više
                <span>↗</span>
            </div>

            <div class="rc-esg-progress">
                <span></span>
            </div>

        </div>


        <!-- =========================================
             POPUP / PREZENTACIJA
        ========================================== -->

        <div
            class="rc-esg-modal"
            aria-hidden="true"
        >

            <div class="rc-esg-modal-backdrop"></div>

            <div
                class="rc-esg-modal-card"
                role="dialog"
                aria-modal="true"
                aria-label="ESG savjeti"
            >

                <button
                    type="button"
                    class="rc-esg-close"
                    aria-label="Zatvori"
                >
                    ×
                </button>


                <div class="rc-esg-modal-top">

                    <div class="rc-esg-modal-label">
                        <span>💡</span>
                        <span>ESG & GRI SAVJETI</span>
                    </div>

                    <div class="rc-esg-counter">
                        <span class="rc-esg-current">1</span>
                        <span class="rc-esg-counter-divider">/</span>
                        <span class="rc-esg-total">
                            <?php echo count($tips); ?>
                        </span>
                    </div>

                </div>


                <div class="rc-esg-modal-content">

                    <div class="rc-esg-modal-icon">
                        <?php echo esc_html($tips[0]['icon']); ?>
                    </div>

                    <h3 class="rc-esg-modal-title">
                        <?php echo esc_html($tips[0]['title']); ?>
                    </h3>

                    <div class="rc-esg-modal-text">
                        <?php echo esc_html($tips[0]['text']); ?>
                    </div>

                </div>


                <div class="rc-esg-modal-footer">

                    <button
                        type="button"
                        class="rc-esg-nav rc-esg-prev"
                        aria-label="Prethodni savjet"
                    >
                        ←
                    </button>

                    <div class="rc-esg-modal-progress">
                        <span></span>
                    </div>

                    <button
                        type="button"
                        class="rc-esg-nav rc-esg-next"
                        aria-label="Sljedeći savjet"
                    >
                        →
                    </button>

                </div>

            </div>

        </div>

    </div>


    <style>

        /* =====================================================
           WRAPPER
        ====================================================== */

        #<?php echo esc_attr($id); ?> {
            width: 100%;
            margin: 0;
            font-family: inherit;
        }


        /* =====================================================
           MALA KARTICA
        ====================================================== */

        #<?php echo esc_attr($id); ?> .rc-esg-card {
            position: relative;

            width: 100%;
            box-sizing: border-box;

            margin: 0;
            padding: 18px 18px 17px;

            background: #f7fbf8;

            border: 1px solid #d7e9dc;
            border-radius: 7px;

            color: #222;

            overflow: hidden;

            cursor: pointer;

            transition:
                box-shadow .2s ease,
                border-color .2s ease,
                transform .2s ease;
        }


        #<?php echo esc_attr($id); ?> .rc-esg-card:hover {
            border-color: #b8dbc0;

            box-shadow:
                0 5px 18px rgba(31, 91, 48, .08);

            transform: translateY(-1px);
        }


        /* LABEL */

        #<?php echo esc_attr($id); ?> .rc-esg-label {
            display: flex;
            align-items: center;
            gap: 7px;

            margin-bottom: 13px;

            color: #3f8051;

            font-size: 11px;
            line-height: 1;

            font-weight: 700;

            letter-spacing: .055em;
        }


        /* FADE */

        #<?php echo esc_attr($id); ?> .rc-esg-content {
            opacity: 1;

            transform: translateY(0);

            transition:
                opacity .4s ease,
                transform .4s ease;
        }


        #<?php echo esc_attr($id); ?> .rc-esg-content.is-hiding {
            opacity: 0;

            transform: translateY(3px);
        }


        /* NASLOV */

        #<?php echo esc_attr($id); ?> .rc-esg-heading {
            display: flex;
            align-items: center;
            gap: 9px;

            margin-bottom: 8px;
        }


        #<?php echo esc_attr($id); ?> .rc-esg-icon {
            font-size: 20px;
            line-height: 1;
        }


        #<?php echo esc_attr($id); ?> .rc-esg-title {
            color: #161916;

            font-size: 20px;
            line-height: 1.2;

            font-weight: 700;

            letter-spacing: -.2px;
        }


        #<?php echo esc_attr($id); ?> .rc-esg-text {
            color: #4c554e;

            font-size: 14px;
            line-height: 1.5;
        }


        /* KLIK HINT */

        #<?php echo esc_attr($id); ?> .rc-esg-open-hint {
            display: flex;
            align-items: center;
            gap: 5px;

            margin-top: 12px;

            color: #61836a;

            font-size: 11px;
            line-height: 1;

            font-weight: 600;

            opacity: .7;
        }


        /* PROGRESS */

        #<?php echo esc_attr($id); ?> .rc-esg-progress {
            position: absolute;

            left: 0;
            right: 0;
            bottom: 0;

            height: 2px;

            background: rgba(64, 132, 78, .05);
        }


        #<?php echo esc_attr($id); ?> .rc-esg-progress span {
            display: block;

            width: 0;
            height: 100%;

            background: rgba(64, 132, 78, .4);
        }


        #<?php echo esc_attr($id); ?> .rc-esg-progress span.running {
            animation:
                rc-small-progress-<?php echo esc_attr($id); ?>
                7s linear forwards;
        }


        @keyframes rc-small-progress-<?php echo esc_attr($id); ?> {

            from {
                width: 0%;
            }

            to {
                width: 100%;
            }

        }


        /* =====================================================
           MODAL
        ====================================================== */

        #<?php echo esc_attr($id); ?> .rc-esg-modal {
            position: fixed;

            inset: 0;

            z-index: 999999;

            display: flex;

            align-items: center;
            justify-content: center;

            padding: 30px;

            box-sizing: border-box;

            visibility: hidden;

            opacity: 0;

            pointer-events: none;

            transition:
                opacity .3s ease,
                visibility .3s ease;
        }


        #<?php echo esc_attr($id); ?> .rc-esg-modal.is-open {
            visibility: visible;

            opacity: 1;

            pointer-events: auto;
        }


        /* ZATAMNJENA POZADINA */

        #<?php echo esc_attr($id); ?> .rc-esg-modal-backdrop {
            position: absolute;

            inset: 0;

            background:
                rgba(10, 17, 12, .76);

            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }


        /* =====================================================
           VELIKA KARTICA
        ====================================================== */

        #<?php echo esc_attr($id); ?> .rc-esg-modal-card {
            position: relative;

            z-index: 2;

            box-sizing: border-box;

            width: 40vw;

            min-width: 520px;
            max-width: 720px;

            min-height: 390px;

            padding: 36px;

            background:
                linear-gradient(
                    145deg,
                    #ffffff 0%,
                    #f4faf6 100%
                );

            border:
                1px solid rgba(151, 203, 163, .65);

            border-radius: 18px;

            box-shadow:
                0 30px 80px rgba(0, 0, 0, .28);

            transform:
                translateY(15px)
                scale(.97);

            transition:
                transform .32s ease;

            overflow: hidden;
        }


        #<?php echo esc_attr($id); ?> .rc-esg-modal.is-open .rc-esg-modal-card {
            transform:
                translateY(0)
                scale(1);
        }


        /* SUPTILNA POZADINA */

        #<?php echo esc_attr($id); ?> .rc-esg-modal-card::before {
            content: "";

            position: absolute;

            width: 330px;
            height: 330px;

            right: -120px;
            top: -120px;

            border-radius: 50%;

            background:
                rgba(81, 160, 101, .06);

            pointer-events: none;
        }


        /* CLOSE */

        #<?php echo esc_attr($id); ?> .rc-esg-close {
            position: absolute;

            top: 18px;
            right: 20px;

            display: flex;

            align-items: center;
            justify-content: center;

            width: 38px;
            height: 38px;

            padding: 0;

            border: 0;

            border-radius: 50%;

            background: rgba(34, 70, 44, .07);

            color: #35533d;

            font-size: 26px;
            line-height: 1;

            cursor: pointer;

            transition:
                background .2s ease,
                transform .2s ease;
        }


        #<?php echo esc_attr($id); ?> .rc-esg-close:hover {
            background: rgba(34, 70, 44, .12);

            transform: scale(1.05);
        }


        /* =====================================================
           MODAL HEADER
        ====================================================== */

        #<?php echo esc_attr($id); ?> .rc-esg-modal-top {
            display: flex;

            align-items: center;
            justify-content: space-between;

            padding-right: 55px;

            margin-bottom: 50px;
        }


        #<?php echo esc_attr($id); ?> .rc-esg-modal-label {
            display: flex;

            align-items: center;

            gap: 8px;

            color: #438253;

            font-size: 12px;
            line-height: 1;

            font-weight: 700;

            letter-spacing: .08em;
        }


        #<?php echo esc_attr($id); ?> .rc-esg-counter {
            color: #849188;

            font-size: 13px;

            font-weight: 600;
        }


        #<?php echo esc_attr($id); ?> .rc-esg-counter-divider {
            margin: 0 4px;

            opacity: .45;
        }


        /* =====================================================
           MODAL SADRŽAJ
        ====================================================== */

        #<?php echo esc_attr($id); ?> .rc-esg-modal-content {
            position: relative;

            z-index: 2;

            opacity: 1;

            transform: translateY(0);

            transition:
                opacity .35s ease,
                transform .35s ease;
        }


        #<?php echo esc_attr($id); ?> .rc-esg-modal-content.is-hiding {
            opacity: 0;

            transform: translateY(6px);
        }


        #<?php echo esc_attr($id); ?> .rc-esg-modal-icon {
            margin-bottom: 18px;

            font-size: 38px;
            line-height: 1;
        }


        #<?php echo esc_attr($id); ?> .rc-esg-modal-title {
            margin:
                0
                0
                17px
                0;

            padding: 0;

            color: #172019;

            font-family: inherit;

            font-size: 30px;
            line-height: 1.18;

            font-weight: 700;

            letter-spacing: -.5px;
        }


        #<?php echo esc_attr($id); ?> .rc-esg-modal-text {
            max-width: 95%;

            color: #4d5950;

            font-size: 17px;
            line-height: 1.65;

            font-weight: 400;
        }


        /* =====================================================
           MODAL FOOTER
        ====================================================== */

        #<?php echo esc_attr($id); ?> .rc-esg-modal-footer {
            display: grid;

            grid-template-columns:
                44px
                1fr
                44px;

            align-items: center;

            gap: 16px;

            margin-top: 52px;
        }


        #<?php echo esc_attr($id); ?> .rc-esg-nav {
            display: flex;

            align-items: center;
            justify-content: center;

            width: 44px;
            height: 44px;

            padding: 0;

            border:
                1px solid #cfe2d4;

            border-radius: 50%;

            background: #fff;

            color: #39724a;

            font-size: 20px;

            cursor: pointer;

            transition:
                background .2s ease,
                border-color .2s ease,
                transform .2s ease;
        }


        #<?php echo esc_attr($id); ?> .rc-esg-nav:hover {
            background: #eef7f0;

            border-color: #a8ceb1;

            transform: scale(1.04);
        }


        /* VELIKI PROGRESS */

        #<?php echo esc_attr($id); ?> .rc-esg-modal-progress {
            position: relative;

            width: 100%;
            height: 3px;

            border-radius: 10px;

            background: #dce9df;

            overflow: hidden;
        }


        #<?php echo esc_attr($id); ?> .rc-esg-modal-progress span {
            display: block;

            width: 0;
            height: 100%;

            border-radius: inherit;

            background: #5b9c6b;
        }


        #<?php echo esc_attr($id); ?> .rc-esg-modal-progress span.running {
            animation:
                rc-modal-progress-<?php echo esc_attr($id); ?>
                7s linear forwards;
        }


        @keyframes rc-modal-progress-<?php echo esc_attr($id); ?> {

            from {
                width: 0%;
            }

            to {
                width: 100%;
            }

        }


        /* =====================================================
           TABLET
        ====================================================== */

        @media (max-width: 1100px) {

            #<?php echo esc_attr($id); ?> .rc-esg-modal-card {
                width: 60vw;

                min-width: 0;
            }

        }


        /* =====================================================
           MOBILE
        ====================================================== */

        @media (max-width: 767px) {

            #<?php echo esc_attr($id); ?> .rc-esg-modal {
                padding: 16px;
            }


            #<?php echo esc_attr($id); ?> .rc-esg-modal-card {
                width: 100%;

                min-width: 0;
                max-width: none;

                min-height: 0;

                padding:
                    28px
                    24px
                    24px;

                border-radius: 15px;
            }


            #<?php echo esc_attr($id); ?> .rc-esg-modal-top {
                margin-bottom: 35px;
            }


            #<?php echo esc_attr($id); ?> .rc-esg-modal-title {
                font-size: 25px;
            }


            #<?php echo esc_attr($id); ?> .rc-esg-modal-text {
                max-width: 100%;

                font-size: 15px;
                line-height: 1.6;
            }


            #<?php echo esc_attr($id); ?> .rc-esg-modal-icon {
                font-size: 32px;
            }


            #<?php echo esc_attr($id); ?> .rc-esg-modal-footer {
                margin-top: 35px;
            }

        }

    </style>


    <script>

        (function () {

            const wrapper =
                document.getElementById(
                    '<?php echo esc_js($id); ?>'
                );


            if (
                !wrapper ||
                wrapper.dataset.initialized === '1'
            ) {
                return;
            }


            wrapper.dataset.initialized = '1';


            let tips = [];


            try {

                tips = JSON.parse(
                    wrapper.dataset.tips || '[]'
                );

            } catch (e) {

                return;

            }


            if (!tips.length) {
                return;
            }


            /* =============================
               ELEMENTI
            ============================== */

            const smallCard =
                wrapper.querySelector(
                    '.rc-esg-card'
                );


            const smallContent =
                wrapper.querySelector(
                    '.rc-esg-content'
                );


            const smallIcon =
                wrapper.querySelector(
                    '.rc-esg-icon'
                );


            const smallTitle =
                wrapper.querySelector(
                    '.rc-esg-title'
                );


            const smallText =
                wrapper.querySelector(
                    '.rc-esg-text'
                );


            const smallProgress =
                wrapper.querySelector(
                    '.rc-esg-progress span'
                );


            const modal =
                wrapper.querySelector(
                    '.rc-esg-modal'
                );


            const backdrop =
                wrapper.querySelector(
                    '.rc-esg-modal-backdrop'
                );


            const closeBtn =
                wrapper.querySelector(
                    '.rc-esg-close'
                );


            const modalContent =
                wrapper.querySelector(
                    '.rc-esg-modal-content'
                );


            const modalIcon =
                wrapper.querySelector(
                    '.rc-esg-modal-icon'
                );


            const modalTitle =
                wrapper.querySelector(
                    '.rc-esg-modal-title'
                );


            const modalText =
                wrapper.querySelector(
                    '.rc-esg-modal-text'
                );


            const currentNumber =
                wrapper.querySelector(
                    '.rc-esg-current'
                );


            const modalProgress =
                wrapper.querySelector(
                    '.rc-esg-modal-progress span'
                );


            const prevBtn =
                wrapper.querySelector(
                    '.rc-esg-prev'
                );


            const nextBtn =
                wrapper.querySelector(
                    '.rc-esg-next'
                );


            let current = 0;

            let smallTimer = null;

            let modalTimer = null;


            /* =============================
               PROGRESS
            ============================== */

            function restartProgress(element) {

                if (!element) {
                    return;
                }


                element.classList.remove(
                    'running'
                );


                void element.offsetWidth;


                element.classList.add(
                    'running'
                );

            }


            /* =============================
               MALA KARTICA
            ============================== */

            function updateSmall() {

                const tip = tips[current];


                smallContent.classList.add(
                    'is-hiding'
                );


                setTimeout(function () {

                    smallIcon.textContent =
                        tip.icon || '💡';


                    smallTitle.textContent =
                        tip.title || 'ESG savjet';


                    smallText.textContent =
                        tip.text || '';


                    smallContent.classList.remove(
                        'is-hiding'
                    );


                    restartProgress(
                        smallProgress
                    );

                }, 350);

            }


            function nextSmall() {

                current =
                    (current + 1)
                    % tips.length;


                updateSmall();

            }


            function startSmallTimer() {

                clearInterval(
                    smallTimer
                );


                restartProgress(
                    smallProgress
                );


                smallTimer =
                    setInterval(
                        nextSmall,
                        7000
                    );

            }


            /* =============================
               MODAL
            ============================== */

            function renderModal(animate = true) {

                const tip =
                    tips[current];


                const change = function () {

                    modalIcon.textContent =
                        tip.icon || '💡';


                    modalTitle.textContent =
                        tip.title || 'ESG savjet';


                    modalText.textContent =
                        tip.text || '';


                    currentNumber.textContent =
                        current + 1;


                    modalContent.classList.remove(
                        'is-hiding'
                    );


                    restartProgress(
                        modalProgress
                    );

                };


                if (!animate) {

                    change();

                    return;

                }


                modalContent.classList.add(
                    'is-hiding'
                );


                setTimeout(
                    change,
                    300
                );

            }


            function startModalTimer() {

                clearInterval(
                    modalTimer
                );


                restartProgress(
                    modalProgress
                );


                modalTimer =
                    setInterval(
                        function () {

                            current =
                                (current + 1)
                                % tips.length;


                            renderModal();

                        },
                        7000
                    );

            }


            function openModal() {

                clearInterval(
                    smallTimer
                );


                renderModal(false);


                modal.classList.add(
                    'is-open'
                );


                modal.setAttribute(
                    'aria-hidden',
                    'false'
                );


                document.body.style.overflow =
                    'hidden';


                startModalTimer();


                setTimeout(
                    function () {

                        closeBtn.focus();

                    },
                    100
                );

            }


            function closeModal() {

                clearInterval(
                    modalTimer
                );


                modal.classList.remove(
                    'is-open'
                );


                modal.setAttribute(
                    'aria-hidden',
                    'true'
                );


                document.body.style.overflow =
                    '';


                updateSmall();


                startSmallTimer();


                smallCard.focus();

            }


            function nextModal() {

                current =
                    (current + 1)
                    % tips.length;


                renderModal();


                startModalTimer();

            }


            function prevModal() {

                current--;

                if (current < 0) {

                    current =
                        tips.length - 1;

                }


                renderModal();


                startModalTimer();

            }


            /* =============================
               EVENTS
            ============================== */

            smallCard.addEventListener(
                'click',
                openModal
            );


            smallCard.addEventListener(
                'keydown',
                function (event) {

                    if (
                        event.key === 'Enter' ||
                        event.key === ' '
                    ) {

                        event.preventDefault();

                        openModal();

                    }

                }
            );


            closeBtn.addEventListener(
                'click',
                closeModal
            );


            backdrop.addEventListener(
                'click',
                closeModal
            );


            nextBtn.addEventListener(
                'click',
                nextModal
            );


            prevBtn.addEventListener(
                'click',
                prevModal
            );


            document.addEventListener(
                'keydown',
                function (event) {

                    if (
                        !modal.classList.contains(
                            'is-open'
                        )
                    ) {
                        return;
                    }


                    if (event.key === 'Escape') {

                        closeModal();

                    }


                    if (event.key === 'ArrowRight') {

                        nextModal();

                    }


                    if (event.key === 'ArrowLeft') {

                        prevModal();

                    }

                }
            );


            /* START */

            startSmallTimer();

        })();

    </script>

    <?php

    return ob_get_clean();

});
//kraj esg tips

add_filter('woocommerce_add_to_cart_redirect', function($url) {
    return wc_get_checkout_url();
});

add_filter('woocommerce_available_payment_gateways', function($gateways) {
    // Exit early if we're not on the checkout page
    if (!is_checkout()) {
        return $gateways;
    }

    $has_paketi = false;

    // Loop through cart items
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];
        
        // Check if product is in 'paketi' category
        if (has_term('paketi', 'product_cat', $product->get_id())) {
            $has_paketi = true;
            break;
        }
    }

    // If there's NO 'paketi' product in the cart, remove Monri
    if (!$has_paketi) {
        unset($gateways['monri']); // Replace 'monri' with the correct gateway ID if different
    }

    return $gateways;
});




add_filter('woocommerce_checkout_fields', 'remove_billing_last_name_field');
function remove_billing_last_name_field($fields) {
    // Remove billing last name
    unset($fields['billing']['billing_last_name']);

    // Optional: also remove shipping last name if you're using shipping fields
    unset($fields['shipping']['shipping_last_name']);

    return $fields;
}

add_action('wp_footer', 'bm_force_append_profile_links');
function bm_force_append_profile_links() {
    if (!is_user_logged_in()) return;

    ?>
    <script>
    jQuery(document).ready(function($) {
        const userMap = <?php
            $map = [];
            foreach (get_users(['fields' => ['ID']]) as $user) {
                $naziv = trim(get_user_meta($user->ID, 'naziv', true));
                if (!$naziv) continue;
                $map[$naziv] = $user->ID;
            }
            echo json_encode($map, JSON_UNESCAPED_UNICODE);
        ?>;

        function sanitize(text) {
            return text.trim().replace(/\s+/g, ' ');
        }

        function appendLinksToBMUsers() {
            $('.bm-user').each(function () {
                const $userSpan = $(this);
                const userName = sanitize($userSpan.text());

                if ($userSpan.data('profile-linked')) return;

                const userId = userMap[userName];
                if (!userId) return;

                const $wrapper = $('<span>', {
                    class: 'reusechain-profile-wrapper',
                    html: '&nbsp;<a href="/korisnik/' + userId + '/info/" target="_blank" class="reusechain-profile-link" style="color:black!important;">Pogledaj profil&nbsp;&nbsp;</a>'
                });

                $userSpan.after($wrapper);
                $userSpan.data('profile-linked', true);
            });
        }

        // Initial run
        appendLinksToBMUsers();

        // Re-run on DOM updates
        const observer = new MutationObserver(() => {
            appendLinksToBMUsers();
        });
        observer.observe(document.body, { childList: true, subtree: true });

        // Add CSS dynamically
        const styleTag = document.createElement('style');
        styleTag.innerHTML = `
            .reusechain-profile-link {
                color: black !important;
                text-decoration: underline;
                font-size: 90%;
            }
            .reusechain-profile-wrapper {
                margin-left: 8px;
            }
        `;
        document.head.appendChild(styleTag);
    });
    </script>
    <?php
}

