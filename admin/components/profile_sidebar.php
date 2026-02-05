<!-- components/profile_sidebar.php -->
<div class="bg-white rounded shadow-sm border border-gray-200 p-2">
    <nav class="space-y-1">
        <a href="?tab=profile" class="flex items-center gap-3 px-4 py-3 rounded text-sm font-medium transition-colors <?php echo $activeTab === 'profile' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50'; ?>">
            <i data-lucide="user" style="width: 18px;"></i>
            Profile
        </a>
        <a href="?tab=password" class="flex items-center gap-3 px-4 py-3 rounded text-sm font-medium transition-colors <?php echo $activeTab === 'password' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50'; ?>">
            <i data-lucide="lock" style="width: 18px;"></i>
            Password
        </a>
        <a href="?tab=settings" class="flex items-center gap-3 px-4 py-3 rounded text-sm font-medium transition-colors <?php echo $activeTab === 'settings' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50'; ?>">
            <i data-lucide="settings" style="width: 18px;"></i>
            System Settings
        </a>
    </nav>
</div>
