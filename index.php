

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lingunan Fitness Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/landing-page.css" rel="stylesheet">
</head>

<body>

    <?php
    include "component/landingPage-header.php"
    ?>

    <div class="overlay" id="overlay"></div>
    <main>


        <section class="hero-section" id="home">
            <div class="sliding-man"></div>

            <div class="hero-content">
                <h1 class="title-white">BUILD</h1>
                <h1 class="title-orange">YOUR BODY</h1>

                <div class="fade-only">
                    <p class="description">
                        Lingunan Gym offers a raw, motivating atmosphere that appeals to both seasoned lifters and
                        beginners.
                        You won't find any pretension here—just the sound of iron clanking and people pushing their
                        limits.
                    </p>
                    <a href="#" class="locate-btn">
                        <span class="icon">📍</span> LOCATE US
                    </a>
                </div>
            </div>
        </section>

        <section class="membership-section" id="membership">
            <div class="membership-container">

                <h2 class="section-title">MEMBERSHIPS</h2>
                <p class="section-subtitle">
                    Choose the plan that fits your lifestyle. Whether you're a casual visitor or a dedicated athlete,
                    we have a spot for you at Lingunan Fitness Gym.
                </p>

                <div class="pricing-grid">
                    <div class="price-card reveal-up">
                        <div class="card-header">
                            <h3>Walk-In</h3>
                            <p class="price">₱50 <span>/session</span></p>
                        </div>
                        <ul class="features">
                            <li>Full Equipment Access</li>
                            <li>No Commitment</li>
                            <li>Valid for entry only. Re-entry requires a new payment</li>
                        </ul>

                    </div>

                    <div class="price-card featured reveal-up">
                        <div class="card-header">
                            <h3>Monthly Pass</h3>
                            <div class="multi-price">
                                <p>1 Month — <strong>₱850</strong></p>
                                <p>3 Months — <strong>₱1800</strong></p>
                                <p>5 Months — <strong>₱2500</strong></p>
                            </div>
                        </div>
                        <ul class="features">
                            <li>Unlimited Gym Access</li>
                            <li>Free Locker Usage</li>
                            <li>Personalized Progress Tracking</li>
                            <li>Best Value for Regulars</li>
                        </ul>

                    </div>
                </div>
            </div>
        </section>

        <section class="gallery-section" id="gallery">
            <div class="gallery-container">
                <h2 class="section-title reveal-top">GYM GALLERY</h2>
                <p class="section-subtitle reveal-top">Explore our state-of-the-art facility and active community in
                    action.
                </p>

                <div class="gallery-showcase">
                    <button class="nav-btn prev" id="prevBtn">&#10094;</button>

                    <div class="card-grid" id="cardGrid">
                        <div class="gallery-card reveal-pop">
                            <img src="https://images.unsplash.com/photo-1517836357463-d25dfeac3438"
                                alt="Man powerlifting">
                            <div class="card-overlay">
                                <h3>Powerlifting Area</h3>
                            </div>
                        </div>

                        <div class="gallery-card reveal-pop">
                            <img src="https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5"
                                alt="Person running on treadmill">
                            <div class="card-overlay">
                                <h3>Cardio Zone</h3>
                            </div>
                        </div>

                        <div class="gallery-card reveal-pop">
                            <img src="https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5"
                                alt="Person running on treadmill">
                            <div class="card-overlay">
                                <h3>Cardio Zone</h3>
                            </div>
                        </div>
                        <div class="gallery-card reveal-pop">
                            <img src="https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5"
                                alt="Person running on treadmill">
                            <div class="card-overlay">
                                <h3>Cardio Zone</h3>
                            </div>
                        </div>
                        <div class="gallery-card reveal-pop">
                            <img src="https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5"
                                alt="Person running on treadmill">
                            <div class="card-overlay">
                                <h3>Cardio Zone</h3>
                            </div>
                        </div>
                        <div class="gallery-card reveal-pop">
                            <img src="https://images.unsplash.com/photo-1574680096145-d05b474e2155"
                                alt="Rows of dumbbells">
                            <div class="card-overlay">
                                <h3>Free Weights</h3>
                            </div>
                        </div>

                        <div class="gallery-card reveal-pop">
                            <img src="https://images.unsplash.com/photo-1606335543042-57c525922933"
                                alt="Person training in boxing ring">
                            <div class="card-overlay">
                                <h3>Boxing Area</h3>
                            </div>
                        </div>

                    </div>

                    <button class="nav-btn next" id="nextBtn">&#10095;</button>
                </div>
            </div>
        </section>


        <section class="location-section" id="about">
            <div class="location-container">
                <div class="location-info reveal-left">
                    <div class="location-header">
                        <img src="assets/image/location.png" alt="Location Pin" class="pin-icon">
                        <h2 class="get-directions">Get Directions</h2>
                    </div>

                    <h3 class="gym-address-title">LINGUNAN GYM FITNESS LOCATED AT;</h3>
                    <p class="address-details">
                        3 New York, Valenzuela, 1446<br>
                        Kalakhang Maynila
                    </p>

                    <a href="https://www.google.com/maps/place/LINGUNAN+FITNESS+GYM/@14.721659,120.9744507,20.25z/data=!4m6!3m5!1s0x3397b3a2cccf48dd:0xcdf8906e1f45c096!8m2!3d14.7216051!4d120.9744613!16s%2Fg%2F11y6lqy1dt?entry=ttu&g_ep=EgoyMDI2MDMxOC4xIKXMDSoASAFQAw%3D%3D"
                        target="_blank" class="map-link-btn">
                        📍 Open in Google Maps
                    </a>
                </div>

                <div class="map-wrapper reveal-right">
                    <div class="map-frame">
                        <iframe width="100%" height="450" style="border:0" loading="lazy" allowfullscreen
                            referrerpolicy="no-referrer-when-downgrade"
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3859.3475716584284!2d120.97541677583648!3d14.692829974805726!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b146467385a3%3A0x676579f1873426a3!2s3%20New%20York%2C%20Valenzuela%2C%201446%20Metro%20Manila!5e0!3m2!1sen!2sph!4v1710000000000!5m2!1sen!2sph">
                        </iframe>
                        <div class="map-overlay-edge"></div>
                    </div>
                </div>
            </div>
        </section>


      <?php 
        include "component/landingPage-footer.php"
      ?>
    </main>
    <script src="assets/js/landing-page.js"></script>
    
</body>

</html>