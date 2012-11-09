<?php
define('IOS_ADHOC_OTA', true);
define('IS_PHP_CLI', PHP_SAPI == 'cli');

require_once('config.php');
require_once('adhocotalib.php');

if ( IS_PHP_CLI ) {
    error_reporting(E_ALL);
    ini_set('display_errors', true);
    $username = $argv[1];
    $password = $argv[2];
} else {
    // E_NONE is not a predefined constant
    error_reporting(E_ALL);
    ini_set('display_errors', false);
    webui_session_start();
    $username = $_POST['usr'];
    $password = $_POST['pwd'];
}

if ( $_SERVER['QUERY_STRING'] == 'logout' ) {
    webui_logout();
}
if ( webui_loggedin() ) {
    $username = webui_loggedin();
    $apps = adhocota_find_apps($username);
    webui_forward('apps', compact('apps', 'username'));
} else {
    if ( empty($username) || empty($password) ) {
        if ( IS_PHP_CLI ) {
            cliui_exit('Missing username and password!');
        } else {
            webui_logout();
            if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
                $error_message = 'You must specify both a username and a password to log in';
            }
            webui_forward('login', compact('error_message'));
        }
    } else {
        $access = false;
        $accountPasswordFile = "$IOSADHOC_BASE_DIR/$username/__password.php";
        if ( file_exists($accountPasswordFile) ) {
            require($accountPasswordFile);
            if ( $password === $accountPassword ) {
                $access = true;
            }
        }
        if ( $access ) {
            webui_login($username);
            webui_forward('standin');
        } else {
            if ( IS_PHP_CLI ) {
                cliui_exit('Wrong username and/or password!');
            } else {
                webui_logout();
                $error_message = 'Your access credentials are incorrect. Try again!';
                webui_forward('login', compact('error_message'));
            }
        }
    }
}
