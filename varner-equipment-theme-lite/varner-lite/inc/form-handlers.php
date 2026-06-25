<?php
/**
 * Varner Theme — Form Handlers
 *
 * Extracted from functions.php. Loaded via require_once in that file.
 * IP resolver, CIDR helper, captcha, form verification, and all 5 public
 * form submit handlers with their hooks. Self-contained block.
 */

defined('ABSPATH') || exit;

/**
 * Resolve the client IP for rate limiting.
 *
 * REMOTE_ADDR is the only value the client cannot forge, so it's the default.
 * X-Forwarded-For is honored ONLY when the connection actually arrives from a
 * trusted proxy (e.g. Cloudflare). Otherwise the header is attacker-controlled
 * and would let a single client evade the limiter by rotating it each request.
 *
 * Populate $trusted_proxies with your real proxy/Cloudflare egress ranges, or
 * leave it empty if the site is served directly (REMOTE_ADDR only).
 */
function varner_get_client_ip() {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if ( ! filter_var( $remote, FILTER_VALIDATE_IP ) ) {
        return '';
    }

    // CIDR ranges for proxies you control. Empty = trust nothing, use REMOTE_ADDR.
    // Cloudflare publishes its ranges at https://www.cloudflare.com/ips/
    $trusted_proxies = array(
        // '173.245.48.0/20',
        // '103.21.244.0/22',
        // ... add your proxy/CDN ranges here ...
    );

    $is_trusted = false;
    foreach ( $trusted_proxies as $cidr ) {
        if ( varner_ip_in_cidr( $remote, $cidr ) ) {
            $is_trusted = true;
            break;
        }
    }

    if ( ! $is_trusted ) {
        // Direct connection (or unknown proxy): only REMOTE_ADDR is trustworthy.
        return $remote;
    }

    // Behind a trusted proxy: take the right-most XFF entry, which the trusted
    // proxy appended. Walk right-to-left past any further trusted hops.
    $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ( $xff ) {
        $parts = array_map( 'trim', explode( ',', $xff ) );
        for ( $i = count( $parts ) - 1; $i >= 0; $i-- ) {
            $candidate = $parts[ $i ];
            if ( ! filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
                continue;
            }
            $candidate_is_proxy = false;
            foreach ( $trusted_proxies as $cidr ) {
                if ( varner_ip_in_cidr( $candidate, $cidr ) ) {
                    $candidate_is_proxy = true;
                    break;
                }
            }
            if ( ! $candidate_is_proxy ) {
                return $candidate; // first non-proxy hop = real client
            }
        }
    }

    return $remote;
}

/**
 * Check whether an IPv4/IPv6 address falls within a CIDR range.
 */
function varner_ip_in_cidr( $ip, $cidr ) {
    if ( strpos( $cidr, '/' ) === false ) {
        return $ip === $cidr;
    }
    list( $subnet, $bits ) = explode( '/', $cidr, 2 );
    $bits = (int) $bits;

    $ip_bin     = @inet_pton( $ip );
    $subnet_bin = @inet_pton( $subnet );
    if ( $ip_bin === false || $subnet_bin === false || strlen( $ip_bin ) !== strlen( $subnet_bin ) ) {
        return false; // mismatched family or invalid input
    }

    $bytes = intdiv( $bits, 8 );
    $rem   = $bits % 8;

    if ( $bytes > 0 && strncmp( $ip_bin, $subnet_bin, $bytes ) !== 0 ) {
        return false;
    }
    if ( $rem > 0 ) {
        $mask = chr( 0xFF << ( 8 - $rem ) & 0xFF );
        if ( ( ord( $ip_bin[ $bytes ] ) & ord( $mask ) ) !== ( ord( $subnet_bin[ $bytes ] ) & ord( $mask ) ) ) {
            return false;
        }
    }
    return true;
}

/**
 * Form Submission Helper: Verifies nonce and captcha, and applies IP-based rate limiting
 */
function varner_generate_stateless_captcha() {
    $num1 = rand(10, 99);
    $num2 = rand(10, 99);
    $ans  = $num1 + $num2;
    $time = time();
    $key  = wp_salt('nonce');
    $hash = hash_hmac('sha256', "$ans|$time", $key);
    
    return array(
        'num1' => $num1,
        'num2' => $num2,
        'time' => $time,
        'hash' => $hash,
    );
}

/**
 * Form Submission Helper: Verifies nonce and captcha, and applies IP-based rate limiting
 */
