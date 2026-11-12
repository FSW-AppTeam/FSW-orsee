<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="login";
$title="profile_login";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['logout']) && $_REQUEST['logout']) {
        message(lang('logout'),'note',null,'toast');
    }
    if (isset($_REQUEST['pw']) && $_REQUEST['pw']) {
        message(lang('logout'),'note',null,'toast');
        message(lang('password_changed_log_in_again'));
    }

    if (isset($_REQUEST['requested_url']) && $_REQUEST['requested_url']) {
        $_SESSION['requested_url']=$_REQUEST['requested_url'];
    }

    if (isset($_REQUEST['login']) && isset($_REQUEST['email']) && isset($_REQUEST['password'])) {
        if (!csrf__validate_request_message()) {
            redirect("public/participant_login.php");
        }
        $logged_in=participant__check_login($_REQUEST['email'],$_REQUEST['password']);
        if ($logged_in) {
            if (isset($_SESSION['requested_url']) && $_SESSION['requested_url']) {
                $url=$_SESSION['requested_url'];
                unset($_SESSION['requested_url']);
                redirect($url);
            } else {
                redirect("public/participant_show.php");
            }
        } else {
            redirect("public/participant_login.php");
        }
        $proceed=false;
    }
}

if ($proceed) {
    $participant_password_dir=($settings['force_ltr_participant_login_password']==='y' ? ' dir="ltr"' : '');
    echo '<div class="orsee-panel orsee-login-panel">';
    echo '<form name="login" action="participant_login.php" method="post" class="orsee-login-form">';
    echo csrf__field();
    echo '<div class="field">';
    echo '<label class="label">'.lang('email').'</label>';
    echo '<div class="control"><input class="input is-primary orsee-input orsee-input-email" type="email" dir="ltr" maxlength="100" name="email"></div>';
    echo '</div>';
    echo '<div class="field">';
    echo '<label class="label">'.lang('password').'</label>';
    echo '<div class="control"><input class="input is-primary orsee-input orsee-input-password" type="password"'.$participant_password_dir.' maxlength="30" name="password"></div>';
    echo '</div>';
    echo '<div class="orsee-form-actions orsee-login-actions">';
    echo '<button class="button orsee-public-btn" type="submit" name="login" value="1">'.lang('login').'</button>';
    echo '</div>';
    echo '<div class="orsee-login-forgot">';
    echo '<a href="participant_reset_pw.php">'.lang('forgot_your_password?').'</a>';
    echo '</div>';
    echo '</form>';
    echo '</div>';
}
include("footer.php");

?>
