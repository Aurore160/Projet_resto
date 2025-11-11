<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code de parrainage - Miam Miam</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #cfbd97 0%, #bda875 100%);
            color: #000;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #333;
        }
        .referral-section {
            background-color: #f9f9f9;
            padding: 25px;
            border-radius: 8px;
            margin: 25px 0;
            text-align: center;
        }
        .referral-code {
            font-size: 32px;
            font-weight: bold;
            color: #cfbd97;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            border: 3px dashed #cfbd97;
            margin: 20px 0;
            letter-spacing: 3px;
            font-family: 'Courier New', monospace;
        }
        .benefits {
            background-color: #fff9e6;
            padding: 20px;
            border-left: 4px solid #cfbd97;
            margin: 20px 0;
            border-radius: 4px;
        }
        .benefits h3 {
            margin-top: 0;
            color: #cfbd97;
        }
        .benefits ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .benefits li {
            margin: 8px 0;
        }
        .cta-button {
            display: inline-block;
            background-color: #cfbd97;
            color: #000;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
        }
        .footer {
            background-color: #333;
            color: #fff;
            padding: 20px;
            text-align: center;
            font-size: 14px;
        }
        .footer a {
            color: #cfbd97;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="display: flex; align-items: center; justify-content: center; gap: 15px;">
                @if(file_exists(public_path('logo.jpg')))
                <img src="{{ asset('logo.jpg') }}" alt="Miam Miam Logo" style="height: 60px; width: auto;" />
                @endif
                <h1>Miam Miam</h1>
            </div>
        </div>
        
        <div class="content">
            <div class="greeting">
                Bonjour,
            </div>
            
            <p>{{ $userName }} vous invite à rejoindre Miam Miam et vous offre un code de parrainage spécial !</p>
            
            <div class="referral-section">
                <h2 style="margin-top: 0; color: #333;">Votre code de parrainage</h2>
                <div class="referral-code">{{ $referralCode }}</div>
                <p style="margin: 0; color: #666;">
                    Utilisez ce code lors de votre inscription pour gagner des points de fidélité dès le départ !
                </p>
            </div>

            <div class="benefits">
                <h3>🎁 Avantages du parrainage</h3>
                <ul>
                    <li><strong>Vous gagnez des points</strong> dès votre inscription</li>
                    <li><strong>Vous gagnez encore plus de points</strong> lors de votre première commande</li>
                    <li><strong>{{ $userName }} gagne aussi des points</strong> quand vous vous inscrivez et commandez</li>
                    <li><strong>1 point = 50 FC</strong> - Utilisez vos points pour réduire le coût de vos commandes</li>
                </ul>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $frontendUrl }}/register?code={{ $referralCode }}" class="cta-button">
                    S'inscrire avec ce code
                </a>
            </div>

            <p style="text-align: center; color: #666; margin-top: 30px;">
                Rejoignez Miam Miam et découvrez une expérience culinaire exceptionnelle !
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Miam Miam - Tous droits réservés</p>
            <p>
                <a href="#">Nous contacter</a> | 
                <a href="#">En savoir plus</a>
            </p>
        </div>
    </div>
</body>
</html>

