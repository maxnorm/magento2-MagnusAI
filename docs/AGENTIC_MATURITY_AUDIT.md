# Magnus Assistant — Agentic Maturity Audit

**Purpose:** Evaluate autonomy, reasoning, execution, and reliability of the Magento AI assistant for real merchant operations.  
**Scope:** Not conversational quality; readiness to be trusted with store operations.  
**Date:** 2025-02-13.

**Updates (Phase 1 implementation, 2025-02):** The following gaps have been addressed: (1) **Tool-call history** is now persisted and replayed in conversation history (`tool_context` on messages; PromptBuilder replays assistant + tool messages). (2) **Two write actions** are registered and executable: `config_update` and `catalog_price_rule_create`. (3) **Revenue/AOV/order count** report added (`run_report` query_type `revenue_aov_orders`). (4) **ToolExecutor** decoupled from tool names via optional `ToolMessageBuilderInterface` and generic fallback. Relevant sections below are marked as completed or updated.

---

## 1. Agentic Capability Scorecard

| Dimension | Score (0–5) | Explanation |
|-----------|-------------|-------------|
| **1. Intent comprehension** | **3** | Prompt and tool descriptions give the LLM clear mapping (how_do_i, explain_config, run_report, etc.). No explicit intent classifier; the model infers from text. Works for common phrasings; ambiguous or compound intents (“prepare for Black Friday” = catalog + promo + inventory?) are not decomposed by the system. |
| **2. Ability to ask clarifying questions** | **1** | No built-in flow for “I need X before I can proceed.” The LLM can reply in natural language asking for details, but there is no structured clarification state, no required-slot pattern, and no persistence of “waiting for order_id.” One-shot reply then next user message; no guided multi-turn clarification. |
| **3. Task decomposition / planning** | **2** | No explicit plan step. The agent can call multiple tools in one turn (up to 5 rounds, `MAX_FUNCTION_CALLING_TURNS`). Tool combination is emergent from the model (e.g. search_config_paths → get_config_value → open_admin_page). No plan-and-execute loop, no subgoal tracking, no “step 1 of 4” visibility. Complex goals are not decomposed into a visible plan. |
| **4. Tool/API selection** | **3** | Static tool set (11 tools + propose_action), all sent every turn. Descriptions and system prompt guide selection. No dynamic tool retrieval; no embedding-based or intent-based filtering. Wrong-tool failures (e.g. run_report with unsupported query_type) return a message to the LLM; the model can try again or apologize but there is no automatic fallback or tool suggestion. |
| **5. Multi-step execution** | **4** | Function-calling loop supports up to 5 turns; tool results are appended to messages and the LLM can chain (e.g. search_products → open_admin_page). **Updated:** Conversation history now includes **tool_calls and tool results** for assistant turns (persisted in `tool_context`, replayed by PromptBuilder). Multi-step continuity across **new messages in the same conversation** is restored (e.g. “open the first one” after search_products). |
| **6. Memory and context retention** | **3** | Last 10 messages per conversation (CONVERSATION_HISTORY_LIMIT). **Updated:** Assistant messages can store **tool_context** (JSON of assistant + tool messages) and it is replayed when building the prompt, so the model sees prior tool calls and results. Instance knowledge (config index, module count, admin areas) remains in the system prompt; ContextRetriever refines by keyword. No episodic memory or “remember this” yet. |
| **7. Error detection & recovery** | **2** | Tool errors return `success: false` and a result string to the LLM; the model can react in the next turn. No automatic retry, no “try alternative tool,” no structured error codes. ChatService catches Throwable and returns a generic “I encountered an error” message; no classification (rate limit vs. validation vs. backend error). Approval flow: if propose_action fails (e.g. unknown action_type), the user sees the failure message but there is no “suggest correction” or “closest action” recovery. |
| **8. Output verification** | **1** | No verification layer. Tools return data (e.g. report rows, config value); the LLM summarizes. No check that “top 10 products” actually contains 10 rows, no sanity checks on config values, no comparison of proposed action preview vs. actual outcome after execution. Audit logging exists for executed actions but no post-execution validation that the store state matches intent. |
| **9. Level of autonomy** | **2** | Read-only tools run automatically (open_admin_page, get_config_value, run_report, search_products, etc.). **All writes go through propose_action → user approval.** ApprovalDetector is keyword-based (“yes”, “approve”, “go ahead”); only the **latest** pending action can be approved (“latest”). No time-based or scope-based autonomy (e.g. “apply to all products in category” without confirm). So: low autonomy for anything that changes data; acceptable for read/navigate. |
| **10. Business usefulness of outcome** | **3** | **Updated:** Reports now include **top products by revenue** and **revenue + order count + AOV** for a date range (`revenue_aov_orders`). **Two write actions are registered:** `config_update` (single path, value, scope) and `catalog_price_rule_create` (name, discount, dates, optional category). propose_action → approve → execute works for these. How_do_i and config tools unchanged. Still no conversions trend, slow-moving inventory, or “what to do today” digest. |

