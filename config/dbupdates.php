<?php
// part of orsee. see orsee.org
// THIS FILE WILL CHANGE FROM VERSION TO VERSION. BETTER NOT EDIT.


// DATABASE UPGRADE DEFINITIONS //
// add entries to array $system__database_upgrades


/* SAMPLE CODE FOR UPGRADES

$system__database_upgrades[]=array(
'version'=>'2020021000', // *database version from which on this is expected
'type'=>'new_lang_item', // *can be: new_lang_item, new_admin_right, query
'specs'=> array(
    'content_name'=>'', // *for new_lang_item: shortcut for item
    'content_type'=>'', // for new_lang_item: type for item, default: lang
    'content'=>array('en'=>'','de'=>'')    // *for new_lang_item: one expression for each language, first one is taked as default and filled in for non-existing languages
    )
);

$system__database_upgrades[]=array(
'version'=>'2020021000', // *database version from which on this is expected
'type'=>'new_admin_right', // *can be: new_lang_item, new_admin_right, query
'specs'=> array(
    'right_name'=>'', // *for new_admin_right: shortcut for admin right
    'admin_types'=>array('admin','experimenter')    // *for new_admin_right: list of admin types for which this right should be set (if not exists yet)
    )
);

$system__database_upgrades[]=array(
'version'=>'2020021000', // *database version from which on this is expected
'type'=>'query', // *can be: new_lang_item, new_admin_right, query
'specs'=> array(
    'query_code'=>'' // *for query: SQL statement to be executed. You can use "TABLE(tablename)" to have "or_" or the respective ORSEE table rpefix automatically prepended
    )
);

END SAMPLE CODE
*/

$system__database_upgrades[]=array(
'version'=>'2020022400',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'query_interface_language',
    'content_type'=>'lang',
    'content'=>array('en'=>'Interface language ...','de'=>'Interface-Sprache ...')
    )
);

$system__database_upgrades[]=array(
'version'=>'2020022400',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'where_interface_language_is',
    'content_type'=>'lang',
    'content'=>array('en'=>'where the interface language is','de'=>'wo die Interface-Sprache ist')
    )
);


$system__database_upgrades[]=array(
'version'=>'2020022500', 
'type'=>'new_admin_right',
'specs'=> array(
    'right_name'=>'participants_bulk_anonymization', 
    'admin_types'=>array('admin','developer','installer')
    )
);

$system__database_upgrades[]=array(
'version'=>'2020022500', 
'type'=>'new_admin_right',
'specs'=> array(
    'right_name'=>'pform_anonymization_fields_edit', 
    'admin_types'=>array('admin','developer','installer')
    )
);

$system__database_upgrades[]=array(
'version'=>'2020022500',
'type'=>'new_lang_item', 
'specs'=> array(
    'content_name'=>'fields_to_anonymize_in_anonymization_bulk_action', 
    'content_type'=>'lang',
    'content'=>array('en'=>'Fields to anonymize in anonymization bulk action','de'=>'Zu setzende Felder bei Profil-Anonymisierung')
    )
);

$system__database_upgrades[]=array(
'version'=>'2020022500',
'type'=>'new_lang_item', 
'specs'=> array(
    'content_name'=>'anonymized_dummy_value', 
    'content_type'=>'lang',
    'content'=>array('en'=>'Anonymized dummy value','de'=>'Zu setzender Dummy-Wert')
    )
);

$system__database_upgrades[]=array(
'version'=>'2020022500',
'type'=>'new_lang_item', 
'specs'=> array(
    'content_name'=>'anonymize_profiles', 
    'content_type'=>'lang',
    'content'=>array('en'=>'Anonymize profiles','de'=>'Anonymisiere Profile')
    )
);

$system__database_upgrades[]=array(
'version'=>'2020022500',
'type'=>'new_lang_item', 
'specs'=> array(
    'content_name'=>'anonymize_profiles_for', 
    'content_type'=>'lang',
    'content'=>array('en'=>'Anonymize profiles for','de'=>'Anonymisiere Profile für')
    )
);

