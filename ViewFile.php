<?php
if (isset($_GET["Fileid"])) {
    $Token_Data = json_decode($_COOKIE['GoogleDriveToken'], true);

    $Curl = curl_init("https://www.googleapis.com/drive/v3/files/".$_GET["Fileid"]."?alt=media");

    $Header = [
        "Authorization: Bearer " . $Token_Data["access_token"],
    ];

    $Response = CURL ("GET", $Curl, "", $Header);
    $Response = base64_encode($Response);
    $fileExt = pathinfo($_GET["fileName"]);

    $DocExt = ["pdf", "docx", "xlsx", "ppt", "doc", "txt"];
    $videoExt = ["mp4", "mkv", "avi", "mov", "wmv", "flv", "webm", "m4v", "3gp", "ogg"];
    $audioExt = ["mp3", "wav", "ogg", "aac", "flac", "wma", "m4a", "amr", "mid", "midi"];
    $imageExt = ["jpg", "jpeg", "png", "gif", "bmp", "tiff", "tif", "webp", "svg", "ico"];

    if (isset($fileExt["extension"]) && in_array(strtolower($fileExt["extension"]), $DocExt)) {
        if (strtolower($fileExt["extension"]) != "pdf" || strtolower($fileExt["extension"]) != "txt") {
            if (file_put_contents($_GET["fileName"], $Response)) echo "File Downloaded Successfully.";
        } else {
            echo '<iframe src="data:application/'.$fileExt["extension"].';base64,'.$Response.'" width="100%" height="100%"></iframe>';
        }
    } elseif (isset($fileExt["extension"]) && in_array(strtolower($fileExt["extension"]), $videoExt)) {
        echo '<video controls src="data:video/'.$fileExt["extension"].';base64,'.$Response.'"  width="100%" height="70%"></video>';
    } elseif (isset($fileExt["extension"]) && in_array(strtolower($fileExt["extension"]), $audioExt)) {
        echo '<audio controls src="data:audio/'.$fileExt["extension"].';base64,'.$Response.'"  width="100%" height="70%"></audio>';
    } elseif (isset($fileExt["extension"]) && in_array(strtolower($fileExt["extension"]), $imageExt)) {
        echo '<img src="data:image/'.$fileExt["extension"].';base64,'.$Response.'"  width="100%" height="70%">';
    }  else {
        if (file_put_contents($_GET["fileName"], $Response)) echo "File Downloaded Successfully.";
    }

}

function CURL ($Method, $Curl, $Param, $Header) {
    if ($Method != "GET") {
        curl_setopt($Curl, CURLOPT_POST, true);
    }
    if ($Param != "" || $Param != NULL)
        curl_setopt($Curl, CURLOPT_POSTFIELDS, $Param);
    curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);
    if ($Header != "" || $Header != NULL)
        curl_setopt($Curl, CURLOPT_HTTPHEADER, $Header);
    $Response = curl_exec($Curl);
    curl_close($Curl);
    return $Response;
}


?>