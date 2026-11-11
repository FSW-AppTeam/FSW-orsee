<?php
// part of orsee. see orsee.org

function load_settings() {
    global $system__options_general, $system__options_defaults, $settings__sendmail_path;

    $query="SELECT * FROM ".table('options')."
    WHERE option_type='general' OR option_type='default'";
    $result=or_query($query);
    while ($line = pdo_fetch_assoc($result)) {
        $settings[$line['option_name']]=stripslashes($line['option_value']);
    }

    if (isset($settings__sendmail_path) && $settings__sendmail_path) {
        $settings['email_sendmail_path']=$settings__sendmail_path;
    }

    foreach ($system__options_general as $option) {
        if (isset($option['type']) && ($option['type']=='line' || $option['type']=='comment')) {
        } else {
            if (!isset($settings[$option['option_name']])) {
                $settings[$option['option_name']]=$option['default_value'];
            }
        }
    }
    foreach ($system__options_defaults as $option) {
        if (isset($option['type']) && ($option['type']=='line' || $option['type']=='comment')) {
        } else {
            if (!isset($settings[$option['option_name']])) {
                $settings[$option['option_name']]=$option['default_value'];
            }
        }
    }

    $styles=get_style_array();
    foreach (array('orsee_public_style','orsee_admin_style') as $style_key) {
        if (!in_array($settings[$style_key],$styles)) {
            if (in_array('orsee',$styles)) {
                $settings[$style_key]='orsee';
            } else {
                $settings[$style_key]=$styles[0];
            }
        }
    }

    return $settings;
}

function load_colors($style='') {
    global $settings, $system__colors;
    $color=array();
    $known_color_names=array();

    foreach ($system__colors as $c) {
        if (isset($c['type']) && ($c['type']=='line' || $c['type']=='comment')) {
        } else {
            $known_color_names[$c['color_name']]=true;
            $color[$c['color_name']]=$c['default_value'];
        }
    }

    if (!$style && isset($settings['style'])) {
        $style=$settings['style'];
    }
    $style=(string)$style;
    $style_safe=preg_replace('/[^a-zA-Z0-9_\-]/','',$style);

    if ($style_safe!=='') {
        $colors_json_file=__DIR__.'/../style/'.$style_safe.'/colors.json';
        if (is_readable($colors_json_file)) {
            $json_raw=file_get_contents($colors_json_file);
            if ($json_raw!==false) {
                $json_colors=json_decode($json_raw,true);
                if (is_array($json_colors)) {
                    foreach ($json_colors as $k=>$v) {
                        if (!isset($known_color_names[$k])) {
                            continue;
                        }
                        if (is_array($v) || is_object($v)) {
                            continue;
                        }
                        $color[$k]=(string)$v;
                    }
                }
            }
        }
    }

    $pars=array(':style'=>$style);
    $query="select * from ".table('options')."
            where option_type='color'
            and option_style= :style
            order by option_name";
    $result=or_query($query,$pars);
    while ($line=pdo_fetch_assoc($result)) {
        if (!isset($known_color_names[$line['option_name']])) {
            continue;
        }
        if (trim((string)$line['option_value'])==='') {
            continue;
        }
        $color[$line['option_name']]=$line['option_value'];
    }

    return $color;
}



function or_setting($o,$v="") {
    global $settings;
    $ret=false;
    if ($v && $settings[$o]==$v) {
        $ret=true;
    } elseif ($settings[$o]=='y') {
        $ret=true;
    }
    return $ret;
}

function options__show_option($o) {
    global $options;
    if (isset($o['option_name']) && (!isset($options[$o['option_name']]))) {
        if (isset($o['default_value'])) {
            $options[$o['option_name']]=$o['default_value'];
        } else {
            $options[$o['option_name']]="";
        }
    }
    if ($o['type']=='plain') {
        $o=options__replace_funcs_in_field($o);
        $field=options__style_field_html($o['field']);
        $done=option__display_option($o['option_text'],$field);
    } elseif (isset($o['type']) && $o['type']=='line') {
        options__line();
    } elseif (isset($o['type']) && $o['type']=='comment') {
        $done=option__display_option('<B>'.$o['text'].'</B>','',true);
    } else {
        $o=options__replace_funcs_in_field($o);
        if (isset($options[$o['option_name']]) && ($options[$o['option_name']] || $options[$o['option_name']]=='0')) {
            $o['value']=$options[$o['option_name']];
        } else {
            $o['value']=$o['default_value'];
        }
        $o['submitvarname']='options['.$o['option_name'].']';
        if (!isset($o['compact']) && in_array($o['type'],array('select_list','select_numbers','select_yesno'),true)) {
            $o['compact']=true;
        }
        $field=survey__render_field($o);
        $field=options__style_field_html($field);
        $done=option__display_option($o['option_text'],$field);
    }
}

