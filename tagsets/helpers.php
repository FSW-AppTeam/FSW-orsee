<?php
// part of orsee. see orsee.org

function getmicrotime($time=-1) {
    if ($time==-1) {
        $time=microtime();
    }
    list($usec, $sec) = explode(" ",$time);
    return ((float)$usec + (float)$sec);
}

function support_mail_link() {
    global $settings;
    $sml='<A HREF="mailto:'.$settings['support_mail'].'">'.$settings['support_mail'].'</A>';
    return $sml;
}

function multi_array_sort(&$data, $sortby) {
    $sorting_var1="";
    $sorting_var2="";
    $sorting_var3="";
    if (is_array($sortby)) {
        $sorting_var1 = $sortby[0];
        if (isset($sortby[1])) {
            $sorting_var2 = $sortby[1];
        }
        if (isset($sortby[2])) {
            $sorting_var3 = $sortby[2];
        }
    } else {
        $sorting_var1 = $sortby;
    }
    uasort($data,function ($a,$b) use ($sorting_var1, $sorting_var2, $sorting_var3) {
        if ($a[$sorting_var1]>$b[$sorting_var1]) {
            return 1;
        } elseif ($a[$sorting_var1]<$b[$sorting_var1]) {
            return -1;
        } else {
            if (!$sorting_var2) {
                return 0;
            } else {
                if ($a[$sorting_var2]>$b[$sorting_var2]) {
                    return 1;
                } elseif ($a[$sorting_var2]<$b[$sorting_var2]) {
                    return -1;
                } else {
                    if (!$sorting_var3) {
                        return 0;
                    } else {
                        if ($a[$sorting_var3]>$b[$sorting_var3]) {
                            return 1;
                        } elseif ($a[$sorting_var3]<$b[$sorting_var3]) {
                            return -1;
                        } else {
                            return 0;
                        }
                    }
                }
            }
        }
    });
}

// debug output
function debug_output() {
    global $settings__time_debugging_enabled, $settings__query_debugging_enabled, $debug__script_started;
    global $debug__query_array, $debug__query_time;
    if (isset($settings__time_debugging_enabled) && $settings__time_debugging_enabled=='y') {
        if (isset($debug__script_started)) {
            $debug__script_stopped=getmicrotime();
            $time_needed=round(($debug__script_stopped-getmicrotime($debug__script_started))*1000,3);
            echo 'Overall query time: '.round($debug__query_time*1000,3).'msec,<br>
                 Overall script time: '.$time_needed.'msec<BR><BR>';
        } else {
            echo 'No script start time found.<BR><BR>';
        }
    }
    if (isset($settings__query_debugging_enabled) && $settings__query_debugging_enabled=='y') {
        $i=0;
        if (isset($debug__query_array)) {
            echo 'Nb of queries: '.count($debug__query_array).'<BR>';
            echo '<div>';
            foreach ($debug__query_array as $query) {
                $i++;
                echo '<div style="margin: 0 0 0.4rem 0;">';
                echo '<div><b>'.$i.'.</b> '.round($query['time']*1000,3).'msec</div>';
                echo '<div>'.str_replace(array("\n",","),array("<br />",", "),$query['query']).'</div>';
                echo '</div>';
            }
            echo '</div>';
        }
    }
}


/////////////////////////////
/// NEW TIME FUNCTIONS
function ortime__unixtime_to_sesstime($unixtime=-1) {
    if ($unixtime<0) {
        $unixtime=time();
    }
    return date("YmdHi",$unixtime);
}

function ortime__sesstime_to_unixtime($t) {
    if (!$t) {
        return 0;
    }
    $a=ortime__sesstime_to_array($t);
    extract($a);
    $y=(int)$y;
    $m=(int)$m;
    $d=(int)$d;
    $h=(int)$h;
    $i=(int)$i;
    $s=(int)$s;
    if ($y<=0 || $m<=0 || $d<=0) {
        return 0;
    }
    $unixtime=mktime($h,$i,$s,$m,$d,$y);
    return $unixtime;
}

function ortime__add_hourmin_to_sesstime($t,$h,$m=0) {
    $old_utime=ortime__sesstime_to_unixtime($t);
    $new_utime= $old_utime + ((int) $h * 3600) + ((int) $m * 60);
    return ortime__unixtime_to_sesstime($new_utime);
}



function ortime__sesstime_to_array($t) {
    $a=array();
    $a['y']=substr($t,0,4);
    $a['m']=substr($t,4,2);
    $a['d']=substr($t,6,2);
    $a['h']=(strlen($t)>8) ? substr($t,8,2) : 12;
    $a['i']=(strlen($t)>10) ? substr($t,10,2) : 0;
    $a['s']=(strlen($t)>12) ? substr($t,12,2) : 0;
    foreach (array('h','i','s') as $v) {
        $a[$v]=helpers__pad_number($a[$v],2);
    }
    return $a;
}

function ortime__array_to_sesstime($a,$pre='') {
    $shortcuts=array('y','m','d','h','i');
    foreach ($shortcuts as $s) {
        if (!isset($a[$pre.$s])) {
            $a[$pre.$s]=0;
        }
    }
    return helpers__pad_number($a[$pre.'y'],4).
            helpers__pad_number($a[$pre.'m'],2).
            helpers__pad_number($a[$pre.'d'],2).
            helpers__pad_number($a[$pre.'h'],2).
            helpers__pad_number($a[$pre.'i'],2);
    //return $a[$pre.'y']*100000000+$a[$pre.'m']*1000000+$a[$pre.'d']*10000+$a[$pre.'h']*100+$a[$pre.'i'];
}

function is_mil_time($time_format) {
    $ampme=strpos($time_format,"%a");
    if ("$ampme" == '0' || $ampme>0) {
        return false;
    } else {
        return true;
    }
}

function ortime__array_mil_time_to_array_ampm_time($a) {
    $r=array();
    $h=($a['h']>12) ? $a['h']-12 : $a['h'];
    $r['h']=($h==0) ? 12 : $h;
    $r['h']=helpers__pad_number($r['h'],2);
    $r['a']=($a['h']>=12) ? "pm" : "am";
    $r['i']=$a['i'];
    return $r;
}

function ortime__array_ampm_time_to_array_mil_time($a) { // unused?
    $r=array();
    $p=strtolower($a['a']);
    $h=($a['h']==12) ? 0 : $a['h'];
    $r['h']=($p=='pm') ? $h+12 : $h;
    $r['h']=helpers__pad_number($r['h'],2);
    $r['i']=$a['i'];
    return $r;
}

function ortime__get_weekday($unixtime,$language='') {
    global $lang, $expadmindata, $settings;
    if (!$language) {
        if (isset($lang['lang']) && $lang['lang']) {
            $language=$lang['lang'];
        } else {
            if (isset($expadmindata['language']) && $expadmindata['language']) {
                $language=$expadmindata['language'];
            } else {
                $language=$settings['public_standard_language'];
            }
        }
    }
    $w_index=date("w",$unixtime);
    if (isset($lang['lang']) && $language==$lang['lang']) {
        $wdays=$lang['format_datetime_weekday_abbr'];
    } else {
        $wdays=load_language_symbol('format_datetime_weekday_abbr',$language);
    }
    $wday_arr=explode(',',$wdays);
    $w=$wday_arr[$w_index];
    return $w;
}

function ortime__format($unixtime,$options='',$language='') {
    // possible options: hide_time hide_second hide_date hide_year
    global $lang;

    $op=array('hide_second'=>true);
    $opa=explode(",",$options);
    foreach ($opa as $o) {
        $to=explode(":",trim($o));
        if (isset($to[1]) && trim($to[1])=="false") {
            unset($op[$to[0]]);
        } else {
            $op[$to[0]]=true;
        }
    }

    $arr=ortime__sesstime_to_array(ortime__unixtime_to_sesstime($unixtime));
    $p=ortime__array_mil_time_to_array_ampm_time($arr);
    $arr['h12']=$p['h'];
    $arr['a']=$p['a'];

    if (!$language) {
        if (isset($lang['lang']) && $lang['lang']) {
            $language=$lang['lang'];
        } else {
            global $expadmindata, $settings;
            if (isset($expadmindata['language']) && $expadmindata['language']) {
                $language=$expadmindata['language'];
            } else {
                $language=$settings['public_standard_language'];
            }
        }
    }

    if (isset($op['hide_year'])) {
        $fd='date_no_year';
    } else {
        $fd='date';
    }
    if (isset($op['hide_second'])) {
        $ft='time_no_sec';
    } else {
        $ft='time';
    }
    if (isset($lang['lang']) && $language==$lang['lang']) {
        $dformat=$lang['format_datetime_'.$fd];
        $tformat=$lang['format_datetime_'.$ft];
    } else {
        $dformat=load_language_symbol('format_datetime_'.$fd,$language);
        $tformat=load_language_symbol('format_datetime_'.$ft,$language);
    }

    $f="";
    if (!isset($op['hide_date']) && !isset($op['hide_time'])) {
        if (lang__is_rtl($language)) {
            $f=$tformat." ".$dformat;
        } else {
            $f=$dformat." ".$tformat;
        }
    } else {
        if (!isset($op['hide_date'])) {
            $f.=$dformat;
        }
        if (!isset($op['hide_time'])) {
            $f.=$tformat;
        }
    }
    $arr['w']=ortime__get_weekday($unixtime,$language);

    $datestring=str_replace(array('%Y','%m','%d','%H','%h','%i','%s','%a','%w'),
        array($arr['y'],$arr['m'],$arr['d'],$arr['h'],
        $arr['h12'],$arr['i'],$arr['s'],$arr['a'],$arr['w']),
        $f);
    $datestring=str_replace(" ","&nbsp;",$datestring);
    return $datestring;
}

function ortime__date_parts_to_ymd($y,$m,$d,$mode='ymd') {
    if ($mode!=='ymd' && $mode!=='ym' && $mode!=='y') {
        $mode='ymd';
    }
    $yi=(int)$y;
    $mi=($mode==='y') ? 1 : (int)$m;
    $di=($mode==='ymd') ? (int)$d : 1;
    if ($yi>0 && $mi>0 && $di>0 && checkdate($mi,$di,$yi)) {
        return sprintf('%04d-%02d-%02d',$yi,$mi,$di);
    }
    return '';
}

