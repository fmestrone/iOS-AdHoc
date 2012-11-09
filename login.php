<?php
if ( !defined('IOS_ADHOC_OTA') ) {
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<link href="style.css" rel="stylesheet" />
<title>Moodsdesign AdHoc Testing</title>
</head>
<body>

<div class="congrats">Have you been invited to test one of our apps?</div>
<?php if ( $error_message ) { ?>
    <div class="congrats4"><?php echo $error_message; ?></div>
<?php } ?>
<div class="congrats2">Provide your access credentials using the form below</div>
<div class="congrats2">
    <form method="post" action="?">
        <p>Username : <input type="text" name="usr" /></p>
        <p>Password : <input type="password" name="pwd" /></p>
        <p><input type="submit" value="Submit" /></p>
    </form>
</div>
</body>
</html>