function options__render_grouped_options($opts,$show_colors=false) {
    $groups=array();
    $current=array('title'=>'','rows'=>array());

    foreach ($opts as $o) {
        if (isset($o['type']) && $o['type']=='line') {
            if (count($current['rows'])>0 || $current['title']) {
                $groups[]=$current;
            }
            $current=array('title'=>'','rows'=>array());
            continue;
        }

        if (isset($o['type']) && $o['type']=='comment') {
            if (count($current['rows'])>0 || $current['title']) {
                $groups[]=$current;
            }
            $title_text=(isset($o['text']) ? $o['text'] : '');
            $current=array('title'=>$title_text,'rows'=>array());
            continue;
        }

        ob_start();
        if ($show_colors) {
            options__show_color_option($o);
        } else {
            options__show_option($o);
        }
        $row_html=ob_get_clean();
        if ($row_html) {
            $current['rows'][]=$row_html;
        }
    }

    if (count($current['rows'])>0 || $current['title']) {
        $groups[]=$current;
    }

    foreach ($groups as $group) {
        if ($group['title']) {
            echo '<div class="orsee-option-row-comment"><strong>'.$group['title'].'</strong></div>';
        }
        if (count($group['rows'])===0) {
            continue;
        }
        echo '<div class="orsee-surface-card">';
        foreach ($group['rows'] as $row_html) {
            echo $row_html;
        }
        echo '</div>';
    }
}

function options__get_styles() {
    $styles=get_style_array();
    return implode(",",$styles);
}
function options__replace_funcs_in_field($f) {
    global $lang, $settings, $options;
    foreach ($f as $o=>$v) {
        if (substr($f[$o],0,5)=='func:') {
            eval('$f[$o]='.substr($f[$o],5).';');
        }
    }
    return $f;
}

function options__time_field($option_name, $default_value='10:00', $picker_key='') {
    global $options;
    $value='';
    if (isset($options[$option_name]) && $options[$option_name]) {
        $value=trim((string)$options[$option_name]);
    }
    if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/',$value)) {
        $value=$default_value;
    }
    if (!$picker_key) {
        $picker_key=$option_name.'_picker';
    }
    $picker_time=strtotime(date('Y-m-d').' '.$value);
    return formhelpers__pick_time($picker_key,$picker_time,5);
}

function options__style_field_html($field) {
    if (!is_string($field) || $field==="") {
        return $field;
    }

    $field=preg_replace_callback(
        '/<(input|select|textarea)\b([^>]*)>/i',
        function ($m) {
            $tag=strtolower($m[1]);
            $attrs=$m[2];
            $type='';
            if ($tag==='input' && preg_match('/\btype\s*=\s*["\']?([a-zA-Z0-9_-]+)/i',$attrs,$tm)) {
                $type=strtolower($tm[1]);
            }
            if ($tag==='input' && in_array($type,array('hidden','submit','button','file'),true)) {
                return '<'.$m[1].$attrs.'>';
            }

            if (preg_match('/\bclass\s*=\s*("|\')([^"\']*)\1/i',$attrs,$cm)) {
                $existing=trim($cm[2]);
                if ($tag==='input' && preg_match('/\bcolorpickerinput\b/i',$existing)) {
                    $add='';
                } elseif ($tag==='textarea') {
                    $add=' textarea is-primary orsee-option-control orsee-textarea';
                } elseif ($type==='checkbox' || $type==='radio') {
                    $add=' orsee-option-check';
                } else {
                    $add=' input is-primary orsee-option-control orsee-input orsee-input-compact';
                }
                $new_classes=trim($existing.' '.$add);
                $attrs=preg_replace('/\bclass\s*=\s*("|\')([^"\']*)\1/i','class="'.$new_classes.'"',$attrs,1);
            } else {
                if ($tag==='textarea') {
                    $attrs.=' class="textarea is-primary orsee-option-control orsee-textarea"';
                } elseif ($type==='checkbox' || $type==='radio') {
                    $attrs.=' class="orsee-option-check"';
                } else {
                    $attrs.=' class="input is-primary orsee-option-control orsee-input orsee-input-compact"';
                }
            }
            return '<'.$m[1].$attrs.'>';
        },
        $field
    );

    return $field;
}


