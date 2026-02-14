# Magento Sidekick Clone: Full Feature List & Instance Learning

**Goal:** Store-agnostic Magento 2 module that behaves like Shopify Sidekick and **learns over time** each Magento instance’s setup, config, and context to better help the store owner.

---

## 1. Core Platform (Chat & UX)

| # | Feature | Sidekick behavior | Magento implication |
|---|--------|--------------------|----------------------|
| 1.1 | **Admin chat UI** | Persistent chat sidebar/panel in admin; conversation history; full-screen mode | Admin layout + JS (e.g. Knockout or Alpine) for sidebar; session or DB-backed conversation history; responsive for desktop/tablet |
| 1.2 | **Entry point** | Always-visible entry (e.g. icon) in admin header/shell | Add block + layout XML to admin; icon in header; no dependency on specific theme |
| 1.3 | **Conversation context** | Multi-turn context; optional “current page” or “current entity” awareness | Pass current route, entity type/ID (product, order, CMS page, etc.) as context to backend; store in session or per-conversation |
| 1.4 | **Feedback** | Thumbs up/down or similar on replies | Store feedback linked to message/session for model improvement and learning (per-instance or aggregated) |
| 1.5 | **Mobile / accessibility** | Usable on small screens; optional voice input (e.g. mobile) | Responsive panel; optional Web Speech API or future voice pipeline; ARIA and keyboard support |
| 1.6 | **Localization** | UI and, where applicable, model language follow store/admin locale | Use `__()` in templates; pass locale to LLM; respect admin locale for dates/numbers |

---

## 2. Writer (Content Generation)

| # | Feature | Sidekick behavior | Magento implication |
|---|--------|--------------------|----------------------|
| 2.1 | **Product copy** | Generate name, short/long description, meta title/description from prompt or attributes | Use Catalog Product Repository + attributes; call LLM with product context; **suggest** text only; apply only with merchant approval (write via API or form) |
| 2.2 | **Category copy** | Same for category name, description, meta | Category repository; same approval flow |
| 2.3 | **CMS content** | Blocks and pages: headings, body, meta | Cms Block/Page repositories; suggest content; apply with approval |
| 2.4 | **Email content** | Transactional/email template subject and body; campaign copy | Read email templates (DB or config); suggest variants; no direct send without approval |
| 2.5 | **Marketing copy** | Ad copy, social posts, promo text | No direct Magento “campaign” entity; generate text only; optional integration with marketing modules if present |
| 2.6 | **Bulk / rule-based** | Apply generation rules across many products (e.g. “tone”, “length”) | Background or queue job; respect “suggest then approve”; optionally use existing attributes (e.g. brand) for rules |
| 2.7 | **Localization** | Generate or suggest content in multiple store locales | Use store view scope; pass locale to LLM; suggest per-store content |

---

## 3. Designer & Photo Editor

| # | Feature | Sidekick behavior | Magento implication |
|---|--------|--------------------|----------------------|
| 3.1 | **Theme/layout hints** | Suggest layout or theme changes from natural language | Read theme/layout XML structure (read-only); suggest changes as instructions or snippets; no direct file write in core—optional “export” for devs |
| 3.2 | **Image improvement** | “Studio-quality” product images; background removal; resizing | Call external image API (e.g. OpenAI, dedicated image service); upload result to Magento Media Gallery via API; **always with approval** (preview then confirm) |
| 3.3 | **Media from prompt** | Generate images from text for placeholders or marketing | Same as 3.2: external API → Media Gallery with approval |
| 3.4 | **Alt text / captions** | Generate alt text and captions for existing media | Read media metadata; suggest alt/caption; save via repository with approval |

---

## 4. Tech Support & Setup

| # | Feature | Sidekick behavior | Magento implication |
|---|--------|--------------------|----------------------|
| 4.1 | **Config explanation** | Explain what a config path or section does | **Instance learning:** index system config (paths, labels, scope) from `config.xml` / `system.xml` and DB; answer “what does X do?” with store-specific path and value |
| 4.2 | **Config recommendations** | Suggest config changes (e.g. “enable X for better Y”) | Same knowledge base; suggest path + value; **never write** without explicit approval; support default/website/store scope |
| 4.3 | **Setup wizard / checklist** | Guided setup steps (domain, tax, shipping, etc.) | Curated checklist (config flags, optional modules); mark steps done by reading config/code; deep links to admin URLs |
| 4.4 | **Troubleshooting** | Interpret errors, logs, stack traces | **Instance learning:** known modules, versions, customizations; read exception/log (sanitized); suggest fixes or next steps; optional log file parsing (with care for PII/secrets) |
| 4.5 | **“How do I…?”** | Answer procedural questions (e.g. “how do I add a product?”) | Combine static Magento docs with **instance-aware** steps (e.g. “in your store you have X, so do Y”); use learned modules and config |
| 4.6 | **Domain / URL** | Help with base URL, domain, SSL | Read `core_config_data` and store config; explain and suggest; no direct DNS/SSL changes |

