<?php
// part of orsee. see orsee.org

function survey__render_field($field) {
    $out='';
    $compact=(isset($field['compact']) && $field['compact']===true);
    switch ($field['type']) {
        case 'textline': $out=survey__render_textline($field);
            break;
        case 'textarea': $out=survey__render_textarea($field);
            break;
        case 'radioline': $out=survey__render_radioline($field);
            break;
        case 'select_list': $out=survey__render_select_list($field,'',$compact);
            break;
        case 'select_numbers': $out=survey__render_select_numbers($field,$compact);
            break;
        case 'select_yesno': $out=survey__render_select_yesno($field,$compact);
            break;
        case 'select_yesno_switchy': $out=survey__render_select_yesno_switchy($field);
            break;
        case 'date': $out=survey__render_date($field);
            break;
    }
    return $out;
}

function survey__render_textline($f) {
    $dir_attr=(isset($f['force_ltr']) && $f['force_ltr']==='y') ? ' dir="ltr"' : '';
    $out='<INPUT type="text" name="'.$f['submitvarname'].'" value="'.$f['value'].'" size="'.
        $f['size'].'" maxlength="'.$f['maxlength'].'"'.$dir_attr.'>';
    return $out;
}

function survey__render_textarea($f) {
    $out='<textarea name="'.$f['submitvarname'].'" cols="'.$f['cols'].'" rows="'.
            $f['rows'].'" wrap="'.$f['wrap'].'">'.$f['value'].'</textarea>';
    return $out;
}

function survey__render_radioline($f) {
    global $lang;
    $optionvalues=explode(",",$f['option_values']);
    $optionnames=explode(",",$f['option_values_lang']);
    $items=array();
    foreach ($optionvalues as $k=>$v) {
        if (isset($optionnames[$k])) {
            $items[$v]=$optionnames[$k];
        }
    }
    $out='';
    foreach ($items as $val=>$text) {
        $out.='<INPUT name="'.$f['submitvarname'].'" type="radio" value="'.$val.'"';
        if ($f['value']==$val) {
            $out.=" CHECKED";
        }
        $out.='>';
        if (isset($lang[$text])) {
            $out.=$lang[$text];
        } else {
            $out.=$text;
        }
        $out.='&nbsp;&nbsp;&nbsp;';
    }
    return $out;
}

function survey__render_select_list($f,$formfieldvarname='',$compact=false) {
    global $lang;
    if (!$formfieldvarname) {
        $formfieldvarname=$f['submitvarname'];
    }
    $optionvalues=explode(",",$f['option_values']);
    $optionnames=explode(",",$f['option_values_lang']);
    if ($f['include_none_option']=='y') {
        $incnone=true;
    } else {
        $incnone=false;
    }
    $items=array();
    foreach ($optionvalues as $k=>$v) {
        if (isset($optionnames[$k])) {
            $items[$v]=$optionnames[$k];
        }
    }
    $out='';
    $select_wrapper_class='select is-primary';
    if ($compact) {
        $select_wrapper_class.=' select-compact';
    }
    $out='<span class="'.$select_wrapper_class.'">'.helpers__select_text($items,$formfieldvarname,$f['value'],$incnone).'</span>';
    return $out;
}

function survey__render_select_numbers($f,$compact=false) {
    if ($f['include_none_option']=='y') {
        $incnone=true;
    } else {
        $incnone=false;
    }
    if ($f['values_reverse']=='y') {
        $reverse=true;
    } else {
        $reverse=false;
    }
    $out=participant__select_numbers($f['submitvarname'],$f['submitvarname'],$f['value'],$f['value_begin'],$f['value_end'],0,$f['value_step'],$reverse,$incnone,false,'',false,$compact);
    return $out;
}

function survey__render_select_yesno($f,$compact=false) {
    global $lang;
    $items=array('y'=>'y','n'=>'n');
    if ($f['include_none_option']=='y') {
        $incnone=true;
    } else {
        $incnone=false;
    }
    $out='';
    $select_wrapper_class='select is-primary';
    if ($compact) {
        $select_wrapper_class.=' select-compact';
    }
    $out='<span class="'.$select_wrapper_class.'">'.helpers__select_text($items,$f['submitvarname'],$f['value'],$incnone).'</span>';
    return $out;
}

function survey__render_select_yesno_switchy($f) {
    global $lang;

    $items=array('n'=>'n','y'=>'y');

    $id=uniqid();
    $out='';
    $out='<select data-elem-name="yesnoswitch" id="id'.$id.'" name="'.$f['submitvarname'].'">';
    foreach ($items as $k=>$text) {
        $out.='<option value="'.$k.'"';
        if ($k == $f['value']) {
            $out.=' SELECTED';
        }
        $out.='>';
        $out.=lang($text);
        $out.='</option>
        ';
    }
    $out.='</select>';

    return $out;
}

function survey__render_date($f,$formfieldvarname='') {
    global $lang;
    if (!$formfieldvarname) {
        $formfieldvarname=$f['submitvarname'];
    }
    if (preg_match('/([^\[]+)\[([^\[\]]+)\]/', $formfieldvarname, $matches)) {
        $formfieldvarname=$matches[1]."__".$matches[2];
    }
    $out='';
    $out=formhelpers__pick_date($formfieldvarname,$f['value']);
    return $out;
}

?>
