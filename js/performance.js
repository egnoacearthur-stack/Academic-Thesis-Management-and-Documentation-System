/**
 * Performance Enhancements
 */

// Lazy load images
document.addEventListener('DOMContentLoaded', function() {
    // Image lazy loading
    if ('loading' in HTMLImageElement.prototype) {
        const images = document.querySelectorAll('img[data-src]');
        images.forEach(img => {
            img.src = img.dataset.src;
        });
    }
    
    // Debounce resize events
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // Handle resize after user stops resizing
            adjustMobileLayout();
        }, 250);
    });
    
    // Optimize scroll events
    let scrollTimer;
    let lastScrollPosition = 0;
    
    window.addEventListener('scroll', function() {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(function() {
            const currentScroll = window.pageYOffset;
            
            // Hide header on scroll down, show on scroll up
            if (window.innerWidth <= 768) {
                const header = document.querySelector('.main-header');
                if (header) {
                    if (currentScroll > lastScrollPosition && currentScroll > 100) {
                        header.style.transform = 'translateY(-100%)';
                    } else {
                        header.style.transform = 'translateY(0)';
                    }
                }
            }
            
            lastScrollPosition = currentScroll;
        }, 100);
    });
    
    // Prefetch links on hover
    const links = document.querySelectorAll('a[href^="index.php"]');
    links.forEach(link => {
        link.addEventListener('mouseenter', function() {
            const url = this.getAttribute('href');
            if (url && !document.querySelector(`link[rel="prefetch"][href="${url}"]`)) {
                const prefetch = document.createElement('link');
                prefetch.rel = 'prefetch';
                prefetch.href = url;
                document.head.appendChild(prefetch);
            }
        }, { once: true });
    });
});

// Mobile layout adjustments
function adjustMobileLayout() {
    if (window.innerWidth <= 768) {
        // Auto-close dropdowns on mobile when clicking outside
        document.addEventListener('click', function(e) {
            const dropdowns = document.querySelectorAll('.user-dropdown.show');
            dropdowns.forEach(dropdown => {
                if (!dropdown.contains(e.target) && !e.target.closest('.user-avatar-wrapper')) {
                    dropdown.classList.remove('show');
                }
            });
        });
        
        // Improve table scrolling
        const tables = document.querySelectorAll('.table');
        tables.forEach(table => {
            if (!table.parentElement.classList.contains('table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
        });
    }
}

// Service Worker for offline support (optional)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        // Uncomment to enable offline support
        // navigator.serviceWorker.register('/sw.js');
    });
}

// Performance monitoring
if (window.performance && window.performance.timing) {
    window.addEventListener('load', function() {
        setTimeout(function() {
            const timing = window.performance.timing;
            const loadTime = timing.loadEventEnd - timing.navigationStart;
            
            if (loadTime > 3000) {
                console.warn('Page load time: ' + loadTime + 'ms (consider optimization)');
            }
        }, 0);
    });
}