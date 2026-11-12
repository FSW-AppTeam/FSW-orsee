<?php
// part of orsee. see orsee.org
ob_start();
$title="edit_colors";
$include_coloris=true;
$menu__area="options";
include("header.php");

if ($proceed) {
    $allow=check_allow('settings_view_colors','options_main.php');
}

if ($proceed) {
    $color_styles=options__get_color_styles();
    $styles=options__get_styles();
}


if ($proceed) {
    if (isset($_REQUEST['style']) && $_REQUEST['style']) {
        $style=trim($_REQUEST['style']);
    } else {
        $style="";
    }

    if (!$style && isset($settings['style']) && $settings['style']) {
        $style=$settings['style'];
    }

    if (!$style && isset($settings['orsee_admin_style']) && $settings['orsee_admin_style']) {
        $style=$settings['orsee_admin_style'];
    }

    if ($style && !in_array($style,explode(',',$styles))) {
        $style='orsee';
    }

    if (!$style) {
        if (isset($color_styles[0]) && $color_styles[0]) {
            $style=$color_styles[0];
        } else {
            $style='orsee';
        }
    }
}


if ($proceed) {
    $styles_array=array_filter(array_map('trim',explode(',',$styles)));
    $pars=array(':style'=>$style);
    $query="select * from ".table('options')."
            where option_type='color'
            and option_style= :style
            order by option_name";
    $result=or_query($query,$pars);
    $dbcolors=array();
    while ($line=pdo_fetch_assoc($result)) {
        $dbcolors[$line['option_name']]=$line['option_value'];
    }
    $mycolors=load_colors($style);

    if (isset($_REQUEST['export_colors_json']) && (string)$_REQUEST['export_colors_json']==='1') {
        $export_colors=load_colors($style);
        $json=json_encode($export_colors,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
        if ($json===false) {
            $json='{}';
        }
        while (ob_get_level()>0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="colors.json"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $json;
        exit;
    }

    if (check_allow('settings_edit_colors') && isset($_REQUEST['change']) && $_REQUEST['change']) {
        if (!csrf__validate_request_message()) {
            redirect('admin/options_colors.php?style='.$style);
        }
        $newcolors=$_REQUEST['mycolors'];
        $now=time();
        $pars_new=array();
        $pars_update=array();
        $pars_delete=array();
        foreach ($newcolors as $oname => $ovalue) {
            if (trim((string)$ovalue)==='') {
                if (isset($dbcolors[$oname])) {
                    $pars_delete[]=array(
                        ':name'=>$oname,
                        ':style'=>$style
                    );
                }
            } elseif (isset($dbcolors[$oname])) {
                $pars_update[]=array(':value'=>$ovalue,
                                    ':name'=>$oname,
                                    ':style'=>$style);
            } else {
                $pars_new[]=array(':value'=>$ovalue,
                                    ':name'=>$oname,
                                    ':style'=>$style,
                                    ':now'=>$now);
                $now++;
            }
        }
        if (count($pars_update)>0) {
            $query="UPDATE ".table('options')."
                    SET option_value= :value
                    WHERE option_name= :name
                    AND option_style= :style
                    AND option_type= 'color'";
            $done=or_query($query,$pars_update);
        }
        if (count($pars_new)>0) {
            $query="INSERT INTO ".table('options')." SET
                option_id= :now,
                option_name= :name,
                option_value= :value,
                option_style= :style,
                option_type= 'color'";
            $done=or_query($query,$pars_new);
        }
        if (count($pars_delete)>0) {
            $query="DELETE FROM ".table('options')."
                    WHERE option_name= :name
                    AND option_style= :style
                    AND option_type= 'color'";
            $done=or_query($query,$pars_delete);
        }
        message(lang('changes_saved'));
        log__admin("options_colors_edit","style:".$style);
        redirect('admin/options_colors.php?style='.$style);
    }
}

if ($proceed) {
    show_message();
    echo '<div class="orsee-options-list-panel">';
    echo '<div class="orsee-panel">';
    echo '<div class="orsee-options-colors-style-row">';
    echo '<FORM id="styleform" action="'.thisdoc().'" method="GET" class="orsee-options-colors-style-form">';
    echo '<label class="label">'.lang('edit_colors_for_style').'</label>';
    echo '<span class="select is-primary select-compact"><select name="style">';
    foreach ($styles_array as $style_item) {
        echo '<option value="'.htmlspecialchars($style_item,ENT_QUOTES).'"';
        if ($style_item==$style) {
            echo ' selected';
        }
        echo '>'.htmlspecialchars($style_item,ENT_QUOTES).'</option>';
    }
    echo '</select></span>';
    echo '<INPUT class="button orsee-btn" type="submit" value="'.lang('apply').'">';
    echo '<button class="button orsee-btn" type="submit" name="export_colors_json" value="1">'.lang('export_color_scheme').'</button>';
    echo '</FORM>';
    echo '</div>';

    if (check_allow('settings_edit_colors')) {
        echo '<FORM action="options_colors.php" method="post">';
        echo csrf__field();
        echo '<INPUT type="hidden" name="style" value="'.$style.'">';
    }

    $GLOBALS['orsee_options_render_mode']='div';
    if (check_allow('settings_edit_colors')) {
        echo '<div class="orsee-options-actions-center orsee-options-actions"><INPUT class="button orsee-btn" type="submit" name="change" value="'.lang('change').'"></div>';
    }

    echo '<div class="orsee-options-edit-list">';
    options__render_grouped_options($system__colors,true);
    echo '</div>';

    if (check_allow('settings_edit_colors')) {
        echo '<div class="orsee-options-actions-center orsee-options-actions"><INPUT class="button orsee-btn" type="submit" name="change" value="'.lang('change').'"></div>';
    }
    echo '<div class="orsee-options-actions">'.button_back('options_main.php').'</div>';
    unset($GLOBALS['orsee_options_render_mode']);

    if (check_allow('settings_edit_colors')) {
        echo '</FORM>';
    }

    echo "<script type='text/javascript'>
            (function() {
                var inputs = document.querySelectorAll('.colorpickerinput');
                if (typeof Coloris === 'function') {
                    Coloris({
                        el: '.colorpickerinput',
                        theme: 'large',
                        themeMode: 'dark',
                        format: 'hex',
                        alpha: false,
                        closeButton: true,
                        clearButton: false
                    });
                }
            })();";

    if (!check_allow('settings_edit_colors')) {
        echo "
            (function() {
                var allInputs = document.querySelectorAll('input, select, textarea, button');
                allInputs.forEach(function(el) { el.disabled = true; });
                var styleFormInputs = document.querySelectorAll('#styleform input, #styleform select, #styleform textarea, #styleform button');
                styleFormInputs.forEach(function(el) { el.disabled = false; });
            })();
            ";
    }

    echo "</script>";
    echo '</div>';
    echo '</div>';
}
include("footer.php");

?>
