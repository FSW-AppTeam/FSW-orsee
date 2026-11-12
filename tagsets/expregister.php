<?php
// part of orsee. see orsee.org

function expregister__get_invitations($participant_id) {
    global $settings;
    $pars=array(':participant_id'=>$participant_id);
    $query="SELECT *
            FROM ".table('participate_at').", ".table('experiments').", ".table('sessions')."
            WHERE ".table('experiments').".experiment_id=".table('sessions').".experiment_id
            AND ".table('experiments').".experiment_id=".table('participate_at').".experiment_id
            AND ".table('participate_at').".participant_id = :participant_id
            AND ".table('sessions').".session_status = 'live'
            AND ".table('participate_at').".session_id=0
            AND ".table('participate_at').".pstatus_id=0 ";
    if ($settings['enable_enrolment_only_on_invite']=='y') {
        $query.= " AND ".table('participate_at').".invited=1 ";
    }
    $query.="AND ".table('experiments').".experiment_type='laboratory'
            ORDER BY ".table('experiments').".experiment_id, session_start";
    $result=or_query($query,$pars);
    $invited=array();
    $inv_experiments=array();
    $invited_by_experiment=array();
    while ($varray = pdo_fetch_assoc($result)) {
        $varray['session_unixtime']=ortime__sesstime_to_unixtime($varray['session_start']);
        $varray['registration_unixtime']=sessions__get_registration_end($varray);
        $varray['session_full']=sessions__session_full("",$varray);
        $now=time();
        if ($now < $varray['session_unixtime']) {
            $varray['session_name']=session__build_name($varray);
            $experiment_id=$varray['experiment_id'];
            if (!isset($invited_by_experiment[$experiment_id])) {
                $invited_by_experiment[$experiment_id]=array(
                    'first_session_unixtime'=>$varray['session_unixtime'],
                    'sessions'=>array()
                );
            } elseif ($varray['session_unixtime'] < $invited_by_experiment[$experiment_id]['first_session_unixtime']) {
                $invited_by_experiment[$experiment_id]['first_session_unixtime']=$varray['session_unixtime'];
            }
            $invited_by_experiment[$experiment_id]['sessions'][]=$varray;
        }
    }
    uasort($invited_by_experiment, function ($a,$b) {
        if ($a['first_session_unixtime']==$b['first_session_unixtime']) {
            return 0;
        }
        return ($a['first_session_unixtime'] < $b['first_session_unixtime']) ? -1 : 1;
    });
    foreach ($invited_by_experiment as $experiment_id=>$expgroup) {
        usort($expgroup['sessions'], function ($a,$b) {
            if ($a['session_unixtime']==$b['session_unixtime']) {
                if ($a['session_id']==$b['session_id']) {
                    return 0;
                }
                return ($a['session_id'] < $b['session_id']) ? -1 : 1;
            }
            return ($a['session_unixtime'] < $b['session_unixtime']) ? -1 : 1;
        });
        $new_experiment=true;
        foreach ($expgroup['sessions'] as $session_row) {
            $session_row['new_experiment']=$new_experiment;
            $new_experiment=false;
            $invited[]=$session_row;
        }
        $inv_experiments[$experiment_id]=true;
    }
    $result=array('inv_experiments'=>$inv_experiments,'invited'=>$invited);
    return $result;
}


