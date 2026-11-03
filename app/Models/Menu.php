<?php
/**
 * เมนูของเว็บ (header / footer)
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class Menu extends Model
{
    protected string $table = 'menus';

    protected array $fillable = ['location', 'name'];

    public const LOCATIONS = ['header', 'footer'];

    /** @return array<string, mixed>|null */
    public function findByLocation(string $location): ?array
    {
        return Database::selectOne(
            'SELECT * FROM menus WHERE location = :location AND deleted_at IS NULL LIMIT 1',
            ['location' => $location]
        );
    }
}
