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

/**
 * Generate unique book data
 * @returns {Object} Test data for book creation
 */
function generateBookData() {
  const timestamp = Date.now();
  const randomSuffix = Math.floor(Math.random() * 10000); // 4 digits

  return {
    title: `Test Book ${timestamp}`,
    // valid ISBN-13 format not strictly enforced by regex in PHP (just unique check), but let's make it look real
    isbn: `978-${Math.floor(100000000 + Math.random() * 900000000)}-${Math.floor(Math.random() * 10)}`,
    publicationYear: (2000 + Math.floor(Math.random() * 23)).toString(), // 2000-2023
    copies: 5,
  };
}

module.exports = {
  generateUniqueMemberData,
  generateInvalidMemberData,
  generateDuplicateUsernameData,
  generateBookData,
};
