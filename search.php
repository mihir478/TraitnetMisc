<?php
//extract data from the post
ini_set("display_errors", true);
error_reporting(E_ALL);
$searchTerm = /*preg_replace("/(?<=\\w)(?=[A-Z])/", " $1", */$_POST["searchTerm"];

$url='http://knb.ecoinformatics.org/knb/metacat';
$fields_string='operator=UNION&organizationName='.$searchTerm.'&surName='.$searchTerm.'&givenName='.$searchTerm.'&keyword='.$searchTerm.'&para='.$searchTerm.'&geographicDescription='.$searchTerm.'&literalLayout='.$searchTerm.'&abstract%2Fpara='.$searchTerm.'&abstract='.$searchTerm.'&idinfo%2Fcitation%2Fciteinfo%2Ftitle='.$searchTerm.'&idinfo%2Fcitation%2Fciteinfo%2Forigin='.$searchTerm.'&idinfo%2Fkeywords%2Ftheme%2Fthemekey='.$searchTerm.'&placekey='.$searchTerm.'&qformat=xml&returnfield=originator%2FindividualName%2FsurName&returnfield=originator%2FindividualName%2FgivenName&returnfield=creator%2FindividualName%2FsurName&returnfield=creator%2FindividualName%2FgivenName&returnfield=originator%2ForganizationName&returnfield=creator%2ForganizationName&returnfield=dataset%2Ftitle&returnfield=keyword&returnfield=idinfo%2Fcitation%2Fciteinfo%2Ftitle&returnfield=idinfo%2Fcitation%2Fciteinfo%2Forigin&returnfield=idinfo%2Fkeywords%2Ftheme%2Fthemekey&returndoctype=eml%3A%2F%2Fecoinformatics.org%2Feml-2.1.0&title='.$searchTerm.'&action=query';
/*

$ch = curl_init();
curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch,CURLOPT_POST,1);
curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
$result = curl_exec($ch);
curl_close($ch);*/

$result = file_get_contents($url, false, stream_context_create(array('http'=>array('method'=>'POST', 'header'=>'Content-Type: application/x-www-form-urlencoded', 'content'=> $fields_string))));

include "xml.php";

$arr = xmlstr_to_array($result);

foreach($arr["document"] as $i){
	$list[] = $i["docid"];
}

// Multi handle

$result = array();

$mh = curl_multi_init();
foreach($list as $k => $i){
	$v = $i;
	$list[$k] = curl_init();	
	curl_setopt($list[$k], CURLOPT_URL, "http://knb.ecoinformatics.org/knb/metacat");
	curl_setopt($list[$k], CURLOPT_HEADER, 0);
	curl_setopt($list[$k], CURLOPT_RETURNTRANSFER,1);
	curl_setopt($list[$k], CURLOPT_POSTFIELDS, "action=read&sessionid=0&qformat=xml&docid=" . $v);
	curl_multi_add_handle($mh, $list[$k]);
}

$active = null;

do {
    $mrc = curl_multi_exec($mh, $active);
} while ($mrc == CURLM_CALL_MULTI_PERFORM);

while ($active && $mrc == CURLM_OK) {
    if (curl_multi_select($mh) != -1) {
        do {
            curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
    }
}
foreach($list as $k => $i){
	$result[] = xmlstr_to_array(curl_exec($list[$k]));
	curl_multi_remove_handle($list[$k]);
}
curl_multi_close($mh);

if(count($result) > 0) {
	echo "<h3 style = 'white;'>Results for: <span class=\"error_result\">" . $searchTerm . "</span></h3><table class='results'>";
	echo "<thead><tr><td>Dataset</td><td>Owner</td><td>Contact</td><td width=30%>Intellectual Rights</td><td>Geographic Location</td><td>Description</td></tr></thead>";
}
else
{
	echo "<div class=\"error\">No results found for <span class=\"error_result\">" . $searchTerm . "</span><br />Try searching for another Trait.</div>";
}


for($i = 0; $i < count($result); $i++ ) {
	$title = $result[$i]["dataset"]["title"];
	$contact = "";
	$owner = "";
	
	$ownerArr = $result[$i]["dataset"]["creator"];
	if( !is_associative_array($ownerArr) ) {
		for( $j = 0; $j < count($ownerArr); $j++ ){
				$name = isset($ownerArr[$j]["individualName"]) ? $ownerArr[$j]["individualName"]["givenName"] . " " . $ownerArr[$j]["individualName"]["surName"] : $ownerArr[$j]["positionName"];
			$owner .= "<br /><br />" . $name . "<br />" . $ownerArr[$j]["organizationName"] .  "<br />" . $ownerArr[$j]["electronicMailAddress"];
		}
	} else {
		$owner .= "<br /><br />" . $ownerArr["individualName"]["givenName"] . " " . $ownerArr["individualName"]["surName"] . "<br />" . $ownerArr["organizationName"] .  "<br />" . $ownerArr["electronicMailAddress"];
	}
	$owner = substr($owner, 12);

	$contactArr = $result[$i]["dataset"]["contact"];
	if( isset($contactArr["electronicMailAddress"]) || isset($contactArr[0]) ){
		if( !is_associative_array($contactArr) ) {
			for( $j = 0; $j < count($contactArr); $j++ ){
				$name = isset($contactArr[$j]["individualName"]) ? $contactArr[$j]["individualName"]["givenName"] . " " . $contactArr[$j]["individualName"]["surName"] : $contactArr[$j]["positionName"];
				$owner .= "<br /><br />" . $name . "<br />" . $contactArr[$j]["organizationName"] .  "<br />" . $contactArr[$j]["electronicMailAddress"];
			}
		} else {
			$owner .= "<br /><br />" . $contactArr["individualName"]["givenName"] . " " . $contactArr["individualName"]["surName"] . "<br />" . $contactArr["organizationName"] .  "<br />" . $contactArr["electronicMailAddress"];
		}
		$owner = substr($owner, 12);
	} else {
		$contact = $owner;
	}

	$rights = $result[$i]["dataset"]["intellectualRights"]["para"];
	$geo = $result[$i]["dataset"]["coverage"]["geographicCoverage"]["geographicDescription"];
	$desc = "";
	echo "<tr>";
	echo "<td><a>$title</a></td><td>$owner</td><td>$contact</td><td>$rights</td><td>$geo</td><td>$desc</td>";
	echo "</tr>";
}

if(count($result) > 0) {
echo "</table>";
}

function is_associative_array($arr) {
    return array_keys($arr) !== range(0, count($arr) - 1);
}
?>