function options__show_color_option($o) {
    global $mycolors;
    if (isset($o['type']) && $o['type']=='line') {
        options__line();
    } elseif (isset($o['type']) && $o['type']=='comment') {
        $done=option__display_option('<B>'.$o['text'].'</B>','',true);
    } elseif (isset($o['color_name']) && isset($o['default_value'])) {
        if (isset($mycolors[$o['color_name']]) && ($mycolors[$o['color_name']] || $mycolors[$o['color_name']]=='0')) {
            $o['value']=$mycolors[$o['color_name']];
        } else {
            $o['value']=$o['default_value'];
        }
        $o['submitvarname']='mycolors['.$o['color_name'].']';
        if (isset($o['options']['size'])) {
            $size=$o['options']['size'];
        } else {
            $size=10;
        }
        if (isset($o['options']['maxlength'])) {
            $maxlength=$o['options']['maxlength'];
        } else {
            $maxlength=10;
        }
        if (isset($o['options']['nopicker']) && $o['options']['nopicker']) {
            $picker="";
        } else {
            $picker=' class="colorpickerinput" ';
        }
        $field='<INPUT type="text" '.$picker.' name="'.$o['submitvarname'].'" size="'.$size.'" maxlength="'.$maxlength.'" value="'.$o['value'].'">';

        $done=option__display_option($o['color_name'],$field);
    }
}


function option__display_option($text,$field,$colspan=false) {
    if (isset($GLOBALS['orsee_options_render_mode']) && $GLOBALS['orsee_options_render_mode']=='div') {
        if ($colspan) {
            echo '<div class="orsee-option-row-comment">'.$text.'</div>';
        } else {
            echo '<div class="orsee-option-item">
                    <div class="orsee-option-label">'.$text.'</div>
                    <div class="orsee-option-field">'.$field.'</div>
                  </div>';
        }
        return;
    }
    if ($colspan) {
        echo '<TR>
              <TD colspan="2">'.$text.'</TD>
           </TR>';
    } else {
        echo '<TR>
              <TD>'.$text.'</TD>
              <TD>'.$field.'</TD>
           </TR>';
    }
}

function options__line() {
    if (isset($GLOBALS['orsee_options_render_mode']) && $GLOBALS['orsee_options_render_mode']=='div') {
        echo '<div class="orsee-option-divider-row"><hr class="orsee-option-divider"></div>';
        return;
    }
    echo '  <TR><TD colspan=2><hr></TD></TR>';
}

function options__get_color_styles() {
    global $preloaded_color_styles;
    if (isset($preloaded_color_styles) && is_array($preloaded_color_styles)
        && count($preloaded_color_styles)>0) {
        return $preloaded_color_styles;
    } else {
        $color_styles=array();
        $query="select option_style from ".table('options')."
                where option_type='color'
                group by option_style
                order by option_style";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            $color_styles[]=$line['option_style'];
        }
        $preloaded_color_styles=$color_styles;
        return $color_styles;
    }
}

function options__save_item_order($item_type,$order_array,$details=array()) {
    $pars=array(':item_type'=>$item_type);
    $query="DELETE FROM ".table('objects')."
            WHERE item_type= :item_type";
    $done=or_query($query,$pars);

    $pars=array();
    foreach ($order_array as $k=>$v) {
        if (isset($details[$v]) && is_array($details[$v])) {
            $detstr=property_array_to_db_string($details[$v]);
        } else {
            $detstr='';
        }
        $pars[]=array(':item_type'=>$item_type,
                    ':item_name'=>$v,
                    ':order_number'=>$k,
                    ':item_details'=>$detstr);
    }
    $query="INSERT INTO ".table('objects')."
            SET order_number = :order_number,
            item_type = :item_type,
            item_name = :item_name,
            item_details = :item_details";
    $done=or_query($query,$pars);
    return $done;
}

