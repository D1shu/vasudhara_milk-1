# Vasudhara Milk Distribution System - Testing Guide

## Project Overview
- **System**: Milk Distribution Management
- **Type**: PHP Web Application
- **Database**: MySQL
- **Key Modules**: Authentication, Admin, User, Reports, Orders

---

## PHASE 1: SETUP & PREREQUISITES

### Step 1.1: Verify XAMPP Installation
- [ ] XAMPP is installed
- [ ] Apache is running
- [ ] MySQL is running
- [ ] PHP version is 7.4+ (verify in phpinfo())

**Test Command:**
```
Access: http://localhost/phpmyadmin
```

### Step 1.2: Database Setup
- [ ] Create database `vasudhara_milk`
- [ ] Import SQL schema files in order:
  1. `milk_db_schema.sql`
  2. `vasudhara_milk.sql`
  3. `nasudhara-vansda-roo1.sql` (if needed)

**Test Command:**
```
Access: http://localhost/phpmyadmin
Check: Database tables are created successfully
```

### Step 1.3: Configuration Verification
- [ ] `config.php` has correct DB credentials
- [ ] Database: `localhost`
- [ ] User: `root`
- [ ] Password: (empty or correct)
- [ ] Database name: `vasudhara_milk`

---

## PHASE 2: AUTHENTICATION TESTING

### Step 2.1: User Registration
**Test Cases:**

| Test Case | Input | Expected Result | Status |
|-----------|-------|-----------------|--------|
| Valid Registration | Valid email, mobile, password | User created successfully | ☐ |
| Duplicate Email | Existing email | Error: Email already exists | ☐ |
| Invalid Email | Invalid format | Error: Invalid email | ☐ |
| Short Password | < 6 characters | Error: Password too short | ☐ |
| Empty Fields | Leave fields empty | Error: Required fields missing | ☐ |
| Invalid Mobile | Non-numeric | Error: Invalid mobile number | ☐ |

**Manual Test:**
```
1. Go to: http://localhost/vasudhara_milk/register.php
2. Fill in registration form
3. Submit and verify database entry
```

### Step 2.2: User Login
**Test Cases:**

| Test Case | Email | Password | Expected Result | Status |
|-----------|-------|----------|-----------------|--------|
| Valid Login | Registered user | Correct password | Redirect to dashboard | ☐ |
| Invalid Email | Non-existent email | Any password | Error: Invalid credentials | ☐ |
| Invalid Password | Valid email | Wrong password | Error: Invalid credentials | ☐ |
| Empty Email | (empty) | Any password | Error: Required field | ☐ |
| Empty Password | Any email | (empty) | Error: Required field | ☐ |

**Manual Test:**
```
1. Go to: http://localhost/vasudhara_milk/login.php
2. Test each case above
3. Verify session creation
```

### Step 2.3: OTP Verification
**Test Cases:**

| Test Case | Input | Expected Result | Status |
|-----------|-------|-----------------|--------|
| Valid OTP | Correct 6-digit OTP | OTP verified, proceed | ☐ |
| Invalid OTP | Wrong OTP | Error: Invalid OTP | ☐ |
| Expired OTP | OTP > 5 minutes old | Error: OTP expired | ☐ |
| Empty OTP | No input | Error: Required field | ☐ |

**Manual Test:**
```
1. Go to: verify-otp.php
2. Enter OTP from SMS/email
3. Verify authentication
```

### Step 2.4: Logout
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| Logout Click | Click logout button | Session destroyed, redirect to home | ☐ |
| Direct Access | Try to access /admin/ after logout | Redirect to login page | ☐ |

---

## PHASE 3: USER DASHBOARD TESTING

### Step 3.1: Dashboard Access
**Test Steps:**
1. [ ] Login as regular user
2. [ ] Access: http://localhost/vasudhara_milk/user/dashboard.php
3. [ ] Verify user information displayed correctly
4. [ ] Check sidebar navigation loads