---

## 5. Marketer & Growth

| # | Feature | Sidekick behavior | Magento implication |
|---|--------|--------------------|----------------------|
| 5.1 | **Campaign ideas** | Suggest campaigns, channels, timing | Use learned data: product mix, past orders, segments; suggest ideas only; optional link to marketing automation if present |
| 5.2 | **Segments** | Suggest or create customer segments from natural language | Map to Magento Customer Segment rules (if module present) or report definitions; **create/update only with approval** |
| 5.3 | **Discounts / promotions** | “Create a 20% off category X” | Build cart rule / catalog rule structure; present for approval; create via Rule repository (or equivalent) after confirm |
| 5.4 | **Reports** | Answer “top 10 products”, “revenue by …”, “refund rate” | **Read-only** analytics: use Sales, Catalog, Order APIs and reports; generate SQL or use collection/report APIs; return tables/charts; never write |
| 5.5 | **Benchmarks / goals** | “How am I doing vs last month?” | Compare periods from order/catalog data; store optional “goals” in instance knowledge if we add that later |

---

## 6. Data Analyst (Queries & Reports)

| # | Feature | Sidekick behavior | Magento implication |
|---|--------|--------------------|----------------------|
| 6.1 | **Natural-language queries** | “Top 10 products by revenue last 30 days” | **Safe read-only** layer: either (a) predefined query templates mapped to NL, or (b) generated SQL validated and restricted (e.g. SELECT only, allowed tables, no sensitive columns); return tabular/csv |
| 6.2 | **Suggested questions** | Pre-built prompts (e.g. “Best sellers”, “At-risk stock”) | List of templates; optionally personalized from instance learning (e.g. “your top category”) |
| 6.3 | **Export** | CSV/Excel export of query results | Use Magento export or custom; respect ACL and scope |
| 6.4 | **Explain query** | “Why did you run this SQL?” | Store “reasoning” or query explanation with each answer (transparency and learning) |

---

## 7. Proactive (Sidekick Pulse–style)

| # | Feature | Sidekick behavior | Magento implication |
|---|--------|--------------------|----------------------|
| 7.1 | **Proactive cards** | Up to N “recommendations” (e.g. 5) per period; shown in admin or inside chat | Background job (cron) that analyzes orders, catalog, config, inventory; produces recommendation cards (title, body, citation, optional action link); store in DB per store/view |
| 7.2 | **Citations** | Each recommendation links to evidence (e.g. report, config page) | Card model includes “sources” (URLs, entity IDs, config paths); pass to Sidekick when user asks “tell me more” |
| 7.3 | **Action from card** | “Act on this” opens chat with full context | Card has payload (e.g. pre-filled prompt or intent); opening chat loads that context |
| 7.4 | **Personalization** | Recommendations depend on store size, vertical, and history | **Instance learning:** use store profile (revenue band, product count, modules, config) to choose which recommendation engine runs and how it’s phrased |
| 7.5 | **Frequency** | Pulse runs periodically (e.g. daily); no spam | Cron schedule configurable; respect “last run” and rate limits |

---

## 8. Workflows & Automation

| # | Feature | Sidekick behavior | Magento implication |
|---|--------|--------------------|----------------------|
| 8.1 | **Describe automation** | “When order is placed, add tag and notify” | Map to Magento events + actions (e.g. observer, queue, cron); **describe** intended flow; optional export as config or code snippet |
| 8.2 | **Build workflow** | Generate trigger + conditions + actions from NL | If using a workflow engine (e.g. custom or BPM): generate config from NL and present for approval; else suggest observer/cron design only |
| 8.3 | **No execution without approval** | All write/automation steps require explicit merchant OK | Every state-changing action (config write, rule create, content apply) goes through “propose → show diff → confirm”; log approvals for audit |

---

## 9. App / Extension Awareness (Sidekick App Extensions–style)

| # | Feature | Sidekick behavior | Magento implication |
|---|--------|--------------------|----------------------|
| 9.1 | **Discover extensions** | Know which “apps” (modules) are installed | **Instance learning:** read `app/etc/config.php` and module list; optionally read composer.json and version; no PII |
| 9.2 | **Extension-specific Q&A** | “How does X work?” using extension’s config/docs | Per-extension “knowledge”: system config paths, ACL, admin routes; optionally ingest README or admin docs if provided by vendor |
| 9.3 | **Scoped actions** | Extensions expose safe actions (e.g. “sync”, “export”) | Extension points: other modules can register “Sidekick actions” (name, description, handler); Sidekick shows them in chat and runs only with approval |
| 9.4 | **Navigate to extension** | “Open X” → deep link to admin URL | Use learned admin routes and ACL; generate URLs for current admin user |

---

## 10. Instance Learning (Store-Agnostic, Learns This Store)

What the module **discovers and maintains over time** so it can adapt to any Magento instance without hardcoding store-specific details.

