# Magento Sidekick Clone - Product Engineering Plan

**Date:** February 13, 2026  
**Product:** Magnus AI Assistant for Magento  
**Goal:** Build a Shopify Sidekick-equivalent AI assistant for Magento merchants  
**Status:** Phase 1 Foundation ~85% complete

---

## Executive Summary

**Vision:** Enable Magento merchants to manage their stores through natural language conversation, reducing admin complexity and accelerating common tasks.

**Current State:** Phase 1 foundation is ~85% complete. Core chat infrastructure works, but critical action execution framework is missing.

**Strategic Approach:** Build foundational capabilities first (action framework + Phase 2 Q&A), then layer on high-value task execution features (Phase 3 Tier 1).

**Timeline Estimate:** 
- **MVP (Phase 1 + 2):** 4-6 weeks
- **Tier 1 Complete (Phase 3):** +6-8 weeks
- **Full Feature Set (Phase 4-6):** +12-16 weeks

---

## 1. Product Vision & Success Metrics

### 1.1 Vision Statement

> Magnus enables Magento merchants to manage their stores through natural language conversation. Merchants can ask questions, get instant reports, generate content, create promotions, and optimize their store—all without navigating complex admin menus or writing code.

### 1.2 Success Metrics

**Adoption Metrics:**
- **Daily Active Users (DAU):** % of admin users who use Magnus daily
- **Messages per Session:** Average conversation length
- **Feature Usage:** % of users who use each skill type (reports, copy, discounts, etc.)

**Value Metrics:**
- **Time Saved:** Average time reduction for common tasks (e.g., creating discount: 5 min → 30 sec)
- **Task Completion Rate:** % of merchant requests successfully completed
- **Error Reduction:** Fewer mistakes in config changes, promotions, etc.

**Quality Metrics:**
- **Response Accuracy:** % of answers rated helpful (thumbs up)
- **Action Success Rate:** % of executed actions that complete without errors
- **Merchant Satisfaction:** NPS or satisfaction score

**Technical Metrics:**
- **Response Time:** P95 latency for chat responses
- **LLM Cost:** Cost per conversation/message
- **Uptime:** 99.9% availability target

### 1.3 Success Criteria by Phase

**Phase 1 + 2 (MVP):**
- ✅ Merchant can ask questions and get instance-aware answers
- ✅ Merchant can understand config settings
- ✅ Merchant can get basic reports ("top 10 products")
- ✅ 80%+ of common questions answered correctly

**Phase 3 (Tier 1):**
- ✅ Merchant can generate product copy with approval
- ✅ Merchant can create discounts via natural language
- ✅ Merchant can run multiple report types
- ✅ Proactive recommendations shown daily
- ✅ 70%+ task completion rate for supported actions

**Phase 4-6 (Complete):**
- ✅ All Tier 1-4 features implemented
- ✅ Extensible platform for third-party skills
- ✅ 90%+ merchant satisfaction score

---

## 2. Strategic Roadmap

### 2.1 Phase Prioritization Strategy

**Foundation First (Critical Path):**
1. **Complete Phase 1:** Action execution framework (blocks all task execution)
2. **Build Phase 2:** Q&A skills (immediate merchant value, validates architecture)
3. **Phase 3 Tier 1:** High-value task execution (product copy, discounts, reports, Pulse)

**Then Scale:**
4. **Phase 4:** Bulk operations, troubleshooting, segments
5. **Phase 5:** Differentiation features (image editing, campaigns, extensions)
6. **Phase 6:** Ecosystem and polish

### 2.2 Recommended Execution Order

**Sprint 1-2: Complete Foundation (Phase 1)**
- Build action execution framework
- Implement rate limiting enforcement
- Add tests for core chat flow

**Sprint 3-4: MVP Q&A (Phase 2)**
- Extend instance learning (config values)
- Build "How do I?" skill
- Build config explanation skill
- Build one NL report skill
- Add suggested questions UI

**Sprint 5-8: Tier 1 Task Execution (Phase 3)**
- Build product copy skill (with action framework)
- Build discount creation skill
- Build full NL reports skill
- Build Pulse (proactive cards)
- Add export functionality

**Sprint 9+: Scale & Differentiate (Phase 4-6)**
- Bulk operations
- Troubleshooting
- Advanced features
- Ecosystem extensions

---

