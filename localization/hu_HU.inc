<?php

$labels = array();

$labels['signature'] = 'Aláírás';
$labels['changesignature'] = 'Aláírás módosítása';

$labels['enabled'] = 'Engedélyezve';
$labels['htmlsignature'] = 'HTML aláírás';
$labels['plaintextsignature'] = 'Egyszerű szöveges aláírás';

$messages = array();
$messages['successfullyupdated'] = 'Az aláírás sikeresen frissült.';
$messages['internalerror'] = 'Hiba a kiválasztott művelettel.';
//$messages['connecterror'] = $messages['internalerror'] . ' Connection error.';
$messages['loaderror'] = 'Nem sikerült betölteni az aláírást.';
$messages['loadconnecterror'] = $messages['loadinternalerror'] . ' Connection error.';
$messages['updateerror'] = 'Nem sikerült frissíteni a külső fiókot.';
$messages['updateconnecterror'] = $messages['updateerror'] . ' Connection error.';
