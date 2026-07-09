# Troubleshooting 401 Authentication Errors

## What is a 401 Error?

A **401 Unauthorized** error means Thinkific's API is rejecting your authentication credentials. This happens when:
- API key is incorrect or invalid
- Subdomain is incorrect or malformed
- API key lacks required permissions
- Headers are not formatted correctly

## Quick Diagnostic Steps

### Step 1: Verify API Key

1. Log into your **Thinkific admin**
2. Go to **Settings** > **Code & Analytics**
3. Click **API & Webhooks** tab
4. Check if your API key exists
5. If unsure, **create a new API key**:
   - Click "Create New API Key"
   - Give it a name: "WordPress Integration"
   - Copy the key **immediately** (you won't see it again)

### Step 2: Check Subdomain Format

**Correct formats:**
- ✅ `yourschool` (just the subdomain)
- ✅ `yourschool.thinkific.com` (plugin auto-strips)

**Incorrect formats:**
- ❌ `https://yourschool.thinkific.com` (includes protocol)
- ❌ `yourschool.thinkific.com/` (trailing slash)
- ❌ ` yourschool ` (leading/trailing spaces)

**To verify:**
1. Go to **Thinkific > Settings** in WordPress
2. Look at your subdomain field
3. Remove any extra characters
4. Save settings
5. Test connection again

### Step 3: Check API Key Permissions

Your API key needs these permissions:
- ✅ **Read Users**
- ✅ **Write Users** (to create new users)
- ✅ **Read Enrollments**
- ✅ **Write Enrollments** (to enroll users)
- ✅ **Read Courses** (optional, for sync feature)

**To verify:**
1. In Thinkific admin, go to API keys
2. Check permissions for your key
3. If missing permissions, create a new key with correct permissions

### Step 4: Test with Debug Logging

The updated plugin now logs detailed 401 errors. Check the logs:

1. Go to **Thinkific > Logs** in WordPress admin
2. Look for error entries with these details:
   - `subdomain`: Should show your subdomain
   - `api_key_present`: Should be `true`
   - `api_key_length`: Should be 32-40 characters (typical)
   - `response`: Thinkific's error message

**Example log entry:**
```
[ERROR] API Authentication Failed (401)
Context:
  - subdomain: "yourschool"
  - api_key_present: true
  - api_key_length: 32
  - response: "Invalid API credentials"
```

## Common Issues & Solutions

### Issue 1: API Key Has Spaces or Hidden Characters

**Symptoms:**
- API key looks correct but fails
- Copy-pasted from Thinkific

**Solution:**
```
1. In WordPress: Thinkific > Settings
2. Click in API Key field
3. Select all (Ctrl+A or Cmd+A)
4. Delete
5. Go back to Thinkific and copy key again
6. Paste directly without any formatting
7. Save and test
```

### Issue 2: Wrong Thinkific Account

**Symptoms:**
- Multiple Thinkific schools
- Settings work in one, fail in another

**Solution:**
```
1. Verify which school you're configuring
2. Make sure subdomain matches the school
3. Make sure API key is from the SAME school
4. Subdomain "schoolA" can't use API key from "schoolB"
```

### Issue 3: API Key Was Deleted/Regenerated

**Symptoms:**
- Worked before, stopped working
- No changes to settings

**Solution:**
```
1. Check if API key still exists in Thinkific
2. If deleted, create new one
3. Update WordPress settings with new key
4. Test connection
```

### Issue 4: Thinkific Account Issue

**Symptoms:**
- Everything looks correct
- Still getting 401

**Solution:**
```
1. Check if Thinkific account is active
2. Verify subscription/plan status
3. Contact Thinkific support to verify API access
4. Ask them to check if API is enabled for your account
```

## Step-by-Step Test Process

### Test 1: Manual API Test (Outside WordPress)

You can test your credentials using a tool like **curl** or **Postman**:

```bash
curl -X GET "https://api.thinkific.com/api/public/v1/users?limit=1" \
  -H "X-Auth-API-Key: YOUR_API_KEY_HERE" \
  -H "X-Auth-Subdomain: yourschool" \
  -H "Content-Type: application/json"
```

**Expected result:**
- Status 200 with JSON response = Credentials work
- Status 401 = Credentials are wrong

If this **works outside WordPress** but **fails in the plugin**, there may be a server configuration issue.

### Test 2: Check WordPress HTTP Functions

Some servers block outgoing HTTP requests. Test this:

```php
// Add to functions.php temporarily
add_action('admin_init', function() {
    if (isset($_GET['test_http'])) {
        $response = wp_remote_get('https://api.thinkific.com/api/public/v1/');
        
        if (is_wp_error($response)) {
            wp_die('HTTP Error: ' . $response->get_error_message());
        }
        
        wp_die('HTTP works! Status: ' . wp_remote_retrieve_response_code($response));
    }
});

// Visit: yoursite.com/wp-admin/?test_http
```

If this fails, your server is blocking HTTP requests. Contact your host.

### Test 3: Updated Plugin Test Connection

With the updated code:

1. Go to **Thinkific > Settings**
2. Click **Test Connection**
3. Check the result
4. Go to **Thinkific > Logs**
5. Look at the latest entries

The logs will now show:
- Cleaned subdomain
- API key length (not the key itself)
- Whether key is present
- Full error response from Thinkific

## Fresh Start Procedure

If all else fails, start from scratch:

### 1. Delete Old Settings
```sql
-- In phpMyAdmin or database tool
DELETE FROM wp_options WHERE option_name LIKE 'thinkific_wp_%';
```

Or use WordPress admin:
```
Deactivate plugin → Reactivate plugin
```

### 2. Get New API Key
```
1. Thinkific admin
2. Delete old API key
3. Create new API key
4. Copy it immediately
5. Save in secure location
```

### 3. Configure Fresh
```
1. Thinkific > Settings
2. Subdomain: [just the name]
3. API Key: [paste fresh key]
4. Leave API Base URL as default
5. Save Settings
6. Test Connection
```

### 4. Check Logs
```
Thinkific > Logs
Look for DEBUG entries showing:
- Request details
- Response details
```

## Understanding the Logs

### Good Request Log (Success)
```
[DEBUG] API Request: GET https://api.thinkific.com/api/public/v1/users?limit=1
Context:
  - subdomain: "yourschool"
  - api_key_length: 32
  
[DEBUG] API Response: 200
Context:
  - data: {"items": [...]}
```

### Bad Request Log (401)
```
[ERROR] API Authentication Failed (401)
Context:
  - subdomain: "yourschool"
  - api_key_present: true
  - api_key_length: 32
  - response: "Invalid API credentials"
```

## Contact Thinkific Support

If you've tried everything and still get 401:

**What to tell Thinkific:**
```
"I'm trying to use the Thinkific Public API v1 from my WordPress site.

My subdomain is: [yoursubdomain]
I'm getting 401 errors when calling any endpoint.

I've verified:
- API key is copied correctly
- Subdomain is correct
- Using headers: X-Auth-API-Key and X-Auth-Subdomain
- API key has Read/Write permissions for Users and Enrollments

Can you verify:
1. Is API access enabled for my account?
2. Is there any IP blocking or restrictions?
3. Are my API credentials active?

Thank you!"
```

## Server-Side Issues

Some hosting providers block or modify HTTP requests:

### Check with Your Host
1. **Cloudflare**: May block API requests (whitelist Thinkific's IPs)
2. **ModSecurity**: May block POST requests (disable for /wp-admin/admin-ajax.php)
3. **Firewall**: May block outgoing HTTPS (whitelist api.thinkific.com)
4. **Proxy**: May modify headers (contact host)

### Quick Host Test
```php
// Add to functions.php
add_action('wp_ajax_test_thinkific_direct', function() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.thinkific.com/api/public/v1/users?limit=1');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Auth-API-Key: YOUR_KEY',
        'X-Auth-Subdomain: yoursubdomain',
        'Content-Type: application/json'
    ));
    
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    wp_die("Status: $status | Response: $response");
});

// Visit: yoursite.com/wp-admin/admin-ajax.php?action=test_thinkific_direct
```

## Summary Checklist

Before asking for help, verify:

- [ ] API key is fresh and copied correctly
- [ ] Subdomain has no extra characters or spaces
- [ ] API key has correct permissions in Thinkific
- [ ] Tested outside WordPress (curl/Postman)
- [ ] Checked plugin logs (Thinkific > Logs)
- [ ] Tried Test Connection button
- [ ] Server allows HTTPS requests to external APIs
- [ ] No firewall/security blocking Thinkific's API
- [ ] Thinkific account is active and in good standing

## Need More Help?

1. **Check logs**: Thinkific > Logs (most detailed info)
2. **Review**: [SETUP.md](SETUP.md) for correct setup
3. **Test**: Use curl command above to isolate issue
4. **Contact**: Thinkific support if credentials verified correct

---

**This troubleshooting guide is specifically for 401 authentication errors. For other issues, see [README.md#troubleshooting](README.md#troubleshooting).**