function expregister__list_invited_for($participant,$invited=null,$labs=null) {
    global $preloaded_laboratories, $token_string, $settings;

    if (is_array($labs) && count($labs)>0) {
        $preloaded_laboratories=$labs;
    } elseif (!(is_array($preloaded_laboratories) && count($preloaded_laboratories)>0)) {
        $preloaded_laboratories=laboratories__get_laboratories();
    }

    if (!is_array($invited)) {
        $invdata=expregister__get_invitations($participant['participant_id']);
        $invited=$invdata['invited'];
    }
    $now=time();
    if (count($invited)==0) {
        orsee_callout(lang('no_current_invitations'),'note','');
        return array();
    }

    echo '<div class="orsee-public-desktop-list">';
    $labs=array();
    $group_open=false;
    $group_experiment_id='';
    $show_expired=(isset($settings['show_expired_in_session_list']) && $settings['show_expired_in_session_list']=='y');
    $show_full=(isset($settings['show_full_in_session_list']) && $settings['show_full_in_session_list']=='y');
    $shown_sessions=0;
    foreach ($invited as $s) {
        $is_expired=($s['registration_unixtime'] < $now);
        $is_full=(bool)$s['session_full'];
        if (($is_expired && !$show_expired) || ($is_full && !$show_full)) {
            continue;
        }

        if (!$group_open || $group_experiment_id!=$s['experiment_id']) {
            if ($group_open) {
                echo '</div>';
            }
            echo '<div class="orsee-public-session-group">
                    <div class="orsee-public-session-group-title">'.lang('experiment').': '.htmlspecialchars((string)$s['experiment_public_name'],ENT_QUOTES,'UTF-8').'</div>';
            if (or_setting('allow_public_experiment_note') && isset($s['public_experiment_note']) && trim($s['public_experiment_note'])) {
                echo '<div class="orsee-public-session-group-note orsee-note-preline">'.lang('note').': '.htmlspecialchars((string)trim($s['public_experiment_note']),ENT_QUOTES,'UTF-8').'</div>';
            }
            $group_open=true;
            $group_experiment_id=$s['experiment_id'];
        }

        echo '<div class="orsee-public-session-row-desktop">
                <div class="orsee-public-session-row-cell">
                    <div class="orsee-public-session-row-title">'.$s['session_name'].'</div>
                    <div class="orsee-public-session-row-sub">';
        if (isset($preloaded_laboratories[$s['laboratory_id']])) {
            echo $preloaded_laboratories[$s['laboratory_id']]['lab_name'];
        } else {
            echo lang('unknown_laboratory');
        }
        echo '      </div>
                </div>
                <div class="orsee-public-session-row-cell">
                    <div class="orsee-public-session-row-title">'.lang('registration_until').': '.ortime__format($s['registration_unixtime'],'',lang('lang')).'</div>';
        if (or_setting('allow_public_session_note') && isset($s['public_session_note']) && trim($s['public_session_note'])) {
            echo '<div class="orsee-public-session-row-note orsee-note-preline">'.lang('note').': '.htmlspecialchars((string)trim($s['public_session_note']),ENT_QUOTES,'UTF-8').'</div>';
        }
        echo '  </div>
                <div class="orsee-public-session-row-action">';
        if ((!$s['session_full']) && ($s['registration_unixtime'] >= $now)) {
            echo '<form action="participant_show.php" method="POST">';
            if ($token_string) {
                echo '<input type="hidden" name="p" value="'.$participant['participant_id_crypt'].'">';
            }
            echo '  <input type="hidden" name="s" value="'.$s['session_id'].'">
                    <input type="hidden" name="register" value="true">
                    <input type="hidden" name="reallyregister" value="true">
                    '.csrf__field().'
                    <button type="submit" class="button orsee-btn orsee-btn-compact" data-orsee-confirm-submit="1" data-confirm="'.lang('mobile_do_you_really_want_to_signup').'">'.lang('register').'</button>
                  </form>';
        } else {
            if ($s['registration_unixtime'] < $now) {
                echo '<span class="button orsee-btn orsee-btn-compact" disabled style="color: var(--color-session-public-expired) !important;">'.lang('expired').'</span>';
            } else {
                echo '<span class="button orsee-btn orsee-btn-compact" disabled style="color: var(--color-session-public-complete) !important;">'.lang('complete').'</span>';
            }
        }
        echo '  </div>
              </div>';
        $labs[$s['laboratory_id']]=$s['laboratory_id'];
        $shown_sessions++;
    }
    if ($group_open) {
        echo '</div>';
    }
    if ($shown_sessions==0) {
        orsee_callout(lang('no_current_invitations'),'note','');
    }
    echo '</div>';
    return $labs;
}

