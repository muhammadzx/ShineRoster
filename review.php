<?php
require_once __DIR__ . '/includes/init.php';
$currentAccount = $auth->requireRole('customer', 'login.php');
$customerId = (int) $currentAccount['id'];
$bookingId = positiveId($_GET, 'booking_id');
$reviewObject = new Review($db);
$booking = $reviewObject->getBooking($customerId, $bookingId);
$error = '';
$rating = 0;
$comment = inputText($_POST, 'comment');
$ratingLabels = [1 => 'Poor', 2 => 'Fair', 3 => 'Good', 4 => 'Very good', 5 => 'Excellent'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = positiveId($_POST, 'rating');
    if ($reviewObject->create($customerId, $bookingId, $rating, $comment)) {
        $_SESSION['booking_success'] = 'Thank you! Your review has been submitted.';
        header('Location: my-bookings.php', true, 303);
        exit();
    }
    $error = $reviewObject->getError();
}
if (!$booking) {
    http_response_code(404);
}
$canReview = $booking && $booking['status'] === 'completed' && $booking['review_id'] === null;
$hasReview = $booking && $booking['review_id'] !== null;
$startsAt = null;
if ($booking) {
    $startsAt = strtotime($booking['starts_at']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>Review Your Visit | ShineRoster</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0F766E">
    <link rel="icon" href="images/favicon.ico" sizes="any">
    <link rel="icon" href="images/favicon.png" type="image/png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/services.css">
    <link rel="stylesheet" href="css/review.css">
</head>
<body class="services-page review-page">
    <a class="catalog-skip-link" href="#review-main">
        Skip to review
    </a>

    

    <?php require __DIR__ . '/includes/catalog-header.php'; ?>

    <main id="review-main">
        <section class="review-hero">
            <div class="catalog-container">
                <nav
                    class="catalog-breadcrumb"
                    aria-label="Breadcrumb"
                >
                    <a href="index.php">Home</a>
                    <span aria-hidden="true">/</span>
                    <a href="my-bookings.php">My bookings</a>
                    <span aria-hidden="true">/</span>
                    <span aria-current="page">
                        Review your visit
                    </span>
                </nav>
                <span class="catalog-eyebrow">
                    Your experience matters
                </span>
                <h1>How was your fresh start?</h1>
                <p>
                    Share your experience and help other customers
                    choose the right provider for their homes.
                </p>
            </div>
        </section>
        <div class="catalog-container review-container">

            <?php if ($error !== ''): ?>
                <div class="review-alert" role="alert">
                    <span aria-hidden="true">!</span>
                    <p><?= escapeHtml($error) ?></p>
                </div>
            <?php endif; ?>

            <?php if (!$booking): ?>

                <div class="catalog-empty">
                    <span
                        class="catalog-empty-icon"
                        aria-hidden="true"
                    >
                        ⌕
                    </span>
                    <h2>Booking not found</h2>
                    <p>
                        We couldn’t find this booking in your account.
                        Open your bookings to choose a completed visit.
                    </p>
                    <a
                        class="catalog-button catalog-button-primary"
                        href="my-bookings.php"
                    >
                        Back to my bookings
                        <span aria-hidden="true">↗</span>
                    </a>
                </div>

            <?php elseif ($booking['status'] !== 'completed'): ?>

                <div class="catalog-empty">
                    <span
                        class="catalog-empty-icon"
                        aria-hidden="true"
                    >
                        ▦
                    </span>
                    <h2>Your review opens after the visit</h2>
                    <p>
                        You can review this booking once it has
                        been marked as completed.
                    </p>
                    <a
                        class="catalog-button catalog-button-primary"
                        href="my-bookings.php"
                    >
                        Back to my bookings
                        <span aria-hidden="true">↗</span>
                    </a>
                </div>

            <?php else: ?>

                <div class="review-layout">
                    <section
                        class="review-panel"
                        aria-labelledby="review-form-title"
                    >

                        <?php if ($hasReview): ?>

                            <div class="review-panel-heading">
                                <span
                                    class="review-panel-icon"
                                    aria-hidden="true"
                                >
                                    ✓
                                </span>
                                <div>
                                    <span class="catalog-eyebrow">
                                        Thank you for sharing
                                    </span>
                                    <h2 id="review-form-title">
                                        Your review has been submitted
                                    </h2>
                                </div>
                            </div>
                            <p class="review-intro">
                                You have already reviewed this booking.
                                Your submitted review is shown below.
                            </p>
                            <div class="review-existing">
                                <div
                                    class="review-existing-stars"
                                    aria-label="<?= (int) $booking[
                                        'review_rating'
                                    ] ?> out of 5 stars"
                                >
                                    <span aria-hidden="true">
                                        <?= str_repeat('★', (int) $booking['review_rating']) ?>

                                        <?= str_repeat('☆', 5 - (int) $booking['review_rating']) ?>
                                    </span>
                                </div>
                                <strong class="review-existing-score">
                                    <?= (int) $booking['review_rating'] ?>/5

                                    ·

                                    <?= escapeHtml(
                                        $ratingLabels[(int) $booking['review_rating']]
                                    ) ?>
                                </strong>

                                <?php if (trim((string) $booking['review_comment']) !== ''): ?>

                                    <p
                                        class="review-existing-comment"
                                        dir="auto"
                                    >
                                        <?= nl2br(escapeHtml($booking['review_comment'])) ?>
                                    </p>

                                <?php else: ?>

                                    <p class="review-no-comment">
                                        You submitted a rating without a comment.
                                    </p>

                                <?php endif; ?>

                                <p class="review-submitted-date">
                                    Submitted on

                                    <?= escapeHtml(
                                        date('d M Y', strtotime($booking['review_created_at']))
                                    ) ?>
                                </p>
                            </div>
                            <a
                                class="catalog-button catalog-button-primary"
                                href="my-bookings.php"
                            >
                                Back to my bookings
                                <span aria-hidden="true">↗</span>
                            </a>

                        <?php elseif ($canReview): ?>

                            <div class="review-panel-heading">
                                <span
                                    class="review-panel-icon"
                                    aria-hidden="true"
                                >
                                    ☆
                                </span>
                                <div>
                                    <span class="catalog-eyebrow">
                                        A few words go a long way
                                    </span>
                                    <h2 id="review-form-title">
                                        Tell us about your visit
                                    </h2>
                                </div>
                            </div>
                            <p class="review-intro">
                                Rate your experience with this cleaning visit.
                                Adding a comment is optional.
                            </p>
                            <form
                                class="review-form"
                                action="review.php?booking_id=<?= $bookingId ?>"
                                method="post"
                            >
                                
                                <fieldset class="review-rating-fieldset">
                                    <legend>
                                        How was your cleaning visit?
                                        <span aria-hidden="true">*</span>
                                    </legend>
                                    <div class="review-rating-options">

                                        <?php for ($value = 1; $value <= 5; $value++): ?>

                                            <label class="review-rating-option">
                                                <input
                                                    type="radio"
                                                    name="rating"
                                                    value="<?= $value ?>"
                                                    aria-label="<?= $value ?> out of 5 stars: <?= escapeHtml(
     $ratingLabels[$value]
 ) ?>"
                                                    <?php if ($rating === $value) {
                                                        echo 'checked';
                                                    } else {
                                                        echo '';
                                                    } ?>
                                                    required
                                                >
                                                <span class="review-rating-face">
                                                    <span
                                                        class="review-rating-star"
                                                        aria-hidden="true"
                                                    >
                                                        ★
                                                    </span>
                                                    <strong aria-hidden="true">
                                                        <?= $value ?>
                                                    </strong>
                                                </span>
                                            </label>

                                        <?php endfor; ?>

                                    </div>
                                    <div
                                        class="review-rating-scale"
                                        aria-hidden="true"
                                    >
                                        <span>Poor</span>
                                        <span>Excellent</span>
                                    </div>
                                </fieldset>
                                <div class="review-comment-field">
                                    <label for="review-comment">
                                        Your experience
                                        <span>(optional)</span>
                                    </label>
                                    <textarea
                                        id="review-comment"
                                        name="comment"
                                        rows="7"
                                        maxlength="2000"
                                        placeholder="What went well? Was there anything that could have been better?"
                                        aria-describedby="review-comment-hint"
                                    ><?= escapeHtml($comment) ?></textarea>
                                    <p
                                        class="review-field-hint"
                                        id="review-comment-hint"
                                    >
                                        Up to 2,000 characters.
                                        Please avoid sharing private information.
                                    </p>
                                </div>
                                <div class="review-information">
                                    <span aria-hidden="true">✓</span>
                                    <p>
                                        Share an honest and respectful review
                                        of your completed visit.
                                        Each booking can receive one review.
                                    </p>
                                </div>
                                <div class="review-form-actions">
                                    <a
                                        class="catalog-button catalog-button-secondary"
                                        href="my-bookings.php"
                                    >
                                        <span aria-hidden="true">←</span>
                                        Back to bookings
                                    </a>
                                    <button
                                        class="catalog-button catalog-button-primary"
                                        type="submit"
                                    >
                                        Submit review
                                        <span aria-hidden="true">↗</span>
                                    </button>
                                </div>
                            </form>

                        <?php endif; ?>

                    </section>
                    <aside
                        class="review-summary"
                        aria-labelledby="review-summary-title"
                    >
                        <span class="catalog-eyebrow">
                            Your completed visit
                        </span>
                        <h2 id="review-summary-title">
                            Booking #<?= (int) $booking['id'] ?>
                        </h2>
                        <span class="review-completed-badge">
                            <span aria-hidden="true">✓</span>
                            Completed
                        </span>
                        <div class="review-summary-service">
                            <span
                                class="review-service-icon"
                                aria-hidden="true"
                            >
                                ✦
                            </span>
                            <div>
                                <h3 dir="auto">
                                    <?= escapeHtml($booking['service_name']) ?>
                                </h3>
                                <p dir="auto">
                                    <?= escapeHtml($booking['provider_name']) ?>
                                </p>
                            </div>
                        </div>
                        <dl class="review-summary-details">
                            <div>
                                <dt>Visit date</dt>
                                <dd>
                                    <?= escapeHtml(date('d M Y', $startsAt)) ?>
                                </dd>
                            </div>
                            <div>
                                <dt>Start time</dt>
                                <dd>
                                    <?= escapeHtml(date('g:i A', $startsAt)) ?>
                                </dd>
                            </div>
                            <div>
                                <dt>City</dt>
                                <dd dir="auto">
                                    <?= escapeHtml($booking['city']) ?>
                                </dd>
                            </div>
                            <div>
                                <dt>Service price</dt>
                                <dd>
                                    JOD <?= number_format((float) $booking['total_price'], 3) ?>
                                </dd>
                            </div>
                        </dl>
                        <p class="review-summary-timezone">
                            Appointment times are shown in Jordan time.
                        </p>
                        <div class="review-summary-help">
                            <span aria-hidden="true">?</span>
                            <p>
                                Need help with this visit?
                                <a href="index.php#contact">
                                    Contact us
                                </a>
                            </p>
                        </div>
                    </aside>
                </div>

            <?php endif; ?>

        </div>
    </main>

    

    <?php require __DIR__ . '/includes/catalog-footer.php'; ?>

</body>
</html>