# iOS Reminders Replacement - API Documentation

This document describes the REST API endpoints for the iOS Reminders replacement application.

## Base URL
```
http://localhost:8000/api
```

## Authentication

The API uses Laravel Sanctum for authentication. Include the Bearer token in the Authorization header for protected routes.

```
Authorization: Bearer {your-token-here}
```

## Response Format

All API responses follow this consistent format:

```json
{
    "success": true|false,
    "data": {...},
    "message": "Optional message"
}
```

## Authentication Endpoints

### Register User
```http
POST /api/auth/register
```

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "created_at": "2025-05-23T14:00:00.000000Z",
            "updated_at": "2025-05-23T14:00:00.000000Z"
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    },
    "message": "User registered successfully"
}
```

### Login User
```http
POST /api/auth/login
```

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response:** Same as register

### Logout User
```http
POST /api/auth/logout
```
*Requires authentication*

**Response:**
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

### Get Current User
```http
GET /api/auth/user
```
*Requires authentication*

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "created_at": "2025-05-23T14:00:00.000000Z",
        "updated_at": "2025-05-23T14:00:00.000000Z"
    }
}
```

## Users Endpoints

### Get All Users (for sharing)
```http
GET /api/users
```
*Requires authentication*

**Description:** Returns all users except the current user for sharing purposes.

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 2,
            "name": "Jane Doe",
            "email": "jane@example.com"
        },
        {
            "id": 3,
            "name": "Bob Smith",
            "email": "bob@example.com"
        }
    ]
}
```

## Lists Endpoints

### Get All Lists
```http
GET /api/lists
```
*Requires authentication*

**Query Parameters:**
- `include_shared` (optional): Include shared user information

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Grocery List",
            "description": "Weekly grocery shopping",
            "color": "#007AFF",
            "icon": "list.bullet",
            "is_shared": false,
            "is_public": false,
            "sort_order": 0,
            "user_id": 1,
            "created_at": "2025-05-23T14:00:00.000000Z",
            "updated_at": "2025-05-23T14:00:00.000000Z",
            "user": {
                "id": 1,
                "name": "John Doe",
                "email": "john@example.com"
            },
            "items": [...]
        }
    ]
}
```

### Create List
```http
POST /api/lists
```
*Requires authentication*

**Request Body:**
```json
{
    "name": "Grocery List",
    "description": "Weekly grocery shopping",
    "color": "#007AFF",
    "icon": "list.bullet",
    "is_public": false,
    "sort_order": 0
}
```

### Get Specific List
```http
GET /api/lists/{id}
```
*Requires authentication*

### Update List
```http
PUT /api/lists/{id}
```
*Requires authentication*

### Delete List
```http
DELETE /api/lists/{id}
```
*Requires authentication*

### Reorder Lists
```http
POST /api/lists/reorder
```
*Requires authentication*

**Request Body:**
```json
{
    "list_ids": [3, 1, 2]
}
```

## List Items Endpoints

### Get List Items
```http
GET /api/lists/{list_id}/items
```
*Requires authentication*

**Query Parameters:**
- `completed` (optional): Filter by completion status (true/false)

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Buy milk",
            "is_completed": false,
            "completed_at": null,
            "sort_order": 0,
            "usage_count": 5,
            "last_used_at": "2025-05-23T14:00:00.000000Z",
            "created_by_user_id": 1,
            "completed_by_user_id": null,
            "reusable_list_id": 1,
            "created_at": "2025-05-23T14:00:00.000000Z",
            "updated_at": "2025-05-23T14:00:00.000000Z",
            "created_by": {
                "id": 1,
                "name": "John Doe",
                "email": "john@example.com"
            },
            "completed_by": null
        }
    ]
}
```

### Create List Item
```http
POST /api/lists/{list_id}/items
```
*Requires authentication*

**Request Body:**
```json
{
    "title": "Buy milk",
    "sort_order": 0
}
```

**Note:** If an item with the same title was previously created by the user and is completed, it will be restored instead of creating a new one.

### Get Specific Item
```http
GET /api/lists/{list_id}/items/{item_id}
```
*Requires authentication*

### Update Item
```http
PUT /api/lists/{list_id}/items/{item_id}
```
*Requires authentication*

**Request Body:**
```json
{
    "title": "Buy organic milk",
    "sort_order": 0
}
```

### Delete Item
```http
DELETE /api/lists/{list_id}/items/{item_id}
```
*Requires authentication*

### Toggle Item Completion
```http
PATCH /api/lists/{list_id}/items/{item_id}/toggle-complete
```
*Requires authentication*

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "title": "Buy milk",
        "is_completed": true,
        "completed_at": "2025-05-23T14:00:00.000000Z",
        "completed_by_user_id": 1,
        "usage_count": 6,
        ...
    },
    "message": "Item marked as complete"
}
```