function expregister__list_invited_for_mobile($invited,$labs) {
    global $settings;
    $invited_labs=array();
    $group_open=false;
    $group_experiment_id='';
    $now=time();
    $show_expired=(isset($settings['show_expired_in_session_list']) && $settings['show_expired_in_session_list']=='y');
    $show_full=(isset($settings['show_full_in_session_list']) && $settings['show_full_in_session_list']=='y');
    foreach ($invited as $s) {
        $is_expired=($s['registration_unixtime'] < $now);
        $is_full=(bool)$s['session_full'];
        if (($is_expired && !$show_expired) || ($is_full && !$show_full)) {
            continue;
        }

        $exp_name=htmlspecialchars((string)$s['experiment_public_name'],ENT_QUOTES,'UTF-8');
        $sess_name=(string)$s['session_name'];
        $lab_name=htmlspecialchars((string)$labs[$s['laboratory_id']]['lab_name'],ENT_QUOTES,'UTF-8');
        $lab_address=htmlspecialchars((string)$labs[$s['laboratory_id']]['lab_address'],ENT_QUOTES,'UTF-8');
        $invited_labs[$s['laboratory_id']]=$s['laboratory_id'];
        $can_register=(!$s['session_full']) && ($s['registration_unixtime'] >= $now);

        if (!$group_open || $group_experiment_id!=$s['experiment_id']) {
            if ($group_open) {
                echo '</div>';
            }
            echo '<div class="orsee-public-session-group">
                    <div class="orsee-public-session-group-title">'.lang('experiment').': '.$exp_name.'</div>';
            if (or_setting('allow_public_experiment_note') && isset($s['public_experiment_note']) && trim($s['public_experiment_note'])) {
                echo '<div class="orsee-public-session-group-note orsee-note-preline">'.lang('note').': '.htmlspecialchars((string)trim($s['public_experiment_note']),ENT_QUOTES,'UTF-8').'</div>';
            }
            $group_open=true;
            $group_experiment_id=$s['experiment_id'];
        }

        if ($can_register) {
            echo '<button type="button" class="orsee-public-session-link" data-pane="invitations"
                    data-session-id="'.$s['session_id'].'"
                    data-exp="'.$exp_name.'"
                    data-exp-note="'.htmlspecialchars((string)(isset($s['public_experiment_note']) ? trim($s['public_experiment_note']) : ''),ENT_QUOTES,'UTF-8').'"
                    data-session-note="'.htmlspecialchars((string)(isset($s['public_session_note']) ? trim($s['public_session_note']) : ''),ENT_QUOTES,'UTF-8').'"
                    data-lab="'.$lab_name.'"
                    data-labaddr="'.$lab_address.'"
                    data-action="register">
                    <span class="orsee-public-session-link-main">
                        <span class="orsee-public-session-link-title">'.$sess_name.'</span>
                        <span class="orsee-public-session-link-sub">'.$lab_name.'</span>
                    </span>
                    <span class="orsee-public-session-link-chevron"><i class="fa fa-angle-'.(lang__is_rtl() ? 'left' : 'right').'" aria-hidden="true"></i></span>
                </button>';
        } else {
            $status_text=($s['registration_unixtime'] < $now) ? lang('expired') : lang('complete');
            $status_color=($s['registration_unixtime'] < $now) ? 'var(--color-session-public-expired)' : 'var(--color-session-public-complete)';
            echo '<div class="orsee-public-session-link is-static">
                    <span class="orsee-public-session-link-main">
                        <span class="orsee-public-session-link-title">'.$sess_name.'</span>
                        <span class="orsee-public-session-link-sub">'.$lab_name.'</span>
                        <span class="orsee-public-session-link-status" style="color: '.$status_color.';">'.$status_text.'</span>
                    </span>
                </div>';
        }
    }
    if ($group_open) {
        echo '</div>';
    } else {
        orsee_callout(lang('mobile_no_current_invitations'),'note','');
    }

    if (count($invited_labs)>0) {
        if (count($invited_labs)>1) {
            $lab_addresses_title=lang('laboratory_addresses');
        } else {
            $lab_addresses_title=lang('laboratory_address');
        }
        echo '<div class="orsee-public-detail-card mt-3">
                <div class="orsee-public-detail-row">
                    <div class="orsee-public-detail-label">'.$lab_addresses_title.'</div>
                </div>';
        foreach ($invited_labs as $lab_id) {
            if (!isset($labs[$lab_id])) {
                continue;
            }
            echo '<div class="orsee-public-detail-row">
                    <div class="orsee-public-detail-label">'.htmlspecialchars((string)$labs[$lab_id]['lab_name'],ENT_QUOTES,'UTF-8').'</div>
                    <div>'.nl2br(htmlspecialchars((string)$labs[$lab_id]['lab_address'],ENT_QUOTES,'UTF-8')).'</div>
                  </div>';
        }
        echo '</div>';
    }

    return $invited_labs;
}

