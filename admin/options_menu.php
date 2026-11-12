<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="options";
$js_modules=array('listtool');
include("header.php");

if ($proceed) {
    $area='public';
    if (isset($_REQUEST['area']) && in_array($_REQUEST['area'],array('public','admin'),true)) {
        $area=$_REQUEST['area'];
    }
    if ($area==='public') {
        $allow=check_allow('public_content_edit','options_main.php');
    } else {
        $allow=check_allow('settings_view','options_main.php');
    }
}

if (!function_exists('menu__new_item_id')) {
    function menu__new_item_id($prefix,$existing_ids=array()) {
        $prefix=preg_replace('/[^a-zA-Z0-9_]/','',strtolower((string)$prefix));
        if ($prefix==='') {
            $prefix='item';
        }
        do {
            $candidate=$prefix.'_'.substr(create_random_token($prefix.microtime(true)),0,8);
        } while (in_array($candidate,$existing_ids,true));
        return $candidate;
    }
}

if (!function_exists('menu__new_content_name')) {
    function menu__new_content_name($existing_content_names=array()) {
        do {
            $content_name=html__menu_normalize_content_name('page_'.substr(create_random_token('menu_content_name'.microtime(true)),0,8));
        } while (in_array($content_name,$existing_content_names,true));
        return $content_name;
    }
}

if (!function_exists('menu__default_lang_array')) {
    function menu__default_lang_array($default_text='') {
        $out=array();
        $languages=get_languages();
        foreach ($languages as $language) {
            $out[$language]=$default_text;
        }
        return $out;
    }
}

