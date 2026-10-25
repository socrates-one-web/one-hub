<?php
declare(strict_types=1);

/**
 * Generador PDF pequeño y autosuficiente para documentos del CRM.
 * Usa fuentes PDF estándar y codificación Windows-1252 para ES/PT/EN.
 */
final class SimplePdf
{
    private const W = 595.276;
    private const H = 841.890;
    private array $pages = [];
    private int $page = -1;
    private array $images = [];
    private array $pageImages = [];
    private array $metadata = [];

    public function __construct(array $metadata = [])
    {
        $this->metadata = $metadata;
        $this->addPage();
    }

    public function addPage(): void
    {
        $this->pages[] = '';
        $this->page = count($this->pages) - 1;
        $this->pageImages[$this->page] = [];
    }

    public function pageCount(): int { return count($this->pages); }

    public function rect(float $x, float $y, float $w, float $h, array $fill, ?array $stroke = null, float $lineWidth = 0.5): void
    {
        $cmd = $this->fillColor($fill);
        if ($stroke !== null) $cmd .= $this->strokeColor($stroke) . $this->num($lineWidth) . " w\n";
        $cmd .= $this->num($x) . ' ' . $this->num(self::H - $y - $h) . ' ' . $this->num($w) . ' ' . $this->num($h) . ' re ' . ($stroke !== null ? 'B' : 'f') . "\n";
        $this->write($cmd);
    }

    public function line(float $x1, float $y1, float $x2, float $y2, array $color = [0,0,0], float $width = 0.5): void
    {
        $this->write($this->strokeColor($color) . $this->num($width) . " w\n" . $this->num($x1) . ' ' . $this->num(self::H - $y1) . ' m ' . $this->num($x2) . ' ' . $this->num(self::H - $y2) . " l S\n");
    }

    public function text(float $x, float $y, string $text, float $size = 10, bool $bold = false, array $color = [0,0,0], string $align = 'left'): void
    {
        $encoded = $this->encode($text);
        if ($encoded === '') return;
        $width = $this->textWidth($text, $size, $bold);
        if ($align === 'right') $x -= $width;
        elseif ($align === 'center') $x -= $width / 2;
        $font = $bold ? 'F2' : 'F1';
        $cmd = "BT\n/{$font} " . $this->num($size) . " Tf\n" . $this->fillColor($color)
            . '1 0 0 1 ' . $this->num($x) . ' ' . $this->num(self::H - $y) . " Tm\n(" . $this->escape($encoded) . ") Tj\nET\n";
        $this->write($cmd);
    }

    public function wrap(string $text, float $maxWidth, float $size = 10, bool $bold = false): array
    {
        $paragraphs = preg_split('/\R/u', trim($text)) ?: [];
        $lines = [];
        foreach ($paragraphs as $pi => $paragraph) {
            $paragraph = trim((string)$paragraph);
            if ($paragraph === '') {
                $lines[] = '';
                continue;
            }
            $words = preg_split('/\s+/u', $paragraph) ?: [];
            $line = '';
            foreach ($words as $word) {
                $candidate = $line === '' ? $word : $line . ' ' . $word;
                if ($line !== '' && $this->textWidth($candidate, $size, $bold) > $maxWidth) {
                    $lines[] = $line;
                    $line = $word;
                } else {
                    $line = $candidate;
                }
            }
            if ($line !== '') $lines[] = $line;
            if ($pi < count($paragraphs) - 1) $lines[] = '';
        }
        return $lines;
    }

    public function multiText(float $x, float $y, string|array $text, float $maxWidth, float $size = 10, float $lineHeight = 14, bool $bold = false, array $color = [0,0,0]): float
    {
        $lines = is_array($text) ? $text : $this->wrap($text, $maxWidth, $size, $bold);
        foreach ($lines as $line) {
            if ((string)$line !== '') $this->text($x, $y, (string)$line, $size, $bold, $color);
            $y += $lineHeight;
        }
        return $y;
    }

