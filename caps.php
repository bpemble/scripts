#!/usr/bin/php
<?php

ini_set('auto_detect_line_endings', true);
ini_set('date.timezone', 'America/New_York');

// Handling of the last run time.
$suppress = array_key_exists('s', getopt('s'));
$txt_handle = fopen('/Users/bpemble/Dev/caps/caps.txt', 'r');
$time = fread($txt_handle, filesize('/Users/bpemble/Dev/caps/caps.txt'));
if (!$suppress && (time() - $time) < 1800) {
  exit;
}
$txt_handle = fopen('/Users/bpemble/Dev/caps/caps.txt', 'w');
fwrite($txt_handle, time());
$time = time() - (3 * 24 * 60 * 60);

error_reporting($suppress ? E_ALL ^ E_WARNING : E_ALL);

$api_key = '7b2f813e-2b97-45e5-a733-7f46cd05d4de';
$ch = curl_init();
$csv_handle = fopen('/Users/bpemble/Dev/caps/caps.csv', 'r');
$fools_picks = $started_picks = $ended_picks = array();
$fools_to_watch = array(
  'bbmaven',
  'TMFBabo',
  'AsimovRobot',
  'Wh1sp',
  'TMFEldrehad',
  'NTMF',
  'ClientNine',
  'TMFStockSpam',
  'JoeySolitro2',
  'JoeySolitro1',
  'TheGreatSatan',
  'msIRA',
  'cvdynasty0',
  'AMTM122112',
  'zk116',
  'cecamadocv',
  'senkihazi',
  'Griffin416',
  'vanamonde',
  'XMFYoung',
  'Emilie111',
  'zgelfan3',
  'BravoBevo',
  'cecamado1',
  'SNHamilton',
  'TSIF',
  'Mega',
  'cecamadocv1',
  'RayNobleEsq',
  'liszewski',
  'chk999',
  'cecamadocv4',
  'LarryRicardo',
  'Bigeric98',
  'InflationSilver',
  'cecamadocv2',
  'ktrotter79',
  'ozzie',
  'giffenbone',
  'FoolsGrad'
);

// Querying for ended picks.
$active_picks = array();
while (($csv_array = fgetcsv($csv_handle)) !== false) {
  if ($csv_array[0] && $csv_array[1] && !$csv_array[5]) {
    $active_picks[] = $csv_array[1];

    if (!array_key_exists($csv_array[0], $fools_picks)) {
      $fools_picks[$csv_array[0]] = getFoolPicks($ch, $csv_array[0], $api_key);
    }

    $fool_pick_found = false;
    foreach ($fools_picks[$csv_array[0]] as $fool_pick) {
      if ($fool_pick['PickCall'] === 'Outperform' && $fool_pick['TickerSymbol'] === $csv_array[1]) {
        $fool_pick_found = true;
        break 1;
      }
    }

    if (!$fool_pick_found) {
      $ended_picks[] = $csv_array[0] . '|' . $csv_array[1];
    }
  }
}

// Querying for started picks.
foreach ($fools_to_watch as $fool_to_watch) {
  if (!array_key_exists($fool_to_watch, $fools_picks)) {
    $fools_picks[$fool_to_watch] = getFoolPicks($ch, $fool_to_watch, $api_key);    
  }

  if ($fools_picks[$fool_to_watch]) {
    foreach ($fools_picks[$fool_to_watch] as $fool_pick) {
      if (!in_array($fool_pick['TickerSymbol'], $active_picks) && $fool_pick['PickCall'] === 'Outperform' && $fool_pick['StartDate'] > date('Y-m-d H:i:s', $time)) {
        $started_picks[] = $fool_to_watch . '|' . $fool_pick['TickerSymbol'];
      }
    }
  }
}

// Output the results, either via stdout or a text.
$output = 'No activity.';
if (count($started_picks) || count($ended_picks)) {
  $output = 'Started picks: ' . implode(', ', $started_picks) . "\nEnded picks: " . implode(', ', $ended_picks);

  if (!$suppress) {
    mail('brian.pemble@gmail.com', 'CAPS Activity', $output);
  }
}

echo "\n{$output}\n\n";

function getFoolPicks($ch, $fool_name, $api_key) {
  curl_setopt($ch, CURLOPT_URL, "http://www.fool.com/a/caps/ws/caps/ws/Player/{$fool_name}/Picks/Active?apikey={$api_key}");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  return json_decode(json_encode(simplexml_load_string(curl_exec($ch))), true)['Player']['Picks'];
}

?>
