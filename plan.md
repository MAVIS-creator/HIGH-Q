## Plan: Role-Based Tour + AI Assistant Service

Implement a role-aware onboarding tour and a separate AI assistant backend that is constrained by role permissions, confirmation gates, and audited actions. The AI provider configuration is sourced from existing .env keys only (no hardcoded secrets).

**Steps**
1. Phase 1: Contract and policy definition
2. Lock AI capability contract to your required scope: explain admin pages, explain system actions, answer settings/payments/users/courses/roles questions, summarize logs/alerts/system activity, draft admin/support responses, and prepare safe automation suggestions. *blocks all later phases*
3. Lock AI safety contract: no direct destructive changes, no RBAC bypass, no sensitive data exposure beyond role access, and mandatory confirmation for write actions. *depends on step 2*
4. Define provider-resolution order from .env (primary + fallback) using existing keys and normalize response schema in one adapter layer. *parallel with step 2*
5. Phase 2: Role-based tour architecture (not page-general)
6. Build a role-driven tour map derived from session role and allowed menu slugs from role_permissions; only generate steps for modules the current role can access. *depends on step 3*
7. Add onboarding state model for first-run completion and post-signup trigger with first-login fallback, including restart support for admins. *depends on step 6*
8. Design selector strategy per role capability groups (not fixed global page list), with fallback selectors and step skipping if permission or target is missing. *depends on step 6*
9. Phase 3: Backend implementation design
10. Add internal admin endpoints that call a separate AI backend service and pass only approved context for the signed-in role. *depends on steps 3 and 4*
11. Add server-side confirmation workflow endpoints for any write-intent suggestion so AI can propose, but never execute state-changing actions directly. *depends on step 10*
12. Add audit logging for all AI requests/responses/actions and all tour lifecycle events (shown, skipped, completed, restarted). *parallel with step 10*
13. Phase 4: Admin UI integration design
14. Add AI assistant menu entry with role permission slug and visibility via existing sidebar/permission model.
15. Add assistant UI behaviors: context explain, log summaries, draft generation, and safe automation proposal cards with explicit confirm action.
16. Add role-based tour bootstrap at onboarding entry and dynamically compute steps from current role permissions at runtime.
17. Phase 5: QA and documentation
18. Validate role matrix paths (admin/sub-admin/moderator/applicant or other active roles in DB) for both AI access and tour step generation.
19. Validate env-driven provider behavior with missing-key and failover scenarios.
20. Update README admin documentation with role-based tour logic and AI capability/safety statements exactly as specified.

**Relevant files**
- `README.md` — update admin docs for role-based onboarding and AI behavior
- `.env` — source of AI provider URLs, keys, model selections
- `admin/.env` — mirrored/override environment handling for admin runtime
- `admin/includes/auth.php` — role/session guard and permission checks
- `admin/includes/sidebar.php` — role-based visibility for assistant menu item
- `admin/includes/menu.php` — assistant menu slug definition
- `admin/includes/functions.php` — shared logging/helpers for AI and tour events
- `admin/login.php` — first-login fallback trigger
- `admin/signup.php` — post-signup onboarding trigger
- `admin/pages/dashboard.php` — onboarding bootstrap entry
- `admin/pages/users.php` — role-aware step anchor candidates
- `admin/pages/settings.php` — role-aware step anchor candidates
- `admin/pages/payments.php` — role-aware step anchor candidates
- `admin/pages/roles.php` — role-management context for assistant explanations
- `admin/pages/chat_view.php` — draft-response assistant hook candidate
- `admin/includes/header.php` — shared script/style bootstrap for assistant/tour
- `admin/includes/footer.php` — runtime initialization/cleanup hooks
- `admin/api/notifications.php` — secure endpoint pattern reference

**Verification**
1. AI capability verification: confirm the assistant can perform each required read/summarize/draft/suggest behavior.
2. AI safety verification: confirm write intents always require user confirmation and are never auto-executed.
3. RBAC verification: confirm assistant responses and tool access are bounded by current role_permissions.
4. Role-based tour verification: confirm steps are generated per role/menu access, and inaccessible modules produce no steps.
5. Onboarding verification: confirm trigger after signup with first-login fallback and one-time completion behavior.
6. Env verification: confirm provider adapter reads configured .env keys and handles missing/invalid keys gracefully.
7. Audit verification: confirm all AI and tour events are recorded with sufficient metadata.

**Decisions**
- Tour mapping is role-based, not a single global page flow.
- AI runs as a separate backend service called from internal admin APIs.
- AI capabilities and safety rules follow your exact specification.
- AI provider/API configuration is taken from existing .env keys.

**Further Considerations**
1. Secret hygiene: rotate any previously exposed API keys/tokens and move production secrets to secure secret storage.
2. Provider abstraction: keep one adapter contract for Groq/OpenRouter/Gemini selection to avoid lock-in.
3. Privacy controls: redact personal identifiers before external AI calls unless strictly needed and role-authorized.
