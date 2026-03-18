// Navigation Toggle
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    
    hamburger.addEventListener('click', function() {
        navMenu.classList.toggle('active');
        hamburger.classList.toggle('active');
    });
    
    // Close menu when clicking a link
    document.querySelectorAll('.nav-menu a').forEach(link => {
        link.addEventListener('click', () => {
            navMenu.classList.remove('active');
            hamburger.classList.remove('active');
        });
    });
    
    // Hero Slider
    initHeroSlider();
    
    // Horizontal Scroll
    initHorizontalScroll();
    
    // Smooth Scrolling
    initSmoothScroll();
    
    // Video Modal
    initVideoModal();
    
    // Contact Form
    initContactForm();
});

// Hero Slider
function initHeroSlider() {
    const slides = document.querySelectorAll('.hero-slide');
    const prevBtn = document.querySelector('.prev');
    const nextBtn = document.querySelector('.next');
    const dots = document.querySelectorAll('.dot');
    let currentSlide = 0;
    let slideInterval;
    
    if (slides.length === 0) return;
    
    function showSlide(index) {
        slides.forEach(slide => slide.classList.remove('active'));
        slides[index].classList.add('active');
        
        // Update dots
        if (dots.length > 0) {
            dots.forEach(dot => dot.classList.remove('active'));
            dots[index].classList.add('active');
        }
    }
    
    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }
    
    function prevSlide() {
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        showSlide(currentSlide);
    }
    
    function goToSlide(index) {
        currentSlide = index;
        showSlide(currentSlide);
    }
    
    // Auto advance slides every 5 seconds
    slideInterval = setInterval(nextSlide, 5000);
    
    // Pause on hover
    const heroSection = document.querySelector('.hero');
    heroSection.addEventListener('mouseenter', () => clearInterval(slideInterval));
    heroSection.addEventListener('mouseleave', () => {
        slideInterval = setInterval(nextSlide, 5000);
    });
    
    // Navigation buttons
    if (prevBtn && nextBtn) {
        prevBtn.addEventListener('click', prevSlide);
        nextBtn.addEventListener('click', nextSlide);
    }
    
    // Dot navigation
    if (dots.length > 0) {
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                goToSlide(index);
                // Reset auto-advance timer
                clearInterval(slideInterval);
                slideInterval = setInterval(nextSlide, 5000);
            });
        });
    }
}

// Rotating Words Animation
function initRotatingWords() {
    const words = document.querySelectorAll('.word');
    if (words.length === 0) return;
    
    setInterval(() => {
        words.forEach((word, index) => {
            setTimeout(() => {
                word.style.opacity = '1';
                word.style.transform = 'translateY(0)';
            }, index * 3000);
        });
    }, 9000);
}

