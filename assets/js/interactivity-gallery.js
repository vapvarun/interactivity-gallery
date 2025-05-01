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
                        // Log the endpoint for debugging
                        console.log(`Loading media from: /wp-json/interactivity-gallery/v1/media/${state.postId}?per_page=${state.perPage}&page=${state.currentPage}`);
                        
                        const response = await fetch(`/wp-json/interactivity-gallery/v1/media/${state.postId}?per_page=${state.perPage}&page=${state.currentPage}`);
                        
                        if (!response.ok) {
                            throw new Error(`Network response was not ok: ${response.status} ${response.statusText}`);
                        }
                        
                        const data = await response.json();
                        
                        // Log the response data for debugging
                        console.log('Media data received:', data);
                        
                        if (!data.success || !Array.isArray(data.media)) {
                            throw new Error('Invalid data format received from server');
                        }
                        
                        // Update state with fetched data
                        state.media = data.media;
                        state.totalPages = data.pages;
                        state.currentPage = data.current_page;
                        state.isLoading = false;
                        
                        // Log the media items
                        console.log('Media items loaded:', state.media.length);
                        
                        // Set the first image as the active one if we have images
                        if (state.media.length > 0) {
                            // Find the first image in the collection
                            let firstImageIndex = -1;
                            for (let i = 0; i < state.media.length; i++) {
                                if (state.media[i].type && state.media[i].type.includes('image')) {
                                    firstImageIndex = i;
                                    break;
                                }
                            }
                            
                            if (firstImageIndex !== -1) {
                                console.log('Setting first image as active:', firstImageIndex);
                                state.activeMediaIndex = firstImageIndex;
                            }
                        }
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
                    
                    // Reset active media index
                    state.activeMediaIndex = -1;
                    
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
                    
                    const index = data.index;
                    console.log('Opening lightbox for index:', index);
                    console.log('Media item:', state.media[index]);
                    
                    // Only open lightbox for images
                    if (state.media[index] && state.media[index].type && state.media[index].type.includes('image')) {
                        state.activeMediaIndex = index;
                        state.isLightboxOpen = true;
                        document.body.style.overflow = 'hidden'; // Prevent body scrolling
                        console.log('Lightbox opened, activeMediaIndex:', state.activeMediaIndex);
                    } else {
                        console.log('Not opening lightbox - not an image or invalid media item');
                    }
                },
                
                closeLightbox: ({ state, event }) => {
                    console.log('Closing lightbox');
                    state.isLightboxOpen = false;
                    document.body.style.overflow = ''; // Restore body scrolling
                },
                
                prevMedia: ({ state, event }) => {
                    event.preventDefault();
                    console.log('Current activeMediaIndex:', state.activeMediaIndex);
                    
                    if (state.activeMediaIndex > 0) {
                        // Find previous image in the collection
                        let prevIndex = state.activeMediaIndex - 1;
                        
                        // Skip non-image media types
                        while (prevIndex >= 0 && !state.media[prevIndex].type.includes('image')) {
                            prevIndex--;
                        }
                        
                        if (prevIndex >= 0) {
                            console.log('Moving to previous image at index:', prevIndex);
                            state.activeMediaIndex = prevIndex;
                        } else {
                            console.log('No previous image found');
                        }
                    }
                },
                
                nextMedia: ({ state, event }) => {
                    event.preventDefault();
                    console.log('Current activeMediaIndex:', state.activeMediaIndex);
                    
                    if (state.activeMediaIndex < state.media.length - 1) {
                        // Find next image in the collection
                        let nextIndex = state.activeMediaIndex + 1;
                        
                        // Skip non-image media types
                        while (nextIndex < state.media.length && !state.media[nextIndex].type.includes('image')) {
                            nextIndex++;
                        }
                        
                        if (nextIndex < state.media.length) {
                            console.log('Moving to next image at index:', nextIndex);
                            state.activeMediaIndex = nextIndex;
                        } else {
                            console.log('No next image found');
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