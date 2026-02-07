// e2e/member-management.spec.js
const { test, expect } = require("@playwright/test");
const {
  MemberManagementPage,
} = require("./helpers/page-objects/member-management.page");
const {
  generateUniqueMemberData,
  generateInvalidMemberData,
  generateDuplicateUsernameData,
} = require("./helpers/test-data");

test.describe("Member Management - Register New Member", () => {
  let memberPage;
  let testMemberData;

  test.beforeEach(async ({ page }) => {
    memberPage = new MemberManagementPage(page);
    await memberPage.goto();

    // Generate unique test data for each test
    testMemberData = generateUniqueMemberData();
  });

  test("should successfully register a new member with valid data", async () => {
    // Act: Register new member
    await memberPage.registerMember(testMemberData);

    // Assert: Success message appears
    const successMessage = await memberPage.getSuccessMessage();
    expect(successMessage).toContain("Member registered successfully");

    // Assert: Member appears in the table
    const isMemberVisible = await memberPage.isMemberInTable(
      testMemberData.username,
    );
    expect(isMemberVisible).toBeTruthy();
  });

  test("should display member in search results after registration", async () => {
    // Arrange: Register a member first
    await memberPage.registerMember(testMemberData);
    await memberPage.getSuccessMessage(); // Wait for success

    // Act: Search for the newly registered member
    await memberPage.searchMember(testMemberData.username);

    // Assert: Member appears in search results
    const isMemberVisible = await memberPage.isMemberInTable(
      testMemberData.username,
    );
    expect(isMemberVisible).toBeTruthy();

    // Assert: Member details are correct
    const tableContent = await memberPage.membersTable.textContent();
    expect(tableContent).toContain(testMemberData.fullName);
    expect(tableContent).toContain(testMemberData.email);
  });

  test("should search member by email", async () => {
    // Arrange: Register a member
    await memberPage.registerMember(testMemberData);
    await memberPage.getSuccessMessage();

    // Act: Search by email
    await memberPage.searchMember(testMemberData.email);

    // Assert: Member is found
    const isMemberVisible = await memberPage.isMemberInTable(
      testMemberData.email,
    );
    expect(isMemberVisible).toBeTruthy();
  });

  test("should search member by full name", async () => {
    // Arrange: Register a member
    await memberPage.registerMember(testMemberData);
    await memberPage.getSuccessMessage();

    // Act: Search by name (partial match)
    const searchTerm = testMemberData.fullName.split(" ")[0]; // First word
    await memberPage.searchMember(searchTerm);

    // Assert: Member is found
    const isMemberVisible = await memberPage.isMemberInTable(
      testMemberData.fullName,
    );
    expect(isMemberVisible).toBeTruthy();
  });

  test("should show error when registering with duplicate username", async () => {
    // Arrange: Register first member
    await memberPage.registerMember(testMemberData);
    await memberPage.getSuccessMessage();

    // Act: Try to register another member with same username
    const duplicateData = generateDuplicateUsernameData(
      testMemberData.username,
    );
    await memberPage.registerMember(duplicateData);

    // Assert: Error message appears
    const errorMessage = await memberPage.getErrorMessage();
    expect(errorMessage).toContain("Username or Email already exists");
  });

  test("should show error when registering with duplicate email", async () => {
    // Arrange: Register first member
    await memberPage.registerMember(testMemberData);
    await memberPage.getSuccessMessage();

    // Act: Try to register with same email but different username
    const duplicateEmailData = {
      ...generateUniqueMemberData(),
      email: testMemberData.email, // Reuse same email
    };
    await memberPage.registerMember(duplicateEmailData);

    // Assert: Error message appears
    const errorMessage = await memberPage.getErrorMessage();
    expect(errorMessage).toContain("Username or Email already exists");
  });

  test("should require all fields to be filled", async ({ page }) => {
    // Arrange: Open form
    await memberPage.openAddMemberForm();

    // Act: Try to submit empty form
    await memberPage.submitButton.click();

    // Assert: HTML5 validation prevents submission
    // Check if form is still visible (not submitted)
    await expect(memberPage.fullNameInput).toBeVisible();

    // Verify required attributes exist
    await expect(memberPage.fullNameInput).toHaveAttribute("required", "");
    await expect(memberPage.emailInput).toHaveAttribute("required", "");
    await expect(memberPage.usernameInput).toHaveAttribute("required", "");
    await expect(memberPage.passwordInput).toHaveAttribute("required", "");
  });

  test("should validate email format", async ({ page }) => {
    // Arrange: Open form and fill with invalid email
    await memberPage.openAddMemberForm();

    const invalidData = {
      fullName: "Test User",
      email: "invalid-email-format", // Invalid format
      username: "TESTUSER",
      password: "password123",
    };

    // Act: Fill form with invalid email
    await memberPage.fillMemberForm(invalidData);
    await memberPage.submitButton.click();

    // Assert: HTML5 email validation prevents submission
    // Form should still be visible
    await expect(memberPage.emailInput).toBeVisible();

    // Email input should have type="email"
    await expect(memberPage.emailInput).toHaveAttribute("type", "email");
  });

  test("should close the form when cancel button is clicked", async ({
    page,
  }) => {
    // Arrange: Open the form
    await memberPage.openAddMemberForm();
    await expect(memberPage.fullNameInput).toBeVisible();

    // Act: Click cancel button
    const cancelButton = page.locator('button:has-text("Cancel")');
    await cancelButton.click();

    // Assert: Form should be hidden
    await expect(memberPage.fullNameInput).toBeHidden();
  });

  test("should display correct member count after registration", async () => {
    // Arrange: Get initial count
    const initialCount = await memberPage.getMemberCount();

    // Act: Register a new member
    await memberPage.registerMember(testMemberData);
    await memberPage.getSuccessMessage();

    // Reload to see updated count
    await memberPage.goto();

    // Assert: Count increased by 1
    const newCount = await memberPage.getMemberCount();
    expect(newCount).toBeGreaterThan(initialCount);
  });
});
