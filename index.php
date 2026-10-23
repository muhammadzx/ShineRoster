<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Contact.php';
$contactError = '';
$contactSuccess = $_SESSION['contact_success'] ?? '';
unset($_SESSION['contact_success']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!validCsrf($_POST['csrf_token'] ?? null)) { http_response_code(403); throw new RuntimeException('The form expired. Reload and try again.'); }
        if (time() - ($_SESSION['contact_sent'] ?? 0) < 60) throw new RuntimeException('Please wait a minute before sending another message.');
        if (!$auth->allowAttempt('contact', 5)) { http_response_code(429); throw new RuntimeException('Too many messages. Try again in 15 minutes.'); }
        (new Contact($db))->send($_POST);
        $_SESSION['contact_sent'] = time();
        $_SESSION['contact_success'] = 'Your message has been received. Thank you.';
        header('Location: index.php#contact', true, 303); exit;
    } catch (PDOException $e) {
        error_log((string)$e); $contactError = 'Your message could not be saved. Please try again later.';
    } catch (RuntimeException $e) { $contactError = $e->getMessage(); }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ShineRoster | Find Your Home’s Perfect Clean</title>

    <meta name="description"
        content="Compare home cleaning providers, explore their skills and customer reviews, and book an available appointment with ShineRoster.">

    <meta name="theme-color" content="#0F766E">

    <link rel="icon" href="images/favicon.ico" sizes="any">
    <link rel="icon" href="images/favicon.png" type="image/png">

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/landing.css">
</head>

