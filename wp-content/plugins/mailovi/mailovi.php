<?php
/**
 * Plugin Name: Mailovi
 * Description: Ovdje su mailovi i notifikacije
 * Version: 1.0
 * Author: Azra Kadrić
 */

 // TODO: Postavljanje max udaljenosti obavještenja na profilu korisnika
 // TODO: Zamijeniti user role slug "donator" na "kompanija"
 
/* add_action('jet-form-builder/custom-action/send-email-to-korisnik', 'handle_add_product_form_action', 10, 2);

function handle_add_product_form_action($form_data, $form) {
    $allowed_form_ids = [12360];

    $form_id = isset($form_data['__form_id']) ? (int)$form_data['__form_id'] : 0;
    if (!in_array($form_id, $allowed_form_ids, true)) {
        return;
    }

    $post_id = $form_data['post_id'] ?? 0;
    if ($post_id) {
        $author_id = isset($form_data['current_user_id']) ? (int)$form_data['current_user_id'] : 0;
        if ($author_id > 0) {
            process_oglas($author_id, $form_id);
        }
    }
}

function process_oglas($author_id, $form_id) {
    $latest_oglas = get_latest_oglas_by_author($author_id);

    if (!empty($latest_oglas)) {
        $latest_oglas_id = $latest_oglas[0]->ID;
        $oglas_link = get_permalink($latest_oglas_id);

        $custom_fields = get_custom_fields($latest_oglas_id);
        
        $naziv = get_user_meta($author_id, 'naziv', true);
        $headers = get_email_headers();

        $styled_dostava = generate_delivery_block($custom_fields);
        $subject = determine_email_subject($form_id, $custom_fields['direktna_prodaja']);

        if ($custom_fields['direktna_prodaja'] == 1) {
            handle_direct_oglas($latest_oglas_id, $naziv, $oglas_link, $styled_dostava, $headers, $subject);
        } else {
            handle_non_direct_oglas($custom_fields, $naziv, $oglas_link, $styled_dostava, $headers, $subject);
        }
    }
}

// Helper Functions
function get_latest_oglas_by_author($author_id) {
    return get_posts([
        'post_type'      => 'product',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'orderby'        => 'modified',
        'order'          => 'DESC',
        'author'         => $author_id
    ]);
}

function get_custom_fields($post_id) {
    return [
        'uslovi_dostave' => get_post_meta($post_id, 'uslovi_dostave', true),
        'radius_dostave' => get_post_meta($post_id, 'radius_dostave', true),
        'ulica_i_broj' => get_post_meta($post_id, 'ulica_i_broj', true),
        'grad' => get_post_meta($post_id, 'grad', true),
        'direktna_prodaja' => get_post_meta($post_id, 'direktna_prodaja', true),
        'lat' => get_post_meta($post_id, '7b4fc4a2c108dc0e296f1d6cd56ebd96_lat', true),
        'lng' => get_post_meta($post_id, '7b4fc4a2c108dc0e296f1d6cd56ebd96_lng', true),
    ];
}

function get_email_headers() {
    return [
        'Content-Type: text/html; charset=UTF-8',
        'From: Reusechain.com <noreply@reusechain.com>'
    ];
}

function generate_delivery_block($custom_fields) {
    $styled_dostava = "<div style='border:1px solid #ccc; background-color:#F5F5F5; padding:10px; border-radius:16px; margin:20px 0; width:50%;'>";
    $styled_dostava .= "<p><strong>Uslovi dostave:</strong> {$custom_fields['uslovi_dostave']}</p>";

    if ($custom_fields['uslovi_dostave'] === 'Kompanija dostavlja na lokaciju kupca') {
        $styled_dostava .= "<p><strong>Maksimalna udaljenost za dostavu:</strong> {$custom_fields['radius_dostave']} km</p>";
    } elseif ($custom_fields['uslovi_dostave'] === 'Materijal se preuzima na adresi prodavca') {
        $styled_dostava .= "<p><strong>Adresa preuzimanja:</strong> {$custom_fields['ulica_i_broj']},{$custom_fields['grad']}</p>";
    }
    $styled_dostava .= "</div>";

    return $styled_dostava;
}

function determine_email_subject($form_id, $direktna_donacija) {
    return $direktna_donacija == 1
        ? 'Novi direktni oglas/prodaja na Reusechain.com'
        : 'Novi oglas na Reusechain.com';
}

function handle_direct_oglas($post_id, $naziv, $link, $styled_dostava, $headers, $subject) {
    // Retrieve selected kupci
    $selected_kupci = get_post_meta($post_id, 'odabrane_kompanije_za_direktnu_prodaju', true);
    if (is_serialized($selected_kupci)) {
        $selected_kupci = unserialize($selected_kupci);
    }

    // Prepare the email message
    $message_intro = "<p>Korisnik <strong>$naziv</strong> je objavio novi oglas na Reusechain.com.</p>";
    $message_intro .= "<p>Vi ste odabrani kao jedan od kupaca koji može predati zahtjev za preuzimanje/kupovinu.</p>";
    $message_intro .= "<p>Link oglasa: <a href='$link'>$link</a></p>";

    $message = $message_intro . $styled_dostava;

    // Notify selected kupci
    if (!empty($selected_kupci) && is_array($selected_kupci)) {
        foreach ($selected_kupci as $kupac_id) {
            $kupac_user = get_user_by('ID', $kupac_id);
            if ($kupac_user && $kupac_user->user_email) {
            //$mail_sent = wp_mail($kupac_user->user_email, $subject, $message, $headers);
            $mail_sent = wp_mail('azraa.kadric@gmail.com', $subject . $kupac_user->user_email, $message, $headers);
            // Log the result of wp_mail
            if ($mail_sent) {
                error_log("Email successfully sent to: {$kupac_user->user_email}");
            } else {
                error_log("Failed to send email to: {$kupac_user->user_email}");
            }
            }
        }
    }
}


function handle_non_direct_oglas($custom_fields, $naziv, $link, $styled_dostava, $headers, $subject) {
    $uslovi_dostave = $custom_fields['uslovi_dostave'];
    error_log(print_r($custom_fields));
    // Prepare the email message
    $message = "<p>Novi oglas je upravo oglašen na Reusechain.com.</p>";
    $message .= $styled_dostava;
    $message .= "<p>Pogledajte najnoviji oglas na linku: <a href='$link'>$link</a></p>";
    $message .= "<p><strong>Naziv prodavača:</strong> $naziv</p>";
    $message .= "<p>Lijep pozdrav,<br>Reusechain.com</p>";

    if ($uslovi_dostave === 'Kompanija dostavlja na lokaciju kupca') {
        // Notify kupci within the radius
        notify_kupci_in_radius($custom_fields, $message, $headers, $subject);
    } elseif ($uslovi_dostave === 'Materijal se preuzima na adresi prodavca') {
        // Notify all approved kupci for this condition
        notify_all_approved_kupci($message, $headers, $subject, $custom_fields);
    }
}

function notify_kupci_in_radius($custom_fields, $message, $headers, $subject) {
    $lat = $custom_fields['lat'];
    $lng = $custom_fields['lng'];
    $radius = $custom_fields['radius_dostave'];

    error_log("Fetching kupci within radius: Lat = $lat, Lng = $lng, Radius = $radius");

    // Fetch kupci within the radius
    $api_url = add_query_arg([
        'koordinate_lat' => $lat,
        'koordinate_lon' => $lng,
        'radius'         => $radius,
    ], 'https://reusechain.com/wp-json/reusechain/kupci-u-radiusu-sql/');

    $response = wp_remote_get($api_url);
    $kupci_in_radius = [];

    if (is_wp_error($response)) {
        error_log('Error fetching kupci: ' . $response->get_error_message());
        return;
    }

    $body = wp_remote_retrieve_body($response);
    error_log('API Response Body: ' . $body);
    $kupci_in_radius = json_decode($body, true);

    // Notify kupci within the radius
    foreach ($kupci_in_radius as $kupac) {
        $email = $kupac['user_email'];
        $message_radius = "<p><strong>Primili ste ovaj mail jer ste unutar maksimalne udaljenosti dostave koju je prodavač odredio.</strong></p>";
        
        // $mail_sent = wp_mail($email, $subject, $message . $message_radius, $headers);
        $mail_sent = wp_mail('azraa.kadric@gmail.com', $subject . $email, $message . $message_radius, $headers);
            // Log the result of wp_mail
            if ($mail_sent) {
                error_log("Email successfully sent to: {$email}");
            } else {
                error_log("Failed to send email to: {$email}");
            } 
    }

    // Fetch all kupci
    $all_kupci = get_users(['role' => 'donator']); 

    foreach ($all_kupci as $kupac) {
        $user_id = $kupac->ID;
        $email = $kupac->user_email;

        // Skip kupci already notified based on radius
        if (in_array($user_id, array_column($kupci_in_radius, 'ID'))) {
            continue;
        }

        $is_approved = get_user_meta($user_id, 'is_approved', true);
        $has_location = get_user_meta($user_id, 'koordinate', true);

        if ($is_approved) {
                // Additional note for users without location
                $additional_note = '';
                if (empty($has_location)) {
                    $additional_note = "<p><strong>Napomena:</strong> Nemate postavljenu lokaciju u svom profilu. Molimo vas da postavite lokaciju kako bismo vas mogli povezati s donacijama u vašoj blizini.</p>";
                }

                // Send notification email
                $message_outside_radius = "<p><strong>Obavijest:</strong> Iako niste unutar maksimalne udaljenosti dostave, možete aplicirati na ovu oglas. 
                                            Molimo vas da uzmete u obzir da trebate pokriti dio udaljenosti.</p>";

            /* $mail_sent = wp_mail(
                    $email,
                    $subject,
                    $message . $message_outside_radius . $additional_note,
                    $headers
                );  */
