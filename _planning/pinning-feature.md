# List Pinning Feature

## Overview
Simple pinning system for lists that allows users to pin important lists to the top of their list view.

## Implementation

### Backend Changes
1. **Database Migration**: Added `is_pinned` boolean field to `reusable_lists` table
2. **Model Updates**: Added `is_pinned` to the casts in `ReusableList` model
3. **Controller**: 
   - Added `pin()` method to `ReusableListController`
   - Updated ordering in `index()` method to show pinned lists first
   - Updated validation in `store()` and `update()` methods
4. **Routes**: Added `PATCH /api/lists/{id}/pin` route

### Frontend Changes
1. **Service**: Added `pinList(listId, isPinned)` method to `ListService`
2. **Home Component**:
   - Updated `sortedLists` and `sharedLists` computed properties to prioritize pinned lists
   - Added pin button to list cards (both owned and shared lists)
   - Added `togglePin()` method with loading state
   - Added styles for pin button and list actions

### User Experience
- Pin button shows 📍 when unpinned, 📌 when pinned
- Pinned lists appear at the top of their respective sections
- Button has hover effects and disabled state during API calls
- Works for both owned lists and shared lists
- Clicking pin button toggles the pinned state immediately

### API Endpoints
- `PATCH /api/lists/{id}/pin` - Toggle pin status
  - Body: `{ "is_pinned": boolean }`
  - Returns: Updated list object

### Sorting Logic
1. Pinned lists first (is_pinned: true)
2. Then by sort_order (for owned lists) or name (for shared lists)

This provides a simple and intuitive way for users to organize their most important lists at the top. 