<?php
// part of orsee. see orsee.org


// current experiments summary
function experiment__current_experiment_summary($experimenter="",$finished="n",$show_filter=false,$addbutton=true) {
    global $lang, $expadmindata, $color;

    $experimentclasses=experiment__load_experimentclassnames();
    $experimenters=experiment__load_experimenters();

    $pars=array();
    $experimenter_arr=array();
    if (isset($_REQUEST['experimenter_search']) && $_REQUEST['experimenter_search']) {
        $experimenter_arr=multipicker_json_to_array($_REQUEST['experimenter_search']);
    }
    if ($experimenter && count($experimenter_arr)==0) {
        $experimenter_arr=array($experimenter);
    }
    $exp_clause=query__get_experimenter_or_clause($experimenter_arr);
    if ($exp_clause['clause']) {
        $expq=' AND '.$exp_clause['clause'];
        foreach ($exp_clause['pars'] as $k=>$v) {
            $pars[$k]=$v;
        }
    } else {
        $expq="";
    }

    $class_arr=array();
    if (isset($_REQUEST['class_search']) && $_REQUEST['class_search']) {
        $class_arr=multipicker_json_to_array($_REQUEST['class_search']);
    }
    $class_clause=query__get_class_or_clause($class_arr);
    if ($class_clause['clause']) {
        $classq=' AND '.$class_clause['clause'];
        foreach ($class_clause['pars'] as $k=>$v) {
            $pars[$k]=$v;
        }
    } else {
        $classq="";
    }

    $finq=" ".table('experiments').".experiment_finished= :finished";
    $pars[':finished']=$finished;

    $aquery=$finq.$expq.$classq;

    $query="SELECT ".table('experiments').".*,
                (SELECT count(*) from ".table('sessions')." as s1 WHERE s1.experiment_id=".table('experiments').".experiment_id) as num_sessions,
                if((SELECT count(*) from ".table('sessions')." as s2 WHERE s2.experiment_id=".table('experiments').".experiment_id)=0,1,0) as no_sessions,
                (SELECT min(if(session_start > date_format(now(),'%Y%m%d%H%i'),session_start,NULL)) from ".table('sessions')." as s3 WHERE s3.experiment_id=".table('experiments').".experiment_id) as time,
                (SELECT min(session_start) from ".table('sessions')." as s4 WHERE s4.experiment_id=".table('experiments').".experiment_id) as first_session_date,
                (SELECT max(session_start) from ".table('sessions')." as s5 WHERE s5.experiment_id=".table('experiments').".experiment_id) as last_session_date 
                FROM ".table('experiments')."
                WHERE ".table('experiments').".experiment_id IS NOT NULL
                AND ".$aquery."
                ORDER BY no_sessions, time, last_session_date DESC, experiment_id";
    $result=or_query($query,$pars);
    $experiments=array();
    $eids=array();
    while ($line=pdo_fetch_assoc($result)) {
        $line['sessions']=array();
        $experiments[$line['experiment_id']]=$line;
        $eids[]=$line['experiment_id'];
    }

    if (count($eids)>0) {
        $query="SELECT *
                    FROM ".table('sessions')."
                    WHERE (session_status='planned' OR session_status='live')
                    AND experiment_id IN (".
                implode(',',$eids).")
                    ORDER BY session_start, session_id";
        $result=or_query($query);
        $sids=array();
        while ($line=pdo_fetch_assoc($result)) {
            $experiments[$line['experiment_id']]['sessions'][$line['session_id']]=$line;
            $sids[]=$line['session_id'];
        }

        // get counts at experiment level
        // performance is better if doing this separately
        $query="SELECT experiment_id,
                    count(*) as num_assigned
                    FROM ".table('participate_at')."
                    WHERE experiment_id IN (".
                implode(',',$eids).")
                    GROUP BY experiment_id";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            $experiments[$line['experiment_id']]['num_assigned']=$line['num_assigned'];
        }

        $query="SELECT experiment_id,
                    count(*) as num_registered
                    FROM ".table('participate_at')."
                    WHERE session_id!=0
                    AND experiment_id IN (".
                implode(',',$eids).")
                    GROUP BY experiment_id";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            $experiments[$line['experiment_id']]['num_registered']=$line['num_registered'];
        }

        $participated_clause=expregister__get_pstatus_query_snippet("participated");
        $query="SELECT experiment_id,
                    count(*) as num_participated
                    FROM ".table('participate_at')."
                    WHERE ".$participated_clause."
                    AND experiment_id IN (".
                implode(',',$eids).")
                    GROUP BY experiment_id";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            $experiments[$line['experiment_id']]['num_participated']=$line['num_participated'];
        }
        //
        if ($finished=='y') {
            $noshow_clause=expregister__get_pstatus_query_snippet("noshow");
            // get showup counts at session level
            // couldn't get much better performance if separating counts
            $query="SELECT ".table('participate_at').".experiment_id,
                        count(*) as comp_num_registered,
                        sum(if(".$noshow_clause.",1,0)) as comp_num_noshow
                        FROM ".table('participate_at').", ".table('sessions')."
                        WHERE ".table('participate_at').".session_id=".table('sessions').".session_id
                        AND (".table('sessions').".session_status='completed' OR ".table('sessions').".session_status='balanced')
                        AND ".table('participate_at').".experiment_id IN (".
                    implode(',',$eids).")
                        GROUP BY ".table('participate_at').".experiment_id";
            $result=or_query($query);
            while ($line=pdo_fetch_assoc($result)) {
                $experiments[$line['experiment_id']]['comp_num_registered']=$line['comp_num_registered'];
                $experiments[$line['experiment_id']]['comp_num_noshow']=$line['comp_num_noshow'];
            }
        }

        if (count($sids)>0) {
            $query="SELECT experiment_id, session_id,
                        count(*) as num_registered
                        FROM ".table('participate_at')."
                        WHERE session_id IN (".
                    implode(',',$sids).")
                        GROUP BY experiment_id, session_id";
            $result=or_query($query);
            while ($line=pdo_fetch_assoc($result)) {
                $experiments[$line['experiment_id']]['sessions'][$line['session_id']]['num_registered']=$line['num_registered'];
            }
        }
    }

    if ($show_filter) {
        echo '<div class="orsee-panel">';
        $experimenter_options=array();
        foreach ($experimenters as $k=>$e) {
            if (in_array($e['admin_id'],$experimenter_arr) || ($e['experimenter_list']=='y' && $e['disabled']!='y')) {
                $experimenter_options[(string)$e['admin_id']]=$e['lname'].', '.$e['fname'];
            }
        }
        asort($experimenter_options);

        $class_options=$experimentclasses;
        asort($class_options);
        $num_filters_selected=0;
        if (isset($_REQUEST['class_search']) && $_REQUEST['class_search']) {
            $num_filters_selected += count($class_arr);
        }
        if (isset($_REQUEST['experimenter_search']) && $_REQUEST['experimenter_search']) {
            $num_filters_selected += count($experimenter_arr);
        }
        $num_filters_active=count($class_arr)+count($experimenter_arr);
        if ($num_filters_active>0) {
            $filter_summary=$num_filters_active.' selected';
        } else {
            $filter_summary='No filters';
        }

        echo '<div class="orsee-filter" data-orsee-filter-selected="'.$num_filters_selected.'">
                <div class="orsee-filter-mobilebar">
                    <a href="#" class="orsee-filter-mobiletoggle" aria-expanded="false">Filter</a>
                    <span class="orsee-filter-mobilesummary">'.$filter_summary.'</span>
                </div>
                <form action="'.thisdoc().'" class="orsee-filter-form">
                    ' . csrf__field(). '
                    <div class="orsee-filter-fields">
                        <div class="orsee-filter-row">
                            <label class="orsee-filter-label">'.lang('restrict_list_to_experiments_of_class').'</label>
                            <div class="orsee-picker-field">'.get_tag_picker('class_search',$class_options,$class_arr,array('tag_bg_color'=>'--color-selector-tag-bg-class')).'</div>
                        </div>
                        <div class="orsee-filter-row">
                            <label class="orsee-filter-label">'.lang('restrict_list_to_experimenters').'</label>
                            <div class="orsee-picker-field">'.get_tag_picker('experimenter_search',$experimenter_options,$experimenter_arr,array('tag_bg_color'=>'--color-selector-tag-bg-experimenters')).'</div>
                        </div>
                    </div>
                    <div class="orsee-filter-actions">
                        <button class="button orsee-btn" type="submit" name="show">'.lang('show').'</button>
                    </div>
                </form>
                <script>
                (function() {
                    var root = document.currentScript ? document.currentScript.parentNode : null;
                    if (!root) return;
                    if (root.getAttribute("data-orsee-filter-init") === "1") return;
                    root.setAttribute("data-orsee-filter-init", "1");
                    var btn = root.querySelector(".orsee-filter-mobiletoggle");
                    var form = root.querySelector(".orsee-filter-form");
                    if (!btn || !form) return;

                    var hasSelections = parseInt(root.getAttribute("data-orsee-filter-selected"), 10) > 0;
                    var mq = window.matchMedia("(max-width: 1100px)");

                    function applyState() {
                        if (!mq.matches) {
                            root.classList.remove("is-collapsed");
                            root.classList.remove("is-open");
                            btn.setAttribute("aria-expanded", "true");
                            return;
                        }
                        if (!root.classList.contains("is-open") && !hasSelections) {
                            root.classList.add("is-collapsed");
                            btn.setAttribute("aria-expanded", "false");
                        } else {
                            root.classList.add("is-open");
                            root.classList.remove("is-collapsed");
                            btn.setAttribute("aria-expanded", "true");
                        }
                    }

                    btn.addEventListener("click", function(e) {
                        e.preventDefault();
                        root.classList.toggle("is-open");
                        root.classList.toggle("is-collapsed", !root.classList.contains("is-open"));
                        btn.setAttribute("aria-expanded", root.classList.contains("is-open") ? "true" : "false");
                    });

                    if (mq.addEventListener) mq.addEventListener("change", applyState);
                    else if (mq.addListener) mq.addListener(applyState);
                    applyState();
                })();
                </script>
              </div>';
        echo '</div>';
    }

    echo '<div class="orsee-panel">';
    echo '<div class="orsee-panel-title">';
    echo '<div>';
    echo count($experiments).' ';
    if ($finished=="n") {
        echo lang('xxx_current_experiments');
    } else {
        echo lang('xxx_finished_experiments');
    }
    echo '</div>';
    echo '<div class="orsee-panel-actions">';
    if ($addbutton && check_allow('experiment_edit')) {
        echo button_link("experiment_edit.php?addit=true",lang('register_new_experiment'),'plus-circle');
    }
    if ($experimenter && $finished=="n" && (check_allow('calendar_view_my') || check_allow('calendar_view_all'))) {
        echo button_link("calendar_main.php?experimenter_id=".urlencode((string)$experimenter),lang('show_my_calendar'),'calendar');
    }
    if (!$experimenter) {
        if ($finished=="n") {
            echo button_link("experiment_old.php",lang('finished_experiments'),'fast-backward','','data-orsee-mobile-hide="exp-switch"');
        } else {
            echo button_link("experiment_main.php",lang('current_experiments'),'fast-forward','','data-orsee-mobile-hide="exp-switch"');
        }
    }
    echo '</div>';
    echo '</div>';

    echo '<div class="orsee-dense-list">';
    foreach ($experiments as $id=>$exp) {
        if ($finished=="n") {
            experiment__experiments_format_alist($exp);
        } else {
            experiment__old_experiments_format_alist($exp);
        }
    }
    echo '</div>';
    echo '</div><br><br>';
}

function experiment__list_experimenters($namelist,$showlinks=true,$realnames=false,$just_emails=false,$reverse_names=false) {
    global $settings, $expadmindata;
    $selected=db_string_to_id_array($namelist);
    $list=array();
    $emails=array();
    $experimenters=experiment__load_experimenters();

    if (!$just_emails) {
        foreach ($selected as $admin) {
            $item='';
            if (isset($experimenters[$admin])) {
                $emails[]=$experimenters[$admin]['email'];
                //if ($showlinks) $item.='<A  class="small" HREF="mailto:'.$experimenters[$admin]['email'].'">';
                if ($realnames) {
                    if ($reverse_names) {
                        $item.=$experimenters[$admin]['lname'].', '.$experimenters[$admin]['fname'];
                    } else {
                        $item.=$experimenters[$admin]['fname'].' '.$experimenters[$admin]['lname'];
                    }
                } else {
                    $item.=$experimenters[$admin]['adminname'];
                }
                //if ($showlinks) $item.='</A>';
            } else {
                $item=$admin;
            }
            $list[]=$item;
        }
        $string='';
        if ($showlinks && count($emails)>0) {
            $string.='<A HREF="mailto:'.implode(",",$emails).'">';
        }
        natsort($list);
        $string.=implode(", ",$list);
        if ($showlinks && count($emails)>0) {
            $string.='</A>';
        }
    } else {
        foreach ($selected as $admin) {
            if (isset($experimenters[$admin])) {
                $list[]=$experimenters[$admin]['email'];
            }
        }
        $string=implode(",",$list);
    }
    return $string;
}

function experiment__load_experimenters() {
    global $settings, $preloaded_experimenters;
    if (isset($preloaded_experimenters) && is_array($preloaded_experimenters)
        && count($preloaded_experimenters)>0) {
        return $preloaded_experimenters;
    } else {
        $admins=array();
        $query="SELECT * from ".table('admin')." order by adminname";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            $admins[$line['admin_id']]=$line;
        }
        $preloaded_experimenters=$admins;
        return $admins;
    }
}


function check_experiment_allowed($experiment_var,$redirect="admin/experiment_main.php") {
    if (!experiment__allowed($experiment_var)) {
        global $lang;
        message(lang('error_experiment_access_restricted'),'error');
        redirect($redirect);
    }
}


function experiment__allowed($experiment_var) {
    if (is_array($experiment_var)) {
        $experiment=$experiment_var;
    } else {
        $experiment=orsee_db_load_array("experiments",$experiment_var,"experiment_id");
    }

    $return=true;

    if ($experiment['access_restricted']=='y') {
        global $settings, $expadmindata;
        if ($settings['allow_experiment_restriction']=='y' &&
            $expadmindata['rights']['experiment_override_restrictions']!='y') {
            $experimenters=db_string_to_id_array($experiment['experimenter']);
            if (!in_array($expadmindata['admin_id'],$experimenters)) {
                $return=false;
            }
        }
    }
    return $return;
}

function experiment__check_required($varname) {
    global $error__error;
    $test=$_REQUEST[$varname];
    if ((!isset($_REQUEST[$varname])) || $test=="nix" || $test=="" || $test=" ") {
        $error__error=true;
        return true;
    } else {
        return false;
    }
}


function experiment__experiments_format_alist($alist) {
    global $lang, $roweven;
    extract($alist);

    $exptypes=load_external_experiment_types();

    if (!isset($roweven) || $roweven==true) {
        $roweven=false;
    } else {
        $roweven=true;
    }

    if (!isset($num_assigned)) {
        $num_assigned=0;
    }
    if (!isset($num_participated)) {
        $num_participated=0;
    }
    if (!isset($num_registered)) {
        $num_registered=0;
    }
    if ($no_sessions==1) {
        $num_sessions=0;
    }

    if (!isset($exptypes[$experiment_ext_type]['exptype_name'])) {
        $exptypes[$experiment_ext_type]['exptype_name']='type undefined';
    }
    $ssicons=array("planned"=>"wrench","live"=>"spinner fa-spin fa-fw","completed"=>"thumbs-o-up","balanced"=>"money");

    $rowclass=$roweven ? 'is-even' : 'is-odd';

    echo '<div class="orsee-dense-row orsee-dense-row--experiment '.$rowclass.'">';
    echo '<div class="orsee-dense-grid">';

    echo '<div class="orsee-dense-cell is-row1">';
    if (check_allow('experiment_show')) {
        echo '<a href="experiment_show.php?experiment_id='.$experiment_id.'" class="orsee-dense-row-title">';
    } else {
        echo '<span class="orsee-dense-row-title">';
    }
    echo $experiment_name.' ('.$experiment_public_name.')';
    if (check_allow('experiment_show')) {
        echo '</a>';
    } else {
        echo '</span>';
    }
    echo '</div>';

    echo '<div class="orsee-dense-cell is-row1 is-col2">';
    if ($num_sessions>0) {
        $first=ortime__format(ortime__sesstime_to_unixtime($first_session_date),'hide_time:true');
        $last=ortime__format(ortime__sesstime_to_unixtime($last_session_date),'hide_time:true');
        echo '<i class="fa fa-calendar"></i> <bdi>'.$first.'</bdi> '.lang('to').' <bdi>'.$last.'</bdi>';
    } else {
        echo '-';
    }
    echo '</div>';

    echo '<div class="orsee-dense-cell orsee-dense-cell-right is-row1">';
    echo '<span class="orsee-dense-row-type">'.lang($experiment_type).' ('.$exptypes[$experiment_ext_type]['exptype_name'].')</span>';
    echo '</div>';

    $classlist=trim((string)experiment__experiment_class_field_to_list($experiment_class));

    echo '<div class="orsee-dense-cell is-row2">';
    echo '<a href="mailto:'.experiment__list_experimenters($experimenter_mail,false,false,true).'" class="orsee-dense-link orsee-dense-link-muted">'.experiment__list_experimenters($experimenter,false,true).'</a>';
    if ($classlist!=="") {
        echo '<div class="orsee-dense-row-class">'.$classlist.'</div>';
    }
    echo '</div>';

    echo '<div class="orsee-dense-cell is-row2 is-col2">';
    if ($experiment_type=="laboratory") {
        echo '<span><strong>'.lang('sessions').':</strong> '.$num_sessions.' <strong><i class="fa fa-users"></i>:</strong> '.$num_assigned.'/'.$num_registered.'/'.$num_participated.'</span>';
    } else {
        echo '-';
    }
    echo '</div>';

    echo '<div class="orsee-dense-cell orsee-dense-cell-sessions is-row2">';
    if (count($sessions)>0) {
        echo '<div class="orsee-dense-sessions-list">';
        foreach ($sessions as $s) {
            if (isset($s['num_registered'])) {
                $reg=$s['num_registered'];
            } else {
                $reg=0;
            }
            if ($reg < $s['part_needed']) {
                $regfontcolor='var(--color-session-not-enough-participants)';
            } elseif ($reg < $s['part_needed'] + $s['part_reserve']) {
                $regfontcolor='var(--color-session-not-enough-reserve)';
            } else {
                $regfontcolor='var(--color-session-complete)';
            }
            $s_start=ortime__sesstime_to_unixtime($s['session_start']);
            echo '<div class="orsee-dense-session-item">';
            echo '<span class="orsee-dense-session-status"><span class="session_status_'.$s['session_status'].'"><i class="fa fa-'.$ssicons[$s['session_status']].'"></i></span></span>';
            echo '<span class="orsee-dense-session-main">';
            echo '<a href="session_edit.php?session_id='.$s['session_id'].'" class="orsee-dense-session-datetime-link is-'.htmlspecialchars($s['session_status']).'"><bdi>'.ortime__format($s_start,'hide_time:true').'</bdi> <bdi>'.ortime__format($s_start,'hide_date:true,hide_second:true').'</bdi></a> ';
            echo '<a href="experiment_participants_show.php?experiment_id='.$experiment_id.'&session_id='.$s['session_id'].'" class="orsee-dense-session-count-link" style="color: '.$regfontcolor.';">'.$reg.' ('.$s['part_needed'].','.$s['part_reserve'].')</a>';
            echo '</span>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '-';
    }
    echo '</div>';

    echo '</div>';
    echo '</div>';
}
//-----------------------------------------------------------------------


// finished experiments - overview table
function experiment__old_experiments_format_alist($alist) {
    global $lang;
    static $shade=true;
    extract($alist);

    $exptypes=load_external_experiment_types();

    if ($shade) {
        $shade=false;
    } else {
        $shade=true;
    }

    if (!isset($num_assigned)) {
        $num_assigned=0;
    }
    if (!isset($num_registered)) {
        $num_registered=0;
    }
    if (!isset($num_participated)) {
        $num_participated=0;
    }
    if ($no_sessions==1) {
        $num_sessions=0;
    }

    if (!isset($exptypes[$experiment_ext_type]['exptype_name'])) {
        $exptypes[$experiment_ext_type]['exptype_name']='type undefined';
    }

    $rowclass=$shade ? 'is-even' : 'is-odd';
    $ssicons=array("planned"=>"wrench","live"=>"spinner fa-spin fa-fw","completed"=>"thumbs-o-up","balanced"=>"money");
    $classlist=trim((string)experiment__experiment_class_field_to_list($experiment_class));

    echo '<div class="orsee-dense-row orsee-dense-row--experiment '.$rowclass.' is-all-compact">';
    echo '<div class="orsee-dense-grid">';

    echo '<div class="orsee-dense-cell is-row1">';
    if (check_allow('experiment_show')) {
        echo '<a href="experiment_show.php?experiment_id='.$experiment_id.'" class="orsee-dense-row-title">';
    } else {
        echo '<span class="orsee-dense-row-title">';
    }
    echo $experiment_name.' ('.$experiment_public_name.')';
    if (check_allow('experiment_show')) {
        echo '</a>';
    } else {
        echo '</span>';
    }
    echo '</div>';

    echo '<div class="orsee-dense-cell is-row1 is-col2">';
    if ($num_sessions>0) {
        $first=ortime__format(ortime__sesstime_to_unixtime($first_session_date),'hide_time:true');
        $last=ortime__format(ortime__sesstime_to_unixtime($last_session_date),'hide_time:true');
        echo '<i class="fa fa-calendar"></i> <bdi>'.$first.'</bdi> '.lang('to').' <bdi>'.$last.'</bdi>';
    } else {
        echo '-';
    }
    echo '</div>';

    echo '<div class="orsee-dense-cell orsee-dense-cell-right is-row1">';
    echo '<span class="orsee-dense-row-type">'.lang($experiment_type).' ('.$exptypes[$experiment_ext_type]['exptype_name'].')</span>';
    echo '</div>';

    echo '<div class="orsee-dense-cell is-row2">';
    echo '<a href="mailto:'.experiment__list_experimenters($experimenter_mail,false,false,true).'" class="orsee-dense-link orsee-dense-link-muted">'.experiment__list_experimenters($experimenter,false,true).'</a>';
    echo '</div>';

    echo '<div class="orsee-dense-cell is-row2 is-col2">';
    if ($experiment_type=="laboratory") {
        if (!isset($comp_num_registered)) {
            $comp_num_registered=0;
        }
        if (!isset($comp_num_noshow)) {
            $comp_num_noshow=0;
        }
        if ($comp_num_registered==0) {
            $noshowrate="??";
        } else {
            $noshowrate=round(($comp_num_noshow/$comp_num_registered)*100,1).'%';
        }
        echo '<span><strong>'.lang('sessions').':</strong> '.$num_sessions.' <strong><i class="fa fa-users"></i>:</strong> '.$num_assigned.'/'.$num_registered.'/'.$num_participated.' <strong>'.str_replace("-","&#8209;",lang('noshowup')).':</strong> '.$noshowrate.'</span>';
    } else {
        echo '-';
    }
    echo '</div>';

    echo '<div class="orsee-dense-cell orsee-dense-cell-sessions is-row2">';
    if (count($sessions)>0) {
        echo '<div class="orsee-dense-row-warning"><i class="fa fa-exclamation-triangle"></i> Incomplete sessions!</div>';
        echo '<div class="orsee-dense-sessions-list">';
        foreach ($sessions as $s) {
            if (isset($s['num_registered'])) {
                $reg=$s['num_registered'];
            } else {
                $reg=0;
            }
            if ($reg < $s['part_needed']) {
                $regfontcolor='var(--color-session-not-enough-participants)';
            } elseif ($reg < $s['part_needed'] + $s['part_reserve']) {
                $regfontcolor='var(--color-session-not-enough-reserve)';
            } else {
                $regfontcolor='var(--color-session-complete)';
            }
            $s_start=ortime__sesstime_to_unixtime($s['session_start']);
            echo '<div class="orsee-dense-session-item">';
            echo '<span class="orsee-dense-session-status"><span class="session_status_'.$s['session_status'].'"><i class="fa fa-'.$ssicons[$s['session_status']].'"></i></span></span>';
            echo '<span class="orsee-dense-session-main">';
            echo '<a href="session_edit.php?session_id='.$s['session_id'].'" class="orsee-dense-session-datetime-link is-'.htmlspecialchars($s['session_status']).'"><bdi>'.ortime__format($s_start,'hide_time:true').'</bdi> <bdi>'.ortime__format($s_start,'hide_date:true,hide_second:true').'</bdi></a> ';
            echo '<a href="experiment_participants_show.php?experiment_id='.$experiment_id.'&session_id='.$s['session_id'].'" class="orsee-dense-session-count-link" style="color: '.$regfontcolor.';">'.$reg.' ('.$s['part_needed'].','.$s['part_reserve'].')</a>';
            echo '</span>';
            echo '</div>';
        }
        echo '</div>';
    } elseif ($classlist!=="") {
        echo '<div class="orsee-dense-row-class">'.$classlist.'</div>';
    }
    echo '</div>';

    echo '</div>';
    echo '</div>';
}


function experiment__exptype_select_field($postvarname,$var,$showvar,$selected,$hidden='',$compact=false) {
    echo '<span class="select is-primary';
    if ($compact) {
        echo ' select-compact';
    }
    echo '"><SELECT name="'.$postvarname.'">';
    $query="SELECT *
            FROM ".table('experiment_types')." as ttype, ".table('lang')." as tlang
            WHERE ttype.exptype_id=tlang.content_name
            AND tlang.content_type='experiment_type'
            ORDER BY exptype_id";

    $result=or_query($query);
    while ($line = pdo_fetch_assoc($result)) {
        if ($line[$var] != $hidden) {
            echo '<OPTION value="'.$line[$var].'"';
            if ($line[$var]==$selected) {
                echo " SELECTED";
            }
            echo '>'.$line[$showvar];
            echo '</OPTION>';
        }
    }
    echo '</SELECT></span>';
}

// multipicker form fields
function experiment__experimenters_select_field($postvarname,$selected,$multi=true,$mpoptions=array()) {
    // $postvarname - name of form field
    // selected - array of pre-selected experimenter usernames
    global $lang;
    $out="";
    if (!is_array($mpoptions)) {
        $mpoptions=array();
    }

    $experimenters=experiment__load_experimenters();

    $mylist=array();
    foreach ($experimenters as $k=>$e) {
        if (in_array($e['admin_id'],$selected) || ($e['experimenter_list']=='y' && $e['disabled']!='y')) {
            $mylist[$e['admin_id']]=$e['lname'].', '.$e['fname'];
        }
    }
    asort($mylist);
    if ($multi) {
        $out.= get_tag_picker($postvarname,$mylist,$selected,$mpoptions);
    } else {
        $out.= '<span class="select is-primary select-compact"><SELECT name="'.$postvarname.'">
                <OPTION value=""';
        if (!$selected) {
            $out.= ' SELECTED';
        } $out.= '>-</OPTION>
                ';
        foreach ($mylist as $k=>$v) {
            $out.= '<OPTION value="'.$k.'"';
            if ($selected==$k) {
                $out.= ' SELECTED';
            } $out.= '>'.$v.'</OPTION>
                ';
        }
        $out.= '</SELECT></span>
        ';
    }
    return $out;
}

function experiment__experiment_class_select_field($postvarname,$selected,$multi=true,$mpoptions=array()) {
    // $postvarname - name of form field
    // selected - array of pre-selected class ids
    global $lang;
    $out="";
    if (!is_array($mpoptions)) {
        $mpoptions=array();
    }

    $experimentclasses=experiment__load_experimentclassnames();
    $mylist=$experimentclasses;
    if ($multi) {
        $out.= get_tag_picker($postvarname,$mylist,$selected,$mpoptions);
    } else {
        $out.= '<SELECT name="'.$postvarname.'">
                <OPTION value=""';
        if (!$selected) {
            $out.= ' SELECTED';
        } $out.= '>-</OPTION>
                ';
        foreach ($mylist as $k=>$v) {
            $out.= '<OPTION value="'.$k.'"';
            if ($selected==$k) {
                $out.= ' SELECTED';
            } $out.= '>'.$v.'</OPTION>
                ';
        }
        $out.= '</SELECT>
        ';
    }
    return $out;
}


function experiment__get_ethics_approval_desc($experiment,$maxsessiontime=-1) {
    $out=lang('human_subjects_ethics_approval').':';
    if (!$experiment['ethics_by'] && !$experiment['ethics_number']) {
        $out.=' '.lang('ethics_not_entered_yet');
        $expired=false;
        $row_bgcolor='var(--color-ethics-approval-not-entered)';
    } else {
        $out.='<br>'.lang('ethics_by').' '.$experiment['ethics_by'].'
                '.lang('ethics_number').' '.$experiment['ethics_number'];
        if ($experiment['ethics_exempt']=='y') {
            $expired=false;
            $row_bgcolor='var(--color-ethics-approval-valid)';
        } else {
            $expiration_unixtime=ortime__sesstime_to_unixtime($experiment['ethics_expire_date']);
            if ($maxsessiontime==-1) {
                $last_session_unixtime=time();
            } else {
                $last_session_unixtime=ortime__sesstime_to_unixtime($maxsessiontime);
            }
            if ($expiration_unixtime>$last_session_unixtime) {
                $out.='<br>'.lang('ethics_expires_on').' '.ortime__format($expiration_unixtime,'hide_time:true');
                $expired=false;
                $row_bgcolor='var(--color-ethics-approval-valid)';
            } else {
                $out.='<br><B>';
                if ($expiration_unixtime>time()) {
                    $out.=lang('ethics_will_expire_on');
                } else {
                    $out.=lang('ethics_has_expired_on');
                }
                $out.=' '.ortime__format($expiration_unixtime,'hide_time:true').'</B>';
                $expired=true;
                $row_bgcolor='var(--color-ethics-approval-expired)';
            }
        }
    }
    return array('text'=>$out,'color'=>$row_bgcolor);
}


function experiment__experiment_class_field_to_list($experiment_class) {
    $experiment_class=db_string_to_id_array($experiment_class);
    $experimentclasses=experiment__load_experimentclassnames();
    $out=array();
    foreach ($experiment_class as $class) {
        if (isset($experimentclasses[$class])) {
            $out[]=$experimentclasses[$class];
        } elseif ($class=='0') {
            $out[]='-';
        } else {
            $out[]=$class;
        }
    }
    return implode(", ",$out);
}

function experiment__load_experimentclassnames() {
    global $lang, $preloaded_experimentclasses;
    if (isset($preloaded_experimentclasses) && is_array($preloaded_experimentclasses)
        && count($preloaded_experimentclasses)>0) {
        return $preloaded_experimentclasses;
    } else {
        $names=array();
        $query="SELECT *
                FROM ".table('lang')."
                WHERE content_type='experimentclass'
                ORDER BY ".lang('lang');
        $result=or_query($query);
        while ($line = pdo_fetch_assoc($result)) {
            $names[$line['content_name']]=$line[lang('lang')];
        }
        $preloaded_experimentclasses=$names;
        return $names;
    }
}

function experiment__get_public_name($experiment_id) {
    $exp=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
    return $exp['experiment_public_name'];
}


function experiment__count_experiments($constraint="",$const_pars=array()) {
    if ($constraint) {
        $whereclause="WHERE ".$constraint;
    } else {
        $whereclause="";
    }

    $query="SELECT COUNT(*) as cnt
            FROM ".table('experiments')." ".$whereclause;
    $line=orsee_query($query,$const_pars);
    return $line['cnt'];
}


function experiment__count_participate_at($experiment_id,$session_id="",$condition="",$cond_pars=array()) {
    $query="";
    $pars=array();
    $query="SELECT COUNT(*) as regcount FROM ".table('participate_at')." WHERE ";
    if ($session_id) {
        $query.="session_id= :tsession_id";
        $pars[':tsession_id']=$session_id;
    } else {
        $query.="experiment_id= :texperiment_id";
        $pars[':texperiment_id']=$experiment_id;
    }
    if ($condition) {
        $query.=" AND (".$condition.")";
        foreach ($cond_pars as $p=>$v) {
            $pars[$p]=$v;
        }
    }
    $line=orsee_query($query,$pars);
    return $line['regcount'];
}

function experiment__count_pstatus($experiment_id,$session_id="") {
    $pstatuses=expregister__get_participation_statuses();
    $pars=array();
    $query="SELECT COUNT(*) as regcount, pstatus_id";
    if (!$session_id) {
        $query.=", session_id";
    }
    $query.=" FROM ".table('participate_at')." WHERE";
    if ($session_id) {
        $query=$query." session_id=:session_id";
        $pars[':session_id']=$session_id;
    } else {
        $query=$query." experiment_id=:experiment_id";
        $pars[':experiment_id']=$experiment_id;
    }
    $query.=' GROUP BY pstatus_id';
    if (!$session_id) {
        $query.=", session_id";
    }
    $result=or_query($query, $pars);
    $pstatus_counts=array();
    $assigned=0;
    $enroled=0;
    $participated=0;
    $noshow=0;
    while ($line=pdo_fetch_assoc($result)) {
        if (!isset($pstatus_counts[$line['pstatus_id']])) {
            $pstatus_counts[$line['pstatus_id']]=0;
        }
        if ($line['pstatus_id']>0) {
            $pstatus_counts[$line['pstatus_id']]=$pstatus_counts[$line['pstatus_id']]+$line['regcount'];
        } elseif ($line['session_id']>0) {
            $pstatus_counts[0]=$pstatus_counts[0]+$line['regcount'];
        }
        $assigned=$assigned+$line['regcount'];
        if (!$session_id && $line['session_id']>0) {
            $enroled=$enroled+$line['regcount'];
        } elseif ($session_id) {
            $enroled=$enroled+$line['regcount'];
        }
        if ($pstatuses[$line['pstatus_id']]['participated']) {
            $participated=$participated+$line['regcount'];
        }
        if ($pstatuses[$line['pstatus_id']]['noshow']) {
            $noshow=$noshow+$line['regcount'];
        }
    }
    $counts=array();
    $counts['assigned']=$assigned;
    $counts['enroled']=$enroled;
    $counts['participated']=$participated;
    $counts['noshow']=$noshow;
    $counts['pstatus']=array();
    foreach ($pstatus_counts as $ps=>$psc) {
        $counts['pstatus'][$ps]['count']=$psc;
        $counts['pstatus'][$ps]['internal_name']=$pstatuses[$ps]['internal_name'];
    }
    return $counts;
}

function load_external_experiment_types($expinttype="",$enabled=true) {
    global $preloaded_experiment_types;
    if (is_array($preloaded_experiment_types) && count($preloaded_experiment_types)>0 && (!$expinttype)) {
        return $preloaded_experiment_types;
    } else {
        $exttypes=array();
        $enstring="";
        $pars=array();
        if ($enabled) {
            $enstring.=" AND texpt.enabled='y' ";
        }
        if ($expinttype) {
            $enstring.=" AND texpt.exptype_mapping LIKE :expinttype";
            $pars[':expinttype']="%".$expinttype."%";
        }
        $query="SELECT *
                FROM ".table('experiment_types')." as texpt, ".table('lang')." as tlang
                WHERE texpt.exptype_id=tlang.content_name
                AND tlang.content_type='experiment_type'".
                $enstring."
                ORDER BY exptype_id";
        $result=or_query($query,$pars);
        while ($line=pdo_fetch_assoc($result)) {
            $exttypes[$line['exptype_id']]=$line;
        }
        if (!$expinttype) {
            $preloaded_experiment_types=$exttypes;
        }
        return $exttypes;
    }
}

function experiment__exp_id_list_to_exp_names($experiment_list) {
    $allexperiments=experiment__preload_experiments();
    $expids=explode(",",$experiment_list);
    $expnames=array();
    foreach ($expids as $id) {
        if ($id!='') {
            $expnames[]=$allexperiments[$id]['experiment_name'];
        }
    }
    return implode(", ",$expnames);
}

function experiment__preload_experiments() {
    global $lang, $preloaded_experiments;

    if (isset($preloaded_experiments) && is_array($preloaded_experiments)
            && count($preloaded_experiments)>0) {
        return $preloaded_experiments;
    } else {
        $experiments=array();
        $query="SELECT experiment_id, experiment_name, experimenter from ".table('experiments');
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            $experiments[$line['experiment_id']]=array('time'=>'','start_date'=>'','end_date'=>'','assigned'=>'n','participated'=>'n');
            $experiments[$line['experiment_id']]['experiment_id']=$line['experiment_id'];
            $experiments[$line['experiment_id']]['experiment_name']=$line['experiment_name'];
            $experiments[$line['experiment_id']]['experimenter']=$line['experimenter'];
        }
        $query="SELECT experiment_id,
                min(session_start) as start_date,
                max(session_start) as end_date
                FROM ".table('sessions')."
                WHERE session_id>0
                GROUP BY experiment_id";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            $experiments[$line['experiment_id']]['time']=1000000000000-$line['end_date'];
            $experiments[$line['experiment_id']]['start_date']=$line['start_date'];
            $experiments[$line['experiment_id']]['end_date']=$line['end_date'];
        }

        $query="SELECT experiment_id FROM ".table('participate_at')." GROUP BY experiment_id";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            $experiments[$line['experiment_id']]['assigned']='y';
        }

        $participated_clause=expregister__get_pstatus_query_snippet("participated");
        $query="SELECT experiment_id FROM ".table('participate_at')." WHERE ".$participated_clause." GROUP BY experiment_id";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            $experiments[$line['experiment_id']]['participated']='y';
        }

        $sort_order = array("time", "experiment_name");
        multi_array_sort($experiments,$sort_order);
        $preloaded_experiments=$experiments;
        return $experiments;
    }
}



