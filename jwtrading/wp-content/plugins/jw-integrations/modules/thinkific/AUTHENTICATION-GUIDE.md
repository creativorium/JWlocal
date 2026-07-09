# Thinkific Authentication Guide

## Two Authentication Methods Supported

This plugin supports **both** Thinkific authentication methods:

### 1. API Access Token (JWT Bearer) - **RECOMMENDED** ✅

**What it looks like:**
```
eyJraWQiOiI4MGQwZjQzMmQ2OGM3M2I5ODkxOWYyZjU5ZTI5ZTNiMDk4ZjViNDg0ZmY1ODRkNWQ5ZGYwODhjNWZhMGU3NWJiIiwiYWxnIjoiUlMyNTYifQ.eyJpc3MiOiJodHRwczovL2NvdXJzZXMudGhpbmtpZmljLmNvbSIsImF1ZCI6ImFwaS50aGlua2lmaWMuY29tIiwiZXhwIjoxNzg2ODQ0MzI1LCJpYXQiOjE3NzEyOTIzMjUsImp0aSI6ImU1OGY3MmFkLTY4NjUtNGYyMy1hM2RkLWM3YTJmYmNhNGQ0MSIsInN1YmRvbWFpbiI6Imp3LWljdC1ib290Y2FtcCIsImNvbnN1bWVyIjoianctaWN0LWJvb3RjYW1wIiwidHlwZSI6ImFwaV9hY2Nlc3NfdG9rZW4iLCJzY29wZSI6IndyaXRlOmFsbCJ9.YgiPCNRXDfGBNuSbc5YS2tGilRDtfwdveIwm8Gddbo0...
```

**Characteristics:**
- Starts with `eyJ`
- Contains exactly 2 dots (3 parts)
- Very long (400-600 characters)
- Contains subdomain embedded in the token
- Uses `Authorization: Bearer` header
- **Modern authentication method**

**How to get it:**
1. Log into Thinkific admin
2. Go to **Settings** > **Code & Analytics** > **API & Webhooks**
3. Look for **"API Access Token"** section
4. Copy the token (starts with eyJ...)
5. Paste into WordPress: Thinkific > Settings > API Key field

