<?php
$master_country_list_json = file_get_contents("json/master_country_list.json");
$master_country_list = json_decode($master_country_list_json);

$wikiVisaRequirement = 'http://en.wikipedia.org/w/api.php?action=parse&prop=text&format=php&page=';
   
foreach ($master_country_list as $country_element) 
{
    if($country_element->wikipage)
    {
        $apiResultVisa = file_get_contents($wikiVisaRequirement . $country_element->wikipage);
        $parsedResultVisa = unserialize($apiResultVisa);
        $renderedHTMLVisa = $parsedResultVisa["parse"]["text"]["*"];

        $domVisa = new DOMDocument;
        $domVisa->loadHTML($renderedHTMLVisa);

        $xpathVisa = new DOMXPath($domVisa);

        $visaWorldMap = $xpathVisa->query("//*[contains(@href, \"/wiki/File:" . $country_element->wikipage . ".png\")]/img");
        $countryName = strtolower(preg_replace('/\s*/', '', $country_element->name));

        mkdir('output/' . $countryName);

        if($visaWorldMap)
        {
            $url = 'http:' . $visaWorldMap[0]->attributes[1]->nodeValue;
            $img = 'output/' . $countryName . '/visa_req_' . $countryName . '.png';
            file_put_contents($img, file_get_contents($url));            
        }
    }
   
}

?>