| # | What we learn | How we learn it | Used for |
|---|----------------|-----------------|----------|
| 10.1 | **Module list** | `ModuleList`, `config.php`, composer | Tech support, “how do I”, extension-aware answers, proactive logic |
| 10.2 | **System config schema** | Collect from `etc/config.xml` / `system.xml` (and DB) per scope | Explaining config, suggesting changes, troubleshooting |
| 10.3 | **Config values (non-secret)** | `ScopeConfig` + allowed paths (no passwords, keys, tokens) | “What is X set to?”, recommendations, setup checklist |
| 10.4 | **Store structure** | Websites, store views, locales, currencies | Localization, scope in queries, “your store has N stores” |
| 10.5 | **Catalog shape** | Attribute sets, product types, category tree depth, count | Query templates, content generation context, proactive tips |
| 10.6 | **Admin routes & ACL** | Admin routing and ACL resources | “Open X” links, permission-aware suggestions |
| 10.7 | **Theme / locale** | Active theme, admin locale | Designer hints, language of generated content |
| 10.8 | **Conversation & feedback** | Stored messages, thumbs up/down, optional corrections | Fine-tune prompts, improve templates, avoid repeated bad answers |
| 10.9 | **Optional: custom glossary** | Merchant-defined terms (e.g. “SKU X means Y”) | Better NL understanding and generated copy |
| 10.10 | **Optional: goals / KPI** | Merchant-set targets (e.g. “reach $X revenue”) | Proactive cards and “how am I doing?” answers |

Implementation notes for learning:

- **Store-agnostic:** No hardcoded store names, domains, or merchant identity; all context is discovered at runtime.
- **Refresh:** Learning can be recomputed on config change, module enable/disable, or on a schedule; keep a “knowledge” version/checksum to invalidate when needed.
- **Storage:** “Instance knowledge” can live in DB (e.g. JSON or normalized tables) or cache with long TTL; secrets must never be stored in this layer.
- **Privacy:** No sending of PII or full config dumps to external LLM without consent and masking; prefer summaries and structure.

---

## 11. Security, Compliance & Safety

| # | Requirement | Magento implication |
|---|-------------|----------------------|
| 11.1 | **ACL** | Sidekick UI and API restricted by ACL; same admin user as rest of admin |
| 11.2 | **Read-only by default** | All data access is read-only unless an explicit “approve” flow is used |
| 11.3 | **No secrets in prompts** | Strip or mask passwords, API keys, tokens before sending to any LLM or external service |
| 11.4 | **Audit** | Log approved actions (who, when, what) for compliance |
| 11.5 | **Configurable LLM** | Support different backends (e.g. OpenAI, Anthropic, self-hosted); API keys in config, not code |
| 11.6 | **Rate limiting** | Throttle requests to LLM and to Magento APIs to avoid abuse and cost |

---

## 12. Summary: Feature Count by Area

| Area | Feature count (approx.) |
|------|--------------------------|
| Core platform (chat, UX) | 6 |
| Writer | 7 |
| Designer & photo | 4 |
| Tech support & setup | 6 |
| Marketer & growth | 5 |
| Data analyst | 4 |
| Proactive (Pulse) | 5 |
| Workflows & automation | 3 |
| App/extension awareness | 4 |
| Instance learning | 10 (dimensions) |
| Security & safety | 6 |

**Total:** on the order of **60 distinct feature dimensions** to consider for a full Sidekick clone, with **instance learning** as the cross-cutting capability that makes the module store-agnostic and improving over time.

---

## 13. Feature ranking by merchant value (Magento)

Ranking is based on: **Shopify merchant usage** (what store owners use Sidekick for most), **Magento merchant pain points** (repetitive admin work, slow search, config confusion, lack of automation), and **impact × frequency**. Ordered **highest → lowest value** for a merchant using Magento.

