<?php
// part of orsee. see orsee.org
ob_start();
$title="user_management";
$menu__area="options";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['admin_id']) && $_REQUEST['admin_id']) {
        $admin_id=$_REQUEST['admin_id'];
    } elseif (isset($_REQUEST['new']) && $_REQUEST['new']) {
        $admin_id="";
    } else {
        $admin_id=$expadmindata['admin_id'];
    }

    if ($admin_id) {
        $admin=orsee_db_load_array("admin",$admin_id,"admin_id");
    } else {
        $admin=array('adminname'=>'','fname'=>'','lname'=>'','email'=>'','admin_type'=>'','language'=>'',
                'experimenter_list'=>'','get_calendar_mail'=>'','get_statistics_mail'=>'','disabled'=>'n',
                'locked'=>0,'last_login_attempt'=>0,'failed_login_attempts'=>0,'pw_update_requested'=>0);
    }

    if ((!$admin_id) ||  $admin_id!=$expadmindata['admin_id']) {
        $allow=check_allow('admin_edit','admin_show.php');
    }

    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        if (!csrf__validate_request_message()) {
            redirect("admin/admin_edit.php?admin_id=".$admin_id);
        }

        $continue=true;

        if (!check_allow('admin_edit')) {
            unset($_REQUEST['admin_type']);
            unset($_REQUEST['experimenter_list']);
            unset($_REQUEST['new_password']);
            unset($_REQUEST['new_password2']);
            unset($_REQUEST['adminname']);
        }

        if (isset($_REQUEST['adminname']) && !$_REQUEST['adminname']) {
            message(lang('you_have_to_give_a_username'),'error');
            $continue=false;
        }
        if (!$_REQUEST['fname']) {
            message(lang('you_have_to_fname'),'error');
            $continue=false;
        }
        if (!$_REQUEST['lname']) {
            message(lang('you_have_to_lname'),'error');
            $continue=false;
        }

        if (!$_REQUEST['email']) {
            message(lang('you_have_to_give_email_address'),'error');
            $continue=false;
        }

        if (!$admin_id && !$_REQUEST['new_password']) {
            message(lang('you_have_to_give_a_password'),'error');
            $continue=false;
            $_REQUEST['new_password']="";
            $_REQUEST['new_password2']="";
        }


        if ($_REQUEST['new_password'] && (! $_REQUEST['new_password']==$_REQUEST['new_password2'])) {
            message(lang('you_have_to_give_a_password'),'error');
            $continue=false;
            $_REQUEST['new_password']="";
            $_REQUEST['new_password2']="";
        }

        if ($continue && isset($_REQUEST['adminname'])) {
            $_REQUEST['adminname']=trim($_REQUEST['adminname']);
            $pars=array(':adminname'=>$_REQUEST['adminname']);
            $query="SELECT admin_id FROM ".table('admin')."
                    WHERE adminname = :adminname";
            $existing_admin=orsee_query($query,$pars);
            if (isset($existing_admin['admin_id']) && $existing_admin['admin_id']!=$admin_id) {
                $continue=false;
                message(lang('error_username_exists'),'error');
            }
        }

        if ($continue) {
            if ($_REQUEST['new_password']) {
                // no password strength checks when account created by super-admin?
                $_REQUEST['password_crypt']=unix_crypt($_REQUEST['new_password']);
                message(lang('password_changed'));
            } else {
                unset($_REQUEST['new_password']);
            }

            if (!$admin_id) {
                $admin_id=time();
            }
            foreach (array('fname','lname') as $k) {
                $_REQUEST[$k]=trim($_REQUEST[$k]);
            }
            $save_allowed_fields=array('fname','lname','email','language','get_calendar_mail','get_statistics_mail');
            if (check_allow('admin_edit')) {
                $save_allowed_fields=array_merge($save_allowed_fields,array('adminname','admin_type','experimenter_list','disabled','locked','pw_update_requested'));
            }
            if (isset($_REQUEST['password_crypt']) && $_REQUEST['password_crypt']) {
                $save_allowed_fields[]='password_crypt';
            }
            $form_fields=array_filter_allowed($_REQUEST,$save_allowed_fields);
            $done=orsee_db_save_array($form_fields,"admin",$admin_id,"admin_id");
            message(lang('changes_saved'));
            if (isset($form_fields['adminname'])) {
                $log_adminname=$form_fields['adminname'];
            } else {
                $log_adminname=$admin['adminname'];
            }
            log__admin("admin_edit",$log_adminname);
            if ($admin_id==$expadmindata['admin_id']) {
                $nl="&new_language=".$form_fields['language'];
            } else {
                $nl="";
            }
            redirect("admin/admin_edit.php?admin_id=".$admin_id.$nl);
            $proceed=false;
        }

        if ($proceed) {
            foreach ($admin as $k=>$v) {
                if (isset($_REQUEST[$k])) {
                    $admin[$k]=$_REQUEST[$k];
                }
            }
        }
    }
}

