# Cara Mengetes Framework dan Panduan

Folder ini punya dua peran:

1. **Harness** — aplikasi contoh di `app/` yang memuat `src/` repo, dipakai
   untuk menguji perubahan kode framework.
2. **Verifikasi panduan** — setiap halaman `docs/` punya aplikasi uji sendiri,
   dengan nama folder sama seperti nama file dokumennya.

`tests/app/` **bukan** acuan gaya penulisan kode aplikasi. Controller di sana
menaruh query model langsung di controller dan memakai `switch($metode)` demi
menguji dua jalur sekaligus. Contoh controller yang mengikuti best practice
adalah `tests/05.01-crud-endpoint/Controllers/ProdukController.php`: controller
hanya mengurus HTTP, query lewat model, dan kesalahan dilempar sebagai
`Selvi\Exception`.

Beberapa contoh di folder uji juga **disalin ke `docs/`** (mis.
`ProdukController.php` pada `docs/05-api/01-crud-endpoint.md`). Salinan itu
tidak boleh menyimpang: bila salah satu diubah, perbarui keduanya dan catat
file sumbernya di dokumen.

Konvensi penamaan: halaman `docs/01-memulai/01-instalasi.md` diuji oleh
`tests/01.01-instalasi/`. Dengan begitu audit bisa dilakukan per halaman.

Tidak ada PHPUnit di repo ini. Semua verifikasi dilakukan lewat HTTP.

## Lingkungan uji

| Hal | Nilai |
|---|---|
| Image | `mochrira/dev` (dari `.docker/compose.yaml`) |
| Container | `selvi_test_dev` |
| Mount | root repo → `/workspace` |
| DocumentRoot Apache | `/workspace/tests` |
| Port host | `8089` → container `80` |
| Server database | container `db_mariadb` (`mariadb:10.7.8-focal`) di network `database`, port `3306` |
| Client database | `mysql-client` di **host**, bukan di container |

Konsekuensinya:

- Seluruh isi repo langsung terlihat di container; tidak perlu menyalin file.
- Folder baru di dalam `tests/` **langsung** bisa diakses di
  `http://localhost:8089/{nama-folder}/`.
- Port mengikuti `ports:` di `.docker/compose.yaml`. Nilai saat ini
  `8089:80`. Bila mapping diubah (misalnya ke `8090:80`), sesuaikan URL-nya.

### Batasan akses database

Pengujian fitur database menuntut akses langsung ke server, misalnya untuk
memeriksa isi tabel setelah migrasi. Batasannya:

- **`mysql-client` wajib terpasang di host.** Image `mochrira/dev` berbasis
  Alpine dan tidak memuat client MySQL, jadi `docker exec selvi_test_dev mysql`
  gagal (`not found`). Semua perintah database dari terminal dijalankan di
  host. Di komputer ini sudah tersedia (`mysql Ver 8.4.11`).
- **Server database sudah disediakan; jangan membuat yang baru.** Container
  `db_mariadb` (image `mariadb:10.7.8-focal`) berjalan di network `database`
  dan listening di port `3306`. Dari host aksesnya `127.0.0.1:3306`, dari
  container uji `db_mariadb:3306`.
- **Kredensial diambil dari `tests/private/.ENV`**, jangan ditulis di dokumen
  atau dicetak ke terminal. Contoh tanpa menampilkan password:

  ```sh
  set -a; . tests/private/.ENV; set +a
  MYSQL_PWD="$DB_PASS" mysql --protocol=TCP -h 127.0.0.1 -P 3306 \
    -u"$DB_USER" -e 'SELECT 1 AS ok'
  ```

  `DB_HOST` di `.ENV` berisi nama host di dalam network Docker
  (`mariadb.database`), sehingga dari host tetap memakai `127.0.0.1`.

## Aturan dasar

- Hanya memakai image dari `.docker/compose.yaml`. Jangan memakai image lain.
- **Selalu cek container lebih dulu.** Container sering sudah berjalan sebelum
  pengujian dimulai.
- Jangan menghapus folder verifikasi panduan; isinya bukti uji yang perlu bisa
  diperiksa ulang.
- Jangan menjalankan `docker compose down` pada container yang tidak Anda
  nyalakan sendiri.
- Uji HTTP dilakukan dari **host** ke `http://localhost:8089`. `docker exec`
  hanya untuk perintah CLI (`php -l`), karena PHP tidak tersedia di host.
