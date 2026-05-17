# Week 1 Backend Implementation Plan & Tasks

## Objective
Establish the project foundation for the Exam Portal backend based strictly on the roadmap and backend handoff. This includes finalizing the database schema, defining the API contract and route map, deciding on the authentication/session model, and creating a migration and seed approach.

---

## Task 1: Authentication Model Decision
**Requirement:** Decide auth model for exam portal.
**Details:**
- **Decision:** Implement a dedicated exam student auth flow for the MVP.
- **Table:** `exam_students` (stores student account credentials).
- **Security:** Use native PHP password hashing.
- **Session:** Use PHP session-based authentication.
- **Admin Auth:** Keep admin exam auth separate from the main site admin for now (`exam_admins`).
- **Rule:** Do NOT force reuse of current public registration users.

---

## Task 2: Finalize Database Schema & Migration Plan
**Requirement:** Finalize exam schema, create migration plan, and seed approach.
**Details:**
- Create SQL migration scripts using strictly `exam_` prefixed tables.
- Keep the exam portal schema artifact inside the `exam/` workspace so the whole workstream stays self-contained.
- Ensure no conflicts with existing `HIGH-Q` site tables.

**Schema Draft (Tables Needed):**
1. **Users & Profiles**
   - `exam_students` (id, email, password_hash, created_at, updated_at)
   - `exam_student_profiles` (student_id, full_name, phone, created_at, updated_at)
   - `exam_admins` (id, username, password_hash)
2. **Exams & Question Bank**
   - `exam_categories` (id, name, description)
   - `exam_subjects` (id, name, description)
   - `exam_definitions` (id, category_id, title, description, duration_minutes, rules)
   - `exam_definition_subjects` (exam_id, subject_id, question_count)
   - `exam_questions` (id, subject_id, question_text, explanation)
   - `exam_question_options` (id, question_id, option_text, is_correct)
3. **Attempt Engine**
   - `exam_attempts` (id, student_id, exam_id, status, started_at, completed_at)
   - `exam_attempt_subjects` (attempt_id, subject_id)
   - `exam_attempt_answers` (id, attempt_id, question_id, selected_option_id)
   - `exam_attempt_flags` (id, attempt_id, question_id)
   - `exam_sessions` (if external session handling is needed beyond default PHP sessions)
4. **Results & Analytics**
   - `exam_results` (id, attempt_id, total_score, status, percentile)
   - `exam_result_subject_breakdowns` (result_id, subject_id, score)
   - `exam_leaderboard_cache`
5. **Subscriptions & Payments**
   - `exam_subscription_plans` (id, name, price, benefits)
   - `exam_student_subscriptions` (id, student_id, plan_id, status, expires_at)
   - `exam_payments` (id, student_id, amount, status)
6. **System**
   - `exam_activity_logs`
   - `exam_settings`

---

## Task 3: API Contract & Route Map Definition
**Requirement:** Define API contract draft and route map.
**Details:**
- **Namespace:** `exam/api/`
- **Response Format:**
  ```json
  {
    "status": "ok", // or "error"
    "message": "Human-readable summary",
    "data": {}, // or null
    "errors": [] // or object mapping for validation errors
  }
  ```

**Route Map Draft:**
*Note: Week 1 focuses on drafting these contracts and establishing the file structure. Logic implementation starts Week 2.*

*   **Auth (`exam/api/auth/`)**
    *   `POST /register.php`
    *   `POST /login.php`
    *   `POST /logout.php`
    *   `GET /me.php`
*   **Exams (`exam/api/exams/`)**
    *   `GET /index.php`
    *   `GET /show.php?id=`
    *   `POST /start.php`
*   **Attempts (`exam/api/attempts/`)**
    *   `GET /questions.php?attempt_id=`
    *   `POST /save-answer.php`
    *   `POST /flag.php`
    *   `GET /status.php?attempt_id=`
    *   `POST /submit.php`
*   **Results (`exam/api/results/`)**
    *   `GET /show.php?attempt_id=`
    *   `GET /review.php?attempt_id=`
    *   `GET /history.php`
*   **Leaderboard (`exam/api/leaderboard/`)**
    *   `GET /index.php`
    *   `GET /my-rank.php`
*   **Subscriptions (`exam/api/subscriptions/`)**
    *   `GET /plans.php`
    *   `GET /current.php`
    *   `POST /subscribe.php`
    *   `POST /verify.php`
*   **Admin (`exam/api/admin/`)**
    *   `POST /login.php`
    *   `GET /dashboard.php`
    *   `GET /exams.php`
    *   `POST /exams-create.php`
    *   `POST /exams-update.php`
    *   `POST /questions-create.php`
    *   `POST /questions-update.php`
    *   `GET /students.php`
    *   `GET /results.php`
    *   `GET /settings.php`
    *   `POST /settings-update.php`

---

## Frontend Alignment (Context for Backend)
*What the frontend is doing in Week 1, ensuring no blockages:*
- Frontend is auditing Stitch screens, creating page inventories, and setting up UI tokens.
- Frontend is replacing static placeholders in `exam/` (naming plan only).
- **Backend responsibility to Frontend for Week 1:** Provide this solid API contract (routes and JSON structure) so Frontend knows the target URLs for their UI components.
- **Workspace rule:** New exam portal implementation should live inside `exam/` unless a file is explicitly acting as a bridge from the main site.

---

## Week 1 Deliverables Checklist
- [x] Documented Auth/Session Decision (Completed in Task 1).
- [x] Drafted MySQL Schema with `exam_` prefix (Completed in Task 2).
- [x] Documented API Contracts and Route Map (Completed in Task 3).
- [x] Created the initial SQL migration/seed script file: `exam/database/2026-05-12-exam-portal-schema.sql`
- [x] Scaffolded the `exam/api/` directory structure matching the Route Map.

## Week 1 Implementation Notes

Artifacts created in the repo:
- `exam/database/2026-05-12-exam-portal-schema.sql`
- `exam/api/_bootstrap.php`
- `exam/api/auth/`
- `exam/api/exams/`
- `exam/api/attempts/`
- `exam/api/results/`
- `exam/api/leaderboard/`
- `exam/api/subscriptions/`
- `exam/api/admin/`

Week 1 backend status is now ready to hand to frontend as a stable route contract baseline, with `exam/` as the canonical working area for this portal.
