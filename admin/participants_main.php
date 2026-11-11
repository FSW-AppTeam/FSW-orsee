<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="participants_main";
$title="participants";
include("header.php");

if ($proceed) {
    // participants summary

    echo '<div class="orsee-options-list-panel">';

    $exptypes=load_external_experiment_types();
    $pstatuses=participant_status__get_statuses();

    $query="SELECT count(*) as num_part, subscriptions, status_id
            FROM ".table('participants')."
            GROUP BY subscriptions, status_id";
    $result=or_query($query);
    $part_nums=array();
    while ($line = pdo_fetch_assoc($result)) {
        $etemp=db_string_to_id_array($line['subscriptions']);
        foreach ($etemp as $et) {
            if (!isset($part_nums[$et][$line['status_id']])) {
                $part_nums[$et][$line['status_id']]=0;
            }
            $part_nums[$et][$line['status_id']]=$part_nums[$et][$line['status_id']]+$line['num_part'];
        }
    }


    echo '<div class="orsee-panel orsee-option-section">';
    echo '<div class="orsee-panel-title"><div>'.participants__count_participants().' '.lang('xxx_participants_registered').'.</div></div>';
    echo '<div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile orsee-data-cols-participants-summary" style="--orsee-participant-status-cols: '.count($pstatuses).';">';
    echo '<div class="orsee-table-row orsee-table-head">';
    echo '<div class="orsee-table-cell"><span data-orsee-mobile="hide">'.lang('registered_for_xxx_experiments_xxx').'</span><span data-orsee-mobile="show">&nbsp;</span></div>';
    foreach ($pstatuses as $status_id=>$status) {
        $status_short=mb_substr($status['name'],0,6,'UTF-8');
        echo '<div class="orsee-table-cell"><span data-orsee-mobile="hide">'.$status['name'].'</span><span data-orsee-mobile="show">'.htmlspecialchars($status_short).'</span></div>';
    }
    echo '</div>';

    $first=true;
    foreach ($exptypes as $exptype_id=>$exptype) {
        $row_class='orsee-table-row';
        if ($first) {
            $row_class.=' is-alt';
            $first=false;
        }
        echo '<div class="'.$row_class.'">';
        echo '<div class="orsee-table-cell" data-label="">'.$exptype[lang('lang')].'</div>';
        foreach ($pstatuses as $status_id=>$status) {
            echo '<div class="orsee-table-cell" data-label="'.$status['name'].'">';
            if (isset($part_nums[$exptype_id][$status_id])) {
                echo $part_nums[$exptype_id][$status_id];
            } else {
                echo '0';
            }
            echo '</div>';
        }
        echo '</div>';
    }
    echo '</div>';
    echo '<div style="margin-top: 0.8rem;"></div>';
    echo '<div class="orsee-panel-split-main">';
    echo '<div class="orsee-stat-list">';

    echo '<div class="orsee-stat-row">';
    echo '<div class="orsee-stat-label">';
    if (check_allow('participants_unconfirmed_edit')) {
        echo '<A HREF="participants_unconfirmed.php">'.
                            lang('registered_but_not_confirmed_xxx').':</A>';
    } else {
        echo lang('registered_but_not_confirmed_xxx');
    }
    echo '</div>';
    echo '<div class="orsee-stat-value">'.
            participants__count_participants("status_id='0'").'</div>';
    echo '</div>';

    $now=time();
    $before=$now-(60*60*24*7*4);
    $tstring="status_id='0' AND creation_time < ".$before;
    echo '<div class="orsee-stat-row">';
    echo '<div class="orsee-stat-label">'.lang('from_this_older_than_4_weeks_xxx').':</div>';
    echo '<div class="orsee-stat-value">'.participants__count_participants($tstring).'</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '<div id="orsee-participants-main-actions" class="orsee-options-actions-center" style="margin-top: 0.8rem;">';
    if (check_allow('participants_show')) {
        echo button_link('participants_show.php?active=true',lang('edit_active_participants'),'list-alt');
        echo button_link('participants_show.php',lang('edit_all_participants'),'search');
    }
    if (check_allow('participants_edit')) {
        echo button_link('participants_edit.php',lang('add_participant'),'plus-circle');
    }
    if (check_allow('participants_duplicates')) {
        echo button_link('participants_duplicates.php',lang('search_for_duplicates'),'magnet');
    }
    echo '</div>';
    echo '</div>';
}
include("footer.php");

?>
