-- ========================================
-- SCRIPT SQL COMPLET - RESTAURANT MIAM MIAM
-- Base de données Supabase (PostgreSQL)
-- Toutes les fonctions sont corrigées avec SET search_path = 'public'
-- ========================================

-- Note: Ne pas exécuter CREATE DATABASE dans Supabase (la base existe déjà)

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- ======================
-- SECTION 1: CRÉATION DES TABLES
-- ======================

-- Table 1: UTILISATEUR (table centrale)
CREATE TABLE IF NOT EXISTS utilisateur (
    id_utilisateur SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    adresse_livraison TEXT,
    adresse_facturation TEXT,
    role VARCHAR(20) NOT NULL CHECK (role IN ('etudiant', 'employe', 'gerant', 'admin')),
    points_balance INTEGER DEFAULT 0,
    code_parrainage VARCHAR(10) UNIQUE,
    parrain_id INTEGER REFERENCES utilisateur(id_utilisateur),
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut_compte VARCHAR(20) DEFAULT 'actif' CHECK (statut_compte IN ('actif', 'inactif', 'suspendu')),
    consentement_cookies BOOLEAN DEFAULT false
);

-- Table 2: CATEGORIES (pour organiser le menu)
CREATE TABLE IF NOT EXISTS categories (
    id_categorie SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    ordre_affichage INTEGER DEFAULT 0,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table 3: MENU_ITEM (les articles du menu)
CREATE TABLE IF NOT EXISTS menu_item (
    id_menuitem SERIAL PRIMARY KEY,
    id_categorie INTEGER REFERENCES categories(id_categorie) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL CHECK (prix >= 0),
    statut_disponibilite BOOLEAN DEFAULT true,
    photo_url VARCHAR(255),
    plat_du_jour BOOLEAN DEFAULT false,
    temps_preparation INTEGER,
    ingredients TEXT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table 4: COMMANDES (gestion des commandes clients)
CREATE TABLE IF NOT EXISTS commandes (
    id_commande SERIAL PRIMARY KEY,
    id_utilisateur INTEGER REFERENCES utilisateur(id_utilisateur) NOT NULL,
    numero_commande VARCHAR(20) UNIQUE NOT NULL,
    statut VARCHAR(20) NOT NULL DEFAULT 'en_attente' 
        CHECK (statut IN ('en_attente', 'confirmee', 'en_preparation', 'pret', 'livree', 'annulee', 'panier')),
    type_commande VARCHAR(20) NOT NULL 
        CHECK (type_commande IN ('sur_place', 'livraison')),
    heure_arrivee_prevue TIMESTAMP,
    adresse_livraison TEXT,
    montant_total DECIMAL(10,2) NOT NULL CHECK (montant_total >= 0),
    points_utilises INTEGER DEFAULT 0 CHECK (points_utilises >= 0),
    reduction_points DECIMAL(10,2) DEFAULT 0 CHECK (reduction_points >= 0),
    frais_livraison DECIMAL(10,2) DEFAULT 0,
    date_commande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    commentaire TEXT,
    instructions_speciales TEXT
);

-- Table 5: COMMANDE_ARTICLES (détail des articles commandés)
CREATE TABLE IF NOT EXISTS commande_articles (
    id_commande_article SERIAL PRIMARY KEY,
    id_commande INTEGER REFERENCES commandes(id_commande) NOT NULL,
    id_menuitem INTEGER REFERENCES menu_item(id_menuitem) NOT NULL,
    quantite INTEGER NOT NULL CHECK (quantite > 0),
    prix_unitaire DECIMAL(10,2) NOT NULL CHECK (prix_unitaire >= 0),
    sous_total DECIMAL(10,2) GENERATED ALWAYS AS (quantite * prix_unitaire) STORED,
    instructions TEXT,
    date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table 6: EMPLOYE (spécifique aux employés)
CREATE TABLE IF NOT EXISTS employe (
    id_employe SERIAL PRIMARY KEY,
    id_utilisateur INTEGER REFERENCES utilisateur(id_utilisateur) UNIQUE NOT NULL,
    matricule VARCHAR(20) UNIQUE NOT NULL,
    salaire DECIMAL(10,2) CHECK (salaire >= 0),
    date_embauche DATE NOT NULL,
    date_fin_contrat DATE,
    statut VARCHAR(20) DEFAULT 'actif' CHECK (statut IN ('actif', 'inactif', 'congé', 'licencie')),
    role_specifique VARCHAR(50) NOT NULL CHECK (role_specifique IN ('cuisinier', 'serveur', 'livreur', 'caissier', 'manager')),
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table 7: PAYMENT (gestion des paiements)
CREATE TABLE IF NOT EXISTS payment (
    id_payment SERIAL PRIMARY KEY,
    id_commande INTEGER REFERENCES commandes(id_commande) NOT NULL,
    montant DECIMAL(10,2) NOT NULL CHECK (montant >= 0),
    methode VARCHAR(50) NOT NULL CHECK (methode IN ('carte_bancaire', 'mobile_money', 'especes', 'points_fidelite')),
    statut_payment VARCHAR(20) NOT NULL DEFAULT 'en_attente' 
        CHECK (statut_payment IN ('en_attente', 'paye', 'echec', 'rembourse', 'annule')),
    date_payment TIMESTAMP,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    transaction_ref VARCHAR(255),
    operateur_mobile_money VARCHAR(50),
    numero_transaction VARCHAR(100),
    carte_last_digits VARCHAR(4),
    carte_type VARCHAR(20),
    frais_transaction DECIMAL(10,2) DEFAULT 0
);

-- Table 8: RECLAMATION (gestion des réclamations)
CREATE TABLE IF NOT EXISTS reclamation (
    id_reclamation SERIAL PRIMARY KEY,
    id_utilisateur INTEGER REFERENCES utilisateur(id_utilisateur) NOT NULL,
    id_commande INTEGER REFERENCES commandes(id_commande),
    id_employe_traitant INTEGER REFERENCES utilisateur(id_utilisateur),
    sujet VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    type_reclamation VARCHAR(50) CHECK (type_reclamation IN ('livraison', 'qualite', 'service', 'paiement', 'autre')),
    priorite VARCHAR(20) DEFAULT 'moyenne' CHECK (priorite IN ('basse', 'moyenne', 'haute', 'urgente')),
    statut_reclamation VARCHAR(20) DEFAULT 'ouverte' 
        CHECK (statut_reclamation IN ('ouverte', 'en_cours', 'resolue', 'fermee', 'rejetee')),
    reponse_employe TEXT,
    date_reclamation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_fermeture TIMESTAMP,
    satisfaction_client INTEGER CHECK (satisfaction_client >= 1 AND satisfaction_client <= 5)
);

-- Table 9: PARAMETRES_FIDELITE (configuration du système de fidélité)
CREATE TABLE IF NOT EXISTS parametres_fidelite (
    id_parametre SERIAL PRIMARY KEY,
    taux_conversion DECIMAL(10,2) NOT NULL DEFAULT 1000,
    valeur_point DECIMAL(10,2) NOT NULL DEFAULT 1000,
    points_parrainage INTEGER NOT NULL DEFAULT 50,
    points_premiere_commande INTEGER DEFAULT 20,
    duree_validite_points INTEGER DEFAULT 12,
    points_minimum_utilisation INTEGER DEFAULT 15,
    montant_minimum_commande DECIMAL(10,2) DEFAULT 0,
    date_debut_application TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_fin_application TIMESTAMP,
    actif BOOLEAN DEFAULT true
);

-- Table 10: POINT_TRANSACTION (historique des points de fidélité)
CREATE TABLE IF NOT EXISTS point_transaction (
    id_pointtransaction SERIAL PRIMARY KEY,
    id_utilisateur INTEGER REFERENCES utilisateur(id_utilisateur) NOT NULL,
    type_transaction VARCHAR(20) NOT NULL CHECK (type_transaction IN ('gain', 'utilisation', 'expiration', 'ajout_manuel', 'retrait_manuel')),
    source VARCHAR(100) NOT NULL,
    montant_points INTEGER NOT NULL,
    solde_apres_transaction INTEGER NOT NULL,
    date_transaction TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_reference INTEGER,
    description TEXT,
    date_expiration TIMESTAMP
);

-- Table 10.5: CONNEXION_LOG (journal des connexions utilisateurs)
CREATE TABLE IF NOT EXISTS connexion_log (
    id SERIAL PRIMARY KEY,
    utilisateur_id INTEGER REFERENCES utilisateur(id_utilisateur) ON DELETE SET NULL,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    statut VARCHAR(20) NOT NULL CHECK (statut IN ('succes', 'echec')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table 11: EVENEMENT (jeux et événements promotionnels)
CREATE TABLE IF NOT EXISTS evenement (
    id_evenement SERIAL PRIMARY KEY,
    titre VARCHAR(100) NOT NULL,
    description TEXT,
    type_evenement VARCHAR(50) NOT NULL CHECK (type_evenement IN ('jeu', 'concours', 'promotion', 'evenement_special')),
    date_debut TIMESTAMP NOT NULL,
    date_fin TIMESTAMP NOT NULL,
    regles TEXT,
    points_gagnants INTEGER DEFAULT 0,
    image_url VARCHAR(255),
    statut VARCHAR(20) DEFAULT 'actif' CHECK (statut IN ('actif', 'inactif', 'termine', 'annule')),
    nombre_participants_max INTEGER,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    createur_id INTEGER REFERENCES utilisateur(id_utilisateur)
);

-- Table 12: PARTICIPATION (participation aux événements)
CREATE TABLE IF NOT EXISTS participation (
    id_participation SERIAL PRIMARY KEY,
    id_utilisateur INTEGER REFERENCES utilisateur(id_utilisateur) NOT NULL,
    id_evenement INTEGER REFERENCES evenement(id_evenement) NOT NULL,
    date_participation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resultat VARCHAR(100),
    points_gagnes INTEGER DEFAULT 0,
    rang INTEGER,
    recompense_supplementaire TEXT,
    statut_participation VARCHAR(20) DEFAULT 'inscrit' CHECK (statut_participation IN ('inscrit', 'participe', 'gagnant', 'perdant', 'abandon')),
    UNIQUE(id_utilisateur, id_evenement)
);

-- Table 13: PROMOTION (promotions et offres spéciales)
CREATE TABLE IF NOT EXISTS promotion (
    id_promo SERIAL PRIMARY KEY,
    titre VARCHAR(100) NOT NULL,
    type_promotion VARCHAR(50) NOT NULL CHECK (type_promotion IN ('pourcentage', 'montant_fixe', 'offre_speciale', 'menu_special')),
    valeur DECIMAL(10,2) NOT NULL,
    valeur_minimum_panier DECIMAL(10,2) DEFAULT 0,
    date_debut TIMESTAMP NOT NULL,
    date_fin TIMESTAMP NOT NULL,
    applicabilite TEXT,
    details TEXT,
    code_promo VARCHAR(50) UNIQUE,
    utilisations_max INTEGER,
    utilisations_actuelles INTEGER DEFAULT 0,
    statut VARCHAR(20) DEFAULT 'active' CHECK (statut IN ('active', 'inactive', 'expiree', 'epuisee')),
    image_url VARCHAR(255),
    createur_id INTEGER REFERENCES utilisateur(id_utilisateur)
);

-- Table 14: PROMO_MENU_ITEM (liaison promotion-articles)
CREATE TABLE IF NOT EXISTS promo_menu_item (
    id_promomenuitem SERIAL PRIMARY KEY,
    id_promo INTEGER REFERENCES promotion(id_promo) NOT NULL,
    id_menuitem INTEGER REFERENCES menu_item(id_menuitem) NOT NULL,
    prix_promotionnel DECIMAL(10,2),
    date_debut TIMESTAMP,
    date_fin TIMESTAMP,
    statut VARCHAR(20) DEFAULT 'active',
    UNIQUE(id_promo, id_menuitem)
);

-- ======================
-- SECTION 2: FONCTIONS AVEC SEARCH_PATH CORRIGÉ
-- ======================

-- 2.1 Trigger pour générer automatiquement le numéro de commande
CREATE OR REPLACE FUNCTION generer_numero_commande()
RETURNS TRIGGER
LANGUAGE plpgsql
SET search_path = 'public'
AS $$
BEGIN
    NEW.numero_commande := 'CMD-' || TO_CHAR(CURRENT_DATE, 'YYYYMMDD-') || LPAD(NEW.id_commande::TEXT, 6, '0');
    RETURN NEW;
END;
$$;

-- 2.2 Trigger pour générer le code de parrainage automatique
CREATE OR REPLACE FUNCTION generer_code_parrainage()
RETURNS TRIGGER
LANGUAGE plpgsql
SET search_path = 'public'
AS $$
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
$$;

-- 2.3 Trigger pour valider la complexité du mot de passe
CREATE OR REPLACE FUNCTION valider_mot_de_passe()
RETURNS TRIGGER
LANGUAGE plpgsql
SET search_path = 'public'
AS $$
BEGIN
    IF NEW.mot_de_passe !~ '[A-Z]' THEN
        RAISE EXCEPTION 'Le mot de passe doit contenir au moins une majuscule';
    END IF;
    IF NEW.mot_de_passe !~ '[0-9]' THEN
        RAISE EXCEPTION 'Le mot de passe doit contenir au moins un chiffre';
    END IF;
    IF LENGTH(NEW.mot_de_passe) < 8 THEN
        RAISE EXCEPTION 'Le mot de passe doit contenir au moins 8 caracteres';
    END IF;
    RETURN NEW;
END;
$$;

-- 2.4 Trigger pour calculer les points de fidélité
CREATE OR REPLACE FUNCTION calculer_points_fidelite()
RETURNS TRIGGER
LANGUAGE plpgsql
SET search_path = 'public'
AS $$
DECLARE
    points_gagnes INTEGER;
    taux_conversion DECIMAL(10,2);
    points_parrainage INTEGER;
    points_premiere_commande INTEGER;
    parrain_id INTEGER;
    est_premiere_commande BOOLEAN;
BEGIN
    SELECT taux_conversion, points_parrainage, points_premiere_commande
    INTO taux_conversion, points_parrainage, points_premiere_commande
    FROM parametres_fidelite 
    WHERE actif = true 
    ORDER BY date_debut_application DESC 
    LIMIT 1;

    points_gagnes := FLOOR(NEW.montant_total / taux_conversion);
    
    SELECT COUNT(*) = 0 INTO est_premiere_commande
    FROM commandes 
    WHERE id_utilisateur = NEW.id_utilisateur 
    AND id_commande != NEW.id_commande
    AND statut = 'livree';

    IF est_premiere_commande THEN
        points_gagnes := points_gagnes + points_premiere_commande;
    END IF;

    UPDATE utilisateur 
    SET points_balance = points_balance + points_gagnes
    WHERE id_utilisateur = NEW.id_utilisateur;

    INSERT INTO point_transaction (
        id_utilisateur, type_transaction, source, montant_points,
        solde_apres_transaction, id_reference, description
    )
    SELECT 
        NEW.id_utilisateur, 'gain', 'commande', points_gagnes,
        points_balance + points_gagnes, NEW.id_commande,
        'Points commande #' || NEW.numero_commande || 
        CASE WHEN est_premiere_commande THEN ' (+premiere commande)' ELSE '' END
    FROM utilisateur 
    WHERE id_utilisateur = NEW.id_utilisateur;

    IF est_premiere_commande THEN
        SELECT parrain_id INTO parrain_id 
        FROM utilisateur 
        WHERE id_utilisateur = NEW.id_utilisateur;

        IF parrain_id IS NOT NULL THEN
            UPDATE utilisateur 
            SET points_balance = points_balance + points_parrainage
            WHERE id_utilisateur = parrain_id;

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
$$;

-- 2.5 Trigger pour les points des JEUX et ÉVÉNEMENTS
CREATE OR REPLACE FUNCTION attribuer_points_jeux()
RETURNS TRIGGER
LANGUAGE plpgsql
SET search_path = 'public'
AS $$
BEGIN
    IF NEW.points_gagnes > 0 THEN
        UPDATE utilisateur 
        SET points_balance = points_balance + NEW.points_gagnes
        WHERE id_utilisateur = NEW.id_utilisateur;

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
$$;

-- 2.6 Trigger pour gérer l'UTILISATION des points dans les commandes
CREATE OR REPLACE FUNCTION gerer_utilisation_points()
RETURNS TRIGGER
LANGUAGE plpgsql
SET search_path = 'public'
AS $$
DECLARE
    solde_points INTEGER;
    valeur_point DECIMAL(10,2);
    reduction DECIMAL(10,2);
    points_minimum INTEGER;
BEGIN
    IF NEW.points_utilises > 0 THEN
        SELECT points_balance INTO solde_points
        FROM utilisateur WHERE id_utilisateur = NEW.id_utilisateur;

        SELECT points_minimum_utilisation, valeur_point 
        INTO points_minimum, valeur_point
        FROM parametres_fidelite 
        WHERE actif = true 
        LIMIT 1;

        IF solde_points < NEW.points_utilises THEN
            RAISE EXCEPTION 'Solde points insuffisant. Solde: %, Demandes: %', solde_points, NEW.points_utilises;
        END IF;

        IF NEW.points_utilises < points_minimum THEN
            RAISE EXCEPTION 'Minimum % points requis pour utilisation', points_minimum;
        END IF;

        reduction := (NEW.points_utilises * valeur_point) / 15;

        UPDATE utilisateur 
        SET points_balance = points_balance - NEW.points_utilises
        WHERE id_utilisateur = NEW.id_utilisateur;

        INSERT INTO point_transaction (
            id_utilisateur, type_transaction, source, montant_points,
            solde_apres_transaction, id_reference, description
        )
        SELECT 
            NEW.id_utilisateur, 'utilisation', 'commande', -NEW.points_utilises,
            points_balance - NEW.points_utilises, NEW.id_commande,
            'Points utilises commande #' || NEW.numero_commande || ' (reduction: ' || reduction || ' FCFA)'
        FROM utilisateur 
        WHERE id_utilisateur = NEW.id_utilisateur;
    END IF;
    RETURN NEW;
END;
$$;

-- 2.7 Fonction pour calculer le solde de points valides
CREATE OR REPLACE FUNCTION get_solde_points_utilisateur(user_id INTEGER)
RETURNS INTEGER
LANGUAGE plpgsql
SET search_path = 'public'
AS $$
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
$$;

-- 2.8 Fonction pour les 10 meilleurs clients
CREATE OR REPLACE FUNCTION get_meilleurs_clients(
    periode VARCHAR DEFAULT 'mois'
) 
RETURNS TABLE (
    id_utilisateur INTEGER,
    nom_complet TEXT,
    total_commandes BIGINT,
    montant_total DECIMAL,
    points_total INTEGER,
    rang BIGINT
)
LANGUAGE plpgsql
SET search_path = 'public'
AS $$
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
$$;

-- 2.9 Fonction pour utiliser les points comme PAIEMENT COMPLET
CREATE OR REPLACE FUNCTION utiliser_points_paiement_complet(
    p_id_commande INTEGER,
    p_points_utilises INTEGER
) 
RETURNS DECIMAL
LANGUAGE plpgsql
SET search_path = 'public'
AS $$
DECLARE
    v_id_utilisateur INTEGER;
    v_solde_points INTEGER;
    v_montant_commande DECIMAL;
    v_valeur_point DECIMAL;
    v_reduction DECIMAL;
BEGIN
    SELECT id_utilisateur, montant_total 
    INTO v_id_utilisateur, v_montant_commande
    FROM commandes WHERE id_commande = p_id_commande;

    SELECT points_balance INTO v_solde_points
    FROM utilisateur WHERE id_utilisateur = v_id_utilisateur;

    IF v_solde_points < p_points_utilises THEN
        RAISE EXCEPTION 'Solde points insuffisant';
    END IF;

    SELECT valeur_point INTO v_valeur_point
    FROM parametres_fidelite WHERE actif = true LIMIT 1;

    v_reduction := (p_points_utilises * v_valeur_point) / 15;

    IF v_reduction >= v_montant_commande THEN
        UPDATE commandes 
        SET points_utilises = p_points_utilises,
            reduction_points = v_montant_commande,
            montant_total = 0
        WHERE id_commande = p_id_commande;

        UPDATE utilisateur 
        SET points_balance = points_balance - p_points_utilises
        WHERE id_utilisateur = v_id_utilisateur;

        INSERT INTO payment (
            id_commande, montant, methode, statut_payment, date_payment, description
        ) VALUES (
            p_id_commande, 0, 'points_fidelite', 'paye', CURRENT_TIMESTAMP,
            'Paiement complet avec ' || p_points_utilises || ' points'
        );

        RETURN v_montant_commande;
    ELSE
        RAISE EXCEPTION 'Points insuffisants pour paiement complet. Reduction possible: % FCFA', v_reduction;
    END IF;
END;
$$;

-- ======================
-- SECTION 3: CRÉATION DES TRIGGERS
-- ======================

CREATE TRIGGER trigger_generer_numero_commande
    BEFORE INSERT ON commandes
    FOR EACH ROW
    EXECUTE FUNCTION generer_numero_commande();

CREATE TRIGGER trigger_generer_code_parrainage
    BEFORE INSERT ON utilisateur
    FOR EACH ROW
    EXECUTE FUNCTION generer_code_parrainage();

CREATE TRIGGER trigger_valider_mot_de_passe
    BEFORE INSERT OR UPDATE ON utilisateur
    FOR EACH ROW
    EXECUTE FUNCTION valider_mot_de_passe();

CREATE TRIGGER trigger_calculer_points_fidelite
    AFTER INSERT ON commandes
    FOR EACH ROW
    WHEN (NEW.statut = 'livree')
    EXECUTE FUNCTION calculer_points_fidelite();

CREATE TRIGGER trigger_attribuer_points_jeux
    AFTER INSERT ON participation
    FOR EACH ROW
    WHEN (NEW.points_gagnes > 0)
    EXECUTE FUNCTION attribuer_points_jeux();

CREATE TRIGGER trigger_gerer_utilisation_points
    BEFORE UPDATE OF points_utilises ON commandes
    FOR EACH ROW
    WHEN (NEW.points_utilises != OLD.points_utilises AND NEW.points_utilises > 0)
    EXECUTE FUNCTION gerer_utilisation_points();

-- ======================
-- SECTION 4: INDEX POUR PERFORMANCE
-- ======================

CREATE INDEX IF NOT EXISTS idx_utilisateur_email ON utilisateur(email);
CREATE INDEX IF NOT EXISTS idx_utilisateur_role ON utilisateur(role);
CREATE INDEX IF NOT EXISTS idx_commandes_utilisateur ON commandes(id_utilisateur);
CREATE INDEX IF NOT EXISTS idx_commandes_statut ON commandes(statut);
CREATE INDEX IF NOT EXISTS idx_commandes_date ON commandes(date_commande);
CREATE INDEX IF NOT EXISTS idx_commande_articles_commande ON commande_articles(id_commande);
CREATE INDEX IF NOT EXISTS idx_point_transaction_utilisateur ON point_transaction(id_utilisateur);
CREATE INDEX IF NOT EXISTS idx_point_transaction_date ON point_transaction(date_transaction);
CREATE INDEX IF NOT EXISTS idx_connexion_log_utilisateur ON connexion_log(utilisateur_id);
CREATE INDEX IF NOT EXISTS idx_connexion_log_email ON connexion_log(email);
CREATE INDEX IF NOT EXISTS idx_connexion_log_ip ON connexion_log(ip_address);
CREATE INDEX IF NOT EXISTS idx_connexion_log_date ON connexion_log(created_at);
CREATE INDEX IF NOT EXISTS idx_payment_commande ON payment(id_commande);
CREATE INDEX IF NOT EXISTS idx_reclamation_utilisateur ON reclamation(id_utilisateur);
CREATE INDEX IF NOT EXISTS idx_menu_item_categorie ON menu_item(id_categorie);
CREATE INDEX IF NOT EXISTS idx_promotion_dates ON promotion(date_debut, date_fin);
CREATE INDEX IF NOT EXISTS idx_participation_utilisateur ON participation(id_utilisateur);
CREATE INDEX IF NOT EXISTS idx_evenement_dates ON evenement(date_debut, date_fin);

-- ======================
-- SECTION 5: DONNÉES INITIALES
-- ======================

-- 5.1 Paramètres de fidélité
INSERT INTO parametres_fidelite (
    taux_conversion, valeur_point, points_parrainage, 
    points_premiere_commande, duree_validite_points,
    points_minimum_utilisation, montant_minimum_commande
) VALUES (
    1000, 1000, 50, 20, 12, 15, 0
)
ON CONFLICT DO NOTHING;

-- 5.2 Catégories
INSERT INTO categories (nom, description, ordre_affichage) VALUES
('Entrées', 'Entrées et salades', 1),
('Plats Principaux', 'Plats et spécialités', 2),
('Desserts', 'Desserts maison', 3),
('Boissons', 'Boissons diverses', 4),
('Menus', 'Menus complets', 5)
ON CONFLICT DO NOTHING;

-- 5.3 Articles de menu
INSERT INTO menu_item (id_categorie, nom, description, prix, temps_preparation) VALUES
(1, 'Salade Niçoise', 'Salade complète niçoise', 3500, 8),
(2, 'Poulet Braisé', 'Poulet braisé avec alloco', 6000, 15),
(2, 'Poisson Grillé', 'Poisson frais grillé', 7000, 20),
(3, 'Tiramisu', 'Tiramisu maison', 2800, 5),
(4, 'Jus Naturel', 'Jus de fruit naturel', 1800, 2)
ON CONFLICT DO NOTHING;

-- 5.4 Événement exemple
INSERT INTO evenement (titre, description, type_evenement, date_debut, date_fin, points_gagnants, statut)
VALUES (
    'Concours Menu Spécial',
    'Gagnez des points en découvrant notre nouveau menu',
    'concours',
    CURRENT_DATE,
    CURRENT_DATE + INTERVAL '7 days',
    25,
    'actif'
)
ON CONFLICT DO NOTHING;

