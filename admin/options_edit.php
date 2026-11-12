<?php
// part of orsee. see orsee.org
ob_start();
$title="";
if (isset($_REQUEST['otype']) && $_REQUEST['otype']) {
    if ($_REQUEST['otype']=="general") {
        $title='edit_general_settings';
    } elseif ($_REQUEST['otype']=="default") {
        $title='edit_default_values';
    }
}
$js_modules=array('switchy','flatpickr');
$menu__area="options";
include("header.php");

if ($proceed) {
    $allow=check_allow('settings_view','options_main.php');
}

if ($proceed) {
    if (isset($_REQUEST['otype']) && $_REQUEST['otype'] && in_array($_REQUEST['otype'],array('general','default'))) {
        $otype=$_REQUEST['otype'];
    } else {
        $otype="";
        redirect("admin/options_main.php");
    }

    if ($otype=='general') {
        $opts=$system__options_general;
    } else {
        $opts=$system__options_defaults;
    }

    echo '<div class="orsee-options-general-edit-wrap">';

    $pars=array(':type'=>$otype);
    $query="select * from ".table('options')."
            where option_type= :type
            order by option_name";
    $result=or_query($query,$pars);
    $options=array();
    while ($line=pdo_fetch_assoc($result)) {
        $options[$line['option_name']]=$line['option_value'];
    }

    if (check_allow('settings_edit') && isset($_REQUEST['change']) && $_REQUEST['change']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        }
    }
}

if ($proceed) {
    if (check_allow('settings_edit') && isset($_REQUEST['change']) && $_REQUEST['change']) {
        $newoptions=$_REQUEST['options'];
        $now=time();

        // add and process option values which may be differently submitted
        foreach ($opts as $o) {
            if ($o['type']=='date') {
                $date_value=ortime__array_to_sesstime($_REQUEST,'options__'.$o['option_name'].'_');
                if ((int)$date_value<=0) {
                    $date_value=0;
                }
                $newoptions[$o['option_name']]=$date_value;
            }
        }

        $pars_new=array();
        $pars_update=array();
        foreach ($newoptions as $oname => $ovalue) {
            if (isset($options[$oname])) {
                $pars_update[]=array(':value'=>$ovalue,
                                    ':name'=>$oname,
                                    ':type'=>$otype);
            } else {
                $pars_new[]=array(':value'=>$ovalue,
                                    ':name'=>$oname,
                                    ':type'=>$otype,
                                    ':now'=>$now);
                $now++;
            }
        }
        if (count($pars_update)>0) {
            $query="UPDATE ".table('options')."
                    SET option_value= :value
                    WHERE option_name= :name
                    AND option_type= :type";
            $done=or_query($query,$pars_update);
        }
        if (count($pars_new)>0) {
            $query="INSERT INTO ".table('options')." SET
                option_id= :now,
                option_name= :name,
                option_value= :value,
                option_type= :type";
            $done=or_query($query,$pars_new);
        }
        message(lang('changes_saved'));
        log__admin("options_edit","type:".$otype);
        redirect('admin/options_edit.php?otype='.$otype);
    }
}

if ($proceed) {
    if (check_allow('settings_edit')) {
        echo '<FORM action="options_edit.php" method="post">';
        echo csrf__field();
        echo '<INPUT type="hidden" name="otype" value="'.$otype.'">';
    }

    echo '<div class="orsee-panel">';
    $GLOBALS['orsee_options_render_mode']='div';
    if (check_allow('settings_edit')) {
        echo '<div class="orsee-options-actions-center orsee-options-actions"><INPUT class="button orsee-btn" type="submit" name="change" value="'.lang('change').'"></div>';
    }

    echo '<div class="orsee-options-edit-list">';
    options__render_grouped_options($opts,false);
    echo '</div>';

    if (check_allow('settings_edit')) {
        echo '<div class="orsee-options-actions-center orsee-options-actions"><INPUT class="button orsee-btn" type="submit" name="change" value="'.lang('change').'"></div>';
    }
    echo '<div class="orsee-options-actions">'.button_back('options_main.php').'</div>';
    unset($GLOBALS['orsee_options_render_mode']);
    echo '</div>';
    if (check_allow('settings_edit')) {
        echo '</FORM>';
    }
    echo '</div>';

    if (!check_allow('settings_edit')) {
        echo '<script type="text/javascript">
            (function() {
                var allInputs=document.querySelectorAll("input, select, textarea, button");
                allInputs.forEach(function(el) { el.disabled=true; });
            })();
            </script>';
    }
}

include("footer.php");

?>