- **Jangan menjalankan `composer install`/`composer require` di folder uji**
  selain `01.01-instalasi/`. Folder uji lain memuat `vendor/` workspace
  dengan `require __DIR__.'/../../vendor/autoload.php';`, seperti
  `tests/index.php`. Dengan begitu yang diuji adalah `src/` repo, bukan paket
  terbit.

## Langkah 0 — cek container (wajib)

```sh
docker ps --filter name=selvi_test_dev --format '{{.Names}} | {{.Status}}'
```

| Hasil | Tindakan |
|---|---|
| `selvi_test_dev \| Up ...` | lanjut ke Langkah 2 |
| kosong atau `Exited` | jalankan Langkah 1 |

## Langkah 1 — jalankan container

```sh
cd tests
docker compose -f .docker/compose.yaml up -d
```

Compose membutuhkan network eksternal `environment` dan `database`. Tunggu
sampai status `Up`:

```sh
docker ps --filter name=selvi_test_dev --format '{{.Status}}'
```

## Langkah 2 — baseline harness

```sh
curl -s -w '\n%{http_code}\n' http://localhost:8089/
```

Hasil yang diharapkan: `404` dengan body JSON.

```json
{
    "code": "http\/error",
    "message": "Route GET '\/'  not found",
    "request": {
        "uri": "\/",
        "method": "GET"
    }
}
```

404 ini benar: `app/Config/routes.php` tidak mendaftarkan route `/`. Kalau
hasilnya berbeda, Apache atau handler exception belum siap, dan pengujian
berikutnya tidak valid.

## Langkah 3 — uji halaman instalasi (`01.01-instalasi/`)

Folder ini menguji `docs/01-memulai/01-instalasi.md`. Strukturnya mengikuti
panduan: `index.php`, `.htaccess`, dan `vendor/` hasil Composer.

```sh
docker exec -w /workspace/tests/01.01-instalasi selvi_test_dev \
  composer require mochrira/selvi-framework --no-interaction
```

Perintah itu sudah dijalankan; `vendor/` diabaikan git. Untuk mengulang dari
nol, hapus `vendor/`, `composer.json`, dan `composer.lock` di folder tersebut,
lalu jalankan perintah di atas.

Cek sintaks dan endpoint:

```sh
docker exec selvi_test_dev php -l /workspace/tests/01.01-instalasi/index.php

for u in /01.01-instalasi/ '/01.01-instalasi/?x=1' /01.01-instalasi /01.01-instalasi/tidak-ada; do
  printf '%-30s ' "$u"
  curl -s -o /dev/null -w '%{http_code}\n' "http://localhost:8089$u"
done
```

Hasil terverifikasi (28 Oktober 2026, `mochrira/selvi-framework` 3.6.10,
PHP 8.4.14):

| Request | Status | Body |
|---|---|---|
| `GET /01.01-instalasi/` | `200` | `Halo, Selvi Framework!` |
| `GET /01.01-instalasi/?x=1` | `200` | `Halo, Selvi Framework!` |
| `GET /01.01-instalasi` | `301` | redirect Apache ke versi bergaris miring |
| `GET /01.01-instalasi/tidak-ada` | `500` | kosong (handler exception belum didaftarkan) |
| `php -l index.php` | — | tanpa error |

## Langkah 4 — uji halaman response (`01.02-response/`)

Folder ini menguji `docs/01-memulai/02-response.md`. Tidak ada
`composer install` di sini: `index.php` memuat
`__DIR__.'/../../vendor/autoload.php'`, yaitu `vendor/` workspace, sama seperti
`tests/index.php`. Jadi yang diuji adalah `src/` repo.

```sh
docker exec selvi_test_dev sh -lc '
php -l /workspace/tests/01.02-response/index.php
php -l /workspace/tests/01.02-response/views/home.php'

for u in / /json /json-header /halaman /halaman-hilang /tidak-ditemukan /tanpa-isi /tanpa-return; do
  printf '%-22s ' "/01.02-response$u"
  curl -s -o /dev/null -w '%{http_code}\n' "http://localhost:8089/01.02-response$u"
done

curl -si http://localhost:8089/01.02-response/json-header | grep -i '^content-type'
```

Hasil terverifikasi:

