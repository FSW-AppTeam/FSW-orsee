<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="participant_profile_fields";
$js_modules=array('flatpickr','switchy','intltelinput');
include("header.php");

if ($proceed) {
    $item_name='profile_form_public';
    if (isset($_REQUEST['item_name']) && in_array($_REQUEST['item_name'],array('profile_form_public','profile_form_admin_part'))) {
        $item_name=$_REQUEST['item_name'];
    }
}
if ($proceed) {
    $allow=check_allow('pform_templates_edit','options_main.php');
}
if ($proceed) {
    $subpools=subpools__get_subpools();
    if (isset($_REQUEST['subpool_id']) && trim($_REQUEST['subpool_id']) && isset($subpools[trim($_REQUEST['subpool_id'])])) {
        $subpool_id=(int)trim($_REQUEST['subpool_id']);
    } elseif (isset($_SESSION['profile_form_preview_subpool_id']) && isset($subpools[$_SESSION['profile_form_preview_subpool_id']])) {
        $subpool_id=(int)$_SESSION['profile_form_preview_subpool_id'];
    } else {
        $subpool_id=1;
        foreach (array_keys($subpools) as $subpool_key) {
            if ((int)$subpool_key>1) {
                $subpool_id=(int)$subpool_key;
                break;
            }
        }
    }
    $_SESSION['profile_form_preview_subpool_id']=$subpool_id;
    $subpool=orsee_db_load_array("subpools",$subpool_id,"subpool_id");
    if (!$subpool['subpool_id']) {
        $subpool=orsee_db_load_array("subpools",1,"subpool_id");
    }

    $profile_field_specs=participant__profile_field_editor_specs();
    $scope_options=array();
    foreach ($profile_field_specs['fields']['scope_contexts']['default'] as $scope_key) {
        $scope_options[$scope_key]=$profile_field_specs['fields']['scope_contexts']['control']['options'][$scope_key];
    }
    if (isset($_REQUEST['scope_context']) && isset($scope_options[$_REQUEST['scope_context']])) {
        $preview_scope_context=(string)$_REQUEST['scope_context'];
    } elseif (isset($_SESSION['profile_form_preview_scope_context']) && isset($scope_options[$_SESSION['profile_form_preview_scope_context']])) {
        $preview_scope_context=(string)$_SESSION['profile_form_preview_scope_context'];
    } else {
        $preview_scope_context='profile_form_public_create';
    }
    $_SESSION['profile_form_preview_scope_context']=$preview_scope_context;
}

if ($proceed && isset($_REQUEST['activate']) && $_REQUEST['activate']) {
    if (!csrf__validate_request_message()) {
        $proceed=false;
    } else {
        // activate field policies and public/admin profile layouts together
        $done=true;
        $result=or_query("SELECT * FROM ".table('profile_fields'));
        while ($line=pdo_fetch_assoc($result)) {
            if (!participant__profile_field_policy_activate_draft($line,$profile_field_specs)) {
                $done=false;
                break;
            }
        }
        foreach (array('profile_form_public','profile_form_admin_part') as $activate_item_name) {
            $draft=participant__load_profile_layout($activate_item_name,'draft');
            if (!isset($draft['blocks']) || !is_array($draft['blocks'])) {
                $draft['blocks']=array();
            }
            if (!participant__save_profile_layout($activate_item_name,'current',$draft)) {
                $done=false;
                break;
            }
        }
        if ($done) {
            log__admin("pform_layout_activate","item_name:profile_form_public");
            log__admin("pform_layout_activate","item_name:profile_form_admin_part");
            message(lang('participant_profile_layout_draft_activated'));
            redirect('admin/options_participant_profile.php');
        } else {
            message(lang('database_problem'),'error');
        }
    }
}

