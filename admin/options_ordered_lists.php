<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="options";
$js_modules=array('listtool');
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['list'])) {
        $list=$_REQUEST['list'];
    } else {
        $list="";
    }
}

if ($proceed) {
    $result_lists=array('result_table_search_active','result_table_search_all',
                'result_table_assign','result_table_search_duplicates',
                'experiment_assigned_list','session_participants_list','session_participants_list_pdf',
                'email_participant_guesses_list','result_table_search_unconfirmed','anonymize_profile_list');
    if (!in_array($list,$result_lists)) {
        redirect('admin/options_main.php');
    }
}

if ($proceed) {
    if ($list=='result_table_search_active') {
        $header=lang('columns_in_search_results_table_for_active_participants');
        $cols=participant__get_possible_participant_columns($list);
        $allow_check='pform_results_lists_edit';
        $list_add_options=array('hide_for_admin_types'=>lang('hide_column_for_admin_types'),'sortby_radio'=>lang('sort_table_by'));
    } elseif ($list=='result_table_search_all') {
        $header=lang('columns_in_search_results_table_for_all_participants');
        $cols=participant__get_possible_participant_columns($list);
        $allow_check='pform_results_lists_edit';
        $list_add_options=array('hide_for_admin_types'=>lang('hide_column_for_admin_types'),'sortby_radio'=>lang('sort_table_by'));
    } elseif ($list=='result_table_assign') {
        $header=lang('columns_in_results_table_for_assign_query');
        $cols=participant__get_possible_participant_columns($list);
        $allow_check='pform_results_lists_edit';
        $list_add_options=array('hide_for_admin_types'=>lang('hide_column_for_admin_types'),'sortby_radio'=>lang('sort_table_by'));
    } elseif ($list=='result_table_search_duplicates') {
        $header=lang('columns_in_search_results_table_for_profile_duplicates');
        $cols=participant__get_possible_participant_columns($list);
        $allow_check='pform_results_lists_edit';
        $list_add_options=array('sortby_radio'=>lang('sort_table_by'));
    } elseif ($list=='result_table_search_unconfirmed') {
        $header=lang('columns_in_search_results_table_for_unconfirmed_profiles');
        $cols=participant__get_possible_participant_columns($list);
        $allow_check='pform_results_lists_edit';
        $list_add_options=array('hide_for_admin_types'=>lang('hide_column_for_admin_types'));
    } elseif ($list=='experiment_assigned_list') {
        $header=lang('columns_in_list_of_assigned_participants');
        $cols=participant__get_possible_participant_columns($list);
        $allow_check='pform_results_lists_edit';
        $list_add_options=array('hide_for_admin_types'=>lang('hide_column_for_admin_types'),'sortby_radio'=>lang('sort_table_by'));
    } elseif ($list=='session_participants_list') {
        $header=lang('columns_in_session_participants_list');
        $cols=participant__get_possible_participant_columns($list);
        $allow_check='pform_results_lists_edit';
        $list_add_options=array('hide_for_admin_types'=>lang('hide_column_for_admin_types'),'sortby_radio'=>lang('sort_table_by'),'editable_on_session_list'=>'Editable on session participants list');
    } elseif ($list=='session_participants_list_pdf') {
        $header=lang('columns_in_pdf_session_participants_list');
        $cols=participant__get_possible_participant_columns($list);
        $allow_check='pform_results_lists_edit';
        $list_add_options=array('hide_for_admin_types'=>lang('hide_column_for_admin_types'),'sortby_radio'=>lang('sort_table_by'));
    } elseif ($list=='email_participant_guesses_list') {
        $header=lang('email_module_participant_guesses_list');
        $cols=participant__get_possible_participant_columns($list);
        $allow_check='pform_results_lists_edit';
        $list_add_options=array('sortby_radio'=>lang('sort_table_by'));
    } elseif ($list=='anonymize_profile_list') {
        $header=lang('fields_to_anonymize_in_anonymization_bulk_action');
        $cols=participant__get_possible_participant_columns($list);
        $allow_check='pform_anonymization_fields_edit';
        $list_add_options=array('field_value'=>lang('anonymized_dummy_value'));
        $button_text=lang('save');
    }
    if (!isset($cols)) {
        redirect('admin/options_main.php');
    }
}

if ($proceed && $allow_check) {
    $allow=check_allow($allow_check,'options_main.php');
}

if ($proceed) {
    if (isset($_REQUEST['save_order']) && $_REQUEST['save_order']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        }
    }
}

