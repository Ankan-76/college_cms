// assets/js/main.js

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Lucide Icons globally
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // 2. Theme Toggle Logic
    const themeToggleBtn = document.getElementById('theme-toggle');
    const darkIcon = document.getElementById('theme-toggle-dark-icon');
    const lightIcon = document.getElementById('theme-toggle-light-icon');

    const updateIcons = () => {
        if (!themeToggleBtn || !darkIcon || !lightIcon) return;
        
        if (document.documentElement.classList.contains('dark')) {
            darkIcon.classList.add('hidden');
            lightIcon.classList.remove('hidden');
        } else {
            lightIcon.classList.add('hidden');
            darkIcon.classList.remove('hidden');
        }
    };

    updateIcons();

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            // Toggle dark class
            document.documentElement.classList.toggle('dark');
            
            // Save preference to localStorage
            if (document.documentElement.classList.contains('dark')) {
                localStorage.theme = 'dark';
            } else {
                localStorage.theme = 'light';
            }
            
            // Sync icons
            updateIcons();
        });
    }

    // 3. Mobile Sidebar Drawer Logic
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.getElementById('sidebar');
    
    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', () => {
            const isExpanded = mobileMenuBtn.getAttribute('aria-expanded') === 'true';
            mobileMenuBtn.setAttribute('aria-expanded', !isExpanded);
            
            // Assuming sidebar uses 'transform -translate-x-full' for hiding off-canvas
            sidebar.classList.toggle('-translate-x-full');
        });
    }

    // 4. Global Toast Handling via SweetAlert2
    window.showToast = (message, type = 'success') => {
        if (typeof Swal === 'undefined') return;
        
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        Toast.fire({
            icon: type,
            title: message,
            // Apply customized background based on current theme to match UI
            background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
            color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a'
        });
    };
});
