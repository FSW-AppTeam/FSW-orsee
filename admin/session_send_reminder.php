<?php
// part of orsee. see orsee.org
ob_start();

$title="session_reminder_send";
include("header.php");
if ($proceed) {
    if (isset($_REQUEST['session_id']) && $_REQUEST['session_id']) $session_id=$_REQUEST['session_id'];
    else redirect ("admin/");
}

if ($proceed) {
    $session=orsee_db_load_array("sessions",$session_id,"session_id");
    if (!isset($session['session_id'])) redirect ("admin/");
}

if ($proceed) {
    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
        redirect ('admin/experiment_participants_show.php?experiment_id='.$session['experiment_id'].'&session_id='.$session_id);
}

if ($proceed) {
    if (isset($_REQUEST['reallysend']) && $_REQUEST['reallysend']) $reallysend=true;
    else $reallysend=false;

    $allow=check_allow('session_send_reminder','experiment_participants_show.php?experiment_id='.
                            $session['experiment_id'].'&session_id='.$session_id);
}

if ($proceed) {

    if ($reallysend) {
        if (!csrf__validate_request_message()) {
            redirect ('admin/session_send_reminder.php?session_id='.$session_id);
        }
        // send it out to mail queue
        $number=experimentmail__send_session_reminders_to_queue($session);
        message ($number.' '.lang('xxx_session_reminder_emails_sent_out'));
        log__admin("session_send_reminder","session:".session__build_name($session,$settings['admin_standard_language']).
                                "\nsession_id:".$session_id);
        redirect ('admin/experiment_participants_show.php?experiment_id='.$session['experiment_id'].'&session_id='.$session_id);
    }
}

if ($proceed) {
    // form
    echo '  <CENTER>
        <TABLE class="or_formtable">
            <TR><TD colspan="2">
                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                                <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">
                                    '.lang('session_reminder_send').' '.session__build_name($session).'
                                </TD>
                </TR></TABLE>
            </TD></TR>
            <TR>
                <TD colspan=2>
                    '.lang('really_send_session_reminder_now').'
                </TD>
            </TR>
            <TR>
                <TD align=left>
                    <FORM action="session_send_reminder.php" method="POST">
                    '.csrf__field().'
                    <INPUT type="hidden" name="session_id" value="'.$session_id.'">
                    <INPUT class="button" type="submit" name="reallysend" value="'.lang('yes').'">
                    </FORM>
                </TD>
                <TD align=right>
                    '.button_link('session_send_reminder.php?session_id='.$session_id.'&betternot=true',
                    lang('no_sorry'),'undo biconred').'
                </TD>
            </TR>
        </TABLE>
        </center>';

}
include ("footer.php");
?>
