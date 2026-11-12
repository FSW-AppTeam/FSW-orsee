<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="delete_admin_type";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['type_id']) && $_REQUEST['type_id']) {
        $type_id=$_REQUEST['type_id'];
    } else {
        redirect('admin/admin_type_show.php');
        $proceed=false;
    }
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) {
        $reallydelete=true;
    } else {
        $reallydelete=false;
    }

    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) {
        redirect('admin/admin_type_edit.php?type_id='.$type_id);
        $proceed=false;
    }
}

if ($proceed) {
    $allow=check_allow('admin_type_delete','admin_type_edit.php?type_id='.$type_id);
}

if ($proceed) {
    $type=orsee_db_load_array("admin_types",$type_id,"type_id");
    if (!isset($type['type_id'])) {
        redirect('admin/admin_type_show.php');
    }
}

if ($proceed) {
    if ($reallydelete) {
        if (!csrf__validate_request_message()) {
            redirect('admin/admin_type_delete.php?type_id='.$type_id);
        }

        if (isset($_REQUEST['stype']) && $_REQUEST['stype']) {
            $stype=$_REQUEST['stype'];
        } else {
            $stype='';
        }
        if ($stype) {
            $stype_type=orsee_db_load_array("admin_types",$stype,"type_id");
        }

        if (!isset($stype_type['type_id'])) {
            message("No target type id provided!",'warning');
            redirect('admin/admin_type_edit.php?type_id='.$type_id);
            $proceed=false;
        } else {
            if ($stype==$type_id) {
                message(lang('type_to_be_deleted_cannot_be_type_to_substitute'),'warning');
                redirect('admin/admin_type_delete.php?type_id='.$type_id);
                $proceed=false;
            }

            if ($proceed) {
                // update admins
                $pars=array(':new_type'=>$stype_type['type_name'],
                            ':old_type'=>$type['type_name']);
                $query="UPDATE ".table('admin')." SET admin_type= :new_type
                        WHERE admin_type= :old_type";
                $done=or_query($query,$pars);

                // delete admin type
                $query="DELETE FROM ".table('admin_types')."
                        WHERE type_id='".$type_id."'";
                $done=or_query($query);

                // bye, bye
                message(lang('admin_type_deleted').': '.$type['type_name']);
                log__admin("admin_type_delete","admintype:".$type['type_name'].", replacedby:".$stype_type['type_name']);
                redirect('admin/admin_type_show.php');
                $proceed=false;
            }
        }
    }
}

if ($proceed) {
    echo '<div class="orsee-panel orsee-form-shell">
            <div class="orsee-panel-title">'.lang('delete_admin_type').'</div>
            <div class="orsee-content">
                <div class="orsee-callout orsee-message-box orsee-callout-warning">'.lang('do_you_really_want_to_delete').'</div>
                <div class="field">
                    <label class="label">'.lang('id').'</label>
                    <div><span class="orsee-dense-id-tag">'.htmlspecialchars($type['type_id']).'</span></div>
                </div>
                <div class="field">
                    <label class="label">'.lang('name').'</label>
                    <div>'.htmlspecialchars($type['type_name']).'</div>
                </div>
                <form action="admin_type_delete.php" method="POST">
                    <input type="hidden" name="type_id" value="'.$type_id.'">
                    '.csrf__field().'
                    <div class="field">
                        <label class="label">'.lang('copy_admins_of_this_type_to').'</label>
                        <div>'.admin__select_admin_type("stype",$settings['default_admin_type'],"type_id",array($type_id)).'</div>
                    </div>
                    <div class="field orsee-form-row-grid orsee-form-row-grid--2" style="align-items: center;">
                        <div class="orsee-form-row-col">
                            <button class="button orsee-btn orsee-btn--delete" type="submit" name="reallydelete" value="1"><i class="fa fa-check-square"></i> '.lang('yes_delete').'</button>
                        </div>
                        <div class="orsee-form-row-col has-text-right">
                            <button class="button orsee-btn" type="submit" name="betternot" value="1"><i class="fa fa-undo"></i> '.lang('no_sorry').'</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>';
}
include("footer.php");

?>
