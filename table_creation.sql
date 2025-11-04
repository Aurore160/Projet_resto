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

-- Table 1: UTILISATEUR (table centrale)
CREATE TABLE utilisateur (
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

-- Commentaires pour la table utilisateur
COMMENT ON TABLE utilisateur IS 'Table des utilisateurs du système (étudiants, employés, gérants, admin)';
COMMENT ON COLUMN utilisateur.role IS 'Rôle de l''utilisateur: etudiant, employe, gerant, admin';
COMMENT ON COLUMN utilisateur.code_parrainage IS 'Code unique de parrainage généré automatiquement';
COMMENT ON COLUMN utilisateur.parrain_id IS 'Référence à l''utilisateur parrain';

-- Table 2: CATEGORIES (pour organiser le menu)
CREATE TABLE categories (
    id_categorie SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    ordre_affichage INTEGER DEFAULT 0,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE categories IS 'Catégories des articles du menu (Entrées, Plats, Desserts, Boissons...)';
COMMENT ON COLUMN categories.ordre_affichage IS 'Ordre d''affichage des catégories dans le menu';

-- Table 3: MENU_ITEM (les articles du menu)
CREATE TABLE menu_item (
    id_menuitem SERIAL PRIMARY KEY,
    id_categorie INTEGER REFERENCES categories(id_categorie) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL CHECK (prix >= 0),
    statut_disponibilite BOOLEAN DEFAULT true,
    photo_url VARCHAR(255),
    plat_du_jour BOOLEAN DEFAULT false,
    temps_preparation INTEGER, -- en minutes
    ingredients TEXT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE menu_item IS 'Articles du menu (plats, boissons, desserts...)';
COMMENT ON COLUMN menu_item.temps_preparation IS 'Temps estimé de préparation en minutes';
COMMENT ON COLUMN menu_item.plat_du_jour IS 'Indique si l''article est le plat du jour';

-- Table 4: COMMANDES (gestion des commandes clients)
CREATE TABLE commandes (
    id_commande SERIAL PRIMARY KEY,
    id_utilisateur INTEGER REFERENCES utilisateur(id_utilisateur) NOT NULL,
    numero_commande VARCHAR(20) UNIQUE NOT NULL, -- Numéro unique pour les clients
    statut VARCHAR(20) NOT NULL DEFAULT 'en_attente' 
        CHECK (statut IN ('en_attente', 'confirmee', 'en_preparation', 'pret', 'livree', 'annulee')),
    type_commande VARCHAR(20) NOT NULL 
        CHECK (type_commande IN ('sur_place', 'livraison')),
    heure_arrivee_prevue TIMESTAMP, -- Pour commande sur place
    adresse_livraison TEXT, -- Pour commande livraison
    montant_total DECIMAL(10,2) NOT NULL CHECK (montant_total >= 0),
    points_utilises INTEGER DEFAULT 0 CHECK (points_utilises >= 0),
    reduction_points DECIMAL(10,2) DEFAULT 0 CHECK (reduction_points >= 0),
    frais_livraison DECIMAL(10,2) DEFAULT 0,
    date_commande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    commentaire TEXT, -- Commentaire du client
    instructions_speciales TEXT -- Instructions pour la préparation
);

COMMENT ON TABLE commandes IS 'Commandes passées par les utilisateurs';
COMMENT ON COLUMN commandes.numero_commande IS 'Numéro unique de commande pour référence client';
COMMENT ON COLUMN commandes.type_commande IS 'sur_place ou livraison';
COMMENT ON COLUMN commandes.heure_arrivee_prevue IS 'Heure à laquelle le client compte arriver (sur place)';

-- Table 5: COMMANDE_ARTICLES (détail des articles commandés)
CREATE TABLE commande_articles (
    id_commande_article SERIAL PRIMARY KEY,
    id_commande INTEGER REFERENCES commandes(id_commande) NOT NULL,
    id_menuitem INTEGER REFERENCES menu_item(id_menuitem) NOT NULL,
    quantite INTEGER NOT NULL CHECK (quantite > 0),
    prix_unitaire DECIMAL(10,2) NOT NULL CHECK (prix_unitaire >= 0),
    sous_total DECIMAL(10,2) GENERATED ALWAYS AS (quantite * prix_unitaire) STORED,
    instructions TEXT, -- Instructions spécifiques pour cet article
    date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE commande_articles IS 'Détail des articles dans chaque commande';
COMMENT ON COLUMN commande_articles.prix_unitaire IS 'Prix au moment de la commande (pour historique)';
COMMENT ON COLUMN commande_articles.sous_total IS 'Calcul automatique: quantite * prix_unitaire';

-- Table 6: EMPLOYE (spécifique aux employés du restaurant)
CREATE TABLE employe (
    id_employe SERIAL PRIMARY KEY,
    id_utilisateur INTEGER REFERENCES utilisateur(id_utilisateur) UNIQUE NOT NULL,
    matricule VARCHAR(20) UNIQUE NOT NULL, -- Numéro matricule de l'employé
    salaire DECIMAL(10,2) CHECK (salaire >= 0),
    date_embauche DATE NOT NULL,
    date_fin_contrat DATE,
    statut VARCHAR(20) DEFAULT 'actif' CHECK (statut IN ('actif', 'inactif', 'congé', 'licencie')),
    role_specifique VARCHAR(50) NOT NULL CHECK (role_specifique IN ('cuisinier', 'serveur', 'livreur', 'caissier', 'manager')),
    
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE employe IS 'Informations spécifiques aux employés du restaurant';
COMMENT ON COLUMN employe.matricule IS 'Numéro matricule unique de l''employé';
COMMENT ON COLUMN employe.role_specifique IS 'Rôle métier: cuisinier, serveur, livreur, caissier, manager';

-- Table 7: PAYMENT (gestion des paiements)
CREATE TABLE payment (
    id_payment SERIAL PRIMARY KEY,
    id_commande INTEGER REFERENCES commandes(id_commande) NOT NULL,
    montant DECIMAL(10,2) NOT NULL CHECK (montant >= 0),
    methode VARCHAR(50) NOT NULL CHECK (methode IN ('carte_bancaire', 'mobile_money', 'especes', 'points_fidelite')),
    statut_payment VARCHAR(20) NOT NULL DEFAULT 'en_attente' 
        CHECK (statut_payment IN ('en_attente', 'paye', 'echec', 'rembourse', 'annule')),
    date_payment TIMESTAMP,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    transaction_ref VARCHAR(255), -- Référence de transaction externe
    operateur_mobile_money VARCHAR(50), -- Orange Money, MTN Money, etc.
    numero_transaction VARCHAR(100), -- Numéro de transaction mobile money
    carte_last_digits VARCHAR(4), -- 4 derniers chiffres de la carte
    carte_type VARCHAR(20), -- Visa, Mastercard, etc.
    frais_transaction DECIMAL(10,2) DEFAULT 0 -- Frais de transaction
);

COMMENT ON TABLE payment IS 'Gestion des paiements des commandes';
COMMENT ON COLUMN payment.methode IS 'Méthode de paiement: carte_bancaire, mobile_money, especes, points_fidelite';
COMMENT ON COLUMN payment.transaction_ref IS 'Référence de la transaction du processeur de paiement';

-- Table 8: RECLAMATION (gestion des réclamations clients)
CREATE TABLE reclamation (
    id_reclamation SERIAL PRIMARY KEY,
    id_utilisateur INTEGER REFERENCES utilisateur(id_utilisateur) NOT NULL,
    id_commande INTEGER REFERENCES commandes(id_commande),
    id_employe_traitant INTEGER REFERENCES utilisateur(id_utilisateur), -- Employé qui traite la réclamation
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
    satisfaction_client INTEGER CHECK (satisfaction_client >= 1 AND satisfaction_client <= 5) -- Note 1-5
);

COMMENT ON TABLE reclamation IS 'Réclamations des clients sur les commandes';
COMMENT ON COLUMN reclamation.id_employe_traitant IS 'Employé assigné pour traiter la réclamation';
COMMENT ON COLUMN reclamation.satisfaction_client IS 'Note de satisfaction après traitement (1-5)';

-- Table 9: PARAMETRES_FIDELITE (configuration du système de fidélité)
CREATE TABLE parametres_fidelite (
    id_parametre SERIAL PRIMARY KEY,
    taux_conversion DECIMAL(10,2) NOT NULL DEFAULT 1000, -- 1000 FC = 1 point
    valeur_point DECIMAL(10,2) NOT NULL DEFAULT 1000, -- 15 points = 1000 FC de réduction
    points_parrainage INTEGER NOT NULL DEFAULT 50, -- Points donnés au parrain
    points_premiere_commande INTEGER DEFAULT 20, -- Points offerts à la première commande
    duree_validite_points INTEGER DEFAULT 12, -- Durée en mois
    points_minimum_utilisation INTEGER DEFAULT 15, -- Seuil minimum pour utiliser les points
    montant_minimum_commande DECIMAL(10,2) DEFAULT 0, -- Montant minimum pour gagner des points
    date_debut_application TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_fin_application TIMESTAMP,
    actif BOOLEAN DEFAULT true
);

COMMENT ON TABLE parametres_fidelite IS 'Paramètres configurables du système de fidélité';
COMMENT ON COLUMN parametres_fidelite.taux_conversion IS 'Montant en FCFA pour 1 point (ex: 1000 FC = 1 point)';
COMMENT ON COLUMN parametres_fidelite.valeur_point IS 'Valeur en FCFA de X points (ex: 15 points = 1000 FC)';
COMMENT ON COLUMN parametres_fidelite.duree_validite_points IS 'Durée de validité des points en mois';

-- Table 10: POINT_TRANSACTION (historique des points de fidélité)
CREATE TABLE point_transaction (
    id_pointtransaction SERIAL PRIMARY KEY,
    id_utilisateur INTEGER REFERENCES utilisateur(id_utilisateur) NOT NULL,
    type_transaction VARCHAR(20) NOT NULL CHECK (type_transaction IN ('gain', 'utilisation', 'expiration', 'ajout_manuel', 'retrait_manuel')),
    source VARCHAR(100) NOT NULL, -- 'commande', 'parrainage', 'jeu', 'evenement', 'annulation'
    montant_points INTEGER NOT NULL,
    solde_apres_transaction INTEGER NOT NULL,
    date_transaction TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_reference INTEGER, -- Référence à la source (id_commande, id_evenement, etc.)
    description TEXT,
    date_expiration TIMESTAMP -- Pour les gains de points
);

COMMENT ON TABLE point_transaction IS 'Historique complet des transactions de points de fidélité';
COMMENT ON COLUMN point_transaction.source IS 'Source des points: commande, parrainage, jeu, evenement, annulation';
COMMENT ON COLUMN point_transaction.id_reference IS 'ID de référence selon la source (id_commande, id_evenement, etc.)';
COMMENT ON COLUMN point_transaction.solde_apres_transaction IS 'Solde du compte après cette transaction';

-- Table 11: EVENEMENT (jeux et événements promotionnels)
CREATE TABLE evenement (
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
    createur_id INTEGER REFERENCES utilisateur(id_utilisateur) -- Admin qui a créé l'événement
);

COMMENT ON TABLE evenement IS 'Jeux, concours et événements promotionnels';
COMMENT ON COLUMN evenement.type_evenement IS 'Type: jeu, concours, promotion, evenement_special';
COMMENT ON COLUMN evenement.nombre_participants_max IS 'Nombre maximum de participants (NULL si illimité)';

-- Table 12: PARTICIPATION (participation aux événements)
CREATE TABLE participation (
    id_participation SERIAL PRIMARY KEY,
    id_utilisateur INTEGER REFERENCES utilisateur(id_utilisateur) NOT NULL,
    id_evenement INTEGER REFERENCES evenement(id_evenement) NOT NULL,
    date_participation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resultat VARCHAR(100), -- Résultat du jeu/concours
    points_gagnes INTEGER DEFAULT 0,
    rang INTEGER, -- Classement dans le concours
    recompense_supplementaire TEXT, -- Autres récompenses que les points
    statut_participation VARCHAR(20) DEFAULT 'inscrit' CHECK (statut_participation IN ('inscrit', 'participe', 'gagnant', 'perdant', 'abandon')),
    UNIQUE(id_utilisateur, id_evenement) -- Un utilisateur ne peut participer qu'une fois
);

COMMENT ON TABLE participation IS 'Participations des utilisateurs aux événements et jeux';
COMMENT ON COLUMN participation.rang IS 'Classement dans le concours (1 = premier)';
COMMENT ON COLUMN participation.recompense_supplementaire IS 'Autres récompenses (cadeaux, menus gratuits, etc.)';

-- Table 13: PROMOTION (promotions et offres spéciales)
CREATE TABLE promotion (
    id_promo SERIAL PRIMARY KEY,
    titre VARCHAR(100) NOT NULL,
    type_promotion VARCHAR(50) NOT NULL CHECK (type_promotion IN ('pourcentage', 'montant_fixe', 'offre_speciale', 'menu_special')),
    valeur DECIMAL(10,2) NOT NULL, -- Pourcentage ou montant fixe
    valeur_minimum_panier DECIMAL(10,2) DEFAULT 0, -- Montant minimum du panier
    date_debut TIMESTAMP NOT NULL,
    date_fin TIMESTAMP NOT NULL,
    applicabilite TEXT, -- Conditions d'application
    details TEXT,
    code_promo VARCHAR(50) UNIQUE, -- Code promotionnel optionnel
    utilisations_max INTEGER, -- Nombre maximum d'utilisations
    utilisations_actuelles INTEGER DEFAULT 0,
    statut VARCHAR(20) DEFAULT 'active' CHECK (statut IN ('active', 'inactive', 'expiree', 'epuisee')),
    image_url VARCHAR(255),
    createur_id INTEGER REFERENCES utilisateur(id_utilisateur)
);

COMMENT ON TABLE promotion IS 'Promotions et offres spéciales';
COMMENT ON COLUMN promotion.type_promotion IS 'Type: pourcentage, montant_fixe, offre_speciale, menu_special';
COMMENT ON COLUMN promotion.code_promo IS 'Code promotionnel à saisir (optionnel)';

-- Table 14: PROMO_MENU_ITEM (liaison promotion-articles)
CREATE TABLE promo_menu_item (
    id_promomenuitem SERIAL PRIMARY KEY,
    id_promo INTEGER REFERENCES promotion(id_promo) NOT NULL,
    id_menuitem INTEGER REFERENCES menu_item(id_menuitem) NOT NULL,
    prix_promotionnel DECIMAL(10,2), -- Prix spécifique pendant la promo
    date_debut TIMESTAMP,
    date_fin TIMESTAMP,
    statut VARCHAR(20) DEFAULT 'active',
    UNIQUE(id_promo, id_menuitem) -- Éviter les doublons
);

COMMENT ON TABLE promo_menu_item IS 'Liaison entre promotions et articles du menu';
COMMENT ON COLUMN promo_menu_item.prix_promotionnel IS 'Prix spécifique pendant la promotion';

SELECT * FROM evenement;