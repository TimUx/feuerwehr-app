# Password Reset Feature - Testing Guide

## Overview
This guide covers testing the password reset functionality that allows users to recover their accounts via email.

## Prerequisites
- Admin access to the application
- SMTP server configured in Email Settings
- Test user account with email address configured

## Setup Steps

### 1. Configure SMTP Settings
1. Log in as admin
2. Navigate to **Email Einstellungen**
3. Configure SMTP settings:
   - SMTP Server (e.g., smtp.gmail.com)
   - Port (587 for TLS or 465 for SSL)
   - Enable SMTP authentication
   - Enter username and password
   - Set sender address
4. Click **Speichern**
5. Click **Test-E-Mail senden** to verify email is working

### 2. Create Test User with Email
1. Navigate to **Benutzer** (Admin section)
2. Click **Benutzer hinzufügen**
3. Enter:
   - Username: testuser
   - Email: your-test-email@example.com
   - Password: Test123
   - Role: Operator
4. Click **Speichern**
5. ✅ Verify user appears in list with email address

## Test Scenarios

### Test 1: Add Email to Existing User
**Objective**: Verify email field can be added to existing users

1. Navigate to **Benutzer**
2. Click edit icon for an existing user
3. Enter email address in the **E-Mail-Adresse** field
4. Click **Speichern**
5. ✅ Verify email is saved and displayed in user list

**Expected Result**: Email field is optional and saves correctly

---

### Test 2: Request Password Reset with Valid User
**Objective**: Verify password reset email is sent for valid user with email

1. Log out from the application
2. Click **Passwort vergessen?** on login page
3. Enter username of test user (with email configured)
4. Click **Link senden**
5. ✅ Verify success message appears
6. ✅ Check email inbox for password reset email
7. ✅ Verify email contains:
   - Greeting with username
   - Reset link
   - 1-hour expiry notice
   - Sender is configured email address

**Expected Result**: Reset email is received within 1-2 minutes

---

### Test 3: Request Password Reset with User Without Email
**Objective**: Verify security - no information disclosure about user existence

1. Navigate to login page
2. Click **Passwort vergessen?**
3. Enter username of user WITHOUT email configured
4. Click **Link senden**
5. ✅ Verify same generic message appears (no indication if user exists)
6. ✅ Verify no email is sent

**Expected Result**: Generic success message, no email sent

---

### Test 4: Request Password Reset with Non-existent User
**Objective**: Verify security - no user enumeration

1. Navigate to login page
2. Click **Passwort vergessen?**
3. Enter non-existent username (e.g., "nonexistent123")
4. Click **Link senden**
5. ✅ Verify same generic message appears
6. ✅ Verify no email is sent

**Expected Result**: Generic success message prevents user enumeration

---

### Test 5: Complete Password Reset Flow
**Objective**: Verify complete password reset works end-to-end

1. Request password reset for test user (Test 2)
2. Open email and click reset link
3. ✅ Verify redirect to password reset page
4. ✅ Verify page shows username
5. Enter new password (min 6 characters): "NewTest123"
6. Confirm password: "NewTest123"
7. Click **Passwort ändern**
8. ✅ Verify success message appears
9. ✅ Verify redirect to login page after 2 seconds
10. Try to log in with OLD password
11. ✅ Verify login fails
12. Try to log in with NEW password (NewTest123)
13. ✅ Verify login succeeds

**Expected Result**: Password is changed and old password no longer works

---

### Test 6: Password Mismatch Validation
**Objective**: Verify password confirmation validation

1. Request password reset and open link
2. Enter new password: "NewTest123"
3. Enter different confirm password: "DifferentPassword"
4. Click **Passwort ändern**
5. ✅ Verify error message: "Die Passwörter stimmen nicht überein"
6. ✅ Verify password is NOT changed

**Expected Result**: Validation prevents password mismatch

---

### Test 7: Token Expiry (1 Hour)
**Objective**: Verify tokens expire after 1 hour

⚠️ **Note**: This test requires waiting 1 hour or manipulating system time

1. Request password reset
2. Wait 1 hour (or adjust system time)
3. Click reset link from email
4. ✅ Verify error message: "Ungültiger oder abgelaufener Token"
5. ✅ Verify password reset form is hidden
6. ✅ Verify "Zurück zum Login" link works

**Expected Result**: Expired tokens are rejected

---

### Test 8: Token Reuse Prevention
**Objective**: Verify tokens cannot be reused after password reset

1. Request password reset
2. Open reset link and change password successfully
3. Try to use same reset link again
4. ✅ Verify error message: "Ungültiger oder abgelaufener Token"
5. ✅ Verify form is disabled

**Expected Result**: Used tokens are invalidated

---

### Test 9: Invalid Token
**Objective**: Verify invalid tokens are rejected

