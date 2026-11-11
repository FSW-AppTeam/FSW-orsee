<?php
// part of orsee. see orsee.org
ob_start();
$title="configure_participant_profile_field";
$menu__area="options";
$js_modules=array('flatpickr');
include("header.php");

if ($proceed) {
    $user_columns=participant__userdefined_columns();
    $system_field_names=array('email','language','subscriptions');
    if (!isset($_REQUEST['mysql_column_name'])) {
        redirect('admin/options_participant_profile.php');
    } else {
        $field_name=trim((string)$_REQUEST['mysql_column_name']);
        if (!isset($user_columns[$field_name]) && !in_array($field_name,$system_field_names,true)) {
            redirect('admin/options_participant_profile.php');
        }
    }
}

if ($proceed) {
    $allow=check_allow('pform_config_field_configure','options_participant_profile.php');
}

if ($proceed) {
    $field_row=orsee_db_load_array("profile_fields",$field_name,"mysql_column_name");
    $profile_field_specs=participant__profile_field_editor_specs();
    $existing_variants=array();
    $baseline_changed_keys=array();
    $variant_changed_map=array();
    $variant_added_keys=array();
    if (!isset($field_row['mysql_column_name'])) {
        $new=true;
        $field=array();
    } else {
        $new=false;
        $policy=participant__profile_field_policy_load($field_row,$profile_field_specs);
        $existing_variants=$policy['draft']['variants'];
        if (isset($policy['changes']['enabled']) && $policy['changes']['enabled']) {
            $baseline_changed_keys[]='enabled';
        }
        if (isset($policy['changes']['type']) && $policy['changes']['type']) {
            $baseline_changed_keys[]='type';
        }
        if (isset($policy['changes']['baseline']) && is_array($policy['changes']['baseline'])) {
            $baseline_changed_keys=array_merge($baseline_changed_keys,$policy['changes']['baseline']);
        }
        $baseline_changed_keys=array_values(array_unique($baseline_changed_keys));
        if (isset($policy['changes']['variants']['changed']) && is_array($policy['changes']['variants']['changed'])) {
            $variant_changed_map=$policy['changes']['variants']['changed'];
        }
        if (isset($policy['changes']['variants']['added']) && is_array($policy['changes']['variants']['added'])) {
            $variant_added_keys=$policy['changes']['variants']['added'];
        }
        $field=$policy['draft']['baseline'];
        $field['mysql_column_name']=$field_name;
        $field['enabled']=($policy['draft']['enabled']==='y' ? 1 : 0);
        $field['type']=$policy['draft']['type'];
    }
    $system_field=participant__system_profile_field_prepare($field_name,$field,$profile_field_specs);
    if (is_array($system_field)) {
        $field=$system_field;
        $new=false;
    } elseif ($new) {
        $field=array('mysql_column_name'=>$field_name,
                    'enabled'=>'y',
                    'name_lang'=>$field_name,
                    'type'=>'select_lang');
        $field=participant__profile_field_properties_normalize($field,$profile_field_specs);
    }
}

if ($proceed) {
    $field_sets=participant__get_editable_display_fields($profile_field_specs,$field_name);
    $editable_fields=$field_sets['editable_fields'];
    $display_fields=$field_sets['display_fields'];
}

if ($proceed) {
    if ((isset($_REQUEST['save']) && $_REQUEST['save']) || (isset($_REQUEST['convert_legacy_type']) && $_REQUEST['convert_legacy_type'])) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        }
    }
}