**Expected Results:**
- Dashboard displays user's profile
- All menu items visible and clickable
- No database errors in console

### Step 3.2: Profile Management (`user/profile.php`)
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| View Profile | Click Profile | All user data displayed | ☐ |
| Edit Name | Update name | Changes saved to database | ☐ |
| Edit Email | Update email | Validation applied, saved if valid | ☐ |
| Edit Mobile | Update mobile | 10-digit validation, saved | ☐ |
| Invalid Email | Enter bad email | Error message shown | ☐ |

### Step 3.3: Submit Order (`user/submit-order.php`)
**Test Cases:**

| Test Case | Input | Expected Result | Status |
|-----------|-------|-----------------|--------|
| Select Anganwadi | Choose from dropdown | Anganwadi details loaded | ☐ |
| Enter Mon-Fri Qty | Valid quantities | Data populated | ☐ |
| Submit Valid Order | All fields filled | Order created, confirmation shown | ☐ |
| Empty Fields | Missing required fields | Error: Required field message | ☐ |
| Negative Qty | Negative numbers | Error: Invalid quantity | ☐ |
| Zero Quantity | All zeros | Error or warning | ☐ |

### Step 3.4: Order History (`user/order-history.php`)
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| View Orders | Load page | List all user's orders | ☐ |
| Filter by Status | Select status | Shows filtered orders | ☐ |
| View Order Details | Click order | Shows complete order details | ☐ |
| Empty History | No orders submitted | Shows "No orders found" message | ☐ |

### Step 3.5: Notifications (`user/notifications.php`)
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| View Notifications | Load page | Shows all notifications | ☐ |
| Mark as Read | Click notification | Notification marked as read | ☐ |
| Delete Notification | Click delete | Notification removed | ☐ |

---

## PHASE 4: ADMIN DASHBOARD TESTING

### Step 4.1: Admin Access
**Test Steps:**
1. [ ] Login as admin user
2. [ ] Access: http://localhost/vasudhara_milk/admin/dashboard.php
3. [ ] Verify admin-only content displayed
4. [ ] Check admin menu items

**Expected Results:**
- Admin dashboard displays statistics
- All admin modules accessible
- Regular users cannot access admin pages

### Step 4.2: Districts Management (`admin/districts.php`)
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| View Districts | Load page | Lists all districts | ☐ |
| Add District | Enter name, click Add | District saved, appears in list | ☐ |
| Edit District | Click Edit, update name | Changes saved | ☐ |
| Delete District | Click Delete | District removed | ☐ |
| Duplicate Name | Add existing district | Error: Already exists | ☐ |

**Database Check:**
```sql
SELECT * FROM districts;
```

### Step 4.3: Talukas Management (`admin/talukas.php`)
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| View Talukas | Load page | Lists talukas with district names | ☐ |
| Add Taluka | Select district, enter name | Taluka saved with district link | ☐ |
| Filter by District | Select district | Shows only that district's talukas | ☐ |
| Edit Taluka | Click Edit, update | Changes saved | ☐ |
| Delete Taluka | Click Delete | Taluka removed | ☐ |

**Database Check:**
```sql
SELECT * FROM talukas;
```

### Step 4.4: Villages Management (`admin/villages.php`)
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| View Villages | Load page | Lists villages with taluka/district | ☐ |
| Add Village | Select taluka, enter name | Village saved | ☐ |
| Edit Village | Click Edit, update | Changes saved | ☐ |
| Delete Village | Click Delete | Village removed | ☐ |

### Step 4.5: Anganwadi Management (`admin/anganwadi.php`)
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| View Anganwadis | Load page | List all anganwadis | ☐ |
| Add Anganwadi | Fill form completely | New anganwadi created | ☐ |
| Edit Details | Update info | Changes saved to database | ☐ |
| Delete Anganwadi | Click Delete | Anganwadi removed | ☐ |
| Search Anganwadi | Enter code/name | Filtered results shown | ☐ |

