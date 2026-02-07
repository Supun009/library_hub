# Quick Start Guide - E2E Testing

## 🚀 Get Started in 3 Steps

### Step 1: Install Dependencies

```bash
cd e:\xampp\htdocs\LibraryHub\tests
npm install
npx playwright install
```

### Step 2: Verify XAMPP is Running

- Start Apache from XAMPP Control Panel
- Verify: http://localhost/LibraryHub is accessible
- Ensure you have an admin account (username: `admin`, password: `admin123`)

### Step 3: Run Tests

```bash
# Run all tests with UI (recommended first time)
npm run test:e2e:ui

# Or run in headless mode
npm run test:e2e
```

## 📊 Expected Results

You should see tests for:

- ✅ Registering new members
- ✅ Searching members
- ✅ Duplicate prevention
- ✅ Form validation

## 🔧 Customize Admin Credentials

If your admin credentials are different, edit `e2e/auth.setup.js`:

```javascript
await page.getByTestId("login-username").fill("your-admin-username");
await page.getByTestId("login-password").fill("your-admin-password");
```

## 📹 View Test Results

After running tests:

```bash
npm run test:e2e:report
```

This opens an HTML report with screenshots and videos of test runs.

## 🐛 Debugging Failed Tests

```bash
# Run in debug mode with Playwright Inspector
npm run test:e2e:debug

# Or run specific test file
npx playwright test e2e/member-management.spec.js --debug
```

## 📝 Next Steps

1. Review the test code in `e2e/member-management.spec.js`
2. Check the Page Object in `e2e/helpers/page-objects/member-management.page.js`
3. Add your own tests following the same pattern
4. Read the full README.md for advanced usage

## ⚡ Common Issues

**Issue:** Tests fail with "Target closed"

- **Fix:** Ensure XAMPP is running and base URL is correct

**Issue:** Authentication fails

- **Fix:** Verify admin credentials in `auth.setup.js`

**Issue:** "Element not found"

- **Fix:** Check that data-testid attributes exist in HTML

---

Happy Testing! 🎉
