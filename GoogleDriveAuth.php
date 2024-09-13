<!DOCTYPE html>
<html lang="en">
<head>
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <form method="POST" enctype="multipart/form-data">
        <div class="container">
            <div class="row mt-5">
                <div class="col-md-3"></div>
                <div class="col-md-3">
                    <input class="form-control" type="file" name="upload">
                </div>
                <div class="col-md-1">
                    <input class="btn btn-success" type="submit" name="sub" value="Upload">
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#myModal">
                        Create Folder
                    </button>
                </div>
            </div>
        </div>

        <!-- The Modal -->
        <div class="modal fade" id="myModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h4 class="modal-title">Create Folder</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <!-- Modal body -->
                    <div class="modal-body">
                        <form method="POST">
                            <input class="form-control" type="text" name="folderName" placeholder="Folder Name"> <br>
                            <input class="btn btn-success float-end" type="submit" name="create" value="Create">
                        </form>
                    </div>
                    <!-- Modal footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

<?php
$ClientId = "your-client-id";
$ClientSecret = "yourClientSecret";
$DropboxAccessToken = "DropboxAccessToken";
date_default_timezone_set('Asia/Karachi');

// Function for cURL requests
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

// Handle Google Drive authorization and token
if (isset($_GET["code"]) && !isset($_COOKIE['GoogleDriveToken'])) {
    $Curl = curl_init("https://oauth2.googleapis.com/token");
    $Param = [
        "client_id" => $ClientId,
        "client_secret" => $ClientSecret,
        "code" => $_GET["code"],
        "grant_type" => "authorization_code", 
        "redirect_uri" => "http://localhost/Learing_Project/GoogleDriveDropBox/GoogleDriveAuth.php",
    ];
    $Header = ["Content-Type" => "application/x-www-form-urlencoded"];
    $Response = json_decode(CURL("POST", $Curl, $Param, $Header), true);
    $TokenData = json_encode([
        "access_token" => $Response['access_token'], 
        "refresh_token" => $Response['refresh_token'], 
        "expires_in" => time() + $Response['expires_in']
    ]);
    setcookie('GoogleDriveToken', $TokenData, time() + (86400 * 30), '/');
} else {
    $Token_Data = json_decode($_COOKIE['GoogleDriveToken'], true);
    $access_token = $Token_Data['access_token'];
    $sFolderid = isset($_GET["Folderid"]) && $_GET["Folderid"] != "" ? $_GET["Folderid"] : "root";

    // Refresh token if expired
    if (time() > $Token_Data['expires_in']) {
        $Curl = curl_init("https://oauth2.googleapis.com/token");
        $Param = [
            "client_id" => $ClientId,
            "client_secret" => $ClientSecret,
            "refresh_token" => $Token_Data["refresh_token"],
            "grant_type" => "refresh_token", 
        ];
        $Header = ["Content-Type" => "application/x-www-form-urlencoded"];
        $Response = json_decode(CURL("POST", $Curl, $Param, $Header), true);
        $Token_Data["access_token"] = $Response["access_token"];
        $Token_Data["expires_in"] = time() + $Response["expires_in"];
        setcookie('GoogleDriveToken', json_encode($Token_Data), time() + (86400 * 30), '/');
    }

    // Handle file upload
    if (isset($_POST["sub"])) {
        $sFolderPath = isset($_GET["FolderRoute"]) && $_GET["FolderRoute"] != "" ? $_GET["FolderRoute"] : "";
        $file = $_FILES["upload"];
        $dir = "IMG";
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        $fileName = rand(0000,9999) . "_" . str_replace(" ", "", $file["name"]);
        $filePath = $dir . "/" . $fileName;
        $fileTmp_Name = $file["tmp_name"];
        move_uploaded_file($fileTmp_Name, $filePath);
        
        $fileContent = file_get_contents($filePath);
        $Curl = curl_init("https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart");
        $boundary = uniqid();
        $Param = [
            "--$boundary",
            'Content-Type: application/json; charset=UTF-8',
            'Content-Disposition: form-data; name="metadata"',
            '',
            json_encode([
                'name' => $fileName,
                'parents' => [$sFolderid],
            ]),
            "--$boundary",
            'Content-Type: application/octet-stream',
            'Content-Disposition: form-data; name="file"; filename="' . $fileName . '"',
            '',
            $fileContent,
            "--$boundary--",
        ];
        $Header = [
            "Authorization: Bearer " . $Token_Data["access_token"],
            'Content-Type: multipart/related; boundary=' . $boundary,
        ];
        $Response = json_decode(CURL("POST", $Curl, implode("\r\n", $Param), $Header), true);
    }

    // Handle folder creation
    if (isset($_POST["create"])) {
        $sFoldeName = $_POST['folderName'];
        $Curl = curl_init("https://www.googleapis.com/drive/v3/files?fields=id");
        $Header = [
            'Authorization: Bearer ' . $Token_Data['access_token'],
            'Content-Type: application/json',
        ];
        $Param = json_encode([
            "name" => $sFoldeName,
            "mimeType" => "application/vnd.google-apps.folder",
            "parents" => [$sFolderid],
        ]);
        $Response = CURL("POST", $Curl, $Param, $Header);
    }

    // Handle file download
    if (isset($_GET['downloadFileId'])) {
        $fileId = $_GET['downloadFileId'];
        $fileName = $_GET['fileName'];
        $Curl = curl_init("https://www.googleapis.com/drive/v3/files/$fileId?alt=media");
        $Header = [
            "Authorization: Bearer " . $Token_Data["access_token"],
        ];
        curl_setopt($Curl, CURLOPT_HTTPHEADER, $Header);
        curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);
        $fileContent = curl_exec($Curl);
        curl_close($Curl);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        echo $fileContent;
        exit();
    }

    // Handle file deletion
    if (isset($_GET['deleteFileId'])) {
        $fileId = $_GET['deleteFileId'];
        $Curl = curl_init("https://www.googleapis.com/drive/v3/files/$fileId");
        $Header = [
            "Authorization: Bearer " . $Token_Data["access_token"],
        ];
        curl_setopt($Curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($Curl, CURLOPT_HTTPHEADER, $Header);
        curl_exec($Curl);
        curl_close($Curl);
        header("Location: GoogleDriveAuth.php");
        exit();
    }

    // Move file to Dropbox
    if (isset($_GET['moveToDropboxFileId'])) {
       
        $fileId = $_GET['moveToDropboxFileId'];
        $fileName = $_GET['fileName'];
        
        // Download file from Google Drive
        $Curl = curl_init("https://www.googleapis.com/drive/v3/files/$fileId?alt=media");
        $Header = [
            "Authorization: Bearer " . $Token_Data["access_token"],
        ];
        
        curl_setopt($Curl, CURLOPT_HTTPHEADER, $Header);
        curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);
        $fileContent = curl_exec($Curl);
        curl_close($Curl);

        // Upload file to Dropbox
        $Curl = curl_init("https://content.dropboxapi.com/2/files/upload");
        $Param = json_encode([
            "path" => "/$fileName",
            "mode" => "add",
            "autorename" => true,
            "mute" => false,
        ]);
        $Header = [
            "Authorization: Bearer " . $DropboxAccessToken,
            "Content-Type: application/octet-stream",
            "Dropbox-API-Arg: " . $Param,
        ];
        curl_setopt($Curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($Curl, CURLOPT_HTTPHEADER, $Header);
        curl_setopt($Curl, CURLOPT_POSTFIELDS, $fileContent);
        curl_exec($Curl);
        curl_close($Curl);

        header("Location: GoogleDriveAuth.php");
        exit();
    }

    // List files
    $Curl = curl_init("https://www.googleapis.com/drive/v3/files?q='" . $sFolderid . "'+in+parents");
    $Header = [
        "Authorization: Bearer " . $Token_Data["access_token"],
    ];
    $Response = json_decode(CURL("GET", $Curl, '', $Header), true);
    $Sno = 1;
    echo '<div class="container mt-5">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>File Name</th>
                </tr>
            </thead>
            <tbody>';
    foreach ($Response['files'] as $value) {
        if ($value["mimeType"] == "application/vnd.google-apps.folder") {
            $sName = '<a href="GoogleDriveAuth.php?Folderid=' . $value["id"] . '" class="btn btn-primary">' . $value["name"] . '</a>';
        } else {
            $viewLink = '<a href="ViewFile.php?Fileid=' . $value["id"] . '&fileName=' . $value["name"] . '" target="_blank" class="btn btn-light">View</a>';
            $downloadLink = '<a href="GoogleDriveAuth.php?downloadFileId=' . $value["id"] . '&fileName=' . $value["name"] . '" class="btn btn-success">Download</a>';
            $deleteLink = '<a href="GoogleDriveAuth.php?deleteFileId=' . $value["id"] . '" class="btn btn-danger" onclick="return confirm(\'Are you sure you want to delete this file?\');">Delete</a>';
            $moveToDropboxLink = '<a href="GoogleDriveAuth.php?moveToDropboxFileId=' . $value["id"] . '&fileName=' . $value["name"] . '" class="btn btn-warning" onclick="return confirm(\'Are you sure you want to move this file to Dropbox?\');">Move to Dropbox</a>';
            $sName = $value["name"] . ' ' . $viewLink . ' ' . $downloadLink . ' ' . $deleteLink. ' ' . $moveToDropboxLink;
        }
        echo "<tr align='center'>
            <td>$Sno</td>
            <td>" . $sName . "</td>
        </tr>";
        $Sno++;
    }
    echo '</tbody></table></div>';
}
?>
</body>
</html>
