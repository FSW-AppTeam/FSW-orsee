<?php
// part of orsee. see orsee.org

function include_js($name,$inc_css=true,$use_min=true) {
    // include a JS module from tagsets/js, with optional matching CSS and minified naming
    if ($use_min) {
        if ($name) {
            $name.='.';
        }
        $name=$name.'min.';
    } else {
        if ($name) {
            $name.='.';
        }
    }
    if ($inc_css) {
        echo '<link rel="stylesheet" type="text/css" href="../tagsets/js/'.$name.'css" />'."\n";
    }
    echo '<script src="../tagsets/js/'.$name.'js"></script>'."\n";
}

function include_flatpickr() {
    // include flatpickr assets and auto-load dark theme via prefers-color-scheme
    include_js('flatpickr/flatpickr',true,true);
    echo '<link rel="stylesheet" type="text/css" href="../tagsets/js/flatpickr/themes/dark.css" media="(prefers-color-scheme: dark)" />'."\n";
}

function include_coloris() {
    // include color picker assets
    include_js('coloris/coloris',true,true);
}

function html__menu_normalize_content_name($content_name) {
    // normalize a menu content_name to a safe lowercase token
    $content_name=strtolower(trim((string)$content_name));
    $content_name=preg_replace('/[^a-z0-9_\-]+/','_',$content_name);
    $content_name=trim((string)$content_name,'_');
    return $content_name;
}

function html__header() {
    // render shared HTML head/body start and include active style/js assets
    global $pagetitle,$settings, $color, $lang_icons_prepare;
    global $js_modules;
    global $include_coloris;
    $orsee_dir=(lang__is_rtl() ? 'rtl' : 'ltr');

    echo '<HTML dir="'.$orsee_dir.'">
<HEAD>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="expires" content="0">
<TITLE>'.$pagetitle.'</TITLE>
<link rel="stylesheet" type="text/css" href="../tagsets/css/bulma.min.css">
<link rel="stylesheet" type="text/css" href="../tagsets/css/orsee_core.css">
<link rel="stylesheet" type="text/css" href="../tagsets/css/orsee_default.css">
';
    $style_css_file=__DIR__.'/../style/'.$settings['style'].'/orsee.css';
    if (is_file($style_css_file)) {
        echo '<link rel="stylesheet" type="text/css" href="../style/'.$settings['style'].'/orsee.css">'."\n";
    }
    echo '
<link rel="stylesheet" href="../tagsets/fonts/fontawesome/icons.fa.css">
';


    if (isset($js_modules) && is_array($js_modules)) {
        if (in_array('colorpicker',$js_modules)) {
            include_coloris();
        }
        if (in_array('faiconselector',$js_modules)) {
            include_js('fa-icon-selector',true,false);
        }
        if (in_array('listtool',$js_modules)) {
            include_js('listtool-native',false,false);
        }
        if (in_array('switchy',$js_modules)) {
            include_js('switchy-native',false,false);
        }
        if (in_array('queryform',$js_modules)) {
            include_js('queryform-native',true,false);
        }
        if (in_array('flatpickr',$js_modules)) {
            include_flatpickr();
        }
        if (in_array('intltelinput',$js_modules)) {
            echo '<link rel="stylesheet" type="text/css" href="../tagsets/js/intlTelInput/intlTelInput.css" />'."\n";
            echo '<script src="../tagsets/js/intlTelInput/intlTelInput.min.js"></script>'."\n";
        }
    }
    if (isset($include_coloris) && $include_coloris) {
        include_coloris();
    }

    echo '<style type="text/css">';
    echo ':root{'."\n";
    if (isset($color) && is_array($color)) {
        foreach ($color as $k=>$v) {
            $css_key=preg_replace('/[^a-zA-Z0-9_\-]/','',$k);
            if ($css_key!=="") {
                echo '--orsee-'.$css_key.': '.htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8').';'."\n";
            }
        }
    }
    echo '}'."\n";
    if (isset($lang_icons_prepare) && $lang_icons_prepare) {
        lang_icons_prepare();
    }
    echo '</style>';
    if (isset($settings['enable_header_glimmer_effect']) && $settings['enable_header_glimmer_effect']=='y') {
        echo '<style type="text/css">
@keyframes orsee-header-glimmer-sweep {
  from { left: -95%; }
  to { left: 115%; }
}
@keyframes orsee-header-glimmer-sweep-rtl {
  from { right: -95%; }
  to { right: 115%; }
}
.orsee .orsee-masthead {
  overflow: hidden;
}
.orsee .orsee-masthead::after {
  content: "";
  position: absolute;
  top: 0;
  left: -95%;
  width: 62%;
  height: 100%;
  pointer-events: none;
  transform: skewX(-14deg);
  background: linear-gradient(
    90deg,
    color-mix(in srgb, var(--color-mix-anchor-light) 0%, transparent),
    color-mix(in srgb, var(--color-mix-anchor-light) 34%, transparent),
    color-mix(in srgb, var(--color-mix-anchor-light) 0%, transparent)
  );
  z-index: 4;
  will-change: left;
}
[dir="rtl"] .orsee .orsee-masthead::after {
  left: auto;
  right: -95%;
}
.orsee .orsee-masthead:hover::after,
.orsee .orsee-masthead:focus-within::after {
  animation: orsee-header-glimmer-sweep 0.72s linear;
}
[dir="rtl"] .orsee .orsee-masthead:hover::after,
[dir="rtl"] .orsee .orsee-masthead:focus-within::after {
  animation: orsee-header-glimmer-sweep-rtl 0.72s linear;
}
</style>';
    }

    echo '
</HEAD>
<body dir="'.$orsee_dir.'"';
    echo ' TOPMARGIN=0 LEFTMARGIN=0 MARGINWIDTH=0 MARGINHEIGHT=0';
    echo '>
';
}


function html__footer() {
    // render shared HTML closing tags

    echo '
</BODY>
</HTML>';
}

