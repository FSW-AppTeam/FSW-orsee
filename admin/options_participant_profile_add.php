<?php
// part of orsee. see orsee.org
ob_start();
$title="create_new_mysql_table_column";
$menu__area="options";
include("header.php");

if ($proceed) {
    $allow=check_allow('pform_config_field_add','options_participant_profile.php');
}

if ($proceed) {
    $type_specs=array(1=>array('spec'=>'varchar(250)','fullspec'=>"varchar(250) collate utf8_unicode_ci default ''"),
                    2=>array('spec'=>'mediumtext','fullspec'=>'mediumtext collate utf8_unicode_ci'),
                    3=>array('spec'=>'integer','fullspec'=>'bigint(30) default NULL'));
    $index_specs=array(1=>'#name#_index (#name#)',
                    2=>'#name#_index (#name#(250))',
                    3=>'#name#_index (#name#)');

    if (isset($_REQUEST['create']) && $_REQUEST['create']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        } else {
            $continue=true;
            $name=trim((string)$_REQUEST['mysql_column_name']);
            $coltype=(isset($_REQUEST['mysql_column_type']) ? trim((string)$_REQUEST['mysql_column_type']) : '1');
            if ($continue) {
                if ($name==='') {
                    $continue=false;
                    message(lang('error_missing_mysql_column_name'),'error');
                }
            }
            if ($continue) {
                if (!preg_match("/^[a-z][a-z_]+[a-z]$/",$name)) {
                    $continue=false;
                    message(lang('error_column_name_does_not_match_requirements').': <b>'.htmlspecialchars((string)$name,ENT_QUOTES,'UTF-8').'</b>', 'error');
                }
            }
            if ($continue) {
                $user_columns=participant__userdefined_columns();
                if (isset($user_columns[$name])) {
                    $continue=false;
                    message(lang('error_column_of_this_name_exists').': <b>'.htmlspecialchars((string)$name,ENT_QUOTES,'UTF-8').'</b>', 'error');
                }
            }
            if ($continue) {
                $pars=array(':content_type'=>$name);
                $query="SELECT count(*) AS ct
                    FROM ".table('lang')."
                    WHERE content_type=:content_type";
                $line=orsee_query($query,$pars);
                if ((int)$line['ct']>0) {
                    $continue=false;
                    message(lang('error_mysql_column_name_conflicts_with_lang_content_type').': <b>'.htmlspecialchars((string)$name,ENT_QUOTES,'UTF-8').'</b>', 'error');
                }
            }
            if ($continue) {
                if ($coltype==='3') {
                    $ttypespec=$type_specs[3]['fullspec'];
                    $tindexspec=$index_specs[3];
                } elseif ($coltype==='2') {
                    $ttypespec=$type_specs[2]['fullspec'];
                    $tindexspec=$index_specs[2];
                } else {
                    $ttypespec=$type_specs[1]['fullspec'];
                    $tindexspec=$index_specs[1];
                }

                $create_query="ALTER TABLE ".table('participants')."
                            ADD COLUMN ".$name." ".$ttypespec.",
                            ADD INDEX ".str_replace("#name#",$name,$tindexspec);
                $done=or_query($create_query);
                if ($done) {
                    message(lang('mysql_column_created'));
                    redirect('admin/options_participant_profile.php');
                } else {
                    message(lang('database_error'), 'error');
                }
            }
        }
    }
}


if ($proceed) {
    if (isset($_REQUEST['mysql_column_name'])) {
        $mysql_column_name=trim($_REQUEST['mysql_column_name']);
    } else {
        $mysql_column_name='';
    }

    if (isset($_REQUEST['mysql_column_type'])) {
        $mysql_column_type=trim($_REQUEST['mysql_column_type']);
    } else {
        $mysql_column_type=1;
    }


    javascript__tooltip_prepare();
    show_message();

    echo '<div class="orsee-panel">
            <div class="orsee-panel-title"><div>'.lang($title).'</div></div>
            <div class="orsee-form-shell">
                <form id="columnform" action="'.thisdoc().'" method="POST">
                    '.csrf__field().'
                    <div class="field tooltip" title="Name of the new MySQL column. Name must start and end with a lowercase letter, and can only contain lower case letters and underscore (_).">
                        <label class="label">'.lang('mysql_column_name').' (a-z_):</label>
                        <div class="control">
                            <input class="input is-primary orsee-input orsee-input-text" type="text" name="mysql_column_name" dir="ltr" size="30" maxlength="50" value="'.htmlspecialchars((string)$mysql_column_name,ENT_QUOTES).'">
                        </div>
                    </div>
                    <div class="field tooltip" title="Type of the new MySQL column. &quot;varchar(250)&quot; is the most versatile type
                            for numbers or shorter text. Must be chosen for &quot;select_lang&quot; and &quot;radioline_lang&quot; lists.
                            If the field needs to store longer text, then &quot;mediumtext&quot; might be appropriate.
                            &quot;integer&quot; can be chosen if the field will only hold integer numbers (but &quot;varchar(250)&quot;
                            is recommended also in this case).">
                        <label class="label">'.lang('profile_editor_mysql_column_type').':</label>
                        <div class="control">
                            <span class="select is-primary"><select name="mysql_column_type">';
    foreach ($type_specs as $k=>$arr) {
        echo '<option value="'.$k.'"';
        if ($k==$mysql_column_type) {
            echo ' SELECTED';
        }
        echo '>'.$arr['spec'].'</option>';
    }
    echo                    '</select></span>
                        </div>
                    </div>
                    <div class="orsee-options-actions-center">
                        <p id="submit_message"></p>
                        <input class="button orsee-btn" type="submit" name="create" value="'.lang('create_column').'">
                    </div>
                </form>
                <div class="orsee-options-actions">
                    '.button_back('options_participant_profile.php').'
                </div>
            </div>
        </div>';

    echo '<script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function () {
                var form = document.getElementById("columnform");
                var submitMessage = document.getElementById("submit_message");
                if (!form) return;
                form.addEventListener("submit", function (event) {
                    if (form.dataset.isSubmitted === "1") {
                        event.preventDefault();
                        return false;
                    }
                    var createMarker = form.querySelector("input[type=hidden][name=create]");
                    if (!createMarker) {
                        createMarker = document.createElement("input");
                        createMarker.type = "hidden";
                        createMarker.name = "create";
                        form.appendChild(createMarker);
                    }
                    createMarker.value = "1";
                    var submitButtons = form.querySelectorAll("input[type=submit]");
                    submitButtons.forEach(function (button) {
                        button.setAttribute("disabled", "disabled");
                    });
                    form.dataset.isSubmitted = "1";
                    if (submitMessage) submitMessage.textContent = "Creating ...";
                    return true;
                });
            });
        </script>';
}
include("footer.php");

?>
