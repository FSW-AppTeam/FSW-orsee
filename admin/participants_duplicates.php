<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="participants";
$title="search_for_duplicates";
include("header.php");

if ($proceed) {
    $allow=check_allow('participants_duplicates','participants_main.php');
}
if ($proceed) {
    if (isset($_REQUEST['save_data'])) {
        redirect('admin/'.thisdoc());
    }
}

if ($proceed) {
    echo '<div class="orsee-panel">';
    show_message();

    if (isset($_REQUEST['search'])) {
        $pform_fields=participantform__load();
        $fields=array();
        foreach ($pform_fields as $f) {
            $fields[]=$f['mysql_column_name'];
        }
        $field_names=array();
        foreach ($pform_fields as $f) {
            $field_names[$f['mysql_column_name']]=participant__field_localized_text($f,'name_text_lang_json','name_lang');
        }

        // sanitize search_for
        $columns=array();
        if (isset($_REQUEST['search_for']) && is_array($_REQUEST['search_for'])) {
            foreach ($_REQUEST['search_for'] as $k=>$v) {
                if (in_array($k,$fields)) {
                    $columns[]=$k;
                }
            }
        }

        if (count($columns)==0) {
            message(lang('no_data_columns_selected'),'warning');
            redirect('admin/'.thisdoc());
        } else {
            $query="SELECT count(*) as num_matches, ".implode(', ',$columns)."
                    FROM ".table('participants')."
                    GROUP BY ".implode(', ',$columns)."
                    HAVING num_matches>1
                    ORDER BY num_matches DESC";
            $result=or_query($query);
            $dupvals=array();
            while ($line = pdo_fetch_assoc($result)) {
                $dupvals[]=$line;
            }
            if (check_allow('participants_edit')) {
                echo javascript__edit_popup();
            }
            $part_statuses=participant_status__get_statuses();
            $cols=participant__get_result_table_columns('result_table_search_duplicates');

            echo '<div class="orsee-table orsee-table-tablet-2rows orsee-table-mobile orsee-table-cells-compact">';
            echo '<div class="orsee-table-row orsee-table-head">';
            echo '<div class="orsee-table-cell"></div>';
            echo participant__get_result_table_headcells($cols,false);
            echo '</div>';
            foreach ($dupvals as $dv) {
                $mvals=array();
                $pars=array();
                $qclause=array();
                foreach ($columns as $c) {
                    $mvals[]=$field_names[$c].': '.$dv[$c];
                    $pars[':'.$c]=$dv[$c];
                    $qclause[]=' '.$c.' = :'.$c.' ';
                }
                echo '<div class="orsee-table-row">';
                echo '<div class="orsee-table-cell"><B>'.implode(", ",$mvals).'</B></div>';
                for ($i=0; $i<count($cols); $i++) {
                    echo '<div class="orsee-table-cell"></div>';
                }
                echo '</div>';
                $query="SELECT * FROM ".table('participants')."
                        WHERE ".implode(" AND ",$qclause)."
                        ORDER BY creation_time";
                $result=or_query($query,$pars);
                $shade=false;
                while ($p = pdo_fetch_assoc($result)) {
                    echo '<div class="orsee-table-row';
                    if ($shade) {
                        echo ' is-alt';
                    }
                    echo '">';
                    echo '<div class="orsee-table-cell"></div>';
                    echo participant__get_result_table_row($cols,$p);
                    echo '</div>';
                    if ($shade) {
                        $shade=false;
                    } else {
                        $shade=true;
                    }
                }
            }
            echo '</div>';
        }
    } else {
        $pform_fields=participantform__load();
        $field_names=array();
        foreach ($pform_fields as $f) {
            $field_names[$f['mysql_column_name']]=participant__field_localized_text($f,'name_text_lang_json','name_lang');
        }

        echo '<FORM action="participants_duplicates.php" method="GET">';
        echo '<div class="orsee-panel-title"><div class="orsee-panel-title-main">'.lang('search_duplicates_on_the_following_combined_characteristics').'</div><div class="orsee-panel-actions"></div></div>';
        echo '<div class="orsee-form-shell">';
        echo '<div class="orsee-form-row-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr)); row-gap: 0.35rem; column-gap: 0.75rem;">';
        $num_cols=4;
        $c=0;
        foreach ($field_names as $m=>$n) {
            $c++;
            echo '<div class="orsee-form-row-col"><label class="label" style="margin-bottom: 0;"><INPUT type="checkbox" name="search_for['.$m.']" value="y"> '.$n.'</label></div>';
        }
        echo '</div>';
        echo '<div class="orsee-options-actions-center" style="margin-top: 0.7rem;">
                <INPUT class="button orsee-btn" type="submit" name="search" value="'.lang('search').'">
                </div>';
        echo '</div>';
        echo '</FORM>';
    }
    echo '</div>';
}
include("footer.php");

?>