/*
            $mail_sent = wp_mail(
                'azraa.kadric@gmail.com',
                $subject . $email,
                $message . $message_outside_radius . $additional_note,
                $headers
            );
            // Log the result of wp_mail
            if ($mail_sent) {
                error_log("Email successfully sent to: {$email}");
            } else {
                error_log("Failed to send email to: {$email}");
            }
        }
    }
}

function notify_all_approved_kupci($message, $headers, $subject, $custom_fields) {
    $oglas_lat = $custom_fields['lat'];
    $oglas_lon = $custom_fields['lng'];
    $oglas_radius = $custom_fields['radius_dostave'];
    error_log(print_r($custom_fields));

    // Fetch kupci from the REST API
    $api_url = add_query_arg([
        'koordinate_lat' => $oglas_lat,
        'koordinate_lon' => $oglas_lon,
    ], 'https://reusechain.com/wp-json/reusechain/kupci-u-radiusu-sql-sa-postavkama-obavjestenja/');

    $response = wp_remote_get($api_url);

    error_log('Oglas Latitude: ' . $oglas_lat . ', Oglas Longitude: ' . $oglas_lon);
    if (is_wp_error($response)) {
        error_log('Error fetching kupci: ' . $response->get_error_message());
        return;
    }

    $kupci = json_decode(wp_remote_retrieve_body($response), true);

    foreach ($kupci as $kupac) {
        $is_within_radius = $kupac['distance'] !== null && $kupac['distance'] <= $kupac['radius_primanja_obavjestenja'];
        $naziv = $kupac['naziv'];
        //$je_premium = $kupac['je_premium'];
        // Check conditions for sending email
        // OVDJE JE MOGUĆE DODATI NEŠTO KAO "IF PREMIUM" I DA PREMIUM KORISNICI DOBIJU SVA OBAVJEŠTENJA
            $additional_note = '';
            $pickup_note = "<p><strong>Napomena:</strong> Oglas se preuzima u sjedištu prodavača. Molimo vas da uzmete u obzir adresu preuzimanja.</p>";
           
            if (empty($kupac['koordinate'])) {
                $additional_note .= "<p><strong>Napomena:</strong> Nemate postavljenu lokaciju u svom profilu. 
                Molimo vas da postavite lokaciju kako bismo vas mogli obavijestiti o oglasima u vašoj blizini.</p>";
            }
            error_log('API Response: ' . print_r($kupac, true));


            // Send email notification
            /* wp_mail(
                $kupac['user_email'],
                $subject,
                'Pozdrav ' . $naziv .',' . '<br>' . $message . $additional_note . $pickup_note,
                $headers
            ); */
            // Send email notification
