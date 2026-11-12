<?php
// part of orsee. see orsee.org
ob_start();
$title="participant_profile_fields";
$js_modules=array('listtool');
$menu__area="options";
include("header.php");

if ($proceed) {
    $allow=check_allow('pform_config_field_configure','options_main.php');
}

if ($proceed) {
    $user_columns=participant__userdefined_columns();
    $profile_field_specs=participant__profile_field_editor_specs();
    $public_scope_contexts=$profile_field_specs['fields']['scope_contexts']['default'];
    $system_fields=array(
        'email'=>array('mysql_type'=>'-','has_index'=>0,'is_configured'=>0,'enabled'=>1,'type'=>'email','properties'=>array(),'variants'=>array(),'changes'=>array()),
        'language'=>array('mysql_type'=>'-','has_index'=>0,'is_configured'=>0,'enabled'=>1,'type'=>'language','properties'=>array(),'variants'=>array(),'changes'=>array()),
        'subscriptions'=>array('mysql_type'=>'-','has_index'=>0,'is_configured'=>0,'enabled'=>1,'type'=>'subscriptions','properties'=>array(),'variants'=>array(),'changes'=>array())
    );
    foreach ($user_columns as $k=>$arr) {
        $user_columns[$k]['has_index']=0;
        $user_columns[$k]['is_configured']=0;
        $user_columns[$k]['properties']=array();
        $user_columns[$k]['variants']=array();
        $user_columns[$k]['changes']=array();
    }
    $query="SHOW INDEX FROM ".table('participants');
    $result=or_query($query);
    while ($line=pdo_fetch_assoc($result)) {
        if (isset($user_columns[$line['Column_name']])) {
            $user_columns[$line['Column_name']]['has_index']=1;
        } elseif (isset($system_fields[$line['Column_name']])) {
            $system_fields[$line['Column_name']]['has_index']=1;
        }
    }
    $query="SHOW COLUMNS FROM ".table('participants');
    $result=or_query($query);
    while ($line=pdo_fetch_assoc($result)) {
        if (isset($system_fields[$line['Field']])) {
            $system_fields[$line['Field']]['mysql_type']=$line['Type'];
        }
    }
    $query="SELECT * FROM ".table('profile_fields');
    $result=or_query($query);
    $redundant=array();
    while ($line=pdo_fetch_assoc($result)) {
        $policy=participant__profile_field_policy_load($line,$profile_field_specs);
        $draft_enabled=($policy['draft']['enabled']==='n' ? 0 : 1);
        $draft_type=$policy['draft']['type'];
        $draft_properties=$policy['draft']['baseline'];
        $draft_variants=$policy['draft']['variants'];
        $draft_changes=(isset($policy['changes']) && is_array($policy['changes']) ? $policy['changes'] : array());
        if (isset($user_columns[$line['mysql_column_name']])) {
            $user_columns[$line['mysql_column_name']]['is_configured']=1;
            $user_columns[$line['mysql_column_name']]['enabled']=$draft_enabled;
            $user_columns[$line['mysql_column_name']]['type']=$draft_type;
            $user_columns[$line['mysql_column_name']]['properties']=$draft_properties;
            $user_columns[$line['mysql_column_name']]['variants']=$draft_variants;
            $user_columns[$line['mysql_column_name']]['changes']=$draft_changes;
        } elseif (isset($system_fields[$line['mysql_column_name']])) {
            $system_draft=participant__system_profile_field_prepare($line['mysql_column_name'],array_merge($draft_properties,array(
                'mysql_column_name'=>$line['mysql_column_name'],
                'enabled'=>$draft_enabled,
                'type'=>$draft_type
            )),$profile_field_specs);
            $system_fields[$line['mysql_column_name']]['is_configured']=1;
            $system_fields[$line['mysql_column_name']]['enabled']=1;
            $system_fields[$line['mysql_column_name']]['type']=$system_draft['type'];
            unset($system_draft['mysql_column_name'],$system_draft['enabled'],$system_draft['type'],$system_draft['variants']);
            $system_fields[$line['mysql_column_name']]['properties']=$system_draft;
            $system_fields[$line['mysql_column_name']]['variants']=$draft_variants;
            $system_fields[$line['mysql_column_name']]['changes']=$draft_changes;
        } else {
            $redundant[]=$line['mysql_column_name'];
        }
    }

    // group fields for the new participant-profile page layout
    $profile_fields_grouped=array(
        'public'=>array(),
        'admin'=>array(),
        'disabled'=>array()
    );

    // system fields are mandatory and always part of the public profile form
    foreach ($system_fields as $field_name=>$field_data) {
        $profile_fields_grouped['public'][$field_name]=array('field_name'=>$field_name);
    }

    foreach ($user_columns as $field_name=>$field_data) {
        $is_configured=(isset($field_data['is_configured']) && $field_data['is_configured']);
        if (!$is_configured) {
            $profile_fields_grouped['disabled'][$field_name]=array(
                'reason'=>'new_unconfigured',
                'field_name'=>$field_name
            );
            continue;
        }

        $normalized_properties=participant__profile_field_properties_normalize($field_data['properties'],$profile_field_specs);
        $enabled=(isset($field_data['enabled']) && $field_data['enabled'] ? 1 : 0);
        $scope_contexts=$normalized_properties['scope_contexts'];
        if (!$enabled) {
            $profile_fields_grouped['disabled'][$field_name]=array(
                'reason'=>'disabled',
                'field_name'=>$field_name
            );
            continue;
        }
        if (count($scope_contexts)===0) {
            $profile_fields_grouped['disabled'][$field_name]=array(
                'reason'=>'no_scope',
                'field_name'=>$field_name
            );
            continue;
        }

        $is_public=false;
        foreach ($public_scope_contexts as $scope_context) {
            if (in_array($scope_context,$scope_contexts,true)) {
                $is_public=true;
                break;
            }
        }
        if ($is_public) {
            $profile_fields_grouped['public'][$field_name]=array('field_name'=>$field_name);
        }
        if (in_array('profile_form_admin_part',$scope_contexts,true)) {
            $profile_fields_grouped['admin'][$field_name]=array('field_name'=>$field_name);
        }
    }

    $layout_list_defs=array(
        'public'=>array(
            'item_name'=>'profile_form_public',
            'title'=>lang('profile_editor_fields_public_form'),
            'list_id'=>'profile_layout_public_list',
            'form_name'=>'public_item_order'
        ),
        'admin'=>array(
            'item_name'=>'profile_form_admin_part',
            'title'=>lang('profile_editor_fields_admin_only_form'),
            'list_id'=>'profile_layout_admin_list',
            'form_name'=>'admin_item_order'
        )
    );
}

