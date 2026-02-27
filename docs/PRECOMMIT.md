# Pre-Commit Setup

Enable repository hooks once per clone:

```bash
git config core.hooksPath .githooks
```

The pre-commit hook runs:
- staged PHP lint (`php -l`)
- inline `<script>` guard in views (non-error templates)
- inline `<style>` guard in views (non-error/PDF templates)
- targeted PHPUnit checks (`tests/unit/Helpers`, `tests/unit/Support`, `tests/feature/AdminBulkEndpointsTest.php`)

Manual run:

```bash
# Linux/macOS
bash tools/pre-commit-checks.sh

# Windows PowerShell
powershell -ExecutionPolicy Bypass -File tools/pre-commit-checks.ps1
```
