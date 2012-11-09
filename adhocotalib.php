<?php

require_once('CFPropertyList/classes/CFPropertyList/CFPropertyList.php');
require_once('PngUncrush/pnguncrush.php');

function webui_session_start() {
    global $IOSADHOC_TIMEOUT;
    session_start();
    // this type of timeout is not reset with client activity
    if ( !isset($_SESSION['created_ts']) ) {
        $_SESSION['created_ts'] = time();
    } else {
        $created_ts = $_SESSION['created_ts'];
        if ( time() - $created_ts >= $IOSADHOC_TIMEOUT) {
            webui_logout();
        }
    }
}

function webui_forward($page, $vars = array()) {
    extract($vars);
    require("$page.php");
    exit;
}

function cliui_exit($message) {
    echo "\n\n$message\n\n";
    exit(1);
}

function webui_login($username) {
    $_SESSION['username'] = $username;
}

function webui_loggedin() {
    return $_SESSION['username'];
}

function webui_logout() {
    unset($_SESSION);
    session_destroy();
    session_start();
}

function adhocota_find_apps($username) {
    global $IOSADHOC_BASE_DIR, $IOSADHOC_BASE_URL;
    $apps = array();
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
            if ( IS_PHP_CLI ) {
                echo "  plist does not exist\n";
            }
            $zip = zip_open($ipaFilename);
            if ( !is_resource($zip) ) {
                if ( IS_PHP_CLI ) echo "  Sorry! Could not open $ipaFilename ($zip)\n";
                continue;
            }
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
                        if ( pnguncrush_decode_data($iconContents, $pngFilename) !== false ) {
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
        if ( !file_exists($iconSrc = $pngFilename) ) {
            $iconSrc = 'app_store_icon.jpg';
        }
        $ipaPList = new \CFPropertyList\CFPropertyList($plistFilename);
        $ipaPList->adhocota_iconSrc = $iconSrc;
        $apps[$ipaBasenameNoExt] = $ipaPList;
    }
    return $apps;
}
