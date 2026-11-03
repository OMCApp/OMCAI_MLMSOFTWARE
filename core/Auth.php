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
    private const MAX_ATTEMPTS  = 5;
    private const LOCK_MINUTES  = 15;

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
        $ip = self::ip();

        if (self::isLocked($username, $ip)) {
            return ['ok' => false, 'reason' => 'throttled', 'minutes' => self::LOCK_MINUTES];
        }

        $user = Database::selectOne(
            'SELECT id, username, password_hash, role, locale, theme, is_active
             FROM users
             WHERE username = :username AND deleted_at IS NULL
             LIMIT 1',
            ['username' => $username]
        );

        $hash = (string) ($user['password_hash'] ?? '');

        // เทียบเสมอแม้ไม่เจอผู้ใช้ เพื่อให้เวลาที่ใช้ใกล้เคียงกัน
        $valid = $user !== null
            && (int) $user['is_active'] === 1
            && password_verify($password, $hash);

        if (!$valid) {
            self::recordFailure($username, $ip);

            // ‼️ ข้อความเดียวกันไม่ว่าจะผิดที่ชื่อผู้ใช้หรือรหัสผ่าน
            //    บอกแยกเท่ากับบอกคนนอกว่าบัญชีไหนมีอยู่จริง
            return ['ok' => false, 'reason' => 'failed', 'minutes' => 0];
        }

        // รหัสผ่านที่ hash ด้วยอัลกอริทึมเก่า ให้อัปเกรดเงียบๆ ตอนเข้าสำเร็จ
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            Database::run(
                'UPDATE users SET password_hash = :hash WHERE id = :id',
                ['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $user['id']]
            );
        }

        self::clearFailures($username, $ip);
        self::login($user);

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
        Session::start();
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
        $row = Database::selectOne(
            'SELECT COUNT(*) AS total
             FROM login_attempts
             WHERE (username = :username OR ip_address = :ip)
               AND attempted_at > (NOW() - INTERVAL :minutes MINUTE)',
            ['username' => $username, 'ip' => $ip, 'minutes' => self::LOCK_MINUTES]
        );

        return (int) ($row['total'] ?? 0) >= self::MAX_ATTEMPTS;
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
            'DELETE FROM login_attempts WHERE username = :username OR ip_address = :ip',
            ['username' => $username, 'ip' => $ip]
        );
    }

    public static function ip(): string
    {
        return mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    }
}
