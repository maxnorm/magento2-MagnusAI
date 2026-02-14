# Magnus Assistant — Tool Audit & Capability Review

**Purpose:** Audit every tool and capability exposed to the agent; identify why the agent feels constrained and recommend expansions.  
**Date:** 2026-02-13.

---

## 1. Executive Summary

The agent has **13 read/navigate tools** plus **propose_action** for writes. Constraints that make it feel limited:

| Constraint type | What’s happening |
|-----------------|-----------------|
| **Narrow procedures** | `how_do_i` supports only **9 fixed tasks**; anything else returns “I don’t have step-by-step instructions for that yet.” |
| **Search requires query** | `search_products` **rejects empty query** — no “list all” or “first 20 products”; product count is via `run_report` (product_count) only. |
| **Single-order lookup** | `get_order_summary` is **one order at a time** by ID/increment_id — no “last 10 orders” or “orders by status.” |
| **No customer search** | No tool to search customers by name/email or get customer summary; only open customer list/edit. |
| **No category/catalog listing** | No “list categories” or “products in category X”; only open_admin_page to category grid. |
| **Report metrics fixed** | `run_report` has 6 metrics; no custom or ad-hoc queries (e.g. “orders last 7 days” as list, period-over-period). |
| **Only 2 write actions** | propose_action: **config_update**, **catalog_price_rule_create**, **product_copy_apply** — no cart rule, product create, bulk update, inventory. |
| **Prompt mentions subset of tools** | System prompt names how_do_i, explain_config, search_config_paths, get_config_value, open_admin_page, run_report, list_modules, greeting — **does not mention** search_products, get_order_summary, get_product_for_copy, list_admin_areas, so the model may underuse them. |
| **Static tool set** | All tools sent every turn; no dynamic filtering — can add noise and confuse tool choice. |

**Verdict:** The agent is strong on **config**, **navigation**, and **a few report types**; it is constrained by **fixed procedures**, **single-entity reads** (one order, search products only with query), **no customer/category listing**, and **few write actions**.

---

## 2. Full Tool Inventory

### 2.1 Read & navigate tools

| Tool | Description (to LLM) | Parameters | Constraints / limits |
|------|----------------------|------------|----------------------|
| **how_do_i** | Step-by-step procedures for admin tasks | `task`: string | **Only 9 tasks:** add product, create category, configure shipping, configure payment, set up tax, manage orders, manage customers, create discount, create coupon. Any other task → “I don’t have step-by-step instructions for that yet.” |
| **explain_config** | What a config setting does + current value | `config_path`: string | Depends on instance knowledge (config schema + values). No config path → may suggest search_config_paths. |
| **search_config_paths** | Find config paths by keywords | `keywords`: string | Max **15 results**. Empty keywords → “Please provide keywords.” |
| **get_config_value** | Current value for a config path | `path`: string | Path must exist in config index; returns value + optional link to config page. |
| **open_admin_page** | Deep link to admin page | `target`, optional `id`, optional `config_path` | **15 targets only:** product_list, product_edit, order_list, order_view, category_list, category_edit, customer_list, customer_edit, config, cms_pages, cms_page_edit, cms_blocks, cms_block_edit, promo_catalog, promo_cart. No arbitrary routes. |
| **run_report** | Reports and data summaries | `report_spec`: { metric, dimensions?, filters?, limit? } | **6 metrics:** top_products_revenue, revenue_aov_orders, missing_images, low_stock, slow_moving, **product_count**. Enum-only; no free-form queries. |
| **search_products** | Search products by name or SKU | `query`: string, `limit`: int (default 10, max 50) | **Query required.** Empty query → “Please provide a search query.” Name OR SKU (tries name first, then SKU if empty). No filter by category, status, or attribute. |
| **get_order_summary** | Summary of one order | `order_id`: string (increment_id or entity_id) | **One order per call.** No “last N orders” or filter by status/date. Sales module required. |
| **get_product_for_copy** | Product attributes for copy (name, descriptions, meta) | `product_id`: int | Read-only; used before product_copy_apply. Catalog required. |
| **list_admin_areas** | Admin areas/menu items that can be opened | `filter`?: string | From DiscoveryProvider; max 50 items; optional keyword filter. |
| **list_modules** | Enabled modules/extensions | (none) | Up to 100 module names in reply; rest summarized. |
| **greeting** | Greetings / empty message | (none) | Only for hello/empty; returns short “what Magnus can do.” |

