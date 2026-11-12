<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="public_register";
$title="registration_form";
$js_modules=array('flatpickr','switchy','intltelinput');
include("header.php");

if ($proceed) {
    $subpools=subpools__get_subpools();
    $all_pool_ids=array();
    foreach ($subpools as $pool) {
        if ($pool['subpool_id']>1 && $pool['show_at_registration_page']=='y') {
            $all_pool_ids[]=(int)$pool['subpool_id'];
        }
    }

    $self_descriptions=array();
    $query="SELECT * from ".table('lang')."
            WHERE content_type='subjectpool'";
    $result=or_query($query);
    while ($line=pdo_fetch_assoc($result)) {
        $self_descriptions[(int)$line['content_name']]=$line[lang('lang')];
    }

    $default_subpool=(int)$settings['subpool_default_registration_id'];
    $selected_subpool=0;

    if (isset($_REQUEST['subpool_id']) && $_REQUEST['subpool_id']!=='' && in_array((int)$_REQUEST['subpool_id'],$all_pool_ids,true)) {
        $selected_subpool=(int)$_REQUEST['subpool_id'];
    } elseif (isset($_REQUEST['s']) && $_REQUEST['s']!=='' && in_array((int)$_REQUEST['s'],$all_pool_ids,true)) {
        $selected_subpool=(int)$_REQUEST['s'];
    } elseif (isset($_SESSION['subpool_id']) && in_array((int)$_SESSION['subpool_id'],$all_pool_ids,true)) {
        $selected_subpool=(int)$_SESSION['subpool_id'];
    } elseif (count($all_pool_ids)<=1 && $default_subpool) {
        $selected_subpool=$default_subpool;
    } elseif (count($all_pool_ids)==1 && !$default_subpool) {
        $selected_subpool=(int)$all_pool_ids[0];
    } elseif (count($all_pool_ids)==0 && !$default_subpool) {
        $selected_subpool=1;
    }

    $need_subpool_step=(count($all_pool_ids)>1);
    $need_rules_step=(
        $settings['registration__require_rules_acceptance']=='y' ||
        $settings['registration__require_privacy_policy_acceptance']=='y'
    );
    $force_subpool_stage=(
        $need_subpool_step &&
        !isset($_REQUEST['add']) &&
        !isset($_REQUEST['subpool_id']) &&
        !isset($_REQUEST['s'])
    );

    $rules_accepted='';
    if (!$need_rules_step) {
        $rules_accepted='1';
    } elseif (isset($_REQUEST['accept_rules']) && $_REQUEST['accept_rules']) {
        $rules_accepted='1';
    }

    if (isset($_REQUEST['notaccept_rules']) && $_REQUEST['notaccept_rules']) {
        if (!csrf__validate_request_message()) {
            redirect("public/".thisdoc());
        }
        redirect("public/");
    }

    $form=true;
    $errors__dataform=array();

    if (isset($_REQUEST['add'])) {
        if (!csrf__validate_request_message()) {
            redirect("public/participant_create.php");
        }

        $continue=true;

        if (!$selected_subpool) {
            message(lang('please_choose_subgroup'),'warning');
            $continue=false;
        }

        if ($continue && $need_rules_step && $rules_accepted!='1') {
            message(lang('do_you_agree_rules_privacy'),'warning');
            $continue=false;
        }

        if ($continue) {
            if (!isset($_REQUEST['captcha']) || !isset($_SESSION['captcha_string']) || $_REQUEST['captcha']!=$_SESSION['captcha_string']) {
                if (!isset($_REQUEST['subscriptions']) || !is_array($_REQUEST['subscriptions'])) {
                    $_REQUEST['subscriptions']=array();
                }
                $_REQUEST['subscriptions']=id_array_to_db_string($_REQUEST['subscriptions']);
                $formfields=participantform__load();
                foreach ($formfields as $f) {
                    if (!isset($f['type']) || $f['type']!=='date') {
                        continue;
                    }
                    $field_name=$f['mysql_column_name'];
                    $date_mode=(isset($f['date_mode']) ? $f['date_mode'] : 'ymd');
                    if (!in_array($date_mode,array('ymd','ym','y'))) {
                        $date_mode='ymd';
                    }
                    $date_ymd=ortime__date_parts_to_ymd(
                        (isset($_REQUEST[$field_name.'_y']) ? $_REQUEST[$field_name.'_y'] : ''),
                        (isset($_REQUEST[$field_name.'_m']) ? $_REQUEST[$field_name.'_m'] : ''),
                        (isset($_REQUEST[$field_name.'_d']) ? $_REQUEST[$field_name.'_d'] : ''),
                        $date_mode
                    );
                    if ($date_ymd) {
                        $_REQUEST[$field_name]=$date_ymd;
                    } else {
                        $_REQUEST[$field_name]='';
                    }
                }
                $continue=false;
                message(lang('error_wrong_captcha'),'error');
            }
        }

        if ($continue) {
            foreach ($_REQUEST as $k=>$v) {
                if (!is_array($v)) {
                    $_REQUEST[$k]=trim($v);
                }
            }
            $_REQUEST['subpool_id']=$selected_subpool;
            $check_result=participantform__check_fields($_REQUEST,'profile_form_public_create');
            $errors__dataform=$check_result['errors'];
            $form_input=$check_result['sanitized'];
            $allowed_fields=$check_result['allowed_fields'];
            $error_count=count($errors__dataform);
            if ($error_count>0) {
                $continue=false;
            }

            $response=participantform__check_unique($form_input,"create");
            if (isset($response['disable_form']) && $response['disable_form']) {
                $continue=false;
                $proceed=false;
                unset($_SESSION['pauthdata']['pw_provided']);
                unset($_SESSION['pauthdata']['submitted_checked_pw']);
                if ($settings['subject_authentication']=='token') {
                    redirect("public/");
                } else {
                    redirect("public/participant_login.php");
                }
            } elseif ($response['problem']) {
                $continue=false;
            }

            if ($settings['subject_authentication']!='token') {
                if (isset($_SESSION['pauthdata']['pw_provided']) && $_SESSION['pauthdata']['pw_provided'] &&
                    isset($_SESSION['pauthdata']['submitted_checked_pw']) && $_SESSION['pauthdata']['submitted_checked_pw']) {
                    $form_input['password']=$_SESSION['pauthdata']['submitted_checked_pw'];
                } else {
                    $pw_ok=participant__check_password($form_input['password'],$form_input['password2']);
                    if ($pw_ok) {
                        $_SESSION['pauthdata']['pw_provided']=true;
                        $_SESSION['pauthdata']['submitted_checked_pw']=$form_input['password'];
                    } else {
                        $continue=false;
                    }
                }
            }
        }

        if ($continue) {
            $participant=$form_input;
            unset($_SESSION['pauthdata']['pw_provided']);
            unset($_SESSION['pauthdata']['submitted_checked_pw']);
            unset($_SESSION['captcha_string']);
            $new_id=participant__create_participant_id($participant);
            $participant['participant_id']=$new_id['participant_id'];
            $participant['participant_id_crypt']=$new_id['participant_id_crypt'];
            if ($settings['subject_authentication']!='token') {
                $participant['password_crypted']=unix_crypt($participant['password']);
            }
            $participant['confirmation_token']=create_random_token(get_entropy($participant));
            $participant['creation_time']=time();
            $participant['last_profile_update']=$participant['creation_time'];
            $participant['status_id']=0;
            $participant['subpool_id']=$selected_subpool;
            if (!isset($participant['language']) || !$participant['language']) {
                $participant['language']=$settings['public_standard_language'];
            }
            $save_allowed_fields=array_values(array_unique(array_merge(
                $allowed_fields,
                array('language','participant_id','participant_id_crypt','password_crypted','confirmation_token','creation_time','last_profile_update','status_id','subpool_id')
            )));
            $participant=array_filter_allowed($participant,$save_allowed_fields);
            $done=orsee_db_save_array($participant,"participants",$participant['participant_id'],"participant_id");
            if ($done) {
                log__participant("subscribe",$participant['lname'].', '.$participant['fname']);
                $proceed=false;
                $done=experimentmail__confirmation_mail($participant);
                message(lang('successfully_registered'));
                redirect("public/");
            } else {
                message(lang('database_error'),'error');
            }
        }
    }

    if ($proceed) {
        if (isset($_REQUEST['add']) && $_REQUEST['add'] && isset($_SESSION['message_queue']) && is_array($_SESSION['message_queue'])) {
            foreach ($_SESSION['message_queue'] as $idx=>$msg) {
                $style=(isset($msg['style']) ? (string)$msg['style'] : 'note');
                if ($style==='note' || $style==='') {
                    $_SESSION['message_queue'][$idx]['style']='error';
                }
            }
        }

        $render_subpool=$selected_subpool;
        if (!$render_subpool) {
            if (count($all_pool_ids)>0) {
                $render_subpool=(int)$all_pool_ids[0];
            } else {
                $render_subpool=1;
            }
        }
        $render_input=array();
        if (isset($_REQUEST['add']) && $_REQUEST['add']) {
            if (isset($form_input) && is_array($form_input)) {
                $render_input=$form_input;
            } else {
                $render_input=$_REQUEST;
            }
        }
        $render_input['subpool_id']=$render_subpool;

        $pw_provided=(isset($_SESSION['pauthdata']['pw_provided']) && $_SESSION['pauthdata']['pw_provided']);
        $force_form_stage=(isset($_REQUEST['add']) && $_REQUEST['add']);

        echo '<div id="orsee-public-mobile-screen" class="orsee-public-screen">';
        echo '<div class="orsee-public-inline-message-buffer">';
        show_message();
        echo '</div>';
        echo '  <div class="orsee-public-tabpages">
                    <div id="orsee-public-create-track" class="orsee-public-tabtrack">';

        if ($need_subpool_step) {
            echo '<div id="orsee-public-create-step-subpool" class="orsee-public-tabpage">
                    <div class="orsee-public-profile-formwrap">
                        <div class="orsee-public-profile-form">
                            <div class="orsee-form-shell orsee-public-profile-shell">
                                <div class="orsee-public-session-group">
                                    <div class="orsee-public-session-group-title">'.lang('please_choose_subgroup').'</div>';
            foreach ($all_pool_ids as $subpool_id) {
                $desc=(isset($self_descriptions[$subpool_id]) ? $self_descriptions[$subpool_id] : ('#'.$subpool_id));
                echo '          <button type="button" class="orsee-public-session-link" data-create-subpool="'.$subpool_id.'">
                                    <span class="orsee-public-session-link-main">
                                        <span class="orsee-public-session-link-title">'.$desc.'</span>
                                    </span>
                                    <span class="orsee-public-session-link-chevron"><i class="fa fa-angle-'.(lang__is_rtl() ? 'left' : 'right').'" aria-hidden="true"></i></span>
                                </button>';
            }
            echo '              </div>
                            </div>
                        </div>
                    </div>
                  </div>';
        }

        if ($need_rules_step) {
            echo '<div id="orsee-public-create-step-rules" class="orsee-public-tabpage">
                    <div class="orsee-public-profile-formwrap">
                        <div class="orsee-public-profile-form">
                            <div class="orsee-form-shell orsee-public-profile-shell">';
            if ($settings['registration__require_rules_acceptance']=='y') {
                echo '          <div class="orsee-public-detail-card-head">'.lang('rules').'</div>
                                <div class="field"><div class="control orsee-richtext">'.content__get_content("rules").'</div></div>';
            }
            if ($settings['registration__require_privacy_policy_acceptance']=='y') {
                echo '          <div class="field"><div class="orsee-public-detail-card-head">'.lang('privacy_policy').'</div></div>
                                <div class="field"><div class="control orsee-richtext">'.content__get_content("privacy_policy").'</div></div>';
            }
            echo '              <div class="field"><div class="orsee-public-detail-card-head">'.lang('do_you_agree_rules_privacy').'</div></div>
                                <div class="orsee-form-actions has-text-centered">
                                    <button class="button orsee-public-btn" type="button" id="orsee-public-create-rules-yes">'.lang('yes').'</button>
                                    <button class="button orsee-public-btn orsee-public-btn--delete" type="button" id="orsee-public-create-rules-no">'.lang('no').'</button>
                                </div>
                            </div>
                        </div>
                    </div>
                  </div>';
        }

        $participant_password_dir=($settings['force_ltr_participant_login_password']==='y' ? ' dir="ltr"' : '');
        echo '      <div id="orsee-public-create-step-form" class="orsee-public-tabpage">
                        <div class="orsee-public-profile-formwrap">
                            <form id="orsee-public-create-form" action="'.thisdoc().'" method="POST" class="orsee-public-profile-form">
                                '.csrf__field().'
                                <input type="hidden" name="subpool_id" id="orsee-public-create-subpool-input" value="'.htmlspecialchars((string)$selected_subpool,ENT_QUOTES,'UTF-8').'">
                                <input type="hidden" name="accept_rules" id="orsee-public-create-rules-input" value="'.htmlspecialchars((string)$rules_accepted,ENT_QUOTES,'UTF-8').'">
                                <div class="orsee-form-shell orsee-public-profile-shell">';
        participant__show_inner_form($render_input,$errors__dataform,'profile_form_public_create');

        if ($settings['subject_authentication']!='token') {
            if ($pw_provided) {
                echo '              <div class="field">
                                        <label class="label">'.lang('password').'</label>
                                        <div class="control">***'.lang('password_provided').'***</div>
                                    </div>
                                    <div class="field">
                                        <label class="label">'.lang('repeat_password').'</label>
                                        <div class="control">***'.lang('password_provided').'***</div>
                                    </div>';
            } else {
                echo '              <div class="field">
                                        <label class="label">'.lang('password').'</label>
                                        <div class="control"><input type="password" name="password" class="input is-primary orsee-input orsee-input-text"'.$participant_password_dir.' maxlength="40"></div>
                                        <p class="help">'.lang('participant_password_note').'</p>
                                    </div>
                                    <div class="field">
                                        <label class="label">'.lang('repeat_password').'</label>
                                        <div class="control"><input type="password" name="password2" class="input is-primary orsee-input orsee-input-text"'.$participant_password_dir.' maxlength="40"></div>
                                    </div>';
            }
        }

        echo '                  <div class="field">
                                    <label class="label">'.lang('captcha_text').'</label>
                                    <div class="control"><img src="captcha.php" alt="captcha"></div>
                                </div>
                                <div class="field">
                                    <div class="control"><input type="text" name="captcha" class="input is-primary orsee-input orsee-input-text" dir="ltr" maxlength="8"></div>
                                </div>
                            </div>
                            <div class="orsee-public-profile-actions">
                                <button class="button orsee-public-btn" name="add" type="submit" value="1">'.lang('submit').'</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                </div>
              </div>';

        echo '<script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function () {
                var track = document.getElementById("orsee-public-create-track");
                if (!track) return;

                var pages = Array.prototype.slice.call(track.querySelectorAll(".orsee-public-tabpage"));
                var formPanel = document.getElementById("orsee-public-create-step-form");
                var subpoolPanel = document.getElementById("orsee-public-create-step-subpool");
                var rulesPanel = document.getElementById("orsee-public-create-step-rules");
                var formIndex = pages.indexOf(formPanel);
                var subpoolIndex = pages.indexOf(subpoolPanel);
                var rulesIndex = pages.indexOf(rulesPanel);

                var subpoolInput = document.getElementById("orsee-public-create-subpool-input");
                var rulesInput = document.getElementById("orsee-public-create-rules-input");
                var rulesYes = document.getElementById("orsee-public-create-rules-yes");
                var rulesNo = document.getElementById("orsee-public-create-rules-no");
                var currentIndex = 0;
                var slideSign = (document.documentElement && document.documentElement.dir === "rtl") ? 1 : -1;

                function syncTrackHeight() {
                    if (!pages[currentIndex]) return;
                    track.style.height = pages[currentIndex].offsetHeight + "px";
                }

                function openIndex(idx) {
                    if (idx < 0) idx = 0;
                    if (idx >= pages.length) idx = pages.length - 1;
                    currentIndex = idx;
                    track.style.transform = "translateX(" + (slideSign * idx * 100) + "%)";
                    syncTrackHeight();
                    window.setTimeout(syncTrackHeight, 60);
                    window.scrollTo(0, 0);
                    if (document.documentElement) document.documentElement.scrollTop = 0;
                    if (document.body) document.body.scrollTop = 0;
                }

                function openForm() {
                    if (formIndex >= 0) openIndex(formIndex);
                }

                document.querySelectorAll("[data-create-subpool]").forEach(function (el) {
                    el.addEventListener("click", function () {
                        var sid = this.getAttribute("data-create-subpool") || "";
                        if (sid) {
                            var url = new URL(window.location.href);
                            url.searchParams.set("subpool_id", sid);
                            window.location.href = url.toString();
                            return;
                        }
                        if (subpoolInput) subpoolInput.value = sid;
                        if (rulesIndex >= 0) openIndex(rulesIndex);
                        else openForm();
                    });
                });

                if (rulesYes) {
                    rulesYes.addEventListener("click", function () {
                        if (rulesInput) rulesInput.value = "1";
                        openForm();
                    });
                }

                if (rulesNo) {
                    rulesNo.addEventListener("click", function () {
                        window.location.href = "./";
                    });
                }

                if (window.ResizeObserver) {
                    var ro = new ResizeObserver(function () {
                        syncTrackHeight();
                    });
                    pages.forEach(function (p) { if (p) ro.observe(p); });
                }
                window.addEventListener("resize", syncTrackHeight);
                window.addEventListener("load", syncTrackHeight);

                var forceFormStage = '.($force_form_stage ? 'true' : 'false').';
                var forceSubpoolStage = '.($force_subpool_stage ? 'true' : 'false').';
                if (forceFormStage) {
                    openForm();
                } else if (forceSubpoolStage && subpoolIndex >= 0) {
                    openIndex(subpoolIndex);
                } else if (subpoolIndex >= 0 && (!subpoolInput || !subpoolInput.value)) {
                    openIndex(subpoolIndex);
                } else if (rulesIndex >= 0 && (!rulesInput || rulesInput.value !== "1")) {
                    openIndex(rulesIndex);
                } else {
                    openForm();
                }
            });
        </script>';
    }
}

include("footer.php");

?>
