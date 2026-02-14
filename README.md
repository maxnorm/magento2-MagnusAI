# Magnus AI Assistant

Magnus is an AI-powered assistant integrated into the Magento 2 admin panel, designed to help store administrators with questions, configuration guidance, and automated actions.

## Overview

Magnus provides a conversational interface within the Magento admin that combines:
- **LLM-powered responses** using configurable providers (OpenAI, OpenRouter) with **AI-driven function calling**
- **Tools** exposed to the LLM via function calling (procedural guidance, config explanation, reports, and more)
- **Action proposal and approval system** for executing Magento operations
- **Instance knowledge integration** that provides context about the store's modules and configuration
- **Conversation persistence** with message history
- **Rate limiting** and **audit logging** for security and compliance

## Current Capabilities

### 1. Chat Interface

Magnus provides a chat panel accessible from the admin header that allows administrators to:
- Send messages and receive AI-powered responses
- Maintain conversation history across sessions
- View previous conversations
- Receive action proposals that can be approved and executed
- View structured data (tables, step-by-step guides, links) in responses
- Use suggested questions for quick access to common queries

**Access**: Available in all admin pages via a trigger button in the header (requires `Magnus_Assistant::assistant` ACL permission)

**Endpoints**:
- `POST /admin/magnus/chat/send` - Send a message
- `GET /admin/magnus/chat/history` - Retrieve conversation history

### 2. LLM Provider Support

Magnus supports multiple LLM providers through a pluggable architecture:

- **OpenAI** (`openai`) - Direct OpenAI API integration
- **OpenRouter** (`openrouter`) - OpenRouter API for accessing multiple models

**Configuration**: 
- Provider selection via System Configuration
- API key storage (encrypted)
- Configurable per-instance

### 3. Tool System

Tools are exposed to the LLM via **function calling**. The LLM decides when to call a tool based on the user's message and the tool descriptions.

**Current tools**:
- **HowDoITool** - Step-by-step procedures (add product, configure shipping, etc.)
- **ConfigExplanationTool** - Explains config settings and current values
- **SearchConfigPathsTool** - Search config index by keywords
- **GetConfigValueTool** - Get current value for a config path
- **OpenAdminPageTool** - Build deep links to admin pages (products, orders, config, etc.)
- **ReportTool** - Read-only reports (e.g. top products by revenue)
- **SearchProductsTool** - Search products by name or SKU (requires Catalog)
- **GetOrderSummaryTool** - Order summary by order number or ID (requires Sales)
- **ListAdminAreasTool** - List admin areas the agent can open
- **DemoModuleListTool** - Lists enabled modules
- **FallbackTool** - Greetings and fallback

**Tool names**: `how_do_i`, `explain_config`, `search_config_paths`, `get_config_value`, `open_admin_page`, `run_report`, `search_products`, `get_order_summary`, `list_admin_areas`, `list_modules`, `greeting`.

**Safety**: All tools above are read-only or navigation; they run automatically. Any data change uses `propose_action` and requires user approval.

**Extensibility**: New tools can be registered via Dependency Injection by implementing `ToolInterface` and adding them to **FunctionSchemaBuilder** and **ToolExecutor** in `di.xml` (same tool list for both).

### 4. Action System

Magnus can propose and execute actions that modify the Magento instance. Actions follow a proposal → approval → execution workflow.

**Action Lifecycle**:
1. **Proposal**: LLM or tool proposes an action with description and preview
2. **Storage**: Action is stored as a pending action linked to the conversation
3. **Approval Detection**: User can approve by saying "yes", "approve", "confirm", etc.
4. **Execution**: Approved actions are executed with validation and error handling
5. **Audit Logging**: Successful actions are logged to the audit log

**Action Features**:
- **Preview Generation**: Actions can generate previews showing what will change
- **Validation**: Actions validate parameters before execution
- **Approval Requirements**: Actions can require explicit approval or execute automatically
- **Expiry**: Pending actions expire after 1 hour
- **Audit Trail**: All executed actions are logged with user ID, action type, and details

**Action Interface**: Actions implement `ActionInterface` with methods:
- `execute(array $params): ActionResult` - Execute the action
- `preview(array $params): ActionPreview` - Generate preview
- `validate(array $params): bool` - Validate parameters
- `getDescription(): string` - Human-readable description
- `requiresApproval(): bool` - Whether approval is required