### 2.2 Write path: propose_action

| Action type | Purpose | Current status |
|-------------|---------|----------------|
| **config_update** | Set one config path to a value (scope) | Registered, executable |
| **catalog_price_rule_create** | Create catalog price rule (name, discount, dates, optional category) | Registered, executable |
| **product_copy_apply** | Apply suggested name, descriptions, meta to a product | Registered, executable |
| **cart_price_rule_create** | Create cart price rule (+ coupon) | **Not implemented** (on ROADMAP) |
| **product_bulk_update** | Bulk status/visibility by IDs or category | **Not implemented** |
| **product_create** / **product_update** | Create or update product | **Not implemented** |

Only the first three are available; the agent cannot execute cart rules, bulk product updates, or product create/update.

### 2.3 Report metrics (run_report)

| Metric | What it returns |
|--------|------------------|
| top_products_revenue | Top N products by revenue in last N days (default 30). |
| revenue_aov_orders | Revenue, order count, AOV for date range. |
| missing_images | Products with no image (up to limit). |
| low_stock | Low-stock products. |
| slow_moving | Products with no sales in N days. |
| product_count | Total product count in store. |

**Not available:** period-over-period, “orders last N days” as a list, custom date-range order list, category-level stats.

---

## 3. System Prompt vs. Tools

The system prompt explicitly names:

- how_do_i, explain_config, search_config_paths, get_config_value, open_admin_page, run_report, list_modules, greeting.

It **does not** name in the compact list:

- search_products  
- get_order_summary  
- get_product_for_copy  
- list_admin_areas  

The full tool schemas (name + description + parameters) are still sent to the LLM, so the model *can* use these tools, but the prompt does not encourage them. That can lead to underuse or wrong-tool choice (e.g. opening Products instead of running product_count or search_products).

**Recommendation:** Extend the system prompt to mention in one line: search_products (find products by name/SKU), get_order_summary (order by number), get_product_for_copy (before product copy action), list_admin_areas (what pages you can open).

---

## 4. Gap Analysis: What Merchants Might Ask vs. What the Agent Can Do

| User intent | Can do? | How | Gap / constraint |
|-------------|--------|-----|-------------------|
| How many products in the store? | Yes | run_report(product_count) | — |
| Top products by revenue? | Yes | run_report(top_products_revenue) | — |
| Revenue / AOV last 30 days? | Yes | run_report(revenue_aov_orders) | — |
| Products missing images? | Yes | run_report(missing_images) | — |
| Low stock? | Yes | run_report(low_stock) | — |
| Slow-moving products? | Yes | run_report(slow_moving) | — |
| Find product by name/SKU? | Yes | search_products then open_admin_page | Query required; no “all” or “first 20.” |
| Open product/order/category/config? | Yes | open_admin_page | — |
| Order status for #10001? | Yes | get_order_summary | One order only. |
| Last 10 orders? / Orders by status? | No | — | No list_orders or order search tool. |
| Find customer by name/email? | No | — | No search_customers or get_customer_summary. |
| List categories? / Products in category X? | No | — | Only open category grid; no data. |
| Why are conversions down? (compare periods) | Partial | revenue_aov_orders for one period | No period-over-period report. |
| Create cart rule / coupon? | No (execute) | how_do_i(create coupon) only | No cart_price_rule_create action. |
| Create product? | No (execute) | how_do_i(add product) only | No product_create action. |
| Bulk enable/disable products? | No | — | No product_bulk_update action. |
| How do I [task not in list]? | No | — | “I don’t have step-by-step instructions for that yet.” |

---

## 5. Why the Agent Feels Constrained — Summary

