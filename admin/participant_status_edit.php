<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="edit_participant_status";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['status_id'])) {
        $status_id=$_REQUEST['status_id'];
    }

    if (isset($status_id)) {
        $allow=check_allow('participantstatus_edit','participant_status_main.php');
    } else {
        $allow=check_allow('participantstatus_add','participant_status_main.php');
    }
}

if ($proceed) {
    if (isset($status_id) && $status_id==0) {
        $not_unconfirmed=false;
    } else {
        $not_unconfirmed=true;
    }

    // load languages
    $languages=get_languages();

    if (isset($status_id)) {
        $status=orsee_db_load_array("participant_statuses",$status_id,"status_id");
        if (!isset($status['status_id'])) {
            redirect('admin/participant_status_main.php');
        }
        if ($proceed) {
            $pars=array(':status_id'=>$status_id);
            $query="SELECT * from ".table('lang')." WHERE content_type='participant_status_name' AND content_name= :status_id";
            $status_name=orsee_query($query,$pars);
            $query="SELECT * from ".table('lang')." WHERE content_type='participant_status_error' AND content_name= :status_id";
            $status_error=orsee_query($query,$pars);
        }
    } else {
        $status=array('is_default_active'=>'n','is_default_inactive'=>'n','access_to_profile'=>'n','eligible_for_experiments'=>'n');
        $status_name=array();
        $status_error=array();
    }
}

if ($proceed) {
    $continue=true;

    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        if (!csrf__validate_request_message()) {
            redirect("admin/participant_status_edit.php?status_id=".$status_id);
        }

        if ($not_unconfirmed && $_REQUEST['is_default_active']=="y" && $_REQUEST['is_default_inactive']=="y") {
            message(lang('error_participant_status_cannot_be_default_for_both_active_and_inactive'),'error');
            $_REQUEST['is_default_active']="n";
            $_REQUEST['is_default_inactive']="n";
            $continue=false;
        }

        $status_name=$_REQUEST['status_name'];
        foreach ($languages as $language) {
            if (!$status_name[$language]) {
                message(lang('missing_language').': "'.lang('name').'" - '.$language,'error');
                $continue=false;
            }
        }
        if ($not_unconfirmed) {
            $status_error=$_REQUEST['status_error'];
            foreach ($languages as $language) {
                if ($_REQUEST['access_to_profile']!='y' && !$status_error[$language]) {
                    message(lang('missing_language').': "'.lang('error_message_to_participant_when_access_is_denied').'" - '.$language,'error');
                    $continue=false;
                }
            }
        }

        if ($continue) {
            $status_name_lang=array();
            $status_error_lang=array();
            if (!isset($status_id)) {
                $new=true;
                $query="SELECT status_id+1 as new_status_id FROM ".table('participant_statuses')."
                        ORDER BY status_id DESC LIMIT 1";
                $line=orsee_query($query);
                if (isset($line['new_status_id'])) {
                    $status_id=$line['new_status_id'];
                } else {
                    $status_id=1;
                }
                $status_name_lang['content_type']="participant_status_name";
                $status_name_lang['content_name']=$status_id;
                $status_error_lang['content_type']="participant_status_error";
                $status_error_lang['content_name']=$status_id;
            } else {
                $new=false;
                $pars=array(':status_id'=>$status_id);
                $query="SELECT * from ".table('lang')." WHERE content_type='participant_status_name' AND content_name= :status_id";
                $status_name_lang=orsee_query($query,$pars);
                if ($not_unconfirmed) {
                    $query="SELECT * from ".table('lang')." WHERE content_type='participant_status_error' AND content_name= :status_id";
                    $status_error_lang=orsee_query($query,$pars);
                }
            }

            foreach ($languages as $language) {
                $status_name_lang[$language]=$status_name[$language];
                if ($not_unconfirmed) {
                    $status_error_lang[$language]=$status_error[$language];
                }
            }

            if ($new) {
                $status_name['lang_id']=lang__insert_to_lang($status_name_lang);
                $status_error['lang_id']=lang__insert_to_lang($status_error_lang);
            } else {
                $done=orsee_db_save_array($status_name_lang,"lang",$status_name_lang['lang_id'],"lang_id");
                if ($not_unconfirmed) {
                    $done=orsee_db_save_array($status_error_lang,"lang",$status_error_lang['lang_id'],"lang_id");
                }
            }

            if ($not_unconfirmed) {
                $status=$_REQUEST;
                $status['status_id']=$status_id;
                $pars=array(':status_id'=>$status_id);
                if ($status['is_default_active']=="y") {
                    $query="UPDATE ".table('participant_statuses')."
                            SET is_default_active='n'
                            WHERE status_id!= :status_id";
                    $done=or_query($query,$pars);
                }
                if ($status['is_default_inactive']=="y") {
                    $query="UPDATE ".table('participant_statuses')."
                            SET is_default_inactive='n'
                            WHERE status_id!= :status_id";
                    $done=or_query($query,$pars);
                }
                $form_fields=array_filter_allowed($status,array(
                        'status_id','access_to_profile','eligible_for_experiments',
                        'is_default_active','is_default_inactive'));
                $form_fields['status_id']=$status_id;
                $done=orsee_db_save_array($form_fields,"participant_statuses",$status_id,"status_id");
            }
            message(lang('changes_saved'));
            log__admin("participant_status_edit","status_id:".$status['status_id']);
            redirect("admin/participant_status_edit.php?status_id=".$status_id);
        } else {
            $status=$_REQUEST;
        }
    }
}

