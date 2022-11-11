<?php
/// This program is free software; you can redistribute it and/or modify
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

if (isset($_POST["action"]) && $_POST["action"] == "forgot") {
	$username = $_POST["username"];

	try {
		// make sure that username is valid 
		$stmt = $smarty->dbh()->prepare("SELECT email FROM {$opt["table_prefix"]}users WHERE username = ?");
		$stmt->bindParam(1, $username, PDO::PARAM_STR);
			
		$stmt->execute();
		if ($row = $stmt->fetch()) {
			$email = $row["email"];
		
			if ($email == "")
				$error = "The username '" . $username . "' does not have an e-mail address, so the password could not be sent.";
			else {
				$pwd = generatePassword($opt);
				$stmt = $smarty->dbh()->prepare("UPDATE {$opt["table_prefix"]}users SET password = {$opt["password_hasher"]}(?) WHERE username = ?");
				$stmt->bindParam(1, $pwd, PDO::PARAM_STR);
				$stmt->bindParam(2, $username, PDO::PARAM_STR);

				$stmt->execute();
				if ($opt[ses_email_server]=="") {
					mail(
					$email,
					"Gift Registry password reset",
					"Your Gift Registry account information:\r\n" . 
						"Your username is '" . $username . "' and your new password is '$pwd'.",
					"From: {$opt["email_from"]}\r\nReply-To: {$opt["email_reply_to"]}\r\nX-Mailer: {$opt["email_xmailer"]}\r\n"
				) or die("Mail not accepted for $email");
} else {				
				$mail = new PHPMailer(true);

				try {
				    // Specify the SMTP settings.
				    $mail->isSMTP();
				    $mail->setFrom($opt["email_from"], "PHP Gift Registry");
				    $mail->Username   = $opt["ses_email_username"];
				    $mail->Password   = $opt["ses_email_password"];
						$mail->Host       = $opt[ses_email_server];
				    $mail->Port       = 587;
				    $mail->SMTPAuth   = true;
				    $mail->SMTPSecure = 'tls';
				    //$mail->addCustomHeader('X-SES-CONFIGURATION-SET', $configurationSet);

				    // Specify the message recipients.
				    $mail->addAddress($email);
				    // You can also add CC, BCC, and additional To recipients here.

				    // Specify the content of the message.
				    $mail->isHTML(false);
				    $mail->Subject    = "PHP Gift Registry: Forgotten Password";
				    $mail->Body       = "Your username is '" . $username . "' and your new password is '$pwd'.";
				    //$mail->AltBody    = "test alt body";
				    $mail->Send();
				    //echo "Email sent!" , PHP_EOL;
				} catch (phpmailerException $e) {
				    echo "An error occurred. {$e->errorMessage()}", PHP_EOL; //Catch errors from PHPMailer.
				} catch (Exception $e) {
				    echo "Email not sent. {$mail->ErrorInfo}", PHP_EOL; //Catch errors from Amazon SES.
				}

			}

			}
		}
		else {
			$error = "The username '" . $username . "' could not be found.";
		}

		if (!empty($error)) {
			$smarty->assign('error', $error);
		}
		$smarty->assign('action', $_POST["action"]);
		$smarty->assign('username', $username);
		$smarty->display('forgot.tpl');
	}
	catch (PDOException $e) {
		die("sql exception: " . $e->getMessage());
	}
}
else {
	$smarty->display('forgot.tpl');
}
?>
