# Chime - AI Chat API

A robust Laravel-based API for AI chat applications, featuring DeepSeek AI integration with support for multiple models and real-time streaming.

## Features

### Authentication
- JWT (JSON Web Token) based authentication
- Secure token management
- Token refresh mechanism
- Protected routes
- Custom error handling for authentication failures

### Conversations
- Create new conversations
- Update conversation titles
- Delete conversations
- List all conversations (ordered by last activity)
- View single conversation with its chat history
- Automatic last_message_at timestamp updates
- Chat count tracking per conversation

### Chat Functionality
- Support for multiple AI models
  - deepseek-chat
  - deepseek-reasoner
- Real-time streaming responses
- Regular (non-streaming) responses
- Temperature control (model-specific ranges)
- Token usage tracking
- Reasoning content support (for reasoner model)
- Chat history retrieval
- Chronological message ordering
- Continuous conversation support
  - Maintains context across messages
  - Automatic token limit management
  - Warning system for long conversations
  - Token usage tracking per conversation

### Conversation Management
- Token limits and warnings
  - Warning at 6,000 tokens
  - Hard limit at 8,000 tokens per conversation
  - Approximate token calculation (1 token ≈ 4 characters)
- Automatic context handling
  - Previous messages included in AI requests
  - Both user and assistant messages maintained
  - Chronological order preserved

### Attachments
- Multiple attachment types support:
  - Files (up to 10MB)
  - Images
  - URLs
- Secure file storage
- Attachment metadata handling
- URL content processing for AI context

### AI Integration
- Provider-based architecture for easy expansion
- Model validation
- Temperature validation
- Default values per model
- Configurable API endpoints
- Error handling
- Response transformation
- Streaming support

### Security
- Input validation
- Authorization checks
- File upload restrictions
- Secure file storage
- Error handling
- Rate limiting
- Protected routes

## API Endpoints

### Authentication
```http
POST /api/login
POST /api/register
POST /api/logout
POST /api/refresh
```

### Conversations
```http
GET    /api/conversations
POST   /api/conversations
GET    /api/conversations/{id}
PUT    /api/conversations/{id}
DELETE /api/conversations/{id}
```

### Chat
```http
POST /api/conversations/{id}/chat
POST /api/conversations/{id}/chat/stream
GET  /api/conversations/{id}/history
GET  /api/models
```

## Models Configuration

```php
'allowed_models' => [
    'deepseek-chat' => [
        'temperature_range' => [0.1, 1.0],
        'default_temperature' => 0.7,
        'endpoint' => '/v1/chat/completions',
    ],
    'deepseek-reasoner' => [
        'temperature_range' => [0.1, 0.8],
        'default_temperature' => 0.5,
        'endpoint' => '/v1/chat/completions',
    ],
]
```

## Database Structure

### Users Table
- id
- name
- email
- password
- created_at
- updated_at

### Conversations Table
- id
- user_id
- title
- last_message_at
- created_at
- updated_at

### Chats Table
- id
- conversation_id
- message
- response
- reasoning_content
- model
- tokens_used
- temperature
- created_at
- updated_at

### Chat Attachments Table
- id
- chat_id
- type
- name
- path
- url
- metadata
- created_at
- updated_at

## Environment Configuration
Required environment variables:
```env
JWT_SECRET=your_jwt_secret
JWT_ALGO=HS256
OPENAI_DEEPSEEK_API_KEY=your_deepseek_api_key
```

## Error Handling
- Custom JSON responses for all errors
- Proper HTTP status codes
- Validation error messages
- Authentication error handling
- API error handling
- File upload error handling

## Technical Implementation
- Laravel 10.x
- JWT Authentication
- PostgreSQL database
- Provider-based AI service architecture
- Server-Sent Events for streaming
- Repository pattern
- Service layer architecture
- Middleware protection
- Custom exception handling

