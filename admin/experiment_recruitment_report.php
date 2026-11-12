<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="experiments";
$title="experiment_recruitment_report";
$lang_icons_prepare=true;
include("header.php");

if ($proceed) {
    if (!$_REQUEST['experiment_id']) redirect ("admin/");
    //app-team security update: redirect away if ID is not a number
    else if (!is_numeric($_REQUEST['experiment_id']) ) redirect ("admin/");
    else $experiment_id=$_REQUEST['experiment_id'];
}

if ($proceed) {
    $allow=check_allow('experiment_recruitment_report_show','experiment_show.php?experiment_id='.$experiment_id);
}

if ($proceed) {
    // load experiment data into array experiment
    $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
    if (!check_allow('experiment_restriction_override')) {
        check_experiment_allowed($experiment,"admin/experiment_show.php?experiment_id=".$experiment_id);
    }
}

if ($proceed) {
    $alllangs=get_languages();
    if (isset($_REQUEST['replang']) && in_array($_REQUEST['replang'],$alllangs) && $_REQUEST['replang']!=lang('lang')) {
        $replang=$_REQUEST['replang'];
    } else {
        $replang=lang('lang');
    }

    $lang_names=lang__get_language_names();
    $switchlang_text='';
    foreach ($alllangs as $thislang) {
        if ($thislang != $replang) {
            $switchlang_text.='<A HREF="'.thisdoc().'?experiment_id='.$experiment_id.'&replang='.$thislang.'"><span class="languageicon langicon-'.$thislang.'">';
            if (isset($lang_names[$thislang]) && $lang_names[$thislang]) {
                $switchlang_text.=$lang_names[$thislang];
            } else {
                $switchlang_text.=$thislang;
            }
            $switchlang_text.='</span></A>&nbsp;&nbsp;&nbsp;';
        }
    }
    if ($switchlang_text) {
        $switchlang_text='<p style="text-align:end;">'.lang('this_report_in_language').' '.$switchlang_text.'</p>';
    }
    if ($replang!=lang('lang')) {
        $switched_lang=true;
        $mylang=$lang;
        $lang=load_language($_REQUEST['replang']);
    }
}

if ($proceed) {
    // load sessions if lab experiment
    // also: check if $experiment_id is actually a number
    $sessions=array();
    if ($experiment['experiment_type']=="laboratory" && is_numeric($experiment_id) == TRUE) {
        $pars=array(':experiment_id'=>$experiment['experiment_id']);
        $query="SELECT *
                FROM ".table('sessions')."
                WHERE experiment_id= :experiment_id
                ORDER BY session_start";
        $result=or_query($query,$pars);
        $min=0;
        $max=0;
        $sids=array();
        while ($s=pdo_fetch_assoc($result)) {
            $sessions[$s['session_id']]=$s;
            $sesstime=$s['session_start'];
            if ($min==0) {
                $min=$sesstime;
                $max=$sesstime;
            } else {
                if ($sesstime < $min) {
                    $min=$sesstime;
                }
                if ($sesstime > $max) {
                    $max=$sesstime;
                }
            }
            $sids[]=$s['session_id'];
        }
        // get pstatus counts
        // also: check if $experiment_id is actually a number
        if (count($sids)>0 && is_numeric($experiment_id) == TRUE) {
            $pars=array(':experiment_id'=>$experiment['experiment_id']);
            $query="SELECT session_id, pstatus_id,
                    COUNT(*) as num
                    FROM ".table('participate_at')."
                    WHERE experiment_id= :experiment_id
                    AND session_id>0
                    GROUP BY session_id, pstatus_id";
            $result=or_query($query,$pars);
            while ($s=pdo_fetch_assoc($result)) {
                $sessions[$s['session_id']]['num_status'.$s['pstatus_id']]=$s['num'];
            }
        }
    }
}

if ($proceed) {
    // load all types we need to know
    $exptypes=load_external_experiment_types();
    $preloaded_laboratories=laboratories__get_laboratories();
    $pstatuses=expregister__get_participation_statuses();
}

