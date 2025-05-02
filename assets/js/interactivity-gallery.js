/**
 * Interactivity Gallery - Interactive API Implementation for WordPress 6.8+
 */
wp.interactivity.init({
    name: 'interactivityGallery',
    
    selectors: {
        lightbox: '.interactivity-gallery-lightbox',
        lightboxImage: '.interactivity-gallery-lightbox-image',
        galleryContainer: '.interactivity-gallery-container'
    },
    
    context: {
        interactivityGallery: {
            state: {
                // State is initialized from the data-wp-context attribute
                // postId, perPage, columns, currentPage, totalPages
                // isLoading, hasError, media
                mediaMap: {}, // For quick lookup by ID
                lightboxItems: [], // Array of only image items for lightbox
                currentImageIndex: -1, // Index in the lightboxItems array
                isLightboxOpen: false
            },
            actions: {
                loadMedia: async ({ state, event, selectors }) => {
                    state.isLoading = true;
                    state.hasError = false;
                    
                    try {
                        const apiUrl = `/wp-json/interactivity-gallery/v1/media/${state.postId}?per_page=${state.perPage}&page=${state.currentPage}`;
                        const response = await fetch(apiUrl);
                        
                        if (!response.ok) {
                            throw new Error(`Network response was not ok: ${response.status}`);
                        }
                        
                        const data = await response.json();
                        
                        if (!data.success || !Array.isArray(data.media)) {
                            throw new Error('Invalid data format received from server');
                        }
                        
                        // Store the raw media data
                        state.media = data.media;
                        
                        // Build improved data structures for efficient access
                        state.mediaMap = {};
                        state.lightboxItems = [];
                        
                        // Process media items to build lookups and filter for lightbox
                        state.media.forEach((item, index) => {
                            // Store in map for quick lookups
                            state.mediaMap[item.id] = item;
                            
                            // For image items only, prepare for lightbox
                            if (item.type && item.type.includes('image')) {
                                state.lightboxItems.push({
                                    originalIndex: index, // Store original position in media array
                                    id: item.id,
                                    url: item.url,
                                    thumbnail: item.thumbnail,
                                    title: item.title,
                                    alt: item.alt || item.title
                                });
                            }
                        });
                        
                        // Update pagination data
                        state.totalPages = data.pages;
                        state.currentPage = data.current_page;
                        state.isLoading = false;
                        
                        // Set default current image if we have images, but don't open lightbox
                        if (state.lightboxItems.length > 0) {
                            state.currentImageIndex = 0;
                        } else {
                            state.currentImageIndex = -1;
                        }
                    } catch (error) {
                        state.hasError = true;
                        state.isLoading = false;
                    }
                },
                
                goToPage: async ({ state, event, data, selectors }) => {
                    event.preventDefault();
                    
                    if (data.page < 1 || data.page > state.totalPages || data.page === state.currentPage) {
                        return;
                    }
                    
                    // Update current page
                    state.currentPage = data.page;
                    
                    // Reset current image index and close lightbox if open
                    state.currentImageIndex = -1;
                    if (state.isLightboxOpen) {
                        state.isLightboxOpen = false;
                        document.body.style.overflow = '';
                    }
                    
                    // Reload media
                    await wp.interactivity.actions.interactivityGallery.loadMedia({ state, event });
                    
                    // Scroll to top of gallery
                    const galleryContainer = event.target.closest(selectors.galleryContainer);
                    if (galleryContainer) {
                        galleryContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                },
                
                openLightbox: ({ state, event, data, selectors }) => {
                    event.preventDefault();
                    
                    const originalIndex = parseInt(data.index);
                    
                    // Find the corresponding lightbox item by original index
                    const lightboxItemIndex = state.lightboxItems.findIndex(
                        item => item.originalIndex === originalIndex
                    );
                    
                    if (lightboxItemIndex === -1) {
                        return;
                    }
                    
                    // Set the current image index and open the lightbox
                    state.currentImageIndex = lightboxItemIndex;
                    
                    // Open the lightbox
                    state.isLightboxOpen = true;
                    document.body.style.overflow = 'hidden'; // Prevent body scrolling
                    
                    // Force any inline styles that might help
                    const lightbox = document.querySelector(selectors.lightbox);
                    if (lightbox) {
                        // Directly set important styles to ensure visibility
                        lightbox.style.cssText = `
                            position: fixed !important;
                            top: 0 !important;
                            left: 0 !important;
                            right: 0 !important;
                            bottom: 0 !important;
                            z-index: 9999 !important;
                            background-color: rgba(0, 0, 0, 0.9) !important;
                            display: flex !important;
                            visibility: visible !important;
                            opacity: 1 !important;
                        `;
                        
                        // Force the hidden attribute to be removed
                        lightbox.hidden = false;
                        lightbox.removeAttribute('hidden');
                    }
                    
                    // Also update the image directly to ensure it's visible
                    const lightboxImage = document.querySelector(selectors.lightboxImage);
                    const lightboxItem = state.lightboxItems[lightboxItemIndex];
                    if (lightboxImage && lightboxItem.url) {
                        // Add a timestamp to force reload of the image
                        const timestamp = new Date().getTime();
                        const imageUrl = lightboxItem.url.includes('?') 
                            ? `${lightboxItem.url}&_t=${timestamp}` 
                            : `${lightboxItem.url}?_t=${timestamp}`;
                            
                        lightboxImage.src = imageUrl;
                        lightboxImage.style.cssText = `
                            max-width: 100% !important;
                            max-height: 100% !important;
                            object-fit: contain !important;
                            display: block !important;
                        `;
                    }
                    
                    // Add a short delay then check again to make sure styles are applied
                    setTimeout(() => {
                        const lightbox = document.querySelector(selectors.lightbox);
                        const lightboxImage = document.querySelector(selectors.lightboxImage);
                        
                        if (lightbox && (lightbox.hidden || lightbox.hasAttribute('hidden') || 
                            window.getComputedStyle(lightbox).display === 'none')) {
                            
                            lightbox.hidden = false;
                            lightbox.removeAttribute('hidden');
                            lightbox.style.cssText = `
                                position: fixed !important;
                                top: 0 !important;
                                left: 0 !important;
                                right: 0 !important;
                                bottom: 0 !important;
                                z-index: 9999999 !important;
                                background-color: rgba(0, 0, 0, 0.9) !important;
                                display: flex !important;
                                align-items: center !important;
                                justify-content: center !important;
                                visibility: visible !important;
                                opacity: 1 !important;
                            `;
                        }
                        
                        // Double check the image
                        if (lightboxImage && lightboxItem.url && 
                            (!lightboxImage.src || !lightboxImage.src.includes(lightboxItem.url))) {
                            
                            // Try one more time with the image
                            const timestamp = new Date().getTime();
                            const imageUrl = lightboxItem.url.includes('?') 
                                ? `${lightboxItem.url}&_t=${timestamp}` 
                                : `${lightboxItem.url}?_t=${timestamp}`;
                                
                            lightboxImage.src = imageUrl;
                        }
                    }, 100);
                },
                
                closeLightbox: ({ state, event, selectors }) => {
                    state.isLightboxOpen = false;
                    document.body.style.overflow = ''; // Restore body scrolling
                    
                    // Directly manipulate DOM to ensure hiding
                    const lightbox = document.querySelector(selectors.lightbox);
                    if (lightbox) {
                        lightbox.hidden = true;
                        lightbox.setAttribute('hidden', '');
                        lightbox.style.display = 'none';
                    }
                },
                
                prevImage: ({ state, event, selectors }) => {
                    event.preventDefault();
                    
                    if (state.currentImageIndex > 0) {
                        // Simply decrement the index since we're working with pre-filtered images
                        state.currentImageIndex--;
                        
                        // Update image directly with timestamp to force reload
                        updateLightboxImage(state, selectors);
                    }
                },
                
                nextImage: ({ state, event, selectors }) => {
                    event.preventDefault();
                    
                    if (state.currentImageIndex < state.lightboxItems.length - 1) {
                        // Simply increment the index since we're working with pre-filtered images
                        state.currentImageIndex++;
                        
                        // Update image directly with timestamp to force reload
                        updateLightboxImage(state, selectors);
                    }
                },
                
                stopPropagation: ({ event }) => {
                    event.stopPropagation();
                }
            }
        }
    }
});

// Helper function to update the lightbox image directly
function updateLightboxImage(state, selectors) {
    if (state.currentImageIndex >= 0 && 
        state.lightboxItems && 
        state.lightboxItems.length > 0 &&
        state.currentImageIndex < state.lightboxItems.length) {
        
        const lightboxImage = document.querySelector(selectors.lightboxImage);
        if (lightboxImage) {
            const item = state.lightboxItems[state.currentImageIndex];
            
            // Add timestamp to force reload
            const timestamp = new Date().getTime();
            const imageUrl = item.url.includes('?') 
                ? `${item.url}&_t=${timestamp}` 
                : `${item.url}?_t=${timestamp}`;
                
            lightboxImage.src = imageUrl;
        }
    }
}