# Technical Rule — โครงสร้างและ convention

> **อ่านก่อนเขียน Controller / Service / Model / route / migration / config ทุกครั้ง**
> กฎความปลอดภัยอยู่ที่ [`security.md`](security.md) · กฎด้าน UI อยู่ที่ [`design.md`](design.md) และ [`components.md`](components.md)

---

## 1. Tech Stack

| รายการ | ค่าที่ใช้ | ขั้นต่ำ |
|---|---|---|
| ภาษา | PHP native — **ไม่ใช้ framework** | 8.1 |
| ฐานข้อมูล | MySQL หรือ MariaDB ผ่าน **PDO เท่านั้น** (ห้าม `mysqli` / `mysql_*`) | MySQL 8.0 / MariaDB 10.4 |
| Frontend | **CSS เขียนเอง ไม่ใช้ CSS framework** — custom properties + `@layer` · JS เขียนเอง ไม่มี build step | — |
| Dependency | **ไม่มี** — autoload เขียนเองที่ `core/autoload.php` · ไม่ต้องมี composer เพื่อรันระบบ | — |

เลือก native เพราะต้องการให้โปรเจคเบา เหมาะกับงานขนาดไม่ใหญ่ และ deploy ผ่าน FTP ได้โดยไม่ต้องมี CLI บนเซิร์ฟเวอร์

### ‼️ ระบบต้องรันได้ด้วย `git clone` อย่างเดียว

**เกณฑ์: clone มาแล้วเปิดหน้าเว็บได้ทันที โดยไม่ต้องติดตั้งเครื่องมืออะไรเพิ่ม**

เดิม composer ถูกติดตั้งไว้เพื่อทำงานเดียวคือ map namespace → โฟลเดอร์ ซึ่ง `core/autoload.php` ทำเองได้ในไม่กี่บรรทัด
แลกมาด้วยการที่ทุกคนต้องมี composer บนเครื่องก่อน และ clone มาแล้วใช้ไม่ได้จนกว่าจะรัน `composer install` — **ซึ่งขัดกับเหตุผลที่เลือก PHP native ตั้งแต่แรก**

| | ต้องมี composer ไหม |
|---|---|
| เปิดหน้าเว็บ · `server.php` | ❌ |
| `scripts/` ทุกตัว รวม `check.php` | ❌ |
| CI และ deploy ผ่าน FTP | ❌ |
| ลงเครื่องมือเสริม (`phpstan`, `phpunit`) | ✅ เฉพาะคนที่อยากใช้ |

> ‼️ **วันที่ต้องเพิ่ม library จริง** ให้เอา composer กลับมาแล้ว `require vendor/autoload.php` **ต่อจาก** `core/autoload.php` ไม่ต้องลบของเดิม — `spl_autoload_register` ต่อกันได้หลายตัว
> และการเพิ่ม dependency ต้องขออนุญาตผู้ใช้ก่อนทุกครั้งตามกฎเหล็กข้อ 2 อยู่แล้ว

> **ห้ามเพิ่ม package โดยไม่ถามผู้ใช้ก่อน** (กฎเหล็กข้อ 2) และ **ห้ามโหลดอะไรจาก CDN** — ไฟล์ภายนอกทุกอย่างต้องเก็บใน `public/assets/vendor/` หรือ `public/assets/fonts/`

> **XAMPP ที่ทีมใช้มาพร้อม MariaDB ไม่ใช่ MySQL** จึงต้องรองรับทั้งสองตัว
> ข้อต่างที่ต้องรู้: MariaDB ไม่มีชนิด `JSON` จริง มันเป็น alias ของ `LONGTEXT` — เขียน `JSON` ใน migration ได้ปกติ แต่**ห้ามพึ่งฟังก์ชันหรือ index ที่มีเฉพาะ JSON ของ MySQL 8**

---

## 2. โครงสร้างโฟลเดอร์

