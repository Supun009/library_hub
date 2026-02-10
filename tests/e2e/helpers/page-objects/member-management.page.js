// e2e/helpers/page-objects/member-management.page.js

/**
 * Page Object for Member Management page
 * Encapsulates selectors and actions for the manage_members.php page
 */
class MemberManagementPage {
  constructor(page) {
    this.page = page;

    // Selectors
    this.addMemberButton = page.getByTestId("add-member-button");
    this.fullNameInput = page.getByTestId("input-full-name");
    this.emailInput = page.getByTestId("input-email");
    this.usernameInput = page.getByTestId("input-username");
    this.passwordInput = page.getByTestId("input-password");
    this.submitButton = page.getByTestId("submit-register-member");
    this.successAlert = page.getByTestId("success-alert");
    this.errorAlert = page.getByTestId("error-alert");
    this.searchInput = page.getByTestId("search-members");
    this.membersTable = page.locator("table tbody");
  }

  /**
   * Navigate to the member management page
   */
  async goto() {
    await this.page.goto(
      "http://localhost/lib_system/library_system/admin/members",
    );
  }

  /**
   * Open the "Add New Member" form
   */
  async openAddMemberForm() {
    await this.addMemberButton.click();
    // Wait for form to be visible
    await this.fullNameInput.waitFor({ state: "visible" });
  }

  /**
   * Fill the member registration form
   * @param {Object} memberData - Member data to fill
   */
  async fillMemberForm(memberData) {
    await this.fullNameInput.fill(memberData.fullName);
    await this.emailInput.fill(memberData.email);
    await this.usernameInput.fill(memberData.username);
    await this.passwordInput.fill(memberData.password);
  }

  /**
   * Submit the member registration form
   */
  async submitForm() {
    await this.submitButton.click();
  }

  /**
   * Register a new member (complete flow)
   * @param {Object} memberData - Member data
   */
  async registerMember(memberData) {
    await this.openAddMemberForm();
    await this.fillMemberForm(memberData);
    await this.submitForm();
  }

  /**
   * Get success message text
   * @returns {Promise<string>} Success message
   */
  async getSuccessMessage() {
    await this.successAlert.waitFor({ state: "visible", timeout: 5000 });
    return await this.successAlert.textContent();
  }

  /**
   * Get error message text
   * @returns {Promise<string>} Error message
   */
  async getErrorMessage() {
    await this.errorAlert.waitFor({ state: "visible", timeout: 5000 });
    return await this.errorAlert.textContent();
  }

  /**
   * Search for a member
   * @param {string} searchTerm - Search term
   */
  async searchMember(searchTerm) {
    await this.searchInput.fill(searchTerm);
    await this.searchInput.press("Enter");
    // Wait for page to reload with search results
    await this.page.waitForLoadState("networkidle");
  }

  /**
   * Check if member appears in the table
   * @param {string} identifier - Username, email, or name to search for
   * @returns {Promise<boolean>} True if member is found
   */
  async isMemberInTable(identifier) {
    const tableText = await this.membersTable.textContent();
    return tableText.includes(identifier);
  }

  /**
   * Get row count in members table
   * @returns {Promise<number>} Number of member rows
   */
  async getMemberCount() {
    // Try to get total count from the stats text first (e.g., "Total: 12 member(s)")
    const totalStats = this.page.locator(".table-header p");
    if ((await totalStats.count()) > 0) {
      const text = await totalStats.textContent();
      const match = text.match(/Total:\s*(\d+)/);
      if (match) {
        return parseInt(match[1], 10);
      }
    }

    // Fallback to row counting (visible rows only)
    const rows = await this.membersTable.locator("tr").count();
    // Subtract 1 if there's a "No members found" row
    const noMembersRow = await this.membersTable
      .locator("text=No members found")
      .count();
    return noMembersRow > 0 ? 0 : rows;
  }
}

module.exports = { MemberManagementPage };