| Rank | Feature (section.id) | Why it ranks here |
|------|----------------------|-------------------|
| 1 | **Reports / NL queries** (5.4, 6.1) | Shopify merchants use “instant reports” and data questions constantly. Magento admin search/reports are often slow; “top 10 products”, “revenue by …” in plain language saves time daily. |
| 2 | **Product copy** (2.1) | Most-cited Sidekick use: product descriptions. Magento pain = repetitive catalog work per product; generating copy from attributes + approval cuts hours. |
| 3 | **Discounts / promotions** (5.3) | “Create a 20% off code” in natural language is a top Sidekick use case. Direct value: fewer clicks, fewer mistakes, faster campaigns. |
| 4 | **“How do I…?”** (4.5) | Magento admin is complex; “how do I add a product / set up shipping?” with **instance-aware** steps (your theme, your modules) reduces support and onboarding time. |
| 5 | **Proactive cards / Pulse** (7.1–7.4) | “Best advice you never asked for.” Surfaces growth opportunities and tasks; high perceived value and differentiation once chat is in place. |
| 6 | **Config explanation** (4.1) | “What does this setting do?” with store-specific path and value. Magento has hundreds of config paths; this reduces trial-and-error and support. |
| 7 | **Admin chat UI + entry** (1.1, 1.2) | Foundation: without a persistent, easy-to-find chat, nothing else is used. Must be reliable and visible. |
| 8 | **Bulk / rule-based content** (2.6) | Scaling product copy to hundreds of SKUs. Mirrors Magento pain: “manually adding products to categories, metadata” at scale. |
| 9 | **Troubleshooting** (4.4) | Interpreting errors and logs with instance context (modules, versions). Reduces downtime and dev dependency. |
| 10 | **Export** (6.3) | Merchants want to take report results to Excel/CSV; natural follow-up to NL queries. |
| 11 | **Segments** (5.2) | Customer segmentation + targeted marketing is a common Sidekick use; create/update segments from NL with approval. |
| 12 | **Conversation context** (1.3) | “I’m on this product/order” makes answers relevant and reduces back-and-forth; improves perceived quality. |
| 13 | **Suggested questions** (6.2) | Lowers friction to use the assistant; “Best sellers”, “At-risk stock” as one-click prompts. |
| 14 | **Config recommendations** (4.2) | “Enable X for better Y” with safe suggestions; helps optimization without guessing. |
| 15 | **Setup wizard / checklist** (4.3) | Guided setup (domain, tax, shipping) with deep links; valuable for new stores or new staff. |
| 16 | **Category copy** (2.2) | Same value as product copy but less frequent (fewer categories than products). |
| 17 | **Email content** (2.4) | Transactional and campaign email copy; commonly requested in Shopify usage. |
| 18 | **Instance learning – config & modules** (10.1–10.3) | Enables 4.1, 4.2, 4.4, 4.5; store-agnostic and accurate answers. Cross-cutting high value. |
| 19 | **Image improvement / alt text** (3.2, 3.4) | Product photos and alt text matter for conversion; “studio-quality” and captions are cited Sidekick uses. |
| 20 | **Campaign ideas** (5.1) | Suggestions based on product mix and data; idea generation, not execution. |
| 21 | **Benchmarks / goals** (5.5) | “How am I doing vs last month?”; good for motivation and quick health checks. |
| 22 | **Explain query** (6.4) | Transparency for analyst-style answers; builds trust and helps merchants learn. |
| 23 | **Navigate to extension** (9.4) | “Open X” deep links; saves time in a multi-extension Magento stack. |
| 24 | **Discover extensions** (9.1) | “What modules do I have?”; basis for extension-aware Q&A and navigation. |
| 25 | **Describe automation** (8.1) | “When order is placed, do X”; first step before building workflows; educates and reduces custom dev. |
| 26 | **CMS content** (2.3) | Blocks and pages; useful but less frequent than product/category. |
| 27 | **Marketing copy** (2.5) | Ad/social copy; no direct Magento entity; generate text only. |
| 28 | **Feedback** (1.4) | Improves answers over time; indirect value but important for quality. |
| 29 | **Action from card** (7.3) | “Act on this” from Pulse; ties proactive value to chat actions. |
| 30 | **Citations** (7.2) | Evidence links for recommendations; trust and follow-through. |
| 31 | **Workflow build / no execution without approval** (8.2, 8.3) | Automation from NL is a Sidekick highlight; in Magento, often “describe + suggest” unless a workflow engine exists. Approval flow is mandatory. |
| 32 | **Theme/layout hints** (3.1) | Design suggestions; more niche than content or reports. |
| 33 | **Extension-specific Q&A** (9.2) | “How does module X work?”; valuable in extension-heavy stores. |
| 34 | **Scoped actions** (9.3) | Other modules expose actions to Sidekick; platform play, value grows with ecosystem. |
| 35 | **Writer localization** (2.7) | Multi-store/locale copy; important for international stores, narrower audience. |
| 36 | **Domain / URL** (4.6) | One-time or rare setup; still useful when needed. |
| 37 | **Media from prompt** (3.3) | Generate images from text; placeholders/marketing; depends on external API. |
| 38 | **Personalization / frequency** (7.4, 7.5) | Pulse tuning; avoids spam and increases relevance. |
| 39 | **Instance learning – catalog & routes** (10.4–10.7) | Store structure, catalog shape, admin routes; supports queries, content, and “Open X”. |
| 40 | **Localization (UI)** (1.6) | Admin locale and dates; important for non-English stores. |
| 41 | **Mobile / accessibility** (1.5) | Broader access; voice input is a differentiator on mobile. |
| 42 | **Conversation & feedback learning** (10.8) | Improves over time from usage; long-term quality. |
| 43 | **Optional glossary / goals** (10.9, 10.10) | Custom terms and KPI targets; power-user and proactive refinement. |

**Summary for prioritization**

