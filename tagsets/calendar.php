<?php
// part of orsee. see orsee.org


//returns an array of every day of the month. first dimension
//represents the week, second dimension represents the weekday
function days_in_month($month, $year) {
    global $lang;
    $dates = array();
    $time = mktime(0,0,0,$month,1,$year);
    if (!isset($lang['format_datetime_firstdayofweek_0:Su_1:Mo']) || (!$lang['format_datetime_firstdayofweek_0:Su_1:Mo'])) {
        $firstdayofweek = 7;
    } else {
        $firstdayofweek = 1;
    }
    $firstdays = 1;
    for ($i = 1; $i <= date("t", $time); $i++) {
        $time = mktime(0,0,0,$month,$i,$year);
        //subtract 'weeks up to this month' from 'weeks up to
        //previous month' to get the number of weeks in this month
        //$weekNum = date("W", $time) - date("W",  strtotime(date("Y-m-01", $time))) + 1;
        //$weekNum = date("W", $time) - date("W", strtotime(date("Y-m-01", $time))) + 1;

        //count the number of firstdays( Weeks )
        if (date('N', $time) == $firstdayofweek && $i == 1) {
            $firstdays = 0;
        }
        if (date('N', $time) == $firstdayofweek) {
            $firstdays++;
        }
        $day = date('d', $time);
        $dayOfWeek = date('N', $time);
        if ($firstdayofweek==7) {
            $dayOfWeek++;
        }
        if ($dayOfWeek>7) {
            $dayOfWeek=$dayOfWeek-7;
        }
        $dates[$firstdays][$dayOfWeek] = $day;
    }
    return $dates;
}

function date__skip_months($count,$time=0) {
    if ($time==0) {
        $time=time();
    }
    $td=getdate($time);
    $tmonth=$td['mon']+$count;
    $newtimestamp=mktime(0,0,1,$tmonth,1,$td['year']);
    return $newtimestamp;
}

function date__skip_years($count,$time=0) {
    if ($time==0) {
        $time=time();
    }
    $td=getdate($time);
    $newyear=$td['year']+$count;
    $newtimestamp=mktime(0,0,1,1,1,$newyear);
    return $newtimestamp;
}

function date__year_start_time($time=0) {
    if ($time==0) {
        $time=time();
    }
    $td=getdate($time);
    $newtimestamp=mktime(0,0,1,1,1,$td['year']);
    return $newtimestamp;
}

function calendar__days_in_month($month, $year) {
    // calculate number of days in a month
    return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
}


