<?php
// part of orsee. see orsee.org

function stripTagsRequestArray($var,$exempt=array()) {
    if (is_array($var)) {
        foreach($var as $k=>$v) {
            if (!in_array($k, $exempt)) {
                $var[$k] = stripTagsRequestArray($v);
            }
        }
    } else {
        $var = strip_tags($var);
        $var=str_replace(array('&','<','>','"',"'",'/'),
            array('&amp;','&lt;','&gt;','&quot;','&#x27;','&#x2F;'),
            strip_tags($var));
    }
    return $var;
}

function getRefererFileName()
{
    $ar = pathinfo($_SERVER['HTTP_REFERER']);
    return $ar['filename'];
}

// Added Csrf security security
if(!function_exists('hash_equals')) {
    function hash_equals($str1, $str2) {
        if(strlen($str1) != strlen($str2)) {
            return false;
        } else {
            $res = $str1 ^ $str2;
            $ret = 0;
            for($i = strlen($res) - 1; $i >= 0; $i--) $ret |= ord($res[$i]);
            return !$ret;
        }
    }
}

// End of HdR additions

// messages
function message($new_message,$style='note',$title=null,$form='callout') {
    if (!isset($_SESSION['message_queue']) || !is_array($_SESSION['message_queue'])) {
        $_SESSION['message_queue']=array();
    }
    $_SESSION['message_queue'][]=array(
        'text'=>(string)$new_message,
        'style'=>(string)$style,
        'title'=>$title,
        'form'=>(string)$form
    );
}

function orsee_callout($text,$style='note',$title=null,$extra_class='') {
    $style=(string)$style;
    if ($style==='') {
        $style='note';
    }

    $style_class='orsee-callout-notice';
    if ($style==='warning') {
        $style_class='orsee-callout-warning';
    } elseif ($style==='error') {
        $style_class='orsee-callout-error';
    }

    if ($title===null) {
        $title_key='message_label_'.$style;
        $default_title=lang($title_key);
        if ($default_title===$title_key) {
            $default_title=lang('message_label_note');
        }
        $title_text=(string)$default_title;
    } else {
        $title_text=(string)$title;
    }

    $extra_class=trim((string)$extra_class);
    $class_attr='orsee-callout '.$style_class.' orsee-message-box';
    if ($extra_class!=='') {
        $class_attr.=' '.$extra_class;
    }

    echo '<div class="'.$class_attr.'">';
    if ($title_text!=='') {
        echo '<div class="orsee-message-box-title"><b>'.htmlspecialchars($title_text).':</b></div>';
    }
    echo '<div class="orsee-message-box-body">'.$text.'</div>';
    echo '</div>';
}

function show_message($text=null,$style='note',$title=null,$form='callout') {
    if ($text!==null && $text!=='') {
        message($text,$style,$title,$form);
    }

    $queue=array();

    if (isset($_SESSION['message_queue']) && is_array($_SESSION['message_queue'])) {
        $queue=$_SESSION['message_queue'];
    }

    // Legacy fallback in case older code/session still uses message_text.
    if (isset($_SESSION['message_text']) && is_string($_SESSION['message_text']) && $_SESSION['message_text']!=="") {
        $queue[]=array(
            'text'=>$_SESSION['message_text'],
            'style'=>'note',
            'title'=>null,
            'form'=>'callout'
        );
    }

    if (count($queue)>0) {
        $grouped=array();
        foreach ($queue as $item) {
            $item_text=(isset($item['text']) ? (string)$item['text'] : '');
            if ($item_text==='') {
                continue;
            }
            $item_style=(isset($item['style']) ? (string)$item['style'] : 'note');
            if ($item_style==='') {
                $item_style='note';
            }
            $item_form=(isset($item['form']) ? (string)$item['form'] : 'callout');
            if ($item_form==='') {
                $item_form='callout';
            }
            $item_title=(array_key_exists('title',$item) ? $item['title'] : null);
            $title_key=($item_title===null ? '__NULL__' : (string)$item_title);
            $group_key=$item_form.'|'.$item_style.'|'.$title_key;
            if (!isset($grouped[$group_key])) {
                $grouped[$group_key]=array(
                    'form'=>$item_form,
                    'style'=>$item_style,
                    'title'=>$item_title,
                    'texts'=>array()
                );
            }
            $grouped[$group_key]['texts'][]=$item_text;
        }

        foreach ($grouped as $group) {
            $merged_text=implode('<BR>',$group['texts']);
            if ($group['form']==='toast') {
                orsee_callout($merged_text,$group['style'],$group['title'],'orsee-message-toast');
            } else {
                orsee_callout($merged_text,$group['style'],$group['title']);
            }
        }
    }

    $_SESSION['message_queue']=array();
    $_SESSION['message_text']="";
}