**Advantages:**
- ✅ More secure (JWT with expiration)
- ✅ Subdomain embedded (don't need to enter separately)
- ✅ More modern authentication
- ✅ Recommended by Thinkific

**Setup in WordPress:**
```
Thinkific > Settings
- Subdomain: [LEAVE BLANK - auto-detected from token]
- API Key: [paste your full JWT token]
- Save Settings
```

### 2. Private API Key - **LEGACY**

**What it looks like:**
```
abc123def456ghi789jkl012mno345pqr678
```

**Characteristics:**
- Short (32-64 characters)
- Alphanumeric string
- No dots
- Requires separate subdomain field
- Uses `X-Auth-API-Key` + `X-Auth-Subdomain` headers
- **Older authentication method**

**How to get it:**
1. Log into Thinkific admin
2. Go to **Settings** > **Code & Analytics** > **API & Webhooks**
3. Click **"Create New API Key"**
4. Copy the key
5. Also note your subdomain

**Advantages:**
- ✅ Still fully supported
- ✅ Simpler to understand
- ✅ Works with older Thinkific accounts

**Setup in WordPress:**
```
Thinkific > Settings
- Subdomain: yourschool [REQUIRED]
- API Key: abc123def456... [paste your API key]
- Save Settings
```

---

## Which Should You Use?

### Use API Access Token (JWT) if:
- ✅ You see it in your Thinkific admin under "API Access Token"
- ✅ You want the most secure method
- ✅ You don't want to manually enter subdomain

### Use Private API Key if:
- ✅ JWT token is not available in your Thinkific admin
- ✅ Your Thinkific plan doesn't support JWT tokens
- ✅ You prefer the simpler legacy method

---

## How the Plugin Detects Authentication Method

The plugin automatically detects which method you're using:

```php
// If token contains 2 dots, it's a JWT
if (substr_count($api_key, '.') === 2) {
    // Use Bearer token authentication
    headers['Authorization'] = 'Bearer ' . $token;
} else {
    // Use legacy API key authentication
    headers['X-Auth-API-Key'] = $api_key;
    headers['X-Auth-Subdomain'] = $subdomain;
}
```

**You don't need to configure anything - it's automatic!**

---

## Setup Examples

### Example 1: Using JWT (Your Case)

**What you have:**
```
Token: eyJraWQiOiI4MGQwZjQzMmQ2OGM3M2I5ODkxOWYyZjU5ZTI5ZTNiMDk4ZjViNDg0ZmY1ODRkNWQ5ZGYwODhjNWZhMGU3NWJiIiwiYWxnIjoiUlMyNTYifQ.eyJpc3MiOiJodHRwczovL2NvdXJzZXMudGhpbmtpZmljLmNvbSIsImF1ZCI6ImFwaS50aGlua2lmaWMuY29tIiwiZXhwIjoxNzg2ODQ0MzI1LCJpYXQiOjE3NzEyOTIzMjUsImp0aSI6ImU1OGY3MmFkLTY4NjUtNGYyMy1hM2RkLWM3YTJmYmNhNGQ0MSIsInN1YmRvbWFpbiI6Imp3LWljdC1ib290Y2FtcCIsImNvbnN1bWVyIjoianctaWN0LWJvb3RjYW1wIiwidHlwZSI6ImFwaV9hY2Nlc3NfdG9rZW4iLCJzY29wZSI6IndyaXRlOmFsbCJ9.YgiPCNRXDfGBNuSbc5YS2tGilRDtfwdveIwm8Gddbo0...
Subdomain: jw-ict-bootcamp (embedded in token)
```

**WordPress Setup:**
```
1. Thinkific > Settings
2. Subdomain: [LEAVE BLANK or type jw-ict-bootcamp]
3. API Key: [paste the ENTIRE JWT token above]
4. Save Settings
5. Test Connection
```

**What happens:**
- Plugin detects JWT (has 2 dots)
- Extracts subdomain from token: "jw-ict-bootcamp"
- Uses Bearer authentication
- ✅ Should work!

### Example 2: Using Legacy API Key

**What you have:**
```
API Key: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
Subdomain: myschool
```

**WordPress Setup:**
```
1. Thinkific > Settings
2. Subdomain: myschool [REQUIRED]
3. API Key: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
4. Save Settings
5. Test Connection
```

**What happens:**
- Plugin detects traditional API key (no dots)
- Uses subdomain from field: "myschool"
- Uses legacy authentication with headers
- ✅ Should work!

---

## Troubleshooting

### "Authentication Error" with JWT

**Check:**
1. Token is complete (not truncated)
2. Token hasn't expired (check `exp` in payload)
3. Token has `write:all` scope
4. No extra spaces before/after token

**How to verify token:**
```bash
# Decode the payload (middle part between dots)
echo "eyJpc3MiOi..." | base64 -d

# Should show:
{
  "subdomain": "your-school",
  "scope": "write:all",
  "exp": 1786844325,
  ...
}
```

### "Authentication Error" with Legacy Key

**Check:**
1. API key is correct (no typos)
2. Subdomain matches exactly
3. API key hasn't been deleted in Thinkific
4. API key has correct permissions

---

## Testing Your Setup

### WordPress Test Connection
```
1. Thinkific > Settings
2. Scroll down
3. Click "Test Connection"
4. Should show green success
```

### View Diagnostic Info
```
1. Thinkific > Settings
2. Look at "Current Configuration" box
3. Shows:
   - Authentication Type: API Access Token (JWT Bearer) or Private API Key
   - Subdomain being used
   - Token length
```

### Check Logs
```
1. Thinkific > Logs
2. Look for DEBUG entry showing request
3. Shows authentication method being used
```

### Advanced Test (test-credentials.php)
```
1. Upload test-credentials.php to WordPress root
2. Visit: yoursite.com/test-credentials.php?test=1
3. See detailed test results
4. DELETE FILE after testing
```

---

## API Endpoints Used

Both authentication methods call the same Thinkific API endpoints:

```
GET  /api/public/v1/users
POST /api/public/v1/users
GET  /api/public/v1/enrollments
POST /api/public/v1/enrollments
GET  /api/public/v1/courses
```

The only difference is the HTTP headers:

**JWT Authentication:**
```
Authorization: Bearer eyJraWQiOiI4MGQw...
Content-Type: application/json
```

**Legacy Authentication:**
```
X-Auth-API-Key: a1b2c3d4e5f6...
X-Auth-Subdomain: yourschool
Content-Type: application/json
```

---

## FAQ

**Q: Can I use both authentication methods?**  
A: No, use one or the other. The plugin auto-detects based on your API key.

**Q: Which is more secure?**  
A: JWT tokens are more secure (time-limited, cryptographically signed).

**Q: Can I switch between methods?**  
A: Yes, just paste a different key in the API Key field.

**Q: Do I need to change my code?**  
A: No, the plugin handles both automatically.

**Q: My JWT token is really long, is that normal?**  
A: Yes! JWT tokens are typically 400-600 characters. That's expected.

**Q: Can I see my subdomain in the JWT?**  
A: Yes! The plugin extracts and displays it in Settings > Current Configuration.

**Q: What if subdomain field doesn't match JWT?**  
A: The plugin will use the JWT subdomain and show a warning. You can leave the field blank.

**Q: My token has newlines when I paste it**  
A: That's fine! The plugin trims whitespace automatically.

---

## Your Specific Setup

Based on your token, here's what to do:

```
✅ You have: API Access Token (JWT Bearer)
✅ Subdomain: jw-ict-bootcamp (embedded in token)
✅ Authentication: Modern Bearer method
```

**Setup Steps:**
1. WordPress Admin → Thinkific → Settings
2. **Subdomain:** Leave blank OR type `jw-ict-bootcamp`
3. **API Key:** Paste your FULL JWT token (the entire eyJraWQi... string)
4. **Save Settings**
5. **Test Connection** - should now work!

The plugin will:
- ✅ Detect it's a JWT (has 2 dots)
- ✅ Extract subdomain automatically
- ✅ Use Bearer authentication
- ✅ Call Thinkific API with correct headers

**Should now work! 🎉**

---

## Need Help?

1. Check **Current Configuration** box in Thinkific > Settings
2. Click **Test Connection**
3. View **Thinkific > Logs** for details
4. See [TROUBLESHOOTING-401.md](TROUBLESHOOTING-401.md) for 401 errors
