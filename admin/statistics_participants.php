<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="statistics";
$title="subject_pool_statistics";
include("header.php");

if ($proceed) {
    $allow=check_allow('statistics_participants_show','statistics_main.php');
}

if ($proceed) {
    if (isset($_REQUEST['all']) && $_REQUEST['all']) {
        $all=true;
        $title_add=lang('for_all_profiles_in_database');
    } else {
        $all=false;
        $title_add=lang('for_active_subjects_in_pool');
    }

    $browsable=true;

    $restrict=array();
    if (isset($_REQUEST['restrict']) && array($_REQUEST['restrict'])) {
        $posted=$_REQUEST['restrict'];
        foreach ($posted as $s=>$valarr) {
            if (is_array($valarr)) {
                foreach ($valarr as $k=>$v) {
                    if ($v=='y') {
                        $restrict[$s][$k]=true;
                    }
                }
            }
        }
    }

    if ($all) {
        $condition=array();
    } else {
        $condition=array('clause'=>participant_status__get_pquery_snippet('eligible_for_experiments'),
                        'pars'=>array()
                        );
    }
    $stats_data=stats__get_data($condition,'stats',$restrict);
    $_SESSION['stats_data']=$stats_data;

    echo '<div class="orsee-options-list-panel">';

    if ($browsable) {
        echo '<FORM id="orsee-stats-filter-form" action="'.thisdoc().'" METHOD="GET">';
        echo '<INPUT type="hidden" name="all" value="'.urlencode($all).'">';
    }

    echo '<div class="orsee-panel orsee-option-section">';
    echo '<div class="orsee-panel-title">';
    echo '<div>'.$title_add.'</div>';
    echo '<div class="orsee-panel-actions">';
    if ($all) {
        echo button_link(thisdoc(),lang('stats_show_for_active'),'dot-circle-o');
    } else {
        echo button_link(thisdoc().'?all=true',lang('stats_show_for_all'),'circle-o','','id="orsee-stats-show-all-btn"');
    }
    echo '</div>';
    echo '</div>';
    if ($browsable) {
        echo '<div class="orsee-options-actions-center orsee-stat-actions">';
        echo '<button type="submit" class="button orsee-btn" name="filter" value="1">'.lang('apply_filter').'</button>';
        echo '</div>';
    }
    echo '</div>';

    foreach ($stats_data as $k=>$table) {
        if (isset($table['data']) && is_array($table['data']) && count($table['data'])>0) {
            $show=true;
        } else {
            $show=false;
        }
        if ($show) {
            $out=stats__stats_display_table($table,$browsable,$restrict);
            echo '<div class="orsee-panel orsee-option-section">';
            echo '<div class="orsee-panel-title"><div>'.$table['title'].'</div></div>';
            if ($table['charttype']=='none') {
                echo '<div style="width: min(100%, 860px); margin: 0 auto;">'.$out.'</div>';
            } else {
                echo '<div class="orsee-panel-split">';
                echo '<div class="orsee-panel-split-main">'.$out.'</div>';
                echo '<div class="orsee-panel-split-actions" style="text-align: center;">';
                echo '<img border="0" src="statistics_graph.php?stype='.$k.'" style="max-width: 100%; height: auto;">';
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
        }
    }

    if ($browsable) {
        echo '<div class="orsee-options-actions-center orsee-stat-actions">';
        echo '<button type="submit" class="button orsee-btn" name="filter" value="1">'.lang('apply_filter').'</button>';
        echo '</div>';
        echo '</FORM>';
        echo '<script type="text/javascript">
            (function() {
                // keep GET URLs short: submit only active (y) restrictions
                var form=document.getElementById("orsee-stats-filter-form");
                if (!form) return;
                form.addEventListener("submit",function(ev) {
                    ev.preventDefault();
                    var fd=new FormData(form);
                    if (ev.submitter && ev.submitter.name) {
                        fd.append(ev.submitter.name, ev.submitter.value);
                    }
                    var params=new URLSearchParams();
                    fd.forEach(function(value,key) {
                        if (key.indexOf("restrict[")===0 && value==="n") return;
                        params.append(key,value);
                    });
                    var target=form.action;
                    var qs=params.toString();
                    if (qs!=="") target+="?"+qs;
                    window.location.assign(target);
                });
            })();
            </script>';
    }

    echo '<div class="orsee-stat-actions">';
    echo button_back('statistics_main.php');
    echo '</div>';
    echo '</div>';
}
include("footer.php");

?>
