/**
 * Issue Book - Search Functionality
 * Handles member and book search with autocomplete dropdowns
 */

let bookRowIndex = 0;

/**
 * Initialize member search functionality
 */
function initMemberSearch(members) {
  const memberSearch = document.getElementById("member_search");
  const memberDropdown = document.getElementById("member_dropdown");
  const memberIdHidden = document.getElementById("member_id_hidden");
  const selectedMemberInfo = document.getElementById("selected_member_info");

  if (!memberSearch) return;

  memberSearch.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase();

    if (searchTerm.length === 0) {
      memberDropdown.classList.add("hidden");
      memberIdHidden.value = "";
      selectedMemberInfo.textContent = "";
      return;
    }

    const filtered = members.filter(
      (m) =>
        m.full_name.toLowerCase().includes(searchTerm) ||
        m.uid.toLowerCase().includes(searchTerm),
    );

    if (filtered.length === 0) {
      memberDropdown.innerHTML =
        '<div class="px-3 py-2 text-sm text-gray-500">No members found</div>';
      memberDropdown.classList.remove("hidden");
      return;
    }

    memberDropdown.innerHTML = filtered
      .map(
        (m) => `
            <div class="px-3 py-2 hover:bg-indigo-50 cursor-pointer text-sm member-option" data-id="${m.member_id}" data-name="${escapeHtml(m.full_name)}" data-uid="${escapeHtml(m.uid)}">
                <div class="font-medium text-gray-900">${escapeHtml(m.full_name)}</div>
                <div class="text-xs text-gray-500">Username: ${escapeHtml(m.uid)} • Books Issued: ${m.issued_books_count}</div>
            </div>
        `,
      )
      .join("");

    memberDropdown.classList.remove("hidden");

    // Add click handlers
    document.querySelectorAll(".member-option").forEach((option) => {
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
 * Create a book search row
 */
function createBookRow(index, availableBooks) {
  const row = document.createElement("div");
  row.className = "flex items-start gap-2 book-row";
  row.innerHTML = `
        <div class="flex-1 relative">
            <input
                type="text"
                class="book-search block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                placeholder="Search book by title or ISBN..."
                autocomplete="off"
                required
            >
            <input type="hidden" name="book_ids[]" class="book-id-hidden" required>
            <div class="book-dropdown absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-auto"></div>
        </div>
        ${
          index > 0
            ? `
        <button
            type="button"
            onclick="this.closest('.book-row').remove(); updateBookCount();"
            title="Remove Book"
            class="inline-flex h-10 min-w-[42px] items-center justify-center rounded-md border border-red-200 bg-white px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
        >
            <i data-lucide="trash-2" class="h-4 w-4"></i>
        </button>`
            : ""
        }
    `;

  // Add search functionality to this book row
  const bookSearch = row.querySelector(".book-search");
  const bookDropdown = row.querySelector(".book-dropdown");
  const bookIdHidden = row.querySelector(".book-id-hidden");

  bookSearch.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase();

    if (searchTerm.length === 0) {
      bookDropdown.classList.add("hidden");
      bookIdHidden.value = "";
      return;
    }

    const filtered = availableBooks.filter(
      (b) =>
        b.title.toLowerCase().includes(searchTerm) ||
        b.isbn.toLowerCase().includes(searchTerm),
    );

    if (filtered.length === 0) {
      bookDropdown.innerHTML =
        '<div class="px-3 py-2 text-sm text-gray-500">No books found</div>';
      bookDropdown.classList.remove("hidden");
      return;
    }

    bookDropdown.innerHTML = filtered
      .slice(0, 10)
      .map(
        (b) => `
            <div class="px-3 py-2 hover:bg-indigo-50 cursor-pointer text-sm book-option" data-id="${b.book_id}" data-title="${escapeHtml(b.title)}" data-isbn="${escapeHtml(b.isbn)}">
                <div class="font-medium text-gray-900">${escapeHtml(b.title)}</div>
                <div class="text-xs text-gray-500">ISBN: ${escapeHtml(b.isbn)}</div>
            </div>
        `,
      )
      .join("");

    bookDropdown.classList.remove("hidden");

    // Add click handlers
    bookDropdown.querySelectorAll(".book-option").forEach((option) => {
      option.addEventListener("click", function () {
        const id = this.dataset.id;
        const title = this.dataset.title;

        bookSearch.value = title;
        bookIdHidden.value = id;
        bookDropdown.classList.add("hidden");
      });
    });
  });

  // Hide dropdown when clicking outside
  document.addEventListener("click", function (e) {
    if (!bookSearch.contains(e.target) && !bookDropdown.contains(e.target)) {
      bookDropdown.classList.add("hidden");
    }
  });

  return row;
}

/**
 * Add a new book row
 */
function addBookRow(availableBooks) {
  const container = document.getElementById("books-container");
  container.appendChild(createBookRow(bookRowIndex++, availableBooks));
  if (typeof lucide !== "undefined") {
    lucide.createIcons();
  }
  updateBookCount();
}

/**
 * Update book count and ensure at least one row exists
 */
function updateBookCount() {
  const count = document.querySelectorAll(".book-row").length;
  if (count === 0) {
    addBookRow(window.availableBooks); // Always have at least one row
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
function initIssueBookSearch(members, availableBooks) {
  // Store books globally for addBookRow function
  window.availableBooks = availableBooks;

  // Initialize member search
  initMemberSearch(members);

  // Initialize with one book row
  addBookRow(availableBooks);
}
