// Shared helpers for the ACADOCS Playwright tests.
//
// Accounts come from e2e/accounts.json (git-ignored; copy accounts.example.json):
//   { "admin": { "email": "...", "password": "..." }, "teacher": { ... } }
const fs = require('fs');
const path = require('path');
const { expect } = require('@playwright/test');

const accountsFile = path.join(__dirname, '..', 'accounts.json');
const accounts = fs.existsSync(accountsFile) ? JSON.parse(fs.readFileSync(accountsFile, 'utf8')) : {};

/** { email, password } for a role ('admin', 'teacher', …), or null when not configured. */
function accountFor(role) {
  const a = accounts[role];

  return a && a.email && a.password ? a : null;
}

/** Why a role's tests are skipped, naming exactly what's missing in accounts.json. */
function skipReason(role) {
  if (!fs.existsSync(accountsFile)) {
    return 'e2e/accounts.json not found: copy accounts.example.json to accounts.json and fill it in';
  }
  const a = accounts[role] || {};
  if (!a.email) return `No email for "${role}" in e2e/accounts.json`;
  if (!a.password) return `Password for "${role}" (${a.email}) is blank in e2e/accounts.json (saved the file?)`;

  return '';
}

/** Signs in through the real login form. */
async function login(page, role) {
  const account = accountFor(role);
  if (!account) {
    throw new Error(`Add a "${role}" account to e2e/accounts.json`);
  }

  await page.goto('/login');
  await page.locator('input[name="email"]').fill(account.email);
  await page.locator('input[name="password"]').fill(account.password);
  await page.locator('#loginSubmit').click();
  // Redirects may carry the index.php prefix (/index.php/dashboard).
  await expect(page).not.toHaveURL(/\/login$/);
}

module.exports = { accountFor, skipReason, login };