```
project-root/
├── app/
│   ├── Controllers/       รับ request → เรียก Service → ส่งข้อมูลให้ View
│   ├── Services/          business logic ทั้งหมดอยู่ที่นี่
│   ├── Models/            เข้าถึงฐานข้อมูล (query ผ่าน PDO)
│   ├── Middlewares/       ตรวจสิทธิ์ก่อนเข้าถึง Controller
│   └── Views/
│       ├── layouts/       header/footer/nav กลาง + ปุ่มซัพพอร์ต + สลับภาษา + สลับโหมด
│       ├── partials/      component กลางทั้งหมด + icons/
│       ├── pages/         เนื้อหาแต่ละหน้า
│       └── manuals/       บทความคู่มือ แยกโฟลเดอร์ตามภาษา
├── core/
│   ├── Router.php         จับคู่ route + รัน middleware
│   ├── Path.php           ‼️ ตรวจ base path เอง — หัวใจของ url()
│   ├── Database.php       PDO connection (Singleton)
│   ├── Controller.php     base Controller — render / redirect / json / flash
│   ├── Model.php          base Model — ‼️ จุดที่เขียน audit log อัตโนมัติ
│   ├── Request.php        อ่าน input + validate
│   ├── Auth.php           login/logout · Auth::id() · Auth::role() · rate limit
│   ├── Security.php       CSRF · escape · security headers
│   ├── Uploader.php       อัปโหลดปลอดภัย
│   ├── Lang.php           โหลด lang/ + เลือกภาษา
│   ├── Logger.php         เขียน log + รหัสอ้างอิง error
│   ├── autoload.php       ‼️ แผนที่ namespace → โฟลเดอร์ ที่เดียว (แทน composer)
│   └── helpers.php        __() · e() · url() · old() · csrf_field()
├── config/
│   ├── config.php         ‼️ ที่เดียวที่อ่าน .env
│   ├── routes.php         เส้นทางทั้งหมด + ผูก middleware ตามกลุ่ม
│   └── manuals.php        ทะเบียนบทความคู่มือ
├── lang/                  th.php · en.php (+ ภาษาอื่นตามโปรเจค)
├── database/
│   ├── migrations/        ไฟล์ .sql เรียงด้วยเลขนำหน้า
│   └── seeders/           ข้อมูลตัวอย่าง
├── public/                ‼️ document root ต้องชี้มาที่นี่เท่านั้น
│   ├── index.php          entry point เดียวของระบบ
│   ├── .htaccess
│   └── assets/            css/app.css · js/ · images/ · fonts/ · vendor/ · manuals/
├── scripts/               ‼️ ของมาตรฐาน ห้ามแก้ในโปรเจค (ตั้งแต่ 3.15.0) · check · migrate · design-sync
│                          standard-update · backup · hooks/ · upgrades/
├── storage/
│   ├── logs/app.log
│   ├── uploads/           ‼️ เก็บนอก public/ เสิร์ฟผ่าน PHP ที่ตรวจสิทธิ์แล้ว
│   └── backups/
├── .github/workflows/     deploy.yml
├── .claude/               commands/wf.md · settings.json
├── server.php             รันบนเครื่อง local เท่านั้น ห้ามอัปขึ้น server
├── standard/              ‼️ มาตรฐานกลาง อ่านอย่างเดียว ห้ามแก้ในโปรเจค
├── project/               BRIEF · SCOPE · PLAN · DESIGN · STATE · QUESTIONS · NOTES
├── vendor/                ‼️ มีเฉพาะเมื่อลงเครื่องมือเสริม — ระบบไม่ต้องใช้
└── CLAUDE.md  README.md  composer.json  phpstan.neon  .env  .env.example  .gitignore
```

> ‼️ **ตั้ง document root ที่ `public/` เท่านั้น** ห้ามชี้ที่ root ของโปรเจค มิฉะนั้น `.env`, `storage/` และซอร์สโค้ดทั้งหมดจะเข้าถึงได้จากเบราว์เซอร์โดยตรง

---

## 3. การแบ่งหน้าที่

```
Router → Middleware (ถ้ามี) → Controller → Service → Model → Database
```

