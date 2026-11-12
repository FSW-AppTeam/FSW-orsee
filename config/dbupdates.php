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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
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

$system__database_upgrades[]=array(
'version'=>'2026031701',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'message_label_error',
    'content_type'=>'lang',
    'content'=>array('en'=>'Error','de'=>'Fehler')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031701',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'message_label_warning',
    'content_type'=>'lang',
    'content'=>array('en'=>'Warning','de'=>'Warnung')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026031701',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'message_label_note',
    'content_type'=>'lang',
    'content'=>array('en'=>'Note','de'=>'Hinweis')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'error_you_have_to_provide_budget_name',
    'content_type'=>'lang',
    'content'=>array('en'=>'You have to provide a budget name.','de'=>'Sie müssen einen Budget-Namen angeben.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'size',
    'content_type'=>'lang',
    'content'=>array('en'=>'Size','de'=>'Größe')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'cases',
    'content_type'=>'lang',
    'content'=>array('en'=>'Cases','de'=>'Fälle')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'legacy_template',
    'content_type'=>'lang',
    'content'=>array('en'=>'Legacy template','de'=>'Altes HTML-Template')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'structured_layout',
    'content_type'=>'lang',
    'content'=>array('en'=>'Structured layout','de'=>'Strukturiertes Layout')
    )
);


$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'edit_layout',
    'content_type'=>'lang',
    'content'=>array('en'=>'Edit layout','de'=>'Layout bearbeiten')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'activate_layout',
    'content_type'=>'lang',
    'content'=>array('en'=>'Activate layout','de'=>'Layout aktivieren')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'activate_new_layout',
    'content_type'=>'lang',
    'content'=>array('en'=>'Activate new layout','de'=>'Neues Layout aktivieren')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'participant_form_layout_current',
    'content_type'=>'lang',
    'content'=>array('en'=>'Current layout','de'=>'Derzeitiges Layout')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'participant_form_layout_new',
    'content_type'=>'lang',
    'content'=>array('en'=>'New layout','de'=>'Neues Layout')
    )
);