function html__show_style_header($area='public',$title="") {
    // load style header template, inject runtime title/menu/modal hosts, then render
    global $settings, $lang, $color, $expadmindata, $authdata, $navigation_disabled, $show_logged_in_menu;

    $style_tpl='../style/'.$settings['style'].'/orsee_header.php';
    $default_tpl='../tagsets/css/orsee_default_header.php';
    $tpl_file=(is_file($style_tpl) ? $style_tpl : $default_tpl);
    $tpl=file_get_contents($tpl_file);

    // add title
    $title_html=($title ?? '');
    $document=thisdoc();
    $title_style_attr='';
    if ($document=='participant_login.php') {
        $title_html='<span class="orsee-page-title-icon orsee-page-title-icon-participant"><i class="fa fa-user" aria-hidden="true"></i></span>'.$title_html;
        $title_style_attr=' style="text-align: center;"';
    } elseif ($document=='admin_login.php') {
        $title_html='<span class="orsee-page-title-icon orsee-page-title-icon-admin"><i class="fa fa-shield" aria-hidden="true"></i></span>'.$title_html;
        $title_style_attr=' style="text-align: center;"';
    }

    $menu_mode=html__menu_mode_for_area($area);
    $menu_orientation='vertical';
    if ($menu_mode!='vertical') {
        $menu_orientation='horizontal';
    }

    // prepare menu
    $menu_html='';
    if (!(isset($navigation_disabled) && $navigation_disabled)) {
        if ($area=='admin' && isset($expadmindata['adminname'])) {
            $logged_in=true;
            $menu=html__get_admin_menu();
        } else {
            if ((isset($_SESSION['pauthdata']['user_logged_in']) && $_SESSION['pauthdata']['user_logged_in'])
                || $show_logged_in_menu) {
                $logged_in=true;
            } else {
                $logged_in=false;
            }
            $menu=html__get_public_menu();
        }
        $menu_extra_vertical='';
        $menu_extra_horizontal='';
        $menu_langswitch_script='';
        if ($area==='public' && isset($settings['display_language_select_in_public_menu']) && $settings['display_language_select_in_public_menu']==='y') {
            $menu_extra_vertical='<div class="orsee-public-menu-langswitch orsee-public-menu-langswitch-vertical">'.lang__compact_language_switch(lang('lang'),'public','orsee-public-menu-langswitch-vertical','language').'</div>';
            $menu_extra_horizontal='<div class="orsee-public-menu-langswitch orsee-public-menu-langswitch-horizontal">'.lang__compact_language_switch(lang('lang'),'public','orsee-public-menu-langswitch-horizontal','language').'</div>';
            $menu_langswitch_script=javascript__language_switch_script('.orsee-public-menu-langswitch [data-orsee-language-select]','language');
        }
        $menu_vertical=html__build_menu($menu,$logged_in,'vertical',$menu_mode,$menu_extra_vertical);
        $menu_horizontal=html__build_menu($menu,$logged_in,'horizontal',$menu_mode,$menu_extra_horizontal);
        $menu_html='<aside class="orsee-menu"><div class="orsee-menu-vertical">'.$menu_vertical.'</div><div class="orsee-menu-horizontal">'.$menu_horizontal.'</div></aside>';
        $menu_html.=$menu_langswitch_script;
    }

    // fill in language terms in style masthead template if any
    $pattern="/lang\[([^\]]+)\]/i";
    $replacement = "\$lang['$1']";
    $tpl=preg_replace_callback($pattern,
        'template_replace_callbackB',
        $tpl);

    $root_open='<div class="orsee orsee-area-'.$area.' orsee-orientation-'.$menu_orientation.'" data-menu-mode="'.$menu_mode.'">';
    $overlay_host='
    <div id="orsee-public-confirm" class="orsee-public-confirm is-hidden">
        <div class="orsee-public-confirm-card">
            <div class="orsee-public-confirm-title">'.lang('mobile_confirmation').'</div>
            <div id="orsee-public-confirm-text" class="orsee-public-confirm-text"></div>
            <div class="orsee-public-confirm-actions">
                <button type="button" id="orsee-public-confirm-no" class="orsee-public-confirm-action">'.lang('mobile_sorry_no').'</button>
                <button type="button" id="orsee-public-confirm-yes" class="orsee-public-confirm-action">'.lang('mobile_yes_please').'</button>
            </div>
        </div>
    </div>
    <div id="participantEditModal" class="modal orsee-modal orsee-modal--participant-edit">
        <div class="modal-background" data-close-participant-modal></div>
        <div class="modal-card">
            <header class="modal-card-head orsee-panel-title">
                <p class="modal-card-title">'.lang('edit_participant').'</p>
                <button class="delete" aria-label="close" data-close-participant-modal></button>
            </header>
            <section class="modal-card-body orsee-modal-body">
                <iframe id="participantPopupIframe" class="orsee-modal-frame" src="about:blank"></iframe>
                <div id="participantPopupLoadAnimation" class="orsee-modal-loader">
                    <i class="fa fa-spinner fa-spin" style="color: var(--color-text-secondary);"></i>
                </div>
            </section>
            <footer class="modal-card-foot orsee-panel-title is-justify-content-flex-end">
                <button type="button" class="button orsee-btn" data-close-participant-modal><i class="fa fa-'.(lang__is_rtl() ? 'arrow-right' : 'arrow-left').'"></i>&nbsp;'.lang('back_to_results').'</button>
            </footer>
        </div>
    </div>
    <div id="emailViewModal" class="modal orsee-modal orsee-modal--participant-edit">
        <div class="modal-background" data-close-email-modal></div>
        <div class="modal-card">
            <header class="modal-card-head orsee-panel-title">
                <p class="modal-card-title">'.lang('email_view_message').'</p>
                <button class="delete" aria-label="close" data-close-email-modal></button>
            </header>
            <section class="modal-card-body orsee-modal-body">
                <iframe id="emailPopupIframe" class="orsee-modal-frame" src="about:blank"></iframe>
                <div id="emailPopupLoadAnimation" class="orsee-modal-loader">
                    <i class="fa fa-spinner fa-spin" style="color: var(--color-text-secondary);"></i>
                </div>
            </section>
            <footer class="modal-card-foot orsee-panel-title is-justify-content-flex-end">
                <button type="button" class="button orsee-btn" data-close-email-modal><i class="fa fa-'.(lang__is_rtl() ? 'arrow-right' : 'arrow-left').'"></i>&nbsp;'.lang('email_back_to_list').'</button>
            </footer>
        </div>
    </div>
    <div id="bulkActionModal" class="modal orsee-modal orsee-modal--bulk-action">
        <div class="modal-background" data-close-bulk-modal></div>
        <div class="modal-card">
            <header class="modal-card-head orsee-panel-title">
                <p class="modal-card-title">'.lang('for_all_selected_participants').'</p>
                <button class="delete" aria-label="close" data-close-bulk-modal></button>
            </header>
            <section class="modal-card-body orsee-modal-body">
                <div id="bulkPopupContent"></div>
            </section>
            <footer class="modal-card-foot orsee-panel-title is-justify-content-flex-end">
                <button type="button" class="button orsee-btn" data-close-bulk-modal><i class="fa fa-'.(lang__is_rtl() ? 'arrow-right' : 'arrow-left').'"></i>&nbsp;'.lang('back_to_results').'</button>
            </footer>
        </div>
    </div>';
    $shell_open='<div class="orsee-shell">'.$menu_html.'<main class="orsee-main"><h1 class="orsee-title"'.$title_style_attr.'>'.$title_html.'</h1><div class="orsee-content">';

    echo $root_open.$overlay_host.$tpl.$shell_open;
}

function html__show_style_footer($area='public') {
    // load style footer template, resolve language placeholders, then render
    global $settings, $lang, $color, $expadmindata, $authdata, $navigation_disabled, $show_logged_in_menu;

    $style_tpl='../style/'.$settings['style'].'/orsee_footer.php';
    $default_tpl='../tagsets/css/orsee_default_footer.php';
    $tpl_file=(is_file($style_tpl) ? $style_tpl : $default_tpl);
    $tpl=file_get_contents($tpl_file);

    // fill in language terms if any
    $pattern="/lang\[([^\]]+)\]/i";
    $replacement = "\$lang['$1']";
    $tpl=preg_replace_callback($pattern,
        'template_replace_callbackB',
        $tpl);

    echo $tpl;
    echo '</div></main></div></div>';
}



