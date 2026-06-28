document.addEventListener('DOMContentLoaded', () => {
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.getElementById('navMenu');
    const closeBtn = document.getElementById('closeBtn');
    const overlay = document.getElementById('overlay');

    function openMenu() {
        if (!navMenu) return;
        navMenu.classList.add('active');
        if (overlay) {
            overlay.classList.add('active');
        }
        document.body.style.overflow = 'hidden';
    }

    function closeMenu() {
        if (!navMenu) return;
        navMenu.classList.remove('active');
        if (overlay) {
            overlay.classList.remove('active');
        }
        document.body.style.overflow = '';
    }

    if (hamburger && navMenu) {
        hamburger.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            openMenu();
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', closeMenu);
        }

        if (overlay) {
            overlay.addEventListener('click', closeMenu);
        }

        document.querySelectorAll('.nav-menu a').forEach((link) => {
            link.addEventListener('click', closeMenu);
        });
    }

    const sections = document.querySelectorAll('.hero-section, .membership-section, .gallery-section, .location-section');
    if (!sections.length) {
        return;
    }

    document.body.classList.add('is-visible-nav');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
            } else {
                entry.target.classList.remove('is-visible');
            }
        });
    }, { threshold: 0.1 });

    const heroSection = document.querySelector('.hero-section');
    if (heroSection) {
        observer.observe(heroSection);
    }

    document.querySelectorAll('.hero-section, .membership-section').forEach((section) => {
        observer.observe(section);
    });

    sections.forEach((section) => {
        observer.observe(section);
    });

    const grid = document.getElementById('cardGrid');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');

    if (grid) {
        const cards = Array.from(grid.children);
        cards.forEach((card) => {
            const clone = card.cloneNode(true);
            grid.appendChild(clone);
        });

        let autoScrollTimer;
        const scrollAmount = 330;

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                grid.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                grid.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            });
        }

        function startAutoRotation() {
            autoScrollTimer = window.setInterval(() => {
                if (grid.scrollLeft >= grid.scrollWidth / 2) {
                    grid.scrollTo({ left: 0, behavior: 'auto' });
                }
                grid.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            }, 3000);
        }

        function stopAutoRotation() {
            clearInterval(autoScrollTimer);
        }

        let autoScroll;

        function startAutoScroll() {
            autoScroll = window.setInterval(() => {
                if (grid.scrollLeft + grid.clientWidth >= grid.scrollWidth - 10) {
                    grid.scrollTo({ left: 0, behavior: 'smooth' });
                } else {
                    grid.scrollBy({ left: scrollAmount, behavior: 'smooth' });
                }
            }, 4000);
        }

        function stopAutoScroll() {
            clearInterval(autoScroll);
        }

        startAutoScroll();
        startAutoRotation();
        grid.addEventListener('mouseenter', stopAutoRotation);
        grid.addEventListener('mouseleave', startAutoRotation);
        grid.addEventListener('mouseenter', stopAutoScroll);
        grid.addEventListener('mouseleave', startAutoScroll);
    }

    const backToTop = document.getElementById('backToTop');
    if (backToTop) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 500) {
                backToTop.style.opacity = '1';
                backToTop.style.visibility = 'visible';
            } else {
                backToTop.style.opacity = '0';
                backToTop.style.visibility = 'hidden';
            }
        });
    }

    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener('click', function (event) {
            const targetId = this.getAttribute('href');
            if (!targetId || targetId === '#') {
                return;
            }
            event.preventDefault();
            const target = document.querySelector(targetId);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});
