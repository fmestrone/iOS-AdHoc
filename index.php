<?php
$IOSADHOC_BASE_URL = 'http://fedmest.com/iosadhoc/data'; // no trailing slash
$IOSADHOC_BASE_DIR = './data'; // no trailing slash


define('IS_PHP_CLI', PHP_SAPI == 'cli');

if ( IS_PHP_CLI ) {
    error_reporting(E_ALL);
    ini_set('display_errors', true);
    $username = $argv[1];
    $password = $argv[2];
} else {
    // E_NONE is not a predefined constant
    error_reporting(E_ERROR);
    ini_set('display_errors', false);
    $username = $_POST['usr'];
    $password = $_POST['pwd'];
}

if ( !IS_PHP_CLI ) {
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<title>Moodsdesign AdHoc Testing</title>
<style type="text/css">
body {
	background: url(ios_table_bkg.png) repeat #c5ccd4;
	font-family: Helvetica, arial, sans-serif;
}

.congrats {
	font-size: 16pt;
	padding: 6px;
	text-align: center;
}

.congrats2 {
	font-size: 14pt;
	padding: 6px;
	text-align: center;
}

.congrats3 {
	font-size: 14pt;
	font-style: italic;
	padding: 10px;
	text-align: center;
}

.congrats4 {
    font-size: 14pt;
    font-weight: bolder;
    padding: 6px;
    text-align: center;
    color: red;
}

.step {
	background: white;
	border: 1px #ccc solid;
	border-radius: 14px;
	padding: 4px 10px;
	margin: 10px 0;
}

.instructions {
	font-size: 10pt;
	overflow: hidden;
}

.arrow {
	font-size: 15pt;
}

table {
	width: 100%;
}
</style>
</head>
<body>

<div class="congrats">Have you been invited to test one of our apps?</div>

<?php
}

$empty = null;

