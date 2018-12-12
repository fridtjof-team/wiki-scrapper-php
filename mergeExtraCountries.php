<?php

/*
 * This script merges countries without any wikidata into a standard master copy with wiki attributes as null.
 */


$country_list_merged_json = file_get_contents("json/country_list_merged.json");
$country_list_json = file_get_contents("json/country_list.json");

$country_list_merged = json_decode($country_list_merged_json);
$country_list = json_decode($country_list_json);

$newlist = [];
$found = false;
foreach ($country_list as $country_iso) 
{
    $isocountryname = strtolower(preg_replace('/\s*/', '', $country_iso->name));
    $countryObj = new stdClass;
    $countryObj->name = $country_iso->name;
    $countryObj->iso = $country_iso->code;

    foreach ($country_list_merged as $country_wiki) 
    {
        $wikicountryname = strtolower(preg_replace('/\s*/', '', $country_wiki->name));
        if ($wikicountryname === $isocountryname) {
            $countryObj->wikipage = $country_wiki->wikipage;
            $countryObj->wikilink = $country_wiki->wikilink;
            $countryObj->citizenship = $country_wiki->citizenship;
            $found = true;
        }
    }

   
    if (!$found) {
        $countryObj->wikipage = null;
        $countryObj->wikilink = null;
        $countryObj->citizenship = null;
    }
    $newlist[] = $countryObj;
    $found = false;
}

$fp = fopen("newlist.json", "w");
fwrite($fp, json_encode($newlist));
fclose($fp);


?>