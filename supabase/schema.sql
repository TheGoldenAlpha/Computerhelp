-- ============================================================================
--  McSammler – Supabase / Postgres Schema
--  Einfach komplett in den Supabase SQL-Editor kopieren und ausführen.
--  (Dashboard -> SQL Editor -> New query -> einfügen -> Run)
--
--  Sicherheit:
--   - Passwörter werden mit bcrypt (pgcrypto) gehasht, niemals im Klartext.
--   - Row Level Security ist AN; die Tabellen sind für den öffentlichen
--     "anon"-Schlüssel NICHT direkt lesbar/schreibbar.
--   - Aller Zugriff läuft über die unten definierten Funktionen (RPC).
-- ============================================================================

create extension if not exists pgcrypto with schema extensions;

-- --- Tabellen ---------------------------------------------------------------
create table if not exists public.groups (
  id         bigint generated always as identity primary key,
  name       text not null,
  name_lower text not null unique,
  pass_hash  text not null,
  created_at timestamptz not null default now()
);

create table if not exists public.visits (
  group_id   bigint not null references public.groups(id) on delete cascade,
  ext_id     text   not null,
  visited_at timestamptz not null default now(),
  primary key (group_id, ext_id)
);
create index if not exists idx_visits_group on public.visits(group_id);

create table if not exists public.sessions (
  token      uuid primary key default gen_random_uuid(),
  group_id   bigint not null references public.groups(id) on delete cascade,
  created_at timestamptz not null default now()
);
create index if not exists idx_sessions_group on public.sessions(group_id);

-- --- RLS einschalten, direkten Zugriff sperren ------------------------------
alter table public.groups   enable row level security;
alter table public.visits   enable row level security;
alter table public.sessions enable row level security;

revoke all on public.groups   from anon, authenticated;
revoke all on public.visits   from anon, authenticated;
revoke all on public.sessions from anon, authenticated;

-- ============================================================================
--  Funktionen (RPC). SECURITY DEFINER -> laufen mit Besitzerrechten und
--  umgehen damit RLS kontrolliert. Rückgabe immer als JSON.
-- ============================================================================

-- Token -> group_id auflösen (intern). Fängt ungültige Tokens sauber ab.
create or replace function public._mc_group_for_token(p_token text)
returns bigint
language plpgsql security definer set search_path = public, extensions as $$
begin
  return (select group_id from public.sessions where token = p_token::uuid);
exception when others then
  return null;
end;
$$;

-- Gruppe erstellen
create or replace function public.mc_create_group(p_name text, p_password text)
returns jsonb
language plpgsql security definer set search_path = public, extensions as $$
declare
  v_name text := btrim(coalesce(p_name, ''));
  v_id bigint;
  v_token uuid;
begin
  if char_length(v_name) < 1 or char_length(v_name) > 40 then
    return jsonb_build_object('ok', false, 'error', 'Name muss 1–40 Zeichen lang sein.');
  end if;
  if char_length(coalesce(p_password,'')) < 3 then
    return jsonb_build_object('ok', false, 'error', 'Passwort muss mindestens 3 Zeichen haben.');
  end if;
  if exists (select 1 from public.groups where name_lower = lower(v_name)) then
    return jsonb_build_object('ok', false, 'error', 'Diese Gruppe gibt es schon.');
  end if;

  insert into public.groups (name, name_lower, pass_hash)
  values (v_name, lower(v_name), crypt(p_password, gen_salt('bf', 10)))
  returning id into v_id;

  insert into public.sessions (group_id) values (v_id) returning token into v_token;

  return jsonb_build_object('ok', true, 'id', v_id, 'name', v_name, 'token', v_token::text);
end;
$$;

-- Login (per Gruppen-ID + Passwort)
create or replace function public.mc_login(p_group_id bigint, p_password text)
returns jsonb
language plpgsql security definer set search_path = public, extensions as $$
declare
  v_group public.groups;
  v_token uuid;
