<?php
// part of orsee. see orsee.org
ob_start();
$title="configure_participant_profile_field";
$menu__area="options";
$js_modules=array('flatpickr');
include("header.php");

if ($proceed) {
    $allow=check_allow('pform_config_field_configure','options_participant_profile.php');
}

if ($proceed) {
    $user_columns=participant__userdefined_columns();
    $system_field_names=array('email','language','subscriptions');
    if (!isset($_REQUEST['mysql_column_name'])) {
        redirect('admin/options_participant_profile.php');
    }
    $field_name=trim((string)$_REQUEST['mysql_column_name']);
    if (!isset($user_columns[$field_name]) && !in_array($field_name,$system_field_names,true)) {
        redirect('admin/options_participant_profile.php');
    }

    $field_row=orsee_db_load_array("profile_fields",$field_name,"mysql_column_name");
    $profile_field_specs=participant__profile_field_editor_specs();
    $variants=array();
    $variant_changed_keys=array();
    if (isset($field_row['mysql_column_name'])) {
        $policy=participant__profile_field_policy_load($field_row,$profile_field_specs);
        $variants=$policy['draft']['variants'];
        $variant_changes=array();
        if (isset($policy['changes']['variants']['changed']) && is_array($policy['changes']['variants']['changed'])) {
            $variant_changes=$policy['changes']['variants']['changed'];
        }
        $field=$policy['draft']['baseline'];
        $field['mysql_column_name']=$field_name;
        $field['enabled']=($policy['draft']['enabled']==='y' ? 1 : 0);
        $field['type']=$policy['draft']['type'];
        $draft_enabled=$policy['draft']['enabled'];
        $draft_type=$policy['draft']['type'];
    } else {
        $field=array();
        $draft_enabled='y';
        $draft_type=(isset($profile_field_specs['fields']['type']['default']) ? $profile_field_specs['fields']['type']['default'] : '');
    }
    $system_field=participant__system_profile_field_prepare($field_name,$field,$profile_field_specs);
    if (is_array($system_field)) {
        $field=$system_field;
        if (!isset($policy) || !is_array($policy)) {
            $draft_enabled='y';
            $draft_type=$field['type'];
        }
    } elseif (!isset($field['mysql_column_name'])) {
        redirect('admin/options_participant_profile.php');
    }
}

