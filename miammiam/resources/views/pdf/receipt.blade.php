<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de paiement - {{ $commande->numero_commande }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #cfbd97;
            padding-bottom: 20px;
        }
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        .header img {
            height: 60px;
            width: auto;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #cfbd97;
        }
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 12px;
        }
        .receipt-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            color: #28a745;
        }
        .receipt-info {
            margin-bottom: 25px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        .receipt-info h2 {
            margin-top: 0;
            color: #cfbd97;
            font-size: 16px;
            border-bottom: 2px solid #cfbd97;
            padding-bottom: 8px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
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
            font-size: 12px;
        }
        .items-table th {
            background-color: #cfbd97;
            color: #000;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .total-section {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 14px;
        }
        .total-final {
            font-size: 18px;
            font-weight: bold;
            color: #cfbd97;
            border-top: 2px solid #cfbd97;
            padding-top: 10px;
            margin-top: 10px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .success-badge {
            background-color: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-block;
            margin: 20px 0;
            font-weight: bold;
            text-align: center;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            @php
                $logoExists = file_exists($logoPath);
                $logoBase64 = $logoExists ? base64_encode(file_get_contents($logoPath)) : null;
            @endphp
            @if($logoBase64)
            <img src="data:image/jpeg;base64,{{ $logoBase64 }}" alt="Miam Miam Logo" />
            @endif
            <div>
                <h1>Miam Miam</h1>
                <p>Reçu de paiement officiel</p>
            </div>
        </div>
    </div>

    <div class="receipt-title">REÇU DE PAIEMENT</div>

    <div class="success-badge">✓ PAIEMENT EFFECTUÉ AVEC SUCCÈS</div>

    <div class="receipt-info">
        <h2>Informations du paiement</h2>
        <div class="info-row">
            <span class="info-label">Numéro de commande :</span>
            <span class="info-value"><strong>#{{ $commande->numero_commande }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Référence transaction :</span>
            <span class="info-value">{{ $payment->transaction_ref ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date de paiement :</span>
            <span class="info-value">{{ $payment->date_payment ? $payment->date_payment->format('d/m/Y à H:i') : date('d/m/Y à H:i') }}</span>
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
    </div>

    <div class="receipt-info">
        <h2>Informations client</h2>
        <div class="info-row">
            <span class="info-label">Nom complet :</span>
            <span class="info-value">{{ $commande->utilisateur->prenom }} {{ $commande->utilisateur->nom }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email :</span>
            <span class="info-value">{{ $commande->utilisateur->email }}</span>
        </div>
    </div>

    <div class="receipt-info">
        <h2>Détails de la commande</h2>
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
        @if($commande->type_commande === 'livraison' && $commande->adresse_livraison)
        <div class="info-row">
            <span class="info-label">Adresse de livraison :</span>
            <span class="info-value">{{ $commande->adresse_livraison }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Date de commande :</span>
            <span class="info-value">{{ $commande->date_commande->format('d/m/Y à H:i') }}</span>
        </div>
    </div>

    <h3 style="color: #cfbd97; margin-top: 30px;">Articles commandés :</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th>Article</th>
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
                <td>{{ number_format($article->prix_unitaire * $article->quantite, 0, ',', ' ') }} FC</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">
            <span>Sous-total :</span>
            <span>{{ number_format($commande->montant_total - $commande->frais_livraison + $commande->reduction_points, 0, ',', ' ') }} FC</span>
        </div>
        @if($commande->points_utilises > 0)
        <div class="total-row">
            <span>Points utilisés :</span>
            <span>- {{ number_format($commande->reduction_points, 0, ',', ' ') }} FC</span>
        </div>
        @endif
        @if($commande->frais_livraison > 0)
        <div class="total-row">
            <span>Frais de livraison :</span>
            <span>{{ number_format($commande->frais_livraison, 0, ',', ' ') }} FC</span>
        </div>
        @endif
        <div class="total-row total-final">
            <span>MONTANT TOTAL PAYÉ :</span>
            <span>{{ number_format($payment->montant, 0, ',', ' ') }} FC</span>
        </div>
    </div>

    <div class="footer">
        <p><strong>Ce document est un reçu officiel de paiement.</strong></p>
        <p>Miam Miam - {{ date('Y') }}</p>
        <p>Merci pour votre confiance !</p>
    </div>
</body>
</html>


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de paiement - {{ $commande->numero_commande }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #cfbd97;
            padding-bottom: 20px;
        }
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        .header img {
            height: 60px;
            width: auto;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #cfbd97;
        }
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 12px;
        }
        .receipt-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            color: #28a745;
        }
        .receipt-info {
            margin-bottom: 25px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        .receipt-info h2 {
            margin-top: 0;
            color: #cfbd97;
            font-size: 16px;
            border-bottom: 2px solid #cfbd97;
            padding-bottom: 8px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
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
            font-size: 12px;
        }
        .items-table th {
            background-color: #cfbd97;
            color: #000;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .total-section {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 14px;
        }
        .total-final {
            font-size: 18px;
            font-weight: bold;
            color: #cfbd97;
            border-top: 2px solid #cfbd97;
            padding-top: 10px;
            margin-top: 10px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .success-badge {
            background-color: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-block;
            margin: 20px 0;
            font-weight: bold;
            text-align: center;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            @php
                $logoExists = file_exists($logoPath);
                $logoBase64 = $logoExists ? base64_encode(file_get_contents($logoPath)) : null;
            @endphp
            @if($logoBase64)
            <img src="data:image/jpeg;base64,{{ $logoBase64 }}" alt="Miam Miam Logo" />
            @endif
            <div>
                <h1>Miam Miam</h1>
                <p>Reçu de paiement officiel</p>
            </div>
        </div>
    </div>

    <div class="receipt-title">REÇU DE PAIEMENT</div>

    <div class="success-badge">✓ PAIEMENT EFFECTUÉ AVEC SUCCÈS</div>

    <div class="receipt-info">
        <h2>Informations du paiement</h2>
        <div class="info-row">
            <span class="info-label">Numéro de commande :</span>
            <span class="info-value"><strong>#{{ $commande->numero_commande }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Référence transaction :</span>
            <span class="info-value">{{ $payment->transaction_ref ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date de paiement :</span>
            <span class="info-value">{{ $payment->date_payment ? $payment->date_payment->format('d/m/Y à H:i') : date('d/m/Y à H:i') }}</span>
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
    </div>

    <div class="receipt-info">
        <h2>Informations client</h2>
        <div class="info-row">
            <span class="info-label">Nom complet :</span>
            <span class="info-value">{{ $commande->utilisateur->prenom }} {{ $commande->utilisateur->nom }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email :</span>
            <span class="info-value">{{ $commande->utilisateur->email }}</span>
        </div>
    </div>

    <div class="receipt-info">
        <h2>Détails de la commande</h2>
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
        @if($commande->type_commande === 'livraison' && $commande->adresse_livraison)
        <div class="info-row">
            <span class="info-label">Adresse de livraison :</span>
            <span class="info-value">{{ $commande->adresse_livraison }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Date de commande :</span>
            <span class="info-value">{{ $commande->date_commande->format('d/m/Y à H:i') }}</span>
        </div>
    </div>

    <h3 style="color: #cfbd97; margin-top: 30px;">Articles commandés :</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th>Article</th>
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
                <td>{{ number_format($article->prix_unitaire * $article->quantite, 0, ',', ' ') }} FC</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">
            <span>Sous-total :</span>
            <span>{{ number_format($commande->montant_total - $commande->frais_livraison + $commande->reduction_points, 0, ',', ' ') }} FC</span>
        </div>
        @if($commande->points_utilises > 0)
        <div class="total-row">
            <span>Points utilisés :</span>
            <span>- {{ number_format($commande->reduction_points, 0, ',', ' ') }} FC</span>
        </div>
        @endif
        @if($commande->frais_livraison > 0)
        <div class="total-row">
            <span>Frais de livraison :</span>
            <span>{{ number_format($commande->frais_livraison, 0, ',', ' ') }} FC</span>
        </div>
        @endif
        <div class="total-row total-final">
            <span>MONTANT TOTAL PAYÉ :</span>
            <span>{{ number_format($payment->montant, 0, ',', ' ') }} FC</span>
        </div>
    </div>

    <div class="footer">
        <p><strong>Ce document est un reçu officiel de paiement.</strong></p>
        <p>Miam Miam - {{ date('Y') }}</p>
        <p>Merci pour votre confiance !</p>
    </div>
</body>
</html>


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de paiement - {{ $commande->numero_commande }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #cfbd97;
            padding-bottom: 20px;
        }
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        .header img {
            height: 60px;
            width: auto;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #cfbd97;
        }
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 12px;
        }
        .receipt-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            color: #28a745;
        }
        .receipt-info {
            margin-bottom: 25px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        .receipt-info h2 {
            margin-top: 0;
            color: #cfbd97;
            font-size: 16px;
            border-bottom: 2px solid #cfbd97;
            padding-bottom: 8px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
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
            font-size: 12px;
        }
        .items-table th {
            background-color: #cfbd97;
            color: #000;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .total-section {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 14px;
        }
        .total-final {
            font-size: 18px;
            font-weight: bold;
            color: #cfbd97;
            border-top: 2px solid #cfbd97;
            padding-top: 10px;
            margin-top: 10px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .success-badge {
            background-color: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-block;
            margin: 20px 0;
            font-weight: bold;
            text-align: center;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            @php
                $logoExists = file_exists($logoPath);
                $logoBase64 = $logoExists ? base64_encode(file_get_contents($logoPath)) : null;
            @endphp
            @if($logoBase64)
            <img src="data:image/jpeg;base64,{{ $logoBase64 }}" alt="Miam Miam Logo" />
            @endif
            <div>
                <h1>Miam Miam</h1>
                <p>Reçu de paiement officiel</p>
            </div>
        </div>
    </div>

    <div class="receipt-title">REÇU DE PAIEMENT</div>

    <div class="success-badge">✓ PAIEMENT EFFECTUÉ AVEC SUCCÈS</div>

    <div class="receipt-info">
        <h2>Informations du paiement</h2>
        <div class="info-row">
            <span class="info-label">Numéro de commande :</span>
            <span class="info-value"><strong>#{{ $commande->numero_commande }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Référence transaction :</span>
            <span class="info-value">{{ $payment->transaction_ref ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date de paiement :</span>
            <span class="info-value">{{ $payment->date_payment ? $payment->date_payment->format('d/m/Y à H:i') : date('d/m/Y à H:i') }}</span>
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
    </div>

    <div class="receipt-info">
        <h2>Informations client</h2>
        <div class="info-row">
            <span class="info-label">Nom complet :</span>
            <span class="info-value">{{ $commande->utilisateur->prenom }} {{ $commande->utilisateur->nom }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email :</span>
            <span class="info-value">{{ $commande->utilisateur->email }}</span>
        </div>
    </div>

    <div class="receipt-info">
        <h2>Détails de la commande</h2>
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
        @if($commande->type_commande === 'livraison' && $commande->adresse_livraison)
        <div class="info-row">
            <span class="info-label">Adresse de livraison :</span>
            <span class="info-value">{{ $commande->adresse_livraison }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Date de commande :</span>
            <span class="info-value">{{ $commande->date_commande->format('d/m/Y à H:i') }}</span>
        </div>
    </div>

    <h3 style="color: #cfbd97; margin-top: 30px;">Articles commandés :</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th>Article</th>
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
                <td>{{ number_format($article->prix_unitaire * $article->quantite, 0, ',', ' ') }} FC</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">
            <span>Sous-total :</span>
            <span>{{ number_format($commande->montant_total - $commande->frais_livraison + $commande->reduction_points, 0, ',', ' ') }} FC</span>
        </div>
        @if($commande->points_utilises > 0)
        <div class="total-row">
            <span>Points utilisés :</span>
            <span>- {{ number_format($commande->reduction_points, 0, ',', ' ') }} FC</span>
        </div>
        @endif
        @if($commande->frais_livraison > 0)
        <div class="total-row">
            <span>Frais de livraison :</span>
            <span>{{ number_format($commande->frais_livraison, 0, ',', ' ') }} FC</span>
        </div>
        @endif
        <div class="total-row total-final">
            <span>MONTANT TOTAL PAYÉ :</span>
            <span>{{ number_format($payment->montant, 0, ',', ' ') }} FC</span>
        </div>
    </div>

    <div class="footer">
        <p><strong>Ce document est un reçu officiel de paiement.</strong></p>
        <p>Miam Miam - {{ date('Y') }}</p>
        <p>Merci pour votre confiance !</p>
    </div>
</body>
</html>


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de paiement - {{ $commande->numero_commande }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #cfbd97;
            padding-bottom: 20px;
        }
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        .header img {
            height: 60px;
            width: auto;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #cfbd97;
        }
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 12px;
        }
        .receipt-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            color: #28a745;
        }
        .receipt-info {
            margin-bottom: 25px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        .receipt-info h2 {
            margin-top: 0;
            color: #cfbd97;
            font-size: 16px;
            border-bottom: 2px solid #cfbd97;
            padding-bottom: 8px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
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
            font-size: 12px;
        }
        .items-table th {
            background-color: #cfbd97;
            color: #000;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .total-section {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 14px;
        }
        .total-final {
            font-size: 18px;
            font-weight: bold;
            color: #cfbd97;
            border-top: 2px solid #cfbd97;
            padding-top: 10px;
            margin-top: 10px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .success-badge {
            background-color: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-block;
            margin: 20px 0;
            font-weight: bold;
            text-align: center;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            @php
                $logoExists = file_exists($logoPath);
                $logoBase64 = $logoExists ? base64_encode(file_get_contents($logoPath)) : null;
            @endphp
            @if($logoBase64)
            <img src="data:image/jpeg;base64,{{ $logoBase64 }}" alt="Miam Miam Logo" />
            @endif
            <div>
                <h1>Miam Miam</h1>
                <p>Reçu de paiement officiel</p>
            </div>
        </div>
    </div>

    <div class="receipt-title">REÇU DE PAIEMENT</div>

    <div class="success-badge">✓ PAIEMENT EFFECTUÉ AVEC SUCCÈS</div>

    <div class="receipt-info">
        <h2>Informations du paiement</h2>
        <div class="info-row">
            <span class="info-label">Numéro de commande :</span>
            <span class="info-value"><strong>#{{ $commande->numero_commande }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Référence transaction :</span>
            <span class="info-value">{{ $payment->transaction_ref ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date de paiement :</span>
            <span class="info-value">{{ $payment->date_payment ? $payment->date_payment->format('d/m/Y à H:i') : date('d/m/Y à H:i') }}</span>
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
    </div>

    <div class="receipt-info">
        <h2>Informations client</h2>
        <div class="info-row">
            <span class="info-label">Nom complet :</span>
            <span class="info-value">{{ $commande->utilisateur->prenom }} {{ $commande->utilisateur->nom }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email :</span>
            <span class="info-value">{{ $commande->utilisateur->email }}</span>
        </div>
    </div>

    <div class="receipt-info">
        <h2>Détails de la commande</h2>
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
        @if($commande->type_commande === 'livraison' && $commande->adresse_livraison)
        <div class="info-row">
            <span class="info-label">Adresse de livraison :</span>
            <span class="info-value">{{ $commande->adresse_livraison }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Date de commande :</span>
            <span class="info-value">{{ $commande->date_commande->format('d/m/Y à H:i') }}</span>
        </div>
    </div>

    <h3 style="color: #cfbd97; margin-top: 30px;">Articles commandés :</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th>Article</th>
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
                <td>{{ number_format($article->prix_unitaire * $article->quantite, 0, ',', ' ') }} FC</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">
            <span>Sous-total :</span>
            <span>{{ number_format($commande->montant_total - $commande->frais_livraison + $commande->reduction_points, 0, ',', ' ') }} FC</span>
        </div>
        @if($commande->points_utilises > 0)
        <div class="total-row">
            <span>Points utilisés :</span>
            <span>- {{ number_format($commande->reduction_points, 0, ',', ' ') }} FC</span>
        </div>
        @endif
        @if($commande->frais_livraison > 0)
        <div class="total-row">
            <span>Frais de livraison :</span>
            <span>{{ number_format($commande->frais_livraison, 0, ',', ' ') }} FC</span>
        </div>
        @endif
        <div class="total-row total-final">
            <span>MONTANT TOTAL PAYÉ :</span>
            <span>{{ number_format($payment->montant, 0, ',', ' ') }} FC</span>
        </div>
    </div>

    <div class="footer">
        <p><strong>Ce document est un reçu officiel de paiement.</strong></p>
        <p>Miam Miam - {{ date('Y') }}</p>
        <p>Merci pour votre confiance !</p>
    </div>
</body>
</html>






















































































































































































<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de paiement - {{ $commande->numero_commande }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #cfbd97;
            padding-bottom: 20px;
        }
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        .header img {
            height: 60px;
            width: auto;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #cfbd97;
        }
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 12px;
        }
        .receipt-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            color: #28a745;
        }
        .receipt-info {
            margin-bottom: 25px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        .receipt-info h2 {
            margin-top: 0;
            color: #cfbd97;
            font-size: 16px;
            border-bottom: 2px solid #cfbd97;
            padding-bottom: 8px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
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
            font-size: 12px;
        }
        .items-table th {
            background-color: #cfbd97;
            color: #000;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .total-section {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 14px;
        }
        .total-final {
            font-size: 18px;
            font-weight: bold;
            color: #cfbd97;
            border-top: 2px solid #cfbd97;
            padding-top: 10px;
            margin-top: 10px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .success-badge {
            background-color: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-block;
            margin: 20px 0;
            font-weight: bold;
            text-align: center;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            @php
                $logoExists = file_exists($logoPath);
                $logoBase64 = $logoExists ? base64_encode(file_get_contents($logoPath)) : null;
            @endphp
            @if($logoBase64)
            <img src="data:image/jpeg;base64,{{ $logoBase64 }}" alt="Miam Miam Logo" />
            @endif
            <div>
                <h1>Miam Miam</h1>
                <p>Reçu de paiement officiel</p>
            </div>
        </div>
    </div>

    <div class="receipt-title">REÇU DE PAIEMENT</div>

    <div class="success-badge">✓ PAIEMENT EFFECTUÉ AVEC SUCCÈS</div>

    <div class="receipt-info">
        <h2>Informations du paiement</h2>
        <div class="info-row">
            <span class="info-label">Numéro de commande :</span>
            <span class="info-value"><strong>#{{ $commande->numero_commande }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Référence transaction :</span>
            <span class="info-value">{{ $payment->transaction_ref ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date de paiement :</span>
            <span class="info-value">{{ $payment->date_payment ? $payment->date_payment->format('d/m/Y à H:i') : date('d/m/Y à H:i') }}</span>
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
    </div>

    <div class="receipt-info">
        <h2>Informations client</h2>
        <div class="info-row">
            <span class="info-label">Nom complet :</span>
            <span class="info-value">{{ $commande->utilisateur->prenom }} {{ $commande->utilisateur->nom }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email :</span>
            <span class="info-value">{{ $commande->utilisateur->email }}</span>
        </div>
    </div>

    <div class="receipt-info">
        <h2>Détails de la commande</h2>
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
        @if($commande->type_commande === 'livraison' && $commande->adresse_livraison)
        <div class="info-row">
            <span class="info-label">Adresse de livraison :</span>
            <span class="info-value">{{ $commande->adresse_livraison }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Date de commande :</span>
            <span class="info-value">{{ $commande->date_commande->format('d/m/Y à H:i') }}</span>
        </div>
    </div>

    <h3 style="color: #cfbd97; margin-top: 30px;">Articles commandés :</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th>Article</th>
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
                <td>{{ number_format($article->prix_unitaire * $article->quantite, 0, ',', ' ') }} FC</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">
            <span>Sous-total :</span>
            <span>{{ number_format($commande->montant_total - $commande->frais_livraison + $commande->reduction_points, 0, ',', ' ') }} FC</span>
        </div>
        @if($commande->points_utilises > 0)
        <div class="total-row">
            <span>Points utilisés :</span>
            <span>- {{ number_format($commande->reduction_points, 0, ',', ' ') }} FC</span>
        </div>
        @endif
        @if($commande->frais_livraison > 0)
        <div class="total-row">
            <span>Frais de livraison :</span>
            <span>{{ number_format($commande->frais_livraison, 0, ',', ' ') }} FC</span>
        </div>
        @endif
        <div class="total-row total-final">
            <span>MONTANT TOTAL PAYÉ :</span>
            <span>{{ number_format($payment->montant, 0, ',', ' ') }} FC</span>
        </div>
    </div>

    <div class="footer">
        <p><strong>Ce document est un reçu officiel de paiement.</strong></p>
        <p>Miam Miam - {{ date('Y') }}</p>
        <p>Merci pour votre confiance !</p>
    </div>
</body>
</html>


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de paiement - {{ $commande->numero_commande }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #cfbd97;
            padding-bottom: 20px;
        }
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        .header img {
            height: 60px;
            width: auto;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #cfbd97;
        }
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 12px;
        }
        .receipt-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            color: #28a745;
        }
        .receipt-info {
            margin-bottom: 25px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        .receipt-info h2 {
            margin-top: 0;
            color: #cfbd97;
            font-size: 16px;
            border-bottom: 2px solid #cfbd97;
            padding-bottom: 8px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
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
            font-size: 12px;
        }
        .items-table th {
            background-color: #cfbd97;
            color: #000;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .total-section {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 14px;
        }
        .total-final {
            font-size: 18px;
            font-weight: bold;
            color: #cfbd97;
            border-top: 2px solid #cfbd97;
            padding-top: 10px;
            margin-top: 10px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .success-badge {
            background-color: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-block;
            margin: 20px 0;
            font-weight: bold;
            text-align: center;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            @php
                $logoExists = file_exists($logoPath);
                $logoBase64 = $logoExists ? base64_encode(file_get_contents($logoPath)) : null;
            @endphp
            @if($logoBase64)
            <img src="data:image/jpeg;base64,{{ $logoBase64 }}" alt="Miam Miam Logo" />
            @endif
            <div>
                <h1>Miam Miam</h1>
                <p>Reçu de paiement officiel</p>
            </div>
        </div>
    </div>

    <div class="receipt-title">REÇU DE PAIEMENT</div>

    <div class="success-badge">✓ PAIEMENT EFFECTUÉ AVEC SUCCÈS</div>

    <div class="receipt-info">
        <h2>Informations du paiement</h2>
        <div class="info-row">
            <span class="info-label">Numéro de commande :</span>
            <span class="info-value"><strong>#{{ $commande->numero_commande }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Référence transaction :</span>
            <span class="info-value">{{ $payment->transaction_ref ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date de paiement :</span>
            <span class="info-value">{{ $payment->date_payment ? $payment->date_payment->format('d/m/Y à H:i') : date('d/m/Y à H:i') }}</span>
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
    </div>

    <div class="receipt-info">
        <h2>Informations client</h2>
        <div class="info-row">
            <span class="info-label">Nom complet :</span>
            <span class="info-value">{{ $commande->utilisateur->prenom }} {{ $commande->utilisateur->nom }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email :</span>
            <span class="info-value">{{ $commande->utilisateur->email }}</span>
        </div>
    </div>

    <div class="receipt-info">
        <h2>Détails de la commande</h2>
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
        @if($commande->type_commande === 'livraison' && $commande->adresse_livraison)
        <div class="info-row">
            <span class="info-label">Adresse de livraison :</span>
            <span class="info-value">{{ $commande->adresse_livraison }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Date de commande :</span>
            <span class="info-value">{{ $commande->date_commande->format('d/m/Y à H:i') }}</span>
        </div>
    </div>

    <h3 style="color: #cfbd97; margin-top: 30px;">Articles commandés :</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th>Article</th>
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
                <td>{{ number_format($article->prix_unitaire * $article->quantite, 0, ',', ' ') }} FC</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">
            <span>Sous-total :</span>
            <span>{{ number_format($commande->montant_total - $commande->frais_livraison + $commande->reduction_points, 0, ',', ' ') }} FC</span>
        </div>
        @if($commande->points_utilises > 0)
        <div class="total-row">
            <span>Points utilisés :</span>
            <span>- {{ number_format($commande->reduction_points, 0, ',', ' ') }} FC</span>
        </div>
        @endif
        @if($commande->frais_livraison > 0)
        <div class="total-row">
            <span>Frais de livraison :</span>
            <span>{{ number_format($commande->frais_livraison, 0, ',', ' ') }} FC</span>
        </div>
        @endif
        <div class="total-row total-final">
            <span>MONTANT TOTAL PAYÉ :</span>
            <span>{{ number_format($payment->montant, 0, ',', ' ') }} FC</span>
        </div>
    </div>

    <div class="footer">
        <p><strong>Ce document est un reçu officiel de paiement.</strong></p>
        <p>Miam Miam - {{ date('Y') }}</p>
        <p>Merci pour votre confiance !</p>
    </div>
</body>
</html>


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de paiement - {{ $commande->numero_commande }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #cfbd97;
            padding-bottom: 20px;
        }
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        .header img {
            height: 60px;
            width: auto;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #cfbd97;
        }
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 12px;
        }
        .receipt-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            color: #28a745;
        }
        .receipt-info {
            margin-bottom: 25px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        .receipt-info h2 {
            margin-top: 0;
            color: #cfbd97;
            font-size: 16px;
            border-bottom: 2px solid #cfbd97;
            padding-bottom: 8px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
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
            font-size: 12px;
        }
        .items-table th {
            background-color: #cfbd97;
            color: #000;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .total-section {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 14px;
        }
        .total-final {
            font-size: 18px;
            font-weight: bold;
            color: #cfbd97;
            border-top: 2px solid #cfbd97;
            padding-top: 10px;
            margin-top: 10px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .success-badge {
            background-color: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-block;
            margin: 20px 0;
            font-weight: bold;
            text-align: center;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            @php
                $logoExists = file_exists($logoPath);
                $logoBase64 = $logoExists ? base64_encode(file_get_contents($logoPath)) : null;
            @endphp
            @if($logoBase64)
            <img src="data:image/jpeg;base64,{{ $logoBase64 }}" alt="Miam Miam Logo" />
            @endif
            <div>
                <h1>Miam Miam</h1>
                <p>Reçu de paiement officiel</p>
            </div>
        </div>
    </div>

    <div class="receipt-title">REÇU DE PAIEMENT</div>

    <div class="success-badge">✓ PAIEMENT EFFECTUÉ AVEC SUCCÈS</div>

    <div class="receipt-info">
        <h2>Informations du paiement</h2>
        <div class="info-row">
            <span class="info-label">Numéro de commande :</span>
            <span class="info-value"><strong>#{{ $commande->numero_commande }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Référence transaction :</span>
            <span class="info-value">{{ $payment->transaction_ref ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date de paiement :</span>
            <span class="info-value">{{ $payment->date_payment ? $payment->date_payment->format('d/m/Y à H:i') : date('d/m/Y à H:i') }}</span>
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
    </div>

    <div class="receipt-info">
        <h2>Informations client</h2>
        <div class="info-row">
            <span class="info-label">Nom complet :</span>
            <span class="info-value">{{ $commande->utilisateur->prenom }} {{ $commande->utilisateur->nom }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email :</span>
            <span class="info-value">{{ $commande->utilisateur->email }}</span>
        </div>
    </div>

    <div class="receipt-info">
        <h2>Détails de la commande</h2>
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
        @if($commande->type_commande === 'livraison' && $commande->adresse_livraison)
        <div class="info-row">
            <span class="info-label">Adresse de livraison :</span>
            <span class="info-value">{{ $commande->adresse_livraison }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Date de commande :</span>
            <span class="info-value">{{ $commande->date_commande->format('d/m/Y à H:i') }}</span>
        </div>
    </div>

    <h3 style="color: #cfbd97; margin-top: 30px;">Articles commandés :</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th>Article</th>
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
                <td>{{ number_format($article->prix_unitaire * $article->quantite, 0, ',', ' ') }} FC</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">
            <span>Sous-total :</span>
            <span>{{ number_format($commande->montant_total - $commande->frais_livraison + $commande->reduction_points, 0, ',', ' ') }} FC</span>
        </div>
        @if($commande->points_utilises > 0)
        <div class="total-row">
            <span>Points utilisés :</span>
            <span>- {{ number_format($commande->reduction_points, 0, ',', ' ') }} FC</span>
        </div>
        @endif
        @if($commande->frais_livraison > 0)
        <div class="total-row">
            <span>Frais de livraison :</span>
            <span>{{ number_format($commande->frais_livraison, 0, ',', ' ') }} FC</span>
        </div>
        @endif
        <div class="total-row total-final">
            <span>MONTANT TOTAL PAYÉ :</span>
            <span>{{ number_format($payment->montant, 0, ',', ' ') }} FC</span>
        </div>
    </div>

    <div class="footer">
        <p><strong>Ce document est un reçu officiel de paiement.</strong></p>
        <p>Miam Miam - {{ date('Y') }}</p>
        <p>Merci pour votre confiance !</p>
    </div>
</body>
</html>


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de paiement - {{ $commande->numero_commande }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #cfbd97;
            padding-bottom: 20px;
        }
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        .header img {
            height: 60px;
            width: auto;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #cfbd97;
        }
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 12px;
        }
        .receipt-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            color: #28a745;
        }
        .receipt-info {
            margin-bottom: 25px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        .receipt-info h2 {
            margin-top: 0;
            color: #cfbd97;
            font-size: 16px;
            border-bottom: 2px solid #cfbd97;
            padding-bottom: 8px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
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
            font-size: 12px;
        }
        .items-table th {
            background-color: #cfbd97;
            color: #000;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .total-section {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 14px;
        }
        .total-final {
            font-size: 18px;
            font-weight: bold;
            color: #cfbd97;
            border-top: 2px solid #cfbd97;
            padding-top: 10px;
            margin-top: 10px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .success-badge {
            background-color: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-block;
            margin: 20px 0;
            font-weight: bold;
            text-align: center;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            @php
                $logoExists = file_exists($logoPath);
                $logoBase64 = $logoExists ? base64_encode(file_get_contents($logoPath)) : null;
            @endphp
            @if($logoBase64)
            <img src="data:image/jpeg;base64,{{ $logoBase64 }}" alt="Miam Miam Logo" />
            @endif
            <div>
                <h1>Miam Miam</h1>
                <p>Reçu de paiement officiel</p>
            </div>
        </div>
    </div>

    <div class="receipt-title">REÇU DE PAIEMENT</div>

    <div class="success-badge">✓ PAIEMENT EFFECTUÉ AVEC SUCCÈS</div>

    <div class="receipt-info">
        <h2>Informations du paiement</h2>
        <div class="info-row">
            <span class="info-label">Numéro de commande :</span>
            <span class="info-value"><strong>#{{ $commande->numero_commande }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Référence transaction :</span>
            <span class="info-value">{{ $payment->transaction_ref ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date de paiement :</span>
            <span class="info-value">{{ $payment->date_payment ? $payment->date_payment->format('d/m/Y à H:i') : date('d/m/Y à H:i') }}</span>
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
    </div>

    <div class="receipt-info">
        <h2>Informations client</h2>
        <div class="info-row">
            <span class="info-label">Nom complet :</span>
            <span class="info-value">{{ $commande->utilisateur->prenom }} {{ $commande->utilisateur->nom }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email :</span>
            <span class="info-value">{{ $commande->utilisateur->email }}</span>
        </div>
    </div>

    <div class="receipt-info">
        <h2>Détails de la commande</h2>
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
        @if($commande->type_commande === 'livraison' && $commande->adresse_livraison)
        <div class="info-row">
            <span class="info-label">Adresse de livraison :</span>
            <span class="info-value">{{ $commande->adresse_livraison }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Date de commande :</span>
            <span class="info-value">{{ $commande->date_commande->format('d/m/Y à H:i') }}</span>
        </div>
    </div>

    <h3 style="color: #cfbd97; margin-top: 30px;">Articles commandés :</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th>Article</th>
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
                <td>{{ number_format($article->prix_unitaire * $article->quantite, 0, ',', ' ') }} FC</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">
            <span>Sous-total :</span>
            <span>{{ number_format($commande->montant_total - $commande->frais_livraison + $commande->reduction_points, 0, ',', ' ') }} FC</span>
        </div>
        @if($commande->points_utilises > 0)
        <div class="total-row">
            <span>Points utilisés :</span>
            <span>- {{ number_format($commande->reduction_points, 0, ',', ' ') }} FC</span>
        </div>
        @endif
        @if($commande->frais_livraison > 0)
        <div class="total-row">
            <span>Frais de livraison :</span>
            <span>{{ number_format($commande->frais_livraison, 0, ',', ' ') }} FC</span>
        </div>
        @endif
        <div class="total-row total-final">
            <span>MONTANT TOTAL PAYÉ :</span>
            <span>{{ number_format($payment->montant, 0, ',', ' ') }} FC</span>
        </div>
    </div>

    <div class="footer">
        <p><strong>Ce document est un reçu officiel de paiement.</strong></p>
        <p>Miam Miam - {{ date('Y') }}</p>
        <p>Merci pour votre confiance !</p>
    </div>
</body>
</html>



















































































































































