if ($proceed) {
    if (isset($_REQUEST['delete_redundant']) && $_REQUEST['delete_redundant']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        }
    }
}

if ($proceed) {
    $form_action='';
    $requested_edit_kind='';
    $requested_edit_group='';
    $requested_edit_key='';
    $requested_edit_field_name='';
    if (isset($_REQUEST['save_layout_order']) && $_REQUEST['save_layout_order']) {
        $form_action='save_layout_order';
    } elseif (isset($_REQUEST['go_activate']) && $_REQUEST['go_activate']) {
        $form_action='go_activate';
    } elseif (isset($_REQUEST['go_edit']) && $_REQUEST['go_edit']) {
        $form_action='go_edit';
        $requested_edit=(string)$_REQUEST['go_edit'];
        if (substr($requested_edit,0,7)==='field__') {
            $requested_edit_kind='field';
            $requested_edit_field_name=substr($requested_edit,7);
        } elseif (substr($requested_edit,0,7)==='block__') {
            $requested_edit_kind='block';
            $requested_block=substr($requested_edit,7);
            $requested_block_parts=explode('__',$requested_block,2);
            if (count($requested_block_parts)===2) {
                $requested_edit_group=$requested_block_parts[0];
                $requested_edit_key=$requested_block_parts[1];
            }
        }
    }
}

