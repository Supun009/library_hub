<?php
/**
 * Reusable Pagination Component
 * 
 * @param int $currentPage Current page number (1-indexed)
 * @param int $totalItems Total number of items
 * @param int $itemsPerPage Number of items per page
 * @param array $queryParams Additional query parameters to preserve (e.g., search, status)
 * @return void Outputs the pagination HTML
 */
function renderPagination($currentPage, $totalItems, $itemsPerPage, $queryParams = []) {
    // Ensure all numeric parameters are integers to prevent type errors
    $currentPage = (int) $currentPage;
    $totalItems = (int) $totalItems;
    $itemsPerPage = (int) $itemsPerPage;
    
    // Validate inputs
    if ($itemsPerPage <= 0) {
        $itemsPerPage = 10; // Default fallback
    }
    if ($currentPage < 1) {
        $currentPage = 1;
    }
    
    $totalPages = ceil($totalItems / $itemsPerPage);
    
    // Don't show pagination if only one page or no items
    if ($totalPages <= 1) {
        return;
    }
    
    // Build query string from additional parameters
    $queryString = '';
    foreach ($queryParams as $key => $value) {
        if (!empty($value) && $key !== 'page') {
            $queryString .= '&' . urlencode($key) . '=' . urlencode($value);
        }
    }
    
    // Calculate page range to show
    $range = 2; // Show 2 pages on each side of current page
    $startPage = max(1, $currentPage - $range);
    $endPage = min($totalPages, $currentPage + $range);
    
    
    ?>
    <div class="mt-6 flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6 rounded-md">
        <div class="flex flex-1 justify-between sm:hidden">
            <!-- Mobile pagination -->
            <?php if ($currentPage > 1): ?>
                <a
                    href="?page=<?php echo $currentPage - 1; ?><?php echo $queryString; ?>"
                    class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Previous
                </a>
            <?php else: ?>
                <span class="relative inline-flex items-center rounded-md border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-400 cursor-not-allowed">
                    Previous
                </span>
            <?php endif; ?>
            
            <?php if ($currentPage < $totalPages): ?>
                <a
                    href="?page=<?php echo $currentPage + 1; ?><?php echo $queryString; ?>"
                    class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Next
                </a>
            <?php else: ?>
                <span class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-400 cursor-not-allowed">
                    Next
                </span>
            <?php endif; ?>
        </div>
        
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Showing
                    <span class="font-medium"><?php echo min(($currentPage - 1) * $itemsPerPage + 1, $totalItems); ?></span>
                    to
                    <span class="font-medium"><?php echo min($currentPage * $itemsPerPage, $totalItems); ?></span>
                    of
                    <span class="font-medium"><?php echo $totalItems; ?></span>
                    results
                </p>
            </div>
            <div>
                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                    <!-- Previous Button -->
                    <?php if ($currentPage > 1): ?>
                        <a
                            href="?page=<?php echo $currentPage - 1; ?><?php echo $queryString; ?>"
                            class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20"
                        >
                            <span class="sr-only">Previous</span>
                            <i data-lucide="chevron-left" class="h-5 w-5"></i>
                        </a>
                    <?php else: ?>
                        <span class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-300 ring-1 ring-inset ring-gray-300 bg-gray-100 cursor-not-allowed">
                            <i data-lucide="chevron-left" class="h-5 w-5"></i>
                        </span>
                    <?php endif; ?>
                    
                    <!-- First page -->
                    <?php if ($startPage > 1): ?>
                        <a
                            href="?page=1<?php echo $queryString; ?>"
                            class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20"
                        >
                            1
                        </a>
                        <?php if ($startPage > 2): ?>
                            <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300">
                                ...
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Page numbers -->
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <?php if ($i == $currentPage): ?>
                            <span
                                aria-current="page"
                                class="relative z-10 inline-flex items-center bg-indigo-600 px-4 py-2 text-sm font-semibold text-white focus:z-20"
                            >
                                <?php echo $i; ?>
                            </span>
                        <?php else: ?>
                            <a
                                href="?page=<?php echo $i; ?><?php echo $queryString; ?>"
                                class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20"
                            >
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <!-- Last page -->
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300">
                                ...
                            </span>
                        <?php endif; ?>
                        <a
                            href="?page=<?php echo $totalPages; ?><?php echo $queryString; ?>"
                            class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20"
                        >
                            <?php echo $totalPages; ?>
                        </a>
                    <?php endif; ?>
                    
                    <!-- Next Button -->
                    <?php if ($currentPage < $totalPages): ?>
                        <a
                            href="?page=<?php echo $currentPage + 1; ?><?php echo $queryString; ?>"
                            class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20"
                        >
                            <span class="sr-only">Next</span>
                            <i data-lucide="chevron-right" class="h-5 w-5"></i>
                        </a>
                    <?php else: ?>
                        <span class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-300 ring-1 ring-inset ring-gray-300 bg-gray-100 cursor-not-allowed">
                            <i data-lucide="chevron-right" class="h-5 w-5"></i>
                        </span>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </div>
    <?php
}
?>
