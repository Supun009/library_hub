# E2E Testing with Playwright

This directory contains end-to-end tests for the Library Management System using Playwright.

## Prerequisites

- **XAMPP/Apache running** on `http://localhost`
- **Library System** accessible at `http://localhost/LibraryHub`
- **Admin account** with credentials (default: `admin` / `admin123`)
- **Node.js** (v16 or higher)

## Setup

1. **Install dependencies:**

   ```bash
   cd tests
   npm install
   ```

2. **Install Playwright browsers:**

   ```bash
   npx playwright install
   ```

3. **Update admin credentials** (if different from defaults):
   - Edit `e2e/auth.setup.js`
   - Update the username and password in the authentication setup

## Running Tests

### Run all tests (headless)

```bash
npm run test:e2e
```

### Run tests with UI mode (recommended for development)

```bash
npm run test:e2e:ui
```

### Run tests in headed mode (see browser)

```bash
npm run test:e2e:headed
```

### Debug tests step-by-step

```bash
npm run test:e2e:debug
```

### View test report

```bash
npm run test:e2e:report
```

## Test Structure

```
tests/
├── e2e/
│   ├── auth.setup.js                    # Authentication setup (runs once)
│   ├── member-management.spec.js        # Member registration tests
│   └── helpers/
│       ├── test-data.js                 # Test data generators
│       └── page-objects/
│           └── member-management.page.js # Page Object Model
├── playwright.config.js                  # Playwright configuration
├── package.json                          # Dependencies and scripts
└── README.md                             # This file
```

## Test Coverage

### Member Management Tests

1. **Positive Tests:**
   - ✅ Register new member with valid data
   - ✅ Search member by username
   - ✅ Search member by email
   - ✅ Search member by full name
   - ✅ Verify member count increases

2. **Negative Tests:**
   - ❌ Duplicate username prevention
   - ❌ Duplicate email prevention
   - ❌ Required field validation
   - ❌ Email format validation

3. **UI Tests:**
   - 🎨 Form open/close behavior
   - 🎨 Success/error message display

## Configuration

### Base URL

The base URL is configured in `playwright.config.js`:

```javascript
baseURL: "http://localhost/LibraryHub";
```

### Timeouts

- Test timeout: 30 seconds
- Action timeout: 10 seconds
- Navigation timeout: 15 seconds

### Authentication

Tests use a shared authentication state saved in `.auth/admin.json` to avoid logging in for every test.

## Test Data Strategy

- **Unique data:** Each test generates unique usernames and emails using timestamps
- **No cleanup required:** Tests are designed to be re-runnable without manual cleanup
- **Collision prevention:** Random suffixes ensure uniqueness even in rapid test runs

## Troubleshooting

### Tests fail with "Target page, context or browser has been closed"

- Ensure XAMPP/Apache is running
- Verify the base URL is correct
- Check that the admin account exists

### Authentication fails

- Verify admin credentials in `e2e/auth.setup.js`
- Ensure the admin user exists in the database
- Check that login redirects to `/admin/dashboard`

### Tests timeout

- Increase timeouts in `playwright.config.js`
- Check database connection in PHP
- Verify Apache is not overloaded

### "Element not found" errors

- Ensure `data-testid` attributes are present in HTML
- Check that the page structure hasn't changed
- Verify selectors in page objects

## Adding New Tests

1. **Create a new spec file** in `e2e/`
2. **Add test IDs** to the relevant PHP templates
3. **Create a Page Object** in `helpers/page-objects/`
4. **Write tests** using the Page Object pattern
5. **Run tests** to verify

## CI/CD Integration

To run tests in CI/CD:

```bash
# Install dependencies
npm ci

# Install browsers
npx playwright install --with-deps

# Run tests
npm run test:e2e

# Upload test results
npx playwright show-report
```

## Best Practices

1. **Use data-testid selectors** - More stable than CSS classes
2. **Page Object Model** - Encapsulate page logic
3. **Unique test data** - Avoid test interdependencies
4. **Wait for elements** - Use Playwright's auto-waiting
5. **Meaningful assertions** - Test user-visible behavior

## Resources

- [Playwright Documentation](https://playwright.dev)
- [Best Practices](https://playwright.dev/docs/best-practices)
- [Page Object Model](https://playwright.dev/docs/pom)
