<div class="w-full">
    @php
        $data = $slideshowData ?? [
            'images' => [],
            'celebrationId' => 'default',
            'hasImages' => false
        ];
        $images = is_array($data['images']) ? collect($data['images']) : collect([]);
        $celebrationId = $data['celebrationId'] ?? 'default';
        $hasImages = $data['hasImages'] ?? false;
    @endphp

    @if($hasImages && $images->count() > 0)
        <!-- Full Width Section without padding -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <!-- Header with title and download button -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">
                    Slideshow Images ({{ $images->count() }} {{ $images->count() === 1 ? 'image' : 'images' }})
                </h3>

                <button type="button"
                        wire:click="downloadSlideshow"
                        class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Download All Images
                </button>
            </div>

            <!-- Slideshow Content -->
            <div class="p-6 space-y-4">
                <!-- Main Slideshow Container -->
                <div class="relative bg-gray-100 rounded-lg overflow-hidden border-2 border-dashed border-gray-300" style="height: 500px;">
                    <div id="slideshow-container-{{ $celebrationId }}" class="relative w-full h-full">
                        @foreach($images as $index => $image)
                            <div class="slideshow-image absolute inset-0 flex items-center justify-center transition-opacity duration-500 {{ $index === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0' }}"
                                 data-index="{{ $index }}"
                                 style="background-color: #f8f9fa;">
                                <img src="{{ $image['url'] }}"
                                     alt="Slideshow Image {{ $index + 1 }}"
                                     class="max-w-full max-h-full object-contain"
                                     style="display: block;"
                                     loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <!-- Error fallback -->
                                <div style="display: none;" class="text-center text-gray-500 p-4">
                                    <svg class="w-16 h-16 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 002 2z"></path>
                                    </svg>
                                    <p>Image failed to load</p>
                                    <p class="text-xs mt-1 break-all">{{ $image['url'] }}</p>
                                </div>
                            </div>
                        @endforeach

                        <!-- Loading indicator -->
                        <div id="loading-indicator-{{ $celebrationId }}" class="absolute inset-0 flex items-center justify-center bg-gray-100">
                            <div class="text-center">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto mb-2"></div>
                                <p class="text-gray-600 text-sm">Loading images...</p>
                            </div>
                        </div>

                        <!-- Navigation arrows -->
                        @if($images->count() > 1)
                            <button type="button"
                                    id="prev-btn-{{ $celebrationId }}"
                                    class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-3 rounded-full hover:bg-opacity-75 transition-all z-20 backdrop-blur-sm">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>

                            <button type="button"
                                    id="next-btn-{{ $celebrationId }}"
                                    class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-3 rounded-full hover:bg-opacity-75 transition-all z-20 backdrop-blur-sm">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        @endif

                        <!-- Image counter overlay -->
                        <div class="absolute bottom-4 left-4 bg-black bg-opacity-50 text-white px-3 py-1 rounded-full text-sm backdrop-blur-sm z-20">
                            <span id="current-image-{{ $celebrationId }}">1</span> / {{ $images->count() }}
                        </div>
                    </div>
                </div>

                <!-- Thumbnails strip -->
                @if($images->count() > 1)
                    <div class="border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Quick Navigation</h4>
                        <div class="flex space-x-3 overflow-x-auto pb-2">
                            @foreach($images as $index => $image)
                                <button type="button"
                                        class="thumbnail-{{ $celebrationId }} flex-shrink-0 w-20 h-20 rounded-lg border-2 overflow-hidden transition-all duration-200 hover:scale-105 {{ $index === 0 ? 'border-primary-500 ring-2 ring-primary-200' : 'border-gray-300 hover:border-gray-400' }}"
                                        data-index="{{ $index }}">
                                    <img src="{{ $image['url'] }}"
                                         alt="Thumbnail {{ $index + 1 }}"
                                         class="w-full h-full object-cover"
                                         loading="lazy"
                                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjIwIiBoZWlnaHQ9IjIwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0xMCAxNUMxMi43NjE0IDE1IDE1IDEyLjc2MTQgMTUgMTBDMTUgNy4yMzg1OCAxMi43NjE0IDUgMTAgNUM3LjIzODU4IDUgNSA3LjIzODU4IDUgMTBDNSAxMi43NjE0IDcuMjM4NTggMTUgMTAgMTVaIiBzdHJva2U9IiM5Q0E0QUYiIHN0cm9rZS13aWR0aD0iMS41Ii8+CjxwYXRoIGQ9Ik0xMCAxMlY4IiBzdHJva2U9IiM5Q0E0QUYiIHN0cm9rZS13aWR0aD0iMS41IiBzdHJva2UtbGluZWNhcD0icm91bmQiLz4KPHBhdGggZD0iTTEwIDEySDEwLjAwOCIgc3Ryb2tlPSIjOUNBNEFGIiBzdHJva2Utd2lkdGg9IjEuNSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+Cjwvc3ZnPgo=';">
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @once
        @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            function initializeSlideshow(celebrationId) {
                console.log('Initializing slideshow for celebration:', celebrationId);

                const container = document.getElementById('slideshow-container-' + celebrationId);
                const loadingIndicator = document.getElementById('loading-indicator-' + celebrationId);

                if (!container) {
                    console.error('Slideshow container not found');
                    return;
                }

                const images = container.querySelectorAll('.slideshow-image');
                const thumbnails = document.querySelectorAll('.thumbnail-' + celebrationId);
                const prevBtn = document.getElementById('prev-btn-' + celebrationId);
                const nextBtn = document.getElementById('next-btn-' + celebrationId);
                const currentImageSpan = document.getElementById('current-image-' + celebrationId);
                let currentIndex = 0;
                let autoSlideInterval;
                let imagesLoaded = 0;
                const totalImages = images.length;

                console.log('Found', totalImages, 'images');

                if (totalImages === 0) {
                    console.error('No images found');
                    return;
                }

                // Hide loading indicator after a short delay
                setTimeout(() => {
                    if (loadingIndicator) {
                        loadingIndicator.style.display = 'none';
                    }
                }, 1000);

                function showImage(index) {
                    console.log('Showing image', index);

                    // Hide all images
                    images.forEach((img, i) => {
                        img.classList.remove('opacity-100', 'z-10');
                        img.classList.add('opacity-0', 'z-0');
                    });

                    // Show current image
                    if (images[index]) {
                        images[index].classList.remove('opacity-0', 'z-0');
                        images[index].classList.add('opacity-100', 'z-10');
                    }

                    // Update thumbnails
                    thumbnails.forEach((thumb, i) => {
                        thumb.classList.remove('border-primary-500', 'ring-2', 'ring-primary-200');
                        thumb.classList.add('border-gray-300');
                    });
                    if (thumbnails[index]) {
                        thumbnails[index].classList.remove('border-gray-300');
                        thumbnails[index].classList.add('border-primary-500', 'ring-2', 'ring-primary-200');
                    }

                    // Update counter
                    if (currentImageSpan) {
                        currentImageSpan.textContent = index + 1;
                    }

                    currentIndex = index;
                }

                function nextImage() {
                    const nextIndex = (currentIndex + 1) % totalImages;
                    showImage(nextIndex);
                }

                function prevImage() {
                    const prevIndex = (currentIndex - 1 + totalImages) % totalImages;
                    showImage(prevIndex);
                }

                function startAutoSlide() {
                    if (totalImages > 1) {
                        autoSlideInterval = setInterval(nextImage, 6000);
                    }
                }

                function stopAutoSlide() {
                    clearInterval(autoSlideInterval);
                }

                // Event listeners
                if (nextBtn) {
                    nextBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        nextImage();
                        stopAutoSlide();
                        setTimeout(startAutoSlide, 10000);
                    });
                }

                if (prevBtn) {
                    prevBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        prevImage();
                        stopAutoSlide();
                        setTimeout(startAutoSlide, 10000);
                    });
                }

                // Thumbnail click events
                thumbnails.forEach((thumbnail, index) => {
                    thumbnail.addEventListener('click', function(e) {
                        e.preventDefault();
                        showImage(index);
                        stopAutoSlide();
                        setTimeout(startAutoSlide, 10000);
                    });
                });

                // Keyboard navigation
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'ArrowLeft') {
                        e.preventDefault();
                        prevImage();
                    } else if (e.key === 'ArrowRight') {
                        e.preventDefault();
                        nextImage();
                    }
                });

                // Check if images are loading
                images.forEach((imageDiv, index) => {
                    const img = imageDiv.querySelector('img');
                    if (img) {
                        img.addEventListener('load', function() {
                            imagesLoaded++;
                            console.log('Image', index, 'loaded. Total loaded:', imagesLoaded);
                        });

                        img.addEventListener('error', function() {
                            console.error('Failed to load image', index, ':', img.src);
                        });
                    }
                });

                // Auto-slideshow
                if (totalImages > 1) {
                    startAutoSlide();

                    // Pause on hover
                    container.addEventListener('mouseenter', stopAutoSlide);
                    container.addEventListener('mouseleave', startAutoSlide);
                }

                // Initialize first image
                showImage(0);
                console.log('Slideshow initialized');
            }

            // Initialize slideshow for celebration {{ $celebrationId }}
            initializeSlideshow('{{ $celebrationId }}');

            // Re-initialize on Livewire updates
            document.addEventListener('livewire:navigated', function() {
                setTimeout(() => initializeSlideshow('{{ $celebrationId }}'), 100);
            });
        });
        </script>
        @endpush
        @endonce
    @else
        <div class="bg-gray-50 rounded-lg border-2 border-dashed border-gray-300 p-8 text-center">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 002 2z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Slideshow Images</h3>
            <p class="text-gray-600">This celebration doesn't have any slideshow images yet.</p>
        </div>
    @endif
</div>