function expregister__get_registrations($participant_id) {
    $pars=array(':participant_id'=>$participant_id);
    $query="SELECT * FROM ".table('experiments').",
        ".table('sessions').", ".table('participate_at')."
        WHERE ".table('experiments').".experiment_id=".table('sessions').".experiment_id
        AND ".table('experiments').".experiment_id=".table('participate_at').".experiment_id
        AND ".table('participate_at').".participant_id = :participant_id
        AND ".table('sessions').".session_id = ".table('participate_at').".session_id
        AND ".table('sessions').".session_status = 'live'
        AND ".table('participate_at').".session_id!=0
        AND ".table('experiments').".experiment_type='laboratory'
        ORDER BY session_start";
    $result=or_query($query,$pars);
    $registered=array();
    while ($varray = pdo_fetch_assoc($result)) {
        $varray['session_unixtime']=ortime__sesstime_to_unixtime($varray['session_start']);
        $now=time();
        if ($now < $varray['session_unixtime']) {
            $varray['session_name']=session__build_name($varray);
            $registered[]=$varray;
        }
    }
    return $registered;
}

function expregister__list_registered_for($participant,$reg_session_id="",$registered=null,$labs=null) {
    global $preloaded_laboratories, $settings, $token_string;

    if (!is_array($registered)) {
        $registered=expregister__get_registrations($participant['participant_id']);
    }

    if (is_array($labs) && count($labs)>0) {
        $preloaded_laboratories=$labs;
    } elseif (!(is_array($preloaded_laboratories) && count($preloaded_laboratories)>0)) {
        $preloaded_laboratories=laboratories__get_laboratories();
    }

    if (count($registered)==0) {
        orsee_callout(lang('mobile_no_current_registrations'),'note','');
        return array();
    }

    $allow_subject_cancellation=(isset($settings['allow_subject_cancellation']) && $settings['allow_subject_cancellation']=='y');

    echo '<div class="orsee-public-session-group-title">'.lang('experiments_already_registered_for').'</div>
          <div class="orsee-public-desktop-list">';
    $labs=array();
    foreach ($registered as $s) {
        $session_note='';
        if (or_setting('allow_public_session_note') && isset($s['public_session_note']) && trim($s['public_session_note'])) {
            $session_note=trim($s['public_session_note']);
        }
        $row_class='orsee-public-session-row-desktop orsee-public-enrolment-row-desktop';
        if ($allow_subject_cancellation) {
            $row_class.=' has-cancel-actions';
        }
        if ($session_note) {
            $row_class.=' has-session-note';
        }

        echo '<div class="'.$row_class.'">
                <div class="orsee-public-session-row-cell">
                    <div class="orsee-public-session-row-title"><strong>'.$s['session_name'].'</strong></div>
                    <div class="orsee-public-session-row-sub">';
        if (isset($preloaded_laboratories[$s['laboratory_id']])) {
            echo $preloaded_laboratories[$s['laboratory_id']]['lab_name'];
        } else {
            echo lang('unknown_laboratory');
        }
        echo '      </div>';
        if ($session_note) {
            echo '<div class="orsee-public-enrolment-row-session-note orsee-note-preline">'.lang('note').': '.htmlspecialchars((string)$session_note,ENT_QUOTES,'UTF-8').'</div>';
        }
        echo '  </div>
                <div class="orsee-public-session-row-cell">';
        echo '<div class="orsee-public-session-row-title">'.lang('experiment').': '.htmlspecialchars((string)$s['experiment_public_name'],ENT_QUOTES,'UTF-8').'</div>';
        if (or_setting('allow_public_experiment_note') && isset($s['public_experiment_note']) && trim($s['public_experiment_note'])) {
            echo '<div class="orsee-public-session-row-note orsee-note-preline">'.lang('note').': '.htmlspecialchars((string)trim($s['public_experiment_note']),ENT_QUOTES,'UTF-8').'</div>';
        }
        echo '</div>';
        if ($allow_subject_cancellation) {
            echo '<div class="orsee-public-session-row-action orsee-public-enrolment-row-action">';
            $s['cancellation_deadline']=sessions__get_cancellation_deadline($s);
            if ($s['cancellation_deadline']>time()) {
                echo '<form action="participant_show.php" method="POST">';
                if ($token_string) {
                    echo '<input type="hidden" name="p" value="'.$participant['participant_id_crypt'].'">';
                }
                echo '  <input type="hidden" name="s" value="'.$s['session_id'].'">
                        <input type="hidden" name="cancel" value="true">
                        <input type="hidden" name="reallycancel" value="true">
                        '.csrf__field().'
                        <button type="submit" class="button orsee-btn orsee-btn-compact orsee-btn--delete" data-orsee-confirm-submit="1" data-confirm="'.lang('mobile_do_you_really_want_to_cancel_signup').'">'.lang('cancel_enrolment').'</button>
                      </form>
                      <div class="orsee-public-enrolment-row-deadline-label">'.lang('cancellation_possible_until').'</div>
                      <div class="orsee-public-enrolment-row-deadline">'.ortime__format($s['cancellation_deadline'],'',lang('lang')).'</div>';
            } else {
                echo '<span class="orsee-public-session-row-note">'.lang('error_enrolment_cancellation_deadline_expired').'</span>';
            }
            echo '</div>';
        }
        echo '</div>';
        $labs[$s['laboratory_id']]=$s['laboratory_id'];
    }
    echo '</div>';
    return $labs;
}

