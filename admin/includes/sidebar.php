<aside class="dashboard-sidebar" id="admin-sidebar">
    <label class="sidebar-close" for="sidebar-toggle" aria-label="Close navigation"><span aria-hidden="true">×</span></label>
    <a class="brand dashboard-brand" href="dashboard.php" aria-label="ShineRoster admin dashboard"><img src="../images/ShineRoster_Logo.png" alt="ShineRoster" width="220" height="74"></a>
    <span class="sidebar-label">Site administration</span>
    <nav class="sidebar-nav" aria-label="Admin navigation">
        <a href="dashboard.php" <?php if ($adminPage === 'dashboard.php') {
            echo 'aria-current="page"';
        } ?>><span class="nav-icon" aria-hidden="true">▦</span>Overview</a>
        <a href="users.php" <?php if ($adminPage === 'users.php') {
            echo 'aria-current="page"';
        } ?>><span class="nav-icon" aria-hidden="true">◎</span>Users</a>
        <a href="providers.php" <?php if ($adminPage === 'providers.php') {
            echo 'aria-current="page"';
        } ?>><span class="nav-icon" aria-hidden="true">◇</span>Providers</a>
        <a href="services.php" <?php if ($adminPage === 'services.php') {
            echo 'aria-current="page"';
        } ?>><span class="nav-icon" aria-hidden="true">✦</span>Services</a>
        <a href="appointments.php" <?php if ($adminPage === 'appointments.php') {
            echo 'aria-current="page"';
        } ?>><span class="nav-icon" aria-hidden="true">▤</span>Appointments</a>
        <a href="bookings.php" <?php if ($adminPage === 'bookings.php') {
            echo 'aria-current="page"';
        } ?>><span class="nav-icon" aria-hidden="true">▣</span>Bookings</a>
        <a href="reviews.php" <?php if ($adminPage === 'reviews.php') {
            echo 'aria-current="page"';
        } ?>><span class="nav-icon" aria-hidden="true">☆</span>Reviews</a>
        <a href="messages.php" <?php if ($adminPage === 'messages.php') {
            echo 'aria-current="page"';
        } ?>><span class="nav-icon" aria-hidden="true">✉</span>Messages</a>
        <a href="skills.php" <?php if ($adminPage === 'skills.php') {
            echo 'aria-current="page"';
        } ?>><span class="nav-icon" aria-hidden="true">✧</span>Skills</a>
        <a href="account.php" <?php if ($adminPage === 'account.php') {
            echo 'aria-current="page"';
        } ?>><span class="nav-icon" aria-hidden="true">◉</span>My account</a>
    </nav>
    <div class="sidebar-bottom">
        <a class="sidebar-help" href="../index.php"><span aria-hidden="true">↗</span>View website</a>
        <a class="sidebar-logout" href="../logout.php"><span aria-hidden="true">↪</span>Log out</a>
    </div>
</aside>