<body class="landing-page">

    <a class="skip-link" href="#main-content">Skip to content</a>

    <!-- HEADER -->
    <header class="site-header">
        <div class="container header-inner">

            <a class="brand" href="index.php" aria-label="ShineRoster home">
                <img src="images/ShineRoster_Logo.png" alt="ShineRoster" width="220" height="74">
            </a>

            <nav class="site-nav" aria-label="Main navigation">
                <a href="index.php" aria-current="page">Home</a>
                <a href="services.php">Services</a>
                <a href="providers.php">Find a cleaner</a>
                <a href="#how-it-works">How it works</a>
                <a href="index.php#contact">Contact us</a>
            </nav>

            <div class="header-actions">
                <a class="login-link" href="login.php">Log in</a>
                <a class="btn btn-primary btn-small" href="register.php">
                    Get started <span aria-hidden="true">↗</span>
                </a>
            </div>

        </div>
    </header>

    <main id="main-content">

        <!-- HERO -->
        <section class="hero" aria-labelledby="hero-title">
            <div class="container hero-grid">

                <div class="hero-content">
                    <span class="eyebrow">
                        <span class="status-dot" aria-hidden="true"></span>
                        A fresh start for your home
                    </span>

                    <h1 id="hero-title">
                        A cleaner home.<br>
                        A little more <span>you time.</span>
                    </h1>

                    <p class="hero-description">
                        Find the right cleaning provider for your home.
                        Compare skills, explore customer reviews, and book
                        a time that fits your day.
                    </p>

                    <div class="hero-actions">
                        <a class="btn btn-primary" href="providers.php">
                            Find your cleaner
                            <span aria-hidden="true">↗</span>
                        </a>

                        <a class="btn btn-secondary" href="services.php">
                            Explore services
                        </a>
                    </div>

                    <ul class="hero-benefits" aria-label="Booking benefits">
                        <li><span aria-hidden="true">✓</span> Compare providers</li>
                        <li><span aria-hidden="true">✓</span> See prices upfront</li>
                        <li><span aria-hidden="true">✓</span> Choose your time</li>
                    </ul>
                </div>

                <div class="hero-visual">
                    <div class="hero-photo">
                        <img src="images/living-room.jpg"
                            alt="Bright living room with a comfortable sofa, plants, and natural light" width="1100"
                            height="1100" fetchpriority="high">

                        <div class="photo-caption">
                            <span class="caption-sparkle" aria-hidden="true">✦</span>
                            A space to feel good in.
                        </div>
                    </div>

                    <div class="floating-note floating-note-top" aria-hidden="true">
                        <span class="note-icon">✦</span>
                        <div>
                            <strong>Fresh home, clear mind</strong>
                            <span>Make room for what matters.</span>
                        </div>
                    </div>

                    <div class="floating-note floating-note-bottom">
                        <span class="note-check" aria-hidden="true">✓</span>
                        <div>
                            <strong>Your clean. Your choice.</strong>
                            <span>The right provider, on your schedule.</span>
                        </div>
                    </div>

                    <span class="hero-decoration" aria-hidden="true">✳</span>
                </div>

            </div>
        </section>

        <!-- BENEFITS STRIP -->
        <section class="benefits-strip" aria-label="Why book with ShineRoster">
            <div class="container benefits-grid">

                <div class="benefit-item">
                    <span class="benefit-icon" aria-hidden="true">◎</span>
                    <div>
                        <strong>Find your fit</strong>
                        <p>Compare skills and services.</p>
                    </div>
                </div>

                <div class="benefit-item">
                    <span class="benefit-icon" aria-hidden="true">☆</span>
                    <div>
                        <strong>Reviews that help</strong>
                        <p>Feedback after completed bookings.</p>
                    </div>
                </div>

                <div class="benefit-item">
                    <span class="benefit-icon" aria-hidden="true">▦</span>
                    <div>
                        <strong>A time that works</strong>
                        <p>Choose an available appointment.</p>
                    </div>
                </div>

            </div>
        </section>

        <!-- SERVICES -->
        <section class="section services-section" aria-labelledby="services-title">
            <div class="container">

                <div class="section-heading section-heading-row">
                    <div>
                        <span class="eyebrow">A clean for every need</span>
                        <h2 id="services-title">
                            Your home, cared for.
                        </h2>
                        <p>
                            From everyday upkeep to a fresh start,
                            explore the service that suits your space.
                        </p>
                    </div>

                    <a class="text-link" href="services.php">
                        Explore all services
                        <span aria-hidden="true">↗</span>
                    </a>
                </div>

                <div class="service-grid">

                    <article class="service-card">
                        <div class="service-art service-art-home" aria-hidden="true">
                            <svg viewBox="0 0 100 100" fill="none">
                                <path d="M20 46 50 21 80 46"></path>
                                <path d="M28 42v36h44V42"></path>
                                <path d="M43 78V58h14v20"></path>
                                <path d="M76 16v14M69 23h14"></path>
                            </svg>
                        </div>

                        <div class="service-card-body">
                            <span class="service-label">Everyday freshness</span>
                            <h3>Regular home cleaning</h3>
                            <p>
                                Keep your living spaces fresh with routine
                                cleaning for the rooms you use every day.
                            </p>
                            <a class="text-link" href="services.php">
                                Explore services
                                <span aria-hidden="true">↗</span>
                            </a>
                        </div>
                    </article>

                    <article class="service-card">
                        <div class="service-art service-art-deep" aria-hidden="true">
                            <svg viewBox="0 0 100 100" fill="none">
                                <path d="M47 17v22M36 28h22"></path>
                                <path d="M72 40v14M65 47h14"></path>
                                <path d="m25 71 32-32 12 12-32 32Z"></path>
                                <path d="m47 49 12 12"></path>
                                <path d="M25 24v10M20 29h10"></path>
                            </svg>
                        </div>

                        <div class="service-card-body">
                            <span class="service-label">A little extra care</span>
                            <h3>Deep cleaning</h3>
                            <p>
                                Give overlooked corners and busy spaces
                                more attention with a thorough clean.
                            </p>
                            <a class="text-link" href="services.php">
                                Explore services
                                <span aria-hidden="true">↗</span>
                            </a>
                        </div>
                    </article>

                    <article class="service-card">
                        <div class="service-art service-art-move" aria-hidden="true">
                            <svg viewBox="0 0 100 100" fill="none">
                                <path d="m22 39 28-14 28 14-28 14Z"></path>
                                <path d="M22 39v31l28 14 28-14V39"></path>
                                <path d="M50 53v31M36 32l28 14"></path>
                                <path d="M80 15v14M73 22h14"></path>
                            </svg>
                        </div>

                        <div class="service-card-body">
                            <span class="service-label">New chapter, fresh space</span>
                            <h3>Moving home cleaning</h3>
                            <p>
                                Prepare a home for your next chapter,
                                whether you are moving in or moving out.
                            </p>
                            <a class="text-link" href="services.php">
                                Explore services
                                <span aria-hidden="true">↗</span>
                            </a>
                        </div>
                    </article>

                </div>
            </div>
        </section>

        <!-- HOW IT WORKS -->
        <section class="section how-section" id="how-it-works" aria-labelledby="how-title">
            <div class="container">

                <div class="section-heading section-heading-center">
                    <span class="eyebrow">Less effort. More ease.</span>
                    <h2 id="how-title">Your next clean, in three steps.</h2>
                    <p>
                        A simple way to find help and make time for yourself.
                    </p>
                </div>

                <ol class="steps-grid">

                    <li class="step-card">
                        <span class="step-number">01</span>
                        <h3>Find your provider</h3>
                        <p>
                            Explore profiles, compare skills,
                            and read reviews from completed bookings.
                        </p>
                    </li>

                    <li class="step-card">
                        <span class="step-number">02</span>
                        <h3>Pick your appointment</h3>
                        <p>
                            Choose a service and an available time,
                            then add your home address.
                        </p>
                    </li>

                    <li class="step-card">
                        <span class="step-number">03</span>
                        <h3>Enjoy your fresh space</h3>
                        <p>
                            Confirm your booking, follow its status,
                            and share your review after the visit.
                        </p>
                    </li>

                </ol>

                <div class="section-action">
                    <a class="btn btn-primary" href="providers.php">
                        Find your cleaner
                        <span aria-hidden="true">↗</span>
                    </a>
                </div>

            </div>
        </section>

        <!-- WHY SHINEROSTER -->
        <section class="section why-section" aria-labelledby="why-title">
            <div class="container why-grid">

                <div class="why-visual">
                    <img src="images/clean-home.jpg"
                        alt="Calm, light-filled home interior with natural finishes" width="1000" height="1100"
                        loading="lazy" decoding="async">

                    <div class="why-image-note">
                        <span aria-hidden="true">✦</span>
                        <p>A little help.<br><strong>A lot more breathing room.</strong></p>
                    </div>
                </div>

                <div class="why-content">
                    <span class="eyebrow">Made around your needs</span>
                    <h2 id="why-title">
                        Feel good about<br>who you choose.
                    </h2>

                    <p class="section-intro">
                        Your home is personal. ShineRoster helps you
                        make an informed choice before you book.
                    </p>

                    <ul class="feature-list">

                        <li>
                            <span class="feature-check" aria-hidden="true">✓</span>
                            <div>
                                <h3>Get to know your provider</h3>
                                <p>
                                    Explore their profile, listed skills,
                                    and cleaning services.
                                </p>
                            </div>
                        </li>

                        <li>
                            <span class="feature-check" aria-hidden="true">✓</span>
                            <div>
                                <h3>Know the details upfront</h3>
                                <p>
                                    Review the service price, duration,
                                    and appointment before confirming.
                                </p>
                            </div>
                        </li>

                        <li>
                            <span class="feature-check" aria-hidden="true">✓</span>
                            <div>
                                <h3>Keep your bookings together</h3>
                                <p>
                                    View upcoming and past visits
                                    from your account in one place.
                                </p>
                            </div>
                        </li>

                    </ul>

                    <a class="text-link" href="providers.php">
                        Meet the providers
                        <span aria-hidden="true">↗</span>
                    </a>
                </div>

            </div>
        </section>

        <!-- PROVIDER CTA -->
        <section class="provider-section" aria-labelledby="provider-title">
            <div class="container">
                <div class="provider-banner">

                    <div class="provider-banner-content">
                        <span class="eyebrow">For cleaning providers</span>

                        <h2 id="provider-title">
                            Bring your skills.<br>
                            Build your next opportunity.
                        </h2>

                        <p>
                            Create a provider account, showcase your services,
                            set your available appointments, and manage
                            your bookings in one place.
                        </p>

                        <a class="btn btn-white" href="register.php">
                            Join as a provider
                            <span aria-hidden="true">↗</span>
                        </a>
                    </div>

                    <div class="provider-illustration" aria-hidden="true">
                        <div class="illustration-orbit"></div>

                        <div class="illustration-card">
                            <span class="illustration-avatar">✦</span>
                            <span class="illustration-line illustration-line-long"></span>
                            <span class="illustration-line"></span>

                            <div class="illustration-calendar">
                                <span></span><span></span><span></span>
                                <span></span><span></span><span></span>
                                <span></span><span></span><span></span>
                            </div>
                        </div>

                        <span class="illustration-check">✓</span>
                    </div>

                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section class="section faq-section" aria-labelledby="faq-title">
            <div class="container faq-grid">

                <div class="section-heading">
                    <span class="eyebrow">A few helpful answers</span>
                    <h2 id="faq-title">
                        Before you<br>book your clean.
                    </h2>
                    <p>
                        Get familiar with how booking on ShineRoster works.
                    </p>
                </div>

                <div class="faq-list">

                    <details class="faq-item">
                        <summary>How do I choose a cleaning provider?</summary>
                        <p>
                            Visit the provider directory to compare profiles,
                            listed skills, services, and customer reviews.
                            Open a provider’s profile to explore their services
                            and available appointments.
                        </p>
                    </details>

                    <details class="faq-item">
                        <summary>Do I need an account to book?</summary>
                        <p>
                            Yes. You can explore services and providers without
                            signing in, but you need a customer account to
                            confirm a booking and manage your visits.
                        </p>
                    </details>

                    <details class="faq-item">
                        <summary>Can I cancel my booking?</summary>
                        <p>
                            You can cancel a confirmed booking from My bookings
                            before its scheduled start time. Open the booking
                            details to see the available action.
                        </p>
                    </details>

                    <details class="faq-item">
                        <summary>When can I leave a review?</summary>
                        <p>
                            Once your booking is marked as completed, you can
                            leave one rating and an optional comment about
                            your experience.
                        </p>
                    </details>

                    <details class="faq-item">
                        <summary>How can I offer my cleaning services?</summary>
                        <p>
                            Create an account and select the provider role.
                            Complete your profile, add your skills and services,
                            then publish your available appointments.
                        </p>
                    </details>

                </div>

            </div>
        </section>

        <!-- CONTACT -->
