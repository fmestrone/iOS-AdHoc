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
    <link rel="stylesheet" type="text/css" href="style.css" />
    <title>Moodsdesign AdHoc Testing</title>
</head>
<body>

<div class="congrats2">
    You logged on successfully.<br>Please wait while we load the next page.<br><br><br><br>
    <img src="loading.gif" height="32" width="220" />
</div>
<script>
    setTimeout(function() { window.location.reload(); }, 2750);
</script>

</body>
</html>