| ชั้น | ทำอะไร | ห้ามทำ |
|---|---|---|
| **Controller** | รับ input จาก Router · เรียก Service · ส่งผลลัพธ์ให้ View | ห้ามมี business logic หรือ query |
| **Service** | business logic ทั้งหมด — คำนวณ ตรวจเงื่อนไขธุรกิจ ประกอบ Model หลายตัว | ห้ามแตะ `$_POST`/`$_GET` ตรง ให้ Controller ส่งค่ามา |
| **Model** | CRUD เข้าถึงข้อมูลอย่างเดียว | ห้ามมี business logic ปน |
| **Middleware** | ตรวจสิทธิ์ก่อนเข้าถึง Controller | ห้ามทำอย่างอื่น |

- ออกแบบให้ **Service ตัวเดียวถูกเรียกซ้ำได้จากหลาย Controller**
- **ไฟล์ที่ใหญ่เกินไปคือสัญญาณว่าทำหลายหน้าที่เกินไป** ให้แยกออก

---

## 4. Routing & URL

ใช้ **Front Controller Pattern** — ทุก request เข้าที่ `public/index.php` ผ่าน `.htaccess` rewrite แล้ว `core/Router.php` จับคู่ `HTTP Method + URI` → `Controller@method`

```apache
# public/.htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

Router ต้องผูก Middleware กับ route หรือกลุ่ม route ได้

```php
$router->get('/products', 'ProductController@index');
$router->group(['middleware' => 'AdminMiddleware'], function ($router) {
    $router->get('/admin/products', 'Admin\ProductController@index');
    $router->delete('/admin/products/{id}', 'Admin\ProductController@destroy');
});
```

ฟอร์ม HTML รองรับแค่ GET/POST ใช้ method spoofing ผ่าน hidden field

```html
<input type="hidden" name="_method" value="PUT">
```

### ‼️ ทุกลิงก์ต้องผ่าน `url()`

**ห้ามเขียน `href="/login"` หรือ `src="/assets/..."` ตรงๆ** ต้องเป็น `href="<?= e(url('/login')) ?>"` เสมอ

เหตุผล: ระบบต้องเปิดได้ทั้งกรณี document root ชี้ที่ `public/` ตรงๆ (`/login`) และกรณีติดตั้งใต้ subdirectory (`/ชื่อระบบ/login`) — `core/Path.php` ตรวจ base path จากตำแหน่ง `index.php` เองโดยไม่ต้องตั้งค่า · **เขียน path ดิบไว้ วันที่ย้ายที่ติดตั้งลิงก์จะพังทั้งระบบและ asset โหลดไม่ขึ้น**

- ประกาศ route ใน `config/routes.php` เป็น**เส้นทางภายในแอป**เสมอ (`/login`) Router ตัด base path ให้เองก่อนเทียบ
- `redirect()` ของ base Controller เติม base path ให้แล้ว
- ตรวจด้วย `grep -rn 'href="/\|action="/\|src="/' app/Views/` ผลลัพธ์ต้องว่าง (ยกเว้นโฟลเดอร์ `manuals/`)

### กติกาตั้งชื่อ URL

- ตัวพิมพ์เล็กและ `kebab-case` เท่านั้น — `/order-history` ไม่ใช่ `/orderHistory`
- **ห้ามให้ชื่อไฟล์หรือนามสกุลปรากฏใน URL** (`/product.php?id=12` ห้ามใช้)

| Method | URL | หน้าที่ |
|---|---|---|
| GET | `/products` | แสดงรายการ |
| GET | `/products/create` | ฟอร์มสร้างใหม่ |
| POST | `/products` | บันทึกข้อมูลใหม่ |
| GET | `/products/{id}` | แสดงรายละเอียด |
| GET | `/products/{id}/edit` | ฟอร์มแก้ไข |
| PUT | `/products/{id}` | อัปเดต |
| DELETE | `/products/{id}` | ลบ |

### ‼️ สถานะของหน้ารายการต้องอยู่ใน query string

**ตัวกรอง · หน้า · การเรียง · จำนวนแถว ต้องเก็บใน URL ไม่ใช่ session**

```
/orders?status=pending&from=2026-08-01&to=2026-08-20&sort=created_at&dir=desc&per=25&page=2
```

เก็บใน session แล้วจะเกิด 3 ปัญหาที่แก้ทีหลังยาก: **แชร์ลิงก์รายงานให้คนอื่นดูไม่ได้ · กด back แล้วค่าหาย · เปิดสองแท็บแล้วค่าตีกัน**

### JSON Response

ถ้าโปรเจคมีส่วนที่เรียกผ่าน AJAX ต้องตอบกลับรูปแบบเดียวกันทั้งระบบ

```json
{ "success": true, "data": {}, "message": "บันทึกสำเร็จ" }
```

`success` = ผลการทำงาน · `data` = ข้อมูล (ไม่มีให้เป็น `null`) · `message` = ข้อความแจ้งผู้ใช้ ต้องผ่าน `__()`
ถ้า error ต้องมี `code` ที่เป็นรหัสอ้างอิงด้วย

---

## 5. Database

เชื่อมต่อผ่าน `core/Database.php` (PDO, Singleton) **จุดเดียวทั้งระบบ**

```php
$dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,  // ให้ MySQL ทำ prepare จริง
]);
```

> `ATTR_EMULATE_PREPARES => false` สำคัญ — ถ้าเปิด emulate ไว้ PDO จะประกอบ query เป็น string เอง ทำให้การป้องกัน SQL injection อ่อนลง

```php
// ✅ ถูก
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
$stmt->execute(['id' => $id]);

