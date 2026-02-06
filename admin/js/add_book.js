// add_book.js - JavaScript for Add Book Form

let authorRowCount = 0;

// Category Search Logic
document.addEventListener("DOMContentLoaded", () => {
  const categorySearch = document.getElementById("category_search");
  const categoryDropdown = document.getElementById("category_dropdown");
  const categoryIdHidden = document.getElementById("category_id_hidden");

  // Set initial value if exists
  const initialId = categoryIdHidden.value;
  if (initialId) {
    const found = window.categoriesData.find((c) => c.category_id == initialId);
    if (found) {
      categorySearch.value = found.category_name;
    }
  }

  // Category search input handler
  categorySearch.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase();
    if (this.value === "") categoryIdHidden.value = "";

    const filtered = window.categoriesData.filter((c) =>
      c.category_name.toLowerCase().includes(searchTerm),
    );

    let html = "";
    if (filtered.length === 0) {
      html +=
        '<div class="px-3 py-2 text-sm text-gray-500">No categories found</div>';
    } else {
      html += filtered
        .map(
          (c) => `
                <div class="px-3 py-2 hover:bg-indigo-50 cursor-pointer text-sm category-option" 
                     data-id="${c.category_id}" 
                     data-name="${c.category_name}">
                    ${c.category_name}
                </div>
            `,
        )
        .join("");
    }

    categoryDropdown.innerHTML = html;
    categoryDropdown.classList.remove("hidden");

    // Add click handlers
    document.querySelectorAll(".category-option").forEach((option) => {
      option.addEventListener("click", function () {
        categoryIdHidden.value = this.dataset.id;
        categorySearch.value = this.dataset.name;
        categoryDropdown.classList.add("hidden");
      });
    });
  });

  // Show dropdown on focus
  categorySearch.addEventListener("focus", function () {
    // Trigger input event to populate dropdown with all items
    const searchTerm = this.value.toLowerCase();
    const filtered = window.categoriesData.filter((c) =>
      c.category_name.toLowerCase().includes(searchTerm),
    );

    let html = "";
    if (filtered.length === 0) {
      html +=
        '<div class="px-3 py-2 text-sm text-gray-500">No categories found</div>';
    } else {
      html += filtered
        .map(
          (c) => `
                <div class="px-3 py-2 hover:bg-indigo-50 cursor-pointer text-sm category-option" 
                     data-id="${c.category_id}" 
                     data-name="${c.category_name}">
                    ${c.category_name}
                </div>
            `,
        )
        .join("");
    }

    categoryDropdown.innerHTML = html;
    categoryDropdown.classList.remove("hidden");

    // Add click handlers
    document.querySelectorAll(".category-option").forEach((option) => {
      option.addEventListener("click", function () {
        categoryIdHidden.value = this.dataset.id;
        categorySearch.value = this.dataset.name;
        categoryDropdown.classList.add("hidden");
      });
    });
  });

  // Hide dropdown when clicking outside
  document.addEventListener("click", function (e) {
    if (
      !categorySearch.contains(e.target) &&
      !categoryDropdown.contains(e.target)
    ) {
      categoryDropdown.classList.add("hidden");
    }
  });

  // Initialize first author row
  addAuthorRow();

  // Re-initialize Lucide icons
  if (typeof lucide !== "undefined") {
    lucide.createIcons();
  }
});

// Add Author Row Function with searchable dropdown
function addAuthorRow() {
  authorRowCount++;
  const container = document.getElementById("authors-container");

  const rowHtml = `
        <div class="author-row flex items-start gap-2" data-row-id="${authorRowCount}">
            <div class="flex-1 relative">
                <input type="hidden" name="author_ids[]" class="author-id-hidden" required>
                <input
                    type="text"
                    class="author-search block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    placeholder="Search author..."
                    autocomplete="off"
                >
                <div class="author-dropdown absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-auto">
                    <!-- Authors will be populated here -->
                </div>
            </div>
            <button
                type="button"
                onclick="removeAuthorRow(${authorRowCount})"
                class="mt-2 text-red-600 hover:text-red-800"
                title="Remove Author"
            >
                <i data-lucide="x-circle" class="h-5 w-5"></i>
            </button>
        </div>
    `;

  container.insertAdjacentHTML("beforeend", rowHtml);

  // Get the newly added row
  const newRow = container.querySelector(
    `.author-row[data-row-id="${authorRowCount}"]`,
  );
  const authorSearch = newRow.querySelector(".author-search");
  const authorDropdown = newRow.querySelector(".author-dropdown");
  const authorIdHidden = newRow.querySelector(".author-id-hidden");

  // Author search input handler
  authorSearch.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase();
    if (this.value === "") authorIdHidden.value = "";

    const filtered = window.authorsData.filter((a) =>
      a.name.toLowerCase().includes(searchTerm),
    );

    let html = "";
    if (filtered.length === 0) {
      html +=
        '<div class="px-3 py-2 text-sm text-gray-500">No authors found</div>';
    } else {
      html += filtered
        .map(
          (a) => `
                <div class="px-3 py-2 hover:bg-indigo-50 cursor-pointer text-sm author-option" 
                     data-id="${a.author_id}" 
                     data-name="${a.name}">
                    ${a.name}
                </div>
            `,
        )
        .join("");
    }

    authorDropdown.innerHTML = html;
    authorDropdown.classList.remove("hidden");

    // Add click handlers
    authorDropdown.querySelectorAll(".author-option").forEach((option) => {
      option.addEventListener("click", function () {
        authorIdHidden.value = this.dataset.id;
        authorSearch.value = this.dataset.name;
        authorDropdown.classList.add("hidden");
      });
    });
  });

  // Show dropdown on focus
  authorSearch.addEventListener("focus", function () {
    // Populate dropdown with all or filtered items
    const searchTerm = this.value.toLowerCase();
    const filtered = window.authorsData.filter((a) =>
      a.name.toLowerCase().includes(searchTerm),
    );

    let html = "";
    if (filtered.length === 0) {
      html +=
        '<div class="px-3 py-2 text-sm text-gray-500">No authors found</div>';
    } else {
      html += filtered
        .map(
          (a) => `
                <div class="px-3 py-2 hover:bg-indigo-50 cursor-pointer text-sm author-option" 
                     data-id="${a.author_id}" 
                     data-name="${a.name}">
                    ${a.name}
                </div>
            `,
        )
        .join("");
    }

    authorDropdown.innerHTML = html;
    authorDropdown.classList.remove("hidden");

    // Add click handlers
    authorDropdown.querySelectorAll(".author-option").forEach((option) => {
      option.addEventListener("click", function () {
        authorIdHidden.value = this.dataset.id;
        authorSearch.value = this.dataset.name;
        authorDropdown.classList.add("hidden");
      });
    });
  });

  // Hide dropdown when clicking outside
  document.addEventListener("click", function (e) {
    if (!newRow.contains(e.target)) {
      authorDropdown.classList.add("hidden");
    }
  });

  // Re-initialize Lucide icons for the new row
  if (typeof lucide !== "undefined") {
    lucide.createIcons();
  }
}

// Remove Author Row Function
function removeAuthorRow(rowId) {
  const row = document.querySelector(`.author-row[data-row-id="${rowId}"]`);
  if (row) {
    row.remove();
  }

  // Ensure at least one author row exists
  const container = document.getElementById("authors-container");
  if (container.children.length === 0) {
    addAuthorRow();
  }
}
