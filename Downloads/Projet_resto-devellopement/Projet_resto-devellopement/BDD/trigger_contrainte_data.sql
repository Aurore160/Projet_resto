-- Database: restau

-- DROP DATABASE IF EXISTS restau;

CREATE DATABASE restau
    WITH
    OWNER = postgres
    ENCODING = 'UTF8'
    LC_COLLATE = 'English_United States.1252'
    LC_CTYPE = 'English_United States.1252'
    LOCALE_PROVIDER = 'libc'
    TABLESPACE = pg_default
    CONNECTION LIMIT = -1
    IS_TEMPLATE = False;

COMMENT ON DATABASE restau
    IS 'Base de donnee du restaurant miam miam';


-- ======================
-- SECTION 1: TRIGGERS
-- ======================

-- 1.1 Trigger pour générer automatiquement le numéro de commande
CREATE OR REPLACE FUNCTION generer_numero_commande()
RETURNS TRIGGER AS $$
BEGIN
    NEW.numero_commande := 'CMD-' || TO_CHAR(CURRENT_DATE, 'YYYYMMDD-') || LPAD(NEW.id_commande::TEXT, 6, '0');
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_generer_numero_commande
    BEFORE INSERT ON commandes
    FOR EACH ROW
    EXECUTE FUNCTION generer_numero_commande();

-- 1.2 Trigger pour générer le code de parrainage automatique
CREATE OR REPLACE FUNCTION generer_code_parrainage()
RETURNS TRIGGER AS $$
DECLARE
    code VARCHAR(10);
BEGIN
    IF NEW.role = 'etudiant' AND NEW.code_parrainage IS NULL THEN
        LOOP
            code := UPPER(SUBSTRING(MD5(RANDOM()::TEXT) FROM 1 FOR 6));
            IF NOT EXISTS (SELECT 1 FROM utilisateur WHERE code_parrainage = code) THEN
                EXIT;
            END IF;
        END LOOP;
        NEW.code_parrainage := code;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_generer_code_parrainage
    BEFORE INSERT ON utilisateur
    FOR EACH ROW
    EXECUTE FUNCTION generer_code_parrainage();

-- 1.3 Trigger pour valider la complexité du mot de passe
CREATE OR REPLACE FUNCTION valider_mot_de_passe()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.mot_de_passe !~ '[A-Z]' THEN
        RAISE EXCEPTION 'Le mot de passe doit contenir au moins une majuscule';
    END IF;
    IF NEW.mot_de_passe !~ '[0-9]' THEN
        RAISE EXCEPTION 'Le mot de passe doit contenir au moins un chiffre';
    END IF;
    IF LENGTH(NEW.mot_de_passe) < 8 THEN
        RAISE EXCEPTION 'Le mot de passe doit contenir au moins 8 caractères';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_valider_mot_de_passe
    BEFORE INSERT OR UPDATE ON utilisateur
    FOR EACH ROW
    EXECUTE FUNCTION valider_mot_de_passe();

-- 1.4 Trigger pour calculer les points de fidélité (COMMANDES + PARRAINAGE + JEUX)
CREATE OR REPLACE FUNCTION calculer_points_fidelite()
RETURNS TRIGGER AS $$
DECLARE
    points_gagnes INTEGER;
    taux_conversion DECIMAL(10,2);
    points_parrainage INTEGER;
    points_premiere_commande INTEGER;
    parrain_id INTEGER;
    est_premiere_commande BOOLEAN;