**Action type registry**: Write actions are registered in `ActionTypeRegistry` via `di.xml` (argument `actionMap`: action_type_id => ActionInterface class). The LLM sees available write actions in the system prompt and uses them as `action_type` in `propose_action`. Third-party modules can add items to the registry to expose new actions.

### 5. Instance Knowledge

Magnus builds and maintains a knowledge index of the Magento instance to provide context-aware responses.

**Knowledge Components**:
- **Module List**: All enabled modules in the instance
- **Configuration Schema**: System configuration paths with labels and comments (excludes encrypted/secret fields for security)
- **Configuration Values**: Non-secret config values for current store/scope (Phase 2)
- **Summary Statistics**: Module count, config path count, config value count

**Knowledge Features**:
- **Caching**: Knowledge index is cached for 24 hours (configurable)
- **Context Retrieval**: Relevant config chunks are retrieved based on user message keywords
- **Selective Injection**: Instance knowledge can be enabled/disabled per configuration
- **Auto-invalidation**: Cache invalidates when configuration changes
- **Config Value Lookup**: Can retrieve current config values by path (Phase 2)

**Context Retrieval**:
- Keyword-based matching against config paths and labels
- Scores chunks by relevance
- Limits context size to prevent token overflow (max 4000 chars)
- Returns top 10 most relevant chunks

### 6. Conversation Management

Magnus maintains persistent conversations with full message history.

**Features**:
- **Conversation Persistence**: Conversations are stored in the database
- **Message History**: Last 10 messages are included in LLM context
- **User Isolation**: Each admin user has separate conversations
- **Conversation Continuity**: Conversations persist across sessions

**Database Tables**:
- `magnus_assistant_conversation` - Conversation metadata
- `magnus_assistant_message` - Individual messages (user/assistant)
- `magnus_assistant_pending_action` - Pending action proposals
- `magnus_assistant_audit_log` - Executed action audit trail

### 7. Rate Limiting

Magnus implements per-user rate limiting to prevent abuse and control API costs.

**Features**:
- **Sliding Window**: Uses a 1-minute sliding window
- **Configurable Limit**: Set via System Configuration (requests per minute)
- **Per-User Tracking**: Each admin user has independent rate limits
- **Cache-Based**: Uses Magento cache for efficient tracking

**Configuration**: `magnus/assistant/rate_limit_per_minute` (0 = no limit)

### 8. Security & Permissions

**ACL Resource**: `Magnus_Assistant::assistant`
- Controls access to the chat interface
- Required for sending messages and viewing history

**Security Features**:
- **API Key Encryption**: LLM API keys are stored encrypted
- **User Authentication**: All requests require authenticated admin user
- **Input Validation**: Message length limits (10,000 characters)
- **Action Validation**: Actions validate parameters before execution
- **Audit Logging**: All executed actions are logged with user attribution

### 9. Configuration

System Configuration path: `Stores > Configuration > Advanced > Magnus AI Assistant`

**Settings**:
- **Enable Magnus**: Toggle to enable/disable the assistant
- **LLM Provider**: Select provider (OpenAI, OpenRouter)
- **API Key**: Encrypted storage for provider API key
- **Rate Limit**: Requests per minute per user (0 = unlimited)
- **Use Instance Knowledge**: Enable/disable injection of instance knowledge into prompts

**Logging**: Magnus writes its own log file at `var/log/magnus_assistant.log`. Use it to debug LLM/API errors, chat flow issues, and action handling. Log level is DEBUG by default.

## Architecture

### Core Components

1. **ChatService** - Main orchestrator: builds context, calls LLM with tools, executes tool calls in a loop, returns reply and action proposals
2. **LlmProviderProxy** - Proxy that creates provider instances based on configuration
3. **KnowledgeProvider** - Builds and caches instance knowledge index
4. **ContextRetriever** - Retrieves relevant context chunks for user messages
5. **PromptBuilder** - Constructs LLM prompt messages with context and history
6. **FunctionSchemaBuilder** - Builds OpenAI-style function schemas from tools for the LLM
7. **ToolExecutor** - Executes tools when the LLM requests them via function calling
8. **ActionRegistry** - Manages pending actions (store, retrieve, expire)
9. **ActionExecutor** - Executes approved actions with validation and logging
10. **ApprovalDetector** - Detects approval/denial intent in user messages
11. **RateLimiter** - Enforces per-user rate limits

