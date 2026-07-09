# How to Get Your Thinkific Private API Key

## The Issue

Your current JWT token (API Access Token) is designed for **SSO (Single Sign-On)**, not for Admin API calls.

For the Admin API endpoints (`/users`, `/enrollments`, `/courses`), you need a **Private API Key**.

## Step-by-Step: Get Your Private API Key

### 1. Log into Thinkific Admin

Go to your Thinkific admin dashboard:
```
https://jw-ict-bootcamp.thinkific.com/manage
```

### 2. Navigate to Settings

Click **Settings** in the left sidebar

### 3. Go to Code & Analytics

Click **Code & Analytics** tab

### 4. Find API & Webhooks Section

Scroll down to find **"API & Webhooks"**

### 5. Locate Private API Key

Look for a section that says:
- **"Private API Key"** or
- **"API Key"** or  
- **"Generate API Key"**

It should look something like:
```
Private API Key
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
[Show] or [Generate]

Your API key: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
```

### 6. Copy the Key

- If you see a key already, click **"Show"** or **"Copy"**
- If there's no key, click **"Generate New Key"**
- Copy the key (it's usually 32-64 characters, alphanumeric)

**Important:** This is NOT the same as the JWT "API Access Token" you currently have!

### 7. What the Private API Key Looks Like

```
Correct (Private API Key):
a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0

Wrong (JWT Token - what you have now):
eyJraWQiOiI4MGQwZjQzMmQ2OGM3M2I5ODkxOWYyZjU5ZTI5ZTNiMDk4ZjViNDg0ZmY1ODRkNWQ5ZGYwODhjNWZhMGU3NWJiIiwiYWxnIjoiUlMyNTYifQ...
```

## Setup in WordPress

Once you have your Private API Key:

### 1. Go to WordPress Admin
```
WordPress Admin → Thinkific → Settings
```

### 2. Enter Your Credentials

**Subdomain:**
```
jw-ict-bootcamp
```

**API Key:** (paste your Private API Key)
```
a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0
```
(Example - use your actual key!)

### 3. Save and Test

1. Click **"Save Settings"**
2. Click **"Test Connection"**
3. Should see: ✅ **"Connection successful!"**

## Why This Is Different

| Feature | JWT Token (what you have) | Private API Key (what you need) |
|---------|---------------------------|----------------------------------|
| **Purpose** | SSO (Single Sign-On) | Admin API calls |
| **Format** | Very long JWT (500+ chars) | Short alphanumeric (32-64 chars) |
| **Contains** | User info, subdomain, expiry | Just the key |
| **Used for** | Logging users into Thinkific | Creating users, enrollments, etc. |
| **Header** | `Authorization: Bearer` | `X-Auth-API-Key` |

## Authentication Headers Comparison

### Your Current JWT (Won't Work)
```http
GET /api/public/v1/users?limit=1
Authorization: Bearer eyJraWQiOiI4MGQwZjQzMmQ2OGM3M2I...
```
❌ Returns 401 - JWT is for SSO, not Admin API

### Private API Key (Will Work)
```http
GET /api/public/v1/users?limit=1
X-Auth-API-Key: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
X-Auth-Subdomain: jw-ict-bootcamp
```
✅ Returns 200 - Correct authentication for Admin API

## If You Can't Find Private API Key

### Option A: Contact Thinkific Support

If you don't see "Private API Key" in your admin:

1. Go to Thinkific support
2. Ask: "How do I access my Private API Key for the Admin API?"
3. Explain: "I need to call the Admin API endpoints (users, enrollments) programmatically"

### Option B: Check Your Plan

Some Thinkific plans may not have API access. Verify:
- Growth plan and above typically have API access
- If not visible, contact support to enable it

### Option C: Alternative Location

Some Thinkific admins have the API key in:
```
Settings → Advanced Settings → API
```
or
```
Apps → Private Apps → Create Private App
```

## Testing Your New Key

### Test in Terminal (cURL)
```bash
curl -X GET "https://api.thinkific.com/api/public/v1/users?limit=1" \
  -H "X-Auth-API-Key: YOUR_PRIVATE_API_KEY_HERE" \
  -H "X-Auth-Subdomain: jw-ict-bootcamp" \
  -H "Content-Type: application/json"
```

**Expected result:**
```json
{
  "items": [
    {
      "id": 12345,
      "email": "user@example.com",
      ...
    }
  ]
}
```

### Test in WordPress

1. Enter key in WordPress settings
2. Click "Test Connection"
3. Check Thinkific → Logs
4. Should see status 200

## Important Notes

⚠️ **Security:**
- Keep your Private API Key secret
- Don't commit it to version control
- Don't share it publicly
- It has full access to your Thinkific data

✅ **The plugin is already updated:**
- It detects whether you're using JWT or Private API Key
- It automatically uses the correct authentication headers
- You just need to paste the right type of key!

## Summary

1. ❌ **What you have:** JWT "API Access Token" (for SSO)
2. ✅ **What you need:** "Private API Key" (for Admin API)
3. 📍 **Where to get it:** Thinkific Admin → Settings → Code & Analytics → API & Webhooks
4. 🔧 **How to use it:** Paste in WordPress → Thinkific → Settings → API Key field

Once you paste the Private API Key, everything will work! 🎉