| Endpoint | Status | Catatan |
|---|---|---|
| `/01.02-response/` | `200` | teks biasa |
| `/01.02-response/json` | `200` | JSON rapi, `Content-Type: text/html` |
| `/01.02-response/json-header` | `200` | `Content-Type: application/json` |
| `/01.02-response/halaman` | `200` | HTML utuh dari `views/home.php` |
| `/01.02-response/halaman-hilang` | `500` | sengaja salah: template tidak ada |
| `/01.02-response/tidak-ditemukan` | `404` | body `Data tidak ditemukan` |
| `/01.02-response/tanpa-isi` | `204` | tanpa body |
| `/01.02-response/tanpa-return` | `500` | sengaja salah: `echo` tanpa `return` |
| `/01.02-response/cookie-set` | `200` | header `Set-Cookie: demo=abc123` |
| `/01.02-response/options` | `200` | JSON tanpa escape garis miring |
| `/01.02-response/ubah-respons` | `201` | body `diubah` |

Tiga endpoint terakhir dipakai untuk memverifikasi
`docs/99-referensi/04-response.md` (cookie, `setContent`/`setCode`) dan
`docs/99-referensi/05-json-response.md` (opsi JSON).

## Langkah 5 — uji halaman request (`01.03-request/`)

Folder ini menguji `docs/01-memulai/03-request.md`. Sama seperti folder uji
lain, `index.php` memuat `vendor/` workspace. `.htaccess` di folder ini
memuat aturan passthrough `Authorization`, karena halaman request menjelaskan
aturan tersebut.

```sh
docker exec selvi_test_dev php -l /workspace/tests/01.03-request/index.php

B=http://localhost:8089/01.03-request
curl -s "$B/halo?nama=Budi"; echo
curl -s "$B/halo"; echo
curl -s -X POST -H 'Content-Type: application/json' -d '{"nama":"Budi"}' "$B/kontak"; echo
curl -s -X POST -d 'nama=Budi' "$B/form"; echo
curl -s -H 'X-Token: abc123' "$B/token"; echo
curl -s "$B/metode"; echo
```

Hasil terverifikasi:

| Request | Status | Hasil |
|---|---|---|
| `GET /01.03-request/halo?nama=Budi` | `200` | `Halo, Budi!` |
| `GET /01.03-request/halo` | `200` | `Halo, Pengunjung!` |
| `POST /01.03-request/kontak` (JSON) | `201` | JSON `diterima` |
| `POST /01.03-request/form` | `200` | `Halo, Budi` |
| `GET /01.03-request/token` + `X-Token` | `200` | `abc123` |
| `GET /01.03-request/metode` | `200` | `GET` |
| `GET /01.03-request/semua-header` | `200` | array seluruh header |
| `GET /01.03-request/route-info` | `200` | `/route-info` |
| `POST /01.03-request/unggah` | `200` | `nama` dan `ukuran` file |

Tiga endpoint terakhir dipakai untuk memverifikasi
`docs/99-referensi/03-request.md`.

## Langkah 6 — uji halaman controller (`01.04-controller/`)

Folder ini menguji `docs/01-memulai/04-controller.md`. `index.php` memuat
`vendor/` workspace dan memuat kelas `App\` secara manual, karena autoload repo
tidak memetakan namespace `App\`.

```sh
docker exec selvi_test_dev sh -lc '
php -l /workspace/tests/01.04-controller/index.php
php -l /workspace/tests/01.04-controller/Controllers/HaloController.php
php -l /workspace/tests/01.04-controller/Services/SapaanService.php'

