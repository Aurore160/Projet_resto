-- Script SQL pour créer les dépenses de salaires dans la table depense
-- à partir des données de la table employe

-- IMPORTANT : Exécuter d'abord la migration pour rendre plat_id nullable
-- php artisan migrate --path=database/migrations/2024_12_20_000000_modify_depense_table_for_general_expenses.php

-- Option 1 : Créer les salaires pour le mois actuel
INSERT INTO depense (plat_id, montant, type_depense, description, date_depenses)
SELECT 
    NULL as plat_id,
    COALESCE(SUM(salaire), 0) as montant,
    'salaire' as type_depense,
    'Salaires du mois de ' || to_char(CURRENT_DATE, 'Month YYYY') as description,
    date_trunc('month', CURRENT_DATE)::date as date_depenses
FROM employe
WHERE statut = 'actif'
  AND NOT EXISTS (
    SELECT 1 FROM depense d
    WHERE d.type_depense = 'salaire'
      AND d.date_depenses = date_trunc('month', CURRENT_DATE)::date
  );

-- Option 2 : Créer les salaires pour tous les mois de 2024
-- (Décommentez cette section si vous voulez créer les salaires pour toute l'année)
/*
WITH salaires_actifs AS (
    SELECT COALESCE(SUM(salaire), 0) as total_salaires
    FROM employe
    WHERE statut = 'actif'
)
INSERT INTO depense (plat_id, montant, type_depense, description, date_depenses)
SELECT 
    NULL as plat_id,
    sa.total_salaires as montant,
    'salaire' as type_depense,
    'Salaires du mois de ' || to_char(date_val, 'Month YYYY') as description,
    date_trunc('month', date_val)::date as date_depenses
FROM generate_series(
    '2024-01-01'::date,
    '2024-12-01'::date,
    '1 month'::interval
) as date_val
CROSS JOIN salaires_actifs sa
WHERE NOT EXISTS (
    SELECT 1 FROM depense d
    WHERE d.type_depense = 'salaire'
      AND d.date_depenses = date_trunc('month', date_val)::date
);
*/

-- Vérification : Voir les dépenses créées
SELECT 
    date_depenses,
    type_depense,
    montant,
    description
FROM depense
WHERE type_depense = 'salaire'
ORDER BY date_depenses DESC;

-- Vérification : Comparer avec les salaires dans employe
SELECT 
    'Total salaires employés actifs' as libelle,
    COALESCE(SUM(salaire), 0) as montant
FROM employe
WHERE statut = 'actif';





-- IMPORTANT : Exécuter d'abord la migration pour rendre plat_id nullable
-- php artisan migrate --path=database/migrations/2024_12_20_000000_modify_depense_table_for_general_expenses.php

-- Option 1 : Créer les salaires pour le mois actuel
INSERT INTO depense (plat_id, montant, type_depense, description, date_depenses)
SELECT 
    NULL as plat_id,
    COALESCE(SUM(salaire), 0) as montant,
    'salaire' as type_depense,
    'Salaires du mois de ' || to_char(CURRENT_DATE, 'Month YYYY') as description,
    date_trunc('month', CURRENT_DATE)::date as date_depenses
FROM employe
WHERE statut = 'actif'
  AND NOT EXISTS (
    SELECT 1 FROM depense d
    WHERE d.type_depense = 'salaire'
      AND d.date_depenses = date_trunc('month', CURRENT_DATE)::date
  );

-- Option 2 : Créer les salaires pour tous les mois de 2024
-- (Décommentez cette section si vous voulez créer les salaires pour toute l'année)
/*
WITH salaires_actifs AS (
    SELECT COALESCE(SUM(salaire), 0) as total_salaires
    FROM employe
    WHERE statut = 'actif'
)
INSERT INTO depense (plat_id, montant, type_depense, description, date_depenses)
SELECT 
    NULL as plat_id,
    sa.total_salaires as montant,
    'salaire' as type_depense,
    'Salaires du mois de ' || to_char(date_val, 'Month YYYY') as description,
    date_trunc('month', date_val)::date as date_depenses
FROM generate_series(
    '2024-01-01'::date,
    '2024-12-01'::date,
    '1 month'::interval
) as date_val
CROSS JOIN salaires_actifs sa
WHERE NOT EXISTS (
    SELECT 1 FROM depense d
    WHERE d.type_depense = 'salaire'
      AND d.date_depenses = date_trunc('month', date_val)::date
);
*/

-- Vérification : Voir les dépenses créées
SELECT 
    date_depenses,
    type_depense,
    montant,
    description
FROM depense
WHERE type_depense = 'salaire'
ORDER BY date_depenses DESC;

-- Vérification : Comparer avec les salaires dans employe
SELECT 
    'Total salaires employés actifs' as libelle,
    COALESCE(SUM(salaire), 0) as montant
FROM employe
WHERE statut = 'actif';