$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'query_who_have_subscribed_to_experiment_types',
    'content_type'=>'lang',
    'content'=>array('en'=>'who have subscribed to experiment types','de'=>'die angemeldet sind für Experimenttypen')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'query_subscriptions',
    'content_type'=>'lang',
    'content'=>array('en'=>'Subscriptions ...','de'=>'Experimenttypen ...')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'layout_label',
    'content_type'=>'lang',
    'content'=>array('en'=>'Label','de'=>'Bezeichnung')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'layout_text_before',
    'content_type'=>'lang',
    'content'=>array('en'=>'Text before','de'=>'Text davor')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'layout_text_after',
    'content_type'=>'lang',
    'content'=>array('en'=>'Text after','de'=>'Text danach')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'layout_help_text',
    'content_type'=>'lang',
    'content'=>array('en'=>'Help text','de'=>'Hilfe-Text')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'error_phone_invalid',
    'content_type'=>'lang',
    'content'=>array('en'=>'Phone number is invalid.','de'=>'Telefonnummer ist ungültig.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'error_phone_invalid_country_code',
    'content_type'=>'lang',
    'content'=>array('en'=>'Phone number has invalid country code.','de'=>'Telefonnummer hat ungültigen Ländercode.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'error_phone_too_short',
    'content_type'=>'lang',
    'content'=>array('en'=>'Phone number too short.','de'=>'Telefonnummer zu kurz.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'error_phone_too_long',
    'content_type'=>'lang',
    'content'=>array('en'=>'Phone number too long.','de'=>'Telefonnummer zu lang.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'error_phone_possible_local_only',
    'content_type'=>'lang',
    'content'=>array('en'=>'Phone number is only possible as local number.','de'=>'Telefonnummer könnte nur lokal möglich sein.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'error_phone_invalid_length',
    'content_type'=>'lang',
    'content'=>array('en'=>'Phone number has invalid length.','de'=>'Telefonnummer hat ungültige Länge.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'format_datetime_date_no_day',
    'content_type'=>'datetime_format',
    'content'=>array('en'=>'%m/%Y','de'=>'%m.%Y')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_public_menu_and_pages',
    'content_type'=>'lang',
    'content'=>array('en'=>'Public menu and pages','de'=>'Öffentliches Menü und Seiten')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_admin_menu_and_pages',
    'content_type'=>'lang',
    'content'=>array('en'=>'Admin menu and pages','de'=>'Admin-Menü und Seiten')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_editor',
    'content_type'=>'lang',
    'content'=>array('en'=>'Menu editor','de'=>'Menü-Editor')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_area_public',
    'content_type'=>'lang',
    'content'=>array('en'=>'public menu','de'=>'öffentliches Menü')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_area_admin',
    'content_type'=>'lang',
    'content'=>array('en'=>'admin menu','de'=>'Admin-Menü')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_type',
    'content_type'=>'lang',
    'content'=>array('en'=>'Type','de'=>'Typ')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_menu_term',
    'content_type'=>'lang',
    'content'=>array('en'=>'Menu term','de'=>'Bezeichnung im Menü')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_show_when_logged_in_out',
    'content_type'=>'lang',
    'content'=>array('en'=>'Show when logged in/out','de'=>'Anzeigen wenn eingeloggt/ausgeloggt')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_hidden',
    'content_type'=>'lang',
    'content'=>array('en'=>'Hidden','de'=>'Verborgen')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_type_primary_link',
    'content_type'=>'lang',
    'content'=>array('en'=>'Main menu item','de'=>'Haupt-Menüpunkt')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_type_head',
    'content_type'=>'lang',
    'content'=>array('en'=>'Title','de'=>'Überschrift')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_type_submenu',
    'content_type'=>'lang',
    'content'=>array('en'=>'Submenu item','de'=>'Unter-Menüpunkt')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_type_spacer',
    'content_type'=>'lang',
    'content'=>array('en'=>'spacer','de'=>'Abstandhalter')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_visibility_always',
    'content_type'=>'lang',
    'content'=>array('en'=>'always','de'=>'immer')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_visibility_logged_in',
    'content_type'=>'lang',
    'content'=>array('en'=>'when logged in','de'=>'wenn eingeloggt')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_visibility_logged_out',
    'content_type'=>'lang',
    'content'=>array('en'=>'when logged out','de'=>'wenn ausgeloggt')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_visibility_never',
    'content_type'=>'lang',
    'content'=>array('en'=>'never','de'=>'nie')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_new_richtext_page',
    'content_type'=>'lang',
    'content'=>array('en'=>'New richtext page','de'=>'Neue Richtext-Seite')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_new_head_item',
    'content_type'=>'lang',
    'content'=>array('en'=>'New title item','de'=>'Neues Überschriften-Element')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_new_spacer',
    'content_type'=>'lang',
    'content'=>array('en'=>'New spacer','de'=>'Neuer Abstandhalter')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_item',
    'content_type'=>'lang',
    'content'=>array('en'=>'Menu item','de'=>'Menüpunkt')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_item_id',
    'content_type'=>'lang',
    'content'=>array('en'=>'Id','de'=>'ID')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_item_type',
    'content_type'=>'lang',
    'content'=>array('en'=>'Type','de'=>'Typ')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_item_display_level',
    'content_type'=>'lang',
    'content'=>array('en'=>'Display level','de'=>'Anzeigeebene')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_item_type_submenu_link',
    'content_type'=>'lang',
    'content'=>array('en'=>'Submenu item','de'=>'Unter-Menüpunkt')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_item_icon',
    'content_type'=>'lang',
    'content'=>array('en'=>'Icon','de'=>'Icon')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_item_login_visibility',
    'content_type'=>'lang',
    'content'=>array('en'=>'Login visibility','de'=>'Login-Sichtbarkeit')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_item_shortcut',
    'content_type'=>'lang',
    'content'=>array('en'=>'Shortcut','de'=>'Kürzel')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_item_shortcut_help',
    'content_type'=>'lang',
    'content'=>array('en'=>'Allowed: letters, numbers, underscore, dash. Spaces are converted to underscore.','de'=>'Erlaubt: Buchstaben, Zahlen, Unterstrich, Bindestrich. Leerzeichen werden in Unterstriche umgewandelt.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_item_shortcut_already_exists',
    'content_type'=>'lang',
    'content'=>array('en'=>'Shortcut already exists.','de'=>'Kürzel existiert bereits.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_item_page_title',
    'content_type'=>'lang',
    'content'=>array('en'=>'Page title','de'=>'Seitentitel')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_item_hide_for_admin_types',
    'content_type'=>'lang',
    'content'=>array('en'=>'Hide for these admin types','de'=>'Für folgende Admin-Typen ausblenden')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_item_richtext_content',
    'content_type'=>'lang',
    'content'=>array('en'=>'Richtext content','de'=>'Richtext-Inhalt')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_item_edit_content',
    'content_type'=>'lang',
    'content'=>array('en'=>'Edit content','de'=>'Inhalt bearbeiten')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_new_external_link',
    'content_type'=>'lang',
    'content'=>array('en'=>'New link','de'=>'Neuer Link')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_type_external_link',
    'content_type'=>'lang',
    'content'=>array('en'=>'External link','de'=>'Externer Link')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_item_external_url',
    'content_type'=>'lang',
    'content'=>array('en'=>'URL address','de'=>'URL-Adresse')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_item_external_url_required',
    'content_type'=>'lang',
    'content'=>array('en'=>'URL address required','de'=>'URL-Adresse benötigt')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_item_invalid_external_url',
    'content_type'=>'lang',
    'content'=>array('en'=>'Invalid URL address','de'=>'Ungültige URL-Adresse')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_item_menu_term_required',
    'content_type'=>'lang',
    'content'=>array('en'=>'Menu term required','de'=>'Bezeichnung im Menü benötigt')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'cancellation_possible_until',
    'content_type'=>'lang',
    'content'=>array('en'=>'Cancellation possible until','de'=>'Absage möglich bis')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'menu_completed_experiments',
    'content_type'=>'lang',
    'content'=>array('en'=>'Completed','de'=>'Archiviert')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'password_reset_token_not_found',
    'content_type'=>'lang',
    'content'=>array('en'=>'Password change token not found.','de'=>'Passwortänderungs-Token nicht gefunden.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'email_deleted',
    'content_type'=>'lang',
    'content'=>array('en'=>'Email deleted.','de'=>'Email gelöscht.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'email_marked_as_processed',
    'content_type'=>'lang',
    'content'=>array('en'=>'Email marked as processed.','de'=>'Email als bearbeitet markiert.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'active',
    'content_type'=>'lang',
    'content'=>array('en'=>'Active','de'=>'Aktiv')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'inactive',
    'content_type'=>'lang',
    'content'=>array('en'=>'Inactive','de'=>'Inaktiv')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'add_payment_type',
    'content_type'=>'lang',
    'content'=>array('en'=>'Add payment type','de'=>'Zahlungstyp hinzufügen')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'choose',
    'content_type'=>'lang',
    'content'=>array('en'=>'Choose','de'=>'Auswählen')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'copy',
    'content_type'=>'lang',
    'content'=>array('en'=>'Copy','de'=>'Kopieren')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'database_problem',
    'content_type'=>'lang',
    'content'=>array('en'=>'Database problem','de'=>'Datenbank-Problem')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'default',
    'content_type'=>'lang',
    'content'=>array('en'=>'Default','de'=>'Voreinstellung')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'delete_participant_profile_field',
    'content_type'=>'lang',
    'content'=>array('en'=>'Delete participant profile field','de'=>'Teilnehmerprofil-Feld löschen')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'email_received',
    'content_type'=>'lang',
    'content'=>array('en'=>'Received','de'=>'Empfangen')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'empty',
    'content_type'=>'lang',
    'content'=>array('en'=>'Empty','de'=>'Leeren')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'error_column_of_this_name_exists',
    'content_type'=>'lang',
    'content'=>array('en'=>'A column of this name already exists.','de'=>'Eine Spalte mit diesem Namen existiert bereits.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'error_no_upload_file_name',
    'content_type'=>'lang',
    'content'=>array('en'=>'No upload file name given.','de'=>'Kein Dateiname angegeben.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'layout_short_name',
    'content_type'=>'lang',
    'content'=>array('en'=>'Short name (used internally)','de'=>'Kurzname (für interne Nutzung)')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'link',
    'content_type'=>'lang',
    'content'=>array('en'=>'Link','de'=>'Link')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'no_budgets_available_for_view',
    'content_type'=>'lang',
    'content'=>array('en'=>'No budgets available to view.','de'=>'Keine Budgets verfügbar zum Anzeigen.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'no_faqs_found',
    'content_type'=>'lang',
    'content'=>array('en'=>'No FAQs found.','de'=>'Keine FAQs gefunden.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'no_emails',
    'content_type'=>'lang',
    'content'=>array('en'=>'No emails.','de'=>'Keine Emails.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'payment_type_deleted',
    'content_type'=>'lang',
    'content'=>array('en'=>'Payment type deleted.','de'=>'Zahlungstyp gelöscht.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'password_provided',
    'content_type'=>'lang',
    'content'=>array('en'=>'Password provided.','de'=>'Passwort angegeben.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'select',
    'content_type'=>'lang',
    'content'=>array('en'=>'Select','de'=>'Wähle')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'showing_emails',
    'content_type'=>'lang',
    'content'=>array('en'=>'Showing emails','de'=>'Angezeigte Emails')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'participant_profile_form_template_legacy',
    'content_type'=>'lang',
    'content'=>array('en'=>'Legacy participant profile form template (to be discontinued)','de'=>'Altes Teilnehmerprofil-Formular (wird abgelöst)')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033001',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'unlock_account',
    'content_type'=>'lang',
    'content'=>array('en'=>'Unlock account','de'=>'Account freigeben')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033002',
'type'=>'query',
'specs'=> array(
    'query_code'=>"UPDATE TABLE(lang)
                  SET en='Change password', de='Passwort ändern'
                  WHERE content_type='lang' AND content_name='change_my_password'"
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033002',
'type'=>'query',
'specs'=> array(
    'query_code'=>"UPDATE TABLE(lang)
                  SET en='Sign up', de='Registrierung'
                  WHERE content_type='lang' AND content_name='mobile_sign_up'"
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033002',
'type'=>'query',
'specs'=> array(
    'query_code'=>"UPDATE TABLE(lang)
                  SET en='Sign up', de='Registrierung'
                  WHERE content_type='lang' AND content_name='register'"
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033002',
'type'=>'query',
'specs'=> array(
    'query_code'=>"UPDATE TABLE(lang)
                  SET en='Login', de='Login'
                  WHERE content_type='lang' AND content_name='login'"
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033002',
'type'=>'query',
'specs'=> array(
    'query_code'=>"UPDATE TABLE(lang)
                  SET en='Logout', de='Logout'
                  WHERE content_type='lang' AND content_name='logout'"
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033002',
'type'=>'query',
'specs'=> array(
    'query_code'=>"UPDATE TABLE(lang)
                  SET en='Experiments you are enrolled for', de='Experimente, für die Sie angemeldet sind'
                  WHERE content_type='lang' AND content_name='experiments_already_registered_for'"
    )
);

$system__database_upgrades[]=array(
'version'=>'2026033002',
'type'=>'query',
'specs'=> array(
    'query_code'=>"UPDATE TABLE(lang)
                  SET en='Previous', de='Vorherige'
                  WHERE content_type='lang' AND content_name='previous'"
    )
);

$system__database_upgrades[]=array(
'version'=>'2026040201',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'error_page_does_not_exist',
    'content_type'=>'lang',
    'content'=>array('en'=>'Page does note exist.','de'=>'Seite existiert nicht.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026040201',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'error_not_authorized_to_access_this_page',
    'content_type'=>'lang',
    'content'=>array('en'=>'You are not authorized to access this page.','de'=>'Sie sind nicht berechtigt, auf diese Seite zuzugreifen.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026040201',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'error_csrf_token',
    'content_type'=>'lang',
    'content'=>array('en'=>'No valid CSRF token submitted.','de'=>'Kein gültiges CSRF-Token erhalten.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026040202',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'export_color_scheme',
    'content_type'=>'lang',
    'content'=>array('en'=>'Export color scheme','de'=>'Farbschema exportieren')
    )
);


$system__database_upgrades[]=array(
'version'=>'2026040602',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'email_address_enforced_domain_mismatch',
    'content_type'=>'lang',
    'content'=>array('en'=>'Your email address does not match the required format. Please enter a valid email address.','de'=>'Ihre E-Mail-Adresse erfüllt nicht das erforderliche Format. Bitte geben Sie eine passende Email-Adresse ein.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026040701',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'save_participant_data',
    'content_type'=>'lang',
    'content'=>array('en'=>'Save participant data','de'=>'Teilnehmerdaten speichern')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026040701',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'save_participant_admin_data',
    'content_type'=>'lang',
    'content'=>array('en'=>'Save admin data','de'=>'Admin-Daten speichern')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026040701',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'participant_data_saved',
    'content_type'=>'lang',
    'content'=>array('en'=>'Participant data saved.','de'=>'Teilnehmerdaten gespeichert.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026040701',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'participant_admin_data_saved',
    'content_type'=>'lang',
    'content'=>array('en'=>'Admin data saved.','de'=>'Administrative Daten gespeichert.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041401',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_block_text',
    'content_type'=>'lang',
    'content'=>array('en'=>'Text','de'=>'Text')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041401',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_block_section',
    'content_type'=>'lang',
    'content'=>array('en'=>'Section','de'=>'Abschnitt')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041401',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'error_profile_field_only_one_admin_scope_possible',
    'content_type'=>'lang',
    'content'=>array('en'=>'Only one of scopes \"Public profile form in admin area\" and \"Admin-only form part\" is possible.','de'=>'Nur einer der Geltungsbereiche \"Public profile form in admin area\" und \"Admin-only form part\" ist möglich.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041401',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'layout_text',
    'content_type'=>'lang',
    'content'=>array('en'=>'Text','de'=>'Text')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041401',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'layout_section_title',
    'content_type'=>'lang',
    'content'=>array('en'=>'Section title','de'=>'Abschnittsüberschrift')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041401',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'display_preview_for_scope_subjectpool',
    'content_type'=>'lang',
    'content'=>array('en'=>'Display preview for scope/subpool','de'=>'Zeige Vorschau für Geltungsbereich/Subpool')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041402',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'participant_profile_layout_draft_activated',
    'content_type'=>'lang',
    'content'=>array('en'=>'New participant profile layout activated.','de'=>'Neues Teilnehmerprofil-Layout aktiviert.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_col_reason',
    'content_type'=>'lang',
    'content'=>array('en'=>'Reason','de'=>'Grund')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_reason_no_scope',
    'content_type'=>'lang',
    'content'=>array('en'=>'no scope','de'=>'kein Geltungsbereich')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_fields_disabled_unplaced_new',
    'content_type'=>'lang',
    'content'=>array('en'=>'Disabled, unplaced, newly created fields','de'=>'Deaktivierte, nicht platzierte, neu erstellte Felder')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_col_db_field',
    'content_type'=>'lang',
    'content'=>array('en'=>'MySQL field','de'=>'MySQL-Feld')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_reason_disabled',
    'content_type'=>'lang',
    'content'=>array('en'=>'disabled','de'=>'deaktiviert')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_preview__and_activation',
    'content_type'=>'lang',
    'content'=>array('en'=>'Preview and activation','de'=>'Vorschau und Aktivierung')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_reason_new_unconfigured',
    'content_type'=>'lang',
    'content'=>array('en'=>'new/unconfigured field','de'=>'neues/nicht konfiguriertes Feld')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_really_remove_layout_block',
    'content_type'=>'lang',
    'content'=>array('en'=>'Do you really want to remove this text/section from the form draft?','de'=>'Wollen Sie diesen Text/Abschnitt wirklich aus dem Formular-Entwurf entfernen?')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_fields_public_form',
    'content_type'=>'lang',
    'content'=>array('en'=>'Fields in public form','de'=>'Felder im öffentlichen Formular')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_fields_admin_only_form',
    'content_type'=>'lang',
    'content'=>array('en'=>'Fields in admin-only form','de'=>'Felder im Admin-Formular')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_public_preview_and_comparison',
    'content_type'=>'lang',
    'content'=>array('en'=>'Public form preview and comparison','de'=>'Vorschau und Vergleich des öffentlichen Formulars')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_admin_preview_and_comparison',
    'content_type'=>'lang',
    'content'=>array('en'=>'Admin-only form preview and comparison','de'=>'Vorschau und Vergleich des Admin-Formulars')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_add_variant',
    'content_type'=>'lang',
    'content'=>array('en'=>'Add variant','de'=>'Variante hinzufügen')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_stored_variants',
    'content_type'=>'lang',
    'content'=>array('en'=>'Stored variants','de'=>'Gespeicherte Varianten')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_scopes',
    'content_type'=>'lang',
    'content'=>array('en'=>'Scopes','de'=>'Geltungsbereich')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'subpools',
    'content_type'=>'lang',
    'content'=>array('en'=>'Sub-subjectpools','de'=>'Sub-Subpools')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_affected_fields',
    'content_type'=>'lang',
    'content'=>array('en'=>'Affected fields','de'=>'Betroffene Felder')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_field_variant',
    'content_type'=>'lang',
    'content'=>array('en'=>'Field variant','de'=>'Feldvariante')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_variant_scope_contexts',
    'content_type'=>'lang',
    'content'=>array('en'=>'Variant scope contexts','de'=>'Geltungsbereiche der Variante')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_variant_subpools',
    'content_type'=>'lang',
    'content'=>array('en'=>'Variant subpools','de'=>'Subpools der Variante')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_delete_variant',
    'content_type'=>'lang',
    'content'=>array('en'=>'Delete variant','de'=>'Variante löschen')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_really_delete_variant',
    'content_type'=>'lang',
    'content'=>array('en'=>'Do you really want to delete this field variant?','de'=>'Wollen Sie diese Feldvariante wirklich löschen?')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_variant_deleted',
    'content_type'=>'lang',
    'content'=>array('en'=>'Variant deleted.','de'=>'Variante gelöscht.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_variant_not_found',
    'content_type'=>'lang',
    'content'=>array('en'=>'Variant not found.','de'=>'Variante nicht gefunden.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_error_scope_required',
    'content_type'=>'lang',
    'content'=>array('en'=>'Please select at least one scope context.','de'=>'Bitte wählen Sie mindestens einen Geltungsbereich aus.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_error_subpool_required',
    'content_type'=>'lang',
    'content'=>array('en'=>'Please select at least one subpool.','de'=>'Bitte wählen Sie mindestens einen Subpool aus.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_error_override_required',
    'content_type'=>'lang',
    'content'=>array('en'=>'Please select at least one override field.','de'=>'Bitte wählen Sie mindestens ein Override-Feld aus.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_variant_overlaps_with',
    'content_type'=>'lang',
    'content'=>array('en'=>'Variant overlaps with','de'=>'Variante überschneidet sich mit')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_conflicting_cells',
    'content_type'=>'lang',
    'content'=>array('en'=>'Conflicting scope/subpool cells','de'=>'Überlappende Kombinationen')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_field_name',
    'content_type'=>'lang',
    'content'=>array('en'=>'Field name','de'=>'Feldname')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_variant',
    'content_type'=>'lang',
    'content'=>array('en'=>'variant','de'=>'Variante')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_restrict_to_subpools',
    'content_type'=>'lang',
    'content'=>array('en'=>'Restrict to subpools','de'=>'Auf Subpools beschränken')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041801',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_mysql_column_type',
    'content_type'=>'lang',
    'content'=>array('en'=>'MySQL column type','de'=>'MySQL-Spaltentyp')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041802',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_applies_to',
    'content_type'=>'lang',
    'content'=>array('en'=>'Applies to','de'=>'Benutzt für')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041803',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_everywhere',
    'content_type'=>'lang',
    'content'=>array('en'=>'Everywhere','de'=>'Überall')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041802',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'profile_editor_plus_variants',
    'content_type'=>'lang',
    'content'=>array('en'=>'+ Variants','de'=>'+ Varianten')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026041804',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'error_missing_mysql_column_name',
    'content_type'=>'lang',
    'content'=>array('en'=>'Missing MySQL column name.','de'=>'Der MySQL-Spaltenname fehlt.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026042202',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'lang_is_rtl',
    'content_type'=>'lang',
    'content'=>array('en'=>'n','de'=>'n')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026042201',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'language_is_right_to_left_lang',
    'content_type'=>'lang',
    'content'=>array('en'=>'Is right-to-left language (RTL)?','de'=>'Hat rechts-nach-links-Ausrichtung (RTL)?')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026042401',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'lang_flag_iso2',
    'content_type'=>'lang',
    'content'=>array('en'=>'','de'=>'')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026042401',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'flag_for_language',
    'content_type'=>'lang',
    'content'=>array('en'=>'Flag for language (country code, iso2)','de'=>'Flagge für Sprache (Ländercode, iso2)')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026042501',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'all_downloads',
    'content_type'=>'lang',
    'content'=>array('en'=>'All downloads','de'=>'Alle Downloads')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026042502',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'all_laboratories',
    'content_type'=>'lang',
    'content'=>array('en'=>'All laboratories','de'=>'Alle Labore')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026042503',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'all_experimenters',
    'content_type'=>'lang',
    'content'=>array('en'=>'All experimenters','de'=>'Alle Experimentatoren')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026042504',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'show_my_calendar',
    'content_type'=>'lang',
    'content'=>array('en'=>'Show my calendar','de'=>'Mein Kalender')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026042505',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'error_mysql_column_name_conflicts_with_lang_content_type',
    'content_type'=>'lang',
    'content'=>array('en'=>'MySQL column name conflicts with an existing content type in or_lang table.','de'=>'MySQL-Spaltenname kollidiert mit einem bestehenden Content-Type in Tabelle or_lang.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026042601',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'error_lang_symbol_name_required',
    'content_type'=>'lang',
    'content'=>array('en'=>'Symbol name is required','de'=>'Symbolname muss angegeben werden.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026042602',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'error_event_description_required',
    'content_type'=>'lang',
    'content'=>array('en'=>'An (internal) event description is required.','de'=>'Eine (interne) Event-Beschreibung muss angegeben werden.')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026042603',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'query_auth_migration_status',
    'content_type'=>'lang',
    'content'=>array('en'=>'Auth migration status ...','de'=>'Auth-Migrations-Status ...')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026042603',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'where_auth_migration_status_is',
    'content_type'=>'lang',
    'content'=>array('en'=>'where auth migration status is','de'=>'bei denen der Status der Auth-Migration ist')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026042603',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'query_auth_migration_no_password',
    'content_type'=>'lang',
    'content'=>array('en'=>'not yet migrated (no password set)','de'=>'noch nicht migriert (kein Passwort gesetzt)')
    )
);

$system__database_upgrades[]=array(
'version'=>'2026042603',
'type'=>'new_lang_item',
'specs'=> array(
    'content_name'=>'query_auth_migration_has_password',
    'content_type'=>'lang',
    'content'=>array('en'=>'migrated (password set)','de'=>'migriert (Passwort gesetzt)')
    )
);

?>
