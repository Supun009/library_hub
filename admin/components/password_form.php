<!-- components/password_form.php -->
<div class="mb-6 flex items-center gap-3">
    <i data-lucide="lock" class="h-6 w-6 text-indigo-600"></i>
    <h2 class="text-xl font-semibold text-gray-900">Change Password</h2>
</div>

<form method="POST" class="max-w-md space-y-4">
    <input type="hidden" name="action" value="change_password">
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Current Password</label>
        <input
            type="password"
            name="current_password"
            required
            placeholder="Enter current password"
            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
        >
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">New Password</label>
        <input
            type="password"
            name="new_password"
            required
            placeholder="Enter new password"
            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
        >
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Confirm New Password</label>
        <input
            type="password"
            name="confirm_password"
            required
            placeholder="Confirm new password"
            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
        >
    </div>
    
    <div class="mt-4 rounded border border-blue-200 bg-blue-50 p-4">
        <h3 class="mb-2 text-sm font-semibold text-blue-900">Password Requirements</h3>
        <ul class="list-disc pl-5 text-sm text-blue-800">
            <li>At least 8 characters long</li>
            <li>Include uppercase & lowercase letters</li>
            <li>Include numbers</li>
        </ul>
    </div>

    <div class="flex justify-end pt-4">
        <button
            type="submit"
            class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
        >
            <i data-lucide="save" class="h-4 w-4"></i>
            Update Password
        </button>
    </div>
</form>
