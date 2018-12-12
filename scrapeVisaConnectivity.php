<?php
// Ignore warnings
error_reporting(E_ERROR | E_PARSE);

function getCountryVisaConnectivity($country_element, $wikiVisaRequirement, $master_country_list, $final_visa_types)
{
    $visaConnectivity = [];

    if ($country_element->wikipage) {
        $apiResultVisa = file_get_contents($wikiVisaRequirement . $country_element->wikipage);
        $parsedResultVisa = unserialize($apiResultVisa);
        $renderedHTMLVisa = $parsedResultVisa["parse"]["text"]["*"];

        $domVisa = new DOMDocument;
        $domVisa->loadHTML($renderedHTMLVisa);

        $xpathVisa = new DOMXPath($domVisa);

        $visaRequirementTable = $xpathVisa->query("(//table[contains(@class, \"wikitable\")])[1]/tbody/tr");
        if ($visaRequirementTable) {
            $firstElementPassed = false;
            foreach ($visaRequirementTable as $visaRequirementRow) {
                if ($firstElementPassed) {
                    $countryExists = false;

                    $td = $visaRequirementRow->childNodes;
                    $countryNameVisaTable = strtolower(preg_replace('/\PL/u', '', $td->item(1)->nodeValue));
                    $countryNameToSave = "";
                    $countryISOSave = "";

                    foreach ($master_country_list as $country) {
                        $countryNameList = strtolower(preg_replace('/\PL/u', '', $country->name));

                        if (strpos($countryNameVisaTable, $countryNameList) !== false) {
                            $countryExists = true;
                            $countryNameToSave = $countryNameList;
                            $countryISOSave = $country->iso;

                        }
                    }

                    if (!countryExists) {
                        if ("CÃtedIvoire" == preg_replace('/\PL/u', '', $td->item(1)->nodeValue)) {
                            $countryNameToSave = "ivorycoast";
                            $countryISOSave = "CI";
                        }
                        if ("SÃoTomÃandPrÃncipe" == preg_replace('/\PL/u', '', $td->item(1)->nodeValue)) {
                            $countryNameToSave = "saotomeandprincipe";
                            $countryISOSave = "ST";
                        }
                    }

                    $visaRequirementType = $td->item(3)->nodeValue;
                    $shrunkVisaRequirementType = strtolower(preg_replace('/\PL/u', '', $visaRequirementType));

                    //Regex to remove citation numbers Eg: [1]
                    $visaRequirementType = strtolower(preg_replace('/\[(.*?)\]/', '', $visaRequirementType));
                    //Regex to remove line breaks Eg: \n
                    $visaRequirementType = strtolower(preg_replace('/(\r\n|\r|\n)/', '', $visaRequirementType));

                    
                    // Find Visa Type ID
                    $visa_number;

                    foreach ($final_visa_types as $visaType) {
                        if ($visaType->type == $shrunkVisaRequirementType) {
                            $visa_number = $visaType->id;
                        }
                    }

                    // Source Link
                    $visaRequirementTypeChildNodes = $td->item(3)->childNodes;
                    preg_match_all('/\[(.*?)\]/', $td->item(3)->nodeValue, $citationNumbers);
                    $citationNumbers = $citationNumbers[0];

                    $sources = [];
                    foreach ($visaRequirementTypeChildNodes as $node) {
                        foreach ($citationNumbers as $number) {
                            if ($number == $node->nodeValue) {
                                $referenceID = substr($node->childNodes[0]->attributes[0]->nodeValue, 1);
                                $referenceNode = $xpathVisa->query("//*[@id='$referenceID']//span[@class='reference-text']//a[contains(@class, \"external\")]/@href");
                                if ($referenceNode && !empty($referenceNode[0])) {
                                    array_push($sources, $referenceNode[0]->nodeValue);
                                }
                            }
                        }
                    }


                    // Notes
                    $notes = explode("\n", $td->item(7)->nodeValue);

                    $message = [];
                    foreach ($notes as $note) {
                        if ($note != "") {
                            $source = "";
                            $referenceDOM = $xpathVisa->query('.//*[contains(@class, "reference")]//a', $td->item(7));

                            preg_match_all('/\[(.*?)\]/', $note, $citationNumbers);
                            $citationNumbers = $citationNumbers[0];

                            foreach ($referenceDOM as $identifier) {
                                foreach ($citationNumbers as $number) {
                                    if ($number == $identifier->nodeValue) {
                                        $referenceID = substr($identifier->attributes[0]->nodeValue, 1);
                                        $referenceNode = $xpathVisa->query("//*[@id='$referenceID']//span[@class='reference-text']//a[contains(@class, \"external\")]/@href");
                                        if ($referenceNode && !empty($referenceNode[0])) {
                                            $source = $referenceNode[0]->nodeValue;
                                        }
                                    }
                                }
                            }


                            $note = preg_replace('/\[(.*?)\]/', '', $note);

                            $notesObj = new stdClass;
                            $notesObj->text = $note;
                            $notesObj->source = $source;
                            array_push($message, $notesObj);
                        }
                    }

                    $time = strtolower(preg_replace('/(\r\n|\r|\n)/', '', $td->item(5)->nodeValue));

                    $visaConnectivityObj = new stdClass;
                    $visaConnectivityObj->iso = $countryISOSave;
                    $visaConnectivityObj->name = $countryNameToSave;
                    $visaConnectivityObj->visa = $visaRequirementType;
                    $visaConnectivityObj->visa_number = $visa_number;
                    $visaConnectivityObj->time = $time;
                    $visaConnectivityObj->notes = $message;
                    $visaConnectivityObj->source = $sources;
                    $visaConnectivity[] = $visaConnectivityObj;
                }
                $firstElementPassed = true;
            }
        }
    }
    return $visaConnectivity;
}

$master_country_list_json = file_get_contents("json/master_country_list.json");
$master_country_list = json_decode($master_country_list_json);

$test_master_country_list_json = file_get_contents("json/test_master_country_list.json");
$test_master_country_list = json_decode($test_master_country_list_json);

$final_visa_types_json = file_get_contents("json/final_visa_types.json");
$final_visa_types = json_decode($final_visa_types_json);

$wikiVisaRequirement = 'http://en.wikipedia.org/w/api.php?action=parse&prop=text&format=php&page=';

$visaReq = [];

foreach ($master_country_list as $country_element) {

    $visaConnectivity = getCountryVisaConnectivity($country_element, $wikiVisaRequirement, $master_country_list, $final_visa_types);
    $countryName = strtolower(preg_replace('/\s*/', '', $country_element->name));
    
    $visaConnectivityObj = new stdClass;
    $visaConnectivityObj->citizenship_iso = $country_element->iso;
    $visaConnectivityObj->citizenship_country = $country_element->name;
    $visaConnectivityObj->visa_connectivity = $visaConnectivity;
    array_push($visaReq, $visaConnectivityObj);   
}


$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.mlab.com/api/1/databases/frisco/collections/visa_connectivity?apiKey=SAegfE9Mt98CqImokCAv6p9sECY7V_b1");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($visaReq));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen(json_encode($visaReq)))
);

$server_output = curl_exec($ch);
curl_close($ch);