function calendar__get_events($admin = false, $start_time = 0, $end_time = 0, $admin_id = false, $split_events=false, $laboratory_id=false) {
    $events = array();
    global $lang, $settings, $settings__root_url, $color;

    $labs=laboratories__get_laboratories();
    $sessions=array();
    $signed_up=array();
    $lines=array();

    //build query to get all sessions
    $query="SELECT * FROM ".table('sessions').", ".table('experiments').
            " WHERE ".table('sessions').".experiment_id=".table('experiments').".experiment_id";
    //don't include hidden if not admin
    if (!$admin) {
        $query.=" AND ".table('experiments').".hide_in_cal='n' ";
    }
    // don't include planned sessions if not admin and setting is disabled
    if (!$admin & $settings['hide_planned_sessions_in_public_calendar']=='y') {
        $query.=" AND ".table('sessions').".session_status!='planned' ";
    }
    //only events between start and end time parameters
    $pars=array(':end_time'=>date("Ym320000", $end_time), // lowerr than "32nd day" of end time month
                ':start_time'=>date("Ym000000", $start_time)); // larger than "0st day" of start time month
    $query .= " AND session_start <= :end_time ";
    $query .= " AND session_start >= :start_time ";
    if ($admin_id) {
        $query.=" AND ".table('experiments').".experimenter LIKE :admin_id ";
        $pars[':admin_id']='%|'.$admin_id.'|%';
    }
    if ($laboratory_id) {
        $query.=" AND ".table('sessions').".laboratory_id=:laboratory_id ";
        $pars[':laboratory_id']=$laboratory_id;
    }

    $result=or_query($query,$pars);
    $exp_colors = array();
    $exp_colors_used = 0;
    $exp_colors_defined_list=explode(",",$color['calendar_public_experiment_sessions']);
    while ($line=pdo_fetch_assoc($result)) {
        $lines[]=$line;
        $sessions[]=$line['session_id'];
    }

    if (count($sessions)>0) {
        $query="SELECT session_id, COUNT(*) as regcount FROM ".table('participate_at')."
                WHERE session_id IN (".implode(",",$sessions).")
                GROUP BY session_id";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            $signed_up[$line['session_id']]=$line['regcount'];
        }
    }

    foreach ($lines as $line) {
        $tmp_new_event = array();
        //get colour
        if (!isset($exp_colors[$line['experiment_id']])) {
            $exp_colors[$line['experiment_id']] = $exp_colors_defined_list[$exp_colors_used];
            $exp_colors_used +=1;
            if ($exp_colors_used > count($exp_colors_defined_list)-1) {
                $exp_colors_used = 0;
            }
        }
        $tmp_new_event['color'] = $exp_colors[$line['experiment_id']];
        //convert to unix time
        $unix_time = ortime__sesstime_to_unixtime($line['session_start']);
        $tmp_new_event['start_time'] = $unix_time;
        //add duration to start time to create end time (in seconds)
        $duration = (($line['session_duration_hour'] * 60) + $line['session_duration_minute']) * 60;
        $tmp_new_event['end_time'] = $unix_time + $duration;
        //formatted time with language features
        $tmp_new_event['display_time'] = ortime__format($unix_time,'hide_date:true,hide_second:true',$lang['lang']).'-'.
                        ortime__format($unix_time + $duration,'hide_date:true,hide_second:true',$lang['lang']);
        if ($admin) {
            $tmp_new_event['title'] = $line['experiment_name'];
        } else {
            $tmp_new_event['title'] = $line['experiment_public_name'];
        }
        if (check_allow('experiment_show')) {
            $tmp_new_event['title_link'] = $settings__root_url.'/admin/experiment_show.php?experiment_id='.$line['experiment_id'];
        }
        $tmp_new_event['participants_link'] = $settings__root_url.'/admin/experiment_participants_show.php?experiment_id=' . $line['experiment_id'] . '&session_id=' . $line['session_id'];
        if (isset($labs[$line['laboratory_id']]['lab_name'])) {
            $tmp_new_event['location'] = $labs[$line['laboratory_id']]['lab_name'];
        } else {
            $tmp_new_event['location'] = lang('unknown_laboratory');
        }
        if (isset($signed_up[$line['session_id']])) {
            $participating = $signed_up[$line['session_id']];
        } else {
            $participating=0;
        }
        $tmp_new_event['participants_needed'] = $line['part_needed'];
        $tmp_new_event['participants_reserve'] = $line['part_reserve'];
        $tmp_new_event['participants_registered'] = $participating;
        //uid (unique identifier) for use by ICS
        $tmp_new_event['uid'] = "session_" . $line['session_id'] . "@" .  $settings__root_url;
        $tmp_new_event['type'] = "experiment_session";
        if ($participating < $line['part_needed']) {
            $tmp_new_event['status'] = "not_enough_participants";
        } elseif ($participating < ($line['part_needed'] + $line['part_reserve'])) {
            $tmp_new_event['status'] = "not_enough_reserve";
        } else {
            $tmp_new_event['status'] = "complete";
        }
        $tmp_new_event['experimenters'] = $line['experimenter'];

        $tmp_new_event['id'] = $line['session_id'];

        $events[date("Y",$tmp_new_event['start_time'])*10000+date("n",$tmp_new_event['start_time'])*100+date("j",$tmp_new_event['start_time'])][] = $tmp_new_event;
    }

    //non-experimental laboratory booking events
    $event_categories=lang__load_lang_cat('events_category');
    $pars=array(':end_time'=>date("Ym320000", $end_time), // lowerr than "32nd day" of end time month
                ':start_time'=>date("Ym000000", $start_time)); // larger than "0st day" of start time month
    $query = "SELECT *  FROM ".table('events').
            " WHERE event_start <= :end_time
              AND event_stop >= :start_time";
    if ($admin_id) {
        $query.=" AND ".table('events').".experimenter LIKE :admin_id ";
        $pars[':admin_id']='%|'.$admin_id.'|%';
    }
    if ($laboratory_id) {
        $query.=" AND ".table('events').".laboratory_id=:laboratory_id ";
        $pars[':laboratory_id']=$laboratory_id;
    }
    $result=or_query($query,$pars);
    $exp_colors = array();
    $exp_colors_used = 0;
    $exp_colors_defined_list=explode(",",$color['calendar_event_reservation']);
    while ($line=pdo_fetch_assoc($result)) {
        if ($admin || trim($line['reason_public'])) {
            //get color
            if (!isset($exp_colors[$line['laboratory_id']])) {
                $exp_colors[$line['laboratory_id']] = $exp_colors_defined_list[$exp_colors_used];
                $exp_colors_used +=1;
                if ($exp_colors_used > count($exp_colors_defined_list)-1) {
                    $exp_colors_used = 0;
                }
            }
            $tmp_new_event = array();
            $tmp_new_event['color'] = $exp_colors[$line['laboratory_id']];
            $unix_start_time = ortime__sesstime_to_unixtime($line['event_start']);
            $unix_stop_time = ortime__sesstime_to_unixtime($line['event_stop']);
            $tmp_new_event['start_time'] = $unix_start_time;
            $tmp_new_event['end_time'] = $unix_stop_time;
            $tmp_new_event['display_time'] = ortime__format($unix_start_time,'hide_second:true',$lang['lang']).'-'.
                        ortime__format($unix_stop_time,'hide_second:true',$lang['lang']);
            if (isset($labs[$line['laboratory_id']]['lab_name'])) {
                $tmp_new_event['location'] = $labs[$line['laboratory_id']]['lab_name'];
            } else {
                $tmp_new_event['location'] = $lang['unknown_laboratory'];
            }
            $tmp_new_event['type'] = "location_reserved";
            if ($admin) {
                $tmp_new_event['title'] = $line['reason'];
                if (trim($line['reason_public'])) {
                    $tmp_new_event['title'] .= ' ('.$line['reason_public'].')';
                }
                if ($line['event_category'] && isset($event_categories[$line['event_category']])) {
                    if (!$tmp_new_event['title']) {
                        $tmp_new_event['title']=$event_categories[$line['event_category']];
                    }
                }
                if (!$tmp_new_event['title']) {
                    $tmp_new_event['title'] = lang('laboratory_booked');
                }
            } else {
                $tmp_new_event['title'] = $line['reason_public'];
                if (!$tmp_new_event['title']) {
                    $tmp_new_event['title'] = lang('laboratory_booked');
                }
            }
            $tmp_new_event['edit_link'] = $settings__root_url."/admin/events_edit.php?event_id=" . $line['event_id'];
            $tmp_new_event['experimenters'] = $line['experimenter'];
            $tmp_new_event['id'] = $line['event_id'];
            $tmp_new_event['uid'] = "booking_" . $line['event_id'] . "@" .  $settings__root_url;
            if ($split_events) {
                $continue=true;
                $today=$unix_start_time;
                while ($continue) {
                    if (date("Ymd",$today)==date("Ymd",$unix_start_time)) {
                        $tmp_new_event['start_time']=$unix_start_time;
                    } else {
                        $tmp_new_event['start_time']=mktime($settings['laboratory_opening_time_hour'],$settings['laboratory_opening_time_minute'],0,date("n",$today),date("j",$today),date("Y",$today));
                    }
                    if (date("Ymd",$today)>=date("Ymd",$unix_stop_time)) {
                        $tmp_new_event['end_time']=$unix_stop_time;
                        $continue=false;
                    } else {
                        $tmp_new_event['end_time']=mktime($settings['laboratory_closing_time_hour'],$settings['laboratory_closing_time_minute'],0,date("n",$today),date("j",$today),date("Y",$today));
                    }
                    $tmp_new_event['display_time'] = ortime__format($tmp_new_event['start_time'],'hide_date:true,hide_second:true',lang('lang')).'-'.
                                            ortime__format($tmp_new_event['end_time'],'hide_date:true,hide_second:true',lang('lang'));
                    $events[date("Y",$today)*10000+date("n",$today)*100+date("j",$today)][] = $tmp_new_event;
                    $today=strtotime("+1 day", $today);
                }
            } else {
                $events[date("Y",$tmp_new_event['start_time'])*10000+date("n",$tmp_new_event['start_time'])*100+date("j",$tmp_new_event['start_time'])][] = $tmp_new_event;
            }
        }
    }

    return $events;
}

