<!-- components/settings_form.php -->
<div class="mb-6 flex items-center gap-3">
    <i data-lucide="settings" class="h-6 w-6 text-indigo-600"></i>
    <h2 class="text-xl font-semibold text-gray-900">System Settings</h2>
</div>

<form method="POST" class="space-y-6">
    <input type="hidden" name="action" value="update_settings">
    
    <div>
        <h3 class="text-base text-gray-900 font-medium mb-3">Library Policies</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Fine Per Day ($)</label>
                <input
                    type="number"
                    step="0.01"
                    name="fine_per_day"
                    value="<?php echo htmlspecialchars($settings['fine_per_day'] ?? '0.50'); ?>"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Loan Period (Days)</label>
                <input
                    type="number"
                    name="loan_period_days"
                    value="<?php echo htmlspecialchars($settings['loan_period_days'] ?? '14'); ?>"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>
        </div>
    </div>

    <div class="flex justify-end border-t border-gray-200 pt-4">
        <button
            type="submit"
            class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
        >
            <i data-lucide="save" class="h-4 w-4"></i>
            Save Settings
        </button>
    </div>
</form>
