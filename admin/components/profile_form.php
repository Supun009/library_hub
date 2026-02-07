<!-- components/profile_form.php -->
<div class="mb-6 flex items-center gap-3">
    <i data-lucide="user" class="h-6 w-6 text-indigo-600"></i>
    <h2 class="text-xl font-semibold text-gray-900">Profile Information</h2>
</div>

<form method="POST" class="space-y-6">
    <input type="hidden" name="action" value="update_profile">
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Full Name</label>
            <input
                type="text"
                name="full_name"
                value="<?php echo htmlspecialchars($userProfile['full_name'] ?? ''); ?>"
                required
                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            >
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Email Address</label>
            <input
                type="email"
                name="email"
                value="<?php echo htmlspecialchars($userProfile['email'] ?? ''); ?>"
                required
                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            >
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Phone Number</label>
            <input
                type="text"
                name="phone"
                value="<?php echo htmlspecialchars($userProfile['phone_number'] ?? ''); ?>"
                placeholder="+1 234-567-8900"
                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            >
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Employee ID (Username)</label>
            <input
                type="text"
                value="<?php echo htmlspecialchars($userProfile['username']); ?>"
                disabled
                class="block w-full cursor-not-allowed rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500"
            >
        </div>
        <div class="md:col-span-2">
             <label class="mb-1 block text-sm font-medium text-gray-700">Role</label>
             <input
                type="text"
                value="<?php echo ucfirst($userProfile['role_name']); ?>"
                disabled
                class="block w-full cursor-not-allowed rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500"
             >
        </div>
    </div>
    <div class="flex justify-end pt-4">
        <button
            type="submit"
            class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
        >
            <i data-lucide="save" class="h-4 w-4"></i>
            Save Changes
        </button>
    </div>
</form>