**Check Fields:**
- Anganwadi Code
- Name
- Type (AWW/AWH/Mini)
- Village
- Taluka
- District
- Contact Person
- Mobile
- Total Children
- Pregnant Women
- Route

### Step 4.6: Users Management (`admin/users.php`)
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| View All Users | Load page | List all registered users | ☐ |
| View User Details | Click user | User info displayed | ☐ |
| Activate User | Click Activate | User status changed to active | ☐ |
| Deactivate User | Click Deactivate | User status changed to inactive | ☐ |
| Reset Password | Click Reset | Password reset link sent | ☐ |
| Delete User | Click Delete | User removed from system | ☐ |

### Step 4.7: Orders Management (`admin/orders.php`)
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| View All Orders | Load page | Lists all pending/submitted orders | ☐ |
| Filter by Status | Select status | Shows filtered orders | ☐ |
| View Order Details | Click order | Complete order info displayed | ☐ |
| Approve Order | Click Approve | Order status = approved, user notified | ☐ |
| Reject Order | Click Reject | Order status = rejected, reason sent | ☐ |
| Mark as Dispatched | Click Dispatch | Status = dispatched | ☐ |

### Step 4.8: Approve Orders (`admin/approve-order.php`)
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| View Pending | Load page | Shows orders awaiting approval | ☐ |
| Approve Order | Click Approve | Status updated, notification sent | ☐ |
| Reject with Reason | Add reason, reject | Rejection logged, user notified | ☐ |

### Step 4.9: Routes Management (`admin/routes.php`)
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| View Routes | Load page | List all delivery routes | ☐ |
| Add Route | Enter details | New route created | ☐ |
| Edit Route | Update details | Changes saved | ☐ |
| Delete Route | Click Delete | Route removed | ☐ |

---

## PHASE 5: REPORTS TESTING

### Step 5.1: Daily Dispatch Report (`reports/daily-dispatch.php`)
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| Generate Report | Select date | Report generated with today's data | ☐ |
| Print Report | Click Print | Print dialog opens | ☐ |
| Export to PDF | Click Export | PDF file downloads | ☐ |
| No Data | Select date with no orders | "No data" message shown | ☐ |

### Step 5.2: Weekly Summary (`reports/weekly-summary.php`)
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| Select Week | Choose week | Summary data displayed | ☐ |
| Total Calculations | Verify totals | Calculations correct | ☐ |
| Export Report | Click Export | Data exported successfully | ☐ |

### Step 5.3: Monthly Report (`reports/monthly-report.php`)
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| Select Month/Year | Choose month | Report generated | ☐ |
| Calculate Totals | Verify calculations | Correct totals shown | ☐ |
| Breakdown | Check district/taluka breakdown | Accurate breakdown | ☐ |

### Step 5.4: Anganwadi Report (`reports/anganwadi-report.php`)
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| Select Anganwadi | Choose from list | Detailed report shown | ☐ |
| Date Range Filter | Select dates | Filtered report displayed | ☐ |
| Order History | View all orders | Complete history shown | ☐ |

### Step 5.5: District Report (`reports/district-report.php`)
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| Select District | Choose district | District stats displayed | ☐ |
| Taluka Breakdown | View talukas | Breakdown by taluka | ☐ |
| Export | Click export | Report exported | ☐ |

### Step 5.6: Route Report (`reports/route-report.php`)
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| Select Route | Choose route | Route delivery info shown | ☐ |
| Order Details | View orders on route | All orders listed | ☐ |
| Print Labels | Click print | Labels print correctly | ☐ |

---

## PHASE 6: AJAX FUNCTIONALITY TESTING

### Step 6.1: Get Talukas (`ajax/get-talukas.php`)
**Test Steps:**
1. [ ] Open browser DevTools (F12)
2. [ ] Go to Districts page
3. [ ] Select a district
4. [ ] Check Network tab - AJAX request sent
5. [ ] Talukas dropdown populated correctly

