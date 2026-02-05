// add_book.js - JavaScript for Add Book Form

// Category Search Logic
document.addEventListener("DOMContentLoaded", () => {
  const categorySearch = document.getElementById("category_search");
  const categoryDropdown = document.getElementById("category_dropdown");
  const categoryIdHidden = document.getElementById("category_id_hidden");
  const categorySearchContainer = document.getElementById(
    "category_search_container",
  );
  const newCategoryInputContainer = document.getElementById(
    "new_category_input_container",
  );
  const newCategoryNameInput = document.getElementById("new_category_name");
  const cancelNewCategoryBtn = document.getElementById("cancel_new_category");

  // Set initial value if exists
  const initialId = categoryIdHidden.value;
  if (initialId === "new") {
    // Show new category input
    categorySearchContainer.classList.add("hidden");
    newCategoryInputContainer.classList.remove("hidden");
    newCategoryInputContainer.classList.add("flex");
    newCategoryNameInput.required = true;
    newCategoryNameInput.value =
      newCategoryInputContainer.dataset.initialValue || "";
  } else if (initialId) {
    const found = window.categoriesData.find((c) => c.category_id == initialId);
    if (found) {
      categorySearch.value = found.category_name;
    }
  }

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
                    data-id="${c.category_id}" data-name="${escapeHtml(c.category_name)}">
                    ${escapeHtml(c.category_name)}
                </div>
            `,
        )
        .join("");
    }

    // Add "Add New Category" option
    html += `
            <div class="border-t border-gray-100 px-3 py-2 hover:bg-blue-50 cursor-pointer text-sm font-medium text-blue-600 new-category-option">
                + Add New Category
            </div>
        `;

    categoryDropdown.innerHTML = html;
    categoryDropdown.classList.remove("hidden");

    // Existing Category Click
    categoryDropdown.querySelectorAll(".category-option").forEach((option) => {
      option.addEventListener("click", function () {
        categorySearch.value = this.dataset.name;
        categoryIdHidden.value = this.dataset.id;
        categoryDropdown.classList.add("hidden");
      });
    });

    // New Category Click
    const newOption = categoryDropdown.querySelector(".new-category-option");
    if (newOption) {
      newOption.addEventListener("click", function () {
        categoryIdHidden.value = "new";
        categorySearchContainer.classList.add("hidden");
        newCategoryInputContainer.classList.remove("hidden");
        newCategoryInputContainer.classList.add("flex");
        newCategoryNameInput.required = true;
        newCategoryNameInput.focus();
        categoryDropdown.classList.add("hidden");
      });
    }
  });

  // Cancel New Category
  cancelNewCategoryBtn.addEventListener("click", function () {
    categoryIdHidden.value = "";
    categorySearch.value = "";
    newCategoryNameInput.value = "";
    newCategoryNameInput.required = false;
    newCategoryInputContainer.classList.add("hidden");
    newCategoryInputContainer.classList.remove("flex");
    categorySearchContainer.classList.remove("hidden");
    categorySearch.focus();
  });

  categorySearch.addEventListener("focus", function () {
    if (this.value === "") this.dispatchEvent(new Event("input"));
  });

  document.addEventListener("click", function (e) {
    if (
      !categorySearch.contains(e.target) &&
      !categoryDropdown.contains(e.target)
    ) {
      categoryDropdown.classList.add("hidden");
    }
  });
});

// Author Search Logic
function createAuthorRow(index) {
  const row = document.createElement("div");
  row.className = "flex items-start gap-2 author-row relative mb-3";
  row.innerHTML = `
        <div class="flex-grow">
            <!-- Searchable Author Input -->
            <div class="relative author-search-container">
                <input type="hidden" name="authors[${index}][id]" class="author-id-hidden" required>
                <input
                    type="text"
                    class="author-search block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    placeholder="Search author..."
                    autocomplete="off"
                >
                <!-- Dropdown -->
                <div class="author-dropdown absolute z-20 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-auto"></div>
            </div>

            <!-- New Author Name Input (Hidden by default) -->
            <div class="new-author-input hidden mt-2">
                <div class="flex items-center gap-2">
                    <input
                        type="text"
                        name="authors[${index}][new_name]"
                        placeholder="Enter New Author Name"
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    >
                    <button type="button" class="cancel-new-author text-sm text-red-600 hover:text-red-800 font-medium">Cancel</button>
                </div>
            </div>
        </div>
        ${
          index > 0
            ? `
        <button
            type="button"
            onclick="this.closest('.author-row').remove();"
            title="Remove Author"
            class="inline-flex h-10 min-w-[42px] items-center justify-center rounded-md border border-red-200 bg-white px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
        >
            <i data-lucide="trash-2" class="h-4 w-4"></i>
        </button>`
            : ""
        }
    `;

  // Attach Search Logic
  const searchInput = row.querySelector(".author-search");
  const dropdown = row.querySelector(".author-dropdown");
  const hiddenId = row.querySelector(".author-id-hidden");
  const newAuthorContainer = row.querySelector(".new-author-input");
  const newAuthorInput = newAuthorContainer.querySelector("input");
  const cancelNewButton = row.querySelector(".cancel-new-author");
  const searchContainer = row.querySelector(".author-search-container");

  searchInput.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase();
    if (this.value === "") hiddenId.value = "";

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
                    data-id="${a.author_id}" data-name="${escapeHtml(a.name)}">
                    ${escapeHtml(a.name)}
                </div>
            `,
        )
        .join("");
    }

    // Always show "Add New Author" option
    html += `
            <div class="border-t border-gray-100 px-3 py-2 hover:bg-blue-50 cursor-pointer text-sm font-medium text-blue-600 new-author-option">
                + Add New Author
            </div>
        `;

    dropdown.innerHTML = html;
    dropdown.classList.remove("hidden");

    // Existing Author Click
    dropdown.querySelectorAll(".author-option").forEach((option) => {
      option.addEventListener("click", function () {
        searchInput.value = this.dataset.name;
        hiddenId.value = this.dataset.id;
        dropdown.classList.add("hidden");
      });
    });

    // New Author Click
    dropdown
      .querySelector(".new-author-option")
      .addEventListener("click", function () {
        hiddenId.value = "new";
        searchContainer.classList.add("hidden");
        newAuthorContainer.classList.remove("hidden");
        newAuthorInput.required = true;
        newAuthorInput.focus();
        dropdown.classList.add("hidden");
      });
  });

  searchInput.addEventListener("focus", function () {
    if (this.value === "") this.dispatchEvent(new Event("input"));
  });

  // Cancel New Author logic
  cancelNewButton.addEventListener("click", function () {
    hiddenId.value = "";
    searchInput.value = "";
    newAuthorInput.value = "";
    newAuthorInput.required = false;
    newAuthorContainer.classList.add("hidden");
    searchContainer.classList.remove("hidden");
    searchInput.focus();
  });

  // Close dropdown on outside click
  document.addEventListener("click", function (e) {
    if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.classList.add("hidden");
    }
  });

  return row;
}

function addAuthorRow() {
  const container = document.getElementById("authors-container");
  const index = container.children.length;
  container.appendChild(createAuthorRow(index + Date.now()));
  if (window.lucide) lucide.createIcons();
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

// Initialize with one row
document.addEventListener("DOMContentLoaded", () => {
  addAuthorRow();
});
