<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="edit_admin_type";
include("header.php");

if ($proceed) {
    $allow=check_allow('admin_type_edit','admin_type_show.php');
}

if ($proceed) {
    if (isset($_REQUEST['type_id']) && $_REQUEST['type_id']) {
        $type_id=$_REQUEST['type_id'];
    } else {
        $type_id="";
    }

    $rights=array();
    if ($type_id) {
        $type=orsee_db_load_array("admin_types",$type_id,"type_id");
    } else {
        $type=array();
    }


    if (isset($_REQUEST['save']) && $_REQUEST['save']) {
        if (!csrf__validate_request_message()) {
            redirect("admin/admin_type_edit.php?type_id=".$type_id);
        }

        $continue=true;

        $type=$_REQUEST;

        if (!$type_id && !$type['type_name']) {
            message(lang('error_admintype_name_required'),'error');
            $continue=false;
        }

        if (isset($type['right_list'])) {
            $trights=array();
            foreach ($type['right_list'] as $key=>$value) {
                if ($value) {
                    $trights[]=$key;
                }
            }
            $type['rights']=implode(",",$trights);
        } else {
            $type['rights']="";
        }


        if ($continue) {
            if (!$type_id) {
                $form_fields=array_filter_allowed($type,array('type_name','rights'));
                $pars=array(':type_name'=>$form_fields['type_name'],
                            ':rights'=>$form_fields['rights']);
                $query="INSERT INTO ".table('admin_types')."
                    SET type_name= :type_name,
                    rights= :rights";
                $done=or_query($query,$pars);
                $type_id=pdo_insert_id();
            } else {
                $form_fields=array_filter_allowed($type,array('rights'));
                $done=orsee_db_save_array($form_fields,"admin_types",$type_id,"type_id");
            }

            if ($done) {
                message(lang('changes_saved'));
                redirect("admin/admin_type_edit.php?type_id=".$type_id);
                $proceed=false;
            } else {
                message(lang('database_error'),'error');
            }
        }
    }
}


