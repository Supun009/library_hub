// e2e/auth.setup.js
const { test as setup, expect } = require('@playwright/test');
const path = require('path');

const authFile = path.join(__dirname, '../.auth/admin.json');

/**
 * Setup authentication for admin user
 * This runs once before all tests and saves the authenticated state
 */
setup('authenticate as admin', async ({ page, baseURL }) => {
  // Navigate to login page
  await page.goto('/auth/login.php');
  
  // Fill in admin credentials
  // NOTE: Update these credentials to match your actual admin account
  await page.getByTestId('login-username').fill('admin');
  await page.getByTestId('login-password').fill('admin123');
  
  // Click login button
  await page.getByTestId('login-submit').click();
  
  // Wait for navigation to admin dashboard
  await page.waitForURL('**/admin/dashboard.php', { timeout: 10000 });
  
  // Verify we're logged in by checking for admin-specific content
  await expect(page).toHaveURL(/.*admin\/dashboard\.php/);
  
  // Save authenticated state
  await page.context().storageState({ path: authFile });
  
  console.log('✓ Admin authentication successful');
});
