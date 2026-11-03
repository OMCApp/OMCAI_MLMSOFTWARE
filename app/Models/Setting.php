<?php
/**
 * ค่าตั้งของเว็บ — หนึ่ง key หนึ่งค่า JSON
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class Setting extends Model
{
    protected string $table = 'settings';

    protected array $fillable = ['setting_key', 'value'];

    /** @return array<string, mixed>|null */
    public function findByKey(string $key): ?array
    {
        return Database::selectOne(
            'SELECT * FROM settings WHERE setting_key = :setting_key AND deleted_at IS NULL LIMIT 1',
            ['setting_key' => $key]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function allLive(): array
    {
        return Database::select('SELECT setting_key, value FROM settings WHERE deleted_at IS NULL');
    }
}