function ortime__format_ymd_localized($ymd,$language='',$mode='ymd') {
    global $lang;
    $ymd=(string)$ymd;
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/',$ymd,$dm)) {
        return '';
    }
    if ($mode==='y') {
        return $dm[1];
    }
    if ($mode==='ym') {
        if (!$language) {
            if (isset($lang['lang']) && $lang['lang']) {
                $language=$lang['lang'];
            }
        }
        if (!$language || (isset($lang['lang']) && $language==$lang['lang'])) {
            $format=lang('format_datetime_date_no_day');
        } else {
            $format=load_language_symbol('format_datetime_date_no_day',$language);
        }
        if (!$format || $format==='format_datetime_date_no_day') {
            $format='%m/%Y';
        }
        return str_replace(array('%Y','%m'),array($dm[1],$dm[2]),$format);
    }
    $sesstime=$dm[1].$dm[2].$dm[3].'1200';
    return ortime__format(ortime__sesstime_to_unixtime($sesstime),'hide_time:true',$language);
}

// functions for parsing dates according to format

function mult_strpos($haystack,$needle,$sort=true) {
    $result = array();
    if (is_array($needle)) {
        foreach ($needle as $n) {
            $result=$result + mult_strpos($haystack,$n,false);
        }
    } else {
        $pos=0;
        $continue=true;
        $nlen=strlen($needle);
        while ($continue) {
            $pos=strpos(strtolower($haystack),strtolower($needle),$pos);
            if ("$pos"=='0' || $pos>0) {
                $result[$pos]=$needle;
                $pos=$pos+$nlen;
            } else {
                $continue=false;
            }
        }
    }
    if ($sort) {
        ksort($result);
    }
    return $result;
}

function date__parse_string($string,$format) { // unused?
    // check for existence and position of %Y, %m, %d
    $vars=mult_strpos($format,array('%Y','%m','%d'));

    // check whether there are delimiters, and choose relaxed or strict format
    if (preg_match("/(".implode(").+(",$vars).")/i",$format)) {
        $ye='([0-9]{2,4})';
        $me='([0-9]{1,2})';
        $de='([0-9]{1,2})'; // relaxed format
    } else {
        $ye='([0-9]{4})';
        $me='([0-9]{2})';
        $de='([0-9]{2})';
    } // strict format

    // build pattern
    $pattern="/".str_replace(array('%Y','%m','%d'),array($ye,$me,$de),
        preg_quote($format,"/"))."/i";

    // extract numbers
    $vals=array();
    if (preg_match($pattern,$string,$matches)) {
        $i=1;
        foreach ($vars as $var) {
            if (isset($matches[$i])) {
                $vals[strtolower(substr($var,1))]=$matches[$i];
            }
            $i++;
        }
    }
    return($vals);
}


function or__format_number($number,$decimals=2) {
    return number_format($number,$decimals,
        lang('numberformat__decimal_point'),
        lang('numberformat__thousands_separator'));
}



function helpers__pad_number() {
    if (func_num_args()>0) {
        $help=func_get_arg(0);
        $number="$help";
    } else {
        $number="1";
    }
    if (func_num_args()>1) {
        $fillzeros=func_get_arg(1);
    } else {
        $fillzeros=2;
    }

    $padnumber="";
    $length=strlen($number);
    while ($length<$fillzeros) {
        $padnumber=$padnumber."0";
        $length++;
    }
    $padnumber=$padnumber.$number;
    return $padnumber;
}


function id_array_to_db_string($id_array) {
    $db_string="";
    if (is_array($id_array)) {
        foreach ($id_array as $k=>$v) {
            $id_array[$k]='|'.trim($v).'|';
        }
        $db_string=implode(",",$id_array);
    }
    return $db_string;
}

function array_filter_allowed($array,$whitelist) {
    if (!is_array($array)) {
        return array();
    }
    if (!is_array($whitelist) || count($whitelist)===0) {
        return array();
    }
    return array_intersect_key($array,array_flip($whitelist));
}

function db_string_to_id_array($db_string) {
    $in_array=explode(",",$db_string);
    $out_array=array();
    foreach ($in_array as $k=>$v) {
        $v=trim($v);
        if (substr($v,0,1)=='|') {
            $v=substr($v,1);
        }
        if (substr($v,strlen($v)-1,1)=='|') {
            $v=substr($v,0,strlen($v)-1);
        }
        if ($v) {
            $out_array[$k]=$v;
        }
    }
    return $out_array;
}

function property_array_to_db_string($property_array) {
    if (!is_array($property_array)) {
        return "";
    }
    return json_encode($property_array);
}

function db_string_to_property_array($db_string) {
    $property_array=array();
    if ($db_string) {
        $trimmed=ltrim((string)$db_string);
        if ($trimmed!=='' && substr($trimmed,0,1)==='{') {
            $json_decoded=json_decode($db_string,true);
            if (is_array($json_decoded)) {
                return $json_decoded;
            }
        }
        $db_string_array=explode("+=+",$db_string);
        foreach ($db_string_array as $line) {
            $vals=explode("===",$line,2);
            if (count($vals)!==2) {
                continue;
            }
            $k=$vals[0];
            $v=$vals[1];
            if (substr($v,0,1)=='|') {
                $v=substr($v,1);
            }
            if (substr($v,strlen($v)-1,1)=='|') {
                $v=substr($v,0,strlen($v)-1);
            }
            if (substr($k,0,1)=='|') {
                $k=substr($k,1);
            }
            if (substr($k,strlen($k)-1,1)=='|') {
                $k=substr($k,0,strlen($k)-1);
            }
            $property_array[$k]=$v;
        }
    }
    return $property_array;
}

function array_to_table($array) {
    echo '<TABLE>';
    foreach ($array as $row) {
        echo '<TR>';
        foreach ($row as $column) {
            echo '<TD>'.$column.'</TD>';
        }
        echo '</TR>';
    }
    echo '</TABLE>';
}

function or_array_delete_values($array,$values) {
    foreach ($values as $val) {
        if (($key = array_search($val, $array)) !== false) {
            unset($array[$key]);
        }
    }
    return $array;
}

function helpers__sanitize_richtext_href($href) {
    if (!is_string($href)) {
        return '';
    }
    $href=trim(html_entity_decode($href,ENT_QUOTES | ENT_HTML5,'UTF-8'));
    if ($href==='') {
        return '';
    }
    if (preg_match('/^(https?:|mailto:)/i',$href)) {
        return htmlspecialchars($href,ENT_QUOTES,'UTF-8');
    }
    if (preg_match('/^(?:\/[A-Za-z0-9_\-\/\.]*|\.\/[A-Za-z0-9_\-\/\.]*|\.\.\/[A-Za-z0-9_\-\/\.]*|[A-Za-z0-9][A-Za-z0-9_\-\/\.]*)(\?[A-Za-z0-9\-._~%!$&\'()*+,;=:@\/?]*)?(#[A-Za-z0-9\-._~%!$&\'()*+,;=:@\/?]*)?$/',$href)) {
        return htmlspecialchars($href,ENT_QUOTES,'UTF-8');
    }
    return '';
}

function helpers__href_is_external($href) {
    if (!is_string($href)) {
        return false;
    }
    return ((bool) preg_match('/^https?:\/\//i',trim($href)));
}

function helpers__richtext_normalize_image_width($width) {
    if (!is_string($width)) {
        return '';
    }
    $width=trim($width);
    if ($width==='') {
        return '';
    }
    if (preg_match('/^\d+$/',$width)) {
        $width.='px';
    }
    if (!preg_match('/^[0-9]+(?:\.[0-9]+)?(?:%|px|rem|em|vw|vh)$/i',$width)) {
        return '';
    }
    return $width;
}

function helpers__richtext_html_extract_img_meta($attr) {
    $src='';
    $alt='';
    $width='';
    if (!is_string($attr) || $attr==='') {
        return array('src'=>'','alt'=>'','width'=>'');
    }
    if (preg_match('/\bsrc\s*=\s*"([^"]*)"/i',$attr,$sm)
        || preg_match("/\bsrc\s*=\s*'([^']*)'/i",$attr,$sm)
        || preg_match('/\bsrc\s*=\s*([^\s"\']+)/i',$attr,$sm)) {
        $src=trim(html_entity_decode($sm[1],ENT_QUOTES | ENT_HTML5,'UTF-8'));
    }
    if (preg_match('/\balt\s*=\s*"([^"]*)"/i',$attr,$am)
        || preg_match("/\balt\s*=\s*'([^']*)'/i",$attr,$am)
        || preg_match('/\balt\s*=\s*([^\s"\']+)/i',$attr,$am)) {
        $alt=trim((string)$am[1]);
    }
    if (preg_match('/\bwidth\s*=\s*"([^"]*)"/i',$attr,$wm)
        || preg_match("/\bwidth\s*=\s*'([^']*)'/i",$attr,$wm)
        || preg_match('/\bwidth\s*=\s*([^\s"\']+)/i',$attr,$wm)) {
        $width=trim((string)$wm[1]);
    }
    if ($width==='' && (preg_match('/\bstyle\s*=\s*"([^"]*)"/i',$attr,$stm)
        || preg_match("/\bstyle\s*=\s*'([^']*)'/i",$attr,$stm))) {
        if (preg_match('/\bwidth\s*:\s*([0-9]+(?:\.[0-9]+)?(?:%|px|rem|em|vw|vh))/i',$stm[1],$swm)) {
            $width=trim((string)$swm[1]);
        }
    }
    return array('src'=>$src,'alt'=>$alt,'width'=>$width);
}

function helpers__richtext_html_img_tag_to_markup($attr) {
    $img=helpers__richtext_html_extract_img_meta($attr);
    if ($img['src']==='' || preg_match('/^mailto:/i',$img['src'])) {
        return '';
    }
    $safe_src=helpers__sanitize_richtext_href($img['src']);
    if ($safe_src==='') {
        return '';
    }
    $src=html_entity_decode($safe_src,ENT_QUOTES | ENT_HTML5,'UTF-8');
    $width=helpers__richtext_normalize_image_width($img['width']);
    if ($width!=='') {
        return '{{image:'.$src.'|'.$img['alt'].'|'.$width.'}}';
    }
    if ($img['alt']!=='') {
        return '{{image:'.$src.'|'.$img['alt'].'}}';
    }
    return '{{image:'.$src.'}}';
}