**Overall agentic maturity (average of dimensions): ~2.5 — Improved.** Multi-step and memory are stronger; two executable write actions and a second report type increase business usefulness. Still no planning, clarification protocol, or output verification.

---

## 2. Strengths (Where It Already Behaves Like an Operator)

- **Read-only and navigation are first-class:** open_admin_page, get_config_value, search_config_paths, search_products, get_order_summary, list_admin_areas, explain_config give a composable “read + link” experience. The prompt encourages combining tools (e.g. search then open).
- **Human-in-the-loop for writes:** propose_action + approval is the right safety model for store-changing actions. ApprovalDetector and pending action storage are implemented. **Updated:** The registry now includes `config_update` and `catalog_price_rule_create`; propose → approve → execute works for config and catalog price rules.
- **Instance-aware context:** KnowledgeProvider (config schema + values, module list) and ContextRetriever (keyword-relevant chunks) reduce hallucination about config and keep answers tied to the store.
- **Structured UI output:** Tools return `structured_data` (tables, links, steps) so the frontend can render tables and buttons; the agent is not just plain text.
- **Rate limiting and audit:** RateLimiter and AuditLogger (on action execution) are in place for abuse and accountability.
- **Function-calling loop:** Up to 5 turns with tool results fed back allows the LLM to chain tools within a single request (e.g. search products → open product edit page).

---

## 3. Critical Gaps (Where It Fails to Act Independently)

- **~~No registered write actions~~** **Completed:** `ActionTypeRegistry` now includes `config_update` and `catalog_price_rule_create`. propose_action → approve → execute works for single config path updates and catalog price rule creation (name, discount, dates, optional category).
- **~~Conversation history omits tool calls~~** **Completed:** Assistant messages can store `tool_context` (JSON of assistant + tool message shapes). PromptBuilder decodes and replays them when building the prompt, so follow-up messages see prior tool calls and results (e.g. “open the first one” after search_products).
- **No explicit planning or decomposition:** “Prepare my store for Black Friday” or “Optimize slow-moving inventory” are not broken into sub-tasks by the system. The model may call several tools ad hoc but there is no plan state, no progress, and no guarantee of completeness.
- **~~Single report type~~** **Partially addressed:** In addition to “top products by revenue,” `run_report` now supports `revenue_aov_orders` (revenue, order count, AOV for date range). “Why are my conversions down?” still has no period-over-period report; “slow-moving inventory” and “what to do today” remain uncovered.
- **No clarification protocol:** The agent cannot say “I need the order ID” and then persist that it is waiting for it. It can only ask in natural language; the next message is a new turn with no guaranteed slot filling.
- **No output verification:** Tool and action results are not checked for consistency or completeness. No “did the report return 10 rows?” or “did the config update actually apply?”
- **Approval is “latest” only:** Only the most recent pending action can be approved. If the agent proposes two actions in one flow, the user cannot approve the first and then the second by ID.
- **~~Executor tightly coupled to tool names~~** **Completed:** `ToolExecutor::buildMessageFromArguments` now delegates to optional `ToolMessageBuilderInterface` per tool, with a generic fallback (first string arg or JSON). New tools do not require editing ToolExecutor (see SKILLS_AND_TOOLS_REVIEW.md).

