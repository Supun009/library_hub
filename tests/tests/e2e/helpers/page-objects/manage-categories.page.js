const { expect } = require("@playwright/test");

class ManageCategoriesPage {
  constructor(page) {
    this.page = page;
    this.categoryNameInput = page.locator('input[name="category_name"]');
    this.submitButton = page.locator('button[type="submit"]');
    this.tableRows = page.locator("tbody tr");
    this.searchInput = page.locator('input[name="search"]');
  }

  async goto() {
    // Assuming the routing from the PHP redirect hints
    await this.page.goto(
      "http://localhost/lib_system/library_system/admin/categories",
    );
  }

  async addCategory(name) {
    await this.categoryNameInput.fill(name);
    await this.submitButton.click();
  }

  async getSuccessMessage() {
    const successAlert = this.page.locator(".text-green-700");
    if (await successAlert.isVisible()) {
      return await successAlert.textContent();
    }
    return null;
  }

  async getErrorMessage() {
    const errorAlert = this.page.locator(".text-red-700");
    if (await errorAlert.isVisible()) {
      return await errorAlert.textContent();
    }
    return null;
  }

  async isCategoryInTable(name) {
    // Determine if a row contains the category name column text
    const count = await this.tableRows.filter({ hasText: name }).count();
    return count > 0;
  }

  async searchCategory(name) {
    await this.searchInput.fill(name);
    // Wait for the form submission triggered by the input event (debounced 500ms)
    // or just press enter if the JS implementation allows, but the JS uses input event + debounce
    await this.page.waitForTimeout(600); // 500ms debounce + buffer
    await this.page.waitForLoadState("networkidle");
  }
}

module.exports = { ManageCategoriesPage };
