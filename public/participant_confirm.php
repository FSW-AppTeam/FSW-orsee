<?php
// part of orsee. see orsee.org
ob_start();
$temp__nosession=true;
$menu__area="public_register";
$title="confirm_registration";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['c'])) {
        $c=$_REQUEST['c'];
    } else {
        $c='';
    }
    if (!$c) {
        message(lang('confirmation_error'),'warning');
        redirect("public/");
    }
}
if ($proceed) {
    $participant_id=participant__participant_get_if_not_confirmed($c);
    if (!$participant_id) {
        message(lang('already_confirmed_error'),'warning');
        redirect("public/");
    } else {
        // change status to active
        $default_active_status=participant_status__get("is_default_active");
        $pars=array(':participant_id'=>$participant_id,':default_active_status'=>$default_active_status);
        if ($settings['allow_permanent_queries']=='y') {
            $qadd=', apply_permanent_queries = 1 ';
        } else {
            $qadd='';
        }
        $query="UPDATE ".table('participants')."
                SET status_id= :default_active_status,
                 confirmation_token = ''
                ".$qadd."
                WHERE participant_id= :participant_id ";
        $done=or_query($query,$pars);

        if (!$done) {
            message(lang('database_error'),'error');
            redirect("public/");
        } else {
            log__participant("confirm",$participant_id);
            $mess=lang('registration_confirmed').'<BR><BR>';
            $mess.=lang('thanks_for_registration');
            message($mess);
            echo '<div id="orsee-public-mobile-screen">';
            echo '<div class="orsee-public-inline-message-buffer">';
            show_message();
            echo '</div>';
            echo '<div class="orsee-public-profile-formwrap">';
            echo '<div class="orsee-public-profile-form">';
            echo '<div class="orsee-form-shell orsee-public-profile-shell">';
            echo '<div class="orsee-form-actions has-text-centered">';
            echo '<a class="button orsee-public-btn" href="participant_login.php">'.lang('profile_login').'</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
    }
}
include("footer.php");

?>
