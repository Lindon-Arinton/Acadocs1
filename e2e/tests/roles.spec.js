// Signed-in checks per role. Each block skips itself until that role's account
// is in e2e/accounts.json, so the suite still runs on a fresh machine.
// URLs are matched on their ending: redirects keep the index.php prefix.
const { test, expect } = require('@playwright/test');
const { accountFor, skipReason, login } = require('./helpers');

test.describe('Principal (admin)', () => {
  test.skip(!accountFor('admin'), skipReason('admin'));
  test.beforeEach(async ({ page }) => login(page, 'admin'));

  test('lands on the school dashboard', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page.getByText('Welcome back,')).toBeVisible();
    await expect(page.getByText('Enrollment Breakdown')).toBeAttached();
  });

  test('asks before logging out, and Cancel keeps you signed in', async ({ page }) => {
    await page.goto('/dashboard');
    await page.locator('.profile-dropdown-toggle').click();
    await page.getByRole('link', { name: 'Logout' }).click();
    const prompt = page.locator('.swal2-popup');
    await expect(prompt).toContainText('Log out?');
    await prompt.getByRole('button', { name: 'Cancel' }).click();
    await expect(page).toHaveURL(/\/dashboard$/);
  });
});

test.describe('Teacher', () => {
  test.skip(!accountFor('teacher'), skipReason('teacher'));
  test.beforeEach(async ({ page }) => login(page, 'teacher'));

  test('lands on the teacher dashboard', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveURL(/\/teacher-dashboard$/);
    await expect(page.getByText('My MPS Performance')).toBeVisible();
  });

  test('cannot open User Management', async ({ page }) => {
    await page.goto('/users');
    await expect(page).not.toHaveURL(/\/users$/);
  });

  test('opens Messages on "Select a conversation"', async ({ page }) => {
    await page.goto('/chat');
    await expect(page.getByText('Select a conversation')).toBeVisible();
  });
});