function experiment__load_experiments_for_ids($ids=array()) {
    $experiments=array();
    if (count($ids)>0) {
        $par_array=id_array_to_par_array($ids);
        $query="SELECT * FROM ".table('experiments')."
                WHERE experiment_id IN (".implode(',',$par_array['keys']).")";
        $result=or_query($query,$par_array['pars']);
        while ($line=pdo_fetch_assoc($result)) {
            $experiments[$line['experiment_id']]=$line;
        }
    }
    return $experiments;
}

function experiment__other_experiments_select_field($postvarname,$type="assigned",$experiment_id="",$selected='',$multi=true,$mpoptions=array()) {
    // $postvarname - name of form field
    // selected - array of pre-selected experimenter usernames
    global $lang, $preloaded_experiments, $settings;

    $out="";
    if (!(is_array($preloaded_experiments) && count($preloaded_experiments)>0)) {
        $preloaded_experiments=experiment__preload_experiments();
    }

    $mylist=array();
    foreach ($preloaded_experiments as $e) {
        if ($e['experiment_id']!=$experiment_id &&
             ($type=='all' || ($type=='assigned' && $e['assigned']=='y') || ($type=='participated' && $e['participated']=='y'))
        ) {
            $ename=$e['experiment_name'];
            if ($e['time'] || $e['experimenter']) {
                $ename.=' (';
            }
            if ($e['experimenter']) {
                $ename.=experiment__list_experimenters($e['experimenter'],false,false);
            }
            if ($e['time'] && $e['experimenter']) {
                $ename.=', ';
            }
            if ($e['time']) {
                $ename.=ortime__format(ortime__sesstime_to_unixtime($e['start_date']),'hide_time:true').'-'.ortime__format(ortime__sesstime_to_unixtime($e['end_date']),'hide_time:true');
            }
            if ($e['time'] || $e['experimenter']) {
                $ename.=')';
            }
            $mylist[$e['experiment_id']]=$ename;
        }
    }

    if (!is_array($mpoptions)) {
        $mpoptions=array();
    }
    if ($multi) {
        $out.= get_tag_picker($postvarname,$mylist,$selected,$mpoptions);
    } else {
        $out.= '<SELECT name="'.$postvarname.'">
                <OPTION value=""';
        if (!$selected) {
            $out.= ' SELECTED';
        } $out.= '>-</OPTION>
                ';
        foreach ($mylist as $k=>$v) {
            $out.= '<OPTION value="'.$k.'"';
            if ($selected==$k) {
                $out.= ' SELECTED';
            } $out.= '>'.$v.'</OPTION>
                ';
        }
        $out.= '</SELECT>
        ';
    }
    return $out;
}

?>