function helpers__richtext_html_convert_img_tags_to_markup($text) {
    if (!is_string($text) || $text==='') {
        return '';
    }
    return preg_replace_callback(
        '/<img\b([^>]*)>/is',
        function ($m) {
            return helpers__richtext_html_img_tag_to_markup($m[1]);
        },
        $text
    );
}

function helpers__richtext_html_convert_links_to_markup($text) {
    if (!is_string($text) || $text==='') {
        return '';
    }
    return preg_replace_callback(
        '/<a\b([^>]*)>(.*?)<\/a>/is',
        function ($m) {
            $href='';
            if (preg_match('/\bhref\s*=\s*"([^"]*)"/i',$m[1],$hm)
                || preg_match("/\bhref\s*=\s*'([^']*)'/i",$m[1],$hm)
                || preg_match('/\bhref\s*=\s*([^\s"\']+)/i',$m[1],$hm)) {
                $href=html_entity_decode($hm[1],ENT_QUOTES | ENT_HTML5,'UTF-8');
            }
            $label=trim(strip_tags($m[2]));
            $label=html_entity_decode($label,ENT_QUOTES | ENT_HTML5,'UTF-8');
            if ($href==='' || $label==='') {
                return $label;
            }
            return '['.$href.' '.$label.']';
        },
        $text
    );
}

function helpers__richtext_html_convert_inline_tags_to_markup($text,$br_replacement=' ') {
    if (!is_string($text) || $text==='') {
        return '';
    }
    $text=preg_replace('/<\s*strong\b[^>]*>/i',"'''",$text);
    $text=preg_replace('/<\s*\/\s*strong\s*>/i',"'''",$text);
    $text=preg_replace('/<\s*b\b[^>]*>/i',"'''",$text);
    $text=preg_replace('/<\s*\/\s*b\s*>/i',"'''",$text);

    $text=preg_replace('/<\s*em\b[^>]*>/i',"''",$text);
    $text=preg_replace('/<\s*\/\s*em\s*>/i',"''",$text);
    $text=preg_replace('/<\s*i\b[^>]*>/i',"''",$text);
    $text=preg_replace('/<\s*\/\s*i\s*>/i',"''",$text);

    $text=preg_replace('/<\s*u\b[^>]*>/i',"__",$text);
    $text=preg_replace('/<\s*\/\s*u\s*>/i',"__",$text);

    $text=preg_replace('~<\s*br\s*\/?\s*>~i',$br_replacement,$text);
    return $text;
}

function helpers__sanitize_richtext_html($html) {
    if (!is_string($html) || $html==='') {
        return '';
    }

    $allowed_tags='<p><br><ul><ol><li><b><strong><i><em><u><a><img><h3><h4><center><table><thead><tbody><tr><th><td>';
    $clean=strip_tags($html,$allowed_tags);

    $clean=preg_replace_callback(
        '/<\s*(\/?)\s*(p|br|ul|ol|li|b|strong|i|em|u|h3|h4|center|table|thead|tbody|tr|th|td)\b[^>]*>/i',
        function ($m) {
            $closing=($m[1]==='/') ? '/' : '';
            $tag=strtolower($m[2]);
            if ($closing) {
                return '</'.$tag.'>';
            }
            return '<'.$tag.'>';
        },
        $clean
    );

    $clean=preg_replace_callback(
        '/<img\b([^>]*)>/i',
        function ($m) {
            $img=helpers__richtext_html_extract_img_meta($m[1]);
            if ($img['src']==='' || preg_match('/^mailto:/i',$img['src'])) {
                return '';
            }
            $src=helpers__sanitize_richtext_href($img['src']);
            if ($src==='') {
                return '';
            }
            $width=helpers__richtext_normalize_image_width($img['width']);
            $alt_html=htmlspecialchars($img['alt'],ENT_QUOTES,'UTF-8');
            $out='<img src="'.$src.'" alt="'.$alt_html.'"';
            if ($width!=='') {
                $out.=' width="'.htmlspecialchars($width,ENT_QUOTES,'UTF-8').'"';
            }
            $out.='>';
            return $out;
        },
        $clean
    );

    $clean=preg_replace_callback(
        '/<a\b([^>]*)>/i',
        function ($m) {
            $attr=$m[1];
            $href='';
            if (preg_match('/\bhref\s*=\s*"([^"]*)"/i',$attr,$hm)
                || preg_match("/\bhref\s*=\s*'([^']*)'/i",$attr,$hm)
                || preg_match('/\bhref\s*=\s*([^\s"\']+)/i',$attr,$hm)) {
                $href=helpers__sanitize_richtext_href($hm[1]);
            }
            if ($href==='') {
                return '<a>';
            }
            $target='';
            $rel='';
            if (isset($hm[1]) && helpers__href_is_external($hm[1])) {
                $target=' target="_blank"';
                $rel=' rel="noopener noreferrer"';
            }
            return '<a href="'.$href.'"'.$target.$rel.'>';
        },
        $clean
    );

    return $clean;
}

function helpers__richtext_html_list_block_to_markup($list_html,$marker='*') {
    if (!is_string($list_html) || $list_html==='') {
        return '';
    }
    $out_lines=array();
    if (!preg_match_all('/<li\b[^>]*>(.*?)<\/li>/is',$list_html,$li_matches)) {
        return '';
    }
    foreach ($li_matches[1] as $li_html) {
        $line=trim(strip_tags($li_html));
        if ($line!=='') {
            $out_lines[]=$marker.' '.$line;
        }
    }
    if (!count($out_lines)) {
        return '';
    }
    return implode("\n",$out_lines)."\n\n";
}

function helpers__richtext_html_inline_to_markup($html_fragment) {
    if (!is_string($html_fragment) || $html_fragment==='') {
        return '';
    }
    $text=$html_fragment;
    $text=helpers__richtext_html_convert_img_tags_to_markup($text);
    $text=helpers__richtext_html_convert_links_to_markup($text);
    $text=helpers__richtext_html_convert_inline_tags_to_markup($text,' ');
    $text=strip_tags($text);
    $text=html_entity_decode($text,ENT_QUOTES | ENT_HTML5,'UTF-8');
    $text=preg_replace('/\s+/u',' ',$text);
    return trim($text);
}

function helpers__richtext_html_table_to_pipe($table_html) {
    $rows=array();
    if (!preg_match_all('/<tr\b[^>]*>(.*?)<\/tr>/is',$table_html,$tr_matches)) {
        return '';
    }
    foreach ($tr_matches[1] as $row_html) {
        $cells=array();
        if (!preg_match_all('/<(th|td)\b[^>]*>(.*?)<\/\1>/is',$row_html,$cell_matches,PREG_SET_ORDER)) {
            continue;
        }
        foreach ($cell_matches as $cell_match) {
            $cell=trim($cell_match[2]);
            $cell=helpers__richtext_html_inline_to_markup($cell);
            $cell=str_replace('|','\|',$cell);
            $cells[]=$cell;
        }
        if (count($cells)) {
            $rows[]=$cells;
        }
    }
    if (!count($rows)) {
        return '';
    }

    $out_lines=array();
    foreach ($rows as $i => $cells) {
        $out_lines[]='| '.implode(' | ',$cells).' |';
        if ($i===0) {
            $sep=array();
            foreach ($cells as $unused) {
                $sep[]='---';
            }
            $out_lines[]='| '.implode(' | ',$sep).' |';
        }
    }
    return implode("\n",$out_lines)."\n\n";
}

function helpers__richtext_html_to_markup($html) {
    if (!is_string($html) || $html==='') {
        return '';
    }

    $text=$html;
    $text=helpers__richtext_html_convert_img_tags_to_markup($text);

    $text=preg_replace('~<\s*center\s*>~i',"\n{{center:start}}\n",$text);
    $text=preg_replace('~<\s*/\s*center\s*>~i',"\n{{center:end}}\n",$text);

    $text=preg_replace_callback(
        '/<table\b[^>]*>(.*?)<\/table>/is',
        function ($m) {
            return helpers__richtext_html_table_to_pipe($m[1]);
        },
        $text
    );

    $text=helpers__richtext_html_convert_links_to_markup($text);
    $text=helpers__richtext_html_convert_inline_tags_to_markup($text,"\n");

    $text=preg_replace_callback(
        '/<ol\b[^>]*>(.*?)<\/ol>/is',
        function ($m) {
            return helpers__richtext_html_list_block_to_markup($m[1],'#');
        },
        $text
    );

    $text=preg_replace_callback(
        '/<ul\b[^>]*>(.*?)<\/ul>/is',
        function ($m) {
            return helpers__richtext_html_list_block_to_markup($m[1],'*');
        },
        $text
    );

    $text=preg_replace_callback(
        '/<h3\b[^>]*>(.*?)<\/h3>/is',
        function ($m) {
            return "\n= ".trim(strip_tags($m[1]))." =\n\n";
        },
        $text
    );
    $text=preg_replace_callback(
        '/<h4\b[^>]*>(.*?)<\/h4>/is',
        function ($m) {
            return "\n== ".trim(strip_tags($m[1]))." ==\n\n";
        },
        $text
    );

    $text=preg_replace('~<\s*/\s*p\s*>~i',"\n\n",$text);
    $text=preg_replace('~<\s*p\b[^>]*>~i','',$text);
    $text=preg_replace('~<\s*/?\s*(ul|ol|li|center|thead|tbody|tr|th|td)\b[^>]*>~i','',$text);

    $text=strip_tags($text);
    $text=html_entity_decode($text,ENT_QUOTES | ENT_HTML5,'UTF-8');
    return $text;
}