function html__menu_mode_for_area($area='public') {
    // resolve configured menu mode for public/admin area with vertical fallback
    global $settings;
    $menu_mode='vertical';
    $valid_menu_modes=array('vertical','horizontal_dynamic_submenu','horizontal_static_submenu');
    if ($area==='admin') {
        if (isset($settings['admin_menu_mode']) && in_array($settings['admin_menu_mode'],$valid_menu_modes)) {
            $menu_mode=(string)$settings['admin_menu_mode'];
        }
    } else {
        if (isset($settings['public_menu_mode']) && in_array($settings['public_menu_mode'],$valid_menu_modes)) {
            $menu_mode=(string)$settings['public_menu_mode'];
        }
    }
    return $menu_mode;
}

function html__menu_text_from_lang_map($text_lang,$fallback='') {
    // resolve localized text from language map with deterministic fallback order
    global $settings;
    if (!is_array($text_lang) || count($text_lang)===0) {
        return (string)$fallback;
    }
    $language=lang('lang');
    if (isset($text_lang[$language]) && trim((string)$text_lang[$language])!=='') {
        return (string)$text_lang[$language];
    }
    if (isset($settings['public_standard_language']) && isset($text_lang[$settings['public_standard_language']]) && trim((string)$text_lang[$settings['public_standard_language']])!=='') {
        return (string)$text_lang[$settings['public_standard_language']];
    }
    foreach ($text_lang as $value) {
        if (trim((string)$value)!=='') {
            return (string)$value;
        }
    }
    return (string)$fallback;
}

function html__menu_default_admin_items() {
    // return legacy admin menu defaults used to initialize menu config
    global $settings;
    $menu=array();
    $menu[]=                    array(
                                'menu_area'=>'admin_mainpage',
                                'entrytype'=>'headlink',
                                'lang_item'=>'mainpage',
                                'page_title_lang_item'=>'welcome',
                                'link'=>'/admin/',
                                'icon'=>'home',
                                'show_if_not_logged_in'=>0,
                                'show_if_logged_in'=>1
                                );
    $menu[]=
                        array(
                                'menu_area'=>'experiments',
                                'entrytype'=>'head',
                                'lang_item'=>'experiments',
                                'link'=>'',
                                'icon'=>'cogs',
                                'show_if_not_logged_in'=>0,
                                'show_if_logged_in'=>1
                                );
    $menu[]=
                        array(
                                'menu_area'=>'experiments_main',
                                'entrytype'=>'link',
                                'lang_item'=>'overview',
                                'page_title_lang_item'=>'experiments',
                                'link'=>'/admin/experiment_main.php',
                                'icon'=>'',
                                'show_if_not_logged_in'=>0,
                                'show_if_logged_in'=>1
                                );
    $menu[]=
                        array(
                                'menu_area'=>'experiments_my',
                                'entrytype'=>'link',
                                'lang_item'=>'my_experiments',
                                'link'=>'/admin/experiment_my.php',
                                'icon'=>'',
                                'show_if_not_logged_in'=>0,
                                'show_if_logged_in'=>1
                                );
    $menu[]=
                        array(
                                'menu_area'=>'experiments_new',
                                'entrytype'=>'link',
                                'lang_item'=>'create_new',
                                'page_title_lang_item'=>'edit_experiment',
                                'link'=>'/admin/experiment_edit.php?addit=true',
                                'icon'=>'',
                                'show_if_not_logged_in'=>0,
                                'show_if_logged_in'=>1
                                );
    $menu[]=
                        array(
                                'menu_area'=>'experiments_old',
                                'entrytype'=>'link',
                                'lang_item'=>'menu_completed_experiments',
                                'page_title_lang_item'=>'finished_experiments',
                                'link'=>'/admin/experiment_old.php',
                                'icon'=>'',
                                'show_if_not_logged_in'=>0,
                                'show_if_logged_in'=>1
                                );
    $menu[]=
                        array(
                                'menu_area'=>'participants',
                                'entrytype'=>'head',
                                'lang_item'=>'participants',
                                'link'=>'',
                                'icon'=>'users',
                                'show_if_not_logged_in'=>0,
                                'show_if_logged_in'=>1
                                );
    $menu[]=
                        array(
                                'menu_area'=>'participants_main',
                                'entrytype'=>'link',
                                'lang_item'=>'overview',
                                'page_title_lang_item'=>'participants',
                                'link'=>'/admin/participants_main.php',
                                'icon'=>'',
                                'show_if_not_logged_in'=>0,
                                'show_if_logged_in'=>1
                                );
    $menu[]=
                        array(
                                'menu_area'=>'participants_create',
                                'entrytype'=>'link',
                                'lang_item'=>'create_new',
                                'page_title_lang_item'=>'edit_participant',
                                'link'=>'/admin/participants_edit.php',
                                'icon'=>'',
                                'show_if_not_logged_in'=>0,
                                'show_if_logged_in'=>1
                                );
    $menu[]=                    array(
                                'menu_area'=>'experiment_calendar',
                                'entrytype'=>'headlink',
                                'lang_item'=>'calendar',
                                'link'=>'/admin/calendar_main.php',
                                'icon'=>'calendar',
                                'show_if_not_logged_in'=>0,
                                'show_if_logged_in'=>1
                                );
    if ($settings['enable_email_module']=='y') {
        $menu[]=                    array(
                                    'menu_area'=>'emails',
                                    'entrytype'=>'headlink',
                                    'lang_item'=>'emails',
                                    'link'=>'/admin/emails_main.php',
                                    'icon'=>'envelope-o',
                                    'show_if_not_logged_in'=>0,
                                    'show_if_logged_in'=>1
                                    );
    }
    $menu[]=
                        array(
                                'menu_area'=>'files',
                                'entrytype'=>'headlink',
                                'lang_item'=>'files',
                                'link'=>'/admin/download_main.php',
                                'icon'=>'download',
                                'show_if_not_logged_in'=>0,
                                'show_if_logged_in'=>1
                                );
    $menu[]=
                        array(
                                'menu_area'=>'options',
                                'entrytype'=>'headlink',
                                'lang_item'=>'options',
                                'link'=>'/admin/options_main.php',
                                'icon'=>'gavel',
                                'show_if_not_logged_in'=>0,
                                'show_if_logged_in'=>1
                                );
    $menu[]=
                        array(
                                'menu_area'=>'statistics',
                                'entrytype'=>'headlink',
                                'lang_item'=>'statistics',
                                'link'=>'/admin/statistics_main.php',
                                'icon'=>'bar-chart-o',
                                'show_if_not_logged_in'=>0,
                                'show_if_logged_in'=>1
                                );
    $menu[]=                    array(
                                'menu_area'=>'logout',
                                'entrytype'=>'headlink',
                                'lang_item'=>'logout',
                                'link'=>'/admin/admin_logout.php',
                                'icon'=>'sign-out',
                                'show_if_not_logged_in'=>0,
                                'show_if_logged_in'=>1
                                );
    return $menu;
}