1. **Procedures are a fixed list** — Only 9 how_do_i tasks; everything else is “I don’t have that.”
2. **No list/search for orders or customers** — One order lookup only; no customer search.
3. **Product search is query-only** — No “list all” or “first N”; count is separate (run_report product_count).
4. **No category/catalog listing** — Can only open grids, not “list categories” or “products in category X.”
5. **Reports are fixed metrics** — No ad-hoc or period-over-period; “orders last 7 days” as list not covered.
6. **Only 3 write actions** — Config, catalog rule, product copy; no cart rule, product create, or bulk.
7. **Prompt under-advertises some tools** — search_products, get_order_summary, get_product_for_copy, list_admin_areas not mentioned in the short prompt list.
8. **All tools every turn** — No dynamic tool set; possible noise and wrong-tool selection.

---

## 6. Prioritized Recommendations

### Quick wins (low effort, high impact)

| # | Recommendation | Rationale |
|---|----------------|-----------|
| 1 | **Mention all tools in the system prompt** | Add one line listing search_products, get_order_summary, get_product_for_copy, list_admin_areas so the model uses them more consistently. |
| 2 | **Allow search_products with optional query** | When query is empty or “*”, return first N products (e.g. 20) or total count + first page so “show me products” works without run_report. |
| 3 | **Add “list_orders” or extend run_report** | e.g. metric `orders_list`: last N orders with status, total, date (and optional link). Covers “last 10 orders,” “recent orders.” |
| 4 | **Output verification hints** | When a tool returns empty (e.g. search 0 results, report 0 rows), append a single line in the tool result (“No products matched.” / “No orders in that period.”) so the agent can respond accurately. |

### Medium term (more capability)

| # | Recommendation | Rationale |
|---|----------------|-----------|
| 5 | **Add search_customers (or get_customer_summary)** | Search by name/email; return id, email, name; then open_admin_page(customer_edit, id). |
| 6 | **Add list_categories or category_tree** | Read-only list of category names/IDs (and optional product count) so the agent can answer “what categories exist?” and “open category X.” |
| 7 | **Expand how_do_i or add “generic” procedure** | Either add more PROCEDURES (e.g. “import products,” “manage inventory”) or a fallback: “I don’t have steps for that; here are admin areas that might help: …” + list_admin_areas. |
| 8 | **Report: period_over_period** | Compare two periods (e.g. last 7 vs previous 7) for revenue/orders/AOV so “why are conversions down?” can be answered with comparison. |
| 9 | **Register cart_price_rule_create** | Implement and register so “create a cart rule / coupon” can be proposed and executed after approval. |

### Longer term (roadmap alignment)

| # | Recommendation | Rationale |
|---|----------------|-----------|
| 10 | **Dynamic tool selection** | Filter tools by intent/embedding or keyword so only relevant tools are sent per turn; reduce noise and improve selection. |
| 11 | **product_bulk_update action** | Bulk status/visibility by IDs or category; approval required. |
| 12 | **Clarification protocol** | When date range / store / product set is missing, structured “I need X” (or ask_clarification tool) so the agent doesn’t guess. |
| 13 | **Approve by action ID** | Allow approving a specific pending action by ID, not only “latest.” |

---

## 7. Reference: Tool Count and Coverage

| Category | Count | Tools |
|----------|-------|--------|
| Procedures | 1 | how_do_i (9 tasks) |
| Config | 3 | explain_config, search_config_paths, get_config_value |
| Navigation | 2 | open_admin_page (15 targets), list_admin_areas |
| Products | 3 | search_products (query required), get_product_for_copy, run_report (incl. product_count, missing_images, low_stock, slow_moving, top_products_revenue) |
| Orders | 1 | get_order_summary (single order) |
| Reports | 1 | run_report (6 metrics) |
| Instance | 1 | list_modules |
| Fallback | 1 | greeting |
| **Total read/navigate** | **13** | |
| **Writes (propose_action)** | **3** | config_update, catalog_price_rule_create, product_copy_apply |

---

*This audit is based on static code review of all tools, ReportSpec, ActionTypeRegistry, PromptBuilder, and existing docs (SKILLS_AND_TOOLS_REVIEW.md, AGENTIC_MATURITY_AUDIT.md, ROADMAP.md).*