function varner_verify_form_submission( $nonce_name, $action_name, $require_captcha = false ) {
    // Validate nonce FIRST — don't spend rate-limit budget on forged/expired requests.
    if ( ! isset( $_POST[$nonce_name] ) || ! wp_verify_nonce( $_POST[$nonce_name], $action_name ) ) {
        wp_safe_redirect( wp_get_referer() ?: home_url() );
        exit;
    }

    $now = time();

    // Rate Limiting Check using client IP and WordPress transients
    $ip = varner_get_client_ip();
    if ( $ip !== '' ) {
        $transient_key = 'vne_rl_' . substr( md5( $ip ), 0, 20 );
        $rate_data = get_transient( $transient_key );

        if ( ! is_array( $rate_data ) ) {
            $rate_data = array(
                'last_time'  => 0,
                'count'      => 0,
                'first_time' => $now,
            );
        }

        // 1. Cooldown check (5 seconds between submissions)
        if ( $now - $rate_data['last_time'] < 5 ) {
            wp_die( '<h1>Too Many Requests</h1><p>Please wait a few seconds before submitting another form.</p><a href="javascript:history.back()">Go Back</a>', 'Too Many Requests', array( 'response' => 429 ) );
        }

        // 2. Hourly limit check (max 10 submissions per hour per IP)
        if ( $now - $rate_data['first_time'] > 3600 ) {
            $rate_data['first_time'] = $now;
            $rate_data['count']      = 0;
        }

        if ( $rate_data['count'] >= 10 ) {
            wp_die( '<h1>Rate Limit Exceeded</h1><p>You have reached the maximum number of submissions allowed per hour. Please try again later.</p><a href="javascript:history.back()">Go Back</a>', 'Rate Limit Exceeded', array( 'response' => 429 ) );
        }

        // Update rate limit data and set transient TTL to the remaining time in the hourly window
        $rate_data['last_time'] = $now;
        $rate_data['count']++;
        $ttl = max( 1, 3600 - ( $now - $rate_data['first_time'] ) );
        set_transient( $transient_key, $rate_data, $ttl );
    }

    if ( $require_captcha ) {
        $user_ans = isset( $_POST['captcha_answer'] ) ? intval( $_POST['captcha_answer'] ) : 0;
        $time     = isset( $_POST['captcha_time'] ) ? intval( $_POST['captcha_time'] ) : 0;
        $hash     = isset( $_POST['captcha_hash'] ) ? sanitize_text_field( $_POST['captcha_hash'] ) : '';

        // Check expiration: 1 hour (3600 seconds)
        if ( time() - $time > 3600 || time() - $time < -10 ) {
            wp_die( '<h1>Security Verification Failed</h1><p>Captcha expired. Please go back, reload the page, and try again.</p><a href="javascript:history.back()">Go Back</a>' );
        }

        $key = wp_salt('nonce');
        $expected_hash = hash_hmac('sha256', "$user_ans|$time", $key);

        if ( empty($hash) || ! hash_equals( $expected_hash, $hash ) ) {
            wp_die( '<h1>Security Verification Failed</h1><p>Incorrect sum. Please go back and try again.</p><a href="javascript:history.back()">Go Back</a>' );
        }
    }
}

/**
 * Helper: resolve a departmental email with fallback to the general contact_email, then admin_email.
 */
function varner_dept_email( $dept_key ) {
    $email = varner_get_theme_setting( $dept_key );
    if ( ! empty( $email ) ) {
        return $email;
    }
    return varner_get_theme_setting( 'contact_email', get_option( 'admin_email' ) );
}

/**
 * Chatbox form handler — routed to general contact_email.
 */
function varner_handle_chatbox_submit() {
    varner_verify_form_submission( 'varner_chatbox_nonce', 'varner_chatbox_submit', false );

    $dept    = sanitize_text_field( $_POST['department'] ?? '' );
    $name    = sanitize_text_field( $_POST['name'] ?? '' );
    $mobile  = sanitize_text_field( $_POST['mobile'] ?? '' );
    $msg     = sanitize_textarea_field( $_POST['message'] ?? '' );

    $recipient = varner_dept_email( 'contact_email' );
    $subject   = sanitize_text_field( "Chatbox Inquiry [{$dept}]: {$name}" );
    $body      = "Department: {$dept}\nName: {$name}\nMobile: {$mobile}\n\nMessage:\n{$msg}";
    wp_mail( $recipient, $subject, $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );

    wp_safe_redirect( wp_get_referer() ?: home_url() );
    exit;
}