1. Navigate to: `index.php?action=reset-password&token=invalidtoken123`
2. ✅ Verify error message appears
3. ✅ Verify password reset form is hidden
4. ✅ Verify "Zurück zum Login" link works

**Expected Result**: Invalid tokens are rejected gracefully

---

### Test 10: Cancel Password Reset
**Objective**: Verify user can cancel password reset request

1. Click **Passwort vergessen?** on login page
2. Enter username
3. Click **Abbrechen**
4. ✅ Verify modal closes
5. ✅ Verify user remains on login page
6. ✅ Verify no email is sent

**Expected Result**: Cancel button works without side effects

---

### Test 11: Multiple Password Reset Requests
**Objective**: Verify multiple requests can be made

1. Request password reset for user
2. Wait 1 minute
3. Request password reset again for same user
4. ✅ Verify both emails are received
5. ✅ Verify both tokens work (test most recent first)
6. Use second (most recent) token to reset password
7. ✅ Verify password change succeeds

**Expected Result**: Multiple valid tokens can coexist

---

### Test 12: Password Strength Validation
**Objective**: Verify minimum password length

1. Request password reset and open link
2. Try to enter password shorter than 6 characters: "Test1"
3. Try to submit
4. ✅ Verify HTML5 validation prevents submission
5. Enter valid password (6+ characters)
6. ✅ Verify submission succeeds

**Expected Result**: Minimum 6 character password required

---

### Test 13: Special Characters in Password
**Objective**: Verify special characters work in passwords

1. Request password reset and open link
2. Enter password with special chars: "Test@123!#$"
3. Confirm same password
4. Click **Passwort ändern**
5. ✅ Verify password change succeeds
6. Log in with new password
7. ✅ Verify login succeeds

**Expected Result**: Special characters are supported

---

### Test 14: Cross-Site Scripting (XSS) Protection
**Objective**: Verify username is properly escaped in email

1. Create user with username: `<script>alert('xss')</script>`
2. Add email to this user
3. Request password reset
4. Check received email
5. ✅ Verify script tags are escaped in HTML email
6. ✅ Verify no JavaScript executes when viewing email

**Expected Result**: HTML escaping prevents XSS

---

### Test 15: UI - Forgot Password Link Visibility
**Objective**: Verify forgot password link is visible on login

1. Log out
2. View login page
3. ✅ Verify "Passwort vergessen?" link appears below password field
4. ✅ Verify link is styled correctly (primary color)
5. Hover over link
6. ✅ Verify link is clearly clickable

**Expected Result**: Link is visible and accessible

---

### Test 16: UI - Reset Password Page Layout
**Objective**: Verify reset password page displays correctly

1. Request password reset and open link
2. ✅ Verify page shows Feuerwehr branding (🚒)
3. ✅ Verify title: "Passwort zurücksetzen"
4. ✅ Verify username is displayed in highlighted box
5. ✅ Verify password fields have proper labels
6. ✅ Verify "Zurück zum Login" link is visible
7. ✅ Verify page is responsive on mobile

**Expected Result**: Professional, branded UI

---

### Test 17: Email Not Configured - Admin Warning
**Objective**: Verify admin is warned about email configuration

1. Log in as admin
2. Navigate to **Benutzer**
3. Check if users have email addresses
4. ✅ Verify help text explains email is needed for password reset
5. ✅ Verify "optional" is clearly stated

**Expected Result**: Clear guidance for admins

---

### Test 18: Session Security During Password Reset
**Objective**: Verify password reset doesn't require active session

1. Request password reset while logged in
2. Log out
3. Open reset link from email
4. ✅ Verify reset page loads without requiring login
5. Reset password successfully
6. ✅ Verify redirect to login page (not authenticated area)

**Expected Result**: Password reset works without authentication

---

### Test 19: Network Error Handling
**Objective**: Verify graceful error handling

1. Disable network (or pause SMTP server)
2. Request password reset
3. ✅ Verify generic success message still appears (no error leaked)
4. Re-enable network
5. Request password reset again
6. ✅ Verify email is sent

**Expected Result**: Errors don't leak information about users

---

### Test 20: User List Display with Email
**Objective**: Verify email column displays correctly

1. Log in as admin
2. Navigate to **Benutzer**
3. ✅ Verify "E-Mail" column appears
4. ✅ Verify users with email show address
5. ✅ Verify users without email show "Keine E-Mail" in gray
6. ✅ Verify email addresses are not truncated
7. ✅ Verify table is responsive

**Expected Result**: Email column is clear and user-friendly

---

## Security Checklist

### Token Security
- [x] Tokens use cryptographically secure random generation (random_bytes)
- [x] Tokens are hashed before storage (password_hash)
- [x] Tokens expire after 1 hour
- [x] Tokens are single-use (removed after password reset)
- [x] Token length is 64 characters (32 bytes hex encoded)