**Expected Result:**
- Correct talukas for selected district appear
- No JavaScript errors in console

### Step 6.2: Get Villages (`ajax/get-villages.php`)
**Test Steps:**
1. [ ] Open browser DevTools (F12)
2. [ ] Go to Villages page or form with taluka selection
3. [ ] Select a taluka
4. [ ] Check Network tab - AJAX request sent
5. [ ] Villages dropdown populated correctly

**Expected Result:**
- Correct villages for selected taluka appear
- No JavaScript errors in console

---

## PHASE 7: CALCULATOR TESTING

### Step 7.1: Calculator Functionality (`calculator/index.html`)
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| Basic Math | 2 + 3 | Result: 5 | ☐ |
| Subtraction | 10 - 4 | Result: 6 | ☐ |
| Multiplication | 3 × 4 | Result: 12 | ☐ |
| Division | 10 ÷ 2 | Result: 5 | ☐ |
| Decimal Values | 5.5 + 2.5 | Result: 8.0 | ☐ |
| Clear Function | Enter, click Clear | All values cleared | ☐ |

---

## PHASE 8: SECURITY TESTING

### Step 8.1: SQL Injection Testing
**Test Cases:**

| Test Case | Where | Input | Expected Result | Status |
|-----------|-------|-------|-----------------|--------|
| Login Email | Email field | `' OR '1'='1` | Should not bypass login | ☐ |
| Search Field | Any search | `'; DROP TABLE users; --` | No table deletion | ☐ |
| Anganwadi Code | Search field | `%' OR '1'='1` | Normal search only | ☐ |

**Expected Result:**
- All inputs should be parameterized queries
- No sensitive data exposure

### Step 8.2: Session Security
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| Session Timeout | Wait 30 minutes idle | Auto logout | ☐ |
| Direct URL Access | Type admin URL without login | Redirect to login | ☐ |
| Session Hijacking | Try to use old session | Session invalid | ☐ |

### Step 8.3: CSRF Protection
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| Form Submission | Submit form | CSRF token validated | ☐ |
| Missing Token | Remove token, submit | Form rejected | ☐ |

### Step 8.4: XSS Prevention
**Test Cases:**

| Test Case | Where | Input | Expected Result | Status |
|-----------|-------|-------|-----------------|--------|
| Comment/Remark | Remarks field | `<script>alert('XSS')</script>` | Script not executed | ☐ |
| User Name | Profile name | `<img src=x onerror=alert('XSS')>` | Script not executed | ☐ |

---

## PHASE 9: DATABASE INTEGRITY TESTING

### Step 9.1: Relationships & Foreign Keys
**SQL Checks:**

```sql
-- Check all tables exist
SHOW TABLES;

-- Verify foreign keys
SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'vasudhara_milk' AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Check districts
SELECT COUNT(*) FROM districts;

-- Check talukas linked to districts
SELECT COUNT(*) FROM talukas WHERE district_id IS NOT NULL;

-- Check villages linked to talukas
SELECT COUNT(*) FROM villages WHERE taluka_id IS NOT NULL;

-- Check anganwadis linked to villages
SELECT COUNT(*) FROM anganwadi WHERE village_id IS NOT NULL;

-- Check users and roles
SELECT COUNT(*) FROM users WHERE role IN ('admin', 'user');

-- Check orders and their status
SELECT status, COUNT(*) FROM weekly_orders GROUP BY status;
```

### Step 9.2: Data Integrity Tests
**Test Cases:**

| Test Case | Query | Expected Result | Status |
|-----------|-------|-----------------|--------|
| No Orphaned Orders | Check orders with non-existent anganwadi | Should be 0 | ☐ |
| No Orphaned Anganwadi | Check anganwadi with non-existent village | Should be 0 | ☐ |
| Qty Values | Check negative quantities | Should be 0 | ☐ |

---

## PHASE 10: PERFORMANCE TESTING

### Step 10.1: Page Load Times
**Test Cases:**