- **Tier 1 (must-have for MVP):** 1–7 and 18 (chat UI, reports/NL queries, product copy, discounts, “how do I”, Pulse, config explanation, plus instance learning for config/modules).
- **Tier 2 (high value, next phase):** 8–15 (bulk content, troubleshooting, export, segments, context, suggested questions, config recommendations, setup checklist).
- **Tier 3 (differentiation and scale):** 16–27 (category/email/marketing copy, image/alt, campaign ideas, benchmarks, explain query, extension discovery/navigate, automation describe, CMS, feedback, Pulse actions/citations).
- **Tier 4 (ecosystem and polish):** 28–43 (workflow approval, theme hints, extension Q&A/scoped actions, localization, domain, media from prompt, instance learning catalog/routes, UI locale, mobile, learning from feedback, glossary/goals).

---

## 14. Definition of Done (module, aligned with Sidekick)

The module is **done** for a given release when the following hold, in line with what merchants expect from a Sidekick-style assistant.

**Product**

- **Discoverable:** Merchant can open the assistant from the admin in one click (header/shell entry) and see it as a persistent chat panel or equivalent.
- **Conversational:** Merchant can ask in natural language and get a relevant answer (or a clear “I can’t do that yet”) with multi-turn context where applicable.
- **Safe:** No state-changing actions (config write, content save, rule create, etc.) without explicit merchant approval; no secrets sent to external services; ACL enforced.
- **Instance-aware:** Answers and suggestions reflect this store (config, modules, scope) where relevant, not a generic Magento answer.
- **Usable:** Response time and UX are acceptable (e.g. no indefinite loading); errors are surfaced clearly; feedback (e.g. thumbs up/down) is captured where implemented.

**Engineering**

- **Store-agnostic:** Works on a vanilla Magento 2.4.x (or declared versions) without hardcoded store/domain; instance knowledge is discovered at runtime.
- **Extensible:** New capabilities (new “skills”, actions, or data sources) can be added via configuration or extension points without rewriting core chat flow.
- **Documented:** Admin user guide (how to use the assistant) and developer notes (how to extend, config, LLM keys) exist for the scope of the release.
- **Tested:** Critical paths (e.g. send message → get reply, approve action → state change) have automated tests; no known regressions for the release scope.

**Per-feature DoD**

- Each shipped feature meets: **functional** (does what the plan says), **secure** (no new ACL or data-leak issues), **observable** (logged or measurable where it matters), and **reversible** (config or feature flag where appropriate).

---

## 15. Jobs To Be Done (JTBD) — problems the merchant can solve

For each major problem the module addresses, the job is stated in the form: **When [situation], I want to [action], so that [outcome].**

### Core / access

| Job | JTBD |
|-----|------|
| **Get help without leaving admin** | When I’m in the Magento admin, I want to ask a question or give an instruction in plain language, so that I get an answer or something done without opening docs or another tool. |
| **Resume where I left off** | When I return to the admin, I want to see my recent conversation with the assistant, so that I can continue or refer back without repeating myself. |

### Writer (content)

| Job | JTBD |
|-----|------|
| **Product copy** | When I’m adding or editing a product, I want the assistant to suggest name, description, or meta from the product’s attributes, so that I get usable copy quickly and can edit before saving. |
| **Category copy** | When I’m editing a category, I want the assistant to suggest name, description, or meta, so that I keep categories consistent and SEO-friendly without writing from scratch. |
| **Bulk product copy** | When I have many products with missing or weak copy, I want to request generation by rule (e.g. tone, length) and approve in batches, so that I scale content without a full-time copywriter. |
| **Email / campaign copy** | When I’m preparing an email or campaign, I want the assistant to suggest subject lines and body copy, so that I can test variants and send faster. |
| **CMS content** | When I’m editing a block or page, I want the assistant to suggest headings or body text, so that I fill CMS content without leaving the admin. |

### Tech support & setup

| Job | JTBD |
|-----|------|
| **Understand a setting** | When I see a config path or section I don’t recognize, I want to ask “what does this do?” and get an explanation (and current value for this store), so that I don’t guess or break something. |
| **Get config recommendations** | When I’m optimizing the store, I want the assistant to suggest specific config changes with reasoning, so that I can apply them with confidence (and approve before any write). |
| **Learn a procedure** | When I need to do something in the admin (e.g. add a product, set up shipping), I want step-by-step instructions that match my store (themes, modules), so that I don’t waste time on generic or wrong steps. |
| **Fix an error** | When I see an error or stack trace, I want to paste it and get an explanation and suggested fix (using my modules/versions), so that I can resolve it or hand off to dev with context. |
| **Complete setup** | When I’m new to the store or onboarding, I want a guided checklist (domain, tax, shipping, etc.) with deep links, so that I don’t miss critical setup steps. |

### Data / analyst

| Job | JTBD |
|-----|------|
| **Ask a data question** | When I need a number or list (e.g. “top 10 products last 30 days”, “revenue by category”), I want to ask in plain language and get a table or chart, so that I don’t build reports or write SQL. |
| **Export results** | When the assistant returns data, I want to export it (e.g. CSV), so that I can use it in spreadsheets or other tools. |
| **Understand the answer** | When I get a data answer, I want to see why that query was run (e.g. “I used orders from the last 30 days”), so that I trust and reuse the logic. |