$system__database_upgrades[]=array(
'version'=>'2020022500',
'type'=>'new_lang_item', 
'specs'=> array(
    'content_name'=>'fields_will_be_anonymized_as_follows', 
    'content_type'=>'lang',
    'content'=>array('en'=>'Fields will be anonymized as follows','de'=>'Felder werden wie folgt anonymisiert')
    )
);

$system__database_upgrades[]=array(
'version'=>'2020022500',
'type'=>'new_lang_item', 
'specs'=> array(
    'content_name'=>'disclaimer_anonymize_profiles', 
    'content_type'=>'lang',
    'content'=>array('en'=>'<font color="red">Careful! This procedure is irreversible. Anonymized profiles cannot be recovered.</font>','de'=>'<font color="red">Vorsicht! Diese Aktion kann nicht rückgängig gemacht werden. Anonymiserte Profile können nicht wiederhergestellt werden.</font>')
    )
);

$system__database_upgrades[]=array(
'version'=>'2020022500',
'type'=>'new_lang_item', 
'specs'=> array(
    'content_name'=>'upon_anonymization_change_status_to', 
    'content_type'=>'lang',
    'content'=>array('en'=>'Upon anonymization of the profile, change participant status to','de'=>'Nach der Anonymisierung, ändere Teilnehmer-Status zu')
    )
);

$system__database_upgrades[]=array(
    'version'=>'2020022500',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'profile_anonymize', 
        'content_type'=>'lang',
        'content'=>array('en'=>'Anonymize profiles','de'=>'Anonymisiere Profile')
    )
);

$system__database_upgrades[]=array(
    'version'=>'2020022500',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'error_no_fields_to_anonymize_defined', 
        'content_type'=>'lang',
        'content'=>array('en'=>'Error! There is no definition of fields to anonymize. See ORSEE options.','de'=>'Fehler! Es wurden keine Felder zur Anonymiserung definiert. Siehe ORSEE Optionen.')
    )
);

$system__database_upgrades[]=array(
    'version'=>'2020022500',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'xxx_participant_profiles_were_anonymized', 
        'content_type'=>'lang',
        'content'=>array('en'=>'participant profiles were anonymized.','de'=>'Teilnehmer-Profile wurden anonymisiert.')
    )
);

$system__database_upgrades[]=array(
    'version'=>'2020022600',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'hide_column_for_admin_types', 
        'content_type'=>'lang',
        'content'=>array('en'=>'Hide this column for admin types','de'=>'Diese Spalte verbergen für Admin-Typen')
    )
);

$system__database_upgrades[]=array(
    'version'=>'2020022600',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'enter_comma_separated_list_of_any_of', 
        'content_type'=>'lang',
        'content'=>array('en'=>'Enter comma seperated list of any of these types:','de'=>'Geben Sie eine komma-separierte Liste aus folgenden Typen ein:')
    )
);

$system__database_upgrades[]=array(
    'version'=>'2020022600',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'hidden_data_symbol', 
        'content_type'=>'lang',
        'content'=>array('en'=>'***','de'=>'***')
    )
);

 $system__database_upgrades[]=array(
    'version'=>'2020022700',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'bulk_updated_session_statuses', 
        'content_type'=>'lang',
        'content'=>array('en'=>'Bulk-updated status of selected sessions.','de'=>'Session-Status der gewählten Sessions geändert.')
    )
);

$system__database_upgrades[]=array(
    'version'=>'2020022700',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'set_session_status_for_selected_sessions_to', 
        'content_type'=>'lang',
        'content'=>array('en'=>'Set session status of selected sessions to:','de'=>'Setze Session-Status der selektierten Session auf:')
   )
);

$system__database_upgrades[]=array(
    'version'=>'2020022800',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'download_as', 
        'content_type'=>'lang',
        'content'=>array('en'=>'DOWNLOAD AS','de'=>'HERUNTERLADEN ALS')
    )
);
  

$system__database_upgrades[]=array(
    'version'=>'2020022800',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'pdf_file', 
        'content_type'=>'lang',
        'content'=>array('en'=>'PDF','de'=>'PDF')
    )
);

