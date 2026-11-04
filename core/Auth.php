<?php
/**
 * Auth Contract — ทั้งโปรเจคต้องมีคำตอบเดียวกันว่า "ผู้ใช้ปัจจุบันคือใคร อ่านจากไหน"
 *
 * ‼️ เก็บแค่ user_id และ role ใน session
 *    ห้ามเก็บข้อมูลผู้ใช้ทั้งก้อน เพราะจะเก่าค้างถ้าผู้ใช้ถูกแก้ระหว่างที่ session ยังไม่หมดอายุ
 *    เช่นถูกลดสิทธิ์แล้วแต่ session เก่ายังไม่รู้
 *
 * ‼️ การเช็คสิทธิ์จริงต้องทำที่ Middleware กลางเท่านั้น
 *    เมธอด can() ในไฟล์นี้ใช้สำหรับ "ซ่อนเมนู" ได้ แต่ซ่อนเมนูไม่ใช่การเช็คสิทธิ์
 */

declare(strict_types=1);

namespace Core;

final class Auth
{
    /** จำนวนครั้งที่ผิดได้ต่อคู่ username + IP ก่อนล็อกคู่นั้น */
    private const MAX_ATTEMPTS    = 5;
    /** จำนวนครั้งที่ผิดได้ต่อ IP รวมทุก username — กันการไล่ลองรหัสเดียวกับหลายบัญชี */
    private const MAX_IP_ATTEMPTS = 20;
    /**
     * จำนวนครั้งที่ผิดได้ต่อ username รวมทุก IP — กันการเดารหัสแบบกระจายหลาย IP
     * ตั้งสูงไว้จงใจ: ต่ำเกินไปคนนอกจะใช้ล็อกเจ้าของบัญชีได้ง่าย
     */
    private const MAX_USER_ATTEMPTS = 50;
    /** เพดานต่อ username สำหรับอุปกรณ์ที่มี cookie อุปกรณ์ถูกต้อง (Core\DeviceCookie) — สูงกว่า แต่ไม่ไม่จำกัด */
    private const MAX_DEVICE_USER_ATTEMPTS = 200;
    private const LOCK_MINUTES    = 15;

    /**
     * hash ตายตัวสำหรับเทียบตอนไม่เจอผู้ใช้ — cost เดียวกับ PASSWORD_DEFAULT (bcrypt 10)
     *
     * ‼️ ต้องเป็นค่าคงที่ ห้าม password_hash() ตอน runtime
     *    hash ใหม่ทุก request ทำให้ username ที่ไม่มีอยู่ช้ากว่า (hash + verify) = เดาได้ว่าใครมีบัญชี
     */
    private const DUMMY_HASH = '$2y$10$BT1IMKiSaZ0XzEgCSnaDkud.6VIuEWWxc07ZaffmR7HTxPAzPLIoC';

    public static function id(): ?int
    {
        $id = Session::get('user_id');

        return is_int($id) ? $id : null;
    }

