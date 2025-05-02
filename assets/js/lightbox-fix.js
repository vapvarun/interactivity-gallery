/**
 * Interactivity Gallery Lightbox Fix - Simplified
 * This script provides a fallback for lightbox display issues
 */
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        console.log('[IG LIGHTBOX FIX] Script loaded - simplified version');
        
        // Observe DOM for lightbox visibility changes
        observeLightboxChanges();
        
        // Add backup click handlers to gallery images
        addBackupClickHandlers();
    });
    
    function observeLightboxChanges() {
        // Create a mutation observer to detect when the lightbox becomes visible
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'hidden' || 
                    mutation.attributeName === 'style' ||
                    mutation.attributeName === 'class') {
                    
                    const lightbox = document.querySelector('.interactivity-gallery-lightbox');
                    if (lightbox && !lightbox.hidden && 
                        !lightbox.hasAttribute('hidden') &&
                        (window.getComputedStyle(lightbox).display !== 'none')) {
                        
                        console.log('[IG LIGHTBOX FIX] Lightbox visible - ensuring proper display');
                        ensureLightboxIsVisible();
                    }
                }
            });
        });
        
        // Start observing the lightbox element
        const lightbox = document.querySelector('.interactivity-gallery-lightbox');
        if (lightbox) {
            observer.observe(lightbox, { 
                attributes: true, 
                attributeFilter: ['hidden', 'style', 'class'] 
            });
            console.log('[IG LIGHTBOX FIX] Observing lightbox for changes');
        }
    }
    
    function addBackupClickHandlers() {
        // This is just a fallback in case the Interactivity API events fail
        document.querySelectorAll('.interactivity-gallery-item a').forEach(link => {
            link.addEventListener('click', function(e) {
                // Only add as backup - don't prevent default behavior
                setTimeout(() => {
                    const lightbox = document.querySelector('.interactivity-gallery-lightbox');
                    if (lightbox && !lightbox.hidden && 
                        !lightbox.hasAttribute('hidden') &&
                        window.getComputedStyle(lightbox).display === 'none') {
                        
                        console.log('[IG LIGHTBOX FIX] Backup handler fixing lightbox display');
                        ensureLightboxIsVisible();
                    }
                }, 100);
            });
        });
    }
    
    function ensureLightboxIsVisible() {
        const lightbox = document.querySelector('.interactivity-gallery-lightbox');
        
        if (!lightbox) return;
        
        // Force the lightbox to be visible
        lightbox.style.cssText = `
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            background-color: rgba(0, 0, 0, 0.9) !important;
            z-index: 9999999 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            visibility: visible !important;
            opacity: 1 !important;
        `;
        
        // Remove hidden attributes
        lightbox.hidden = false;
        lightbox.removeAttribute('hidden');
    }
})();