function options__ordered_lists_get_current($poss_cols,$saved_cols,$extra_fields=array()) {
    // filter out non-draggable at begin and end
    $first_draggable=false;
    $first=array();
    $num_first=0;
    $last=array();
    $num_last=0;
    $draggable=array();
    foreach ($poss_cols as $k=>$arr) {
        if (isset($arr['allow_drag']) && $arr['allow_drag']==false) {
            if ($first_draggable) {
                $num_last--;
                $arr['fixed_position']=$num_last;
                if (isset($arr['allow_remove']) && $arr['allow_remove']==false) {
                    $arr['on_list']=true;
                } else {
                    $arr['on_list']=false;
                }
                $last[$k]=$arr;
                unset($poss_cols[$k]);
            } else {
                $num_first++;
                $arr['fixed_position']=$num_first;
                if (isset($arr['allow_remove']) && $arr['allow_remove']==false) {
                    $arr['on_list']=true;
                } else {
                    $arr['on_list']=false;
                }
                $first[$k]=$arr;
                unset($poss_cols[$k]);
            }
        } else {
            $draggable[$k]=$arr;
            $first_draggable=true;
        }
    }
    // get the saved columns and put them on list
    $draggable_num=0;
    $onlist_draggable=array();
    foreach ($saved_cols as $k=>$line) {
        if (isset($first[$k])) {
            $first[$k]['on_list']=true;
        } elseif (isset($last[$k])) {
            $last[$k]['on_list']=true;
        } elseif (isset($draggable[$k])) {
            $draggable_num++;
            $onlist_draggable[$k]=$draggable[$k];
            $onlist_draggable[$k]['fixed_position']=$num_first+$draggable_num;
            $onlist_draggable[$k]['on_list']=true;
            $onlist_draggable[$k]['item_details']=db_string_to_property_array($line['item_details']);
            unset($draggable[$k]);
        }
    }
    foreach ($draggable as $k=>$arr) {
        if (isset($arr['allow_remove']) && $arr['allow_remove']==false) {
            $draggable_num++;
            $onlist_draggable[$k]=$draggable[$k];
            $onlist_draggable[$k]['fixed_position']=$num_first+$draggable_num;
            $onlist_draggable[$k]['on_list']=true;
            unset($draggable[$k]);
        }
    }
    // now put eveyrhting together
    $listrows=array();
    foreach ($first as $k=>$arr) {
        $listrows[$k]=$arr;
    }
    foreach ($onlist_draggable as $k=>$arr) {
        $listrows[$k]=$arr;
    }
    foreach ($draggable as $k=>$arr) {
        $arr['fixed_position']=0;
        $arr['on_list']=false;
        $listrows[$k]=$arr;
    }
    foreach ($last as $k=>$arr) {
        $listrows[$k]=$arr;
    }
    // and now just make sure all fields exist
    foreach ($listrows as $k=>$arr) {
        if (!isset($arr['display_text'])) {
            $arr['display_text']=$k;
        }
        if (!isset($arr['on_list'])) {
            $arr['on_list']=false;
        }
        if (!isset($arr['allow_remove'])) {
            $arr['allow_remove']=true;
        }
        if (!isset($arr['allow_drag'])) {
            $arr['allow_drag']=true;
        }
        if (!isset($arr['fixed_position'])) {
            $arr['fixed_position']=0;
        }
        if (!isset($arr['sortable'])) {
            $arr['sortable']=true;
        }
        if (!isset($arr['cols'])) {
            $arr['cols']='<div class="orsee-listcell">'.$arr['display_text'].'</div>';
        }
        foreach ($extra_fields as $extra_field=>$display_name) {
            if ($extra_field=='sortby_radio') {
                if ($arr['sortable']) {
                    $arr['cols'].='<div class="orsee-listcell orsee-listcell-center"><label class="radio"><INPUT type="radio" name="sortby" value="'.$k.'"';
                    if (isset($arr['item_details']['default_sortby']) && $arr['item_details']['default_sortby']) {
                        $arr['cols'].=' CHECKED';
                    }
                    $arr['cols'].='></label></div>';
                } else {
                    $arr['cols'].='<div class="orsee-listcell"></div>';
                }
            } elseif ($extra_field=='field_value') {
                if (!isset($arr['item_details'])) {
                    $arr['item_details']=array();
                }
                if (!isset($arr['item_details']['field_value'])) {
                    $arr['item_details']['field_value']='';
                }
                $arr['cols'].='<div class="orsee-listcell"><INPUT class="input is-primary orsee-option-control orsee-input orsee-input-text orsee-input-compact" type="text" size="30" maxlength="255" name="field_values['.$k.']" value="'.$arr['item_details']['field_value'].'"></div>';
            } elseif ($extra_field=='hide_for_admin_types') {
                if (!(isset($arr['disallow_hide']) && $arr['disallow_hide'])) {
                    if (!isset($arr['item_details'])) {
                        $arr['item_details']=array();
                    }
                    if (!isset($arr['item_details']['hide_admin_types'])) {
                        $arr['item_details']['hide_admin_types']='';
                    }
                    $arr['cols'].='<div class="orsee-listcell orsee-listcell-center"><INPUT class="input is-primary orsee-option-control orsee-input orsee-input-text orsee-input-compact" type="text" size="30" maxlength="255" name="hide_admin_types['.$k.']" value="'.$arr['item_details']['hide_admin_types'].'"></div>';
                } else {
                    $arr['cols'].='<div class="orsee-listcell"></div>';
                }
            } elseif ($extra_field=='editable_on_session_list') {
                if (!(isset($arr['session_list_editable']) && $arr['session_list_editable'])) {
                    $arr['cols'].='<div class="orsee-listcell"></div>';
                } else {
                    if (!isset($arr['item_details'])) {
                        $arr['item_details']=array();
                    }
                    $checked='';
                    if (isset($arr['item_details']['editable_on_session_list']) && $arr['item_details']['editable_on_session_list']=='y') {
                        $checked=' CHECKED';
                    }
                    $arr['cols'].='<div class="orsee-listcell orsee-listcell-center"><label class="checkbox"><INPUT type="checkbox" name="editable_on_session_list['.$k.']" value="y"'.$checked.'></label></div>';
                }
            }
        }
        $listrows[$k]=$arr;
    }
    return $listrows;
}


