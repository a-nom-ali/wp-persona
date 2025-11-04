# Playwright Smoke Tests (Scaffold)

1. Install dependencies:

```bash
cd tests/playwright
npm init playwright@latest
```

2. Configure the base URL to point at your WordPress test site (e.g., `http://campaign-forge.local`).

3. Add smoke specs that cover persona creation, chat streaming, and export flows. Keep tests minimal so they can run in CI.

4. Run the suite:

```bash
npx playwright test
```

> Tip: use `npx playwright codegen http://campaign-forge.local/wp-admin/post-new.php?post_type=ai_persona` to record UI interactions before refining them into stable selectors.
