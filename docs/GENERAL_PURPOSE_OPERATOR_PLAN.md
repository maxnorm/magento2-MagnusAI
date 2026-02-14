# Magnus: Path to a General-Purpose Magento AI Operator

**Goal:** Magnus should be able to help with **every aspect** of managing a Magento store—merchandising, SEO, orders, marketing, config, inventory, and operations—as a single, trustworthy AI operator.

**Sources:** Operator audit, [ROADMAP.md](ROADMAP.md), [2026-02-11 Sidekick features & learning](../../../../docs/plans/2026-02-11-magento-sidekick-features-and-learning.md), [2026-02-13 product engineering plan](../../../../docs/plans/2026-02-13-magento-sidekick-product-engineering-plan.md), [Shopify Engineering: production-ready agentic systems](https://shopify.engineering/building-production-ready-agentic-systems).

---

## 1. “Everything Possible” — Capability Matrix

What “general purpose” means per store area, in **read** (answer + navigate) and **write** (propose → approve → execute).

| Store area | Read (Magnus can today) | Write (Magnus can today) | Target read | Target write |
|------------|-------------------------|---------------------------|-------------|--------------|
| **Merchandising** | search_products, top products report, open product/category | — | + missing images (done), low stock (done), slow-moving (done), category tree | catalog_price_rule (done), **product_copy_apply** (done), **product_bulk_update**, optional product_create/update |
| **SEO** | — | — | products/categories with missing meta, alt text status | **Product copy** (suggest → approve → apply meta, descriptions) (done); category copy, alt later |
| **Orders** | get_order_summary, open order | — | order list (recent, by status, date range), revenue/AOV (done) | optional: hold, cancel, ship (later) |
| **Marketing / promos** | open promo pages | config_update, catalog_price_rule_create | list active rules, campaign ideas (suggestions) | **cart_price_rule_create**, segments (suggest → approve) |
| **Config & setup** | explain_config, get_config_value, search_config_paths, open config | config_update | config audit (shipping/payment summary) | config recommendations (suggest → approve) |
| **Inventory** | — | — | low stock (done), out-of-stock, slow-moving (done) | optional: inventory_update |
| **Analytics & insights** | top products, revenue/AOV/orders (done) | — | period-over-period, “what to do today” digest, export | — |
| **Content (CMS)** | open CMS pages/blocks | — | list pages/blocks | optional: suggest CMS copy → approve |
| **Operations** | how_do_i, list admin areas, list modules | — | troubleshooting (errors/logs), setup checklist | — |

**Summary:** To cover “everything possible,” Magnus needs (1) **broader read** in every area (reports, lists, audits), (2) **Writer track** (product/category copy + SEO meta + alt), (3) **more writes** (cart rule, product bulk, optional inventory/product), (4) **reliability** (clarification, verification, approval by ID), and (5) **proactive** (Pulse/digest).

---

## 2. The One Recommended Next Step

**Next step: Add the “data everywhere” foundation and one cross-cutting write.**

Concretely, do **both** in parallel (or in quick sequence):

1. **Pluggable report registry + 3 catalog/operations reads**  
   - Implement a **report-type registry** in ReportTool (or equivalent) so new report types can be added without editing core.  
   - Add **three read capabilities** that span merchandising and operations:  
     - **Products missing images** (tool or report type)  
     - **Low stock / out-of-stock** (tool)  
     - **Slow-moving** (report type: no sales in N days)  
   - Outcome: the agent can answer “which products have no image?”, “show low stock”, “what’s slow-moving?” with real data and optional admin links. This touches **merchandising** and **inventory** and sets the pattern for all future report types (period-over-period, digest, etc.) without more core churn.

2. **Product copy (Writer) — suggest → approve → apply**  
   - One **action** (or skill with action): given a product ID (from context or conversation), call the LLM to generate **name, short description, long description, meta title, meta description** from product attributes; return **suggested text**; on approval, **write** to the product via Catalog API.  
   - Outcome: Magnus helps with **merchandising** and **SEO** in one go (descriptions + meta). This is the #2 feature in the Sidekick ranking and the highest-impact single addition for “everything possible” in catalog and SEO.

**Why this order**

- **Data first:** Without “missing images,” “low stock,” “slow-moving,” the agent cannot reliably advise on merchandising or operations. The pluggable registry keeps the codebase maintainable as we add more reports (Shopify lesson: avoid “death by a thousand instructions”; keep tool/report boundaries clear).  
- **Product copy next:** It’s the top unmet Tier 1 item (reports and discounts are partially done). It spans merchandising and SEO and is the natural “first Writer” capability.  
- Together, these two steps make Magnus useful across **merchandising, SEO, and operations** in one move and establish **extensible reports** and **content generation with approval**.

---

## 2a. Report registry vs. “query the database from the user prompt”

**Idea:** Instead of a report registry (many predefined report types), let the system interpret the user’s prompt and run a database query (e.g. text-to-SQL) to produce any report.

**Verdict:** Letting the LLM generate **arbitrary SQL** and run it is **not wise** for production. A **structured semantic layer** (LLM picks metric + dimensions + filters; we run only safe queries) is a good middle ground.

| Approach | Pros | Cons |
|----------|------|------|
| **Prompt → raw SQL** | One “tool” can answer many ad-hoc questions; no new code per report. | **Security:** SQL injection, PII exposure, destructive or heavy queries. **Correctness:** Magento schema is complex (EAV, flat tables, index tables, multi-db); wrong JOINs/status filters → wrong revenue/orders. **Performance:** Unbounded queries, table locks. **Operational:** Hard to audit, rate-limit, or explain. |
| **Report registry** (current plan) | Safe, predictable, uses Magento APIs/collections; each report type is testable and auditable. | New question patterns require adding a new report type (or extending params). |
| **Structured semantic layer** (recommended middle path) | One “report” tool, but the LLM outputs a **structured spec** (e.g. `metric`, `dimensions`, `filters`, `date_range`), and backend **only** runs allowed combinations against Magento’s data layer (repositories, collections, or a read-only query template). No free-form SQL. | Requires defining the schema of allowed metrics/dimensions/filters and mapping them to safe queries; some ad-hoc questions may still need new report types. |

**Recommendation**

- **Do not** expose a single “run any SQL from prompt” capability in production.
- **Do** one of:
  - **Option A (simplest):** Keep a **report registry**. Add report types as needed (missing images, low stock, slow-moving, period-over-period). The LLM already “chooses” report type + params from the prompt; that’s the right abstraction.
  - **Option B (more flexible):** Add a **semantic report tool** where the LLM returns a structured object, e.g. `{ "metric": "revenue"|"order_count"|"aov"|"top_products", "dimensions": ["product"|"date"|"store"], "filters": { "days": 30, "store_id": null }, "limit": 10 }`. The backend has a **single executor** that maps each allowed (metric, dimensions) to one safe implementation (existing ReportTool logic or collections). New report types become “new allowed metric/dimension combinations” plus one handler each—no raw SQL, no prompt in the query path.

So: **yes** to “one mechanism that can serve many report-like questions from the user prompt,” but **no** to “the LLM writes the database query.” Use either a **registry of report types** (LLM picks type + params) or a **structured semantic layer** (LLM picks metric/dimensions/filters; we run only safe code paths). Both avoid arbitrary DB access and keep Magento semantics correct.

---

## 3. Sequence to General-Purpose Operator

After the step above, this sequence gets Magnus to “everything possible” in a logical order.

| Step | What | Why |
|------|------|-----|
| **1 (next)** | Pluggable reports + (missing images, low stock, slow-moving) + **Product copy** (suggest → approve → apply) | Data everywhere in catalog/operations; first Writer capability for merchandising + SEO. |
| **2** | **Cart price rule** action + **product_bulk_update** (status/visibility) + **output verification** (empty/failure hints) + **clarification** (prompt protocol: ask for date/store when ambiguous) | Completes promotions; merchandising writes; agent stops “making up” empty results and asks when needed. |
| **3** | **Period-over-period** report + **“What to do today”** digest (or first Pulse-style cards) + **approval by action ID** | “Why conversions down?” and “what should I do today?” with data; better UX when multiple proposals. |
| **4** | **Category copy** (suggest → approve) + **Export** (CSV for reports) + **Config audit** tool (shipping/payment summary) | SEO/category; analysts; config visibility. |
| **5** | **Explicit planning/checklist** for compound tasks + **Pulse** (cron, cards, “Act on this”) + optional **product_create/update**, **inventory_update** | “Prepare for Black Friday” style tasks; proactive value; full catalog/inventory writes if in scope. |

Evaluation (from [Shopify’s post](https://shopify.engineering/building-production-ready-agentic-systems)): in parallel with steps 1–2, add a **small Ground Truth Set** (10–20 real or realistic conversations) and 2–3 evaluation criteria (e.g. task completion, safety, clarity). Use human labels first, then align an LLM judge with human agreement so we can measure regressions as we add features.

---

## 4. What “General Purpose” Implies by Area

- **Merchandising:** Read: search, top products, missing images, low stock, slow-moving, category tree. Write: catalog rule (done), product bulk, product/category copy (suggest → approve).  
- **SEO:** Read: products/categories with missing meta. Write: product/category meta and descriptions (product copy), alt text (later).  
- **Orders:** Read: order summary (done), order list, revenue/AOV (done), period-over-period. Write: optional hold/cancel/ship later.  
- **Marketing:** Read: list active rules, open promo pages. Write: catalog rule (done), cart rule (step 2).  
- **Config:** Read: explain, get, search, open (done), config audit. Write: config_update (done), config recommendations (suggest → approve).  
- **Inventory:** Read: low stock, slow-moving. Write: optional inventory_update (step 5).  
- **Analytics:** Read: top products, revenue/AOV/orders, period-over-period, digest. Write: export (CSV).  
- **Operations:** Read: how_do_i, troubleshooting, setup checklist. Write: none beyond config/recommendations.

---

## 5. Single-Sentence Summary

**Next step:** Implement **pluggable report types** plus **three catalog/operations reads** (missing images, low stock, slow-moving) and the **product copy** Writer flow (suggest name/description/meta → approve → apply), so Magnus can help with merchandising, SEO, and operations with real data and one high-impact content action; then follow the 5-step sequence above to reach a general-purpose Magento AI operator that can do everything possible on a Magento store within a safe, approval-gated design.
