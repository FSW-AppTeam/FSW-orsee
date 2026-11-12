<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="public_register";
$title="reset_password";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['t']) && $_REQUEST['t']) {
        $_SESSION['pw_reset_token']=$_REQUEST['t'];
        redirect("public/participant_reset_pw.php");
    }
}

if ($proceed) {
    $reset_email_value='';
    if (isset($_REQUEST['email'])) {
        $reset_email_value=trim((string)$_REQUEST['email']);
    } elseif (isset($_REQUEST['reset_email'])) {
        $reset_email_value=trim((string)$_REQUEST['reset_email']);
    }

    if (isset($_SESSION['pw_reset_token']) && $_SESSION['pw_reset_token'] &&
        isset($_REQUEST['password']) && isset($_REQUEST['password2']) &&
        ($reset_email_value || $_REQUEST['password'] || $_REQUEST['password2'])) {
        if (!csrf__validate_request_message()) {
            redirect("public/participant_reset_pw.php");
        }

        $continue=true;

        $_SESSION['reset_email_address']=$reset_email_value;
        // captcha
        if ($continue) {
            if ($_REQUEST['captcha']!=$_SESSION['captcha_string']) {
                $continue=false;
                message(lang('error_wrong_captcha'),'error');
                redirect("public/participant_reset_pw.php");
            }
        }
        if ($continue) {
            // check password, token, and email address
            $status_clause=participant_status__get_pquery_snippet("access_to_profile");
            $pars=array(':token'=>$_SESSION['pw_reset_token']);
            $query="SELECT * FROM ".table('participants')."
                    WHERE pwreset_token= :token
                    AND ".$status_clause;
            $participant=orsee_query($query,$pars);
            if (!isset($participant['participant_id'])) {
                //if token not ok, redirect to main page without comment
                $continue=false;
                message(lang('password_reset_token_not_found'),'error');
                redirect("public/");
            } elseif ($participant['pwreset_request_time']+60*60<time()) {
                //if token validity elapsed, show message and redirect
                message(lang('password_reset_token_not_valid_anymore'),'warning');
                $continue=false;
                redirect("public/");
            }
        }
        if ($continue) {
            if (strtolower($participant['email'])!=strtolower($reset_email_value)) {
                //if email address not ok: save email address to session, show message, redirect
                message(lang('password_reset_provided_email_address_not_correct'),'error');
                $continue=false;
                redirect("public/participant_reset_pw.php");
            }
        }
        if ($continue) {
            $pw_ok=participant__check_password($_REQUEST['password'],$_REQUEST['password2']);
            if (!$pw_ok) {
                //if passwords not ok: save email address to session, show message, redirect
                $continue=false;
                redirect("public/participant_reset_pw.php");
            }
        }
        if ($continue) {
            //if all ok, save new password (reset reset_request, token), reset token, password, email address, set OK, redirect
            $participant['password_crypted']=unix_crypt($_REQUEST['password']);
            $pars=array(':password'=>$participant['password_crypted'],
                        ':participant_id'=>$participant['participant_id']);
            $query="UPDATE ".table('participants')."
                    SET password_crypted = :password,
                    pwreset_token= NULL
                    WHERE participant_id = :participant_id";
            $participant=or_query($query,$pars);
            unset($_SESSION['pw_reset_token']);
            unset($_SESSION['captcha_string']);
            unset($_SESSION['reset_email_address']);
            $_SESSION['password_has_been_changed']=true;
            redirect("public/participant_reset_pw.php");
        }
    }
}

if ($proceed) {
    if (isset($_SESSION['pw_reset_token']) && $_SESSION['pw_reset_token']) {
        // show form, captcha
        if (isset($_SESSION['reset_email_address']) && $_SESSION['reset_email_address']) {
            $email=$_SESSION['reset_email_address'];
        } else {
            $email='';
        }
        $participant_password_dir=($settings['force_ltr_participant_login_password']==='y' ? ' dir="ltr"' : '');

        echo '<div id="orsee-public-mobile-screen">';
        echo '<div class="orsee-public-inline-message-buffer">';
        show_message();
        echo '</div>';
        echo '<div class="orsee-public-profile-formwrap">';
        echo '<form action="participant_reset_pw.php" method="POST" class="orsee-public-profile-form">';
        echo csrf__field();
        echo '<div class="orsee-form-shell orsee-public-profile-shell">';
        echo '<div class="field"><div class="control">'.lang('reset_pw_please_enter_email_and_new_password').'</div></div>';
        echo '<div class="field">
                <label class="label">'.lang('email').'</label>
                <div class="control"><input class="input is-primary orsee-input orsee-input-email" type="email" name="email" dir="ltr" maxlength="100" autocomplete="email" autocapitalize="off" spellcheck="false" value="'.htmlspecialchars((string)$email,ENT_QUOTES,'UTF-8').'"></div>
              </div>';
        echo '<div class="field">
                <label class="label">'.lang('new_password').'</label>
                <div class="control"><input class="input is-primary orsee-input orsee-input-password" type="password" name="password"'.$participant_password_dir.' maxlength="40"></div>
                <p class="help">'.lang('participant_password_note').'</p>
              </div>';
        echo '<div class="field">
                <label class="label">'.lang('repeat_new_password').'</label>
                <div class="control"><input class="input is-primary orsee-input orsee-input-password" type="password" name="password2"'.$participant_password_dir.' maxlength="40"></div>
              </div>';
        echo '<div class="field">
                <label class="label">'.lang('captcha_text').'</label>
                <div class="control"><img src="captcha.php" alt="captcha"></div>
              </div>';
        echo '<div class="field">
                <div class="control"><input class="input is-primary orsee-input" type="text" name="captcha" dir="ltr" maxlength="8" value=""></div>
              </div>';
        echo '</div>';
        echo '<div class="orsee-public-profile-actions">
                <button class="button orsee-public-btn" type="submit" name="submit" value="1">'.lang('change').'</button>
              </div>';
        echo '</form>';
        echo '</div>';
        echo '</div>';
        $proceed=false;
    }
}

