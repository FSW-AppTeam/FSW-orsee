<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="my_registrations";
$title="experiments";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['s']) && $_REQUEST['s']) {
        $session_id=trim($_REQUEST['s']);
    } else {
        $session_id="";
    }

    if (isset($_REQUEST['register']) && $_REQUEST['register']) {
        $continue=true;

        if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) {
            redirect("public/participant_show.php".$token_string);
        }
        if (isset($_REQUEST['reallyregister']) && $_REQUEST['reallyregister']) {
            if (!csrf__validate_request_message()) {
                redirect("public/participant_show.php".$token_string);
            }
        }

        if ($proceed) {
            if (!$session_id) {
                $continue=false;
                log__participant("interfere enrolment - no session_id",$participant_id);
                message(lang('error_session_id_register'),'error');
                redirect("public/participant_show.php".$token_string);
            }
        }
        if ($proceed) {
            $session=orsee_db_load_array("sessions",$session_id,"session_id");
            if (!isset($session['session_id'])) {
                log__participant("interfere enrolment - invalid session_id",$participant_id);
                message(lang('error_session_id_register'),'error');
                redirect("public/participant_show.php".$token_string);
            }
        }
        if ($proceed) {
            $participate_at=expregister__get_participate_at($participant_id,$session['experiment_id']);
            if (!isset($participate_at['session_id'])) {
                $continue=false;
                redirect("public/participant_show.php".$token_string);
            }
        }
        if ($proceed) {
            if ($settings['enable_enrolment_only_on_invite']=='y') {
                if (!$participate_at['invited']) {
                    $continue=false;
                    redirect("public/participant_show.php".$token_string);
                }
            }
        }

        if ($proceed) {
            if (isset($participate_at['session_id']) && $participate_at['session_id']>0) {
                $continue=false;
                message(lang('error_already_registered'),'warning');
                redirect("public/participant_show.php".$token_string);
            }
        }

        if ($proceed) {
            $registration_end=sessions__get_registration_end($session);
            $full=sessions__session_full($session_id,$session);
            $now=time();
            if ($registration_end < $now) {
                $continue=false;
                message(lang('error_registration_expired'),'warning');
                redirect("public/participant_show.php".$token_string);
            }
        }

        if ($proceed) {
            if ($full) {
                $continue=false;
                message(lang('error_session_complete'),'warning');
                redirect("public/participant_show.php".$token_string);
            }
        }

        if ($proceed) {
            if (isset($_REQUEST['reallyregister']) && $_REQUEST['reallyregister']) {
                // if all checks are done, register ...
                if ($continue) {
                    $done=expregister__register($participant,$session);
                    $done=participant__update_last_enrolment_time($participant_id);
                    $done=log__participant("register",$participant['participant_id'],
                        "experiment_id:".$session['experiment_id']."\nsession_id:".$session_id);
                    message(lang('successfully_registered_to_experiment_xxx')." ".
                        experiment__get_public_name($session['experiment_id']).", ".
                        session__build_name($session).". ".
                        lang('this_will_be_confirmed_by_an_email'),'note',null,'toast');
                    $redir="public/participant_show.php".$token_string;
                    if ($token_string) {
                        $redir.="&";
                    } else {
                        $redir.="?";
                    }
                    $redir.="s=".$session_id;
                    redirect($redir);
                }
            } else {
                redirect("public/participant_show.php".$token_string);
            }
        }
    } elseif (isset($_REQUEST['cancel']) && $_REQUEST['cancel'] &&
            isset($settings['allow_subject_cancellation']) && $settings['allow_subject_cancellation']=='y') {
        $continue=true;

        if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) {
            redirect("public/participant_show.php".$token_string);
        }
        if (isset($_REQUEST['reallycancel']) && $_REQUEST['reallycancel']) {
            if (!csrf__validate_request_message()) {
                redirect("public/participant_show.php".$token_string);
            }
        }

        if ($proceed) {
            if (!$session_id) {
                $continue=false;
                log__participant("interfere enrolment cancellation- no session_id",$participant_id);
                message(lang('error_session_id_register'),'error');
                redirect("public/participant_show.php".$token_string);
            }
        }
        if ($proceed) {
            $session=orsee_db_load_array("sessions",$session_id,"session_id");
            if (!isset($session['session_id'])) {
                log__participant("interfere enrolment cancellation - invalid session_id",$participant_id);
                message(lang('error_session_id_register'),'error');
                redirect("public/participant_show.php".$token_string);
            }
        }
        if ($proceed) {
            $participate_at=expregister__get_participate_at($participant_id,$session['experiment_id']);
            if (!isset($participate_at['session_id']) || $participate_at['session_id']!=$session_id) {
                $continue=false;
                redirect("public/participant_show.php".$token_string);
            }
        }

        if ($proceed) {
            $cancellation_deadline=sessions__get_cancellation_deadline($session);
            $now=time();
            if ($cancellation_deadline < $now) {
                $continue=false;
                message(lang('error_enrolment_cancellation_deadline_expired'),'warning');
                redirect("public/participant_show.php".$token_string);
            }
        }

        if ($proceed) {
            if (isset($_REQUEST['reallycancel']) && $_REQUEST['reallycancel']) {
                // if all checks are done, register ...
                if ($continue) {
                    $done=expregister__cancel($participant,$session);
                    $done=participant__update_last_enrolment_time($participant_id);
                    $done=log__participant("cancel_session_enrolment",$participant['participant_id'],
                        "experiment_id:".$session['experiment_id']."\nsession_id:".$session_id);
                    message(lang('successfully_canceled_enrolment_xxx')." ".
                        experiment__get_public_name($session['experiment_id']).", ".
                        session__build_name($session).". "
                        .lang('this_will_be_confirmed_by_an_email'),
                        'note',null,'toast'
                    );
                    redirect("public/participant_show.php".$token_string);
                }
            } else {
                redirect("public/participant_show.php".$token_string);
            }
        }
    } else {
        $labs=laboratories__get_laboratories();

        $invdata=expregister__get_invitations($participant_id);
        $invited=$invdata['invited'];
        $inv_experiments = isset($invdata['inv_experiments']) && is_array($invdata['inv_experiments']) ? $invdata['inv_experiments'] : array();
        $registered=expregister__get_registrations($participant_id);
        $history=expregister__get_history($participant_id);
        $pstatuses=expregister__get_participation_statuses();
        $payment_types=payments__load_paytypes();

        echo '<div id="orsee-public-mobile-screen" class="orsee-public-screen has-rail">
                <div id="orsee-public-tabpages" class="orsee-public-tabpages">
                <div id="orsee-public-tabtrack" class="orsee-public-tabtrack">
                <div id="orsee-public-tab-invitations" class="orsee-public-tabpage">
                    <div class="orsee-public-tablet-mobile-only">
                    <div id="orsee-public-invitations-pane" class="orsee-public-subpane">
                        <div class="orsee-public-subpane-list">
                            <p class="orsee-public-intro">'.lang('please_check_availability_before_register').'</p>';

        expregister__list_invited_for_mobile($invited,$labs);
        echo '          </div>
                        <div class="orsee-public-subpane-detail">
                            <div class="orsee-public-detail-top">
                                <button type="button" class="orsee-public-detail-back" data-pane="invitations"><i class="fa fa-angle-'.(lang__is_rtl() ? 'right' : 'left').'" aria-hidden="true"></i> '.lang('back').'</button>
                                <div class="orsee-public-detail-title">'.lang('mobile_session_details').'</div>
                            </div>
                            <div class="orsee-public-detail-card">
                                <div class="orsee-public-detail-card-head">'.lang('mobile_you_can_enroll_for').'</div>
                                <div class="orsee-public-detail-row">
                                    <div class="orsee-public-detail-label">'.lang('experiment').':</div>
                                    <div id="orsee-public-inv-detail-exp"></div>
                                </div>
                                <div id="orsee-public-inv-detail-exp-note-row" class="orsee-public-detail-row" style="display:none;">
                                    <div class="orsee-public-detail-label">'.lang('note').':</div>
                                    <div id="orsee-public-inv-detail-exp-note" class="orsee-note-preline"></div>
                                </div>
                                <div class="orsee-public-detail-row">
                                    <div class="orsee-public-detail-label">'.lang('date_and_time').':</div>
                                    <div id="orsee-public-inv-detail-datetime"></div>
                                </div>
                                <div id="orsee-public-inv-detail-session-note-row" class="orsee-public-detail-row" style="display:none;">
                                    <div class="orsee-public-detail-label">'.lang('note').':</div>
                                    <div id="orsee-public-inv-detail-session-note" class="orsee-note-preline"></div>
                                </div>
                                <div class="orsee-public-detail-row">
                                    <div class="orsee-public-detail-label">'.lang('laboratory').':</div>
                                    <div id="orsee-public-inv-detail-lab"></div>
                                    <div id="orsee-public-inv-detail-labaddr"></div>
                                </div>
                                <div class="orsee-public-detail-row orsee-public-detail-row-actions">
                                    <form id="orsee-public-register-form" action="participant_show.php" method="POST">
                                        <input type="hidden" id="orsee-public-register-session" name="s" value="">';
        if ($token_string) {
            echo '<input type="hidden" name="p" value="'.$participant['participant_id_crypt'].'">';
        }
        echo '                          <input type="hidden" name="register" value="true">
                                        <input type="hidden" name="reallyregister" value="true">
                                        '.csrf__field().'
                                        <button type="submit" id="orsee-public-register-btn" class="button orsee-public-btn" data-orsee-confirm-submit="1" data-orsee-confirm-form="orsee-public-register-form" data-confirm="'.lang('mobile_do_you_really_want_to_signup').'">'.lang('mobile_sign_up').'</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                    <div class="orsee-public-desktop-only orsee-public-profile-formwrap">
                        <p class="orsee-public-intro">'.lang('please_check_availability_before_register').'</p>';
        $invited_labs=expregister__list_invited_for($participant,$invited,$labs);
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
        echo '      </div>
                </div>';

        echo '<div id="orsee-public-tab-registered" class="orsee-public-tabpage">
                    <div class="orsee-public-tablet-mobile-only">
                    <div id="orsee-public-registered-pane" class="orsee-public-subpane">
                        <div class="orsee-public-subpane-list">';
        $allow_subject_cancellation=(isset($settings['allow_subject_cancellation']) && $settings['allow_subject_cancellation']=='y');
        expregister__list_registered_for_mobile($registered,$labs,$allow_subject_cancellation);
        echo '          </div>
                        <div class="orsee-public-subpane-detail">
                            <div class="orsee-public-detail-top">
                                <button type="button" class="orsee-public-detail-back" data-pane="registered"><i class="fa fa-angle-'.(lang__is_rtl() ? 'right' : 'left').'" aria-hidden="true"></i> '.lang('back').'</button>
                                <div class="orsee-public-detail-title">'.lang('mobile_session_details').'</div>
                            </div>
                            <div class="orsee-public-detail-card">
                                <div class="orsee-public-detail-card-head">'.lang('mobile_you_are_enrolled_for').'</div>
                                <div class="orsee-public-detail-row">
                                    <div class="orsee-public-detail-label">'.lang('experiment').':</div>
                                    <div id="orsee-public-reg-detail-exp"></div>
                                </div>
                                <div id="orsee-public-reg-detail-exp-note-row" class="orsee-public-detail-row" style="display:none;">
                                    <div class="orsee-public-detail-label">'.lang('note').':</div>
                                    <div id="orsee-public-reg-detail-exp-note" class="orsee-note-preline"></div>
                                </div>
                                <div class="orsee-public-detail-row">
                                    <div class="orsee-public-detail-label">'.lang('date_and_time').':</div>
                                    <div id="orsee-public-reg-detail-datetime"></div>
                                </div>
                                <div id="orsee-public-reg-detail-session-note-row" class="orsee-public-detail-row" style="display:none;">
                                    <div class="orsee-public-detail-label">'.lang('note').':</div>
                                    <div id="orsee-public-reg-detail-session-note" class="orsee-note-preline"></div>
                                </div>
                                <div class="orsee-public-detail-row">
                                    <div class="orsee-public-detail-label">'.lang('laboratory').':</div>
                                    <div id="orsee-public-reg-detail-lab"></div>
                                    <div id="orsee-public-reg-detail-labaddr"></div>
                                </div>
                                <div id="orsee-public-reg-detail-cancel-deadline-row" class="orsee-public-detail-row" style="display:none;">
                                    <div class="orsee-public-detail-label">'.lang('cancellation_possible_until').':</div>
                                    <div id="orsee-public-reg-detail-cancel-deadline"></div>
                                </div>
                                <div id="orsee-public-reg-detail-action-row" class="orsee-public-detail-row orsee-public-detail-row-actions">
                                    <form id="orsee-public-cancel-form" action="participant_show.php" method="POST">
                                        <input type="hidden" id="orsee-public-cancel-session" name="s" value="">';
        if ($token_string) {
            echo '<input type="hidden" name="p" value="'.$participant['participant_id_crypt'].'">';
        }
        echo '                          <input type="hidden" name="cancel" value="true">
                                        <input type="hidden" name="reallycancel" value="true">
                                        '.csrf__field().'
                                        <button type="submit" id="orsee-public-cancel-btn" class="button orsee-public-btn orsee-public-btn--delete" data-orsee-confirm-submit="1" data-orsee-confirm-form="orsee-public-cancel-form" data-confirm="'.lang('mobile_do_you_really_want_to_cancel_signup').'">'.lang('mobile_cancel_signup').'</button>
                                    </form>
                                    <div id="orsee-public-cancel-disabled" class="orsee-public-detail-note" style="display:none;">'.lang('error_enrolment_cancellation_deadline_expired').'</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                    <div class="orsee-public-desktop-only orsee-public-profile-formwrap">';
        if (count($registered)>0) {
            echo '<div class="orsee-public-detail-card">';
        }
        $registered_labs=expregister__list_registered_for($participant,"",$registered,$labs);
        if (count($registered)>0) {
            echo '</div>';
        }
        echo '';
        if (count($registered_labs)>0) {
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
        }
        echo '      </div>
                </div>';

        echo '<div id="orsee-public-tab-history" class="orsee-public-tabpage">
                <div class="orsee-public-profile-formwrap" data-orsee-mobile="show">
                <div class="orsee-surface-card" style="margin-bottom: 0.7rem; padding: 0.55rem 0.7rem;">
                    <div>'.lang('registered_for').' '.$participant['number_reg'].'</div>
                    <div>'.lang('not_shown_up').' '.$participant['number_noshowup'].'</div>
                </div>';
        if (count($history)>0) {
            foreach ($history as $s) {
                echo '<div class="orsee-surface-card" style="margin-bottom: 0.7rem; padding: 0.55rem 0.7rem;">
                        <div>'.$s['session_name'].'</div>
                        <div class="orsee-font-compact">'.lang('experiment').': '.htmlspecialchars((string)$s['experiment_public_name'],ENT_QUOTES,'UTF-8').'</div>
                        <div class="orsee-font-compact">'.$labs[$s['laboratory_id']]['lab_name'].'</div>
                        <div class="orsee-font-compact">'.lang('showup?').' ';
                if ($s['session_status']=="completed" || $s['session_status']=="balanced") {
                    if ($pstatuses[$s['pstatus_id']]['noshow']) {
                        $tcolor='var(--color-shownup-no)';
                    } else {
                        $tcolor='var(--color-shownup-yes)';
                    }
                    $ttext=$pstatuses[$s['pstatus_id']]['display_name'];
                    echo '<strong style="color: '.$tcolor.';">'.$ttext.'</strong>';
                } else {
                    echo '<strong style="color: gray;">'.lang('three_questionmarks').'</strong>';
                }
                echo '  </div>';
                if ($settings['enable_payment_module']=='y' && $settings['payments_in_part_history']=='y' && $s['session_status']=="balanced") {
                    echo '<div class="orsee-font-compact">'.lang('payment_type_abbr').': ';
                    if (isset($payment_types[$s['payment_type']])) {
                        echo $payment_types[$s['payment_type']];
                    } else {
                        echo '-';
                    }
                    echo ', '.lang('payment_amount_abbr').': ';
                    if ($s['payment_amt']!='') {
                        echo $s['payment_amt'];
                    } else {
                        echo '-';
                    }
                    echo '</div>';
                }
                echo '</div>';
            }
        } else {
            orsee_callout(lang('mobile_no_past_enrolments'),'note','');
        }
        echo '      </div>
                    <div class="orsee-public-profile-formwrap" data-orsee-mobile="hide">
                    <div class="orsee-surface-card" style="margin-bottom: 0.7rem; padding: 0.55rem 0.7rem;">
                        <div>'.lang('registered_for').' '.$participant['number_reg'].'</div>
                        <div>'.lang('not_shown_up').' '.$participant['number_noshowup'].'</div>
                    </div>';
        expregister__list_history($participant);
        echo '      </div>
                </div>
            </div>
            </div>';

        $invitation_count = count($inv_experiments);
        $registered_count = count($registered);
        $history_count = count($history);
        $invitation_badge = ($invitation_count > 0) ? '<span class="orsee-public-tab-badge orsee-public-tab-badge--hot">'.$invitation_count.'</span>' : '';
        $registered_badge = ($registered_count > 0) ? '<span class="orsee-public-tab-badge">'.$registered_count.'</span>' : '';
        $history_badge = ($history_count > 0) ? '<span class="orsee-public-tab-badge">'.$history_count.'</span>' : '';

        echo '<nav id="orsee-public-mobile-tabbar">
                <div class="orsee-public-tabbar-inner">
                    <a href="#" id="orsee-public-tabbtn-invitations" class="orsee-public-tabitem is-active">
                        <span class="orsee-public-tab-icon-wrap"><i class="fa fa-inbox" aria-hidden="true"></i>'.$invitation_badge.'</span>
                        <span class="orsee-public-tab-label">'.lang('mobile_invitations').'</span>
                    </a>
                    <a href="#" id="orsee-public-tabbtn-registered" class="orsee-public-tabitem">
                        <span class="orsee-public-tab-icon-wrap"><i class="fa fa-calendar" aria-hidden="true"></i>'.$registered_badge.'</span>
                        <span class="orsee-public-tab-label">'.lang('mobile_enrolments').'</span>
                    </a>
                    <a href="#" id="orsee-public-tabbtn-history" class="orsee-public-tabitem">
                        <span class="orsee-public-tab-icon-wrap"><i class="fa fa-list-alt" aria-hidden="true"></i>'.$history_badge.'</span>
                        <span class="orsee-public-tab-label">'.lang('mobile_history').'</span>
                    </a>
                    <a href="participant_edit.php'.$token_string.'" class="orsee-public-tabitem orsee-public-tabitem--profile">
                        <span class="orsee-public-tab-icon-wrap"><i class="fa fa-user" aria-hidden="true"></i></span>
                        <span class="orsee-public-tab-label">'.lang('my_data').'</span>
                    </a>
                </div>
            </nav>
        </div>';

        echo participant__public_confirm_modal();

        echo '<script type="text/javascript">
                document.addEventListener("DOMContentLoaded", function () {
                    var tabs = ["invitations", "registered", "history"];
                    var track = document.getElementById("orsee-public-tabtrack");
                    var activeTab = "invitations";
                    var currentIndex = 0;
                    var slideSign = (document.documentElement && document.documentElement.dir === "rtl") ? 1 : -1;
                    var registerBtn = document.getElementById("orsee-public-register-btn");
                    var cancelBtn = document.getElementById("orsee-public-cancel-btn");
                    var cancelNote = document.getElementById("orsee-public-cancel-disabled");
                    var cancelDeadlineRow = document.getElementById("orsee-public-reg-detail-cancel-deadline-row");
                    var cancelActionRow = document.getElementById("orsee-public-reg-detail-action-row");
                    var allowSubjectCancellation = '.((isset($settings['allow_subject_cancellation']) && $settings['allow_subject_cancellation']=='y') ? 'true' : 'false').';

                    function isDesktopSplit() {
                        return window.matchMedia && window.matchMedia("(min-width: 1100px)").matches;
                    }

                    function syncTrackHeight() {
                        if (!track) return;
                        var pages = track.querySelectorAll(".orsee-public-tabpage");
                        if (!pages[currentIndex]) return;
                        track.style.height = pages[currentIndex].offsetHeight + "px";
                    }

                    function scrollToTop() {
                        window.scrollTo(0, 0);
                        if (document.documentElement) document.documentElement.scrollTop = 0;
                        if (document.body) document.body.scrollTop = 0;
                    }

                    function setActiveButton(name) {
                        tabs.forEach(function (t) {
                            var btn = document.getElementById("orsee-public-tabbtn-" + t);
                            if (!btn) return;
                            if (t === name) btn.classList.add("is-active");
                            else btn.classList.remove("is-active");
                        });
                    }

                    function setHashState(tabName, detail, replaceMode, sessionId) {
                        var detailSuffix = "";
                        if (detail) detailSuffix = ":detail" + (sessionId ? (":" + sessionId) : "");
                        var hashValue = "#" + tabName + detailSuffix;
                        if (replaceMode && window.history && window.history.replaceState) {
                            var urlNoHash = window.location.href.split("#")[0];
                            window.history.replaceState(null, "", urlNoHash + hashValue);
                        } else {
                            window.location.hash = hashValue;
                        }
                        try {
                            window.sessionStorage.setItem("orsee_public_mobile_show_state", JSON.stringify({
                                tab: tabName,
                                detail: !!detail,
                                sessionId: sessionId ? String(sessionId) : ""
                            }));
                        } catch (e) {}
                    }

                    function parseHashState() {
                        var state = { tab: "invitations", detail: false, sessionId: "" };
                        if (window.location.hash) {
                            var raw = window.location.hash.replace(/^#/, "");
                            if (raw) {
                                var parts = raw.split(":");
                                if (tabs.indexOf(parts[0]) >= 0) state.tab = parts[0];
                                if (parts.length > 1 && parts[1] === "detail") {
                                    state.detail = true;
                                    if (parts.length > 2) state.sessionId = parts[2];
                                }
                                return state;
                            }
                        }
                        try {
                            var saved = window.sessionStorage.getItem("orsee_public_mobile_show_state");
                            if (!saved) return state;
                            var parsed = JSON.parse(saved);
                            if (parsed && tabs.indexOf(parsed.tab) >= 0) {
                                state.tab = parsed.tab;
                                state.detail = !!parsed.detail;
                                state.sessionId = parsed.sessionId ? String(parsed.sessionId) : "";
                            }
                        } catch (e) {}
                        return state;
                    }

                    function openTab(name, syncHash, replaceMode) {
                        var idx = tabs.indexOf(name);
                        if (idx < 0 || !track) return;
                        currentIndex = idx;
                        document.querySelectorAll(".orsee-public-subpane").forEach(function (pane) {
                            pane.classList.remove("is-detail");
                        });
                        track.style.transform = "translateX(" + (slideSign * idx * 100) + "%)";
                        syncTrackHeight();
                        window.setTimeout(syncTrackHeight, 60);
                        scrollToTop();
                        activeTab = name;
                        setActiveButton(name);
                        if (syncHash) setHashState(name,false,!!replaceMode);
                    }

                    function showDetailPane(paneName, syncHash, sessionId) {
                        if (isDesktopSplit()) {
                            syncTrackHeight();
                            window.setTimeout(syncTrackHeight, 60);
                            return;
                        }
                        var pane = document.getElementById("orsee-public-" + paneName + "-pane");
                        if (pane) pane.classList.add("is-detail");
                        syncTrackHeight();
                        window.setTimeout(syncTrackHeight, 60);
                        scrollToTop();
                        if (syncHash) setHashState(activeTab,true,false,sessionId || "");
                    }

                    function showListPane(paneName, syncHash) {
                        if (isDesktopSplit()) {
                            syncTrackHeight();
                            window.setTimeout(syncTrackHeight, 60);
                            return;
                        }
                        var pane = document.getElementById("orsee-public-" + paneName + "-pane");
                        if (pane) pane.classList.remove("is-detail");
                        syncTrackHeight();
                        window.setTimeout(syncTrackHeight, 60);
                        scrollToTop();
                        if (syncHash) setHashState(activeTab,false,false);
                    }


                    tabs.forEach(function (t) {
                        var btn = document.getElementById("orsee-public-tabbtn-" + t);
                        if (!btn) return;
                        btn.addEventListener("click", function (ev) {
                            ev.preventDefault();
                            if (t === activeTab) return;
                            openTab(t,true,false);
                        });
                    });

                    document.querySelectorAll(".orsee-public-session-link").forEach(function (row) {
                        row.addEventListener("click", function () {
                            if (this.classList.contains("is-static")) return;
                            var pane = this.dataset.pane || "";
                            var exp = this.dataset.exp || "";
                            var expNote = this.dataset.expNote || "";
                            var sessionNote = this.dataset.sessionNote || "";
                            var cancelDeadline = this.dataset.cancelDeadline || "";
                            var titleEl = this.querySelector(".orsee-public-session-link-title");
                            var datetime = titleEl ? titleEl.textContent : "";
                            var lab = this.dataset.lab || "";
                            var labaddr = this.dataset.labaddr || "";
                            var sessionId = this.dataset.sessionId || "";
                            if (!pane || !sessionId) return;

                            if (pane === "invitations") {
                                document.getElementById("orsee-public-inv-detail-exp").textContent = exp;
                                document.getElementById("orsee-public-inv-detail-datetime").textContent = datetime;
                                document.getElementById("orsee-public-inv-detail-lab").textContent = lab;
                                document.getElementById("orsee-public-inv-detail-labaddr").textContent = labaddr;
                                document.getElementById("orsee-public-inv-detail-exp-note").textContent = expNote;
                                document.getElementById("orsee-public-inv-detail-session-note").textContent = sessionNote;
                                document.getElementById("orsee-public-inv-detail-exp-note-row").style.display = expNote ? "" : "none";
                                document.getElementById("orsee-public-inv-detail-session-note-row").style.display = sessionNote ? "" : "none";
                                document.getElementById("orsee-public-register-session").value = sessionId;
                                if (registerBtn) registerBtn.disabled = (sessionId === "");
                            } else if (pane === "registered") {
                                document.getElementById("orsee-public-reg-detail-exp").textContent = exp;
                                document.getElementById("orsee-public-reg-detail-datetime").textContent = datetime;
                                document.getElementById("orsee-public-reg-detail-lab").textContent = lab;
                                document.getElementById("orsee-public-reg-detail-labaddr").textContent = labaddr;
                                document.getElementById("orsee-public-reg-detail-exp-note").textContent = expNote;
                                document.getElementById("orsee-public-reg-detail-session-note").textContent = sessionNote;
                                document.getElementById("orsee-public-reg-detail-exp-note-row").style.display = expNote ? "" : "none";
                                document.getElementById("orsee-public-reg-detail-session-note-row").style.display = sessionNote ? "" : "none";
                                document.getElementById("orsee-public-reg-detail-cancel-deadline").textContent = cancelDeadline;
                                document.getElementById("orsee-public-cancel-session").value = sessionId;
                                var canCancel = (this.dataset.canCancel === "1");
                                if (!allowSubjectCancellation) {
                                    if (cancelActionRow) cancelActionRow.style.display = "none";
                                    if (cancelDeadlineRow) cancelDeadlineRow.style.display = "none";
                                    if (cancelBtn) {
                                        cancelBtn.style.display = "none";
                                        cancelBtn.disabled = true;
                                    }
                                    if (cancelNote) cancelNote.style.display = "none";
                                } else {
                                    if (cancelActionRow) cancelActionRow.style.display = "";
                                    if (canCancel) {
                                        if (cancelDeadlineRow) cancelDeadlineRow.style.display = cancelDeadline ? "" : "none";
                                        if (cancelBtn) {
                                            cancelBtn.style.display = "";
                                            cancelBtn.disabled = false;
                                        }
                                        if (cancelNote) cancelNote.style.display = "none";
                                    } else {
                                        if (cancelDeadlineRow) cancelDeadlineRow.style.display = "none";
                                        if (cancelBtn) {
                                            cancelBtn.style.display = "none";
                                            cancelBtn.disabled = true;
                                        }
                                        if (cancelNote) cancelNote.style.display = "";
                                    }
                                }
                            }
                            showDetailPane(pane,true,sessionId);
                        });
                    });

                    document.querySelectorAll(".orsee-public-detail-back").forEach(function (backBtn) {
                        backBtn.addEventListener("click", function () {
                            showListPane(this.dataset.pane || "",true);
                        });
                    });

                    if (window.ResizeObserver && track) {
                        var ro = new ResizeObserver(function () {
                            syncTrackHeight();
                        });
                        track.querySelectorAll(".orsee-public-tabpage").forEach(function (p) { ro.observe(p); });
                    }
                    window.addEventListener("resize", syncTrackHeight);
                    window.addEventListener("load", syncTrackHeight);

                    if (registerBtn) registerBtn.disabled = true;
                    if (cancelBtn) cancelBtn.disabled = true;
                    if (cancelNote) cancelNote.style.display = "none";
                    if (cancelDeadlineRow) cancelDeadlineRow.style.display = "none";
                    if (!allowSubjectCancellation && cancelActionRow) cancelActionRow.style.display = "none";

                    function applyHashState(replaceMode) {
                        var state = parseHashState();
                        openTab(state.tab,false,replaceMode);
                        if (state.detail) {
                            var matchedRow = null;
                            document.querySelectorAll(".orsee-public-session-link").forEach(function (row) {
                                if ((row.dataset.pane || "") !== state.tab) return;
                                if ((row.dataset.sessionId || "") === state.sessionId) matchedRow = row;
                            });
                            if (state.sessionId !== "" && matchedRow) {
                                matchedRow.click();
                                return;
                            }
                            if (state.sessionId !== "") {
                                showListPane(state.tab,false);
                                return;
                            }
                            showDetailPane(state.tab,false);
                        } else {
                            showListPane(state.tab,false);
                        }
                    }

                    window.addEventListener("hashchange", function () {
                        applyHashState(false);
                    });

                    applyHashState(true);
                });
            </script>';
        echo javascript__confirm_modal_script();
    }
}
include("footer.php");

?>
