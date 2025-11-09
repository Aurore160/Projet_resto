<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de paiement - Miam Miam</title>
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
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        .header img {
            height: 60px;
            width: auto;
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
        .receipt-info {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .receipt-info h2 {
            margin-top: 0;
            color: #cfbd97;
            font-size: 20px;
            border-bottom: 2px solid #cfbd97;
            padding-bottom: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        .success-badge {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin: 20px 0;
            font-weight: bold;
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
            <div class="header-content">
                @if(file_exists(public_path('logo.jpg')))
                <img src="{{ asset('logo.jpg') }}" alt="Miam Miam Logo" />
                @endif
                <h1>Miam Miam</h1>
            </div>
        </div>
        
        <div class="content">
            <div class="greeting">
                Bonjour {{ $payment->commande->utilisateur->prenom }} {{ $payment->commande->utilisateur->nom }},
            </div>
            
            <div class="success-badge">
                Paiement effectué avec succès
            </div>
            
            <p>Nous vous confirmons la réception de votre paiement. Votre reçu détaillé est joint à cet email en format PDF.</p>
            
            <div class="receipt-info">
                <h2>Informations du paiement</h2>
                <div class="info-row">
                    <span class="info-label">Numéro de commande :</span>
                    <span class="info-value"><strong>#{{ $payment->commande->numero_commande }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Référence transaction :</span>
                    <span class="info-value">{{ $payment->transaction_ref ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Montant payé :</span>
                    <span class="info-value"><strong>{{ number_format($payment->montant, 0, ',', ' ') }} FC</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Méthode de paiement :</span>
                    <span class="info-value">
                        @if($payment->methode === 'carte_bancaire')
                            Carte bancaire
                        @elseif($payment->methode === 'mobile_money')
                            Mobile Money
                        @else
                            {{ ucfirst(str_replace('_', ' ', $payment->methode)) }}
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date de paiement :</span>
                    <span class="info-value">{{ $payment->date_payment ? $payment->date_payment->format('d/m/Y à H:i') : 'N/A' }}</span>
                </div>
            </div>

            <div class="receipt-info">
                <h2>Détails de la commande</h2>
                <div class="info-row">
                    <span class="info-label">Type de commande :</span>
                    <span class="info-value">
                        @if($payment->commande->type_commande === 'livraison')
                            Livraison
                        @else
                            À emporter
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut :</span>
                    <span class="info-value">{{ ucfirst(str_replace('_', ' ', $payment->commande->statut)) }}</span>
                </div>
            </div>

            <p style="margin-top: 30px; color: #666;">
                Merci pour votre confiance ! Votre commande est en cours de préparation.
            </p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Miam Miam. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>




<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de paiement - Miam Miam</title>
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
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        .header img {
            height: 60px;
            width: auto;
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
        .receipt-info {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .receipt-info h2 {
            margin-top: 0;
            color: #cfbd97;
            font-size: 20px;
            border-bottom: 2px solid #cfbd97;
            padding-bottom: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        .success-badge {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin: 20px 0;
            font-weight: bold;
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
            <div class="header-content">
                @if(file_exists(public_path('logo.jpg')))
                <img src="{{ asset('logo.jpg') }}" alt="Miam Miam Logo" />
                @endif
                <h1>Miam Miam</h1>
            </div>
        </div>
        
        <div class="content">
            <div class="greeting">
                Bonjour {{ $payment->commande->utilisateur->prenom }} {{ $payment->commande->utilisateur->nom }},
            </div>
            
            <div class="success-badge">
                Paiement effectué avec succès
            </div>
            
            <p>Nous vous confirmons la réception de votre paiement. Votre reçu détaillé est joint à cet email en format PDF.</p>
            
            <div class="receipt-info">
                <h2>Informations du paiement</h2>
                <div class="info-row">
                    <span class="info-label">Numéro de commande :</span>
                    <span class="info-value"><strong>#{{ $payment->commande->numero_commande }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Référence transaction :</span>
                    <span class="info-value">{{ $payment->transaction_ref ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Montant payé :</span>
                    <span class="info-value"><strong>{{ number_format($payment->montant, 0, ',', ' ') }} FC</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Méthode de paiement :</span>
                    <span class="info-value">
                        @if($payment->methode === 'carte_bancaire')
                            Carte bancaire
                        @elseif($payment->methode === 'mobile_money')
                            Mobile Money
                        @else
                            {{ ucfirst(str_replace('_', ' ', $payment->methode)) }}
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date de paiement :</span>
                    <span class="info-value">{{ $payment->date_payment ? $payment->date_payment->format('d/m/Y à H:i') : 'N/A' }}</span>
                </div>
            </div>

            <div class="receipt-info">
                <h2>Détails de la commande</h2>
                <div class="info-row">
                    <span class="info-label">Type de commande :</span>
                    <span class="info-value">
                        @if($payment->commande->type_commande === 'livraison')
                            Livraison
                        @else
                            À emporter
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut :</span>
                    <span class="info-value">{{ ucfirst(str_replace('_', ' ', $payment->commande->statut)) }}</span>
                </div>
            </div>

            <p style="margin-top: 30px; color: #666;">
                Merci pour votre confiance ! Votre commande est en cours de préparation.
            </p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Miam Miam. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
























































































































































































<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de paiement - Miam Miam</title>
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
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        .header img {
            height: 60px;
            width: auto;
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
        .receipt-info {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .receipt-info h2 {
            margin-top: 0;
            color: #cfbd97;
            font-size: 20px;
            border-bottom: 2px solid #cfbd97;
            padding-bottom: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        .success-badge {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin: 20px 0;
            font-weight: bold;
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
            <div class="header-content">
                @if(file_exists(public_path('logo.jpg')))
                <img src="{{ asset('logo.jpg') }}" alt="Miam Miam Logo" />
                @endif
                <h1>Miam Miam</h1>
            </div>
        </div>
        
        <div class="content">
            <div class="greeting">
                Bonjour {{ $payment->commande->utilisateur->prenom }} {{ $payment->commande->utilisateur->nom }},
            </div>
            
            <div class="success-badge">
                Paiement effectué avec succès
            </div>
            
            <p>Nous vous confirmons la réception de votre paiement. Votre reçu détaillé est joint à cet email en format PDF.</p>
            
            <div class="receipt-info">
                <h2>Informations du paiement</h2>
                <div class="info-row">
                    <span class="info-label">Numéro de commande :</span>
                    <span class="info-value"><strong>#{{ $payment->commande->numero_commande }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Référence transaction :</span>
                    <span class="info-value">{{ $payment->transaction_ref ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Montant payé :</span>
                    <span class="info-value"><strong>{{ number_format($payment->montant, 0, ',', ' ') }} FC</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Méthode de paiement :</span>
                    <span class="info-value">
                        @if($payment->methode === 'carte_bancaire')
                            Carte bancaire
                        @elseif($payment->methode === 'mobile_money')
                            Mobile Money
                        @else
                            {{ ucfirst(str_replace('_', ' ', $payment->methode)) }}
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date de paiement :</span>
                    <span class="info-value">{{ $payment->date_payment ? $payment->date_payment->format('d/m/Y à H:i') : 'N/A' }}</span>
                </div>
            </div>

            <div class="receipt-info">
                <h2>Détails de la commande</h2>
                <div class="info-row">
                    <span class="info-label">Type de commande :</span>
                    <span class="info-value">
                        @if($payment->commande->type_commande === 'livraison')
                            Livraison
                        @else
                            À emporter
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut :</span>
                    <span class="info-value">{{ ucfirst(str_replace('_', ' ', $payment->commande->statut)) }}</span>
                </div>
            </div>

            <p style="margin-top: 30px; color: #666;">
                Merci pour votre confiance ! Votre commande est en cours de préparation.
            </p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Miam Miam. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>




<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de paiement - Miam Miam</title>
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
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        .header img {
            height: 60px;
            width: auto;
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
        .receipt-info {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .receipt-info h2 {
            margin-top: 0;
            color: #cfbd97;
            font-size: 20px;
            border-bottom: 2px solid #cfbd97;
            padding-bottom: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        .success-badge {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin: 20px 0;
            font-weight: bold;
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
            <div class="header-content">
                @if(file_exists(public_path('logo.jpg')))
                <img src="{{ asset('logo.jpg') }}" alt="Miam Miam Logo" />
                @endif
                <h1>Miam Miam</h1>
            </div>
        </div>
        
        <div class="content">
            <div class="greeting">
                Bonjour {{ $payment->commande->utilisateur->prenom }} {{ $payment->commande->utilisateur->nom }},
            </div>
            
            <div class="success-badge">
                Paiement effectué avec succès
            </div>
            
            <p>Nous vous confirmons la réception de votre paiement. Votre reçu détaillé est joint à cet email en format PDF.</p>
            
            <div class="receipt-info">
                <h2>Informations du paiement</h2>
                <div class="info-row">
                    <span class="info-label">Numéro de commande :</span>
                    <span class="info-value"><strong>#{{ $payment->commande->numero_commande }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Référence transaction :</span>
                    <span class="info-value">{{ $payment->transaction_ref ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Montant payé :</span>
                    <span class="info-value"><strong>{{ number_format($payment->montant, 0, ',', ' ') }} FC</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Méthode de paiement :</span>
                    <span class="info-value">
                        @if($payment->methode === 'carte_bancaire')
                            Carte bancaire
                        @elseif($payment->methode === 'mobile_money')
                            Mobile Money
                        @else
                            {{ ucfirst(str_replace('_', ' ', $payment->methode)) }}
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date de paiement :</span>
                    <span class="info-value">{{ $payment->date_payment ? $payment->date_payment->format('d/m/Y à H:i') : 'N/A' }}</span>
                </div>
            </div>

            <div class="receipt-info">
                <h2>Détails de la commande</h2>
                <div class="info-row">
                    <span class="info-label">Type de commande :</span>
                    <span class="info-value">
                        @if($payment->commande->type_commande === 'livraison')
                            Livraison
                        @else
                            À emporter
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut :</span>
                    <span class="info-value">{{ ucfirst(str_replace('_', ' ', $payment->commande->statut)) }}</span>
                </div>
            </div>

            <p style="margin-top: 30px; color: #666;">
                Merci pour votre confiance ! Votre commande est en cours de préparation.
            </p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Miam Miam. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>





















































































































































