function pform_options_yesnoradio($varname,$field) {
    global $editable_fields;
    $out='';
    if (in_array($varname,$editable_fields)) {
        $out.='<label class="radio">'.lang('y').' <INPUT TYPE="radio" NAME="'.$varname.'" VALUE="y"';
        if ($field[$varname]=='y') {
            $out.=' CHECKED';
        }
        $out.='></label>&nbsp;&nbsp;<label class="radio">'.lang('n').' <INPUT TYPE="radio" NAME="'.$varname.'" VALUE="n"';
        if ($field[$varname]!='y') {
            $out.=' CHECKED';
        }
        $out.='></label>';
    } else {
        $out.=($field[$varname]=='y') ? lang('y') : lang('n');
    }
    return $out;
}

function pform_options_inputtext($varname,$field,$size=25,$extra_attributes='') {
    global $editable_fields;
    $out='';
    if (in_array($varname,$editable_fields)) {
        $out='<INPUT class="input is-primary orsee-input orsee-input-text" type="text" name="'.$varname.'" size="'.$size.'" maxlength="200" value="'.htmlentities($field[$varname], ENT_QUOTES).'"'.$extra_attributes.'>';
    } else {
        $out=htmlentities($field[$varname], ENT_QUOTES);
    }
    return $out;
}

