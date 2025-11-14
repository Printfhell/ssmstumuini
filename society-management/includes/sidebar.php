<button class="menu-btn" id="menuBtn">
    <i class="fas fa-bars"></i>
</button>
<div id="sidebar" class="sidebar">
<button class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-chevron-left"></i>
</button>
 <div class="logo">
  <img src="../images/OIP.png" class="img-fluid" alt="">
  <span class="logo-text">SOCIETY MANAGEMENT SYSTEM</span>
</div>
    <ul class="nav flex-column">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <li class="nav-item"><a class="nav-link" href="../admin/dashboard.php"><i class="fas fa-tachometer-alt"></i><span class="menu-text">Dashboard</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../admin/flats.php"><i class="fas fa-building"></i><span class="menu-text">Flats</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../admin/allotments.php"><i class="fas fa-home"></i><span class="menu-text">Allotments</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../admin/bills.php"><i class="fas fa-file-invoice-dollar"></i><span class="menu-text">Bills</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../admin/complaints.php"><i class="fas fa-exclamation-triangle"></i><span class="menu-text">Complaints</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../admin/visitors.php"><i class="fas fa-users"></i><span class="menu-text">Visitors</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../admin/notifications.php"><i class="fas fa-bell"></i><span class="menu-text">Notifications</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../admin/users.php"><i class="fas fa-user"></i><span class="menu-text">Users</span></a></li>

            <li class="nav-item"><a class="nav-link" href="../admin/profile.php"><i class="fas fa-user-circle"></i><span class="menu-text">Profile</span></a></li>
        <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="../resident/dashboard.php"><i class="fas fa-tachometer-alt"></i><span class="menu-text">Dashboard</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../resident/bills.php"><i class="fas fa-file-invoice-dollar"></i><span class="menu-text">Bills</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../resident/complaints.php"><i class="fas fa-exclamation-triangle"></i><span class="menu-text">Complaints</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../resident/visitors.php"><i class="fas fa-users"></i><span class="menu-text">Visitors</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../resident/notifications.php"><i class="fas fa-bell"></i><span class="menu-text">Notifications</span></a></li>

            <li class="nav-item"><a class="nav-link" href="../resident/profile.php"><i class="fas fa-user-circle"></i><span class="menu-text">Profile</span></a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link" href="#" onclick="return confirm('Confirm Logout? Are you sure you want to log out?') ? window.location.href='../auth/logout.php' : false;"><i class="fas fa-sign-out-alt"></i><span class="menu-text">Logout</span></a></li>
    </ul>
    <div class="copyright">
        ©RhamsKage 2025
    </div>
</div>


<div class="main-content">