### Marketer & growth

| Job | JTBD |
|-----|------|
| **Create a promotion** | When I want to run a discount (e.g. “20% off category X”), I want to describe it in natural language and have the assistant propose the rule, so that I approve and create it without clicking through many screens. |
| **Define a segment** | When I want to target a group of customers (e.g. “bought in last 90 days, never returned”), I want to describe it and get a segment definition or rule, so that I can create or update it with approval. |
| **Compare periods** | When I want to know how the store is doing, I want to ask “how am I doing vs last month?” and get a clear comparison, so that I can act on trends without building reports. |
| **Get campaign ideas** | When I’m planning marketing, I want the assistant to suggest campaigns or channels based on my products and data, so that I have a starting point for execution. |

### Designer & media

| Job | JTBD |
|-----|------|
| **Improve product images** | When I have low-quality or cluttered product photos, I want the assistant to suggest or generate improved versions (e.g. background removal), so that I can approve and add them to the catalog. |
| **Get alt text** | When I’m adding or editing media, I want the assistant to suggest alt text or captions, so that I improve accessibility and SEO without writing each one. |
| **Get design guidance** | When I’m changing theme or layout, I want the assistant to suggest changes or snippets from my description, so that I can hand off to a dev or apply carefully. |

### Proactive (Pulse-style)

| Job | JTBD |
|-----|------|
| **See what to do next** | When I open the admin, I want to see a short list of prioritized recommendations (with evidence links), so that I focus on high-impact tasks without figuring them out myself. |
| **Act on a recommendation** | When I see a recommendation card, I want to click “act on this” and have the assistant open with full context, so that I can execute without re-explaining. |

### Extensions & automation

| Job | JTBD |
|-----|------|
| **Find my extensions** | When I’m not sure what’s installed, I want to ask “what modules do I have?” and get a clear list, so that I know what’s available and can ask extension-specific questions. |
| **Open an extension** | When the assistant refers to a feature (e.g. “go to X”), I want a direct link to the right admin page, so that I don’t hunt through menus. |
| **Understand automation** | When I want to automate something (e.g. “when order is placed, notify me”), I want the assistant to describe the flow and, if possible, suggest how to implement it in Magento, so that I can hand off to dev or configure with confidence. |

---

## 16. Deliverable phases (development roadmap to complete version)

Phases build on each other. Each phase ships a usable increment and leaves the codebase **modular and extensible** so the next phase adds capabilities without rewriting the core.

---

### Phase 1 — Foundation (platform + extensibility)

**Goal:** Ship a working admin chat that can route to pluggable “skills,” with no store-specific hardcoding and a baseline of instance knowledge.

**Deliverables**

| Deliverable | Description |
|-------------|-------------|
| Admin chat UI | Persistent chat panel (sidebar or slide-out); header entry point; responsive layout. |
| Conversation persistence | Store messages and turns (session or DB); conversation history in UI. |
| Chat API | Backend endpoint: receive message + optional context (route, entity), return reply; multi-turn context in request. |
| LLM integration | Configurable provider (e.g. OpenAI); API key in config; prompt assembly; no secrets in payloads; rate limiting. |
| Skill/action registry | Extension point: register “skills” (intent → handler) and “actions” (propose → approve → execute); router calls the right skill or returns “I can’t do that yet.” |
| Instance learning — modules & config schema | Index: module list (enabled), system config paths/labels from `config.xml`/`system.xml` (no secret values); store in cache/DB; refresh on config change or schedule. |
| Security baseline | ACL for Sidekick UI and API; read-only by default; audit log for approved actions (stub if no actions yet). |
| Admin config | System config: enable/disable module, LLM provider, API key (masked), optional feature flags. |

**Features from plan:** 1.1, 1.2, 18 (instance learning 10.1–10.2 only), 11.1–11.2, 11.5, 11.6.

**JTBD:** Get help without leaving admin (partial — answers only from built-in fallback or one demo skill); resume where I left off.

**Definition of done for Phase 1:** Merchant can open chat, send a message, get a reply (e.g. “I’m here; ask me about your store” or one demo skill), see history; new skills can be added via registry without changing core.

---

### Phase 2 — MVP: Answer + guide (first merchant value)

**Goal:** Merchant gets real value: instance-aware “how do I?” and “what does this config do?”, plus one simple data answer (e.g. “top 10 products last 30 days”).

**Deliverables**

| Deliverable | Description |
|-------------|-------------|
| Instance learning — config values | Extend index with non-secret config values (allowed paths); used by config explanation and “how do I?”. |
| Skill: “How do I…?” | Procedural answers using instance-aware steps (modules, config); optional deep links to admin pages. |
| Skill: Config explanation | “What does X do?” returns explanation + current value for this store/scope. |
| Skill: One NL report | Single predefined query template (e.g. top N products by revenue, last 30 days); safe read-only; return table in chat. |
| Suggested questions (minimal) | 3–5 one-click prompts (e.g. “What are my top products?”, “How do I add a product?”) to lower friction. |