// ❌ ห้ามเด็ดขาด
$pdo->query("SELECT * FROM products WHERE id = {$id}");
```

### การตั้งชื่อ

- ตาราง: พหูพจน์ `snake_case` — `users`, `order_items`
- คอลัมน์: `snake_case` — `created_at`, `user_id`
- Primary key ชื่อ `id` · Foreign key ชื่อ `{เอกพจน์}_id`
- ทุกตารางควรมี `created_at`, `updated_at` (**ยกเว้น `audit_logs` ที่ห้ามมี** ดู [`features/audit-log.md`](features/audit-log.md))
- **charset `utf8mb4` collation `utf8mb4_unicode_ci` เสมอ** ทั้งฐานข้อมูลและ connection เพื่อไม่ให้ภาษาไทย จีน และ emoji เพี้ยน
- ตาราง `users` ต้องมีคอลัมน์ `locale` (จำภาษาข้ามอุปกรณ์) และ `theme` (จำโหมดสว่าง/มืด)

### ‼️ กฎที่ย้อนแก้ทีหลังแพงที่สุด

1. **คอลัมน์ที่เก็บสถานะ / ประเภท / บทบาท ต้องเก็บเป็น key ภาษาอังกฤษ** (`done` ไม่ใช่ `เสร็จสิ้น`) แปลตอนแสดงผ่าน `__()` — เก็บข้อความแสดงผลไว้ วันที่แก้คำหรือเพิ่มภาษา ข้อมูลเก่าทั้งตารางใช้ไม่ได้
2. **วันเวลาเก็บเป็นคริสต์ศักราชเสมอ** แปลง พ.ศ. ตอนแสดงเท่านั้น — เก็บ พ.ศ. แล้วการเรียงลำดับและคำนวณช่วงวันเพี้ยนทั้งระบบ
3. **เงินเก็บเป็นจำนวนเต็มหน่วยย่อย (สตางค์) หรือ `DECIMAL`** ห้ามใช้ `FLOAT`/`DOUBLE` — ปัดเศษเพี้ยนแล้วยอดไม่ตรง

### Soft delete เป็นค่าเริ่มต้น

ตารางที่ผู้ใช้ลบข้อมูลได้ ให้มีคอลัมน์ `deleted_at NULL` แทนการลบจริง

- base Model ต้องกรอง `deleted_at IS NULL` ให้อัตโนมัติ
- ลบจริงได้เฉพาะกรณีที่ตกลงไว้ใน `SCOPE.md` (เช่น ข้อมูลชั่วคราว หรือข้อกำหนดเรื่องความเป็นส่วนตัว)
- เหตุผล: ลบจริงแล้ว **audit log จะชี้ไปหาของที่ไม่มีอยู่** และผู้ใช้ที่ลบผิดจะกู้ไม่ได้
- unique index ต้องเผื่อ soft delete ด้วย ไม่งั้นสร้างข้อมูลชื่อเดิมซ้ำไม่ได้ทั้งที่ของเก่าถูกลบไปแล้ว

### Index

- ทุก foreign key ต้องมี index
- คอลัมน์ที่ใช้ใน `WHERE`, `ORDER BY`, และตัวกรองของหน้ารายงาน ต้องมี index
- **ทดสอบกับข้อมูล 50-100 แถวขึ้นไปเสมอ** — 3 แถวมองไม่เห็นปัญหา
- ระวัง N+1: วนลูปแล้ว query ในลูป ให้ดึงทีเดียวด้วย `WHERE id IN (...)` แทน

### Migration & Seeder

- `database/migrations/` ตั้งชื่อด้วย**เลขลำดับนำหน้าเสมอ** — `001_create_users_table.sql`
- ‼️ **ทุกไฟล์ `.sql` ต้องขึ้นต้นด้วย**

```sql
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
```

> ถ้าไม่มีบรรทัดนี้ แล้วมีคนรัน `mysql < file.sql` บน Windows โดยไม่ใส่ `--default-character-set` ข้อความไทยจะถูกเขียนลงฐานข้อมูลแบบเพี้ยน (double encoding) **โดยไม่มี error แจ้งเลย** และกู้คืนยากเพราะเสียตั้งแต่ตอน `INSERT` · การใส่บรรทัดนี้ทำให้ไฟล์ถูกต้องไม่ว่าใครรันด้วยวิธีไหน แทนที่จะหวังว่าทุกคนจะจำ flag ได้

**วิธีรัน** — `scripts/migrate.php` จำว่ารันอันไหนไปแล้วในตาราง `migrations` จึงรันซ้ำได้ไม่พัง

| ทาง | ใช้เมื่อไหร่ |
|---|---|
| `php scripts/migrate.php` | บนเครื่องตัวเอง |
| `GET /_migrate?token=...` | CI ยิงเรียกอัตโนมัติหลังอัปไฟล์ — token อยู่ใน `.env` |
| `php scripts/migrate.php --export` | ออกเป็นไฟล์ `.sql` เดียวไว้ import ผ่าน phpMyAdmin เป็นทางสำรอง |

- **ห้ามแก้ไฟล์ migration ที่ apply ไปแล้ว** ต้องสร้างไฟล์ใหม่เสมอ — ไม่งั้น schema ระหว่างเครื่องในทีมจะเพี้ยนไม่ตรงกัน
- **ห้ามใส่ข้อมูลจริงลง seeder** (รหัสผ่านจริง ข้อมูลลูกค้าจริง) ใช้ข้อมูลตัวอย่างเท่านั้น
- seeder ต้องมีข้อมูล **50-100 แถวขึ้นไป** สำหรับตารางหลัก ไม่ใช่ 3 แถว

---

## 6. ไฟล์ที่ผู้ใช้อัปโหลด

- เก็บใน `storage/uploads/` **นอก `public/`** เสิร์ฟผ่าน Controller ที่ตรวจสิทธิ์ก่อนเสมอ
- **ชื่อไฟล์บนดิสก์ต้องเป็นชื่อที่สร้างใหม่** (เช่น `uuid` หรือ hash) ห้ามใช้ชื่อที่ผู้ใช้ส่งมา
- **เก็บชื่อเดิมไว้ในฐานข้อมูลเพื่อแสดงผลและตอนดาวน์โหลด** — ชื่อไฟล์ภาษาไทยและอักขระพิเศษพังบน server จริง แต่ผู้ใช้ต้องเห็นชื่อที่ตัวเองตั้ง
- แยกโฟลเดอร์ตามปี/เดือน ไม่กองไว้โฟลเดอร์เดียว
- การตรวจชนิดไฟล์และขนาดอยู่ที่ [`security.md`](security.md)

---

## 7. Coding Standards

| ประเภท | รูปแบบ | ตัวอย่าง |
|---|---|---|
| Class | `PascalCase` | `ProductService` |
| Method / Function / Variable | `camelCase` | `getProductById()` |
| Constant | `UPPER_SNAKE_CASE` | `MAX_UPLOAD_SIZE` |
| ไฟล์ | ตรงกับชื่อ class (PSR-4) | `ProductService.php` |

แผนที่ namespace อยู่ที่ `core/autoload.php` **จุดเดียว**

```php
const PSR4_MAP = [
    'App\\'  => __DIR__ . '/../app/',
    'Core\\' => __DIR__ . '/../core/',
];
```

> ‼️ **ห้ามประกาศซ้ำในหัวข้อ `autoload` ของ `composer.json`** — สองที่แล้ววันที่แก้ที่หนึ่ง อีกที่จะค้าง
> อาการที่ได้คือเครื่องมือวิเคราะห์รายงาน class not found ทั้งโปรเจคโดยไม่มีอะไรผิดจริง
> เครื่องมือที่ต้องรู้แผนที่นี้ให้ชี้มาที่ไฟล์เดียวกัน (`phpstan.neon` ใช้ `bootstrapFiles`)

**ชื่อตัวแปร ฟังก์ชัน คลาส และคอมเมนต์เป็นภาษาอังกฤษ** ส่วนที่คุยกับผู้ใช้เป็นภาษาไทย

**คอมเมนต์:** เขียนเฉพาะตอนที่โค้ดสื่อความหมายเองไม่ได้ (เหตุผลของ workaround, เงื่อนไขธุรกิจที่ไม่ชัด) — **ห้ามเขียนคอมเมนต์อธิบายว่าโค้ดทำอะไรถ้าอ่านจากชื่อแล้วเข้าใจอยู่แล้ว**

> ‼️ **ห้ามเขียนเครื่องหมายปิด PHP tag ในคอมเมนต์แบบ `//` เด็ดขาด** — PHP ปิด tag จริงตรงนั้น
> ผลคือโค้ดที่เหลือของไฟล์กลายเป็น HTML และได้ parse error ที่ **ท้ายไฟล์** ซึ่งชี้ผิดที่จนหาต้นเหตุยาก
> ถ้าจำเป็นต้องพูดถึงมัน ให้ใช้คอมเมนต์แบบ `/* */` แทน

