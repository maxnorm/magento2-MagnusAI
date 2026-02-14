# Complete Tool List: Making the Magnus Agent Powerful and Useful for Magento Merchants

**Purpose:** Single reference of every tool and write action needed so the agent can **respond to anything** a merchant might ask or do in the Magento admin.  
**Audience:** Product, engineering, and roadmap planning.  
**Date:** 2026-02-13.

**Related:** [MAGENTO_FEATURE_COVERAGE.md](MAGENTO_FEATURE_COVERAGE.md) maps every Magento admin area to user prompts and required tools.

---

## How to Read This Document

- **Status:** **Have** = implemented today; **Need** = not yet (gap).
- **Type:** **Read** = read-only or navigation; **Write** = state-changing (always via propose_action → approval).
- Domains follow Magento admin: Catalog, Sales, Customers, Marketing, Content, Reports, Config/Stores, Inventory, System, Procedures.

---

## 1. Catalog & Products

| # | Tool / action | Purpose | Key parameters | Type | Status |
|---|----------------|--------|----------------|------|--------|
| 1.1 | **search_products** | Find products by name or SKU; return id, name, SKU, link | query, limit (default 10, max 50) | Read | **Have** |
| 1.2 | **list_products** (or extend search_products) | List first N products when user says “show me products” / no query | limit, optional page; optional filters (status, visibility) | Read | **Need** — today search_products requires non-empty query |
| 1.3 | **get_product** / **get_product_for_copy** | Get one product’s attributes (for copy: name, descriptions, meta) | product_id | Read | **Have** (get_product_for_copy) |
| 1.4 | **product_count** (run_report metric) | Total number of products in the store | (via report_spec) | Read | **Have** |
| 1.5 | **products_in_category** | List products in a category (id or path) with optional count | category_id or path, limit | Read | **Need** |
| 1.6 | **list_categories** / **category_tree** | List category names and IDs (optional product count per category) | optional parent_id, depth, include_product_count | Read | **Need** |
| 1.7 | **get_category** | Get one category’s name, path, product count | category_id | Read | **Need** (optional; can be derived from list) |
| 1.8 | **product_copy_apply** (action) | Apply suggested name, short/long description, meta to a product | product_id, name, short_description, description, meta_title, meta_description | Write | **Have** |
| 1.9 | **product_create** (action) | Create a simple product (minimal attributes) | name, sku, price, status, visibility, optional category | Write | **Need** |
| 1.10 | **product_update** (action) | Update product attributes (price, status, visibility, stock) | product_id, attributes to update | Write | **Need** |
| 1.11 | **product_bulk_update** (action) | Bulk update status/visibility for a set of product IDs or a category | product_ids or category_id, status and/or visibility | Write | **Need** |
| 1.12 | **open_admin_page** (product targets) | Deep link to product list, product edit, category list, category edit | target, id | Read | **Have** |

---

## 2. Orders & Sales

| # | Tool / action | Purpose | Key parameters | Type | Status |
|---|----------------|--------|----------------|------|--------|
| 2.1 | **get_order_summary** | Get one order: status, total, customer, date | order_id (increment_id or entity_id) | Read | **Have** |
| 2.2 | **list_orders** | List last N orders with status, total, date, link | limit, optional status, optional days | Read | **Need** — “last 10 orders,” “pending orders” |
| 2.3 | **search_orders** | Find orders by customer email, status, date range | email, status, from_date, to_date, limit | Read | **Need** (optional; list_orders + filter may suffice) |
| 2.4 | **orders_summary** (report metric) | Count and/or totals for orders in date range (already partly in revenue_aov_orders) | days, optional store_id | Read | **Have** (via revenue_aov_orders) |
| 2.5 | **open_admin_page** (order targets) | Deep link to order list, order view | target, order_id | Read | **Have** |
| 2.6 | **open_admin_page** (Sales sub) | Invoices, Shipments, Credit Memos lists | target: invoice_list, shipment_list, creditmemo_list | Read | **Need** |
| 2.7 | **order_action** (action, optional) | Invoice, ship, or cancel an order (high risk; optional) | order_id, action: invoice | ship | cancel | Write | **Need** (optional; high effort and risk) |

---

## 3. Customers

| # | Tool / action | Purpose | Key parameters | Type | Status |
|---|----------------|--------|----------------|------|--------|
| 3.1 | **search_customers** | Find customers by name or email; return id, name, email | query (name/email), limit | Read | **Need** |
| 3.2 | **get_customer_summary** | Get one customer: name, email, created_at, order count (optional) | customer_id | Read | **Need** |
| 3.3 | **list_customers** | List recent or first N customers (optional) | limit, optional sort | Read | **Need** (lower priority than search) |
| 3.4 | **open_admin_page** (customer targets) | Deep link to customer list, customer edit | target, customer_id | Read | **Have** |
| 3.5 | **open_admin_page** (customer sub) | Now Online, Customer Groups | target: customer_online, customer_groups | Read | **Need** |
| 3.6 | **customer_segments** (read, optional) | List or describe customer segments (if module present) | — | Read | **Need** (optional) |