    public static function role(): ?string
    {
        $role = Session::get('role');

        return is_string($role) ? $role : null;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    /**
     * อ่านผู้ใช้ปัจจุบันจากฐานข้อมูลใหม่แล้วปรับ session ให้ตรง
     *
     * ‼️ AuthMiddleware เรียกทุก request — ไม่งั้นผู้ใช้ที่ถูกปิดใช้งาน ถูกลบ หรือถูกลดสิทธิ์
     *    ยังใช้สิทธิ์เดิมต่อได้จนกว่า session จะหมดอายุ
     *
     * @return bool false เมื่อบัญชีไม่มีสิทธิ์เข้าแล้ว (ล้าง session ให้แล้ว)
     */
    public static function refresh(): bool
    {
        $id = self::id();

        if ($id === null) {
            return false;
        }

        $user = Database::selectOne(
            'SELECT role, is_active, must_change_password FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            ['id' => $id]
        );

        if ($user === null || (int) $user['is_active'] !== 1) {
            Logger::info('session_revoked', ['user_id' => $id, 'ip' => self::ip()]);
            Session::destroy();
            // ‼️ ต้องส่ง config — ไม่ส่งแล้ว cookie ใหม่หลุด flag Secure และ idle timeout
            Session::start((array) config('session'));

            return false;
        }

        if ((string) $user['role'] !== self::role()) {
            Session::put('role', (string) $user['role']);
        }

        Session::put('must_change_password', (int) ($user['must_change_password'] ?? 0) === 1);

        return true;
    }

    /** บัญชีนี้ต้องตั้งรหัสผ่านใหม่ก่อนใช้งาน (ค่าจาก refresh() ล่าสุด) */
    public static function mustChangePassword(): bool
    {
        return Session::get('must_change_password') === true;
    }

    /** ใช้ซ่อนเมนูเท่านั้น — การกันจริงต้องอยู่ที่ Middleware */
    public static function can(string ...$roles): bool
    {
        $role = self::role();

        return $role !== null && in_array($role, $roles, true);
    }

    /**
     * พยายามเข้าสู่ระบบ
     *
     * @return array{ok:bool,reason:string,minutes:int}
     */
    public static function attempt(string $username, string $password): array
    {
        $ip = self::rateLimitKey();

        if (self::isLocked($username, $ip)) {
            return ['ok' => false, 'reason' => 'throttled', 'minutes' => self::LOCK_MINUTES];
        }

        $user = Database::selectOne(
            'SELECT id, username, password_hash, role, locale, theme, is_active, device_token_version
             FROM users
             WHERE username = :username AND deleted_at IS NULL
             LIMIT 1',
            ['username' => $username]
        );

        $hash = (string) ($user['password_hash'] ?? '');

        /* ‼️ ต้องเรียก password_verify() ทุกครั้ง แม้ไม่เจอผู้ใช้ — เทียบกับ hash หลอกแทน
              ไม่งั้นเวลาตอบที่ต่างกันบอกคนนอกได้ว่า username ไหนมีอยู่จริง */
        $verified = password_verify($password, $hash !== '' ? $hash : self::DUMMY_HASH);

        $valid = $user !== null
            && $hash !== ''
            && (int) $user['is_active'] === 1
            && $verified;

        if (!$valid) {
            self::recordFailure($username, $ip);

            // ‼️ ข้อความเดียวกันไม่ว่าจะผิดที่ชื่อผู้ใช้หรือรหัสผ่าน
            //    บอกแยกเท่ากับบอกคนนอกว่าบัญชีไหนมีอยู่จริง
            return ['ok' => false, 'reason' => 'failed', 'minutes' => 0];
        }

        // รหัสผ่านที่ hash ด้วยอัลกอริทึมเก่า ให้อัปเกรดเงียบๆ ตอนเข้าสำเร็จ
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            // cookie อุปกรณ์ลงลายเซ็นทับ hash ปัจจุบัน — ต้องใช้ค่าใหม่ไม่งั้นใบที่ออกวันนี้ใช้ไม่ได้
            $user['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            Database::run(
                'UPDATE users SET password_hash = :hash WHERE id = :id',
                ['hash' => $user['password_hash'], 'id' => $user['id']]
            );
        }

        self::clearFailures($username, $ip);
        self::login($user);
        DeviceCookie::issue($user);

        return ['ok' => true, 'reason' => '', 'minutes' => 0];
    }

    /** @param array<string, mixed> $user */
    public static function login(array $user): void
    {
        // ‼️ ต้องเรียกทุกครั้งหลัง login สำเร็จ เพื่อกัน session fixation
        Session::regenerate();
        Security::rotate();

        Session::put('user_id', (int) $user['id']);
        Session::put('role', (string) $user['role']);

        // จำภาษาและโหมดที่ผู้ใช้ตั้งไว้ ข้ามอุปกรณ์
        if (($user['locale'] ?? '') !== '' && Lang::allowed((string) $user['locale'])) {
            Session::put('locale', (string) $user['locale']);
        }
        if (in_array($user['theme'] ?? '', ['light', 'dark'], true)) {
            Session::put('theme', (string) $user['theme']);
        }

        Logger::info('login', ['user_id' => $user['id'], 'ip' => self::ip()]);

        /* ‼️ ต้องเรียกหลัง Session::put('user_id') เท่านั้น
           Model::audit() อ่านผู้ทำจาก Auth::id() เสมอ — เรียกก่อนหน้านี้จะได้ user_id ว่าง
           การเข้าสู่ระบบเป็นเหตุการณ์ที่ audit-log.md บังคับให้บันทึก */
        Model::audit('login', 'users', (int) $user['id']);
    }