/*
            $mail_sent = wp_mail(
            'azraa.kadric@gmail.com',
            $subject . $naziv,
            'Pozdrav ' . $naziv .',' . '<br>' . $message . $additional_note . $pickup_note,
            $headers
        );
        error_log($mail_sent);
        error_log('API Response: ' . print_r($kupac, true));
        }
    }
 */




/* TODO: Preraditi tako da se:
* Doda opcija za otkaz oglasa na platformi, na edit oglasa ili slično
* Pošalje email svim korisnicima koji su pokrenuli kupovinu, ie. imaju napravljen, ali NEPLAĆEN order
*/

/* add_action('jet-form-builder/custom-action/send-email-on-cancelled-donation', function($form_data, $form) {
    global $wpdb;

    // Set the form ID
    $expected_form_id = 5433;

    // Get the current post ID
    $current_post_id = $form_data['post_id'] ?? 0;

    // Check if the submitted form matches the expected form ID
    if (isset($form_data['__form_id']) && (int) $form_data['__form_id'] === $expected_form_id) {
        if ($current_post_id) {
            // Query the custom table for related zahtjev-preuzimanje entries
            $table_name = $wpdb->prefix . 'zahtjev_preuzimanje_meta';
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT object_ID, id_kupca 
                FROM $table_name 
                WHERE id_oglasa = %d
            ", $current_post_id), ARRAY_A);

            // Check if there are any matching entries
            if (!empty($results)) {
                // Collect unique 'posrednik' IDs
                $posrednik_ids = array_unique(array_filter(array_column($results, 'id_kupca')));

                if (!empty($posrednik_ids)) {
                    // Fetch 'posrednik' users by their IDs
                    $posrednik_users = get_users([
                        'include' => $posrednik_ids,
                        'role'    => 'donator',
                    ]);

                    // Get the title and link of the current post
                    $current_post_title = esc_html(get_the_title($current_post_id));
                    $current_post_link = esc_url(get_permalink($current_post_id));

                    // Reason for cancellation
                    $razlog_otkazivanja = !empty($form_data['razlog_otkazivanja'])
                        ? esc_html($form_data['razlog_otkazivanja'])
                        : 'Korisnik nije naveo razlog otkaza';

                    // Email content
                    $subject = "Oglas {$current_post_title} u kom imate zahtjev za preuzimanje je otkazan";
                    $message = "
                        <p>Poštovani,</p>
                        <p>Oglas \"<a href='{$current_post_link}'>{$current_post_title}</a>\" je upravo otkazan.</p>
                        <p>Vaš zahtjev za preuzimanje je otkazan.</p>
                        <p><strong>Razlog otkaza:</strong><br>{$razlog_otkazivanja}</p>
                        <p>Srdačan pozdrav,<br>Tim Reusechain.com</p>
                    ";

                    // Email headers
                    $headers = [
                        'Content-Type: text/html; charset=UTF-8',
                        'From: Reusechain.com <noreply@reusechain.com>',
                    ];

                    // Send email
                    foreach ($posrednik_users as $user) {
                        wp_mail('azraa.kadric@gmail.com', $subject, $message, $headers);
                    }
                }
            }
        }
    }
}, 10, 2); */