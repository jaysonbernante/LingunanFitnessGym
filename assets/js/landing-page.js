const sections = document.querySelectorAll('.hero-section, .membership-section, .gallery-section, .location-section');
        const hamburger = document.getElementById('hamburger');
        const navMenu = document.getElementById('navMenu');
        const closeBtn = document.getElementById('closeBtn');
        const overlay = document.getElementById('overlay');
        window.addEventListener('load', () => {

            document.body.classList.add('is-visible-nav');
        });


        function openMenu() {
            navMenu.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeMenu() {
            navMenu.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        hamburger.addEventListener('click', openMenu);
        closeBtn.addEventListener('click', closeMenu);
        overlay.addEventListener('click', closeMenu);


        document.querySelectorAll('.nav-menu a').forEach(link => {
            link.addEventListener('click', closeMenu);
        });



        const observerOptions = {
            threshold: 0.2
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                } else {
                    entry.target.classList.remove('is-visible');
                }
            });
        }, {
            threshold: 0.1
        });

        const heroSection = document.querySelector('.hero-section');
        observer.observe(heroSection);

        const heroObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                } else {
                    entry.target.classList.remove('is-visible');
                }
            });
        }, {
            threshold: 0.25
        });

        heroObserver.observe(document.querySelector('.hero-section'));


        document.querySelectorAll('.hero-section, .membership-section').forEach(section => {
            observer.observe(section);
        });

        sections.forEach(section => {
            observer.observe(section);
        });

        const grid = document.getElementById('cardGrid');
        const cards = Array.from(grid.children);
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        cards.forEach(card => {
            const clone = card.cloneNode(true);
            grid.appendChild(clone);
        });

        let autoScrollTimer;
        const scrollAmount = 330;



        nextBtn.addEventListener('click', () => {
            grid.scrollBy({
                left: scrollAmount,
                behavior: 'smooth'
            });
        });

        prevBtn.addEventListener('click', () => {
            grid.scrollBy({
                left: -scrollAmount,
                behavior: 'smooth'
            });
        });

        function startAutoRotation() {
            autoScrollTimer = setInterval(() => {
                if (grid.scrollLeft >= grid.scrollWidth / 2) {
                    grid.scrollTo({
                        left: 0,
                        behavior: 'auto'
                    });
                }

                grid.scrollBy({
                    left: scrollAmount,
                    behavior: 'smooth'
                });

            }, 3000);
        }

        function stopAutoRotation() {
            clearInterval(autoScrollTimer);
        }


        // --- AUTO-MOTION LOGIC ---
        let autoScroll;

        function startAutoScroll() {
            autoScroll = setInterval(() => {

                if (grid.scrollLeft + grid.clientWidth >= grid.scrollWidth - 10) {
                    grid.scrollTo({
                        left: 0,
                        behavior: 'smooth'
                    });
                } else {

                    grid.scrollBy({
                        left: scrollAmount,
                        behavior: 'smooth'
                    });
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


        sections.forEach(section => {
            observer.observe(section);
        });

        const backToTop = document.getElementById('backToTop');

        window.addEventListener('scroll', () => {
            if (window.scrollY > 500) {
                backToTop.style.opacity = '1';
                backToTop.style.visibility = 'visible';
            } else {
                backToTop.style.opacity = '0';
                backToTop.style.visibility = 'hidden';
            }
        });

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });


        const navLinks = document.querySelectorAll('.nav-menu ul li a');

        // Open Menu
        hamburger.addEventListener('click', () => {
            navMenu.classList.add('active');
        });

        // Close Menu (using the X button)
        closeBtn.addEventListener('click', () => {
            navMenu.classList.remove('active');
        });

        // Close Menu (when a link is clicked - important for mobile UX)
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
            });
        });