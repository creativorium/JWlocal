# ⚠️ QUICK FIX for 401 Authentication Error

## The Problem

Your JWT token (API Access Token) is designed for **SSO**, not for calling the Admin API.

```
❌ What you have: eyJraWQiOiI4MGQw... (JWT for SSO)
✅ What you need: Private API Key (for Admin API)
```

---

## 🔧 Quick Fix (5 minutes)

### Step 1: Get Private API Key from Thinkific

1. **Log into Thinkific Admin**
   ```
   https://jw-ict-bootcamp.thinkific.com/manage
   ```

2. **Go to Settings** (left sidebar)

3. **Click "Code & Analytics"** tab

4. **Scroll to "API & Webhooks"**

5. **Find "Private API Key"** section

6. **Click "Show" or "Generate"**

7. **Copy the key** (looks like: `a1b2c3d4e5f6g7h8...`)

### Step 2: Update WordPress

1. **Go to WordPress**
   ```
   WordPress Admin → Thinkific → Settings
   ```

2. **Enter Subdomain**
   ```
   jw-ict-bootcamp
   ```

3. **Paste Private API Key**
   ```
   a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
   ```
   (Use your actual key!)

4. **Save Settings**

5. **Click "Test Connection"**

6. **Should see:** ✅ Success!

---

## 📊 The Difference

| Type | Current (JWT) | Needed (Private API Key) |
|------|---------------|--------------------------|
| **Length** | 500+ characters | 32-64 characters |
| **Starts with** | `eyJ...` | Letters/numbers |
| **Purpose** | SSO (logging in) | API calls |
| **Works with Admin API?** | ❌ NO | ✅ YES |

---

## 🎯 Visual Guide

### What you'll see in Thinkific Admin:

```
Settings > Code & Analytics > API & Webhooks

┌─────────────────────────────────────┐
│  API Access Token                   │
│  eyJraWQiOiI4MGQw...                │  ← This is what you copied (for SSO)
│  [Copy Token]                       │
└─────────────────────────────────────┘

                 ↓ Scroll down ↓

┌─────────────────────────────────────┐
│  Private API Key                    │
│  a1b2c3d4e5f6g7h8i9j0               │  ← This is what you NEED!
│  [Show Key] [Regenerate]            │
└─────────────────────────────────────┘
```

---

## ✅ How to Verify You Got It Right

### Your Private API Key should:
- ✅ Be 32-64 characters long
- ✅ Be alphanumeric (letters and numbers)
- ✅ NOT start with `eyJ`
- ✅ NOT have dots (`.`) in it

### Test with cURL (optional):
```bash
curl -X GET "https://api.thinkific.com/api/public/v1/users?limit=1" \
  -H "X-Auth-API-Key: YOUR_KEY_HERE" \
  -H "X-Auth-Subdomain: jw-ict-bootcamp"
```

**Should return 200 with user data!**

---

## 🆘 If You Can't Find "Private API Key"

### Check These Locations:

1. **Settings → Code & Analytics → API & Webhooks**
2. **Settings → Advanced Settings → API**
3. **Apps → Private Apps**

### Still Not There?

**Contact Thinkific Support:**
```
Subject: Need Private API Key for Admin API Access

Message:
"Hi, I need to access the Thinkific Admin API programmatically 
to create users and enrollments. Where can I find or generate 
my Private API Key? I'm on the Growth plan."
```

---

## 🎉 After You Get It Working

Once you paste the Private API Key and test successfully:

1. ✅ Test Connection should work
2. ✅ Logs will show status 200
3. ✅ You can start creating course mappings
4. ✅ Orders will auto-enroll students

---

## 📚 More Help

- **Detailed Guide**: [HOW-TO-GET-API-KEY.md](HOW-TO-GET-API-KEY.md)
- **Authentication Explained**: [AUTHENTICATION-GUIDE.md](AUTHENTICATION-GUIDE.md)
- **General Troubleshooting**: [TROUBLESHOOTING-401.md](TROUBLESHOOTING-401.md)

---

**TL;DR:** You grabbed the SSO token by mistake. Get the "Private API Key" instead from the same page in Thinkific admin (scroll down a bit). It's shorter and alphanumeric. 🔑
