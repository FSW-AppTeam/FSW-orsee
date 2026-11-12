<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="change_my_password";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['submit']) && $_REQUEST['submit']) {
        if (!csrf__validate_request_message()) {
            redirect("admin/admin_pw.php");
        }

        if (isset($_REQUEST['passold'])) {
            $passold=$_REQUEST['passold'];
        } else {
            $passold="";
        }
        if (isset($_REQUEST['password'])) {
            $password=$_REQUEST['password'];
        } else {
            $password="";
        }
        if (isset($_REQUEST['password2'])) {
            $password2=$_REQUEST['password2'];
        } else {
            $password2="";
        }

        // password tests
        $continue=true;

        if (!$passold || !$password || !$password2) {
            message(lang('error_please_fill_in_all_fields'),'error');
            $continue=false;
        }

        if ($password!=$password2) {
            message(lang('error_password_repetition_does_not_match'),'error');
            $continue=false;
        }

        if (!crypt_verify($passold,$expadmindata['password_crypt'])) {
            message(lang('error_old_password_wrong'),'error');
            $continue=false;
        }

        if ($password==$expadmindata['adminname']) {
            message(lang('error_do_not_use_username_as_password'),'error');
            $continue=false;
        }
        if ($settings['admin_password_change_require_different']=='y') {
            if ($passold==$password) {
                message(lang('error_new_password_must_be_different_from_old_password'),'error');
                $continue=false;
            }
        }

        if (!preg_match('/'.$settings['admin_password_regexp'].'/',$password)) {
            message(lang('error_password_does_not_meet_requirements'),'error');
            $continue=false;
        }


        if ($continue==false) {
            message(lang('error_password_not_changed'),'error');
            redirect("admin/admin_pw.php");
        } else {
            admin__set_password($password,$expadmindata['admin_id']);
            message(lang('password_changed_log_in_again'));
            log__admin("admin_password_change",$expadmindata['adminname']);
            log__admin("logout");
            admin__logout();
            redirect("admin/admin_login.php?pw=true");
        }
        $proceed=false;
    }
}

if ($proceed) {
    show_message();
    $admin_password_dir=($settings['force_ltr_admin_login_password']==='y' ? ' dir="ltr"' : '');

    echo '<form action="admin_pw.php" method="POST">
            '.csrf__field().'
            <div class="orsee-panel">
                <div class="orsee-panel-title">
                    <div class="orsee-panel-title-main">'.lang('change_my_password').'</div>
                </div>
                <div class="orsee-form-shell">
                    <div class="field">
                        <label class="label">'.lang('old_password').':</label>
                        <div class="control">
                            <input class="input is-primary orsee-input orsee-input-text" type="password" name="passold"'.$admin_password_dir.' autocomplete="off" maxlength="40">
                        </div>
                    </div>
                    <div class="field">
                        <div class="control">
                            ';
    orsee_callout(lang('admin_password_strength_requirements'),'warning');
    echo '              </div>
                    </div>
                    <div class="field orsee-form-row-grid orsee-form-row-grid--2">
                        <div class="orsee-form-row-col">
                            <label class="label">'.lang('new_password').':</label>
                            <div class="control">
                                <input class="input is-primary orsee-input orsee-input-text" type="password" name="password"'.$admin_password_dir.' autocomplete="off" maxlength="40">
                            </div>
                        </div>
                        <div class="orsee-form-row-col">
                            <label class="label">'.lang('repeat_new_password').':</label>
                            <div class="control">
                                <input class="input is-primary orsee-input orsee-input-text" type="password"  name="password2"'.$admin_password_dir.' autocomplete="off" maxlength="40">
                            </div>
                        </div>
                    </div>
                    <div class="field orsee-form-row-grid orsee-form-row-grid--3 orsee-form-actions">
                        <div class="orsee-form-row-col has-text-left">
                            '.button_back('options_main.php').'
                        </div>
                        <div class="orsee-form-row-col has-text-centered">
                            <input class="button orsee-btn" type="submit" name="submit" value="'.lang('change').'">
                        </div>
                        <div class="orsee-form-row-col has-text-right"></div>
                    </div>
                </div>
            </div>
        </form>';
}
include("footer.php");

?>