function html__menu_default_public_items() {
    // return legacy public menu defaults used to initialize menu config
    global $settings;
    $menu=array();
    $menu[]=        array(
                            'menu_area'=>'mainpage_welcome',
                            'entrytype'=>'headlink',
                            'lang_item'=>'mainpage',
                            'link'=>'/public/index.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>1,
                            'show_if_logged_in'=>1
                            );
    $menu[]=        array(
                            'menu_area'=>'public_register',
                            'entrytype'=>'link',
                            'lang_item'=>'register',
                            'page_title_lang_item'=>'registration_form',
                            'link'=>'/public/participant_create.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>1,
                            'show_if_logged_in'=>0
                            );
    if ($settings['subject_authentication']!='token') {
        $menu[]=        array(
                                'menu_area'=>'login',
                                'entrytype'=>'link',
                                'lang_item'=>'login',
                                'page_title_lang_item'=>'profile_login',
                                'link'=>'/public/participant_login.php',
                                'icon'=>'',
                                'show_if_not_logged_in'=>1,
                                'show_if_logged_in'=>0
                                );
    }

    $menu[]=        array(
                            'menu_area'=>'my_data',
                            'entrytype'=>'link',
                            'lang_item'=>'my_data',
                            'page_title_lang_item'=>'edit_participant_data',
                            'link'=>'/public/participant_edit.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>0,
                            'show_if_logged_in'=>1
                            );
    $menu[]=        array(
                            'menu_area'=>'my_registrations',
                            'entrytype'=>'link',
                            'lang_item'=>'my_registrations',
                            'page_title_lang_item'=>'experiments',
                            'link'=>'/public/participant_show.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>0,
                            'show_if_logged_in'=>1
                            );
    if ($settings['subject_authentication']!='token') {
        $menu[]=        array(
                                'menu_area'=>'logout',
                                'entrytype'=>'link',
                                'lang_item'=>'logout',
                                'link'=>'/public/participant_logout.php',
                                'icon'=>'',
                                'show_if_not_logged_in'=>0,
                                'show_if_logged_in'=>1
                                );
    }
    $menu[]=            array(
                            'menu_area'=>'calendar',
                            'entrytype'=>'headlink',
                            'lang_item'=>'calendar',
                            'page_title_lang_item'=>'experiment_calendar',
                            'link'=>'/public/show_calendar.php',
                            'icon'=>'',
                            'show_if_not_logged_in'=>1,
                            'show_if_logged_in'=>1
                            );
    $menu[]=            array(
                                'menu_area'=>'rules',
                                'entrytype'=>'headlink',
                                'lang_item'=>'rules',
                                'link'=>'/public/rules.php',
                                'icon'=>'',
                                'show_if_not_logged_in'=>1,
                                'show_if_logged_in'=>1
                                );
    $menu[]=            array(
                                'menu_area'=>'privacy_policy',
                                'entrytype'=>'headlink',
                                'lang_item'=>'privacy_policy',
                                'link'=>'/public/privacy.php',
                                'icon'=>'',
                                'show_if_not_logged_in'=>1,
                                'show_if_logged_in'=>1
                                );
    $menu[]=            array(
                                'menu_area'=>'faqs',
                                'entrytype'=>'headlink',
                                'lang_item'=>'faqs',
                                'page_title_lang_item'=>'faq_long',
                                'link'=>'/public/faq.php',
                                'icon'=>'',
                                'show_if_not_logged_in'=>1,
                                'show_if_logged_in'=>1
                                );
    $menu[]=            array(
                                'entrytype'=>'space'
                                );
    $menu[]=            array(
                                'menu_area'=>'impressum',
                                'entrytype'=>'link',
                                'lang_item'=>'impressum',
                                'page_title_lang_item'=>'legal_notice',
                                'link'=>'/public/impressum.php',
                                'icon'=>'',
                                'show_if_not_logged_in'=>1,
                                'show_if_logged_in'=>1
                                );
    $menu[]=            array(
                                'menu_area'=>'contact',
                                'entrytype'=>'link',
                                'lang_item'=>'contact',
                                'link'=>'/public/contact.php',
                                'icon'=>'',
                                'show_if_not_logged_in'=>1,
                                'show_if_logged_in'=>1
                                );
    return $menu;
}

function html__menu_default_items($area='public') {
    // dispatch area-specific default menu set
    if ($area==='admin') {
        return html__menu_default_admin_items();
    }
    return html__menu_default_public_items();
}

function html__menu_defaults_to_config($area='public',$resolve_lang_terms=true) {
    // convert legacy defaults into normalized menu_config structure
    global $settings;

    $defaults=html__menu_default_items($area);
    $languages=array();
    if ($resolve_lang_terms) {
        $languages=get_languages();
    }
    $items=array();
    $idx=0;
    foreach ($defaults as $default) {
        $idx++;
        $entrytype=(isset($default['entrytype']) ? (string)$default['entrytype'] : 'link');
        $menu_area=(isset($default['menu_area']) && trim((string)$default['menu_area'])!=='' ? trim((string)$default['menu_area']) : ('item_'.$idx));
        $content_name='';
        $richtext='n';
        if ($area==='public') {
            if ($menu_area==='mainpage_welcome') {
                $content_name='mainpage_welcome';
                $richtext='y';
            } elseif ($menu_area==='rules') {
                $content_name='rules';
                $richtext='y';
            } elseif ($menu_area==='privacy_policy') {
                $content_name='privacy_policy';
                $richtext='y';
            } elseif ($menu_area==='impressum') {
                $content_name='impressum';
                $richtext='y';
            } elseif ($menu_area==='contact') {
                $content_name='contact';
                $richtext='y';
            }
        } else {
            if ($menu_area==='admin_mainpage') {
                $content_name='admin_mainpage';
                $richtext='y';
            }
        }

        $menu_term_lang=array();
        $page_title_lang=array();
        $lang_item=(isset($default['lang_item']) ? trim((string)$default['lang_item']) : '');
        $page_title_lang_item=(isset($default['page_title_lang_item']) ? trim((string)$default['page_title_lang_item']) : '');
        if ($resolve_lang_terms) {
            foreach ($languages as $language) {
                $term=($lang_item!=='' ? load_language_symbol($lang_item,$language) : '');
                $title_term=($page_title_lang_item!=='' ? load_language_symbol($page_title_lang_item,$language) : $term);
                $menu_term_lang[$language]=$term;
                $page_title_lang[$language]=$title_term;
            }
        } else {
            $menu_term_lang=array();
            $page_title_lang=array();
        }

        $hidden='n';
        if ($area==='public') {
            if ($menu_area==='calendar' && $settings['show_public_calendar']!=='y') {
                $hidden='y';
            }
            if ($menu_area==='rules' && $settings['show_public_rules_page']!=='y') {
                $hidden='y';
            }
            if ($menu_area==='privacy_policy' && $settings['show_public_privacy_policy']!=='y') {
                $hidden='y';
            }
            if ($menu_area==='faqs' && $settings['show_public_faqs']!=='y') {
                $hidden='y';
            }
            if ($menu_area==='impressum' && $settings['show_public_legal_notice']!=='y') {
                $hidden='y';
            }
            if ($menu_area==='contact' && $settings['show_public_contact']!=='y') {
                $hidden='y';
            }
        }

        $fixed='y';
        $removable='n';
        $can_be_secondary='y';
        $can_hide='y';
        if ($entrytype==='space') {
            $fixed='n';
            $removable='y';
            $can_be_secondary='n';
        }
        if ($area==='public') {
            if (in_array($menu_area,array('rules','privacy_policy','impressum','contact'),true)) {
                $fixed='n';
                $removable='y';
            }
            if ($menu_area==='mainpage_welcome') {
                $can_be_secondary='n';
            }
            if (in_array($menu_area,array('mainpage_welcome','public_register','login','my_data','my_registrations','logout'),true)) {
                $can_hide='n';
            }
        } else {
            if ($fixed==='y') {
                $can_hide='n';
            }
        }

        $link_value='';
        if (in_array($entrytype,array('link','headlink'),true) && isset($default['link'])) {
            $link_value=(string)$default['link'];
        }

        $items[$menu_area]=array(
            'id'=>$menu_area,
            'menu_area'=>$menu_area,
            'entrytype'=>$entrytype,
            'link'=>$link_value,
            'custom_external'=>'n',
            'icon'=>(isset($default['icon']) ? (string)$default['icon'] : ''),
            'show_if_not_logged_in'=>(isset($default['show_if_not_logged_in']) ? (int)$default['show_if_not_logged_in'] : 1),
            'show_if_logged_in'=>(isset($default['show_if_logged_in']) ? (int)$default['show_if_logged_in'] : 1),
            'hidden'=>$hidden,
            'fixed'=>$fixed,
            'removable'=>$removable,
            'can_be_secondary'=>$can_be_secondary,
            'can_hide'=>$can_hide,
            'menu_term_lang'=>$menu_term_lang,
            'page_title_lang'=>$page_title_lang,
            'richtext'=>$richtext,
            'content_name'=>$content_name,
            'admin_type_hide'=>array()
        );
    }
    return array('version'=>1,'items'=>$items);
}