begin
  select * into v_group from public.groups where id = p_group_id;
  if not found then
    return jsonb_build_object('ok', false, 'error', 'Gruppe nicht gefunden.');
  end if;
  if v_group.pass_hash <> crypt(coalesce(p_password,''), v_group.pass_hash) then
    return jsonb_build_object('ok', false, 'error', 'Falsches Passwort.');
  end if;

  insert into public.sessions (group_id) values (v_group.id) returning token into v_token;
  return jsonb_build_object('ok', true, 'id', v_group.id, 'name', v_group.name, 'token', v_token::text);
end;
$$;

-- Alle Gruppen (mit Besuchszahl) – öffentlich, optional gefiltert
create or replace function public.mc_list_groups(p_search text default '')
returns jsonb
language sql security definer set search_path = public, extensions as $$
  select coalesce(jsonb_agg(grp order by (grp->>'visit_count')::int desc, lower(grp->>'name')), '[]'::jsonb)
  from (
    select jsonb_build_object('id', g.id, 'name', g.name, 'visit_count', count(v.ext_id)) as grp
    from public.groups g
    left join public.visits v on v.group_id = g.id
    where p_search is null or p_search = '' or g.name_lower like '%' || lower(p_search) || '%'
    group by g.id
  ) s;
$$;

-- Besuche der eigenen Gruppe (per Token)
create or replace function public.mc_get_visits(p_token text)
returns jsonb
language plpgsql security definer set search_path = public, extensions as $$
declare
  v_gid bigint := public._mc_group_for_token(p_token);
  v_name text;
  v_list jsonb;
begin
  if v_gid is null then
    return jsonb_build_object('ok', false, 'error', 'Nicht eingeloggt.');
  end if;
  select name into v_name from public.groups where id = v_gid;
  select coalesce(jsonb_agg(ext_id), '[]'::jsonb) into v_list from public.visits where group_id = v_gid;
  return jsonb_build_object('ok', true, 'name', v_name, 'visits', v_list, 'visit_count', jsonb_array_length(v_list));
end;
$$;

-- McDonald's abhaken
create or replace function public.mc_add_visit(p_token text, p_ext_id text)
returns jsonb
language plpgsql security definer set search_path = public, extensions as $$
declare
  v_gid bigint := public._mc_group_for_token(p_token);
  v_count int;
begin
  if v_gid is null then return jsonb_build_object('ok', false, 'error', 'Nicht eingeloggt.'); end if;
  if coalesce(p_ext_id,'') = '' then return jsonb_build_object('ok', false, 'error', 'Ungültiger Standort.'); end if;
  insert into public.visits (group_id, ext_id) values (v_gid, p_ext_id) on conflict do nothing;
  select count(*) into v_count from public.visits where group_id = v_gid;
  return jsonb_build_object('ok', true, 'visit_count', v_count);
end;
$$;

-- McDonald's zurücksetzen
create or replace function public.mc_remove_visit(p_token text, p_ext_id text)
returns jsonb
language plpgsql security definer set search_path = public, extensions as $$
declare
  v_gid bigint := public._mc_group_for_token(p_token);
  v_count int;
begin
  if v_gid is null then return jsonb_build_object('ok', false, 'error', 'Nicht eingeloggt.'); end if;
  delete from public.visits where group_id = v_gid and ext_id = p_ext_id;
  select count(*) into v_count from public.visits where group_id = v_gid;
  return jsonb_build_object('ok', true, 'visit_count', v_count);
end;
$$;

-- Abmelden (Token ungültig machen) – optional
create or replace function public.mc_logout(p_token text)
returns jsonb
language sql security definer set search_path = public, extensions as $$
  delete from public.sessions where token = p_token::uuid;
  select jsonb_build_object('ok', true);
$$;

-- --- Ausführungsrechte für den öffentlichen anon-Schlüssel -------------------
revoke all on function public._mc_group_for_token(text) from public;  -- intern
grant execute on function public.mc_create_group(text, text)   to anon, authenticated;
grant execute on function public.mc_login(bigint, text)        to anon, authenticated;
grant execute on function public.mc_list_groups(text)          to anon, authenticated;
grant execute on function public.mc_get_visits(text)           to anon, authenticated;
grant execute on function public.mc_add_visit(text, text)      to anon, authenticated;
grant execute on function public.mc_remove_visit(text, text)   to anon, authenticated;
grant execute on function public.mc_logout(text)               to anon, authenticated;
