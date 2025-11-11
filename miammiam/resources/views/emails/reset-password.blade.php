<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation du mot de passe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .header h1 {
            color: #FF6B6B;
            margin: 0;
        }
        .content {
            padding: 30px 0;
            line-height: 1.6;
            color: #333;
        }
        .button {
            display: inline-block;
            padding: 15px 30px;
            background-color: #FF6B6B;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #FF5252;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
            color: #999;
            font-size: 12px;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Miam Miam Restaurant</h1>
        </div>
        
        <div class="content">
            <h2>Réinitialisation de votre mot de passe</h2>
            
            <p>Bonjour,</p>
            
            <p>Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe :</p>
            
            <div style="text-align: center;">
                @php
                    $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
                    $resetUrl = $frontendUrl . '/reset-password?token=' . $token . '&email=' . urlencode($email);
                @endphp
                <a href="{{ $resetUrl }}" class="button">
                    Réinitialiser mon mot de passe
                </a>
            </div>
            
            <p>Ou copiez ce lien dans votre navigateur :</p>
            <p style="background-color: #f9f9f9; padding: 10px; border-radius: 4px; word-break: break-all;">
                {{ $resetUrl }}
            </p>
            
            <div class="warning">
                <strong>⚠️ Important :</strong>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <li>Ce lien est valable pendant <strong>60 minutes</strong></li>
                    <li>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email</li>
                    <li>Votre mot de passe actuel reste inchangé tant que vous n'en créez pas un nouveau</li>
                </ul>
            </div>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Miam Miam Restaurant - Tous droits réservés</p>
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        </div>
    </div>
</body>
</html>



