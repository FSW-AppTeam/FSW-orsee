<?php
// part of orsee. see orsee.org
$debug__script_started=microtime();
include ("../config/settings.php");
include ("../config/system.php");
include ("../config/requires.php");

error_reporting(0);  // shut down all error reporting

$proceed=true;

header('X-Frame-Options: SAMEORIGIN');

if ($proceed) {
    $document=thisdoc();
    if ($settings__stop_admin_site=="y" && $document!="error_temporarily_disabled.php")
        redirect("admin/error_temporarily_disabled.php");
}

$_REQUEST = stripTagsRequestArray($_REQUEST, array('requested_url', 'sign', 'experimenter', 'experimenter_mail'));
if(isset($_REQUEST['requested_url'])) {
    // ruin possible XSS attempts
    $_REQUEST['requested_url'] = str_replace( array("'", '"', '(', ')', ':', ';', '='), '',$_REQUEST['requested_url']);
}

if ($proceed) {
    site__database_config();

    $settings=load_settings();
    $settings['style']=$settings['orsee_admin_style'];
    $color=load_colors();

    orsee_session_register_handler();

    session_start();

    $currentCookieParams = session_get_cookie_params();

    // set cookie flags secure and http
    session_set_cookie_params(
        $currentCookieParams['lifetime'],
        $currentCookieParams['path'],
        $currentCookieParams['domain'],
        true,
        true
    );

    if (isset($_SESSION['expadmindata'])) {
        $expadmindata = $_SESSION['expadmindata'];
    }
    else {
        $expadmindata = array();
    }

    $tmparr=explode("/",$_SERVER['PHP_SELF']); $tmpnum=count($tmparr);
    if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING']) $query_string='?'.$_SERVER['QUERY_STRING'];
    else $query_string='';
    $requested_url=$tmparr[$tmpnum-2]."/".$tmparr[$tmpnum-1].$query_string;

    // Check for login
    if ((!(isset($expadmindata['adminname']) && $expadmindata['adminname'])) && $document!="admin_login.php") {
        redirect ("admin/admin_login.php?requested_url=".urlencode($requested_url));
    }
}

if ($proceed) {

    if(!isset($_SESSION['csrf_token'])) {
        // new token to be taken into each form
        if (function_exists('mcrypt_create_iv')) {
            $_SESSION['csrf_token'] = bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
        } else {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }

    if (isset($expadmindata['pw_update_requested']) && $expadmindata['pw_update_requested']  && $document!="admin_pw.php") {
        message(lang('please_change_your_password'));
        redirect ("admin/admin_pw.php");
    }
}

if ($proceed) {
    if (isset($_REQUEST['new_language'])) {
        $expadmindata['language']=$_REQUEST['new_language'];
        $_SESSION['expadmindata']=$expadmindata;
    }

    if (!isset($expadmindata['language']))
        $expadmindata['language']=$settings['admin_standard_language'];

    $authdata['language']=$expadmindata['language'];
    $_SESSION['authdata']=$authdata;

    $lang=load_language($expadmindata['language']);

    $done=check_database_upgrade();

    if (!isset($title)) $title="";
    if ($title) $title=lang($title);
    $pagetitle=$settings['default_area'].': '.$title;

    html__header();
    html__show_style_header('admin',$title);

    echo "<center>";

    show_message();

    echo "</center>";
}

?>