### Data Flow

```
User Message
    ↓
ChatService::send()
    ↓
Rate Limiter Check
    ↓
Approval Detection (if pending action exists)
    ↓
ChatService::getReply() (function-calling path)
    ↓
KnowledgeProvider::getIndex()
ContextRetriever::getRelevantContext()
PromptBuilder::build()
FunctionSchemaBuilder::buildAllSchemas() → tools
    ↓
Loop (up to MAX_FUNCTION_CALLING_TURNS):
    LlmProvider::completeWithFunctions(messages, tools)
    → reply + tool_calls
    → ToolExecutor / ActionProposalHandler for each tool call
    → append tool results to messages, next turn
    ↓
ReplyResult (reply text, optional ActionProposal, optional structured_data)
    ↓
Store Message & Action Proposal (if any)
    ↓
Return Response
```

### Database Schema

- **magnus_assistant_conversation**: Stores conversation metadata
- **magnus_assistant_message**: Stores individual messages
- **magnus_assistant_pending_action**: Stores pending action proposals
- **magnus_assistant_audit_log**: Stores executed action audit records

## Extensibility

### Adding a New Tool

1. Create a class implementing `ToolInterface`
2. Implement `supports(string $intent, array $context): bool`
3. Implement `execute(MessageRequestInterface $request): ReplyResultInterface`
4. Implement `getToolName(): string` (used for function calling)
5. Register in `di.xml` in both **FunctionSchemaBuilder** and **ToolExecutor** tools arrays

Example:
```xml
<type name="Magnus\Assistant\Model\Llm\FunctionSchemaBuilder">
    <arguments>
        <argument name="tools" xsi:type="array">
            <!-- ...existing tools... -->
            <item name="my_tool" xsi:type="object">Vendor\Module\Model\Tool\MyTool</item>
        </argument>
    </arguments>
</type>
<type name="Magnus\Assistant\Model\Tool\ToolExecutor">
    <arguments>
        <argument name="tools" xsi:type="array">
            <!-- ...existing tools... -->
            <item name="my_tool" xsi:type="object">Vendor\Module\Model\Tool\MyTool</item>
        </argument>
    </arguments>
</type>
```

### Adding a New Action

1. Create a class implementing `ActionInterface`
2. Implement all required methods (`execute`, `preview`, `validate`, `getDescription`, `requiresApproval`)
3. Register in `ActionTypeRegistry` via `di.xml`: add an item to the `actionMap` argument with name = action_type_id (e.g. `product_create`) and value = your ActionInterface class. The LLM will see it in the prompt and can use it in `propose_action`.

### Adding a New LLM Provider

1. Create a class implementing `LlmProviderInterface`
2. Implement `completeWithFunctions(array $messages, array $tools, array $options): array`
3. Add to `LlmProviderFactory::$providerMap`
4. Add option to `LlmProvider` source model

## Testing

Unit tests are located in `Test/Unit/`:
- `Model/RateLimiterTest.php` - Rate limiter tests
- `Model/Action/ApprovalDetectorTest.php` - Approval detection tests

## Phase 2 Features (Current)

### Structured Data Support
- **Tables**: Report results displayed as formatted tables
- **Steps**: Step-by-step guides with optional deep links to admin pages
- **Links**: Clickable links to relevant admin pages

### Suggested Questions
- One-click prompts for common queries
- Automatically hidden after first message
- Includes: "What are my top products?", "How do I add a product?", "What does catalog search do?", etc.

### Deep Linking
- Admin URL generation helper for navigation
- Links to config pages, product/category grids, order pages, CMS pages
- Respects ACL permissions

## Future Enhancements

This documentation will evolve as new capabilities are added. Current roadmap items (not yet implemented):
- Enhanced intent detection (beyond keyword matching)
- More built-in tools
- More action types
- Enhanced context retrieval (semantic search)
- Multiple report templates
- Export report data to CSV
- Multi-language support
- Conversation export
- Advanced analytics

## Version

Current version: **1.0.0** (Phase 2 MVP)

## License

Copyright © Magnus. All rights reserved.
