-- PostgreSQL 14+ companion for
-- database/migrations/2026_10_27_000000_add_scoped_role_menu_and_project_access.php
--
-- Use *either* `php artisan migrate --database=pgsql` (recommended) or this
-- file for an existing database provisioned outside Laravel. Do not run both.

BEGIN;

ALTER TABLE roles ADD COLUMN IF NOT EXISTS client_id BIGINT NULL;
ALTER TABLE roles ADD COLUMN IF NOT EXISTS is_super_admin BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE roles ADD COLUMN IF NOT EXISTS is_assignable BOOLEAN NOT NULL DEFAULT TRUE;
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'roles_client_id_foreign'
    ) THEN
        ALTER TABLE roles
            ADD CONSTRAINT roles_client_id_foreign
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL;
    END IF;
END;
$$;
CREATE INDEX IF NOT EXISTS roles_client_assignable_idx ON roles (client_id, is_assignable);

CREATE TABLE IF NOT EXISTS navigation_menus (
    id BIGSERIAL PRIMARY KEY,
    parent_id BIGINT NULL REFERENCES navigation_menus(id) ON DELETE SET NULL,
    code VARCHAR(255) NOT NULL UNIQUE,
    label VARCHAR(255) NOT NULL,
    route_name VARCHAR(255) NULL,
    resource VARCHAR(255) NULL,
    sort_order INTEGER NOT NULL DEFAULT 0 CHECK (sort_order >= 0),
    is_project_scoped BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    metadata JSONB NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NULL
);
CREATE INDEX IF NOT EXISTS navigation_menus_tree_idx ON navigation_menus (parent_id, sort_order);
CREATE INDEX IF NOT EXISTS navigation_menus_visibility_idx ON navigation_menus (is_active, is_project_scoped);

CREATE TABLE IF NOT EXISTS role_has_menu_access (
    id BIGSERIAL PRIMARY KEY,
    role_id BIGINT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    navigation_menu_id BIGINT NOT NULL REFERENCES navigation_menus(id) ON DELETE CASCADE,
    can_view BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    CONSTRAINT role_menu_access_unique UNIQUE (role_id, navigation_menu_id)
);

CREATE TABLE IF NOT EXISTS user_has_project_roles (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    project_id BIGINT NOT NULL REFERENCES resq_projects(id) ON DELETE CASCADE,
    role_id BIGINT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    assigned_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    CONSTRAINT user_project_role_unique UNIQUE (user_id, project_id, role_id)
);
CREATE INDEX IF NOT EXISTS user_project_role_lookup_idx ON user_has_project_roles (project_id, role_id);

-- The application validates the same rule before creating an assignment. This
-- trigger additionally protects imports and direct SQL writes: a client user,
-- a client-owned role, and the selected project must belong to one client.
CREATE OR REPLACE FUNCTION ensure_user_project_role_client_match()
RETURNS TRIGGER AS $$
DECLARE
    user_client_id BIGINT;
    project_client_id BIGINT;
    role_client_id BIGINT;
BEGIN
    SELECT client_id INTO user_client_id FROM users WHERE id = NEW.user_id;
    SELECT client_id INTO project_client_id FROM resq_projects WHERE id = NEW.project_id;
    SELECT client_id INTO role_client_id FROM roles WHERE id = NEW.role_id;

    IF user_client_id IS NOT NULL AND user_client_id IS DISTINCT FROM project_client_id THEN
        RAISE EXCEPTION 'User % and project % belong to different clients', NEW.user_id, NEW.project_id;
    END IF;

    IF role_client_id IS NOT NULL AND role_client_id IS DISTINCT FROM project_client_id THEN
        RAISE EXCEPTION 'Role % and project % belong to different clients', NEW.role_id, NEW.project_id;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS user_project_role_client_match ON user_has_project_roles;
CREATE TRIGGER user_project_role_client_match
BEFORE INSERT OR UPDATE OF user_id, project_id, role_id ON user_has_project_roles
FOR EACH ROW EXECUTE FUNCTION ensure_user_project_role_client_match();

COMMIT;
