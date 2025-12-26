# Testing Guide for Location Management Feature

## Prerequisites
- Admin user account
- Access to the Feuerwehr App
- Browser with JavaScript enabled

## Test Scenarios

### 1. Location Management (Admin)

#### 1.1 Create Location
1. Log in as admin
2. Navigate to Admin → Standorte
3. Click "Hinzufügen"
4. Enter location details:
   - Name: "Feuerwehrhaus Willingshausen"
   - Address: "Hauptstraße 1, 34628 Willingshausen"
5. Click "Speichern"
6. ✅ Verify location appears in the list

#### 1.2 Edit Location
1. In the locations list, click edit icon for a location
2. Change the name or address
3. Click "Speichern"
4. ✅ Verify changes are reflected in the list

#### 1.3 Delete Location
1. Click delete icon for a location
2. Confirm deletion in the dialog
3. ✅ Verify location is removed from the list
4. ✅ Verify associated vehicles/personnel are NOT deleted

### 2. Vehicle Management with Locations

#### 2.1 Create Vehicle with Location
1. Navigate to Admin → Fahrzeuge
2. Click "Hinzufügen"
3. Select vehicle type
4. Select a location from the dropdown
5. Enter other details
6. Click "Speichern"
7. ✅ Verify vehicle appears with correct location

#### 2.2 Filter Vehicles by Location
1. In vehicle list, use the location filter dropdown
2. Select a location
3. ✅ Verify only vehicles from that location are shown

#### 2.3 Edit Vehicle Location
1. Click edit on a vehicle
2. Change the location
3. Click "Speichern"
4. ✅ Verify vehicle now shows new location

### 3. Personnel Management with Locations

#### 3.1 Create Personnel with Location
1. Navigate to Admin → Einsatzkräfte
2. Click "Hinzufügen"
3. Enter name and select location
4. Add qualifications if needed
5. Check "Ist Ausbilder" if applicable
6. Click "Speichern"
7. ✅ Verify personnel appears with correct location

#### 3.2 View Personnel Location
1. In personnel list, check the "Standort" column
2. ✅ Verify locations are displayed correctly
3. ✅ Verify personnel without location show "-"

### 4. Attendance Form Location Filtering

#### 4.1 Filter by Location
1. Navigate to Anwesenheitsliste
2. Select a location from the dropdown at the top
3. ✅ Verify only instructors from that location appear in the dropdown
4. ✅ Verify only personnel from that location appear in checkboxes
5. ✅ Verify hidden items are deselected automatically

#### 4.2 No Location Selected
1. Leave location dropdown empty
2. ✅ Verify all instructors and personnel are visible

#### 4.3 Location with No Personnel
1. Create a new location with no assigned personnel
2. Select that location
3. ✅ Verify an alert appears: "Keine Einsatzkräfte verfügbar"

#### 4.4 Submit Form
1. Select a location
2. Select instructors and attendees
3. Fill in other required fields
4. Submit the form
5. ✅ Verify form submits successfully

### 5. Mission Report Location Filtering

#### 5.1 Filter Vehicles by Location
1. Navigate to Einsatzbericht
2. Select a location from the dropdown at the top
3. ✅ Verify only vehicles from that location are shown
4. ✅ Verify hidden vehicles are unchecked

#### 5.2 Filter Crew by Location
1. Select a location
2. Check some vehicles
3. Scroll to crew section
4. ✅ Verify crew name dropdowns only show personnel from selected location

#### 5.3 Change Location
1. Select vehicles and some crew members
2. Change the location
3. ✅ Verify vehicle checkboxes update
4. ✅ Verify crew fields regenerate
5. ✅ Verify only personnel from new location are available

#### 5.4 No Location Selected
1. Leave location dropdown empty (if possible)
2. ✅ Verify form requires location selection

### 6. Navigation

#### 6.1 Menu Item
1. Log in as admin
2. Open navigation menu
3. ✅ Verify "Standorte" appears in Admin section
4. ✅ Verify it's between "Fahrzeuge" and "Telefonnummern"
5. Click on "Standorte"
6. ✅ Verify it navigates to the locations page

### 7. Backward Compatibility

#### 7.1 Existing Data
1. If you have existing vehicles with string locations
2. ✅ Verify they still display correctly
3. ✅ Verify filtering still works for new location_id based records

### 8. Edge Cases

#### 8.1 No Locations Available
1. Delete all locations
2. Go to vehicle form
3. ✅ Verify helpful message and link to create locations

#### 8.2 Personnel Without Location
1. Create personnel without selecting location
2. ✅ Verify they can still be saved
3. ✅ Verify they show "-" in the location column
4. When no location filter is active, ✅ verify they appear in forms

#### 8.3 Multiple Locations
1. Create multiple locations
2. Assign different vehicles/personnel to each
3. Test filtering in all forms
4. ✅ Verify filtering works correctly for each location

## Test Results Template

```
Date: __________
Tester: __________

| Test | Status | Notes |
|------|--------|-------|
| 1.1 Create Location | ⬜ Pass ⬜ Fail | |
| 1.2 Edit Location | ⬜ Pass ⬜ Fail | |
| 1.3 Delete Location | ⬜ Pass ⬜ Fail | |
| 2.1 Create Vehicle with Location | ⬜ Pass ⬜ Fail | |
| 2.2 Filter Vehicles | ⬜ Pass ⬜ Fail | |
| 2.3 Edit Vehicle Location | ⬜ Pass ⬜ Fail | |
| 3.1 Create Personnel with Location | ⬜ Pass ⬜ Fail | |
| 3.2 View Personnel Location | ⬜ Pass ⬜ Fail | |
| 4.1 Attendance Filter | ⬜ Pass ⬜ Fail | |
| 4.2 Attendance No Location | ⬜ Pass ⬜ Fail | |
| 4.3 Location with No Personnel | ⬜ Pass ⬜ Fail | |
| 4.4 Submit Attendance | ⬜ Pass ⬜ Fail | |
| 5.1 Mission Filter Vehicles | ⬜ Pass ⬜ Fail | |
| 5.2 Mission Filter Crew | ⬜ Pass ⬜ Fail | |
| 5.3 Mission Change Location | ⬜ Pass ⬜ Fail | |
| 5.4 Mission No Location | ⬜ Pass ⬜ Fail | |
| 6.1 Navigation Menu | ⬜ Pass ⬜ Fail | |
| 7.1 Backward Compatibility | ⬜ Pass ⬜ Fail | |
| 8.1 No Locations | ⬜ Pass ⬜ Fail | |
| 8.2 Personnel Without Location | ⬜ Pass ⬜ Fail | |
| 8.3 Multiple Locations | ⬜ Pass ⬜ Fail | |
```

## Known Limitations

1. Form submissions (attendance, mission report) require the form backend handlers to be updated to store the location_id
2. Location deletion does not cascade - vehicles and personnel keep their location_id references
3. No validation to prevent assigning personnel/vehicles to deleted locations
4. Browser must support ES6+ JavaScript for filtering to work

## Troubleshooting

### Issue: Location not showing in dropdown
- Clear browser cache
- Verify location was saved successfully
- Check browser console for JavaScript errors

### Issue: Filtering not working
- Verify JavaScript is enabled
- Check browser console for errors
- Verify personnel/vehicles have location_id set

### Issue: Personnel shows "-" for location
- This is expected if no location is assigned
- Edit the personnel record and select a location
