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

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

COMMENT ON EXTENSION "uuid-ossp" IS 'Génération de UUID pour les identifiants uniques';
COMMENT ON EXTENSION "pgcrypto" IS 'Fonctions cryptographiques pour les mots de passe';