## 3. Technical Architecture Decisions

### 3.1 Action Execution Framework (Critical)

**Decision:** Build action framework before Phase 3 features.

**Architecture:**
```
ActionInterface
├── execute() - Perform the action
├── preview() - Show what will happen
├── validate() - Check if action is valid
└── getDescription() - Human-readable description

ActionProposal
├── action - ActionInterface instance
├── description - Text shown to merchant
├── preview - Preview/diff data
└── conversationId - Link to conversation

ActionRegistry
├── storePendingAction() - Save pending action
├── getPendingAction() - Retrieve by conversation
└── clearPendingAction() - Remove after execution

ApprovalDetector
├── detectApproval() - Parse "yes", "approve", etc.
└── linkToAction() - Match approval to pending action

ActionExecutor
├── execute() - Run approved action
├── handleErrors() - Error handling
└── auditLog() - Log to audit_log table
```

**Implementation Priority:** **HIGHEST** - Blocks all Phase 3+ features

### 3.2 Skill System Enhancement

**Current:** Basic skill routing (keyword-based intent)

**Enhancement Needed:**
- Support action proposals in skills
- Better intent detection (LLM-based classification)
- Skill priority/ordering
- Skill metadata (description, examples)

**Decision:** Enhance skill system to support actions, but keep keyword-based routing for Phase 2 (can upgrade to LLM-based later).

### 3.3 Instance Learning Expansion

**Current:** Modules list + config schema

**Phase 2 Needs:**
- Config values (non-secret)
- Store structure (websites, store views)
- Catalog shape (attribute sets, product types)

**Phase 3+ Needs:**
- Admin routes + ACL
- Order/catalog data summaries
- Performance metrics

**Decision:** Expand incrementally per phase needs.

### 3.4 LLM Strategy

**Current:** OpenAI GPT-4o-mini

**Considerations:**
- Cost optimization (use cheaper models for simple tasks)
- Response time (faster models for real-time chat)
- Quality (better models for complex tasks)

**Decision:** 
- **Phase 1-2:** Single provider (OpenAI) - keep simple
- **Phase 3+:** Consider multi-model strategy (cheap for routing, expensive for generation)

### 3.5 Data Storage Strategy

**Current:** 
- Conversations/Messages: MySQL
- Instance Knowledge: Cache (24h TTL)

**Phase 3+ Needs:**
- Pulse cards: MySQL table
- Action proposals: Session or MySQL
- Feedback: MySQL table

**Decision:** Use MySQL for persistent data, cache for computed knowledge.

---

## 4. Feature Prioritization Matrix

### 4.1 Value vs. Effort Analysis

**High Value, Low Effort (Quick Wins):**
- Suggested questions UI (Phase 2)
- Config explanation skill (Phase 2)
- Export functionality (Phase 3)

**High Value, High Effort (Strategic):**
- Action execution framework (Phase 1) - **CRITICAL**
- Product copy generation (Phase 3)
- Pulse/proactive cards (Phase 3)

**Low Value, Low Effort (Nice to Have):**
- Feedback UI (thumbs up/down)
- Mobile responsiveness improvements

**Low Value, High Effort (Defer):**
- Voice input (Phase 6)
- Workflow automation (Phase 6)
- Extension ecosystem (Phase 6)

### 4.2 Dependency Graph

```
Action Framework (Phase 1)
    ↓
Product Copy Skill (Phase 3)
    ↓
Discount Creation Skill (Phase 3)
    ↓
Config Recommendations (Phase 3)

Instance Learning (Phase 1)
    ↓
Config Values (Phase 2)
    ↓
Config Explanation (Phase 2)
    ↓
"How do I?" Skill (Phase 2)

Reports Skill (Phase 2)
    ↓
Full Reports (Phase 3)
    ↓
Export (Phase 3)
```

**Critical Path:** Action Framework → All Phase 3+ task execution

---

## 5. Risk Assessment & Mitigation

### 5.1 Technical Risks

**Risk 1: LLM Cost Escalation**
- **Impact:** High - Could make product uneconomical
- **Probability:** Medium
- **Mitigation:** 
  - Rate limiting per user
  - Cache common queries
  - Use cheaper models for routing
  - Monitor costs per conversation