function pform_options_phone_country_options($selected_iso='') {
    global $preloaded_phone_country_options;
    if (!is_array($preloaded_phone_country_options)) {
        $preloaded_phone_country_options=array();
        $js_file=__DIR__.'/js/intlTelInput/intlTelInput.min.js';
        if (file_exists($js_file)) {
            $js_code=file_get_contents($js_file);
            if (is_string($js_code) && preg_match('/var It=\{(.*?)\},[A-Za-z0-9_]+=?It;?/s',$js_code,$map_match)) {
                if (preg_match_all('/([a-z]{2}):"((?:\\\\.|[^"])*)"/',$map_match[1],$country_matches,PREG_SET_ORDER)) {
                    foreach ($country_matches as $country_match) {
                        $iso=strtolower(trim((string)$country_match[1]));
                        if (!$iso) {
                            continue;
                        }
                        $name_raw=(string)$country_match[2];
                        $name_json_ready=preg_replace('/\\\\x([0-9A-Fa-f]{2})/','\\\\u00$1',$name_raw);
                        $name=json_decode('"'.$name_json_ready.'"');
                        if (!is_string($name) || $name==='') {
                            $name=stripcslashes($name_raw);
                        }
                        $preloaded_phone_country_options[$iso]=$name.' ('.$iso.')';
                    }
                }
            }
        }
        if (count($preloaded_phone_country_options)>0) {
            asort($preloaded_phone_country_options,SORT_NATURAL | SORT_FLAG_CASE);
        }
    }
    $options=$preloaded_phone_country_options;
    if (!is_array($options)) {
        $options=array();
    }
    if ($selected_iso) {
        $selected_iso=strtolower(trim((string)$selected_iso));
        if ($selected_iso && !isset($options[$selected_iso])) {
            $options[$selected_iso]=$selected_iso.' ('.$selected_iso.')';
        }
    }
    return $options;
}

function pform_options_vallanglist($varname_val,$varname_lang,$value_lang_map,$force_ltr=false) {
    global $editable_fields;
    $allow_edit=in_array($varname_val,$editable_fields);
    $dir_attr=($force_ltr ? ' dir="ltr"' : '');
    $i=0;
    $out='<div class="orsee-table orsee-table-no-hover">';
    $out.='<div class="orsee-table-row orsee-table-head">';
    $out.='<div class="orsee-table-cell">Option values</div>';
    $out.='<div class="orsee-table-cell">Language symbols</div>';
    $out.='</div>';

    foreach ($value_lang_map as $value=>$lang_symbol) {
        $out.='<div class="orsee-table-row">';
        $out.='<div class="orsee-table-cell">';
        if ($allow_edit) {
            $out.='<INPUT class="input is-primary orsee-input orsee-input-text" style="width:min(100%,10ch);" type="text" name="'.$varname_val.'['.$i.']" size="10" maxlength="200" value="'.$value.'"'.$dir_attr.'>';
        } else {
            $out.=$value;
        }
        $out.='</div>';
        $out.='<div class="orsee-table-cell">';
        if ($allow_edit) {
            $out.='<INPUT class="input is-primary orsee-input orsee-input-text" style="width:min(100%,25ch);" type="text" name="'.$varname_lang.'['.$i.']" size="25" maxlength="200" value="'.$lang_symbol.'"'.$dir_attr.'>';
        } else {
            $out.=$lang_symbol;
        }
        $out.='</div>';
        $out.='</div>';
        $i++;
    }
    if ($allow_edit) {
        for ($j=1; $j<=3; $j++) {
            $out.='<div class="orsee-table-row">';
            $out.='<div class="orsee-table-cell"><INPUT class="input is-primary orsee-input orsee-input-text" style="width:min(100%,10ch);" type="text" name="'.$varname_val.'['.$i.']" size="10" maxlength="200" value=""'.$dir_attr.'></div>';
            $out.='<div class="orsee-table-cell"><INPUT class="input is-primary orsee-input orsee-input-text" style="width:min(100%,25ch);" type="text" name="'.$varname_lang.'['.$i.']" size="25" maxlength="200" value=""'.$dir_attr.'></div>';
            $out.='</div>';
            $i++;
        }
    }
    $out.='</div>';
    return $out;
}

function options__load_object($item_type,$item_name) {
    $pars=array(':item_type'=>$item_type,
                ':item_name'=>$item_name);
    $query="select * from ".table('objects')."
            where item_type= :item_type
            and item_name= :item_name";
    $object=orsee_query($query,$pars);
    $object['item_details']=db_string_to_property_array($object['item_details']);
    return $object;
}

