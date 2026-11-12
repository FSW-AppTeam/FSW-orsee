<?php
// part of orsee. see orsee.org
ob_start();
$title="send_bulk_mail";
include("header.php");

if ($proceed) {
    $allow=check_allow('participants_bulk_mail','participants_main.php');
}

if ($proceed) {
    if (isset($_REQUEST['send']) && $_REQUEST['send']) {
        $send=true;
    } else {
        $send=false;
    }
    if (isset($_REQUEST['experiment_id'])) {
        $experiment_id=$_REQUEST['experiment_id'];
    } else {
        $experiment_id='';
    }
    if (isset($_REQUEST['session_id'])) {
        $session_id=$_REQUEST['session_id'];
    } else {
        $session_id='';
    }
    if (isset($_REQUEST['focus'])) {
        $focus=$_REQUEST['focus'];
    } else {
        $focus='';
    }

    // load invitation languages
    $inv_langs=lang__get_part_langs();
    $lang_dirs=lang__is_rtl_all_langs();
    $plist_ids=$_SESSION['plist_ids'];
    $number=count($plist_ids);

    $return_target='admin/';
    if ($experiment_id) {
        $return_target='admin/experiment_participants_show.php?experiment_id='.urlencode($experiment_id);
        if ($session_id) {
            $return_target.='&session_id='.urlencode($session_id);
        }
        if ($focus) {
            $return_target.='&focus='.urlencode($focus);
        }
    }

    if ($send) {
        if ((!is_array($plist_ids)) || count($plist_ids)<1) {
            redirect($return_target);
        }
    }
}

if ($proceed) {
    if ($send) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        }
    }
}

if ($proceed) {
    if ($send) {
        // checks
        $bulk=$_REQUEST;
        $continue=true;

        foreach ($inv_langs as $inv_lang) {
            if (!$bulk[$inv_lang.'_subject']) {
                message(lang('subject').': '.lang('missing_language').": ".$inv_lang,'error');
                $continue=false;
            }
            if (!$bulk[$inv_lang.'_body']) {
                message(lang('body_of_message').': '.lang('missing_language').": ".$inv_lang,'error');
                $continue=false;
            }
        }

        if ($continue) {
            $bulk_id=time();
            $pars=array();
            foreach ($inv_langs as $inv_lang) {
                $pars[]=array(':bulk_id'=>$bulk_id,
                            ':inv_lang'=>$inv_lang,
                            ':subject'=>$bulk[$inv_lang.'_subject'],
                            ':body'=>$bulk[$inv_lang.'_body']);
            }
            $query="INSERT INTO ".table('bulk_mail_texts')."
                    SET bulk_id= :bulk_id,
                    lang= :inv_lang,
                    bulk_subject= :subject,
                    bulk_text= :body";
            $done=or_query($query,$pars);

            $done=experimentmail__send_bulk_mail_to_queue($bulk_id,$plist_ids);

            message($number.' '.lang('xxx_bulk_mails_sent_to_mail_queue'));
            log__admin("bulk_mail","recipients:".$number);
            redirect($return_target);
        }
    }
}

if ($proceed) {
    show_message();

    // form
    echo '<FORM action="'.thisdoc().'" method="post">
        '.csrf__field().'
        <input type="hidden" name="experiment_id" value="'.htmlspecialchars($experiment_id).'">
        <input type="hidden" name="session_id" value="'.htmlspecialchars($session_id).'">
        <input type="hidden" name="focus" value="'.htmlspecialchars($focus).'">
        <div class="orsee-panel">
            <div class="orsee-panel-title">'.$number.' '.lang('recipients').'</div>
            <div class="orsee-form-shell">';

    foreach ($inv_langs as $inv_lang) {
        $field_dir=(isset($lang_dirs[$inv_lang]) && $lang_dirs[$inv_lang] ? 'rtl' : 'ltr');
        if (count($inv_langs) > 1) {
            echo '<div class="orsee-surface-card">
                    <div class="orsee-options-section-title">'.$inv_lang.':</div>
                    <div class="orsee-options-section-content">';
        } else {
            echo '<div>';
        }
        if (!isset($_REQUEST[$inv_lang.'_subject'])) {
            $_REQUEST[$inv_lang.'_subject']="";
        }
        if (!isset($_REQUEST[$inv_lang.'_body'])) {
            $_REQUEST[$inv_lang.'_body']="";
        }
        echo '  <div class="field">
                    <label class="label" for="'.$inv_lang.'_subject">'.lang('subject').':</label>
                    <div class="control">
                        <input id="'.$inv_lang.'_subject" class="input is-primary orsee-input orsee-input-text" dir="'.$field_dir.'" type="text" name="'.$inv_lang.'_subject" maxlength="80" value="'.$_REQUEST[$inv_lang.'_subject'].'">
                    </div>
                </div>
                <div class="field">
                    <label class="label" for="'.$inv_lang.'_body">'.lang('body_of_message').':</label>
                    <div class="control">
                        <textarea id="'.$inv_lang.'_body" class="textarea is-primary orsee-textarea" dir="'.$field_dir.'" name="'.$inv_lang.'_body" wrap="virtual" rows="20">'.$_REQUEST[$inv_lang.'_body'].'</textarea>
                    </div>
                </div>
            </div>';
    }
    echo '      <div class="field is-grouped is-justify-content-center orsee-form-actions">
                    <div class="control">
                        <input class="button orsee-btn" type="submit" name="send" value="'.lang('send').'">
                    </div>
                </div>
            </div>
        </div>
        </FORM>';
}
include("footer.php");

?>
