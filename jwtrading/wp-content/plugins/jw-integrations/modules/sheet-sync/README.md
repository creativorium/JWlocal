# JW WooCommerce Google Sheet Sync

A production-ready WordPress plugin that sends successful WooCommerce order data to a Google Apps Script webhook for recording in Google Sheets.

## Requirements

- WordPress 5.8+
- WooCommerce 6.0+
- PHP 7.4+

## Installation

1. **Upload the plugin**
   - Zip the `jw-woocommerce-google-sheet-sync` folder
   - Go to **WordPress Admin → Plugins → Add New → Upload Plugin**
   - Upload the zip and click **Install Now** → **Activate**

   Or copy the `jw-woocommerce-google-sheet-sync` folder to `wp-content/plugins/` and activate from the Plugins screen.

2. **Ensure WooCommerce is active**
   - The plugin requires WooCommerce. If WooCommerce is inactive, an admin notice will appear.

3. **Configure the plugin**
   - Go to **WooCommerce → Google Sheet Sync**
   - Enter your settings (see below)

## Where to Paste Webhook URL and Secret Token

1. Go to **WooCommerce → Google Sheet Sync** in your WordPress admin.
2. **Google Apps Script Webhook URL**: Paste the full URL of your deployed Google Apps Script web app (e.g. `https://script.google.com/macros/s/AKfycbz.../exec`).
3. **Secret Token**: Enter the same secret token you use in your Google Apps Script to verify requests. This must match exactly.

## File/Folder Structure

```
jw-woocommerce-google-sheet-sync/
├── jw-woocommerce-google-sheet-sync.php   # Main plugin file
├── includes/
│   ├── class-jw-gsheet-sync.php           # Main plugin class
│   ├── class-jw-gsheet-sync-settings.php  # Settings page
│   ├── class-jw-gsheet-sync-payload.php   # Payload builder & field mapping
│   ├── class-jw-gsheet-sync-webhook.php   # HTTP sender
│   ├── class-jw-gsheet-sync-order-sync.php # Order status listener
│   └── class-jw-gsheet-sync-order-metabox.php # Order metabox & resend
├── assets/
│   ├── js/
│   │   └── admin.js
│   └── css/
│       └── admin.css
├── languages/
│   └── (translation files)
└── README.md
```

## Example Payload JSON

The plugin sends a POST request with `Content-Type: application/json`. Example payload:

```json
{
  "secret_token": "your-secret-token",
  "site_label": "My Store",
  "order_id": 12345,
  "order_number": "12345",
  "order_status": "processing",
  "order_date": "2025-03-12 14:30:00",
  "payment_method": "stripe",
  "payment_method_title": "Credit Card (Stripe)",
  "transaction_id": "ch_abc123",
  "currency": "USD",
  "total": 99.99,
  "subtotal": 89.99,
  "discount_total": 0,
  "customer_note": "Please leave at door",
  "first_name": "John",
  "last_name": "Doe",
  "full_name": "John Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "country": "US",
  "product_names": "Product A | Product B",
  "item_count": 3,
  "quantities_summary": "Product A: 2 | Product B: 1",
  "sku_summary": "SKU-A x2 | SKU-B x1",
  "discord_username": "johndoe#1234",
  "enrollment_tracker": "ENR-001",
  "website_label": "",
  "whatsapp_number": "+1234567890"
}
```

## Extending Field Mapping

Custom checkout fields are configured in `includes/class-jw-gsheet-sync-payload.php` via the `CUSTOM_FIELD_MAPPING` constant.

### Adding or changing custom fields

Edit the `CUSTOM_FIELD_MAPPING` array:

```php
const CUSTOM_FIELD_MAPPING = array(
    'discord_username'   => array( '_discord_username', 'discord_username', 'billing_discord_username' ),
    'enrollment_tracker' => array( '_enrollment_tracker', 'enrollment_tracker', 'billing_enrollment_tracker' ),
    'website_label'      => array( '_website_label', 'website_label', 'billing_website_label' ),
    'whatsapp_number'    => array( '_whatsapp_number', 'whatsapp_number', 'billing_whatsapp_number' ),
    // Add your field:
    'my_custom_field'    => array( '_my_custom_field', 'my_custom_field' ),
);
```

- **Key**: Output field name in the JSON payload.
- **Value**: Array of possible order meta keys. The first non-empty value found is used.

### Adding new payload fields

To add new fields to the payload, edit `JW_GSheet_Sync_Payload::build()` in `class-jw-gsheet-sync-payload.php` and add your data to the `$payload` array.

## Google Apps Script Example

Minimal web app to receive and log the data:

```javascript
function doPost(e) {
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  const data = JSON.parse(e.postData.contents);
  
  // Verify secret
  if (data.secret_token !== 'your-secret-token') {
    return ContentService.createTextOutput(JSON.stringify({ error: 'Unauthorized' }))
      .setMimeType(ContentService.MimeType.JSON);
  }
  
  // Append row (adjust columns to match your payload)
  sheet.appendRow([
    data.order_date,
    data.order_number,
    data.full_name,
    data.email,
    data.total,
    data.order_status
  ]);
  
  return ContentService.createTextOutput(JSON.stringify({ message: 'OK' }))
    .setMimeType(ContentService.MimeType.JSON);
}
```

Deploy as **Web app** (Execute as: Me, Who has access: Anyone).

## Order Meta Keys

The plugin stores:

| Meta Key | Description |
|----------|-------------|
| `_jw_gsheet_sent` | `yes` if sent successfully |
| `_jw_gsheet_sent_at` | MySQL datetime of last sync |
| `_jw_gsheet_response` | Short summary of webhook response |

## License

GPL v2 or later
