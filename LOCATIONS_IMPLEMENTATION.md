# Implementation Summary: Locations/Departments Management

## Overview
This implementation adds a complete management system for operational departments/locations (Einsatzabteilungen/Standorte) to the Feuerwehr App. The system allows administrators to manage locations centrally and use them throughout the application.

## Changes Made

### 1. Database Layer (datastore.php)
- Added CRUD operations for locations:
  - `getLocations()` - Get all locations
  - `getLocationById($id)` - Get single location
  - `createLocation($data)` - Create new location
  - `updateLocation($id, $data)` - Update location
  - `deleteLocation($id)` - Delete location

- Updated Personnel and Vehicle data structures:
  - Added `location_id` field to personnel records
  - Added `location_id` field to vehicle records (keeping legacy `location` for compatibility)
  - Updated create/update methods to handle the new field

### 2. API Layer
- Created `/src/php/api/locations.php`:
  - Full REST API for locations (GET, POST, PUT, DELETE)
  - Admin-only access for create/update/delete operations
  - Proper authentication and error handling

### 3. Admin Pages

#### Admin Locations Page (`/src/php/pages/admin-locations.php`)
- New page for managing locations
- Features:
  - List all locations with name and address
  - Add new locations
  - Edit existing locations
  - Delete locations (with warning about associated data)
  - Modal-based forms for add/edit operations

#### Updated Admin Vehicles Page (`/src/php/pages/admin-vehicles.php`)
- Changed location field from text input to dropdown
- Dropdown populated from locations database
- Filter by location now uses location IDs
- Backward compatible with old string-based locations
- Link to locations management if no locations exist

#### Updated Personnel Page (`/src/php/pages/personnel.php`)
- Added location column to personnel table
- Added location dropdown to personnel form
- Displays location name for each person
- Link to locations management if no locations exist

### 4. Form Pages

#### Attendance Form (`/src/php/pages/attendance.php`)
- Added location dropdown at the top of the form (required)
- JavaScript filtering:
  - Filters instructors in dropdown by selected location
  - Filters personnel checkboxes by selected location
  - Hides non-matching items and deselects them
  - Updates participant count after filtering
- Only shows instructors and personnel from the selected location

#### Mission Report Form (`/src/php/pages/mission-report.php`)
- Added location dropdown at the top of the form (required)
- JavaScript filtering:
  - Filters vehicles by selected location
  - Filters personnel in crew dropdowns by selected location
  - Unchecks hidden vehicles when location changes
  - Regenerates crew fields when location changes
- Only shows vehicles and personnel from the selected location

### 5. Navigation
- Added new menu item in admin section:
  - Icon: location_city
  - Label: "Standorte"
  - Links to admin-locations page
  - Positioned between Vehicles and Phone Numbers

## Data Structure

### Location Object
```json
{
  "id": "loc_xxxxx",
  "name": "Feuerwehrhaus Willingshausen",
  "address": "Straße, PLZ, Ort",
  "created_at": "2024-01-01 12:00:00",
  "updated_at": "2024-01-01 12:00:00"
}
```

### Updated Personnel Object
```json
{
  "id": "pers_xxxxx",
  "name": "Max Mustermann",
  "location_id": "loc_xxxxx",  // NEW FIELD
  "qualifications": [...],
  "leadership_roles": [...],
  "is_instructor": true
}
```

### Updated Vehicle Object
```json
{
  "id": "veh_xxxxx",
  "type": "LF 8",
  "location": "...",  // Legacy field (kept for compatibility)
  "location_id": "loc_xxxxx",  // NEW FIELD
  "radio_call_sign": "...",
  "crew_size": 6
}
```

## User Flow

1. **Admin creates locations** (Admin → Standorte):
   - Add locations with name and optional address
   - Edit or delete existing locations

2. **Admin assigns locations to vehicles** (Admin → Fahrzeuge):
   - Select location from dropdown when creating/editing vehicles
   - Location is now required field

3. **Admin assigns locations to personnel** (Admin → Einsatzkräfte):
   - Select location from dropdown when creating/editing personnel
   - Location is optional field

4. **User creates attendance list** (Anwesenheitsliste):
   - First, select location from dropdown (required)
   - Form filters to show only instructors and personnel from that location
   - Complete form as usual

5. **User creates mission report** (Einsatzbericht):
   - First, select location from dropdown (required)
   - Form filters to show only vehicles and personnel from that location
   - Complete form as usual

## Backward Compatibility

- Existing vehicles with string-based `location` field continue to work
- The system uses `location_id` when available, falls back to `location` string
- No data migration required - existing records remain functional
- New records should use `location_id` for proper filtering

## Technical Notes

- All filtering is done client-side using JavaScript
- Data attributes (`data-location-id`) used to track location associations
- Personnel without location can still be selected if no location filter is active
- Vehicles without location can still be selected if no location filter is active
- Location deletion does not delete associated personnel or vehicles (maintains referential integrity)

## Testing Recommendations

1. Create sample locations
2. Assign locations to vehicles and personnel
3. Test attendance form filtering
4. Test mission report form filtering
5. Verify backward compatibility with existing data
6. Test edit/delete operations on locations
7. Verify navigation menu updates

## Future Enhancements

Potential future improvements:
- Bulk location assignment for personnel/vehicles
- Location-based statistics and reporting
- Location hierarchy (main station, sub-stations)
- Location-based user permissions
- Import/export locations
- Location map integration
