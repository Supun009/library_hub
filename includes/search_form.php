<?php
// includes/search_form.php

// Expects $filters and $categories to be available in the scope
$actionUrl = $actionUrl ?? ''; 
?>
<div class="mb-6 rounded border border-gray-200 bg-white p-6 shadow-sm">
    <form method="GET" action="<?php echo htmlspecialchars($actionUrl); ?>">
        <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">
            <!-- Book Title -->
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Book Title</label>
                <input
                    type="text"
                    name="title"
                    value="<?php echo htmlspecialchars($filters['title']); ?>"
                    placeholder="Enter book title"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>

            <!-- Author Name -->
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Author Name</label>
                <input
                    type="text"
                    name="author"
                    value="<?php echo htmlspecialchars($filters['author']); ?>"
                    placeholder="Enter author name"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>

            <!-- Category -->
            <div class="relative">
                <label class="mb-2 block text-sm font-medium text-gray-700">Category</label>
                <input type="hidden" name="category" id="category_hidden" value="<?php echo htmlspecialchars($filters['category'] === 'All' ? '' : $filters['category']); ?>">
                <div class="relative">
                     <input
                        type="text"
                        id="category_search"
                        placeholder="All Categories"
                        autocomplete="off"
                        value="<?php echo htmlspecialchars($filters['category'] === 'All' ? '' : $filters['category']); ?>"
                        class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    >
                    <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                        <i data-lucide="chevron-down" class="h-4 w-4 text-gray-400"></i>
                    </div>
                </div>
                <div id="category_dropdown" class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-auto">
                    <!-- Categories will be populated here -->
                </div>
            </div>

            <!-- ISBN -->
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">ISBN</label>
                <input
                    type="text"
                    name="isbn"
                    value="<?php echo htmlspecialchars($filters['isbn']); ?>"
                    placeholder="Enter ISBN"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>

            <!-- Publication Year From -->
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Publication Year (From)</label>
                <input
                    type="number"
                    name="year_from"
                    value="<?php echo htmlspecialchars($filters['year_from']); ?>"
                    placeholder="e.g., 2000"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>

            <!-- Publication Year To -->
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Publication Year (To)</label>
                <input
                    type="number"
                    name="year_to"
                    value="<?php echo htmlspecialchars($filters['year_to']); ?>"
                    placeholder="e.g., 2024"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>

            <!-- Availability Status -->
            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-medium text-gray-700">Availability Status</label>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input
                            type="radio"
                            name="status"
                            value="all"
                            <?php echo $filters['status'] === 'all' ? 'checked' : ''; ?>
                        >
                        <span>All Books</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input
                            type="radio"
                            name="status"
                            value="available"
                            <?php echo $filters['status'] === 'available' ? 'checked' : ''; ?>
                        >
                        <span>Available Only</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input
                            type="radio"
                            name="status"
                            value="issued"
                            <?php echo $filters['status'] === 'issued' ? 'checked' : ''; ?>
                        >
                        <span>Issued Only</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button
                type="submit"
                class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
            >
                <i data-lucide="search" class="h-4 w-4"></i>
                Search Books
            </button>
            <a
                href="<?php echo htmlspecialchars($actionUrl); ?>"
                class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
                Reset Filters
            </a>
        </div>
    </form>
</div>

<!-- Category Search Script used by the form -->
<script>
    // Transform simple array to object array to match JS expectation
    window.categoriesData = <?php echo json_encode(array_map(function($c) { return ['category_name' => $c]; }, $categories)); ?>;
</script>
<script src="<?php echo asset('js/book-catalog-search.js'); ?>?v=<?php echo time(); ?>"></script>