if ($proceed) {
    if (isset($_REQUEST['save_order']) && $_REQUEST['save_order']) {
        if (isset($_REQUEST['item_order']) && is_array($_REQUEST['item_order']) && count($_REQUEST['item_order'])>0) {
            $details=array();
            if (isset($_REQUEST['sortby']) && $_REQUEST['sortby']) {
                $details[trim($_REQUEST['sortby'])]['default_sortby']=1;
            }
            if (isset($_REQUEST['field_values']) && $_REQUEST['field_values'] && is_array($_REQUEST['field_values'])) {
                foreach ($_REQUEST['field_values'] as $field=>$field_value) {
                    $details[$field]['field_value']=$field_value;
                }
            }
            if (isset($_REQUEST['hide_admin_types']) && $_REQUEST['hide_admin_types'] && is_array($_REQUEST['hide_admin_types'])) {
                $admin_types=admin__load_admin_types();
                foreach ($_REQUEST['hide_admin_types'] as $field=>$field_value) {
                    $type_array=explode(",",$field_value);
                    $ftype_array=array();
                    foreach ($type_array as $k=>$v) {
                        $v=trim($v);
                        if (isset($admin_types[$v])) {
                            $ftype_array[]=$v;
                        }
                    }
                    sort($ftype_array);
                    $field_value=implode(",",$ftype_array);
                    $details[$field]['hide_admin_types']=$field_value;
                }
            }
            if ($list=='session_participants_list') {
                foreach ($_REQUEST['item_order'] as $field) {
                    if (isset($_REQUEST['editable_on_session_list']) && is_array($_REQUEST['editable_on_session_list']) && isset($_REQUEST['editable_on_session_list'][$field]) && $_REQUEST['editable_on_session_list'][$field]=='y') {
                        $details[$field]['editable_on_session_list']='y';
                    } else {
                        $details[$field]['editable_on_session_list']='n';
                    }
                }
            }
            $done=options__save_item_order($list,$_REQUEST['item_order'],$details);
            message(lang('changes_saved'));
            redirect('admin/options_ordered_lists.php?list='.urlencode($list));
        }
    }
}

if ($proceed) {
    javascript__tooltip_prepare();

    $pars=array(':item_type'=>$list);
    $query="SELECT *
            FROM ".table('objects')."
            WHERE item_type= :item_type
            ORDER BY order_number";
    $result=or_query($query,$pars);

    $rows=array();
    while ($line=pdo_fetch_assoc($result)) {
        $rows[$line['item_name']]=$line;
    }

    if (isset($list_add_options) && is_array($list_add_options) && count($list_add_options)>0) {
        $listrows=options__ordered_lists_get_current($cols,$rows,$list_add_options);
        $headers='<div class="orsee-listcell"></div>';
        foreach ($list_add_options as $name=>$display_name) {
            if ($name=='hide_for_admin_types') {
                $admin_types=admin__load_admin_types();
                $admin_types_arr=array();
                foreach ($admin_types as $k=>$line) {
                    $admin_types_arr[]=$k;
                }
                $admin_types_list=implode(", ",$admin_types_arr);
                $tooltip_text=lang('enter_comma_separated_list_of_any_of').' '.$admin_types_list;
                $headers.='<div class="orsee-listcell orsee-listcell-center tooltip" title="'.htmlspecialchars($tooltip_text,ENT_QUOTES).'">'.$display_name.'</div>';
            } else {
                $headers.='<div class="orsee-listcell orsee-listcell-center">'.$display_name.'</div>';
            }
        }
    } else {
        $listrows=options__ordered_lists_get_current($cols,$rows);
        $headers='';
    }

    echo '<div class="orsee-options-list-panel">';
    show_message();
    echo '<div class="orsee-panel">';
    echo '<form action="" method="POST">';
    echo csrf__field();
    echo '<div class="orsee-panel-title"><div class="orsee-panel-title-main">'.$header.'</div></div>';
    echo formhelpers__orderlist("ordered_list", "item_order", $listrows, false, lang('add'),$headers);
    echo '<div class="orsee-options-actions-center orsee-options-actions">';
    if (isset($button_text) && $button_text) {
        echo '<input class="button orsee-btn" name="save_order" type="submit" value="'.$button_text.'">';
    } else {
        echo '<input class="button orsee-btn" name="save_order" type="submit" value="'.lang('save_order').'">';
    }
    echo '</div>';
    echo '</form>';
    echo '<div class="orsee-options-actions">'.button_back('options_main.php').'</div>';
    echo '</div>';
    echo '</div>';
}
include("footer.php");

?>
