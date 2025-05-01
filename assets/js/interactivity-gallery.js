/**
 * Interactivity Gallery - Interactive API Implementation
 */
wp.interactivity.init({
    context: {
        interactivityGallery: {
            state: {
                // State is initialized from the data-wp-context attribute
            },
            actions: {
                loadMedia: async ({ state, event }) => {
                    state.isLoading = true;
                    state.hasError = false;
                    
                    try {
                        const response = await fetch(`/wp-json/interactivity-gallery/v1/media/${state.postId}?per_page=${state.perPage}&page=${state.currentPage}`);
                        
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        
                        const data = await response.json();
                        
                        // Update state with fetched data
                        state.media = data.media;
                        state.totalPages = data.pages;
                        state.currentPage = data.current_page;
                        state.isLoading = false;
                    } catch (error) {
                        console.error('Error loading media:', error);
                        state.hasError = true;
                        state.isLoading = false;
                    }
                },
                
                goToPage: async ({ state, event, data }) => {
                    event.preventDefault();
                    
                    if (data.page < 1 || data.page > state.totalPages || data.page === state.currentPage) {
                        return;
                    }
                    
                    // Update current page
                    state.currentPage = data.page;
                    
                    // Reload media
                    await wp.interactivity.actions.interactivityGallery.loadMedia({ state, event });
                    
                    // Scroll to top of gallery
                    const galleryContainer = event.target.closest('.interactivity-gallery-container');
                    if (galleryContainer) {
                        galleryContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                },
                
                openLightbox: ({ state, event, data }) => {
                    event.preventDefault();
                    
                    // Only open lightbox for images
                    if (state.media[data.index].type.includes('image')) {
                        state.activeMediaIndex = data.index;
                        state.isLightboxOpen = true;
                        document.body.style.overflow = 'hidden'; // Prevent body scrolling
                    }
                },
                
                closeLightbox: ({ state, event }) => {
                    state.isLightboxOpen = false;
                    document.body.style.overflow = ''; // Restore body scrolling
                },
                
                prevMedia: ({ state, event }) => {
                    event.preventDefault();
                    
                    if (state.activeMediaIndex > 0) {
                        // Find previous image in the collection
                        let prevIndex = state.activeMediaIndex - 1;
                        
                        // Skip non-image media types
                        while (prevIndex >= 0 && !state.media[prevIndex].type.includes('image')) {
                            prevIndex--;
                        }
                        
                        if (prevIndex >= 0) {
                            state.activeMediaIndex = prevIndex;
                        }
                    }
                },
                
                nextMedia: ({ state, event }) => {
                    event.preventDefault();
                    
                    if (state.activeMediaIndex < state.media.length - 1) {
                        // Find next image in the collection
                        let nextIndex = state.activeMediaIndex + 1;
                        
                        // Skip non-image media types
                        while (nextIndex < state.media.length && !state.media[nextIndex].type.includes('image')) {
                            nextIndex++;
                        }
                        
                        if (nextIndex < state.media.length) {
                            state.activeMediaIndex = nextIndex;
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