BEGIN
    -- Récupérer les paramètres de fidélité
    SELECT taux_conversion, points_parrainage, points_premiere_commande
    INTO taux_conversion, points_parrainage, points_premiere_commande
    FROM parametres_fidelite 
    WHERE actif = true 
    ORDER BY date_debut_application DESC 
    LIMIT 1;

    -- Calculer les points de la commande (1000 FC = 1 point)
    points_gagnes := FLOOR(NEW.montant_total / taux_conversion);
    
    -- Vérifier si c'est la première commande
    SELECT COUNT(*) = 0 INTO est_premiere_commande
    FROM commandes 
    WHERE id_utilisateur = NEW.id_utilisateur 
    AND id_commande != NEW.id_commande
    AND statut = 'livree';

    -- Points pour première commande
    IF est_premiere_commande THEN
        points_gagnes := points_gagnes + points_premiere_commande;
    END IF;

    -- Ajouter les points à l'utilisateur
    UPDATE utilisateur 
    SET points_balance = points_balance + points_gagnes
    WHERE id_utilisateur = NEW.id_utilisateur;

    -- Enregistrer la transaction de points
    INSERT INTO point_transaction (
        id_utilisateur, type_transaction, source, montant_points,
        solde_apres_transaction, id_reference, description
    )
    SELECT 
        NEW.id_utilisateur, 'gain', 'commande', points_gagnes,
        points_balance + points_gagnes, NEW.id_commande,
        'Points commande #' || NEW.numero_commande || 
        CASE WHEN est_premiere_commande THEN ' (+première commande)' ELSE '' END
    FROM utilisateur 
    WHERE id_utilisateur = NEW.id_utilisateur;

    -- Gestion du PARRAINAGE pour la première commande
    IF est_premiere_commande THEN
        SELECT parrain_id INTO parrain_id 
        FROM utilisateur 
        WHERE id_utilisateur = NEW.id_utilisateur;

        IF parrain_id IS NOT NULL THEN
            -- Ajouter les points de parrainage au parrain
            UPDATE utilisateur 
            SET points_balance = points_balance + points_parrainage
            WHERE id_utilisateur = parrain_id;

            -- Enregistrer la transaction de parrainage
            INSERT INTO point_transaction (
                id_utilisateur, type_transaction, source, montant_points,
                solde_apres_transaction, id_reference, description
            )
            SELECT 
                parrain_id, 'gain', 'parrainage', points_parrainage,
                points_balance + points_parrainage, NEW.id_commande,
                'Parrainage filleul: ' || NEW.id_utilisateur
            FROM utilisateur 
            WHERE id_utilisateur = parrain_id;
        END IF;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_calculer_points_fidelite
    AFTER INSERT ON commandes
    FOR EACH ROW
    WHEN (NEW.statut = 'livree')
    EXECUTE FUNCTION calculer_points_fidelite();

-- 1.5 Trigger pour les points des JEUX et ÉVÉNEMENTS
CREATE OR REPLACE FUNCTION attribuer_points_jeux()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.points_gagnes > 0 THEN
        -- Ajouter les points à l'utilisateur
        UPDATE utilisateur 
        SET points_balance = points_balance + NEW.points_gagnes
        WHERE id_utilisateur = NEW.id_utilisateur;

        -- Enregistrer la transaction de points
        INSERT INTO point_transaction (
            id_utilisateur, type_transaction, source, montant_points,
            solde_apres_transaction, id_reference, description
        )
        SELECT 
            NEW.id_utilisateur, 'gain', 'jeu', NEW.points_gagnes,
            points_balance + NEW.points_gagnes, NEW.id_evenement,
            'Points jeu: ' || (SELECT titre FROM evenement WHERE id_evenement = NEW.id_evenement)
        FROM utilisateur 
        WHERE id_utilisateur = NEW.id_utilisateur;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_attribuer_points_jeux
    AFTER INSERT ON participation
    FOR EACH ROW
    WHEN (NEW.points_gagnes > 0)
    EXECUTE FUNCTION attribuer_points_jeux();

-- 1.6 Trigger pour gérer l'UTILISATION des points dans les commandes
CREATE OR REPLACE FUNCTION gerer_utilisation_points()
RETURNS TRIGGER AS $$
DECLARE
    solde_points INTEGER;
    valeur_point DECIMAL(10,2);
    reduction DECIMAL(10,2);
    points_minimum INTEGER;
BEGIN
    IF NEW.points_utilises > 0 THEN
        -- Vérifier le solde
        SELECT points_balance INTO solde_points
        FROM utilisateur WHERE id_utilisateur = NEW.id_utilisateur;

        -- Vérifier le minimum de points
        SELECT points_minimum_utilisation, valeur_point 
        INTO points_minimum, valeur_point
        FROM parametres_fidelite 
        WHERE actif = true 
        LIMIT 1;

        IF solde_points < NEW.points_utilises THEN
            RAISE EXCEPTION 'Solde points insuffisant. Solde: %, Demandés: %', solde_points, NEW.points_utilises;
        END IF;

        IF NEW.points_utilises < points_minimum THEN
            RAISE EXCEPTION 'Minimum % points requis pour utilisation', points_minimum;
        END IF;

        -- Calculer la réduction (15 points = 1000 FC)
        reduction := (NEW.points_utilises * valeur_point) / 15;

        -- Déduire les points
        UPDATE utilisateur 
        SET points_balance = points_balance - NEW.points_utilises
        WHERE id_utilisateur = NEW.id_utilisateur;

        -- Enregistrer la transaction
        INSERT INTO point_transaction (
            id_utilisateur, type_transaction, source, montant_points,
            solde_apres_transaction, id_reference, description
        )
        SELECT 
            NEW.id_utilisateur, 'utilisation', 'commande', -NEW.points_utilises,
            points_balance - NEW.points_utilises, NEW.id_commande,
            'Points utilisés commande #' || NEW.numero_commande || ' (réduction: ' || reduction || ' FCFA)'
        FROM utilisateur 
        WHERE id_utilisateur = NEW.id_utilisateur;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_gerer_utilisation_points
    BEFORE UPDATE OF points_utilises ON commandes
    FOR EACH ROW
    WHEN (NEW.points_utilises != OLD.points_utilises AND NEW.points_utilises > 0)
    EXECUTE FUNCTION gerer_utilisation_points();