---

## 4. Analytics & Reports (run_report + metrics)

| # | Tool / metric | Purpose | Key parameters | Type | Status |
|---|----------------|--------|----------------|------|--------|
| 4.1 | **run_report** | Execute a report by metric | report_spec: metric, dimensions?, filters (days, store_id, limit) | Read | **Have** |
| 4.2 | **top_products_revenue** | Top N products by revenue in last N days | days, limit | Read | **Have** |
| 4.3 | **revenue_aov_orders** | Revenue, order count, AOV for date range | days, optional store_id | Read | **Have** |
| 4.4 | **product_count** | Total products in store | — | Read | **Have** |
| 4.5 | **missing_images** | Products with no image | limit | Read | **Have** |
| 4.6 | **low_stock** | Products below stock threshold | limit | Read | **Have** |
| 4.7 | **slow_moving** | Products with no sales in N days | days, limit | Read | **Have** |
| 4.8 | **orders_list** (new metric) | List of last N orders (increment_id, status, total, date) | days or limit, optional status | Read | **Need** |
| 4.9 | **period_over_period** | Compare two periods (e.g. last 7 vs previous 7): revenue, orders, AOV | days, compare_previous | Read | **Need** |
| 4.10 | **daily_digest** / **what_to_do_today** | Composite: low stock count, recent orders summary, active promos, etc. | — | Read | **Need** |
| 4.11 | **category_performance** (optional) | Revenue or units by category for a period | days, limit | Read | **Need** (optional) |

---

## 5. Configuration

| # | Tool / action | Purpose | Key parameters | Type | Status |
|---|----------------|--------|----------------|------|--------|
| 5.1 | **search_config_paths** | Find config paths by keywords | keywords | Read | **Have** |
| 5.2 | **get_config_value** | Get current value for a config path | path | Read | **Have** |
| 5.3 | **explain_config** | What a setting does + current value + link | config_path (or keywords) | Read | **Have** |
| 5.4 | **config_audit** (tool) | Summarize shipping and payment config (carriers, methods enabled) | section (e.g. shipping, payment) | Read | **Need** |
| 5.5 | **config_update** (action) | Set one config path to a value (scope) | path, value, scope | Write | **Have** |
| 5.6 | **open_admin_page** (config target) | Deep link to config section | target=config, config_path | Read | **Have** |

---

## 6. Marketing & Promotions

| # | Tool / action | Purpose | Key parameters | Type | Status |
|---|----------------|--------|----------------|------|--------|
| 6.1 | **list_promo_rules** (optional) | List active catalog/cart rules (name, dates, discount) | type: catalog | cart, limit | Read | **Need** (optional) |
| 6.2 | **catalog_price_rule_create** (action) | Create catalog price rule | name, discount, from_date, to_date, optional category | Write | **Have** |
| 6.3 | **cart_price_rule_create** (action) | Create cart price rule and optional coupon | name, conditions (e.g. cart total), discount, dates, coupon_code (optional) | Write | **Need** |
| 6.4 | **open_admin_page** (promo targets) | Deep link to Catalog Price Rules, Cart Price Rules | target: promo_catalog, promo_cart | Read | **Have** |
| 6.5 | **list_email_templates** | List email templates (transactional) | limit | Read | **Need** |
| 6.6 | **list_newsletter_templates** / **list_newsletter_subscribers** | Newsletter templates, queue, subscribers | limit | Read | **Need** |
| 6.7 | **list_url_rewrites** / **list_search_terms** / **list_search_synonyms** | SEO: URL rewrites, search terms, synonyms | limit, optional filter | Read | **Need** |
| 6.8 | **list_reviews** | All or pending product reviews | status: all | pending, limit | Read | **Need** |
| 6.9 | **open_admin_page** (marketing sub) | Email templates, Newsletter, URL Rewrites, Search Terms, Synonyms, Sitemap, Reviews | target: email_templates, newsletter_*, url_rewrites, search_terms, search_synonyms, sitemap, reviews_all, reviews_pending | Read | **Need** |
| 6.10 | **review_approve** (action, optional) | Approve or reject a review | review_id, action | Write | **Need** (optional) |

---

## 7. Inventory

| # | Tool / action | Purpose | Key parameters | Type | Status |
|---|----------------|--------|----------------|------|--------|
| 7.1 | **low_stock** (report metric) | Products below threshold | limit | Read | **Have** |
| 7.2 | **get_stock** (optional) | Stock level for one or more SKUs | sku or product_ids | Read | **Need** (optional) |
| 7.3 | **inventory_update** (action, optional) | Update stock for SKU(s) | sku(s), qty, optional source (MSI) | Write | **Need** (optional) |