function options__load_object_raw($item_type,$item_name) {
    $pars=array(':item_type'=>$item_type,
                ':item_name'=>$item_name);
    $query="select * from ".table('objects')."
            where item_type= :item_type
            and item_name= :item_name";
    $object=orsee_query($query,$pars);
    return $object;
}

function options__save_object_raw($item_type,$item_name,$item_details,$enabled=1,$order_number=-1) {
    $object=options__load_object_raw($item_type,$item_name);
    if (is_array($object) && isset($object['item_id']) && $object['item_id']) {
        $object['enabled']=$enabled;
        $object['order_number']=$order_number;
        $object['item_details']=$item_details;
        $done=orsee_db_save_array($object,"objects",$object['item_id'],"item_id");
        if (!$done) {
            return false;
        }
        return options__load_object_raw($item_type,$item_name);
    }

    $pars=array(':enabled'=>$enabled,
                ':order_number'=>$order_number,
                ':item_type'=>$item_type,
                ':item_name'=>$item_name,
                ':item_details'=>$item_details);
    $query="INSERT INTO ".table('objects')."
            SET enabled= :enabled,
                order_number= :order_number,
                item_type= :item_type,
                item_name= :item_name,
                item_details= :item_details";
    $done=or_query($query,$pars);
    if (!$done) {
        return false;
    }
    return options__load_object_raw($item_type,$item_name);
}

function options__load_json_object($item_type,$item_name,$default=array()) {
    $object=options__load_object_raw($item_type,$item_name);
    if (!is_array($object) || !isset($object['item_details']) || trim((string)$object['item_details'])==='') {
        return $default;
    }

    $decoded=json_decode((string)$object['item_details'],true);
    if (!is_array($decoded)) {
        return $default;
    }
    return $decoded;
}

function options__save_json_object($item_type,$item_name,$json_array,$enabled=1,$order_number=-1) {
    $json_string=json_encode($json_array);
    if ($json_string===false) {
        return false;
    }
    return options__save_object_raw($item_type,$item_name,$json_string,$enabled,$order_number);
}

function options__show_main_section($sname,$optionlist) {
    if (is_array($optionlist) && count($optionlist)>0) {
        echo '<div class="orsee-panel orsee-option-section">';
        echo '<div class="orsee-panel-title"><div>'.$sname.'</div></div>';
        echo '<div class="orsee-option-links">';
        foreach ($optionlist as $oitem) {
            echo '<div class="orsee-option-link-item">'.$oitem.'</div>';
        }
        echo '</div></div>';
    }
}

function pform_options_selectfield($name,$array,$field,$id="") {
    global $editable_fields;
    $out='';
    if (in_array($name,$editable_fields)) {
        $out='<span class="select is-primary"><SELECT name="'.$name.'"';
        if ($id) {
            $out.=' id="'.$id.'"';
        }
        $out.='>';
        foreach ($array as $k=>$v) {
            if (is_int($k)) {
                $option_value=$v;
                $option_text=$v;
            } else {
                $option_value=$k;
                $option_text=$v;
            }
            $out.='<OPTION value="'.$option_value.'"';
            if ($field[$name]==$option_value) {
                $out.=' SELECTED';
            }
            $out.='>'.$option_text.'</OPTION>';
        }
        $out.='</SELECT></span>';
    } else {
        $out=$field[$name];
    }
    return $out;
}

function pform_options_checkboxrow($varname,$options,$selected_values=array()) {
    if (!is_array($options)) {
        $options=array();
    }
    if (!is_array($selected_values)) {
        $selected_values=array();
    }

    $selected=array();
    foreach ($selected_values as $selected_value) {
        $selected[]=(string)$selected_value;
    }

    $out='<div class="orsee-tag-picker-tags">';
    foreach ($options as $option_value=>$option_label) {
        $option_value=(string)$option_value;
        $out.='<label class="checkbox orsee-checkline">';
        $out.='<input type="checkbox" name="'.$varname.'[]" value="'.htmlspecialchars($option_value,ENT_QUOTES).'"';
        if (in_array($option_value,$selected,true)) {
            $out.=' checked';
        }
        $out.='> '.htmlspecialchars((string)$option_label,ENT_QUOTES);
        $out.='</label>';
    }
    $out.='</div>';
    return $out;
}



?>