| Page | Expected Time | Actual Time | Status |
|------|----------------|-------------|--------|
| Home Page | < 2s | ___ | ☐ |
| Login | < 1s | ___ | ☐ |
| Dashboard | < 2s | ___ | ☐ |
| Reports | < 3s | ___ | ☐ |
| Orders (100+ records) | < 3s | ___ | ☐ |

**Testing Tool:**
- Use browser DevTools > Network tab
- Check Load Time for each page

### Step 10.2: Database Query Optimization
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| Large Report | Generate monthly report | Loads in < 3 seconds | ☐ |
| Search 1000 Orders | Search for specific order | Results in < 2 seconds | ☐ |
| Export 500 Records | Export data | Export completes in < 5 seconds | ☐ |

### Step 10.3: Concurrent User Testing
**Scenario:**
- 5 users logging in simultaneously
- Each submitting orders
- Admin generating reports

**Expected Result:**
- No database locks
- No timeout errors
- All operations complete successfully

---

## PHASE 11: BROWSER COMPATIBILITY TESTING

**Test Matrix:**

| Browser | Version | Desktop | Mobile | Status |
|---------|---------|---------|--------|--------|
| Chrome | Latest | ☐ | ☐ | ___ |
| Firefox | Latest | ☐ | ☐ | ___ |
| Safari | Latest | ☐ | ☐ | ___ |
| Edge | Latest | ☐ | ☐ | ___ |

**Test Checklist for Each Browser:**
- [ ] All pages load correctly
- [ ] Forms submit without errors
- [ ] AJAX requests work
- [ ] Charts/reports display properly
- [ ] Mobile responsive design works

---

## PHASE 12: MOBILE RESPONSIVENESS TESTING

**Test Cases:**

| Device | Screen Size | Test | Expected Result | Status |
|--------|-------------|------|-----------------|--------|
| Mobile | 320px | Sidebar navigation | Hamburger menu works | ☐ |
| Mobile | 320px | Login form | All fields visible | ☐ |
| Tablet | 768px | Dashboard | Layout adapts | ☐ |
| Tablet | 768px | Tables | Horizontal scroll if needed | ☐ |

---

## PHASE 13: FILE UPLOAD TESTING

### Step 13.1: Upload Validation
**Test Cases:**

| Test Case | File | Expected Result | Status |
|-----------|------|-----------------|--------|
| Valid Image | JPG/PNG 2MB | File uploads | ☐ |
| Oversized File | > 5MB | Error: File too large | ☐ |
| Invalid Format | .exe, .txt | Error: Invalid file type | ☐ |
| Empty File | 0 bytes | Error: Empty file | ☐ |

---

## PHASE 14: EMAIL & NOTIFICATION TESTING

### Step 14.1: OTP Sending
**Test Steps:**
1. [ ] Register with phone number
2. [ ] Verify OTP received (check SMS/email)
3. [ ] OTP expires after 5 minutes
4. [ ] Can request new OTP

### Step 14.2: Order Notifications
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| Order Submitted | Submit order | User notification created | ☐ |
| Order Approved | Admin approves | User receives notification | ☐ |
| Order Rejected | Admin rejects | User notified with reason | ☐ |

---

## PHASE 15: EDGE CASES & ERROR HANDLING

### Step 15.1: Error Messages
**Test Cases:**

| Test Case | Action | Expected Result | Status |
|-----------|--------|-----------------|--------|
| DB Connection Fail | Database offline | Friendly error message | ☐ |
| Invalid Data | Submit corrupted data | Error message shown | ☐ |
| Missing Config | config.php incomplete | Error message, not crash | ☐ |
| Permission Denied | User access admin | Redirect with message | ☐ |

### Step 15.2: Boundary Testing
**Test Cases:**