if ($proceed) {
    if ((isset($_REQUEST['save']) && $_REQUEST['save']) || (isset($_REQUEST['convert_legacy_type']) && $_REQUEST['convert_legacy_type'])) {
        $is_system_field=in_array($field_name,array('email','language','subscriptions'),true);
        $draft_enabled='y';
        $draft_type=$field['type'];
        $convert_target=(isset($_REQUEST['convert_legacy_type']) ? trim((string)$_REQUEST['convert_legacy_type']) : '');
        if ($is_system_field) {
            $draft_enabled='y';
            $draft_type=$field['type'];
        } else {
            if (in_array('enabled',$editable_fields,true)) {
                $draft_enabled=(isset($_REQUEST['enabled']) && $_REQUEST['enabled']==='y' ? 'y' : 'n');
            } else {
                $draft_enabled=(isset($field['enabled']) && $field['enabled'] ? 'y' : 'n');
            }
            if (in_array('type',$editable_fields,true) && isset($_REQUEST['type'])) {
                $draft_type=trim((string)$_REQUEST['type']);
            }
        }

        if ($convert_target!=='') {
            $current_type=(string)$field['type'];
            $valid_conversion=(
                ($current_type==='select_list' && $convert_target==='select_lang') ||
                ($current_type==='radioline' && $convert_target==='radioline_lang')
            );
            if (!$valid_conversion) {
                message('Invalid conversion request.','error');
                redirect('admin/'.thisdoc().'?mysql_column_name='.$field_name);
            }
            $draft_type=$convert_target;
        }

        $draft_baseline=participant__profile_field_editor_validate_submission($field,($convert_target!=='' ? (string)$field['type'] : $draft_type),$profile_field_specs,$_REQUEST,$editable_fields);

        if ($convert_target!=='') {
            $legacy_option_values=(isset($draft_baseline['option_values']) && is_array($draft_baseline['option_values']) ? $draft_baseline['option_values'] : array());
            $legacy_option_keys=array();
            foreach (array_keys($legacy_option_values) as $legacy_option_key) {
                $legacy_option_keys[]=(string)$legacy_option_key;
            }

            $pars=array(':content_type'=>$field_name);
            $query="SELECT lang_id, content_name
                    FROM ".table('lang')."
                    WHERE content_type=:content_type";
            $result=or_query($query,$pars);
            $existing_by_name=array();
            while ($line=pdo_fetch_assoc($result)) {
                $existing_by_name[(string)$line['content_name']]=(int)$line['lang_id'];
            }
            $extra_existing=array();
            foreach (array_keys($existing_by_name) as $existing_name) {
                if (!in_array((string)$existing_name,$legacy_option_keys,true)) {
                    $extra_existing[]=(string)$existing_name;
                }
            }
            if (count($extra_existing)>0) {
                message('Cannot convert: existing or_lang entries for this content type would be overwritten ('.implode(', ',$extra_existing).').','error');
                redirect('admin/'.thisdoc().'?mysql_column_name='.$field_name);
            }

            $languages=get_languages();
            $order_number=0;
            foreach ($legacy_option_values as $option_value=>$option_symbol) {
                $lang_item=array(
                    'content_type'=>$field_name,
                    'content_name'=>(string)$option_value,
                    'enabled'=>'y',
                    'order_number'=>$order_number
                );
                foreach ($languages as $language) {
                    $lang_item[$language]=load_language_symbol((string)$option_symbol,$language);
                }
                if (isset($existing_by_name[(string)$option_value])) {
                    orsee_db_save_array($lang_item,'lang',$existing_by_name[(string)$option_value],'lang_id');
                } else {
                    lang__insert_to_lang($lang_item);
                }
                $order_number++;
            }

            if ($convert_target==='select_lang') {
                $draft_baseline['order_select_lang_values']='fixed_order';
            } elseif ($convert_target==='radioline_lang') {
                $draft_baseline['order_radio_lang_values']='fixed_order';
            }
        }

        if ($is_system_field) {
            $draft_baseline['scope_contexts']=$profile_field_specs['fields']['scope_contexts']['default'];
            $draft_baseline['restrict_to_subpools']=array();
        }
        $policy_field=(isset($field_row['mysql_column_name']) ? $field_row : array(
            'mysql_column_name'=>$field_name,
            'enabled'=>(isset($field['enabled']) && $field['enabled'] ? 1 : 0),
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
                'baseline'=>$draft_baseline,
                'variants'=>$existing_variants
            ),
            $profile_field_specs
        );
        if ($convert_target!=='') {
            message('Converted to '.$convert_target.'.');
        } else {
            message(lang('changes_saved'));
        }
        redirect('admin/'.thisdoc().'?mysql_column_name='.$field_name);
    }
}