function html__menu_load_config($area='public') {
    // load, normalize, and cache runtime menu_config for requested area
    // return cached runtime config if already loaded during this request
    if (isset($GLOBALS['menu_config_runtime_cache']) && is_array($GLOBALS['menu_config_runtime_cache']) &&
        isset($GLOBALS['menu_config_runtime_cache'][$area]) && is_array($GLOBALS['menu_config_runtime_cache'][$area])) {
        return $GLOBALS['menu_config_runtime_cache'][$area];
    }

    // load raw menu config (fallback to defaults if missing/invalid)
    $config=options__load_json_object('menu_config',$area,array());
    $changed=false;
    if (!is_array($config) || !isset($config['items']) || !is_array($config['items'])) {
        $config=html__menu_defaults_to_config($area);
        $changed=true;
    }
    // load defaults keyed by id for normalization and fixed-item enforcement
    $defaults=html__menu_defaults_to_config($area,false);
    $default_items=(isset($defaults['items']) && is_array($defaults['items']) ? $defaults['items'] : array());
    $schema_defaults=array(
        'menu_area'=>'',
        'entrytype'=>'link',
        'link'=>'',
        'custom_external'=>'n',
        'icon'=>'',
        'show_if_not_logged_in'=>1,
        'show_if_logged_in'=>1,
        'hidden'=>'n',
        'fixed'=>'n',
        'removable'=>'y',
        'can_be_secondary'=>'y',
        'can_hide'=>'y',
        'menu_term_lang'=>array(),
        'page_title_lang'=>array(),
        'richtext'=>'n',
        'content_name'=>'',
        'admin_type_hide'=>array()
    );
    $fixed_immutable_fields=array(
        'menu_area',
        'entrytype',
        'link',
        'custom_external',
        'show_if_not_logged_in',
        'show_if_logged_in',
        'hidden',
        'fixed',
        'removable',
        'can_be_secondary',
        'can_hide',
        'richtext',
        'content_name'
    );
    $existing_ids=array();
    $next_fallback_id=1;
    $items_by_id=array();

    // normalize items and rebuild runtime structure keyed by menu item id
    foreach ($config['items'] as $item) {
        if (!is_array($item)) {
            $changed=true;
            continue;
        }
        $id=(isset($item['id']) ? (string)$item['id'] : '');
        if (substr($id,0,6)==='__new_') {
            $changed=true;
            continue;
        }
        if ($id==='' || isset($existing_ids[$id])) {
            do {
                $id='item_'.$next_fallback_id;
                $next_fallback_id++;
            } while (isset($existing_ids[$id]));
            $item['id']=$id;
            $changed=true;
        }
        $existing_ids[$id]=true;
        $item_before_merge=$item;
        $item=array_merge($schema_defaults,$item);
        if ($item!==$item_before_merge) {
            $changed=true;
        }
        if (!isset($item_before_merge['menu_area']) || trim((string)$item_before_merge['menu_area'])==='') {
            $item['menu_area']=$id;
            $changed=true;
        }
        if (!is_array($item['menu_term_lang'])) {
            $item['menu_term_lang']=array();
            $changed=true;
        }
        if (!is_array($item['page_title_lang'])) {
            $item['page_title_lang']=array();
            $changed=true;
        }
        if (!is_array($item['admin_type_hide'])) {
            $item['admin_type_hide']=array();
            $changed=true;
        }

        if (isset($default_items[$id]) && is_array($default_items[$id])) {
            $default_item=$default_items[$id];
            if (isset($default_item['fixed']) && $default_item['fixed']==='y') {
                foreach ($fixed_immutable_fields as $fkey) {
                    if (isset($default_item[$fkey]) && $item[$fkey]!==$default_item[$fkey]) {
                        $item[$fkey]=$default_item[$fkey];
                        $changed=true;
                    }
                }
            }
        }

        $items_by_id[$id]=$item;
    }

    // ensure all fixed default items exist in the loaded config
    foreach ($default_items as $id=>$default_item) {
        if (!isset($default_item['fixed']) || $default_item['fixed']!=='y') {
            continue;
        }
        if (!isset($existing_ids[$id])) {
            $items_by_id[$id]=$default_item;
            $existing_ids[$id]=true;
            $changed=true;
        }
    }

    // build quick lookup map: public content_name -> menu item id
    $config['items']=$items_by_id;
    $config['public_content_to_id']=array();
    foreach ($config['items'] as $id=>$item) {
        if (!is_array($item)) {
            continue;
        }
        if (!(isset($item['richtext']) && $item['richtext']==='y')) {
            continue;
        }
        if (!isset($item['content_name'])) {
            continue;
        }
        $content_name=trim((string)$item['content_name']);
        if ($content_name==='') {
            continue;
        }
        $config['public_content_to_id'][$content_name]=(string)$id;
    }

    // persist normalized config if it was repaired and cache runtime result
    if ($changed) {
        html__menu_save_config($area,$config);
    }
    if (!isset($GLOBALS['menu_config_runtime_cache']) || !is_array($GLOBALS['menu_config_runtime_cache'])) {
        $GLOBALS['menu_config_runtime_cache']=array();
    }
    $GLOBALS['menu_config_runtime_cache'][$area]=$config;
    return $config;
}

