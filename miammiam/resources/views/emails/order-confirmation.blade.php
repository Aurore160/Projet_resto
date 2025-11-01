<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de commande - Miam Miam</title>
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
        .order-info {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .order-info h2 {
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
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th {
            background-color: #cfbd97;
            color: #000;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .total-section {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 16px;
        }
        .total-final {
            font-size: 20px;
            font-weight: bold;
            color: #cfbd97;
            border-top: 2px solid #cfbd97;
            padding-top: 10px;
            margin-top: 10px;
        }
        .delivery-info {
            background-color: #fff9e6;
            padding: 15px;
            border-left: 4px solid #cfbd97;
            margin: 20px 0;
            border-radius: 4px;
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
                Bonjour {{ $commande->utilisateur->prenom }} {{ $commande->utilisateur->nom }},
            </div>
            
            <p>Nous vous confirmons que votre commande a bien été enregistrée !</p>
            
            <div class="order-info">
                <h2>Détails de la commande</h2>
                <div class="info-row">
                    <span class="info-label">Numéro de commande :</span>
                    <span class="info-value"><strong>#{{ $commande->numero_commande }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date de commande :</span>
                    <span class="info-value">{{ $commande->date_commande->format('d/m/Y à H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Type de commande :</span>
                    <span class="info-value">
                        @if($commande->type_commande === 'livraison')
                            Livraison
                        @else
                            À emporter
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut :</span>
                    <span class="info-value">{{ ucfirst(str_replace('_', ' ', $commande->statut)) }}</span>
                </div>
            </div>

            @if($commande->type_commande === 'livraison' && $commande->adresse_livraison)
            <div class="delivery-info">
                <strong>Adresse de livraison :</strong><br>
                {{ $commande->adresse_livraison }}
            </div>
            @endif

            <h3 style="color: #cfbd97; margin-top: 30px;">Articles commandés :</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Plat</th>
                        <th>Quantité</th>
                        <th>Prix unitaire</th>
                        <th>Sous-total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($commande->articles as $article)
                    <tr>
                        <td>{{ $article->menuItem->nom ?? 'Plat supprimé' }}</td>
                        <td>{{ $article->quantite }}</td>
                        <td>{{ number_format($article->prix_unitaire, 0, ',', ' ') }} FC</td>
                        <td>{{ number_format($article->getSousTotal(), 0, ',', ' ') }} FC</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="total-section">
                <div class="total-row">
                    <span>Sous-total :</span>
                    <span>{{ number_format($commande->getTotal(), 0, ',', ' ') }} FC</span>
                </div>
                @if($commande->frais_livraison > 0)
                <div class="total-row">
                    <span>Frais de livraison :</span>
                    <span>{{ number_format($commande->frais_livraison, 0, ',', ' ') }} FC</span>
                </div>
                @endif
                @if($commande->points_utilises > 0)
                <div class="total-row">
                    <span>Points utilisés :</span>
                    <span>- {{ $commande->points_utilises }} points</span>
                </div>
                <div class="total-row">
                    <span>Réduction :</span>
                    <span>- {{ number_format($commande->reduction_points, 0, ',', ' ') }} FC</span>
                </div>
                @endif
                <div class="total-row total-final">
                    <span>Total à payer :</span>
                    <span>{{ number_format($commande->montant_total, 0, ',', ' ') }} FC (Franc Congolais)</span>
                </div>
            </div>

            @if($commande->commentaire)
            <div style="margin-top: 20px; padding: 15px; background-color: #f0f0f0; border-radius: 8px;">
                <strong>Votre commentaire :</strong><br>
                {{ $commande->commentaire }}
            </div>
            @endif

            @if($commande->instructions_speciales)
            <div style="margin-top: 20px; padding: 15px; background-color: #f0f0f0; border-radius: 8px;">
                <strong>Instructions spéciales :</strong><br>
                {{ $commande->instructions_speciales }}
            </div>
            @endif

            <p style="margin-top: 30px; text-align: center; color: #666;">
                Merci pour votre commande ! Nous vous tiendrons informé de l'avancement de votre commande.
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Miam Miam - Tous droits réservés</p>
            <p>
                <a href="#">Nous contacter</a> | 
                <a href="#">Suivre ma commande</a>
            </p>
        </div>
    </div>
</body>
</html>


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de commande - Miam Miam</title>
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
        .order-info {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .order-info h2 {
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
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th {
            background-color: #cfbd97;
            color: #000;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .total-section {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 16px;
        }
        .total-final {
            font-size: 20px;
            font-weight: bold;
            color: #cfbd97;
            border-top: 2px solid #cfbd97;
            padding-top: 10px;
            margin-top: 10px;
        }
        .delivery-info {
            background-color: #fff9e6;
            padding: 15px;
            border-left: 4px solid #cfbd97;
            margin: 20px 0;
            border-radius: 4px;
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
                Bonjour {{ $commande->utilisateur->prenom }} {{ $commande->utilisateur->nom }},
            </div>
            
            <p>Nous vous confirmons que votre commande a bien été enregistrée !</p>
            
            <div class="order-info">
                <h2>Détails de la commande</h2>
                <div class="info-row">
                    <span class="info-label">Numéro de commande :</span>
                    <span class="info-value"><strong>#{{ $commande->numero_commande }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date de commande :</span>
                    <span class="info-value">{{ $commande->date_commande->format('d/m/Y à H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Type de commande :</span>
                    <span class="info-value">
                        @if($commande->type_commande === 'livraison')
                            Livraison
                        @else
                            À emporter
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut :</span>
                    <span class="info-value">{{ ucfirst(str_replace('_', ' ', $commande->statut)) }}</span>
                </div>
            </div>

            @if($commande->type_commande === 'livraison' && $commande->adresse_livraison)
            <div class="delivery-info">
                <strong>Adresse de livraison :</strong><br>
                {{ $commande->adresse_livraison }}
            </div>
            @endif

            <h3 style="color: #cfbd97; margin-top: 30px;">Articles commandés :</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Plat</th>
                        <th>Quantité</th>
                        <th>Prix unitaire</th>
                        <th>Sous-total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($commande->articles as $article)
                    <tr>
                        <td>{{ $article->menuItem->nom ?? 'Plat supprimé' }}</td>
                        <td>{{ $article->quantite }}</td>
                        <td>{{ number_format($article->prix_unitaire, 0, ',', ' ') }} FC</td>
                        <td>{{ number_format($article->getSousTotal(), 0, ',', ' ') }} FC</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="total-section">
                <div class="total-row">
                    <span>Sous-total :</span>
                    <span>{{ number_format($commande->getTotal(), 0, ',', ' ') }} FC</span>
                </div>
                @if($commande->frais_livraison > 0)
                <div class="total-row">
                    <span>Frais de livraison :</span>
                    <span>{{ number_format($commande->frais_livraison, 0, ',', ' ') }} FC</span>
                </div>
                @endif
                @if($commande->points_utilises > 0)
                <div class="total-row">
                    <span>Points utilisés :</span>
                    <span>- {{ $commande->points_utilises }} points</span>
                </div>
                <div class="total-row">
                    <span>Réduction :</span>
                    <span>- {{ number_format($commande->reduction_points, 0, ',', ' ') }} FC</span>
                </div>
                @endif
                <div class="total-row total-final">
                    <span>Total à payer :</span>
                    <span>{{ number_format($commande->montant_total, 0, ',', ' ') }} FC (Franc Congolais)</span>
                </div>
            </div>

            @if($commande->commentaire)
            <div style="margin-top: 20px; padding: 15px; background-color: #f0f0f0; border-radius: 8px;">
                <strong>Votre commentaire :</strong><br>
                {{ $commande->commentaire }}
            </div>
            @endif

            @if($commande->instructions_speciales)
            <div style="margin-top: 20px; padding: 15px; background-color: #f0f0f0; border-radius: 8px;">
                <strong>Instructions spéciales :</strong><br>
                {{ $commande->instructions_speciales }}
            </div>
            @endif

            <p style="margin-top: 30px; text-align: center; color: #666;">
                Merci pour votre commande ! Nous vous tiendrons informé de l'avancement de votre commande.
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Miam Miam - Tous droits réservés</p>
            <p>
                <a href="#">Nous contacter</a> | 
                <a href="#">Suivre ma commande</a>
            </p>
        </div>
    </div>
</body>
</html>