if ($proceed) {
    javascript__tooltip_prepare();
    $field['enabled']=($field['enabled']) ? 'y' : 'n';
    $delete_button='';
    if (!in_array($field_name,array('email','language','subscriptions'),true)) {
        $delete_button=button_link(
            'options_participant_profile_delete.php?mysql_column_name='.urlencode($field_name).'&csrf_token='.urlencode(csrf__get_token()),
            lang('delete'),
            'trash-o',
            '',
            '',
            'orsee-btn--delete'
        );
    }
    show_message();

    $field_type='';
    if (isset($user_columns[$field_name])) {
        $field_type=' <span class="orsee-font-compact">'.trim((string)$user_columns[$field_name]['Type']).'</span>';
    }
    $add_variant_button='';
    if (!$new) {
        $add_variant_button=button_link(
            'options_participant_profile_variant_edit.php?mysql_column_name='.urlencode($field_name),
            lang('profile_editor_add_variant'),
            'plus',
            '',
            '',
            'orsee-btn-compact'
        );
    }
    $variants_table='';
    if (count($existing_variants)>0) {
        $scope_context_options=$profile_field_specs['fields']['scope_contexts']['control']['options'];
        $all_subpools=subpools__get_subpools();
        $variants_rows='';
        $variant_pos=0;
        foreach ($existing_variants as $variant_key=>$variant) {
            $variant_pos++;
            $variant_changes=(isset($variant_changed_map[$variant_key]) && is_array($variant_changed_map[$variant_key]) ? $variant_changed_map[$variant_key] : array());
            $variant_added=in_array((string)$variant_key,$variant_added_keys,true);
            $variant_row_changed=($variant_added || count($variant_changes)>0);
            $scope_changed=($variant_added || in_array('scope_contexts',$variant_changes,true));
            $subpools_changed=($variant_added || in_array('subpools',$variant_changes,true));
            $overrides_changed=$variant_added;
            if (!$overrides_changed) {
                foreach ($variant_changes as $variant_change_key) {
                    if ($variant_change_key!=='scope_contexts' && $variant_change_key!=='subpools') {
                        $overrides_changed=true;
                        break;
                    }
                }
            }
            $scope_labels=array();
            foreach ($variant['scope_contexts'] as $scope_context) {
                $scope_labels[]=(isset($scope_context_options[$scope_context]) ? $scope_context_options[$scope_context] : $scope_context);
            }
            $id_text='#'.$variant_pos;
            $scope_text=implode(', ',$scope_labels);
            $scope_text_html=htmlspecialchars($scope_text,ENT_QUOTES);
            if ($scope_changed) {
                $scope_text_html='<span class="orsee-track-changed-underline">'.$scope_text_html.'</span>';
            }
            $subpool_labels=array();
            foreach ($variant['subpools'] as $subpool_id) {
                $subpool_id=(int)$subpool_id;
                $subpool_labels[]=(isset($all_subpools[$subpool_id]) ? $all_subpools[$subpool_id]['subpool_name'] : (string)$subpool_id);
            }
            $subpool_text_html=htmlspecialchars(implode(', ',$subpool_labels),ENT_QUOTES);
            if ($subpools_changed) {
                $subpool_text_html='<span class="orsee-track-changed-underline">'.$subpool_text_html.'</span>';
            }
            $affected_fields=array();
            foreach (array_keys($variant['overrides']) as $override_key) {
                $affected_fields[]=$profile_field_specs['fields'][$override_key]['label'];
            }
            $affected_fields_html=htmlspecialchars(implode(', ',$affected_fields),ENT_QUOTES);
            if ($overrides_changed) {
                $affected_fields_html='<span class="orsee-track-changed-underline">'.$affected_fields_html.'</span>';
            }
            $edit_button=button_link(
                'options_participant_profile_variant_edit.php?mysql_column_name='.urlencode($field_name).'&variant_key='.urlencode((string)$variant_key),
                lang('edit'),
                'pencil-square-o',
                '',
                '',
                'orsee-btn-compact'
            );
            $id_cell_class='orsee-table-cell';
            if ($variant_row_changed) {
                $id_cell_class.=' orsee-track-changed-left';
            }
            $variants_rows.='<div class="orsee-table-row">';
            $variants_rows.='<div class="'.$id_cell_class.'" style="white-space:nowrap;">'.htmlspecialchars($id_text,ENT_QUOTES).'</div>';
            $variants_rows.='<div class="orsee-table-cell">'.$scope_text_html.'</div>';
            $variants_rows.='<div class="orsee-table-cell">'.$subpool_text_html.'</div>';
            $variants_rows.='<div class="orsee-table-cell">'.$affected_fields_html.'</div>';
            $variants_rows.='<div class="orsee-table-cell orsee-table-action">'.$edit_button.'</div>';
            $variants_rows.='</div>';
        }
        $variants_table.='<div class="orsee-table orsee-table-cells-compact" style="margin-inline-start:auto; margin-inline-end:0;">';
        $variants_table.='<div class="orsee-table-row orsee-table-head">';
        $variants_table.='<div class="orsee-table-cell" style="white-space:nowrap;"></div>';
        $variants_table.='<div class="orsee-table-cell">'.lang('profile_editor_scopes').'</div>';
        $variants_table.='<div class="orsee-table-cell">'.lang('subpools').'</div>';
        $variants_table.='<div class="orsee-table-cell">'.lang('profile_editor_affected_fields').'</div>';
        $variants_table.='<div class="orsee-table-cell"></div>';
        $variants_table.='</div>';
        $variants_table.=$variants_rows;
        $variants_table.='</div>';
    }
    $variants_block='';
    if ($add_variant_button!=='' || $variants_table!=='') {
        $variants_block.='<style type="text/css">
            #orsee-profile-edit-variants-wrap { max-width:none; }
            @media (min-width:1101px) { #orsee-profile-edit-variants-wrap { max-width:50%; } }
        </style>';
        $variants_block.='<div style="display:flex; justify-content:flex-end; margin:0 0 0.55rem;">';
        $variants_block.='<div id="orsee-profile-edit-variants-wrap" style="width:fit-content; margin-inline-start:auto;">';
        if ($add_variant_button!=='') {
            $variants_block.='<div class="has-text-right">'.$add_variant_button.'</div>';
        }
        if ($variants_table!=='') {
            $variants_block.='<div style="margin-top:0.42rem;"><div class="orsee-option-row-comment"><strong>'.lang('profile_editor_stored_variants').'</strong></div>'.$variants_table.'</div>';
        }
        $variants_block.='</div></div>';
    }

    echo '<form action="'.thisdoc().'" method="POST">
            <input type="hidden" name="mysql_column_name" value="'.$field_name.'">
            '.csrf__field().'
            <div class="orsee-panel">
                <div class="orsee-panel-title"><div>'.lang('configure_participant_profile_field').' '.$field_name.'</div></div>
                '.$variants_block.'
                <div class="orsee-form-shell">
                    <div class="field">
                        <label class="label">'.lang('mysql_column_name').':</label>
                        <div class="control"><div class="orsee-dense-id"><span class="orsee-dense-id-tag orsee-dense-id-tag--verbatim">'.$field_name.'</span>'.$field_type.'</div></div>
                    </div>';
    echo participant__profile_field_editor_render_controls($profile_field_specs,$field,false,array(),$baseline_changed_keys);
    echo '
                    <div class="field orsee-form-row-grid orsee-form-row-grid--3 orsee-form-actions">
                        <div class="orsee-form-row-col has-text-left">'.button_back('options_participant_profile.php').'</div>
                        <div class="orsee-form-row-col has-text-centered"><input class="button orsee-btn" type="submit" name="save" value="'.lang('save').'"></div>
                        <div class="orsee-form-row-col has-text-right">'.$delete_button.'</div>
                    </div>
                </div>
            </div>
        </form>';

    echo '<script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function () {
                var adminOnly=document.querySelector(\'input[name="scope_contexts[]"][value="profile_form_admin_part"]\');
                var publicAdmin=document.querySelector(\'input[name="scope_contexts[]"][value="profile_form_public_admin_edit"]\');
                if (!adminOnly || !publicAdmin) return;
                adminOnly.addEventListener("change", function () {
                    if (adminOnly.checked) publicAdmin.checked=false;
                });
                publicAdmin.addEventListener("change", function () {
                    if (publicAdmin.checked) adminOnly.checked=false;
                });
            });
        </script>';

    if (in_array('type',$editable_fields)) {
        echo '<script type="text/javascript">
                function toggle_form_fields() {
                    var condfields = document.querySelectorAll(".condfield");
                    condfields.forEach(function (field) {
                        field.style.display = "none";
                    });

                    var typeSelect = document.getElementById("type_select");
                    if (!typeSelect) return;
                    var typeval = typeSelect.value;
                    var typeclass = (typeval === "textarea") ? "cond-textarea" : typeval;
                    var visibleFields = document.querySelectorAll("." + typeclass);
                    visibleFields.forEach(function (field) {
                        field.style.display = "";
                    });
                    toggle_ltr_fields_for_type(typeval);
                    toggle_date_default_fields();
                    toggle_default_value_modes();
                }
                function toggle_ltr_fields_for_type(typeval) {
                    var ltrFields=document.querySelectorAll("[data-force-ltr-if-type]");
                    ltrFields.forEach(function(field) {
                        var ltrTypes=(field.getAttribute("data-force-ltr-if-type") || "").split(",");
                        if (ltrTypes.indexOf(typeval)!==-1) field.setAttribute("dir","ltr");
                        else field.removeAttribute("dir");
                    });
                }
                function toggle_date_default_fields() {
                    var typeSelect=document.getElementById("type_select");
                    var modeSelect=document.getElementById("date_default_mode_select");
                    var fixedFields=document.querySelectorAll(".date-default-fixed");
                    var showFixed=false;
                    if (typeSelect && modeSelect && typeSelect.value==="date" && modeSelect.value==="fixed") showFixed=true;
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
    } else {
        echo '<script type="text/javascript">
                function toggle_form_fields() {
                    var condfields = document.querySelectorAll(".condfield");
                    condfields.forEach(function (field) {
                        field.style.display = "none";
                    });
                    var typeclass = ("'.$field['type'].'" === "textarea") ? "cond-textarea" : "'.$field['type'].'";
                    var visibleFields = document.querySelectorAll("." + typeclass);
                    visibleFields.forEach(function (field) {
                        field.style.display = "";
                    });
                    toggle_date_default_fields();
                    toggle_default_value_modes();
                }
                function toggle_date_default_fields() {
                    var modeSelect=document.getElementById("date_default_mode_select");
                    var fixedFields=document.querySelectorAll(".date-default-fixed");
                    var showFixed=false;
                    if ("'.$field['type'].'"==="date" && modeSelect && modeSelect.value==="fixed") showFixed=true;
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
                    var modeSelect = document.getElementById("date_default_mode_select");
                    if (modeSelect) modeSelect.addEventListener("change", toggle_date_default_fields);
                });
            </script>';
    }
}
include("footer.php");

?>
