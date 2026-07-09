# 🚀 Quick Start Checklist

**Use this checklist to set up your seamless course delivery system.**

---

## ✅ Phase 1: Initial Setup (Already Done!)

- [x] Plugin installed and activated
- [x] WooCommerce active
- [x] API credentials configured
- [x] Connection test passed

---

## ✅ Phase 2: Create Products (15 minutes)

### For Each Course You Want to Sell:

- [ ] **Create WooCommerce Product**
  - [ ] Go to: Products → Add New
  - [ ] Product Name: _______________________
  - [ ] Price: $_______
  - [ ] Type: Simple/Virtual
  - [ ] Sold Individually: YES
  - [ ] Publish
  - [ ] Note Product ID: _______

### Product IDs:
```
Product 1: _______ (Name: ___________________)
Product 2: _______ (Name: ___________________)
Product 3: _______ (Name: ___________________)
```

---

## ✅ Phase 3: Get Thinkific Course Info (10 minutes)

### For Each Course:

- [ ] **Log into Thinkific Admin**
  ```
  https://jw-ict-bootcamp.thinkific.com/manage
  ```

- [ ] **Go to: Manage Learning Content → Courses**

- [ ] **Get Course Details:**

### Course 1:
```
Course Name: _______________________
Course URL: https://jw-ict-bootcamp.thinkific.com/courses/__________
Course ID (optional): _______
WooCommerce Product ID: _______
```

### Course 2:
```
Course Name: _______________________
Course URL: https://jw-ict-bootcamp.thinkific.com/courses/__________
Course ID (optional): _______
WooCommerce Product ID: _______
```

### Course 3:
```
Course Name: _______________________
Course URL: https://jw-ict-bootcamp.thinkific.com/courses/__________
Course ID (optional): _______
WooCommerce Product ID: _______
```

---

## ✅ Phase 4: Create Course Mappings (10 minutes)

### For Each Course:

- [ ] **Go to: WordPress Admin → Thinkific → Course Mapping**

- [ ] **Click: "Add New Mapping"**

- [ ] **Fill in:**
  - [ ] WooCommerce Product: [Select from dropdown]
  - [ ] Course Name: [From list above]
  - [ ] Course URL: [From list above]
  - [ ] Course ID: [From list above - optional]
  - [ ] Description: [Optional]

- [ ] **Click: "Save Mapping"**

- [ ] **Verify:** Mapping appears in table below

### Repeat for Each Course:
- [ ] Course 1 mapped
- [ ] Course 2 mapped
- [ ] Course 3 mapped

---

## ✅ Phase 5: Create Dashboard Page (5 minutes)

- [ ] **Go to: Pages → Add New**

- [ ] **Page Settings:**
  ```
  Title: My Courses
  Permalink: /my-courses/
  ```

- [ ] **Add Shortcode:**
  ```
  [thinkific_dashboard]
  ```

- [ ] **Publish Page**

- [ ] **Copy URL:** _________________________________

- [ ] **Add to Menu:**
  - [ ] Go to: Appearance → Menus
  - [ ] Add "My Courses" page
  - [ ] Save menu

---

## ✅ Phase 6: Test Everything (15 minutes)

### Test Order:
- [ ] **Create Test Order:**
  - [ ] Go to: WooCommerce → Orders → Add Order
  - [ ] Customer: Your email or test email
  - [ ] Add Product: [One of your course products]
  - [ ] Status: Processing
  - [ ] Save

### Check Enrollment:
- [ ] **Check Order Meta Box:**
  - [ ] Scroll to "Thinkific Enrollments"
  - [ ] Status shows: ✓ Enrolled

- [ ] **Check Logs:**
  - [ ] Go to: Thinkific → Logs
  - [ ] See: [INFO] Enrollment successful

- [ ] **Check Thinkific:**
  - [ ] Log into Thinkific admin
  - [ ] Go to: Users → [Test customer email]
  - [ ] Verify: Enrolled in course

### Test Dashboard:
- [ ] **Log in as Customer:**
  - [ ] Use test customer account
  - [ ] Visit: [Your My Courses URL]
  
- [ ] **Verify Dashboard:**
  - [ ] Course card appears
  - [ ] Course name correct
  - [ ] "Continue Course" button visible
  - [ ] Helper text shows (first time)

