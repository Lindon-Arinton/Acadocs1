// @ts-check
const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests',
  // One PHP dev server and one shared database: run tests one at a time.
  fullyParallel: false,
  workers: 1,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    // Same address as the app's baseURL (php/app/Config/App.php).
    baseURL: 'http://localhost:8080',
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
  },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
  // Starts the app for the run (or reuses one you already started with `php spark serve`).
  webServer: {
    command: 'php spark serve --port 8080',
    cwd: '../php',
    url: 'http://localhost:8080/login',
    reuseExistingServer: true,
    timeout: 60_000,
    stderr: 'ignore', // PHP's built-in server logs every request to stderr
  },
});