// URL redirecting
function redirect($url) {
    global $settings__root_url, $proceed;
    $proceed=false;
    if (preg_match("/^(http:\/\/|https:\/\/)/i",$url)) {
        header("Location: ".trim($url));
    } else {
        $newurl=trim($settings__root_url."/".$url);
        header("Location: ".$newurl);
    }
    if (ob_get_level() != 0) {
        ob_end_flush();
    }
    session_write_close();
    exit(1);
}

function thisdoc() {
    if (isset($_SERVER['SCRIPT_NAME'])) {
        return basename($_SERVER['SCRIPT_NAME']);
    } else {
        return '';
    }
}


// Icons
function lang__parse_intltelinput_flag_offsets() {
    static $offsets=null;
    if (is_array($offsets)) {
        return $offsets;
    }

    $offsets=array();
    $css_file='../tagsets/js/intlTelInput/intlTelInput.css';
    if (!file_exists($css_file)) {
        return $offsets;
    }

    $css=file_get_contents($css_file);
    if (!is_string($css) || $css==='') {
        return $offsets;
    }

    if (preg_match_all('/\.iti__([a-z]{2})\s*\{\s*--iti-flag-offset:\s*(-?\d+px)\s*;\s*\}/',$css,$matches,PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $offsets[strtolower($match[1])]=$match[2];
        }
    }

    return $offsets;
}

function lang__guess_flag_for_language($language) {
    $language=strtolower(trim((string)$language));
    $offsets=lang__parse_intltelinput_flag_offsets();

    $fallback_iso2=$language;
    $fallback_map=array(
        'ar'=>'sa',
        'el'=>'gr',
        'en'=>'gb',
        'fa'=>'ir',
        'he'=>'il',
        'ja'=>'jp',
        'ko'=>'kr',
        'sv'=>'se',
        'uk'=>'ua',
        'zh'=>'cn'
    );
    if (isset($fallback_map[$language])) {
        $fallback_iso2=$fallback_map[$language];
    }
    if ($fallback_iso2!=='' && isset($offsets[$fallback_iso2])) {
        return $fallback_iso2;
    }
    return '';
}

function lang_icons_prepare() {
    $langarray=get_languages();
    $offsets=lang__parse_intltelinput_flag_offsets();
    $explicit_iso2=array();
    $legacy_base64=array();

    $query="SELECT * FROM ".table('lang')."
            WHERE content_type='lang'
            AND content_name IN ('lang_flag_iso2','lang_icon_base64')";
    $result=or_query($query);
    while ($line=pdo_fetch_assoc($result)) {
        foreach ($langarray as $tlang) {
            if (!isset($line[$tlang])) {
                continue;
            }
            $value=trim((string)$line[$tlang]);
            if ($line['content_name']==='lang_flag_iso2') {
                if ($value!=='') {
                    $explicit_iso2[$tlang]=strtolower($value);
                }
            } elseif ($line['content_name']==='lang_icon_base64') {
                if ($value!=='') {
                    $legacy_base64[$tlang]=$value;
                }
            }
        }
    }

    foreach ($langarray as $tlang) {
        $iso2='';
        if (isset($explicit_iso2[$tlang])) {
            if ($explicit_iso2[$tlang]==='none') {
                continue;
            }
            if (isset($offsets[$explicit_iso2[$tlang]])) {
                $iso2=$explicit_iso2[$tlang];
            }
        }
        if ($iso2==='') {
            $iso2=lang__guess_flag_for_language($tlang);
        }

        if ($iso2!=='' && isset($offsets[$iso2])) {
            echo '.langicon-'.$tlang.':before {
                content:"";
                background-image:url("../tagsets/js/intlTelInput/flags.webp");
                background-repeat:no-repeat;
                background-position:'.$offsets[$iso2].' 0;
                background-size:3904px 12px;
                }
            ';
        } elseif (isset($legacy_base64[$tlang])) {
            echo '.langicon-'.$tlang.':before {
                content:url(\''.$legacy_base64[$tlang].'\');
                }
            ';
        }
    }
}