---

## 8. Content (CMS)

| # | Tool / action | Purpose | Key parameters | Type | Status |
|---|----------------|--------|----------------|------|--------|
| 8.1 | **list_cms_pages** (optional) | List CMS pages (id, title, identifier) | limit | Read | **Need** (optional) |
| 8.2 | **list_cms_blocks** (optional) | List CMS blocks | limit | Read | **Need** (optional) |
| 8.3 | **get_cms_content** (optional) | Get page or block content for copy suggestions | page_id or block_id | Read | **Need** (optional) |
| 8.4 | **cms_content_apply** (action, optional) | Apply suggested content to page/block | page_id or block_id, content fields | Write | **Need** (optional) |
| 8.5 | **open_admin_page** (CMS targets) | Deep link to CMS pages, CMS blocks, edit | target, page_id / block_id | Read | **Have** |
| 8.6 | **list_widgets** | List widget instances | limit | Read | **Need** |
| 8.7 | **open_admin_page** (content sub) | Widgets, Page Builder templates, Design config, Themes | target: widgets, pagebuilder_templates, design_config, themes | Read | **Need** |

---

## 9. Procedures & Navigation

| # | Tool / action | Purpose | Key parameters | Type | Status |
|---|----------------|--------|----------------|------|--------|
| 9.1 | **how_do_i** | Step-by-step procedures for admin tasks | task (from fixed list) | Read | **Have** — 9 tasks only |
| 9.2 | **expand how_do_i** | Add more procedures (import products, manage inventory, refund, etc.) | — | Read | **Need** — expand PROCEDURES map |
| 9.3 | **how_do_i fallback** | When task not in list: suggest list_admin_areas or “I don’t have steps; you can open…” | — | Read | **Need** — better UX than hard “I don’t have that” |
| 9.4 | **open_admin_page** | Deep link to any supported admin area | target, id, config_path | Read | **Have** |
| 9.5 | **list_admin_areas** | List admin menu items / areas the agent can open | optional filter | Read | **Have** |
| 9.6 | **plan_or_checklist** (optional) | Return a checklist for compound intent (e.g. “Black Friday”) with status | intent (e.g. black_friday) | Read | **Need** (Phase 5) |
| 9.7 | **how_do_i** (expand) | Add: import products, export, refund, manage reviews, CMS page/block, widget, URL rewrites, flush cache, reindex, admin users | task | Read | **Need** |
| 9.8 | **how_do_i fallback** | When task not in list: suggest list_admin_areas + “open these areas” | — | Read | **Need** |

---

## 10. System & Instance

| # | Tool / action | Purpose | Key parameters | Type | Status |
|---|----------------|--------|----------------|------|--------|
| 10.1 | **list_modules** | List enabled modules/extensions | — | Read | **Have** |
| 10.2 | **store_scope** (in prompt / discovery) | Website count, store view count, default currency, locale | — | Read | **Have** (in prompt via DiscoveryProvider) |
| 10.3 | **greeting** | Greetings and empty message; what Magnus can do | — | Read | **Have** |
| 10.4 | **ask_clarification** (optional) | Structured “I need X” (e.g. date range, store view) so UI can prompt | need: date_range | store_id | … | Read | **Need** (optional) |
| 10.5 | **open_admin_page** (Stores) | All Stores, Configuration, Tax, Currency, Order Status, Product Attributes, Attribute Sets | target: system_store, config, tax_rules, order_status, product_attributes, attribute_sets | Read | **Have** (config) / **Need** (rest) |
| 10.6 | **open_admin_page** (Inventory MSI) | Sources, Stocks | target: inventory_sources, inventory_stocks | Read | **Need** |
| 10.7 | **open_admin_page** (System) | Import, Export, Import History, Cache, Index, All Users, User Roles, Locked Users | target: import, export, cache, index, admin_users, user_roles | Read | **Need** |
| 10.8 | **list_admin_users** (optional) | List admin users (read-only) | limit | Read | **Need** (optional) |
| 10.9 | **cache_flush** / **index_reindex** (action, optional) | Flush cache or reindex (high risk; optional) | — | Write | **Need** (optional) |

---

## 11. Write Actions (propose_action) — Summary

All writes go through **propose_action** → user approval → execution.