if ( empty($username) || empty($password) ) {
	if ( IS_PHP_CLI ) {
		echo "\n\nMissing username and password!\n\n";
		exit(1);
	} else {
        if ( $_SERVER['REQUEST_METHOD'] == 'POST' && (empty($username) || empty($password)) ) {
?>
        <div class="congrats4">You must specify a username and a password to log in</div>
<?php
        }
?>
<div class="congrats2">Provide your access credentials using the form below</div>
<div class="congrats2">
	<form method="post">
		<p>Username : <input type="text" name="usr" /></p>
		<p>Password : <input type="password" name="pwd" /></p>
		<p><input type="submit" value="Submit" /></p>
	</form>
</div>
<?php
	}
} else {
	$access = false;
	$accountPasswordFile = "$IOSADHOC_BASE_DIR/$username/__password.php";
	if ( file_exists($accountPasswordFile) ) {
		require $accountPasswordFile;
		if ( $password === $accountPassword ) {
			$access = true;
		}
	}
	if ( !$access ) {
		if ( IS_PHP_CLI ) {
			echo 'Wrong username and/or password!';
			exit(1);
		} else {
?>
<div class="congrats4">Your access credentials are incorrect. Sorry!</div>
</body>
</html>
<?php
			die;		
		}
	}
	if ( !IS_PHP_CLI ) {
?>

<div class="congrats2">Select the app from the list. Are you browsing from your iOS device?</div>
<div class="congrats2">Remember also that your iOS device UUID must have been submitted to Moodsdesign for this to work.</div>

<?php
	}

	require_once('CFPropertyList/classes/CFPropertyList/CFPropertyList.php');
	require_once('iospng/iospng.php');

	$empty = true;
	foreach ( glob("$IOSADHOC_BASE_DIR/$username/*.ipa") as $ipaFilename ) {
		$ipaBasename = basename($ipaFilename);
		$ipaBasenameNoExt = basename($ipaFilename, ".ipa");
		$plistFilename = substr($ipaFilename, 0, -3) . 'plist';
		$pngFilename = substr($ipaFilename, 0, -3) . 'png';
		if ( IS_PHP_CLI ) echo "Found IPA Filename : $ipaFilename\n";
		if ( IS_PHP_CLI ) echo "  IPA Basename : $ipaBasename\n";
		if ( IS_PHP_CLI ) echo "  IPA Basename No Extension : $ipaBasenameNoExt\n";
		if ( IS_PHP_CLI ) echo "  plist Filename : $plistFilename\n";
		if ( IS_PHP_CLI ) echo "  PNG Filename : $pngFilename\n";
		if ( !file_exists($plistFilename) ) {
            flush();
			if ( IS_PHP_CLI ) {
                echo "  plist does not exist\n";
            } else {
                /*
?>
                <div class="congrats2">
                    Found IPA files with no metadata. Please wait as we generate the metadata...<br>
                    <img src="loading.gif" height="32" width="32" />
                </div>
<?php
                flush();
                */
            }
			$zip = zip_open($ipaFilename);
			if ( !is_resource($zip) ) {
				if ( IS_PHP_CLI ) echo "  Sorry! Could not open $ipaFilename ($zip)\n";
				continue;
			}
			unset($iconFile);
			$iconFound = false;
			$plistFound = false;
			while ( $entry = zip_read($zip) ) {
			    $entry_name = zip_entry_name($entry);
				if ( preg_match('/Payload\/([ a-zA-Z0-9.-_]+).app\/Info.plist/', $entry_name) && zip_entry_filesize($entry) ) {
					if ( IS_PHP_CLI ) echo "  found non-empty Info.plist in $entry_name\n";
		            if ( zip_entry_open($zip, $entry) ) {
						$plistContents = zip_entry_read($entry, zip_entry_filesize($entry));
						$infoPlist = new \CFPropertyList\CFPropertyList();
						$infoPlist->parseBinary($plistContents);
						$plistFound = true;
	                }
	                zip_entry_close($entry);
				}
				if ( preg_match('/Payload\/([ a-zA-Z0-9.-_]+).app\/[Ii]con.*72.png/', $entry_name) ||
				     preg_match('/Payload\/([ a-zA-Z0-9.-_]+).app\/[Ii]con.*i[pP]ad.png/', $entry_name) && zip_entry_filesize($entry) ) {
					if ( IS_PHP_CLI ) echo "  found icon file 72x72 in $entry_name\n";
		            if ( zip_entry_open($zip, $entry) ) {
						$iconContents = zip_entry_read($entry, zip_entry_filesize($entry));
						$iconContents = normalizePNG4iOS($iconContents);
						if ( $iconContents ) {
							file_put_contents($pngFilename, $iconContents);
							$iconFound = true;
						}
	                }
	                zip_entry_close($entry);
				}
			}
			zip_close($zip);
			if ( !$plistFound ) {
				if ( IS_PHP_CLI ) echo "  Could not find Info.plist in IPA - skipping app...\n";
				continue;
			}
			if ( !$iconFound ) {
				if ( IS_PHP_CLI ) echo "  Could not find icon file in IPA - will use default...\n";
			}
            // because 'use' statements are ineffective on CLI, have to fully qualify class names here
			$newPlist = new \CFPropertyList\CFPropertyList();
			$newPlist->add($dict = new \CFPropertyList\CFDictionary());
			$dict->add('items', $array = new \CFPropertyList\CFArray());
			$array->add($dictItem1 = new \CFPropertyList\CFDictionary());
			$dictItem1->add('assets', $arrayAssets = new \CFPropertyList\CFArray());
			$dictItem1->add('metadata', $dictMeta = new \CFPropertyList\CFDictionary());
			$arrayAssets->add($dictAsset1 = new \CFPropertyList\CFDictionary());
			$dictAsset1->add('kind', new \CFPropertyList\CFString('software-package'));
			$dictAsset1->add('url', new \CFPropertyList\CFString("$IOSADHOC_BASE_URL/$username/$ipaBasename"));
			if ( file_exists($pngFilename) ) {
				$arrayAssets->add($dictAsset2 = new \CFPropertyList\CFDictionary());
				$dictAsset2->add('kind', new \CFPropertyList\CFString('display-image'));
				$dictAsset2->add('needs-shine', new \CFPropertyList\CFBoolean(false));
				$dictAsset2->add('url', new \CFPropertyList\CFString("$IOSADHOC_BASE_URL/$username/" . basename($pngFilename)));
			}
			$dictMeta->add('bundle-identifier', $infoPlist->getValue()->get('CFBundleIdentifier'));
			$dictMeta->add('bundle-version', $infoPlist->getValue()->get('CFBundleVersion'));
			$dictMeta->add('kind', new \CFPropertyList\CFString('software'));
			$dictMeta->add('title', $infoPlist->getValue()->get('CFBundleName'));
			$newPlist->saveXML($plistFilename);
		}
		$ipaPlist = new \CFPropertyList\CFPropertyList($plistFilename);
		if ( file_exists($pngFilename) ) {
			$iconSrc = "data/$username/" . basename($pngFilename);
		} else {
			$iconSrc = 'app_store_icon.jpg';
		}
		if ( !IS_PHP_CLI ) {
			$empty = false;
?>
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
			<a href="itms-services://?action=download-manifest&url=<?php echo $IOSADHOC_BASE_URL . '/' . $username . '/' . basename($plistFilename); ?>">
				<img src="<?php echo $iconSrc; ?>" height="72" width="72" />
			</a>
		</td>
	</tr>
</table>
</div>
<?php
		}
	}
	
}

if ( !IS_PHP_CLI ) {
	if ( $empty === true ) {
?>
<div class="congrats3">Sorry!</div>
<div class="congrats3">There are no apps for you to test at the moment...</div>
<?php
	}
?>
</body>
</html>
<?php
}
?>
