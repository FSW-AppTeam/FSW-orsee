<?php
// part of orsee. see orsee.org
ob_start();
$title="send_invitations";
include("header.php");

if ($proceed) {
    if ($_REQUEST['experiment_id']) {
        $experiment_id=$_REQUEST['experiment_id'];
    } else {
        redirect("admin/");
    }
}

if ($proceed) {
    $allow=check_allow('experiment_invitation_edit','experiment_show.php?experiment_id='.$experiment_id);
}

if ($proceed) {
    if (isset($_REQUEST['id'])) {
        $id=$_REQUEST['id'];
    } else {
        $id="";
    }

    if (isset($_REQUEST['preview']) && $_REQUEST['preview']) {
        $preview=true;
    } else {
        $preview=false;
    }
    if (isset($_REQUEST['save']) && $_REQUEST['save']) {
        $save=true;
    } else {
        $save=false;
    }
    if (isset($_REQUEST['send']) && $_REQUEST['send']) {
        $send=true;
    } else {
        $send=false;
    }
    if (isset($_REQUEST['sendall']) && $_REQUEST['sendall']) {
        $sendall=true;
    } else {
        $sendall=false;
    }

    if ($preview || $save || $send || $sendall) {
        $action=true;
    } else {
        $action=false;
    }


    $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
    if (!check_allow('experiment_restriction_override')) {
        check_experiment_allowed($experiment,"admin/experiment_show.php?experiment_id=".$experiment_id);
    }
}

if ($proceed) {
    // load invitation languages
    $inv_langs=lang__get_part_langs();
    $installed_langs=get_languages();


    if ($action) {
        if (!csrf__validate_request_message()) {
            redirect('admin/experiment_mail_participants.php?experiment_id='.$experiment_id);
        }

        $sitem=$_REQUEST;
        $sitem['content_type']='experiment_invitation_mail';
        $sitem['content_name']=$experiment_id;

        // prepare lang stuff
        foreach ($inv_langs as $inv_lang) {
            $sitem[$inv_lang]=$sitem[$inv_lang.'_subject']."\n".$sitem[$inv_lang.'_body'];
        }

        // well: just to be sure: for all other languages, copy the public default lang
        foreach ($installed_langs as $inst_lang) {
            if (!in_array($inst_lang,$inv_langs)) {
                $sitem[$inst_lang]=$sitem[$settings['public_standard_language']];
            }
        }

        // is unknown or known?
        $allowed_fields=array('content_type','content_name');
        foreach ($installed_langs as $inst_lang) {
            $allowed_fields[]=$inst_lang;
        }
        $form_fields=array_filter_allowed($sitem,$allowed_fields);

        if (!$id) {
            $done=lang__insert_to_lang($form_fields);
        } else {
            $done=orsee_db_save_array($form_fields,"lang",$id,"lang_id");
        }

        if ($done) {
            message(lang('changes_saved'));
        } else {
            message(lang('database_error'),'error');
        }

        if ($preview) {
            redirect('admin/experiment_mail_preview.php?experiment_id='.$experiment_id);
        } elseif ($send || $sendall) {
            // send mails!

            $allow=check_allow('experiment_invite_participants','experiment_mail_participants.php?experiment_id='.$experiment_id);

            if ($allow) {
                $whom= ($sendall) ? "all" : "not-invited";
                $measure_start=getmicrotime();
                $sent=experimentmail__send_invitations_to_queue($experiment_id,$whom);
                message($sent.' '.lang('xxx_inv_mails_added_to_mail_queue'));
                $measure_end=getmicrotime();
                message(lang('time_needed_in_seconds').': '.round(($measure_end-$measure_start),5));
                log__admin("experiment_send_invitations","experiment:".$experiment['experiment_name'].", experiment_id:".$experiment['experiment_id']);
                redirect("admin/experiment_mail_participants.php?experiment_id=".$experiment_id);
            }
        } else {
            message(lang('mail_text_saved'));
            log__admin("experiment_edit_invitation_mail","experiment:".$experiment['experiment_name'].", experiment_id:".$experiment['experiment_id']);
            redirect('admin/'.thisdoc().'?experiment_id='.$experiment_id);
        }
    }
}

