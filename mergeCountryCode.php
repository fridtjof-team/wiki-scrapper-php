<?php

/*
 * This script merges results from the wiki scrape result and the country iso.
 * It adds the ISO country code to the wiki result.
 */

$countrylist_json = file_get_contents("json/country_list.json");
$results_json = file_get_contents("json/wiki_result.json");

$countrylist = json_decode($countrylist_json, true);
$wikiresults = json_decode($results_json, true);

$wikicount = 0;
$count = 0;
$newlist = [];
$found = false;

foreach ($wikiresults as $wikiresult) {
    $wikicountryname = strtolower(preg_replace('/\s*/', '', $wikiresult['name']));
    $wikicount += 1;

    foreach ($countrylist as $isocountry) {
        $isocountryname = strtolower(preg_replace('/\s*/', '', $isocountry['name']));

        if($wikicountryname === $isocountryname){
            $count += 1;
            $countryObj = new stdClass;
            $countryObj->wikipage = $wikiresult['wikipage'];
            $countryObj->wikilink = $wikiresult['wikilink'];
            $countryObj->citizenship = $wikiresult['citizenship'];
            $countryObj->name = $wikiresult['name'];
            $countryObj->iso = $isocountry['code'];
            $newlist[] = $countryObj;
            $found = true;
        }
    }

    if($found) {
        $found = false;
    } else {
        echo($wikiresult['name'] . "\n");
    }
}

echo("Wiki count: " . $wikicount . "\n");
echo("Found count: ". $count . "\n");

$fp = fopen("newlist.json", "w");
fwrite($fp, json_encode($newlist));
fclose($fp);

?>