function html__menu_save_config($area='public',$config=array()) {
    // persist menu_config to or_objects in DB-safe shape (list items, no runtime maps)
    if (!is_array($config)) {
        return false;
    }
    if (!isset($config['items']) || !is_array($config['items'])) {
        $config['items']=array();
    }
    $items_to_save=array();
    foreach ($config['items'] as $item) {
        if (!is_array($item)) {
            continue;
        }
        $items_to_save[]=$item;
    }
    $config['items']=$items_to_save;
    if (isset($config['public_content_to_id'])) {
        unset($config['public_content_to_id']);
    }
    return options__save_json_object('menu_config',$area,$config,1,-1);
}

function html__menu_item_visible($item,$logged_in,$area='public') {
    // evaluate menu item visibility for current context (auth state, module flags, admin type)
    global $settings, $expadmindata;
    if (!is_array($item)) {
        return false;
    }
    if (isset($item['hidden']) && $item['hidden']==='y') {
        return false;
    }
    if ($logged_in) {
        if (isset($item['show_if_logged_in']) && !(int)$item['show_if_logged_in']) {
            return false;
        }
    } else {
        if (isset($item['show_if_not_logged_in']) && !(int)$item['show_if_not_logged_in']) {
            return false;
        }
    }
    $menu_area=(isset($item['menu_area']) ? (string)$item['menu_area'] : '');
    if ($menu_area==='emails' && (!isset($settings['enable_email_module']) || $settings['enable_email_module']!=='y')) {
        return false;
    }
    if ($area==='public' && $settings['subject_authentication']==='token') {
        if (in_array($menu_area,array('login','logout'),true)) {
            return false;
        }
    }
    if ($area==='admin') {
        $menu_area=(isset($item['menu_area']) ? (string)$item['menu_area'] : '');
        if ($menu_area!=='admin_mainpage') {
            if (isset($item['admin_type_hide']) && is_array($item['admin_type_hide']) && isset($expadmindata['admin_type']) && in_array($expadmindata['admin_type'],$item['admin_type_hide'],true)) {
                return false;
            }
        }
    }
    return true;
}

function html__menu_build_runtime_items($area='public',$logged_in=false) {
    // build visible runtime menu entries with resolved labels/links/icons
    global $settings__root_url;
    $config=html__menu_load_config($area);
    $runtime=array();
    foreach ($config['items'] as $item) {
        if (!is_array($item)) {
            continue;
        }
        if (!html__menu_item_visible($item,$logged_in,$area)) {
            continue;
        }

        $entrytype=(isset($item['entrytype']) ? (string)$item['entrytype'] : 'link');
        $menu_label=html__menu_text_from_lang_map((isset($item['menu_term_lang']) ? $item['menu_term_lang'] : array()),'');
        $link=(isset($item['link']) ? (string)$item['link'] : '');
        $menu_area=(isset($item['menu_area']) ? (string)$item['menu_area'] : '');
        $content_name=(isset($item['content_name']) ? trim((string)$item['content_name']) : '');
        if (isset($item['richtext']) && $item['richtext']==='y' && $content_name!=='') {
            if ($area==='public' && $content_name==='mainpage_welcome') {
                $link='/public/';
            } elseif ($area==='admin' && $content_name==='admin_mainpage') {
                $link='/admin/';
            } elseif ($area==='public') {
                $link='/public/index.php?page='.urlencode($content_name);
            } else {
                $link='/admin/index.php?page='.urlencode($content_name);
            }
        }

        $runtime[]=array(
            'menu_area'=>$menu_area,
            'entrytype'=>$entrytype,
            'lang_item'=>'',
            'menu_label'=>$menu_label,
            'link'=>$link,
            'custom_external'=>(isset($item['custom_external']) && $item['custom_external']==='y' ? 'y' : 'n'),
            'icon'=>(isset($item['icon']) ? (string)$item['icon'] : ''),
            'show_if_not_logged_in'=>1,
            'show_if_logged_in'=>1
        );
    }
    return $runtime;
}

function html__menu_can_access_item($area='public',$item=false,$logged_in=false) {
    // access gate wrapper mirroring visibility rules
    if (!is_array($item)) {
        return false;
    }
    return html__menu_item_visible($item,$logged_in,$area);
}

function html__get_admin_menu() {
    // return admin runtime menu for logged-in admin context
    return html__menu_build_runtime_items('admin',true);
}

function html__get_public_menu() {
    // return public runtime menu based on current participant login state
    $logged_in=false;
    if (isset($_SESSION['pauthdata']['user_logged_in']) && $_SESSION['pauthdata']['user_logged_in']) {
        $logged_in=true;
    }
    if (isset($GLOBALS['show_logged_in_menu']) && $GLOBALS['show_logged_in_menu']) {
        $logged_in=true;
    }
    return html__menu_build_runtime_items('public',$logged_in);
}