function expregister__list_registered_for_mobile($registered,$labs,$allow_subject_cancellation=false) {
    $registered_labs=array();
    if (count($registered)>0) {
        echo '<div class="orsee-public-session-group">';
        foreach ($registered as $s) {
            $exp_name=htmlspecialchars((string)$s['experiment_public_name'],ENT_QUOTES,'UTF-8');
            $sess_name=(string)$s['session_name'];
            $lab_name=htmlspecialchars((string)$labs[$s['laboratory_id']]['lab_name'],ENT_QUOTES,'UTF-8');
            $lab_address=htmlspecialchars((string)$labs[$s['laboratory_id']]['lab_address'],ENT_QUOTES,'UTF-8');
            $registered_labs[$s['laboratory_id']]=$s['laboratory_id'];
            $can_cancel=false;
            if ($allow_subject_cancellation) {
                $s['cancellation_deadline']=sessions__get_cancellation_deadline($s);
                if ($s['cancellation_deadline']>time()) {
                    $can_cancel=true;
                }
            }
            $cancel_deadline_text='';
            if ($allow_subject_cancellation && isset($s['cancellation_deadline']) && $s['cancellation_deadline']) {
                $cancel_deadline_text=ortime__format($s['cancellation_deadline'],'',lang('lang'));
            }
            echo '<button type="button" class="orsee-public-session-link" data-pane="registered"
                    data-session-id="'.$s['session_id'].'"
                    data-exp="'.$exp_name.'"
                    data-exp-note="'.htmlspecialchars((string)(isset($s['public_experiment_note']) ? trim($s['public_experiment_note']) : ''),ENT_QUOTES,'UTF-8').'"
                    data-session-note="'.htmlspecialchars((string)(isset($s['public_session_note']) ? trim($s['public_session_note']) : ''),ENT_QUOTES,'UTF-8').'"
                    data-lab="'.$lab_name.'"
                    data-labaddr="'.$lab_address.'"
                    data-cancel-deadline="'.$cancel_deadline_text.'"
                    data-can-cancel="'.($can_cancel ? '1' : '0').'"
                    data-action="cancel">
                    <span class="orsee-public-session-link-main">
                        <span class="orsee-public-session-link-title"><strong>'.$sess_name.'</strong></span>
                        <span class="orsee-public-session-link-sub">'.lang('experiment').': '.$exp_name.'</span>
                        <span class="orsee-public-session-link-sub">'.$lab_name.'</span>
                    </span>
                    <span class="orsee-public-session-link-chevron"><i class="fa fa-angle-'.(lang__is_rtl() ? 'left' : 'right').'" aria-hidden="true"></i></span>
                </button>';
        }
        echo '</div>';
        if (count($registered_labs)>1) {
            $lab_addresses_title=lang('laboratory_addresses');
        } else {
            $lab_addresses_title=lang('laboratory_address');
        }
        echo '<div class="orsee-public-detail-card mt-3">
                <div class="orsee-public-detail-row">
                    <div class="orsee-public-detail-label">'.$lab_addresses_title.'</div>
                </div>';
        foreach ($registered_labs as $lab_id) {
            if (!isset($labs[$lab_id])) {
                continue;
            }
            echo '<div class="orsee-public-detail-row">
                    <div class="orsee-public-detail-label">'.htmlspecialchars((string)$labs[$lab_id]['lab_name'],ENT_QUOTES,'UTF-8').'</div>
                    <div>'.nl2br(htmlspecialchars((string)$labs[$lab_id]['lab_address'],ENT_QUOTES,'UTF-8')).'</div>
                  </div>';
        }
        echo '</div>';
    } else {
        orsee_callout(lang('mobile_no_current_registrations'),'note','');
    }
    return $registered_labs;
}

