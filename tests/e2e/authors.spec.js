const { test, expect } = require("@playwright/test");
const {
  ManageAuthorsPage,
} = require("./helpers/page-objects/manage-authors.page");

test.describe("Author Management", () => {
  let authorPage;

  test.beforeEach(async ({ page }) => {
    authorPage = new ManageAuthorsPage(page);
    await authorPage.goto();
  });

  test("should add a new author", async ({ page }) => {
    const authorName = `Test Author ${Date.now()}`;
    await authorPage.addAuthor(authorName);

    // Check for success message
    const successMsg = await authorPage.getSuccessMessage();
    expect(successMsg).toContain("Author added successfully");

    // Verify in list
    const exists = await authorPage.isAuthorInTable(authorName);
    expect(exists).toBeTruthy();
  });

  test("should not add duplicate author", async ({ page }) => {
    const authorName = `Duplicate Author ${Date.now()}`;

    // Add first time
    await authorPage.addAuthor(authorName);
    await expect(page.locator(".text-green-700")).toBeVisible();

    // Add second time
    await authorPage.addAuthor(authorName);

    // Check for error
    const errorMsg = await authorPage.getErrorMessage();
    expect(errorMsg).toContain("Author already exists");
  });

  test("should search for an author", async ({ page }) => {
    const uniqueId = Date.now();
    const authorName = `Searchable Author ${uniqueId}`;

    await authorPage.addAuthor(authorName);
    await expect(page.locator(".text-green-700")).toBeVisible();

    // Perform search
    await authorPage.searchAuthor(uniqueId.toString());

    // Verify visibility
    const rows = page.locator("tbody tr");
    await expect(rows).toContainText(authorName);
  });
});
