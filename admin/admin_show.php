<?php
// part of orsee. see orsee.org
ob_start();
$title="user_management";
$menu__area="options";
include("header.php");

if ($proceed) {
    $allow=check_allow('admin_edit','options_main.php');
    if (check_allow('admin_edit')) {
        if (isset($_REQUEST['change']) && $_REQUEST['change']) {
            if (!csrf__validate_request_message()) {
                redirect("admin/admin_show.php");
            }
            if (isset($_REQUEST['disabled']) && is_array($_REQUEST['disabled'])) {
                $pars=array();
                foreach ($_REQUEST['disabled'] as $a=>$d) {
                    $pars[]=array(':a'=>$a,':d'=>$d);
                }
                $query="UPDATE ".table("admin")."
                        SET disabled= :d
                        WHERE admin_id= :a";
                $done=or_query($query,$pars);
            }
            if (isset($_REQUEST['experimenter_list']) && is_array($_REQUEST['experimenter_list'])) {
                $pars=array();
                foreach ($_REQUEST['experimenter_list'] as $a=>$d) {
                    $pars[]=array(':a'=>$a,':d'=>$d);
                }
                $query="UPDATE ".table("admin")."
                        SET experimenter_list= :d
                        WHERE admin_id= :a";
                $done=or_query($query,$pars);
            }
            if (isset($_REQUEST['admin_type']) && is_array($_REQUEST['admin_type'])) {
                $pars=array();
                foreach ($_REQUEST['admin_type'] as $a=>$d) {
                    $pars[]=array(':a'=>$a,':d'=>$d);
                }
                $query="UPDATE ".table("admin")."
                        SET admin_type= :d
                        WHERE admin_id= :a";
                $done=or_query($query,$pars);
            }
            log__admin("admin_show_edit");
            message(lang('changes_saved'));
            redirect("admin/admin_show.php");
            $proceed=false;
        }
    }
}

if ($proceed) {
    $can_edit=check_allow('admin_edit');

    show_message();

    echo '<form action="'.thisdoc().'" method="POST">';
    echo csrf__field();
    echo '<div class="orsee-panel">';
    echo '<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; align-items: center; margin-bottom: 0.42rem;">';
    echo '<div></div>';
    echo '<div style="text-align: center;">';
    if ($can_edit) {
        echo '<input name="change" type="submit" class="button orsee-btn" value="'.lang('save_changes_in_list').'">';
    }
    echo '</div>';
    echo '<div style="text-align: end;">'.button_link('admin_edit.php?new=true',lang('create_new'),'plus-circle').'</div>';
    echo '</div>';

    echo '<div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
    echo '<div class="orsee-table-row orsee-table-head">';
    echo '<div class="orsee-table-cell">'.lang('firstname').'</div>';
    echo '<div class="orsee-table-cell">'.lang('lastname').'</div>';
    echo '<div class="orsee-table-cell">'.lang('username').'</div>';
    echo '<div class="orsee-table-cell">'.lang('type').'</div>';
    echo '<div class="orsee-table-cell">'.lang('is_experimenter').'</div>';
    echo '<div class="orsee-table-cell">'.lang('account').'</div>';
    echo '<div class="orsee-table-cell">'.lang('action').'</div>';
    echo '</div>';

    $query="SELECT * FROM ".table('admin')."
            ORDER BY disabled, lname, fname";
    $result=or_query($query);

    $enabled_emails=array();
    $emails=array();
    $shade=false;
    while ($admin=pdo_fetch_assoc($result)) {
        if ($admin['email']) {
            $emails[]=$admin['email'];
            if ($admin['disabled']=='n') {
                $enabled_emails[]=$admin['email'];
            }
        }

        $row_class='orsee-table-row';
        if ($shade) {
            $row_class.=' is-alt';
            $shade=false;
        } else {
            $shade=true;
        }
        if ($admin['disabled']=='y') {
            $row_class.=' orsee-table-row-disabled';
        }

        echo '<div class="'.$row_class.'">';
        echo '<div class="orsee-table-cell" data-label="'.lang('firstname').'">'.$admin['fname'].'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('lastname').'">'.$admin['lname'].'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('username').'">'.$admin['adminname'].'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('type').'">';
        if ($can_edit) {
            echo admin__select_admin_type('admin_type['.$admin['admin_id'].']',$admin['admin_type']);
        } else {
            echo $admin['admin_type'];
        }
        echo '</div>';

        echo '<div class="orsee-table-cell" data-label="'.lang('is_experimenter').'">';
        if ($can_edit) {
            echo '<input name="experimenter_list['.$admin['admin_id'].']" type="radio" value="y"';
            if ($admin['experimenter_list']=='y') {
                echo ' checked';
            }
            echo '>'.lang('yes').'&nbsp;&nbsp;';
            echo '<input name="experimenter_list['.$admin['admin_id'].']" type="radio" value="n"';
            if ($admin['experimenter_list']!='y') {
                echo ' checked';
            }
            echo '>'.lang('no');
        } else {
            if ($admin['experimenter_list']=='n') {
                echo lang('n');
            } else {
                echo lang('y');
            }
        }
        echo '</div>';

        echo '<div class="orsee-table-cell" data-label="'.lang('account').'">';
        if ($can_edit) {
            echo '<input name="disabled['.$admin['admin_id'].']" type="radio" value="n"';
            if ($admin['disabled']!='y') {
                echo ' checked';
            }
            echo '>'.lang('account_enabled').'&nbsp;&nbsp;';
            echo '<input name="disabled['.$admin['admin_id'].']" type="radio" value="y"';
            if ($admin['disabled']=='y') {
                echo ' checked';
            }
            echo '>'.lang('account_disabled');
        } else {
            if ($admin['disabled']!='y') {
                echo lang('account_enabled');
            } else {
                echo lang('account_disabled');
            }
        }
        echo '</div>';

        echo '<div class="orsee-table-cell orsee-table-action" data-label="'.lang('action').'">';
        echo button_link('admin_edit.php?admin_id='.$admin['admin_id'],lang('edit'),'pencil-square-o');
        echo '</div>';
        echo '</div>';
    }

    echo '</div>';
    if ($can_edit) {
        echo '<div class="orsee-options-actions-center orsee-options-actions">';
        echo '<input name="change" type="submit" class="button orsee-btn" value="'.lang('save_changes_in_list').'">';
        echo '</div>';
    }
    echo '<div class="orsee-options-actions-center orsee-options-actions">';
    echo '<a href="mailto:'.$settings['support_mail'].'?bcc='.implode(",",$enabled_emails).'">'.lang('write_message_to_all_enabled_admins').'</a>';
    echo '</div>';
    echo '<div class="orsee-options-actions-center orsee-options-actions">';
    echo '<a href="mailto:'.$settings['support_mail'].'?bcc='.implode(",",$emails).'">'.lang('write_message_to_all_listed').'</a>';
    echo '</div>';
    echo '<div class="orsee-options-actions">'.button_back('options_main.php').'</div>';
    echo '</div>';
    echo '</form>';
}
include("footer.php");

?>
