<?php
/**
 * บริบทที่ส่งให้การตรวจแต่ละตัว — หาไฟล์ อ่านไฟล์ และบันทึกสิ่งที่เจอ
 */

declare(strict_types=1);

final class Ctx
{
    /** @var array<int, array{level:string,check:string,group:string,title:string,file:string,line:int,message:string,fix:string,snippet:string}> */
    public array $issues = [];

    private string $checkId = '';
    private string $group   = '';
    private string $title   = '';

    /** @var array<string, string> */
    private array $cache = [];

    /** @var array<int, string>|null ไฟล์ที่การตรวจแบบรายไฟล์มองเห็น · null = ทั้งโปรเจค */
    private ?array $limit = null;

    /**
     * @param array<int, string>|null $changed ไฟล์ที่เปลี่ยน (โหมด --changed) · null = ไม่ได้อยู่ในโหมดนั้น
     */
    public function __construct(
        public readonly string $root,
        public readonly ?string $onlyFile = null,
        public readonly ?array $changed = null,
    ) {
    }

    /**
     * จำกัดให้ files() เห็นเฉพาะไฟล์ชุดนี้ — check.php ตั้งให้ก่อนรันการตรวจแต่ละตัว
     *
     * @param array<int, string>|null $files
     */
    public function limitTo(?array $files): void
    {
        $this->limit = $files;
    }

    public function begin(string $id, string $group, string $title): void
    {
        $this->checkId = $id;
        $this->group   = $group;
        $this->title   = $title;
    }

    /**
     * หาไฟล์ตาม glob แบบรองรับ ** เช่น 'app/**\/*.php'
     * ถ้าสั่ง --file มา จะคืนเฉพาะไฟล์นั้นถ้ามันเข้าเงื่อนไข
     *
     * @param  array<int, string> $patterns
     * @return array<int, string> path แบบ relative กับ root
     */
    public function files(array $patterns): array
    {
        $found = [];

        foreach ($patterns as $pattern) {
            foreach ($this->expand($pattern) as $file) {
                $found[$file] = true;
            }
        }

        $files = array_keys($found);
        sort($files);

        if ($this->onlyFile !== null) {
            return in_array($this->onlyFile, $files, true) ? [$this->onlyFile] : [];
        }

        if ($this->limit !== null) {
            return array_values(array_intersect($files, $this->limit));
        }

        return $files;
    }

    /** @return array<int, string> */
    private function expand(string $pattern): array
    {
        $regex = '#^' . str_replace(
            ['\*\*/', '\*\*', '\*', '\?'],
            ['(?:.*/)?', '.*', '[^/]*', '.'],
            preg_quote($pattern, '#')
        ) . '$#';

        // หารากที่ต้องเดิน เพื่อไม่ต้องไล่ทั้งโปรเจค
        $base = explode('*', $pattern)[0];
        $base = rtrim(dirname($base . 'x'), '/');
        $dir  = $this->root . ($base === '.' || $base === '' ? '' : '/' . $base);

        if (!is_dir($dir)) {
            return [];
        }

        $out      = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $relative = str_replace('\\', '/', $item->getPathname());
            $relative = ltrim(substr($relative, strlen(str_replace('\\', '/', $this->root))), '/');

            if ($this->isIgnored($relative)) {
                continue;
            }

            if (preg_match($regex, $relative) === 1) {
                $out[] = $relative;
            }
        }

        return $out;
    }

    private function isIgnored(string $relative): bool
    {
        foreach (['vendor/', 'node_modules/', '.git/', 'storage/', 'old/', 'docs/'] as $prefix) {
            if (str_starts_with($relative, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public function read(string $relative): string
    {
        if (!isset($this->cache[$relative])) {
            $path = $this->root . '/' . $relative;
            $this->cache[$relative] = is_file($path) ? (string) file_get_contents($path) : '';
        }

        return $this->cache[$relative];
    }

    public function exists(string $relative): bool
    {
        return file_exists($this->root . '/' . $relative);
    }

    /** แปลงตำแหน่งตัวอักษรเป็นเลขบรรทัด */
    public static function lineAt(string $content, int $offset): int
    {
        return substr_count($content, "\n", 0, min($offset, strlen($content))) + 1;
    }

    /** ดึงบรรทัดนั้นมาแสดงเป็นตัวอย่าง */
    public static function snippet(string $content, int $line): string
    {
        $lines = preg_split('/\R/', $content) ?: [];
        $text  = trim($lines[$line - 1] ?? '');

        return mb_strlen($text) > 110 ? mb_substr($text, 0, 107) . '...' : $text;
    }

    public function error(string $file, int $line, string $message, string $fix = '', string $snippet = ''): void
    {
        $this->add('error', $file, $line, $message, $fix, $snippet);
    }

    public function warn(string $file, int $line, string $message, string $fix = '', string $snippet = ''): void
    {
        $this->add('warn', $file, $line, $message, $fix, $snippet);
    }

    private function add(string $level, string $file, int $line, string $message, string $fix, string $snippet): void
    {
        $this->issues[] = [
            'level'   => $level,
            'check'   => $this->checkId,
            'group'   => $this->group,
            'title'   => $this->title,
            'file'    => $file,
            'line'    => $line,
            'message' => $message,
            'fix'     => $fix,
            'snippet' => $snippet,
        ];
    }

    /**
     * ช่วยตรวจด้วย regex ทีละไฟล์ แล้วรายงานตำแหน่งให้อัตโนมัติ
     *
     * @param array<int, string> $patterns glob ของไฟล์ที่จะตรวจ
     */
    public function scan(array $patterns, string $regex, string $message, string $fix = '', string $level = 'error'): void
    {
        foreach ($this->files($patterns) as $file) {
            $content = $this->read($file);

            if ($content === '' || preg_match_all($regex, $content, $matches, PREG_OFFSET_CAPTURE) === 0) {
                continue;
            }

            foreach ($matches[0] as $match) {
                $line = self::lineAt($content, (int) $match[1]);
                $this->add($level, $file, $line, $message, $fix, self::snippet($content, $line));
            }
        }
    }
}