B=http://localhost:8089/01.04-controller
curl -s "$B/"; echo
curl -s "$B/sapa/Budi"; echo
curl -s "$B/method-inject/Budi"; echo
curl -s "$B/inject/Budi"; echo
curl -s "$B/array-callback/Budi"; echo
curl -s "$B/cari?q=selvi"; echo
curl -s "$B/urut/A/B"; echo
curl -s "$B/angka/5"; echo
curl -s "$B/grup"; echo
curl -s "$B/middleware"; echo
curl -s "$B/info-route/Budi"; echo
curl -s "$B/param"; echo
curl -s -X PATCH "$B/metode"; echo
```

Hasil terverifikasi:

| Endpoint | Status | Hasil |
|---|---|---|
| `GET /01.04-controller/` | `200` | `Halo dari controller!` |
| `GET /01.04-controller/sapa/Budi` | `200` | `Halo, Budi!` |
| `GET /01.04-controller/method-inject/Budi` | `200` | `Halo, Budi!` (dependency di parameter method) |
| `GET /01.04-controller/inject/Budi` | `200` | JSON `pesan`, `samaDenganConstructor` `true` |
| `GET /01.04-controller/array-callback/Budi` | `200` | `Halo, Budi!` |
| `GET /01.04-controller/cari?q=selvi` | `200` | JSON `q` berisi `selvi` |
| `GET /01.04-controller/cari` | `200` | JSON `q` berisi `null` |
| `GET /01.04-controller/urut/A/B` | `200` | `B\|A` |
| `GET /01.04-controller/angka/5` | `200` | JSON `id` berisi `5` |
| `GET /01.04-controller/angka/abc` | `400` | `ID harus angka` |
| `PATCH /01.04-controller/metode` | `200` | `PATCH` |
| `DELETE /01.04-controller/metode` | `200` | `DELETE` |
| `OPTIONS /01.04-controller/metode` | `200` | `OPTIONS` |
| `GET /01.04-controller/grup` | `200` | `Halo dari controller!` |
| `GET /01.04-controller/middleware` | `200` | `Halo dari controller! + middleware` |
| `GET /01.04-controller/middleware-route` | `200` | `Middleware route` |
| `GET /01.04-controller/info-route/Budi` | `200` | JSON method, URI, callback, dan parameter route |
| `GET /01.04-controller/param` | `200` | `Nilai Param` |
| `GET /01.04-controller/tipe-angka/abc` | `500` | sengaja salah: type hint `int` menolak string non-angka |
| `GET /01.04-controller/salah-nama/Budi` | `500` | sengaja salah: nama parameter tidak cocok |
| `GET /01.04-controller/tanpa-return` | `500` | sengaja salah: tidak mengembalikan `Response` |
| `GET /01.04-controller/kelas-salah` | `500` | sengaja salah: kelas controller tidak ada |

Endpoint `PATCH`/`DELETE`/`OPTIONS`, grup, dan middleware dipakai untuk
memverifikasi `docs/99-referensi/02-route.md`. `HaloController` mewarisi
`Selvi\Controller` untuk memverifikasi `docs/99-referensi/07-controller.md`.
Endpoint `method-inject`, `inject`, dan `array-callback` memverifikasi bagian
dependency di `docs/01-memulai/04-controller.md`.

## Langkah 7 — uji halaman struktur folder (`01.05-struktur-folder/`)

Folder ini menguji `docs/01-memulai/05-struktur-folder.md`. Layout yang diuji:
folder proyek sebagai document root, berisi `index.php` + `bootstrap.php` +
`app/Config/routes.php` + `app/Controllers/` + `app/Services/` + `.htaccess`.
`.htaccess` mengarahkan URI ke `index.php` sekaligus memblokir `app/`,
`vendor/`, `private/`, `bootstrap.php`, `composer.json`, dan file berawalan
titik.

```sh
docker exec selvi_test_dev sh -lc '
php -l /workspace/tests/01.05-struktur-folder/index.php
php -l /workspace/tests/01.05-struktur-folder/bootstrap.php
php -l /workspace/tests/01.05-struktur-folder/app/Config/routes.php
php -l /workspace/tests/01.05-struktur-folder/app/Controllers/HaloController.php
php -l /workspace/tests/01.05-struktur-folder/app/Services/SapaanService.php'

B=http://localhost:8089/01.05-struktur-folder
curl -s "$B/"; echo
curl -s "$B/sapa/Budi"; echo
curl -s -o /dev/null -w '404 %{http_code}\n' "$B/tidak-ada"
curl -s -o /dev/null -w 'app %{http_code}\n' "$B/app/Config/routes.php"
curl -s -o /dev/null -w 'bootstrap %{http_code}\n' "$B/bootstrap.php"
curl -s -o /dev/null -w 'vendor %{http_code}\n' "$B/vendor/autoload.php"
curl -s -o /dev/null -w 'composer %{http_code}\n' "$B/composer.json"
curl -s -o /dev/null -w 'private %{http_code}\n' "$B/private/contoh-rahasia.txt"
curl -s -o /dev/null -w 'env %{http_code}\n' "$B/.env"
curl -s -o /dev/null -w 'well-known %{http_code}\n' "$B/.well-known/test"
```

Hasil terverifikasi:

| Endpoint | Status | Hasil |
|---|---|---|
| `GET /01.05-struktur-folder/` | `200` | `Halo dari index.php` |
| `GET /01.05-struktur-folder/sapa/Budi` | `200` | `Halo, Budi!` |
| `GET /01.05-struktur-folder/tidak-ada` | `404` | JSON error `Route GET ... not found` |
| `GET /01.05-struktur-folder/app/Config/routes.php` | `403` | diblokir `.htaccess` |
| `GET /01.05-struktur-folder/bootstrap.php` | `403` | diblokir `.htaccess` |
| `GET /01.05-struktur-folder/private/contoh-rahasia.txt` | `403` | diblokir `.htaccess` (folder uji tidak memuat `private/`; tanpa aturan hasilnya `404`) |
| `GET /01.05-struktur-folder/vendor/autoload.php` | `403` | diblokir `.htaccess` (folder `vendor/` uji tidak ada; tanpa aturan hasilnya `404`) |
| `GET /01.05-struktur-folder/composer.json` | `403` | diblokir `.htaccess` (file tidak ada; tanpa aturan hasilnya `404`) |
| `GET /01.05-struktur-folder/.env` | `403` | diblokir `.htaccess` (file tidak ada; tanpa aturan hasilnya `404`) |
| `GET /01.05-struktur-folder/.well-known/test` | `404` | sengaja tidak diblokir (validasi sertifikat) |

`bootstrap.php` memetakan `App\` ke `app/` lewat `spl_autoload_register()`
karena autoload repo hanya memetakan `Selvi\` dan `Selvi\Tests\`; aplikasi
nyata memakai `autoload.psr-4`.

## Langkah 8 — uji halaman DTO (`02.01`–`02.03`)

Folder `02.01-data-object/`, `02.02-properti-nested/`, dan
`02.03-cast-dan-serialize/` memuat `vendor/` workspace dan tidak memerlukan
database.

```sh
docker exec selvi_test_dev sh -lc '
php -l /workspace/tests/02.01-data-object/index.php
php -l /workspace/tests/02.02-properti-nested/index.php
php -l /workspace/tests/02.03-cast-dan-serialize/index.php'

