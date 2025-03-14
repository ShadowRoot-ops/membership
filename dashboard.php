<?php
include('includes/config.php');
include('includes/twilio.php'); // Include Twilio API integration

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if 'end_date' exists, fallback to 'expiry_date'
$endDateColumn = "end_date";
$checkColumnQuery = "SHOW COLUMNS FROM members LIKE 'end_date'";
$result = $conn->query($checkColumnQuery);
if ($result->num_rows == 0) {
    $endDateColumn = "expiry_date"; // Use expiry_date if end_date is missing
}

// Counter Functions
function getTotalMembersCount() {
    global $conn;
    $query = "SELECT COUNT(*) AS totalMembers FROM members";
    $result = $conn->query($query);
    return ($result->num_rows > 0) ? $result->fetch_assoc()['totalMembers'] : 0;
}

function getTotalMembershipTypesCount() {
    global $conn;
    $query = "SELECT COUNT(*) AS totalMembershipTypes FROM membership_types";
    $result = $conn->query($query);
    return ($result->num_rows > 0) ? $result->fetch_assoc()['totalMembershipTypes'] : 0;
}

function getExpiringSoonCount() {
    global $conn, $endDateColumn;
    $query = "SELECT COUNT(*) AS expiringSoon FROM members WHERE $endDateColumn BETWEEN CURDATE() AND CURDATE() + INTERVAL 7 DAY";
    $result = $conn->query($query);
    return ($result->num_rows > 0) ? $result->fetch_assoc()['expiringSoon'] : 0;
}

function getExpiredMembersCount() {
    global $conn, $endDateColumn;
    $query = "SELECT COUNT(*) AS expiredMembersCount FROM members WHERE $endDateColumn < CURDATE()";
    $result = $conn->query($query);
    return ($result->num_rows > 0) ? $result->fetch_assoc()['expiredMembersCount'] : 0;
}

function getNewMembersCount() {
    global $conn;
    $query = "SELECT COUNT(*) AS newMembersCount FROM members WHERE created_at >= NOW() - INTERVAL 1 DAY";
    $result = $conn->query($query);
    return ($result->num_rows > 0) ? $result->fetch_assoc()['newMembersCount'] : 0;
}

// Twilio Alert for Expired Memberships
function sendTwilioAlertForExpiredMembers() {
    global $conn, $endDateColumn;
    $query = "SELECT * FROM members WHERE $endDateColumn < CURDATE() AND message_sent = 0";
    $result = $conn->query($query);

    while ($row = $result->fetch_assoc()) {
        $contactNumber = $row['contact_number'];
        
        if (!empty($contactNumber)) {
            if (substr($contactNumber, 0, 1) !== "+") {
                $contactNumber = "+91" . $contactNumber; 
            }
            
            $message = "Hello {$row['fullname']}, your gym membership has expired. Renew now to continue enjoying our facilities. Contact us for renewal.";
            sendWhatsAppMessage($contactNumber, $message);
            
            // Mark message as sent
            $updateQuery = "UPDATE members SET message_sent = 1 WHERE id = {$row['id']}";
            $conn->query($updateQuery);
        }
    }
}

// Trigger Twilio alerts
sendTwilioAlertForExpiredMembers();
?>

<?php include('includes/header.php'); ?>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
    <?php include('includes/nav.php'); ?>
    <?php include('includes/sidebar.php'); ?>

    <div class="content-wrapper">
        <?php include('includes/pagetitle.php'); ?>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <!-- Total Members -->
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-primary"><i class="fas fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Members</span>
                                <span class="info-box-number"><?php echo getTotalMembersCount(); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Membership Types -->
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-danger"><i class="fas fa-list"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Membership Types</span>
                                <span class="info-box-number"><?php echo getTotalMembershipTypesCount(); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Expiring Soon -->
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-warning"><i class="fas fa-hourglass-half"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Expiring Soon</span>
                                <span class="info-box-number"><?php echo getExpiringSoonCount(); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Expired Memberships -->
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-maroon"><i class="fas fa-times"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Expired Memberships</span>
                                <span class="info-box-number"><?php echo getExpiredMembersCount(); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recently Joined Members -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Recently Joined Members</h3>
                            </div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <?php
                                    $query = "SELECT * FROM members ORDER BY created_at DESC LIMIT 4";
                                    $result = $conn->query($query);
                                    while ($row = $result->fetch_assoc()) {
                                        $photoPath = (!empty($row['photo'])) ? "uploads/member_photos/{$row['photo']}" : "uploads/member_photos/default.jpg";
                                        echo "<li class='list-group-item'>";
                                        echo "<img src='$photoPath' class='img-size-50' alt='Member Photo'> ";
                                        echo "<strong>{$row['fullname']}</strong> - Membership Number: {$row['membership_number']}";
                                        echo "</li>";
                                    }
                                    ?>
                                </ul>
                            </div>
                            <div class="card-footer text-center">
                                <a href="manage_members.php">View All Members</a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </div>

    <footer class="main-footer">
         All rights reserved.
    </footer>
</div>

<?php include('includes/footer.php'); ?>
</body>
</html>
