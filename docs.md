# Chime AI Chat Application API Documentation

This document provides a comprehensive overview of the available API endpoints, including details on how to use them, the required request parameters, sample requests, and expected responses. The API is built with Laravel and supports JWT authentication, conversation management, AI chat interactions (including streaming responses), and administrative actions for super admins.


## 1. Overview

The Chime AI Chat Application enables users to engage in AI-powered conversations. It supports multiple AI providers and various models, gives real-time streaming responses, and offers role-based access control.

**Key Features:**
- User Authentication (Login, Register, Logout, Token Refresh)
- Conversation Management (Create, List, Show, Delete)
- Chat Messaging (Text, Attachments, Streaming responses)
- AI Models Listing and Customization
- Super Admin Functions (Provider and Model Management)
- Rate Limiting and CORS Protection


## 2. Base URL

All API endpoints are prefixed with `/api`. For example, if your domain is `http://localhost`, the full URL for login is:
```
http://localhost/api/login
```


## 3. Authentication

The API uses JWT tokens for authentication. Once you log in or register, you will receive a token. Include this token in the `Authorization` header for all protected endpoints.

**Header Example:**
```
Authorization: Bearer <your_token_here>
```


## 4. Public Routes

These endpoints do not require authentication.

### 4.1. POST `/api/login`

**Description:**  
Authenticate a user using their email and password.

**Request Parameters:**
- `email` (string, required)
- `password` (string, required)

**Sample Request:**
```bash
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "yourpassword"
}'
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "user": { /* user details */ },
    "authorization": {
      "token": "jwt_token_here",
      "type": "bearer",
      "expires_in": 3600
    }
  }
}
```


### 4.2. POST `/api/register`

**Description:**  
Register a new user account.

**Request Parameters:**
- `name` (string, required)
- `email` (string, required, unique)
- `password` (string, required, minimum 8 characters)
- `password_confirmation` (string, required, must match password)

**Sample Request:**
```bash
curl -X POST http://localhost/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "securepassword",
    "password_confirmation": "securepassword"
}'
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "user": { /* newly created user details */ },
    "authorization": {
      "token": "jwt_token_here",
      "type": "bearer",
      "expires_in": 3600
    }
  }
}
```


## 5. Protected Routes

All routes in this section require JWT authentication. Include the `Authorization: Bearer <your_token_here>` header with your request.

### 5.1. Auth Routes

#### 5.1.1. POST `/api/logout`

**Description:**  
Log out the authenticated user by invalidating their JWT.

**Sample Request:**
```bash
curl -X POST http://localhost/api/logout \
  -H "Authorization: Bearer <your_token_here>"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Successfully logged out"
}
```

#### 5.1.2. POST `/api/refresh`

**Description:**  
Refresh the current JWT token.
  
**Sample Request:**
```bash
curl -X POST http://localhost/api/refresh \
  -H "Authorization: Bearer <your_token_here>"
```

**Expected Response:**  
A new JWT token is issued along with related token metadata.


### 5.2. Conversation Routes

These endpoints allow users to manage their conversations.

#### 5.2.1. GET `/api/conversations`

**Description:**  
Retrieve all conversations belonging to the authenticated user. Each conversation includes a count of its chat messages and is sorted by the time of the last message.

**Sample Request:**
```bash
curl -X GET http://localhost/api/conversations \
  -H "Authorization: Bearer <your_token_here>"
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Project Chat",
      "last_message_at": "2023-10-15T12:34:56Z",
      "chats_count": 5
    }
    // ... additional conversations
  ]
}
```

#### 5.2.2. POST `/api/conversations`

**Description:**  
Create a new conversation.

**Request Parameters:**
- `title` (string, required)

**Sample Request:**
```bash
curl -X POST http://localhost/api/conversations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <your_token_here>" \
  -d '{
    "title": "Support Conversation"
}'
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "title": "Support Conversation",
    "last_message_at": "2023-10-16T08:00:00Z"
  }
}
```

#### 5.2.3. GET `/api/conversations/{id}`

**Description:**  
Retrieve a single conversation and its associated chat history.