add_action( 'admin_post_nopriv_varner_chatbox_submit', 'varner_handle_chatbox_submit' );
add_action( 'admin_post_varner_chatbox_submit', 'varner_handle_chatbox_submit' );

/**
 * General Contact Form handler — routed to general contact_email.
 */
function varner_handle_contact_form_submit() {
    varner_verify_form_submission( 'varner_contact_nonce', 'varner_contact_form_submit', true );

    $name = sanitize_text_field( $_POST['full_name'] );
    $body = "CONTACT FORM SUBMISSION:\n\n"
          . "Name: $name\n"
          . "Email: " . sanitize_email( $_POST['email'] ) . "\n"
          . "Phone: " . sanitize_text_field( $_POST['phone'] ) . "\n\n"
          . "Message:\n" . sanitize_textarea_field( $_POST['message'] ) . "\n";
    $recipient = varner_dept_email( 'contact_email' );
    wp_mail( $recipient, 'General Website Inquiry: ' . $name, $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );

    wp_safe_redirect( esc_url_raw( add_query_arg( 'request', 'sent', wp_get_referer() ?: home_url() ) ) );
    exit;
}

add_action( 'admin_post_nopriv_varner_contact_form_submit', 'varner_handle_contact_form_submit' );
add_action( 'admin_post_varner_contact_form_submit', 'varner_handle_contact_form_submit' );

/**
 * Parts Request form handler
 */
function varner_handle_parts_request_submit() {
    varner_verify_form_submission( 'varner_parts_nonce', 'varner_parts_request_submit', true );

    $fname = sanitize_text_field( $_POST['first_name'] ?? '' );
    $lname = sanitize_text_field( $_POST['last_name']  ?? '' );
    $make  = sanitize_text_field( $_POST['make']        ?? '' );
    $model = sanitize_text_field( $_POST['model']       ?? '' );

    $body = "CUSTOMER INFORMATION:\n"
          . "Name: $fname $lname\n"
          . "Email: " . sanitize_email( $_POST['email'] ) . "\n"
          . "Phone: " . sanitize_text_field( $_POST['phone'] ) . "\n"
          . "Address: " . sanitize_text_field( $_POST['address'] ) . ", " . sanitize_text_field( $_POST['city'] ) . ", " . sanitize_text_field( $_POST['state'] ) . " " . sanitize_text_field( $_POST['zip'] ) . "\n\n"
          . "EQUIPMENT DETAILS:\n"
          . "Make: $make\nModel: $model\nYear: " . sanitize_text_field( $_POST['year'] ) . "\nSerial: " . sanitize_text_field( $_POST['serial'] ) . "\nHours: " . sanitize_text_field( $_POST['hours'] ) . "\n\n"
          . "PARTS REQUESTED:\n"
          . "Preferred Date: " . sanitize_text_field( $_POST['appointment_date'] ) . "\nDescription: " . sanitize_textarea_field( $_POST['parts_needed'] ) . "\n\n"
          . "HISTORY:\n"
          . "Prior Customer: " . sanitize_text_field( $_POST['prior_service'] ) . "\nLast Date: " . sanitize_text_field( $_POST['last_service_date'] ) . "\nLast Work: " . sanitize_text_field( $_POST['last_service_work'] ) . "\n";
    $recipient = varner_dept_email( 'parts_email' );
    wp_mail( $recipient, "Parts Request: $fname $lname ($make $model)", $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );

    wp_safe_redirect( home_url( '/contact?request=sent' ) );
    exit;
}

add_action( 'admin_post_nopriv_varner_parts_request_submit', 'varner_handle_parts_request_submit' );
add_action( 'admin_post_varner_parts_request_submit', 'varner_handle_parts_request_submit' );

/**
 * Service Request form handler
 */