if ($proceed) {
    // form
    show_message();
    echo '
            <form action="participant_status_edit.php" method="POST">'.csrf__field();
    if (isset($status_id)) {
        echo '<input type="hidden" name="status_id" value="'.$status_id.'">';
    }

    echo '
                <div class="orsee-panel">
                    <div class="orsee-panel-title">
                        <div class="orsee-panel-title-main">'.lang('edit_participant_status');
    if (isset($status_id)) {
        echo ' '.$status_name[lang('lang')];
    }
    echo '              </div>
                    </div>
                    <div class="orsee-form-shell">';

    if (isset($status_id)) {
        echo '          <div class="field">
                            <div class="control"><span class="orsee-dense-id-tag">'.lang('id').': '.$status_id.'</span></div>
                        </div>';
    }

    echo '              <div class="field">
                            <label class="label">'.lang('name').':</label>
                            <div class="control">';
    foreach ($languages as $language) {
        if (!isset($status_name[$language])) {
            $status_name[$language]='';
        }
        echo '                  <div class="field">
                                    <label class="label">'.$language.':</label>
                                    <div class="control">
                                        <input class="input is-primary orsee-input orsee-input-text" name="status_name['.$language.']" type="text" maxlength="200" value="'.htmlspecialchars(stripslashes($status_name[$language])).'">
                                    </div>
                                </div>';
    }
    echo '                  </div>
                        </div>';

    if ($not_unconfirmed) {
        echo '          <div class="field">
                            <label class="label">'.lang('is_default_for_participants_becoming_active').'</label>
                            <div class="control">';
        if ($status['is_default_active']=="y" || $status['is_default_inactive']=="y") {
            if ($status['is_default_active']=="y") {
                echo lang('yes');
            } else {
                echo lang('no');
            }
        } else {
            echo '              <label class="radio"><input type="radio" name="is_default_active" value="y"';
            if ($status['is_default_active']=="y") {
                echo ' CHECKED';
            }
            echo '>'.lang('yes').'</label>&nbsp;&nbsp;
                                <label class="radio"><input type="radio" name="is_default_active" value="n"';
            if ($status['is_default_active']!="y") {
                echo ' CHECKED';
            }
            echo '>'.lang('no').'</label>';
        }
        echo '              </div>
                        </div>';

        echo '          <div class="field">
                            <label class="label">'.lang('is_default_for_participants_becoming_inactive').'</label>
                            <div class="control">';
        if ($status['is_default_active']=="y" || $status['is_default_inactive']=="y") {
            if ($status['is_default_inactive']=="y") {
                echo lang('yes');
            } else {
                echo lang('no');
            }
        } else {
            echo '              <label class="radio"><input type="radio" name="is_default_inactive" value="y"';
            if ($status['is_default_inactive']=="y") {
                echo ' CHECKED';
            }
            echo '>'.lang('yes').'</label>&nbsp;&nbsp;
                                <label class="radio"><input type="radio" name="is_default_inactive" value="n"';
            if ($status['is_default_inactive']!="y") {
                echo ' CHECKED';
            }
            echo '>'.lang('no').'</label>';
        }
        echo '              </div>
                        </div>';

        echo '          <div class="field">
                            <label class="label">'.lang('access_to_profile').'</label>
                            <div class="control">
                                <label class="radio"><input type="radio" name="access_to_profile" value="y"';
        if ($status['access_to_profile']=="y") {
            echo ' CHECKED';
        }
        echo '>'.lang('yes').'</label>&nbsp;&nbsp;
                                <label class="radio"><input type="radio" name="access_to_profile" value="n"';
        if ($status['access_to_profile']!="y") {
            echo ' CHECKED';
        }
        echo '>'.lang('no').'</label>
                            </div>
                        </div>';

        echo '          <div class="field">
                            <label class="label">'.lang('error_message_to_participant_when_access_is_denied').'</label>
                            <div class="control">';
        foreach ($languages as $language) {
            if (!isset($status_error[$language])) {
                $status_error[$language]='';
            }
            echo '              <div class="field">
                                    <label class="label">'.$language.':</label>
                                    <div class="control">
                                        <input class="input is-primary orsee-input orsee-input-text" style="width: 100%; max-width: 100%;" name="status_error['.$language.']" type="text" maxlength="200" value="'.htmlspecialchars(stripslashes($status_error[$language])).'">
                                    </div>
                                </div>';
        }
        echo '              </div>
                        </div>';

        echo '          <div class="field">
                            <label class="label">'.lang('eligible_for_experiments').'</label>
                            <div class="control">
                                <label class="radio"><input type="radio" name="eligible_for_experiments" value="y"';
        if ($status['eligible_for_experiments']=="y") {
            echo ' CHECKED';
        }
        echo '>'.lang('yes').'</label>&nbsp;&nbsp;
                                <label class="radio"><input type="radio" name="eligible_for_experiments" value="n"';
        if ($status['eligible_for_experiments']!="y") {
            echo ' CHECKED';
        }
        echo '>'.lang('no').'</label>
                            </div>
                        </div>';
    }

    echo '              <div class="field orsee-form-row-grid orsee-form-row-grid--3 orsee-form-actions">
                            <div class="orsee-form-row-col has-text-left">
                                '.button_back('participant_status_main.php').'
                            </div>
                            <div class="orsee-form-row-col has-text-centered">
                                <input class="button orsee-btn" name="edit" type="submit" value="';
    if (!isset($status_id)) {
        echo lang('add');
    } else {
        echo lang('change');
    }
    echo '                      ">
                            </div>
                            <div class="orsee-form-row-col has-text-right">';
    if (isset($status_id) && check_allow('participantstatus_delete') && $not_unconfirmed) {
        echo button_link('participant_status_delete.php?status_id='.urlencode($status_id),
            lang('delete'),'trash-o','','','orsee-btn--delete');
    }
    echo '                  </div>
                        </div>
                    </div>
                </div>
            </form>
            <br>';
}
include("footer.php");

?>