---

## 8. Error Handling & Logging

- ใช้ `try/catch` ครอบงานที่เสี่ยง (DB, external API, file operation) ที่ระดับ Service/Controller
- **ต้องมี helper กลางจุดเดียวสำหรับเขียน log** ห้ามเรียก `error_log()` กระจัดกระจาย
- log ลง `storage/logs/app.log` รูปแบบ `[YYYY-MM-DD HH:MM:SS] LEVEL: message | context`
- **ห้าม log ข้อมูล sensitive** (รหัสผ่าน token บัตรเครดิต) เด็ดขาด
- production ต้อง `display_errors = 0` — **ห้ามแสดง stack trace ให้ผู้ใช้เห็น**
- **ข้อความ error ที่ผู้ใช้เห็นต้องผ่าน `__()`** ส่วนรายละเอียดที่ลง log เขียนภาษาอังกฤษได้ เพราะคนอ่านคือ dev

### หน้า error ที่ต้องมีครบ

`403` ไม่มีสิทธิ์ · `404` ไม่พบ · `500` ระบบผิดพลาด · `maintenance`

ทุกหน้าใช้ layout กลางและมีทางกลับเสมอ · **403 ต้องต่างจาก 404 ให้ชัด** เพราะ "เข้าได้แต่ไม่มีสิทธิ์" กับ "ไม่มีหน้านี้" คนละเรื่องกัน

