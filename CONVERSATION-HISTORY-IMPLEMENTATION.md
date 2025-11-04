# Conversation History & System Role Implementation

**Date:** 2025-11-04
**Status:** ✅ Completed and Tested

## Summary

Successfully implemented proper conversation history tracking and role-based message structure across all AI providers (Ollama, OpenAI, Anthropic).

## Problems Solved

### Issue 1: No Conversation History
**Before:** Each message was treated as an isolated conversation. The chat widget didn't maintain any context between messages.

**After:** Full conversation history is now maintained in the frontend and sent with each request, allowing the AI to remember previous exchanges.

### Issue 2: No Role-Based Message Structure
**Before:**
- Ollama used `/api/generate` endpoint with plain text prompt
- Persona was concatenated with user input as a single string
- No separation between system instructions and user messages

**After:**
- Ollama now uses `/api/chat` endpoint with structured messages
- OpenAI and Anthropic updated for consistency
- Proper message structure:
  ```json
  {
    "messages": [
      {"role": "system", "content": "[compiled persona prompt]"},
      {"role": "user", "content": "[previous user message]"},
      {"role": "assistant", "content": "[previous AI response]"},
      {"role": "user", "content": "[current user message]"}
    ]
  }
  ```

## Files Modified

### 1. Ollama Provider (`includes/providers/class-ollama-provider.php`)
**Changes:**
- Switched from `/api/generate` to `/api/chat` endpoint
- Implemented structured message array with system/user/assistant roles
- Added conversation history support via `$context['messages']`
- Updated response parsing for chat format: `$data['message']['content']`

**Message Structure:**
```php
$messages = array(
    array('role' => 'system', 'content' => $prompt),  // Persona
    ...array_merge($messages, $context['messages']),   // History
    array('role' => 'user', 'content' => $user_input)  // Current
);
```

### 2. OpenAI Provider (`includes/providers/class-openai-provider.php`)
**Changes:**
- Added conversation history support
- Already used proper message structure, now enhanced with history

### 3. Anthropic Provider (`includes/providers/class-anthropic-provider.php`)
**Changes:**
- Added conversation history support
- Properly uses `system` parameter (Anthropic's API design)
- Message array contains only user/assistant exchanges

### 4. Frontend Chat Widget (`assets/js/chat.js`)
**Changes:**
- Added `conversationHistory` array to track messages
- User messages added to history before sending
- Assistant responses added to history after completion
- History serialized and sent with each request via `messages` parameter
- Works with both EventSource streaming and fetch fallback

**History Management:**
```javascript
// Store conversation in memory
conversationHistory.push({role: 'user', content: prompt});
conversationHistory.push({role: 'assistant', content: response});

// Send with requests (excluding current user message)
params.messages = JSON.stringify(conversationHistory.slice(0, -1));
```

### 5. API Endpoints (`includes/frontend/api-endpoints.php`)
**Changes:**
- Both `/generate` and `/stream` endpoints now accept `messages` parameter
- Added JSON parsing for GET requests (EventSource sends as query string)
- Sanitization and validation of message history
- Only allows `user` and `assistant` roles in history

**Sanitization:**
```php
// Parse JSON if from GET request
if (is_string($messages)) {
    $messages = json_decode($messages, true);
}

// Validate each message
foreach ($messages as $message) {
    if (!in_array($message['role'], ['user', 'assistant'], true)) {
        continue; // Skip invalid roles
    }
    $conversation_history[] = array(
        'role'    => sanitize_text_field($message['role']),
        'content' => sanitize_textarea_field($message['content'])
    );
}
```

## Testing Results

### Test 1: Memory Retention ✅
**Conversation:**
- User: "Hi, my name is John. What's yours?"
- AI: "Hello John! My name is **Alex**..."
- User: "What's my name again?"
- AI: "Your name is **John**, as you mentioned earlier."
- User: "And what's your name?"
- AI: "My name is **Alex**, as I mentioned before."

**Result:** ✅ Perfect memory retention across multiple turns

### Test 2: API Direct Testing ✅
```bash
curl ".../stream?...&messages=[{\"role\":\"user\",\"content\":\"Test\"},{\"role\":\"assistant\",\"content\":\"Hi\"}]"
```
**Result:** ✅ API correctly parses and uses conversation history

### Test 3: System Role Verification ✅
- Persona prompt is sent as `role: "system"`
- User messages sent as `role: "user"`
- AI responses as `role: "assistant"`
- Provider logs confirm proper structure

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Frontend (chat.js)                      │
│  conversationHistory = []                                   │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐           │
│  │ User Msg 1 │→ │ AI Reply 1 │→ │ User Msg 2 │           │
│  └────────────┘  └────────────┘  └────────────┘           │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ↓ (SSE or POST with messages[])
┌─────────────────────────────────────────────────────────────┐
│            Backend (api-endpoints.php)                      │
│  • Parse & sanitize message history                         │
│  • Compile persona prompt                                   │
│  • Build context with messages                              │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ↓ (context['messages'])
┌─────────────────────────────────────────────────────────────┐
│                  Provider Layer                             │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │   Ollama     │  │   OpenAI     │  │  Anthropic   │     │
│  │  /api/chat   │  │ /completions │  │  /messages   │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
│                                                              │
│  Builds structured message array:                           │
│  [                                                           │
│    {role: "system", content: "persona"},                    │
│    {role: "user", content: "msg1"},                         │
│    {role: "assistant", content: "reply1"},                  │
│    {role: "user", content: "current"}                       │
│  ]                                                           │
└─────────────────────────────────────────────────────────────┘
```

## Benefits

1. **Context Preservation**: AI remembers the entire conversation
2. **Better Persona Adherence**: System role clearly separates instructions from conversation
3. **Consistent Behavior**: All providers now work the same way
4. **API Compatibility**: Follows OpenAI, Anthropic, and Ollama best practices
5. **Security**: Proper sanitization of all conversation history
6. **Scalability**: Ready for multi-turn complex conversations

## Backward Compatibility

- All changes are backward compatible
- If no `messages` parameter provided, works as before (single-turn)
- Existing integrations continue to function

## Provider-Specific Notes

### Ollama
- Changed from `/api/generate` (legacy) to `/api/chat` (modern)
- Response format: `data['message']['content']` instead of `data['response']`

### OpenAI
- Already used proper message format
- Enhanced with conversation history support

### Anthropic
- Uses separate `system` parameter (not in messages array)
- Only user/assistant messages in array
- System prompt passed as top-level parameter

## Next Steps (Optional Enhancements)

1. **Session Persistence**: Store conversation history in browser localStorage
2. **Clear History Button**: Allow users to start fresh conversations
3. **Export Conversation**: Download chat transcripts
4. **Token Counting**: Display approximate token usage
5. **History Limits**: Implement sliding window for very long conversations

## References

- Ollama Chat API: https://github.com/ollama/ollama/blob/main/docs/api.md#generate-a-chat-completion
- OpenAI Chat Completions: https://platform.openai.com/docs/api-reference/chat
- Anthropic Messages API: https://docs.anthropic.com/claude/reference/messages_post