function expregister__get_history($participant_id) {
    $pars=array(':participant_id'=>$participant_id);
    $query="SELECT * FROM ".table('experiments').",
        ".table('sessions').", ".table('participate_at')."
        WHERE ".table('experiments').".experiment_id=".table('sessions').".experiment_id
        AND ".table('experiments').".experiment_id=".table('participate_at').".experiment_id
        AND ".table('participate_at').".participant_id = :participant_id
        AND ".table('sessions').".session_id = ".table('participate_at').".session_id
        AND ".table('participate_at').".session_id!=0
        AND ".table('experiments').".experiment_type='laboratory'
        ORDER BY session_start DESC";
    $result=or_query($query,$pars);
    $history=array();
    while ($varray = pdo_fetch_assoc($result)) {
        $varray['session_unixtime']=ortime__sesstime_to_unixtime($varray['session_start']);
        $now=time();
        if ($now >= $varray['session_unixtime']) {
            $varray['session_name']=session__build_name($varray);
            $history[]=$varray;
        }
    }
    return $history;
}

function expregister__list_history($participant) {
    global $settings, $lang, $color, $preloaded_laboratories, $preloaded_payment_types;

    if (!(is_array($preloaded_laboratories) && count($preloaded_laboratories)>0)) {
        $preloaded_laboratories=laboratories__get_laboratories();
    }

    if (!(is_array($preloaded_payment_types) && count($preloaded_payment_types)>0)) {
        $preloaded_payment_types=payments__load_paytypes();
    }

    $history=expregister__get_history($participant['participant_id']);

    if (count($history)==0) {
        orsee_callout(lang('mobile_no_past_enrolments'),'note','');
        return array();
    }

    echo '<div class="orsee-table orsee-table-no-hover" style="width: 100%;">';
    echo '<div class="orsee-table-row orsee-table-head">
            <div class="orsee-table-cell">'.lang('experiment').'</div>
            <div class="orsee-table-cell">'.lang('date_and_time').'</div>
            <div class="orsee-table-cell">'.lang('location').'</div>
            <div class="orsee-table-cell">'.lang('showup?').'</div>';
    if ($settings['enable_payment_module']=='y' && $settings['payments_in_part_history']=='y') {
        echo '<div class="orsee-table-cell">'.lang('payment_type_abbr').'</div>
              <div class="orsee-table-cell">'.lang('payment_amount_abbr').'</div>';
    }
    echo '  </div>';

    $labs=array();
    $shade=true;
    $pstatuses=expregister__get_participation_statuses();
    foreach ($history as $s) {
        if ($shade) {
            $shade=false;
            $rowclass=' is-alt';
        } else {
            $shade=true;
            $rowclass='';
        }
        echo '<div class="orsee-table-row'.$rowclass.'">
                <div class="orsee-table-cell">'.htmlspecialchars((string)$s['experiment_public_name'],ENT_QUOTES,'UTF-8').'</div>
                <div class="orsee-table-cell">'.$s['session_name'].'</div>
                <div class="orsee-table-cell">';
        if (isset($preloaded_laboratories[$s['laboratory_id']])) {
            echo $preloaded_laboratories[$s['laboratory_id']]['lab_name'];
        } else {
            echo lang('unknown_laboratory');
        }
        echo '  </div>
                <div class="orsee-table-cell">';
        if ($s['session_status']=="completed" || $s['session_status']=="balanced") {
            if ($pstatuses[$s['pstatus_id']]['noshow']) {
                $tcolor='var(--color-shownup-no)';
            } else {
                $tcolor='var(--color-shownup-yes)';
            }
            $ttext=$pstatuses[$s['pstatus_id']]['display_name'];
            echo '<span style="color: '.$tcolor.'">'.$ttext.'</span>';
        } else {
            echo lang('three_questionmarks');
        }
        echo '  </div>';
        if ($settings['enable_payment_module']=='y' && $settings['payments_in_part_history']=='y') {
            echo '<div class="orsee-table-cell">';
            if ($s['session_status']=="balanced") {
                if (isset($preloaded_payment_types[$s['payment_type']])) {
                    echo $preloaded_payment_types[$s['payment_type']];
                } else {
                    echo '-';
                }
            } else {
                echo '-';
            }
            echo '</div>
                  <div class="orsee-table-cell">';
            if ($s['session_status']=="balanced" && $s['payment_amt']!='') {
                echo $s['payment_amt'];
            } else {
                echo '-';
            }
            echo '</div>';
        }
        echo '</div>';
        $labs[$s['laboratory_id']]=$s['laboratory_id'];
    }
    echo '</div>';
    return $labs;
}


