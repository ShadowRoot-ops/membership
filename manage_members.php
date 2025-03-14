<?php
include('includes/config.php');

$selectQuery = "SELECT * FROM members ORDER BY created_at DESC";
$result = $conn->query($selectQuery);

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $membershipType = $_POST['membershipType'];
    $membershipAmount = $_POST['membershipAmount'];

    $insertQuery = "INSERT INTO membership_types (type, amount) VALUES ('$membershipType', $membershipAmount)";
    
    if ($conn->query($insertQuery) === TRUE) {
        $successMessage = 'Membership type added successfully!';
    } else {
        echo "Error: " . $insertQuery . "<br>" . $conn->error;
    }
}
?>

<?php include('includes/header.php');?>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
  <?php include('includes/nav.php');?>
  <?php include('includes/sidebar.php');?>

  <div class="content-wrapper">
    <?php include('includes/pagetitle.php');?>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Members DataTable</h3>
                </div>
                
                <div class="card-body">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fullname</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Type</th>
                                <th>Start Date</th> <!-- Added Start Date Column -->
                                <th>End Date</th>   <!-- Added End Date Column -->
                                <th>Payment Mode</th>
                                <th>Amount Paid</th>
                                <th>Remaining Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $counter = 1;
                            while ($row = $result->fetch_assoc()) {
                                $currentDate = date('Y-m-d');
                                $startDate = $row['start_date']; // Ensure 'start_date' exists in DB
                                $endDate = $row['end_date'];     // Ensure 'end_date' exists in DB
                                
                                // Determine Membership Status
                                $membershipStatus = ($endDate >= $currentDate) ? 'Active' : 'Expired';

                                $membershipTypeId = $row['membership_type'];
                                $membershipTypeQuery = "SELECT type FROM membership_types WHERE id = $membershipTypeId";
                                $membershipTypeResult = $conn->query($membershipTypeQuery);
                                $membershipTypeRow = $membershipTypeResult->fetch_assoc();
                                $membershipTypeName = ($membershipTypeRow) ? $membershipTypeRow['type'] : 'Unknown';

                                echo "<tr>";
                                echo "<td>{$row['membership_number']}</td>";
                                echo "<td>{$row['fullname']}</td>";
                                echo "<td>{$row['contact_number']}</td>";
                                echo "<td>{$row['email']}</td>";
                                echo "<td>{$row['address']}</td>";
                                echo "<td>{$membershipTypeName}</td>";
                                echo "<td>{$startDate}</td>"; // Display Start Date
                                echo "<td>{$endDate}</td>";   // Display End Date
                                echo "<td>{$row['payment_mode']}</td>";
                                echo "<td>₹{$row['amount_paid']}</td>";
                                echo "<td>₹{$row['remaining_amount']}</td>";
                                echo "<td>{$membershipStatus}</td>";

                                echo "<td>";
                                echo "<a href='memberProfile.php?id={$row['id']}' class='btn btn-info'><i class='fas fa-id-card'></i></a>";
                                echo "<a href='edit_member.php?id={$row['id']}' class='btn btn-primary'><i class='fas fa-edit'></i></a>";
                                echo "<button class='btn btn-danger' onclick='deleteMember({$row['id']})'><i class='fas fa-trash'></i></button>";
                                echo "</td>";
                                echo "</tr>";

                                $counter++;
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

          </div>
        </div>
      </div>
    </section>
  </div>

  <aside class="control-sidebar control-sidebar-dark"></aside>

  <footer class="main-footer">
    
    All rights reserved.
  </footer>
</div>

<?php include('includes/footer.php');?>

<script>
  $(function () {
    $("#example1").DataTable({
      "responsive": true,
      "autoWidth": false,
    });
  });

  function deleteMember(id) {
      if (confirm("Are you sure you want to delete this member?")) {
          window.location.href = 'delete_members.php?id=' + id;
      }
  }
</script>

</body>
</html>
