<?php
$wikiAPICountries = "https://en.wikipedia.org/w/api.php?action=parse&prop=text&page=Category:Visa_requirements_by_nationality&format=php"; 
$wikiVisaRequirement = 'http://en.wikipedia.org/w/api.php?action=parse&prop=text&format=php&page=';		

$apiResult = file_get_contents($wikiAPICountries);
$parsedResult = unserialize($apiResult);
$renderedHTML = $parsedResult["parse"]["text"]["*"];

$dom = new DOMDocument;
$dom->loadHTML($renderedHTML);

$xpath = new DOMXPath($dom);
$elements = $xpath->query("//*[contains(@class, \"navbox-list\")]/div/ul//li");

$countries = [];

// Loop through to retreive all visa requirements pages
foreach ($elements as $element) {
    $href = $element->firstChild->attributes["href"]->nodeValue;
    $citizenship = $element->nodeValue;
    $wikiPageName = explode("/", $element->firstChild->attributes["href"]->nodeValue);

    $apiResultVisa = file_get_contents($wikiVisaRequirement . $wikiPageName[2]);
    $parsedResultVisa = unserialize($apiResultVisa);
    $renderedHTMLVisa = $parsedResultVisa["parse"]["text"]["*"];

    $domVisa = new DOMDocument;
    $domVisa->loadHTML($renderedHTMLVisa);

    $xpathVisa = new DOMXPath($domVisa);

    // Get the country name from the visa requirement page (The xpath is not 100%)
    $elementsVisa = $xpathVisa->query("//*[contains(@class, \"mw-parser-output\")]/p/a");

    $countryObj = new stdClass;
    $countryObj->wikipage = $wikiPageName[2];
    $countryObj->wikilink = $href;
    $countryObj->citizenship = $citizenship;
    $countryObj->name = $elementsVisa[0]->nodeValue;


    $countries[] = $countryObj;
}

$fp = fopen("results1.json", "w");
fwrite($fp, json_encode($countries));
fclose($fp);

?>