<section
    class="section contact-section"
    id="contact"
    aria-labelledby="contact-title"
>
    <div class="container contact-grid">

        <div class="contact-content">
            <span class="eyebrow">Let’s talk</span>

            <h2 id="contact-title">
                A question?<br>
                We’re here to help.
            </h2>

            <p class="contact-description">
                Need help with a booking, your account, or becoming
                a cleaning provider? Send us your message.
            </p>

            <div class="contact-info-card">
                <span class="contact-info-icon" aria-hidden="true">✉</span>

                <div>
                    <h3>A little detail goes a long way</h3>
                    <p>
                        Tell us what you need help with.
                        If your message concerns a booking,
                        include its booking number.
                    </p>
                </div>
            </div>

            <div class="contact-info-card">
                <span class="contact-info-icon" aria-hidden="true">✓</span>

                <div>
                    <h3>Keep your information safe</h3>
                    <p>
                        Please do not include your password
                        or payment information in your message.
                    </p>
                </div>
            </div>

            <a class="text-link" href="#faq-title">
                Explore frequently asked questions
                <span aria-hidden="true">↗</span>
            </a>
        </div>

        <form
            class="contact-form"
            action="index.php#contact"
            method="post"
        >
            <input type="hidden" name="form_type" value="contact">
            <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">
            <?php if ($contactError !== ''): ?><p role="alert"><?= escapeHtml($contactError) ?></p><?php endif; ?>
            <?php if ($contactSuccess !== ''): ?><p role="status"><?= escapeHtml($contactSuccess) ?></p><?php endif; ?>

            <div class="contact-form-heading">
                <h3>Send us a message</h3>
                <p>
                    Fields marked with
                    <span class="required-mark">*</span>
                    are required.
                </p>
            </div>

            <div class="contact-form-row">
                <div class="contact-field">
                    <label for="contact-name">
                        Full name
                        <span class="required-mark" aria-hidden="true">*</span>
                    </label>

                    <input
                        type="text"
                        id="contact-name"
                        name="full_name"
                        placeholder="Your full name"
                        autocomplete="name"
                        maxlength="100"
                        required
                    >
                </div>

                <div class="contact-field">
                    <label for="contact-email">
                        Email address
                        <span class="required-mark" aria-hidden="true">*</span>
                    </label>

                    <input
                        type="email"
                        id="contact-email"
                        name="email"
                        placeholder="you@example.com"
                        autocomplete="email"
                        maxlength="190"
                        required
                    >
                </div>
            </div>

            <div class="contact-form-row">
                <div class="contact-field">
                    <label for="contact-phone">
                        Phone number
                        <span class="contact-optional">(optional)</span>
                    </label>

                    <input
                        type="tel"
                        id="contact-phone"
                        name="phone"
                        placeholder="Your phone number"
                        autocomplete="tel"
                        maxlength="20"
                    >
                </div>

                <div class="contact-field">
                    <label for="contact-subject">
                        Topic
                        <span class="required-mark" aria-hidden="true">*</span>
                    </label>

                    <select
                        id="contact-subject"
                        name="subject"
                        required
                    >
                        <option value="" selected disabled>
                            Select a topic
                        </option>
                        <option value="booking">Booking support</option>
                        <option value="account">Account support</option>
                        <option value="provider">Becoming a provider</option>
                        <option value="feedback">Feedback</option>
                        <option value="other">Other question</option>
                    </select>
                </div>
            </div>

            <div class="contact-field">
                <label for="contact-message">
                    Your message
                    <span class="required-mark" aria-hidden="true">*</span>
                </label>

                <textarea
                    id="contact-message"
                    name="message"
                    rows="6"
                    minlength="10"
                    maxlength="3000"
                    placeholder="How can we help you?"
                    aria-describedby="contact-message-hint"
                    required
                ></textarea>

                <p class="contact-field-hint" id="contact-message-hint">
                    Between 10 and 3,000 characters.
                </p>
            </div>

            <div class="contact-form-bottom">
                <p>
                    We’ll use your contact details to respond
                    to this request.
                </p>

                <button
                    class="btn btn-primary"
                    type="submit"
                    name="send_message"
                    value="1"
                >
                    Send message
                    <span aria-hidden="true">↗</span>
                </button>
            </div>
        </form>

    </div>
