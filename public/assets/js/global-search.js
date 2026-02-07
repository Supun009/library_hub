// Global Search Functionality
document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.querySelector(".header-search input");
  const searchContainer = document.querySelector(".header-search");

  if (!searchInput || !searchContainer) return;

  let searchTimeout;
  let resultsDropdown;

  // Create results dropdown
  function createResultsDropdown() {
    if (resultsDropdown) return resultsDropdown;

    resultsDropdown = document.createElement("div");
    resultsDropdown.className = "search-results-dropdown";
    resultsDropdown.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            margin-top: 8px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            min-width: 350px;
        `;

    searchContainer.style.position = "relative";
    searchContainer.appendChild(resultsDropdown);

    return resultsDropdown;
  }

  // Show results
  function showResults(results) {
    const dropdown = createResultsDropdown();
    dropdown.innerHTML = "";

    if (results.length === 0) {
      dropdown.innerHTML = `
                <div style="padding: 16px; text-align: center; color: #6b7280;">
                    <i data-lucide="search" style="width: 24px; height: 24px; margin: 0 auto 8px; opacity: 0.5; position: relative; top: auto; left: auto; transform: none; display: block;"></i>
                    <p>No books found</p>
                </div>
            `;
      dropdown.style.display = "block";
      lucide.createIcons();
      return;
    }

    results.forEach((result, index) => {
      const item = document.createElement("a");
      item.href = result.url;
      item.className = "search-result-item";
      item.style.cssText = `
                display: flex;
                align-items: start;
                gap: 12px;
                padding: 12px 16px;
                text-decoration: none;
                color: inherit;
                border-bottom: 1px solid #f3f4f6;
                transition: background-color 0.15s;
            `;

      item.innerHTML = `
                <div style="flex-shrink: 0; margin-top: 2px; position: relative;">
                    <i data-lucide="book-open" style="width: 20px; height: 20px; color: #6366f1; position: relative; top: auto; left: auto; transform: none; display: inline-block;"></i>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 600; color: #111827; margin-bottom: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        ${escapeHtml(result.title)}
                    </div>
                    <div style="font-size: 13px; color: #6b7280; margin-bottom: 4px;">
                        ${escapeHtml(result.subtitle)}
                    </div>
                    <div style="font-size: 12px; color: #9ca3af; font-family: monospace;">
                        ${escapeHtml(result.meta)}
                    </div>
                </div>
                <div style="flex-shrink: 0;">
                    <span style="display: inline-block; padding: 4px 8px; border-radius: 9999px; font-size: 11px; font-weight: 500; ${
                      result.status === "Available"
                        ? "background-color: #dcfce7; color: #166534;"
                        : "background-color: #fee2e2; color: #991b1b;"
                    }">
                        ${escapeHtml(result.status)}
                    </span>
                </div>
            `;

      item.addEventListener("mouseenter", function () {
        this.style.backgroundColor = "#f9fafb";
      });

      item.addEventListener("mouseleave", function () {
        this.style.backgroundColor = "transparent";
      });

      if (index === results.length - 1) {
        item.style.borderBottom = "none";
      }

      dropdown.appendChild(item);
    });

    dropdown.style.display = "block";
    lucide.createIcons();
  }

  // Hide results
  function hideResults() {
    if (resultsDropdown) {
      resultsDropdown.style.display = "none";
    }
  }

  // Perform search
  function performSearch(query) {
    if (query.length < 2) {
      hideResults();
      return;
    }

    fetch(window.url(`api/search?q=${encodeURIComponent(query)}`))
      .then((response) => response.json())
      .then((results) => {
        showResults(results);
      })
      .catch((error) => {
        console.error("Search error:", error);
        hideResults();
      });
  }

  // Escape HTML to prevent XSS
  function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  // Event listeners
  searchInput.addEventListener("input", function (e) {
    clearTimeout(searchTimeout);
    const query = e.target.value.trim();

    searchTimeout = setTimeout(() => {
      performSearch(query);
    }, 300);
  });

  searchInput.addEventListener("focus", function () {
    if (this.value.trim().length >= 2) {
      performSearch(this.value.trim());
    }
  });

  // Close dropdown when clicking outside
  document.addEventListener("click", function (e) {
    if (!searchContainer.contains(e.target)) {
      hideResults();
    }
  });

  // Handle Enter key
  searchInput.addEventListener("keydown", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();
      const query = this.value.trim();
      if (query) {
        window.location.href = window.url(
          `admin/books?search=${encodeURIComponent(query)}`,
        );
      }
    }
  });
});
