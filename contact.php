<?php require_once 'header.php'; ?>

<body>
    <div class ="container">
<?php if(!empty($_POST['message']) && !empty($_POST['sender']) && filter_var($_POST['sender'], FILTER_VALIDATE_EMAIL))
{
    $to = 'eyu@tradegroup.com';
    $subject = 'Message regarding Password Generator';
    $message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);
    $firstname = filter_var($_POST['firstname'], FILTER_SANITIZE_STRING);
    $lastname = filter_var($_POST['lastname'], FILTER_SANITIZE_STRING);
    $headers = 'From: ' . $firstname . ' ' . $lastname . ' <' . $_POST['sender'] . '>' . "\r\n";
    
    mail($to, $subject, $message, $headers);
    print "<p = class='text-success'>Thank you, your message has been sent.</p>";
}
    ?>
<form class="well span8" action ="contact.php" method ="post">
  <div class="row">
		<div class="span3">
			<label>First Name</label>
			<input type="text" class="span3" name = "firstname" placeholder="Your First Name">
			<label>Last Name</label>
			<input type="text" class="span3" name = "lastname" placeholder="Your Last Name">
			<label>Email Address</label>
			<input type="text" class="span3" name = "sender" placeholder="Your email address">
		</div>
		<div class="span5">
			<label>Message</label>
			<textarea name="message" id="message" class="input-xlarge span5" rows="10"></textarea>
		</div>
		<button type="submit" class="btn btn-primary pull-right">Send</button>
	</div>
    
    </div>
</form>

<?php require_once 'footer.php'; ?>