function html__build_menu($menu,$logged_in,$orientation="vertical",$menu_mode='vertical',$menu_extra_html='') {
    // render normalized runtime menu into vertical or horizontal HTML navigation
    global $settings__root_url, $menu__area, $settings;

    // preserve participant token in internal links for token/migration auth flows
    $addp="";
    $ignore_p=array('participant_create.php','participant_confirm.php','participant_forgot.php');
    if (in_array($settings['subject_authentication'],array('token','migration'))) {
        if (isset($_REQUEST['p']) && !(in_array(thisdoc(),$ignore_p))) {
            $addp="?p=".urlencode($_REQUEST['p']);
        }
    }

    // normalize menu entries into generic section/item model before rendering
    $final_menu=array();
    $current_section_index=-1;
    $submenu_break_pending=false;
    foreach ($menu as $item) {
        $continue=true;
        $entrytype=(isset($item['entrytype']) ? $item['entrytype'] : 'head');
        $menu_area=(isset($item['menu_area']) ? $item['menu_area'] : '');

        if ($entrytype=='space') {
            $submenu_break_pending=true;
            continue;
        }

        if ($continue && $entrytype!='space') {
            $continue=false;
            if (isset($item['show_if_not_logged_in']) && $item['show_if_not_logged_in'] && !$logged_in) {
                $continue=true;
            }
            if (isset($item['show_if_logged_in']) && $item['show_if_logged_in'] && $logged_in) {
                $continue=true;
            }
        }
        if (!$continue) {
            continue;
        }

        $link='';
        if (isset($item['link']) && $item['link']) {
            if (substr($item['link'],0,1)=='/') {
                $link=$settings__root_url.$item['link'].$addp;
            } else {
                $link=$item['link'];
            }
        }
        $external_target_attr='';
        if (isset($item['custom_external']) && $item['custom_external']==='y' && $link!=='') {
            $external_target_attr=' target="_blank" rel="noopener noreferrer"';
        }

        $label='';
        if (isset($item['menu_label']) && trim((string)$item['menu_label'])!=='') {
            $label=(string)$item['menu_label'];
        } elseif (isset($item['lang_item']) && $item['lang_item']) {
            $label=lang($item['lang_item']);
        }

        $icon_html='';
        if (isset($item['icon']) && $item['icon']) {
            $icon_html=micon($item['icon']);
        }

        $classes=array();
        if ($menu_area && isset($menu__area) && strcasecmp((string)$menu_area,(string)$menu__area)===0) {
            $classes[]='is-active';
        }
        if ($menu_area=='logout') {
            $classes[]='is-logout';
        }
        $class_attr=(count($classes)>0 ? ' '.implode(' ',$classes) : '');

        if ($menu_area=='current_user_data_box') {
            continue;
        }

        if ($entrytype=='head') {
            $submenu_break_pending=false;
            $current_section_index++;
            $final_menu[]=array(
                'type'=>'section',
                'tag'=>'div',
                'section_index'=>$current_section_index,
                'is_active'=>in_array('is-active',$classes),
                'class'=>$class_attr,
                'content'=>$icon_html.$label
            );
        } elseif ($entrytype=='headlink') {
            $submenu_break_pending=false;
            $current_section_index++;
            $final_menu[]=array(
                'type'=>'section',
                'tag'=>'a',
                'href'=>$link,
                'extra_attr'=>$external_target_attr,
                'section_index'=>$current_section_index,
                'is_active'=>in_array('is-active',$classes),
                'class'=>$class_attr,
                'content'=>$icon_html.$label
            );
        } else {
            if ($orientation=='horizontal' && ($current_section_index<0 || $submenu_break_pending)) {
                $current_section_index++;
                $orphan_section_content='';
                if ($menu_mode=='horizontal_dynamic_submenu') {
                    $orphan_section_content='<i class="fa fa-ellipsis-h" aria-hidden="true"></i>';
                }
                $final_menu[]=array(
                    'type'=>'section',
                    'tag'=>'div',
                    'section_index'=>$current_section_index,
                    'is_active'=>false,
                    'class'=>'',
                    'content'=>$orphan_section_content
                );
                $submenu_break_pending=false;
            }
            $final_menu[]=array(
                'type'=>'item',
                'tag'=>'a',
                'href'=>($link ? $link : '#'),
                'extra_attr'=>$external_target_attr,
                'section_index'=>$current_section_index,
                'is_active'=>in_array('is-active',$classes),
                'class'=>$class_attr,
                'content'=>$icon_html.$label
            );
        }
    }

    // render vertical navigation directly
    if ($orientation!='horizontal') {
        $list='<div class="orsee-nav">';
        foreach ($final_menu as $item) {
            $base_class=($item['type']=='section' ? 'orsee-nav__section' : 'orsee-nav__item');
            $classes=$base_class.(isset($item['class']) ? $item['class'] : '');
            if ($item['tag']=='a') {
                $href=(isset($item['href']) && $item['href'] ? $item['href'] : '#');
                $extra_attr=(isset($item['extra_attr']) ? (string)$item['extra_attr'] : '');
                $list.='<a class="'.$classes.'" href="'.$href.'"'.$extra_attr.'>'.$item['content'].'</a>';
            } else {
                $list.='<div class="'.$classes.'">'.$item['content'].'</div>';
            }
        }
        if ($menu_extra_html) {
            $list.=$menu_extra_html;
        }
        $list.='</div>';
        return $list;
    }

    // split horizontal navigation into sections with grouped sub-items
    $sections=array();
    $items_by_section=array();
    $active_section_index=-1;
    foreach ($final_menu as $item) {
        if ($item['type']=='section') {
            $idx=(isset($item['section_index']) ? (int)$item['section_index'] : -1);
            if ($idx>=0) {
                $sections[$idx]=$item;
            }
            if (isset($item['is_active']) && $item['is_active']) {
                $active_section_index=$idx;
            }
        } else {
            $idx=(isset($item['section_index']) ? (int)$item['section_index'] : -1);
            if (!isset($items_by_section[$idx])) {
                $items_by_section[$idx]=array();
            }
            $items_by_section[$idx][]=$item;
            if (isset($item['is_active']) && $item['is_active']) {
                $active_section_index=$idx;
            }
        }
    }
    ksort($sections);
    if ($active_section_index<0 && count($sections)>0) {
        $keys=array_keys($sections);
        $active_section_index=(int)$keys[0];
    }
    // render horizontal static submenu variant (all subitems visible in columns)
    if ($menu_mode=='horizontal_static_submenu') {
        $list='<div class="orsee-nav-row orsee-nav-row-static">';
        $list.='<div class="orsee-nav-stack orsee-nav-stack-static">';
        $list.='<div class="orsee-nav-static">';
        foreach ($sections as $idx=>$item) {
            $sec_id='s'.(string)$idx;
            $col_class='orsee-nav-static-col';
            if ($idx==$active_section_index) {
                $col_class.=' is-active';
            }
            $list.='<div class="'.$col_class.'" data-orsee-sub="'.$sec_id.'">';
            $tab_class='orsee-nav__section';
            if ($idx==$active_section_index) {
                $tab_class.=' is-active';
            }
            if (isset($item['class']) && $item['class']) {
                $tab_class.=$item['class'];
            }
            $section_content=(isset($item['content']) ? trim(strip_tags((string)$item['content'])) : '');
            if ($section_content!=='') {
                if ($item['tag']=='a') {
                    $href=(isset($item['href']) && $item['href'] ? $item['href'] : '#');
                    $extra_attr=(isset($item['extra_attr']) ? (string)$item['extra_attr'] : '');
                    $list.='<a class="'.$tab_class.'" href="'.$href.'"'.$extra_attr.'>'.$item['content'].'</a>';
                } else {
                    $list.='<div class="'.$tab_class.'">'.$item['content'].'</div>';
                }
            } else {
                $list.='<div class="'.$tab_class.'" style="visibility:hidden;">&nbsp;</div>';
            }
            $list.='<div class="orsee-nav-static-subs">';
            $sub_items=(isset($items_by_section[$idx]) ? $items_by_section[$idx] : array());
            foreach ($sub_items as $sub_item) {
                $item_class='orsee-nav__item';
                if (isset($sub_item['class']) && $sub_item['class']) {
                    $item_class.=$sub_item['class'];
                }
                if ($sub_item['tag']=='a') {
                    $href=(isset($sub_item['href']) && $sub_item['href'] ? $sub_item['href'] : '#');
                    $extra_attr=(isset($sub_item['extra_attr']) ? (string)$sub_item['extra_attr'] : '');
                    $list.='<a class="'.$item_class.'" href="'.$href.'"'.$extra_attr.'>'.$sub_item['content'].'</a>';
                } else {
                    $list.='<div class="'.$item_class.'">'.$sub_item['content'].'</div>';
                }
            }
            $list.='</div>';
            $list.='</div>';
        }
        $list.='</div>';
        $list.='</div>';
        if ($menu_extra_html) {
            $list.='<div class="orsee-nav-userbox">'.$menu_extra_html.'</div>';
        }
        $list.='</div>';
        return $list;
    }
    // render horizontal dynamic submenu variant (hover/focus switches active subgroup)
    $list='<div class="orsee-nav-row">';
    $list.='<div class="orsee-nav-stack">';
    $list.='<div class="orsee-nav orsee-nav-tabs">';
    foreach ($sections as $idx=>$item) {
        $tab_class='orsee-nav__section';
        if ($idx==$active_section_index) {
            $tab_class.=' is-active';
        }
        if (isset($item['class']) && $item['class']) {
            $tab_class.=$item['class'];
        }
        $sec_id='s'.(string)$idx;
        if ($item['tag']=='a') {
            $href=(isset($item['href']) && $item['href'] ? $item['href'] : '#');
            $extra_attr=(isset($item['extra_attr']) ? (string)$item['extra_attr'] : '');
            $list.='<a class="'.$tab_class.'" data-orsee-sec="'.$sec_id.'" href="'.$href.'"'.$extra_attr.'>'.$item['content'].'</a>';
        } else {
            $list.='<div class="'.$tab_class.'" data-orsee-sec="'.$sec_id.'">'.$item['content'].'</div>';
        }
    }
    $list.='</div>';
    $list.='<div class="orsee-nav orsee-nav-subs">';
    foreach ($sections as $idx=>$section_item) {
        $sec_id='s'.(string)$idx;
        $group_class='orsee-nav-subgroup';
        if ($idx==$active_section_index) {
            $group_class.=' is-active';
        }
        $list.='<div class="'.$group_class.'" data-orsee-sub="'.$sec_id.'">';
        $sub_items=(isset($items_by_section[$idx]) ? $items_by_section[$idx] : array());
        foreach ($sub_items as $item) {
            $item_class='orsee-nav__item';
            if (isset($item['class']) && $item['class']) {
                $item_class.=$item['class'];
            }
            if ($item['tag']=='a') {
                $href=(isset($item['href']) && $item['href'] ? $item['href'] : '#');
                $extra_attr=(isset($item['extra_attr']) ? (string)$item['extra_attr'] : '');
                $list.='<a class="'.$item_class.'" href="'.$href.'"'.$extra_attr.'>'.$item['content'].'</a>';
            } else {
                $list.='<div class="'.$item_class.'">'.$item['content'].'</div>';
            }
        }
        $list.='</div>';
    }
    $list.='</div></div>';
    if ($menu_extra_html) {
        $list.='<div class="orsee-nav-userbox">'.$menu_extra_html.'</div>';
    }
    $list.='</div>';
    $list.='<script>
document.addEventListener("DOMContentLoaded", function () {
  var rows = document.querySelectorAll(".orsee[data-menu-mode=\"horizontal_dynamic_submenu\"] .orsee-menu-horizontal .orsee-nav-row");
  rows.forEach(function (row) {
    var tabs = row.querySelectorAll(".orsee-nav-tabs [data-orsee-sec]");
    var tabsContainer = row.querySelector(".orsee-nav-tabs");
    var subsContainer = row.querySelector(".orsee-nav-subs");
    var groups = row.querySelectorAll(".orsee-nav-subs [data-orsee-sub]");
    if (!tabs.length || !groups.length || !tabsContainer || !subsContainer) return;
    var setActive = function (sec) {
      var activeTab = null;
      var activeGroup = null;
      tabs.forEach(function (tab) {
        if (tab.getAttribute("data-orsee-sec") === sec) {
          tab.classList.add("is-active");
          activeTab = tab;
        } else {
          tab.classList.remove("is-active");
        }
      });
      groups.forEach(function (grp) {
        if (grp.getAttribute("data-orsee-sub") === sec) {
          grp.classList.add("is-active");
          activeGroup = grp;
        } else grp.classList.remove("is-active");
      });
      if (activeTab) {
        var tabsRect = tabsContainer.getBoundingClientRect();
        var tabRect = activeTab.getBoundingClientRect();
        var isRTL = (getComputedStyle(tabsContainer).direction === "rtl");
        var offset = isRTL ? Math.max(0, tabsRect.right - tabRect.right) : Math.max(0, tabRect.left - tabsRect.left);
        subsContainer.style.setProperty("--orsee-sub-offset", offset + "px");
        subsContainer.style.setProperty("--orsee-sub-width", tabRect.width + "px");
      }
      var hasSubs = !!(activeGroup && activeGroup.children && activeGroup.children.length > 0);
      if (hasSubs) {
        var gRect = activeGroup.getBoundingClientRect();
        subsContainer.style.setProperty("--orsee-sub-items-width", Math.max(0, gRect.width) + "px");
        subsContainer.classList.add("has-subs");
      } else {
        subsContainer.style.setProperty("--orsee-sub-items-width", "0px");
        subsContainer.classList.remove("has-subs");
      }
    };
    var initial = null;
    tabs.forEach(function (tab) {
      if (!initial && tab.classList.contains("is-active")) initial = tab.getAttribute("data-orsee-sec");
    });
    if (!initial) initial = tabs[0].getAttribute("data-orsee-sec");
    setActive(initial);
    tabs.forEach(function (tab) {
      tab.addEventListener("mouseenter", function () { setActive(tab.getAttribute("data-orsee-sec")); });
      tab.addEventListener("focus", function () { setActive(tab.getAttribute("data-orsee-sec")); });
      tab.addEventListener("click", function (ev) {
        var sec = tab.getAttribute("data-orsee-sec");
        setActive(sec);
        var href = tab.getAttribute("href");
        if (!href || href === "#") ev.preventDefault();
      });
    });
  });
});
</script>';
    return $list;
}