// Horizontal Scroll with Infinite Animation
function initHorizontalScroll() {
    const scrollContainers = document.querySelectorAll('.scroll-container');
    
    scrollContainers.forEach(container => {
        const wrapper = container.querySelector('.scroll-wrapper');
        let isDown = false;
        let startX;
        let scrollLeft;
        let animationPaused = false;
        
        // Pause animation on hover
        container.addEventListener('mouseenter', () => {
            if (!isDown) {
                wrapper.style.animationPlayState = 'paused';
                animationPaused = true;
            }
        });
        
        container.addEventListener('mouseleave', () => {
            if (!isDown && animationPaused) {
                wrapper.style.animationPlayState = 'running';
                animationPaused = false;
            }
        });
        
        // Mouse drag functionality
        wrapper.addEventListener('mousedown', (e) => {
            isDown = true;
            wrapper.style.cursor = 'grabbing';
            wrapper.style.animationPlayState = 'paused';
            startX = e.pageX - wrapper.offsetLeft;
            scrollLeft = wrapper.scrollLeft;
        });
        
        wrapper.addEventListener('mouseleave', () => {
            if (isDown) {
                isDown = false;
                wrapper.style.cursor = 'grab';
                if (!animationPaused) {
                    wrapper.style.animationPlayState = 'running';
                }
            }
        });
        
        wrapper.addEventListener('mouseup', () => {
            isDown = false;
            wrapper.style.cursor = 'grab';
            if (!animationPaused) {
                wrapper.style.animationPlayState = 'running';
            }
        });
        
        wrapper.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - wrapper.offsetLeft;
            const walk = (x - startX) * 2;
            wrapper.scrollLeft = scrollLeft - walk;
        });
        
        // Touch events for mobile
        wrapper.addEventListener('touchstart', (e) => {
            isDown = true;
            wrapper.style.animationPlayState = 'paused';
            startX = e.touches[0].pageX - wrapper.offsetLeft;
            scrollLeft = wrapper.scrollLeft;
        });
        
        wrapper.addEventListener('touchend', () => {
            isDown = false;
            if (!animationPaused) {
                wrapper.style.animationPlayState = 'running';
            }
        });
        
        wrapper.addEventListener('touchmove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.touches[0].pageX - wrapper.offsetLeft;
            const walk = (x - startX) * 2;
            wrapper.scrollLeft = scrollLeft - walk;
        });
        
        // Reset animation when user manually scrolls
        wrapper.addEventListener('scroll', () => {
            if (isDown) {
                wrapper.style.animationPlayState = 'paused';
            }
        });
    });
    
    // Manual scroll controls
    const scrollPrevButtons = document.querySelectorAll('.scroll-prev');
    const scrollNextButtons = document.querySelectorAll('.scroll-next');
    
    scrollPrevButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const container = button.closest('.scroll-container');
            const wrapper = container ? container.querySelector('.scroll-wrapper') : null;
            if (wrapper) {
                // Pause animation and scroll left
                wrapper.style.animationPlayState = 'paused';
                wrapper.scrollBy({
                    left: -300,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    scrollNextButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const container = button.closest('.scroll-container');
            const wrapper = container ? container.querySelector('.scroll-wrapper') : null;
            if (wrapper) {
                // Pause animation and scroll right
                wrapper.style.animationPlayState = 'paused';
                wrapper.scrollBy({
                    left: 300,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Smooth Scrolling
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Video Modal
function initVideoModal() {
    const modal = document.getElementById('videoModal');
    const modalContent = document.querySelector('.modal-content');
    
    // Handle video playback (same as video-manager.php)
    window.playVideo = function(id) {
        // Get video data - this would need to be passed from PHP or fetched via AJAX
        // For now, we'll use a simplified approach
        const videoData = window.videoData ? window.videoData.find(v => v.id == id) : null;
        
        if (!videoData) {
            console.error('Video data not found for ID:', id);
            return;
        }
        
        const playerContainer = document.getElementById('playerContainer');
        const playerInfo = document.getElementById('playerInfo');
        
        let playerHtml = '';
        
        if (videoData.video_path) {
            playerHtml = `
                <video controls autoplay style="width: 100%;">
                    <source src="${videoData.video_path}" type="video/mp4">
                </video>
            `;
        } else if (videoData.video_url) {
            if (videoData.video_url.includes('youtube') || videoData.video_url.includes('youtu.be')) {
                // Extract YouTube ID
                let videoId = '';
                const match = videoData.video_url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/);
                if (match) {
                    videoId = match[1];
                    playerHtml = `
                        <iframe width="100%" height="500" 
                                src="https://www.youtube.com/embed/${videoId}?autoplay=1" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen>
                        </iframe>
                    `;
                }
            } else if (videoData.video_url.includes('vimeo')) {
                // Extract Vimeo ID
                const match = videoData.video_url.match(/(?:vimeo\.com\/(?:video\/)?(\d+))/);
                if (match) {
                    const videoId = match[1];
                    playerHtml = `
                        <iframe width="100%" height="500" 
                                src="https://player.vimeo.com/video/${videoId}?autoplay=1" 
                                frameborder="0" 
                                allow="autoplay; fullscreen; picture-in-picture" 
                                allowfullscreen>
                        </iframe>
                    `;
                }
            }
        }
        
        modal.style.display = 'block';
        modalContent.innerHTML = `
            <span class="close">&times;</span>
            <div id="playerContainer">${playerHtml}</div>
            <div id="playerInfo" class="video-info">
                <h3>${videoData.title}</h3>
                <p>${videoData.description || ''}</p>
            </div>
        `;
        
        // Increment view count
        fetch('ajax/increment-view.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + id
        }).catch(err => console.log('View increment failed:', err));
    };
    
    // Close modal
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('close') || e.target === modal) {
            modal.style.display = 'none';
            modalContent.innerHTML = `
                <span class="close">&times;</span>
                <video id="modalVideo" controls>
                    <source src="" type="video/mp4">
                </video>
            `;
        }
    });
    
    // Handle video card clicks (for backward compatibility)
    document.addEventListener('click', (e) => {
        const videoCard = e.target.closest('.video-card');
        if (videoCard) {
            const videoId = videoCard.getAttribute('data-video-id');
            if (videoId) {
                playVideo(videoId);
            }
        }
    });
}

// Contact Form
function initContactForm() {
    const form = document.getElementById('contactForm');
    
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            formData.append('action', 'contact');
            
            try {
                const response = await fetch('process-contact.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Thank you for your message. We will get back to you soon!');
                    form.reset();
                } else {
                    alert('Error sending message. Please try again.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error sending message. Please try again.');
            }
        });
    }
}

// Sticky Navigation
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 100) {
        navbar.style.background = 'rgba(255,255,255,0.95)';
        navbar.style.backdropFilter = 'blur(10px)';
    } else {
        navbar.style.background = 'var(--white)';
        navbar.style.backdropFilter = 'none';
    }
});

// Lazy Loading Images
const images = document.querySelectorAll('img[data-src]');
const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
            imageObserver.unobserve(img);
        }
    });
});

images.forEach(img => imageObserver.observe(img));