if ($proceed) {
    $rights=array();
    if (isset($type['rights']) && $type['rights']) {
        $trights=explode(",",$type['rights']);
        foreach ($trights as $right) {
            $rights[$right]=true;
        }
    }

    $errors=array();
    $required=array();
    // perform precondition checks
    foreach ($system__admin_rights as $systemright) {
        $line=explode(":",$systemright);
        // if selected and preconditions exist ...
        if (isset($line[2]) && $line[2] && isset($rights[$line[0]]) && $rights[$line[0]]) {
            $preconds=explode(",",$line[2]);
            // check if preconditions are met!
            foreach ($preconds as $cond) {
                if (!isset($rights[$cond]) || !$rights[$cond]) {
                    message(lang('warning').' "'.
                        $line[0].'" '.lang('xxx_right_requires_right_xxx').' "'.$cond.'"!');
                    $errors[]=$line[0];
                    $required[]=$cond;
                }
            }
        }
    }

    show_message();
    if (!isset($type['type_name'])) {
        $type['type_name']="";
    }
    if ($type_id) {
        $save_button='<button type="submit" class="button orsee-btn" name="save" value="1"><i class="fa fa-save"></i> '.lang('save').'</button>';
    } else {
        $save_button='<button type="submit" class="button orsee-btn" name="save" value="1">'.lang('add').'</button>';
    }
    echo '<div class="orsee-options-list-panel">';
    echo '<FORM action="admin_type_edit.php" method="post">'.csrf__field().'
';
    echo '<INPUT type="hidden" name="type_id" value="'.$type_id.'">';

    echo '<div class="orsee-panel orsee-option-section">';
    echo '<div class="orsee-panel-title"><div>'.lang('edit_admin_type');
    if ($type_id) {
        echo ': '.$type['type_name'];
    }
    echo '</div></div>';
    if (!$type_id) {
        echo '<div class="field" style="display: flex; align-items: center; gap: 0.45rem;">';
        echo '<label class="label" style="margin: 0;">'.lang('name').':</label>';
        echo '<input class="input" type="text" name="type_name" size="20" maxlength="20" value="'.$type['type_name'].'" style="max-width: 420px;">';
        echo '</div>';
    }
    echo '<div class="orsee-options-actions-center orsee-stat-actions">';
    echo $save_button;
    echo '</div>';
    echo '<div style="margin-top: 0.6rem;">';
    echo '<div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile orsee-table-body-cells-compact">';
    echo '<div class="orsee-table-row orsee-table-head">';
    echo '<div class="orsee-table-cell"></div>';
    echo '<div class="orsee-table-cell">'.lang('authorization').'</div>';
    echo '<div class="orsee-table-cell">'.lang('description').'</div>';
    echo '<div class="orsee-table-cell">'.lang('precondition_rights').'</div>';
    echo '</div>';

    $shade=true;
    $lastclass="";

    foreach ($system__admin_rights as $right) {
        $line=explode(":",$right);
        $tclass=str_replace(strstr($line[0],"_"),"",$line[0]);
        if (!isset($line[1])) {
            $line[1]="";
        }
        if (!isset($line[2])) {
            $line[2]="";
        }
        if ($tclass!=$lastclass) {
            if ($lastclass!=="") {
                echo '<div class="orsee-table-row orsee-table-row-spacer">';
                echo '<div class="orsee-table-cell"></div>';
                echo '<div class="orsee-table-cell"></div>';
                echo '<div class="orsee-table-cell"></div>';
                echo '<div class="orsee-table-cell"></div>';
                echo '</div>';
            }
            $lastclass=$tclass;
            $shade=true;
        }
        $row_class='orsee-table-row';
        if ($shade) {
            $row_class.=' is-alt';
        }
        echo '<div class="'.$row_class.'">';
        echo '<div class="orsee-table-cell" data-label="">';
        echo '<INPUT type="checkbox" name="right_list['.$line[0].']" value="'.$line[0].'"';
        if (isset($rights[$line[0]]) && $rights[$line[0]]) {
            echo ' CHECKED';
        }
        echo '>';
        echo '</div>';

        $bgcolor_auth="";
        if (in_array($line[0],$required)) {
            $bgcolor_auth='background: var(--color-admin-type-required-by-error); border-radius: 0.4rem; padding: 0 0.3rem;';
        }
        if (in_array($line[0],$errors)) {
            $bgcolor_auth='background: var(--color-admin-type-error-missing-required); border-radius: 0.4rem; padding: 0 0.3rem;';
        }
        echo '<div class="orsee-table-cell" data-label="'.lang('authorization').'" style="'.$bgcolor_auth.'">'.$line[0].'</div>';

        echo '<div class="orsee-table-cell" data-label="'.lang('description').'">'.$line[1].'</div>';

        $bgcolor_pre="";
        if (in_array($line[0],$required)) {
            $bgcolor_pre='background: var(--color-admin-type-error-missing-required); border-radius: 0.4rem; padding: 0 0.3rem;';
        }
        if (in_array($line[0],$errors)) {
            $bgcolor_pre='background: var(--color-admin-type-required-by-error); border-radius: 0.4rem; padding: 0 0.3rem;';
        }
        echo '<div class="orsee-table-cell" data-label="'.lang('precondition_rights').'" style="'.$bgcolor_pre.'">'.$line[2].'</div>';
        echo '</div>';
        if ($shade) {
            $shade=false;
        } else {
            $shade=true;
        }
    }
    echo '</div>';
    echo '</div>';
    echo '<div class="orsee-options-actions-center orsee-stat-actions">';
    echo $save_button;
    echo '</div>';
    echo '</FORM>';

    if ($type_id) {
        if (check_allow('admin_type_delete')) {
            echo '<div class="orsee-options-actions-center orsee-stat-actions">';
            echo button_link_delete('admin_type_delete.php?type_id='.urlencode($type_id),lang('delete'));
            echo '</div>';
        }
    }

    echo '<div class="orsee-stat-actions">';
    echo button_back('admin_type_show.php');
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
include("footer.php");

?>