**URL Parameter:**
- `id` (integer, required): Conversation identifier.

**Sample Request:**
```bash
curl -X GET http://localhost/api/conversations/1 \
  -H "Authorization: Bearer <your_token_here>"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Project Chat",
    "chats": [
      {
        "message": "Hello, AI!",
        "response": "Hi there!"
      }
      // ... additional chat messages
    ]
  }
}
```

#### 5.2.4. DELETE `/api/conversations/{id}`

**Description:**  
Delete a conversation. Only the owner can delete their conversation.

**Sample Request:**
```bash
curl -X DELETE http://localhost/api/conversations/1 \
  -H "Authorization: Bearer <your_token_here>"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Conversation deleted successfully"
}
```


### 5.3. Chat and Models Routes

These endpoints let users interact with AI models within their conversations.

#### 5.3.1. GET `/api/models`

**Description:**  
Retrieve available AI models along with their configurations (default model, temperature ranges, and capabilities).

**Sample Request:**
```bash
curl -X GET http://localhost/api/models \
  -H "Authorization: Bearer <your_token_here>"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "models": ["deepseek-chat", "deepseek-reasoner"],
    "default_model": "deepseek-chat",
    "model_configs": {
      "deepseek-chat": {
        "min_temperature": 0.1,
        "max_temperature": 1.0,
        "default_temperature": 0.7,
        "can_reason": false,
        "can_access_web": true
      },
      "deepseek-reasoner": {
        "min_temperature": 0.1,
        "max_temperature": 0.8,
        "default_temperature": 0.5,
        "can_reason": true,
        "can_access_web": true
      }
    }
  }
}
```

#### 5.3.2. POST `/api/conversations/{conversation}/chat`

**Description:**  
Send a chat message to an AI model in the context of a conversation. This endpoint also supports file attachments.

**URL Parameter:**
- `conversation` (integer): Conversation identifier.

**Request Parameters:**
- `message` (string, required)
- `model` (string, optional): Specify a model (e.g., `deepseek-chat` or `deepseek-reasoner`)
- `stream` (boolean, optional): Default true
- `temperature` (numeric, optional): A value within the model’s defined range.
- `attachments` (file[s], optional): Files to include with the chat message.

**Sample Request (with an attachment):**
```bash
curl -X POST http://localhost/api/conversations/1/chat \
  -H "Authorization: Bearer <your_token_here>" \
  -F "message=Hello, AI! Please review the attached file." \
  -F "model=deepseek-chat" \
  -F "temperature=0.7" \
  -F "attachments[]=@/path/to/file.txt"
```

**Expected Response:**  
Returns updated conversation details and the new chat record.

#### 5.3.3. POST `/api/conversations/{conversation}/chat/stream`

**Description:**  
Receive a real-time, streaming response from the AI model using Server-Sent Events (SSE).

**URL Parameter:**
- `conversation` (integer): Conversation identifier.

**Request Parameters:**
- `message` (string, required)
- `model` (string, optional)
- `temperature` (numeric, optional)

**Sample Request:**
```bash
curl -X POST http://localhost/api/conversations/1/chat/stream \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <your_token_here>" \
  -d '{
    "message": "Tell me a joke",
    "model": "deepseek-reasoner",
    "temperature": 0.5
}'
```

**Expected Behavior:**  
This endpoint streams data back with the `Content-Type: text/event-stream`. The client will receive data in chunks and a final event containing the complete chat response.

#### 5.3.4. GET `/api/conversations/{conversation}/history`

**Description:**  
Retrieve the full chat history of a specific conversation, sorted in chronological order.

**URL Parameter:**
- `conversation` (integer): Conversation identifier.

**Sample Request:**
```bash
curl -X GET http://localhost/api/conversations/1/history \
  -H "Authorization: Bearer <your_token_here>"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "conversation": { /* conversation metadata */ },
    "chats": [
      {
        "message": "Hello!",
        "response": "Hi there!"
      },
      {
        "message": "What's up?",
        "response": "All good!"
      }
      // ... more chat messages
    ]
  }
}
```


