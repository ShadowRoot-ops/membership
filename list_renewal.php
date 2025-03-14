<?php
include('includes/config.php');
include('includes/twilio.php'); // Include Twilio messaging script

// Check for expired members who haven't received a message
$expiredQuery = "SELECT * FROM members WHERE end_date < CURDATE() AND message_sent = 0";
$expiredResult = $conn->query($expiredQuery);

// Send message to expired members
while ($row = $expiredResult->fetch_assoc()) {
    $contactNumber = $row['contact_number'];
    
    if (!empty($contactNumber)) {
        // Ensure number has a country code
        if (substr($contactNumber, 0, 1) !== "+") {
            $contactNumber = "+91" . $contactNumber; // Modify as per your country
        }

        // WhatsApp Message
        $message = "Hello {$row['fullname']}, your gym membership has expired. Renew now to continue enjoying our facilities. Contact us for renewal. Thank you!";

        // Send message via Twilio
        sendWhatsAppMessage($contactNumber, $message);

        // Mark message as sent in the database
        $updateQuery = "UPDATE members SET message_sent = 1 WHERE id = {$row['id']}";
        $conn->query($updateQuery);
    }
}

$selectQuery = "SELECT * FROM members";
$result = $conn->query($selectQuery);

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include('includes/header.php');
?>

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
                      <th>Type</th>
                      <th>End Date</th>
                      <th>Status</th>
                      <th>Action</th>
                      <th>Send Reminder</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    while ($row = $result->fetch_assoc()) {
                        $endDate = $row['end_date']; // Ensure 'end_date' exists in your DB table
                        $currentDate = date('Y-m-d');
                        
                        // Determine Membership Status
                        $membershipStatus = ($endDate >= $currentDate) ? 'Active' : 'Expired';
                        $badgeClass = ($membershipStatus === 'Expired') ? 'badge-danger' : 'badge-success';

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
                        echo "<td>{$membershipTypeName}</td>";
                        echo "<td>{$row['end_date']}</td>";
                        echo "<td><span class='badge $badgeClass'>$membershipStatus</span></td>";
                        echo "<td><a href='renew.php?id={$row['id']}' class='btn btn-success'>Renew</a></td>";
                        
                        // Send Reminder Section: Date Picker + Message Input + Button
                        echo "<td>
                            <input type='date' id='date_{$row['id']}' class='form-control mb-2' placeholder='Select Date'>
                            <textarea id='message_{$row['id']}' class='form-control mb-2' placeholder='Enter custom message'></textarea>
                            <button class='btn btn-primary' onclick='sendReminder({$row['id']})'>Send Reminder</button>
                          </td>";
                        echo "</tr>";
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

  function sendReminder(memberId) {
    let selectedDate = document.getElementById(`date_${memberId}`).value;
    let customMessage = document.getElementById(`message_${memberId}`).value;

    if (!selectedDate) {
        alert("Please select a date before sending a reminder.");
        return;
    }
    if (!customMessage) {
        alert("Please enter a custom message.");
        return;
    }

    $.ajax({
      url: 'send_reminder.php',
      type: 'POST',
      data: { member_id: memberId, selected_date: selectedDate, custom_message: customMessage },
      success: function(response) {
        alert(response);
      },
      error: function() {
        alert("Error sending reminder. Please try again.");
      }
    });
  }
</script>

</body>
</html>
