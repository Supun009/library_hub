// @ts-check
const { defineConfig, devices } = require("@playwright/test");

/**
 * @see https://playwright.dev/docs/test-configuration
 */
module.exports = defineConfig({
  testDir: "./e2e",

  /* Maximum time one test can run for */
  timeout: 30 * 1000,

  /* Test execution settings */
  fullyParallel: false, // Run tests sequentially to avoid DB conflicts
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: 1, // Single worker to avoid race conditions

  /* Reporter to use */
  reporter: [["html", { outputFolder: "playwright-report" }], ["list"]],

  /* Shared settings for all projects */
  use: {
    /* Base URL for navigation */
    baseURL: "http://localhost/lib_system/library_system",

    /* Collect trace when retrying the failed test */
    trace: "on-first-retry",

    /* Screenshot on failure */
    screenshot: "only-on-failure",

    /* Video on failure */
    video: "retain-on-failure",

    /* Timeout for each action */
    actionTimeout: 10 * 1000,

    /* Navigation timeout */
    navigationTimeout: 15 * 1000,
  },

  /* Configure projects for major browsers */
  projects: [
    {
      name: "setup",
      testMatch: /.*\.setup\.js/,
      use: {
        baseURL: "http://localhost/lib_system/library_system",
      },
    },
    {
      name: "chromium",
      testMatch: /.*\.spec\.js/, // Only run spec files, not setup files
      use: {
        ...devices["Desktop Chrome"],
        // Use authenticated state from setup
        storageState: ".auth/admin.json",
      },
      dependencies: ["setup"],
    },
  ],

  /* Run your local dev server before starting the tests */
  // webServer: {
  //   command: 'npm run start',
  //   url: 'http://localhost/lib_system/library_system',
  //   reuseExistingServer: !process.env.CI,
  // },
});
