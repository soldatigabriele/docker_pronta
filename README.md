# iOS Reminders Replacement - Backend API

A modern, feature-rich replacement for the iOS Reminders app with collaborative features, smart item restoration, and usage analytics.

## Features

- **Smart Lists**: Create, organize, and customize lists with colors and icons
- **Intelligent Items**: Automatic restoration of previously created items
- **Collaboration**: Share lists with other users with granular permissions
- **Autocomplete**: Smart suggestions based on usage patterns
- **Usage Analytics**: Track item popularity and completion rates
- **iOS-Style Design**: Native iOS colors, icons, and design patterns
- **Mobile-First**: Optimized for mobile devices

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Database**: SQLite (configurable)
- **Authentication**: Laravel Sanctum
- **Frontend**: Vue 3 (Options API)
- **Styling**: SCSS (no Tailwind)

## Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and npm
- SQLite (or your preferred database)

### Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd pronta
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database setup**
   ```bash
   touch database/database.sqlite
   php artisan migrate
   ```

6. **Start the development servers**
   ```bash
   # Backend (Laravel)
   php artisan serve
   
   # Frontend (Vite) - in another terminal
   npm run dev
   ```

   Or use the combined development command:
   ```bash
   composer run dev
   ```

## API Documentation

The API is fully documented in [`API_DOCUMENTATION.md`](./API_DOCUMENTATION.md).

### Quick Start

1. **Register a user**
   ```bash
   curl -X POST http://localhost:8000/api/auth/register \
     -H "Content-Type: application/json" \
     -d '{
       "name": "John Doe",
       "email": "john@example.com",
       "password": "password123",
       "password_confirmation": "password123"
     }'
   ```

2. **Create a list**
   ```bash
   curl -X POST http://localhost:8000/api/lists \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -d '{
       "name": "Grocery List",
       "description": "Weekly shopping",
       "color": "#007AFF",
       "icon": "cart.fill"
     }'
   ```

3. **Add items to the list**
   ```bash
   curl -X POST http://localhost:8000/api/lists/1/items \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -d '{
       "title": "Buy milk",
       "tags": ["dairy", "organic"],
       "category": "groceries"
     }'
   ```

## Database Schema

### Tables

- **users**: User accounts and authentication
- **reusable_lists**: Lists with metadata (color, icon, sharing status)
- **list_items**: Items within lists with completion tracking
- **list_shares**: Sharing permissions between users
- **item_usage_stats**: Analytics for autocomplete and popularity
- **personal_access_tokens**: Sanctum authentication tokens

### Key Relationships

- Users can own multiple lists
- Lists can have multiple items
- Lists can be shared with multiple users
- Items track usage statistics per user

## Key Features Explained

### Smart Item Restoration

When creating an item, the system checks if the user has previously created an item with the same title. If found and completed, it restores the item instead of creating a duplicate, incrementing the usage count.

### Usage Analytics

The system tracks:
- How often items are created
- Completion rates
- Last usage timestamps
- Popular tags and categories

This data powers the autocomplete feature and item suggestions.

### Collaborative Lists

Lists can be shared with three permission levels:
- **View**: Read-only access
- **Edit**: Can add, edit, and complete items
- **Admin**: Full control including sharing and deletion

### Sorting and Organization

- Lists maintain custom sort order
- Active items follow user-defined order
- Completed items are sorted by popularity (usage count)
- Items can be categorized and tagged

## Testing

### Manual Testing

Run the included test script:
```bash
php test_api.php
```

This will test:
- User registration
- List creation
- Item management
- Completion tracking
- Autocomplete functionality

### Unit Tests

```bash
php artisan test
```

## Development

### Code Structure

```
app/
├── Http/Controllers/Api/
│   ├── AuthController.php          # Authentication
│   ├── ReusableListController.php  # List management
│   ├── ListItemController.php      # Item management
│   └── ListShareController.php     # Sharing functionality
├── Models/
│   ├── User.php                    # User model with Sanctum
│   ├── ReusableList.php           # List model with relationships
│   ├── ListItem.php               # Item model with completion logic
│   ├── ListShare.php              # Sharing model with permissions
│   └── ItemUsageStat.php          # Analytics model
```

### API Routes

All routes are defined in `routes/api.php` with proper middleware and documentation.

### Frontend Integration

The Vue 3 frontend should consume the API endpoints documented in `API_DOCUMENTATION.md`. Key considerations:

- Use Sanctum tokens for authentication
- Implement proper error handling for API responses
- Cache frequently accessed data (lists, user info)
- Implement optimistic updates for better UX

## Deployment

### Production Setup

1. **Environment configuration**
   ```bash
   cp .env.example .env.production
   # Configure database, app URL, etc.
   ```

2. **Optimize for production**
   ```bash
   composer install --optimize-autoloader --no-dev
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   npm run build
   ```

3. **Database migration**
   ```bash
   php artisan migrate --force
   ```

### Docker Deployment

A `Dockerfile` and `docker-compose.yaml` are included for containerized deployment.

```bash
docker-compose up -d
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## Support

For questions or issues, please open a GitHub issue or contact the development team.