function helpers__wiki_parse_inline($text) {
    if (!is_string($text) || $text==='') {
        return '';
    }

    $link_tokens=array();
    $token_index=0;

    $image_tokens=array();
    $image_index=0;
    $text=preg_replace_callback(
        '/\{\{\s*image\s*:\s*([^|}]+?)(?:\s*\|\s*([^|}]*?))?(?:\s*\|\s*([^}]*?))?\s*\}\}/iu',
        function ($m) use (&$image_tokens,&$image_index) {
            $src_raw=trim((string)$m[1]);
            if ($src_raw==='' || preg_match('/^mailto:/i',$src_raw)) {
                return $m[0];
            }
            $src=helpers__sanitize_richtext_href($src_raw);
            if ($src==='') {
                return $m[0];
            }
            $alt='';
            $width='';
            if (isset($m[2])) {
                $alt=trim((string)$m[2]);
            }
            if (isset($m[3])) {
                $width=trim((string)$m[3]);
            }
            if ($width==='' && $alt!=='' && preg_match('/^[0-9]+(?:\.[0-9]+)?(?:%|px|rem|em|vw|vh)$/i',$alt)) {
                $width=$alt;
                $alt='';
            }
            if ($width!=='' && preg_match('/^\d+$/',$width)) {
                $width.='px';
            }
            if ($width!=='' && !preg_match('/^[0-9]+(?:\.[0-9]+)?(?:%|px|rem|em|vw|vh)$/i',$width)) {
                $width='';
            }
            $token='@@ORWIKIIMG_'.$image_index.'@@';
            $img='<img src="'.$src.'" alt="'.htmlspecialchars($alt,ENT_QUOTES,'UTF-8').'"';
            if ($width!=='') {
                $img.=' style="width:'.$width.';"';
            }
            $img.='>';
            $image_tokens[$token]=$img;
            $image_index++;
            return $token;
        },
        $text
    );
    $text=preg_replace_callback(
        '/\[([^\s\]]+)\s+([^\]]+)\]/u',
        function ($m) use (&$link_tokens,&$token_index) {
            $href_raw=$m[1];
            $href=helpers__sanitize_richtext_href($href_raw);
            if ($href==='') {
                return $m[0];
            }
            $attrs='';
            $class='';
            $icon_html='';
            if (helpers__href_is_external($href_raw)) {
                $attrs=' target="_blank" rel="noopener noreferrer"';
                $class=' class="orsee-external-link"';
                $icon_html=' <span class="fa fa-external-link" aria-hidden="true"></span>';
            }
            $token='@@ORWIKILINK_'.$token_index.'@@';
            $link_tokens[$token]='<a href="'.$href.'"'.$class.$attrs.'>'.htmlspecialchars($m[2],ENT_QUOTES,'UTF-8').$icon_html.'</a>';
            $token_index++;
            return $token;
        },
        $text
    );

    $text=htmlspecialchars($text,ENT_QUOTES,'UTF-8');
    $text=preg_replace('/&#039;&#039;&#039;(.*?)&#039;&#039;&#039;/u','<strong>$1</strong>',$text);
    $text=preg_replace('/&#039;&#039;(.*?)&#039;&#039;/u','<em>$1</em>',$text);
    $text=preg_replace('/__(.*?)__/u','<u>$1</u>',$text);

    foreach ($link_tokens as $token => $anchor_html) {
        $text=str_replace($token,$anchor_html,$text);
    }
    foreach ($image_tokens as $token => $image_html) {
        $text=str_replace($token,$image_html,$text);
    }

    return $text;
}

function helpers__wiki_parse_table_row($line) {
    if (!is_string($line)) {
        return array();
    }
    $trim=trim($line);
    if ($trim==='') {
        return array();
    }
    if (substr($trim,0,1)==='|') {
        $trim=substr($trim,1);
    }
    if (substr($trim,-1)==='|') {
        $trim=substr($trim,0,-1);
    }
    $parts=preg_split('/(?<!\\\\)\|/',$trim);
    if (!is_array($parts)) {
        return array();
    }
    $cells=array();
    foreach ($parts as $part) {
        $cells[]=trim(str_replace('\|','|',$part));
    }
    return $cells;
}

function helpers__wiki_table_separator_to_alignments($cells) {
    if (!is_array($cells) || !count($cells)) {
        return false;
    }
    $align=array();
    foreach ($cells as $cell) {
        $c=trim($cell);
        if (!preg_match('/^:?-{3,}:?$/',$c)) {
            return false;
        }
        $left=(substr($c,0,1)===':');
        $right=(substr($c,-1)===':');
        if ($left && $right) {
            $align[]='center';
        } elseif ($right) {
            $align[]='right';
        } else {
            $align[]='left';
        }
    }
    return $align;
}

function helpers__wiki_extract_cell_alignment($cell_text) {
    $out=array('text'=>'','align'=>'');
    if (!is_string($cell_text)) {
        return $out;
    }
    $cell=trim($cell_text);
    if ($cell==='') {
        return $out;
    }

    $left=false;
    $right=false;

    if (preg_match('/^:\s*(.*)$/u',$cell,$m)) {
        $left=true;
        $cell=$m[1];
    }
    if (preg_match('/^(.*?)(?:\s*):\s*$/u',$cell,$m)) {
        $right=true;
        $cell=$m[1];
    }

    $cell=trim($cell);
    if ($left && $right) {
        $out['align']='center';
    } elseif ($right) {
        $out['align']='right';
    } elseif ($left) {
        $out['align']='left';
    }
    $out['text']=$cell;
    return $out;
}

function helpers__parse_simple_wiki_markup($text) {
    if (!is_string($text) || trim($text)==='') {
        return '';
    }

    $text=str_replace(array("\r\n","\r"),"\n",$text);
    $text=preg_replace('~<\s*br\s*/?\s*>~i',"\n",$text);
    $text=preg_replace('~<\s*center\s*>~i',"\n{{center:start}}\n",$text);
    $text=preg_replace('~<\s*/\s*center\s*>~i',"\n{{center:end}}\n",$text);
    $lines=explode("\n",$text);

    $html='';
    $paragraph=array();
    $list_tags=array();
    $li_open=array();
    $center_open=false;
    $current_width=100;
    $container_open=false;
    $table_rows=array();

    $open_container=function ($width) use (&$html,&$container_open) {
        if ($container_open) {
            $html.='</div>';
        }
        $html.='<div class="orsee-wiki-page" style="max-width:'.$width.'%;">';
        $container_open=true;
    };

    $flush_paragraph=function () use (&$paragraph,&$html) {
        if (!count($paragraph)) {
            return;
        }
        $parts=array();
        foreach ($paragraph as $line) {
            $parts[]=helpers__wiki_parse_inline($line);
        }
        $html.='<p>'.implode(' ',$parts).'</p>';
        $paragraph=array();
    };

    $close_lists=function () use (&$list_tags,&$li_open,&$html) {
        for ($depth=count($list_tags);$depth>=1;--$depth) {
            if (!empty($li_open[$depth])) {
                $html.='</li>';
                $li_open[$depth]=false;
            }
            $html.='</'.$list_tags[$depth].'>';
            unset($list_tags[$depth],$li_open[$depth]);
        }
    };

    $flush_table=function () use (&$table_rows,&$html) {
        if (!count($table_rows)) {
            return;
        }

        $alignments=array();
        $header=array();
        $body_rows=$table_rows;

        if (count($table_rows)>=2) {
            $sep_align=helpers__wiki_table_separator_to_alignments($table_rows[1]);
            if (is_array($sep_align)) {
                $header=$table_rows[0];
                $alignments=$sep_align;
                $body_rows=array_slice($table_rows,2);
            }
        }

        $max_cols=0;
        if (count($header)>$max_cols) {
            $max_cols=count($header);
        }
        foreach ($body_rows as $r) {
            if (count($r)>$max_cols) {
                $max_cols=count($r);
            }
        }
        if ($max_cols===0) {
            $table_rows=array();
            return;
        }

        $html.='<div class="orsee-wiki-table">';
        if (count($header)) {
            $html.='<div class="orsee-wiki-table-row is-head">';
            for ($i=0;$i<$max_cols;$i++) {
                $cell=(isset($header[$i]) ? $header[$i] : '');
                $class='';
                $cell_def=helpers__wiki_extract_cell_alignment($cell);
                $cell_text=$cell_def['text'];
                $cell_align=$cell_def['align'];
                if ($cell_align==='') {
                    if (isset($alignments[$i])) {
                        $cell_align=$alignments[$i];
                    }
                }
                if ($cell_align!=='') {
                    $class=' is-'.$cell_align;
                }
                $cell_html=helpers__wiki_parse_inline($cell_text);
                $cell_html=str_ireplace('{{br}}','<br>',$cell_html);
                $html.='<div class="orsee-wiki-table-cell'.$class.'">'.$cell_html.'</div>';
            }
            $html.='</div>';
        }
        foreach ($body_rows as $r) {
            $html.='<div class="orsee-wiki-table-row">';
            for ($i=0;$i<$max_cols;$i++) {
                $cell=(isset($r[$i]) ? $r[$i] : '');
                $class='';
                $cell_def=helpers__wiki_extract_cell_alignment($cell);
                $cell_text=$cell_def['text'];
                $cell_align=$cell_def['align'];
                if ($cell_align==='') {
                    if (isset($alignments[$i])) {
                        $cell_align=$alignments[$i];
                    }
                }
                if ($cell_align!=='') {
                    $class=' is-'.$cell_align;
                }
                $cell_html=helpers__wiki_parse_inline($cell_text);
                $cell_html=str_ireplace('{{br}}','<br>',$cell_html);
                $html.='<div class="orsee-wiki-table-cell'.$class.'">'.$cell_html.'</div>';
            }
            $html.='</div>';
        }
        $html.='</div>';
        $table_rows=array();
    };

    $open_container($current_width);

    foreach ($lines as $line_raw) {
        $line=rtrim($line_raw);
        $trim=trim($line);

        if ($trim!=='' && preg_match('/^\|.*\|$/',$trim)) {
            $flush_paragraph();
            $close_lists();
            if ($center_open) {
                $html.='</div>';
                $center_open=false;
            }
            $cells=helpers__wiki_parse_table_row($trim);
            if (count($cells)) {
                $table_rows[]=$cells;
                continue;
            }
        } else {
            $flush_table();
        }

        if (preg_match('/^\{\{\s*page-width\s*:\s*(\d{1,3})%\s*\}\}$/i',$trim,$m)) {
            $flush_paragraph();
            $close_lists();
            if ($center_open) {
                $html.='</div>';
                $center_open=false;
            }
            $current_width=max(1,min(100,(int) $m[1]));
            $open_container($current_width);
            continue;
        }

        if ($trim==='{{center:start}}') {
            $flush_paragraph();
            $close_lists();
            if (!$center_open) {
                $html.='<div class="orsee-wiki-center">';
                $center_open=true;
            }
            continue;
        }
        if ($trim==='{{center:end}}') {
            $flush_paragraph();
            $close_lists();
            if ($center_open) {
                $html.='</div>';
                $center_open=false;
            }
            continue;
        }

        if (preg_match('/^\{\{\s*spacer\s*(?::\s*([0-9]+(?:\.[0-9]+)?))?\s*\}\}$/i',$trim,$m)) {
            $flush_paragraph();
            $close_lists();
            $height=1.0;
            if (isset($m[1]) && $m[1]!=='') {
                $height=max(0,min(12,(float) $m[1]));
            }
            $html.='<div class="orsee-wiki-spacer" style="height: '.$height.'em;"></div>';
            continue;
        }

        if ($trim==='') {
            $flush_paragraph();
            $close_lists();
            continue;
        }

        if (preg_match('/^==\s*(.*?)\s*==$/u',$trim,$m)) {
            $flush_paragraph();
            $close_lists();
            $html.='<h4>'.helpers__wiki_parse_inline($m[1]).'</h4>';
            continue;
        }
        if (preg_match('/^=\s*(.*?)\s*=$/u',$trim,$m)) {
            $flush_paragraph();
            $close_lists();
            $html.='<h3>'.helpers__wiki_parse_inline($m[1]).'</h3>';
            continue;
        }

        if (preg_match('/^([*]{1,4}|[#]{1,4})\s*(.*)$/u',$trim,$m)) {
            $flush_paragraph();

            $marker=$m[1];
            $depth=strlen($marker);
            $tag=($marker[0]==='#') ? 'ol' : 'ul';
            $text_i=helpers__wiki_parse_inline($m[2]);

            if (count($list_tags)>0 && $list_tags[1]!==$tag) {
                $close_lists();
            }

            $current_depth=count($list_tags);
            if ($depth>$current_depth) {
                for ($d=$current_depth+1;$d<=$depth;$d++) {
                    $html.='<'.$tag.'>';
                    $list_tags[$d]=$tag;
                    $li_open[$d]=false;
                }
            } elseif ($depth<$current_depth) {
                for ($d=$current_depth;$d>$depth;--$d) {
                    if (!empty($li_open[$d])) {
                        $html.='</li>';
                        $li_open[$d]=false;
                    }
                    $html.='</'.$list_tags[$d].'>';
                    unset($list_tags[$d],$li_open[$d]);
                }
            }

            if (!empty($li_open[$depth])) {
                $html.='</li>';
                $li_open[$depth]=false;
            }
            $html.='<li>'.$text_i;
            $li_open[$depth]=true;
            continue;
        }

        $close_lists();
        $paragraph[]=$line;
    }

    $flush_table();
    $flush_paragraph();
    $close_lists();
    if ($center_open) {
        $html.='</div>';
    }
    if ($container_open) {
        $html.='</div>';
    }

    return $html;
}

