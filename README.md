# PHP Tīmekļa Platforma

Šis projekts ir vienkārša tīmekļa platforma, izstrādāta ar PHP, kurā ir iekļauta lietotāju autentifikācija, notikumu pārvaldība, forums un multimediju galerija.

## Funkcionalitāte

- **Lietotāju autentifikācija**: Reģistrācija un pieteikšanās sistēma.
- **Forums**: Lietotāji var veidot un komentēt foruma ierakstus.
- **Notikumu pārvaldība**: Pievienot, rediģēt un skatīt notikumus.
- **Attēlu galerija**: Galerijas satura attēlošana.
- **Ziņojumu pārvaldība**: Atzīmēt ziņojumus kā "izlasītus".
- **Administrēšana (iespējama)**: Funkcijas notikumu un ierakstu rediģēšanai.

## Mapju un failu struktūra

- `index.php` – Galvenā sākumlapa.
- `login.php` / `signup.php` – Autentifikācijas lapa.
- `forums.php`, `forum_post.php` – Foruma funkcionalitāte.
- `events.php`, `new_event.php`, `edit_event.php` – Notikumu pārvaldība.
- `Galerie.php` – Attēlu galerija.
- `contact.php` – Kontaktinformācija vai forma.
- `mark_seen.php`, `mark_seen_user.php` – Atzīmēt ziņojumus kā redzētus.
- `.git/` – Git versiju kontroles metadati.

## Prasības

- PHP 7.x vai jaunāks
- MySQL/MariaDB (ja tiek izmantota datubāze)
- Tīmekļa serveris (ieteicams Apache vai Nginx)
- Composer (ja tiek izmantotas PHP bibliotēkas)


