<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="options";
$js_modules=array('listtool','faiconselector');
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

if ($proceed) {
    if (!isset($_REQUEST['item_id']) || trim((string)$_REQUEST['item_id'])==='') {
        redirect('admin/options_menu.php?area='.$area);
    }
    $item_id=trim((string)$_REQUEST['item_id']);
    $config=html__menu_load_config($area);
    if (!isset($config['items']) || !is_array($config['items'])) {
        redirect('admin/options_menu.php?area='.$area);
    }
    $item_index=-1;
    $item=array();
    foreach ($config['items'] as $k=>$line) {
        if (!is_array($line) || !isset($line['id'])) {
            continue;
        }
        if ((string)$line['id']===$item_id) {
            $item_index=$k;
            $item=$line;
            break;
        }
    }
    if ($item_index<0) {
        redirect('admin/options_menu.php?area='.$area);
    }
}

$menu_item_edit_allowed_defaults=array(
    'entrytype'=>true,
    'hidden'=>true,
    'show_if_not_logged_in'=>true,
    'show_if_logged_in'=>true,
    'icon'=>true,
    'external_url'=>true,
    'content_name'=>true,
    'menu_term_lang'=>true,
    'page_title_lang'=>true,
    'admin_type_hide'=>true
);
$menu_item_edit_fixed_public_visibility=array(
    'public_register'=>array('show_if_not_logged_in'=>1,'show_if_logged_in'=>0),
    'login'=>array('show_if_not_logged_in'=>1,'show_if_logged_in'=>0),
    'my_data'=>array('show_if_not_logged_in'=>0,'show_if_logged_in'=>1),
    'my_registrations'=>array('show_if_not_logged_in'=>0,'show_if_logged_in'=>1),
    'logout'=>array('show_if_not_logged_in'=>0,'show_if_logged_in'=>1)
);
$menu_item_edit_keep_post_values=false;