    public static function logout(): void
    {
        $id = self::id();

        Logger::info('logout', ['user_id' => $id, 'ip' => self::ip()]);

        /* ‼️ ต้องเรียกก่อน Session::destroy()
           ล้าง session ไปแล้ว Auth::id() จะเป็น null และ log จะไม่รู้ว่าใครออกจากระบบ */
        if ($id !== null) {
            Model::audit('logout', 'users', $id);
        }

        // ล้างให้หมดจริง ไม่ใช่แค่ลบบางคีย์
        Session::destroy();
        Session::start((array) config('session'));
    }

    /** บันทึกภาษาหรือโหมดที่ผู้ใช้เลือกลงฐานข้อมูล เพื่อให้คงอยู่ข้ามอุปกรณ์ */
    public static function remember(string $column, string $value): void
    {
        $id = self::id();

        if ($id === null || !in_array($column, ['locale', 'theme'], true)) {
            return;
        }

        Database::run("UPDATE users SET {$column} = :value WHERE id = :id", [
            'value' => $value,
            'id'    => $id,
        ]);
    }

    // ------------------------------------------------------------- rate limit

    private static function isLocked(string $username, string $ip): bool
    {
        $username = mb_substr($username, 0, 100);

        // นับทั้ง 3 แบบใน query เดียว — ตอนโดนยิงรัว ตารางนี้คือตารางที่รับโหลดหนักที่สุด
        $row = Database::selectOne(
            'SELECT
                 SUM(ip_address = :ip1)                         AS by_ip,
                 SUM(username = :user1)                         AS by_user,
                 SUM(username = :user2 AND ip_address = :ip2)   AS by_pair
             FROM login_attempts
             WHERE (ip_address = :ip3 OR username = :user3)
               AND attempted_at > (NOW() - INTERVAL :minutes MINUTE)',
            [
                'ip1' => $ip, 'ip2' => $ip, 'ip3' => $ip,
                'user1' => $username, 'user2' => $username, 'user3' => $username,
                'minutes' => self::LOCK_MINUTES,
            ]
        );

        /* ‼️ ล็อกแข็งที่คู่ username + IP และที่ IP เท่านั้น
              ห้ามล็อกที่ username อย่างเดียวกับทุกคน ไม่งั้นคนนอกยิงรหัสผิดใส่ชื่อแอดมินจนเจ้าของเข้าไม่ได้ */
        if ((int) ($row['by_pair'] ?? 0) >= self::MAX_ATTEMPTS || (int) ($row['by_ip'] ?? 0) >= self::MAX_IP_ATTEMPTS) {
            return true;
        }

        /* เพดานต่อ username รวมทุก IP — กันเดารหัสแบบกระจายหลาย IP
           แต่ยกเว้น IP ที่เคยเข้าบัญชีนี้สำเร็จ → เจ้าของบัญชีตัวจริงยังเข้าได้แม้กำลังถูกโจมตี */
        if ((int) ($row['by_user'] ?? 0) < self::MAX_USER_ATTEMPTS) {
            return false;
        }

        /* ‼️ ข้อยกเว้นผูกกับ cookie อุปกรณ์ที่ลงลายเซ็น (Core\DeviceCookie) ไม่ใช่ IP
              IP เดียวกันอาจเป็นคนทั้งออฟฟิศ — cookie ออกให้เฉพาะเครื่องที่เคยเข้าบัญชีนี้สำเร็จใน 90 วัน */
        /* ‼️ ข้อยกเว้นไม่ไร้ขีดจำกัด — เกิน MAX_DEVICE_USER_ATTEMPTS ใน 15 นาที อุปกรณ์ที่รู้จักก็ถูกล็อกด้วย
              (cookie ที่หลุดไปต้องไม่เปิดทางเดารหัสได้ไม่จำกัด) */
        if ((int) ($row['by_user'] ?? 0) < self::MAX_DEVICE_USER_ATTEMPTS && DeviceCookie::validFor($username)) {
            return false;
        }

        Logger::warning('login_user_cap_reached', ['username' => $username, 'ip' => self::ip()]);

        return true;
    }