if ($proceed) {
    $field_sets=participant__get_editable_display_fields($profile_field_specs,$field_name);
    $editable_fields=$field_sets['editable_fields'];
    $display_fields=array();
    foreach ($editable_fields as $key) {
        if (isset($profile_field_specs['fields'][$key]['variant_overridable']) && $profile_field_specs['fields'][$key]['variant_overridable']) {
            $display_fields[]=$key;
        }
    }

    $variant_key=(isset($_REQUEST['variant_key']) ? trim((string)$_REQUEST['variant_key']) : '');
    $variant=array('scope_contexts'=>array(),'subpools'=>array(),'overrides'=>array());
    if ($variant_key!=='' && isset($variants[$variant_key]) && is_array($variants[$variant_key])) {
        $variant=array_merge($variant,$variants[$variant_key]);
        if (isset($variant_changes[$variant_key]) && is_array($variant_changes[$variant_key])) {
            $variant_changed_keys=$variant_changes[$variant_key];
        }
    } else {
        $variant_key='';
    }

    $field_type='';
    if (isset($user_columns[$field_name])) {
        $field_type=' <span class="orsee-font-compact">'.trim((string)$user_columns[$field_name]['Type']).'</span>';
    }
    $localized_field_name=participant__field_localized_text($field,'name_lang','name_lang');
    if ($localized_field_name==='') {
        $localized_field_name=$field_name;
    }

    // Scope contexts are limited to baseline contexts.
    $scope_context_options=$profile_field_specs['fields']['scope_contexts']['control']['options'];
    $baseline_scope_contexts=(isset($field['scope_contexts']) && is_array($field['scope_contexts']) ? $field['scope_contexts'] : array());
    $scope_context_options=array_intersect_key($scope_context_options,array_flip($baseline_scope_contexts));
    $selected_scope_contexts=(isset($variant['scope_contexts']) && is_array($variant['scope_contexts']) ? $variant['scope_contexts'] : array());
    if (isset($_REQUEST['save']) && $_REQUEST['save']) {
        $selected_scope_contexts=(isset($_REQUEST['scope_contexts']) && is_array($_REQUEST['scope_contexts']) ? $_REQUEST['scope_contexts'] : array());
    } elseif (isset($_REQUEST['scope_contexts']) && is_array($_REQUEST['scope_contexts'])) {
        $selected_scope_contexts=$_REQUEST['scope_contexts'];
    }
    $selected_scope_contexts=array_values(array_intersect(array_unique(array_map('trim',$selected_scope_contexts)),array_keys($scope_context_options)));

    // Subpool choices are limited to baseline restrict_to_subpools (empty => all).
    $all_subpools=subpools__get_subpools();
    $subpool_options=array();
    if (isset($field['restrict_to_subpools']) && is_array($field['restrict_to_subpools']) && count($field['restrict_to_subpools'])>0) {
        foreach ($field['restrict_to_subpools'] as $subpool_id) {
            $subpool_id=(int)$subpool_id;
            if ($subpool_id>0 && isset($all_subpools[$subpool_id])) {
                $subpool_options[(string)$subpool_id]=$all_subpools[$subpool_id]['subpool_name'];
            }
        }
    } else {
        foreach ($all_subpools as $subpool) {
            $subpool_options[(string)$subpool['subpool_id']]=$subpool['subpool_name'];
        }
    }
    $selected_subpools=(isset($variant['subpools']) && is_array($variant['subpools']) ? $variant['subpools'] : array());
    if (isset($_REQUEST['save']) && $_REQUEST['save']) {
        $selected_subpools=(isset($_REQUEST['subpools']) && is_array($_REQUEST['subpools']) ? $_REQUEST['subpools'] : array());
    } elseif (isset($_REQUEST['subpools']) && is_array($_REQUEST['subpools'])) {
        $selected_subpools=$_REQUEST['subpools'];
    }
    $selected_subpools=array_intersect(array_unique(array_map('intval',$selected_subpools)),array_keys($subpool_options));

    $variant_overrides=(isset($variant['overrides']) && is_array($variant['overrides']) ? $variant['overrides'] : array());

    if (isset($_REQUEST['delete_variant']) && $_REQUEST['delete_variant']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        } else {
            if ($variant_key!=='' && isset($variants[$variant_key]) && is_array($variants[$variant_key])) {
                unset($variants[$variant_key]);
                $policy_field=(isset($field_row['mysql_column_name']) ? $field_row : array(
                    'mysql_column_name'=>$field_name,
                    'enabled'=>((isset($field['enabled']) && $field['enabled']) ? 1 : 0),
                    'type'=>$field['type'],
                    'properties'=>''
                ));
                if (!isset($policy_field['properties'])) {
                    $policy_field['properties']='';
                }
                participant__profile_field_policy_save(
                    $policy_field,
                    array(
                        'enabled'=>$draft_enabled,
                        'type'=>$draft_type,
                        'baseline'=>$field,
                        'variants'=>$variants
                    ),
                    $profile_field_specs
                );
                message(lang('profile_editor_variant_deleted'));
                redirect('admin/options_participant_profile_edit.php?mysql_column_name='.urlencode($field_name));
            } else {
                message(lang('profile_editor_variant_not_found'),'error');
            }
        }
    }

    if (isset($_REQUEST['save']) && $_REQUEST['save']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        } else {
            $checked_override_keys=array();
            if (isset($_REQUEST['override']) && is_array($_REQUEST['override'])) {
                foreach ($_REQUEST['override'] as $key=>$flag) {
                    if ($flag && in_array($key,$display_fields,true)) {
                        $checked_override_keys[]=$key;
                    }
                }
            }
            $checked_override_keys=array_values(array_unique($checked_override_keys));
            $effective_type=(isset($field['type']) ? $field['type'] : '');
            if (in_array('type',$checked_override_keys,true) && isset($_REQUEST['type'])) {
                $effective_type=trim((string)$_REQUEST['type']);
            }
            $effective_type_class=($effective_type==='textarea' ? 'cond-textarea' : $effective_type);
            $visible_display_fields=array();
            foreach ($display_fields as $key) {
                if (!isset($profile_field_specs['fields'][$key]['visibility_classes']) || !is_array($profile_field_specs['fields'][$key]['visibility_classes']) || count($profile_field_specs['fields'][$key]['visibility_classes'])===0) {
                    $visible_display_fields[]=$key;
                } elseif (in_array($effective_type_class,$profile_field_specs['fields'][$key]['visibility_classes'],true)) {
                    $visible_display_fields[]=$key;
                }
            }
            $checked_override_keys=array_values(array_intersect($checked_override_keys,$visible_display_fields));
            $validated_values=participant__profile_field_editor_validate_submission($field,$effective_type,$profile_field_specs,$_REQUEST,$checked_override_keys);
            $variant_overrides=array();
            foreach ($checked_override_keys as $key) {
                $variant_overrides[$key]=$validated_values[$key];
            }

            $save_ok=true;
            if (count($selected_scope_contexts)===0) {
                message(lang('profile_editor_error_scope_required'),'error');
                $save_ok=false;
            }
            if (count($selected_subpools)===0) {
                message(lang('profile_editor_error_subpool_required'),'error');
                $save_ok=false;
            }
            if (count($checked_override_keys)===0) {
                message(lang('profile_editor_error_override_required'),'error');
                $save_ok=false;
            }
            if ($save_ok) {
                $candidate_cells=array();
                foreach ($selected_scope_contexts as $scope_context) {
                    foreach ($selected_subpools as $subpool_id) {
                        $subpool_id=(int)$subpool_id;
                        $cell_key=$scope_context.'|'.$subpool_id;
                        $scope_label=(isset($scope_context_options[$scope_context]) ? $scope_context_options[$scope_context] : $scope_context);
                        $subpool_label=(isset($subpool_options[(string)$subpool_id]) ? $subpool_options[(string)$subpool_id] : (string)$subpool_id);
                        $candidate_cells[$cell_key]=$scope_label.' / '.$subpool_label;
                    }
                }
                $conflict_variant_ids=array();
                $conflict_cells=array();
                foreach ($variants as $other_variant_key=>$other_variant) {
                    if ((string)$other_variant_key===(string)$variant_key) {
                        continue;
                    }
                    if (!isset($other_variant['scope_contexts']) || !is_array($other_variant['scope_contexts']) ||
                        !isset($other_variant['subpools']) || !is_array($other_variant['subpools'])) {
                        continue;
                    }
                    foreach ($other_variant['scope_contexts'] as $other_scope_context) {
                        foreach ($other_variant['subpools'] as $other_subpool_id) {
                            $cell_key=$other_scope_context.'|'.(int)$other_subpool_id;
                            if (isset($candidate_cells[$cell_key])) {
                                $conflict_variant_ids[(string)$other_variant_key]=true;
                                $conflict_cells[$candidate_cells[$cell_key]]=true;
                            }
                        }
                    }
                }
                if (count($conflict_variant_ids)>0) {
                    $conflict_links=array();
                    foreach (array_keys($conflict_variant_ids) as $conflict_variant_id) {
                        $link=thisdoc().'?mysql_column_name='.urlencode($field_name).'&variant_key='.urlencode((string)$conflict_variant_id);
                        $conflict_links[]='<a href="'.htmlspecialchars($link,ENT_QUOTES).'">'.lang('profile_editor_variant').' '.htmlspecialchars((string)$conflict_variant_id,ENT_QUOTES).'</a>';
                    }
                    $conflict_cell_text=implode(', ',array_keys($conflict_cells));
                    message(
                        lang('profile_editor_variant_overlaps_with').' '.implode(', ',$conflict_links).
                        '. '.lang('profile_editor_conflicting_cells').': '.htmlspecialchars($conflict_cell_text,ENT_QUOTES).'.',
                        'error'
                    );
                    $save_ok=false;
                }
            }
            if ($save_ok) {
                $variant_save=array(
                    'scope_contexts'=>$selected_scope_contexts,
                    'subpools'=>array_map('intval',$selected_subpools),
                    'overrides'=>$variant_overrides
                );
                if ($variant_key!=='' && isset($variants[$variant_key])) {
                    $variants[$variant_key]=$variant_save;
                } else {
                    $next_variant_num=0;
                    foreach (array_keys($variants) as $existing_variant_key) {
                        $existing_variant_key=(string)$existing_variant_key;
                        if (substr($existing_variant_key,0,1)==='v') {
                            $num_part=substr($existing_variant_key,1);
                            if ($num_part!=='' && ctype_digit($num_part)) {
                                $existing_num=(int)$num_part;
                                if ($existing_num>$next_variant_num) {
                                    $next_variant_num=$existing_num;
                                }
                            }
                        }
                    }
                    $next_variant_num++;
                    $variant_key='v'.$next_variant_num;
                    $variants[$variant_key]=$variant_save;
                }
                $policy_field=(isset($field_row['mysql_column_name']) ? $field_row : array(
                    'mysql_column_name'=>$field_name,
                    'enabled'=>((isset($field['enabled']) && $field['enabled']) ? 1 : 0),
                    'type'=>$field['type'],
                    'properties'=>''
                ));
                if (!isset($policy_field['properties'])) {
                    $policy_field['properties']='';
                }
                participant__profile_field_policy_save(
                    $policy_field,
                    array(
                        'enabled'=>$draft_enabled,
                        'type'=>$draft_type,
                        'baseline'=>$field,
                        'variants'=>$variants
                    ),
                    $profile_field_specs
                );
                message(lang('changes_saved'));
                redirect('admin/'.thisdoc().'?mysql_column_name='.urlencode($field_name).'&variant_key='.urlencode($variant_key));
            }
        }
    }

    javascript__tooltip_prepare();
    show_message();

    $variant_key_field='';
    if ($variant_key!=='') {
        $variant_key_field='<input type="hidden" name="variant_key" value="'.htmlspecialchars($variant_key,ENT_QUOTES).'">';
    }
    $variant_title=lang('profile_editor_add_variant').', '.$field_name;
    if ($variant_key!=='') {
        $variant_title=lang('profile_editor_field_variant').' '.$variant_key.', '.$field_name;
    }
    $delete_button='';
    if ($variant_key!=='') {
        $delete_button='<button class="button orsee-btn orsee-btn--delete" type="submit" name="delete_variant" value="1" data-orsee-confirm-submit="1" data-orsee-confirm-form="profile-field-variant-form" data-confirm="'.htmlspecialchars(lang('profile_editor_really_delete_variant'),ENT_QUOTES).'">'.lang('profile_editor_delete_variant').'</button>';
    }

    echo '<form id="profile-field-variant-form" action="'.thisdoc().'" method="POST">
            <input type="hidden" name="mysql_column_name" value="'.$field_name.'">
            '.$variant_key_field.'
            '.csrf__field().'
            <div class="orsee-panel">
                <div class="orsee-panel-title"><div>'.htmlspecialchars($variant_title,ENT_QUOTES).'</div></div>
                <div class="orsee-form-shell">
                    <div class="field">
                        <label class="label">'.lang('mysql_column_name').':</label>
                        <div class="control"><div class="orsee-dense-id"><span class="orsee-dense-id-tag orsee-dense-id-tag--verbatim">'.$field_name.'</span>'.$field_type.'</div></div>
                    </div>
                    <div class="field">
                        <label class="label">'.lang('profile_editor_field_name').':</label>
                        <div class="control">'.htmlspecialchars($localized_field_name,ENT_QUOTES).'</div>
                    </div>
                    <div class="field" style="background: var(--color-list-shade2); border-radius: 0.32rem; padding: 0.38rem 0.55rem;">
                        <label class="label">'.lang('profile_editor_variant_scope_contexts').'</label>
                        <div class="control">'.pform_options_checkboxrow('scope_contexts',$scope_context_options,$selected_scope_contexts).'</div>
                    </div>
                    <div class="field" style="background: var(--color-list-shade2); border-radius: 0.32rem; padding: 0.38rem 0.55rem;">
                        <label class="label">'.lang('profile_editor_variant_subpools').'</label>
                        <div class="control">'.pform_options_checkboxrow('subpools',$subpool_options,$selected_subpools).'</div>
                    </div>';
    echo participant__profile_field_editor_render_controls($profile_field_specs,$field,true,$variant_overrides,$variant_changed_keys);
    echo '
                    <div class="field orsee-form-row-grid orsee-form-row-grid--3 orsee-form-actions">
                        <div class="orsee-form-row-col has-text-left">'.button_back('options_participant_profile_edit.php?mysql_column_name='.urlencode($field_name), lang('back')).'</div>
                        <div class="orsee-form-row-col has-text-centered"><input class="button orsee-btn" type="submit" name="save" value="'.lang('save').'"></div>
                        <div class="orsee-form-row-col has-text-right">'.$delete_button.'</div>
                    </div>
                </div>
            </div>
        </form>';

    echo '<script type="text/javascript">
            function toggle_form_fields() {
                var condfields = document.querySelectorAll(".condfield");
                condfields.forEach(function (field) {
                    field.style.display = "none";
                });
                var typeSelect=document.getElementById("type_select");
                var typeval=typeSelect ? typeSelect.value : "'.$field['type'].'";
                var typeclass = (typeval === "textarea") ? "cond-textarea" : typeval;
                var visibleFields = document.querySelectorAll("." + typeclass);
                visibleFields.forEach(function (field) {
                    field.style.display = "";
                });
                toggle_date_default_fields();
                toggle_default_value_modes();
            }
            function toggle_date_default_fields() {
                var typeSelect=document.getElementById("type_select");
                var modeSelect=document.getElementById("date_default_mode_select");
                var fixedFields=document.querySelectorAll(".date-default-fixed");
                var showFixed=false;
                var typeval=typeSelect ? typeSelect.value : "'.$field['type'].'";
                if (typeval==="date" && modeSelect && modeSelect.value==="fixed") showFixed=true;
                fixedFields.forEach(function(field) {
                    field.style.display=showFixed ? "" : "none";
                });
            }
            function toggle_default_value_modes() {
                var modeFields=document.querySelectorAll(".orsee-default-value-mode");
                modeFields.forEach(function(field) {
                    var enabled=(field.style.display!=="none");
                    var controls=field.querySelectorAll("input,select,textarea");
                    controls.forEach(function(control) {
                        control.disabled=!enabled;
                    });
                });
            }
            document.addEventListener("DOMContentLoaded", function () {
                toggle_form_fields();
                var typeSelect = document.getElementById("type_select");
                if (typeSelect) typeSelect.addEventListener("change", toggle_form_fields);
                var modeSelect = document.getElementById("date_default_mode_select");
                if (modeSelect) modeSelect.addEventListener("change", toggle_date_default_fields);
            });
        </script>';
    echo javascript__confirm_modal_script();
}

include("footer.php");

?>