$system__database_upgrades[]=array(
    'version'=>'2020022800',
    'type'=>'new_lang_item', 
    'specs'=> array(
        'content_name'=>'csv_file', 
        'content_type'=>'lang',
        'content'=>array('en'=>'CSV','de'=>'CSV')
    )
);

$system__database_upgrades[]=array(
'version'=>'2024031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'mobile_invitations',
    'content_type'=>'lang',
    'content'=>array('en'=>'Invitations','de'=>'Einladungen')
    )
);

$system__database_upgrades[]=array(
'version'=>'2024031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'mobile_enrolments',
    'content_type'=>'lang',
    'content'=>array('en'=>'Enrollments','de'=>'Anmeldungen')
    )
);

$system__database_upgrades[]=array(
'version'=>'2024031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'mobile_history',
    'content_type'=>'lang',
    'content'=>array('en'=>'History','de'=>'Teilnahmen')
    )
);

$system__database_upgrades[]=array(
'version'=>'2024031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'mobile_session_details',
    'content_type'=>'lang',
    'content'=>array('en'=>'Experiment details','de'=>'Experiment-Details')
    )
);

$system__database_upgrades[]=array(
'version'=>'2024031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'mobile_you_can_enroll_for',
    'content_type'=>'lang',
    'content'=>array('en'=>'You can enroll for:','de'=>'Sie können sich anmelden für:')
    )
);

$system__database_upgrades[]=array(
'version'=>'2024031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'mobile_you_are_enrolled_for',
    'content_type'=>'lang',
    'content'=>array('en'=>'You are enrolled for:','de'=>'Sie sind angemeldet für:')
    )
);

$system__database_upgrades[]=array(
'version'=>'2024031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'mobile_sign_up',
    'content_type'=>'lang',
    'content'=>array('en'=>'Sign up','de'=>'Anmelden')
    )
);

$system__database_upgrades[]=array(
'version'=>'2024031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'mobile_cancel_signup',
    'content_type'=>'lang',
    'content'=>array('en'=>'Cancel signup','de'=>'Abmelden')
    )
);

$system__database_upgrades[]=array(
'version'=>'2024031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'mobile_confirmation',
    'content_type'=>'lang',
    'content'=>array('en'=>'Confirmation','de'=>'Bestätigung')
    )
);

$system__database_upgrades[]=array(
'version'=>'2024031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'mobile_do_you_really_want_to_signup',
    'content_type'=>'lang',
    'content'=>array('en'=>'Do you really want to sign up?','de'=>'Wollen Sie sich wirklich anmelden?')
    )
);

$system__database_upgrades[]=array(
'version'=>'2024031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'mobile_do_you_really_want_to_cancel_signup',
    'content_type'=>'lang',
    'content'=>array('en'=>'Do you really want to cancel your signup?','de'=>'Wollen Sie sich wirklich abmelden?')
    )
);