    public function jpeg(string $path, float $x, float $y, float $w, float $h): bool
    {
        if (!is_file($path)) return false;
        $info = @getimagesize($path);
        if (!$info || ($info[2] ?? 0) !== IMAGETYPE_JPEG) return false;
        $key = hash_file('sha256', $path) ?: md5($path);
        if (!isset($this->images[$key])) {
            $data = @file_get_contents($path);
            if ($data === false) return false;
            $channels = (int)($info['channels'] ?? 3);
            $this->images[$key] = [
                'name' => 'Im' . (count($this->images) + 1),
                'data' => $data,
                'width' => (int)$info[0],
                'height' => (int)$info[1],
                'colorspace' => $channels === 4 ? '/DeviceCMYK' : '/DeviceRGB',
            ];
        }
        $image = $this->images[$key];
        $this->pageImages[$this->page][$key] = true;
        $cmd = "q\n" . $this->num($w) . ' 0 0 ' . $this->num($h) . ' ' . $this->num($x) . ' ' . $this->num(self::H - $y - $h) . " cm\n/" . $image['name'] . " Do\nQ\n";
        $this->write($cmd);
        return true;
    }

    public function output(): string
    {
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $imageRefs = [];
        $next = 5;
        foreach ($this->images as $key => $image) {
            $imageRefs[$key] = $next;
            $stream = $image['data'];
            $objects[$next++] = '<< /Type /XObject /Subtype /Image /Width ' . $image['width'] . ' /Height ' . $image['height']
                . ' /ColorSpace ' . $image['colorspace'] . ' /BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
        }

        $pageRefs = [];
        foreach ($this->pages as $index => $content) {
            $contentRef = $next++;
            $objects[$contentRef] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "endstream";
            $pageRef = $next++;
            $pageRefs[] = $pageRef;
            $xobjects = '';
            foreach (array_keys($this->pageImages[$index] ?? []) as $key) {
                $image = $this->images[$key];
                $xobjects .= '/' . $image['name'] . ' ' . $imageRefs[$key] . ' 0 R ';
            }
            $resources = '<< /Font << /F1 3 0 R /F2 4 0 R >>' . ($xobjects !== '' ? ' /XObject << ' . $xobjects . '>>' : '') . ' >>';
            $objects[$pageRef] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::W . ' ' . self::H . '] /Resources ' . $resources . ' /Contents ' . $contentRef . ' 0 R >>';
        }
        $kids = implode(' ', array_map(static fn(int $ref): string => $ref . ' 0 R', $pageRefs));
        $objects[2] = '<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($pageRefs) . ' >>';

        if ($this->metadata !== []) {
            $infoRef = $next++;
            $pairs = [];
            foreach (['Title','Author','Subject','Creator'] as $key) {
                if (!empty($this->metadata[$key])) $pairs[] = '/' . $key . ' (' . $this->escape($this->encode((string)$this->metadata[$key])) . ')';
            }
            $objects[$infoRef] = '<< ' . implode(' ', $pairs) . ' >>';
        } else {
            $infoRef = null;
        }

        ksort($objects);
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0 => 0];
        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $max = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($max + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= $max; $i++) {
            $pdf .= isset($offsets[$i]) ? sprintf('%010d 00000 n ', $offsets[$i]) . "\n" : "0000000000 00000 f \n";
        }
        $pdf .= 'trailer << /Size ' . ($max + 1) . ' /Root 1 0 R' . ($infoRef ? ' /Info ' . $infoRef . ' 0 R' : '') . " >>\nstartxref\n" . $xref . "\n%%EOF";
        return $pdf;
    }

    public function textWidth(string $text, float $size, bool $bold = false): float
    {
        $encoded = $this->encode($text);
        $length = strlen($encoded);
        $factor = $bold ? 0.56 : 0.51;
        return $length * $size * $factor;
    }

    private function write(string $command): void { $this->pages[$this->page] .= $command; }
    private function fillColor(array $c): string { return $this->num($c[0]/255) . ' ' . $this->num($c[1]/255) . ' ' . $this->num($c[2]/255) . " rg\n"; }
    private function strokeColor(array $c): string { return $this->num($c[0]/255) . ' ' . $this->num($c[1]/255) . ' ' . $this->num($c[2]/255) . " RG\n"; }
    private function num(float|int $number): string { return rtrim(rtrim(number_format((float)$number, 3, '.', ''), '0'), '.'); }
    private function escape(string $text): string { return str_replace(['\\','(',')',"\r"], ['\\\\','\\(','\\)',''], $text); }
    private function encode(string $text): string
    {
        $text = str_replace(["\u{2018}","\u{2019}","\u{201C}","\u{201D}","\u{2013}","\u{2014}","\u{2026}","\u{2022}"], ["'","'",'"','"','-','-','...','-'], $text);
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
            if ($converted !== false) return $converted;
        }
        return preg_replace('/[^\x20-\x7E\xA0-\xFF]/', '?', $text) ?? $text;
    }
}
