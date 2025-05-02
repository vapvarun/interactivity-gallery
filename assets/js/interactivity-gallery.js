/**
 * Interactivity Gallery - Interactive API Implementation for WordPress 6.8+
 * Improved with robust state management and storage approach
 */
console.log('[IG DEBUG] Interactivity Gallery Script Loaded');

wp.interactivity.init({
    name: 'interactivityGallery', // Use a consistent store name
    
    selectors: {
        lightbox: '.interactivity-gallery-lightbox',
        lightboxImage: '.interactivity-gallery-lightbox-image',
        galleryContainer: '.interactivity-gallery-container'
    },
    
    context: {
        interactivityGallery: {
            debug: {
                logState: (state, action) => {
                    console.log('%c[IG DEBUG] ' + action, 'color: #2271b1; font-weight: bold;');
                    
                    try {
                        // Log key state variables
                        console.log('State:', {
                            mediaCount: state.media ? state.media.length : 0,
                            imageCount: state.lightboxItems ? state.lightboxItems.length : 0,
                            currentImageIndex: state.currentImageIndex,
                            isLightboxOpen: state.isLightboxOpen,
                            currentPage: state.currentPage,
                            totalPages: state.totalPages
                        });
                        
                        // Check active image
                        if (state.currentImageIndex >= 0 && state.lightboxItems && state.lightboxItems.length > 0) {
                            const activeItem = state.lightboxItems[state.currentImageIndex];
                            console.log('Active Image:', {
                                title: activeItem.title,
                                url: activeItem.url,
                                thumbnail: activeItem.thumbnail,
                                originalIndex: activeItem.originalIndex
                            });
                            
                            // Try to validate the image URL
                            if (activeItem.url) {
                                const img = new Image();
                                img.onload = () => console.log('%c[IG DEBUG] Image URL is valid and loaded successfully', 'color: green');
                                img.onerror = () => console.log('%c[IG DEBUG] Image URL failed to load', 'color: red');
                                img.src = activeItem.url;
                            }
                        }
                    } catch (error) {
                        console.error('[IG DEBUG] Error in debug logging:', error);
                    }
                }
            },
            state: {
                // State is initialized from the data-wp-context attribute
                // The following properties will be added dynamically:
                // postId, perPage, columns, currentPage, totalPages
                // isLoading, hasError, media

                // New state properties for improved lightbox functionality:
                mediaMap: {}, // For quick lookup by ID
                lightboxItems: [], // Array of only image items, pre-processed for lightbox
                currentImageIndex: -1, // Index in the lightboxItems array (not the media array)
                isLightboxOpen: false
            },
            actions: {
                loadMedia: async ({ state, event, selectors }) => {
                    console.log('[IG] loadMedia called, current state:', { 
                        postId: state.postId,
                        perPage: state.perPage,
                        currentPage: state.currentPage
                    });
                    
                    state.isLoading = true;
                    state.hasError = false;
                    
                    try {
                        const apiUrl = `/wp-json/interactivity-gallery/v1/media/${state.postId}?per_page=${state.perPage}&page=${state.currentPage}`;
                        console.log(`[IG] Fetching from: ${apiUrl}`);
                        
                        const response = await fetch(apiUrl);
                        
                        if (!response.ok) {
                            throw new Error(`Network response was not ok: ${response.status} ${response.statusText}`);
                        }
                        
                        const data = await response.json();
                        console.log('[IG] API response:', data);
                        
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
                        
                        console.log(`[IG] Media loaded: ${state.media.length} total items, ${state.lightboxItems.length} images`);
                        
                        // Set default current image if we have images, but don't open lightbox
                        if (state.lightboxItems.length > 0) {
                            state.currentImageIndex = 0;
                            console.log(`[IG] Set initial currentImageIndex to 0`);
                        } else {
                            state.currentImageIndex = -1;
                        }
                        
                        // Log the first few media items if available
                        if (state.lightboxItems.length > 0) {
                            console.log('[IG] First 2 lightbox items:', 
                                state.lightboxItems.slice(0, Math.min(2, state.lightboxItems.length))
                            );
                        }
                    } catch (error) {
                        console.error('[IG] Error loading media:', error);
                        state.hasError = true;
                        state.isLoading = false;
                    }
                },
                
                goToPage: async ({ state, event, data, selectors }) => {
                    event.preventDefault();
                    console.log(`[IG] goToPage called for page ${data.page}, current page: ${state.currentPage}`);
                    
                    if (data.page < 1 || data.page > state.totalPages || data.page === state.currentPage) {
                        console.log('[IG] Page navigation skipped - invalid page number or same page');
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
                    console.log(`[IG] Opening lightbox for media index: ${originalIndex}`);
                    
                    // Use the debug helper
                    wp.interactivity.context.interactivityGallery.debug.logState(state, 'Before Opening Lightbox');
                    
                    // Find the corresponding lightbox item by original index
                    const lightboxItemIndex = state.lightboxItems.findIndex(
                        item => item.originalIndex === originalIndex
                    );
                    
                    if (lightboxItemIndex === -1) {
                        console.error(`[IG] No image found at index ${originalIndex} or item is not an image`);
                        return;
                    }
                    
                    const lightboxItem = state.lightboxItems[lightboxItemIndex];
                    console.log('[IG] Found lightbox item:', lightboxItem);
                    
                    // Set the current image index and open the lightbox
                    state.currentImageIndex = lightboxItemIndex;
                    console.log(`[IG] Set currentImageIndex to ${lightboxItemIndex}`);
                    
                    // Open the lightbox
                    state.isLightboxOpen = true;
                    document.body.style.overflow = 'hidden'; // Prevent body scrolling
                    
                    // Use the debug helper after opening
                    wp.interactivity.context.interactivityGallery.debug.logState(state, 'After Opening Lightbox');
                    
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
                    if (lightboxImage && lightboxItem.url) {
                        lightboxImage.src = lightboxItem.url;
                        lightboxImage.style.cssText = `
                            max-width: 100% !important;
                            max-height: 100% !important;
                            object-fit: contain !important;
                            display: block !important;
                        `;
                    }
                },
                
                closeLightbox: ({ state, event, selectors }) => {
                    console.log('[IG] Closing lightbox');
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
                    console.log(`[IG] prevImage called, current index: ${state.currentImageIndex}`);
                    
                    if (state.currentImageIndex > 0) {
                        // Simply decrement the index since we're working with pre-filtered images
                        state.currentImageIndex--;
                        console.log(`[IG] Moving to previous image at index: ${state.currentImageIndex}`);
                        
                        // Update image directly as a fallback
                        const lightboxImage = document.querySelector(selectors.lightboxImage);
                        if (lightboxImage && state.lightboxItems[state.currentImageIndex]) {
                            lightboxImage.src = state.lightboxItems[state.currentImageIndex].url;
                        }
                    } else {
                        console.log('[IG] Already at first image');
                    }
                },
                
                nextImage: ({ state, event, selectors }) => {
                    event.preventDefault();
                    console.log(`[IG] nextImage called, current index: ${state.currentImageIndex}`);
                    
                    if (state.currentImageIndex < state.lightboxItems.length - 1) {
                        // Simply increment the index since we're working with pre-filtered images
                        state.currentImageIndex++;
                        console.log(`[IG] Moving to next image at index: ${state.currentImageIndex}`);
                        
                        // Update image directly as a fallback
                        const lightboxImage = document.querySelector(selectors.lightboxImage);
                        if (lightboxImage && state.lightboxItems[state.currentImageIndex]) {
                            lightboxImage.src = state.lightboxItems[state.currentImageIndex].url;
                        }
                    } else {
                        console.log('[IG] Already at last image');
                    }
                },
                
                stopPropagation: ({ event }) => {
                    event.stopPropagation();
                }
            }
        }
    }
});