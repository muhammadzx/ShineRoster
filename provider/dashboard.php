<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Provider Dashboard | ShineRoster</title>

    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0F766E">

    <link rel="icon" href="../images/favicon.ico" sizes="any">
    <link rel="icon" href="../images/favicon.png" type="image/png">

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>

<body class="dashboard-page">

    <a class="skip-link" href="#dashboard-main">Skip to content</a>

    <div class="dashboard-layout">

        <?php require_once __DIR__ . "/../includes/sidebar.php"?>

        <div class="dashboard-workspace">

             <?php require_once __DIR__ . "/../includes/header.php"?>

            <!-- ================= PAGE CONTENT START ================= -->
            <main class="dashboard-main" id="dashboard-main">

                <div class="dashboard-page-heading">

                    <div>
                        <span class="eyebrow">Your business at a glance</span>

                        <h1>
                            Welcome back, Ahmad
                            <span aria-hidden="true">✦</span>
                        </h1>

                        <p>
                            Here’s what’s happening with your cleaning services.
                        </p>
                    </div>

                    <a class="btn btn-primary" href="slots.php">
                        Manage appointments
                        <span aria-hidden="true">↗</span>
                    </a>

                </div>

                <p class="dashboard-demo-note">
                    <strong>Preview:</strong>
                    These figures and bookings are sample data.
                </p>

                <!-- SUMMARY CARDS -->
                <section class="stats-grid" aria-label="Booking summary">

                    <article class="stat-card">
                        <div class="stat-card-top">
                            <span>Total bookings</span>
                            <span class="stat-icon" aria-hidden="true">▣</span>
                        </div>

                        <strong class="stat-value">24</strong>
                        <p>All booking statuses</p>
                    </article>

                    <article class="stat-card">
                        <div class="stat-card-top">
                            <span>Upcoming bookings</span>
                            <span class="stat-icon" aria-hidden="true">▤</span>
                        </div>

                        <strong class="stat-value">6</strong>
                        <p>Confirmed future visits</p>
                    </article>

                    <article class="stat-card">
                        <div class="stat-card-top">
                            <span>Completed bookings</span>
                            <span class="stat-icon" aria-hidden="true">✓</span>
                        </div>

                        <strong class="stat-value">16</strong>
                        <p>Finished cleaning visits</p>
                    </article>

                    <article class="stat-card">
                        <div class="stat-card-top">
                            <span>Cancelled bookings</span>
                            <span class="stat-icon" aria-hidden="true">×</span>
                        </div>

                        <strong class="stat-value">2</strong>
                        <p>Cancelled reservations</p>
                    </article>

                </section>

                <!-- NEXT VISIT + QUICK ACTIONS -->
                <div class="dashboard-content-grid">

                    <section
                        class="next-visit"
                        aria-labelledby="next-visit-title"
                    >
                        <div class="next-visit-top">

                            <span class="next-visit-label">
                                <span aria-hidden="true">●</span>
                                Next scheduled visit
                            </span>

                            <span class="status-badge status-confirmed">
                                Confirmed
                            </span>

                        </div>

                        <h2 id="next-visit-title">Regular home cleaning</h2>

                        <p class="next-visit-customer">
                            For Muhammad Sami · Booking #1042
                        </p>

                        <dl class="visit-details">

                            <div>
                                <dt>Date</dt>
                                <dd>
                                    <time datetime="2026-10-25">
                                        25 Oct 2026
                                    </time>
                                </dd>
                            </div>

                            <div>
                                <dt>Time</dt>
                                <dd>10:00 AM – 12:00 PM</dd>
                            </div>

                            <div>
                                <dt>Location</dt>
                                <dd>Amman</dd>
                            </div>

                        </dl>

                        <div class="next-visit-bottom">
                            <p>All times are in Jordan local time.</p>

                            <a
                                class="btn btn-white btn-small"
                                href="bookings.php"
                            >
                                View bookings
                                <span aria-hidden="true">↗</span>
                            </a>
                        </div>

                    </section>

                    <section
                        class="dashboard-card quick-actions"
                        aria-labelledby="quick-actions-title"
                    >
                        <div class="card-heading">
                            <div>
                                <h2 id="quick-actions-title">Quick actions</h2>
                                <p>Keep your workspace ready.</p>
                            </div>
                        </div>

                        <a class="quick-action" href="services.php">
                            <span class="quick-action-icon" aria-hidden="true">
                                ✦
                            </span>

                            <span class="quick-action-text">
                                <strong>Manage services</strong>
                                <small>Update your offers and prices</small>
                            </span>

                            <span class="quick-action-arrow" aria-hidden="true">
                                ↗
                            </span>
                        </a>

                        <a class="quick-action" href="slots.php">
                            <span class="quick-action-icon" aria-hidden="true">
                                ▤
                            </span>

                            <span class="quick-action-text">
                                <strong>Manage availability</strong>
                                <small>Add or update appointments</small>
                            </span>

                            <span class="quick-action-arrow" aria-hidden="true">
                                ↗
                            </span>
                        </a>

                        <a class="quick-action" href="../profile.php">
                            <span class="quick-action-icon" aria-hidden="true">
                                ◎
                            </span>

                            <span class="quick-action-text">
                                <strong>Edit your profile</strong>
                                <small>Refresh your bio and skills</small>
                            </span>

                            <span class="quick-action-arrow" aria-hidden="true">
                                ↗
                            </span>
                        </a>

                    </section>

                </div>

                <!-- RECENT BOOKINGS -->
                <section
                    class="dashboard-card bookings-card"
                    aria-labelledby="recent-bookings-title"
                >
                    <div class="card-heading">

                        <div>
                            <h2 id="recent-bookings-title">Recent bookings</h2>
                            <p>Your most recently created reservations.</p>
                        </div>

                        <a class="text-link" href="bookings.php">
                            View all
                            <span aria-hidden="true">↗</span>
                        </a>

                    </div>

                    <div
                        class="table-scroll"
                        role="region"
                        aria-labelledby="recent-bookings-title"
                        tabindex="0"
                    >
                        <table class="dashboard-table">

                            <caption class="visually-hidden">
                                Sample recent bookings showing customer,
                                service, appointment, price, and status.
                            </caption>

                            <thead>
                                <tr>
                                    <th scope="col">Booking</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Service</th>
                                    <th scope="col">Appointment</th>
                                    <th scope="col">Price</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>

                            <tbody>

                                <tr>
                                    <th scope="row">#1042</th>
                                    <td>Muhammad Sami</td>
                                    <td>Regular home cleaning</td>

                                    <td>
                                        <time datetime="2026-10-25">
                                            25 Oct 2026
                                        </time>
                                        <span class="table-secondary">
                                            10:00 AM – 12:00 PM
                                        </span>
                                    </td>

                                    <td class="table-price">JOD 25.000</td>

                                    <td>
                                        <span class="status-badge status-confirmed">
                                            Confirmed
                                        </span>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">#1041</th>
                                    <td>Layla Omar</td>
                                    <td>Deep cleaning</td>

                                    <td>
                                        <time datetime="2026-10-26">
                                            26 Oct 2026
                                        </time>
                                        <span class="table-secondary">
                                            9:00 AM – 12:00 PM
                                        </span>
                                    </td>

                                    <td class="table-price">JOD 45.000</td>

                                    <td>
                                        <span class="status-badge status-confirmed">
                                            Confirmed
                                        </span>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">#1040</th>
                                    <td>Zaid Ahmad</td>
                                    <td>Regular home cleaning</td>

                                    <td>
                                        <time datetime="2026-10-27">
                                            27 Oct 2026
                                        </time>
                                        <span class="table-secondary">
                                            1:00 PM – 3:00 PM
                                        </span>
                                    </td>

                                    <td class="table-price">JOD 25.000</td>

                                    <td>
                                        <span class="status-badge status-cancelled">
                                            Cancelled
                                        </span>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">#1039</th>
                                    <td>Dina Khalil</td>
                                    <td>Deep cleaning</td>

                                    <td>
                                        <time datetime="2026-10-20">
                                            20 Oct 2026
                                        </time>
                                        <span class="table-secondary">
                                            9:00 AM – 12:00 PM
                                        </span>
                                    </td>

                                    <td class="table-price">JOD 45.000</td>

                                    <td>
                                        <span class="status-badge status-completed">
                                            Completed
                                        </span>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>

                </section>

                <!-- SERVICES + CUSTOMER FEEDBACK -->
                <div class="dashboard-content-grid">

                    <section
                        class="dashboard-card"
                        aria-labelledby="services-overview-title"
                    >
                        <div class="card-heading">

                            <div>
                                <h2 id="services-overview-title">Your services</h2>
                                <p>A quick look at your current offers.</p>
                            </div>

                            <a class="text-link" href="services.php">
                                Manage
                                <span aria-hidden="true">↗</span>
                            </a>

                        </div>

                        <div class="service-overview-list">

                            <article class="service-overview-item">

                                <span
                                    class="service-overview-icon"
                                    aria-hidden="true"
                                >
                                    ✦
                                </span>

                                <div class="service-overview-info">
                                    <h3>Regular home cleaning</h3>
                                    <p>120 minutes · JOD 25.000</p>
                                </div>

                                <span class="status-badge status-completed">
                                    Active
                                </span>

                            </article>

                            <article class="service-overview-item">

                                <span
                                    class="service-overview-icon"
                                    aria-hidden="true"
                                >
                                    ✧
                                </span>

                                <div class="service-overview-info">
                                    <h3>Deep cleaning</h3>
                                    <p>180 minutes · JOD 45.000</p>
                                </div>

                                <span class="status-badge status-completed">
                                    Active
                                </span>

                            </article>

                        </div>
                    </section>

                    <section
                        class="dashboard-card reviews-summary"
                        aria-labelledby="reviews-title"
                    >
                        <div class="card-heading">

                            <div>
                                <h2 id="reviews-title">Customer feedback</h2>
                                <p>From completed bookings.</p>
                            </div>

                            <span class="review-heading-icon" aria-hidden="true">
                                ☆
                            </span>

                        </div>

                        <div class="rating-summary">

                            <strong>
                                4.8<span>/ 5</span>
                            </strong>

                            <div>
                                <span class="rating-stars" aria-hidden="true">
                                    ★★★★★
                                </span>
                                <p>Based on 12 reviews</p>
                            </div>

                        </div>

                        <blockquote class="review-quote">
                            <p>
                                “Excellent service and punctual arrival.
                                The home was cleaned thoroughly.”
                            </p>

                            <footer>
                                <span class="review-avatar" aria-hidden="true">
                                    DS
                                </span>

                                <div>
                                    <strong>Demo customer</strong>
                                    <span>Sample review</span>
                                </div>
                            </footer>
                        </blockquote>

                    </section>

                </div>

            </main>
            <!-- ================= PAGE CONTENT END ================= -->

             <?php require_once __DIR__ . "/../includes/footer.php"?>

        </div>
    </div>

</body>
</html>