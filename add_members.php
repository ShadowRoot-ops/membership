<?php
include('includes/config.php');

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$response = array('success' => false, 'message' => '');

// Ensure required columns exist in the database
$conn->query("ALTER TABLE members 
    ADD COLUMN IF NOT EXISTS amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00, 
    ADD COLUMN IF NOT EXISTS remaining_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00, 
    ADD COLUMN IF NOT EXISTS payment_mode VARCHAR(50) NOT NULL DEFAULT 'Cash', 
    ADD COLUMN IF NOT EXISTS start_date DATE NOT NULL, 
    ADD COLUMN IF NOT EXISTS end_date DATE NOT NULL,
    ADD COLUMN IF NOT EXISTS status VARCHAR(50) NOT NULL DEFAULT 'Active',
    ADD COLUMN IF NOT EXISTS address VARCHAR(255) NOT NULL,
    ADD COLUMN IF NOT EXISTS contact_number VARCHAR(15) NOT NULL");

// Fetch membership types with amount
$membershipTypesQuery = "SELECT id, type, amount FROM membership_types";
$membershipTypesResult = $conn->query($membershipTypesQuery);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle missing fields safely
    $fullname = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $contactNumber = $_POST['contactNumber'] ?? '';
    $address = $_POST['address'] ?? '';
    $membershipType = $_POST['membershipType'] ?? '';
    $startDate = $_POST['startDate'] ?? date('Y-m-d');
    $endDate = $_POST['endDate'] ?? date('Y-m-d', strtotime('+1 month'));
    $paymentMode = $_POST['paymentMode'] ?? 'Cash';
    $amountPaid = isset($_POST['amountPaid']) ? floatval($_POST['amountPaid']) : 0.00;

    // Validate Indian mobile number
    if (!preg_match('/^[6-9]\d{9}$/', $contactNumber)) {
        $response['message'] = "Error: Invalid Indian mobile number. It must be 10 digits and start with 6, 7, 8, or 9.";
    } else {
        // Fetch Membership Amount
        $stmt = $conn->prepare("SELECT amount FROM membership_types WHERE id = ?");
        $stmt->bind_param("i", $membershipType);
        $stmt->execute();
        $result = $stmt->get_result();
        $membershipRow = $result->fetch_assoc();
        $membershipAmount = $membershipRow['amount'] ?? 0;

        $remainingAmount = max(0, $membershipAmount - $amountPaid);

        // Determine Membership Status (Active or Expired)
        $currentDate = date('Y-m-d');
        $membershipStatus = ($endDate >= $currentDate) ? 'Active' : 'Expired';

        // Check for duplicate member (same phone or email)
        $stmt = $conn->prepare("SELECT id FROM members WHERE contact_number = ? OR email = ?");
        $stmt->bind_param("ss", $contactNumber, $email);
        $stmt->execute();
        $duplicateResult = $stmt->get_result();

        if ($duplicateResult->num_rows > 0) {
            $response['message'] = "Error: A member with this phone number or email already exists!";
        } else {
            $membershipNumber = 'CA-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);

            // Insert Member
            $stmt = $conn->prepare("INSERT INTO members (fullname, email, contact_number, address, membership_type, membership_number, 
                            payment_mode, amount_paid, remaining_amount, start_date, end_date, status, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $stmt->bind_param("ssssssssddss", $fullname, $email, $contactNumber, $address, $membershipType, $membershipNumber, 
                                                $paymentMode, $amountPaid, $remainingAmount, $startDate, $endDate, $membershipStatus);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Member added successfully! Membership Number: ' . $membershipNumber;
            } else {
                $response['message'] = 'Error: ' . $stmt->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('includes/header.php'); ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
    <?php include('includes/nav.php'); ?>
    <?php include('includes/sidebar.php'); ?>

    <div class="content-wrapper">
        <?php include('includes/pagetitle.php'); ?>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <?php if (!empty($response['message'])): ?>
                            <div class="alert alert-<?php echo $response['success'] ? 'success' : 'danger'; ?>">
                                <?php echo $response['message']; ?>
                            </div>
                        <?php endif; ?>

                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Add Member</h3>
                            </div>

                            <form method="post">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <label for="fullname">Full Name</label>
                                            <input type="text" class="form-control" id="fullname" name="fullname" required>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="email">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-sm-6">
                                            <label for="contactNumber">Mobile Number</label>
                                            <input type="text" class="form-control" id="contactNumber" name="contactNumber" required
                                                   pattern="[6-9]\d{9}" title="Please enter a valid Indian mobile number (10 digits, starting with 6, 7, 8, or 9)">
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="address">Address</label>
                                            <input type="text" class="form-control" id="address" name="address" required>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-sm-6">
                                            <label for="membershipType">Membership Type</label>
                                            <select class="form-control" id="membershipType" name="membershipType" required onchange="updateRemainingAmount()">
                                                <option value="">Select Membership Type</option>
                                                <?php while ($row = $membershipTypesResult->fetch_assoc()): ?>
                                                    <option value="<?php echo $row['id']; ?>" data-amount="<?php echo $row['amount']; ?>">
                                                        <?php echo $row['type']; ?> - ₹<?php echo $row['amount']; ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="paymentMode">Payment Mode</label>
                                            <select class="form-control" id="paymentMode" name="paymentMode" required>
                                                <option value="Cash">Cash</option>
                                                <option value="Credit Card">Credit Card</option>
                                                <option value="Bank Transfer">Bank Transfer</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-sm-6">
                                            <label for="startDate">Start Date</label>
                                            <input type="date" class="form-control" id="startDate" name="startDate" required>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="endDate">End Date</label>
                                            <input type="date" class="form-control" id="endDate" name="endDate" required>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-sm-6">
                                            <label for="amountPaid">Amount Paid</label>
                                            <input type="number" class="form-control" id="amountPaid" name="amountPaid" step="0.01" required oninput="updateRemainingAmount()">
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="remainingAmount">Remaining Amount</label>
                                            <input type="text" class="form-control" id="remainingAmount" name="remainingAmount" readonly>
                                        </div>
                                    </div>

                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
function updateRemainingAmount() {
    var amountPaid = parseFloat(document.getElementById("amountPaid").value) || 0;
    var membershipAmount = parseFloat(document.getElementById("membershipType").selectedOptions[0].getAttribute("data-amount")) || 0;
    document.getElementById("remainingAmount").value = (membershipAmount - amountPaid).toFixed(2);
}
</script>

<?php include('includes/footer.php'); ?>
</body>
</html>