if ($proceed && isset($_REQUEST['save']) && $_REQUEST['save']) {
    if (!csrf__validate_request_message()) {
        $proceed=false;
    } else {
        $languages=get_languages();
        if (!isset($item['menu_term_lang']) || !is_array($item['menu_term_lang'])) {
            $item['menu_term_lang']=array();
        }
        if (!isset($item['page_title_lang']) || !is_array($item['page_title_lang'])) {
            $item['page_title_lang']=array();
        }
        foreach ($languages as $language) {
            $item['menu_term_lang'][$language]=(isset($_REQUEST['menu_term_lang'][$language]) ? trim((string)$_REQUEST['menu_term_lang'][$language]) : '');
            $item['page_title_lang'][$language]=(isset($_REQUEST['page_title_lang'][$language]) ? trim((string)$_REQUEST['page_title_lang'][$language]) : '');
        }

        $is_public_mainpage=($area==='public' && isset($item['id']) && $item['id']==='mainpage_welcome');
        $is_admin_mainpage=($area==='admin' && isset($item['id']) && $item['id']==='admin_mainpage');
        $is_mainpage=($is_public_mainpage || $is_admin_mainpage);
        $menu_area=(isset($item['menu_area']) ? (string)$item['menu_area'] : '');
        $allowed_to_change=$menu_item_edit_allowed_defaults;
        if ($is_public_mainpage) {
            $allowed_to_change['entrytype']=false;
            $allowed_to_change['hidden']=false;
            $allowed_to_change['show_if_not_logged_in']=false;
            $allowed_to_change['show_if_logged_in']=false;
        }
        if ($is_mainpage) {
            $allowed_to_change['content_name']=false;
        }
        if ($is_admin_mainpage) {
            $allowed_to_change['admin_type_hide']=false;
            $item['admin_type_hide']=array();
        }
        if (isset($item['entrytype']) && $item['entrytype']==='space') {
            $allowed_to_change['entrytype']=false;
            $allowed_to_change['content_name']=false;
            $allowed_to_change['icon']=false;
            $allowed_to_change['external_url']=false;
            $allowed_to_change['menu_term_lang']=false;
            $allowed_to_change['page_title_lang']=false;
        }
        if (!(isset($item['custom_external']) && $item['custom_external']==='y')) {
            $allowed_to_change['external_url']=false;
        }
        if (isset($item['custom_external']) && $item['custom_external']==='y') {
            $allowed_to_change['content_name']=false;
            $allowed_to_change['page_title_lang']=false;
            $allowed_to_change['admin_type_hide']=false;
        }
        if (isset($item['entrytype']) && $item['entrytype']==='head') {
            $allowed_to_change['page_title_lang']=false;
        }
        if (isset($item['can_hide']) && $item['can_hide']==='n') {
            $allowed_to_change['hidden']=false;
        }
        if (isset($item['can_be_secondary']) && $item['can_be_secondary']==='n') {
            $allowed_to_change['entrytype']=false;
        }
        if ($area!=='public') {
            $allowed_to_change['show_if_not_logged_in']=false;
            $allowed_to_change['show_if_logged_in']=false;
        }
        if ($area==='public' && isset($menu_item_edit_fixed_public_visibility[$menu_area])) {
            $allowed_to_change['show_if_not_logged_in']=false;
            $allowed_to_change['show_if_logged_in']=false;
        }
        if (!(isset($item['richtext']) && $item['richtext']==='y')) {
            $allowed_to_change['content_name']=false;
            $allowed_to_change['page_title_lang']=false;
        }
        if ($area!=='admin' || !(isset($item['richtext']) && $item['richtext']==='y')) {
            $allowed_to_change['admin_type_hide']=false;
        }

        if ($allowed_to_change['entrytype']) {
            if (isset($item['entrytype']) && $item['entrytype']==='head') {
                $item['entrytype']='head';
            } else {
                if (isset($_REQUEST['entrytype']) && in_array($_REQUEST['entrytype'],array('headlink','link'),true)) {
                    $item['entrytype']=(string)$_REQUEST['entrytype'];
                }
                if (isset($item['can_be_secondary']) && $item['can_be_secondary']==='n') {
                    $item['entrytype']='headlink';
                }
            }
        }

        if ($allowed_to_change['hidden']) {
            if (isset($_REQUEST['hidden']) && $_REQUEST['hidden']==='y') {
                $item['hidden']='y';
            } else {
                $item['hidden']='n';
            }
        } else {
            $item['hidden']='n';
        }

        if ($allowed_to_change['show_if_not_logged_in'] && $allowed_to_change['show_if_logged_in']) {
            $show_for=(isset($_REQUEST['show_for_login_state']) ? (string)$_REQUEST['show_for_login_state'] : 'both');
            if ($show_for==='logged_in') {
                $item['show_if_logged_in']=1;
                $item['show_if_not_logged_in']=0;
            } elseif ($show_for==='logged_out') {
                $item['show_if_logged_in']=0;
                $item['show_if_not_logged_in']=1;
            } else {
                $item['show_if_logged_in']=1;
                $item['show_if_not_logged_in']=1;
            }
        }

        if ($allowed_to_change['icon'] && isset($_REQUEST['icon'])) {
            $icon_value=trim((string)$_REQUEST['icon']);
            if (substr($icon_value,0,3)==='fa-') {
                $icon_value=substr($icon_value,3);
            }
            $item['icon']=$icon_value;
        } elseif (isset($item['entrytype']) && $item['entrytype']==='space') {
            $item['icon']='';
        }

        $save_has_error=false;
        if (!(isset($item['entrytype']) && $item['entrytype']==='space')) {
            foreach ($languages as $language) {
                if (!isset($item['menu_term_lang'][$language]) || trim((string)$item['menu_term_lang'][$language])==='') {
                    message(lang('menu_item_menu_term_required'),'error');
                    $save_has_error=true;
                    break;
                }
            }
        }

        if ($allowed_to_change['external_url']) {
            $external_url=(isset($_REQUEST['link']) ? trim((string)$_REQUEST['link']) : '');
            $item['link']=$external_url;
            if ($external_url==='') {
                message(lang('menu_item_external_url_required'),'error');
                $save_has_error=true;
            } elseif (!filter_var($external_url,FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i',$external_url)) {
                message(lang('menu_item_invalid_external_url'),'error');
                $save_has_error=true;
            }
        }

        if ($allowed_to_change['content_name']) {
            $old_content_name=(isset($item['content_name']) ? trim((string)$item['content_name']) : '');
            $new_content_name=(isset($_REQUEST['content_name']) ? html__menu_normalize_content_name($_REQUEST['content_name']) : '');
            if ($new_content_name==='') {
                $new_content_name=html__menu_normalize_content_name($item['id']);
            }
            $content_name_exists=false;
            foreach ($config['items'] as $k=>$line) {
                if (!is_array($line) || !isset($line['content_name'])) {
                    continue;
                }
                if ($k===$item_index) {
                    continue;
                }
                if ((string)$line['content_name']===$new_content_name) {
                    $content_name_exists=true;
                    break;
                }
            }
            if ($content_name_exists) {
                message(lang('menu_item_shortcut_already_exists'),'error');
                $save_has_error=true;
            } else {
                if (isset($item['richtext']) && $item['richtext']==='y') {
                    if ($new_content_name==='') {
                        message(lang('menu_item_shortcut_already_exists'),'error');
                        $save_has_error=true;
                    } else {
                        if ($old_content_name!=='' && $old_content_name!==$new_content_name) {
                            $query="SELECT COUNT(*) AS cnt
                                    FROM ".table('lang')."
                                    WHERE content_type='public_content'
                                    AND content_name=:content_name";
                            $line=orsee_query($query,array(':content_name'=>$new_content_name));
                            if (isset($line['cnt']) && (int)$line['cnt']>0) {
                                message(lang('menu_item_shortcut_already_exists'),'error');
                                $save_has_error=true;
                            } else {
                                $query="UPDATE ".table('lang')."
                                        SET content_name=:new_content_name
                                        WHERE content_type='public_content'
                                        AND content_name=:old_content_name";
                                or_query($query,array(':new_content_name'=>$new_content_name,':old_content_name'=>$old_content_name));
                            }
                        }
                    }
                }
                $item['content_name']=$new_content_name;
            }
        }

        if ($allowed_to_change['admin_type_hide']) {
            $admin_type_hide=array();
            if (isset($_REQUEST['admin_type_hide']) && trim((string)$_REQUEST['admin_type_hide'])!=='') {
                $admin_type_hide=array_values(array_unique(multipicker_json_to_array($_REQUEST['admin_type_hide'])));
            }
            $item['admin_type_hide']=$admin_type_hide;
        }

        if ($is_mainpage) {
            $item['entrytype']='headlink';
            $item['show_if_logged_in']=1;
            $item['show_if_not_logged_in']=1;
            $item['hidden']='n';
        }
        if ($area==='public' && isset($menu_item_edit_fixed_public_visibility[$menu_area])) {
            $item['show_if_not_logged_in']=(int)$menu_item_edit_fixed_public_visibility[$menu_area]['show_if_not_logged_in'];
            $item['show_if_logged_in']=(int)$menu_item_edit_fixed_public_visibility[$menu_area]['show_if_logged_in'];
        }

        if (!$allowed_to_change['menu_term_lang']) {
            $item['menu_term_lang']=array();
        }
        if (!$allowed_to_change['page_title_lang']) {
            $item['page_title_lang']=array();
        }

        if (!$save_has_error) {
            $config['items'][$item_index]=$item;
            $done=html__menu_save_config($area,$config);
            if ($done) {
                log__admin('menu_item_edit','area:'.$area.', item_id:'.$item_id);
                message(lang('changes_saved'));
                redirect('admin/options_menu_item_edit.php?area='.$area.'&item_id='.urlencode($item_id));
            } else {
                message(lang('database_problem'),'error');
            }
        } else {
            $menu_item_edit_keep_post_values=true;
        }
    }
}

if ($proceed) {
    if (!$menu_item_edit_keep_post_values) {
        $config=html__menu_load_config($area);
        $item=array();
        foreach ($config['items'] as $line) {
            if (is_array($line) && isset($line['id']) && (string)$line['id']===$item_id) {
                $item=$line;
                break;
            }
        }
    }
    if (!is_array($item) || !isset($item['id'])) {
        redirect('admin/options_menu.php?area='.$area);
    }

    if (!isset($item['menu_term_lang']) || !is_array($item['menu_term_lang'])) {
        $item['menu_term_lang']=array();
    }
    if (!isset($item['page_title_lang']) || !is_array($item['page_title_lang'])) {
        $item['page_title_lang']=array();
    }
    $languages=get_languages();
    $lang_dirs=lang__is_rtl_all_langs();
    $icon_choices=helpers__fontawesome_icon_whitelist();
    $is_public_mainpage=($area==='public' && isset($item['id']) && $item['id']==='mainpage_welcome');
    $is_admin_mainpage=($area==='admin' && isset($item['id']) && $item['id']==='admin_mainpage');
    $is_mainpage=($is_public_mainpage || $is_admin_mainpage);
    $menu_area=(isset($item['menu_area']) ? (string)$item['menu_area'] : '');
    $allowed_to_change=$menu_item_edit_allowed_defaults;
    if ($is_public_mainpage) {
        $allowed_to_change['entrytype']=false;
        $allowed_to_change['hidden']=false;
        $allowed_to_change['show_if_not_logged_in']=false;
        $allowed_to_change['show_if_logged_in']=false;
    }
    if ($is_mainpage) {
        $allowed_to_change['content_name']=false;
    }
    if ($is_admin_mainpage) {
        $allowed_to_change['admin_type_hide']=false;
        $item['admin_type_hide']=array();
    }
    if (isset($item['entrytype']) && $item['entrytype']==='space') {
        $allowed_to_change['entrytype']=false;
        $allowed_to_change['content_name']=false;
        $allowed_to_change['icon']=false;
        $allowed_to_change['external_url']=false;
        $allowed_to_change['menu_term_lang']=false;
        $allowed_to_change['page_title_lang']=false;
    }
    if (!(isset($item['custom_external']) && $item['custom_external']==='y')) {
        $allowed_to_change['external_url']=false;
    }
    if (isset($item['custom_external']) && $item['custom_external']==='y') {
        $allowed_to_change['content_name']=false;
        $allowed_to_change['page_title_lang']=false;
        $allowed_to_change['admin_type_hide']=false;
    }
    if (isset($item['entrytype']) && $item['entrytype']==='head') {
        $allowed_to_change['page_title_lang']=false;
    }
    if (isset($item['can_hide']) && $item['can_hide']==='n') {
        $allowed_to_change['hidden']=false;
    }
    if (isset($item['can_be_secondary']) && $item['can_be_secondary']==='n') {
        $allowed_to_change['entrytype']=false;
    }
    if ($area!=='public') {
        $allowed_to_change['show_if_not_logged_in']=false;
        $allowed_to_change['show_if_logged_in']=false;
    }
    if ($area==='public' && isset($menu_item_edit_fixed_public_visibility[$menu_area])) {
        $allowed_to_change['show_if_not_logged_in']=false;
        $allowed_to_change['show_if_logged_in']=false;
        $item['show_if_not_logged_in']=(int)$menu_item_edit_fixed_public_visibility[$menu_area]['show_if_not_logged_in'];
        $item['show_if_logged_in']=(int)$menu_item_edit_fixed_public_visibility[$menu_area]['show_if_logged_in'];
    }
    if (!(isset($item['richtext']) && $item['richtext']==='y')) {
        $allowed_to_change['content_name']=false;
        $allowed_to_change['page_title_lang']=false;
    }
    if ($area!=='admin' || !(isset($item['richtext']) && $item['richtext']==='y')) {
        $allowed_to_change['admin_type_hide']=false;
    }

    echo '<div class="orsee-panel">';
    show_message();
    echo '<div class="orsee-panel-title"><div>'.lang('menu_item').': '.htmlspecialchars($item_id).' ('.($area==='public' ? lang('menu_area_public') : lang('menu_area_admin')).')</div></div>';
    echo '<div class="orsee-form-shell">';
    echo '<form action="options_menu_item_edit.php" method="POST">'.csrf__field();
    echo '<input type="hidden" name="area" value="'.htmlspecialchars($area).'">';
    echo '<input type="hidden" name="item_id" value="'.htmlspecialchars($item_id).'">';

    echo '<div class="field"><label class="label">'.lang('menu_item_id').'</label><div class="control"><div>'.htmlspecialchars($item_id).'</div></div></div>';
    echo '<div class="field"><label class="label">'.lang('menu_item_type').'</label><div class="control"><div>'.htmlspecialchars((string)$item['entrytype']).'</div></div></div>';

    if (isset($item['entrytype']) && $item['entrytype']!=='space') {
        if (!$allowed_to_change['entrytype']) {
            echo '<div class="field"><label class="label">'.lang('menu_item_display_level').'</label><div class="control"><div>'.lang('menu_type_primary_link').'</div></div></div>';
        } elseif (!isset($item['entrytype']) || $item['entrytype']!=='head') {
            $entry_options=array('headlink'=>lang('menu_type_primary_link'),'link'=>lang('menu_item_type_submenu_link'));
            echo '<div class="field"><label class="label">'.lang('menu_item_display_level').'</label><div class="control"><span class="select is-primary"><select name="entrytype">';
            foreach ($entry_options as $v=>$text) {
                echo '<option value="'.$v.'"';
                if (isset($item['entrytype']) && $item['entrytype']===$v) {
                    echo ' selected';
                }
                echo '>'.$text.'</option>';
            }
            echo '</select></span></div></div>';
        }
    }

    if ($allowed_to_change['icon']) {
        $icon_value=(isset($item['icon']) ? (string)$item['icon'] : '');
        if (substr($icon_value,0,3)==='fa-') {
            $icon_value=substr($icon_value,3);
        }
        $icon_preview_class=preg_replace('/[^a-z0-9\\-]/i','',$icon_value);
        echo '<div class="field">';
        echo '<label class="label">'.lang('menu_item_icon').'</label>';
        echo '<div class="control orsee-fa-icon-selector" data-orsee-fa-icon-selector data-none-label="'.htmlspecialchars(lang('none')).'">';
        echo '<input type="hidden" name="icon" class="orsee-fa-icon-selector-value" value="'.htmlspecialchars($icon_value).'">';
        echo '<div class="orsee-fa-icon-selector-toggle" data-role="toggle">';
        echo '<span class="orsee-fa-icon-selector-toggle-main">';
        if ($icon_preview_class!=='') {
            echo '<i class="orsee-fa-icon-selector-preview fa fa-'.htmlspecialchars($icon_preview_class).'" aria-hidden="true"></i>';
        } else {
            echo '<i class="orsee-fa-icon-selector-preview" aria-hidden="true"></i>';
        }
        echo '<span class="orsee-fa-icon-selector-text">'.($icon_preview_class!=='' ? $icon_preview_class : lang('none')).'</span>';
        echo '</span>';
        echo '<i class="fa fa-angle-down orsee-fa-icon-selector-arrow" aria-hidden="true"></i>';
        echo '</div>';
        echo '<div class="orsee-fa-icon-selector-dropdown" data-role="dropdown">';
        echo '<div class="orsee-fa-icon-selector-dropdown-inner">';
        echo '<div class="field">';
        echo '<input type="text" class="input is-primary orsee-input orsee-input-text orsee-fa-icon-selector-search" data-role="search" placeholder="'.htmlspecialchars(lang('search')).'">';
        echo '</div>';
        echo '<div class="orsee-fa-icon-selector-list" data-role="list">';
        echo '<div data-role="option" data-icon="" data-search="'.htmlspecialchars(strtolower(lang('none').' '.lang('empty').' no icon')).'" class="orsee-fa-icon-selector-option">';
        echo '<span class="orsee-fa-icon-selector-option-icon"></span>';
        echo '<span>'.htmlspecialchars(lang('none')).'</span>';
        echo '</div>';
        foreach ($icon_choices as $icon_name) {
            $icon_name=trim((string)$icon_name);
            if ($icon_name==='') {
                continue;
            }
            $icon_short=(substr($icon_name,0,3)==='fa-' ? substr($icon_name,3) : $icon_name);
            echo '<div data-role="option" data-icon="'.htmlspecialchars($icon_short).'" data-search="'.htmlspecialchars(strtolower($icon_name.' '.$icon_short)).'" class="orsee-fa-icon-selector-option">';
            echo '<i class="fa '.htmlspecialchars($icon_name).' orsee-fa-icon-selector-option-icon" aria-hidden="true"></i>';
            echo '<span>'.htmlspecialchars($icon_short).'</span>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="field"><label class="label">'.lang('menu_item_icon').'</label><div class="control"><div>'.htmlspecialchars((isset($item['icon']) ? (string)$item['icon'] : '')).'</div></div></div>';
    }

    if ($allowed_to_change['external_url']) {
        echo '<div class="field"><label class="label">'.lang('menu_item_external_url').'</label><div class="control"><input class="input is-primary orsee-input orsee-input-text" type="text" name="link" dir="ltr" value="'.htmlspecialchars((isset($item['link']) ? (string)$item['link'] : '')).'"></div></div>';
    }

    if ($allowed_to_change['hidden']) {
        $hidden_value=(isset($item['hidden']) && $item['hidden']==='y' ? 'y' : 'n');
        echo '<div class="field"><label class="label">'.lang('menu_hidden').'</label><div class="control">';
        echo '<label class="radio">'.lang('y').' <input type="radio" name="hidden" value="y"'.($hidden_value==='y' ? ' checked' : '').'></label>';
        echo '&nbsp;&nbsp;';
        echo '<label class="radio">'.lang('n').' <input type="radio" name="hidden" value="n"'.($hidden_value!=='y' ? ' checked' : '').'></label>';
        echo '</div></div>';
    } else {
        echo '<div class="field"><label class="label">'.lang('menu_hidden').'</label><div class="control"><div>'.((isset($item['hidden']) && $item['hidden']==='y') ? lang('y') : lang('n')).'</div></div></div>';
    }

    if ($area==='public' && $allowed_to_change['show_if_not_logged_in'] && $allowed_to_change['show_if_logged_in']) {
        $show_in=(isset($item['show_if_logged_in']) && (int)$item['show_if_logged_in']);
        $show_out=(isset($item['show_if_not_logged_in']) && (int)$item['show_if_not_logged_in']);
        $show_for='both';
        if ($show_in && !$show_out) {
            $show_for='logged_in';
        } elseif (!$show_in && $show_out) {
            $show_for='logged_out';
        }
        echo '<div class="field"><label class="label">'.lang('menu_item_login_visibility').'</label><div class="control"><span class="select is-primary"><select name="show_for_login_state"><option value="both"'.($show_for==='both' ? ' selected' : '').'>'.lang('menu_visibility_always').'</option><option value="logged_in"'.($show_for==='logged_in' ? ' selected' : '').'>'.lang('menu_visibility_logged_in').'</option><option value="logged_out"'.($show_for==='logged_out' ? ' selected' : '').'>'.lang('menu_visibility_logged_out').'</option></select></span></div></div>';
    } elseif ($area==='public') {
        $show_in=(isset($item['show_if_logged_in']) && (int)$item['show_if_logged_in']);
        $show_out=(isset($item['show_if_not_logged_in']) && (int)$item['show_if_not_logged_in']);
        $show_text=lang('menu_visibility_always');
        if ($show_in && !$show_out) {
            $show_text=lang('menu_visibility_logged_in');
        } elseif (!$show_in && $show_out) {
            $show_text=lang('menu_visibility_logged_out');
        } elseif (!$show_in && !$show_out) {
            $show_text=lang('menu_visibility_never');
        }
        echo '<div class="field"><label class="label">'.lang('menu_item_login_visibility').'</label><div class="control"><div>'.$show_text.'</div></div></div>';
    }

    if ($allowed_to_change['content_name']) {
        echo '<div class="field"><label class="label">'.lang('menu_item_shortcut').'</label><p class="help">'.lang('menu_item_shortcut_help').'</p><div class="control"><input class="input is-primary orsee-input orsee-input-text" type="text" name="content_name" dir="ltr" value="'.htmlspecialchars((isset($item['content_name']) ? (string)$item['content_name'] : '')).'"></div></div>';
    } elseif (isset($item['richtext']) && $item['richtext']==='y') {
        echo '<div class="field"><label class="label">'.lang('menu_item_shortcut').'</label><div class="control"><div>'.htmlspecialchars((isset($item['content_name']) ? (string)$item['content_name'] : '')).'</div></div></div>';
    }

    foreach (array('menu_term_lang'=>lang('menu_menu_term'),'page_title_lang'=>lang('menu_item_page_title')) as $field_name=>$field_label) {
        if (isset($allowed_to_change[$field_name]) && !$allowed_to_change[$field_name]) {
            continue;
        }
        echo '<div class="field"><label class="label">'.$field_label.'</label>';
        foreach ($languages as $language) {
            $value=(isset($item[$field_name][$language]) ? (string)$item[$field_name][$language] : '');
            $field_dir=(isset($lang_dirs[$language]) && $lang_dirs[$language] ? 'rtl' : 'ltr');
            echo '<div class="field is-flex is-align-items-center"><label class="label mb-0 mr-2">'.$language.':</label><div class="control is-flex-grow-1"><input class="input is-primary orsee-input orsee-input-text" dir="'.$field_dir.'" type="text" name="'.$field_name.'['.$language.']" value="'.htmlspecialchars($value).'"></div></div>';
        }
        echo '</div>';
    }

    if ($allowed_to_change['admin_type_hide']) {
        $selected=(isset($item['admin_type_hide']) && is_array($item['admin_type_hide']) ? $item['admin_type_hide'] : array());
        echo '<div class="field"><label class="label">'.lang('menu_item_hide_for_admin_types').'</label><div class="control">'.admin__admin_type_select_field('admin_type_hide',$selected,true,array('tag_bg_color'=>'--color-selector-tag-bg-profilefields')).'</div></div>';
    }

    if (isset($item['richtext']) && $item['richtext']==='y' && isset($item['content_name']) && trim((string)$item['content_name'])!=='') {
        $content_lang_id=lang__get_lang_id('public_content',$item['content_name']);
        if (!$content_lang_id) {
            $item_lang=array(
                'content_type'=>'public_content',
                'content_name'=>(string)$item['content_name']
            );
            $content_lang_id=lang__insert_to_lang($item_lang);
        }
        if ($content_lang_id) {
            $edit_link='lang_item_edit.php?item=public_content&id='.(int)$content_lang_id.'&return_to='.urlencode('options_menu_item_edit.php?area='.$area.'&item_id='.$item_id);
            echo '<div class="field"><label class="label">'.lang('menu_item_richtext_content').'</label><div class="control">'.button_link($edit_link,lang('menu_item_edit_content'),'pencil-square-o').'</div></div>';
        }
    }

    echo '<div class="field orsee-form-row-grid orsee-form-row-grid--3 orsee-form-actions">';
    echo '  <div class="orsee-form-row-col has-text-left">'.button_back('options_menu.php?area='.$area).'</div>';
    echo '  <div class="orsee-form-row-col has-text-centered"><button type="submit" name="save" value="1" class="button orsee-btn">'.lang('save').'</button></div>';
    echo '  <div class="orsee-form-row-col has-text-right"></div>';
    echo '</div>';

    echo '</form></div></div>';
}

include("footer.php");

?>