## Future Considerations
- Support for additional AI providers
- Enhanced attachment processing
- Advanced conversation management
- User preferences
- Usage analytics
- Rate limiting improvements

## API Usage Examples

### Chat with Attachments

#### File/Image Upload in Postman
1. Select "POST" method and enter your endpoint
2. Select "form-data" in the Body tab
3. Add the following keys:
```
message                 | "Analyze this document for me"
model                  | "deepseek-chat"
temperature            | 0.7
attachments[0][type]   | "file"
attachments[0][name]   | "document.pdf"
attachments[0][file]   | Select File (Enable File type in Postman)
```

Important: For the file field (attachments[0][file]):
- Click the dropdown on the right of the key field
- Select "File" type
- Then choose your file

Example curl command:
```bash
curl -X POST "http://your-api/api/conversations/1/chat" \
     -H "Authorization: Bearer your_token" \
     -H "Accept: application/json" \
     -F "message=Analyze this document for me" \
     -F "model=deepseek-chat" \
     -F "temperature=0.7" \
     -F "attachments[0][type]=file" \
     -F "attachments[0][name]=document.pdf" \
     -F "attachments[0][file]=@/path/to/your/document.pdf"
```

#### URL Attachment
```http
POST /api/conversations/{id}/chat
Content-Type: application/json
Authorization: Bearer your_jwt_token

{
    "message": "Summarize this webpage",
    "model": "deepseek-chat",
    "attachments": [
        {
            "type": "url",
            "name": "Article",
            "url": "https://example.com/article"
        }
    ]
}
```

#### Response Format
```json
{
    "success": true,
    "data": {
        "id": 1,
        "conversation_id": 1,
        "message": "Analyze this document for me",
        "response": "Based on the document you provided...",
        "model": "deepseek-chat",
        "tokens_used": 150,
        "temperature": 0.7,
        "created_at": "2024-02-12T12:00:00.000000Z",
        "updated_at": "2024-02-12T12:00:00.000000Z",
        "attachments": [
            {
                "id": 1,
                "chat_id": 1,
                "type": "file",
                "name": "document.pdf",
                "path": "chat-attachments/xyz123.pdf",
                "url": null,
                "metadata": null,
                "created_at": "2024-02-12T12:00:00.000000Z",
                "updated_at": "2024-02-12T12:00:00.000000Z"
            }
        ]
    }
}
```

### Continuous Conversation Example
The API automatically maintains conversation context. Each new message includes the full conversation history:

```http
POST /api/conversations/{id}/chat
Content-Type: application/json
Authorization: Bearer your_jwt_token

// First message
{
    "message": "What's the highest mountain in the world?",
    "model": "deepseek-chat"
}

// Response includes token tracking
{
    "success": true,
    "data": {
        "message": "What's the highest mountain in the world?",
        "response": "Mount Everest is the highest mountain...",
        "tokens_used": 45
    },
    "total_tokens": 45
}

// Second message - API automatically includes previous context
{
    "message": "How tall is it?",
    "model": "deepseek-chat"
}

// Response shows accumulated tokens
{
    "success": true,
    "data": {
        "message": "How tall is it?",
        "response": "Mount Everest stands at 29,029 feet (8,848 meters)...",
        "tokens_used": 38
    },
    "total_tokens": 83,
    "warning": "Conversation is getting long. Consider starting a new one soon." // Appears when needed
}
```

### Token Management
- Conversations track total token usage
- Warning message appears at 6,000 tokens
- Error response at 8,000 tokens:
```json
{
    "success": false,
    "message": "Conversation is too long. Please start a new one.",
    "total_tokens": 8234
}
```

### Notes
- Maximum file size: 10MB
- Supported file types: pdf, doc, docx, txt, jpg, png, etc.
- Files are stored in the `storage/app/public/chat-attachments` directory
- URLs are processed and their content is included in the AI context
- Multiple attachments can be sent in a single request
- Attachments are automatically associated with the chat message