**Features from plan:** 4.1, 4.5, 6.1 (one template), 6.2 (minimal), 10.3.

**JTBD:** Get help without leaving admin; understand a setting; learn a procedure; ask a data question (one type).

**Definition of done for Phase 2:** Merchant can ask “how do I add a product?”, “what does catalog search do?”, and “top 10 products last month” and get correct, instance-aware answers; DoD §14 satisfied for this scope.

---

### Phase 3 — Tier 1 complete (reports, copy, discounts, Pulse)

**Goal:** Full Tier 1: NL reports (multiple templates), product copy (suggest → approve), create discounts (propose → approve), Proactive cards (Pulse), config recommendations.

**Deliverables**

| Deliverable | Description |
|-------------|-------------|
| Skill: NL reports (full) | Multiple query templates (top products, revenue by period/category, refund rate, etc.); optional date range; return table; explain query (short “why” text). |
| Skill: Product copy | Generate name, short/long description, meta from product attributes + prompt; suggest in chat; “Apply” writes to product after approval (with diff/preview). |
| Skill: Discounts / promotions | Parse NL (e.g. “20% off category X”); build cart rule or catalog rule structure; show summary; create only after approval; audit log. |
| Skill: Config recommendations | Suggest config changes with reasoning; no write without approval; use instance learning. |
| Proactive cards (Pulse) | Cron job: analyze orders/catalog/config; produce up to N recommendation cards (title, body, citations, action link); store in DB; show in admin (dashboard or chat area). |
| Action from card | Card “Act on this” opens chat with pre-filled context so merchant can continue in conversation. |
| Export (reports) | For report answers: “Download CSV” (or equivalent) from chat. |

**Features from plan:** 5.4, 6.1, 6.3, 6.4, 2.1, 5.3, 4.2, 7.1–7.3.

**JTBD:** Product copy; create a promotion; ask a data question (full); export results; understand the answer; see what to do next; act on a recommendation; get config recommendations.

**Definition of done for Phase 3:** Merchant can run several report types, generate and apply product copy with approval, create a discount from NL with approval, see Pulse cards and act from them, and export report data; DoD §14 satisfied.

---

### Phase 4 — Tier 2 (scale + support)

**Goal:** Bulk content, troubleshooting, segments, conversation context, suggested questions (full), setup checklist.

**Deliverables**

| Deliverable | Description |
|-------------|-------------|
| Conversation context | Pass current admin route and entity (product ID, order ID, CMS page, etc.) into chat API; skills can use “you’re on product X” in answers. |
| Skill: Bulk product copy | Rule-based generation (tone, length, attribute-based); queue job; suggest batch → approve per product or batch. |
| Skill: Troubleshooting | Accept pasted error/log (sanitized); use instance modules/versions; return explanation and suggested fix. |
| Skill: Segments | Map NL to customer segment rules (if module present) or segment definition; propose creation/update; execute only after approval. |
| Suggested questions (full) | Broader set of prompts (e.g. best sellers, at-risk stock, how do I…, config); optionally personalized from instance. |
| Setup wizard / checklist | Curated checklist (domain, tax, shipping, etc.); read config to mark done; deep links to admin; show in chat or dedicated block. |

**Features from plan:** 1.3, 2.6, 4.4, 5.2, 6.2 (full), 4.3.

**JTBD:** Bulk product copy; fix an error; define a segment; complete setup; resume where I left off (with context).

**Definition of done for Phase 4:** Merchant can use context-aware chat, run bulk copy with approval, get error help, create/update segments with approval, use full suggested questions, and run through setup checklist; DoD §14 satisfied.

---

### Phase 5 — Tier 3 (differentiation + scale)

**Goal:** Category/email/CMS/marketing copy, image improvement and alt text, campaign ideas, benchmarks, explain query (richer), extension discovery and “Open X,” describe automation, feedback.

**Deliverables**

| Deliverable | Description |
|-------------|-------------|
| Skills: Category, CMS, email, marketing copy | Same pattern as product copy: suggest from context → approve → apply (where applicable). |
| Skill: Image improvement + alt text | Integrate external image API (e.g. background removal); suggest alt/caption; upload to Media Gallery only after approval. |
| Skill: Campaign ideas | Suggest campaigns/channels from product mix and order data; no execution. |
| Skill: Benchmarks | “How am I doing vs last month?” — compare key metrics; optional goals from instance learning later. |
| Explain query (rich) | For each report answer: clear “I used …” explanation (query logic, filters, scope). |
| Instance learning — catalog & routes | Catalog shape (attribute sets, product types, category tree); admin routes and ACL; used for “Open X” and richer answers. |
| Skill: Discover extensions + navigate | “What modules do I have?”; “Open [feature]” → deep link to admin URL (ACL-aware). |
| Skill: Describe automation | “When order is placed, do X” → describe flow in Magento terms (events, observers, cron); optional snippet or link to docs. |
| Feedback (thumbs up/down) | Per-message feedback; store for learning and quality (no PII in feedback payload). |
| Pulse: citations + personalization | Citations on cards; personalize recommendations by store size/vertical; configurable frequency. |

