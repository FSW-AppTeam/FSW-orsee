<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="delete_experiment_type";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['exptype_id'])) {
        $exptype_id=$_REQUEST['exptype_id'];
    } else {
        $exptype_id="";
    }

    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) {
        redirect('admin/experiment_type_edit.php?exptype_id='.$exptype_id);
    }
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) {
        $reallydelete=true;
    } else {
        $reallydelete=false;
    }

    $allow=check_allow('experimenttype_delete','experiment_type_edit.php?exptype_id='.$exptype_id);
}

if ($proceed) {
    $pars=array(':exptype_id'=>$exptype_id);
    $query="SELECT * from ".table('lang')." WHERE content_type='experiment_type' AND content_name= :exptype_id";
    $selfdesc=orsee_query($query,$pars);

    // load subject pool
    $exptype=orsee_db_load_array("experiment_types",$exptype_id,"exptype_id");
    if (!isset($exptype['exptype_id'])) {
        redirect('admin/experiment_type_main.php');
    }
}

if ($proceed) {
    $exptypes=load_external_experiment_types();
    if (count($exptypes)==1) {
        message(lang('error_cannot_delete_last_experimenttype'),'warning');
        redirect('admin/experiment_type_edit.php?exptype_id='.$exptype_id);
    }
}


if ($proceed) {
    // load languages
    $languages=get_languages();

    foreach ($languages as $language) {
        $exptype[$language]=$selfdesc[$language];
    }

    if ($reallydelete) {
        if (!csrf__validate_request_message()) {
            redirect('admin/experiment_type_delete.php?exptype_id='.$exptype_id);
        }

        if (isset($_REQUEST['merge_with']) && $_REQUEST['merge_with']) {
            $merge_with=$_REQUEST['merge_with'];
        } else {
            $merge_with='';
        }
        if ($merge_with) {
            $merge_with_type=orsee_db_load_array("experiment_types",$merge_with,"exptype_id");
        }

        if (!isset($merge_with_type['exptype_id'])) {
            message("No target exptype provided!",'warning');
            redirect('admin/experiment_type_edit.php?exptype_id='.$exptype_id);
        } else {
            $queries=array();
            $tq=array();
            $tq['pars']=array(':exptype_id'=>$exptype_id);
            $tq['query']="DELETE FROM ".table('experiment_types')."
                    WHERE exptype_id= :exptype_id";
            $queries[]=$tq;

            $tq=array();
            $tq['pars']=array(':exptype_id'=>$exptype_id);
            $tq['query']="DELETE FROM ".table('lang')."
                    WHERE content_name= :exptype_id
                    AND content_type='experiment_type'";
            $queries[]=$tq;

            $tq=array();
            $tq['pars']=array();
            $pars=array(':exptype_id'=>'%|'.$exptype_id.'|%');
            $query="SELECT participant_id, subscriptions
                    FROM ".table('participants')."
                    WHERE subscriptions LIKE :exptype_id";
            $result=or_query($query,$pars);
            while ($line=pdo_fetch_assoc($result)) {
                $subs=db_string_to_id_array($line['subscriptions']);
                foreach ($subs as $k=>$et) {
                    if ($et==$exptype_id) {
                        unset($subs[$k]);
                    }
                }
                if (!in_array($merge_with,$subs)) {
                    $subs[]=$merge_with;
                }
                $tq['pars'][]=array(
                            ':participant_id'=>$line['participant_id'],
                            ':subscriptions'=>id_array_to_db_string($subs)
                                );
            }
            $affected_participants=count($tq['pars']);
            $tq['query']="UPDATE ".table('participants')."
                    SET subscriptions= :subscriptions
                    WHERE participant_id= :participant_id";
            $queries[]=$tq;

            $tq=array();
            $tq['pars']=array(':merge_with'=>$merge_with,
                        ':exptype_id'=>$exptype_id);
            $tq['query']="UPDATE ".table('experiments')."
                    SET experiment_ext_type= :merge_with
                    WHERE experiment_ext_type= :exptype_id";
            $queries[]=$tq;

            $done=pdo_transaction($queries);
            log__admin("experimenttype_delete","experimenttype:".$exptype['exptype_name']);
            message(lang('experimenttype_deleted'));
            message($affected_participants.' '.lang('xx_participants_assigned_to_exptype').' "'.$merge_with_type['exptype_name'].'".');
            redirect("admin/experiment_type_main.php");
        }
    }
}

if ($proceed) {
    // form

    echo '<div class="orsee-panel orsee-form-shell">
                <div class="orsee-panel-title">'.lang('delete_experiment_type').'</div>
                <div class="orsee-content">
                    <div class="orsee-callout orsee-message-box orsee-callout-warning">'.lang('do_you_really_want_to_delete').'</div>
                    <div class="field">
                        <label class="label">'.lang('id').'</label>
                        <div><span class="orsee-dense-id-tag">'.htmlspecialchars($exptype['exptype_id']).'</span></div>
                    </div>
                    <div class="field">
                        <label class="label">'.lang('name').'</label>
                        <div>'.htmlspecialchars($exptype['exptype_name']).'</div>
                    </div>
                    <form action="experiment_type_delete.php" method="POST">
                        <input type="hidden" name="exptype_id" value="'.$exptype_id.'">
                        '.csrf__field().'
                        <div class="field">
                            <label class="label">'.lang('replace_experimenttype_with').'</label>
                            <div>';
    experiment__exptype_select_field("merge_with","exptype_id","exptype_name","",$exptype['exptype_id'],true);
    echo '              </div>
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