**Risk 2: Action Execution Errors**
- **Impact:** High - Could corrupt store data
- **Probability:** Low (with proper validation)
- **Mitigation:**
  - Always require approval
  - Preview before execution
  - Validate all inputs
  - Comprehensive error handling
  - Audit logging

**Risk 3: Performance Issues**
- **Impact:** Medium - Poor UX
- **Probability:** Medium
- **Mitigation:**
  - Cache instance knowledge
  - Optimize database queries
  - Async processing for long tasks
  - Response time monitoring

### 5.2 Product Risks

**Risk 4: Low Adoption**
- **Impact:** High - Product fails
- **Probability:** Medium
- **Mitigation:**
  - Make it discoverable (header trigger)
  - Suggested questions to lower friction
  - Proactive cards (Pulse) to drive engagement
  - Clear value proposition

**Risk 5: Wrong Feature Prioritization**
- **Impact:** Medium - Wasted effort
- **Probability:** Low (we have plan)
- **Mitigation:**
  - Follow Tier 1-4 prioritization
  - Validate with merchants early
  - Iterate based on usage data

### 5.3 Business Risks

**Risk 6: LLM Provider Dependency**
- **Impact:** Medium - Single point of failure
- **Probability:** Low
- **Mitigation:**
  - Abstract LLM provider interface
  - Support multiple providers
  - Self-hosted option (future)

---

## 6. Implementation Plan

### 6.1 Sprint 1-2: Complete Phase 1 Foundation

**Goal:** Build action execution framework + complete Phase 1

**Tasks:**

1. **Action Execution Framework**
   - [ ] Create `ActionInterface` + base implementations
   - [ ] Create `ActionProposal` value object
   - [ ] Create `ActionRegistry` (store pending actions)
   - [ ] Create `ApprovalDetector` (parse approval intent)
   - [ ] Create `ActionExecutor` (execute approved actions)
   - [ ] Extend `ReplyResultInterface` to support action proposals
   - [ ] Update `ChatService` to handle action proposals
   - [ ] Update UI to show action proposals and handle approval

2. **Rate Limiting**
   - [ ] Implement rate limiter per admin user
   - [ ] Use Magento rate limiter or custom implementation
   - [ ] Add config for rate limit threshold

3. **Testing**
   - [ ] Unit tests for action framework
   - [ ] Integration tests for chat flow with actions
   - [ ] Test approval detection

**Definition of Done:**
- ✅ Skills can propose actions
- ✅ Actions stored as pending
- ✅ Approval detected in follow-up messages
- ✅ Actions execute on approval
- ✅ Audit log entries created
- ✅ Rate limiting enforced

**Estimated Effort:** 2-3 weeks

---

### 6.2 Sprint 3-4: Phase 2 MVP Q&A

**Goal:** Build Q&A skills that provide immediate merchant value

**Tasks:**

1. **Instance Learning Enhancement**
   - [ ] Extend `ConfigSchemaCollector` to include config values (non-secret)
   - [ ] Add store structure collector (websites, store views)
   - [ ] Update knowledge index structure
   - [ ] Add cache invalidation for config value changes

2. **"How do I?" Skill**
   - [ ] Create `HowDoISkill` implementing `SkillInterface`
   - [ ] Use instance learning for context-aware steps
   - [ ] Generate deep links to admin pages
   - [ ] Register skill in DI

3. **Config Explanation Skill**
   - [ ] Create `ConfigExplanationSkill`
   - [ ] Parse config path from user message
   - [ ] Retrieve config value from instance learning
   - [ ] Generate explanation with current value
   - [ ] Register skill in DI

4. **NL Report Skill (One Template)**
   - [ ] Create `ReportSkill` with "top N products" template
   - [ ] Query sales/catalog data (read-only)
   - [ ] Format results as table
   - [ ] Return in chat reply
   - [ ] Register skill in DI

5. **Suggested Questions UI**
   - [ ] Create suggested questions component
   - [ ] Add to chat panel UI
   - [ ] 3-5 predefined prompts
   - [ ] Click to send question

**Definition of Done:**
- ✅ Merchant can ask "how do I add a product?" and get instance-aware steps
- ✅ Merchant can ask "what does catalog/search do?" and get explanation + value
- ✅ Merchant can ask "top 10 products last month" and get results
- ✅ Suggested questions visible in UI
- ✅ All answers are instance-aware (not generic)

**Estimated Effort:** 2-3 weeks