function expregister__get_participate_at($participant_id,$experiment_id) {
    $pars=array(':participant_id'=>$participant_id,':experiment_id'=>$experiment_id);
    $query="SELECT *
            FROM ".table('participate_at')."
            WHERE experiment_id= :experiment_id
            AND participant_id= :participant_id";
    $result=orsee_query($query,$pars);
    return $result;
}


function expregister__register($participant,$session) {
    $pars=array(':session_id'=>$session['session_id'],
                ':experiment_id'=>$session['experiment_id'],
                ':participant_id'=>$participant['participant_id']);
    $query="UPDATE ".table('participate_at')."
            SET session_id=:session_id,
            pstatus_id=0
            WHERE experiment_id=:experiment_id
            AND participant_id=:participant_id";
    $done=or_query($query,$pars);
    $done=experimentmail__experiment_registration_mail($participant,$session);
}

function expregister__cancel($participant,$session) {
    global $settings;
    $pstatuses=expregister__get_participation_statuses();
    if (!isset($settings['subject_cancellation_participation_status'])) {
        $new_status=0;
    } else {
        $new_status=$settings['subject_cancellation_participation_status'];
    }
    if (!isset($pstatuses[$new_status])) {
        $new_status=0;
    }
    if ($new_status==0) {
        $session_id=0;
    } else {
        $session_id=$session['session_id'];
    }
    $pars=array(':session_id'=>$session_id,
                ':pstatus_id'=>$new_status,
                ':experiment_id'=>$session['experiment_id'],
                ':participant_id'=>$participant['participant_id']);
    $query="UPDATE ".table('participate_at')."
            SET session_id=:session_id,
            pstatus_id=:pstatus_id
            WHERE experiment_id=:experiment_id
            AND participant_id=:participant_id";
    $done=or_query($query,$pars);
    $done=experimentmail__experiment_cancellation_mail($participant,$session);
}




