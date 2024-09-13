<!DOCTYPE html>
<html lang="en">
<head>
    <title>Google Drive Authorization</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f7f6;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .auth-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .auth-container h1 {
            color: #5f51fb;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .auth-btn {
            background-color: #5f51fb;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .auth-btn:hover {
            background-color: #3e3ab9;
            box-shadow: 0 6px 12px rgba(95, 81, 251, 0.3);
        }
    </style>
</head>
<body>  
    <div class="auth-container">
        <h1>Access Google Drive</h1>
        <a class="auth-btn" href="https://accounts.google.com/o/oauth2/v2/auth?scope=https://www.googleapis.com/auth/drive&access_type=offline&include_granted_scopes=true&response_type=code&redirect_uri=http://localhost/Learing_Project/GoogleDriveDropBox/GoogleDriveAuth.php&client_id=yourclintid">
            Click to Get Access
        </a>
    </div>
</body>
</html>