if ($proceed) {
    show_message();
    $admin_password_dir=($settings['force_ltr_admin_login_password']==='y' ? ' dir="ltr"' : '');

    echo '<form action="admin_edit.php" method="post">'.csrf__field();

    if ($admin_id) {
        echo '<input type="hidden" name="admin_id" value="'.$admin_id.'">';
    } else {
        echo '<input type="hidden" name="new" value="true">';
    }

    echo '  <div class="orsee-panel">
                <div class="orsee-panel-title">
                    <div class="orsee-panel-title-main">'.lang('edit_profile_for').' ';
    if ($admin_id) {
        echo $admin['adminname'];
    } else {
        echo lang('new_administrator');
    }
    echo '          </div>
                </div>
                <div class="orsee-form-shell">';

    echo '          <div class="field">
                        <label class="label">'.lang('username').':</label>
                        <div class="control">';
    if (check_allow('admin_edit')) {
        echo '<input class="input is-primary orsee-input orsee-input-text" name="adminname" type="text" maxlength="40" value="'.$admin['adminname'].'">';
    } else {
        echo $admin['adminname'];
    }
    echo '              </div>
                    </div>';

    echo '          <div class="field orsee-form-row-grid orsee-form-row-grid--2">
                        <div class="orsee-form-row-col">
                            <label class="label">'.lang('firstname').':</label>
                            <div class="control">
                                <input class="input is-primary orsee-input orsee-input-text" name="fname" type="text" maxlength="50" value="'.$admin['fname'].'">
                            </div>
                        </div>
                        <div class="orsee-form-row-col">
                            <label class="label">'.lang('lastname').':</label>
                            <div class="control">
                                <input class="input is-primary orsee-input orsee-input-text" name="lname" type="text" maxlength="50" value="'.$admin['lname'].'">
                            </div>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label">'.lang('email').':</label>
                        <div class="control">
                            <input class="input is-primary orsee-input orsee-input-text" name="email" type="text" dir="ltr" maxlength="200" value="'.$admin['email'].'">
                        </div>
                    </div>';

    if (check_allow('admin_edit')) {
        if ($admin['admin_type']) {
            $selected=$admin['admin_type'];
        } else {
            $selected=$settings['default_admin_type'];
        }

        echo '      <div class="field orsee-form-row-grid orsee-form-row-grid--2">
                        <div class="orsee-form-row-col">
                            <label class="label">'.lang('type').':</label>
                            <div class="control">
                                '.admin__select_admin_type("admin_type",$selected).'
                            </div>
                        </div>
                        <div class="orsee-form-row-col">
                            <label class="label">'.lang('account').':</label>
                            <div class="control">
                                <label class="radio"><input name="disabled" type="radio" value="n"';
        if ($admin['disabled']!='y') {
            echo ' checked';
        }
        echo '>'.lang('account_enabled').'</label>
                                &nbsp;&nbsp;
                                <label class="radio"><input name="disabled" type="radio" value="y"';
        if ($admin['disabled']=='y') {
            echo ' checked';
        }
        echo '>'.lang('account_disabled').'</label>
                            </div>
                        </div>
                    </div>';
    }

    $langs=get_languages();
    $lang_names=lang__get_language_names();
    if ($admin['language']) {
        $clang=$admin['language'];
    } else {
        $clang=$settings['admin_standard_language'];
    }
    echo '          <div class="field">
                        <label class="label">'.lang('language').':</label>
                        <div class="control">
                            <div class="select is-primary">
                                <select name="language">';
    foreach ($langs as $language) {
        echo '<option value="'.$language.'"';
        if ($language==$clang) {
            echo ' selected';
        }
        echo '>'.$lang_names[$language].'</option>';
    }
    echo '                      </select>
                            </div>
                        </div>
                    </div>';

    if (check_allow('admin_edit')) {
        echo '      <div class="field">
                        <label class="label">'.lang('is_experimenter').':</label>
                        <div class="control">
                            <label class="radio"><input name="experimenter_list" type="radio" value="y"';
        if ($admin['experimenter_list']!='n') {
            echo ' checked';
        }
        echo '>'.lang('yes').'</label>
                            &nbsp;&nbsp;
                            <label class="radio"><input name="experimenter_list" type="radio" value="n"';
        if ($admin['experimenter_list']=='n') {
            echo ' checked';
        }
        echo '>'.lang('no').'</label>
                        </div>
                    </div>';
    }

    echo '          <div class="field orsee-form-row-grid orsee-form-row-grid--2">
                        <div class="orsee-form-row-col">
                            <label class="label">'.lang('receives_periodical_calendar').':</label>
                            <div class="control">
                                <label class="radio"><input name="get_calendar_mail" type="radio" value="y"';
    if ($admin['get_calendar_mail']!='n') {
        echo ' checked';
    }
    echo '>'.lang('yes').'</label>
                                &nbsp;&nbsp;
                                <label class="radio"><input name="get_calendar_mail" type="radio" value="n"';
    if ($admin['get_calendar_mail']=='n') {
        echo ' checked';
    }
    echo '>'.lang('no').'</label>
                            </div>
                        </div>
                        <div class="orsee-form-row-col">
                            <label class="label">'.lang('receives_periodical_participant_statistics').':</label>
                            <div class="control">
                                <label class="radio"><input name="get_statistics_mail" type="radio" value="y"';
    if ($admin['get_statistics_mail']!='n') {
        echo ' checked';
    }
    echo '>'.lang('yes').'</label>
                                &nbsp;&nbsp;
                                <label class="radio"><input name="get_statistics_mail" type="radio" value="n"';
    if ($admin['get_statistics_mail']=='n') {
        echo ' checked';
    }
    echo '>'.lang('no').'</label>
                            </div>
                        </div>
                    </div>';

    if (check_allow('admin_edit')) {
        echo '      <div class="field orsee-form-row-grid orsee-form-row-grid--3">
                        <div class="orsee-form-row-col">
                            <label class="label">'.lang('account_locked_due_to_failed_logins').':</label>
                            <div class="control">';
        if ($admin['locked']) {
            echo '<strong>'.lang('yes').'</strong>
                            <label class="radio"><input name="locked" type="radio" value="1"';
            if ($admin['locked']) {
                echo ' checked';
            }
            echo '></label>
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.lang('unlock_account').'
                            <label class="radio"><input name="locked" type="radio" value="0"';
            if (!$admin['locked']) {
                echo ' checked';
            }
            echo '></label>';
        } else {
            echo lang('no');
        }
        echo '                  </div>
                        </div>
                        <div class="orsee-form-row-col">
                            <label class="label">'.lang('last_login_attempt').':</label>
                            <div class="control">';
        if ($admin['last_login_attempt']) {
            echo ortime__format($admin['last_login_attempt'],'hide_second:false');
        } else {
            echo lang('never');
        }
        echo '                  </div>
                        </div>
                        <div class="orsee-form-row-col">
                            <label class="label">'.lang('failed_login_attempts').':</label>
                            <div class="control">'.$admin['failed_login_attempts'].'</div>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label">'.lang('request_passwort_update').':</label>
                        <div class="control">
                            <label class="radio"><input name="pw_update_requested" type="radio" value="1"';
        if ($admin['pw_update_requested']) {
            echo ' checked';
        }
        echo '>'.lang('yes').'</label>
                            &nbsp;&nbsp;
                            <label class="radio"><input name="pw_update_requested" type="radio" value="0"';
        if (!$admin['pw_update_requested']) {
            echo ' checked';
        }
        echo '>'.lang('no').'</label>
                        </div>
                    </div>
                    <div class="field orsee-form-row-grid orsee-form-row-grid--2">
                        <div class="orsee-form-row-col">
                            <label class="label">'.lang('new_password').':</label>
                            <div class="control">
                                <input class="input is-primary orsee-input orsee-input-text" name="new_password" type="password" autocomplete="new-password"'.$admin_password_dir.' maxlength="20" value="">
                            </div>
                        </div>
                        <div class="orsee-form-row-col">
                            <label class="label">'.lang('repeat_new_password').':</label>
                            <div class="control">
                                <input class="input is-primary orsee-input orsee-input-text" name="new_password2" type="password" autocomplete="new-password"'.$admin_password_dir.' maxlength="20" value="">
                            </div>
                        </div>
                    </div>';
    }

    echo '          <div class="field orsee-form-row-grid orsee-form-row-grid--3 orsee-form-actions">
                        <div class="orsee-form-row-col has-text-left">';
    if (check_allow('admin_edit')) {
        echo button_back('admin_show.php');
    }
    echo '              </div>
                        <div class="orsee-form-row-col has-text-centered">
                            <input class="button orsee-btn" name="edit" type="submit" value="';
    if ($admin_id) {
        echo lang('change');
    } else {
        echo lang('add');
    }
    echo '                  ">
                        </div>
                        <div class="orsee-form-row-col has-text-right"></div>
                    </div>';

    if ($admin_id && check_allow('admin_delete')) {
        echo '      <div class="orsee-form-actions has-text-right">'.
                    button_link_delete('admin_delete.php?admin_id='.urlencode($admin_id).'&csrf_token='.urlencode(csrf__get_token()),lang('delete')).
                '</div>';
    }

    echo '          </div>
                </div>
            </form><br>';

    if ($admin_id && (check_allow('calendar_export_my') || check_allow('calendar_export_all'))) {
        echo '  <div class="orsee-panel">
                    <div class="orsee-form-shell" style="width: min(100%, 88rem);">';
        if (check_allow('calendar_export_my')) {
            $own_calendar_url=$settings__root_url.'/admin/calendar_ics.php?cal='.'p'.calendar__gen_ics_token($admin['admin_id'],$admin['password_crypt']);
            echo '      <div class="field">
                            <label class="label">'.lang('if_you_want_to_export_own_calendar').'</label>
                            <div class="control">
                                <div class="field has-addons">
                                    <div class="control is-expanded">
                                        <input id="own_calendar_export_url" style="width: 100% !important; max-width: 100% !important; border-inline-end: 0; border-start-start-radius: 0.4rem; border-end-start-radius: 0.4rem; border-start-end-radius: 0; border-end-end-radius: 0;" class="input is-primary orsee-input orsee-input-text" type="text" dir="ltr" readonly value="'.$own_calendar_url.'">
                                    </div>
                                    <div class="control" style="display: flex; align-items: stretch;">
                                        <a href="#" class="orsee-text-input-inline-button orsee-text-input-inline-button--addon" aria-label="'.lang('copy').'" title="'.lang('copy').'" onclick="if(navigator.clipboard){navigator.clipboard.writeText(document.getElementById(\'own_calendar_export_url\').value);} return false;"><i class="fa fa-copy"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>';
        }
        if (check_allow('calendar_export_all')) {
            $full_calendar_url=$settings__root_url.'/admin/calendar_ics.php?cal='.'a'.calendar__gen_ics_token($admin['admin_id'],$admin['password_crypt']);
            echo '      <div class="field">
                            <label class="label">'.lang('if_you_want_to_export_full_calendar').'</label>
                            <div class="control">
                                <div class="field has-addons">
                                    <div class="control is-expanded">
                                        <input id="full_calendar_export_url" style="width: 100% !important; max-width: 100% !important; border-inline-end: 0; border-start-start-radius: 0.4rem; border-end-start-radius: 0.4rem; border-start-end-radius: 0; border-end-end-radius: 0;" class="input is-primary orsee-input orsee-input-text" type="text" dir="ltr" readonly value="'.$full_calendar_url.'">
                                    </div>
                                    <div class="control" style="display: flex; align-items: stretch;">
                                        <a href="#" class="orsee-text-input-inline-button orsee-text-input-inline-button--addon" aria-label="'.lang('copy').'" title="'.lang('copy').'" onclick="if(navigator.clipboard){navigator.clipboard.writeText(document.getElementById(\'full_calendar_export_url\').value);} return false;"><i class="fa fa-copy"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>';
        }
        echo '      </div>
                </div>';
    }
}
include("footer.php");

?>
