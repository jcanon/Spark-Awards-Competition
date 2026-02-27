#!/usr/bin/env bash
set -euo pipefail

staged_files=$(git diff --cached --name-only --diff-filter=ACM)
if [ -z "$staged_files" ]; then
  exit 0
fi

echo "[pre-commit] Running staged PHP lint..."
php_files=$(echo "$staged_files" | grep -E '\.php$' || true)
if [ -n "$php_files" ]; then
  echo "$php_files" | xargs -n 1 php -l >/dev/null
fi

echo "[pre-commit] Checking for inline scripts in views..."
inline_scripts=$(rg -n "<script(?! src)|<script>" app/Views --pcre2 | rg -v "app/Views/errors/html/error_exception.php" || true)
if [ -n "$inline_scripts" ]; then
  echo "Inline <script> blocks found in views (move to public/js):"
  echo "$inline_scripts"
  exit 1
fi

echo "[pre-commit] Checking for inline styles in views..."
inline_styles=$(rg -n "<style>" app/Views | rg -v "app/Views/errors/html|app/Views/receipt/pdf.php" || true)
if [ -n "$inline_styles" ]; then
  echo "Inline <style> blocks found in views (move to public/css):"
  echo "$inline_styles"
  exit 1
fi

echo "[pre-commit] Running targeted PHPUnit tests..."
vendor/bin/phpunit --no-coverage tests/unit/Helpers tests/unit/Support tests/feature/AdminBulkEndpointsTest.php >/dev/null

echo "[pre-commit] OK"