### Reorder Items
```http
POST /api/lists/{list_id}/items/reorder
```
*Requires authentication*

**Request Body:**
```json
{
    "item_ids": [3, 1, 2]
}
```

### Autocomplete Items
```http
GET /api/items/autocomplete?q={query}
```
*Requires authentication*

**Query Parameters:**
- `q`: Search query (minimum 2 characters)

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "item_title": "Buy milk",
            "usage_count": 5,
            "completion_rate": 80.00
        }
    ]
}
```

## List Sharing Endpoints

### Get List Shares
```http
GET /api/lists/{list_id}/shares
```
*Requires authentication and admin permission*

### Share List
```http
POST /api/lists/{list_id}/shares
```
*Requires authentication and admin permission*

**Request Body:**
```json
{
    "email": "friend@example.com",
    "permission_level": "edit",
    "can_share": false,
    "expires_at": "2025-12-31T23:59:59Z"
}
```

**Permission Levels:**
- `view`: Can only view the list and items
- `edit`: Can view, add, edit, and complete items
- `admin`: Can do everything including sharing and deleting

### Update Share Permissions
```http
PATCH /api/lists/{list_id}/shares/{share_id}
```
*Requires authentication and admin permission*

### Remove Share
```http
DELETE /api/lists/{list_id}/shares/{share_id}
```
*Requires authentication and admin permission*

### Get My Shares
```http
GET /api/shares/my-shares
```
*Requires authentication*

**Response:**
```json
{
    "success": true,
    "data": {
        "pending": [
            {
                "id": 1,
                "permission_level": "edit",
                "is_accepted": false,
                "invited_at": "2025-05-23T14:00:00.000000Z",
                "expires_at": null,
                "reusable_list": {
                    "id": 1,
                    "name": "Grocery List",
                    ...
                },
                "shared_by": {
                    "id": 2,
                    "name": "Jane Doe",
                    "email": "jane@example.com"
                }
            }
        ],
        "accepted": [...]
    }
}
```

### Accept Share Invitation
```http
POST /api/shares/{share_id}/accept
```
*Requires authentication*

### Decline Share Invitation
```http
POST /api/shares/{share_id}/decline
```
*Requires authentication*

## Error Responses

### Validation Error (422)
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

### Unauthorized (401)
```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

### Forbidden (403)
```json
{
    "success": false,
    "message": "Unauthorized to access this list"
}
```

### Not Found (404)
```json
{
    "success": false,
    "message": "Item not found in this list"
}
```

## Key Features

### Smart Item Restoration
When creating an item, the API checks if the user has previously created an item with the same title. If found and completed, it restores the item instead of creating a duplicate, incrementing the usage count.

### Usage Statistics
The API tracks item usage statistics for autocomplete functionality, ordering suggestions by popularity and completion rate.

### Collaborative Lists
Lists can be shared with other users with different permission levels. Users can accept or decline share invitations.

### Sorting and Ordering
- Lists and items can be manually reordered
- Completed items are sorted by popularity (usage count)
- Active items maintain custom sort order

### iOS-Style Design
- Default iOS blue color (#007AFF)
- SF Symbols icon support
- Mobile-first responsive design considerations 