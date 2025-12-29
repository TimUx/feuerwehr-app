# Login Redirect Issue - Fix Documentation

## Problem Description

After successful login, users were being redirected back to the login page instead of the home page. On a second login attempt (with ANY credentials), users would then be redirected to the home page with the original user's credentials from the first login attempt.

This behavior revealed that:
1. The first login WAS succeeding and session data WAS being stored
2. But the session was not being detected as authenticated on the first redirect
3. By the second request, the session from the first login was active

## Root Cause

The issue was caused by the complex control flow in `index.php`, which handled both:
- Login form display and processing
- Main application display
- Conditional rendering based on authentication state

This created several potential issues:
1. Complex conditional logic that was hard to debug
2. Potential timing issues with session data availability
3. Mixed concerns in a single file

Even though PR #78 added `session_write_close()` after login, the problem persisted, suggesting the issue was more architectural than a simple race condition.

## Solution

Created a separate `login.php` file to handle authentication independently from the main application. This architectural change:
- Simplifies the authentication flow
- Makes the code easier to understand and maintain
- Eliminates conditional rendering complexity
- Creates a clear separation between login and application states

## Changes Made

### 1. Created `login.php`

A new dedicated login page that handles:
- **Login form display**: Shows the login form with username and password fields
- **POST request authentication**: Processes login credentials
- **Auto-login**: Handles "remember me" functionality via cookies (only on GET requests)
- **Redirects**: Sends authenticated users to `index.php`
- **Password reset**: Handles password reset link clicks with token validation
- **Forgot password modal**: Maintains the existing forgot password functionality

Key features:
- Redirects already-authenticated users to `index.php`
- Only attempts auto-login on GET requests (not after failed POST)
- Validates reset token format (64 hex characters) for security

### 2. Updated `index.php`

Simplified the main application entry point:
- **Removed**: Login form HTML and POST handling
- **Removed**: Auto-login logic (now handled by `login.php`)
- **Added**: Redirect to `login.php` for unauthenticated users
- **Updated**: Logout to redirect to `login.php`
- **Updated**: Password reset "back to login" link to point to `login.php`

The file now has a clear purpose: serve the authenticated application.

### 3. Updated `src/php/auth.php`

Two important changes:
- **`requireAuth()` method**: Now redirects to `/login.php` instead of `/index.php?page=login`
- **`tryAutoLogin()` method**: Added `session_write_close()` after session regeneration for consistent session handling and to prevent race conditions

## Benefits

### Architectural Improvements
- **Clear separation of concerns**: Authentication logic is isolated from the application
- **Simplified control flow**: No more complex conditionals to determine what to render
- **Easier debugging**: Login flow is now linear and straightforward
- **Better maintainability**: Changes to login don't affect the main app and vice versa

### Session Handling
- **Consistent behavior**: Both manual login and auto-login use `session_write_close()`
- **Race condition prevention**: Session data is written synchronously before redirects
- **Predictable flow**: Authentication always happens in `login.php`, not in `index.php`

### Security
- **Token validation**: Reset tokens are validated to be 64 hex characters
- **Input sanitization**: Username is trimmed, passwords are handled securely
- **Session regeneration**: Session IDs are regenerated after login to prevent session fixation

## Testing

### Manual Testing Performed

1. **Unauthenticated access**: ✅
   - Accessing `/index.php` without authentication redirects to `/login.php`
   - Login form is displayed correctly

2. **Successful login**: ✅
   - POST to `/login.php` with valid credentials
   - Redirects to `/index.php`
   - Main application is displayed
   - User information is shown correctly

3. **Session persistence**: ✅
   - Session data persists across the redirect
   - User stays logged in when navigating the app

4. **Failed login**: ✅
   - POST to `/login.php` with invalid credentials
   - Error message is displayed
   - User remains on login page

5. **Logout**: ✅
   - Clicking logout button
   - Redirects to `/login.php`
   - Session is destroyed

6. **Remember me (auto-login)**: ✅
   - Login with "remember me" checked
   - Close browser and reopen
   - Navigate to application
   - Automatically logged in

## Technical Details

### Login Flow

**Before (with mixed concerns):**
```
GET /index.php
  ↓
Check authentication
  ↓
Not authenticated → Render login form in index.php
  ↓
POST to index.php
  ↓
Auth::login() → session_write_close() → redirect
  ↓
GET /index.php
  ↓
Check authentication (sometimes failed due to timing/complexity)
```

**After (with separation):**
```
GET /index.php
  ↓
Check authentication
  ↓
Not authenticated → Redirect to /login.php
  ↓
GET /login.php
  ↓
Render login form
  ↓
POST to /login.php
  ↓
Auth::login() → session_write_close() → redirect
  ↓
GET /index.php
  ↓
Check authentication → Success!
  ↓
Render application
```

### Session Handling

Both `Auth::login()` and `Auth::tryAutoLogin()` now follow the same pattern:
1. Set session variables
2. Regenerate session ID with `session_regenerate_id(true)`
3. Call `session_write_close()` to write data synchronously
4. Return/redirect

This ensures session data is always written to disk before any redirect response is sent.

### Security Considerations

1. **Reset token validation**: Tokens must match the expected format (64 hex characters from `bin2hex(random_bytes(32))`)
2. **Session fixation prevention**: Session IDs are regenerated after successful authentication
3. **XSS protection**: All output is escaped with `htmlspecialchars()`
4. **CSRF**: Form submissions use POST and session tokens

## Migration Notes

If you have custom code or links that reference:
- `index.php?page=login` → Change to `/login.php`
- Login form actions → Now point to `/login.php`
- Logout redirects → Now go to `/login.php`

The password reset flow remains unchanged from a user perspective.

## Rollback Plan

If issues arise, you can:
1. Revert to the previous commit before this PR
2. The old `index.php` had all login functionality
3. No database changes were made

## Future Improvements

Potential enhancements (out of scope for this fix):
1. Add rate limiting to login attempts
2. Add CAPTCHA after multiple failed attempts
3. Implement 2FA support
4. Add login audit logging
5. Improve API consistency (as noted in code review)

## Conclusion

This architectural change simplifies the authentication flow by separating login from the main application. The clear separation of concerns makes the code easier to understand, maintain, and debug. The consistent use of `session_write_close()` ensures reliable session handling across all authentication scenarios.

The fix addresses the original problem by eliminating the complex control flow that was causing the redirect issue, and provides a more maintainable codebase for future development.
