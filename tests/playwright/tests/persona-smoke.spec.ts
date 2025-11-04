import { test, expect } from '@playwright/test';

const ADMIN_USER = process.env.AI_PERSONA_USER || 'admin';
const ADMIN_PASS = process.env.AI_PERSONA_PASS || 'admin';

async function loginIfNeeded(page) {
  await page.goto('/wp-login.php');
  if (await page.locator('#user_login').isVisible()) {
    await page.fill('#user_login', ADMIN_USER);
    await page.fill('#user_pass', ADMIN_PASS);
    await page.click('#wp-submit');
    await expect(page).toHaveURL(/wp-admin/);
  }
}

test('persona editor loads and saves draft', async ({ page }) => {
  await loginIfNeeded(page);

  await page.goto('/wp-admin/post-new.php?post_type=ai_persona');
  await expect(page.locator('text=Persona Structure')).toBeVisible();

  await page.fill('#title', 'Playwright Persona');
  await page.fill('#ai-persona-role-noscript', 'You are a test persona.');

  await page.locator('#publish').click();
  await expect(page.locator('#message')).toContainText('draft updated');
});
