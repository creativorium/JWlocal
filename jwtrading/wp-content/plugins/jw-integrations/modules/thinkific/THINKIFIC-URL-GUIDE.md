# Thinkific URL Guide - Finding the Right Course URL

## ⚠️ Common Problem: Product Page vs Course Page

Many users accidentally use the **product/sales page URL** instead of the **course player URL**.

---

## 🎯 **3 Ways to Get the Correct Course URL**

### **Method 1: Add "take/" to URL (Easiest)**

Take your current URL and add `take/` after `courses/`:

**Your current URL (wrong):**
```
https://jw-ict-bootcamp.thinkific.com/products/courses/smart-money-trading-bootcamp
```

**Fix it like this:**
```
1. Remove /products/ part
2. Add /take/ after /courses/

Result: https://jw-ict-bootcamp.thinkific.com/courses/take/smart-money-trading-bootcamp
```

**Pattern:**
```
https://[subdomain].thinkific.com/courses/take/[course-slug]
```

---

### **Method 2: Use Enrollments Dashboard (Universal Solution)**

**Single URL for ALL courses:**
```
https://jw-ict-bootcamp.thinkific.com/enrollments
```

**How it works:**
1. Student clicks "Continue Course"
2. Opens Thinkific enrollments page
3. Shows all their enrolled courses
4. They click the course they want
5. Course opens

**When to use:**
- ✅ You have many courses
- ✅ Want one URL for all mappings
- ✅ Course-specific URLs keep redirecting

---

### **Method 3: Get URL as Enrolled Student**

**Most reliable method:**

1. **Enroll yourself manually in Thinkific**
   - Thinkific Admin → Users → Your Email → Enroll in course

2. **Log into Thinkific as student**
   - Go to: `https://jw-ict-bootcamp.thinkific.com`
   - Log in

3. **Access the course**
   - Click "My Courses" or go to `/enrollments`
   - Click on your course

4. **Copy URL from browser**
   - Should be something like:
   - `https://jw-ict-bootcamp.thinkific.com/courses/smart-money-trading-bootcamp/lessons/[lesson-id]`
   
5. **Use base course URL** (remove `/lessons/...` part):
   ```
   https://jw-ict-bootcamp.thinkific.com/courses/smart-money-trading-bootcamp
   ```

---

## 📊 **URL Types Explained**

### **❌ Product/Sales Page (DON'T USE)**
```
URL Pattern:
https://jw-ict-bootcamp.thinkific.com/products/courses/[slug]
                                       ^^^^^^^^^
                                       /products/ = SALES PAGE

What it shows:
- Course description
- Price: $31.00
- "Buy Now" or "Enroll" button
- Marketing content

Who sees it:
- Anyone (public or logged in)
- Always shows purchase option

Problem:
- Shows "Buy" even if already enrolled!
```

---

### **✅ Course Player Page (USE THIS)**
```
URL Pattern:
https://jw-ict-bootcamp.thinkific.com/courses/[slug]
OR
https://jw-ict-bootcamp.thinkific.com/courses/take/[slug]
                                                    ^^^^^
                                                    /take/ forces player

What it shows:
- Course lessons/curriculum
- Video player
- Progress tracking
- Navigation

Who sees it:
- Enrolled users only
- If not enrolled, shows enrollment prompt

Perfect for:
- Direct course access
- Enrolled students
```

---

### **✅ Enrollments Dashboard (UNIVERSAL)**
```
URL Pattern:
https://jw-ict-bootcamp.thinkific.com/enrollments

What it shows:
- List of all enrolled courses
- Progress for each
- "Continue" buttons

Who sees it:
- Logged-in users
- Only shows their courses

Perfect for:
- Multiple courses
- One link for everything
- Simple solution
```

---

## 🎯 **Your Specific Case**

**Your course:** Smart Money Trading Bootcamp

### **Try These URLs in Order:**

**Option 1: Try /take/ URL**
```
https://jw-ict-bootcamp.thinkific.com/courses/take/smart-money-trading-bootcamp
```

**Option 2: Try standard /courses/ URL**
```
https://jw-ict-bootcamp.thinkific.com/courses/smart-money-trading-bootcamp
```

**Option 3: Use enrollments dashboard**
```
https://jw-ict-bootcamp.thinkific.com/enrollments
```

**One of these WILL work!**

---

## 🔧 **Quick Test Method**

### **Test Each URL:**

**While logged into Thinkific as enrolled user:**

1. Open browser
2. Paste URL
3. Press Enter
4. Check what loads

**What you want to see:**
- ✅ Course player with lessons
- ✅ Video or curriculum
- ✅ "Start learning" or lesson list

