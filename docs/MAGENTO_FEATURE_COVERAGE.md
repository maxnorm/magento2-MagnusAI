# Magento Feature Coverage: Tools for “Respond to Anything”

**Goal:** The agent must be able to **respond to and act on any merchant prompt** across the full Magento admin. This document maps every Magento feature area to the tools and actions required.

**Sources:** Magento 2 admin menu (Backend, Sales, Catalog, Customer, Marketing, Content, Reports, Stores, System), Adobe Commerce Page Builder, and standard modules (Newsletter, Review, UrlRewrite, Widget, etc.).

---

## 1. Magento Admin Feature Map (Full Menu → Route)

| Menu path | Admin route (action) | What merchant can do |
|-----------|------------------------|----------------------|
| **Dashboard** | adminhtml/dashboard | View dashboard |
| **Sales > Operations** | | |
| → Orders | sales/order | List, view, create, hold, cancel, invoice, ship, credit memo |
| → Invoices | sales/invoice | List, view, create invoices |
| → Shipments | sales/shipment | List, view, create shipments |
| → Credit Memos | sales/creditmemo | List, view, create refunds |
| → Transactions | sales/transactions | View payment transactions |
| **Catalog (Products)** | | |
| → Products | catalog/product | List, add, edit, delete, duplicate, import |
| → Categories | catalog/category | Tree, add, edit, delete |
| **Customers** | | |
| → All Customers | customer/index | List, add, edit, delete |
| → Now Online | customer/online | View customers currently online |
| → Customer Groups | customer/group | List, add, edit customer groups |
| **Marketing** | | |
| → Promotions > Catalog Price Rules | catalog_rule/promo_catalog | List, add, edit rules |
| → Promotions > Cart Price Rules | sales_rule/promo_quote | List, add, edit rules, coupons |
| → Communications > Email Templates | adminhtml/email_template | List, edit transactional email templates |
| → Communications > Newsletter Templates | newsletter/template | List, add, edit templates |
| → Communications > Newsletter Queue | newsletter/queue | View queue |
| → Communications > Newsletter Subscribers | newsletter/subscriber | List, export subscribers |
| → SEO & Search > URL Rewrites | adminhtml/url_rewrite/index | List, add, edit URL rewrites |
| → SEO & Search > Search Terms | search/term | List, edit search terms |
| → SEO & Search > Search Synonyms | search/synonyms | List, edit synonyms |
| → SEO & Search > Site Map | adminhtml/sitemap | List, add, edit sitemaps |
| → User Content > All Reviews | review/product | List, edit, approve reviews |
| → User Content > Pending Reviews | review/product/pending | Pending reviews |
| **Content > Elements** | | |
| → Pages | cms/page | List, add, edit CMS pages |
| → Blocks | cms/block | List, add, edit CMS blocks |
| → Widgets | adminhtml/widget_instance | List, add, edit widget instances |
| → Templates (Page Builder) | pagebuilder/template | Page Builder templates (if enabled) |
| **Content > Design** | | |
| → Configuration | theme/design_config | Design configuration (scope) |
| → Themes | adminhtml/system_design_theme | List, upload themes |
| → Schedule | adminhtml/system_design | Scheduled changes |
| **Reports** | | |
| → Marketing > Products in Cart | reports/report_shopcart/product | Report |
| → Marketing > Abandoned Carts | reports/report_shopcart/abandoned | Report |
| → Marketing > Search Terms | search/term/report | Report |
| → Marketing > Newsletter Problem Reports | newsletter/problem | Report |
| → Marketing > Reviews | reports/report_review/* | By customer, by product |
| → Sales > Orders, Tax, Invoiced, Shipping, Refunds, Coupons | reports/report_sales/* | Sales reports |
| → Customers > Order Total, Order Count, New | reports/report_customer/* | Customer reports |
| → Products > Views, Bestsellers, Low Stock, Ordered | reports/report_product/* | Product reports |
| → Statistics > Refresh | reports/report_statistics | Refresh stats |
| **Stores > Settings** | | |
| → All Stores | adminhtml/system_store | Websites, stores, store views |
| → Configuration | adminhtml/system_config | System configuration (all sections) |
| → Terms and Conditions | (checkout agreements) | Terms and conditions |
| → Order Status | sales/order_status | Custom order statuses |
| → Taxes | tax/* | Tax rules, rates, zones |
| → Currency | adminhtml/system_currency* | Currency rates, symbols |
| → Attributes > Product | catalog/product_attribute | Product attributes |
| → Attributes > Attribute Set | catalog/product_set | Attribute sets |
| → Attributes > Rating | review/rating | Review ratings |
| → Other Settings > Customer Groups | customer/group | (same as under Customers) |
| **Stores > Inventory** (MSI) | | |
| → Sources | inventory/source | Manage sources |
| → Stocks | inventory/stock | Manage stock assignments |
| **System** | | |
| → Data Transfer > Import | adminhtml/import | Import entities |
| → Data Transfer > Export | adminhtml/export | Export entities |
| → Data Transfer > Import History | adminhtml/history | Import history |
| → Integrations | (integrations) | OAuth integrations |
| → Tools > Cache Management | adminhtml/cache | Flush cache |
| → Tools > Backups | (backup) | Backup |
| → Tools > Index Management | adminhtml/index | Reindex |
| → Permissions > All Users | adminhtml/user | Admin users |
| → Permissions > User Roles | adminhtml/user_role | Roles and permissions |
| → Permissions > Locked Users | adminhtml/locks | Locked users |
| → Other > Notifications | (notifications) | In-app notifications |
| → Other > Custom Variables | (variables) | Custom variables |
| → Other > Encryption Key | (crypt key) | Encryption key |

---

## 2. Coverage by Area: User Prompts → Tools Required

For each area we need: **(1) Read tools** (list, search, get one, count/report), **(2) Navigation** (open_admin_page), **(3) Write actions** (propose_action where applicable).

### 2.1 Sales (Orders, Invoices, Shipments, Credit Memos)

| User prompt example | Read tool / report | Navigate target | Write action |
|---------------------|--------------------|-----------------|--------------|
| “Show order 10001” | get_order_summary ✅ | order_view ✅ | — |
| “Last 10 orders” / “Pending orders” | list_orders / orders_list metric | order_list ✅ | — |
| “Orders by customer X” / “Orders last 7 days” | search_orders or list_orders filters | order_list ✅ | — |
| “Open invoices” / “Open shipments” | — | **invoice_list, shipment_list, creditmemo_list** (need) | — |
| “Invoice order 10001” / “Ship order 10001” | — | order_view ✅ | order_action (invoice/ship/cancel) (need, optional) |
| “Revenue last month” / “Refunds” | run_report (revenue_aov_orders) ✅ | — | — |

### 2.2 Catalog (Products, Categories)

| User prompt example | Read tool / report | Navigate target | Write action |
|---------------------|--------------------|-----------------|--------------|
| “Find product X” / “Search SKU” | search_products ✅ | product_edit ✅ | — |
| “How many products?” | run_report product_count ✅ | product_list ✅ | — |
| “Show me products” / “First 20 products” | list_products (need) | product_list ✅ | — |
| “Products in category X” | products_in_category (need) | category_edit ✅ | — |
| “List categories” / “Category tree” | list_categories (need) | category_list ✅ | — |
| “Add product” / “Create product” | how_do_i ✅ | product_list ✅ | product_create (need) |
| “Update product price” / “Disable product” | get_product / search_products ✅ | product_edit ✅ | product_update, product_bulk_update (need) |
| “Improve product copy” / “Meta description for product X” | get_product_for_copy ✅ | product_edit ✅ | product_copy_apply ✅ |
| “Open product attributes” / “Attribute sets” | — | **product_attributes, attribute_sets** (need) | — |

### 2.3 Customers

| User prompt example | Read tool / report | Navigate target | Write action |
|---------------------|--------------------|-----------------|--------------|
| “Find customer John” / “Customer with email X” | search_customers (need) | customer_edit ✅ | — |
| “Customer details for ID 5” | get_customer_summary (need) | customer_edit ✅ | — |
| “How many customers?” | customer_count (need) or report | customer_list ✅ | — |
| “Open customer groups” | — | **customer_groups** (need) | — |
| “Now online” | — | **customer_online** (need) | — |

### 2.4 Marketing (Promotions, Communications, SEO, Reviews)

| User prompt example | Read tool / report | Navigate target | Write action |
|---------------------|--------------------|-----------------|--------------|
| “Create 10% off category X” | — | promo_catalog ✅ | catalog_price_rule_create ✅ |
| “Create free shipping over $50” / “Create coupon” | — | promo_cart ✅ | cart_price_rule_create (need) |
| “List active promotions” | list_promo_rules (need) | promo_catalog, promo_cart ✅ | — |
| “Email templates” / “Newsletter templates” | list_email_templates, list_newsletter_templates (need) | **email_templates, newsletter_templates, newsletter_queue, newsletter_subscribers** (need) | — |
| “URL rewrites” / “Search terms” / “Synonyms” / “Sitemap” | list_url_rewrites, list_search_terms (need) | **url_rewrites, search_terms, search_synonyms, sitemap** (need) | — |
| “Pending reviews” / “All reviews” | list_reviews (need) | **reviews_all, reviews_pending** (need) | review_approve (need, optional) |

### 2.5 Content (CMS Pages, Blocks, Widgets, Page Builder)

| User prompt example | Read tool / report | Navigate target | Write action |
|---------------------|--------------------|-----------------|--------------|
| “List CMS pages” / “Find page by title” | list_cms_pages (need) | cms_pages ✅ | — |
| “List CMS blocks” | list_cms_blocks (need) | cms_blocks ✅ | — |
| “Edit homepage content” / “Suggest copy for block X” | get_cms_content (need) | cms_page_edit, cms_block_edit ✅ | cms_content_apply (need) |
| “Widgets” / “Page Builder templates” | list_widgets (need) | **widgets, pagebuilder_templates** (need) | — |

### 2.6 Design (Configuration, Themes)

| User prompt example | Read tool / report | Navigate target | Write action |
|---------------------|--------------------|-----------------|--------------|
| “Design configuration” / “Themes” | — | **design_config, themes, design_schedule** (need) | — |

### 2.7 Reports

| User prompt example | Read tool / report | Navigate target | Write action |
|---------------------|--------------------|-----------------|--------------|
| “Top products by revenue” | run_report top_products_revenue ✅ | — | — |
| “Revenue and AOV” | run_report revenue_aov_orders ✅ | — | — |
| “Why conversions down?” | period_over_period (need) | — | — |
| “Products missing images” / “Low stock” / “Slow-moving” | run_report ✅ | — | — |
| “Last 10 orders” | orders_list (need) | order_list ✅ | — |
| “What should I do today?” | daily_digest (need) | — | — |
| “Open Reports > Sales” / “Bestsellers report” | — | **reports_sales, reports_products, reports_customers, reports_marketing** (need) | — |

### 2.8 Stores (Configuration, Attributes, Tax, Currency, Terms, Order Status)

| User prompt example | Read tool / report | Navigate target | Write action |
|---------------------|--------------------|-----------------|--------------|
| “What is config X?” / “Where is payment config?” | get_config_value, search_config_paths, explain_config ✅ | config ✅ | config_update ✅ |
| “Shipping config” / “Payment config summary” | config_audit (need) | config ✅ | — |
| “All stores” / “Websites” | store_scope (in prompt) ✅ | **system_store** (need) | — |
| “Tax rules” / “Currency rates” / “Order status” | — | **tax_rules, currency, order_status, terms** (need) | — |
| “Product attributes” / “Attribute sets” | list_attributes (need) | **product_attributes, attribute_sets** (need) | — |

### 2.9 Inventory (MSI: Sources, Stocks)

| User prompt example | Read tool / report | Navigate target | Write action |
|---------------------|--------------------|-----------------|--------------|
| “Low stock” | run_report low_stock ✅ | — | — |
| “Stock for SKU X” | get_stock (need) | product_edit ✅ | inventory_update (need) |
| “Sources” / “Stocks” (MSI) | — | **inventory_sources, inventory_stocks** (need) | — |

### 2.10 System (Import/Export, Cache, Index, Permissions)

| User prompt example | Read tool / report | Navigate target | Write action |
|---------------------|--------------------|-----------------|--------------|
| “How do I import products?” | how_do_i (expand: import) (need) | **import, export, import_history** (need) | — |
| “Flush cache” / “Reindex” | — | **cache, index** (need) | cache_flush, index_reindex (need, optional) |
| “Admin users” / “Roles” | list_admin_users (need) | **admin_users, user_roles, locked_users** (need) | — |
| “What modules are installed?” | list_modules ✅ | — | — |

---

## 3. Complete Tool List (Everything We Need)

### 3.1 Read & navigate tools (by domain)

| Domain | Tool name | Purpose | Status |
|--------|-----------|---------|--------|
| **Catalog** | search_products | Find by name/SKU | Have |
| | list_products | List first N (optional query) | Need |
| | get_product_for_copy | One product copy fields | Have |
| | product_count (report) | Total products | Have |
| | products_in_category | Products in category | Need |
| | list_categories | Category tree/list | Need |
| | get_category | One category | Need |
| **Orders** | get_order_summary | One order | Have |
| | list_orders / orders_list | Last N, filter by status/days | Need |
| | search_orders | By email, date, status | Need |
| **Customers** | search_customers | By name/email | Need |
| | get_customer_summary | One customer | Need |
| | customer_count | Total customers (optional) | Need |
| **Config** | search_config_paths | Find paths by keywords | Have |
| | get_config_value | Value for path | Have |
| | explain_config | Explain + value + link | Have |
| | config_audit | Shipping/payment summary | Need |
| **Marketing** | list_promo_rules | Catalog/cart rules | Need |
| | list_email_templates | Email templates | Need |
| | list_newsletter_* | Templates, queue, subscribers | Need |
| | list_url_rewrites | URL rewrites | Need |
| | list_search_terms | Search terms | Need |
| | list_reviews | All/pending reviews | Need |
| **Content** | list_cms_pages | CMS pages | Need |
| | list_cms_blocks | CMS blocks | Need |
| | get_cms_content | Page/block content | Need |
| | list_widgets | Widget instances | Need |
| **Reports** | run_report | All current metrics | Have |
| | orders_list metric | List orders | Need |
| | period_over_period | Compare periods | Need |
| | daily_digest | What to do today | Need |
| | category_performance | By category | Need |
| **Inventory** | low_stock (report) | Low stock | Have |
| | get_stock | Stock for SKU(s) | Need |
| **Procedures** | how_do_i | Step-by-step (expand list) | Have / Need expand |
| | list_admin_areas | Menu items | Have |
| **System** | list_modules | Enabled modules | Have |
| | list_admin_users | Admin users (optional) | Need |
| **Navigation** | open_admin_page | Deep link (see §4) | Have + extend |

### 3.2 Write actions (propose_action)

| Action | Purpose | Status |
|--------|---------|--------|
| config_update | Set config path | Have |
| catalog_price_rule_create | Catalog rule | Have |
| cart_price_rule_create | Cart rule + coupon | Need |
| product_copy_apply | Product copy | Have |
| product_create | Create product | Need |
| product_update | Update product | Need |
| product_bulk_update | Bulk status/visibility | Need |
| cms_content_apply | CMS page/block content | Need |
| inventory_update | Stock qty | Need |
| order_action (invoice/ship/cancel) | Order actions | Need (optional) |
| review_approve | Approve review | Need (optional) |
| cache_flush / index_reindex | System (optional) | Need (optional) |

### 3.3 open_admin_page targets to add

Today we have: product_list, product_edit, order_list, order_view, category_list, category_edit, customer_list, customer_edit, config, cms_pages, cms_page_edit, cms_blocks, cms_block_edit, promo_catalog, promo_cart.

**Add these targets** (and corresponding AdminUrl methods + routes):

| Target | Route / URL | Label |
|--------|-------------|--------|
| invoice_list | sales/invoice | Invoices |
| shipment_list | sales/shipment | Shipments |
| creditmemo_list | sales/creditmemo | Credit Memos |
| customer_online | customer/online | Now Online |
| customer_groups | customer/group | Customer Groups |
| email_templates | adminhtml/email_template | Email Templates |
| newsletter_templates | newsletter/template | Newsletter Templates |
| newsletter_queue | newsletter/queue | Newsletter Queue |
| newsletter_subscribers | newsletter/subscriber | Newsletter Subscribers |
| url_rewrites | adminhtml/url_rewrite/index | URL Rewrites |
| search_terms | search/term | Search Terms |
| search_synonyms | search/synonyms | Search Synonyms |
| sitemap | adminhtml/sitemap | Site Map |
| reviews_all | review/product/index | All Reviews |
| reviews_pending | review/product/pending | Pending Reviews |
| widgets | adminhtml/widget_instance | Widgets |
| pagebuilder_templates | pagebuilder/template | Page Builder Templates |
| design_config | theme/design_config | Design Configuration |
| themes | adminhtml/system_design_theme | Themes |
| system_store | adminhtml/system_store | All Stores |
| product_attributes | catalog/product_attribute | Product Attributes |
| attribute_sets | catalog/product_set | Attribute Sets |
| tax_rules | tax/rule | Tax Rules |
| order_status | sales/order_status | Order Status |
| inventory_sources | inventory/source/index | Inventory Sources |
| inventory_stocks | inventory/stock/index | Inventory Stocks |
| import | adminhtml/import | Import |
| export | adminhtml/export | Export |
| cache | adminhtml/cache | Cache Management |
| index | adminhtml/index | Index Management |
| admin_users | adminhtml/user | All Users |
| user_roles | adminhtml/user_role | User Roles |

(Reports are many separate routes; we can add report_sales, report_products, report_customers, report_marketing as generic “Reports > X” links, or rely on list_admin_areas + dynamic route from discovery.)

---

## 4. How Do I — Procedure expansion

Current procedures: add product, create category, configure shipping, configure payment, set up tax, manage orders, manage customers, create discount, create coupon.

**Add procedures for:** import products, export products, manage inventory (sources/stocks), refund order / credit memo, manage reviews (approve/reject), create CMS page, create CMS block, create widget, manage URL rewrites, flush cache, reindex, manage admin users/roles.

**Fallback:** When task not in list, return: “I don’t have step-by-step instructions for that. Here are admin areas that might help: [list_admin_areas]” and/or “You can open [relevant open_admin_page targets].”

---

## 5. Priority for “Respond to Anything”

1. **Navigation first:** Extend open_admin_page + AdminUrl so the agent can open **every** admin screen in the map above. Merchant says “open X” → we have a target for it.
2. **Read where they ask “list” / “find” / “how many”:** list_orders, search_customers, list_categories, list_products (or optional query), list_cms_pages, list_cms_blocks, list_reviews, list_promo_rules, config_audit, then remaining list_* as needed.
3. **Reports:** orders_list, period_over_period, daily_digest; then category_performance, abandoned_carts, etc.
4. **Writes:** cart_price_rule_create, product_bulk_update, product_create/update, cms_content_apply, inventory_update; order_action and review_approve optional.
5. **Procedures:** Expand how_do_i and add fallback so any “how do I” gets a useful response (steps or “open these areas”).

---

## 6. Document references

- [COMPLETE_TOOL_LIST.md](COMPLETE_TOOL_LIST.md) — Single checklist of every tool/action with status.
- [TOOL_AUDIT.md](TOOL_AUDIT.md) — Why the agent feels constrained; quick wins.
- [ROADMAP.md](ROADMAP.md) — Phases and implementation order.

When adding a tool or action, update COMPLETE_TOOL_LIST and this doc so coverage stays accurate.
