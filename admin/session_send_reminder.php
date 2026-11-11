<?php
// part of orsee. see orsee.org
ob_start();
$title="session_reminder_send";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['session_id']) && $_REQUEST['session_id']) {
        $session_id=$_REQUEST['session_id'];
    } else {
        redirect("admin/");
    }
}

if ($proceed) {
    $session=orsee_db_load_array("sessions",$session_id,"session_id");
    if (!isset($session['session_id'])) {
        redirect("admin/");
    }
}

if ($proceed) {
    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) {
        redirect('admin/experiment_participants_show.php?experiment_id='.$session['experiment_id'].'&session_id='.$session_id);
    }
}

if ($proceed) {
    if (isset($_REQUEST['reallysend']) && $_REQUEST['reallysend']) {
        $reallysend=true;
    } else {
        $reallysend=false;
    }

    $allow=check_allow('session_send_reminder','experiment_participants_show.php?experiment_id='.
                            $session['experiment_id'].'&session_id='.$session_id);
}

if ($proceed) {
    if ($reallysend) {
        if (!csrf__validate_request_message()) {
            redirect('admin/session_send_reminder.php?session_id='.$session_id);
        }
        // send it out to mail queue
        $number=experimentmail__send_session_reminders_to_queue($session);
        message($number.' '.lang('xxx_session_reminder_emails_sent_out'));
        log__admin("session_send_reminder","session:".session__build_name($session,$settings['admin_standard_language']).
                                ", session_id:".$session_id.", experiment_id:".$session['experiment_id']);
        redirect('admin/experiment_participants_show.php?experiment_id='.$session['experiment_id'].'&session_id='.$session_id);
    }
}

if ($proceed) {
    // form
    echo '  <form action="session_send_reminder.php" method="POST">
                '.csrf__field().'
                <input type="hidden" name="session_id" value="'.$session_id.'">
                <div class="orsee-panel orsee-form-shell">
                    <div class="orsee-panel-title">'.lang('session_reminder_send').' '.session__build_name($session).'</div>
                    <div class="orsee-callout orsee-message-box orsee-callout-warning">'.lang('really_send_session_reminder_now').'</div>
                    <div class="field orsee-form-row-grid orsee-form-row-grid--2 orsee-form-actions">
                        <div class="orsee-form-row-col has-text-left">
                            <input class="button orsee-btn" type="submit" name="reallysend" value="'.lang('yes').'">
                        </div>
                        <div class="orsee-form-row-col has-text-right">
                            '.button_link('session_send_reminder.php?session_id='.$session_id.'&betternot=true',lang('no_sorry'),'undo').'
                        </div>
                    </div>
                </div>
            </form>';
}
include("footer.php");

?>
