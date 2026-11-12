<?php
// part of orsee. see orsee.org
ob_start();
$title="customize_enrolment_confirmation_email";
include("header.php");

if ($proceed) {
    if ($_REQUEST['experiment_id']) {
        $experiment_id=$_REQUEST['experiment_id'];
    } else {
        redirect("admin/");
    }
}

if ($proceed) {
    if ($settings['enable_enrolment_confirmation_customization']!='y') {
        redirect('admin/experiment_show.php?experiment_id='.$experiment_id);
    }
}

if ($proceed) {
    $allow=check_allow('experiment_customize_enrolment_confirmation','experiment_show.php?experiment_id='.$experiment_id);
}

if ($proceed) {
    if (isset($_REQUEST['id'])) {
        $id=$_REQUEST['id'];
    } else {
        $id="";
    }

    if (isset($_REQUEST['save_preview']) && $_REQUEST['save_preview']) {
        $save_preview=true;
    } else {
        $save_preview=false;
    }
    if (isset($_REQUEST['show_preview']) && $_REQUEST['show_preview']) {
        $show_preview=true;
    } else {
        $show_preview=false;
    }
    if (isset($_REQUEST['save']) && $_REQUEST['save']) {
        $save=true;
    } else {
        $save=false;
    }

    if ($save_preview || $save) {
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
            redirect('admin/experiment_customize_enrol_conf.php?experiment_id='.$experiment_id);
        }

        $sitem=$_REQUEST;
        $sitem['content_type']='experiment_enrolment_conf_mail';
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
            message(lang('mail_text_saved'));
        } else {
            message(lang('database_error'),'error');
        }

        log__admin("experiment_customize_enrolment_confirmation","experiment:".$experiment['experiment_name'].", experiment_id:".$experiment['experiment_id']);

        if ($save_preview) {
            redirect('admin/experiment_customize_enrol_conf.php?experiment_id='.$experiment_id.'&show_preview=true');
        } else {
            redirect('admin/experiment_customize_enrol_conf.php?experiment_id='.$experiment_id);
        }
    }
}

if ($proceed) {
    $pars=array(':experiment_id'=>$experiment_id);
    $query="SELECT * from ".table('lang')."
            WHERE content_type='experiment_enrolment_conf_mail'
            AND content_name= :experiment_id";
    $experiment_mail=orsee_query($query,$pars);

    $session=experimentmail__preview_fake_session_details($experiment_id);
    $lang_dirs=lang__is_rtl_all_langs();
    show_message();

    if ($show_preview) {
        echo '<div class="orsee-panel">
                <div class="orsee-panel-title">
                    <div>'.lang('customize_enrolment_confirmation_email').': '.$experiment['experiment_name'].'</div>
                </div>
                <div class="orsee-form-shell" style="width: min(100%, 62rem);">';

        foreach ($inv_langs as $inv_lang) {
            $field_dir=(isset($lang_dirs[$inv_lang]) && $lang_dirs[$inv_lang] ? 'rtl' : 'ltr');
            // split in subject and text
            $subject=str_replace(strstr($experiment_mail[$inv_lang],"\n"),"",$experiment_mail[$inv_lang]);
            $body=substr($experiment_mail[$inv_lang],strpos($experiment_mail[$inv_lang],"\n")+1,strlen($experiment_mail[$inv_lang]));

            $lab=laboratories__get_laboratory_text($session['laboratory_id'],$inv_lang);

            $pform_fields=participant__load_participant_email_fields($inv_lang);
            $experimentmail=experimentmail__preview_fake_participant_details($pform_fields);
            $experimentmail['language']=$inv_lang;
            $experimentmail=experimentmail__get_session_reminder_details($experimentmail,$experiment,$session,$lab);
            $experimentmail=experimentmail__get_experiment_registration_details($experimentmail,$experiment,$session,$lab,$inv_lang);
            if ($experiment['sender_mail']) {
                $sendermail=$experiment['sender_mail'];
            } else {
                $sendermail=$settings['support_mail'];
            }
            $email_text=process_mail_template(stripslashes($body),$experimentmail);


            $email_text=process_mail_template(stripslashes($body),$experimentmail);

            echo '<div class="orsee-table" style="width: 100%; max-width: 100%; margin-bottom: 0.75rem;">';
            if (count($inv_langs) > 1) {
                echo '<div class="orsee-table-row orsee-table-subheader-row">
                        <div class="orsee-table-cell">'.$inv_lang.':</div>
                        <div class="orsee-table-cell"></div>
                    </div>';
            }
            echo '<div class="orsee-table-row">
                    <div class="orsee-table-cell" style="white-space: nowrap; vertical-align: top;">'.load_language_symbol('email_from',$inv_lang).':</div>
                    <div class="orsee-table-cell">'.$sendermail.'</div>
                  </div>
                  <div class="orsee-table-row is-alt">
                    <div class="orsee-table-cell" style="white-space: nowrap; vertical-align: top;">'.load_language_symbol('email_to',$inv_lang).':</div>
                    <div class="orsee-table-cell">'.$experimentmail['email'].'</div>
                  </div>
                  <div class="orsee-table-row">
                    <div class="orsee-table-cell" style="white-space: nowrap; vertical-align: top;">'.load_language_symbol('subject',$inv_lang).':</div>
                    <div class="orsee-table-cell"><div dir="'.$field_dir.'">'.stripslashes($subject).'</div></div>
                  </div>
                  <div class="orsee-table-row is-alt">
                    <div class="orsee-table-cell" style="white-space: nowrap; vertical-align: top;">'.load_language_symbol('body_of_message',$inv_lang).':</div>
                    <div class="orsee-table-cell"><div dir="'.$field_dir.'">'.nl2br($email_text);
            if (isset($experimentmail['include_footer']) && $experimentmail['include_footer']=="y") {
                echo nl2br(stripslashes(experimentmail__get_mail_footer(0)));
            }
            echo '  </div></div>
                  </div>';
            echo '</div>';
        }

        echo '      <div class="orsee-options-actions">'.
                        button_back('experiment_customize_enrol_conf.php?experiment_id='.urlencode($experiment_id),lang('back'))
                    .'</div>
                </div>
            </div>';
    } else {
        if (!isset($experiment_mail['lang_id'])) {
            $experiment_mail=array('lang_id'=>'');
            foreach ($inv_langs as $inv_lang) {
                $experiment_mail[$inv_lang]='';
            }
        }

        // form

        echo '<div class="orsee-panel">
                <div class="orsee-panel-title">
                    <div>'.lang('customize_enrolment_confirmation_email').': '.$experiment['experiment_name'].'</div>
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
                $subject=load_language_symbol('enrolment_email_subject',$inv_lang);
            }

            if (!$body) {
                $body=load_mail('public_experiment_registration',$inv_lang);
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

        echo '<div class="field">
                <div class="control">'.lang('save_mail_text_only').'</div>
              </div>
              <div class="field orsee-form-row-grid orsee-form-row-grid--2 orsee-form-actions">
                <div class="orsee-form-row-col has-text-left">
                    <INPUT class="button orsee-btn" type="submit" name="save_preview" value="'.lang('mail_preview').'">
                </div>
                <div class="orsee-form-row-col has-text-right">
                    <INPUT class="button orsee-btn" type="submit" name="save" value="'.lang('save').'">
                </div>
              </div>
              </FORM>
              <div class="orsee-options-actions">'.button_back('experiment_show.php?experiment_id='.$experiment_id).'</div>
            </div>
            </div>';
    }
}
include("footer.php");

?>
