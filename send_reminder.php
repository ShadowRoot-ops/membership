<?php
include('includes/config.php');
include('includes/twilio.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $memberId = $_POST['member_id'];
    $selectedDate = $_POST['selected_date'];
    $customMessage = $_POST['custom_message'];

    // Fetch member details
    $fetchMemberQuery = "SELECT fullname, contact_number FROM members WHERE id = $memberId";
    $fetchMemberResult = $conn->query($fetchMemberQuery);

    if ($fetchMemberResult->num_rows > 0) {
        $memberDetails = $fetchMemberResult->fetch_assoc();
        $contactNumber = $memberDetails['contact_number'];

        if (!empty($contactNumber)) {
            if (substr($contactNumber, 0, 1) !== "+") {
                $contactNumber = "+91" . $contactNumber; // Modify for your country code
            }

            $message = "Hello {$memberDetails['fullname']},\n\n{$customMessage}\n\nRenewal Date: {$selectedDate}.\n\nThank you!";

            // Send WhatsApp message
            sendWhatsAppMessage($contactNumber, $message);

            echo "Reminder sent successfully to {$memberDetails['fullname']}.";
        } else {
            echo "Error: No contact number available.";
        }
    } else {
        echo "Error: Member not found.";
    }
}
?>
