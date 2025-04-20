<?php

/**
*  
*  FUNCTIONS USED FOR SENDNING EMAILS
*  WILL NOT FUNCTION XAMPP ONLY ON SITEGROUND
*  getEmailByType() + getAllEmails()  sendEmails() are helper functions 
*  but are left non-private to allow for using them if needed/being lazy
*  emailing should only be done through the email***() functions
*  sending emails is done through the php function mail()
*  
**/


/**
 * Fetch all emails from dbpersons matching a given type.
 *
 * @param string $type  e.g. 'volunteer', 'admin', 'board', 'donator', 'participant'
 * @return array       List of email strings
 */
function getEmailsByType(string $type): array {
    include_once('database/dbinfo.php');
    $conn = connect();
    $stmt = $conn->prepare("SELECT email FROM dbpersons WHERE type = ?");
    $stmt->bind_param('s', $type);
    $stmt->execute();
    $res = $stmt->get_result();
    $emails = [];
    while ($row = $res->fetch_assoc()) {
        $emails[] = $row['email'];
    }
    $stmt->close();
    $conn->close();
    return $emails;
}

/**
 * Fetch every email in dbpersons.
 *
 * @return array
 */
function getAllEmails(): array {
    include_once('database/dbinfo.php');
    $conn = connect();
    $res  = $conn->query("SELECT email FROM dbpersons");
    $emails = [];
    while ($row = $res->fetch_assoc()) {
        $emails[] = $row['email'];
    }
    $conn->close();
    return $emails;
}

/**
 * Send emails to all volunteers.
 */
function emailVolunteer(string $fromUser, string $subject, string $body): array {
    $list = getEmailsByType('volunteer');
    return sendEmails($list, $fromUser, $subject, $body);
}

/**
 *  EACH EMAIL TYPE IS A SEPERATE FUNCTION TO REQUIRE 1 LESS PARAM AND INCREASE READIBILITY
 **/
function emailAdmin(string $fromUser, string $subject, string $body): array {
    $list = getEmailsByType('admin');
    return sendEmails($list, $fromUser, $subject, $body);
}

function emailBoardMember(string $fromUser, string $subject, string $body): array {
    $list = getEmailsByType('board');
    return sendEmails($list, $fromUser, $subject, $body);
}

function emailDonor(string $fromUser, string $subject, string $body): array {
    $list = getEmailsByType('donor');
    return sendEmails($list, $fromUser, $subject, $body);
}

function emailParti(string $fromUser, string $subject, string $body): array {
    $list = getEmailsByType('participant');
    return sendEmails($list, $fromUser, $subject, $body);
}

function emailAll(string $fromUser, string $subject, string $body): array {
    $list = getAllEmails();
    return sendEmails($list, $fromUser, $subject, $body);
}

/**
 * Send emails to each address in the supplied list.
 *  
 *  ------->MAY NEED FIELDS CHANGED BEFORE REAL PRODUCTION <-----------
 *   -------> DOMAIN WILL ALMOST CERTIANLY CHANGE IN PRODUCTION <------------ 
 *
 * @param array  $emails   List of recipient email addresses.
 * @param string $fromUser Local-part for the From address.
 * @param string $subject  Email subject.
 * @param string $body     Email body.
 * @return array Returns an  array where keys are emails and values are boolean statuses.
 */
function sendEmails(array $emails, string $fromUser, string $subject, string $body): array {
    //I wish the site url would work since it would be prettier but it stops working after 
    //a certian amount of emails
    //$domain = 'jenniferp161.sg-host.com';
    $domain = 'fgpfamily.org';   // <------------- NEEDS TO BE CHANGED ON LIVE PRODUCTION!!! 
    $fromAddress = "{$fromUser}@{$domain}";
    
    // Simplified headers – only include essential information.
       $headers = "From: {$fromAddress}\r\n";
    
    $results = [];
    foreach ($emails as $email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $results[$email] = mail($email, $subject, $body, $headers);
        } else {
            $results[$email] = false;
        }
    }
    return $results;
}

/**
 * Send a single email to a specified recipient.
 * 
 * @param string $toEmail Recipient email address.
 * @param string $subject Email subject.
 * @param string $body Email body content.
 * @return bool Returns true if the email was successfully accepted for delivery, false otherwise.
 */
function sendIndividualEmail(string $toEmail, string $subject, string $body): bool {
    // Hardcoded From address
    $fromAddress = 'notifications@fgpfamily.org';

    // Set email headers
    $headers = "From: {$fromAddress}\r\n";

    // Validate recipient email before sending
    if (filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return mail($toEmail, $subject, $body, $headers);
    } else {
        return false;
    }
}

/**
 * Notify the user that their Points Program form has been updated.
 *
 * Sends a personalized confirmation email to the user whose form was updated,
 * including their first and last name.
 *
 * @param string $userEmail The user's email address.
 * @param string $firstName The user's first name.
 * @param string $lastName The user's last name.
 * @return bool True if the email was successfully sent, false otherwise.
 */
function notifyUserOfPointsUpdate(string $userEmail, string $firstName, string $lastName): bool {
    $subject = "Confirmation: Points Program Form Updated";

    $body  = "Dear {$firstName} {$lastName},\n\n";
    $body .= "Your Points Program form associated with $userEmail has been successfully updated.\n\n";
    $body .= "If you did not make this change, please contact fgpfamily.org.\n\n";
    $body .= "Thank you,\nFGP Team";

    // ===== TESTING ONLY =====
    //$testEmail = "jwilli39@mail.umw.edu";
    //return mail($testEmail, $subject, $body, "From: notifications@fgpfamily.org\r\n");
    // ===== END TESTING =====

    return sendIndividualEmail($userEmail, $subject, $body); // <--- uncomment after test
}

/**
 * Notify all admins that a user's Points Program form has been updated.
 *
 * Sends an email to all admins with the name and email of the user
 * whose Points Program form was modified.
 *
 * @param string $userEmail The email address of the updated user.
 * @param string $firstName The user's first name.
 * @param string $lastName The user's last name.
 * @return array An array of email addresses with send status as values.
 */
function notifyAdminsOfPointsUpdate(string $userEmail, string $firstName, string $lastName): array {
    $subject = "Points Program Form Updated";

    $body  = "An update has been submitted for the Points Program form.\n\n";
    $body .= "User: {$firstName} {$lastName}\n";
    $body .= "Email: {$userEmail}\n\n";
    $body .= "Please log in to the dashboard to review the changes.\n\n";
    $body .= "FGP System";

    // ===== TESTING ONLY =====
    //$testEmails = ["jwilli39@mail.umw.edu"]; 
    //return sendEmails($testEmails, 'notifications', $subject, $body);
    // ===== END TESTING =====

    return emailAdmin('notifications', $subject, $body); // <--- uncomment after test
}

?>