if ($proceed) {
    if ($switchlang_text) {
        echo $switchlang_text;
    }

    /////////////////////////////
    /// EXPERIMENT
    /////////////////////////////
    echo '<div class="orsee-panel">';
    echo '<div class="orsee-panel-title"><div>'.lang('experiment').'</div></div>';
    echo '<div class="orsee-table orsee-table-no-hover" style="width: 100%; max-width: 100%;">';
    if (!isset($exptypes[$experiment['experiment_ext_type']]['exptype_name'])) {
        $exptypes[$experiment['experiment_ext_type']]['exptype_name']='type undefined';
    }
    echo '<div class="orsee-table-row">
            <div class="orsee-table-cell"><strong>'.lang('id').':</strong> '.$experiment['experiment_id'].'</div>
            <div class="orsee-table-cell"><strong>'.lang('type').':</strong> '.$lang[$experiment['experiment_type']].' ('.$exptypes[$experiment['experiment_ext_type']]['exptype_name'].')</div>
          </div>';
    echo '<div class="orsee-table-row is-alt">
            <div class="orsee-table-cell"><strong>'.lang('name').':</strong> '.$experiment['experiment_name'].'</div>
            <div class="orsee-table-cell"><strong>'.lang('public_name').':</strong> '.$experiment['experiment_public_name'].'</div>
          </div>';
    echo '<div class="orsee-table-row">
            <div class="orsee-table-cell"><strong>'.lang('class').':</strong> '.experiment__experiment_class_field_to_list($experiment['experiment_class']).'</div>
            <div class="orsee-table-cell"><strong>'.lang('experimenter').':</strong> '.experiment__list_experimenters($experiment['experimenter'],true,true).'</div>
          </div>';

    $conditional_fields=array();
    if ($experiment['experiment_description']) {
        $conditional_fields[]=array('label'=>lang('internal_description').':','value'=>$experiment['experiment_description']);
    }
    if ($experiment['public_experiment_note']) {
        $conditional_fields[]=array('label'=>lang('public_experiment_note').':','value'=>$experiment['public_experiment_note']);
    }
    if ($settings['enable_editing_of_experiment_sender_email']=='y') {
        $conditional_fields[]=array('label'=>lang('email_sender_address').':','value'=>$experiment['sender_mail']);
    }
    $i=0;
    foreach ($conditional_fields as $field) {
        if ($i % 2 == 0) {
            $row_class='orsee-table-row';
            if ((int)($i/2) % 2 == 1) {
                $row_class.=' is-alt';
            }
            echo '<div class="'.$row_class.'">';
            echo '<div class="orsee-table-cell"><strong>'.$field['label'].'</strong> '.$field['value'].'</div>';
            if (isset($conditional_fields[$i+1])) {
                echo '<div class="orsee-table-cell"><strong>'.$conditional_fields[$i+1]['label'].'</strong> '.$conditional_fields[$i+1]['value'].'</div>';
            } else {
                echo '<div class="orsee-table-cell"></div>';
            }
            echo '</div>';
        }
        $i++;
    }

    if ($settings['enable_ethics_approval_module']=='y') {
        $ethics=experiment__get_ethics_approval_desc($experiment);
        echo '<div class="orsee-table-row is-alt">
                <div class="orsee-table-cell"><strong>'.lang('human_subjects_ethics_approval').':</strong></div>
                <div class="orsee-table-cell">'.$ethics['text'].'</div>
              </div>';
    }
    echo '</div>';
    echo '</div>';

    if ($experiment['experiment_type']=="laboratory") {
        /////////////////////////////
        /// SESSIONS
        /////////////////////////////
        echo '<div class="orsee-panel">';
        echo '<div class="orsee-panel-title"><div>'.lang('sessions');
        if ($min>0) {
            echo ' '.lang('from').' '.ortime__format(ortime__sesstime_to_unixtime($min),'hide_time').' '.lang('to').' '.ortime__format(ortime__sesstime_to_unixtime($max),'hide_time');
        }
        echo '</div></div>';

        echo '<div class="orsee-table" style="width: 100%; max-width: 100%;">';
        $session_alt=false;
        foreach ($sessions as $s) {
            $row_base='orsee-table-row';
            if ($session_alt) {
                $row_base.=' is-alt';
            }
            $session_time=session__build_name($s);
            $ssicons=array("planned"=>"wrench","live"=>"spinner fa-spin fa-fw","completed"=>"thumbs-o-up","balanced"=>"money");
            $has_note=($s['public_session_note'] ? true : false);
            $has_remarks=($s['session_remarks'] ? true : false);

            $status_text='<span class="session_status_'.$s['session_status'].'"><i class="fa fa-'.$ssicons[$s['session_status']].'"></i>&nbsp;'.$lang['session_status_'.$s['session_status']].'</span>';
            echo '<div class="'.$row_base.'">
                <div class="orsee-table-cell" style="border-bottom: 0;"><strong>'.$session_time.', '.$preloaded_laboratories[$s['laboratory_id']]['lab_name'].'</strong></div>
                <div class="orsee-table-cell" style="border-bottom: 0;">'.lang('session_status').': <strong>'.$status_text.'</strong></div>
              </div>';

            $status_counts=array();
            foreach ($pstatuses as $pstatus_id=>$pstatus) {
                $count=(isset($s['num_status'.$pstatus_id]) ? $s['num_status'.$pstatus_id] : 0);
                $label=$pstatus['internal_name'].': '.$count;
                if ($pstatus['participated']) {
                    $label='<strong>'.$label.'</strong>';
                }
                $status_counts[]=$label;
            }

            $detail_class='orsee-table-row';
            if ($session_alt) {
                $detail_class.=' is-alt';
            }
            $detail_cell_style='';
            if ($has_note || $has_remarks) {
                $detail_cell_style=' style="border-bottom: 0;"';
            }
            echo '<div class="'.$detail_class.'">
                <div class="orsee-table-cell"'.$detail_cell_style.'>'.lang('subjects').': '.lang('needed_participants_abbr').': '.$s['part_needed'].', '.lang('reserve_participants_abbr').': '.$s['part_reserve'].'</div>
                <div class="orsee-table-cell"'.$detail_cell_style.'>'.implode(' | ',$status_counts).'</div>
              </div>';

            if ($has_note) {
                $note_class='orsee-table-row';
                if ($session_alt) {
                    $note_class.=' is-alt';
                }
                $note_cell_style='';
                if ($has_remarks) {
                    $note_cell_style=' style="border-bottom: 0;"';
                }
                echo '<div class="'.$note_class.'">
                    <div class="orsee-table-cell"'.$note_cell_style.'><strong>'.lang('public_session_note').':</strong></div>
                    <div class="orsee-table-cell"'.$note_cell_style.'>'.$s['public_session_note'].'</div>
                  </div>';
            }
            if ($has_remarks) {
                $remarks_class='orsee-table-row';
                if ($session_alt) {
                    $remarks_class.=' is-alt';
                }
                echo '<div class="'.$remarks_class.'">
                    <div class="orsee-table-cell"><strong>'.lang('remarks').':</strong></div>
                    <div class="orsee-table-cell"><strong>'.$s['session_remarks'].'</strong></div>
                  </div>';
            }
            $session_alt=!$session_alt;
        }
        echo '</div>';
        echo '</div>';
    }

    /////////////////////////////
    /// ASSIGNMENTS
    /////////////////////////////
    echo '<div class="orsee-panel">';
    echo '<div class="orsee-panel-title"><div>'.lang('recruitment_history').'</div></div>';
    echo '<div class="orsee-table" style="width: 100%; max-width: 100%;">';
    echo '<div class="orsee-table-row orsee-table-head">
            <div class="orsee-table-cell">'.lang('date_and_time').'</div>
            <div class="orsee-table-cell">'.lang('query').'</div>
          </div>';

    $queries=query__load_saved_queries('assign,deassign',-1,$experiment_id,true,"query_time ASC");
    $shade=false;
    foreach ($queries as $q) {
        $row_class='orsee-table-row';
        if ($shade) {
            $row_class.=' is-alt';
        }
        $shade=!$shade;

        $left='<strong>'.ortime__format($q['query_time']).'</strong><br>';
        if ($q['permanent'] || (isset($q['properties']['is_permanent']) && $q['properties']['is_permanent'])) {
            $left.='<strong>'.lang('report_queries__permanent_query').'</strong><br>';
            $left.=lang('from').' '.ortime__format($q['properties']['permanent_start_time']).' ';
            if (isset($q['properties']['permanent_start_time']) && !$q['permanent']) {
                $left.=lang('to').' '.ortime__format($q['query_time']);
            } else {
                $left.=lang('until_now');
            }
            $left.='<br>'.lang('report_queries__number_of_subjects_added').': <strong>';
            if (isset($q['properties']['assigned_count'])) {
                $left.=$q['properties']['assigned_count'];
            } else {
                $left.='0';
            }
            $left.='</strong>';
        } else {
            $left.='<strong>';
            if ($q['query_type']=='assign') {
                $left.=lang('report_queries__potential_participants_added');
            } else {
                $left.=lang('report_queries__potential_participants_removed');
            }
            $left.='</strong><br>';
            if ($q['admin_id']) {
                $left.=$q['admin_id'].'<br>';
            }
            $left.=lang('report_queries__subjects_in_result_set').': ';
            if ($q['properties']['selected']=='n') {
                $left.='<strong>';
            }
            $left.=$q['properties']['totalcount'];
            if ($q['properties']['selected']=='n') {
                $left.='</strong>';
            }
            $left.='<br>';
            if ($q['query_type']=='assign') {
                if ($q['properties']['selected']=='y') {
                    $left.=lang('report_queries__assigned_selected_subset_of_size').': <strong>'.$q['properties']['assigned_count'].'</strong>';
                } else {
                    $left.=lang('report_queries__assigned_all');
                }
            } else {
                if ($q['properties']['selected']=='y') {
                    $left.=lang('report_queries__deassigned_selected_subset_of_size').': <strong>'.$q['properties']['assigned_count'].'</strong>';
                } else {
                    $left.=lang('report_queries__deassigned_all');
                }
            }
        }

        $posted_query=json_decode($q['json_query'],true);
        $pseudo_query_array=query__get_pseudo_query_array($posted_query['query']);
        $pseudo_query_display=query__display_pseudo_query($pseudo_query_array,true);

        echo '<div class="'.$row_class.'">
                <div class="orsee-table-cell" style="vertical-align: top;">'.$left.'</div>
                <div class="orsee-table-cell">'.$pseudo_query_display.'</div>
              </div>';
    }
    echo '</div>';
    echo '</div>';

    /////////////////////////////
    /// SUBJECT POOL STATISTICS
    /////////////////////////////
    echo '<div class="orsee-panel">';
    echo '<div class="orsee-panel-title"><div>'.lang('subject_pool_statistics').'</div></div>';

    //check if $experiment_id is actually a number
    if(is_numeric($experiment_id) == TRUE){
        $pars=array(':experiment_id'=>$experiment['experiment_id']);
        $query="SELECT max(session_start) as max_time, min(session_start) as min_time
                FROM ".table('sessions')."
                WHERE experiment_id = :experiment_id";
        $line=orsee_query($query,$pars);
        $min_session_time=ortime__sesstime_to_unixtime($line['min_time']);
        $max_session_time=ortime__sesstime_to_unixtime($line['max_time']);
    }

    $total_data=array();
    // pool
    $options=array('upper_experience_limit'=>$min_session_time);
    $condition=array('clause'=>" status_id > 0 AND creation_time < '".$max_session_time."' AND
                  (deletion_time=0 OR deletion_time > '".$min_session_time."') ",
                    'pars'=>array()
                    );
    $total_data['pool']=stats__get_data($condition,'report',array(),$options);
    // experiment eligible
    $options=array('upper_experience_limit'=>$min_session_time,'condition_only_on_pid'=>true);
    $condition=array('clause'=>"participant_id IN (SELECT participant_id FROM ".table('participate_at')."
                                WHERE experiment_id= :experiment_id )",
                    'pars'=>array(':experiment_id'=>$experiment_id)
                    );
    $total_data['exp']=stats__get_data($condition,'report',array(),$options);

    // participated
    $options=array('upper_experience_limit'=>$min_session_time,'condition_only_on_pid'=>true);
    $participated_clause=expregister__get_pstatus_query_snippet("participated");
    $condition=array('clause'=>"participant_id IN (SELECT participant_id FROM ".table('participate_at')."
                                WHERE experiment_id= :experiment_id AND session_id > 0 AND ".$participated_clause.")",
                    'pars'=>array(':experiment_id'=>$experiment_id)
                    );
    $total_data['part']=stats__get_data($condition,'report',array(),$options);

    echo '<div class="orsee-form-row-grid orsee-form-row-grid--2" style="align-items: start; row-gap: 1rem;">';
    $i=0;
    $cols=2;
    $out=array();
    foreach ($total_data['pool'] as $k=>$table1) {
        if (isset($table1['data']) && is_array($table1['data']) && count($table1['data'])>0) {
            $show=true;
        } else {
            $show=false;
        }
        if ($show) {
            $out[]=stats__report_display_table($table1,lang('stats_report__pool'),$total_data['exp'][$k],lang('stats_report__assigned'),$total_data['part'][$k],lang('stats_report__participated'));
            if (count($out)==$cols) {
                echo '<div class="orsee-form-row-col">'.$out[0].'</div><div class="orsee-form-row-col">'.$out[1].'</div>';
                $out=array();
            }
        }
    }
    if (count($out)>0) {
        echo '<div class="orsee-form-row-col">'.$out[0].'</div>';
        if (count($out)<2) {
            echo '<div class="orsee-form-row-col"></div>';
        }
    }
    echo '</div>';
    echo '</div>';

    if (isset($switched_lang) && $switched_lang && isset($mylang)) {
        $lang=$mylang;
    }
    echo '<div class="orsee-options-actions">'.button_back('experiment_show.php?experiment_id='.$experiment_id).'</div>';
}
include("footer.php");

?>