### Test Course Access:
- [ ] **Click "Continue Course"**
  - [ ] Opens Thinkific course page
  - [ ] Thinkific asks for login (first time)
  - [ ] Can login with same email
  - [ ] Course loads successfully

---

## ✅ Phase 7: Configure Settings (5 minutes)

- [ ] **Go to: Thinkific → Settings**

### Order Statuses:
- [ ] **Enrollment Trigger Statuses:**
  - [ ] Processing: ✓ (recommended)
  - [ ] Completed: ✓ (recommended)

### WooCommerce Options:
- [ ] **Force Single Quantity:** ✓ (recommended)
- [ ] **Skip Cart:** Your choice

### Cache Settings:
- [ ] **Course Cache:** 86400 (24 hours) - Leave default
- [ ] **Enrollment Cache:** 600 (10 minutes) - Leave default

### Logging:
- [ ] **Enable Logging:** ✓ (recommended for now)

- [ ] **Save Settings**

---

## ✅ Phase 8: Customize (Optional - 30 minutes)

### Email Customization:
- [ ] **WooCommerce → Settings → Emails**
- [ ] **Edit "Processing Order" email**
- [ ] **Add course access instructions:**
  ```
  Your course is ready!
  Visit: https://yoursite.com/my-courses/
  
  Important: Use the email [customer_email] to login
  ```

### Dashboard Customization:
- [ ] **Edit "My Courses" page**
- [ ] **Add welcome message above shortcode**
- [ ] **Add help information below shortcode**
- [ ] **Update**

### Menu Improvements:
- [ ] **Appearance → Menus**
- [ ] **Position "My Courses" prominently**
- [ ] **Consider: Only show when logged in**

---

## ✅ Phase 9: Go Live! (Review)

### Final Checks:
- [ ] All courses have products
- [ ] All products are mapped
- [ ] Dashboard page works
- [ ] Test order successful
- [ ] Enrollment verified
- [ ] Dashboard looks good
- [ ] Thinkific access works

### Launch Day:
- [ ] **Monitor first 3-5 orders closely**
- [ ] **Check: Thinkific → Logs regularly**
- [ ] **Verify enrollments happening automatically**
- [ ] **Respond to customer questions quickly**

### Customer Support Prep:
- [ ] **Dashboard URL ready:** _________________________
- [ ] **Know how to check enrollment status**
- [ ] **Know how to retry failed enrollments**
- [ ] **Prepared for "can't login to Thinkific" questions**

---

## 📊 Monitoring (Ongoing)

### Daily (First Week):
- [ ] Check Thinkific → Logs for errors
- [ ] Verify enrollments happening
- [ ] Monitor customer support tickets

### Weekly:
- [ ] Review enrollment success rate
- [ ] Check for failed enrollments
- [ ] Clear old logs if needed

### Monthly:
- [ ] Review overall system performance
- [ ] Gather customer feedback
- [ ] Optimize based on usage

---

## 🆘 Common Issues - Quick Reference

| Issue | Quick Fix |
|-------|-----------|
| Enrollment failed | Order → Thinkific meta box → Retry |
| Course not on dashboard | Check order status, check mapping |
| Customer can't login to Thinkific | Must use same email as checkout |
| Dashboard blank | Check shortcode, check logged in |
| Test connection fails | Check API key, check subdomain |

---

## 📚 Documentation Quick Links

- **Complete Setup:** [COMPLETE-SETUP-GUIDE.md](COMPLETE-SETUP-GUIDE.md)
- **API Issues:** [QUICK-FIX-401.md](QUICK-FIX-401.md)
- **User Guide:** [README.md](README.md)
- **Quick Reference:** [QUICK-REFERENCE.md](QUICK-REFERENCE.md)

---

## ✅ You're Ready When:

- [x] At least 1 product created
- [x] At least 1 course mapped
- [x] Dashboard page published
- [x] Test order enrolled successfully
- [x] Test customer can see and access course
- [x] You understand the customer flow

---

**Print this checklist and check off items as you complete them!** ✓

**Estimated Total Time: 1-2 hours for initial setup**

---

## 🎉 Success!

Once all checkboxes are marked, you're ready to start selling courses with automatic enrollment and seamless access!

**Your customers will love the experience!** 🚀
