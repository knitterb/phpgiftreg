<?php
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/Exception.php';
require 'phpmailer/SMTP.php';
require 'phpmailer/PHPMailer.php';



require_once(dirname(__FILE__) . "/includes/funcLib.php");
require_once(dirname(__FILE__) . "/includes/MySmarty.class.php");
$smarty = new MySmarty();
$opt = $smarty->opt();

session_start();
if (!isset($_SESSION["userid"])) {
	header("Location: " . getFullPath("login.php"));
	exit;
}
else if ($_SESSION["admin"] != 1) {
	echo "You don't have admin privileges.";
	exit;
}
else {
	$userid = $_SESSION["userid"];
}

$action = $_GET["action"];
if ($action == "approve") {
	$pwd = generatePassword($opt);
	if ($_GET["familyid"] != "") {
		$stmt = $smarty->dbh()->prepare("INSERT INTO {$opt["table_prefix"]}memberships(userid,familyid) VALUES(?, ?)");
		$stmt->bindValue(1, (int) $_GET["userid"], PDO::PARAM_INT);
		$stmt->bindValue(2, (int) $_GET["familyid"], PDO::PARAM_INT);
		$stmt->execute();
	}
	$stmt = $smarty->dbh()->prepare("UPDATE {$opt["table_prefix"]}users SET approved = 1, password = {$opt["password_hasher"]}(?) WHERE userid = ?");
	$stmt->bindParam(1, $pwd, PDO::PARAM_INT);
	$stmt->bindValue(2, (int) $_GET["userid"], PDO::PARAM_INT);
	$stmt->execute();
	
	// send the e-mails
	$stmt = $smarty->dbh()->prepare("SELECT username, email FROM {$opt["table_prefix"]}users WHERE userid = ?");
	$stmt->bindValue(1, (int) $_GET["userid"], PDO::PARAM_INT);
	$stmt->execute();
	if ($row = $stmt->fetch()) {
		/*
		mail(
			$row["email"],
			"Gift Registry application approved",
			"Your Gift Registry application was approved by " . $_SESSION["fullname"] . ".\r\n" . 
				"Your username is " . $row["username"] . " and your password is $pwd.",
			"From: {$opt["email_from"]}\r\nReply-To: {$opt["email_reply_to"]}\r\nX-Mailer: {$opt["email_xmailer"]}\r\n"
		) or die("Mail not accepted for " . $row["email"]);	
		 */


      $mail = new PHPMailer(true);

      try {
          // Specify the SMTP settings.
          $mail->isSMTP();
          $mail->setFrom($opt["email_from"], "PHP Gift Registry");
                      $mail->Username   = $opt["ses_email_username"];
          $mail->Password   = $opt["ses_email_password"];

          $mail->Host       = "email-smtp.us-east-1.amazonaws.com";
          $mail->Port       = 587;
          $mail->SMTPAuth   = true;
          $mail->SMTPSecure = 'tls';
          //$mail->addCustomHeader('X-SES-CONFIGURATION-SET', $configurationSet);

          // Specify the message recipients.
          $mail->addAddress($row["email"]);
          // You can also add CC, BCC, and additional To recipients here.

          // Specify the content of the message.
          $mail->isHTML(false);
          $mail->Subject    = "PHP Gift Registry: Account Approved";
          $mail->Body       = "Your username is '" . $row["username"] . "' and your new password is '$pwd'.";
          //$mail->AltBody    = "test alt body";
          $mail->Send();
          //echo "Email sent!" , PHP_EOL;
      } catch (phpmailerException $e) {
          echo "An error occurred. {$e->errorMessage()}", PHP_EOL; //Catch errors from PHPMailer.
      } catch (Exception $e) {
          echo "Email not sent. {$mail->ErrorInfo}", PHP_EOL; //Catch errors from Amazon SES.
      }


	}
	header("Location: " . getFullPath("index.php"));
	exit;
}
else if ($action == "reject") {
	// send the e-mails
	$stmt = $smarty->dbh()->prepare("SELECT email FROM {$opt["table_prefix"]}users WHERE userid = ?");
	$stmt->bindValue(1, (int) $_GET["userid"], PDO::PARAM_INT);
	$stmt->execute();
	if ($row = $stmt->fetch()) {
		/*
		mail(
			$row["email"],
			"Gift Registry application denied",
			"Your Gift Registry application was denied by " . $_SESSION["fullname"] . ".",
			"From: {$opt["email_from"]}\r\nReply-To: {$opt["email_reply_to"]}\r\nX-Mailer: {$opt["email_xmailer"]}\r\n"
		) or die("Mail not accepted for " . $row["email"]);	
		 */
      $mail = new PHPMailer(true);

      try {
        // Specify the SMTP settings.
        $mail->isSMTP();
        $mail->setFrom($opt["email_from"], "PHP Gift Registry");
        $mail->Username   = $opt["ses_email_username"];
        $mail->Password   = $opt["ses_email_password"];

        $mail->Host       = "email-smtp.us-east-1.amazonaws.com";
        $mail->Port       = 587;
        $mail->SMTPAuth   = true;
        $mail->SMTPSecure = 'tls';
        //$mail->addCustomHeader('X-SES-CONFIGURATION-SET', $configurationSet);

        // Specify the message recipients.
        $mail->addAddress($row["email"]);
        // You can also add CC, BCC, and additional To recipients here.

        // Specify the content of the message.
        $mail->isHTML(false);
        $mail->Subject    = "PHP Gift Registry: Account Denied";
        $mail->Body       = "Your Gift Registry application was denied by " . $_SESSION["fullname"] . ".";
        //$mail->AltBody    = "test alt body";
        $mail->Send();
        //echo "Email sent!" , PHP_EOL;
    } catch (phpmailerException $e) {
        echo "An error occurred. {$e->errorMessage()}", PHP_EOL; //Catch errors from PHPMailer.
    } catch (Exception $e) {
        echo "Email not sent. {$mail->ErrorInfo}", PHP_EOL; //Catch errors from Amazon SES.
    }

	}

	$stmt = $smarty->dbh()->prepare("DELETE FROM {$opt["table_prefix"]}users WHERE userid = ?");
	$stmt->bindValue(1, (int) $_GET["userid"], PDO::PARAM_INT);
	$stmt->execute();
	
	header("Location: " . getFullPath("index.php"));
	exit;
}
?>
