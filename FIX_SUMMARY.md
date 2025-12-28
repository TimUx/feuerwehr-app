# Login Redirect Issue - Fix Summary

## Problem
After successful login, users were being redirected back to the login page without any error message. This indicated that session data was not persisting between the login POST request and the subsequent GET request.

## Root Cause Analysis

### Previous Attempts (PR #74 and #75)
- **PR #74**: Added `session_write_close()` before redirect in `index.php`
- **PR #75**: Moved session handling into `Auth::login()` with:
  - `session_regenerate_id(false)` - kept old session temporarily
  - Manual deletion of old session file
  - Absolute URLs for redirects

### Issues with PR #75 Approach
1. **Race Condition**: Manual session file deletion after `session_write_close()` could interfere with the new session
2. **Path Calculation**: The old session file path calculation might be incorrect on some systems
3. **Timing Issue**: Deleting the old session file before ensuring the new one is fully accessible could cause problems
4. **URL Mismatch**: Absolute URLs in redirects could cause cookie domain/path mismatches

## Changes Made in This Fix

### 1. Use `session_regenerate_id(true)` Instead of `(false)`
```php
// BEFORE (PR #75):
$oldSessionId = session_id();
session_regenerate_id(false);  // Keep old session
session_write_close();
// Manually delete old session file
$oldSessionFile = $sessionPath . '/sess_' . $oldSessionId;
@unlink($oldSessionFile);

// AFTER (This Fix):
session_regenerate_id(true);  // PHP handles old session deletion safely
session_write_close();
// No manual deletion needed
```

**Why this is better:**
- PHP's built-in mechanism for deleting old sessions is more reliable
- Eliminates race conditions and path calculation issues
- Works consistently across different PHP configurations

### 2. Change Redirects to Relative URLs
```php
// BEFORE:
header('Location: ' . getSafeRedirectUrl('/index.php'));

// AFTER:
header('Location: /index.php');
```

**Why this is better:**
- Avoids any potential domain/path mismatches that could prevent cookies from being sent
- Simpler and more reliable
- Works correctly behind reverse proxies and load balancers

### 3. Add Comprehensive Debug Logging
Added logging to track:
- Session ID changes during login
- Session cookie presence in browser requests  
- Session data when starting sessions
- Authentication check results

**Files with debug logging:**
- `src/php/auth.php` - Login and authentication checks
- `src/php/session_init.php` - Session initialization

### 4. Created Diagnostic Tool
Added `test_session.php` - A standalone tool to test session persistence without requiring login credentials.

## Testing Instructions

### Method 1: Test with Diagnostic Tool (Recommended First)
1. Access `http://your-domain/test_session.php` in your browser
2. Click "Set Session & Redirect"
3. Check if the session data persists after the redirect
4. If it works, session handling is correct
5. **Delete `test_session.php` after testing!**

### Method 2: Test with Actual Login
1. Clear your browser cookies and cache
2. Go to the login page
3. Enter valid credentials and login
4. Check if you stay logged in or get redirected to login page
5. Check your PHP error log for detailed information

## What to Look For in Error Logs

The debug logging will show you exactly what's happening:

### Successful Login Flow
```
Login successful for user 'admin'. Old session: abc123..., New session: xyz789...
Session written and closed. Session data should be persisted to disk.
initSecureSession: Cookie 'FWAPP_SESSION' from browser: xyz789...
initSecureSession: Session started with ID: xyz789..., Session data: {"authenticated":true,"user_id":"..."}
isAuthenticated check: Session ID: xyz789..., Authenticated: true, User ID: user_...
```

### Failed Login Flow (Session Not Persisting)
```
Login successful for user 'admin'. Old session: abc123..., New session: xyz789...
Session written and closed. Session data should be persisted to disk.
initSecureSession: Cookie 'FWAPP_SESSION' from browser: not set  ← Cookie not received!
initSecureSession: Session started with ID: new456..., Session data: []
isAuthenticated check: Session ID: new456..., Authenticated: not set, User ID: not set
isAuthenticated: authenticated flag not set or false
```

## Possible Issues and Solutions

### Issue 1: Cookie Not Being Sent by Browser
**Symptoms:** Error log shows "Cookie 'FWAPP_SESSION' from browser: not set"

**Possible Causes:**
- Browser privacy settings blocking cookies
- Mixed HTTP/HTTPS causing secure cookie issues
- SameSite cookie policy issues

**Solutions:**
1. Check if site is consistently using HTTP or HTTPS (not mixed)
2. Try with a different browser or incognito mode
3. Check browser console for cookie warnings

### Issue 2: Session File Not Being Created/Written
**Symptoms:** Session ID changes on each request

**Possible Causes:**
- Session directory not writable
- Disk space issues
- PHP session configuration issues

**Solutions:**
1. Check session save path: Run `php -i | grep session.save_path`
2. Verify permissions: `ls -la $(php -r "echo session_save_path();")`
3. Check disk space: `df -h`

### Issue 3: Protocol Mismatch
**Symptoms:** Sometimes logged in, sometimes not

**Possible Causes:**
- Accessing site via both HTTP and HTTPS
- Reverse proxy not forwarding protocol headers correctly

**Solutions:**
1. Force HTTPS redirect in your web server configuration
2. Ensure reverse proxy sets `X-Forwarded-Proto` header
3. Check error logs for "Detected protocol: HTTPS" vs "HTTP"

## After Testing

### If the Issue is Fixed
1. Remove debug logging from:
   - `src/php/auth.php` (all `error_log()` calls)
   - `src/php/session_init.php` (all `error_log()` calls)
2. Delete `test_session.php`
3. Commit and merge the PR

### If the Issue Persists
1. Share the error logs (with debug output)
2. Share the output from `test_session.php`
3. Share any browser console errors
4. Provide information about your hosting environment:
   - Shared hosting vs VPS/dedicated
   - Behind reverse proxy/load balancer?
   - Using HTTP or HTTPS?

## Technical Details

### How Session Persistence Works
1. **Login (POST request)**:
   - User credentials validated
   - Session data set: `$_SESSION['authenticated'] = true`
   - `session_regenerate_id(true)` - New session ID created, old one deleted by PHP
   - `session_write_close()` - Session data written to disk synchronously
   - Browser receives response with:
     - `Set-Cookie: FWAPP_SESSION=xyz789...` header
     - `Location: /index.php` redirect header

2. **After Redirect (GET request)**:
   - Browser sends `Cookie: FWAPP_SESSION=xyz789...` in request
   - PHP starts session with this cookie
   - Session file `/var/lib/php/sessions/sess_xyz789...` is read
   - Session data is restored to `$_SESSION`
   - `isAuthenticated()` checks `$_SESSION['authenticated']` → returns true

### Key PHP Session Functions
- `session_start()` - Starts or resumes a session
- `session_regenerate_id(bool $delete_old)` - Creates new session ID
  - `true` = delete old session file (secure, recommended)
  - `false` = keep old session file (less secure, can cause issues)
- `session_write_close()` - Writes session data and closes session synchronously
- Session data is stored in: `/var/lib/php/sessions/sess_{session_id}`

## References
- PR #73: Original issue reported
- PR #74: First fix attempt (added `session_write_close` in index.php)
- PR #75: Second fix attempt (moved session handling to Auth::login)
- This PR: Final fix using PHP's built-in session management correctly
