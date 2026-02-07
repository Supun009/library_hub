/**
 * Issue Book - Search Functionality
 * Handles member and book search with autocomplete dropdowns
 */

let selectedBooks = []; // Array to store selected books

/**
 * Initialize member search functionality (AJAX)
 */
function initMemberSearch() {
  const memberSearch = document.getElementById("member_search");
  const memberDropdown = document.getElementById("member_dropdown");
  const memberIdHidden = document.getElementById("member_id_hidden");
  const selectedMemberInfo = document.getElementById("selected_member_info");

  if (!memberSearch) return;

  let searchTimeout;

  memberSearch.addEventListener("input", function () {
    const searchTerm = this.value.trim();

    // Clear previous timeout
    if (searchTimeout) {
      clearTimeout(searchTimeout);
    }

    if (searchTerm.length === 0) {
      memberDropdown.classList.add("hidden");
      memberIdHidden.value = "";
      selectedMemberInfo.textContent = "";
      return;
    }

    if (searchTerm.length < 2) {
      memberDropdown.innerHTML =
        '<div class="px-3 py-2 text-sm text-gray-500">Type at least 2 characters to search</div>';
      memberDropdown.classList.remove("hidden");
      return;
    }

    // Show loading state
    memberDropdown.innerHTML =
      '<div class="px-3 py-2 text-sm text-gray-500">Searching...</div>';
    memberDropdown.classList.remove("hidden");

    // Debounce the search
    searchTimeout = setTimeout(() => {
      fetch(`ajax/search-members?q=${encodeURIComponent(searchTerm)}`)
        .then((response) => {
          if (!response.ok) throw new Error("Network response was not ok");
          return response.json();
        })
        .then((members) => {
          if (members.length === 0) {
            memberDropdown.innerHTML =
              '<div class="px-3 py-2 text-sm text-gray-500">No members found</div>';
          } else {
            memberDropdown.innerHTML = members
              .map(
                (m) => `
                <div class="px-3 py-2 hover:bg-indigo-50 cursor-pointer text-sm member-option" data-id="${m.member_id}" data-name="${escapeHtml(m.full_name)}" data-uid="${escapeHtml(m.uid)}">
                    <div class="font-medium text-gray-900">${escapeHtml(m.full_name)}</div>
                    <div class="text-xs text-gray-500">Username: ${escapeHtml(m.uid)} • Books Issued: ${m.issued_books_count}</div>
                </div>
            `,
              )
              .join("");

            // Add click handlers
            memberDropdown
              .querySelectorAll(".member-option")
              .forEach((option) => {
                option.addEventListener("click", function () {
                  const id = this.dataset.id;
                  const name = this.dataset.name;
                  const uid = this.dataset.uid;

                  memberSearch.value = name;
                  memberIdHidden.value = id;
                  selectedMemberInfo.textContent = `Selected: ${name} (${uid})`;
                  memberDropdown.classList.add("hidden");
                });
              });
          }
        })
        .catch((error) => {
          console.error("Error fetching members:", error);
          memberDropdown.innerHTML =
            '<div class="px-3 py-2 text-sm text-red-500">Error loading members</div>';
        });
    }, 300);
  });

  // Hide dropdown when clicking outside
  document.addEventListener("click", function (e) {
    if (
      !memberSearch.contains(e.target) &&
      !memberDropdown.contains(e.target)
    ) {
      memberDropdown.classList.add("hidden");
    }
  });
}

/**
 * Initialize book search functionality with AJAX
 */
function initBookSearch() {
  const bookSearch = document.getElementById("book_search");
  const bookDropdown = document.getElementById("book_dropdown");

  if (!bookSearch) {
    console.error("book_search element not found");
    return;
  }

  let searchTimeout;

  bookSearch.addEventListener("input", function () {
    const searchTerm = this.value.trim();

    // Clear previous timeout
    if (searchTimeout) {
      clearTimeout(searchTimeout);
    }

    if (searchTerm.length === 0) {
      bookDropdown.classList.add("hidden");
      return;
    }

    if (searchTerm.length < 2) {
      bookDropdown.innerHTML =
        '<div class="px-3 py-2 text-sm text-gray-500">Type at least 2 characters to search</div>';
      bookDropdown.classList.remove("hidden");
      return;
    }

    // Show loading state
    bookDropdown.innerHTML =
      '<div class="px-3 py-2 text-sm text-gray-500">Searching...</div>';
    bookDropdown.classList.remove("hidden");

    // Debounce the search (wait 300ms after user stops typing)
    searchTimeout = setTimeout(() => {
      fetchBooks(searchTerm, bookDropdown);
    }, 300);
  });

  // Hide dropdown when clicking outside
  document.addEventListener("click", function (e) {
    if (!bookSearch.contains(e.target) && !bookDropdown.contains(e.target)) {
      bookDropdown.classList.add("hidden");
    }
  });
}

