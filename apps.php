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

<div class="congrats2">Select the app from the list. Are you browsing from your iOS device?</div>
<div class="congrats2">Remember also that your iOS device UUID must have been submitted to Moodsdesign for this to work.</div>
<div class="congrats2">You can <a href="?logout">click here</a> to log out.</div>

<?php if ( empty($apps) ) { ?>
    <div class="congrats3">Sorry!</div>
    <div class="congrats3">There are no apps for you to test at the moment...</div>
<?php } else { ?>
    <?php foreach ( $apps as $plistFilename => $ipaPlist ) { ?>
        <div class="step">
            <table>
                <tr>
                    <td class="instructions">
                        <b><?php echo $ipaPlist->getValue()->get('items')->get(0)->get('metadata')->get('title')->getValue(); ?></b>
                        v<?php echo $ipaPlist->getValue()->get('items')->get(0)->get('metadata')->get('bundle-version')->getValue(); ?>
                        <br/>
                        <i><?php echo $ipaPlist->getValue()->get('items')->get(0)->get('metadata')->get('bundle-identifier')->getValue(); ?></i>
                    </td>
                    <td width="24" class="arrow">&rarr;</td>
                    <td width="72" class="imagelink">
                        <a href="itms-services://?action=download-manifest&url=<?php echo "$IOSADHOC_BASE_URL/$username/$plistFilename.plist"; ?>">
                            <img src="<?php echo $ipaPlist->adhocota_iconSrc; ?>" height="72" width="72" />
                        </a>
                    </td>
                </tr>
            </table>
        </div>
    <?php } ?>
<?php } ?>
</body>
</html>