-- ======================
-- SECTION 2: FONCTIONS UTILITAIRES
-- ======================

-- 2.1 Fonction pour calculer le solde de points valides
CREATE OR REPLACE FUNCTION get_solde_points_utilisateur(user_id INTEGER)
RETURNS INTEGER AS $$
DECLARE
    solde INTEGER;
    duree_validite INTEGER;
BEGIN
    SELECT duree_validite_points INTO duree_validite
    FROM parametres_fidelite WHERE actif = true LIMIT 1;

    SELECT COALESCE(SUM(montant_points), 0) INTO solde
    FROM point_transaction
    WHERE id_utilisateur = user_id
    AND date_transaction > CURRENT_TIMESTAMP - (duree_validite || ' months')::INTERVAL;

    RETURN solde;
END;
$$ LANGUAGE plpgsql;

-- 2.2 Fonction pour les 10 meilleurs clients
CREATE OR REPLACE FUNCTION get_meilleurs_clients(
    periode VARCHAR DEFAULT 'mois'
) RETURNS TABLE (
    id_utilisateur INTEGER,
    nom_complet TEXT,
    total_commandes BIGINT,
    montant_total DECIMAL,
    points_total INTEGER,
    rang INTEGER
) AS $$
BEGIN
    RETURN QUERY
    WITH classement AS (
        SELECT 
            u.id_utilisateur,
            u.prenom || ' ' || u.nom as nom_complet,
            COUNT(c.id_commande) as total_commandes,
            COALESCE(SUM(c.montant_total), 0) as montant_total,
            u.points_balance as points_total,
            ROW_NUMBER() OVER (ORDER BY COALESCE(SUM(c.montant_total), 0) DESC) as rang
        FROM utilisateur u
        LEFT JOIN commandes c ON u.id_utilisateur = c.id_utilisateur
        WHERE u.role = 'etudiant'
        AND CASE 
            WHEN periode = 'jour' THEN c.date_commande::DATE = CURRENT_DATE
            WHEN periode = 'semaine' THEN c.date_commande >= DATE_TRUNC('week', CURRENT_DATE)
            WHEN periode = 'mois' THEN c.date_commande >= DATE_TRUNC('month', CURRENT_DATE)
            ELSE true
        END
        GROUP BY u.id_utilisateur, u.nom, u.prenom, u.points_balance
    )
    SELECT * FROM classement WHERE rang <= 10
    ORDER BY rang;
END;
$$ LANGUAGE plpgsql;

-- 2.3 Fonction pour utiliser les points comme PAIEMENT COMPLET
CREATE OR REPLACE FUNCTION utiliser_points_paiement_complet(
    p_id_commande INTEGER,
    p_points_utilises INTEGER
) RETURNS DECIMAL AS $$
DECLARE
    v_id_utilisateur INTEGER;
    v_solde_points INTEGER;
    v_montant_commande DECIMAL;
    v_valeur_point DECIMAL;
    v_reduction DECIMAL;