### Information Disclosure Prevention
- [x] Generic messages prevent user enumeration
- [x] Same response whether user exists or not
- [x] Same response whether email is configured or not
- [x] Email errors don't leak information to users

### Data Protection
- [x] Reset tokens stored encrypted
- [x] Passwords hashed with bcrypt
- [x] Email addresses stored encrypted
- [x] No passwords in logs or error messages

### Input Validation
- [x] Email addresses validated before sending
- [x] Username sanitized before database lookup
- [x] Token validated before processing
- [x] Password length enforced (min 6 chars)

### XSS Protection
- [x] Username escaped in email HTML
- [x] All user input escaped in HTML output
- [x] Reset link escaped in email

### CSRF Protection
- [x] Password reset API uses POST methods
- [x] Tokens are unpredictable

---

## Known Limitations

1. **Email Dependency**: Password reset only works if user has email configured
2. **SMTP Required**: Requires working SMTP configuration
3. **Token Storage**: Reset tokens stored in encrypted file (not database)
4. **No Rate Limiting**: Multiple requests allowed (consider adding in production)
5. **Generic Messages**: While secure, users can't tell if their request succeeded

---

## Troubleshooting

### Issue: Email Not Received
**Possible Causes:**
- SMTP not configured correctly
- Email in spam folder
- User doesn't have email address set
- Email server blocking messages

**Resolution:**
1. Check Email Settings configuration
2. Send test email from Email Settings
3. Check SMTP server logs
4. Verify user has email in Benutzer list

### Issue: "Ungültiger oder abgelaufener Token"
**Possible Causes:**
- Token expired (>1 hour old)
- Token already used
- Manual URL manipulation
- System time changed

**Resolution:**
1. Request new password reset
2. Use link within 1 hour
3. Don't reuse reset links

### Issue: Password Not Changing
**Possible Causes:**
- Passwords don't match
- Password too short (<6 chars)
- Token invalid
- Form submission error

**Resolution:**
1. Ensure passwords match exactly
2. Use at least 6 characters
3. Request new reset link
4. Check browser console for errors

---

## Success Criteria

All tests should pass with these results:
- ✅ Email field can be added to users (optional)
- ✅ Password reset email sent successfully
- ✅ Reset link works and password changes
- ✅ Security measures prevent enumeration
- ✅ Tokens expire after 1 hour
- ✅ Tokens are single-use
- ✅ UI is clear and user-friendly
- ✅ No XSS or security vulnerabilities

---

## Test Results Template

```
Date: _________________
Tester: _______________
SMTP Server: __________

| Test # | Test Name | Status | Notes |
|--------|-----------|--------|-------|
| 1 | Add Email to User | ☐ Pass ☐ Fail | |
| 2 | Valid User Reset | ☐ Pass ☐ Fail | |
| 3 | User Without Email | ☐ Pass ☐ Fail | |
| 4 | Non-existent User | ☐ Pass ☐ Fail | |
| 5 | Complete Reset Flow | ☐ Pass ☐ Fail | |
| 6 | Password Mismatch | ☐ Pass ☐ Fail | |
| 7 | Token Expiry | ☐ Pass ☐ Fail | |
| 8 | Token Reuse | ☐ Pass ☐ Fail | |
| 9 | Invalid Token | ☐ Pass ☐ Fail | |
| 10 | Cancel Reset | ☐ Pass ☐ Fail | |
| 11 | Multiple Requests | ☐ Pass ☐ Fail | |
| 12 | Password Strength | ☐ Pass ☐ Fail | |
| 13 | Special Characters | ☐ Pass ☐ Fail | |
| 14 | XSS Protection | ☐ Pass ☐ Fail | |
| 15 | Forgot Link Visible | ☐ Pass ☐ Fail | |
| 16 | Reset Page Layout | ☐ Pass ☐ Fail | |
| 17 | Admin Warning | ☐ Pass ☐ Fail | |
| 18 | Session Security | ☐ Pass ☐ Fail | |
| 19 | Network Error | ☐ Pass ☐ Fail | |
| 20 | Email Column Display | ☐ Pass ☐ Fail | |

Overall Status: ☐ All Pass ☐ Some Failures

Comments: _________________________________________________
```

---

## Post-Deployment Recommendations

1. **Monitor Email Delivery**: Track if reset emails are being delivered
2. **Add Rate Limiting**: Consider limiting reset requests per user/IP
3. **Log Reset Attempts**: Add logging for security monitoring
4. **User Education**: Inform users about the password reset feature
5. **Backup Testing**: Test recovery if data directory is backed up
6. **Production SMTP**: Ensure production SMTP is properly configured
7. **Email Templates**: Consider customizing email template for branding
