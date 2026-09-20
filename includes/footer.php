<?php
// includes/footer.php
?>
    </div> <!-- Close flex inner container started in header.php -->

    <!-- Core Scripts -->
    <script src="/college_cms/assets/js/main.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Re-initialize lucide icons ensuring all dynamically added elements are caught
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            
            // Render any flash messages stored in the session
            <?php if (isset($_SESSION['flash_message'])): ?>
                if (typeof window.showToast === 'function') {
                    window.showToast(
                        <?= json_encode($_SESSION['flash_message']['message']) ?>, 
                        <?= json_encode($_SESSION['flash_message']['type']) ?>
                    );
                }
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>

            // Register PWA Service Worker
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/college_cms/sw.js')
                    .then(registration => {
                        console.log('ServiceWorker registration successful with scope: ', registration.scope);
                    })
                    .catch(err => {
                        console.log('ServiceWorker registration failed: ', err);
                    });
            }
        });
    </script>
</body>
</html>
