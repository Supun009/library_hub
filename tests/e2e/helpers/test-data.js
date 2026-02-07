// e2e/helpers/test-data.js

/**
 * Generate unique test data for member registration
 * @returns {Object} Test data with unique username and email
 */
function generateUniqueMemberData() {
  const timestamp = Date.now();
  const randomSuffix = Math.floor(Math.random() * 1000);

  return {
    fullName: `Test User ${timestamp}`,
    email: `testuser${timestamp}${randomSuffix}@test.com`,
    username: `TEST${timestamp}`,
    password: "TestPassword123!",
  };
}

/**
 * Generate invalid test data for negative testing
 * @returns {Object} Invalid test data
 */
function generateInvalidMemberData() {
  return {
    fullName: "Invalid User",
    email: "invalid-email", // Invalid email format
    username: "INVALID",
    password: "password123",
  };
}

/**
 * Generate duplicate username data (for testing duplicate prevention)
 * @param {string} existingUsername - Username that already exists
 * @returns {Object} Test data with duplicate username
 */
function generateDuplicateUsernameData(existingUsername) {
  const timestamp = Date.now();

  return {
    fullName: `Duplicate Test ${timestamp}`,
    email: `duplicate${timestamp}@test.com`,
    username: existingUsername, // Reuse existing username
    password: "TestPassword123!",
  };
}

module.exports = {
  generateUniqueMemberData,
  generateInvalidMemberData,
  generateDuplicateUsernameData,
};