if ($proceed) {
    if (isset($_SESSION['password_has_been_changed']) && $_SESSION['password_has_been_changed']) {
        message(lang('password_changed'),'note',null,'toast');
        unset($_SESSION['password_has_been_changed']);
        $proceed=false;
        echo '<div id="orsee-public-mobile-screen">';
        echo '<div class="orsee-public-inline-message-buffer">';
        show_message();
        echo '</div>';
        echo '<div class="orsee-public-profile-formwrap">';
        echo '<div class="orsee-public-profile-form">';
        echo '<div class="orsee-form-shell orsee-public-profile-shell">';
        echo '<div class="orsee-form-actions has-text-centered">';
        echo '<a class="button orsee-public-btn" href="participant_login.php">'.lang('profile_login').'</a>';
        echo '</div></div></div></div></div>';
    }
}

if ($proceed) {
    if (isset($_REQUEST['email']) && $_REQUEST['email']) {
        if (!csrf__validate_request_message()) {
            redirect("public/participant_reset_pw.php");
        }
        $continue=true;
        // captcha
        if ($continue) {
            if ($_REQUEST['captcha']!=$_SESSION['captcha_string']) {
                $continue=false;
                message(lang('error_wrong_captcha'),'error');
                redirect("public/participant_reset_pw.php");
            }
        }

        if ($continue) {
            $status_clause=participant_status__get_pquery_snippet("access_to_profile");
            $pars=array(':email'=>$_REQUEST['email']);
            $query="SELECT * FROM ".table('participants')."
                    WHERE email= :email
                    AND ".$status_clause;
            $participant=orsee_query($query,$pars);
            if (isset($participant['participant_id'])) {
                // create and save token
                $participant['pwreset_token']=create_random_token(get_entropy($participant));
                $pars=array(':token'=>$participant['pwreset_token'],
                        ':participant_id'=>$participant['participant_id'],
                        ':now'=>time());
                $query="UPDATE ".table('participants')."
                        SET pwreset_token = :token,
                        pwreset_request_time = :now
                        WHERE participant_id= :participant_id";
                $done=or_query($query,$pars);
                // send reset email
                $done=experimentmail__mail_pwreset_link($participant);
                message(lang('password_reset_link_sent_if_email_exists'));
                redirect('public/');
            } else {
                // to not reveal which email addresses exist, just do as if
                message(lang('password_reset_link_sent_if_email_exists'));
                redirect('public/');
            }
        }
    }
}

if ($proceed) {
    echo '<div id="orsee-public-mobile-screen">';
    echo '<div class="orsee-public-inline-message-buffer">';
    show_message();
    echo '</div>';
    echo '<div class="orsee-public-profile-formwrap">';
    echo '<form action="participant_reset_pw.php" method="POST" class="orsee-public-profile-form">';
    echo csrf__field();
    echo '<div class="orsee-form-shell orsee-public-profile-shell">';
    echo '<div class="field"><div class="control">'.lang('reset_pw_please_enter_your_email_address').'</div></div>';
    echo '<div class="field">
            <label class="label">'.lang('email').'</label>
            <div class="control"><input class="input is-primary orsee-input orsee-input-email" type="email" name="email" dir="ltr" maxlength="100" autocomplete="email" autocapitalize="off" spellcheck="false"></div>
          </div>';
    echo '<div class="field">
            <label class="label">'.lang('captcha_text').'</label>
            <div class="control"><img src="captcha.php" alt="captcha"></div>
          </div>';
    echo '<div class="field">
            <div class="control"><input class="input is-primary orsee-input" type="text" name="captcha" dir="ltr" maxlength="8" value=""></div>
          </div>';
    echo '</div>';
    echo '<div class="orsee-public-profile-actions">
            <button class="button orsee-public-btn" type="submit" name="submit" value="1">'.lang('submit').'</button>
          </div>';
    echo '</form>';
    echo '</div>';
    echo '</div>';
}

include("footer.php");

?>