if ($proceed) {
    if ($form_action!=='') {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        } else {
            $save_ok=true;
            $target_item_name='';
            $target_block_id='';
            foreach ($layout_list_defs as $group_name=>$layout_def) {
                $layout_fields=array();
                foreach ($profile_fields_grouped[$group_name] as $field_name=>$group_item) {
                    $layout_fields[$field_name]=array('default_block'=>array('type'=>'field','field'=>$field_name));
                }

                $draft_layout=participant__load_profile_layout($layout_def['item_name'],'draft');
                if (!isset($draft_layout['blocks']) || !is_array($draft_layout['blocks'])) {
                    $draft_layout['blocks']=array();
                }

                // map existing text/section instances by listtool key so we can keep their edited content
                $existing_blocks_by_key=array();
                $max_block_id=0;
                foreach ($draft_layout['blocks'] as $block_index=>$block) {
                    if (!is_array($block) || !isset($block['type'])) {
                        continue;
                    }
                    if ($block['type']==='text') {
                        if (isset($block['block_id']) && preg_match('/^b([0-9]+)$/',trim((string)$block['block_id']),$matches)) {
                            if ((int)$matches[1]>$max_block_id) {
                                $max_block_id=(int)$matches[1];
                            }
                        }
                    } elseif ($block['type']==='section') {
                        if (isset($block['block_id']) && preg_match('/^b([0-9]+)$/',trim((string)$block['block_id']),$matches)) {
                            if ((int)$matches[1]>$max_block_id) {
                                $max_block_id=(int)$matches[1];
                            }
                        }
                    }
                }
                foreach ($draft_layout['blocks'] as $block_index=>$block) {
                    if (!is_array($block) || !isset($block['type'])) {
                        continue;
                    }
                    if ($block['type']==='text') {
                        $saved_block=array('type'=>'text','text'=>'');
                        if (!isset($block['block_id']) || trim((string)$block['block_id'])==='') {
                            continue;
                        }
                        $key_block_id=trim((string)$block['block_id']);
                        $saved_block['block_id']=$key_block_id;
                        if (isset($block['text'])) {
                            $saved_block['text']=(string)$block['text'];
                        }
                        if (isset($block['text_lang']) && is_array($block['text_lang'])) {
                            $saved_block['text_lang']=$block['text_lang'];
                        }
                        if (isset($block['short_name'])) {
                            $saved_block['short_name']=(string)$block['short_name'];
                        }
                        if (isset($block['scope_contexts']) && is_array($block['scope_contexts'])) {
                            $saved_block['scope_contexts']=$block['scope_contexts'];
                        }
                        if (isset($block['restrict_to_subpools']) && is_array($block['restrict_to_subpools'])) {
                            $saved_block['restrict_to_subpools']=$block['restrict_to_subpools'];
                        }
                        $existing_blocks_by_key['text__'.$key_block_id]=$saved_block;
                    } elseif ($block['type']==='section') {
                        $saved_block=array('type'=>'section','text'=>'');
                        if (!isset($block['block_id']) || trim((string)$block['block_id'])==='') {
                            continue;
                        }
                        $key_block_id=trim((string)$block['block_id']);
                        $saved_block['block_id']=$key_block_id;
                        if (isset($block['text'])) {
                            $saved_block['text']=(string)$block['text'];
                        }
                        if (isset($block['text_lang']) && is_array($block['text_lang'])) {
                            $saved_block['text_lang']=$block['text_lang'];
                        }
                        if (isset($block['short_name'])) {
                            $saved_block['short_name']=(string)$block['short_name'];
                        }
                        if (isset($block['scope_contexts']) && is_array($block['scope_contexts'])) {
                            $saved_block['scope_contexts']=$block['scope_contexts'];
                        }
                        if (isset($block['restrict_to_subpools']) && is_array($block['restrict_to_subpools'])) {
                            $saved_block['restrict_to_subpools']=$block['restrict_to_subpools'];
                        }
                        $existing_blocks_by_key['section__'.$key_block_id]=$saved_block;
                    }
                }

                // rebuild blocks from submitted order keys (do not persist raw legacy block payloads)
                $new_blocks=array();
                if (isset($_REQUEST[$layout_def['form_name']]) && is_array($_REQUEST[$layout_def['form_name']])) {
                    foreach ($_REQUEST[$layout_def['form_name']] as $item_key) {
                        $item_key=(string)$item_key;
                        if (substr($item_key,0,7)==='field__') {
                            $field_name=substr($item_key,7);
                            if (isset($layout_fields[$field_name])) {
                                $new_blocks[]=$layout_fields[$field_name]['default_block'];
                            }
                        } elseif (substr($item_key,0,6)==='text__') {
                            if (isset($existing_blocks_by_key[$item_key])) {
                                $new_blocks[]=$existing_blocks_by_key[$item_key];
                            } else {
                                $new_blocks[]=array('type'=>'text','text'=>'','block_id'=>'b'.(++$max_block_id));
                            }
                        } elseif (substr($item_key,0,9)==='section__') {
                            if (isset($existing_blocks_by_key[$item_key])) {
                                $new_blocks[]=$existing_blocks_by_key[$item_key];
                            } else {
                                $new_blocks[]=array('type'=>'section','text'=>'','block_id'=>'b'.(++$max_block_id));
                            }
                        }
                        if ($form_action==='go_edit' && $requested_edit_kind==='block' && $requested_edit_group===$group_name && $requested_edit_key===$item_key) {
                            $target_item_name=$layout_def['item_name'];
                            $target_pos=count($new_blocks)-1;
                            if (isset($new_blocks[$target_pos]['block_id'])) {
                                $target_block_id=(string)$new_blocks[$target_pos]['block_id'];
                            }
                        }
                    }
                }

                if (!participant__save_profile_layout($layout_def['item_name'],'draft',array('blocks'=>$new_blocks))) {
                    $save_ok=false;
                    break;
                }
            }

            if ($save_ok) {
                log__admin("pform_layout_edit","item_name:profile_form_public");
                log__admin("pform_layout_edit","item_name:profile_form_admin_part");
                if ($form_action==='go_edit') {
                    if ($requested_edit_kind==='field' && $requested_edit_field_name!=='') {
                        redirect('admin/options_participant_profile_edit.php?mysql_column_name='.urlencode($requested_edit_field_name));
                    } elseif ($requested_edit_kind==='block' && $target_item_name!=='' && $target_block_id!=='') {
                        redirect('admin/options_participant_profile_block.php?item_name='.$target_item_name.'&block_id='.urlencode($target_block_id));
                    } else {
                        redirect('admin/'.thisdoc());
                    }
                } elseif ($form_action==='go_activate') {
                    redirect('admin/options_participant_profile_activate.php');
                } else {
                    message(lang('changes_saved'));
                    redirect('admin/'.thisdoc());
                }
            } else {
                message(lang('database_problem'),'error');
            }
        }
    }
}

