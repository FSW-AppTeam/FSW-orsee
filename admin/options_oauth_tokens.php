<?php
// part of orsee. see orsee.org
ob_start();

$title="configure_oauth_tokens";
$menu__area="options_main";
include ("header.php");
if ($proceed) {
    $allow=check_allow('settings_oauth_edit','options_main.php');
}

if ($proceed) {
    $allow_edit=$allow;
    $identity_map=experimentmail__oauth_identity_map();
    if (!is_array($identity_map) || count($identity_map)<1) {
        $identity_map=array("*"=>experimentmail__oauth_normalize_identity_config("*",array()));
    }
    $identity_keys=array_keys($identity_map);
    sort($identity_keys);
    $selected_identity_key=(isset($_REQUEST['identity_key']) ? trim((string)$_REQUEST['identity_key']) : "");
    if ($selected_identity_key==="" && isset($_REQUEST['state']) && is_string($_REQUEST['state'])) {
        $state_in=trim((string)$_REQUEST['state']);
        if (strpos($state_in,'orsee:')===0) {
            $payload_b64=substr($state_in,6);
            $payload_raw=base64_decode(strtr($payload_b64,'-_','+/'));
            $payload=json_decode((string)$payload_raw,true);
            if (is_array($payload) && isset($payload['k']) && is_string($payload['k'])) {
                $selected_identity_key=trim($payload['k']);
            }
        }
    }
    if ($selected_identity_key==="" || !isset($identity_map[$selected_identity_key])) {
        $selected_identity_key=$identity_keys[0];
    }
    $oauth_config=$identity_map[$selected_identity_key];
    $default_redirect_uri=$settings__root_url."/admin/options_oauth_tokens.php";
    $redirect_uri=$default_redirect_uri;
    $state_token=experimentmail__oauth_default_state();
    $state_payload=array('k'=>$selected_identity_key,'t'=>$state_token);
    $state="orsee:".rtrim(strtr(base64_encode(json_encode($state_payload)),'+/','-_'),'=');
    $authorization_url="";
    $token_response_json="";
    $stored_token=experimentmail__oauth_tokens__load('smtp_send',$oauth_config['identity'],$oauth_config['provider']);
    $has_config_refresh_token=(isset($oauth_config['refresh_token']) && trim((string)$oauth_config['refresh_token'])!=="");
    $has_stored_refresh_token=(is_array($stored_token) && isset($stored_token['refresh_token']) && trim((string)$stored_token['refresh_token'])!=="");
    $has_refresh_token_available=($has_config_refresh_token || $has_stored_refresh_token);
    $oauth_missing_fields=array();
    if (!isset($oauth_config['identity']) || trim((string)$oauth_config['identity'])==="") {
        $oauth_missing_fields[]='identity';
    }
    if (!isset($oauth_config['client_id']) || trim((string)$oauth_config['client_id'])==="") {
        $oauth_missing_fields[]='client_id';
    }
    if (!isset($oauth_config['client_secret']) || trim((string)$oauth_config['client_secret'])==="") {
        $oauth_missing_fields[]='client_secret';
    }

    if ($allow_edit && isset($_REQUEST['do_action']) && $_REQUEST['do_action']=="generate_url") {
        try {
            $authorization_url=experimentmail__oauth_authorization_url($oauth_config,$redirect_uri,$state);
            if (!headers_sent()) {
                header("Location: ".$authorization_url);
                exit;
            }
            message(lang('oauth_msg_url_generated_no_redirect'),"error_message");
        } catch (Exception $e) {
            message(lang('oauth_msg_url_generation_failed').": ".$e->getMessage(),"error_message");
        }
    }

    if ($allow_edit && isset($_REQUEST['do_action']) && $_REQUEST['do_action']=="exchange_code") {
        $authorization_code=(isset($_REQUEST['authorization_code']) ? trim((string)$_REQUEST['authorization_code']) : "");
        if ($authorization_code==="") {
            message(lang('oauth_msg_please_provide_authorization_code'),"error_message");
        } else {
            try {
                $token_response=experimentmail__oauth_exchange_authorization_code($oauth_config,$redirect_uri,$authorization_code);
                $saved=experimentmail__oauth_store_token_response($oauth_config,$token_response,'smtp_send');
                $token_response_json=json_encode($token_response,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                if (!$saved) {
                    message(lang('oauth_msg_token_exchange_storing_failed'),"error_message");
                } else {
                    message(lang('oauth_msg_token_stored_for_identity')." ".$oauth_config['identity'].".");
                }
            } catch (Exception $e) {
                message(lang('oauth_msg_code_exchange_failed').": ".$e->getMessage(),"error_message");
            }
        }
    }

    if ($allow_edit && (!isset($_REQUEST['do_action']) || $_REQUEST['do_action']!=="exchange_code")
        && isset($_REQUEST['code']) && trim((string)$_REQUEST['code'])!=="") {
        $authorization_code=trim((string)$_REQUEST['code']);
        try {
            $token_response=experimentmail__oauth_exchange_authorization_code($oauth_config,$redirect_uri,$authorization_code);
            $saved=experimentmail__oauth_store_token_response($oauth_config,$token_response,'smtp_send');
            $token_response_json=json_encode($token_response,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if (!$saved) {
                message(lang('oauth_msg_auto_token_exchange_storing_failed'),"error_message");
            } else {
                message(lang('oauth_msg_auto_token_stored_for_identity')." ".$oauth_config['identity'].".");
            }
        } catch (Exception $e) {
            message(lang('oauth_msg_auto_code_exchange_failed').": ".$e->getMessage(),"error_message");
        }
    } elseif (isset($_REQUEST['error']) && trim((string)$_REQUEST['error'])!=="") {
        $oauth_error=trim((string)$_REQUEST['error']);
        $oauth_error_desc=(isset($_REQUEST['error_description']) ? trim((string)$_REQUEST['error_description']) : "");
        if ($oauth_error_desc!=="") {
            message(lang('oauth_msg_provider_returned_error').": ".$oauth_error." (".$oauth_error_desc.")","error_message");
        } else {
            message(lang('oauth_msg_provider_returned_error').": ".$oauth_error,"error_message");
        }
    }

    if ($allow_edit && isset($_REQUEST['do_action']) && $_REQUEST['do_action']=="refresh_test") {
        if (!$has_refresh_token_available) {
            message(lang('oauth_msg_no_refresh_token_available'),"error_message");
        } else {
            try {
                $access_token=experimentmail__oauth_get_valid_access_token($oauth_config,'smtp_send');
                if ($access_token && strlen($access_token)>10) {
                    message(lang('oauth_msg_refresh_test_succeeded'));
                } else {
                    message(lang('oauth_msg_refresh_test_failed_no_access_token'),"error_message");
                }
            } catch (Exception $e) {
                message(lang('oauth_msg_refresh_test_failed').": ".$e->getMessage(),"error_message");
            }
        }
        $stored_token=experimentmail__oauth_tokens__load('smtp_send',$oauth_config['identity'],$oauth_config['provider']);
        $has_stored_refresh_token=(is_array($stored_token) && isset($stored_token['refresh_token']) && trim((string)$stored_token['refresh_token'])!=="");
        $has_refresh_token_available=($has_config_refresh_token || $has_stored_refresh_token);
    }
}

if ($proceed) {
    echo '<center>';
    show_message();

    echo '<TABLE class="or_formtable" style="width: 90%; max-width: 1100px;">
            ';

    echo '<TR><TD colspan="2">
            This page uses OAuth identity definitions from <code>config/settings.php</code> (<code>$settings__phpmailer_smtp_oauth_identities</code>).
            You can authenticate the selected identity here with the provider.
            Tokens are currently stored for SMTP sending (<code>purpose=smtp_send</code>).
          </TD></TR>';

    echo '<TR><TD colspan="2"><hr></TD></TR>';
    echo '<TR><TD colspan="2"><B>Step 1: Generate authorization URL (skip if you already have an authorization code)</B></TD></TR>';

    echo '<FORM action="options_oauth_tokens.php" method="post">';
    echo '<INPUT type="hidden" name="do_action" value="generate_url">';
    echo '<TR><TD>Configured identity</TD><TD>';
    echo '<SELECT name="identity_key">';
    foreach ($identity_keys as $k) {
        echo '<OPTION value="'.htmlspecialchars($k).'"';
        if ($k===$selected_identity_key) {
            echo ' selected';
        }
        echo '>'.htmlspecialchars($k).'</OPTION>';
    }
    echo '</SELECT>';
    echo '</TD></TR>';

    echo '<TR><TD>Provider</TD><TD>'.htmlspecialchars((string)$oauth_config['provider']).'</TD></TR>';
    echo '<TR><TD>OAuth identity</TD><TD>'.htmlspecialchars((string)$oauth_config['identity']).'</TD></TR>';
    echo '<TR><TD>Token endpoint</TD><TD>'.htmlspecialchars((string)$oauth_config['token_endpoint']).'</TD></TR>';
    echo '<TR><TD>Scopes</TD><TD>'.htmlspecialchars((string)$oauth_config['scopes']).'</TD></TR>';
    echo '<TR><TD>Redirect URI</TD><TD>'.htmlspecialchars($redirect_uri).'</TD></TR>';
    echo '<TR><TD>State</TD><TD>'.htmlspecialchars($state).'</TD></TR>';
    if (count($oauth_missing_fields)>0) {
        echo '<TR><TD>Configuration warning</TD><TD><FONT color="red">This identity entry in config/settings.php is incomplete. Missing: '.htmlspecialchars(implode(", ",$oauth_missing_fields)).'.</FONT></TD></TR>';
    }
    echo '<TR><TD></TD><TD><INPUT class="button" type="submit" value="'.lang('oauth_authenticate_with_provider').'"';
    if (count($oauth_missing_fields)>0) {
        echo ' disabled';
    }
    echo '></TD></TR>';
    echo '</FORM>';

    if ($authorization_url!=="") {
        echo '<TR><TD>Authorization URL</TD><TD><TEXTAREA rows="5" cols="110" readonly>'.htmlspecialchars($authorization_url).'</TEXTAREA></TD></TR>';
    }

    echo '<TR><TD colspan="2"><hr></TD></TR>';
    echo '<TR><TD colspan="2">';
    echo '<details>';
    echo '<summary><B>Advanced: Manual code exchange (fallback)</B></summary>';
    echo '<BR>';
    echo '<FORM action="options_oauth_tokens.php" method="post">';
    echo '<INPUT type="hidden" name="do_action" value="exchange_code">';
    echo '<INPUT type="hidden" name="identity_key" value="'.htmlspecialchars($selected_identity_key).'">';
    echo '<TABLE width="100%">';
    echo '<TR><TD width="220">Authorization code</TD><TD><TEXTAREA name="authorization_code" rows="4" cols="100">';
    if (isset($_REQUEST['code']) && trim((string)$_REQUEST['code'])!=="") {
        echo htmlspecialchars(trim((string)$_REQUEST['code']));
    }
    echo '</TEXTAREA></TD></TR>';
    echo '<TR><TD></TD><TD><INPUT class="button" type="submit" value="'.lang('oauth_exchange_code').'"></TD></TR>';
    echo '</TABLE>';
    echo '</FORM>';
    echo '</details>';
    echo '</TD></TR>';

    echo '<TR><TD colspan="2"><hr></TD></TR>';

    echo '<FORM action="options_oauth_tokens.php" method="post">';
    echo '<INPUT type="hidden" name="do_action" value="refresh_test">';
    echo '<INPUT type="hidden" name="identity_key" value="'.htmlspecialchars($selected_identity_key).'">';
    echo '<TR><TD>Test current OAuth refresh token</TD><TD>';
    echo 'Use this to verify that ORSEE can refresh and get a valid access token for the selected identity.<BR>';
    echo 'If the test fails, you need to reauthorize to obtain a new OAuth refresh token.<BR><BR>';
    if (is_array($stored_token)) {
        $exp=(isset($stored_token['access_token_expires_at']) ? (int)$stored_token['access_token_expires_at'] : 0);
        echo 'Refresh token: '.((isset($stored_token['refresh_token']) && trim((string)$stored_token['refresh_token'])!=="") ? 'yes' : 'no').'<BR>';
        echo 'Access token cached: '.((isset($stored_token['access_token']) && trim((string)$stored_token['access_token'])!=="") ? 'yes' : 'no').'<BR>';
        echo 'Access token expires: '.($exp>0 ? ortime__format($exp) : 'n/a');
    } else {
        echo 'No token currently stored for this identity.';
    }
    echo '</TD></TR>';
    echo '<TR><TD></TD><TD><INPUT class="button" type="submit" value="'.lang('oauth_test_current_refresh_token').'"';
    if (!$has_refresh_token_available) {
        echo ' disabled';
    }
    echo '></TD></TR>';
    echo '</FORM>';

    echo '</TABLE>';
    echo '<BR><BR><A href="options_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>';
    echo '</center>';

    if (!$allow_edit) {
        echo '<script type="text/javascript">
                $(".button").attr("disabled", true);
                $(":input").attr("disabled", true);
              </script>';
    }
}

include ("footer.php");
?>