function get_style_array() {
    // return available style directory names under /style
    global $settings__root_directory, $settings__root_to_server;

    // $path=$settings__root_to_server.$settings__root_directory."/style";
    $path=__DIR__."/../style";

    $dir_arr = array() ;
    $handle=opendir($path);
    while ($file = readdir($handle)) {
        if ($file != "." && $file != ".." && is_dir($path."/".$file)) {
            $dir_arr[] = $file ;
        }
    }
    return $dir_arr ;
}

function button_link($link,$text,$icon="",$button_style="",$aextra="",$class_extra="") {
    // build generic action link in button style
    $out='<A HREF="'.$link.'" class="button orsee-btn';
    if ($class_extra) {
        $out.=' '.$class_extra;
    }
    $out.='"';
    if ($button_style) {
        $out.=' style="';
        if ($button_style) {
            $out.=$button_style;
        }
        $out.='"';
    }
    if ($aextra) {
        $out.=' '.$aextra;
    }
    $out.='>';
    if ($icon) {
        $out.='<i class="fa fa-'.$icon.'" style="padding: 0 0.3em 0 0"></i>';
    }
    $out.=$text.'</A>';
    return $out;
}

function button_back($link,$text='',$button_style='',$aextra='',$class_extra='') {
    // build back action link with RTL-aware arrow icon
    if ($text==='') {
        $text=lang('back');
    }
    return button_link($link,$text,(lang__is_rtl() ? 'arrow-right' : 'arrow-left'),$button_style,$aextra,$class_extra);
}

function button_link_delete($link,$text) {
    // build delete-styled action link button
    return button_link($link,$text,'trash-o','','','orsee-btn--delete');
}

function button_submit_delete($name,$text) {
    // build delete-styled submit button
    return '<button type="submit" name="'.$name.'" value="y" class="button orsee-btn orsee-btn--delete"><i class="fa fa-trash-o"></i> '.$text.'</button>';
}

?>