if ($proceed) {
    $config=html__menu_load_config($area);
    if (!isset($config['items']) || !is_array($config['items'])) {
        $config['items']=array();
    }

    $by_id=array();
    $fixed_items=array();
    foreach ($config['items'] as $item) {
        if (!is_array($item) || !isset($item['id'])) {
            continue;
        }
        $id=(string)$item['id'];
        $by_id[$id]=$item;
        if (isset($item['fixed']) && $item['fixed']==='y') {
            $fixed_items[$id]=$item;
        }
    }

    if (isset($_REQUEST['save_order']) && $_REQUEST['save_order']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        } else {
            $order=(isset($_REQUEST['item_order']) && is_array($_REQUEST['item_order']) ? $_REQUEST['item_order'] : array());
            $new_items=array();
            $seen_ids=array();
            $existing_ids=array_keys($by_id);
            $existing_content_names=array();
            foreach ($config['items'] as $item) {
                if (!is_array($item) || !isset($item['content_name'])) {
                    continue;
                }
                $content_name=trim((string)$item['content_name']);
                if ($content_name!=='') {
                    $existing_content_names[]=$content_name;
                }
            }

            foreach ($order as $raw_id) {
                $raw_id=(string)$raw_id;
                if (isset($by_id[$raw_id])) {
                    $new_items[]=$by_id[$raw_id];
                    $seen_ids[$raw_id]=true;
                    continue;
                }
                if ($raw_id==='__new_richtext_page__' || strpos($raw_id,'__new_richtext_page__')===0) {
                    $new_id=menu__new_item_id(($area==='public' ? 'ppage' : 'apage'),$existing_ids);
                    $existing_ids[]=$new_id;
                    $content_name=menu__new_content_name($existing_content_names);
                    $existing_content_names[]=$content_name;
                    $title_lang=menu__default_lang_array(lang('menu_new_richtext_page'));
                    $new_items[]=array(
                        'id'=>$new_id,
                        'menu_area'=>$new_id,
                        'entrytype'=>'link',
                        'link'=>'',
                        'icon'=>'',
                        'show_if_not_logged_in'=>1,
                        'show_if_logged_in'=>1,
                        'hidden'=>'n',
                        'fixed'=>'n',
                        'removable'=>'y',
                        'can_be_secondary'=>'y',
                        'menu_term_lang'=>$title_lang,
                        'page_title_lang'=>$title_lang,
                        'richtext'=>'y',
                        'content_name'=>$content_name,
                        'admin_type_hide'=>array(),
                        'custom_external'=>'n'
                    );
                    continue;
                }
                if ($raw_id==='__new_head__' || strpos($raw_id,'__new_head__')===0) {
                    $new_id=menu__new_item_id('head',$existing_ids);
                    $existing_ids[]=$new_id;
                    $title_lang=menu__default_lang_array(lang('menu_new_head_item'));
                    $new_items[]=array(
                        'id'=>$new_id,
                        'menu_area'=>$new_id,
                        'entrytype'=>'head',
                        'link'=>'',
                        'icon'=>'',
                        'show_if_not_logged_in'=>1,
                        'show_if_logged_in'=>1,
                        'hidden'=>'n',
                        'fixed'=>'n',
                        'removable'=>'y',
                        'can_be_secondary'=>'n',
                        'menu_term_lang'=>$title_lang,
                        'page_title_lang'=>$title_lang,
                        'richtext'=>'n',
                        'content_name'=>'',
                        'admin_type_hide'=>array(),
                        'custom_external'=>'n'
                    );
                    continue;
                }
                if ($raw_id==='__new_spacer__' || strpos($raw_id,'__new_spacer__')===0) {
                    $new_id=menu__new_item_id('space',$existing_ids);
                    $existing_ids[]=$new_id;
                    $new_items[]=array(
                        'id'=>$new_id,
                        'menu_area'=>$new_id,
                        'entrytype'=>'space',
                        'link'=>'',
                        'icon'=>'',
                        'show_if_not_logged_in'=>1,
                        'show_if_logged_in'=>1,
                        'hidden'=>'n',
                        'fixed'=>'n',
                        'removable'=>'y',
                        'can_be_secondary'=>'n',
                        'menu_term_lang'=>array(),
                        'page_title_lang'=>array(),
                        'richtext'=>'n',
                        'content_name'=>'',
                        'admin_type_hide'=>array(),
                        'custom_external'=>'n'
                    );
                    continue;
                }
                if ($raw_id==='__new_external_link__' || strpos($raw_id,'__new_external_link__')===0) {
                    $new_id=menu__new_item_id(($area==='public' ? 'pext' : 'aext'),$existing_ids);
                    $existing_ids[]=$new_id;
                    $title_lang=menu__default_lang_array(lang('menu_new_external_link'));
                    $new_items[]=array(
                        'id'=>$new_id,
                        'menu_area'=>$new_id,
                        'entrytype'=>'link',
                        'link'=>'',
                        'icon'=>'',
                        'show_if_not_logged_in'=>1,
                        'show_if_logged_in'=>1,
                        'hidden'=>'n',
                        'fixed'=>'n',
                        'removable'=>'y',
                        'can_be_secondary'=>'y',
                        'menu_term_lang'=>$title_lang,
                        'page_title_lang'=>array(),
                        'richtext'=>'n',
                        'content_name'=>'',
                        'admin_type_hide'=>array(),
                        'custom_external'=>'y'
                    );
                    continue;
                }
            }

            foreach ($fixed_items as $fixed_id=>$fixed_item) {
                if (!isset($seen_ids[$fixed_id])) {
                    $new_items[]=$fixed_item;
                }
            }

            $new_ids=array();
            foreach ($new_items as $new_item_line) {
                if (!is_array($new_item_line) || !isset($new_item_line['id'])) {
                    continue;
                }
                $new_ids[(string)$new_item_line['id']]=true;
            }
            $removed_content_names=array();
            foreach ($by_id as $old_id=>$old_item) {
                if (isset($new_ids[$old_id])) {
                    continue;
                }
                if (!is_array($old_item)) {
                    continue;
                }
                if (!(isset($old_item['richtext']) && $old_item['richtext']==='y')) {
                    continue;
                }
                if (!isset($old_item['content_name']) || trim((string)$old_item['content_name'])==='') {
                    continue;
                }
                $removed_content_names[trim((string)$old_item['content_name'])]=true;
            }

            $config['items']=$new_items;
            $done=html__menu_save_config($area,$config);
            if ($done) {
                if (count($removed_content_names)>0) {
                    $remaining_refs=array();
                    foreach ($new_items as $ref_item) {
                        if (!is_array($ref_item) || !isset($ref_item['content_name'])) {
                            continue;
                        }
                        $ref_content_name=trim((string)$ref_item['content_name']);
                        if ($ref_content_name!=='') {
                            $remaining_refs[$ref_content_name]=true;
                        }
                    }
                    $other_area=($area==='public' ? 'admin' : 'public');
                    $other_config=html__menu_load_config($other_area);
                    if (is_array($other_config) && isset($other_config['items']) && is_array($other_config['items'])) {
                        foreach ($other_config['items'] as $ref_item) {
                            if (!is_array($ref_item) || !isset($ref_item['content_name'])) {
                                continue;
                            }
                            $ref_content_name=trim((string)$ref_item['content_name']);
                            if ($ref_content_name!=='') {
                                $remaining_refs[$ref_content_name]=true;
                            }
                        }
                    }
                    foreach (array_keys($removed_content_names) as $removed_content_name) {
                        if (isset($remaining_refs[$removed_content_name])) {
                            continue;
                        }
                        $query="DELETE FROM ".table('lang')."
                                WHERE content_type='public_content'
                                AND content_name=:content_name";
                        or_query($query,array(':content_name'=>$removed_content_name));
                    }
                }
                message(lang('changes_saved'));
                log__admin('menu_edit_order','area:'.$area);
            } else {
                message(lang('database_problem'),'error');
            }
            redirect('admin/options_menu.php?area='.$area);
        }
    }

    $config=html__menu_load_config($area);
    if (!isset($config['items']) || !is_array($config['items'])) {
        $config['items']=array();
    }

    $poss_cols=array();
    $saved_cols=array();
    foreach ($config['items'] as $item) {
        if (!is_array($item) || !isset($item['id'])) {
            continue;
        }
        $id=(string)$item['id'];
        $entrytype=(isset($item['entrytype']) ? (string)$item['entrytype'] : 'link');
        $type_label=$entrytype;
        if ($entrytype==='headlink') {
            $type_label=lang('menu_type_primary_link');
        } elseif ($entrytype==='head') {
            $type_label=lang('menu_type_head');
        } elseif ($entrytype==='link') {
            $type_label=lang('menu_type_submenu');
        } elseif ($entrytype==='space') {
            $type_label=lang('menu_type_spacer');
        }
        if (isset($item['custom_external']) && $item['custom_external']==='y') {
            $type_label=lang('menu_type_external_link');
        }
        $name=html__menu_text_from_lang_map((isset($item['menu_term_lang']) ? $item['menu_term_lang'] : array()),$id);
        if ($entrytype==='space') {
            $name='---';
        }
        $edit_button='<button type="submit" name="go_edit" value="'.htmlspecialchars($id).'" class="button orsee-btn orsee-btn-compact"><i class="fa fa-pencil-square-o" style="padding: 0 0.3em 0 0"></i>'.lang('edit').'</button>';
        $hidden=(isset($item['hidden']) && $item['hidden']==='y' ? lang('y') : lang('n'));
        $show_loginout='-';
        if ($area==='public') {
            if ((isset($item['show_if_logged_in']) && (int)$item['show_if_logged_in']) && (isset($item['show_if_not_logged_in']) && (int)$item['show_if_not_logged_in'])) {
                $show_loginout=lang('menu_visibility_always');
            } elseif (isset($item['show_if_logged_in']) && (int)$item['show_if_logged_in']) {
                $show_loginout=lang('menu_visibility_logged_in');
            } elseif (isset($item['show_if_not_logged_in']) && (int)$item['show_if_not_logged_in']) {
                $show_loginout=lang('menu_visibility_logged_out');
            } else {
                $show_loginout=lang('menu_visibility_never');
            }
        }
        $row_cols='<div class="orsee-listcell orsee-listcell-nowrap">'.$type_label.'</div><div class="orsee-listcell">'.$name.'</div>';
        if ($area==='public') {
            $row_cols.='<div class="orsee-listcell orsee-listcell-center">'.$show_loginout.'</div>';
        }
        $row_cols.='<div class="orsee-listcell orsee-listcell-center">'.$hidden.'</div><div class="orsee-listcell">'.$edit_button.'</div>';
        $poss_cols[$id]=array(
            'display_text'=>$name,
            'cols'=>$row_cols,
            'allow_remove'=>(!(isset($item['removable']) && $item['removable']==='n'))
        );
        $saved_cols[$id]=array('item_details'=>'');
    }

    $new_row_loginout=($area==='public' ? '<div class="orsee-listcell orsee-listcell-center">-</div>' : '');
    $poss_cols['__new_richtext_page__']=array(
        'display_text'=>lang('menu_new_richtext_page'),
        'repeatable'=>true,
        'allow_remove'=>true,
        'cols'=>'<div class="orsee-listcell orsee-listcell-nowrap">'.lang('menu_type_submenu').'</div><div class="orsee-listcell">'.lang('menu_new_richtext_page').'</div>'.$new_row_loginout.'<div class="orsee-listcell orsee-listcell-center">-</div><div class="orsee-listcell"></div>'
    );
    $poss_cols['__new_head__']=array(
        'display_text'=>lang('menu_new_head_item'),
        'repeatable'=>true,
        'allow_remove'=>true,
        'cols'=>'<div class="orsee-listcell orsee-listcell-nowrap">'.lang('menu_type_head').'</div><div class="orsee-listcell">'.lang('menu_new_head_item').'</div>'.$new_row_loginout.'<div class="orsee-listcell orsee-listcell-center">-</div><div class="orsee-listcell"></div>'
    );
    $poss_cols['__new_spacer__']=array(
        'display_text'=>lang('menu_new_spacer'),
        'repeatable'=>true,
        'allow_remove'=>true,
        'cols'=>'<div class="orsee-listcell orsee-listcell-nowrap">'.lang('menu_type_spacer').'</div><div class="orsee-listcell">---</div>'.$new_row_loginout.'<div class="orsee-listcell orsee-listcell-center">-</div><div class="orsee-listcell"></div>'
    );
    $poss_cols['__new_external_link__']=array(
        'display_text'=>lang('menu_new_external_link'),
        'repeatable'=>true,
        'allow_remove'=>true,
        'cols'=>'<div class="orsee-listcell orsee-listcell-nowrap">'.lang('menu_type_external_link').'</div><div class="orsee-listcell">'.lang('menu_new_external_link').'</div>'.$new_row_loginout.'<div class="orsee-listcell orsee-listcell-center">-</div><div class="orsee-listcell"></div>'
    );

    $rows=options__ordered_lists_get_current($poss_cols,$saved_cols);

    if (isset($_REQUEST['go_edit']) && $_REQUEST['go_edit']) {
        $edit_id=(string)$_REQUEST['go_edit'];
        if (isset($poss_cols[$edit_id])) {
            redirect('admin/options_menu_item_edit.php?area='.$area.'&item_id='.urlencode($edit_id));
        }
    }

    echo '<div class="orsee-panel">';
    echo '<div class="orsee-panel-title"><div>'.lang('menu_editor').': '.($area==='public' ? lang('menu_area_public') : lang('menu_area_admin')).'</div></div>';
    echo '<div class="orsee-panel-content">';

    echo '<form action="options_menu.php" method="POST">'.csrf__field();
    echo '<input type="hidden" name="area" value="'.htmlspecialchars($area).'">';
    $headers='<div class="orsee-listcell orsee-listcell-nowrap">'.lang('menu_type').'</div><div class="orsee-listcell">'.lang('menu_menu_term').'</div>';
    if ($area==='public') {
        $headers.='<div class="orsee-listcell orsee-listcell-center">'.lang('menu_show_when_logged_in_out').'</div>';
    }
    $headers.='<div class="orsee-listcell orsee-listcell-center">'.lang('menu_hidden').'</div><div class="orsee-listcell"></div>';
    if ($area==='public') {
        $downtime_lang_id=lang__get_lang_id('public_content','error_temporary_disabled');
        if ($downtime_lang_id) {
            echo '<div class="orsee-options-actions-end">';
            echo button_link('lang_item_edit.php?item=public_content&id='.(int)$downtime_lang_id.'&return_to='.urlencode('options_menu.php?area=public'),'Edit downtime page','pencil-square-o');
            echo '</div>';
        }
    }
    echo formhelpers__orderlist('menu_editor_list','item_order',$rows,false,lang('add'),$headers);
    echo '<div style="clear:both;"></div>';
    echo '<div class="orsee-options-actions-center orsee-options-actions" style="margin-top:0.7rem;">';
    echo '<button type="submit" name="save_order" value="1" class="button orsee-btn">'.lang('save').'</button>';
    echo '</div>';
    echo '<div class="orsee-options-actions">';
    echo button_back('options_main.php');
    echo '</div>';
    echo '</form>';

    echo '<script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                var listRoot=document.getElementById("list_menu_editor_list");
                if (!listRoot || typeof window.listtool__bind_delete_confirm!=="function") return;
                window.listtool__bind_delete_confirm(
                    [listRoot],
                    "'.htmlspecialchars(lang('do_you_really_want_to_delete'),ENT_QUOTES).'",
                    function() {
                        if (window.list_menu_editor_list && typeof window.list_menu_editor_list.buildDropdown==="function") {
                            window.list_menu_editor_list.buildDropdown();
                        }
                    }
                );
            });
        </script>';

    echo '</div>';
    echo '</div>';
}

include("footer.php");

?>