### รหัสอ้างอิง error (บังคับ)

ทุกครั้งที่เกิด error ให้สร้างรหัสสั้นที่ไม่ซ้ำ (เช่น `ERR-7X4K2`) บันทึกลง log คู่กับรายละเอียด แล้ว**แสดงรหัสนั้นให้ผู้ใช้เห็น**

```
เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง
รหัสอ้างอิง: ERR-7X4K2
```

> ทำให้ผู้ใช้แจ้งปัญหาแบบสืบต่อได้จริง — แอดมินค้น log ด้วยรหัสนี้เจอทันที ไม่ต้องเดาว่าเจอ error ตัวไหน และผู้ใช้ไม่เห็นข้อมูลทางเทคนิคที่ไม่ควรเห็น · เชื่อมกับ [`features/support-system.md`](features/support-system.md)

---

## 9. Environment & Config

- ค่าที่เปลี่ยนตาม environment อยู่ใน `.env` เท่านั้น · มี `.env.example` เป็นแม่แบบ (ไม่มีค่าจริง)
- **`config/config.php` เป็นที่เดียวที่อ่าน `.env`** ห้ามอ่านกระจัดกระจายทั่วโปรเจค

```php
// config/config.php
date_default_timezone_set('Asia/Bangkok');

return [
    'env'   => $_ENV['APP_ENV'] ?? 'production',   // development | production
    'debug' => ($_ENV['APP_ENV'] ?? 'production') === 'development',
    'db'    => [
        'host' => $_ENV['DB_HOST'],
        'name' => $_ENV['DB_NAME'],
        'user' => $_ENV['DB_USER'],
        'pass' => $_ENV['DB_PASS'],
    ],
    'locales'        => ['th', 'en'],
    'default_locale' => 'th',
    'migrate_token'  => $_ENV['MIGRATE_TOKEN'] ?? null,
];
```

