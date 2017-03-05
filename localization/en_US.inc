<?php

$labels = array();

$labels['signature'] = 'Signature';
$labels['changesignature'] = 'Change signature';

$labels['enabled'] = 'Enabled';
$labels['htmlsignature'] = 'HTML signature';
$labels['plaintextsignature'] = 'Plain text signature';

$messages = array();
$messages['successfullyupdated'] = 'Signature has successfully update.';
$messages['internalerror'] = 'Error with selected action.';
//$messages['connecterror'] = $messages['internalerror'] . ' Connection error.';
$messages['loaderror'] = 'Could not load signature.';
$messages['loadconnecterror'] = $messages['loadinternalerror'] . ' Connection error.';
$messages['updateerror'] = 'Could not update external account.';
$messages['updateconnecterror'] = $messages['updateerror'] . ' Connection error.';
