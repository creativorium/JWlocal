# Journal Integration — jwtrading-core Plugin

## Context

This document is a task brief for adding **Supabase Journal webhook integration** to the existing `jwtrading-core` WordPress plugin.

The journal site (`jwtradingjurnal.com`) has its own dev team and has already provided a webhook endpoint. Our job is to call that endpoint when a WooCommerce order is completed.

---

## Current Plugin State

The `jwtrading-core` plugin already dispatches order data to:
- ✅ Kit (email marketing, API v4)
- ✅ Google Sheets (via Apps Script webhook)
- ✅ Thinkific (via existing plugin)

The main dispatcher class listens to the `woocommerce_order_status_completed` hook and fans out to each integration.

---

## What to Add

### 1. New class: `class-journal-sync.php`

Location: `includes/class-journal-sync.php`

Responsibilities:
- Accept order data (email, order ID)
- POST to the journal webhook endpoint
- Log success or failure to the existing sync log table

```php
<?php

class JWTrading_Journal_Sync {

    private $endpoint = 'https://api.jwtradingjurnal.com/api/webhook/whitelist';

    public function sync( $order_id, $order ) {
        $secret = get_option( 'jwtrading_journal_secret' );

        if ( empty( $secret ) ) {
            // log: missing secret
            return;
        }

        $email = $order->get_billing_email();

        $body = wp_json_encode( [
            'email'     => $email,
            'requestId' => 'WC-' . $order_id,
        ] );

        $response = wp_remote_post( $this->endpoint, [
            'headers' => [
                'Content-Type'     => 'application/json',
                'x-webhook-secret' => $secret,
            ],
            'body'    => $body,
            'timeout' => 15,
        ] );

        if ( is_wp_error( $response ) ) {
            // log failure: $response->get_error_message()
            return;
        }

        $decoded = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $decoded['ok'] ) && $decoded['ok'] === true ) {
            // log success
            // handle duplicate: $decoded['status'] === 'duplicate'
        } else {
            // log failure: $decoded['error'] ?? 'unknown error'
        }
    }
}
```

---

### 2. Register the secret in admin settings

In the existing admin settings page (`admin/settings-page.php` or wherever settings are registered), add a new field:

```php
add_settings_field(
    'jwtrading_journal_secret',
    'Journal Webhook Secret',
    'jwtrading_journal_secret_callback',
    'jwtrading-settings',
    'jwtrading_integrations_section'
);

register_setting( 'jwtrading-settings', 'jwtrading_journal_secret' );
```

Field should render as a password-type input (so the token isn't visible in plain text).

---

### 3. Hook into the dispatcher

In the main dispatcher class (`class-order-dispatcher.php` or similar), instantiate and call the Journal sync:

```php
$journal = new JWTrading_Journal_Sync();
$journal->sync( $order_id, $order );
```

This should run alongside the existing Kit and Sheets sync calls.

---

## Endpoint Details (from journal dev team)

| Field | Value |
|-------|-------|
| URL | `https://api.jwtradingjurnal.com/api/webhook/whitelist` |
| Method | `POST` |
| Auth header | `x-webhook-secret: SECRET_TOKEN` |
| Content-Type | `application/json` |

**Request body:**
```json
{
  "email": "buyer@example.com",
  "requestId": "WC-12345"
}
```

**Success response:**
```json
{
  "ok": true,
  "processed": 1,
  "results": [
    {
      "email": "buyer@example.com",
      "whitelisted": true,
      "invite": {
        "ok": true,
        "status": "sent"
      }
    }
  ]
}
```

**Duplicate (already registered):**
```json
{
  "ok": true,
  "status": "duplicate",
  "processed": 0
}
```

**Error:**
```json
{
  "error": "invalid_webhook_secret"
}
```

---

## Notes

- `requestId` uses prefix `WC-` + WooCommerce order ID — this prevents duplicate processing on their end if the webhook fires more than once
- Duplicate responses (`status: duplicate`) should be logged but not treated as errors
- The secret token must be stored in WordPress options via the admin settings page, **never hardcoded**
- Use `wp_remote_post` (not curl) to stay within WordPress standards
- Plug into the existing sync log table if one exists in the plugin

---

## Checklist

- [ ] Create `includes/class-journal-sync.php`
- [ ] Add `jwtrading_journal_secret` field to admin settings page
- [ ] Call `JWTrading_Journal_Sync::sync()` from the order dispatcher
- [ ] Test with a real completed order in LocalWP
- [ ] Enter the secret token in WP Admin → JW Trading Settings
- [ ] Deploy via SFTP to EasyWP
