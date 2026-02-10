const { test, expect } = require("@playwright/test");
const {
  ManageCategoriesPage,
} = require("./helpers/page-objects/manage-categories.page");

test.describe("Category Management", () => {
  let categoryPage;

  test.beforeEach(async ({ page }) => {
    categoryPage = new ManageCategoriesPage(page);
    await categoryPage.goto();
  });

  test("should add a new category", async ({ page }) => {
    const categoryName = `Test Category ${Date.now()}`;
    await categoryPage.addCategory(categoryName);

    // Check for success message
    const successMsg = await categoryPage.getSuccessMessage();
    expect(successMsg).toContain("Category added successfully");

    // Verify in list
    const exists = await categoryPage.isCategoryInTable(categoryName);
    expect(exists).toBeTruthy();
  });

  test("should not add duplicate category", async ({ page }) => {
    const categoryName = `Duplicate Test ${Date.now()}`;

    // Add first time
    await categoryPage.addCategory(categoryName);
    await expect(page.locator(".text-green-700")).toBeVisible();

    // Add second time
    await categoryPage.addCategory(categoryName);

    // Check for error
    const errorMsg = await categoryPage.getErrorMessage();
    expect(errorMsg).toContain("Category already exists");
  });

  test("should search for a category", async ({ page }) => {
    const uniqueId = Date.now();
    const categoryName = `Searchable Category ${uniqueId}`;

    await categoryPage.addCategory(categoryName);
    await expect(page.locator(".text-green-700")).toBeVisible();

    // Perform search
    await categoryPage.searchCategory(uniqueId.toString());

    // Verify specific row visibility
    const rows = page.locator("tbody tr");
    await expect(rows).toContainText(categoryName);

    // Ensure extraneous rows are filtered out (heuristic: if we search for a specific ID, we likely only get 1 or few results)
  });
});