function helpers__render_richtext($text) {
    if (!is_string($text) || trim($text)==='') {
        return '';
    }
    $source=str_replace(array("\r\n","\r"),"\n",$text);
    if (strpos($source,'<')!==false && strpos($source,'>')!==false) {
        $source=helpers__richtext_html_to_markup(helpers__sanitize_richtext_html($source));
    }
    return helpers__parse_simple_wiki_markup($source);
}

function fix_utf8($mixed) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = fix_utf8($value);
        }
        return $mixed;
    } elseif (is_string($mixed)) {
        return mb_convert_encoding($mixed,'UTF-8','UTF-8');
    } else {
        return $mixed;
    }
}

function helpers__fontawesome_icon_whitelist() {
    $list='fa-0,fa-1,fa-2,fa-3,fa-4,fa-5,fa-500px,fa-6,fa-7,fa-8,fa-9,fa-a,fa-ad,fa-add,fa-address-book,fa-address-card,fa-adjust,fa-adn,fa-air-freshener,fa-align-center,fa-align-justify,fa-align-left,fa-align-right,fa-allergies,fa-amazon,fa-ambulance,fa-american-sign-language-interpreting,fa-anchor,fa-android,fa-angellist,fa-angle-double-down,fa-angle-double-left,fa-angle-double-right,fa-angle-double-up,fa-angle-down,fa-angle-left,fa-angle-right,fa-angle-up,fa-angles-down,fa-angles-left,fa-angles-right,fa-angles-up,fa-angry,fa-ankh,fa-apple,fa-apple-alt,fa-apple-whole,fa-archive,fa-archway,fa-area-chart,fa-arrow-alt-circle-down,fa-arrow-alt-circle-left,fa-arrow-alt-circle-right,fa-arrow-alt-circle-up,fa-arrow-circle-down,fa-arrow-circle-left,fa-arrow-circle-right,fa-arrow-circle-up,fa-arrow-down,fa-arrow-down-1-9,fa-arrow-down-9-1,fa-arrow-down-a-z,fa-arrow-down-long,fa-arrow-down-short-wide,fa-arrow-down-wide-short,fa-arrow-down-z-a,fa-arrow-left,fa-arrow-left-long,fa-arrow-left-rotate,fa-arrow-pointer,fa-arrow-right,fa-arrow-right-arrow-left,fa-arrow-right-from-bracket,fa-arrow-right-from-file,fa-arrow-right-long,fa-arrow-right-rotate,fa-arrow-right-to-bracket,fa-arrow-right-to-file,fa-arrow-rotate-back,fa-arrow-rotate-backward,fa-arrow-rotate-forward,fa-arrow-rotate-left,fa-arrow-rotate-right,fa-arrow-trend-down,fa-arrow-trend-up,fa-arrow-turn-down,fa-arrow-turn-right,fa-arrow-turn-up,fa-arrow-up,fa-arrow-up-1-9,fa-arrow-up-9-1,fa-arrow-up-a-z,fa-arrow-up-from-bracket,fa-arrow-up-long,fa-arrow-up-right-from-square,fa-arrow-up-short-wide,fa-arrow-up-wide-short,fa-arrow-up-z-a,fa-arrows,fa-arrows-alt,fa-arrows-alt-h,fa-arrows-alt-v,fa-arrows-h,fa-arrows-left-right,fa-arrows-rotate,fa-arrows-up-down,fa-arrows-up-down-left-right,fa-arrows-v,fa-asl-interpreting,fa-assistive-listening-systems,fa-asterisk,fa-at,fa-atlas,fa-atom,fa-audio-description,fa-austral-sign,fa-automobile,fa-award,fa-b,fa-baby,fa-baby-carriage,fa-backspace,fa-backward,fa-backward-fast,fa-backward-step,fa-bacon,fa-bacteria,fa-bacterium,fa-bag-shopping,fa-bahai,fa-baht-sign,fa-balance-scale,fa-balance-scale-left,fa-balance-scale-right,fa-ban,fa-ban-smoking,fa-band-aid,fa-bandage,fa-bandcamp,fa-bank,fa-bar-chart,fa-bar-chart-o,fa-barcode,fa-bars,fa-bars-progress,fa-bars-staggered,fa-baseball,fa-baseball-ball,fa-basket-shopping,fa-basketball,fa-basketball-ball,fa-bath,fa-bathtub,fa-battery,fa-battery-0,fa-battery-1,fa-battery-2,fa-battery-3,fa-battery-4,fa-battery-5,fa-battery-car,fa-battery-empty,fa-battery-full,fa-battery-half,fa-battery-quarter,fa-battery-three-quarters,fa-bed,fa-bed-pulse,fa-beer,fa-beer-mug-empty,fa-behance,fa-behance-square,fa-bell,fa-bell-concierge,fa-bell-slash,fa-bezier-curve,fa-bible,fa-bicycle,fa-biking,fa-binoculars,fa-biohazard,fa-birthday-cake,fa-bitbucket,fa-bitbucket-square,fa-bitcoin,fa-bitcoin-sign,fa-black-tie,fa-blackboard,fa-blender,fa-blender-phone,fa-blind,fa-blog,fa-bluetooth,fa-bluetooth-b,fa-bold,fa-bolt,fa-bomb,fa-bone,fa-bong,fa-book,fa-book-atlas,fa-book-bible,fa-book-dead,fa-book-journal-whills,fa-book-medical,fa-book-open,fa-book-open-reader,fa-book-quran,fa-book-reader,fa-book-skull,fa-bookmark,fa-border-all,fa-border-none,fa-border-style,fa-border-top-left,fa-bowling-ball,fa-box,fa-box-archive,fa-box-open,fa-box-tissue,fa-boxes,fa-boxes-alt,fa-boxes-stacked,fa-braille,fa-brain,fa-brazilian-real-sign,fa-bread-slice,fa-briefcase,fa-briefcase-clock,fa-briefcase-medical,fa-broadcast-tower,fa-broom,fa-broom-ball,fa-brush,fa-bug,fa-building,fa-bullhorn,fa-bullseye,fa-burger,fa-burn,fa-bus,fa-bus-alt,fa-bus-simple,fa-business-time,fa-buysellads,fa-c,fa-cab,fa-cake,fa-cake-candles,fa-calculator,fa-calendar,fa-calendar-alt,fa-calendar-check,fa-calendar-day,fa-calendar-days,fa-calendar-minus,fa-calendar-plus,fa-calendar-times,fa-calendar-week,fa-calendar-xmark,fa-camera,fa-camera-alt,fa-camera-retro,fa-camera-rotate,fa-campground,fa-cancel,fa-candy-cane,fa-cannabis,fa-capsules,fa-car,fa-car-alt,fa-car-battery,fa-car-crash,fa-car-rear,fa-car-side,fa-caravan,fa-caret-down,fa-caret-left,fa-caret-right,fa-caret-square-down,fa-caret-square-left,fa-caret-square-right,fa-caret-square-up,fa-caret-up,fa-carriage-baby,fa-carrot,fa-cart-arrow-down,fa-cart-flatbed,fa-cart-flatbed-suitcase,fa-cart-plus,fa-cart-shopping,fa-cash-register,fa-cat,fa-cc-amex,fa-cc-diners-club,fa-cc-discover,fa-cc-mastercard,fa-cc-paypal,fa-cc-stripe,fa-cc-visa,fa-cedi-sign,fa-cent-sign,fa-certificate,fa-chain,fa-chain-broken,fa-chain-slash,fa-chair,fa-chalkboard,fa-chalkboard-teacher,fa-chalkboard-user,fa-champagne-glasses,fa-charging-station,fa-chart-area,fa-chart-bar,fa-chart-column,fa-chart-gantt,fa-chart-line,fa-chart-pie,fa-check,fa-check-circle,fa-check-double,fa-check-square,fa-check-to-slot,fa-cheese,fa-chess,fa-chess-bishop,fa-chess-board,fa-chess-king,fa-chess-knight,fa-chess-pawn,fa-chess-queen,fa-chess-rook,fa-chevron-circle-down,fa-chevron-circle-left,fa-chevron-circle-right,fa-chevron-circle-up,fa-chevron-down,fa-chevron-left,fa-chevron-right,fa-chevron-up,fa-child,fa-chrome,fa-church,fa-circle,fa-circle-arrow-down,fa-circle-arrow-left,fa-circle-arrow-right,fa-circle-arrow-up,fa-circle-check,fa-circle-chevron-down,fa-circle-chevron-left,fa-circle-chevron-right,fa-circle-chevron-up,fa-circle-dollar-to-slot,fa-circle-dot,fa-circle-down,fa-circle-exclamation,fa-circle-h,fa-circle-half-stroke,fa-circle-info,fa-circle-left,fa-circle-minus,fa-circle-notch,fa-circle-o-notch,fa-circle-pause,fa-circle-play,fa-circle-plus,fa-circle-question,fa-circle-radiation,fa-circle-right,fa-circle-stop,fa-circle-up,fa-circle-user,fa-circle-xmark,fa-city,fa-clapperboard,fa-clinic-medical,fa-clipboard,fa-clipboard-check,fa-clipboard-list,fa-clock,fa-clock-four,fa-clock-rotate-left,fa-close,fa-closed-captioning,fa-cloud,fa-cloud-arrow-down,fa-cloud-arrow-up,fa-cloud-download,fa-cloud-download-alt,fa-cloud-meatball,fa-cloud-moon,fa-cloud-moon-rain,fa-cloud-rain,fa-cloud-showers-heavy,fa-cloud-sun,fa-cloud-sun-rain,fa-cloud-upload,fa-cloud-upload-alt,fa-clover,fa-cny,fa-cocktail,fa-code,fa-code-branch,fa-code-commit,fa-code-compare,fa-code-fork,fa-code-merge,fa-code-pull-request,fa-codepen,fa-codiepie,fa-coffee,fa-cog,fa-cogs,fa-coins,fa-colon-sign,fa-columns,fa-comment,fa-comment-alt,fa-comment-dollar,fa-comment-dots,fa-comment-medical,fa-comment-slash,fa-comment-sms,fa-commenting,fa-comments,fa-comments-dollar,fa-compact-disc,fa-compass-drafting,fa-compress,fa-compress-alt,fa-compress-arrows-alt,fa-computer-mouse,fa-concierge-bell,fa-connectdevelop,fa-contact-book,fa-contact-card,fa-cookie,fa-cookie-bite,fa-copy,fa-couch,fa-creative-commons,fa-credit-card-alt,fa-crop,fa-crop-alt,fa-crop-simple,fa-cross,fa-crosshairs,fa-crow,fa-crown,fa-crutch,fa-cruzeiro-sign,fa-css3,fa-cube,fa-cubes,fa-cut,fa-cutlery,fa-d,fa-dashboard,fa-dashcube,fa-database,fa-deaf,fa-deafness,fa-dedent,fa-delete-left,fa-delicious,fa-democrat,fa-desktop,fa-desktop-alt,fa-deviantart,fa-dharmachakra,fa-diagnoses,fa-diagram-project,fa-diamond-turn-right,fa-dice,fa-dice-d20,fa-dice-d6,fa-dice-five,fa-dice-four,fa-dice-one,fa-dice-six,fa-dice-three,fa-dice-two,fa-digg,fa-digital-tachograph,fa-directions,fa-disease,fa-divide,fa-dizzy,fa-dna,fa-dog,fa-dollar,fa-dollar-sign,fa-dolly,fa-dolly-box,fa-dolly-flatbed,fa-donate,fa-dong-sign,fa-door-closed,fa-door-open,fa-dot-circle,fa-dove,fa-down-left-and-up-right-to-center,fa-down-long,fa-download,fa-drafting-compass,fa-dragon,fa-draw-polygon,fa-dribbble,fa-drivers-license,fa-dropbox,fa-droplet,fa-droplet-slash,fa-drum,fa-drum-steelpan,fa-drumstick-bite,fa-drupal,fa-dumbbell,fa-dumpster,fa-dumpster-fire,fa-dungeon,fa-e,fa-ear-deaf,fa-ear-listen,fa-earth,fa-earth-africa,fa-earth-america,fa-earth-americas,fa-earth-asia,fa-earth-europe,fa-earth-oceania,fa-edge,fa-eercast,fa-egg,fa-eject,fa-elevator,fa-ellipsis,fa-ellipsis-h,fa-ellipsis-v,fa-ellipsis-vertical,fa-empire,fa-envelope,fa-envelope-open,fa-envelope-open-text,fa-envelope-square,fa-envelopes-bulk,fa-envira,fa-equals,fa-eraser,fa-ethernet,fa-etsy,fa-eur,fa-euro,fa-euro-sign,fa-exchange,fa-exchange-alt,fa-exclamation,fa-exclamation-circle,fa-exclamation-triangle,fa-expand,fa-expand-alt,fa-expand-arrows-alt,fa-expeditedssl,fa-external-link,fa-external-link-alt,fa-external-link-square,fa-external-link-square-alt,fa-eye,fa-eye-dropper,fa-eye-dropper-empty,fa-eye-low-vision,fa-eyedropper,fa-f,fa-fa,fa-face-angry,fa-face-dizzy,fa-face-flushed,fa-face-frown,fa-face-frown-open,fa-face-grimace,fa-face-grin,fa-face-grin-beam,fa-face-grin-beam-sweat,fa-face-grin-hearts,fa-face-grin-squint,fa-face-grin-squint-tears,fa-face-grin-stars,fa-face-grin-tears,fa-face-grin-tongue,fa-face-grin-tongue-squint,fa-face-grin-tongue-wink,fa-face-grin-wide,fa-face-grin-wink,fa-face-kiss,fa-face-kiss-beam,fa-face-kiss-wink-heart,fa-face-laugh,fa-face-laugh-beam,fa-face-laugh-squint,fa-face-laugh-wink,fa-face-meh,fa-face-meh-blank,fa-face-rolling-eyes,fa-face-sad-cry,fa-face-sad-tear,fa-face-smile,fa-face-smile-beam,fa-face-smile-wink,fa-face-surprise,fa-face-tired,fa-facebook,fa-facebook-square,fa-fan,fa-fast-backward,fa-fast-forward,fa-faucet,fa-fax,fa-feather,fa-feather-alt,fa-feather-pointed,fa-feed,fa-female,fa-fighter-jet,fa-file,fa-file-alt,fa-file-archive,fa-file-arrow-down,fa-file-arrow-up,fa-file-audio,fa-file-clipboard,fa-file-code,fa-file-contract,fa-file-csv,fa-file-download,fa-file-excel,fa-file-export,fa-file-image,fa-file-import,fa-file-invoice,fa-file-invoice-dollar,fa-file-lines,fa-file-medical,fa-file-medical-alt,fa-file-pdf,fa-file-powerpoint,fa-file-prescription,fa-file-signature,fa-file-text,fa-file-upload,fa-file-video,fa-file-waveform,fa-file-word,fa-file-zipper,fa-fill,fa-fill-drip,fa-film,fa-filter,fa-filter-circle-dollar,fa-filter-circle-xmark,fa-fingerprint,fa-fire,fa-fire-alt,fa-fire-extinguisher,fa-fire-flame-curved,fa-fire-flame-simple,fa-firefox,fa-first-aid,fa-first-order,fa-fish,fa-fist-raised,fa-flag,fa-flag-checkered,fa-flag-usa,fa-flash,fa-flask,fa-flickr,fa-floppy-disk,fa-florin-sign,fa-flushed,fa-folder,fa-folder-minus,fa-folder-open,fa-folder-plus,fa-folder-tree,fa-font,fa-font-awesome-flag,fa-font-awesome-logo-full,fa-fonticons,fa-football,fa-football-ball,fa-fort-awesome,fa-forumbee,fa-forward,fa-forward-fast,fa-forward-step,fa-foursquare,fa-franc-sign,fa-free-code-camp,fa-frog,fa-frown,fa-frown-open,fa-funnel-dollar,fa-futbol,fa-futbol-ball,fa-g,fa-gamepad,fa-gas-pump,fa-gauge,fa-gauge-high,fa-gauge-simple,fa-gauge-simple-high,fa-gavel,fa-gbp,fa-gear,fa-gears,fa-gem,fa-genderless,fa-get-pocket,fa-gg,fa-gg-circle,fa-ghost,fa-gift,fa-gifts,fa-git,fa-git-square,fa-gitlab,fa-gittip,fa-glass,fa-glass-cheers,fa-glass-martini,fa-glass-martini-alt,fa-glass-whiskey,fa-glasses,fa-glide,fa-globe,fa-globe-africa,fa-globe-americas,fa-globe-asia,fa-globe-europe,fa-globe-oceania,fa-golf-ball,fa-golf-ball-tee,fa-google,fa-google-plus,fa-google-plus-official,fa-google-plus-square,fa-google-wallet,fa-gopuram,fa-graduation-cap,fa-gratipay,fa-grav,fa-greater-than,fa-greater-than-equal,fa-grimace,fa-grin,fa-grin-alt,fa-grin-beam,fa-grin-beam-sweat,fa-grin-hearts,fa-grin-squint,fa-grin-squint-tears,fa-grin-stars,fa-grin-tears,fa-grin-tongue,fa-grin-tongue-squint,fa-grin-tongue-wink,fa-grin-wink,fa-grip,fa-grip-horizontal,fa-grip-lines,fa-grip-lines-vertical,fa-grip-vertical,fa-group,fa-guarani-sign,fa-guitar,fa-gun,fa-h,fa-h-square,fa-hacker-news,fa-hamburger,fa-hammer,fa-hamsa,fa-hand,fa-hand-back-fist,fa-hand-dots,fa-hand-fist,fa-hand-holding,fa-hand-holding-dollar,fa-hand-holding-droplet,fa-hand-holding-heart,fa-hand-holding-medical,fa-hand-holding-usd,fa-hand-holding-water,fa-hand-lizard,fa-hand-middle-finger,fa-hand-paper,fa-hand-peace,fa-hand-point-down,fa-hand-point-left,fa-hand-point-right,fa-hand-point-up,fa-hand-pointer,fa-hand-rock,fa-hand-scissors,fa-hand-sparkles,fa-hand-spock,fa-hands,fa-hands-american-sign-language-interpreting,fa-hands-asl-interpreting,fa-hands-bubbles,fa-hands-clapping,fa-hands-helping,fa-hands-holding,fa-hands-praying,fa-hands-wash,fa-handshake,fa-handshake-alt-slash,fa-handshake-angle,fa-handshake-simple-slash,fa-handshake-slash,fa-hanukiah,fa-hard-drive,fa-hard-hat,fa-hard-of-hearing,fa-hashtag,fa-hat-cowboy,fa-hat-cowboy-side,fa-hat-hard,fa-hat-wizard,fa-hdd,fa-head-side-cough,fa-head-side-cough-slash,fa-head-side-mask,fa-head-side-virus,fa-header,fa-heading,fa-headphones,fa-headphones-alt,fa-headphones-simple,fa-headset,fa-heart,fa-heart-broken,fa-heart-crack,fa-heart-music-camera-bolt,fa-heart-pulse,fa-heartbeat,fa-helicopter,fa-helmet-safety,fa-highlighter,fa-hiking,fa-hippo,fa-history,fa-hockey-puck,fa-holly-berry,fa-home,fa-home-alt,fa-home-lg,fa-home-lg-alt,fa-home-user,fa-horse,fa-horse-head,fa-hospital,fa-hospital-alt,fa-hospital-symbol,fa-hospital-user,fa-hospital-wide,fa-hot-tub,fa-hot-tub-person,fa-hotdog,fa-hotel,fa-hourglass,fa-hourglass-1,fa-hourglass-2,fa-hourglass-3,fa-hourglass-empty,fa-hourglass-end,fa-hourglass-half,fa-hourglass-o,fa-hourglass-start,fa-house,fa-house-chimney,fa-house-chimney-crack,fa-house-chimney-medical,fa-house-chimney-user,fa-house-crack,fa-house-damage,fa-house-laptop,fa-house-medical,fa-house-user,fa-houzz,fa-hryvnia,fa-hryvnia-sign,fa-html5,fa-i,fa-i-cursor,fa-ice-cream,fa-icicles,fa-icons,fa-id-card,fa-id-card-alt,fa-id-card-clip,fa-igloo,fa-ils,fa-image-portrait,fa-images,fa-imdb,fa-inbox,fa-indent,fa-indian-rupee,fa-indian-rupee-sign,fa-industry,fa-infinity,fa-info,fa-info-circle,fa-inr,fa-instagram,fa-institution,fa-internet-explorer,fa-intersex,fa-ioxhost,fa-italic,fa-j,fa-jedi,fa-jet-fighter,fa-joint,fa-joomla,fa-journal-whills,fa-jpy,fa-jsfiddle,fa-k,fa-kaaba,fa-key,fa-keyboard,fa-khanda,fa-kip-sign,fa-kiss,fa-kiss-beam,fa-kiss-wink-heart,fa-kit-medical,fa-kiwi-bird,fa-krw,fa-l,fa-ladder-water,fa-landmark,fa-language,fa-laptop,fa-laptop-code,fa-laptop-house,fa-laptop-medical,fa-lari-sign,fa-lastfm,fa-laugh,fa-laugh-beam,fa-laugh-squint,fa-laugh-wink,fa-layer-group,fa-leaf,fa-leanpub,fa-left-long,fa-left-right,fa-legal,fa-lemon,fa-less-than,fa-less-than-equal,fa-level-down,fa-level-down-alt,fa-level-up,fa-level-up-alt,fa-life-bouy,fa-life-buoy,fa-life-ring,fa-life-saver,fa-lightbulb,fa-line-chart,fa-link,fa-link-slash,fa-linux,fa-lira-sign,fa-list,fa-list-1-2,fa-list-check,fa-list-dots,fa-list-numeric,fa-list-ol,fa-list-squares,fa-list-ul,fa-litecoin-sign,fa-location,fa-location-arrow,fa-location-crosshairs,fa-location-dot,fa-location-pin,fa-lock,fa-lock-open,fa-long-arrow-alt-down,fa-long-arrow-alt-left,fa-long-arrow-alt-right,fa-long-arrow-alt-up,fa-long-arrow-down,fa-long-arrow-left,fa-long-arrow-right,fa-long-arrow-up,fa-low-vision,fa-luggage-cart,fa-lungs,fa-lungs-virus,fa-m,fa-magic,fa-magic-wand-sparkles,fa-magnet,fa-magnifying-glass,fa-magnifying-glass-dollar,fa-magnifying-glass-location,fa-magnifying-glass-minus,fa-magnifying-glass-plus,fa-mail-bulk,fa-mail-forward,fa-mail-reply,fa-mail-reply-all,fa-male,fa-manat-sign,fa-map,fa-map-location,fa-map-location-dot,fa-map-marked,fa-map-marked-alt,fa-map-marker,fa-map-marker-alt,fa-map-pin,fa-map-signs,fa-marker,fa-mars,fa-mars-and-venus,fa-mars-double,fa-mars-stroke,fa-mars-stroke-h,fa-mars-stroke-right,fa-mars-stroke-up,fa-mars-stroke-v,fa-martini-glass,fa-martini-glass-citrus,fa-martini-glass-empty,fa-mask,fa-mask-face,fa-masks-theater,fa-maximize,fa-medal,fa-medium,fa-medkit,fa-meetup,fa-meh,fa-meh-blank,fa-meh-rolling-eyes,fa-memory,fa-menorah,fa-mercury,fa-message,fa-meteor,fa-microchip,fa-microphone,fa-microphone-alt,fa-microphone-alt-slash,fa-microphone-lines,fa-microphone-lines-slash,fa-microphone-slash,fa-microscope,fa-mill-sign,fa-minimize,fa-minus,fa-minus-circle,fa-minus-square,fa-mitten,fa-mixcloud,fa-mobile,fa-mobile-alt,fa-mobile-button,fa-mobile-phone,fa-mobile-screen-button,fa-modx,fa-money,fa-money-bill,fa-money-bill-1,fa-money-bill-1-wave,fa-money-bill-alt,fa-money-bill-wave,fa-money-bill-wave-alt,fa-money-check,fa-money-check-alt,fa-money-check-dollar,fa-monument,fa-moon,fa-mortar-board,fa-mortar-pestle,fa-mosque,fa-motorcycle,fa-mountain,fa-mouse,fa-mouse-pointer,fa-mug-hot,fa-mug-saucer,fa-multiply,fa-music,fa-n,fa-naira-sign,fa-navicon,fa-network-wired,fa-neuter,fa-newspaper,fa-not-equal,fa-note-sticky,fa-notes-medical,fa-o,fa-object-group,fa-object-ungroup,fa-odnoklassniki,fa-odnoklassniki-square,fa-oil-can,fa-om,fa-opencart,fa-openid,fa-opera,fa-otter,fa-outdent,fa-p,fa-pagelines,fa-pager,fa-paint-brush,fa-paint-roller,fa-palette,fa-pallet,fa-panorama,fa-paper-plane,fa-paperclip,fa-parachute-box,fa-paragraph,fa-parking,fa-passport,fa-pastafarianism,fa-paste,fa-pause,fa-pause-circle,fa-paw,fa-peace,fa-pen,fa-pen-alt,fa-pen-clip,fa-pen-fancy,fa-pen-nib,fa-pen-ruler,fa-pen-square,fa-pen-to-square,fa-pencil,fa-pencil-alt,fa-pencil-ruler,fa-pencil-square,fa-people-arrows,fa-people-arrows-left-right,fa-people-carry,fa-people-carry-box,fa-pepper-hot,fa-percent,fa-percentage,fa-person,fa-person-biking,fa-person-booth,fa-person-dots-from-line,fa-person-dress,fa-person-hiking,fa-person-praying,fa-person-running,fa-person-skating,fa-person-skiing,fa-person-skiing-nordic,fa-person-snowboarding,fa-person-swimming,fa-person-walking,fa-person-walking-with-cane,fa-peseta-sign,fa-peso-sign,fa-phone,fa-phone-alt,fa-phone-flip,fa-phone-slash,fa-phone-square,fa-phone-square-alt,fa-phone-volume,fa-photo-film,fa-photo-video,fa-pie-chart,fa-pied-piper,fa-pied-piper-alt,fa-pied-piper-pp,fa-piggy-bank,fa-pills,fa-ping-pong-paddle-ball,fa-pinterest,fa-pinterest-p,fa-pizza-slice,fa-place-of-worship,fa-plane,fa-plane-arrival,fa-plane-departure,fa-plane-slash,fa-play,fa-play-circle,fa-plug,fa-plus,fa-plus-circle,fa-plus-minus,fa-plus-square,fa-podcast,fa-poll,fa-poll-h,fa-poo,fa-poo-bolt,fa-poo-storm,fa-poop,fa-portrait,fa-pound-sign,fa-power-off,fa-pray,fa-praying-hands,fa-prescription,fa-prescription-bottle,fa-prescription-bottle-alt,fa-prescription-bottle-medical,fa-print,fa-procedures,fa-product-hunt,fa-project-diagram,fa-pump-medical,fa-pump-soap,fa-puzzle-piece,fa-q,fa-qq,fa-qrcode,fa-question,fa-question-circle,fa-quidditch,fa-quidditch-broom-ball,fa-quora,fa-quote-left,fa-quote-left-alt,fa-quote-right,fa-quote-right-alt,fa-quran,fa-r,fa-ra,fa-radiation,fa-radiation-alt,fa-rainbow,fa-random,fa-receipt,fa-record-vinyl,fa-rectangle-ad,fa-rectangle-list,fa-rectangle-times,fa-rectangle-xmark,fa-recycle,fa-reddit,fa-reddit-square,fa-redo,fa-redo-alt,fa-refresh,fa-remove,fa-remove-format,fa-renren,fa-reorder,fa-repeat,fa-reply,fa-reply-all,fa-republican,fa-restroom,fa-retweet,fa-ribbon,fa-right-from-bracket,fa-right-left,fa-right-long,fa-right-to-bracket,fa-ring,fa-rmb,fa-road,fa-robot,fa-rocket,fa-rotate,fa-rotate-back,fa-rotate-backward,fa-rotate-forward,fa-rotate-left,fa-rotate-right,fa-rouble,fa-route,fa-rss,fa-rss-square,fa-rub,fa-ruble,fa-ruble-sign,fa-ruler,fa-ruler-combined,fa-ruler-horizontal,fa-ruler-vertical,fa-running,fa-rupee,fa-rupee-sign,fa-rupiah-sign,fa-s,fa-s15,fa-sad-cry,fa-sad-tear,fa-safari,fa-sailboat,fa-satellite,fa-satellite-dish,fa-scale-balanced,fa-scale-unbalanced,fa-scale-unbalanced-flip,fa-school,fa-scissors,fa-screwdriver,fa-screwdriver-wrench,fa-scribd,fa-scroll,fa-scroll-torah,fa-sd-card,fa-search,fa-search-dollar,fa-search-location,fa-search-minus,fa-search-plus,fa-section,fa-seedling,fa-sellsy,fa-send,fa-server,fa-shapes,fa-share,fa-share-alt,fa-share-alt-square,fa-share-from-square,fa-share-nodes,fa-share-square,fa-share-square-o,fa-shekel,fa-shekel-sign,fa-sheqel,fa-sheqel-sign,fa-shield,fa-shield-alt,fa-shield-blank,fa-shield-virus,fa-ship,fa-shipping-fast,fa-shirt,fa-shirtsinbulk,fa-shoe-prints,fa-shop,fa-shop-slash,fa-shopping-bag,fa-shopping-basket,fa-shopping-cart,fa-shower,fa-shrimp,fa-shuffle,fa-shuttle-space,fa-shuttle-van,fa-sign,fa-sign-hanging,fa-sign-in,fa-sign-in-alt,fa-sign-language,fa-sign-out,fa-sign-out-alt,fa-signal,fa-signal-5,fa-signal-perfect,fa-signature,fa-signing,fa-signs-post,fa-sim-card,fa-simplybuilt,fa-sink,fa-sitemap,fa-skating,fa-skiing,fa-skiing-nordic,fa-skull,fa-skull-crossbones,fa-skype,fa-slack,fa-slash,fa-sleigh,fa-sliders,fa-sliders-h,fa-slideshare,fa-smile,fa-smile-beam,fa-smile-wink,fa-smog,fa-smoking,fa-smoking-ban,fa-sms,fa-snapchat,fa-snapchat-ghost,fa-snapchat-square,fa-snowboarding,fa-snowflake,fa-snowman,fa-snowplow,fa-soap,fa-soccer-ball,fa-socks,fa-solar-panel,fa-sort,fa-sort-alpha-asc,fa-sort-alpha-desc,fa-sort-alpha-down,fa-sort-alpha-down-alt,fa-sort-alpha-up,fa-sort-alpha-up-alt,fa-sort-amount-asc,fa-sort-amount-desc,fa-sort-amount-down,fa-sort-amount-down-alt,fa-sort-amount-up,fa-sort-amount-up-alt,fa-sort-asc,fa-sort-desc,fa-sort-down,fa-sort-numeric-asc,fa-sort-numeric-desc,fa-sort-numeric-down,fa-sort-numeric-down-alt,fa-sort-numeric-up,fa-sort-numeric-up-alt,fa-sort-up,fa-soundcloud,fa-spa,fa-space-shuttle,fa-spaghetti-monster-flying,fa-spell-check,fa-spider,fa-spinner,fa-splotch,fa-spoon,fa-spray-can,fa-spray-can-sparkles,fa-sprout,fa-square,fa-square-arrow-up-right,fa-square-caret-down,fa-square-caret-left,fa-square-caret-right,fa-square-caret-up,fa-square-check,fa-square-envelope,fa-square-full,fa-square-h,fa-square-minus,fa-square-parking,fa-square-pen,fa-square-phone,fa-square-phone-flip,fa-square-plus,fa-square-poll-horizontal,fa-square-poll-vertical,fa-square-root-alt,fa-square-root-variable,fa-square-rss,fa-square-share-nodes,fa-square-up-right,fa-square-xmark,fa-stack-exchange,fa-stairs,fa-stamp,fa-star,fa-star-and-crescent,fa-star-half,fa-star-half-alt,fa-star-half-stroke,fa-star-of-david,fa-star-of-life,fa-steam,fa-steam-square,fa-step-backward,fa-step-forward,fa-sterling-sign,fa-stethoscope,fa-sticky-note,fa-stop,fa-stop-circle,fa-stopwatch,fa-stopwatch-20,fa-store,fa-store-alt,fa-store-alt-slash,fa-store-slash,fa-stream,fa-street-view,fa-strikethrough,fa-stroopwafel,fa-stumbleupon,fa-stumbleupon-circle,fa-subscript,fa-subtract,fa-subway,fa-suitcase,fa-suitcase-medical,fa-suitcase-rolling,fa-sun,fa-superpowers,fa-superscript,fa-support,fa-surprise,fa-swatchbook,fa-swimmer,fa-swimming-pool,fa-synagogue,fa-sync,fa-sync-alt,fa-syringe,fa-t,fa-t-shirt,fa-table,fa-table-cells,fa-table-cells-large,fa-table-columns,fa-table-list,fa-table-tennis,fa-table-tennis-paddle-ball,fa-tablet,fa-tablet-alt,fa-tablet-button,fa-tablet-screen-button,fa-tablets,fa-tachograph-digital,fa-tachometer,fa-tachometer-alt,fa-tachometer-alt-fast,fa-tachometer-fast,fa-tag,fa-tags,fa-tape,fa-tasks,fa-tasks-alt,fa-taxi,fa-teeth,fa-teeth-open,fa-teletype,fa-television,fa-temperature-0,fa-temperature-1,fa-temperature-2,fa-temperature-3,fa-temperature-4,fa-temperature-empty,fa-temperature-full,fa-temperature-half,fa-temperature-high,fa-temperature-low,fa-temperature-quarter,fa-temperature-three-quarters,fa-tencent-weibo,fa-tenge,fa-tenge-sign,fa-terminal,fa-text-height,fa-text-slash,fa-text-width,fa-th,fa-th-large,fa-th-list,fa-theater-masks,fa-themeisle,fa-thermometer,fa-thermometer-0,fa-thermometer-1,fa-thermometer-2,fa-thermometer-3,fa-thermometer-4,fa-thermometer-empty,fa-thermometer-full,fa-thermometer-half,fa-thermometer-quarter,fa-thermometer-three-quarters,fa-thumb-tack,fa-thumbs-down,fa-thumbs-up,fa-thumbtack,fa-ticket,fa-ticket-alt,fa-ticket-simple,fa-timeline,fa-times,fa-times-circle,fa-times-rectangle,fa-times-square,fa-tint,fa-tint-slash,fa-tired,fa-toggle-off,fa-toggle-on,fa-toilet,fa-toilet-paper,fa-toilet-paper-slash,fa-toolbox,fa-tools,fa-tooth,fa-torah,fa-torii-gate,fa-tower-broadcast,fa-tractor,fa-trademark,fa-traffic-light,fa-trailer,fa-train,fa-train-subway,fa-train-tram,fa-tram,fa-transgender,fa-transgender-alt,fa-trash,fa-trash-alt,fa-trash-arrow-up,fa-trash-can,fa-trash-can-arrow-up,fa-trash-restore,fa-trash-restore-alt,fa-tree,fa-trello,fa-triangle-circle-square,fa-triangle-exclamation,fa-trophy,fa-truck,fa-truck-fast,fa-truck-loading,fa-truck-medical,fa-truck-monster,fa-truck-moving,fa-truck-pickup,fa-truck-ramp-box,fa-try,fa-tshirt,fa-tty,fa-tumblr,fa-turkish-lira,fa-turkish-lira-sign,fa-turn-down,fa-turn-up,fa-tv,fa-tv-alt,fa-twitch,fa-u,fa-umbrella,fa-umbrella-beach,fa-underline,fa-undo,fa-undo-alt,fa-universal-access,fa-university,fa-unlink,fa-unlock,fa-unlock-alt,fa-unlock-keyhole,fa-unsorted,fa-up-down,fa-up-down-left-right,fa-up-long,fa-up-right-and-down-left-from-center,fa-up-right-from-square,fa-upload,fa-usd,fa-user,fa-user-alt,fa-user-alt-slash,fa-user-astronaut,fa-user-check,fa-user-circle,fa-user-clock,fa-user-cog,fa-user-doctor,fa-user-edit,fa-user-friends,fa-user-gear,fa-user-graduate,fa-user-group,fa-user-injured,fa-user-large,fa-user-large-slash,fa-user-lock,fa-user-md,fa-user-minus,fa-user-ninja,fa-user-nurse,fa-user-pen,fa-user-plus,fa-user-secret,fa-user-shield,fa-user-slash,fa-user-tag,fa-user-tie,fa-user-times,fa-user-xmark,fa-users,fa-users-cog,fa-users-gear,fa-users-slash,fa-utensil-spoon,fa-utensils,fa-v,fa-van-shuttle,fa-vault,fa-vcard,fa-vector-square,fa-venus,fa-venus-double,fa-venus-mars,fa-vest,fa-vest-patches,fa-viacoin,fa-viadeo,fa-vial,fa-vials,fa-video,fa-video-camera,fa-video-slash,fa-vihara,fa-virus,fa-virus-slash,fa-viruses,fa-vk,fa-voicemail,fa-volleyball,fa-volleyball-ball,fa-volume-control-phone,fa-volume-down,fa-volume-high,fa-volume-low,fa-volume-mute,fa-volume-off,fa-volume-times,fa-volume-up,fa-volume-xmark,fa-vote-yea,fa-vr-cardboard,fa-w,fa-walking,fa-wallet,fa-wand-magic,fa-wand-magic-sparkles,fa-warehouse,fa-warning,fa-water,fa-water-ladder,fa-wave-square,fa-wechat,fa-weight,fa-weight-hanging,fa-weight-scale,fa-wheelchair,fa-wheelchair-alt,fa-whiskey-glass,fa-wifi,fa-wifi-3,fa-wifi-strong,fa-wind,fa-window-close,fa-window-maximize,fa-window-minimize,fa-wine-bottle,fa-wine-glass,fa-wine-glass-alt,fa-wine-glass-empty,fa-won,fa-won-sign,fa-wpbeginner,fa-wrench,fa-x,fa-x-ray,fa-xing,fa-xing-square,fa-xmark,fa-xmark-circle,fa-xmark-square,fa-y,fa-y-combinator,fa-yen,fa-yen-sign,fa-yin-yang,fa-youtube,fa-youtube-play,fa-z,fa-zap';
    return explode(',',$list);
}



?>
