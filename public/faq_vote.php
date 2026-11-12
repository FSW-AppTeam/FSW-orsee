<?php
// part of orsee. see orsee.org
ob_start();
$suppress_html_header=true;
$menu_item_id='faqs';
include("header.php");

if ($proceed) {
    if (!isset($_REQUEST['id'])) {
        $_REQUEST['id']="";
    }
    if (!isset($_SESSION['vote'])) {
        $_SESSION['vote']=array();
    }
    if (!isset($_SESSION['vote'][$_REQUEST['id']])) {
        $_SESSION['vote'][$_REQUEST['id']]="";
    }
    if ($_SESSION['vote'][$_REQUEST['id']]) {
        $v_already=true;
    } else {
        $v_already=false;
    }
    if (isset($_REQUEST['eval']) && !($v_already)) {
        if (!csrf__validate_request()) {
            exit;
        }
        $query="UPDATE ".table('faqs')." SET evaluation=evaluation+1 WHERE faq_id=:id";
        $pars=array(':id'=>$_REQUEST['id']);
        $done=or_query($query,$pars);
        $_SESSION['vote'][$_REQUEST['id']]=true;
    }
}
?>