BEGIN
    -- Récupérer infos commande et utilisateur
    SELECT id_utilisateur, montant_total 
    INTO v_id_utilisateur, v_montant_commande
    FROM commandes WHERE id_commande = p_id_commande;

    -- Vérifier solde
    SELECT points_balance INTO v_solde_points
    FROM utilisateur WHERE id_utilisateur = v_id_utilisateur;

    IF v_solde_points < p_points_utilises THEN
        RAISE EXCEPTION 'Solde points insuffisant';
    END IF;

    -- Calculer réduction
    SELECT valeur_point INTO v_valeur_point
    FROM parametres_fidelite WHERE actif = true LIMIT 1;

    v_reduction := (p_points_utilises * v_valeur_point) / 15;

    -- Si réduction >= montant commande, paiement complet
    IF v_reduction >= v_montant_commande THEN
        -- Marquer la commande comme payée avec points
        UPDATE commandes 
        SET points_utilises = p_points_utilises,
            reduction_points = v_montant_commande,
            montant_total = 0
        WHERE id_commande = p_id_commande;

        -- Déduire les points
        UPDATE utilisateur 
        SET points_balance = points_balance - p_points_utilises
        WHERE id_utilisateur = v_id_utilisateur;

        -- Enregistrer paiement
        INSERT INTO payment (
            id_commande, montant, methode, statut_payment, date_payment, description
        ) VALUES (
            p_id_commande, 0, 'points_fidelite', 'paye', CURRENT_TIMESTAMP,
            'Paiement complet avec ' || p_points_utilises || ' points'
        );

        RETURN v_montant_commande;
    ELSE
        RAISE EXCEPTION 'Points insuffisants pour paiement complet. Réduction possible: % FCFA', v_reduction;
    END IF;
END;
$$ LANGUAGE plpgsql;

-- ======================
-- SECTION 3: INDEX POUR PERFORMANCE
-- ======================

-- Index pour les recherches fréquentes
CREATE INDEX idx_utilisateur_email ON utilisateur(email);
CREATE INDEX idx_utilisateur_role ON utilisateur(role);
CREATE INDEX idx_commandes_utilisateur ON commandes(id_utilisateur);
CREATE INDEX idx_commandes_statut ON commandes(statut);
CREATE INDEX idx_commandes_date ON commandes(date_commande);
CREATE INDEX idx_commande_articles_commande ON commande_articles(id_commande);
CREATE INDEX idx_point_transaction_utilisateur ON point_transaction(id_utilisateur);
CREATE INDEX idx_point_transaction_date ON point_transaction(date_transaction);
CREATE INDEX idx_payment_commande ON payment(id_commande);
CREATE INDEX idx_reclamation_utilisateur ON reclamation(id_utilisateur);
CREATE INDEX idx_menu_item_categorie ON menu_item(id_categorie);
CREATE INDEX idx_promotion_dates ON promotion(date_debut, date_fin);
CREATE INDEX idx_participation_utilisateur ON participation(id_utilisateur);
CREATE INDEX idx_evenement_dates ON evenement(date_debut, date_fin);

-- ======================
-- SECTION 4: DONNÉES INITIALES
-- ======================

-- 4.1 Paramètres de fidélité
INSERT INTO parametres_fidelite (
    taux_conversion, valeur_point, points_parrainage, 
    points_premiere_commande, duree_validite_points,
    points_minimum_utilisation, montant_minimum_commande
) VALUES (
    1000, 1000, 50, 20, 12, 15, 0
);

-- 4.2 Catégories
INSERT INTO categories (nom, description, ordre_affichage) VALUES
('Entrées', 'Entrées et salades', 1),
('Plats Principaux', 'Plats et spécialités', 2),
('Desserts', 'Desserts maison', 3),
('Boissons', 'Boissons diverses', 4),
('Menus', 'Menus complets', 5);

-- 4.3 Admin principal
INSERT INTO utilisateur (
    nom, prenom, email, mot_de_passe, telephone, role,
    salaire, date_embauche, role_specifique, matricule
) VALUES (
    'Admin', 'System', 'admin@miammiam.ci', 'Admin123',
    '+243 07 07 07 07 07', 'admin', 350000, CURRENT_DATE,
    'manager', 'ADM-001'
);

-- 4.4 Articles de menu
INSERT INTO menu_item (id_categorie, nom, description, prix, temps_preparation) VALUES
(1, 'Salade Niçoise', 'Salade complète niçoise', 3500, 8),
(2, 'Poulet Braisé', 'Poulet braisé avec alloco', 6000, 15),
(2, 'Poisson Grillé', 'Poisson frais grillé', 7000, 20),
(3, 'Tiramisu', 'Tiramisu maison', 2800, 5),
(4, 'Jus Naturel', 'Jus de fruit naturel', 1800, 2);

-- 4.5 Événement exemple
INSERT INTO evenement (titre, description, type_evenement, date_debut, date_fin, points_gagnants, statut)
VALUES (
    'Concours Menu Spécial',
    'Gagnez des points en découvrant notre nouveau menu',
    'concours',
    CURRENT_DATE,
    CURRENT_DATE + INTERVAL '7 days',
    25,
    'actif'
);