| Test Case | Input | Expected Result | Status |
|-----------|-------|-----------------|--------|
| Max Quantity | 999999 | Accepted or error | ☐ |
| Min Quantity | 0 | Handled correctly | ☐ |
| Max Name Length | 500 characters | Truncated or error | ☐ |
| Special Characters | `!@#$%^&*()` | Escaped/handled safely | ☐ |

---

## PHASE 16: FUNCTIONAL COMPLETENESS

### Step 16.1: Complete User Journey
**Scenario: New User Places Order**

1. [ ] User registers at `/register.php`
2. [ ] User logs in at `/login.php`
3. [ ] User verifies OTP
4. [ ] User views dashboard `/user/dashboard.php`
5. [ ] User completes profile `/user/profile.php`
6. [ ] User submits order `/user/submit-order.php`
7. [ ] User sees order in history `/user/order-history.php`
8. [ ] Admin approves order at `/admin/approve-order.php`
9. [ ] User receives notification
10. [ ] Admin generates report `/reports/daily-dispatch.php`
11. [ ] User logs out `/logout.php`

**Result:** All steps completed successfully ☐

### Step 16.2: Complete Admin Journey
**Scenario: Admin Reviews System**

1. [ ] Admin logs in
2. [ ] Accesses dashboard `/admin/dashboard.php`
3. [ ] Manages districts `/admin/districts.php`
4. [ ] Manages talukas `/admin/talukas.php`
5. [ ] Manages villages `/admin/villages.php`
6. [ ] Manages anganwadis `/admin/anganwadi.php`
7. [ ] Manages users `/admin/users.php`
8. [ ] Reviews orders `/admin/orders.php`
9. [ ] Approves/rejects orders `/admin/approve-order.php`
10. [ ] Generates reports `/reports/`
11. [ ] Logs out

**Result:** All steps completed successfully ☐

---

## TESTING CHECKLIST SUMMARY

### Critical (Must Pass)
- [ ] Database connectivity
- [ ] User registration and login
- [ ] Admin access control
- [ ] Order submission and approval
- [ ] Security: SQL injection prevention
- [ ] Session management

### Important (Should Pass)
- [ ] All CRUD operations
- [ ] Report generation
- [ ] AJAX functionality
- [ ] Email/SMS notifications
- [ ] Form validation

### Nice to Have (Could Pass)
- [ ] Performance optimization
- [ ] Browser compatibility
- [ ] Mobile responsiveness
- [ ] Export functionality

---

## DEFECT REPORTING TEMPLATE

**When you find an issue:**

```
Title: [Brief description]
Severity: Critical | High | Medium | Low
Module: [Module name]
Steps to Reproduce:
1. Step 1
2. Step 2
3. Step 3

Expected Result:
[What should happen]

Actual Result:
[What actually happened]

Browser/Environment:
- OS: Windows/Mac/Linux
- Browser: Chrome/Firefox/Safari
- Version: X.X.X
- Screen Size: 1920x1080

Screenshots/Error Messages:
[Attach if applicable]

Database State:
[Any relevant queries or data state]
```

---

## QUICK REFERENCE - KEY TEST URLS

| Page | URL |
|------|-----|
| Home | `http://localhost/vasudhara_milk/` |
| Register | `http://localhost/vasudhara_milk/register.php` |
| Login | `http://localhost/vasudhara_milk/login.php` |
| User Dashboard | `http://localhost/vasudhara_milk/user/dashboard.php` |
| Admin Dashboard | `http://localhost/vasudhara_milk/admin/dashboard.php` |
| Orders (Admin) | `http://localhost/vasudhara_milk/admin/orders.php` |
| Reports | `http://localhost/vasudhara_milk/reports/` |
| phpMyAdmin | `http://localhost/phpmyadmin` |

---

## NOTES

- Always test in a **non-production environment** first
- Keep **backups** of database before major testing
- Test with **multiple user roles** (admin, regular user)
- Check **browser console** for JavaScript errors (F12)
- Monitor **server logs** for PHP errors
- Verify **database queries** in admin tools

---

**Last Updated:** November 28, 2025
**Version:** 1.0
