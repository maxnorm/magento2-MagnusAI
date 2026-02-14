# Skills & Tools Review: Toward a General-Purpose Magento AI Assistant

> **Note:** The Magnus Assistant codebase has been refactored from "skills" to **tools** (`ToolInterface`, `Model\Tool\*Tool`, `buildToolSchemas()`). The "current approach" section below describes the design that led to that refactor; implementation now uses tool naming throughout.

This document reviews the former Magnus skill approach (now implemented as tools), compares it with patterns used by OpenClaw, MCP, and modern agent frameworks, and proposes ways to let the agent **navigate more freely** on Magento based on user prompts.

---

## 1. Current Approach: Why It Feels Restrictive

### How it works today

- **Skills = tools**: Each skill implements `SkillInterface` and is exposed to the LLM as a single function (e.g. `how_do_i`, `explain_config`, `run_report`, `list_modules`, `greeting`).
- **Fixed tool set**: All tools are built once via `FunctionSchemaBuilder::buildAllSchemas()` and sent to the LLM on every turn. The LLM must choose from this static list.
- **Strict tool–intent binding**: The system prompt explicitly tells the LLM which tool to use for which intent (e.g. "Use how_do_i for…", "Use explain_config when…"). This is directive and limits exploration.
- **Coarse-grained tools**: Each tool is a "category" (e.g. "how do I" for many procedures, "run_report" for one report type). The LLM cannot:
  - Open a specific admin page by route or config path without going through a pre-defined procedure.
  - Search products/orders/customers by natural language and then deep-link.
  - Compose multiple small steps (look up config → open URL → suggest next step) in a flexible way.
- **Hard-coded procedures**: `HowDoISkill` has a fixed `PROCEDURES` map. New tasks require code changes. The LLM cannot "navigate" to arbitrary admin areas; it can only return steps for a fixed set of tasks.
- **ToolExecutor coupling**: `buildMessageFromArguments()` is a big switch on `$toolName`; every new skill requires updating this method. The executor is tied to skill names and argument shapes.

### Result

The LLM is **channeled into a small set of intents** and cannot freely "navigate" the Magento admin (open any page, search entities, chain read-only operations). It behaves like a **fixed FAQ + procedures bot** rather than a **general-purpose store assistant**.

---

## 2. What Others Are Doing: OpenClaw, MCP, and Dynamic Tools

### OpenClaw

- **Skills** = instructions (AgentSkills-compatible `SKILL.md` with YAML frontmatter). Skills teach the agent *how* to use tools; they are not the tools themselves. Skills can be loaded from bundled, managed (`~/.openclaw/skills`), or workspace (`/skills`) with precedence and gating (e.g. require env vars, binaries).
- **Plugin agent tools**: Plugins register **JSON-schema tools** with `api.registerTool({ name, description, parameters, execute })`. Tools can be required or **optional (opt-in)**. The agent then has a **richer, composable tool set** (browser, exec, web, apply_patch, etc.) and skills describe when/how to use them.
- **Takeaway**: Separate "what the agent can do" (tools) from "how the agent is instructed" (skills). Allow many small, focused tools and let the LLM combine them.

### Model Context Protocol (MCP)

- **MCP** is an open standard (Anthropic, then adopted by OpenAI, Google, etc.) for exposing **tools and resources** to AI applications via a consistent JSON-RPC interface. Servers expose **tools** (functions with name, description, parameters); clients (e.g. Cursor, Claude) call them during conversations.
- **Magento-related MCP**:
  - **Adobe Commerce / Magento MCP** (e.g. codexpect, cursor.directory): Integrates with Magento REST API—no direct SQL/server access. Enables product search, customer/order data, etc. for AI agents.
  - **elgentos/magento2-dev-mcp**: MCP server for Magento 2 **development** tasks (code, debugging).
- **Takeaway**: Think of Magnus as exposing a **MCP-like tool surface** inside the admin: many small, well-described tools (search_products, get_order, open_admin_page, get_config_value, list_modules, run_report, etc.) so the LLM can navigate and act in a composable way.

### Dynamic vs static tools (research)

- **Static**: All tools are sent every time; the LLM picks from a fixed list. Simple but can bloat context and include irrelevant tools.
- **Dynamic**: Tools are **retrieved or filtered** based on the current query and execution context. Studies show **23–104% improvement** in function-calling success and better handling of multi-step tasks. Reduces noise and focuses the model on relevant tools.
- **Takeaway**: For a general-purpose assistant, consider **dynamic tool selection** (e.g. by intent, route, or embedding similarity) so the prompt stays smaller and the LLM sees only relevant tools per turn or per task phase.

---

## 3. Recommendations: Let the Agent Navigate More Freely

### A. Add many small, composable “navigation” and “read” tools

Expose **primitives** the LLM can combine instead of a few coarse skills:

| Tool (example)        | Purpose |
|-----------------------|--------|
| `open_admin_page`     | Build deep link to any admin route (e.g. `catalog/product/index`, `sales/order/view`, `system_config/edit` with section/group). Return URL + short label so the UI can show "Open: Products" / "Open: Order #123". |
| `get_config_value`    | Get current value for a config path (already partly in ConfigExplanationSkill). Expose as a single tool: path in, value + scope out. |
| `search_config_paths`  | Search instance config index by keywords; return matching paths + labels. Lets the LLM find "payment" or "shipping" without hard-coded lists. |
| `search_products`      | Read-only: search by name/SKU/attribute; return list + IDs. LLM can then call `open_admin_page` with product edit URL. |
| `get_order_summary`    | Read-only: order ID → status, total, customer. Enables "show order 10001" then open order page. |
| `list_admin_routes`    | Optional: return a short list of known admin areas (products, orders, customers, config, marketing, etc.) so the LLM can suggest "I can open Products, Orders, or Configuration." |

