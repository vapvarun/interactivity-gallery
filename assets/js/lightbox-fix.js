/**
 * Interactivity Gallery Lightbox Fix
 * This script fixes issues with the lightbox not displaying images correctly
 */
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        console.log('[IG LIGHTBOX FIX] Script loaded');
        
        // Fix lightbox issues after the page loads
        fixLightboxOnLoad();
        
        // Also try to fix the lightbox when it opens
        observeLightboxChanges();
    });
    
    function fixLightboxOnLoad() {
        // Target elements
        const lightbox = document.querySelector('.interactivity-gallery-lightbox');
        const lightboxContainer = document.querySelector('.interactivity-gallery-lightbox-content');
        const lightboxImages = document.querySelectorAll('.interactivity-gallery-lightbox-image');
        
        if (!lightbox) {
            console.log('[IG LIGHTBOX FIX] Lightbox element not found');
            return;
        }
        
        console.log('[IG LIGHTBOX FIX] Found lightbox elements to repair');
        
        // Apply critical CSS to make sure the lightbox displays correctly
        const style = document.createElement('style');
        style.textContent = `
            /* Fix for z-index and positioning issues */
            .interactivity-gallery-lightbox:not([hidden]) {
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
            }
            
            /* Fix for images not displaying */
            .interactivity-gallery-lightbox-image {
                max-width: 100% !important;
                max-height: 100% !important;
                object-fit: contain !important;
            }
            
            /* Ensure navigation buttons are visible */
            .interactivity-gallery-lightbox-prev,
            .interactivity-gallery-lightbox-next {
                position: absolute !important;
                z-index: 10000 !important;
                color: white !important;
                font-size: 30px !important;
                background: rgba(0, 0, 0, 0.5) !important;
                border-radius: 50% !important;
                width: 50px !important;
                height: 50px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
        `;
        document.head.appendChild(style);
        
        // Fix any gallery image click events
        document.querySelectorAll('.interactivity-gallery-item a').forEach(link => {
            link.addEventListener('click', function(e) {
                // Don't override default behavior, just add a backup
                setTimeout(() => {
                    ensureLightboxIsVisible();
                }, 50);
            });
        });
    }
    
    function observeLightboxChanges() {
        // Create a mutation observer to detect when the lightbox becomes visible
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                // Check if the hidden attribute was removed or style was changed
                if (mutation.attributeName === 'hidden' || 
                    mutation.attributeName === 'style' ||
                    mutation.attributeName === 'class') {
                    
                    const lightbox = document.querySelector('.interactivity-gallery-lightbox');
                    if (lightbox && !lightbox.hidden && 
                        !lightbox.hasAttribute('hidden') &&
                        (window.getComputedStyle(lightbox).display !== 'none')) {
                        
                        console.log('[IG LIGHTBOX FIX] Lightbox is now visible!');
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
    
    function ensureLightboxIsVisible() {
        const lightbox = document.querySelector('.interactivity-gallery-lightbox');
        const lightboxImage = document.querySelector('.interactivity-gallery-lightbox-image');
        
        if (!lightbox) return;
        
        console.log('[IG LIGHTBOX FIX] Ensuring lightbox is visible');
        
        // Force the lightbox to be visible with inline styles
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
        
        // If we have an image, make sure it's visible
        if (lightboxImage) {
            // Fix the image display
            lightboxImage.style.cssText = `
                max-width: 100% !important;
                max-height: 100% !important;
                object-fit: contain !important;
                display: block !important;
            `;
            
            // Ensure image has loaded
            if (lightboxImage.src && !lightboxImage.complete) {
                lightboxImage.onload = function() {
                    console.log('[IG LIGHTBOX FIX] Image has loaded:', lightboxImage.src);
                };
                
                lightboxImage.onerror = function() {
                    console.error('[IG LIGHTBOX FIX] Image failed to load:', lightboxImage.src);
                    // Try to set a fallback
                    lightboxImage.src = 'https://via.placeholder.com/800x600?text=Image+Load+Error';
                };
            }
            
            console.log('[IG LIGHTBOX FIX] Image source:', lightboxImage.src);
        }
    }
})();