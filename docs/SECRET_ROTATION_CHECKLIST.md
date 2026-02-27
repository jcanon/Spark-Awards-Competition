# Secret Rotation Checklist

The `.env` file was previously tracked in Git history, so assume past secret exposure.

## Rotate Immediately
- Authorize.Net
  - `ANET_SANDBOX_API_LOGIN_ID`
  - `ANET_SANDBOX_TRANSACTION_KEY`
  - `ANET_SANDBOX_SIGNATURE_KEY`
  - `ANET_PRODUCTION_API_LOGIN_ID`
  - `ANET_PRODUCTION_TRANSACTION_KEY`
  - `ANET_PRODUCTION_SIGNATURE_KEY`
- SMTP / mail credentials
- Any API keys in `.env`
- Session/auth-related keys if present

## After Rotation
1. Update local `.env` with new values.
2. Update deployment secrets store(s).
3. Invalidate old credentials in provider dashboards.
4. Verify payment + auth + email flows in sandbox/production.

## Git Hygiene
- `.env` is now ignored by `.gitignore` and should remain untracked.
- If `.env` appears again, run:

```bash
git rm --cached .env
```
