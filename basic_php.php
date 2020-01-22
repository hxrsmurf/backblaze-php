<?php

function breakLines(){ // For debugging
	echo "<br>";
	echo "<hr>";
};

function getCredentials($credentials){
	$application_key_id = "YOURKEYID";
	$application_key = "YOURKEY";
	$credentials = base64_encode($application_key_id . ":" . $application_key);
	return $credentials;
};

function authorizeAccount($url){
	$session = curl_init($url);
	$headers = array();
	$headers[] = "Accept: application/json";
	$headers[] = "Authorization: Basic " . getCredentials($credentials);
	curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($session, CURLOPT_HTTPGET, true);
	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
	$server_output = curl_exec($session);
	curl_close ($session);
	$json = json_decode($server_output,true);
	return $json;
};

/*

For debugging

echo "<hr>";
$url = "https://api.backblazeb2.com/b2api/v2/b2_authorize_account";

	foreach (authorizeAccount($url) as $headers => $keys){
		echo '$' . $headers . '=$variables["' . $headers . '"];';
		if (strpos($headers,'allowed') !== false) {	
			foreach ($keys  as $headers => $keys){
				echo '$' . $headers . '=variables["' . $headers . '"]';
				echo "<br>";
			};
		};
		echo "<br>";
	};
*/

function getDownloadAuthorization(){
		$url = "https://api.backblazeb2.com/b2api/v2/b2_authorize_account";
		$variables = authorizeAccount($url);
		$accountId = $variables['accountId'];
		$bucketId = $variables['allowed']['bucketId'];
		$bucketName = $variables['allowed']['bucketName'];
		$apiUrl = $variables['apiUrl'];
		$authorizationToken = $variables['authorizationToken'];
		$downloadUrl = $variables['downloadUrl'];
		$validDuration = 86400;
		$file_name_prefix = "";

		$data = array("bucketId" => $bucketId,
	              "validDurationInSeconds" => $validDuration,
	              "fileNamePrefix" => $file_name_prefix);
		$post_fields = json_encode($data);

		$session = curl_init($apiUrl .  "/b2api/v2/b2_get_download_authorization");
		curl_setopt($session, CURLOPT_POSTFIELDS, $post_fields);

		$headers = array();
		$headers[] = "Authorization: " . $authorizationToken;
		curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($session, CURLOPT_POST, true); // HTTP POST
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);  // Receive server response
		$server_output = curl_exec($session); // Let's do this!
		curl_close ($session); // Clean up
		$json = json_decode($server_output,true);
		$authorizationToken = $json['authorizationToken'];
		return $authorizationToken;
};

function base64($server_output){
	$image = "data:image/jpeg;base64,";
	$image .= base64_encode($server_output);
	return $image;	
};

function downloadFile($fileName){

	$authorizationToken = getDownloadAuthorization();
        $variables = authorizeAccount("https://api.backblazeb2.com/b2api/v2/b2_authorize_account");
        $bucketName = $variables['allowed']['bucketName'];
        $downloadUrl = $variables['downloadUrl'];
	$uri = $downloadUrl . "/file/" . $bucketName . "/" . $fileName;

	$session = curl_init($uri);

	// Add headers
	$headers = array();
	$headers[] = "Authorization: " . $authorizationToken;
	curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($session, CURLOPT_HTTPGET, true); // HTTP POST
	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);  // Receive server response
	$server_output = curl_exec($session); // Let's do this!
	curl_close ($session); // Clean up
	$output = base64($server_output);
	return $output;

};

function listFile(){

	$url = "https://api.backblazeb2.com/b2api/v2/b2_authorize_account";
        $variables = authorizeAccount($url);
        $authorizationToken = $variables['authorizationToken'];
	$apiUrl = $variables['apiUrl'];
	$bucketId = $variables['allowed']['bucketId'];

	$session = curl_init($apiUrl .  "/b2api/v2/b2_list_file_names");

	$data = array("bucketId" => $bucketId);
	$post_fields = json_encode($data);
	curl_setopt($session, CURLOPT_POSTFIELDS, $post_fields);

	$headers = array();
	$headers[] = "Authorization: " . $authorizationToken;
	curl_setopt($session, CURLOPT_HTTPHEADER, $headers);

	curl_setopt($session, CURLOPT_POST, true);
	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
	$server_output = curl_exec($session);
	curl_close ($session);
	$json = json_decode($server_output,true);
	return $json;
};

$myfiles = array();


foreach (listFile() as $files => $keys){
	foreach ($keys as $names){
		$fileName = $names['fileName'];
		//echo $fileName;
		$myfiles[] = $fileName;
	};

};

echo '<form method="post" action="">';
echo '<Select name="files">';
foreach ($myfiles as $selections){
	echo '<option value="' . $selections . '">' . $selections . ' </option>';
};
echo '</Select>';
echo '<input type="submit">';
echo '</form>';

$postForm = $_POST['files'];

if(!empty($_POST['files'])){
	echo '<img src="' . downloadFile($postForm) . '" width=400 height=400 </img>';
};

?>
