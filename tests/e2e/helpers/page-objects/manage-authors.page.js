const { expect } = require("@playwright/test");

class ManageAuthorsPage {
  constructor(page) {
    this.page = page;
    this.authorNameInput = page.locator('input[name="author_name"]');
    this.submitButton = page.locator('button[type="submit"]');
    this.tableRows = page.locator("tbody tr");
    this.searchInput = page.locator('input[name="search"]');
  }

  async goto() {
    // Assuming the routing from the PHP redirect hints
    await this.page.goto(
      "http://localhost/lib_system/library_system/admin/authors",
    );
  }

  async addAuthor(name) {
    await this.authorNameInput.fill(name);
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

  async isAuthorInTable(name) {
    const count = await this.tableRows.filter({ hasText: name }).count();
    return count > 0;
  }

  async searchAuthor(name) {
    await this.searchInput.fill(name);
    await this.page.waitForTimeout(600); // 500ms debounce + buffer
    await this.page.waitForLoadState("networkidle");
  }
}

module.exports = { ManageAuthorsPage };