---

## 4. Test Scenarios — Mental Run

| Scenario | Plan? | Right tools? | Chain actions? | Validate results? | Ask when needed? | Stop too early? | Human steering? |
|----------|-------|--------------|-----------------|--------------------|------------------|------------------|------------------|
| **Prepare store for Black Friday** | No explicit plan | Partially (how_do_i, open_admin_page, maybe config) | Maybe 2–3 tools | No | Maybe in text | Yes — no checklist or “done” state | Heavy; merchant must do the actual work |
| **Why are conversions down this week?** | No | run_report has revenue_aov_orders (revenue, AOV, order count); no period-over-period | Can show revenue/AOV for period | No | Could ask for date range | Partial — has revenue/AOV, not trend | Moderate |
| **Optimize slow-moving inventory** | No | No inventory report or tool | N/A | No | Could ask what “optimize” means | Yes — no inventory analytics | Heavy |
| **Create promotion for high-margin items** | No | propose_action (catalog_price_rule_create) | propose → approve → execute works for catalog rules | No | Could ask margin threshold | No — can create catalog price rule | Moderate; one rule type executable |
| **Fix products missing images** | No | search_products exists; no “missing image” filter or bulk action | Could search then open one by one | No | Could ask | Yes — no bulk or filter | Heavy |
| **Increase AOV** | No | run_report revenue_aov_orders (includes AOV) | Can report AOV for period | No | Could ask | Partial — can report AOV | Moderate |
| **What should I do today to grow revenue?** | No | run_report (top products only); no recommendations engine | Limited | No | Could ask | Yes — no actionable recommendations | Heavy |

**Conclusion:** The agent can assist with **procedures and navigation**, **multi-turn chains** (tool context is now persisted), and **two write flows** (config_update, catalog_price_rule_create). It can report **revenue, order count, and AOV** for a date range. It still does **not** create plans, validate outcomes, or support clarification slots; it **stops early** for tasks that need more report types (e.g. slow-moving, period-over-period) or other write actions. **Human steering** is still needed for compound goals and for any write not in the two registered actions.

---

## 5. Risk Level If Deployed Today

- **Read-only and navigation:** **Low risk.** Worst case: wrong link or incomplete procedure; no data mutation.
- **Propose_action (writes):** **Low–medium risk.** Two actions are registered (`config_update`, `catalog_price_rule_create`). The existing approval flow is the right mitigation; scope is narrow (single config path, one rule type).
- **Misleading or incomplete advice:** **Medium risk.** The agent can suggest “create a discount” or “check config” without having conversion or AOV data; merchants might act on generic advice. No disclaimer that reports are limited.
- **Reliance for “what to do today” or “why conversions down”:** **High risk.** The system cannot back those answers with store data; answers would be generic or wrong.

**Overall risk if deployed as-is for real money decisions:** **Medium–high** for any use case that implies analytics or recommendations; **low** for “how do I…” and “open this page.”

---

## 6. What Separates This From Shopify Sidekick

- **Sidekick** (and similar commerce agents) typically: (1) integrate with analytics (conversions, AOV, traffic), (2) offer executable actions (create discount, update product, send campaign), (3) maintain session or cross-session context (e.g. “the products we just looked at”), (4) sometimes use a plan step (“here’s what I’ll do”) and then execute.
- **Magnus today:** Strong on **navigation and read-only** (config, products, orders, links). **Updated:** **Context across turns** is addressed (tool-call history persisted and replayed). **Executable writes** are partially addressed (config_update, catalog_price_rule_create). **Analytics** improved (revenue + AOV + order count for date range). Still weak on: **planning** (no explicit plan), **recommendations** (“what to do today”), **period-over-period** and **slow-moving** reports, and **broader write coverage** (product, cart rule, inventory).