function oicon($icon) {
    // displays icon on options page
    return '<i class="fa fa-'.$icon.' fa-fw optionsicon"></i>';
}

function micon($icon,$link="") {
    global $settings;
    $out='';
    if ($link) {
        $out.='<A HREF="'.$link.'">';
    }
    $out.='<i class="fa fa-'.$icon.' fa-fw menuicon"></i>';
    if ($link) {
        $out.='</A>';
    }
    return $out;
}

function icon($icon,$link="",$classes="",$style="",$title="") {
    global $settings;
    // for backward comp
    if ($icon=='back') {
        $icon='level-up';
    }
    $out='';
    if ($link) {
        $out.='<A HREF="'.$link.'"';
        if ($title) {
            $out.=' title="'.$title.'"';
        }
        $out.='>';
    }
    $out.='<i class="fa fa-'.$icon;
    if ($classes) {
        $out.=' '.$classes;
    }
    $out.='"';
    if ($style) {
        $out.=' style="'.$style.'"';
    }
    $out.='></i>';
    if ($link) {
        $out.='</A>';
    }
    return $out;
}

// security-related functions

function csrf__get_token() {
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || !$_SESSION['csrf_token']) {
        if (function_exists('random_bytes')) {
            try {
                $_SESSION['csrf_token']=bin2hex(random_bytes(32));
            } catch (Exception $e) {
                $_SESSION['csrf_token']=create_random_token(session_id());
            }
        } else {
            $_SESSION['csrf_token']=create_random_token(session_id());
        }
    }
    return $_SESSION['csrf_token'];
}

function csrf__field($name='csrf_token') {
    $token=htmlspecialchars(csrf__get_token(),ENT_QUOTES,'UTF-8');
    return '<input type="hidden" name="'.$name.'" value="'.$token.'">';
}

function csrf__validate_request($name='csrf_token') {
    $submitted=null;
    if (isset($_POST[$name])) {
        $submitted=$_POST[$name];
    } elseif (isset($_GET[$name])) {
        $submitted=$_GET[$name];
    } elseif (isset($_REQUEST[$name])) {
        $submitted=$_REQUEST[$name];
    }
    if (!is_string($submitted) || $submitted==='') {
        return false;
    }
    return hash_equals(csrf__get_token(),$submitted);
}

function csrf__validate_request_message($name='csrf_token',$message_key='error_csrf_token') {
    $valid=csrf__validate_request($name);
    if (!$valid) {
        if (function_exists('lang')) {
            message(lang($message_key),'error');
        } else {
            message('Error: not authorized to access this function','error');
        }
    }
    return $valid;
}

// authenticate with token
function site__check_token() {
    $continue=true;
    // fix the uuencode malformed url issue
    /*
    if ((!isset($_REQUEST['p'])) || (!$_REQUEST['p'])) {
        foreach ($_REQUEST as $key=>$value) {
            if (substr($key,0,1)=='p') $_REQUEST['p']='cd'.substr($key,strlen($key)-11);
        }
    }*/
    if ((!isset($_REQUEST['p'])) || (!trim($_REQUEST['p']))) {
        $continue=false;
    }
    if ($continue) {
        $participant_id=url_cr_decode(trim($_REQUEST['p']));
        if (!$participant_id) {
            $continue=false;
        }
    }
    if ($continue) {
        return $participant_id;
    } else {
        return false;
    }
}

// decode participant token into participant id
function url_cr_decode($value) {
    $pars=array(':crypted_id'=>$value);
    $query="SELECT participant_id FROM ".table('participants')."
            WHERE participant_id_crypt= :crypted_id";
    $decarray=orsee_query($query,$pars);
    if (is_array($decarray) && isset($decarray['participant_id'])) {
        $decoded=$decarray['participant_id'];
        return $decoded;
    } else {
        return false;
    }
}

// password encryption
function unix_crypt($value) {
    return password_hash($value,PASSWORD_DEFAULT);
}

// password verification
function crypt_verify($submitted,$hash) {
    return password_verify($submitted,$hash);
}

// generate participant token
function make_p_token($entropy="") {
    global $settings;
    if (isset($settings['participant_token_length']) && round($settings['participant_token_length'])>=10) {
        $token_length=round($settings['participant_token_length']);
    } else {
        $token_length=15;
    }
    $t=or_get_token($token_length,$entropy);
    return $t;
}

