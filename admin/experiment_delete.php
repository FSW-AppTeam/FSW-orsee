<?php
// part of orsee. see orsee.org
ob_start();
$title="delete_experiment";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id']) {
        $experiment_id=$_REQUEST['experiment_id'];
    } else {
        redirect("admin/");
    }
}

if ($proceed) {
    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        } else {
            redirect('admin/experiment_edit.php?experiment_id='.$experiment_id);
        }
    }
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        }
    }
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) {
        $reallydelete=true;
    } else {
        $reallydelete=false;
    }

    $allow=check_allow('experiment_delete','experiment_edit.php?experiment_id='.$experiment_id);
    if (!check_allow('experiment_restriction_override')) {
        check_experiment_allowed($experiment,"admin/experiment_show.php?experiment_id=".$experiment_id);
    }
}

if ($proceed) {
    $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");

    if ($reallydelete) {
        $pars=array(':experiment_id'=>$experiment_id);
        $query="DELETE FROM ".table('experiments')."
                WHERE experiment_id= :experiment_id";
        $result=or_query($query,$pars);

        $query="DELETE FROM ".table('sessions')."
                WHERE experiment_id= :experiment_id";
        $result=or_query($query,$pars);

        $query="DELETE FROM ".table('participate_at')."
                WHERE experiment_id= :experiment_id";
        $result=or_query($query,$pars);

        $query="DELETE FROM ".table('lang')."
                WHERE content_type='experiment_invitation_mail'
                AND content_name= :experiment_id";
        $result=or_query($query,$pars);

        message(lang('experiment_deleted'));
        log__admin("experiment_delete","experiment:".$experiment['experiment_name'].", experiment_id:".$experiment['experiment_id']);
        redirect('admin/experiment_main.php');
    }
}

if ($proceed) {
    // form
    echo '<div class="orsee-panel orsee-form-shell">
            <div class="orsee-panel-title">'.lang('delete_experiment').'</div>
            <div class="orsee-content">
                <div class="orsee-callout orsee-message-box orsee-callout-warning">
                    '.lang('really_delete_experiment').'
                </div>
                <div class="field">
                    <label class="label">'.lang('id').'</label>
                    <div><span class="orsee-dense-id-tag">'.htmlspecialchars($experiment['experiment_id']).'</span></div>
                </div>
                <div class="field">
                    <label class="label">'.lang('name').'</label>
                    <div>'.htmlspecialchars($experiment['experiment_name']).'</div>
                </div>
                <div class="field">
                    <label class="label">'.lang('public_name').'</label>
                    <div>'.htmlspecialchars($experiment['experiment_public_name']).'</div>
                </div>
                <div class="field orsee-form-row-grid orsee-form-row-grid--2" style="align-items: center;">
                    <div class="orsee-form-row-col">
                        '.button_link(
        'experiment_delete.php?experiment_id='.$experiment_id.'&reallydelete=true&csrf_token='.urlencode(csrf__get_token()),
        lang('yes_delete'),
        'check-square',
        '',
        '',
        'orsee-btn--delete'
    ).'
                    </div>
                    <div class="orsee-form-row-col has-text-right">
                        '.button_link(
        'experiment_delete.php?experiment_id='.$experiment_id.'&betternot=true&csrf_token='.urlencode(csrf__get_token()),
        lang('no_sorry'),
        'undo'
    ).'
                    </div>
                </div>
            </div>
        </div>';
}
include("footer.php");

?>
