<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="participant_profile_fields";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['item_name'])) {
        $item_name=$_REQUEST['item_name'];
    } else {
        redirect("admin/options_main.php");
    }
}
if ($proceed) {
    if (!in_array($item_name,array('profile_form_public','profile_form_admin_part'))) {
        redirect("admin/options_main.php");
    }
}
if ($proceed) {
    $allow=check_allow('pform_templates_edit','options_main.php');
}
if ($proceed) {
    if (!isset($_REQUEST['block_id'])) {
        redirect("admin/options_participant_profile.php");
    }
    $block_id=trim((string)$_REQUEST['block_id']);
    if ($block_id==='') {
        redirect("admin/options_participant_profile.php");
    }
}

if ($proceed) {
    // This page edits composition blocks only (text/section).
    $languages=get_languages();
    $lang_dirs=lang__is_rtl_all_langs();
    $subpools=subpools__get_subpools();
    $subpool_options=array();
    foreach ($subpools as $subpool_id=>$subpool) {
        $subpool_options[(string)$subpool_id]=$subpool['subpool_name'];
    }
    $profile_field_specs=participant__profile_field_editor_specs();
    $public_scope_options=$profile_field_specs['fields']['scope_contexts']['control']['options'];
    unset($public_scope_options['profile_form_admin_part']);
    $scope_tooltip=trim((string)$profile_field_specs['fields']['scope_contexts']['tooltip']);
    $restrict_subpools_tooltip=trim((string)$profile_field_specs['fields']['restrict_to_subpools']['tooltip']);
    $draft_layout=participant__load_profile_layout($item_name,'draft');
    if (!isset($draft_layout['blocks']) || !is_array($draft_layout['blocks'])) {
        $draft_layout['blocks']=array();
    }
    $block_index=-1;
    foreach ($draft_layout['blocks'] as $idx=>$draft_block) {
        if (!is_array($draft_block) || !isset($draft_block['type']) || !in_array($draft_block['type'],array('text','section'))) {
            continue;
        }
        if (!isset($draft_block['block_id']) || trim((string)$draft_block['block_id'])==='') {
            continue;
        }
        $current_block_id=trim((string)$draft_block['block_id']);
        if ($current_block_id===$block_id) {
            $block_index=(int)$idx;
            break;
        }
    }
    if ($block_index<0) {
        redirect("admin/options_participant_profile.php");
    }
    $block=$draft_layout['blocks'][$block_index];
    if (!isset($block['type']) || !in_array($block['type'],array('text','section'))) {
        redirect("admin/options_participant_profile.php");
    }

    $selected_public_scopes=array();
    if ($item_name==='profile_form_public') {
        if (array_key_exists('scope_contexts',$block) && is_array($block['scope_contexts'])) {
            $selected_public_scopes=array_intersect(array_keys($public_scope_options),$block['scope_contexts']);
        } else {
            $selected_public_scopes=array_keys($public_scope_options);
        }
    }

    $selected_subpools=array();
    if (array_key_exists('restrict_to_subpools',$block) && is_array($block['restrict_to_subpools'])) {
        $selected_subpools=array_intersect(array_map('intval',array_keys($subpools)),array_map('intval',$block['restrict_to_subpools']));
    }
    $selected_subpools=array_map('strval',$selected_subpools);
}

if ($proceed) {
    if (isset($_REQUEST['save']) && $_REQUEST['save']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        } else {
            $block['short_name']=trim((string)$_REQUEST['short_name']);
            $block['text_lang']=array();
            foreach ($languages as $language) {
                $block['text_lang'][$language]=(isset($_REQUEST['text_lang'][$language]) ? trim((string)$_REQUEST['text_lang'][$language]) : '');
            }
            if ($item_name==='profile_form_public') {
                $scope_contexts=array();
                if (isset($_REQUEST['scope_contexts']) && is_array($_REQUEST['scope_contexts'])) {
                    foreach ($_REQUEST['scope_contexts'] as $scope_context) {
                        $scope_context=trim((string)$scope_context);
                        if ($scope_context!=='' && isset($public_scope_options[$scope_context]) && !in_array($scope_context,$scope_contexts,true)) {
                            $scope_contexts[]=$scope_context;
                        }
                    }
                }
                $block['scope_contexts']=$scope_contexts;
            } else {
                $block['scope_contexts']=array('profile_form_admin_part');
            }

            $restrict_to_subpools=array();
            if (isset($_REQUEST['restrict_to_subpools']) && is_array($_REQUEST['restrict_to_subpools'])) {
                foreach ($_REQUEST['restrict_to_subpools'] as $subpool_id) {
                    $subpool_id=(int)$subpool_id;
                    if ($subpool_id>0 && isset($subpools[$subpool_id]) && !in_array($subpool_id,$restrict_to_subpools,true)) {
                        $restrict_to_subpools[]=$subpool_id;
                    }
                }
            }
            $block['restrict_to_subpools']=$restrict_to_subpools;

            unset($block['text']);
            $draft_layout['blocks'][$block_index]=$block;
            $done=participant__save_profile_layout($item_name,'draft',$draft_layout);
            if ($done) {
                message(lang('changes_saved'));
                redirect("admin/options_participant_profile_block.php?item_name=".$item_name."&block_id=".urlencode($block_id));
            } else {
                message(lang('database_problem'),'error');
            }
        }
    }
}