$system__database_upgrades[]=array(
'version'=>'2024031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'mobile_sorry_no',
    'content_type'=>'lang',
    'content'=>array('en'=>'Sorry, no.','de'=>'Sorry, nein.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2024031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'mobile_yes_please',
    'content_type'=>'lang',
    'content'=>array('en'=>'Yes, please!','de'=>'Ja bitte!')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031100',
'type'=>'query',
'specs'=> array(
    'query_code'=>"CREATE TABLE IF NOT EXISTS TABLE(oauth_tokens) (
        token_id int(20) NOT NULL AUTO_INCREMENT,
        purpose varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
        identity_email varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
        provider varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
        refresh_token mediumtext COLLATE utf8_unicode_ci,
        access_token mediumtext COLLATE utf8_unicode_ci,
        access_token_expires_at int(20) NOT NULL DEFAULT '0',
        created_at int(20) NOT NULL DEFAULT '0',
        updated_at int(20) NOT NULL DEFAULT '0',
        PRIMARY KEY (token_id),
        UNIQUE KEY identity_purpose_provider (purpose,identity_email,provider)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'configure_oauth_tokens',
    'content_type'=>'lang',
    'content'=>array('en'=>'Configure OAuth Tokens','de'=>'OAuth-Tokens konfigurieren')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'oauth_authenticate_with_provider',
    'content_type'=>'lang',
    'content'=>array('en'=>'Authenticate With Provider','de'=>'Mit Provider authentifizieren')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'oauth_exchange_code',
    'content_type'=>'lang',
    'content'=>array('en'=>'Exchange Authorization Code','de'=>'Autorisierungscode austauschen')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'oauth_test_current_refresh_token',
    'content_type'=>'lang',
    'content'=>array('en'=>'Test current OAuth refresh token','de'=>'Aktuelles OAuth-Refresh-Token testen')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'oauth_msg_url_generated_no_redirect',
    'content_type'=>'lang',
    'content'=>array('en'=>'Authorization URL generated, but automatic redirect was not possible. Please open the URL below.','de'=>'Autorisierungs-URL wurde erstellt, aber die automatische Weiterleitung war nicht möglich. Bitte öffnen Sie die URL unten.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'oauth_msg_url_generation_failed',
    'content_type'=>'lang',
    'content'=>array('en'=>'OAuth URL generation failed','de'=>'Erstellung der OAuth-URL fehlgeschlagen')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'oauth_msg_please_provide_authorization_code',
    'content_type'=>'lang',
    'content'=>array('en'=>'Please provide an authorization code.','de'=>'Bitte geben Sie einen Autorisierungscode ein.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'oauth_msg_token_exchange_storing_failed',
    'content_type'=>'lang',
    'content'=>array('en'=>'Token exchange succeeded, but storing token failed.','de'=>'Token-Austausch war erfolgreich, aber das Speichern des Tokens ist fehlgeschlagen.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'oauth_msg_token_stored_for_identity',
    'content_type'=>'lang',
    'content'=>array('en'=>'OAuth token stored for identity','de'=>'OAuth-Token gespeichert für Identität')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'oauth_msg_code_exchange_failed',
    'content_type'=>'lang',
    'content'=>array('en'=>'OAuth code exchange failed','de'=>'OAuth-Code-Austausch fehlgeschlagen')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'oauth_msg_auto_token_exchange_storing_failed',
    'content_type'=>'lang',
    'content'=>array('en'=>'Automatic token exchange succeeded, but storing token failed.','de'=>'Automatischer Token-Austausch war erfolgreich, aber das Speichern des Tokens ist fehlgeschlagen.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'oauth_msg_auto_token_stored_for_identity',
    'content_type'=>'lang',
    'content'=>array('en'=>'OAuth token stored automatically for identity','de'=>'OAuth-Token automatisch gespeichert für Identität')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'oauth_msg_auto_code_exchange_failed',
    'content_type'=>'lang',
    'content'=>array('en'=>'Automatic OAuth code exchange failed','de'=>'Automatischer OAuth-Code-Austausch fehlgeschlagen')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'oauth_msg_provider_returned_error',
    'content_type'=>'lang',
    'content'=>array('en'=>'OAuth provider returned error','de'=>'OAuth-Provider hat einen Fehler zurückgegeben')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'oauth_msg_no_refresh_token_available',
    'content_type'=>'lang',
    'content'=>array('en'=>'No OAuth refresh token is available for the selected identity. Please authenticate with provider first.','de'=>'Für die ausgewählte Identität ist kein OAuth-Refresh-Token verfügbar. Bitte authentifizieren Sie sich zuerst beim Provider.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'oauth_msg_refresh_test_succeeded',
    'content_type'=>'lang',
    'content'=>array('en'=>'Refresh test succeeded. Access token received.','de'=>'Refresh-Test erfolgreich. Access-Token erhalten.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'oauth_msg_refresh_test_failed_no_access_token',
    'content_type'=>'lang',
    'content'=>array('en'=>'Refresh test failed: no access token returned.','de'=>'Refresh-Test fehlgeschlagen: kein Access-Token zurückgegeben.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031100',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'oauth_msg_refresh_test_failed',
    'content_type'=>'lang',
    'content'=>array('en'=>'Refresh test failed','de'=>'Refresh-Test fehlgeschlagen')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031100',
'type'=>'new_admin_right',
'specs'=> array(
    'right_name'=>'settings_oauth_edit',
    'admin_types'=>array('admin','developer','installer')
    )
);

?>
