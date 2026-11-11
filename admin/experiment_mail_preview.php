<?php
// part of orsee. see orsee.org
ob_start();
$title="mail_preview";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id']) {
        $experiment_id=$_REQUEST['experiment_id'];
    } else {
        $experiment_id="";
        redirect("admin/");
    }
}

if ($proceed) {
    $allow=check_allow('experiment_invitation_edit','experiment_show.php?experiment_id='.$experiment_id);
}
if ($proceed) {
    $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
    if (!check_allow('experiment_restriction_override')) {
        check_experiment_allowed($experiment,"admin/experiment_show.php?experiment_id=".$experiment_id);
    }
}

if ($proceed) {
    $pars=array(':experiment_id'=>$experiment_id);
    $query="SELECT * from ".table('lang')."
            WHERE content_type='experiment_invitation_mail'
            AND content_name= :experiment_id";
    $experiment_mail=orsee_query($query,$pars);

    $inv_langs=lang__get_part_langs();

    echo '<div class="orsee-panel">
            <div class="orsee-panel-title">
                <div>'.lang('mail_preview').': '.$experiment['experiment_name'].'</div>
            </div>
            <div class="orsee-form-shell" style="width: min(100%, 62rem);">';

    $lang_dirs=lang__is_rtl_all_langs();
    foreach ($inv_langs as $inv_lang) {
        $field_dir=(isset($lang_dirs[$inv_lang]) && $lang_dirs[$inv_lang] ? 'rtl' : 'ltr');
        // split in subject and text
        $subject=str_replace(strstr($experiment_mail[$inv_lang],"\n"),"",$experiment_mail[$inv_lang]);
        $body=substr($experiment_mail[$inv_lang],strpos($experiment_mail[$inv_lang],"\n")+1,strlen($experiment_mail[$inv_lang]));

        if ($experiment['experiment_type']=="laboratory") {
            $sessionlist=experimentmail__get_session_list($experiment_id,$inv_lang);
        } else {
            $sessionlist='';
        }

        $pform_fields=participant__load_participant_email_fields($inv_lang);
        $experimentmail=experimentmail__preview_fake_participant_details($pform_fields);
        $experimentmail=experimentmail__get_invitation_mail_details($experimentmail,$experiment,$sessionlist);
        if ($experiment['sender_mail']) {
            $sendermail=$experiment['sender_mail'];
        } else {
            $sendermail=$settings['support_mail'];
        }
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
              </div>
            </div>';
    }

    echo '  <div class="orsee-options-actions">'.
                button_back('experiment_mail_participants.php?experiment_id='.urlencode($experiment_id),lang('back'))
            .'</div>
            </div>
        </div>';
}
include("footer.php");

?>