if ($proceed) {
    $edit=array();
    if (isset($subpool_id)) {
        $edit['subpool_id']=$subpool_id;
    }

    echo '<div class="orsee-panel">';
    echo '<div class="orsee-panel-title"><div>'.lang('activate_layout').'</div></div>';
    echo '<div class="orsee-form-shell">';
    echo '<form action="'.thisdoc().'" method="GET">';
    echo '<div class="field">';
    echo '<label class="label has-text-centered">'.lang('display_preview_for_scope_subjectpool').'</label>';
    echo '<div class="control is-flex is-align-items-center is-justify-content-center">';
    $scope_field=array('scope_context'=>$preview_scope_context);
    $editable_fields_old=(isset($editable_fields) && is_array($editable_fields) ? $editable_fields : array());
    $editable_fields=array('scope_context');
    echo pform_options_selectfield('scope_context',$scope_options,$scope_field);
    $editable_fields=$editable_fields_old;
    echo subpools__select_field('subpool_id',$subpool_id);
    echo '<button class="button orsee-btn" name="change_subpool" type="submit"><i class="fa fa-magic" style="padding: 0 0.3em 0 0"></i>'.lang('apply').'</button>';
    echo '</div>';
    echo '</div>';
    echo '</form>';
    echo '</div>';
    echo '</div>';

    echo '<div class="orsee-panel">';
    echo '<div class="orsee-panel-title"><div class="orsee-panel-title-main">'.lang('profile_editor_public_preview_and_comparison').'</div></div>';
    echo '<div class="orsee-form-row-grid orsee-form-row-grid--2 is-align-items-flex-start">';
    echo '<div class="orsee-form-row-col">';
    echo '<label class="label has-text-centered">'.lang('participant_form_layout_current').'</label>';
    echo '<div class="orsee-preview-surface-card p-2">';
    echo '<div class="field"><div class="control">';
    echo '<div class="orsee-form-shell">';
    participant__show_inner_form($edit,array(),$preview_scope_context,'current_template','structured_layout',true);
    echo '</div>';
    echo '</div></div>';
    echo '</div>';
    echo '</div>';
    echo '<div class="orsee-form-row-col">';
    echo '<label class="label has-text-centered">'.lang('participant_form_layout_new').'</label>';
    echo '<div class="orsee-preview-surface-card p-2">';
    echo '<div class="field"><div class="control">';
    echo '<div class="orsee-form-shell">';
    participant__show_inner_form($edit,array(),$preview_scope_context,'current_draft','structured_layout',true);
    echo '</div>';
    echo '</div></div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    echo '<div class="orsee-panel">';
    echo '<div class="orsee-panel-title"><div class="orsee-panel-title-main">'.lang('profile_editor_admin_preview_and_comparison').'</div></div>';
    echo '<div class="orsee-form-row-grid orsee-form-row-grid--2 is-align-items-flex-start">';
    echo '<div class="orsee-form-row-col">';
    echo '<label class="label has-text-centered">'.lang('participant_form_layout_current').'</label>';
    echo '<div class="orsee-preview-surface-card p-2">';
    echo '<div class="field"><div class="control">';
    echo '<div class="orsee-form-shell">'.participant__get_inner_admin_form($edit,array(),'current_template','structured_layout',true).'</div>';
    echo '</div></div>';
    echo '</div>';
    echo '</div>';
    echo '<div class="orsee-form-row-col">';
    echo '<label class="label has-text-centered">'.lang('participant_form_layout_new').'</label>';
    echo '<div class="orsee-preview-surface-card p-2">';
    echo '<div class="field"><div class="control">';
    echo '<div class="orsee-form-shell">'.participant__get_inner_admin_form($edit,array(),'current_draft','structured_layout',true).'</div>';
    echo '</div></div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    echo '<div class="orsee-panel">';
    echo '<div class="orsee-options-actions-center" style="margin-top:0;">';
    echo '<form action="'.thisdoc().'" method="POST" class="is-inline-block">';
    echo '<input type="hidden" name="subpool_id" value="'.$subpool_id.'">';
    echo csrf__field();
    echo '<button class="button orsee-btn" name="activate" value="y" type="submit"><i class="fa fa-check-to-slot" style="padding: 0 0.3em 0 0"></i>'.lang('activate_new_layout').'</button>';
    echo '</form>';
    echo '</div>';
    echo '<div class="orsee-options-actions" style="margin-top:0.8rem;">';
    echo button_back('options_participant_profile.php');
    echo '</div>';
    echo '</div>';
}

include("footer.php");

?>
