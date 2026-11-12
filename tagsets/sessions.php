<?php
// part of orsee. see orsee.org

function sessions__format_alist($session,$experiment) {
    global $lang, $settings, $sessionlinecolor, $preloaded_laboratories;

    if (!(is_array($preloaded_laboratories) && count($preloaded_laboratories)>0)) {
        $preloaded_laboratories=laboratories__get_laboratories();
    }

    extract($session);
    $session_time=session__build_name($session);

    if (!isset($sessionlinecolor) || !$sessionlinecolor) {
        $sessionlinecolor='empty';
    } elseif ($sessionlinecolor=='empty') {
        $sessionlinecolor='grey';
    } else {
        $sessionlinecolor='empty';
    }

    $reg=$regcount;
    if ($reg < $part_needed) {
        $regfontcolor='var(--color-session-not-enough-participants)';
    } elseif ($reg < $part_needed + $part_reserve) {
        $regfontcolor='var(--color-session-not-enough-reserve)';
    } else {
        $regfontcolor='var(--color-session-complete)';
    }


    if ($session_status=="live") {
        $signup_time=session__get_signup_time_left($session);
        if ($signup_time) {
            $reg_state=lang('signup_time_left').':&nbsp;'.$signup_time;
        } else {
            $reg_state=lang('registration_deadline_passed');
        }

        if ($reminder_sent=="y") {
            $reminder_state=lang('session_reminder_state__sent');
            $reminder_statecolor='var(--color-session-reminder-state-sent)';
        } elseif ($reminder_checked=="y" && $reminder_sent=="n") {
            $reminder_state=lang('session_reminder_state__checked_but_not_sent');
            $reminder_statecolor='var(--color-session-reminder-state-checked)';
        } else {
            $reminder_state=lang('session_reminder_state__waiting');
            $reminder_statecolor='var(--color-session-reminder-state-waiting)';
        }
    } else {
        $reminder_state='';
        $reg_state='';
    }

    if ($settings['enable_payment_module']=="y" &&  check_allow('payments_view')
        && ($session_status=='completed' || $session_status=='balanced')) {
        if ($session_status=='balanced') {
            $payment_types=payments__load_paytypes();
            $payment_parts=array();
            foreach ($payments_by_type as $paytype=>$payamount) {
                if (isset($payment_types[$paytype])) {
                    $paytype_name=$payment_types[$paytype];
                } else {
                    $paytype_name=lang('unknown');
                }
                $payment_parts[]=or__format_number($payamount,2).' ('.$paytype_name.')';
            }
            if (count($payment_parts)>0) {
                $payments=implode(', ',$payment_parts);
                if (count($payment_parts)>1) {
                    $payments='<span class="orsee-font-compact">'.$payments.'</span>';
                }
            } else {
                $payments='-';
            }
        } else {
            $payments=lang('total_payment_abbr').': '.lang('three_questionmarks');
        }
    } else {
        $payments='';
    }

    $ssicons=array("planned"=>"wrench","live"=>"spinner fa-spin fa-fw","completed"=>"thumbs-o-up","balanced"=>"money");

    if ($sessionlinecolor=='empty') {
        $rowclass=' is-odd';
    } else {
        $rowclass='';
    }

    if ($settings['enable_ethics_approval_module']=='y') {
        $ethicsclass='';
        if ($experiment['ethics_by'] || $experiment['ethics_number']) {
            if ($experiment['ethics_exempt']!='y' && $session_start>$experiment['ethics_expire_date']) {
                $ethicsclass=' is-ethics-expired';
            }
        }
    } else {
        $ethicsclass='';
    }


    echo '<div class="orsee-dense-row orsee-dense-row--session'.$rowclass.$ethicsclass.'">';
    echo '<div class="orsee-dense-session-grid">';
    echo '<div class="orsee-dense-session-select"><input name="sel['.$session_id.']" type="checkbox" value="y"></div>';
    echo '<div class="orsee-dense-session-name"><strong>'.$session_time;
    if (count($preloaded_laboratories)>1) {
        echo ' '.$preloaded_laboratories[$laboratory_id]['lab_name'];
    }
    echo '</strong></div>';
    echo '<div class="orsee-dense-session-statusline">'.lang('session_status').': <strong><span class="session_status_'.$session_status.'">'.
        '<i class="fa fa-'.$ssicons[$session_status].'"></i>&nbsp;'.$lang['session_status_'.$session_status].'</span></strong></div>';

    $allow_sp=check_allow('experiment_show_participants');

    echo '<div class="orsee-dense-session-signedup">';
    if ($allow_sp) {
        echo '<a href="experiment_participants_show.php?experiment_id='.$experiment_id.'&session_id='.$session_id.'">';
    }
    echo lang('registered_subjects');
    if ($allow_sp) {
        echo '</a>';
    }
    echo ': <span style="color: '.$regfontcolor.'">'.$reg.' ('.$part_needed.','.$part_reserve.')</span>';
    echo '</div>';
    echo '<div class="orsee-dense-session-regstate">';
    if ($reg_state) {
        echo $reg_state;
    }
    echo '</div>';
    echo '<div class="orsee-dense-session-reminder">';
    if ($reminder_state) {
        echo '<span style="color: '.$reminder_statecolor.'"><i class="fa fa-bell" style="padding-inline-end: 3px;"></i>'.lang('session_reminder').': '.$reminder_state.'</span>';
    }
    echo '</div>';
    echo '<div class="orsee-dense-session-payment">';
    if ($payments) {
        echo $payments;
    }
    echo '</div>';
    echo '<div class="orsee-dense-session-action">';
    if (check_allow('session_edit')) {
        echo button_link('session_edit.php?session_id='.$session_id, lang('edit'),'pencil-square-o');
    }
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

function session__get_signup_time_left($s) {
    global $lang;
    $sutime=ortime__sesstime_to_unixtime($s['session_start']);
    $left_sec=$sutime-($s['registration_end_hours']*60*60)-time();
    if ($left_sec>60*60*24*2) {
        $t=floor($left_sec/(60*60*24)).lang('format_datetime_shortcut_days').
            round(($left_sec-(floor($left_sec/(60*60*24))*60*60*24))/(60*60)).
                                    lang('format_datetime_shortcut_hours');
    } elseif ($left_sec>60*60*1.5) {
        $t=round($left_sec/(60*60)).
                                        lang('format_datetime_shortcut_hours');
    } elseif ($left_sec>0) {
        $t=round($left_sec/60).lang('format_datetime_shortcut_minutes');
    } else {
        $t='';
    }
    return $t;
}

function session__session_status_select($fieldname,$value='') {
    global $lang, $settings;
    $output="";
    $states=array('planned','live','completed');
    if (isset($settings['enable_payment_module']) && $settings['enable_payment_module']=='y') {
        $states[]='balanced';
    }
    $output.='<span class="select is-primary select-compact"><select name="'.$fieldname.'">';
    foreach ($states as $state) {
        $output.='<OPTION value="'.$state.'"';
        if ($state==$value ||
                ((!$value) && $state==$settings['planned'])) {
            $output.=' selected';
        }
        $output.='>'.$lang['session_status_'.$state].'</OPTION>'."\n";
    }
    $output.='</select></span>';
    return $output;
}

function session__check_lab_time_clash($entry) {
    global $lang;

    if (isset($entry['session_start'])) {
        $notice=lang('overlapping_sessions');
        $this_start_time=$entry['session_start'];
        $this_end_time = ortime__add_hourmin_to_sesstime($this_start_time,
            $entry['session_duration_hour'],$entry['session_duration_minute']);
    } else {
        $notice=lang('overlapping_lab_reservation');
        $this_start_time=$entry['event_start'];
        $this_end_time=$entry['event_stop'];
    }

    if (!isset($entry['event_id'])) {
        $entry['event_id']='';
    }
    if (!isset($entry['session_id'])) {
        $entry['session_id']='';
    }

    $pars=array(':this_end_time'=>$this_end_time,
                ':this_start_time'=>$this_start_time,
                ':session_id'=>$entry['session_id'],
                ':laboratory_id'=>$entry['laboratory_id']);
    $query="SELECT session_start,
            date_format(date_add(session_start*100,
            INTERVAL concat(session_duration_hour,':',session_duration_minute) HOUR_MINUTE),'%Y%m%d%H%i')
            as session_stop,
             ".table('experiments').".*, ".table('sessions').".*
            FROM ".table('experiments').", ".table('sessions')."
            WHERE ".table('experiments').".experiment_id=".table('sessions').".experiment_id
            AND ".table('experiments').".experiment_type!='internet'
            AND session_id!=:session_id
            AND laboratory_id=:laboratory_id
            HAVING NOT (session_start >= :this_end_time OR session_stop <= :this_start_time)
            ORDER BY session_start";
    $result=or_query($query,$pars);

    while ($osession=pdo_fetch_assoc($result)) {
        message('<UL><LI>'.
                    $notice.': <A HREF="session_edit.php?session_id='.$osession['session_id'].'">'.
                    $osession['experiment_name'].' - '.
                    session__build_name($osession).'</A></UL>','warning');
    }

    $pars=array(':this_end_time'=>$this_end_time,
                ':this_start_time'=>$this_start_time,
                ':event_id'=>$entry['event_id'],
                ':laboratory_id'=>$entry['laboratory_id']);
    $query="SELECT event_start, event_stop,
           ".table('events').".*
            FROM ".table('events')."
            WHERE laboratory_id=:laboratory_id
            AND event_id!=:event_id
            AND NOT (event_start >= :this_end_time OR event_stop <= :this_start_time)
            ORDER BY event_start";
    $result=or_query($query,$pars);

    while ($osession=pdo_fetch_assoc($result)) {
        $ostart_string=ortime__format(ortime__sesstime_to_unixtime($osession['event_start']));
        $ostop_string=ortime__format(ortime__sesstime_to_unixtime($osession['event_stop']));
        message('<UL><LI>'.
                    $notice.': <A HREF="events_edit.php?event_id='.$osession['event_id'].'">'.
                    $ostart_string.' - '.$ostop_string.'</A></UL>','warning');
    }
}



function session__get_status($session,$tlang="",$reg="") {
    global $settings;
    if ($tlang=="") {
        $tlang=$settings['admin_standard_language'];
    }

    if ($reg=="") {
        $reg=experiment__count_participate_at($session['experiment_id'],$session['session_id']);
    }
    if ($reg < $session['part_needed']) {
        $status=load_language_symbol('not_enough_participants',$tlang);
    } elseif ($reg < $session['part_needed'] + $session['part_reserve']) {
        $status=load_language_symbol('not_enough_reserve',$tlang);
    } else {
        $status=load_language_symbol('complete',$tlang);
    }
    return $status;
}



function session__build_name($pack,$language="") {
    global $lang, $settings;
    if (!$language) {
        if (isset($lang['lang'])) {
            $thislang=$lang['lang'];
        } else {
            $thislang=$settings['public_standard_language'];
        }
    } else {
        $thislang=$language;
    }
    $start_time=$pack['session_start'];
    $end_time = ortime__add_hourmin_to_sesstime($start_time,
        $pack['session_duration_hour'],$pack['session_duration_minute']);
    $start_utime=ortime__sesstime_to_unixtime($start_time);
    $end_utime=ortime__sesstime_to_unixtime($end_time);
    $is_rtl=lang__is_rtl($thislang);

    $start_full=ortime__format($start_utime,'hide_second:true',$thislang);
    $end_part=ortime__format($end_utime,'hide_date:true,hide_second:true',$thislang);
    if ($is_rtl) {
        $session_time_string=$end_part.'-'.$start_full;
    } else {
        $session_time_string=$start_full.'-'.$end_part;
    }

    if (or_setting('include_weekday_in_session_name')) {
        $weekday=ortime__get_weekday($start_utime,$thislang);
        if ($is_rtl) {
            $session_time_string.=', '.$weekday;
        } else {
            $session_time_string=$weekday.', '.$session_time_string;
        }
    }
    return $session_time_string;
}


function sessions__get_first_last_date($session_list) {
    $first_d=0;
    $last_d=0;
    foreach ($session_list as $s) {
        if ($first_d==0 || $s['session_start']<$first_d) {
            $first_d=$s['session_start'];
        }
        if ($last_d==0 || $s['session_start']>$last_d) {
            $last_d=$s['session_start'];
        }
    }
    $first_s=($first_d==0) ? '???' : ortime__format(ortime__sesstime_to_unixtime($first_d),'hide_time:true');
    $last_s=($last_d==0) ? '???' : ortime__format(ortime__sesstime_to_unixtime($last_d),'hide_time:true');
    return array('first'=>$first_s,'last'=>$last_s);
}


function sessions__get_registration_end($alist,$session_id="",$experiment_id="") {
    if ($session_id) {
        $pars=array(':session_id'=>$session_id);
        $query="SELECT * FROM ".table('sessions')." WHERE session_id=:session_id";
        $alist=orsee_query($query,$pars);
    } elseif ($experiment_id) {
        $pars=array(':experiment_id'=>$experiment_id);
        $query="SELECT ".table('sessions').".*
            FROM ".table('experiments').", ".table('sessions')."
            WHERE ".table('experiments').".experiment_id=".table('sessions').".experiment_id
            AND experiment_type='laboratory'
            AND ".table('experiments').".experiment_id=:experiment_id
            ORDER BY session_start DESC
            LIMIT 1";
        $alist=orsee_query($query,$pars);
    }
    $registration_end=ortime__add_hourmin_to_sesstime($alist['session_start'],0-$alist['registration_end_hours']);
    return ortime__sesstime_to_unixtime($registration_end);
}

function sessions__get_cancellation_deadline($alist,$session_id="",$experiment_id="") {
    global $settings;
    if ($session_id) {
        $pars=array(':session_id'=>$session_id);
        $query="SELECT * FROM ".table('sessions')." WHERE session_id=:session_id";
        $alist=orsee_query($query,$pars);
    } elseif ($experiment_id) {
        $pars=array(':experiment_id'=>$experiment_id);
        $query="SELECT ".table('sessions').".*
            FROM ".table('experiments').", ".table('sessions')."
            WHERE ".table('experiments').".experiment_id=".table('sessions').".experiment_id
            AND experiment_type='laboratory'
            AND ".table('experiments').".experiment_id=:experiment_id
            ORDER BY session_start DESC
            LIMIT 1";
        $alist=orsee_query($query,$pars);
    }
    if (isset($settings['subject_cancellation_hours_before_start']) && $settings['subject_cancellation_hours_before_start']>0) {
        $deadline_hours=$settings['subject_cancellation_hours_before_start'];
    } else {
        $deadline_hours=0;
    }
    $cancellation__deadline=ortime__add_hourmin_to_sesstime($alist['session_start'],0-$deadline_hours);
    return ortime__sesstime_to_unixtime($cancellation__deadline);
}


function sessions__get_reminder_time($alist,$session_id="") {
    if ($session_id) {
        $pars=array(':session_id'=>$session_id);
        $query="SELECT * FROM ".table('sessions')." WHERE session_id=:session_id";
        $alist=orsee_query($query,$pars);
    }
    $reminder_time=ortime__add_hourmin_to_sesstime($alist['session_start'],0-$alist['session_reminder_hours']);
    return ortime__sesstime_to_unixtime($reminder_time);
}


function sessions__session_full($session_id,$thissession=array()) {
    if (!isset($thissession['session_id'])) {
        $thissession=orsee_db_load_array("sessions",$session_id,"session_id");
    }
    $reg=experiment__count_participate_at($thissession['experiment_id'],$thissession['session_id']);
    if ($reg < $thissession['part_needed'] + $thissession['part_reserve']) {
        $session_full=false;
    } else {
        $session_full=true;
    }
    return $session_full;
}



function sessions__get_experiment_id($session_id) {
    $pars=array(':session_id'=>$session_id);
    $query="SELECT experiment_id
            FROM ".table('sessions')."
            WHERE session_id=:session_id";
    $res=orsee_query($query,$pars);
    if (isset($res['experiment_id'])) {
        $experiment_id=$res['experiment_id'];
    } else {
        $experiment_id="";
    }
    return $experiment_id;
}

function sessions__get_sessions($experiment_id) {
    // load sessions of an experiment
    $pars=array(':experiment_id'=>$experiment_id);
    $query="SELECT *
            FROM ".table('sessions')."
            WHERE experiment_id= :experiment_id
            ORDER BY session_start";
    $result=or_query($query,$pars);
    $sessions=array();
    while ($line=pdo_fetch_assoc($result)) {
        $sessions[$line['session_id']]=$line;
    }
    return $sessions;
}

function sessions__load_sessions_for_ids($ids=array()) {
    $sessions=array();
    if (count($ids)>0) {
        $par_array=id_array_to_par_array($ids);
        $query="SELECT * FROM ".table('sessions')."
                WHERE session_id IN (".implode(',',$par_array['keys']).")";
        $result=or_query($query,$par_array['pars']);
        while ($line=pdo_fetch_assoc($result)) {
            $sessions[$line['session_id']]=$line;
        }
    }
    return $sessions;
}

?>
