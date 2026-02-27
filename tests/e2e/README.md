# JS Smoke Tests

This project currently uses manual smoke checks for critical front-end behavior after refactors.

## Target Flows

1. Submission Form (`/submissions/create/{compId}`)
- Required field validation
- Word limit validation (short/full descriptions)
- Video URL validation
- Photo modal preview opens

2. Admin Submission Edit (`/admin/submissions/edit/{entryId}`)
- Receipt modal loads AJAX content
- Photo/PDF preview modal opens correctly
- Before-unload warning triggers on dirty form

3. Judging Entry (`/judging/entry/{entryId}/status/{status}`)
- Gallery modal opens
- Next/Prev modal navigation works
- Keyboard arrow navigation works

## Suggested Automation Stack

Use Playwright for deterministic smoke checks:
- Chromium-only run for CI speed
- Fixed test fixtures for entry IDs/users
- One happy-path test per flow above

## Minimal CI Command (future)

```bash
npx playwright test tests/e2e/*.spec.ts --project=chromium
```