function calendar__display_calendar($admin = false) {
    global $lang, $color, $settings;
    $displayfrom = time();
    if (isset($_REQUEST['displayfrom'])) {
        $displayfrom = $_REQUEST['displayfrom'];
    }
    $wholeyear = false;
    if (isset($_REQUEST['wholeyear']) && $admin) {
        $wholeyear = true;
    }
    $laboratory_id=false;
    $experimenter_id=false;
    $labs=laboratories__get_laboratories();
    $experimenters=experiment__load_experimenters();
    $calendar_experimenters=array();
    foreach ($experimenters as $admin_id=>$experimenter) {
        if (isset($experimenter['disabled']) && $experimenter['disabled']=='y') {
            continue;
        }
        $calendar_experimenters[$admin_id]=$experimenter;
    }
    if (isset($_REQUEST['laboratory_id']) && $_REQUEST['laboratory_id'] && $admin) {
        if (isset($labs[$_REQUEST['laboratory_id']])) {
            $laboratory_id=$_REQUEST['laboratory_id'];
        }
    }
    if (isset($_REQUEST['experimenter_id']) && $_REQUEST['experimenter_id'] && $admin) {
        if (isset($calendar_experimenters[$_REQUEST['experimenter_id']])) {
            $experimenter_id=$_REQUEST['experimenter_id'];
        }
    }
    $filter_url_params=array();
    if ($laboratory_id) {
        $filter_url_params[]='laboratory_id='.urlencode((string)$laboratory_id);
    }
    if ($experimenter_id) {
        $filter_url_params[]='experimenter_id='.urlencode((string)$experimenter_id);
    }
    $filter_urlstring=implode('&',$filter_url_params);

    $statusdata = array("not_enough_participants" => array(
            "color" => ($admin) ? 'var(--color-session-not-enough-participants)' : 'var(--color-session-public-free-places)',
            "message" => ($admin) ? $lang["not_enough_participants"] : lang('free_places')
        ),
        "not_enough_reserve" => array(
            "color" => ($admin) ? 'var(--color-session-not-enough-reserve)' : 'var(--color-session-public-free-places)',
            "message" => ($admin) ? $lang["not_enough_reserve"] : lang('free_places')
        ),
        "complete" => array(
            "color" => ($admin) ? 'var(--color-session-complete)' : 'var(--color-session-public-complete)',
            "message" => $lang["complete"]
        )
    );

    echo '<div class="orsee-calendar">';

    //start building calendar
    $displayfrom_lower = $displayfrom;
    $displayfrom_upper = $displayfrom_lower;
    if ($wholeyear && $admin) {
        $displayfrom_upper = mktime(0, 0, 0, 1, 1, date('Y', $displayfrom)+1);
    }
    $results = calendar__get_events($admin, $displayfrom_lower, $displayfrom_upper, $experimenter_id, true, $laboratory_id);
    $buttons1="";
    $buttons2="";

    if ($admin) {
        $buttons1.='<div class="orsee-calendar-toolbar">';
        $buttons1.='<div class="orsee-calendar-toolbar-left">';
        if ($wholeyear) {
            $buttons1.=button_link("?".($filter_urlstring!=='' ? $filter_urlstring : ''),lang('current_month'));
        } else {
            $buttons1.=button_link("?wholeyear=true&displayfrom=" . mktime(0, 0, 0, 1, 1, date('Y', $displayfrom)).($filter_urlstring!=='' ? "&".$filter_urlstring : ''),lang('whole_year'));
        }
        $buttons1.='</div>';
        $buttons1.='<div class="orsee-calendar-toolbar-center">'.
            button_link('events_edit.php',lang('create_event'),'plus-circle').
            '<div class="orsee-font-compact">'.lang('for_session_time_reservation_please_use_experiments').'</div></div>';
        $buttons1.='<div class="orsee-calendar-toolbar-right">'.
            button_link('calendar_main_print_pdf.php?displayfrom='.$displayfrom.'&wholeyear='.$wholeyear.($filter_urlstring!=='' ? "&".$filter_urlstring : ''),
                lang('print_version'),'print','','target="_blank"').
            '</div>';
        $buttons1.='</div>';
    }
    $buttons2 .= '<div class="orsee-calendar-toolbar">';
    $buttons2 .= '<div class="orsee-calendar-toolbar-left"></div>';
    $buttons2 .= '<div class="orsee-calendar-toolbar-center">';
    if ($admin) {
        $buttons2 .= '<div class="orsee-calendar-labs">';
        $buttons2 .= '<form action="calendar_main.php" method="get">';
        $buttons2 .= '<input type="hidden" name="displayfrom" value="'.htmlspecialchars((string)$displayfrom,ENT_QUOTES).'">';
        if ($wholeyear) {
            $buttons2 .= '<input type="hidden" name="wholeyear" value="true">';
        }
        if (count($labs)>1) {
            $buttons2 .= '<span class="select is-primary select-compact"><select name="laboratory_id" onchange="this.form.submit()">';
            $buttons2 .= '<option value=""'.(!$laboratory_id ? ' selected' : '').'>'.lang('all_laboratories').'</option>';
            foreach ($labs as $lab_id=>$lab) {
                $buttons2 .= '<option value="'.htmlspecialchars((string)$lab_id,ENT_QUOTES).'"'.((string)$laboratory_id===(string)$lab_id ? ' selected' : '').'>'.htmlspecialchars((string)$lab['lab_name'],ENT_QUOTES).'</option>';
            }
            $buttons2 .= '</select></span>';
        }
        if (count($calendar_experimenters)>1) {
            if (count($labs)>1) {
                $buttons2 .= '&nbsp;';
            }
            $buttons2 .= '<span class="select is-primary select-compact"><select name="experimenter_id" onchange="this.form.submit()">';
            $buttons2 .= '<option value=""'.(!$experimenter_id ? ' selected' : '').'>'.lang('all_experimenters').'</option>';
            foreach ($calendar_experimenters as $admin_id=>$experimenter) {
                if (!isset($experimenter['adminname'])) {
                    continue;
                }
                $exp_label=trim((string)$experimenter['lname']).', '.trim((string)$experimenter['fname']);
                if (trim($exp_label,', ')==='') {
                    $exp_label=(string)$experimenter['adminname'];
                }
                $buttons2 .= '<option value="'.htmlspecialchars((string)$admin_id,ENT_QUOTES).'"'.((string)$experimenter_id===(string)$admin_id ? ' selected' : '').'>'.htmlspecialchars($exp_label,ENT_QUOTES).'</option>';
            }
            $buttons2 .= '</select></span>';
        }
        $buttons2 .= '</form>';
        $buttons2 .= '</div>';
    }
    $buttons2 .= '</div>';
    $buttons2 .= '<div class="orsee-calendar-toolbar-right"></div></div>';

    echo $buttons1;
    echo $buttons2;
    $month_names=explode(",",$lang['month_names']);
    $calendar__weekdays=explode(",",$lang['format_datetime_weekday_abbr']);
    //loop through each month
    for ($itime = $displayfrom_lower; $itime <= $displayfrom_upper; $itime = date__skip_months(1, $itime)) {
        $year = date("Y", $itime);
        $month = date("m", $itime);
        $weeks = days_in_month($month, $year);
        echo '<section class="orsee-calendar-month">';
        echo '<div class="orsee-calendar-month-head orsee-panel-title">';
        echo '<a class="orsee-calendar-month-nav" href="?displayfrom='.date__skip_months(-1, $displayfrom).($filter_urlstring!=='' ? '&'.$filter_urlstring : '').'" aria-label="'.lang('previous').'"><i class="fa fa-chevron-circle-'.(lang__is_rtl() ? 'right' : 'left').'"></i></a>';
        echo '<span class="orsee-calendar-month-title">' . $month_names[($month-1)] . ' ' . $year . '</span>';
        echo '<a class="orsee-calendar-month-nav" href="?displayfrom='.date__skip_months(1, $displayfrom).($filter_urlstring!=='' ? '&'.$filter_urlstring : '').'" aria-label="'.lang('next').'"><i class="fa fa-chevron-circle-'.(lang__is_rtl() ? 'left' : 'right').'"></i></a>';
        echo '</div>';
        echo '<div class="orsee-calendar-weekdays">';
        $calendar__weekday_fallback=array('Su','Mo','Tu','We','Th','Fr','Sa');
        for ($i3 = 1; $i3 <= 7; ++$i3) {
            if (!isset($lang['format_datetime_firstdayofweek_0:Su_1:Mo']) || (!$lang['format_datetime_firstdayofweek_0:Su_1:Mo'])) {
                $wdindex = $i3-1;
            } else {
                $wdindex = $i3;
                if ($wdindex==7) {
                    $wdindex=0;
                }
            }
            $weekday_label = isset($calendar__weekdays[$wdindex]) ? trim($calendar__weekdays[$wdindex]) : '';
            if ($weekday_label === '') {
                $weekday_label = $calendar__weekday_fallback[$wdindex];
            }
            echo '<div class="orsee-calendar-weekday">' . $weekday_label . '</div>';
        }
        echo '</div>';
        echo '<div class="orsee-calendar-grid">';
        for ($i2 = 1; $i2 <= count($weeks); ++$i2) {
            for ($i3 = 1; $i3 <= 7; ++$i3) {
                if (isset($weeks[$i2][$i3])) {
                    //the date is the key of the $results array for easy searching
                    $today = $year*10000+$month*100+$weeks[$i2][$i3];
                    $realtoday = date("Y")*10000+date("m")*100+date("d");
                    $dayclass='orsee-calendar-day';
                    if ($today==$realtoday) {
                        $dayclass.=' is-today';
                    }
                    echo '<div class="'.$dayclass.'">';
                    echo '<div class="orsee-calendar-daynum">';
                    echo (int)$weeks[$i2][$i3];
                    echo '</div>';
                    echo '<div class="orsee-calendar-daybody">';
                    if (isset($results[$today])) {
                        foreach ($results[$today] as $item) {
                            $title = htmlspecialchars($item['title']);
                            if ($item['type'] == "location_reserved" && $admin && check_allow('events_edit') && isset($item['edit_link'])) {
                                $title = '<a href="' . $item['edit_link'] . '">' . $title . '</a>';
                            } elseif (isset($item['title_link'])) {
                                $title = '<a href="' . $item['title_link'] . '">' . $title . '</a>';
                            }
                            echo '<div class="orsee-calendar-event '.($item['type']=='location_reserved' ? 'is-reservation' : 'is-session').'" style="--color-calendar-event-color: '.$item['color'].';">';
                            echo '<div class="orsee-calendar-event-time">';
                            echo $item['display_time'];
                            echo '</div>';
                            echo '<div class="orsee-calendar-event-location">'.htmlspecialchars($item['location']).'</div>';
                            if ($admin || $settings['public_calendar_hide_exp_name']!='y') {
                                echo '<div class="orsee-calendar-event-title">' . $title . '</div>';
                            } else {
                                echo '<div class="orsee-calendar-event-title">'.lang('calendar_experiment_session').'</div>';
                            }

                            if ($admin) {
                                echo '<div class="orsee-calendar-event-meta">';
                                echo experiment__list_experimenters($item['experimenters'],true,true);
                                echo '</div>';
                            }
                            if ($item['type'] == "location_reserved") {
                            } elseif ($item['type'] == "experiment_session") {
                                echo '<div class="orsee-calendar-event-status" style="color: ' . $statusdata[$item['status']]['color'] . ';">';

                                if ($admin) {
                                    $participants_counts = $item['participants_registered'] . " (" . $item['participants_needed']. "," . $item['participants_reserve'] . ")";
                                    if (check_allow('experiment_show_participants')) {
                                        echo ' <a href="' . $item['participants_link'] . '" class="orsee-dense-session-count-link" title="' . lang('participants') . '" style="color: inherit;">' . $participants_counts . '</a>';
                                    } else {
                                        echo " " . $participants_counts;
                                    }
                                } else {
                                    echo $statusdata[$item['status']]['message'];
                                }
                                echo '</div>';
                            }

                            $pill_dot_color=$item['color'];
                            if (isset($item['status']) && isset($statusdata[$item['status']]['color'])) {
                                $pill_dot_color=$statusdata[$item['status']]['color'];
                            }
                            $pill_start_time = ortime__format($item['start_time'],'hide_date:true,hide_second:true',$lang['lang']);
                            echo '<button type="button" class="orsee-calendar-pill" style="--color-calendar-pill-color: '.$item['color'].';">';
                            echo '<span class="orsee-calendar-pill-time">'.htmlspecialchars($pill_start_time).'</span>';
                            if (isset($item['status']) && isset($statusdata[$item['status']]['color'])) {
                                echo '<span class="orsee-calendar-pill-dot" style="--color-calendar-pill-dot-color: '.$pill_dot_color.'"></span>';
                            }
                            echo '</button>';
                            echo '</div>';
                        }
                    }
                    echo '</div>';
                    echo '</div>';
                } else {
                    echo '<div class="orsee-calendar-day is-empty"><div class="orsee-calendar-daynum">&nbsp;</div><div class="orsee-calendar-daybody"></div></div>';
                }
            }
        }
        echo '</div>';
        echo '</section>';
    }
    echo $buttons2;
    echo '<script>
    (function() {
        if (window.__orseeCalendarMobilePopoverBound) return;
        window.__orseeCalendarMobilePopoverBound = true;

        var popover = document.createElement("div");
        popover.className = "orsee-calendar-popover";
        popover.innerHTML =
            "<div class=\"orsee-calendar-popover-card\">" +
            "<div class=\"orsee-calendar-popover-body\"></div>" +
            "</div>";
        var popoverHost = document.querySelector(".orsee") || document.body;
        popoverHost.appendChild(popover);

        var card = popover.querySelector(".orsee-calendar-popover-card");
        var body = popover.querySelector(".orsee-calendar-popover-body");

        function closePopover() {
            popover.classList.remove("is-open");
            body.innerHTML = "";
            card.style.left = "-9999px";
            card.style.right = "auto";
            card.style.top = "-9999px";
        }

        function showPopover(pill, eventCard) {
            var clone = eventCard.cloneNode(true);
            clone.querySelectorAll(".orsee-calendar-pill").forEach(function(el) { el.remove(); });
            body.innerHTML = "";
            body.appendChild(clone);
            var eventColor = eventCard.style.getPropertyValue("--color-calendar-event-color");
            if (!eventColor) eventColor = "var(--color-calendar-entry-default-background)";
            card.style.setProperty("--color-calendar-event-color", eventColor);

            popover.classList.add("is-open");

            var rect = pill.getBoundingClientRect();
            var isRTL = (getComputedStyle(document.documentElement).direction === "rtl");
            var maxLeft = window.innerWidth - card.offsetWidth - 8;
            var left = (isRTL ? (rect.right - card.offsetWidth) : rect.left);
            if (left > maxLeft) left = maxLeft;
            if (left < 8) left = 8;
            card.style.left = left + "px";
            card.style.right = "auto";

            var top = rect.bottom + 6;
            if (top + card.offsetHeight > window.innerHeight - 8) {
                top = rect.top - card.offsetHeight - 6;
            }
            if (top < 8) top = 8;

            card.style.top = top + "px";
        }

        document.addEventListener("click", function(e) {
            if (!window.matchMedia("(max-width: 740px)").matches) {
                closePopover();
                return;
            }

            var pill = e.target.closest(".orsee-calendar-pill");
            if (pill) {
                e.preventDefault();
                var eventCard = pill.closest(".orsee-calendar-event");
                if (!eventCard) return;
                showPopover(pill, eventCard);
                return;
            }

            if (!e.target.closest(".orsee-calendar-popover")) {
                closePopover();
            }
        });

        window.addEventListener("resize", closePopover);
        window.addEventListener("scroll", closePopover, true);
        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape") closePopover();
        });
    })();
    </script>';
    echo '</div>';
}


/// ics export functions

function calendar__gen_ics_token($admin_id,$password) {
    return md5($admin_id."|-|".$password);
}

function calendar__get_user_for_ics_token($icstoken) {
    $pars=array(':icstoken'=>$icstoken);
    $query="SELECT * FROM ".table('admin').
           " WHERE MD5(concat(admin_id,'|-|',password_crypt))=:icstoken";
    $result=or_query($query,$pars);
    $admin=false;
    while ($line = pdo_fetch_assoc($result)) {
        $admin = $line;
    }
    return $admin;
}

function calendar__unixtime_to_ical_date($timestamp) {
    $current_time_zone=date_default_timezone_get();
    $done=date_default_timezone_set('UTC');
    $thisdate=date('Ymd\THis\Z', $timestamp);
    $done=date_default_timezone_set($current_time_zone);
    return $thisdate;
}

function calendar__escapestring($string) {
    return preg_replace('/([\,;])/','\\\$1', $string);
}


?>
