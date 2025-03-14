<?php
include('includes/config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$response = array('success' => false, 'message' => '');

$membershipTypesQuery = "SELECT id, type, amount FROM membership_types";
$membershipTypesResult = $conn->query($membershipTypesQuery);

if (isset($_GET['id'])) {
    $memberId = $_GET['id'];

    $fetchMemberQuery = "SELECT * FROM members WHERE id = $memberId";
    $fetchMemberResult = $conn->query($fetchMemberQuery);

    if ($fetchMemberResult->num_rows > 0) {
        $memberDetails = $fetchMemberResult->fetch_assoc();
    } else {
        header("Location: members_list.php");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $contactNumber = $_POST['contactNumber'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $membershipType = $_POST['membershipType'];
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    $amountPaid = $_POST['amountPaid'];
    $remainingAmount = $_POST['remainingAmount'];

    $updateQuery = "UPDATE members SET fullname='$fullname', contact_number='$contactNumber', email='$email', 
                    address='$address', membership_type='$membershipType', start_date='$startDate', 
                    end_date='$endDate', amount_paid='$amountPaid', remaining_amount='$remainingAmount'
                    WHERE id = $memberId";

    if ($conn->query($updateQuery) === TRUE) {
        $response['success'] = true;
        $response['message'] = 'Member updated successfully!';
        header("Location: manage_members.php");
        exit();
    } else {
        $response['message'] = 'Error: ' . $conn->error;
    }
}
?>

<?php include('includes/header.php');?>

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

                        <?php if ($response['success']): ?>
                            <div class="alert alert-success alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                <h5><i class="icon fas fa-check"></i> Success</h5>
                                <?php echo $response['message']; ?>
                            </div>
                        <?php elseif (!empty($response['message'])): ?>
                            <div class="alert alert-danger alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                <h5><i class="icon fas fa-ban"></i> Error</h5>
                                <?php echo $response['message']; ?>
                            </div>
                        <?php endif; ?>

                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-keyboard"></i> Edit Member Details</h3>
                            </div>

                            <form method="post" action="">
                                <input type="hidden" name="member_id" value="<?php echo $memberId; ?>">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <label for="fullname">Full Name</label>
                                            <input type="text" class="form-control" id="fullname" name="fullname"
                                                   value="<?php echo $memberDetails['fullname']; ?>" required>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="contactNumber">Contact Number</label>
                                            <input type="tel" class="form-control" id="contactNumber"
                                                   name="contactNumber" value="<?php echo $memberDetails['contact_number']; ?>" required>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-sm-6">
                                            <label for="email">Email</label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                   value="<?php echo $memberDetails['email']; ?>" required>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="address">Address</label>
                                            <input type="text" class="form-control" id="address" name="address"
                                                   value="<?php echo $memberDetails['address']; ?>" required>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-sm-6">
                                            <label for="membershipType">Membership Type</label>
                                            <select class="form-control" id="membershipType" name="membershipType" required>
                                                <?php
                                                while ($row = $membershipTypesResult->fetch_assoc()) {
                                                    $selected = ($row['id'] == $memberDetails['membership_type']) ? "selected" : "";
                                                    echo "<option value='{$row['id']}' $selected>{$row['type']} - ₹{$row['amount']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-sm-6">
                                            <label for="startDate">Start Date</label>
                                            <input type="date" class="form-control" id="startDate" name="startDate"
                                                   value="<?php echo $memberDetails['start_date']; ?>" required>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="endDate">End Date</label>
                                            <input type="date" class="form-control" id="endDate" name="endDate"
                                                   value="<?php echo $memberDetails['end_date']; ?>" required>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-sm-3">
                                            <label for="amountPaid">Amount Paid</label>
                                            <input type="number" class="form-control" id="amountPaid" name="amountPaid"
                                                   value="<?php echo $memberDetails['amount_paid']; ?>" required oninput="updateRemainingAmount()">
                                        </div>
                                        <div class="col-sm-3">
                                            <label for="remainingAmount">Remaining Amount</label>
                                            <input type="number" class="form-control" id="remainingAmount" name="remainingAmount"
                                                   value="<?php echo $memberDetails['remaining_amount']; ?>" readonly>
                                        </div>
                                    </div>

                                </div>

                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">Update</button>
                                </div>
                            </form>
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

<script>
function updateRemainingAmount() {
    var amountPaid = parseFloat(document.getElementById("amountPaid").value) || 0;
    var membershipType = document.getElementById("membershipType");
    var membershipAmount = parseFloat(membershipType.options[membershipType.selectedIndex].getAttribute("data-amount")) || 0;
    document.getElementById("remainingAmount").value = (membershipAmount - amountPaid).toFixed(2);
}
</script>

<?php include('includes/footer.php'); ?>
</body>
</html>