function expregister__participation_status_select_field($postvarname,$selected,$hidden=array(),$show_color=true,$select_wrapper_class='select is-primary',$compact=false) {
    $statuses=expregister__get_participation_statuses();
    if ($compact && stripos($select_wrapper_class,'select-compact')===false) {
        $select_wrapper_class=trim($select_wrapper_class.' select-compact');
    }
    if ($show_color && isset($statuses[$selected])) {
        if ($statuses[$selected]['participated']) {
            $scolor='var(--color-participation-status-participated)';
        } elseif ($statuses[$selected]['noshow']) {
            $scolor='var(--color-participation-status-noshow)';
        } else {
            $scolor='var(--color-participation-status-other)';
        }
        $out='<span class="'.$select_wrapper_class.'"><SELECT name="'.$postvarname.'" style="background: '.$scolor.';">';
    } else {
        $out='<span class="'.$select_wrapper_class.'"><SELECT name="'.$postvarname.'">';
    }
    foreach ($statuses as $status) {
        if (!in_array($status['pstatus_id'],$hidden)) {
            $out.='<OPTION value="'.$status['pstatus_id'].'"';
            if ($status['pstatus_id']==$selected) {
                $out.=" SELECTED";
            }
            $out.='>'.$status['internal_name'];
            $out.='</OPTION>
                ';
        }
    }
    $out.='</SELECT></span>';
    return $out;
}

function expregister__get_pstatus_colors() {
    $statuses=expregister__get_participation_statuses();
    $scolors=array();
    foreach ($statuses as $k=>$status) {
        if ($status['participated']) {
            $scolor='var(--color-participation-status-participated)';
        } elseif ($status['noshow']) {
            $scolor='var(--color-participation-status-noshow)';
        } else {
            $scolor='var(--color-participation-status-other)';
        }
        $scolors[$k]=$scolor;
    }
    return $scolors;
}

function expregister__get_participation_statuses() {
    global $participation_statuses, $lang;
    if (!(is_array($participation_statuses) && count($participation_statuses)>0)) {
        $participation_statuses=array();
        $query="SELECT *
                FROM ".table('participation_statuses')."
                ORDER BY pstatus_id";
        $result=or_query($query);
        while ($line = pdo_fetch_assoc($result)) {
            $participation_statuses[$line['pstatus_id']]=$line;
        }
        $query="SELECT *
                FROM ".table('lang')."
                WHERE content_type='participation_status_internal_name'
                OR content_type='participation_status_display_name'
                ORDER BY content_name";
        $result=or_query($query);
        while ($line = pdo_fetch_assoc($result)) {
            if ($line['content_type']=='participation_status_internal_name') {
                $field='internal_name';
            } else {
                $field='display_name';
            }
            $participation_statuses[$line['content_name']][$field]=$line[lang('lang')];
        }
    }
    return $participation_statuses;
}

function expregister__get_specific_pstatuses($what="participated",$reverse=false) {
    // what can be participated, noshow, participateagain
    $pstatuses=expregister__get_participation_statuses();
    $psarr=array();
    foreach ($pstatuses as $psid=>$pstatus) {
        if ($pstatus[$what]) {
            $psarr[]=$psid;
        }
    }
    return $psarr;
}

function expregister__get_pstatus_query_snippet($what="participated",$reverse=false) {
    // what can be participated, noshow, participateagain
    $psarr=expregister__get_specific_pstatuses($what,$reverse);
    if (count($psarr)==1) {
        return " pstatus_id='".$psarr[0]."' ";
    } else {
        return " pstatus_id IN (".implode(", ",$psarr).") ";
        //$check_statuses_query=array();
        //foreach ($psarr as $cs) $check_statuses_query[]=" pstatus_id='".$cs."' ";
        //$snippet=" (".implode(" OR ",$check_statuses_query).") ";
        //return $snippet;
    }
}

?>