for u in /02.01-data-object/ /02.01-data-object/lengkap \
         /02.01-data-object/wajib-hilang /02.02-properti-nested/ \
         /02.02-properti-nested/dua-tingkat \
         /02.02-properti-nested/objek-bersarang \
         /02.02-properti-nested/objek-null \
         /02.02-properti-nested/objek-null-wajib \
         /02.02-properti-nested/tanpa-anak /02.03-cast-dan-serialize/ \
         /02.03-cast-dan-serialize/null; do
  printf '%-40s ' "$u"
  curl -s -o /dev/null -w '%{http_code}\n' "http://localhost:8089$u"
done
```

Hasil terverifikasi:

| Endpoint | Status | Hasil |
|---|---|---|
| `GET /02.01-data-object/` | `200` | `idKontak` `1` (dari `'1'`), `nmKontak` `Halo`, `nomor` tidak muncul |
| `GET /02.01-data-object/lengkap` | `200` | tiga kunci termasuk `nomor` |
| `GET /02.01-data-object/wajib-hilang` | `200` | `toArray.idKontak` `null`, `inisialisasi` `false` |
| `GET /02.02-properti-nested/` | `200` | `departemen` terisi dari kunci `departemen__...` |
| `GET /02.02-properti-nested/dua-tingkat` | `200` | `unit.departemen` terisi dari `unit_departemen__...` |
| `GET /02.02-properti-nested/objek-bersarang` | `200` | objek `grup` menjadi DTO |
| `GET /02.02-properti-nested/objek-null` | `200` | `"grup": null` pada properti nullable |
| `GET /02.02-properti-nested/objek-null-wajib` | `500` | `TypeError: Cannot assign null to property KontakGrupWajibDTO::$grup of type GrupDTO` |
| `GET /02.02-properti-nested/tanpa-anak` | `200` | `unit` dan `departemen` tidak muncul |
| `GET /02.03-cast-dan-serialize/` | `200` | `opsi` array, `dibuat` `Y-m-d H:i:s`, `diperbarui` `DATE_ATOM` |
| `GET /02.03-cast-dan-serialize/null` | `200` | nilai `null` dipertahankan |

## Langkah 9 — uji halaman model (`03.01`–`03.03`)

Folder ini memakai model contoh di `tests/app/Models/` dan koneksi `main`.

```sh
for u in /03.01-model/ /03.01-model/find /03.01-model/first-count \
         /03.01-model/where /03.01-model/offset /03.01-model/to-array-db \
         /03.01-model/notmapped /03.01-model/create-update-delete \
         /03.01-model/ekspresi /03.01-model/ekspresi-array \
         /03.01-model/ekspresi-raw-model \
         /03.01-model/ekspresi-resolver-missing \
         /03.01-model/ekspresi-resolver-nonstatic \
         /03.01-model/ekspresi-resolver-tipe \
         /03.01-model/ekspresi-resolver-invalid \
         /03.01-model/ekspresi-count /03.01-model/ekspresi-toarraydb \
         /03.01-model/ekspresi-key \
         /03.02-relasi/ /03.02-relasi/aggregate /03.02-relasi/nested \
         /03.02-relasi/nested-aggregate /03.02-relasi/relations \
         /03.02-relasi/aggregate-ekspresi /03.02-relasi/aggregate-resolver \
         /03.02-relasi/nested-ekspresi /03.02-relasi/nested-resolver \
         /03.02-relasi/nested-aggregate-resolver \
         /03.02-relasi/tanpa-relasi /03.03-konversi-value/dasar \
         /03.03-konversi-value/array /03.03-konversi-value/tanggal \
         /03.03-konversi-value/enum /03.03-konversi-value/custom \
         /03.03-konversi-value/model; do
  printf '%-44s ' "$u"
  curl -s -o /dev/null -w '%{http_code}\n' "http://localhost:8089$u"
