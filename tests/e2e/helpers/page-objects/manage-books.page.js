const { expect } = require("@playwright/test");

class ManageBooksPage {
  constructor(page) {
    this.page = page;
    this.titleInput = page.locator('input[name="title"]');
    this.isbnInput = page.locator('input[name="isbn"]');
    this.copiesInput = page.locator('input[name="total_copies"]');

    // Publication Year
    this.yearDisplay = page.locator("#publication_year_display");
    this.yearContainer = page.locator("#year_dropdown_container");

    // Category
    this.categoryInput = page.locator("#category_search");
    this.categoryDropdown = page.locator("#category_dropdown");

    // Author (first row)
    this.authorInput = page.locator(".author-search").first();
    this.authorDropdown = page.locator(".author-dropdown").first();

    this.submitButton = page.locator('button[type="submit"]');
  }

  async gotoAddBook() {
    await this.page.goto(
      "http://localhost/lib_system/library_system/admin/books/add",
    );
  }

  /**
   * Select a year from the dropdown
   * @param {string} year
   */
  async selectYear(year) {
    await this.yearDisplay.click();
    await this.page
      .locator(`#year_dropdown_container div:has-text("${year}")`)
      .click();
  }

  /**
   * Search and select a category.
   * If categoryName is provided, searches for it.
   * If not, types 'a' and selects the first one.
   */
  async selectCategory(categoryName = "a") {
    await this.categoryInput.fill(categoryName);
    // Wait for dropdown options to appear
    await this.page.waitForSelector(".category-option");

    if (categoryName.length > 1) {
      // Try to find exact match or partial match
      await this.page
        .locator(`.category-option:has-text("${categoryName}")`)
        .first()
        .click();
    } else {
      // Just pick the first one
      await this.page.locator(".category-option").first().click();
    }
  }

  /**
   * Search and select an author (for the first row).
   * If authorName is provided, searches for it.
   * If not, types 'a' and selects the first one.
   */
  async selectAuthor(authorName = "a") {
    await this.authorInput.fill(authorName);
    // Wait for dropdown options to appear
    await this.page.waitForSelector(".author-option");

    if (authorName.length > 1) {
      await this.page
        .locator(`.author-option:has-text("${authorName}")`)
        .first()
        .click();
    } else {
      await this.page.locator(".author-option").first().click();
    }
  }

  async fillBookForm(bookData) {
    await this.titleInput.fill(bookData.title);
    await this.isbnInput.fill(bookData.isbn);

    if (bookData.publicationYear) {
      await this.selectYear(bookData.publicationYear);
    }

    if (bookData.copies) {
      await this.copiesInput.fill(bookData.copies.toString());
    }

    // Handle generic selection if specific names aren't provided in test data
    // Assuming backend has some data. If not, these tests will fail (Prerequisite).
    await this.selectCategory(bookData.categoryName || "S");
    await this.selectAuthor(bookData.authorName || "a");
  }

  async submit() {
    await this.submitButton.click();
  }

  async getSuccessMessage() {
    // Assuming redirection to book list with msg=book_added implies success
    // Or we can check for an alert on the book list page if it exists.
    // Based on add_book.php: redirect('admin/books?msg=book_added');
    await this.page.waitForURL(/admin\/books/);
    return this.page.url();
  }

  async getErrorMessage() {
    const errorAlert = this.page.locator(".text-red-700");
    // Wait for the error to appear (default timeout if not specified, e.g. 5s)
    try {
      await errorAlert.waitFor({ state: "visible", timeout: 5000 });
      return await errorAlert.textContent();
    } catch (e) {
      return null;
    }
  }
}

module.exports = { ManageBooksPage };
