-- -------------------------------- --
-- LARAVEL & BASE TABLES            --
-- -------------------------------- --

--
-- Table for managing background jobs
--
CREATE TABLE jobs (
    id BIGSERIAL PRIMARY KEY,
    queue VARCHAR(255),
    payload TEXT,
    attempts SMALLINT,
    reserved_at INTEGER,
    available_at INTEGER,
    created_at INTEGER
);

--
-- Table for storing failed jobs
--
CREATE TABLE failed_jobs (
    id BIGSERIAL PRIMARY KEY,
    uuid VARCHAR(255),
    connection TEXT,
    queue TEXT,
    payload TEXT,
    exception TEXT,
    failed_at TIMESTAMP NOT NULL DEFAULT current_timestamp
);

--
-- Table for API tokens (for your app's own API)
--
CREATE TABLE personal_access_tokens (
    id BIGSERIAL PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL,
    abilities TEXT,
    last_used_at TIMESTAMP,
    expires_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
CREATE INDEX ON personal_access_tokens (tokenable_type);
CREATE INDEX ON personal_access_tokens (tokenable_id);
CREATE UNIQUE INDEX ON personal_access_tokens (token);

--
-- Table for media management
--
CREATE TABLE media (
    id BIGSERIAL PRIMARY KEY,
    model_type VARCHAR(255) NOT NULL,
    model_id BIGINT NOT NULL,
    uuid UUID,
    collection_name VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    temp BOOLEAN NOT NULL DEFAULT FALSE,
    file_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(255),
    disk VARCHAR(255) NOT NULL,
    conversions_disk VARCHAR(255),
    size INTEGER,
    manipulations JSON NOT NULL DEFAULT '[]',
    custom_properties JSON NOT NULL DEFAULT '[]',
    generated_conversions JSON NOT NULL DEFAULT '[]',
    responsive_images JSON NOT NULL DEFAULT '[]',
    order_column SMALLINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
CREATE INDEX ON media (model_type, model_id);


-- -------------------------------- --
-- BASIC / GLOBAL DATA              --
-- -------------------------------- --

CREATE TABLE countries (
    iso2 CHAR(2) NOT NULL PRIMARY KEY,
    iso3 CHAR(3) NOT NULL,
    name VARCHAR(200) NOT NULL
);

CREATE TABLE subcountries (
    iso VARCHAR(10) PRIMARY KEY,
    country CHAR(2) REFERENCES countries (iso2),
    name VARCHAR(200) NOT NULL
);

CREATE TABLE timezones (
    name VARCHAR(200) PRIMARY KEY,
    comments VARCHAR(200),
    country CHAR(2) REFERENCES countries (iso2),
    alias VARCHAR(200) UNIQUE,
    utc_offset INTEGER,
    utc_offset_dst INTEGER,
    notes TEXT
);

CREATE TABLE currencies (
    code VARCHAR(10) PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    symbol_native VARCHAR(10) NOT NULL,
    decimal_digits INTEGER NOT NULL,
    rounding INTEGER NOT NULL,
    name_plural VARCHAR(200) NOT NULL,
    localization VARCHAR(10) NOT NULL DEFAULT 'before'
);
CREATE INDEX idx_currencies_name ON currencies (name);
CREATE INDEX idx_currencies_symbol ON currencies (symbol);
CREATE INDEX idx_currencies_symbol_native ON currencies (symbol_native);
CREATE INDEX idx_currencies_name_symbol ON currencies (name, symbol);
CREATE INDEX idx_currencies_decimal_digits ON currencies (decimal_digits);
CREATE INDEX idx_currencies_rounding ON currencies (rounding);

CREATE TABLE exchange_rates (
    id SERIAL PRIMARY KEY,
    base_currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
    target_currency VARCHAR(3) NOT NULL,
    rate NUMERIC(15, 6) NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (date, base_currency, target_currency)
);
CREATE INDEX idx_exchange_rates_date_currency ON exchange_rates (date, target_currency);
CREATE INDEX idx_exchange_rates_date ON exchange_rates (date);

CREATE TABLE api_call_logs (
    id SERIAL PRIMARY KEY,
    endpoint TEXT NOT NULL,
    requested_date DATE NOT NULL,
    success BOOLEAN DEFAULT FALSE,
    response JSONB,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE colors (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    name VARCHAR(15),
    dark_text CHAR(10),
    dark_bg CHAR(10),
    dark_border CHAR(10),
    light_text CHAR(10),
    light_bg CHAR(10),
    light_border CHAR(10)
);

-- -------------------------------- --
-- ORGANISATIONS & ROLES            --
-- -------------------------------- --

CREATE TABLE organisations (
    id BIGSERIAL PRIMARY KEY,
    uuid_ref UUID NOT NULL DEFAULT gen_random_uuid(),
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT current_timestamp,
    updated_at TIMESTAMPTZ,
    deleted_at TIMESTAMPTZ
);

CREATE TABLE roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE permissions (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT
);

CREATE TABLE role_permissions (
    role_id INT REFERENCES roles(id),
    permission_id INT REFERENCES permissions(id),
    PRIMARY KEY (role_id, permission_id)
);

-- -------------------------------- --
-- USERS & ACCOUNTS                 --
-- -------------------------------- --

CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    account_type VARCHAR(10) NOT NULL DEFAULT 'standard',
    CHECK (account_type IN ('sysadmin', 'standard')),
    fname VARCHAR(100) NOT NULL,
    sname VARCHAR(100),
    dob DATE,
    gender CHAR(1),
    CHECK (gender IN ('M', 'F')),
    phone VARCHAR(20),
    email VARCHAR(100) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP,
    password VARCHAR(100),
    google_id VARCHAR(100) UNIQUE,
    remember_token VARCHAR(100),
    password_reset_token VARCHAR(100),
    last_login_at TIMESTAMP,
    last_login_ip INET,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    timezone VARCHAR(100) NOT NULL DEFAULT 'UTC',
    current_timezone VARCHAR(100) NOT NULL DEFAULT 'UTC',
    mfa_totp_secret VARCHAR(100),
    mfa_sms_phone VARCHAR(20),
    mfa_email VARCHAR(100),
    mfa_default VARCHAR(10),
    CHECK (mfa_default IN ('totp', 'sms', 'email')),
    role_id INT REFERENCES roles(id),
    marketing_consent BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT current_timestamp,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
CREATE INDEX idx_users_email ON users (email);
CREATE INDEX idx_users_phone ON users (phone);
CREATE INDEX idx_users_created_at ON users (created_at);

-- -------------------------------- --
-- ORGANISATION & SITES             --
-- -------------------------------- --

CREATE TABLE organisation_users (
    id BIGSERIAL PRIMARY KEY,
    organisation_id BIGINT NOT NULL REFERENCES organisations,
    user_id BIGINT REFERENCES users,
    created_at TIMESTAMPTZ NOT NULL DEFAULT current_timestamp,
    updated_at TIMESTAMPTZ,
    deleted_at TIMESTAMPTZ
);

CREATE TABLE addresses (
    id BIGSERIAL PRIMARY KEY,
    uuid_ref UUID NOT NULL DEFAULT gen_random_uuid(),
    label VARCHAR(100),
    address TEXT,
    au_gnaf VARCHAR(100),
    address_1 VARCHAR(200),
    address_2 VARCHAR(200),
    suburbcity VARCHAR(100),
    postcode VARCHAR(10),
    stateprov VARCHAR(10),
    CHECK (stateprov IN ('ACT', 'NSW', 'NT', 'QLD', 'SA', 'TAS', 'VIC', 'WA')),
    country CHAR(2) REFERENCES countries (iso2),
    lat DECIMAL(10, 7),
    lng DECIMAL(10, 7),
    created_at TIMESTAMPTZ NOT NULL DEFAULT current_timestamp,
    updated_at TIMESTAMPTZ,
    deleted_at TIMESTAMPTZ
);

CREATE TABLE sites (
    id BIGSERIAL PRIMARY KEY,
    uuid_ref UUID NOT NULL DEFAULT gen_random_uuid(),
    name VARCHAR(100) NOT NULL,
    label VARCHAR(100),
    organisation_id BIGINT NOT NULL REFERENCES organisations,
    address_id BIGINT REFERENCES addresses,
    address_1 VARCHAR(200),
    address_2 VARCHAR(200),
    suburbcity VARCHAR(100),
    postcode VARCHAR(10),
    stateprov VARCHAR(10),
    CHECK (stateprov IN ('ACT', 'NSW', 'NT', 'QLD', 'SA', 'TAS', 'VIC', 'WA')),
    country CHAR(2) REFERENCES countries (iso2),
    phone VARCHAR(20),
    email VARCHAR(200),
    fax VARCHAR(20),
    timezone VARCHAR(200) NOT NULL DEFAULT 'UTC',
    currency CHAR(3) NOT NULL DEFAULT 'AUD',
    status VARCHAR(15) NOT NULL DEFAULT 'open',
    CHECK (status IN ('open', 'tempclosed', 'permclosed')),
    force_timeslot_use BOOLEAN NOT NULL DEFAULT TRUE,
    maintenance_team BIGINT,
    truck_booking_target SMALLINT,
    lolf_booking_target SMALLINT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT current_timestamp,
    updated_at TIMESTAMPTZ,
    deleted_at TIMESTAMPTZ
);

CREATE TABLE site_managers (
    id BIGSERIAL PRIMARY KEY,
    site_id BIGINT NOT NULL REFERENCES sites,
    staff_id BIGINT NOT NULL REFERENCES users,
    created_at TIMESTAMPTZ NOT NULL DEFAULT current_timestamp,
    updated_at TIMESTAMPTZ,
    deleted_at TIMESTAMPTZ
);
CREATE INDEX idx_sites_organisation_id ON sites (organisation_id);
CREATE INDEX idx_sites_status ON sites (status);
CREATE INDEX idx_sites_name ON sites (name);
CREATE INDEX idx_sites_label ON sites (label);
CREATE INDEX idx_sites_address_id ON sites (address_id);
CREATE INDEX idx_site_managers_site_id ON site_managers (site_id);
CREATE INDEX idx_site_managers_staff_id ON site_managers (staff_id);
CREATE UNIQUE INDEX idx_site_managers_unique ON site_managers (site_id, staff_id) WHERE deleted_at IS NULL;

-- -------------------------------- --
-- KNOWLEDGE VAULT TABLES           --
-- -------------------------------- --

--
-- Table for storing repositories connected to the app
--
CREATE TABLE repositories (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    github_id BIGINT UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    owner_id BIGINT NOT NULL,
    webhook_secret VARCHAR(255) NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE knowledge_entries (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    repository_id UUID REFERENCES repositories(id) ON DELETE CASCADE,
    user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
    pr_id BIGINT NOT NULL,
    pr_url VARCHAR(255) NOT NULL,
    pr_title VARCHAR(255) NOT NULL,
    pr_merge_commit_sha VARCHAR(40) NOT NULL,
    knowledge_entry TEXT NOT NULL,
    category VARCHAR(255),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_knowledge_entries_repository_id ON knowledge_entries (repository_id);
CREATE INDEX idx_knowledge_entries_user_id ON knowledge_entries (user_id);

--
-- Pivot table to link repositories to organisations
--
CREATE TABLE organisation_repositories (
    id BIGSERIAL PRIMARY KEY,
    organisation_id BIGINT NOT NULL REFERENCES organisations,
    repository_id UUID NOT NULL REFERENCES repositories(id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT current_timestamp,
    updated_at TIMESTAMPTZ,
    UNIQUE (organisation_id, repository_id)
);

--
-- Table to manage user access to specific repositories
--
CREATE TABLE repository_users (
    id BIGSERIAL PRIMARY KEY,
    repository_id UUID REFERENCES repositories(id),
    user_id BIGINT REFERENCES users(id),
    role_id INT REFERENCES roles(id),
    is_admin BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT current_timestamp,
    updated_at TIMESTAMPTZ,
    deleted_at TIMESTAMPTZ,
    UNIQUE (repository_id, user_id)
);

--
-- Table for storing feedback on knowledge entries
--
CREATE TABLE knowledge_entry_feedback (
    id BIGSERIAL PRIMARY KEY,
    knowledge_entry_id UUID REFERENCES knowledge_entries(id) ON DELETE CASCADE,
    user_id BIGINT REFERENCES users(id),
    rating SMALLINT,
    comment TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT current_timestamp,
    UNIQUE (knowledge_entry_id, user_id)
);

-- -------------------------------- --
-- OPERATING DATA                   --
-- -------------------------------- --

INSERT INTO users (fname, sname, email, password, account_type, email_verified_at) VALUES ('Admin', 'Vault', 'admin@vault.com', '$2y$10$EKaUgclgm1KQr4JhTDyzkeZlKQ0ddlXougIes69Jlrfxisn.7kpfe', 'sysadmin', current_timestamp);
INSERT INTO organisations (name) VALUES ('Default');
INSERT INTO organisation_users (organisation_id, user_id) VALUES (1, 1);
