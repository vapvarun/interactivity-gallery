/**
 * Interactivity Gallery Lightbox Fix
 */
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        // Find all galleries on the page
        const galleries = document.querySelectorAll('.interactivity-gallery-container');
        
        if (galleries.length === 0) {
            return;
        }
        
        // Process each gallery separately
        galleries.forEach(function(gallery) {
            // Get the namespace for this gallery
            const namespace = gallery.getAttribute('data-wp-interactive');
            
            if (!namespace) {
                return;
            }
            
            // Find the lightbox for this gallery
            const lightbox = gallery.querySelector('.interactivity-gallery-lightbox');
            const lightboxImage = gallery.querySelector('.interactivity-gallery-lightbox-image');
            
            if (!lightbox || !lightboxImage) {
                return;
            }
            
            // Observe the lightbox for visibility changes
            observeLightboxChanges(lightbox, lightboxImage, namespace);
            
            // Add enhanced click handlers to gallery images
            addEnhancedClickHandlers(gallery, lightbox, lightboxImage, namespace);
            
            // Add navigation event listeners
            addNavigationHandlers(gallery, lightboxImage, namespace);
        });
    });
    
    // Function to observe lightbox visibility changes
    function observeLightboxChanges(lightbox, lightboxImage, namespace) {
        // Create a mutation observer to detect attribute changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'hidden' || 
                    mutation.attributeName === 'style' ||
                    mutation.attributeName === 'class') {
                    
                    // Check if the lightbox should be visible but isn't
                    if (wp.interactivity && 
                        wp.interactivity.state && 
                        wp.interactivity.state[namespace] &&
                        wp.interactivity.state[namespace].isLightboxOpen) {
                        
                        if (lightbox.hidden || 
                            lightbox.hasAttribute('hidden') ||
                            window.getComputedStyle(lightbox).display === 'none' ||
                            window.getComputedStyle(lightbox).visibility === 'hidden') {
                            
                            fixLightboxVisibility(lightbox, lightboxImage, namespace);
                        }
                    }
                }
            });
        });
        
        // Start observing the lightbox
        observer.observe(lightbox, { 
            attributes: true, 
            attributeFilter: ['hidden', 'style', 'class'] 
        });
        
        // Also poll for state changes
        const stateInterval = setInterval(function() {
            if (wp.interactivity && 
                wp.interactivity.state && 
                wp.interactivity.state[namespace]) {
                
                const state = wp.interactivity.state[namespace];
                
                // If lightbox should be open
                if (state.isLightboxOpen) {
                    // Check if lightbox is properly visible
                    if (lightbox.hidden || 
                        lightbox.hasAttribute('hidden') ||
                        window.getComputedStyle(lightbox).display === 'none' ||
                        window.getComputedStyle(lightbox).visibility === 'hidden') {
                        
                        fixLightboxVisibility(lightbox, lightboxImage, namespace);
                    }
                    
                    // Check if image is correct
                    if (state.currentImageIndex >= 0 && 
                        state.lightboxItems && 
                        state.lightboxItems.length > 0 &&
                        state.currentImageIndex < state.lightboxItems.length) {
                        
                        const expectedUrl = state.lightboxItems[state.currentImageIndex].url;
                        
                        // If image source is empty or wrong
                        if (!lightboxImage.src || !lightboxImage.src.includes(expectedUrl.split('?')[0])) {
                            
                            // Add timestamp to force reload
                            const timestamp = new Date().getTime();
                            const imageUrl = expectedUrl.includes('?') 
                                ? `${expectedUrl}&_t=${timestamp}` 
                                : `${expectedUrl}?_t=${timestamp}`;
                            
                            lightboxImage.src = imageUrl;
                        }
                    }
                }
            }
        }, 500); // Check every 500ms
        
        // Clean up interval when page unloads
        window.addEventListener('beforeunload', function() {
            clearInterval(stateInterval);
        });
    }
    
    // Function to add enhanced click handlers to gallery images
    function addEnhancedClickHandlers(gallery, lightbox, lightboxImage, namespace) {
        // Find all images in this gallery
        const galleryLinks = gallery.querySelectorAll('.interactivity-gallery-item a');
        
        galleryLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                // Let the original handler run, then check after a delay
                setTimeout(function() {
                    // Only proceed if interactivity API is available
                    if (!wp.interactivity || !wp.interactivity.state || !wp.interactivity.state[namespace]) {
                        return;
                    }
                    
                    const state = wp.interactivity.state[namespace];
                    
                    // If lightbox should be open
                    if (state.isLightboxOpen) {
                        // Fix lightbox visibility if needed
                        if (lightbox.hidden || 
                            lightbox.hasAttribute('hidden') ||
                            window.getComputedStyle(lightbox).display === 'none' ||
                            window.getComputedStyle(lightbox).visibility === 'hidden') {
                            
                            fixLightboxVisibility(lightbox, lightboxImage, namespace);
                        }
                        
                        // Fix image if needed
                        if (state.currentImageIndex >= 0 && 
                            state.lightboxItems && 
                            state.lightboxItems.length > 0 &&
                            state.currentImageIndex < state.lightboxItems.length) {
                            
                            const expectedUrl = state.lightboxItems[state.currentImageIndex].url;
                            
                            // If image source is empty or wrong
                            if (!lightboxImage.src || !lightboxImage.src.includes(expectedUrl.split('?')[0])) {
                                
                                // Add timestamp to force reload
                                const timestamp = new Date().getTime();
                                const imageUrl = expectedUrl.includes('?') 
                                    ? `${expectedUrl}&_t=${timestamp}` 
                                    : `${expectedUrl}?_t=${timestamp}`;
                                
                                lightboxImage.src = imageUrl;
                                
                                // Apply necessary styles to image
                                lightboxImage.style.cssText = `
                                    max-width: 100% !important;
                                    max-height: 100% !important;
                                    object-fit: contain !important;
                                    display: block !important;
                                `;
                            }
                        }
                    }
                }, 50); // Short delay to let original handler run
            });
        });
    }
    
    // Function to add navigation handlers
    function addNavigationHandlers(gallery, lightboxImage, namespace) {
        const prevButton = gallery.querySelector('.interactivity-gallery-lightbox-prev');
        const nextButton = gallery.querySelector('.interactivity-gallery-lightbox-next');
        
        if (prevButton) {
            prevButton.addEventListener('click', function() {
                // Let original handler run, then check
                setTimeout(function() {
                    fixImageAfterNavigation(lightboxImage, namespace);
                }, 50);
            });
        }
        
        if (nextButton) {
            nextButton.addEventListener('click', function() {
                // Let original handler run, then check
                setTimeout(function() {
                    fixImageAfterNavigation(lightboxImage, namespace);
                }, 50);
            });
        }
    }
    
    // Function to fix image after navigation
    function fixImageAfterNavigation(lightboxImage, namespace) {
        if (!wp.interactivity || !wp.interactivity.state || !wp.interactivity.state[namespace]) {
            return;
        }
        
        const state = wp.interactivity.state[namespace];
        
        if (state.isLightboxOpen && 
            state.currentImageIndex >= 0 && 
            state.lightboxItems && 
            state.lightboxItems.length > 0 &&
            state.currentImageIndex < state.lightboxItems.length) {
            
            const expectedUrl = state.lightboxItems[state.currentImageIndex].url;
            
            // If image source is empty or wrong
            if (!lightboxImage.src || !lightboxImage.src.includes(expectedUrl.split('?')[0])) {
                
                // Add timestamp to force reload
                const timestamp = new Date().getTime();
                const imageUrl = expectedUrl.includes('?') 
                    ? `${expectedUrl}&_t=${timestamp}` 
                    : `${expectedUrl}?_t=${timestamp}`;
                
                lightboxImage.src = imageUrl;
            }
        }
    }
    
    // Function to fix lightbox visibility
    function fixLightboxVisibility(lightbox, lightboxImage, namespace) {
        // Make absolutely sure the lightbox is visible
        lightbox.hidden = false;
        lightbox.removeAttribute('hidden');
        
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
        
        // Also fix the image if we can
        if (wp.interactivity && 
            wp.interactivity.state && 
            wp.interactivity.state[namespace] &&
            wp.interactivity.state[namespace].isLightboxOpen) {
            
            const state = wp.interactivity.state[namespace];
            
            if (state.currentImageIndex >= 0 && 
                state.lightboxItems && 
                state.lightboxItems.length > 0 &&
                state.currentImageIndex < state.lightboxItems.length) {
                
                const expectedUrl = state.lightboxItems[state.currentImageIndex].url;
                
                // Add timestamp to force reload
                const timestamp = new Date().getTime();
                const imageUrl = expectedUrl.includes('?') 
                    ? `${expectedUrl}&_t=${timestamp}` 
                    : `${expectedUrl}?_t=${timestamp}`;
                
                lightboxImage.src = imageUrl;
                
                // Apply necessary styles to image
                lightboxImage.style.cssText = `
                    max-width: 100% !important;
                    max-height: 100% !important;
                    object-fit: contain !important;
                    display: block !important;
                `;
            }
        }
    }
})();