<?php

/**
 * Plugin Name:  G4Z Dev Mail Logger
 * Description:  Development only — writes wp_mail() output to web/app/mail.log instead of sending it.
 * Author:       Giraffes4Zebras
 * License:      MIT License
 */

declare(strict_types=1);

namespace G4Z\Bedrock;

/*
 * Herd free ships no Mailpit, so local mail has nowhere to go: password resets and
 * Contact Form 7 submissions fail silently while building form-heavy sites. This logs
 * them to a file and short-circuits delivery.
 *
 * Guarded on WP_ENV so it can never intercept mail on staging or production, even if
 * the file is deployed.
 */

if (!defined('WP_ENV') || WP_ENV !== 'development') {
    return;
}

add_filter('pre_wp_mail', static function ($short_circuit, array $atts) {
    $to = is_array($atts['to'] ?? null) ? implode(', ', $atts['to']) : (string) ($atts['to'] ?? '');
    $headers = is_array($atts['headers'] ?? null) ? implode(' | ', $atts['headers']) : (string) ($atts['headers'] ?? '');
    $attachments = is_array($atts['attachments'] ?? null) ? count($atts['attachments']) : 0;

    $entry = sprintf(
        "[%s]\nTo: %s\nSubject: %s\nHeaders: %s\nAttachments: %d\n\n%s\n\n%s\n\n",
        gmdate('Y-m-d H:i:s') . ' UTC',
        $to,
        (string) ($atts['subject'] ?? ''),
        $headers,
        $attachments,
        (string) ($atts['message'] ?? ''),
        str_repeat('-', 72),
    );

    error_log($entry, 3, WP_CONTENT_DIR . '/mail.log');

    // Non-null short-circuits wp_mail() and becomes its return value.
    return true;
}, 10, 2);
