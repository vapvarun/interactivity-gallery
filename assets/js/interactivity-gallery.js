/**
 * Interactivity Gallery - Interactive API Implementation for WordPress 6.8+
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
                            activeMediaIndex: state.activeMediaIndex,
                            isLightboxOpen: state.isLightboxOpen,
                            currentPage: state.currentPage,
                            totalPages: state.totalPages
                        });
                        
                        // Check active media item
                        if (state.activeMediaIndex >= 0 && state.media && state.media.length > 0) {
                            const activeItem = state.media[state.activeMediaIndex];
                            console.log('Active Media:', {
                                title: activeItem.title,
                                type: activeItem.type,
                                url: activeItem.url,
                                thumbnail: activeItem.thumbnail
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
                        
                        // Update state with fetched data
                        state.media = data.media;
                        state.totalPages = data.pages;
                        state.currentPage = data.current_page;
                        state.isLoading = false;
                        
                        console.log(`[IG] Media loaded: ${state.media.length} items`);
                        
                        // Log the first few media items if available
                        if (state.media.length > 0) {
                            console.log('[IG] First 2 media items:', state.media.slice(0, 2));
                            
                            // Find the first image in the collection
                            let firstImageIndex = -1;
                            for (let i = 0; i < state.media.length; i++) {
                                if (state.media[i].type && state.media[i].type.includes('image')) {
                                    firstImageIndex = i;
                                    break;
                                }
                            }
                            
                            if (firstImageIndex !== -1) {
                                console.log(`[IG] Setting first image as active: index=${firstImageIndex}`);
                                state.activeMediaIndex = firstImageIndex;
                            }
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
                    
                    // Reset active media index
                    state.activeMediaIndex = -1;
                    
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
                    
                    const index = parseInt(data.index);
                    console.log(`[IG] Opening lightbox for index: ${index}`);
                    
                    // Use the debug helper
                    wp.interactivity.context.interactivityGallery.debug.logState(state, 'Before Opening Lightbox');
                    
                    // Validate index
                    if (isNaN(index) || index < 0 || !state.media || index >= state.media.length) {
                        console.error(`[IG] Invalid media index: ${index}`);
                        return;
                    }
                    
                    const mediaItem = state.media[index];
                    console.log('[IG] Media item:', mediaItem);
                    
                    // Only open lightbox for images
                    if (mediaItem && mediaItem.type && mediaItem.type.includes('image')) {
                        // Validate image URL
                        if (!mediaItem.url) {
                            console.error('[IG] Media item has no URL:', mediaItem);
                            return;
                        }
                        
                        // Set active index before opening lightbox to ensure proper rendering
                        state.activeMediaIndex = index;
                        console.log(`[IG] Set activeMediaIndex to ${index}`);
                        
                        // Add a small delay to ensure the state is updated before showing the lightbox
                        setTimeout(() => {
                            state.isLightboxOpen = true;
                            document.body.style.overflow = 'hidden'; // Prevent body scrolling
                            
                            // Use the debug helper after opening
                            wp.interactivity.context.interactivityGallery.debug.logState(state, 'After Opening Lightbox');
                            
                            // Force any inline styles that might help
                            const lightbox = document.querySelector(selectors.lightbox);
                            if (lightbox) {
                                lightbox.style.display = 'flex';
                                lightbox.style.position = 'fixed';
                                lightbox.style.top = '0';
                                lightbox.style.left = '0';
                                lightbox.style.right = '0';
                                lightbox.style.bottom = '0';
                                lightbox.style.zIndex = '9999';
                                lightbox.style.backgroundColor = 'rgba(0, 0, 0, 0.9)';
                                
                                // Force the hidden attribute to be removed
                                lightbox.hidden = false;
                                lightbox.removeAttribute('hidden');
                            }
                            
                            // Do a direct DOM check
                            setTimeout(() => {
                                const lightboxImage = document.querySelector(selectors.lightboxImage);
                                console.log('[IG] Lightbox element:', {
                                    lightboxVisible: lightbox ? window.getComputedStyle(lightbox).display !== 'none' : false,
                                    lightboxImage: lightboxImage,
                                    imageSrc: lightboxImage ? lightboxImage.getAttribute('src') : 'no image element',
                                    imageComplete: lightboxImage ? lightboxImage.complete : false
                                });
                                
                                // Directly set the image src as a fallback
                                if (lightboxImage && (!lightboxImage.src || lightboxImage.src === '')) {
                                    console.log('[IG] Setting image src directly:', mediaItem.url);
                                    lightboxImage.src = mediaItem.url;
                                }
                            }, 100);
                        }, 150); // Increased from 100ms to 150ms
                    } else {
                        console.log('[IG] Not opening lightbox - not an image or invalid media item');
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
                    }
                },
                
                prevMedia: ({ state, event, selectors }) => {
                    event.preventDefault();
                    console.log(`[IG] prevMedia called, current index: ${state.activeMediaIndex}`);
                    
                    if (state.activeMediaIndex > 0) {
                        // Find previous image in the collection
                        let prevIndex = state.activeMediaIndex - 1;
                        
                        // Skip non-image media types
                        while (prevIndex >= 0 && !state.media[prevIndex].type.includes('image')) {
                            prevIndex--;
                        }
                        
                        if (prevIndex >= 0) {
                            console.log(`[IG] Moving to previous image at index: ${prevIndex}`);
                            state.activeMediaIndex = prevIndex;
                            
                            // Update image directly as a fallback
                            setTimeout(() => {
                                const lightboxImage = document.querySelector(selectors.lightboxImage);
                                if (lightboxImage && state.media[prevIndex] && state.media[prevIndex].url) {
                                    lightboxImage.src = state.media[prevIndex].url;
                                }
                            }, 50);
                        } else {
                            console.log('[IG] No previous image found');
                        }
                    }
                },
                
                nextMedia: ({ state, event, selectors }) => {
                    event.preventDefault();
                    console.log(`[IG] nextMedia called, current index: ${state.activeMediaIndex}`);
                    
                    if (state.activeMediaIndex < state.media.length - 1) {
                        // Find next image in the collection
                        let nextIndex = state.activeMediaIndex + 1;
                        
                        // Skip non-image media types
                        while (nextIndex < state.media.length && !state.media[nextIndex].type.includes('image')) {
                            nextIndex++;
                        }
                        
                        if (nextIndex < state.media.length) {
                            console.log(`[IG] Moving to next image at index: ${nextIndex}`);
                            state.activeMediaIndex = nextIndex;
                            
                            // Update image directly as a fallback
                            setTimeout(() => {
                                const lightboxImage = document.querySelector(selectors.lightboxImage);
                                if (lightboxImage && state.media[nextIndex] && state.media[nextIndex].url) {
                                    lightboxImage.src = state.media[nextIndex].url;
                                }
                            }, 50);
                        } else {
                            console.log('[IG] No next image found');
                        }
                    }
                },
                
                stopPropagation: ({ event }) => {
                    event.stopPropagation();
                }
            }
        }
    }
});