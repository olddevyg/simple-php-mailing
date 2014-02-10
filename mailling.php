<?php
/* Mailing tool by devyg.fr */

require 'library/PHPMailerAutoload.php';
require 'library/Valitron/Validator.php';

// simple function to display inputs values after failing post
function echo_post($name) {
	echo isset($_POST[$name]) ? $_POST[$name] : '';
}

$msgs = array();
$errors = array();

if ($_POST) {
	// validates inputs
	$v = new Valitron\Validator($_POST, array(), 'fr', 'lang');

	$v->rule('required', 'namefrom');
	$v->rule('required', 'emailfrom');
	$v->rule('required', 'object');
	$v->rule('required', 'message');
	$v->rule('required', 'emailtos');

	$v->rule('email', 'emailfrom');

	if(!$v->validate()) {
		foreach($v->errors() as $err)
	   	array_push($errors, $err);
	}

	$emails = explode($_POST['delimiter'], $_POST['emailtos']);
	
	$emailtos = $emails;
	foreach($emailtos as $emailto) {
		$v2 = new Valitron\Validator(array('emailto'=>$emailto), array(), 'fr', 'lang');
		$v2->rule('email', 'emailto')->message('{field} not valid');

		if(!$v2->validate()) {
			$emails = array_diff($emails, array($emailto));
		}
	}

	// checking file input
	$file = false;
	if (isset($_POST['file']) and isset($_FILES['file'])) {
		if ($_FILES['file']['error'] != UPLOAD_ERR_OK)
			array_push($errors, 'Error attachment');
		else
			$file = $_FILES['file'];
	}

	// if no error(s) try to send the emails
	if(empty($errors)) {

		foreach($emails as $email) {
			$mail = new PHPMailer();
			$mail->From = $_POST['emailfrom'];
			$mail->FromName = $_POST['namefrom'];
			//$mail->addAddress($_POST['emailto']);
			$mail->addReplyTo($_POST['emailfrom'], $_POST['firstname'].' '.$_POST['lastname']);

			if ($file)
				$mail->addAttachment($file['tmp_name'], $file['name']);

			$mail->isHTML(true);
			$mail->CharSet = 'UTF-8';

			$mail->Subject = $_POST['object'];
			$mail->Body = $_POST['message'];
			$mail->AltBody = $_POST['message'];

			$mail->addAddress($email);

			if($mail->send()) {
				array_push($msgs, 'Message sent to '.$email);
				// empty the values
				//unset($_POST);
			} else {
				array_push($errors, array('Error sending to '.$email.' <br>'.$mail->ErrorInfo));
			}
		}
	}
}
?>

<html>
<head>
	<meta charset="utf-8">
	<title>Mailing</title>
	<link rel="stylesheet" href="css/bootstrap.min.css">
</head>

<body>
<div class="container">
	<div class="row">
		<div class="col-md-6">

			<form enctype="multipart/form-data" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" role="form">
				<div class="form-group">
					<label for="namefrom">Name</label>
					<div class="input-group">
							<span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
						<input type="text" name="namefrom" id="namefrom" value="<?php echo_post('namefrom') ?>" class="form-control">
					</div>
				</div>
				<div class="form-group">
					<label for="emailfrom">E-Mail</label>
					<div class="input-group">
							<span class="input-group-addon"><span class="glyphicon glyphicon-envelope"></span></span>
						<input type="text" name="emailfrom" id="emailfrom" value="<?php echo_post('emailfrom') ?>" class="form-control">
					</div>
				</div>
				<div class="form-group">
					<label for="file">File <span class="glyphicon glyphicon-paperclip"></span></label>
					<input type="file" name="file" id="file">
				</div>
				<div class="form-group">
					<label for="emailtos">E-Mails</label>
					<textarea name="emailtos" class="form-control" rows="9"><?php echo_post('emailtos') ?></textarea>
				</div>
				<div class="form-group">
					<label for="emailfrom">Delimiter</label>
					<div class="input-group">
							<span class="input-group-addon"><span class="glyphicon glyphicon-edit"></span></span>
						<input type="text" name="delimiter" id="delimiter" value="<?php echo_post('delimiter') ?>" class="form-control">
					</div>
				</div>
				<div class="form-group">
					<label for="object">Object</label>
					<div class="input-group">
							<span class="input-group-addon"><span class="glyphicon glyphicon-edit"></span></span>
						<input type="text" name="object" id="object" value="<?php echo_post('object') ?>" class="form-control">
					</div>
				</div>
				<div class="form-group">
					<label for="message">Message</label>
					<textarea name="message" class="form-control" rows="18"><?php echo_post('message') ?></textarea>
				</div>
				<div class="form-group">
					<button type="submit" class="btn btn-default">Send</button>
				</div>
			</form>

		</div>

		<div class="col-md-6">
			<?php
			if ($msgs) {
				echo "<div class=\"alert alert-success\">";
				foreach($msgs as $msg) {
					echo $msg.'<br>';
				}
				echo "</div>";
			}

			if ($errors) {
				echo '<div class="alert alert-danger">';
				foreach($errors as $error) {
					foreach($error as $e)
						echo $e.'<br>';
				}
				echo '</div>';
			}
			?>
		</div>
	</div>
</div>

</body>
</html>