// generate other token
function create_random_token($entropy="") {
    global $settings;
    $token_length=20;
    $t=or_get_token($token_length,$entropy);
    return $t;
}

function get_entropy($array) {
    $entropy='';
    if (is_array($array)) {
        foreach ($array as $v) {
            if (!is_array($v)) {
                $entropy.=$v;
            }
        }
    } else {
        $entropy=$array;
    }
    return $entropy;
}

function or_get_token($length,$entropy="") {
    $rnd=$entropy.mt_rand();
    if (function_exists('openssl_random_pseudo_bytes') && strpos(php_uname('s'), 'Windows') === false) {
        $rnd .= hexdec(bin2hex(openssl_random_pseudo_bytes($length)));
    }
    if (function_exists('hash')) {
        $hash=hash('sha256',$rnd);
    } else {
        $hash=sha1($rnd);
    }
    return substr(base64_encode($hash),0,$length);
}

// generate random number
function crypto_rand_secure($min, $max) {
    if (function_exists('openssl_random_pseudo_bytes') && strpos(php_uname('s'), 'Windows') === false) {
        $range = $max - $min;
        if ($range <= 0) {
            return $min;
        } // if min<max return min
        $log = log($range, 2);
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);
        return $min + $rnd;
    } else {
        return mt_rand($min, $max); // for older PHP versions, less secure
    }
}




function helpers__scramblemail($address) {
    $address = "<a class=\"small\" href=\"mailto:$address\">";
    $temp =  chunk_split($address,3,"##");
    $temp_array =  explode("##",$temp);
    $scrambled="";

    foreach ($temp_array as $piece) {
        $scrambled.="+'$piece'";
    }
    $scrambled =  substr($scrambled,1, strlen($scrambled));

    $result = "<script type='text/javascript'>";
    $result.="<!--\n";
    $result.= "document.write($scrambled);\n";
    $result.="-->";
    $result.="</SCRIPT>";
    echo $result;
}

// strip HTML tags from (posted vars) array
function strip_tags_array($var,$exempt=array()) {
    if (is_array($var)) {
        foreach ($var as $k=>$v) {
            if (!in_array($k,$exempt)) {
                $var[$k]=strip_tags_array($v);
            }
        }
    } else {
        $var=strip_tags($var);
        $var=str_replace(array('&','<','>','"',"'",'/'),
            array('&amp;','&lt;','&gt;','&quot;','&#x27;',' &#x2F;'),
            $var);
    }
    return $var;
}


// orsee tracking
function clearpixel() {
    global $settings__disable_orsee_tracking, $settings__root_url,$system__version;
    if (!(isset($settings__disable_orsee_tracking) && $settings__disable_orsee_tracking=='y')) {
        if (check_clearpixel()) {
            $u=$settings__root_url.'|'.$system__version;
            or_load_url('www.orsee.org','/clearpixel3.php?u='.urlencode($u));
        }
    }
}

function check_clearpixel() {
    $return=false;
    $query="SELECT * from ".table('objects')."
            WHERE item_type='clearpixel' AND item_name='clearpixel'";
    $cp=orsee_query($query);
    if (!isset($cp['item_details'])) {
        $query="INSERT IGNORE INTO ".table('objects')."
                SET item_type='clearpixel', item_name='clearpixel', item_details='".time()."'";
        $done=or_query($query);
        $return=true;
    } else {
        if (time()-$cp['item_details']>24*60*60) {
            $query="UPDATE ".table('objects')."
                    SET item_details='".time()."'
                    WHERE item_type='clearpixel' AND item_name='clearpixel'";
            $done=or_query($query);
            $return=true;
        } else {
            $return=false;
        }
    }
    return $return;
}

function or_load_url($host,$file) {
    $fp = fsockopen("$host", 80, $errno, $errdesc);
    $return=false;
    if (!$fp) {
        // no connection
    } else {
        $request = "GET $file HTTP/1.0\r\n";
        $request .= "Host: $host\r\n";
        $request .= "Referer: ORSEE\r\n";
        $request .= "User-Agent: ORSEE\r\n";
        $request .= "Connection: Close\r\n\r\n";
        $page = array();
        fwrite($fp, $request);
        while (!feof($fp)) {
            $page[]=fgets($fp);
        }
        fclose($fp);
        $return=implode('',$page);
    }
    return $return;
}


?>