---

## 7. Top 5 Capabilities to Build Next (Maximum Leap)

1. **~~Register and implement at least one write action~~** **Completed.** `config_update` and `catalog_price_rule_create` are implemented and registered. Next: product_create, cart_price_rule_create, product_bulk_update, etc.
2. **~~Persist and replay tool_calls in conversation history~~** **Completed.** Message has `tool_context` (JSON); ChatService persists assistant + tool message shapes; PromptBuilder replays them when building the prompt. Multi-step continuity across messages is restored.
3. **Add 2–3 more report types** **Partially done.** `revenue_aov_orders` added (revenue, order count, AOV for date range). Still to add: period-over-period (“why conversions down”), slow-moving / low-turnover, or “orders last N days” breakdown. Expose via run_report with clear query_type so the agent can answer “what should I focus on?” with more data.
4. **Explicit planning or checklist for compound tasks.** For intents like “prepare for Black Friday,” either: (a) a dedicated tool that returns a checklist (catalog, promos, inventory, etc.) with status, or (b) a system prompt + tool pattern that forces the model to output “Plan: 1. … 2. …” and then execute step by step. This improves completeness and reduces “stop too early.”
5. **~~Decouple ToolExecutor from tool names~~** **Completed.** Optional `ToolMessageBuilderInterface` + generic fallback in ToolExecutor; all existing tools implement the interface. New tools can opt in without editing the executor.

---

## 8. Estimated Maturity Level

| Level | Description | Magnus placement |
|-------|-------------|------------------|
| **Chatbot** | Answers questions; no tools or store actions | Above this (has tools) |
| **Assistant** | Uses tools for read/navigate; suggests steps | Above this |
| **Semi-Agent** | Plans, multi-step with memory, some writes with approval | **← Current** (multi-step + tool history + 2 write actions; no explicit plan) |
| **Operator** | Executes full workflows with verification and recovery | Not yet |
| **Autonomous** | High autonomy with guardrails and oversight | No |

**Verdict:** **Semi-Agent.** The agent has **persistent tool context** across turns, **two executable write actions** (config_update, catalog_price_rule_create), and **revenue/AOV/order count** reporting. It can chain tools and complete “create a 10% discount for category X” → propose → approve → execute, and follow-up messages see prior tool results. It still lacks **explicit planning**, **clarification protocol**, and **output verification**; more report types and write actions would strengthen Operator readiness.

---

## 9. Summary Table

| Aspect | Status | Note |
|--------|--------|------|
| Intent comprehension | OK | Prompt + tools; no classifier |
| Clarifying questions | Weak | No structured clarification |
| Task decomposition | Weak | No explicit plan |
| Tool selection | OK | Static set; no dynamic filter |
| Multi-step execution | OK | Tool context persisted and replayed; continuity across messages |
| Memory / context | OK | 10 messages + tool_context for assistant turns; no episodic memory |
| Error detection & recovery | Weak | Message to LLM only; no retry or codes |
| Output verification | Missing | No checks on tool/action results |
| Autonomy | Medium | Reads auto; writes need approval; 2 actions registered (config, catalog rule) |
| Business usefulness | Improved | Two reports (top products, revenue/AOV/orders); 2 write actions; procedures + links |
| **Maturity** | **Semi-Agent** | Multi-step + tool history + writes; not yet Operator (no plan, no verification) |

---

*This audit is based on static code review of the Magnus Assistant module (PromptBuilder, ChatService, ToolExecutor, all tools, Action* components, KnowledgeProvider, ContextRetriever, di.xml, and SKILLS_AND_TOOLS_REVIEW.md). No live LLM or UI testing was performed.*

*Sections marked **Completed** or **Updated** reflect Phase 1 implementation: tool_context persistence, config_update and catalog_price_rule_create actions, revenue_aov_orders report, and ToolMessageBuilderInterface.*