done
```

Hasil terverifikasi:

| Endpoint | Status | Hasil |
|---|---|---|
| `GET /03.01-model/` | `200` | dua model terurut |
| `GET /03.01-model/find` | `200` | satu model, relasi `null` |
| `GET /03.01-model/first-count` | `200` | id `5`, count `5` |
| `GET /03.01-model/where` | `200` | count `5` |
| `GET /03.01-model/offset` | `200` | id `[6,7]` |
| `GET /03.01-model/to-array-db` | `200` | kunci nama kolom |
| `GET /03.01-model/notmapped` | `200` | `catatan` hanya di `toArray` |
| `GET /03.01-model/create-update-delete` | `200` | update/delete `true` |
| `GET /03.01-model/ekspresi` | `200` | resolver `COALESCE` |
| `GET /03.01-model/ekspresi-array` | `200` | expression array, kualifikasi otomatis |
| `GET /03.01-model/ekspresi-raw-model` | `200` | node `raw` dipakai apa adanya |
| `GET /03.01-model/ekspresi-resolver-missing` | `400` | resolver tidak ditemukan |
| `GET /03.01-model/ekspresi-resolver-nonstatic` | `400` | resolver tidak static |
| `GET /03.01-model/ekspresi-resolver-tipe` | `400` | tipe node tidak dikenali |
| `GET /03.01-model/ekspresi-resolver-invalid` | `400` | hasil resolver bukan node |
| `GET /03.01-model/ekspresi-count` | `200` | `count` `5` |
| `GET /03.01-model/ekspresi-toarraydb` | `200` | tulis tetap kolom fisik |
| `GET /03.01-model/ekspresi-key` | `400` | key + expression ditolak |
| `GET /03.02-relasi/` | `200` | `departemen`/`unit` terisi |
| `GET /03.02-relasi/aggregate` | `200` | `jmlKontak` `5`/`null` |
| `GET /03.02-relasi/nested` | `200` | `unit.departemen` terisi |
| `GET /03.02-relasi/nested-aggregate` | `200` | `unit.departemen.jmlKontak` `5` |
| `GET /03.02-relasi/aggregate-ekspresi` | `200` | array expression menghasilkan `5`/`0` |
| `GET /03.02-relasi/aggregate-resolver` | `200` | resolver menghasilkan `5`/`0` |
| `GET /03.02-relasi/nested-ekspresi` | `200` | model anak array expression, subquery mode |
| `GET /03.02-relasi/nested-resolver` | `200` | model anak resolver, subquery mode |
| `GET /03.02-relasi/nested-aggregate-resolver` | `200` | resolver agregat bertingkat, `5` |
| `GET /03.02-relasi/relations` | `200` | tiga relasi |
| `GET /03.02-relasi/tanpa-relasi` | `200` | relasi `null` |
| `GET /03.03-konversi-value/dasar` | `200` | cast int/bool/float/string |
| `GET /03.03-konversi-value/array` | `200` | `ArrayCast` |
| `GET /03.03-konversi-value/tanggal` | `200` | `DateTimeCast` |
| `GET /03.03-konversi-value/enum` | `200` | `EnumCast` |
| `GET /03.03-konversi-value/custom` | `200` | cast kustom |
| `GET /03.03-konversi-value/model` | `200` | `toArray`/`toArrayDb` |

## Langkah 10 — uji halaman DML (`04.03`–`04.04`)

Folder ini memakai koneksi `main` dari `tests/private/.ENV` dan data contoh di
tabel `kontak`/`departemen`.

```sh
for u in /04.03-membaca-data/ /04.03-membaca-data/sql \
         /04.03-membaca-data/row /04.03-membaca-data/row-kosong \
         /04.03-membaca-data/where \
         /04.03-membaca-data/join /04.03-membaca-data/order-limit \
         /04.03-membaca-data/hasil-bool /04.03-membaca-data/fungsi \
         /04.03-membaca-data/fungsi-agregat \
         /04.03-membaca-data/fungsi-bertumpuk /04.03-membaca-data/fungsi-sql \
         /04.03-membaca-data/fungsi-null /04.03-membaca-data/fungsi-raw \
         /04.03-membaca-data/fungsi-invalid /04.04-menulis-data/ \
         /04.04-menulis-data/tanpa-where \
         /04.04-menulis-data/tanpa-where-delete; do
  printf '%-44s ' "$u"
  curl -s -o /dev/null -w '%{http_code}\n' "http://localhost:8089$u"
