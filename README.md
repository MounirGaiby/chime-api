# Chime AI Chat Application

A Laravel-based AI chat application that supports multiple AI providers and models with role-based access control.

## Features

### User Features
- **Authentication**
  - Register new account
  - Login to existing account
  - Logout from current session
  - Refresh authentication token

- **Conversations**
  - Create new conversations
  - List all conversations
  - View specific conversation details
  - Delete conversations
  - View conversation history

- **Chat**
  - Send messages to AI models
  - Stream chat responses in real-time
  - View available AI models
  - Set custom temperature for responses
  - Access chat history
  - Support for web search capabilities (on supported models)

### Super Admin Features
- **Provider Management**
  - List all AI providers
  - Add new AI providers
  - Configure provider settings
  - Manage API keys

- **Model Management**
  - Add new AI models
  - Configure model settings:
    - Temperature ranges
    - Endpoints
    - Web access capabilities
    - Reasoning capabilities
  - Enable/disable models

## Technical Setup

### Environment Configuration
```env
APP_NAME=Chime
APP_ENV=local
APP_KEY=your-app-key
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# API Keys Configuration (JSON format)
MODELS_API_KEY={"deepseek-api-key":"your-deepseek-key","openrouter-api-key":"your-openrouter-key"}
```

### Database Setup
```bash
php artisan migrate:fresh --seed
```
This will:
1. Create necessary database tables
2. Create a super admin user
3. Set up API keys from environment variables
4. Create AI providers and models

### Default Super Admin Credentials
- Email: admin@chime.ai
- Password: admin@2024

## API Endpoints

### Public Routes
```
POST /api/login
POST /api/register
```

### Protected Routes (Requires Authentication)
```
# Auth Routes
POST /api/logout
POST /api/refresh

# Conversation Routes
GET    /api/conversations
POST   /api/conversations
GET    /api/conversations/{id}
DELETE /api/conversations/{id}

# Chat Routes
POST /api/conversations/{id}/chat
GET  /api/models
GET  /api/conversations/{id}/history
```

### Super Admin Routes (Requires Super Admin Role)
```
GET  /api/admin/providers
POST /api/admin/providers
POST /api/admin/models
```

## Models Configuration

### Available Models
- **Deepseek Chat**
  - Default temperature: 0.7
  - Temperature range: 0.1 - 1.0
  - Web search enabled
  - Reasoning disabled

- **Deepseek Reasoner**
  - Default temperature: 0.5
  - Temperature range: 0.1 - 0.8
  - Web search enabled
  - Reasoning enabled

## Security Features
- JWT Authentication
- Role-based access control
- API key encryption
- Request validation
- Rate limiting
- CORS protection

## Error Handling
- Comprehensive error messages
- Input validation errors
- Authentication errors
- Authorization errors
- API provider errors
- Rate limit errors

## Contributing
Please read our contributing guidelines before submitting pull requests.

## License
This project is licensed under the MIT License.
