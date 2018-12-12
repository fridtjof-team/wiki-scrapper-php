<?php
// Ignore warnings
error_reporting(E_ERROR | E_PARSE);

function getVisaTypes($master_country_list, $wikiVisaRequirement){
    $visaTypes = [];

    foreach ($master_country_list as $country_element) {
        if ($country_element->wikipage) {
            $apiResultVisa = file_get_contents($wikiVisaRequirement . $country_element->wikipage);
            $parsedResultVisa = unserialize($apiResultVisa);
            $renderedHTMLVisa = $parsedResultVisa["parse"]["text"]["*"];

            $domVisa = new DOMDocument;
            $domVisa->loadHTML($renderedHTMLVisa);

            $xpathVisa = new DOMXPath($domVisa);

            $visaRequirementTable = $xpathVisa->query("(//table[contains(@class, \"wikitable\")])[1]/tbody/tr");
            $referenceList = $xpathVisa->query("(//div[contains(@class, \"reflist\")]/ol/li)");

            if ($visaRequirementTable) {
                $firstElementPassed = false;
                foreach ($visaRequirementTable as $visaRequirementRow) {
                    if ($firstElementPassed) {
                        $visaTypeExists = false;

                        $td = $visaRequirementRow->childNodes;

                        $visaRequirementType = $td->item(3)->nodeValue;
                        $shrunkVisaRequirementType = strtolower(preg_replace('/\PL/u', '', $visaRequirementType));

                        //Regex to remove citation numbers Eg: [1]
                        $visaRequirementType = strtolower(preg_replace('/\[(.*?)\]/', '', $visaRequirementType));
                        //Regex to remove line breaks Eg: \n
                        $visaRequirementType = strtolower(preg_replace('/(\r\n|\r|\n)/', '', $visaRequirementType));

                        foreach ($visaTypes as $visaType) {
                            if ($visaType->type == $shrunkVisaRequirementType) {
                                $visaType->count += 1;
                                $visaTypeExists = true;
                            }
                        }

                        if (!$visaTypeExists) {
                            $visaTypeObject = new stdClass;
                            $visaTypeObject->type = $shrunkVisaRequirementType;
                            $visaTypeObject->name = $visaRequirementType;
                            $visaTypeObject->id = sizeof($visaTypes) + 1;
                            $visaTypeObject->count = 1;
                            $visaTypes[] = $visaTypeObject;
                        }
                    }
                    $firstElementPassed = true;
                }

            }
        }
    }

    return $visaTypes;
}

$master_country_list_json = file_get_contents("json/master_country_list.json");
$master_country_list = json_decode($master_country_list_json);

$test_master_country_list_json = file_get_contents("json/test_master_country_list.json");
$test_master_country_list = json_decode($test_master_country_list_json);

$final_visa_types_json = file_get_contents("json/final_visa_types.json");
$final_visa_types = json_decode($final_visa_types_json);

$wikiVisaRequirement = 'http://en.wikipedia.org/w/api.php?action=parse&prop=text&format=php&page=';

$visaTypeList = getVisaTypes($master_country_list, $wikiVisaRequirement);
$fp = fopen("visa_types.json", "w");
fwrite($fp, json_encode($visaTypeList));
fclose($fp);



