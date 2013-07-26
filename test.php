<?php
 //extract data from the post
 
$searchTerm= $_POST["searchTerm"];
extract($_POST);

$url='http://knb.ecoinformatics.org/knb/metacat';
$fields_string='operator=UNION&organizationName=TRAITNET+'.$searchTerm.'&surName=TRAITNET+'.$searchTerm.'&givenName=TRAITNET+'.$searchTerm.'&keyword=TRAITNET+'.$searchTerm.'&para=TRAITNET+'.$searchTerm.'&geographicDescription=TRAITNET+'.$searchTerm.'&literalLayout=TRAITNET+'.$searchTerm.'&abstract%2Fpara=TRAITNET+'.$searchTerm.'&abstract=TRAITNET+'.$searchTerm.'&idinfo%2Fcitation%2Fciteinfo%2Ftitle=TRAITNET+'.$searchTerm.'&idinfo%2Fcitation%2Fciteinfo%2Forigin=TRAITNET+'.$searchTerm.'&idinfo%2Fkeywords%2Ftheme%2Fthemekey=TRAITNET+'.$searchTerm.'&placekey=TRAITNET+'.$searchTerm.'&qformat=html&returnfield=originator%2FindividualName%2FsurName&returnfield=originator%2FindividualName%2FgivenName&returnfield=creator%2FindividualName%2FsurName&returnfield=creator%2FindividualName%2FgivenName&returnfield=originator%2ForganizationName&returnfield=creator%2ForganizationName&returnfield=dataset%2Ftitle&returnfield=keyword&returnfield=idinfo%2Fcitation%2Fciteinfo%2Ftitle&returnfield=idinfo%2Fcitation%2Fciteinfo%2Forigin&returnfield=idinfo%2Fkeywords%2Ftheme%2Fthemekey&returndoctype=eml%3A%2F%2Fecoinformatics.org%2Feml-2.1.0&title=TRAITNET+'.$searchTerm.'&action=query';

$ch = curl_init();
curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);

$result = curl_exec($ch);
//die($result);
$result = preg_replace('/<pre\b[^>]*>(.*?)<\/pre>/is', "", $result);
include('simple_html_dom.php');
$html = str_get_html($result);
$table = $html->find('.resultstable');
echo $table[0];
//close connection
curl_close($ch);

?>