| Action type | Purpose | Status |
|-------------|---------|--------|
| **config_update** | Set config path value (scope) | **Have** |
| **catalog_price_rule_create** | Create catalog price rule | **Have** |
| **product_copy_apply** | Apply name, descriptions, meta to product | **Have** |
| **cart_price_rule_create** | Create cart rule + optional coupon | **Need** |
| **product_bulk_update** | Bulk status/visibility by IDs or category | **Need** |
| **product_create** | Create simple product | **Need** (optional) |
| **product_update** | Update product attributes | **Need** (optional) |
| **inventory_update** | Update stock for SKU(s) | **Need** (optional) |
| **cms_content_apply** | Apply content to CMS page/block | **Need** (optional) |
| **order_action** (invoice/ship/cancel) | Order state change | **Need** (optional; high risk) |
| **review_approve** | Approve or reject review | **Need** (optional) |
| **cache_flush** / **index_reindex** | System tools | **Need** (optional) |

---

## 12. Full open_admin_page Targets (Summary)

So the agent can **open any admin screen** the merchant names:

| Status | Targets |
|--------|---------|
| **Have** | product_list, product_edit, order_list, order_view, category_list, category_edit, customer_list, customer_edit, config, cms_pages, cms_page_edit, cms_blocks, cms_block_edit, promo_catalog, promo_cart |
| **Need** | invoice_list, shipment_list, creditmemo_list, customer_online, customer_groups, email_templates, newsletter_templates, newsletter_queue, newsletter_subscribers, url_rewrites, search_terms, search_synonyms, sitemap, reviews_all, reviews_pending, widgets, pagebuilder_templates, design_config, themes, system_store, product_attributes, attribute_sets, tax_rules, order_status, inventory_sources, inventory_stocks, import, export, cache, index, admin_users, user_roles |

See [MAGENTO_FEATURE_COVERAGE.md](MAGENTO_FEATURE_COVERAGE.md) §3.3 for exact routes and labels.

---

## 13. Priority Overview

### Tier 1 — High impact, high demand (do first)

| Item | Notes |
|------|--------|
| **Extend open_admin_page** | Add all admin targets (invoices, shipments, newsletter, SEO, reviews, widgets, design, stores, system) so "open X" works for any admin area |
| list_orders / orders_list metric | “Last 10 orders,” “recent orders” |
| search_customers | “Find customer John,” “customer with email X” |
| list_categories / category_tree | “What categories exist?” “Open category X” |
| list_products or optional query in search_products | “Show me products” without a search term |
| cart_price_rule_create (action) | “Create 10% off cart” / “free shipping over $50” |
| period_over_period (report) | “Why are conversions down?” |
| Output verification hints | Empty results say “No data” so agent responds correctly |

### Tier 2 — Strong value, medium effort

| Item | Notes |
|------|--------|
| products_in_category | “Products in category X” |
| get_customer_summary | One customer detail + optional order count |
| config_audit (shipping/payment) | “What’s my shipping setup?” |
| daily_digest / what_to_do_today | “What should I do today?” with real data |
| product_bulk_update (action) | Bulk enable/disable by category or IDs |
| Expand how_do_i + fallback | More procedures; graceful “I don’t have that” |

### Tier 3 — Nice to have

| Item | Notes |
|------|--------|
| product_create / product_update (action) | Create or update product with approval |
| inventory_update (action) | Update stock (MSI or legacy) |
| list_promo_rules, category_performance | More read visibility |
| list_cms_* / get_cms_content / cms_content_apply | CMS copy and listing |
| ask_clarification, plan_or_checklist | Clarification and planning |
| order_action (invoice/ship) | High risk; optional |

---

## 14. Count Summary

| Category | Have (read) | Need (read) | Have (write) | Need (write) |
|----------|-------------|-------------|--------------|--------------|
| Catalog & products | 4 | 4 | 1 | 3 |
| Orders | 1 | 2 | 0 | 0–1 |
| Customers | 0 | 2–3 | 0 | 0 |
| Reports | 6 metrics | 3–4 | — | — |
| Config | 3 | 1 | 1 | 0 |
| Marketing | 0 | 0–1 | 1 | 1 |
| Inventory | 1 | 0–1 | 0 | 0–1 |
| CMS | 0 | 0–3 | 0 | 0–1 |
| Procedures / nav | 3 | 2–3 | — | — |
| System | 3 | 0–1 | — | — |
| **Totals** | **~22** | **~18–22** | **3** | **~5–8** |

*Exact counts depend on what is implemented as separate tools vs. report metrics vs. optional features.*

---

## 15. Document Maintenance

- When a tool or action is implemented, change its **Status** from **Need** to **Have** and add a short note if the name or params differ.
- New ideas (e.g. from merchant feedback) can be added in the right domain with Status **Need** and a priority note in §12.
- Keep [TOOL_AUDIT.md](TOOL_AUDIT.md), [ROADMAP.md](ROADMAP.md), and [MAGENTO_FEATURE_COVERAGE.md](MAGENTO_FEATURE_COVERAGE.md) aligned with this list for gap and phase planning.
