<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="my_rights";
include("header.php");

if ($proceed) {
    echo '<div class="orsee-options-list-panel">';
    echo '<div class="orsee-panel">';
    $rights=$expadmindata['rights'];
    echo '<div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile orsee-table-body-cells-compact">';
    echo '<div class="orsee-table-row orsee-table-head">';
    echo '<div class="orsee-table-cell">'.lang('authorization').'</div>';
    echo '<div class="orsee-table-cell">'.lang('description').'</div>';
    echo '</div>';

    $shade=true;
    $lastclass="";
    foreach ($system__admin_rights as $right) {
        $line=explode(":",$right);
        if (isset($rights[$line[0]]) && $rights[$line[0]]) {
            $tclass=str_replace(strstr($line[0],"_"),"",$line[0]);
            if ($tclass!=$lastclass) {
                if ($lastclass!=="") {
                    echo '<div class="orsee-table-row orsee-table-row-spacer">';
                    echo '<div class="orsee-table-cell"></div>';
                    echo '<div class="orsee-table-cell"></div>';
                    echo '</div>';
                }
                $lastclass=$tclass;
            }
            $row_class='orsee-table-row';
            if ($shade) {
                $row_class.=' is-alt';
            }
            echo '<div class="'.$row_class.'">';
            echo '<div class="orsee-table-cell" data-label="'.lang('authorization').'">'.$line[0].'</div>';
            echo '<div class="orsee-table-cell" data-label="'.lang('description').'">'.$line[1].'</div>';
            echo '</div>';
            if ($shade) {
                $shade=false;
            } else {
                $shade=true;
            }
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