</section>

        <!-- FINAL CTA -->
        <section class="final-cta" aria-labelledby="final-title">
            <div class="container final-cta-inner">
                <span class="final-sparkle" aria-hidden="true">✦</span>
                <h2 id="final-title">Make space for a better day.</h2>
                <p>
                    Your home. Your cleaner. Your choice.
                </p>
                <a class="btn btn-primary" href="providers.php">
                    Find your cleaner
                    <span aria-hidden="true">↗</span>
                </a>
            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <footer class="site-footer">
        <div class="container">

            <div class="footer-grid">

                <div class="footer-brand">
                    <a class="brand" href="index.php" aria-label="ShineRoster home">
                        <img src="images/ShineRoster_Logo.png" alt="ShineRoster" width="220" height="74" loading="lazy">
                    </a>

                    <p>
                        Connecting homes with cleaning providers,
                        one fresh start at a time.
                    </p>
                </div>

                <nav class="footer-column" aria-label="Explore">
                    <h3>Explore</h3>
                    <a href="services.php">Cleaning services</a>
                    <a href="providers.php">Find a cleaner</a>
                    <a href="#how-it-works">How it works</a>
                    <a href="index.php#contact">Contact us</a>
                </nav>

                <nav class="footer-column" aria-label="Your account">
                    <h3>Your account</h3>
                    <a href="login.php">Log in</a>
                    <a href="register.php">Create an account</a>
                    <a href="my-bookings.php">My bookings</a>
                </nav>

                <div class="footer-column footer-provider">
                    <h3>Work with ShineRoster</h3>
                    <p>
                        Turn your cleaning skills into new opportunities.
                    </p>
                    <a class="text-link" href="register.php">
                        Become a provider
                        <span aria-hidden="true">↗</span>
                    </a>
                </div>

            </div>

            <div class="footer-bottom">
                <p>© 2026 ShineRoster. All rights reserved.</p>
                <a href="#main-content">
                    Back to top <span aria-hidden="true">↑</span>
                </a>
            </div>

        </div>
    </footer>

</body>

</html>