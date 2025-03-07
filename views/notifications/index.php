<!-- views/notifications/index.php -->
<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Notificaties</h1>
        
        <?php if (!empty($notifications)): ?>
        <button id="markAllAsRead" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Markeer alles als gelezen
        </button>
        <?php endif; ?>
    </div>
    
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <?php if (empty($notifications)): ?>
            <div class="p-8 text-center text-gray-500">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                <p>Je hebt nog geen notificaties</p>
            </div>
        <?php else: ?>
            <ul class="divide-y divide-gray-200">
                <?php foreach ($notifications as $notification): ?>
                    <?php 
                        $bgColor = $notification['is_read'] ? 'bg-white' : 'bg-blue-50';
                        $typeColor = '';
                        
                        switch ($notification['type']) {
                            case 'info':
                                $typeColor = 'border-blue-500';
                                $icon = '<svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                                break;
                            case 'warning':
                                $typeColor = 'border-yellow-500';
                                $icon = '<svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
                                break;
                            case 'danger':
                                $typeColor = 'border-red-500';
                                $icon = '<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                                break;
                            case 'success':
                                $typeColor = 'border-green-500';
                                $icon = '<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                                break;
                        }
                    ?>
                    <li class="<?= $bgColor ?> border-l-4 <?= $typeColor ?> notification-item" data-id="<?= $notification['id'] ?>">
                        <div class="px-4 py-5 sm:px-6 flex">
                            <div class="mr-4 mt-1">
                                <?= $icon ?>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                                        <?= htmlspecialchars($notification['title']) ?>
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        <?= date('d-m-Y H:i', strtotime($notification['created_at'])) ?>
                                    </p>
                                </div>
                                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                                    <?= htmlspecialchars($notification['message']) ?>
                                </p>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Markeer notificaties als gelezen bij klikken
    const notificationItems = document.querySelectorAll('.notification-item');
    notificationItems.forEach(item => {
        item.addEventListener('click', function() {
            const id = this.dataset.id;
            markAsRead(id);
            this.classList.remove('bg-blue-50');
            this.classList.add('bg-white');
        });
    });
    
    // Markeer alle notificaties als gelezen
    const markAllButton = document.getElementById('markAllAsRead');
    if (markAllButton) {
        markAllButton.addEventListener('click', function() {
            markAllAsRead();
            notificationItems.forEach(item => {
                item.classList.remove('bg-blue-50');
                item.classList.add('bg-white');
            });
        });
    }
    
    // AJAX functies
    function markAsRead(id) {
        fetch('/notifications/mark-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            updateNotificationCounter();
        })
        .catch(error => console.error('Error:', error));
    }
    
    function markAllAsRead() {
        fetch('/notifications/mark-all-read', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            updateNotificationCounter();
        })
        .catch(error => console.error('Error:', error));
    }
    
    function updateNotificationCounter() {
        // Update de notificatieteller in de navigatiebalk
        fetch('/notifications/count')
        .then(response => response.json())
        .then(data => {
            const counter = document.getElementById('notification-counter');
            if (counter) {
                if (data.count > 0) {
                    counter.textContent = data.count;
                    counter.classList.remove('hidden');
                } else {
                    counter.classList.add('hidden');
                }
            }
        })
        .catch(error => console.error('Error:', error));
    }
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>