---

### 6.3 Sprint 5-8: Phase 3 Tier 1 Task Execution

**Goal:** High-value task execution features

**Tasks:**

1. **Product Copy Skill**
   - [ ] Create `ProductCopySkill`
   - [ ] Parse product ID from context or message
   - [ ] Generate copy using LLM (name, description, meta)
   - [ ] Propose action with preview
   - [ ] Execute action on approval (save to product)
   - [ ] Handle errors gracefully

2. **Discount Creation Skill**
   - [ ] Create `DiscountCreationSkill`
   - [ ] Parse NL ("20% off category X")
   - [ ] Build cart rule or catalog rule structure
   - [ ] Propose action with summary
   - [ ] Execute on approval (create rule)
   - [ ] Audit log entry

3. **Full NL Reports**
   - [ ] Extend `ReportSkill` with multiple templates
   - [ ] Add date range parsing
   - [ ] Add category/attribute filtering
   - [ ] Format as tables
   - [ ] Add query explanation

4. **Pulse (Proactive Cards)**
   - [ ] Create `magnus_assistant_pulse_card` table
   - [ ] Create `PulseAnalyzer` cron job
   - [ ] Analyze orders/catalog/config
   - [ ] Generate recommendation cards
   - [ ] Store in DB per store/view
   - [ ] Display in admin dashboard or chat
   - [ ] "Act on this" opens chat with context

5. **Export Functionality**
   - [ ] Add export button to report replies
   - [ ] Generate CSV from report data
   - [ ] Download via browser

6. **Config Recommendations**
   - [ ] Create `ConfigRecommendationSkill`
   - [ ] Analyze config and suggest improvements
   - [ ] Propose config changes
   - [ ] Execute on approval

**Definition of Done:**
- ✅ Merchant can generate product copy with approval
- ✅ Merchant can create discounts via NL
- ✅ Merchant can run multiple report types
- ✅ Pulse cards shown daily with recommendations
- ✅ Export works for reports
- ✅ Config recommendations work

**Estimated Effort:** 6-8 weeks

---

### 6.4 Sprint 9+: Phase 4-6 Scale & Differentiate

**Goal:** Complete feature set and ecosystem

**Tasks:** (See plan document for full list)

- Bulk operations
- Troubleshooting
- Segments
- Image editing
- Campaign ideas
- Extension discovery
- Localization
- Mobile/accessibility
- And more...

**Estimated Effort:** 12-16 weeks

---

## 7. Technical Specifications

### 7.1 Action Execution Framework Design

**ActionInterface:**
```php
interface ActionInterface {
    public function execute(array $params): ActionResult;
    public function preview(array $params): ActionPreview;
    public function validate(array $params): ValidationResult;
    public function getDescription(): string;
    public function requiresApproval(): bool;
}
```

**ActionProposal:**
```php
class ActionProposal {
    private ActionInterface $action;
    private string $description;
    private ActionPreview $preview;
    private int $conversationId;
    private array $params;
}
```

**Approval Detection:**
- Keywords: "yes", "approve", "confirm", "go ahead", "do it", "execute"
- LLM-based classification (optional enhancement)
- Context-aware (only approve if pending action exists)

**Action Execution Flow:**
1. Skill proposes action → `ActionProposal` created
2. Stored in `ActionRegistry` (linked to conversation)
3. Reply shown to merchant with preview
4. Merchant types approval → `ApprovalDetector` matches
5. `ActionExecutor` retrieves action, validates, executes
6. Result shown to merchant
7. Audit log entry created

### 7.2 Database Schema Additions

**Pending Actions Table:**
```sql
CREATE TABLE magnus_assistant_pending_action (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT UNSIGNED NOT NULL,
    action_type VARCHAR(255) NOT NULL,
    action_params TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES magnus_assistant_conversation(id) ON DELETE CASCADE
);
```

**Pulse Cards Table:**
```sql
CREATE TABLE magnus_assistant_pulse_card (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id INT UNSIGNED,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    action_type VARCHAR(255),
    action_params TEXT,
    citations TEXT,
    priority INT DEFAULT 0,
    shown_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_store_shown (store_id, shown_at)
);
```

