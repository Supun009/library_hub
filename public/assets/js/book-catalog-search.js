// Real-time Book Catalog Search
document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.querySelector('input[name="search"]');
  const categorySelect = document.querySelector('select[name="category"]');
  const categoryHidden = document.getElementById("category_hidden");
  const categorySearchInput = document.getElementById("category_search");
  const categoryDropdown = document.getElementById("category_dropdown");

  const booksGrid = document.querySelector(".grid");
  const totalCountElement = document.querySelector(
    ".text-gray-500.text-sm.mt-1",
  );
  const paginationContainer =
    document.querySelector(".grid").parentElement.nextElementSibling;

  // Initialize Custom Dropdown Logic
  if (
    categoryHidden &&
    categorySearchInput &&
    categoryDropdown &&
    window.categoriesData
  ) {
    function filterAndShowDropdown() {
      const searchTerm = categorySearchInput.value.toLowerCase();
      const filtered = window.categoriesData.filter((c) =>
        c.category_name.toLowerCase().includes(searchTerm),
      );

      let html = "";

      // Always show "All Categories" if it matches search or search is empty
      if ("all categories".includes(searchTerm) || searchTerm === "") {
        html += `
                <div class="px-3 py-2 hover:bg-indigo-50 cursor-pointer text-sm category-option font-semibold text-indigo-600" 
                     data-name="All">
                    All Categories
                </div>
            `;
      }

      if (filtered.length === 0 && html === "") {
        html +=
          '<div class="px-3 py-2 text-sm text-gray-500">No categories found</div>';
      } else {
        html += filtered
          .map(
            (c) => `
                <div class="px-3 py-2 hover:bg-indigo-50 cursor-pointer text-sm category-option" 
                     data-name="${c.category_name}">
                    ${escapeHtml(c.category_name)}
                </div>
            `,
          )
          .join("");
      }

      categoryDropdown.innerHTML = html;
      categoryDropdown.classList.remove("hidden");

      // Add click handlers
      categoryDropdown
        .querySelectorAll(".category-option")
        .forEach((option) => {
          option.addEventListener("click", function () {
            const name = this.dataset.name;
            categoryHidden.value = name;
            categorySearchInput.value =
              name === "All" ? "All Categories" : name; // Nice display text
            categoryDropdown.classList.add("hidden");
            performSearch(1); // Trigger search
          });
        });
    }

    // Input handler
    categorySearchInput.addEventListener("input", function () {
      if (this.value === "") categoryHidden.value = "All"; // Default to All if cleared
      filterAndShowDropdown();
    });

    // Focus handler
    categorySearchInput.addEventListener("focus", function () {
      filterAndShowDropdown();
    });

    // Click outside handler
    document.addEventListener("click", function (e) {
      if (
        !categorySearchInput.contains(e.target) &&
        !categoryDropdown.contains(e.target)
      ) {
        categoryDropdown.classList.add("hidden");
      }
    });
  }

  if (!searchInput || !booksGrid) return;

  let searchTimeout;
  let currentPage = 1;

  // Function to perform search
  function performSearch(page = 1) {
    const searchQuery = searchInput.value.trim();
    let category = "All";

    if (categoryHidden) {
      category = categoryHidden.value || "All";
    } else if (categorySelect) {
      category = categorySelect.value;
    }

    // Build URL with parameters
    const params = new URLSearchParams({
      search: searchQuery,
      category: category,
      page: page,
    });

    // Show loading state
    booksGrid.style.opacity = "0.5";
    booksGrid.style.pointerEvents = "none";

    fetch(window.url(`api/search-books?${params.toString()}`))
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          updateBooksGrid(data.books);
          updateTotalCount(data.totalItems);
          updatePagination(
            data.currentPage,
            data.totalPages,
            searchQuery,
            category,
          );
          currentPage = data.currentPage;

          // Update URL without reload
          const newUrl = new URL(window.location);
          newUrl.searchParams.set("search", searchQuery);
          newUrl.searchParams.set("category", category);
          newUrl.searchParams.set("page", page);
          window.history.pushState({}, "", newUrl);
        }

        // Remove loading state
        booksGrid.style.opacity = "1";
        booksGrid.style.pointerEvents = "auto";
      })
      .catch((error) => {
        console.error("Search error:", error);
        booksGrid.style.opacity = "1";
        booksGrid.style.pointerEvents = "auto";
      });
  }

  // Update books grid
  function updateBooksGrid(books) {
    if (books.length === 0) {
      booksGrid.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <i data-lucide="book-x" class="mx-auto h-12 w-12 text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No books found</h3>
                    <p class="text-sm text-gray-500">Try adjusting your search or filter criteria</p>
                </div>
            `;
      lucide.createIcons();
      return;
    }

    booksGrid.innerHTML = books
      .map(
        (book) => `
            <div class="flex h-full flex-col rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
                <div class="flex-grow min-w-0">
                    <h3 class="mb-1 truncate text-lg font-bold text-gray-900" title="${escapeHtml(book.title)}">
                        ${escapeHtml(book.title)}
                    </h3>
                    <p class="mb-2 text-sm italic text-gray-600 truncate" title="${escapeHtml(book.authors || "No author")}">
                        ${escapeHtml(book.authors || "No author")}
                    </p>
                    <div class="flex justify-between items-center text-xs text-gray-500 mb-2">
                        <span class="font-mono truncate mr-2" title="ISBN: ${escapeHtml(book.isbn || "N/A")}">
                            ISBN: ${escapeHtml(book.isbn || "N/A")}
                        </span>
                        <span class="font-semibold whitespace-nowrap ${
                          book.available_copies > 0
                            ? "text-green-600"
                            : "text-red-600"
                        }">
                           Stock: ${book.available_copies}/${book.total_copies}
                        </span>
                    </div>
                </div>
                
                <div class="mt-6 mb-4 flex items-center justify-between">
                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                        ${escapeHtml(book.category_name || "Uncategorized")}
                    </span>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium ${
                      book.status_name === "Available"
                        ? "bg-green-100 text-green-800"
                        : "bg-red-100 text-red-800"
                    }">
                        ${escapeHtml(book.status_name)}
                    </span>
                </div>
                
                <div class="mt-4 flex gap-2">
                    <a href="${window.url("admin/books/edit?id=" + book.book_id)}" 
                       class="flex-1 inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        <i data-lucide="edit" class="mr-2 h-4 w-4"></i>
                        Edit
                    </a>
                    <button 
                        onclick="confirmDeleteBook(${book.book_id})"
                        class="flex-1 inline-flex items-center justify-center rounded-md border border-transparent bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        ${book.available_copies < book.total_copies ? 'disabled title="Cannot delete book with active loans"' : ""}
                    >
                        <i data-lucide="trash-2" class="mr-2 h-4 w-4"></i>
                        Delete
                    </button>
                </div>
            </div>
        `,
      )
      .join("");

    lucide.createIcons();
  }

  // Update total count
  function updateTotalCount(total) {
    if (totalCountElement) {
      totalCountElement.textContent = `Total: ${total} book(s)`;
    }
  }

  // Update pagination
  function updatePagination(currentPage, totalPages, searchQuery, category) {
    if (!paginationContainer) return;

    if (totalPages <= 1) {
      paginationContainer.innerHTML = "";
      return;
    }

    let paginationHTML =
      '<div class="mt-6 flex items-center justify-center gap-2">';

    // Previous button
    if (currentPage > 1) {
      paginationHTML += `
                <button onclick="window.bookCatalogSearch(${currentPage - 1})" 
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <i data-lucide="chevron-left" class="h-4 w-4"></i>
                    Previous
                </button>
            `;
    }

    // Page numbers
    const maxVisible = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let endPage = Math.min(totalPages, startPage + maxVisible - 1);

    if (endPage - startPage < maxVisible - 1) {
      startPage = Math.max(1, endPage - maxVisible + 1);
    }

    if (startPage > 1) {
      paginationHTML += `
                <button onclick="window.bookCatalogSearch(1)" 
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    1
                </button>
            `;
      if (startPage > 2) {
        paginationHTML += '<span class="px-2 text-gray-500">...</span>';
      }
    }

    for (let i = startPage; i <= endPage; i++) {
      paginationHTML += `
                <button onclick="window.bookCatalogSearch(${i})" 
                    class="inline-flex items-center rounded-md border px-3 py-2 text-sm font-medium ${
                      i === currentPage
                        ? "border-indigo-500 bg-indigo-50 text-indigo-600"
                        : "border-gray-300 bg-white text-gray-700 hover:bg-gray-50"
                    }">
                    ${i}
                </button>
            `;
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        paginationHTML += '<span class="px-2 text-gray-500">...</span>';
      }
      paginationHTML += `
                <button onclick="window.bookCatalogSearch(${totalPages})" 
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    ${totalPages}
                </button>
            `;
    }

    // Next button
    if (currentPage < totalPages) {
      paginationHTML += `
                <button onclick="window.bookCatalogSearch(${currentPage + 1})" 
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Next
                    <i data-lucide="chevron-right" class="h-4 w-4"></i>
                </button>
            `;
    }

    paginationHTML += "</div>";
    paginationContainer.innerHTML = paginationHTML;
    lucide.createIcons();
  }

  // Escape HTML
  function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  // Global function for pagination clicks
  window.bookCatalogSearch = function (page) {
    performSearch(page);
  };

  // Event listeners
  searchInput.addEventListener("input", function () {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      performSearch(1); // Reset to page 1 on new search
    }, 500); // 500ms debounce for typing
  });

  if (categorySelect) {
    categorySelect.addEventListener("change", function () {
      performSearch(1); // Reset to page 1 on category change
    });
  }
});