function varner_handle_service_request_submit() {
    varner_verify_form_submission( 'varner_service_nonce', 'varner_service_request_submit', true );

    $fname = sanitize_text_field( $_POST['first_name'] ?? '' );
    $lname = sanitize_text_field( $_POST['last_name']  ?? '' );
    $make  = sanitize_text_field( $_POST['make']        ?? '' );
    $model = sanitize_text_field( $_POST['model']       ?? '' );

    $body = "CUSTOMER INFORMATION:\n"
          . "Name: $fname $lname\n"
          . "Email: " . sanitize_email( $_POST['email'] ) . "\n"
          . "Phone: " . sanitize_text_field( $_POST['phone'] ) . "\n"
          . "Address: " . sanitize_text_field( $_POST['address'] ) . ", " . sanitize_text_field( $_POST['city'] ) . ", " . sanitize_text_field( $_POST['state'] ) . " " . sanitize_text_field( $_POST['zip'] ) . "\n\n"
          . "EQUIPMENT DETAILS:\n"
          . "Make: $make\nModel: $model\nYear: " . sanitize_text_field( $_POST['year'] ) . "\nSerial: " . sanitize_text_field( $_POST['serial'] ) . "\nHours: " . sanitize_text_field( $_POST['hours'] ) . "\n\n"
          . "SERVICE NEEDS:\n"
          . "Appointment Date: " . sanitize_text_field( $_POST['appointment_date'] ) . "\nDescription: " . sanitize_textarea_field( $_POST['services_needed'] ) . "\n\n"
          . "HISTORY:\n"
          . "Prior Customer: " . sanitize_text_field( $_POST['prior_service'] ) . "\nLast Date: " . sanitize_text_field( $_POST['last_service_date'] ) . "\nLast Work: " . sanitize_text_field( $_POST['last_service_work'] ) . "\n";
    $recipient = varner_dept_email( 'service_email' );
    wp_mail( $recipient, "Service Request: $fname $lname ($make $model)", $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );

    wp_safe_redirect( home_url( '/contact?request=sent' ) );
    exit;
}

add_action( 'admin_post_nopriv_varner_service_request_submit', 'varner_handle_service_request_submit' );
add_action( 'admin_post_varner_service_request_submit', 'varner_handle_service_request_submit' );

/**
 * Employment Application form handler
 */
function varner_handle_employment_submit() {
    varner_verify_form_submission( 'varner_employment_nonce', 'varner_employment_submit', true );

    $fname = sanitize_text_field( $_POST['first_name'] ?? '' );
    $lname = sanitize_text_field( $_POST['last_name'] ?? '' );
    $pos   = sanitize_text_field( $_POST['position'] ?? '' );

    $body = "JOB APPLICATION\n\n"
          . "Name:     $fname $lname\n"
          . "Email:    " . sanitize_email( $_POST['email'] ?? '' ) . "\n"
          . "Phone:    " . sanitize_text_field( $_POST['phone'] ?? '' ) . "\n"
          . "Position: $pos\n\n"
          . "Cover Letter / Experience:\n" . sanitize_textarea_field( $_POST['cover_letter'] ?? '' ) . "\n";

    $attachments = array();
    if ( ! empty( $_FILES['resume']['name'] ) ) {
        // Enforce upload errors check and size limit of 5 MB (5 * 1024 * 1024 bytes)
        if ( $_FILES['resume']['error'] !== UPLOAD_ERR_OK ) {
            wp_die( '<h1>Upload Error</h1><p>There was an error uploading your resume. Please go back and try again.</p><a href="javascript:history.back()">Go Back</a>' );
        }
        if ( $_FILES['resume']['size'] > 5 * 1024 * 1024 ) {
            wp_die( '<h1>File Too Large</h1><p>Your resume file is too large. Maximum size allowed is 5 MB.</p><a href="javascript:history.back()">Go Back</a>' );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        $uploaded = wp_handle_upload( $_FILES['resume'], array(
            'test_form' => false,
            'mimes'     => array(
                'pdf'  => 'application/pdf',
                'doc'  => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ),
        ) );
        if ( isset( $uploaded['file'] ) ) {
            $attachments[] = $uploaded['file'];
        }
    }

    $recipient = varner_get_theme_setting( 'employment_email' ) ?: varner_dept_email( 'contact_email' );
    wp_mail( $recipient, "Job Application: $fname $lname — $pos", $body, array( 'Content-Type: text/plain; charset=UTF-8' ), $attachments );

    if ( ! empty( $attachments[0] ) && file_exists( $attachments[0] ) ) {
        wp_delete_file( $attachments[0] );
    }

    wp_safe_redirect( esc_url_raw( add_query_arg( 'application', 'sent', wp_get_referer() ?: home_url() ) ) );
    exit;
}

add_action( 'admin_post_nopriv_varner_employment_submit', 'varner_handle_employment_submit' );
add_action( 'admin_post_varner_employment_submit', 'varner_handle_employment_submit' );