done
```

Hasil terverifikasi:

| Endpoint | Status | Hasil |
|---|---|---|
| `GET /04.03-membaca-data/` | `200` | tiga baris `kontak` |
| `GET /04.03-membaca-data/sql` | `200` | SQL dengan join, group, order, limit |
| `GET /04.03-membaca-data/row` | `200` | `num_rows` `1` |
| `GET /04.03-membaca-data/row-kosong` | `200` | `num_rows` `0`, `row` `null` |
| `GET /04.03-membaca-data/where` | `200` | `operator` `3`, `null` `0`, `raw` `5`, `nested` `2` |
| `GET /04.03-membaca-data/join` | `200` | satu grup `Programmer` |
| `GET /04.03-membaca-data/order-limit` | `200` | `["8","7"]` |
| `GET /04.03-membaca-data/hasil-bool` | `200` | `query('SET ...')` menghasilkan `bool` |
| `GET /04.03-membaca-data/fungsi` | `200` | `COALESCE`, `CONCAT`, `NOW` terisi |
| `GET /04.03-membaca-data/fungsi-agregat` | `200` | `COUNT(*)`, `MAX` dengan alias |
| `GET /04.03-membaca-data/fungsi-bertumpuk` | `200` | `COALESCE(IFNULL(...))` |
| `GET /04.03-membaca-data/fungsi-sql` | `200` | SQL fungsi sesuai |
| `GET /04.03-membaca-data/fungsi-null` | `200` | literal `null` menjadi `NULL` |
| `GET /04.03-membaca-data/fungsi-raw` | `200` | `COUNT(*) AS total` |
| `GET /04.03-membaca-data/fungsi-invalid` | `400` | `InvalidArgumentException` |
| `GET /04.04-menulis-data/` | `200` | insert/update/delete berhasil, tabel sementara dihapus |
| `GET /04.04-menulis-data/tanpa-where` | `400` | `LogicException` UPDATE |
| `GET /04.04-menulis-data/tanpa-where-delete` | `400` | `LogicException` DELETE |

Tabel `demo_tulis` dibuat dan dihapus di dalam endpoint `/`, jadi tidak perlu
pembersihan manual.

## Langkah 11 — uji migrasi dan seeder (`04.05`–`04.06`)

Migrasi:

```sh
curl -s http://localhost:8089/04.05-migrasi/bersihkan
docker exec -w /workspace/tests/04.05-migrasi selvi_test_dev \
  php console.php db:migrate demo_migrasi up --all --no-interaction
curl -s http://localhost:8089/04.05-migrasi/
docker exec -w /workspace/tests/04.05-migrasi selvi_test_dev \
  php console.php db:migrate demo_migrasi down --all --no-interaction
```

Hasil yang diharapkan: `[success:up]`, `tabel_ada: true`, lalu
`[success:down]` dan `tabel_ada: false`. Menjalankan `up` dua kali
menghasilkan `[skipped:up]`.

Seeder:

```sh
curl -s http://localhost:8089/04.06-seeder/siapkan
docker exec -w /workspace/tests/04.06-seeder selvi_test_dev \
  php console.php db:seed demo_seed --no-interaction
curl -s http://localhost:8089/04.06-seeder/
docker exec -w /workspace/tests/04.06-seeder selvi_test_dev \
  php console.php db:seed demo_seed --no-interaction
```

Hasil yang diharapkan: `[success:seed]`, `jumlah: 2`, lalu `[skipped:seed]`
dengan jumlah tetap `2`.

Keduanya memakai koneksi terpisah (`demo_migrasi`, `demo_seed`) yang menunjuk
database yang sama, tetapi path file terdaftar terpisah. Tabel `_migration`
dipakai bersama.

## Langkah 12 — uji halaman CRUD API (`05.01`)

```sh
docker exec -w /workspace/tests/05.01-crud-endpoint selvi_test_dev \
  php console.php db:migrate main up --all --no-interaction