**Features from plan:** 2.2, 2.3, 2.4, 2.5, 3.2, 3.4, 5.1, 5.5, 6.4, 9.1, 9.4, 8.1, 1.4, 7.2, 7.4, 7.5, 10.4–10.7.

**JTBD:** Category copy; email/campaign copy; CMS content; improve product images; get alt text; get campaign ideas; compare periods; understand the answer (rich); find my extensions; open an extension; understand automation.

**Definition of done for Phase 5:** All Tier 3 deliverables above are functional, secure, and documented for the release; DoD §14 satisfied.

---

### Phase 6 — Tier 4 (ecosystem + polish = complete version)

**Goal:** Scoped actions from extensions, extension Q&A, workflow approval flow, theme hints, localization, domain help, media from prompt, UI locale, mobile/accessibility, learning from feedback, optional glossary/goals.

**Deliverables**

| Deliverable | Description |
|-------------|-------------|
| Scoped actions (extension point) | Other modules register “Sidekick actions” (name, description, handler); Sidekick invokes only with approval; audit log. |
| Extension-specific Q&A | Per-extension knowledge (config paths, routes); “How does X work?” using that data; optional README ingestion. |
| Workflow: propose → approve → log | All state-changing actions (including from extensions) go through unified approval flow; reversible where possible. |
| Skill: Theme/layout hints | Read-only theme/layout awareness; suggest changes or snippets from NL. |
| Skill: Domain / URL | Explain base URL, domain, SSL from config; suggest only. |
| Skill: Media from prompt | Generate image from text via external API; add to Media Gallery with approval. |
| Localization (UI) | Admin UI in admin locale; dates/numbers formatted; optional LLM locale for generated content. |
| Writer localization | Generate/suggest content per store view/locale. |
| Mobile / accessibility | Responsive panel; keyboard navigation; ARIA; optional voice input. |
| Conversation & feedback learning | Use feedback and conversation history (anonymized/sanitized) to improve prompts or ranking; no PII to external services. |
| Optional: custom glossary + goals | Merchant-defined terms; optional KPI targets for Pulse and benchmarks. |

**Features from plan:** 9.2, 9.3, 8.2, 8.3, 3.1, 4.6, 3.3, 1.6, 2.7, 1.5, 10.8, 10.9, 10.10.

**JTBD:** Get design guidance; domain/URL; all remaining JTBD covered by prior phases; ecosystem (extensions) and polish complete.

**Definition of done for Phase 6:** Module meets full feature list (§1–§10) and DoD §14 for “complete” version; no known regressions; documentation and extension guide updated.

---

### Phase summary

| Phase | Name | Tier alignment | Main JTBD added | Ship outcome |
|-------|------|----------------|-----------------|--------------|
| 1 | Foundation | — | Get help (partial), Resume | Chat works; extensible; instance learning (modules + config schema). |
| 2 | MVP: Answer + guide | — | Understand setting, Learn procedure, Ask data (1) | First merchant value: Q&A + one report. |
| 3 | Tier 1 complete | Tier 1 | Product copy, Create promotion, Reports/export, Pulse, Config recommendations | Full Sidekick-style MVP. |
| 4 | Tier 2 | Tier 2 | Bulk copy, Fix error, Segments, Setup checklist, Context | Scale + support. |
| 5 | Tier 3 | Tier 3 | Category/email/CMS/marketing copy, Image/alt, Campaign ideas, Benchmarks, Extensions (discover/navigate), Describe automation, Feedback | Differentiation + scale. |
| 6 | Tier 4 (complete) | Tier 4 | Scoped actions, Extension Q&A, Workflow approval, Theme/domain/media, Localization, Mobile, Learning, Glossary/goals | Complete module. |

**Dependencies:** Each phase depends on the previous. Phase 1 is the only one with no in-plan dependency. Phases 2–6 assume the skill/action registry and instance learning from Phase 1 so new skills plug in without replacing core chat or routing logic.

---

## Next steps

1. **Architecture:** Define components (admin UI, chat API, skill registry, job queue for Pulse, knowledge indexer, action executors) and data model (conversations, messages, feedback, cards, instance knowledge).
2. **Implementation plan:** Per-phase breakdown (tickets, order of implementation, testing).
3. **Optional:** Create `docs/plans/YYYY-MM-DD-magento-sidekick-mvp-design.md` for Phase 1–2 technical design before coding.

This document is the single source of truth for scope, ranking, DoD, JTBD, and deliverable phases; the next artifact can be the detailed module architecture and Phase 1–2 implementation plan.