## 6. Super Admin Routes

These endpoints are restricted to users with super admin privileges. Ensure that the JWT provided belongs to a super admin.

#### 6.1. GET `/api/admin/providers`

**Description:**  
List all registered AI providers along with their associated models.

**Sample Request:**
```bash
curl -X GET http://localhost/api/admin/providers \
  -H "Authorization: Bearer <your_super_admin_token>"
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Deepseek",
      "base_url": "https://api.deepseek.com",
      "models": [ /* array of model configurations */ ]
    },
    {
      "id": 2,
      "name": "OpenRouter",
      "base_url": "https://api.openrouter.com",
      "models": [ /* array of model configurations */ ]
    }
  ]
}
```

#### 6.2. POST `/api/admin/providers`

**Description:**  
Add a new AI provider to the system.

**Request Parameters:**
- `name` (string, required, unique)
- `api_key` (string, required)
- `base_url` (string, required, valid URL)
- `implementation_class` (string, required): The fully qualified class name for this provider.

**Sample Request:**
```bash
curl -X POST http://localhost/api/admin/providers \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <your_super_admin_token>" \
  -d '{
    "name": "NewProvider",
    "api_key": "your_api_key_here",
    "base_url": "https://api.newprovider.com",
    "implementation_class": "App\\Services\\AI\\Providers\\NewProvider"
}'
```

**Expected Response:**  
Returns the newly created provider details with a status code of 201.

#### 6.3. POST `/api/admin/models`

**Description:**  
Configure and add a new AI model for a provider.

**Request Parameters:**
- `provider_id` (integer, required): Must correspond to an existing provider.
- `name` (string, required)
- `display_name` (string, required)
- `endpoint` (string, required)
- `min_temperature` (number, required): Minimum allowed temperature.
- `max_temperature` (number, required): Maximum allowed temperature.
- `default_temperature` (number, required)
- `can_reason` (boolean, optional)
- `can_access_web` (boolean, optional)
- `is_active` (boolean, optional)
- `additional_settings` (json string, optional)

**Sample Request:**
```bash
curl -X POST http://localhost/api/admin/models \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <your_super_admin_token>" \
  -d '{
    "provider_id": 1,
    "name": "deepseek-chat",
    "display_name": "Deepseek Chat",
    "endpoint": "/chat/completions",
    "min_temperature": 0.1,
    "max_temperature": 1.0,
    "default_temperature": 0.7,
    "can_reason": false,
    "can_access_web": true,
    "is_active": true,
    "additional_settings": "{\"supports_files\": false}"
}'
```

**Expected Response:**  
Returns the new model configuration details with a status code of 201.


## 7. Error Handling

The API provides consistent error responses:

- **401 Unauthorized:** When authentication fails or a valid JWT is not provided.
- **403 Forbidden:** When the user is not allowed to access the requested resource.
- **422 Unprocessable Entity:** For validation errors. The response includes details about which fields failed.
- **429 Too Many Requests:** When rate limits are exceeded (60 requests per minute).
- **500 Internal Server Error:** For general or unexpected errors.

**Example Error Response:**
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```


## 8. Additional Notes

- **File Attachments:**  
  Chat endpoints support file attachments. These files are processed according to their type (e.g., TXT, PDF) and appended to the chat message.

- **Provider Selection:**  
  The API uses the provided or default AI model to select the appropriate provider. The `AIService` automatically determines which provider instance to use based on the model requested.

- **Rate Limiting:**  
  API requests are limited to 60 per minute for each user (or IP for unauthenticated requests). Exceeding the limit will result in a 429 response.

- **Database Seeding:**  
  The initial setup creates a super admin (email: `admin@chime.ai`, password: `admin@2024`) and seeds AI providers and models. Run:
  ```bash
  php artisan migrate:fresh --seed
  ```
  to set up the database.


## 9. Contributing and Support

For contributions, please review the project's contributing guidelines. For any issues or questions about the API, open an issue on the project repository.


This concludes the detailed documentation for the Chime AI Chat Application API. Use this guide to integrate, test, and extend the API functionality as required.
```