Implementation options:

- **New skills** that implement `SkillInterface` and map 1:1 to one tool (e.g. `OpenAdminPageSkill`, `SearchProductsSkill`). Keeps current architecture; add to `FunctionSchemaBuilder` and `ToolExecutor`, and extend `buildMessageFromArguments` (or refactor it to a more generic "arguments → message" strategy).
- **Separate tool layer**: Introduce a `ToolInterface` (name, description, parameters, execute) used only for function calling. Skills remain for higher-level flows (how_do_i, explain_config, run_report); tools are the low-level building blocks. The LLM would see both skills and tools in the same tools array.

### B. Soften the system prompt; describe tools, don’t mandate mapping

- **Current**: "Use how_do_i for… Use explain_config when… Use run_report for…" — very directive.
- **Better**: Describe **what each tool does** and when it’s *useful*, and add one line: "You may combine tools (e.g. search then open page, or get config then explain). Prefer using a tool over guessing."
- Keep a short list of tool names and one-line descriptions in the system prompt for stability, but remove the strict "use X for Y" mapping so the LLM can choose and combine tools more freely.

### C. Optional: Dynamic tool set per turn

- **Mechanism**: Before calling the LLM, optionally **filter** the list of tools (e.g. by keyword match, intent classifier, or embedding similarity between user message and tool descriptions). Send only the relevant subset to reduce context and improve selection.
- **Fallback**: Always include a minimal set (e.g. `open_admin_page`, `get_config_value`, `greeting`, `propose_action`) so the agent can still navigate and propose actions even when the filter is wrong.

### D. One “navigate” or “open” tool to rule them all

- A single tool **`navigate_admin`** with parameters such as:
  - `target`: `product_list` | `product_edit` | `order_list` | `order_view` | `customer_list` | `config` | `category_list` | …
  - `id`: optional (product_id, order_id, customer_id for edit/view).
  - `config_path`: optional (for config target).
- Implementation: one skill or tool that uses `AdminUrl` (and any route registry) to build the URL and return it as structured data for the UI. The LLM then only needs to pick target + id/path instead of knowing routes.
- This gives a single entry point for "take me somewhere in the admin" and can be extended with more targets over time.

### E. Decouple ToolExecutor from hard-coded skill names

- Replace the `buildMessageFromArguments()` switch with either:
  - A **generic** rule: e.g. pass through the first argument as "message" when it’s a string, or merge all arguments into a single context string for skills that need it; or
  - Per-skill **argument-to-message** strategy: each skill (or tool) declares how to build a "user message" from its arguments for logging/context, or the executor calls `execute()` with an arguments array and the skill is responsible for interpreting it.
- This makes adding new tools/skills a matter of registration only, without editing the executor.

### F. Align with MCP-style thinking

- Even if Magnus doesn’t speak MCP on the wire, **design the tool surface like an MCP server**: small, well-named tools with clear parameters and return values. That makes it easier later to:
  - Expose the same tools via an MCP server for use in Cursor/Claude, or
  - Document "Magnus tools" in a way that matches how other AI harnesses expect tools (name, description, parameters schema, execute).

---

## 4. Summary Table

| Aspect              | Current Magnus                         | OpenClaw / MCP / Dynamic tools       | Suggested direction for Magnus                    |
|--------------------|----------------------------------------|--------------------------------------|---------------------------------------------------|
| Tool granularity   | Coarse (one tool per “category”)       | Many small, composable tools         | Add navigation/read primitives; optional single `navigate_admin` |
| System prompt      | Directive (“use X for Y”)              | Descriptive; LLM chooses and combines | Soften to “what each tool does” + allow combining |
| Tool set           | Static, all tools every turn           | Optional dynamic retrieval           | Consider dynamic filter by intent/context         |
| New procedures     | New code in HowDoISkill / new skill    | New tool or skill instruction        | New small tools + optional procedure “recipes”   |
| Executor           | Switch on tool name in executor        | Generic dispatch by tool name        | Decouple; generic or per-tool argument handling  |
| “Navigate” ability | Only via fixed how_do_i steps + links  | Open any page, search, then open     | `open_admin_page` / `navigate_admin` + search tools |

---

## 5. Next Steps (concrete)

1. **Short term**
   - Add **`open_admin_page`** (or **`navigate_admin`**): one tool that takes a target + optional id/path and returns a deep link (and label). Expose via a new skill or a new tool interface.
   - Soften **PromptBuilder** system prompt: describe tools, allow combination, remove strict "use X for Y" mapping.
   - Refactor **ToolExecutor::buildMessageFromArguments** to a generic or extensible strategy so new tools don’t require executor changes.

2. **Medium term**
   - Add **read-only tools**: `get_config_value`, `search_config_paths`, `search_products`, `get_order_summary` (or similar). Implement as skills or as a separate tool registry.
   - Optionally introduce a **ToolInterface** (parallel to SkillInterface) for low-level, composable tools and keep skills for higher-level flows.

3. **Longer term**
   - Experiment with **dynamic tool selection** (filter tools by query/context) and measure impact on success rate and latency.
   - Consider exposing the same tool set via **MCP** for use in Cursor/other clients.

This review is intended as a living doc: update it as we implement these changes and learn from usage.
