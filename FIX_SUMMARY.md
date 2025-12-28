# Login Redirect Issue - Fix Summary

## Problem
After successful login, users were being redirected back to the login page without any error message. However, on a second login attempt (even with incorrect credentials), the user would be successfully logged in and redirected to the home page.

This behavior revealed that:
1. The first login WAS succeeding and session data WAS being stored
2. But the session was not being resumed properly on the first redirect
3. By the second request, the session was accessible and the user was already authenticated

## Root Cause

The issue was caused by calling `session_write_close()` immediately after `session_regenerate_id(true)` in the `Auth::login()` method. This created a race condition where:

1. **First login POST**: 
   - Session data set: `$_SESSION['authenticated'] = true`
   - `session_regenerate_id(true)` creates new session ID
   - `session_write_close()` writes session and closes it
   - Redirect to `/index.php`
   - Browser receives Set-Cookie header with new session ID

2. **First redirect GET**:
   - Browser sends session cookie with new ID
   - PHP tries to resume session
   - **Race condition**: Session file may not be fully accessible yet
   - `$_SESSION['authenticated']` is not available
   - User sees login page again

3. **Second login POST**:
   - Browser still has session cookie from first login
   - PHP resumes session successfully (file is now accessible)
   - `$_SESSION['authenticated']` is already set from first login
   - User is authenticated BEFORE credentials are even checked
   - User sees home page

## The Fix

**Remove `session_write_close()` call from `Auth::login()` method**

PHP automatically writes session data when the script ends via its shutdown handler. By letting PHP handle the session writing naturally, we ensure proper synchronization:

```php
// BEFORE:
session_regenerate_id(true);
session_write_close();  // Premature close causes race condition
return true;

// AFTER:
session_regenerate_id(true);
// Let PHP write the session automatically at script end
return true;
```

**Why this works:**
- PHP's shutdown handler ensures session is written BEFORE the HTTP response is sent
- No race condition - session file is guaranteed to be accessible before redirect
- Session cookie and session file are properly synchronized
- More reliable across different PHP configurations and hosting environments

## Additional Changes

### Removed Debug Logging
All `error_log()` calls were removed from:
- `src/php/auth.php` - `login()` and `isAuthenticated()` methods
- `src/php/session_init.php` - `initSecureSession()` function

These were only needed for debugging and are not required in production.

## Testing

To verify the fix:
1. Clear browser cookies
2. Navigate to the application
3. Enter valid login credentials
4. Click "Anmelden" (Login)
5. **Expected**: You should be immediately redirected to the home page
6. **Previous bug**: You would be redirected back to the login page

The fix ensures that session data is properly persisted and accessible immediately after the redirect, eliminating the need for a second login attempt.

## Technical Details

### How PHP Session Writing Works

When you call `session_start()`, PHP:
1. Creates or resumes a session
2. Loads session data into `$_SESSION`
3. Locks the session file (to prevent concurrent access)

When the script ends, PHP's shutdown handler:
1. Writes `$_SESSION` data to the session file
2. Unlocks the session file
3. Sends the Set-Cookie header (if needed)

By calling `session_write_close()` explicitly, you're trying to force this to happen early. However, when called immediately after `session_regenerate_id()` and before a redirect, it can cause timing issues where the session file isn't fully accessible when the browser follows the redirect.

### Why the Second Login Worked

The second login worked because:
1. The session file from the first login was now fully written and accessible
2. When the browser sent the session cookie on the second attempt, PHP successfully resumed the session
3. `$_SESSION['authenticated']` was already `true` from the first login
4. The authentication check passed BEFORE the login credentials were even processed
5. The user was shown the home page

This strange behavior was actually the key clue that led to identifying the root cause!
