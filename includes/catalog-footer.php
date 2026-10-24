<footer class="catalog-footer">
    <div class="catalog-container catalog-footer-inner">
        <div class="catalog-footer-brand">
            <a
                class="catalog-brand"
                href="index.php"
                aria-label="ShineRoster home"
            >
                <img
                    src="images/ShineRoster_Logo.png"
                    alt="ShineRoster"
                    width="220"
                    height="74"
                    loading="lazy"
                >
            </a>
            <p>Your home. Your cleaner. Your choice.</p>
        </div>
        <nav
            class="catalog-footer-nav"
            aria-label="Footer navigation"
        >
            <a href="services.php">
                Services
            </a>
            <a href="providers.php">
                Find a cleaner
            </a>

            <?php if ($accountRole === 'customer'): ?>
                <a href="my-bookings.php">
                    My bookings
                </a>
            <?php endif; ?>

            <a href="index.php#contact">
                Help & contact
            </a>
        </nav>
        <p class="catalog-copyright">
            © <?= date('Y') ?> ShineRoster.
            All rights reserved.
        </p>
    </div>
</footer>
