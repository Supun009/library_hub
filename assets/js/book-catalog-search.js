// Real-time Book Catalog Search
document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.querySelector('input[name="search"]');
  const categorySelect = document.querySelector('select[name="category"]');
  const booksGrid = document.querySelector(".grid");
  const totalCountElement = document.querySelector(
    ".text-gray-500.text-sm.mt-1",
  );
  const paginationContainer =
    document.querySelector(".grid").parentElement.nextElementSibling;

  if (!searchInput || !booksGrid) return;

  let searchTimeout;
  let currentPage = 1;

  // Function to perform search
  function performSearch(page = 1) {
    const searchQuery = searchInput.value.trim();
    const category = categorySelect ? categorySelect.value : "All";

    // Build URL with parameters
    const params = new URLSearchParams({
      search: searchQuery,
      category: category,
      page: page,
    });

    // Show loading state
    booksGrid.style.opacity = "0.5";
    booksGrid.style.pointerEvents = "none";

    fetch(`/lib_system/library_system/api/search_books.php?${params}`)
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
                <div class="flex-grow">
                    <h3 class="mb-1 truncate text-lg font-bold text-gray-900" title="${escapeHtml(book.title)}">
                        ${escapeHtml(book.title)}
                    </h3>
                    <p class="mb-2 text-sm italic text-gray-600">
                        ${escapeHtml(book.authors || "No author")}
                    </p>
                    <p class="text-xs font-mono text-gray-400">
                        ISBN: ${escapeHtml(book.isbn || "N/A")}
                    </p>
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
                
                <button
                    class="inline-flex w-full items-center justify-center rounded-md px-4 py-2 text-sm font-medium shadow-sm transition-colors ${
                      book.status_name === "Available"
                        ? "bg-indigo-600 text-white hover:bg-indigo-700"
                        : "cursor-not-allowed bg-gray-100 text-gray-400"
                    }"
                    ${book.status_name === "Available" ? "" : "disabled"}
                >
                    ${book.status_name === "Available" ? book.status_name : "Not Available"}
                </button>
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
