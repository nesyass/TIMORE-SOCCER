# Timore Soccer School — Codex Project Rules

Read `docs/ADMIN_SPEC.md` before changing implementation. Focus on admin dashboard only; do not invent assessment criteria. UI must follow dark–gold design: gold #E9B33B, light gold #FFD86A, pitch #0B0F15, charcoal #111827, slate #273449, text #F4F5F7; headings Oswald, body Plus Jakarta Sans.

Rules:
- Maintain Laravel backend and server-rendered Blade; do not introduce a JS SPA without authorization.
- No payment gateway; verify payments manually with an auditable, idempotent workflow.
- TSS creates tournaments, external schools/teams register. Never model tournament participants as individual TSS students.
- Training schedule is fixed weekly; changes are individual exceptions.
- Student assessment and progress criteria are pending partner discussion. Do not implement imaginary scoring.
- Personal data of minors must be private by default.
- Preserve accessibility, server-side validation, CSRF protection, authorization and feature tests.
- The included prototype uses generic records as a temporary MVP scaffold. Replace with normalized domain models and dedicated migrations before production.
- Never commit production secrets or credentials.