/**
 * Fetch books from server via AJAX
 */
function fetchBooks(searchTerm, bookDropdown) {
  fetch(`ajax/search-books?q=${encodeURIComponent(searchTerm)}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .then((books) => {
      if (books.length === 0) {
        bookDropdown.innerHTML =
          '<div class="px-3 py-2 text-sm text-gray-500">No books found</div>';
        bookDropdown.classList.remove("hidden");
        return;
      }

      // Filter out already selected books
      const availableBooks = books.filter(
        (b) => !selectedBooks.find((sb) => sb.book_id === b.book_id),
      );

      if (availableBooks.length === 0) {
        bookDropdown.innerHTML =
          '<div class="px-3 py-2 text-sm text-gray-500">All matching books already selected</div>';
        bookDropdown.classList.remove("hidden");
        return;
      }

      bookDropdown.innerHTML = availableBooks
        .map(
          (b) => `
            <div class="px-3 py-2 hover:bg-indigo-50 cursor-pointer text-sm book-option" data-id="${b.book_id}" data-title="${escapeHtml(b.title)}" data-isbn="${escapeHtml(b.isbn || "")}">
                <div class="font-medium text-gray-900">${escapeHtml(b.title)}</div>
                <div class="text-xs text-gray-500">ISBN: ${escapeHtml(b.isbn || "N/A")}</div>
            </div>
        `,
        )
        .join("");

      bookDropdown.classList.remove("hidden");

      // Add click handlers
      document.querySelectorAll(".book-option").forEach((option) => {
        option.addEventListener("click", function () {
          const id = this.dataset.id;
          const title = this.dataset.title;
          const isbn = this.dataset.isbn;

          addBookToList({ book_id: id, title: title, isbn: isbn });
          document.getElementById("book_search").value = "";
          bookDropdown.classList.add("hidden");
        });
      });
    })
    .catch((error) => {
      console.error("Error fetching books:", error);
      bookDropdown.innerHTML =
        '<div class="px-3 py-2 text-sm text-red-500">Error loading books. Please try again.</div>';
      bookDropdown.classList.remove("hidden");
    });
}

/**
 * Add a book to the selected books list
 */
function addBookToList(book) {
  // Check if already added
  if (selectedBooks.find((b) => b.book_id === book.book_id)) {
    return;
  }

  selectedBooks.push(book);
  renderSelectedBooks();
}

/**
 * Remove a book from the selected books list
 */
function removeBookFromList(bookId) {
  selectedBooks = selectedBooks.filter((b) => b.book_id !== bookId);
  renderSelectedBooks();
}

/**
 * Render the selected books list
 */
function renderSelectedBooks() {
  const container = document.getElementById("selected-books-list");
  const hiddenInputsContainer = document.getElementById("book-ids-container");

  if (!container) return;

  if (selectedBooks.length === 0) {
    container.innerHTML =
      '<div class="text-sm text-gray-500 py-2">No books selected. Search and click to add books.</div>';
    hiddenInputsContainer.innerHTML = "";
    return;
  }

  // Render selected books
  container.innerHTML = selectedBooks
    .map(
      (book) => `
        <div class="flex items-center justify-between gap-2 p-2 bg-gray-50 rounded border border-gray-200">
            <div class="flex-1 min-w-0">
                <div class="text-sm font-medium text-gray-900 truncate">${escapeHtml(book.title)}</div>
                <div class="text-xs text-gray-500">ISBN: ${escapeHtml(book.isbn || "N/A")}</div>
            </div>
            <button
                type="button"
                onclick="removeBookFromList('${book.book_id}')"
                class="flex-shrink-0 inline-flex items-center justify-center h-8 w-8 rounded-md border border-red-200 bg-white text-red-600 hover:bg-red-50 transition-colors"
                title="Remove book"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
    `,
    )
    .join("");

  // Update hidden inputs for form submission
  hiddenInputsContainer.innerHTML = selectedBooks
    .map(
      (book) =>
        `<input type="hidden" name="book_ids[]" value="${book.book_id}">`,
    )
    .join("");

  // Reinitialize lucide icons
  if (typeof lucide !== "undefined") {
    lucide.createIcons();
  }
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

/**
 * Initialize the issue book search functionality
 */
function initIssueBookSearch() {
  // Initialize member search
  initMemberSearch();

  // Initialize book search (AJAX-based)
  initBookSearch();

  // Initialize empty selected books list
  renderSelectedBooks();
}
