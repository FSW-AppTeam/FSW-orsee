<?php
// part of orsee. see orsee.org
ob_start();
$title="user_types_and_privileges";
$menu__area="options";
include("header.php");

if ($proceed) {
    $allow=check_allow('admin_type_edit','options_main.php');

    echo '<div class="orsee-options-list-panel">';
    echo '<div class="orsee-panel">';
    echo '<div class="orsee-options-actions-center orsee-stat-actions">';
    echo button_link('admin_type_edit.php?new=true',lang('create_new'),'plus-circle');
    echo '</div>';
    echo '<div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
    echo '<div class="orsee-table-row orsee-table-head">';
    echo '<div class="orsee-table-cell" style="min-width: 12rem;">'.lang('name').'</div>';
    echo '<div class="orsee-table-cell">'.lang('rights').'</div>';
    echo '<div class="orsee-table-cell"></div>';
    echo '</div>';

    $query="SELECT * FROM ".table('admin_types')." ORDER BY type_name";
    $result=or_query($query);

    $shade=false;
    while ($type=pdo_fetch_assoc($result)) {
        $num_show_rights=25;
        $rights_array=array();
        if (isset($type['rights']) && $type['rights']) {
            $rights_array=array_map('trim',explode(',',$type['rights']));
            $rights_array=array_filter($rights_array,function ($v) { return ($v!==''); });
            $rights_array=array_values($rights_array);
        }
        $num_rights=count($rights_array);
        $rights_show=array_slice($rights_array,0,$num_show_rights);
        $rights_text=implode(', ',$rights_show);
        if (!$rights_text) {
            $rights_text='-';
        } elseif ($num_rights>$num_show_rights) {
            $rights_text.=' ... and '.($num_rights-$num_show_rights).' more rights.';
        }

        $row_class='orsee-table-row';
        if ($shade) {
            $row_class.=' is-alt';
        }
        echo '<div class="'.$row_class.'">';
        echo '<div class="orsee-table-cell" data-label="'.lang('name').'" style="min-width: 12rem;">'.$type['type_name'].'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('rights').'" style="font-size: var(--font-size-compact); line-height: 1.05;">'.$rights_text.'</div>';
        echo '<div class="orsee-table-cell orsee-table-action" data-label="'.lang('action').'">'.
                button_link('admin_type_edit.php?type_id='.$type['type_id'],lang('edit'),'edit').
            '</div>';
        echo '</div>';

        if ($shade) {
            $shade=false;
        } else {
            $shade=true;
        }
    }

    echo '</div>';
    echo '<div class="orsee-stat-actions">';
    echo button_back('options_main.php');
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
include("footer.php");

?>