B=http://127.0.0.1:8089/05.01-crud-endpoint
curl -s -o /dev/null -w 'spec %{http_code}\n' "$B/specs/index.yaml"
curl -s -o /dev/null -w 'schema %{http_code}\n' "$B/specs/schemas/Produk.yaml"
curl -s -o /dev/null -w 'path %{http_code}\n' "$B/specs/paths/produk.yaml"
curl -s -o /dev/null -w 'ui %{http_code}\n' "$B/specs/"
curl -s "$B/produk"
curl -s -X POST "$B/produk" -H 'Content-Type: application/json' \
  -d '{"nama":"Kopi Gayo","harga":85000,"stok":12}'
curl -s "$B/produk?q=kopi&limit=10&offset=0"
curl -s -X PATCH "$B/produk/1" -H 'Content-Type: application/json' \
  -d '{"stok":20}'
curl -s -o /dev/null -w '%{http_code}\n' -X DELETE "$B/produk/1"

docker exec -w /workspace/tests/05.01-crud-endpoint selvi_test_dev \
  php console.php db:migrate main down --no-interaction
```

Hasil yang diharapkan: `[success:up]`, `spec 200`, `schema 200`, `path 200`,
`ui 200`, daftar kosong, `201` dengan `idProduk: 1`, daftar berisi satu
baris, update `stok` menjadi `20`, `204` saat delete, lalu `[success:down]`.
Setelah `down`, endpoint CRUD tidak bisa dipakai sampai migrasi dijalankan lagi.
Halaman Swagger UI memuat aset dari unpkg, jadi butuh akses internet.

## Langkah 13 — cek sintaks source

```sh
docker exec selvi_test_dev sh -lc '
for f in $(find /workspace/src -name "*.php"); do
  php -l "$f" > /dev/null || echo "FAIL $f"
done'
```

Tidak ada output berarti semua file lolos.

## Langkah 14 — uji driver PostgreSQL (`04.07-driver-postgresql/`)

Prasyarat tambahan: image `mochrira/dev` harus memuat ekstensi `pgsql`
(`php -m | grep -x pgsql`), container `db_postgres` berjalan di network
`database`, dan `tests/private/.ENV` memuat `DB_PGSQL_HOST`, `DB_PGSQL_PORT`,
`DB_PGSQL_USER`, `DB_PGSQL_PASS`, `DB_PGSQL_NAME`.

```sh
docker exec selvi_test_dev php -m | grep -x pgsql
docker exec -w /workspace/tests/04.07-driver-postgresql selvi_test_dev \
  php console.php db:migrate pgdemo up --no-interaction

B=http://127.0.0.1:8089/04.07-driver-postgresql
for p in skema tulis baca model ubah transaksi createdb error kunci hapus migrasi bersihkan; do
  curl -s -o /dev/null -w "$p %{http_code}\n" "$B/$p"
done

docker exec -w /workspace/tests/04.07-driver-postgresql selvi_test_dev \
  php console.php db:migrate pgdemo down --no-interaction
```

Hasil yang diharapkan: `pgsql` muncul sekali dari `php -m`, `[success:up]`,
seluruh endpoint `200` kecuali `error` yang sengaja `500` dengan
`sql.state: 42P01`, lalu `[success:down]`. Urutan endpoint penting: `skema`
harus dijalankan sebelum `tulis`, dan `hapus`/`bersihkan` membersihkan tabel
serta baris `_migration` milik harness ini.

## Catatan penting

- Hanya `01.01-instalasi/` yang menguji **paket terbit di Packagist**, karena
  halaman itu memang menjelaskan alur instalasi user.
- Folder uji halaman lain (mis. `01.02-response/`) memuat `vendor/` workspace
  lewat `require __DIR__.'/../../vendor/autoload.php';`, sehingga yang diuji
  adalah `src/` lokal. Jangan menjalankan Composer di folder-folder itu.
- Harness `tests/index.php` juga memuat `vendor/` repo (autoload root
  memetakan `Selvi\` ke `src/`).
- Pola "route dipisah ke `app/Config/routes.php`" sudah dibuktikan oleh harness
  itu sendiri: `tests/index.php` me-`require` `app/Config/routes.php`, lalu
  `Framework::run()`.
- `tests/private/.ENV` berisi kredensial database lokal dan diabaikan git.
- Port `8090` tidak dipakai oleh compose saat ini; mapping-nya `8089:80`.
  Ubah `ports:` bila ingin memakai port lain.
