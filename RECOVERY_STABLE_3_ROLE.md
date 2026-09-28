# KMU-v2 — Stable 3-Role Recovery

This package is the recovery/stable codebase prepared from the uploaded project.

## Active application roles
- `admin`
- `kelurahan`
- `kecamatan`

Granular v3 roles are intentionally not active in this recovery version. The workflow foundation migrations (`alur_tte`, `current_stage`, approval tracking, document UUID/type/hash) are retained for the next implementation stage.

## Default accounts
- `admin@kecmagelangutara.test` / `password`
- `kecamatan@kecmagelangutara.test` / `password`
- `kelurahan.kramat-utara@kecmagelangutara.test` / `password`
- `kelurahan.kramat-selatan@kecmagelangutara.test` / `password`
- `kelurahan.wates@kecmagelangutara.test` / `password`
- `kelurahan.potrobangsan@kecmagelangutara.test` / `password`
- `kelurahan.kedungsari@kecmagelangutara.test` / `password`

## Important
The package does not contain `.env`. Keep/copy your existing `.env` into the project root.
The bundled `kec_magelang` SQLite database has been normalized so these three roles can log in immediately.

## First commands after copying
```bat
composer install
php artisan optimize:clear
php artisan route:list
php artisan test
```
