<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="delete_participant_profile_field";
include("header.php");

if ($proceed) {
    $user_columns=participant__userdefined_columns();
    if (!isset($_REQUEST['mysql_column_name']) || !isset($user_columns[$_REQUEST['mysql_column_name']])) {
        redirect('admin/options_participant_profile.php');
    } else {
        $field_name=$_REQUEST['mysql_column_name'];
    }
}

if ($proceed) {
    if ($field_name=='email') {
        redirect('admin/options_participant_profile.php');
    }
}

if ($proceed) {
    $allow=check_allow('pform_config_field_delete','options_participant_profile.php');
}

if ($proceed) {
    $field_row=orsee_db_load_array("profile_fields",$field_name,"mysql_column_name");
    $profile_field_specs=participant__profile_field_editor_specs();
    if (!isset($field_row['mysql_column_name'])) {
        $field=array(
            'mysql_column_name'=>$field_name,
            'enabled'=>'y',
            'name_lang'=>$field_name,
            'type'=>'select_lang'
        );
        $field=participant__profile_field_properties_normalize($field,$profile_field_specs);
    } else {
        $policy=participant__profile_field_policy_load($field_row,$profile_field_specs);
        $field=$policy['draft']['baseline'];
        $field['mysql_column_name']=$field_name;
        $field['enabled']=($policy['draft']['enabled']==='y' ? 1 : 0);
        $field['type']=$policy['draft']['type'];
    }
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) {
        $reallydelete=true;
    } else {
        $reallydelete=false;
    }
}

if ($proceed) {
    if ($reallydelete) {
        if (!csrf__validate_request_message()) {
            redirect("admin/options_participant_profile_delete.php?mysql_column_name=".urlencode($field_name));
        }
        // transaction?
        $pars=array(':mysql_column_name'=>$field_name);
        $query="DELETE FROM ".table('profile_fields')."
                WHERE mysql_column_name= :mysql_column_name";
        $result=or_query($query,$pars);

        $query="ALTER TABLE ".table('participants')."
                DROP COLUMN ".$field_name.",
                DROP INDEX ".$field_name."_index";
        $result=or_query($query);

        log__admin("profile_form_field_delete","mysql_column_name:".$field_name);
        message(lang('profile_form_field_deleted'));
        redirect("admin/options_participant_profile.php");
    }
}


if ($proceed) {
    // form

    echo '<div class="orsee-panel orsee-form-shell">
            <div class="orsee-panel-title">'.lang('delete_participant_profile_field').'</div>
            <div class="orsee-content">
                <div class="orsee-callout orsee-message-box orsee-callout-warning">'.lang('really_delete_profile_form_field?').'<br><b>'.lang('delete_profile_form_field_note').'</b></div>
                <div class="field">
                    <label class="label">'.lang('mysql_column_name').'</label>
                    <div><span class="orsee-dense-id-tag orsee-dense-id-tag--verbatim">'.htmlspecialchars($field_name).'</span></div>
                </div>
                <div class="field">
                    <label class="label">'.lang('type').'</label>
                    <div>'.htmlspecialchars($field['type']).'</div>
                </div>
                <form action="options_participant_profile_delete.php" method="POST">
                    <input type="hidden" name="mysql_column_name" value="'.$field_name.'">
                    '.csrf__field().'
                    <div class="field orsee-form-row-grid orsee-form-row-grid--2" style="align-items: center;">
                        <div class="orsee-form-row-col">
                            <button class="button orsee-btn orsee-btn--delete" type="submit" name="reallydelete" value="1"><i class="fa fa-check-square"></i> '.lang('yes_delete').'</button>
                        </div>
                        <div class="orsee-form-row-col has-text-right">
                            '.button_link(
        'options_participant_profile_edit.php?mysql_column_name='.urlencode($field_name),
        lang('no_sorry'),
        'undo'
    ).'
                        </div>
                    </div>
                </form>
            </div>
        </div>';
}
include("footer.php");

?>