if ($proceed) {
    javascript__tooltip_prepare();
    $layout_name='profile_form_admin';
    if ($item_name==='profile_form_public') {
        $layout_name='profile_form_public';
    }
    $block_type_label=($block['type']==='section' ? lang('profile_block_section') : lang('profile_block_text'));
    $block_header=$block_type_label;
    if (isset($block['short_name']) && trim((string)$block['short_name'])!=='') {
        $block_header.=': '.trim((string)$block['short_name']);
    }
    echo '<div class="orsee-panel">';
    echo '<div class="orsee-panel-title"><div>'.lang('edit_layout').': '.$layout_name.', '.$block_header.'</div></div>';
    echo '<form action="options_participant_profile_block.php" method="POST">';
    echo '<input type="hidden" name="item_name" value="'.$item_name.'">';
    echo '<input type="hidden" name="block_id" value="'.htmlspecialchars($block_id,ENT_QUOTES).'">';
    echo csrf__field();
    echo '<div class="orsee-form-shell">';

    echo '<div class="field"><label class="label">'.lang('type').'</label><div class="control">'.$block_type_label.'</div></div>';
    $short_name=(isset($block['short_name']) ? trim((string)$block['short_name']) : '');
    echo '<div class="field"><label class="label">'.lang('layout_short_name').'</label><div class="control"><input class="input is-primary orsee-input orsee-input-text" type="text" name="short_name" value="'.htmlspecialchars($short_name,ENT_QUOTES).'"></div></div>';
    if ($item_name==='profile_form_public') {
        echo '<div class="field tooltip" title="'.htmlspecialchars($scope_tooltip,ENT_QUOTES).'">';
        echo '<label class="label">'.lang('profile_editor_scopes').'</label>';
        echo '<div class="control">'.pform_options_checkboxrow('scope_contexts',$public_scope_options,$selected_public_scopes).'</div>';
        echo '</div>';
    }
    echo '<div class="field tooltip" title="'.htmlspecialchars($restrict_subpools_tooltip,ENT_QUOTES).'">';
    echo '<label class="label">'.lang('profile_editor_restrict_to_subpools').'</label>';
    echo '<div class="control">'.pform_options_checkboxrow('restrict_to_subpools',$subpool_options,$selected_subpools).'</div>';
    echo '</div>';

    $text_values=array();
    foreach ($languages as $language) {
        $text_lang='';
        if (isset($block['text_lang']) && is_array($block['text_lang']) && isset($block['text_lang'][$language])) {
            $text_lang=(string)$block['text_lang'][$language];
        } elseif (isset($block['text']) && trim((string)$block['text'])!=='') {
            $text_lang=load_language_symbol(trim((string)$block['text']),$language);
        }
        $text_values[$language]=$text_lang;
    }
    if ($block['type']==='section') {
        echo '<div class="field"><label class="label">'.lang('layout_section_title').'</label></div>';
    } else {
        echo '<div class="field"><label class="label">'.lang('layout_text').'</label></div>';
    }
    foreach ($languages as $language) {
        $field_dir=(isset($lang_dirs[$language]) && $lang_dirs[$language] ? 'rtl' : 'ltr');
        if ($block['type']==='text') {
            echo '<div class="field is-flex is-align-items-center"><label class="label mb-0 mr-2">'.$language.':</label><div class="control is-flex-grow-1"><textarea class="textarea is-primary orsee-textarea" dir="'.$field_dir.'" name="text_lang['.$language.']" rows="3" wrap="virtual">'.htmlspecialchars($text_values[$language],ENT_QUOTES).'</textarea></div></div>';
        } else {
            echo '<div class="field is-flex is-align-items-center"><label class="label mb-0 mr-2">'.$language.':</label><div class="control is-flex-grow-1"><input class="input is-primary orsee-input orsee-input-text" dir="'.$field_dir.'" type="text" name="text_lang['.$language.']" value="'.htmlspecialchars($text_values[$language],ENT_QUOTES).'"></div></div>';
        }
    }

    echo '</div>';
    echo '<div class="orsee-options-actions-center orsee-options-actions"><input class="button orsee-btn" name="save" type="submit" value="'.lang('save').'"></div>';
    echo '</form>';
    echo '<div class="orsee-options-actions">'.button_back("options_participant_profile.php").'</div>';
    echo '</div>';
}

include("footer.php");

?>
