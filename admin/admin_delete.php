<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="delete_admin";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['admin_id']) && $_REQUEST['admin_id']) {
        $admin_id=$_REQUEST['admin_id'];
    } else {
        redirect("admin/");
        $proceed=false;
    }
}

if ($proceed) {
    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        } else {
            redirect('admin/admin_edit.php?admin_id='.$admin_id);
            $proceed=false;
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

    $allow=check_allow('admin_delete','admin_edit.php?admin_id='.$admin_id);
}

if ($proceed) {
    $admin=orsee_db_load_array("admin",$admin_id,"admin_id");


    if ($reallydelete) {
        $pars=array(':admin_id'=>$admin_id);
        $query="DELETE FROM ".table('admin')."
                WHERE admin_id= :admin_id";
        $result=or_query($query,$pars);
        log__admin("admin_delete",$admin['adminname']);

        message(lang('admin_deleted').': '.$admin['adminname']);

        redirect('admin/admin_show.php');
        $proceed=false;
    }
}

if ($proceed) {
    // form

    $num_experiments=experiment__count_experiments("experimenter LIKE :adminname",array(':adminname'=>'%|'.$admin['adminname'].'|%'));

    if ($num_experiments>0) {
        echo lang('admin_delete_warning');
    }

    echo '<div class="orsee-panel orsee-form-shell">
            <div class="orsee-panel-title">'.lang('delete_admin').'</div>
            <div class="orsee-content">';
    if ($num_experiments>0) {
        echo '<div class="orsee-callout orsee-message-box orsee-callout-warning">'.lang('admin_delete_warning').'</div>';
    }
    echo '      <div class="field">
                    <label class="label">'.lang('id').'</label>
                    <div><span class="orsee-dense-id-tag">'.htmlspecialchars($admin['admin_id']).'</span></div>
                </div>
                <div class="field">
                    <label class="label">'.lang('name').'</label>
                    <div>'.htmlspecialchars($admin['fname'].' '.$admin['lname']).'</div>
                </div>
                <div class="field">
                    <label class="label">'.lang('username').'</label>
                    <div>'.htmlspecialchars($admin['adminname']).'</div>
                </div>
                <div class="field orsee-form-row-grid orsee-form-row-grid--2" style="align-items: center;">
                    <div class="orsee-form-row-col">
                        '.button_link(
                            'admin_delete.php?admin_id='.$admin_id.'&reallydelete=true&csrf_token='.urlencode(csrf__get_token()),
                            lang('yes_delete'),'check-square','','','orsee-btn--delete').'
                    </div>
                    <div class="orsee-form-row-col has-text-right">
                        '.button_link(
                            'admin_delete.php?admin_id='.$admin_id.'&betternot=true&csrf_token='.urlencode(csrf__get_token()),
                            lang('no_sorry'),'undo').'
                    </div>
                </div>
            </div>
        </div>';
}
include("footer.php");

?>
