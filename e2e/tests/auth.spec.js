// Public pages: nothing here needs a real account, and nothing changes data
// (the reset-password checks use a made-up email, so no code is ever sent).
// The app's redirects keep the index.php prefix (/index.php/login), so URLs
// are matched on their ending.
const { test, expect } = require('@playwright/test');

test.describe('Login', () => {
  test('shows the sign-in form', async ({ page }) => {
    await page.goto('/login');
    await expect(page.locator('#loginForm')).toBeVisible();
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
  });

  test('rejects a wrong password', async ({ page }) => {
    await page.goto('/login');
    await page.locator('input[name="email"]').fill('nobody@example.invalid');
    await page.locator('input[name="password"]').fill('wrong-password');
    await page.locator('#loginSubmit').click();
    await expect(page).toHaveURL(/\/login$/);
    await expect(page.locator('.alert-danger')).toBeVisible();
  });

  test('sends signed-out visitors to the login page', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/login$/);
  });
});

test.describe('Reset password', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/forgot-password');
    const email = page.locator('input[name="email"]');
    await email.fill('nobody@example.invalid');
    await email.press('Enter');
    await expect(page).toHaveURL(/\/reset-password$/);
  });

  test('asks for the code first, with no password fields yet', async ({ page }) => {
    await expect(page.getByRole('button', { name: 'Verify Code' })).toBeVisible();
    await expect(page.locator('input[name="password"]')).toHaveCount(0);
    await expect(page.locator('#newPasswordModal')).toHaveCount(0);
  });

  test('rejects a wrong code', async ({ page }) => {
    await page.locator('input[name="code"]').fill('123456');
    await page.getByRole('button', { name: 'Verify Code' }).click();
    await expect(page.locator('.alert-danger')).toContainText('invalid or has expired');
    await expect(page.locator('#newPasswordModal')).toHaveCount(0);
  });
});
