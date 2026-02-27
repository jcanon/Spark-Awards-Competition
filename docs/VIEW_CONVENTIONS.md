# View Conventions

## Flash Messages
- Use `<?= view('partials/flash') ?>` for standard success/error messages.
- Use one-sided rendering only when needed:
  - Success only: `<?= view('partials/flash', ['showError' => false]) ?>`
  - Error only: `<?= view('partials/flash', ['showSuccess' => false]) ?>`

## Form Markup
- Prefer shared form partials:
  - `partials/forms/input`
  - `partials/forms/select`
  - `partials/forms/textarea`
  - `partials/forms/actions`
- Prefer `partials/ui/card-start` + `partials/ui/card-end` for repeated card wrappers.

## JavaScript Placement
- Page-specific behavior belongs in `public/js/pages/*`.
- Shared utilities belong in `public/js/utils/*`.
- Avoid inline `<script>` in views (except explicit error template exceptions).

## CSS Placement
- Page CSS belongs in `public/css/pages/*`.
- Shared component CSS belongs in `public/css/components/*`.
- Avoid inline `<style>` in views (except error templates and PDF template).

## Namespace
- Use `window.Spark.*` for shared browser utilities.
- Avoid adding new global aliases.