**What you DON'T want:**
- ❌ "Enroll Now" button
- ❌ Price displayed
- ❌ Marketing description

---

## 💡 **Thinkific Course Access Settings**

The redirect might be due to Thinkific settings. Check this:

### **In Thinkific Admin:**

```
1. Go to your course: Manage Learning Content → Courses → [Your Course]

2. Click: Settings (or Course Settings)

3. Look for section: "Course Landing Page" or "Course Access"

4. Check if there's an option like:
   - "Redirect to product page"
   - "Show product page first"
   - "Landing page type"

5. Change to: "Direct access" or "Course player" or "Learning page"

6. Save
```

This should stop the redirect!

---

## 🎯 **Recommended Solution for Your Case**

Based on your issue, I recommend using the **Enrollments Dashboard** approach:

### **Update All Mappings to Use One URL:**

```
Thinkific → Course Mapping

For EACH mapping:
Course URL: https://jw-ict-bootcamp.thinkific.com/enrollments
```

**Why this works:**
- ✅ Never shows product page
- ✅ Always shows enrolled courses
- ✅ Works for any course
- ✅ No redirect issues
- ✅ Thinkific handles the rest

**Customer experience:**
```
1. Clicks "Continue Course" on WordPress
2. Opens Thinkific enrollments page
3. Sees: "Smart Money Trading Bootcamp" with progress
4. Clicks course
5. Opens directly to course player
```

It's **one extra click**, but it's reliable and always works!

---

## 🔍 **Alternative: Find Direct Lesson URL**

If you want TRULY direct access:

### **Get First Lesson URL:**

1. **Enroll yourself in the course** (in Thinkific admin)

2. **Open course as student:**
   - Go to: `https://jw-ict-bootcamp.thinkific.com/enrollments`
   - Click your course
   - Click first lesson

3. **Copy URL from browser:**
   ```
   Example: https://jw-ict-bootcamp.thinkific.com/courses/smart-money-trading-bootcamp/lessons/12345678
   ```

4. **Use this URL in mapping!**

This goes DIRECTLY to the course player.

---

## 📋 **Testing Your URLs**

**Test each URL while logged in as enrolled student:**

### **Test 1:**
```
URL: https://jw-ict-bootcamp.thinkific.com/enrollments
Opens: Enrollments dashboard ✅
Shows: All enrolled courses
```

### **Test 2:**
```
URL: https://jw-ict-bootcamp.thinkific.com/courses/take/smart-money-trading-bootcamp
Opens: Course player ✅
Shows: Course lessons
```

### **Test 3:**
```
URL: https://jw-ict-bootcamp.thinkific.com/courses/smart-money-trading-bootcamp/lessons/[first-lesson-id]
Opens: Specific lesson ✅
Shows: Video player directly
```

**Pick whichever works!**

---

## 🎯 **My Recommendation**

For your specific case, use **enrollments dashboard** for all courses:

### **Why:**
- ✅ Simple - one URL for all courses
- ✅ Reliable - never redirects
- ✅ Clean UX - shows progress
- ✅ No configuration needed
- ✅ Works immediately

### **Updated Mapping:**

```
WooCommerce Product: JW - ICT Bootcamp (ID: 684)
Course Name: Smart Money Trading Bootcamp
Course URL: https://jw-ict-bootcamp.thinkific.com/enrollments
Course ID: 2494845
Description: Panduan Lengkap Untuk Trading ICT...
```

### **Customer Experience:**

```
WordPress Dashboard:
┌─────────────────────────────────┐
│ Smart Money Trading Bootcamp    │
│ [Continue Course]               │
└─────────────────────────────────┘
         ↓ (clicks)
         
Thinkific Enrollments Page:
┌─────────────────────────────────┐
│ My Courses                      │
│                                 │
│ Smart Money Trading Bootcamp    │
│ Progress: 15%                   │
│ [Continue Course]               │
└─────────────────────────────────┘
         ↓ (clicks)
         
Course Player Opens:
┌─────────────────────────────────┐
│ Lesson 1.1: Candlesticks       │
│ [Video Player]                  │
│ ✅ Learning!                    │
└─────────────────────────────────┘
```

**Two clicks total - very reasonable and always works!** ✅

---

## 🔧 **Quick Action**

**Do this right now:**

1. Go to: **Thinkific → Course Mapping**
2. Edit your mapping
3. Change Course URL to: `https://jw-ict-bootcamp.thinkific.com/enrollments`
4. Save
5. Go to WordPress My Courses page
6. Click "Continue Course"
7. Should work perfectly now! 🎉

---

**TL;DR: Use `/enrollments` as your Course URL - it's universal, reliable, and always works without any redirects!** ✅