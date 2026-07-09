# ⚠️ IMPORTANT: Correct API Key Type

## 🚨 You MUST Use the Private API Key

### ✅ CORRECT: Private API Key (Long Format)
- **What it looks like**: Long alphanumeric string (50-100+ characters)
- **Example**: `abc123def456ghi789jkl012mno345pqr678stu901vwx234yz...`
- **Format**: Single line, no dots, just letters and numbers
- **Used for**: Admin API calls (what this plugin needs)

### ❌ WRONG: API Access Token (JWT/SSO Format)
- **What it looks like**: Three parts separated by dots
- **Example**: `eyJhbGc...eyJpc3M...YgiPCN...`
- **Format**: `xxxxx.yyyyy.zzzzz` (three parts with dots)
- **Used for**: SSO (Single Sign-On) ONLY - NOT for Admin API!

---

## 📍 Where to Find Your Private API Key

### Step-by-Step:

1. **Log into your Thinkific Admin**
   - Go to your Thinkific site
   - Log in as admin

2. **Navigate to Settings**
   - Click **Settings** in the left menu
   - Click **Code & Analytics**

3. **Scroll to API Keys Section**
   - Scroll down the page
   - Find the section titled **"API Keys"** or **"API & Webhooks"**

4. **Copy the PRIVATE API KEY**
   - You'll see two types of keys:
     - **API Access Token** (JWT - has dots) ❌ DON'T USE THIS
     - **Private API Key** (long string - no dots) ✅ USE THIS ONE
   - Click to reveal/copy the **Private API Key**

5. **Paste into WordPress**
   - Go to: **WordPress Admin → Thinkific → Settings**
   - Paste into **API Key** field
   - Enter your subdomain (e.g., "yourschool" from yourschool.thinkific.com)
   - Click **Save Changes**

---

## 🔍 How to Tell If You Have the Right Key

### Check Your API Key Format:

**Does it have 2 dots (periods) in it?**
- ❌ **YES, it has dots** = JWT Token (WRONG - used for SSO)
- ✅ **NO dots** = Private API Key (CORRECT - used for Admin API)

### In WordPress Settings:

After pasting your key, you'll see:
- ✅ **Green Success Box**: "Correct Format - Private API Key Detected"
- ❌ **Red Error Box**: "WRONG TOKEN TYPE - JWT/SSO Token Detected"

---

## 🎯 Why This Matters

### If You Use JWT Token (Wrong):
- ❌ All API calls will fail with 401 errors
- ❌ Connection test will fail
- ❌ Course enrollments won't work
- ❌ Dashboard won't show courses
- ❌ Plugin basically won't work at all

### If You Use Private API Key (Correct):
- ✅ Connection test passes
- ✅ API calls work
- ✅ Enrollments happen automatically
- ✅ Dashboard shows courses
- ✅ Everything works perfectly!

---

## 📸 Visual Guide

### WRONG Key (JWT - Has Dots):
```
eyJraWQiOiI4MGQwZjQzMmQ2OGM3M2I5ODkxOWYyZjU5ZTI5Z...
     ^                                                ^
   (Part 1)                                       (Part 2.Part 3)
```
**Has 2 dots** = Wrong token type!

### CORRECT Key (Private API Key - No Dots):
```
a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z...
```
**No dots** = Correct token type!

---

## 🔧 Quick Fix Steps

If you're getting 401 errors or connection failures:

1. **Check your current key**
   - Go to: **Thinkific → Settings**
   - Look at the API Key field
   - See the message below it

2. **If it says "WRONG TOKEN TYPE":**
   - Follow the steps above to get your Private API Key
   - Replace the JWT token with the Private API Key
   - Make sure subdomain is filled in
   - Click **Save Changes**
   - Click **Test Connection** - should be green now!

3. **If it says "Correct Format":**
   - Your API key is correct
   - If still getting errors, check subdomain field
   - Try clicking **Test Connection**

---

## 📋 Configuration Checklist

- [ ] I have logged into Thinkific Admin
- [ ] I went to Settings → Code & Analytics
- [ ] I found the API Keys section
- [ ] I copied the **Private API Key** (NOT the API Access Token)
- [ ] My API key has NO dots in it (not JWT format)
- [ ] I pasted it into WordPress → Thinkific → Settings
- [ ] I entered my subdomain (e.g., "yourschool")
- [ ] WordPress shows "Correct Format" in green
- [ ] I clicked Test Connection and it passed

---

## 🆘 Still Having Issues?

### Check These:

1. **Subdomain Field**: Must be filled in (e.g., "yourschool")
2. **API Key**: Must be Private API Key (no dots)
3. **Thinkific Plan**: Must be Growth plan or higher
4. **API Key Status**: Must be active in Thinkific (not revoked)

### Get Help:

1. Check logs: **Admin → Thinkific → Logs**
2. Read guide: **HOW-TO-GET-API-KEY.md**
3. Review auth guide: **AUTHENTICATION-GUIDE.md**
4. Quick fix guide: **QUICK-FIX-401.md**

---

## 📖 Related Documentation

- **HOW-TO-GET-API-KEY.md** - Detailed screenshots and guide
- **AUTHENTICATION-GUIDE.md** - Difference between JWT and Private API Key
- **QUICK-FIX-401.md** - Fix 401 authentication errors
- **TROUBLESHOOTING-401.md** - Complete troubleshooting guide

---

## ✅ Success!

Once you have the correct Private API Key configured:
- Connection test will pass ✅
- Courses will sync ✅
- Enrollments will work ✅
- Dashboard will show courses ✅
- Everything will work! 🎉

---

**Remember**: Always use **Private API Key** (no dots), never use **API Access Token** (JWT with dots)!