    private static function recordFailure(string $username, string $ip): void
    {
        Database::run(
            'INSERT INTO login_attempts (username, ip_address, attempted_at) VALUES (:username, :ip, NOW())',
            ['username' => mb_substr($username, 0, 100), 'ip' => $ip]
        );

        Logger::warning('login_failed', ['username' => mb_substr($username, 0, 100), 'ip' => $ip]);

        /* ‼️ การล็อกอินที่ล้มเหลวต้องลง audit log ด้วย ไม่ใช่แค่ error log
           เป็นข้อมูลเดียวที่ทำให้แอดมินเห็นการไล่เดารหัสผ่านจากหน้าจอในระบบ
           โดยไม่ต้องเปิดไฟล์ log บนเครื่องจริง

           ‼️ user_id ว่างตรงนี้ถูกต้องแล้ว — ยังไม่รู้ว่าเป็นใคร และห้ามเดา
              บันทึกแค่ชื่อผู้ใช้ที่ถูกลอง ห้ามบันทึกรหัสผ่านที่กรอกมาเด็ดขาด

           ‼️ ไม่บันทึกกรณีที่ถูกล็อกไว้แล้ว เพราะ recordFailure() ถูกเรียกเฉพาะตอนที่ยังไม่ล็อก
              จำนวนแถวต่อช่วงเวลาจึงถูกจำกัดด้วย MAX_ATTEMPTS อยู่แล้ว
              ไม่งั้นคนยิงรัวจะทำให้ตาราง audit_logs บวมได้เอง */
        Model::audit('login_failed', 'users', null, null, [
            'username' => mb_substr($username, 0, 100),
        ]);
    }

    private static function clearFailures(string $username, string $ip): void
    {
        Database::run(
            // ‼️ ลบเฉพาะคู่ username + IP นี้ — ลบตาม IP หรือ username อย่างเดียว
            //    เท่ากับให้ login สำเร็จครั้งเดียวล้างประวัติเดารหัสของบัญชีอื่นได้
            'DELETE FROM login_attempts WHERE username = :username AND ip_address = :ip',
            ['username' => mb_substr($username, 0, 100), 'ip' => $ip]
        );
    }

    /** IP จริงของผู้ใช้ — ใช้ใน log และ audit log (ต้องเป็นค่าเต็ม ไว้สืบเหตุการณ์) */
    public static function ip(): string
    {
        return mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    }

    /**
     * กุญแจสำหรับนับครั้งที่ล็อกอินผิด — ใช้กับ login_attempts เท่านั้น
     *
     * ‼️ IPv6 ยุบเหลือ /64 — ผู้ใช้หนึ่งคนได้ทั้งบล็อก สลับที่อยู่ได้หลายล้านค่า
     *    ถ้านับทีละที่อยู่ การจำกัดต่อ IP ไม่มีผลเลย
     * ‼️ IPv4 ที่มาในรูป ::ffff:a.b.c.d ต้องแปลงกลับเป็น IPv4 ก่อน
     *    ไม่งั้น 8 byte แรกเป็นศูนย์หมด ทุกคนที่ใช้ IPv4 ตกถัง "::/64" เดียวกันแล้วล็อกพร้อมกันทั้งโลก
     */
    public static function rateLimitKey(): string
    {
        $ip = self::ip();
        $packed = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ? @inet_pton($ip) : false;

        if ($packed === false || strlen($packed) !== 16) {
            return $ip;
        }

        if (str_starts_with($packed, str_repeat("\0", 10) . "\xff\xff")) {
            return (string) inet_ntop(substr($packed, 12));
        }

        return mb_substr((string) inet_ntop(substr($packed, 0, 8) . str_repeat("\0", 8)) . '/64', 0, 45);
    }
}