if ($proceed) {
    $pars=array(':experiment_id'=>$experiment_id);
    $query="SELECT * from ".table('lang')."
            WHERE content_type='experiment_invitation_mail'
            AND content_name= :experiment_id";
    $experiment_mail=orsee_query($query,$pars);

    if (!isset($experiment_mail['lang_id'])) {
        $experiment_mail=array('lang_id'=>'');
        foreach ($inv_langs as $inv_lang) {
            $experiment_mail[$inv_lang]='';
        }
    }

    $lang_dirs=lang__is_rtl_all_langs();
    // form
    show_message();

    echo '<div class="orsee-panel">
            <div class="orsee-panel-title">
                <div>'.lang('send_invitations').': '.$experiment['experiment_name'].'</div>
            </div>
            <div class="orsee-form-shell" style="width: min(100%, 62rem);">
            <FORM action="'.thisdoc().'" method="post">
            <INPUT type=hidden name="experiment_id" value="'.$experiment_id.'">
            <INPUT type=hidden name="id" value="'.$experiment_mail['lang_id'].'">
            '.csrf__field().'';

    foreach ($inv_langs as $inv_lang) {
        $field_dir=(isset($lang_dirs[$inv_lang]) && $lang_dirs[$inv_lang] ? 'rtl' : 'ltr');
        // split in subject and text
        $subject=str_replace(strstr($experiment_mail[$inv_lang],"\n"),"",$experiment_mail[$inv_lang]);
        $body=substr($experiment_mail[$inv_lang],strpos($experiment_mail[$inv_lang],"\n")+1,strlen($experiment_mail[$inv_lang]));

        // set defaults if not existent
        if (!$subject) {
            $subject=load_language_symbol('def_expmail_subject',$inv_lang);
        }

        if (!$body) {
            $body=load_mail('default_invitation_'.$experiment['experiment_type'].'_'.$experiment['experiment_ext_type'],$inv_lang);
            if (!$body) {
                $body=load_mail('default_invitation_'.$experiment['experiment_type'],$inv_lang);
            }
        }

        if (count($inv_langs) > 1) {
            echo '<div class="field">
                    <label class="label">'.$inv_lang.':</label>
                </div>';
        }

        echo '<div class="field">
                    <label class="label">'.lang('subject').':</label>
                    <div class="control">
                        <input class="input is-primary orsee-input orsee-input-text" dir="'.$field_dir.'" type="text" name="'.$inv_lang.'_subject" size="30" maxlength="80" value="'.htmlspecialchars((string)stripslashes($subject),ENT_QUOTES).'">
                    </div>
                </div>
                <div class="field">
                    <label class="label">'.lang('body_of_message').':</label>
                    <p class="help">'.lang('experimentmail_how_to_rebuild_default').'</p>
                    <div class="control">
                        <textarea class="textarea is-primary orsee-textarea" dir="'.$field_dir.'" name="'.$inv_lang.'_body" wrap="virtual" rows="17" cols="50">'.htmlspecialchars((string)stripslashes($body),ENT_QUOTES).'</textarea>
                    </div>
                </div>';
    }

    echo '<div class="orsee-options-edit-list">
          <div class="orsee-surface-card">
            <div class="orsee-option-item" style="display: block;">
                <div class="field">
                    <div class="control">1. '.lang('save_mail_text_only').'</div>
                </div>
                <div class="field orsee-form-row-grid orsee-form-row-grid--2 orsee-form-actions">
                    <div class="orsee-form-row-col has-text-left">
                        <INPUT class="button orsee-btn" type=submit name="preview" value="'.lang('mail_preview').'">
                    </div>
                    <div class="orsee-form-row-col has-text-right">
                        <INPUT class="button orsee-btn" type=submit name="save" value="'.lang('save').'">
                    </div>
                </div>
            </div>
          </div>
          <div class="orsee-surface-card">
            <div class="orsee-option-item" style="display: block;">
                <div class="orsee-form-row-grid orsee-form-row-grid--3" style="align-items: center;">
                    <div class="orsee-form-row-col">'.lang('assigned_subjects').': '.experiment__count_participate_at($experiment_id).'</div>
                    <div class="orsee-form-row-col">'.lang('invited_subjects').': '.experiment__count_participate_at($experiment_id,"","invited = :invited",array(':invited'=>1)).'</div>
                    <div class="orsee-form-row-col">'.lang('registered_subjects').': '.experiment__count_participate_at($experiment_id,"","session_id != :session_id",array(':session_id'=>0)).'</div>
                </div>
            </div>
            <div class="orsee-option-item" style="display: block;">
                <div class="field">
                    <div class="control">'.lang('inv_mails_in_mail_queue').': ';
    $qmails=experimentmail__mails_in_queue("invitation",$experiment_id);
    echo $qmails;

    if (check_allow('mailqueue_show_experiment')) {
        echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.button_link('experiment_mailqueue_show.php?experiment_id='.
                $experiment['experiment_id'],lang('monitor_experiment_mail_queue'),'envelope-square');
    }
    echo '  </div>
                </div>
            </div>
          </div>';


    if ($qmails>0) {
        echo '<div class="orsee-surface-card">
                    <div class="orsee-option-item" style="display: block;">
                        <div class="field">
                            <div class="control" style="color: var(--color-important-note-text);">
                                '.$qmails.' '.lang('xxx_inv_mails_for_this_exp_still_in_queue').'
                            </div>
                        </div>
                    </div>
                  </div>';
    } elseif (check_allow('experiment_invite_participants')) {
        echo '<div class="orsee-surface-card">
                            <div class="orsee-option-item" style="display: block;">
                                <div class="field">
                                    <div class="control">2. '.lang('mail_to_not_got_one').'</div>
                                </div>
                                <div class="field orsee-form-actions">
                                    <div class="control has-text-right">
                                        <INPUT class="button orsee-btn" type=submit name="send" value="'.lang('send').'">
                                    </div>
                                </div>
                            </div>
                          </div>
                          <div class="orsee-surface-card">
                            <div class="orsee-option-item" style="display: block;">
                                <div class="field">
                                    <div class="control">3. '.lang('mail_have_got_it_already').'</div>
                                </div>
                                <div class="field orsee-form-actions">
                                    <div class="control has-text-right">
                                        <INPUT class="button orsee-btn" type=submit name="sendall" value="'.lang('send_to_all').'">
                                    </div>
                                </div>
                            </div>
                          </div>';
    }
    echo '</div>
          </FORM>
          <div class="orsee-options-actions">'.button_back('experiment_show.php?experiment_id='.$experiment_id).'</div>
        </div>
      </div>';
}
include("footer.php");

?>