**Feedback Table:**
```sql
CREATE TABLE magnus_assistant_feedback (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id INT UNSIGNED NOT NULL,
    admin_user_id INT UNSIGNED NOT NULL,
    rating TINYINT NOT NULL, -- 1 = thumbs up, -1 = thumbs down
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES magnus_assistant_message(id) ON DELETE CASCADE
);
```

### 7.3 API Enhancements

**Enhanced Send Endpoint:**
```json
// Request (unchanged)
{
    "message": "Create 20% discount for category X",
    "conversation_id": 123,
    "context": {}
}

// Response (enhanced)
{
    "reply": "I'll create a 20% discount for category X. Preview: ...",
    "conversation_id": 123,
    "message_id": 456,
    "action_proposal": {  // NEW
        "action_id": "discount_create",
        "description": "Create 20% discount for category X",
        "preview": {...},
        "requires_approval": true
    }
}
```

**Approval Endpoint (NEW):**
```json
// POST /admin/magnus/action/approve
{
    "conversation_id": 123,
    "action_id": "pending_action_123"
}

// Response
{
    "success": true,
    "result": "Discount created successfully",
    "action_id": "pending_action_123"
}
```

---

## 8. Success Criteria & Validation

### 8.1 Phase 1 + 2 (MVP) Success Criteria

**Functional:**
- ✅ Merchant can ask questions and get answers
- ✅ Merchant can understand config settings
- ✅ Merchant can get basic reports
- ✅ 80%+ of common questions answered correctly

**Technical:**
- ✅ Response time < 3 seconds (P95)
- ✅ Action framework works end-to-end
- ✅ No data corruption from actions
- ✅ Rate limiting enforced

**User Experience:**
- ✅ Chat UI is discoverable and usable
- ✅ Suggested questions lower friction
- ✅ Error messages are clear

### 8.2 Phase 3 (Tier 1) Success Criteria

**Functional:**
- ✅ Merchant can generate product copy with approval
- ✅ Merchant can create discounts via NL
- ✅ Merchant can run multiple report types
- ✅ Pulse cards shown daily
- ✅ 70%+ task completion rate

**Technical:**
- ✅ All actions require approval
- ✅ All actions logged to audit log
- ✅ Export works for reports
- ✅ Pulse cron runs daily

**User Experience:**
- ✅ Action previews are clear
- ✅ Approval flow is intuitive
- ✅ Pulse cards are actionable

---

## 9. Go-to-Market Considerations

### 9.1 Launch Strategy

**MVP Launch (Phase 1 + 2):**
- Internal testing with select merchants
- Gather feedback on Q&A quality
- Iterate on suggested questions
- Document common use cases

**Tier 1 Launch (Phase 3):**
- Public beta with full feature set
- Marketing: "AI assistant for Magento"
- Case studies: time saved, tasks completed
- Documentation and tutorials

### 9.2 Documentation Needs

**User Documentation:**
- Getting started guide
- Common use cases
- FAQ
- Video tutorials

**Developer Documentation:**
- How to create custom skills
- Action framework guide
- Extension points
- API reference

---

## 10. Next Steps & Immediate Actions

### 10.1 This Week

1. **Review & Approve Plan**
   - Stakeholder review
   - Technical review
   - Adjust timeline if needed

2. **Set Up Development Environment**
   - Ensure Phase 1 code is stable
   - Set up testing framework
   - Create feature branches

### 10.2 Next 2 Weeks

1. **Start Sprint 1: Action Framework**
   - Design detailed architecture
   - Create interfaces
   - Implement core components
   - Write tests

2. **Parallel: Start Phase 2 Planning**
   - Design skill interfaces
   - Plan instance learning expansion
   - Create UI mockups for suggested questions

### 10.3 Next Month

1. **Complete Action Framework**
2. **Build Phase 2 Skills**
3. **Test MVP with merchants**
4. **Plan Phase 3**

---

## 11. Conclusion

**Strategic Focus:** Build foundational action framework first, then layer on high-value Q&A and task execution features.

**Critical Path:** Action Framework → Phase 2 Q&A → Phase 3 Task Execution

**Success Depends On:**
- ✅ Solid action execution framework
- ✅ High-quality instance learning
- ✅ Merchant value from day one (Phase 2)
- ✅ Iterative improvement based on usage

**Timeline:** MVP in 4-6 weeks, Tier 1 complete in 10-14 weeks, full feature set in 6+ months.

---

**End of Product Engineering Plan**
