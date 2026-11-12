<?php
// part of orsee. see orsee.org
$debug__script_started=microtime();
include("../config/settings.php");
include("../config/system.php");
include("../config/requires.php");

$proceed=true;

error_reporting(0);  // shut down all error reporting

header('X-Frame-Options: SAMEORIGIN');

if (!empty($_SERVER['HTTPS'])) {
    // Adds Strict-Transport-Security to the HTTP header.
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Adds the Content-Security-Policy to the HTTP header.
header("Content-Security-Policy: default-src 'self'; script-src 'self';");
header("X-Content-Security-Policy: default-src 'self'; script-src 'self';"); 

if ($proceed) {
    site__database_config();
    $settings=load_settings();
    $settings['style']=$settings['orsee_public_style'];
    $color=load_colors();
    orsee_session_register_handler();
    session_start();
    $_REQUEST=strip_tags_array($_REQUEST);

    $currentCookieParams = session_get_cookie_params();

    // set cookie flags secure and http
    session_set_cookie_params(
        $currentCookieParams['lifetime'],
        $currentCookieParams['path'],
        $currentCookieParams['domain'],
        true,
        true
    );
}

if ($proceed) {
    if ($settings['stop_public_site']=="y" && !isset($expadmindata['adminname']) && !(thisdoc()=="disabled.php")) {
        redirect("public/disabled.php");
    }
}

if ($proceed) {
    // with token-only, do not allow access to these pages
    $token_exclude=array("participant_reset_pw.php",
            "participant_login.php");
    if ($settings['subject_authentication']=='token' && in_array(thisdoc(),$token_exclude)) {
        redirect("public/");
    }
}


if ($proceed) {
    // if we work with tokens or do the migration, check for token on any page
    if ($settings['subject_authentication']=='token' || $settings['subject_authentication']=='migration') {
        $participant_id=site__check_token();
        if ($participant_id) {
            // get participant's language
            $participant=orsee_db_load_array("participants",$participant_id,"participant_id");
            $_SESSION['pauthdata']['language']=$participant['language'];
            unset($participant);
            $show_logged_in_menu=true;
        }
    }
}

if ($proceed) {
    // determine language for page
    if (!isset($_SESSION['pauthdata']['language']) || !$_SESSION['pauthdata']['language']) {
        $_SESSION['pauthdata']['language']=$settings['public_standard_language'];
    }
    if (isset($_REQUEST['language'])) {
        $langarray=lang__get_public_langs();
        if (in_array($_REQUEST['language'],$langarray)) {
            $_SESSION['pauthdata']['language']=$_REQUEST['language'];
        }
    }
    $lang=load_language($_SESSION['pauthdata']['language']);
    $lang_icons_prepare=true;
}


if ($proceed) {
    if (!in_array(thisdoc(),array('participant_create.php','captcha.php'))) {
        unset($_SESSION['subpool_id']);
        unset($_SESSION['rules']);
    }
}

if ($proceed) {
    // require participant login for the following pages
    $part_load=array("participant_edit.php",
            "participant_show.php",
            "participant_logout.php");

    if (in_array(thisdoc(),$part_load)) {
        $token_string='';
        // if already logged in, just load participant data
        if (isset($_SESSION['pauthdata']['user_logged_in']) && $_SESSION['pauthdata']['user_logged_in'] &&
            isset($_SESSION['pauthdata']['participant_id']) && $_SESSION['pauthdata']['participant_id']) {
            $participant=orsee_db_load_array("participants",$_SESSION['pauthdata']['participant_id'],"participant_id");
            $participant_id=$participant['participant_id'];
        } else {
            if ($settings['subject_authentication']=='token') {
                // if we work with tokens, check whether we are logged in and load participant data
                if ($participant_id) {
                    $participant=orsee_db_load_array("participants",$participant_id,"participant_id");
                    $token_string="?p=".urlencode($participant['participant_id_crypt']);
                } else {
                    redirect("public/");
                }
            } elseif ($settings['subject_authentication']=='migration') {
                // if we migrate
                if ($participant_id) {
                    $participant=orsee_db_load_array("participants",$participant_id,"participant_id");
                    // if pw exists, the send to login page
                    if ($participant['password_crypted']) {
                        redirect("public/participant_login.php");
                    } else {
                        // prepare password reset: generate token, save token to db and session
                        $participant['pwreset_token']=create_random_token(get_entropy($participant));
                        $pars=array(':token'=>$participant['pwreset_token'],
                                ':participant_id'=>$participant['participant_id'],
                                ':now'=>time());
                        $query="UPDATE ".table('participants')."
                                    SET pwreset_token = :token,
                                    pwreset_request_time = :now
                                    WHERE participant_id= :participant_id";
                        $done=or_query($query,$pars);
                        $_SESSION['pw_reset_token']=$participant['pwreset_token'];
                        // send to pw rest page
                        message(lang('please_choose_a_password_for_your_account'));
                        redirect("public/participant_reset_pw.php");
                    }
                } else {
                    // send to login page if no token is present
                    redirect("public/participant_login.php");
                }
            } else {
                // and if we only allow username/passsword, send to login page
                redirect("public/participant_login.php");
            }
        }
        if ($proceed) {
            // do some other checks when we are logged in
            $statuses=participant_status__get_statuses();
            $statuses_profile=participant_status__get("access_to_profile");
            if (isset($participant) && !in_array($participant['status_id'],$statuses_profile)) {
                message($statuses[$participant['status_id']]['error']." ".
                lang('if_you_have_questions_write_to')." ".support_mail_link(),'error');
                redirect("public/");
            }
        }
    }
}

if ($proceed) {
    // load public menu configuration and initialize localized page title
    $menu_config=html__menu_load_config('public');
    $title_lang=(isset($title) && $title!=='' ? lang($title) : '');
    $is_logged_in=((isset($_SESSION['pauthdata']['user_logged_in']) && $_SESSION['pauthdata']['user_logged_in']) || (isset($show_logged_in_menu) && $show_logged_in_menu));
    $menu_item=false;
    if (!isset($menu_item_id)) {
        $menu_item_id=false;
    }

    if (thisdoc()==='index.php') {
        // resolve requested richtext page for index.php (fallback: mainpage_welcome)
        $requested_page='mainpage_welcome';
        if (isset($_REQUEST['page']) && trim((string)$_REQUEST['page'])!=='') {
            $requested_page=trim((string)$_REQUEST['page']);
        }

        $menu_item_id=false;
        if (isset($menu_config['public_content_to_id'][$requested_page])
            && isset($menu_config['items'][$menu_config['public_content_to_id'][$requested_page]])
            && is_array($menu_config['items'][$menu_config['public_content_to_id'][$requested_page]])
            && isset($menu_config['items'][$menu_config['public_content_to_id'][$requested_page]]['richtext'])
            && $menu_config['items'][$menu_config['public_content_to_id'][$requested_page]]['richtext']==='y') {
            $menu_item_id=(string)$menu_config['public_content_to_id'][$requested_page];
        } else {
            $menu_item_id=$menu_config['public_content_to_id']['mainpage_welcome'];
            $requested_page='mainpage_welcome';
        }
        $menu_item=$menu_config['items'][$menu_item_id];

        // set active menu item and title from resolved menu definition
        if (isset($menu_item['id']) && trim((string)$menu_item['id'])!=='') {
            $menu__area=(string)$menu_item['id'];
        }

        if (isset($menu_item['page_title_lang'][lang('lang')]) && $menu_item['page_title_lang'][lang('lang')]!=='') {
            $title_lang=$menu_item['page_title_lang'][lang('lang')];
        }
        $GLOBALS['public__menu_page_item']=$menu_item;
        $GLOBALS['public__show_mainpage']=($requested_page==='mainpage_welcome');

        // show quick actions on public mainpage
        if ($GLOBALS['public__show_mainpage']) {
            if ($is_logged_in) {
                $public_pagebar_actions=array(
                    array('href'=>'participant_edit.php','icon'=>'fa-user','label'=>'my_data'),
                    array('href'=>'participant_show.php','icon'=>'fa-calendar','label'=>'mobile_enrolments')
                );
            } else {
                $public_pagebar_actions=array(
                    array('href'=>'participant_create.php','icon'=>'fa-user','label'=>'register')
                );
                if ($settings['subject_authentication']!=='token') {
                    $public_pagebar_actions[]=array('href'=>'participant_login.php','icon'=>'fa-sign-in orsee-public-pagebar-login-icon','label'=>'login');
                }
            }
        }
    } else {
        // resolve explicit menu item access gate for non-index public pages
        if ($menu_item_id && trim((string)$menu_item_id)!=='') {
            $menu_item_id=trim((string)$menu_item_id);
            if (isset($menu_config['items'][$menu_item_id]) && is_array($menu_config['items'][$menu_item_id])) {
                $menu_item=$menu_config['items'][$menu_item_id];
            }
        }
    }

    // centralized access check for any page that resolved to a menu item id
    if ($menu_item_id && trim((string)$menu_item_id)!=='') {
        if (!is_array($menu_item) || !html__menu_can_access_item('public',$menu_item,$is_logged_in)) {
            redirect('public/');
        }
    }
}

if ($proceed) {
    // render page header and optional public pagebar
    $pagetitle=$settings['default_area'];
    $pagetitle=$pagetitle.': '.$title_lang;
    if (!isset($suppress_html_header) || !$suppress_html_header) {
        html__header();
        html__show_style_header('public',$title_lang);
        $has_title=($title_lang!=='');
        $has_public_pagebar_actions=(isset($public_pagebar_actions) && is_array($public_pagebar_actions) && count($public_pagebar_actions)>0);
        if ($has_title || $has_public_pagebar_actions) {
            echo '<div class="orsee-public-pagebar'.($has_public_pagebar_actions ? ' has-actions' : '').'">';
            if ($has_title) {
                echo '<span class="orsee-public-pagebar-title">'.$title_lang.'</span>';
            }
            if ($has_public_pagebar_actions) {
                echo '<span class="orsee-public-pagebar-actions">';
                foreach ($public_pagebar_actions as $action) {
                    if (!is_array($action) || !isset($action['href']) || !$action['href']) {
                        continue;
                    }
                    $label_key=(isset($action['label']) ? (string)$action['label'] : '');
                    $label_text=($label_key ? lang($label_key) : '');
                    $icon_class=(isset($action['icon']) ? trim((string)$action['icon']) : '');
                    $href=(string)$action['href'];
                    if (in_array($settings['subject_authentication'],array('token','migration'),true) && isset($_REQUEST['p']) && trim((string)$_REQUEST['p'])!=='') {
                        $href.=((strpos($href,'?')===false) ? '?' : '&').'p='.urlencode((string)$_REQUEST['p']);
                    }
                    echo '<a class="orsee-public-pagebar-action" href="'.htmlspecialchars($href,ENT_QUOTES,'UTF-8').'">';
                    if ($icon_class!=='') {
                        echo '<i class="fa '.htmlspecialchars($icon_class,ENT_QUOTES,'UTF-8').'" aria-hidden="true"></i> ';
                    }
                    echo htmlspecialchars($label_text,ENT_QUOTES,'UTF-8');
                    echo '</a>';
                }
                echo '</span>';
            }
            echo '</div>';
        }
        // render flash/toast host and initialize responsive panel min-height sync
        $toast_duration_ms=3200;
        if (isset($settings['public_mobile_toast_duration_ms'])) {
            $toast_duration_ms=(int)$settings['public_mobile_toast_duration_ms'];
            if ($toast_duration_ms<800) {
                $toast_duration_ms=800;
            }
            if ($toast_duration_ms>12000) {
                $toast_duration_ms=12000;
            }
        }
        echo '<div id="orsee-public-message-host">';
        show_message();
        echo '</div>';
        echo javascript__toast_messages($toast_duration_ms,'.orsee','orsee-public-message-host','orsee-public-toast-host');
        echo '<script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function () {
                var root = document.querySelector(".orsee");
                if (!root) return;

                function syncPublicPanelMinHeight() {
                    if (!window.matchMedia || !window.matchMedia("(min-width: 741px)").matches) {
                        root.style.removeProperty("--orsee-public-panel-min-height");
                        return;
                    }
                    var menu = root.querySelector(".orsee-menu");
                    if (!menu) return;
                    var menuHeight = Math.ceil(menu.getBoundingClientRect().height);
                    if (menuHeight > 0) root.style.setProperty("--orsee-public-panel-min-height", menuHeight + "px");
                }

                syncPublicPanelMinHeight();
                window.addEventListener("resize", syncPublicPanelMinHeight);
            });
        </script>';
    }
}
?>