if ($proceed) {
    if (isset($_REQUEST['delete_redundant']) && $_REQUEST['delete_redundant']) {
        $pars=array();
        foreach ($redundant as $r) {
            $pars[]=array(':mysql_column_name'=>$r);
        }
        $query="DELETE FROM ".table('profile_fields')." WHERE mysql_column_name = :mysql_column_name";
        $done=or_query($query,$pars);
        message(lang('redundant_configurations_deleted'));
        redirect('admin/'.thisdoc());
    }
}


if ($proceed) {
    if (count($redundant)>0) {
        $m=lang('pfields_redundant_configurations_message').'<BR><B>'.implode(", ",$redundant).'</B>';
        $m.='<BR><FORM action="'.thisdoc().'" method="POST">
                '.csrf__field().'
                <INPUT class="button orsee-btn" type="submit" name="delete_redundant" value="'.lang('yes').'">
                </FORM>
            </p>';
        show_message($m);
    }

    $system_field_labels=array(
        'email'=>lang('email'),
        'language'=>lang('language'),
        'subscriptions'=>lang('invitations')
    );
    $all_subpools=subpools__get_subpools();
    $scope_options=$profile_field_specs['fields']['scope_contexts']['control']['options'];

    // shared listtool columns for public/admin layout editors
    $layout_headers='<div class="orsee-listcell">'.lang('profile_editor_col_db_field').'</div><div class="orsee-listcell">'.lang('name').'</div><div class="orsee-listcell">'.lang('type').'</div><div class="orsee-listcell">'.lang('profile_editor_applies_to').'</div><div class="orsee-listcell"></div>';
    // build one listtool dataset for each scope layout (public/admin)
    $layout_listrows=array();
    foreach ($layout_list_defs as $group_name=>$layout_def) {
        $draft_layout=participant__load_profile_layout($layout_def['item_name'],'draft');
        $current_layout=participant__load_profile_layout($layout_def['item_name'],'current');
        if (!isset($draft_layout['blocks']) || !is_array($draft_layout['blocks'])) {
            $draft_layout['blocks']=array();
        }
        if (!isset($current_layout['blocks']) || !is_array($current_layout['blocks'])) {
            $current_layout['blocks']=array();
        }
        $layout_changed_keys=participant__profile_layout_changed_keys($draft_layout['blocks'],$current_layout['blocks']);

        // possible rows: field blocks from policy + repeatable text/section templates
        $poss_cols=array();
        foreach ($profile_fields_grouped[$group_name] as $field_name=>$group_item) {
            if (isset($system_fields[$field_name])) {
                $field_data=$system_fields[$field_name];
                $is_system=true;
            } else {
                $field_data=$user_columns[$field_name];
                $is_system=false;
            }
            $normalized_properties=participant__profile_field_properties_normalize($field_data['properties'],$profile_field_specs);
            $field_title=participant__field_localized_text($normalized_properties,'name_lang','name_lang');
            if ($field_title==='') {
                if ($is_system && isset($system_field_labels[$field_name])) {
                    $field_title=$system_field_labels[$field_name];
                } else {
                    $field_title=$field_name;
                }
            }
            $display_type=$field_data['type'];
            if ($display_type==='boolean') {
                $display_type='yes/no';
            }
            $field_changes=(isset($field_data['changes']) && is_array($field_data['changes']) ? $field_data['changes'] : array());
            $row_changed=(count($field_changes)>0 || isset($layout_changed_keys['field__'.$field_name]));
            $type_changed=(isset($field_changes['type']) && $field_changes['type']);
            $field_name_cell_class='orsee-listcell orsee-listcell-nowrap';
            if ($row_changed) {
                $field_name_cell_class.=' orsee-track-changed-left';
            }
            $display_type_html=htmlspecialchars($display_type,ENT_QUOTES);
            if ($type_changed) {
                $display_type_html='<span class="orsee-track-changed-underline">'.$display_type_html.'</span>';
            }
            $selected_subpool_labels=array();
            $selected_subpool_ids=array_map('intval',$normalized_properties['restrict_to_subpools']);
            if (count($selected_subpool_ids)>0) {
                foreach ($selected_subpool_ids as $selected_subpool_id) {
                    if (isset($all_subpools[$selected_subpool_id])) {
                        $selected_subpool_labels[]=$all_subpools[$selected_subpool_id]['subpool_name'];
                    }
                }
            }
            $all_subpools_selected=(count($selected_subpool_ids)===0 || count($selected_subpool_ids)===count($all_subpools));
            $applies_to_subpools=lang('subpools').': '.htmlspecialchars(implode(', ',$selected_subpool_labels),ENT_QUOTES);
            $applies_to_lines=array();
            if ($group_name==='public') {
                $selected_scope_keys=array();
                foreach ($public_scope_contexts as $scope_context) {
                    if (in_array($scope_context,$normalized_properties['scope_contexts'],true)) {
                        $selected_scope_keys[]=$scope_context;
                    }
                }
                $all_public_scopes_selected=(count($selected_scope_keys)===count($public_scope_contexts));
                $selected_scope_labels=array();
                foreach ($selected_scope_keys as $scope_context) {
                    if (isset($scope_options[$scope_context])) {
                        $selected_scope_labels[]=$scope_options[$scope_context];
                    }
                }
                $applies_to_scopes=lang('profile_editor_scopes').': '.htmlspecialchars(implode(', ',$selected_scope_labels),ENT_QUOTES);

                if ($all_subpools_selected && $all_public_scopes_selected) {
                    $applies_to_lines[]=lang('profile_editor_everywhere');
                } elseif ($all_subpools_selected) {
                    $applies_to_lines[]=$applies_to_scopes;
                } elseif ($all_public_scopes_selected) {
                    $applies_to_lines[]=$applies_to_subpools;
                } else {
                    $applies_to_lines[]=$applies_to_subpools;
                    $applies_to_lines[]=$applies_to_scopes;
                }
            } else {
                if ($all_subpools_selected) {
                    $applies_to_lines[]=lang('profile_editor_everywhere');
                } else {
                    $applies_to_lines[]=$applies_to_subpools;
                }
            }
            if (count($field_data['variants'])>0) {
                $variants_suffix=lang('profile_editor_plus_variants');
                if (isset($field_changes['variants'])) {
                    $variants_suffix='<span class="orsee-track-changed-underline">'.$variants_suffix.'</span>';
                }
                $applies_to_lines[]=$variants_suffix;
            }
            $applies_to_html='<span class="orsee-font-compact">'.implode('<br>',$applies_to_lines).'</span>';
            $edit_button='<button type="submit" name="go_edit" value="field__'.htmlspecialchars($field_name,ENT_QUOTES).'" class="button orsee-btn orsee-btn-compact"><i class="fa fa-pencil-square-o" style="padding: 0 0.3em 0 0"></i>'.lang('edit').'</button>';
            $field_name_display=$field_name;
            $field_title_display=$field_title;
            $system_row_style=($is_system ? 'background:var(--color-list-shade-subtitle); color:var(--color-body-text); font-weight:400;' : '');
            $poss_cols['field__'.$field_name]=array(
                'display_text'=>$field_title,
                'allow_remove'=>false,
                'row_style'=>$system_row_style,
                'cols'=>'<div class="'.$field_name_cell_class.'">'.$field_name_display.'</div><div class="orsee-listcell">'.$field_title_display.'</div><div class="orsee-listcell">'.$display_type_html.'</div><div class="orsee-listcell">'.$applies_to_html.'</div><div class="orsee-listcell">'.$edit_button.'</div>'
            );
        }

        // current draft layout defines which rows are already on-list and in which order
        $saved_cols=array();
        foreach ($draft_layout['blocks'] as $block_index=>$block) {
            if (!is_array($block) || !isset($block['type'])) {
                continue;
            }

            // field blocks are tracked by stable field key: field__<mysql_column_name>
            if ($block['type']==='field' && isset($block['field']) && isset($poss_cols['field__'.$block['field']])) {
                $saved_cols['field__'.$block['field']]=array('item_details'=>'');
            } elseif ($block['type']==='text' || $block['type']==='section') {
                // text/section instances are keyed by stable block_id
                $block_type_label=lang($block['type']==='text' ? 'profile_block_text' : 'profile_block_section');
                $block_short_name=(isset($block['short_name']) ? trim((string)$block['short_name']) : '');
                $block_name_label=($block_short_name!=='' ? $block_short_name : '-');
                $block_label=$block_type_label;
                if ($block_short_name!=='') {
                    $block_label.=': '.$block_short_name;
                }
                if (!isset($block['block_id']) || trim((string)$block['block_id'])==='') {
                    continue;
                }
                $block_key=$block['type'].'__'.trim((string)$block['block_id']);
                $block_type_cell_class='orsee-listcell orsee-listcell-nowrap';
                if (isset($layout_changed_keys[$block_key])) {
                    $block_type_cell_class.=' orsee-track-changed-left';
                }
                $block_selected_subpool_labels=array();
                $block_selected_subpool_ids=array();
                if (isset($block['restrict_to_subpools']) && is_array($block['restrict_to_subpools'])) {
                    $block_selected_subpool_ids=array_map('intval',$block['restrict_to_subpools']);
                }
                if (count($block_selected_subpool_ids)>0) {
                    foreach ($block_selected_subpool_ids as $block_selected_subpool_id) {
                        if (isset($all_subpools[$block_selected_subpool_id])) {
                            $block_selected_subpool_labels[]=$all_subpools[$block_selected_subpool_id]['subpool_name'];
                        }
                    }
                }
                $block_all_subpools_selected=(count($block_selected_subpool_ids)===0 || count($block_selected_subpool_ids)===count($all_subpools));
                $block_applies_to_subpools=lang('subpools').': '.htmlspecialchars(implode(', ',$block_selected_subpool_labels),ENT_QUOTES);
                $block_applies_to_lines=array();
                if ($group_name==='public') {
                    $block_selected_scope_keys=array();
                    if (isset($block['scope_contexts']) && is_array($block['scope_contexts'])) {
                        foreach ($public_scope_contexts as $scope_context) {
                            if (in_array($scope_context,$block['scope_contexts'],true)) {
                                $block_selected_scope_keys[]=$scope_context;
                            }
                        }
                    } else {
                        $block_selected_scope_keys=$public_scope_contexts;
                    }
                    $block_all_public_scopes_selected=(count($block_selected_scope_keys)===count($public_scope_contexts));
                    $block_selected_scope_labels=array();
                    foreach ($block_selected_scope_keys as $scope_context) {
                        if (isset($scope_options[$scope_context])) {
                            $block_selected_scope_labels[]=$scope_options[$scope_context];
                        }
                    }
                    $block_applies_to_scopes=lang('profile_editor_scopes').': '.htmlspecialchars(implode(', ',$block_selected_scope_labels),ENT_QUOTES);
                    if ($block_all_subpools_selected && $block_all_public_scopes_selected) {
                        $block_applies_to_lines[]=lang('profile_editor_everywhere');
                    } elseif ($block_all_subpools_selected) {
                        $block_applies_to_lines[]=$block_applies_to_scopes;
                    } elseif ($block_all_public_scopes_selected) {
                        $block_applies_to_lines[]=$block_applies_to_subpools;
                    } else {
                        $block_applies_to_lines[]=$block_applies_to_subpools;
                        $block_applies_to_lines[]=$block_applies_to_scopes;
                    }
                } else {
                    if ($block_all_subpools_selected) {
                        $block_applies_to_lines[]=lang('profile_editor_everywhere');
                    } else {
                        $block_applies_to_lines[]=$block_applies_to_subpools;
                    }
                }
                $block_applies_to_html='<span class="orsee-font-compact">'.implode('<br>',$block_applies_to_lines).'</span>';
                $edit_button='<button type="submit" name="go_edit" value="block__'.$group_name.'__'.htmlspecialchars($block_key,ENT_QUOTES).'" class="button orsee-btn orsee-btn-compact"><i class="fa fa-pencil-square-o" style="padding: 0 0.3em 0 0"></i>'.lang('edit').'</button>';
                $poss_cols[$block_key]=array(
                    'display_text'=>$block_label,
                    'cols'=>'<div class="'.$block_type_cell_class.'">-</div><div class="orsee-listcell">'.$block_name_label.'</div><div class="orsee-listcell">'.$block_type_label.'</div><div class="orsee-listcell">'.$block_applies_to_html.'</div><div class="orsee-listcell">'.$edit_button.'</div>'
                );
                $saved_cols[$block_key]=array('item_details'=>'');
            }
        }

        // add repeatable templates used by the listtool "Add" dropdown
        $poss_cols['text__template']=array(
            'display_text'=>lang('profile_block_text'),
            'repeatable'=>true,
            'cols'=>'<div class="orsee-listcell orsee-listcell-nowrap">-</div><div class="orsee-listcell">-</div><div class="orsee-listcell">'.lang('profile_block_text').'</div><div class="orsee-listcell">-</div><div class="orsee-listcell">-</div>'
        );
        $poss_cols['section__template']=array(
            'display_text'=>lang('profile_block_section'),
            'repeatable'=>true,
            'cols'=>'<div class="orsee-listcell orsee-listcell-nowrap">-</div><div class="orsee-listcell">-</div><div class="orsee-listcell">'.lang('profile_block_section').'</div><div class="orsee-listcell">-</div><div class="orsee-listcell">-</div>'
        );

        // merge possible rows with saved order for rendering/editing by listtool
        $layout_listrows[$group_name]=options__ordered_lists_get_current($poss_cols,$saved_cols);
    }

    echo '<form action="'.thisdoc().'" method="POST">';
    echo csrf__field();

    echo '<div class="orsee-panel">';
    echo '<div class="orsee-options-actions" style="display:flex; justify-content:space-between; align-items:center; margin-top:0;">';
    echo button_back('options_main.php');
    echo '<div style="display:flex; gap:0.5rem; align-items:center;">';
    echo button_link('options_participant_profile_add.php',lang('create_new_mysql_table_column'),'plus-circle');
    echo '<button type="submit" name="go_activate" value="y" class="button orsee-btn"><i class="fa fa-check-to-slot" style="padding: 0 0.3em 0 0"></i>'.lang('profile_editor_preview__and_activation').'</button>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    echo '<div class="orsee-panel">';
    echo '<div class="orsee-panel-title"><div class="orsee-panel-title-main">'.$layout_list_defs['public']['title'].'</div></div>';
    echo '<div style="width:90%; margin:0 auto;">';
    echo formhelpers__orderlist($layout_list_defs['public']['list_id'],$layout_list_defs['public']['form_name'],$layout_listrows['public'],false,lang('add'),$layout_headers);
    echo '</div>';
    echo '<div class="orsee-options-actions-center" style="margin-top:0.7rem;">';
    echo '<button type="submit" name="save_layout_order" value="public" class="button orsee-btn">'.lang('save').'</button>';
    echo '</div>';
    echo '</div>';

    echo '<div class="orsee-panel">';
    echo '<div class="orsee-panel-title"><div class="orsee-panel-title-main">'.$layout_list_defs['admin']['title'].'</div></div>';
    echo '<div style="width:90%; margin:0 auto;">';
    echo formhelpers__orderlist($layout_list_defs['admin']['list_id'],$layout_list_defs['admin']['form_name'],$layout_listrows['admin'],false,lang('add'),$layout_headers);
    echo '</div>';
    echo '<div class="orsee-options-actions-center" style="margin-top:0.7rem;">';
    echo '<button type="submit" name="save_layout_order" value="admin" class="button orsee-btn">'.lang('save').'</button>';
    echo '</div>';
    echo '</div>';
    echo '</form>';

    echo '<div class="orsee-panel">';
    echo '<div class="orsee-panel-title"><div>'.lang('profile_editor_fields_disabled_unplaced_new').'</div></div>';
    echo '<div style="width:90%; margin:0 auto;">';
    echo '<div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile" style="width:100%;">';
    echo '<div class="orsee-table-row orsee-table-head">';
    echo '<div class="orsee-table-cell">'.lang('profile_editor_col_db_field').'</div>';
    echo '<div class="orsee-table-cell">'.lang('name').'</div>';
    echo '<div class="orsee-table-cell">'.lang('type').'</div>';
    echo '<div class="orsee-table-cell">'.lang('profile_editor_col_reason').'</div>';
    echo '<div class="orsee-table-cell"></div>';
    echo '</div>';
    foreach ($profile_fields_grouped['disabled'] as $field_name=>$group_item) {
        if (isset($system_fields[$field_name])) {
            $field_data=$system_fields[$field_name];
            $is_system=true;
        } else {
            $field_data=$user_columns[$field_name];
            $is_system=false;
        }
        $is_configured=(isset($field_data['is_configured']) && $field_data['is_configured']);
        if ($is_configured) {
            $normalized_properties=participant__profile_field_properties_normalize($field_data['properties'],$profile_field_specs);
            $label=participant__field_localized_text($normalized_properties,'name_lang','name_lang');
            if ($label==='') {
                if ($is_system && isset($system_field_labels[$field_name])) {
                    $label=$system_field_labels[$field_name];
                } else {
                    $label=$field_name;
                }
            }
            $display_type=$field_data['type'];
            if ($display_type==='boolean') {
                $display_type='yes/no';
            }
        } else {
            if ($is_system && isset($system_field_labels[$field_name])) {
                $label=$system_field_labels[$field_name];
            } else {
                $label=$field_name;
            }
            $display_type=(isset($field_data['type']) && trim((string)$field_data['type'])!=='' ? $field_data['type'] : '-');
            if ($display_type==='boolean') {
                $display_type='yes/no';
            }
        }
        $field_changes=(isset($field_data['changes']) && is_array($field_data['changes']) ? $field_data['changes'] : array());
        $row_changed=($is_configured && count($field_changes)>0);
        $type_changed=($is_configured && isset($field_changes['type']) && $field_changes['type']);
        $display_type_html=htmlspecialchars($display_type,ENT_QUOTES);
        if ($type_changed) {
            $display_type_html='<span class="orsee-track-changed-underline">'.$display_type_html.'</span>';
        }
        if ($is_configured) {
            $action=button_link('options_participant_profile_edit.php?mysql_column_name='.$field_name,lang('edit'),'pencil-square-o');
        } else {
            $action=button_link('options_participant_profile_edit.php?mysql_column_name='.$field_name,lang('configure'),'cogs');
        }

        $reason=lang('profile_editor_reason_no_scope');
        if (isset($group_item['reason']) && $group_item['reason']==='disabled') {
            $reason=lang('profile_editor_reason_disabled');
        } elseif (isset($group_item['reason']) && $group_item['reason']==='new_unconfigured') {
            $reason=lang('profile_editor_reason_new_unconfigured');
        }

        $row_class='orsee-table-row';
        $field_cell_class='orsee-table-cell';
        if ($row_changed) {
            $field_cell_class.=' orsee-track-changed-left';
        }
        echo '<div class="'.$row_class.'">';
        echo '<div class="'.$field_cell_class.'" data-label="'.lang('profile_editor_col_db_field').'">'.$field_name.'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('name').'">'.$label.'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('type').'">'.$display_type_html.'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('profile_editor_col_reason').'">'.$reason.'</div>';
        echo '<div class="orsee-table-cell orsee-table-action" data-label="">'.$action.'</div>';
        echo '</div>';
    }
    echo '</div>';
    echo '</div>';
    echo '</div>';

    echo '<script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                var listRoots=[];
                var publicListRoot=document.getElementById("list_profile_layout_public_list");
                var adminListRoot=document.getElementById("list_profile_layout_admin_list");
                if (publicListRoot) listRoots.push(publicListRoot);
                if (adminListRoot) listRoots.push(adminListRoot);
                if (listRoots.length===0 || typeof window.listtool__bind_delete_confirm!=="function") return;
                window.listtool__bind_delete_confirm(
                    listRoots,
                    "'.lang('profile_editor_really_remove_layout_block').'",
                    function() {
                        if (window.list_profile_layout_public_list && typeof window.list_profile_layout_public_list.buildDropdown==="function") {
                            window.list_profile_layout_public_list.buildDropdown();
                        }
                        if (window.list_profile_layout_admin_list && typeof window.list_profile_layout_admin_list.buildDropdown==="function") {
                            window.list_profile_layout_admin_list.buildDropdown();
                        }
                    }
                );
            });
        </script>';
}
include("footer.php");

?>