- ‼️ **`APP_ENV` ต้อง default เป็น `production`** — ถ้า default เป็น development แล้วลืมตั้งบน server จริง จะกลายเป็นเปิดแสดง error ให้คนนอกเห็น
- ตั้ง timezone `Asia/Bangkok` ที่จุดเดียวใน `config.php`
- `.gitignore` ต้องครอบคลุมอย่างน้อย `.env` · `vendor/` · `storage/logs/*` · `storage/uploads/*` · `storage/backups/*`
- ค่าที่ CI ต้องใช้ (FTP host/user/pass, `MIGRATE_TOKEN`) เก็บเป็น **GitHub Secrets** ห้าม commit

---

## 10. README

เขียนเพื่อให้ **developer ที่ไม่เคยเห็นระบบมาก่อนเริ่มงานต่อได้** ต้องมี

1. ภาพรวมของระบบ (ทำอะไร ใช้กับใคร)
2. requirement ก่อนติดตั้ง (PHP / MySQL version)
3. ขั้นตอนติดตั้ง — ตั้งค่า `.env` · `php scripts/migrate.php` · seeder · **ไม่มีขั้นติดตั้ง dependency** เพราะไม่มี dependency
4. **การตั้ง document root ให้ชี้ที่ `public/`** (สำคัญต่อความปลอดภัย)
5. โครงสร้างโฟลเดอร์แบบย่อ
6. วิธีรันบนเครื่อง local (`php server.php`)
7. รายการ environment variable พร้อมคำอธิบาย
8. วิธีทำงานของ CI และ secrets ที่ต้องตั้งใน GitHub
9. **วิธีเปิด pre-commit hook** — `git config core.hooksPath scripts/hooks`
   > ต้องอยู่ใน README เพราะ `.git/hooks/` ไม่ติดมากับการ clone · คนที่ไม่ได้ตั้งจะไม่มีอะไรตรวจให้โดยไม่รู้ตัว
