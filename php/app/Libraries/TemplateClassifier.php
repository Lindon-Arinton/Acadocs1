<?php

namespace App\Libraries;

use App\Models\TemplateCategoryModel;
use App\Models\TemplateModel;

/**
 * Auto-classification and auto-tagging for Templates — runs locally, no
 * outside service.
 *
 * - Reads words from the file name, title and the document's own text
 *   (Word, Excel, PowerPoint, PDF, text/CSV).
 * - Suggests a Section with a naive Bayes model learned from the templates
 *   already filed in each section (plus the section's own name), so it gets
 *   better as the ADAS files more templates.
 * - Tags: document-type tags (Certificate, Checklist, Report...) from known
 *   words, plus the most distinctive keywords of the document.
 */
class TemplateClassifier
{
    /** Below this, a suggestion is shown but the ADAS should double-check it. */
    public const CONFIDENT = 0.55;

    private const MAX_TEXT = 60000;

    private const STOPWORDS = [
        // English
        'the', 'and', 'for', 'with', 'from', 'that', 'this', 'are', 'was', 'were', 'been', 'have', 'has', 'not',
        'all', 'any', 'can', 'will', 'shall', 'may', 'its', 'our', 'your', 'their', 'his', 'her', 'who', 'which',
        'what', 'when', 'where', 'how', 'per', 'into', 'upon', 'only', 'also', 'such', 'other', 'than', 'then',
        'there', 'these', 'those', 'each', 'very', 'yes', 'none', 'one', 'two', 'three', 'date', 'name', 'signature',
        'page', 'total', 'office', 'school', 'deped', 'department', 'education', 'republic', 'philippines', 'region',
        'division', 'city', 'province', 'address', 'email', 'telephone', 'contact', 'number', 'over', 'printed',
        // Filipino
        'ang', 'ng', 'mga', 'sa', 'na', 'at', 'ay', 'para', 'nang', 'kay', 'ni', 'ko', 'mo', 'siya', 'ito', 'iyon',
        // File-name noise
        'docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt', 'pdf', 'csv', 'txt', 'copy', 'final', 'blank', 'new', 'old',
        'version', 'rev', 'scan', 'scanned', 'notes', 'document', 'untitled', 'sample', 'draft', 'img', 'image', 'revised', 'updated', 'latest', 'edited', 'edit', 'ver', 'fixed', 'signed', 'submitted', 'okay', 'template', 'templates', 'file', 'annex',
    ];

    /** Word stem → document-type tag. */
    private const TYPE_TAGS = [
        'certificat' => 'Certificate', 'checklist' => 'Checklist', 'report' => 'Report', 'letter' => 'Letter',
        'notice' => 'Notice', 'notification' => 'Notice', 'memorandum' => 'Memo', 'memo' => 'Memo',
        'minutes' => 'Minutes', 'attendance' => 'Attendance', 'plan' => 'Plan', 'survey' => 'Survey',
        'registration' => 'Registration', 'receipt' => 'Receipt', 'inventory' => 'Inventory', 'ledger' => 'Ledger',
        'quotation' => 'Procurement', 'bid' => 'Procurement', 'bidding' => 'Procurement',
        'procurement' => 'Procurement', 'resolution' => 'Resolution', 'travel' => 'Travel', 'itinerary' => 'Travel',
        'research' => 'Research', 'proposal' => 'Proposal', 'enrol' => 'Enrollment', 'enroll' => 'Enrollment',
        'property' => 'Property', 'equipment' => 'Property', 'evaluation' => 'Evaluation', 'rating' => 'Evaluation',
        'performance' => 'Performance', 'indicator' => 'Performance', 'training' => 'Training', 'seminar' => 'Training',
        'leave' => 'Leave', 'payroll' => 'Payroll', 'voucher' => 'Finance', 'disbursement' => 'Finance',
        'liquidation' => 'Finance', 'budget' => 'Finance', 'lesson' => 'Lesson Plan', 'curriculum' => 'Curriculum',
        'transmittal' => 'Transmittal', 'award' => 'Award', 'recognition' => 'Award',
    ];

    /** @var array<int,array{name:string,docs:int,total:float,counts:array<string,float>}>|null */
    private ?array $model = null;

    /** @var array<string,int> how many templates each token appears in */
    private array $docFreq = [];

    private int $docCount = 0;

    /** Template left out of the learned model (to test how well it is classified). */
    private ?int $excludeId = null;

    public function withoutTemplate(?int $id): static
    {
        $this->excludeId = $id;
        $this->model     = null;
        $this->docFreq   = [];
        $this->docCount  = 0;

        return $this;
    }

    // ── Text ────────────────────────────────────────────────────────────

    /**
     * The document's own text (best effort), for the given file on disk.
     */
    /** How the last extractText() got its text: 'text', 'ocr' or 'none'. */
    public string $lastSource = 'none';

    public function extractText(string $path, string $ext): string
    {
        $this->lastSource = 'none';
        if (! is_file($path)) {
            return '';
        }

        try {
            $text = match ($ext) {
                'docx'        => $this->zipXmlText($path, null, '#^word/(document|header\d*|footer\d*|footnotes)\.xml$#'),
                'xlsx'        => $this->zipXmlText($path, ['xl/sharedStrings.xml']),
                'pptx'        => $this->zipXmlText($path, null, '#^ppt/slides/slide\d+\.xml$#'),
                'txt', 'csv'  => (string) file_get_contents($path, false, null, 0, self::MAX_TEXT),
                'xls'         => $this->spreadsheetText($path),
                'doc'         => $this->binaryText($path),
                'pdf'         => $this->pdfText($path),
                default       => '',
            };
            if (count($this->tokenize($text)) >= 10) {
                $this->lastSource = 'text';
            } elseif (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                // Scanned PDF or a photo of a document: read it with OCR.
                $ocr = $this->ocrText($path, $ext);
                if (count($this->tokenize($ocr)) >= 5) {
                    $text             = $ocr;
                    $this->lastSource = 'ocr';
                }
            }
        } catch (\Throwable) {
            $text = '';
        }

        return mb_substr($text, 0, self::MAX_TEXT);
    }

    // ── OCR (optional: needs Tesseract; scanned PDFs also need Poppler) ─

    /** Whether scanned documents / images can be read on this server. */
    public static function ocrAvailable(): bool
    {
        return self::tool('tesseract') !== null;
    }

    /**
     * Path of an external tool: from .env (classifier.tesseract,
     * classifier.pdftotext, classifier.pdftoppm), the usual install folders,
     * or the system PATH. Null when it isn't installed.
     */
    private static function tool(string $name): ?string
    {
        static $cache = [];
        if (array_key_exists($name, $cache)) {
            return $cache[$name];
        }

        $candidates = array_filter([env('classifier.' . $name)]);
        $exe        = DIRECTORY_SEPARATOR === '\\' ? $name . '.exe' : $name;
        if (DIRECTORY_SEPARATOR === '\\') {
            $candidates[] = 'C:\\Program Files\\Tesseract-OCR\\' . $exe;
            $candidates[] = 'C:\\Program Files (x86)\\Tesseract-OCR\\' . $exe;
            foreach (glob('C:\\Program Files\\poppler*\\Library\\bin\\' . $exe) ?: [] as $p) {
                $candidates[] = $p;
            }
            foreach (glob('C:\\poppler*\\Library\\bin\\' . $exe) ?: [] as $p) {
                $candidates[] = $p;
            }
        } else {
            $candidates[] = '/usr/bin/' . $name;
            $candidates[] = '/usr/local/bin/' . $name;
            $candidates[] = '/opt/homebrew/bin/' . $name;
        }
        foreach ($candidates as $c) {
            if (is_file($c)) {
                return $cache[$name] = $c;
            }
        }

        $found = @shell_exec((DIRECTORY_SEPARATOR === '\\' ? 'where ' : 'command -v ') . escapeshellarg($name) . ' 2>' . (DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null'));
        $first = $found ? trim(strtok($found, "\r\n")) : '';

        return $cache[$name] = ($first !== '' && is_file($first)) ? $first : null;
    }

    private function ocrText(string $path, string $ext): string
    {
        $tesseract = self::tool('tesseract');
        if ($tesseract === null) {
            return '';
        }

        $images = [];
        $tmpDir = null;
        if ($ext === 'pdf') {
            $pdftoppm = self::tool('pdftoppm');
            if ($pdftoppm === null) {
                return '';
            }
            // First 3 pages are enough to tell what the document is.
            $tmpDir = WRITEPATH . 'cache/ocr_' . bin2hex(random_bytes(6));
            mkdir($tmpDir, 0755, true);
            exec(escapeshellarg($pdftoppm) . ' -r 150 -gray -f 1 -l 3 -png ' . escapeshellarg($path) . ' ' . escapeshellarg($tmpDir . '/p'));
            $images = glob($tmpDir . '/p*.png') ?: [];
        } else {
            $images = [$path];
        }

        $out = '';
        foreach ($images as $img) {
            $out .= ' ' . (string) shell_exec(escapeshellarg($tesseract) . ' ' . escapeshellarg($img) . ' stdout -l eng --psm 3 2>' . (DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null'));
        }

        if ($tmpDir !== null) {
            array_map('unlink', glob($tmpDir . '/*') ?: []);
            @rmdir($tmpDir);
        }

        return $out;
    }

    /** @param list<string>|null $entries */
    private function zipXmlText(string $path, ?array $entries, ?string $pattern = null): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return '';
        }

        $out = '';
        for ($i = 0; $i < $zip->numFiles && strlen($out) < self::MAX_TEXT; $i++) {
            $name = $zip->getNameIndex($i);
            if (($entries !== null && in_array($name, $entries, true)) || ($pattern !== null && preg_match($pattern, $name))) {
                $xml  = (string) $zip->getFromIndex($i);
                $xml  = preg_replace('#</(w:p|a:p|si|row)>#', "\n", $xml);
                $out .= ' ' . html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
        }
        $zip->close();

        return $out;
    }

    private function spreadsheetText(string $path): string
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $book = $reader->load($path);

        $out = '';
        foreach ($book->getWorksheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator(1, 200) as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    $v = $cell->getValue();
                    if (is_string($v) && $v !== '') {
                        $out .= ' ' . $v;
                    }
                }
                if (strlen($out) > self::MAX_TEXT) {
                    break 2;
                }
            }
        }

        return $out;
    }

    /** Old binary Word files: pull readable runs (ASCII and UTF-16LE). */
    private function binaryText(string $path): string
    {
        $bin = (string) file_get_contents($path, false, null, 0, 1024 * 1024);
        preg_match_all('/(?:[\x20-\x7E]\x00){4,}/', $bin, $utf16);
        preg_match_all('/[\x20-\x7E]{5,}/', $bin, $ascii);

        $out = implode(' ', array_map(static fn ($s) => str_replace("\x00", '', $s), $utf16[0]));

        return $out . ' ' . implode(' ', $ascii[0]);
    }

    /**
     * PDFs: Poppler's pdftotext when installed (most accurate), otherwise the
     * text-showing operators of the (possibly Flate-compressed) content streams.
     */
    private function pdfText(string $path): string
    {
        if ($pdftotext = self::tool('pdftotext')) {
            $out = (string) shell_exec(escapeshellarg($pdftotext) . ' -l 10 -enc UTF-8 ' . escapeshellarg($path) . ' - 2>' . (DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null'));
            if (trim($out) !== '') {
                return $out;
            }
        }

        $pdf = (string) file_get_contents($path, false, null, 0, 5 * 1024 * 1024);
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams);

        $out = '';
        foreach ($streams[1] as $stream) {
            $data = @gzuncompress($stream);
            if ($data === false) {
                $data = $stream;
            }
            if (! str_contains($data, 'Tj') && ! str_contains($data, 'TJ')) {
                continue;
            }
            // [(Hel) -20 (lo)] TJ is one word split for kerning; separate Tj's are separate runs.
            preg_match_all('/\[(.*?)\]\s*TJ|\((.*?)(?<!\\\\)\)\s*Tj|<([0-9A-Fa-f\s]+)>\s*Tj/s', $data, $ops, PREG_SET_ORDER);
            foreach ($ops as $op) {
                if (($op[1] ?? '') !== '') {
                    preg_match_all('/\((.*?)(?<!\\\\)\)/s', $op[1], $parts);
                    $out .= implode('', $parts[1]) . "\n";
                } elseif (($op[2] ?? '') !== '') {
                    $out .= $op[2] . "\n";
                } elseif (($op[3] ?? '') !== '') {
                    $out .= (string) @hex2bin(preg_replace('/\s+/', '', $op[3])) . "\n";
                }
            }
            $out .= "\n";
            if (strlen($out) > self::MAX_TEXT) {
                break;
            }
        }

        return stripcslashes($out);
    }

    /**
     * Lower-case word tokens, without stopwords and numbers. Codes like
     * "BAC1-F-038" also yield their letter part ("bac").
     *
     * @return list<string>
     */
    public function tokenize(string $text): array
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9ñ]+/u', ' ', $text);

        $out = [];
        foreach (explode(' ', $text) as $tok) {
            if ($tok === '' || ctype_digit($tok)) {
                continue;
            }
            if (preg_match('/^([a-z]+)\d+[a-z]*$/', $tok, $m)) {
                $tok = $m[1];
            }
            if (strlen($tok) < 3 && ! in_array($tok, ['hr', 'id', 'ld'], true)) {
                continue;
            }
            if (strlen($tok) > 30 || in_array($tok, self::STOPWORDS, true)) {
                continue;
            }
            $out[] = $tok;
        }

        return $out;
    }

    // ── Title suggestion ────────────────────────────────────────────────

    /** Letterhead / boilerplate lines that are never the document's title. */
    private const NOT_TITLE = '/republic of the philippines|department of education|^deped\b|^region\b|division of|schools? division|division office|national high school|integrated school|elementary school|senior high school|high school$|^school (id|name|head|address|year|principal)\b|^office of|address|telephone|tel\.? no|email|e-mail|website|www\.|http|^page \d|^annex\b|^appendix\b|^\(?sgd\.?\)?|^date\b|^name\b|^to:|^from:|^subject:|^re:|^thru:|^memorandum no|^no\.\s*\d|^series of|^s\. \d{4}/i';

    /** Words that make a line look like a document title. */
    private const TITLE_WORDS = '/\b(form|certificate|certification|report|checklist|request|notice|plan|receipt|application|minutes|attendance|slip|card|ledger|inventory|registry|survey|proposal|evaluation|resolution|order|memorandum|letter|record|summary|abstract|quotation|program|agreement|contract|template|tool|matrix|log|list|profile|clearance|authority|itinerary|accomplishment|acknowledgment|acknowledgement)\b/i';

    /**
     * Title suggestions from the document's own text, best first (at most 3),
     * for files whose name says nothing ("final", "scan001", "Document1").
     * Looks at the first lines, skips the DepEd/school letterhead, and prefers
     * short, capitalised lines with document-type words ("CERTIFICATE OF
     * RECOGNITION", "Request for Quotation"). A memo's SUBJECT line comes
     * first, and when the text names a school/calendar year the best title is
     * also offered with it ("… SY 2026-2027"), which tells versions apart.
     *
     * @return list<string>
     */
    public function suggestTitles(string $text, ?array $layout = null): array
    {
        // $layout: body paragraphs with a formatting bonus (see layoutLines());
        // without it, plain text lines with no bonus.
        $lines = [];
        $bonus = [];
        $isSentence = [];
        $source = $layout ?? array_map(static fn ($l) => ['text' => $l, 'boost' => 0.0], preg_split('/\r\n|\r|\n/', $text));
        foreach ($source as $row) {
            $line = trim(preg_replace('/[\s_]+/u', ' ', $row['text']), " \t.:;,-–—|*•");
            if ($line !== '') {
                $lines[] = $line;
                $bonus[] = (float) $row['boost'];
                // Body sentences ("This plan tracks the progress of ...") are not titles.
                $isSentence[] = (bool) preg_match('/[.!?]\s*$/', trim($row['text'])) && str_word_count($line) >= 6
                    || (str_word_count($line) >= 9 && $line !== mb_strtoupper($line) && (float) $row['boost'] < 1);
            }
            if (count($lines) >= 60) {
                break;
            }
        }

        $scored = [];
        foreach ($lines as $line) {
            if (preg_match('/^(subject|re)\s*:\s*(.{6,110})$/i', $line, $m)) {
                $scored[] = [trim($m[2], " \t.:;,"), 100.0];
                break;
            }
        }

        foreach ($lines as $i => $line) {
            $letters = preg_match_all('/\p{L}/u', $line);
            $words   = str_word_count(preg_replace('/[^\p{L}\s\'-]/u', ' ', $line));
            if ($letters < 6 || $words < 1 || $words > 14 || mb_strlen($line) > 110) {
                continue;
            }
            if ($letters / max(1, mb_strlen($line)) < 0.6 || $isSentence[$i] || self::isLetterhead($line)) {
                continue; // mostly numbers/symbols (OCR noise, dates), or letterhead/address/school name
            }

            $titleWords = preg_match(self::TITLE_WORDS, $line) === 1;
            $score      = 1.0 + $bonus[$i];
            if ($titleWords) {
                $score += 3;
            }
            if ($line === mb_strtoupper($line)) {
                $score += 1.0; // headings are usually in capitals
            }
            if ($words >= 2 && $words <= 8) {
                $score += 1;
            }
            $score -= $i * 0.05; // earlier is likelier
            // A big bold line with no document word could still be a name or a motto.
            if (! $titleWords && $bonus[$i] < 3) {
                $score -= 1;
            }
            if ($score < 2) {
                continue;
            }

            // Titles split over two lines: "CERTIFICATE OF" / "RECOGNITION".
            $title = $line;
            $next  = $lines[$i + 1] ?? '';
            if ($next !== '' && mb_strlen($title . ' ' . $next) <= 110 && ! self::isLetterhead($next)
                && abs(($bonus[$i + 1] ?? 0) - $bonus[$i]) < 1.5   // same heading style
                && (preg_match('/\b(of|for|and|on|to|the|in|ng|sa)$/i', $title)
                    || ($title === mb_strtoupper($title) && $next === mb_strtoupper($next) && str_word_count($next) <= 5 && preg_match('/\p{L}{3}/u', $next)))) {
                $title .= ' ' . $next;
            }
            $scored[] = [$title, $score];
        }

        usort($scored, static fn ($x, $y) => $y[1] <=> $x[1]);

        $out = [];
        foreach ($scored as [$title]) {
            $title = self::prettyTitle($title);
            $key   = mb_strtolower($title);
            // Skip duplicates and lines already contained in a better title.
            foreach ($out as $have) {
                if (str_contains(mb_strtolower($have), $key)) {
                    continue 2;
                }
            }
            $out[] = $title;
            if (count($out) >= 3) {
                break;
            }
        }

        return $out;
    }

    /** Philippine provinces and big cities: a line made of these is an address. */
    private const PLACES = 'abra|agusan|aklan|albay|antique|apayao|aurora|basilan|bataan|batanes|batangas|benguet|biliran|bohol|bukidnon|bulacan|cagayan|camarines|camiguin|capiz|catanduanes|cavite|cebu|cotabato|davao|dinagat|guimaras|ifugao|ilocos|iloilo|isabela|kalinga|laguna|lanao|leyte|maguindanao|marinduque|masbate|mindoro|misamis|mountain province|negros|palawan|pampanga|pangasinan|quezon|quirino|rizal|romblon|samar|sarangani|siquijor|sorsogon|sultan kudarat|sulu|surigao|tarlac|tawi-tawi|zambales|zamboanga|manila|makati|pasig|taguig|caloocan|lipa|tanauan|calamba|lucena|antipolo|bacoor|dasmariñas|imus|baguio|calabarzon|mimaropa|luzon|visayas|mindanao|philippines';

    /**
     * Letterhead lines that are never a document's title: DepEd/region/
     * division lines, school names, addresses ("Matabungkay, Lian,
     * Batangas", "Brgy. Poblacion, Lian"), contact details, IDs, mottos.
     */
    private static function isLetterhead(string $line): bool
    {
        if (preg_match(self::NOT_TITLE, $line)) {
            return true;
        }
        $hasTitleWord = preg_match(self::TITLE_WORDS, $line) === 1;

        // Contact details and IDs.
        if (preg_match('/(\+?63|\(0\d+\)|\b0\d{2,3}[-\s]?\d{3}[-\s]?\d{4}\b|@|\bschool\s*id\b|\bid\s*(no\.?|number)\s*:?\s*\d|\bzip\s*code\b|\b(?!19|20)\d{4}\s*$)/i', $line) && ! $hasTitleWord) {
            return true;
        }
        // School / office names: "Manahis National High School", "Lian Central School", "SDO Batangas".
        if (! $hasTitleWord && preg_match('/\b(school|academy|college|university|institute|campus|learning cent(er|re)|annex|integrated|central|division|district|municipality of|city of|province of|regional office|sdo|deped)\b/i', $line)) {
            return true;
        }
        // Addresses: barangay / street words, or a line that is only place names
        // ("Matabungkay, Lian, Batangas", "Lian, Batangas 4216").
        if (! $hasTitleWord && preg_match('/\b(barangay|brgy\.?|bgy\.?|poblacion|purok|sitio|street|st\.|avenue|ave\.|road|rd\.|highway|hi-way|blk|block|lot|subd\.?|subdivision|village)\b/i', $line)) {
            return true;
        }
        if (! $hasTitleWord && preg_match('/\b(' . self::PLACES . ')\b/i', $line)) {
            // Few words, place name in it, no document word: an address line.
            if (str_word_count($line) <= 6 || substr_count($line, ',') >= 1) {
                return true;
            }
        }
        // "Word, Word, Word" with no document word — a list of places.
        if (! $hasTitleWord && preg_match('/^[\p{L}.\'\s-]+(,\s*[\p{L}.\'\s-]+){2,4}$/u', $line)) {
            return true;
        }

        return false;
    }

    /**
     * Body paragraphs in reading order with a formatting bonus, for title
     * picking: Title/Heading styles, a font clearly bigger than the body
     * text, bold, centred. The page header (where letterheads live) is left
     * out. Null when the file type has no layout we can read.
     *
     * @return list<array{text:string,boost:float}>|null
     */
    public function layoutLines(string $path, string $ext): ?array
    {
        if (! is_file($path)) {
            return null;
        }
        try {
            return match ($ext) {
                'docx'        => $this->docxLayout($path),
                'pptx'        => $this->pptxLayout($path),
                'xlsx', 'xls' => $this->sheetLayout($path),
                default       => null,
            };
        } catch (\Throwable) {
            return null;
        }
    }

    private function docxLayout(string $path): ?array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return null;
        }
        $docXml    = (string) $zip->getFromName('word/document.xml');
        $stylesXml = (string) $zip->getFromName('word/styles.xml');
        $zip->close();
        if ($docXml === '') {
            return null;
        }

        $ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

        // Styles: id → [name, size (half-points), bold], plus the default size.
        $styles      = [];
        $defaultSize = 22;
        if ($stylesXml !== '') {
            $sd = new \DOMDocument();
            @$sd->loadXML($stylesXml, LIBXML_NONET);
            $sx = new \DOMXPath($sd);
            $sx->registerNamespace('w', $ns);
            $sz = $sx->query('//w:docDefaults//w:rPr/w:sz/@w:val')->item(0);
            if ($sz) {
                $defaultSize = (int) $sz->nodeValue;
            }
            foreach ($sx->query('//w:style') as $st) {
                $id   = $st->getAttributeNS($ns, 'styleId');
                $name = strtolower((string) ($sx->query('w:name/@w:val', $st)->item(0)?->nodeValue ?? $id));
                $size = $sx->query('w:rPr/w:sz/@w:val', $st)->item(0)?->nodeValue;
                $bold = $sx->query('w:rPr/w:b', $st)->length > 0;
                $styles[$id] = ['name' => $name, 'size' => $size ? (int) $size : null, 'bold' => $bold];
            }
        }

        $dom = new \DOMDocument();
        @$dom->loadXML($docXml, LIBXML_NONET | LIBXML_COMPACT);
        $xp = new \DOMXPath($dom);
        $xp->registerNamespace('w', $ns);

        $paras = [];
        foreach ($xp->query('//w:body//w:p') as $p) {
            $styleId = $xp->query('w:pPr/w:pStyle/@w:val', $p)->item(0)?->nodeValue;
            $style   = $styleId !== null ? ($styles[$styleId] ?? ['name' => strtolower($styleId), 'size' => null, 'bold' => false]) : ['name' => '', 'size' => null, 'bold' => false];
            $center  = in_array($xp->query('w:pPr/w:jc/@w:val', $p)->item(0)?->nodeValue, ['center', 'both'], true);
            $inTable = $xp->query('ancestor::w:tbl', $p)->length > 0;

            $text = '';
            $maxSize = 0;
            $boldChars = 0;
            foreach ($xp->query('.//w:r', $p) as $r) {
                $t = '';
                foreach ($xp->query('w:t', $r) as $tn) {
                    $t .= $tn->nodeValue;
                }
                if ($t === '') {
                    continue;
                }
                $text .= $t;
                $sz    = $xp->query('w:rPr/w:sz/@w:val', $r)->item(0)?->nodeValue;
                $size  = $sz ? (int) $sz : ($style['size'] ?? $defaultSize);
                $maxSize = max($maxSize, $size);
                $b     = $xp->query('w:rPr/w:b', $r)->item(0);
                $isBold = $b ? ! in_array($b->getAttributeNS($ns, 'val'), ['0', 'false'], true) : $style['bold'];
                if ($isBold) {
                    $boldChars += mb_strlen($t);
                }
            }
            if (trim($text) === '') {
                continue;
            }
            $paras[] = [
                'text'    => $text,
                'size'    => $maxSize ?: $defaultSize,
                'bold'    => $boldChars >= 0.6 * mb_strlen($text),
                'style'   => $style['name'],
                'center'  => $center,
                'inTable' => $inTable,
            ];
            if (count($paras) >= 120) {
                break;
            }
        }
        if ($paras === []) {
            return null;
        }

        $sizes = array_column($paras, 'size');
        sort($sizes);
        $body = $sizes[intdiv(count($sizes), 2)]; // median = body text size

        return array_map(static function ($p) use ($body) {
            $boost = 0.0;
            if ($p['style'] === 'title') {
                $boost += 4;
            } elseif (preg_match('/^heading ?1$|^heading$/', $p['style'])) {
                $boost += 3;
            } elseif (preg_match('/^heading ?[2-3]$|subtitle/', $p['style'])) {
                $boost += 1.5;
            }
            $ratio = $p['size'] / max(1, $body);
            $boost += $ratio >= 1.5 ? 2.5 : ($ratio >= 1.2 ? 1.5 : ($ratio < 0.95 ? -1 : 0));
            $boost += $p['bold'] ? 1 : 0;
            $boost += $p['center'] ? 0.5 : 0;
            $boost -= $p['inTable'] ? 0.5 : 0;

            return ['text' => $p['text'], 'boost' => $boost];
        }, $paras);
    }

    /** PowerPoint: slide title placeholders of the first slides rank highest. */
    private function pptxLayout(string $path): ?array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return null;
        }
        $out = [];
        for ($n = 1; $n <= 3; $n++) {
            $xml = $zip->getFromName("ppt/slides/slide{$n}.xml");
            if ($xml === false) {
                break;
            }
            $dom = new \DOMDocument();
            @$dom->loadXML($xml, LIBXML_NONET);
            $xp = new \DOMXPath($dom);
            $xp->registerNamespace('p', 'http://schemas.openxmlformats.org/presentationml/2006/main');
            $xp->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
            foreach ($xp->query('//p:sp') as $sp) {
                $type  = $xp->query('.//p:nvPr/p:ph/@type', $sp)->item(0)?->nodeValue;
                $text  = trim(implode(' ', array_map(static fn ($t) => $t->nodeValue, iterator_to_array($xp->query('.//a:t', $sp)))));
                if ($text !== '') {
                    $out[] = ['text' => $text, 'boost' => in_array($type, ['title', 'ctrTitle'], true) ? ($n === 1 ? 4.0 : 2.5) : ($type === 'subTitle' ? 1.0 : 0.0)];
                }
            }
        }
        $zip->close();

        return $out ?: null;
    }

    /** Spreadsheets: the first rows of the first sheet, bigger/bold cells rank higher. */
    private function sheetLayout(string $path): ?array
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
        $book   = $reader->load($path);
        $sheet  = $book->getSheet(0);

        $rows = [];
        foreach ($sheet->getRowIterator(1, 25) as $row) {
            $text = [];
            $size = 0.0;
            $bold = false;
            foreach ($row->getCellIterator() as $cell) {
                $v = $cell->getValue();
                if (is_string($v) && trim($v) !== '' || $v instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                    $text[] = trim((string) $v);
                    $font   = $cell->getStyle()->getFont();
                    $size   = max($size, (float) $font->getSize());
                    $bold   = $bold || $font->getBold();
                }
            }
            if ($text !== []) {
                $rows[] = ['text' => implode(' ', $text), 'size' => $size ?: 11.0, 'bold' => $bold];
            }
        }
        if ($rows === []) {
            return null;
        }
        $sizes = array_column($rows, 'size');
        sort($sizes);
        $body = $sizes[intdiv(count($sizes), 2)];

        return array_map(static fn ($r) => [
            'text'  => $r['text'],
            'boost' => ($r['size'] / max(1, $body) >= 1.3 ? 2.0 : 0.0) + ($r['bold'] ? 1.0 : 0.0),
        ], $rows);
    }

    /** Best title suggestion from the document's text, or null. */
    public function suggestTitle(string $text): ?string
    {
        return $this->suggestTitles($text)[0] ?? null;
    }

    /** Upper-case headings read better in Title Case; acronyms stay capitals. */
    private static function prettyTitle(string $title): string
    {
        $title = trim($title, " \t.:;,-–—");
        if ($title === mb_strtoupper($title)) {
            $title = mb_convert_case(mb_strtolower($title), MB_CASE_TITLE, 'UTF-8');
            $title = preg_replace_callback('/(?<!^)\b(Of|For|And|On|To|The|In|A|An|Or|Ng|Sa|At)\b/u', static fn ($m) => mb_strtolower($m[1]), $title);
            $title = preg_replace_callback('/\b(Bac|Coa|Hrd|Ppe|Cid|Sdo|Sgod|Deped|Ld|Ctc|Cav|Ipcrf|Opcrf|Sf\d*|Lrn|Kpi|Sy)\b/u', static fn ($m) => mb_strtoupper($m[1]), $title);
        }

        return mb_substr($title, 0, 120);
    }

    /**
     * File names that say nothing about the document: phone/scanner names
     * ("IMG_2031", "CamScanner 10-21-2026", "scan001"), "Document1", or only
     * numbers/dates.
     */
    public static function isGenericName(string $fileName): bool
    {
        $base = strtolower(trim(pathinfo($fileName, PATHINFO_FILENAME)));
        // A scanner/phone/default word followed only by numbers, dates or copies.
        $junk = '(scan|scanned|img|image|dsc|dcim|photo|pic|picture|document|doc|untitled|new|camscanner|adobe scan|genius scan|file|pdf|whatsapp image|screenshot|screen shot|ocr|print|printout|attachment|new document|new doc|microsoft word)';
        if (preg_match('/^' . $junk . '(?:[\s_\-.()]*(?:\d+|copy|final|pm|am|at))*[\s_\-.()]*$/i', $base)) {
            return true;
        }

        // Only filler words left ("final", "revised copy", "v2 updated", "new").
        return (new self())->tokenize($base) === [];
    }

    // ── Analysis ────────────────────────────────────────────────────────

    /**
     * Section suggestion and tags for one file.
     *
     * @return array{
     *   category_id:int|null, category_name:string|null, confidence:float, confident:bool,
     *   alternatives:list<array{category_id:int,category_name:string,confidence:float}>,
     *   tags:list<string>, keywords:string, suggested_titles:list<string>, suggested_title:string|null, generic_name:bool
     * }
     */
    public function analyze(string $fileName, string $title, string $path, string $ext): array
    {
        $nameTokens    = $this->tokenize(pathinfo($fileName, PATHINFO_FILENAME) . ' ' . $title);
        $rawText       = $this->extractText($path, $ext);
        $contentTokens = $this->tokenize($rawText);
        $source        = $this->lastSource;
        $keywords      = $this->topKeywords($nameTokens, $contentTokens, 40);

        // Content first: when the document has enough readable text, its own
        // words decide and the file name is only a tie-breaker. With little or
        // no text (blank form, scan without OCR) the file name carries it.
        $hasContent = count($contentTokens) >= 25;
        $nameWeight = $hasContent ? 0.5 : 2.0;

        $tf = array_count_values($contentTokens);
        arsort($tf);
        $features = [];
        foreach (array_slice($tf, 0, 80, true) as $t => $n) {
            $features[$t] = 1 + log($n);
        }
        foreach ($nameTokens as $t) {
            $features[$t] = ($features[$t] ?? 0) + $nameWeight;
        }

        [$ranked, $matched] = $this->rank($features);
        $best = $ranked[0] ?? null;

        return [
            'category_id'   => $best ? $best['category_id'] : null,
            'category_name' => $best ? $best['category_name'] : null,
            'confidence'    => $best ? $best['confidence'] : 0.0,
            'confident'     => $best !== null && $best['confidence'] >= self::CONFIDENT,
            'alternatives'  => array_slice($ranked, 1, 2),
            'basis'         => $hasContent ? 'content' : (count($contentTokens) > 0 ? 'name+content' : 'name'),
            'source'        => $source,                 // text | ocr | none
            'words_read'    => count($contentTokens),
            'matched'       => $matched,                // words that pointed to the suggested section
            'tags'          => $this->tags($nameTokens, $contentTokens, $keywords),
            'keywords'      => implode(' ', $keywords),
        ] + $this->titleAdvice($fileName, $rawText, $this->layoutLines($path, $ext));
    }

    /**
     * Title suggestions with the period they cover, and the title to fill in.
     *
     * - The period (SY 2026-2027, FY 2026, Q1 2026, October 2026, 2026) comes
     *   from the document, else from the old file name ("final 2026.docx").
     * - Documents that are tied to a period (reports, plans, enrollment,
     *   inventories, memos...) or that name a quarter/month need it in the
     *   title: the dated version comes first, and a meaningful file name
     *   without a year gets the period added.
     *
     * @return array{suggested_titles:list<string>, suggested_title:?string, generic_name:bool,
     *               period:?string, period_needed:bool, recommended_title:?string, recommended_reason:?string}
     */
    private function titleAdvice(string $fileName, string $rawText, ?array $layout = null): array
    {
        $titles   = $this->suggestTitles($rawText, $layout);
        $generic  = self::isGenericName($fileName);
        $baseName = trim(pathinfo($fileName, PATHINFO_FILENAME));
        $period   = $this->detectPeriod($rawText) ?? $this->detectPeriod($baseName, true);

        $subject = ($titles[0] ?? '') . ' ' . ($generic ? '' : $baseName) . ' ' . mb_substr($rawText, 0, 1500);
        $needed  = $period !== null && (
            preg_match(self::PERIODIC_WORDS, $subject) === 1
            || preg_match('/^(Q[1-4]|[A-Z][a-z]+ 20\d{2}|1st|2nd)/', $period['label']) === 1   // a quarter or month is always specific
        );

        if ($period !== null) {
            $dated = [];
            foreach ($titles as $t) {
                $dated[] = self::hasPeriod($t) ? $t : mb_substr($t . ' ' . $period['label'], 0, 120);
            }
            // Needed: dated titles first. Otherwise: plain title first, dated one offered next.
            $merged = $needed ? array_merge($dated, $titles) : array_merge([$titles[0] ?? null, $dated[0] ?? null], $titles, $dated);
            $titles = array_slice(array_values(array_unique(array_filter($merged))), 0, 4);
        }

        $recommended = null;
        $reason      = null;
        if ($generic && $titles !== []) {
            $recommended = $titles[0];
            $reason      = 'generic';
        } elseif (! $generic && $needed && ! self::hasPeriod($baseName) && $baseName !== '') {
            $recommended = mb_substr($baseName . ' ' . $period['label'], 0, 120);
            $reason      = 'period';
        }

        return [
            'suggested_titles'   => $titles,
            'suggested_title'    => $titles[0] ?? null,
            'generic_name'       => $generic,
            'period'             => $period['label'] ?? null,
            'period_source'      => $period['source'] ?? null,
            'period_needed'      => $needed,
            'recommended_title'  => $recommended,
            'recommended_reason' => $reason,
        ];
    }

    /** Document kinds that are about a specific school year / period. */
    private const PERIODIC_WORDS = '/\b(report|reports|accomplishment|plan|planning|enrol+ment|enrol+ees|inventory|attendance|survey|budget|monitoring|kpi|key performance|performance|ipcrf|opcrf|summary|schedule|calendar|program|programme|memorandum|memo|minutes|payroll|liquidation|evaluation|assessment|statistics|profile|census|masterlist|master list|class record|grades|grading|form 137|sf\s?\d|school form|work plan|wfp|aip|sip|app|ppmp|annual|quarterly|monthly|semestral|yearly|brigada|graduation|recognition rites|moving up|opening of classes|card day|exam|examination|results|roster|list of|directory|consolidated|tally)\b/i';

    private const MONTHS = 'january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|jun|jul|aug|sept|sep|oct|nov|dec';

    /** Whether a title already says which year/period it is for. */
    private static function hasPeriod(string $title): bool
    {
        return preg_match('/\b(19|20)\d{2}\b|\bS\.?Y\.?\s?\d{2}|\bQ[1-4]\b|\b(1st|2nd|3rd|4th|first|second|third|fourth)\s+quarter\b/i', $title) === 1;
    }

    /**
     * The period a document is for, normalised: "SY 2026-2027", "FY 2026",
     * "CY 2026", "Q1 2026", "1st Semester SY 2026-2027", "October 2026" or
     * "2026". Labelled periods win over bare years; years that only appear in
     * references ("DepEd Order No. 8, s. 2015", "RA 10533", "as amended in
     * 2013") and blanks ("S.Y. 20__-20__") are ignored.
     *
     * @return array{label:string,source:string}|null
     */
    private function detectPeriod(string $text, bool $fromName = false): ?array
    {
        if (trim($text) === '') {
            return null;
        }
        $maxYear = (int) date('Y') + 2;
        $okYear  = static fn ($y) => (int) $y >= 2000 && (int) $y <= $maxYear;
        $full    = static fn ($a, $b) => strlen($b) === 2 ? substr($a, 0, 2) . $b : $b; // "2026-27" → 2027
        $src     = $fromName ? 'file name' : 'document';

        $ownSeries = null;
        foreach (array_slice(preg_split('/\r\n|\r|\n/', $text), 0, 8) as $line) {
            if (preg_match('/^\s*(?:deped\s+|division\s+|regional\s+|school\s+)?(?:memorandum|memo|order|advisory|bulletin)\b[^\n]{0,30}?\b(?:s\.?|series\s+of)\s*(20\d{2})\b/i', $line, $mm)) {
                $ownSeries = $mm[1];
                break;
            }
        }

        // Ignore references to other issuances: "DO No. 8, s. 2015", "DepEd Order 21 s. 2019", "RA 10533".
        $clean = preg_replace('/\b(?:deped\s+)?(?:order|o\.|d\.?o\.?|memorandum|memo|d\.?m\.?|r\.?m\.?|division memorandum|regional memorandum|republic act|r\.?a\.?|executive order|e\.?o\.?|resolution|circular)\s*(?:no\.?|number)?\s*[\w\-]*\s*,?\s*(?:s\.?|series of)\s*(?:19|20)\d{2}/i', ' ', $text);
        // Only the start of a long document says what it is for.
        $head  = mb_substr($clean, 0, 4000);

        // School / academic year: "S.Y. 2026-2027", "SY: 2026 – 2027", "School Year 2026-27", "A.Y. 2026 to 2027", "SY2026-2027".
        $sy = null;
        if (preg_match('/\b(?:s\.?\s?y\.?|school\s+year|a\.?\s?y\.?|academic\s+year)\s*[:\-]?\s*(20\d{2})\s*(?:[-–—\/]|to)\s*((?:20)?\d{2})\b/iu', $head, $m)
            && $okYear($m[1]) && (int) $full($m[1], $m[2]) === (int) $m[1] + 1) {
            $sy = 'SY ' . $m[1] . '-' . $full($m[1], $m[2]);
        } elseif (preg_match('/\b(20\d{2})\s*[-–—]\s*(20\d{2})\b/u', $head, $m) && $okYear($m[1]) && (int) $m[2] === (int) $m[1] + 1) {
            $sy = 'SY ' . $m[1] . '-' . $m[2]; // bare consecutive years are a school year
        }

        // Semester / quarter, e.g. "1st Semester", "Third Quarter", "Q2".
        $ordinal = ['first' => '1st', 'second' => '2nd', 'third' => '3rd', 'fourth' => '4th', '1st' => '1st', '2nd' => '2nd', '3rd' => '3rd', '4th' => '4th'];
        $sem = preg_match('/\b(1st|2nd|first|second)\s+semester\b/i', $head, $m) ? $ordinal[strtolower($m[1])] . ' Semester' : null;
        $q   = null;
        if (preg_match('/\b(1st|2nd|3rd|4th|first|second|third|fourth)\s+(?:grading\s+)?quarter\b/i', $head, $m)) {
            $q = 'Q' . $ordinal[strtolower($m[1])][0];
        } elseif (preg_match('/\b(?:quarter\s*|q)([1-4])\b/i', $head, $m)) {
            $q = 'Q' . $m[1];
        }

        if ($sy !== null) {
            $label = trim(($sem ? $sem . ' ' : '') . ($q && ! $sem ? $q . ' ' : '') . $sy);

            return ['label' => $label, 'source' => $src];
        }

        // Fiscal / calendar year: "FY 2026", "CY 2026", "Fiscal Year 2026".
        if (preg_match('/\b(f\.?\s?y\.?|fiscal\s+year|c\.?\s?y\.?|calendar\s+year)\s*[:\-]?\s*(20\d{2})\b/i', $head, $m) && $okYear($m[2])) {
            $kind = stripos($m[1], 'f') === 0 ? 'FY' : 'CY';

            return ['label' => ($q ? $q . ' ' : '') . $kind . ' ' . $m[2], 'source' => $src];
        }

        // Month: "for the month of October 2026", "as of October 31, 2026", "Period covered: October 2026",
        // or a month-year in the first lines (headings), not a signature date at the bottom.
        $firstLines = implode("\n", array_slice(preg_split('/\r\n|\r|\n/', $head), 0, 15));
        if (preg_match('/\b(?:month\s+of|as\s+of|period\s+covered|covering|for)\s*:?\s*(' . self::MONTHS . ')\.?\s+(?:\d{1,2}\s*[-–,]?\s*(?:\d{1,2}\s*,?\s*)?)?(20\d{2})\b/iu', $head, $m)
            || preg_match('/^\s*(' . self::MONTHS . ')\.?\s+(20\d{2})\s*$/im', $firstLines, $m)) {
            if ($okYear($m[2])) {
                $month = ucfirst(strtolower(strlen($m[1]) <= 4 ? date('F', strtotime($m[1] . ' 1')) : $m[1]));

                return ['label' => ($q ? $q . ' ' . $m[2] : $month . ' ' . $m[2]), 'source' => $src];
            }
        }

        // Quarter + any year near it: "1st Quarter 2026", "Q2 CY 2026".
        if ($q !== null && preg_match('/\b(?:quarter|q[1-4])\b[^\n]{0,25}?\b(20\d{2})\b/i', $head, $m) && $okYear($m[1])) {
            return ['label' => $q . ' ' . $m[1], 'source' => $src];
        }

        // A plain year: labelled ("for the year 2026", "Year 2026", "s. 2026" in the document's own
        // memo header), or in the first lines / file name.
        if (preg_match('/\b(?:for\s+the\s+year|year|annual)\s*:?\s*(20\d{2})\b/i', mb_substr($clean, 0, 800), $m) && $okYear($m[1])) {
            return ['label' => $m[1], 'source' => $src];
        }
        if ($ownSeries !== null && $okYear($ownSeries)) {
            return ['label' => $ownSeries, 'source' => $src];
        }
        if (preg_match('/\b(20\d{2})\b/', $fromName ? $text : $firstLines, $m) && $okYear($m[1])) {
            return ['label' => $m[1], 'source' => $src];
        }

        return null;
    }

    /**
     * Sections ranked by probability for the weighted word features, plus the
     * words that most favoured the winner over the runner-up.
     *
     * @param array<string,float|int> $features
     * @return array{0:list<array{category_id:int,category_name:string,confidence:float}>,1:list<string>}
     */
    private function rank(array $features): array
    {
        $model = $this->model();
        if ($model === [] || $features === []) {
            return [[], []];
        }

        $vocab = [];
        foreach ($model as $c) {
            $vocab += $c['counts'];
        }
        $features = array_intersect_key($features, $vocab);
        if ($features === []) {
            return [[], []]; // nothing we've seen before — no basis for a guess
        }

        $v       = count($vocab);
        $alpha   = 0.5;
        $weight  = array_sum($features);
        $docs    = array_sum(array_column($model, 'docs'));
        $scores  = [];
        $logProb = [];
        foreach ($model as $id => $c) {
            // Mild prior so big sections don't swallow everything.
            $score = 0.3 * log(($c['docs'] + 1) / ($docs + count($model)));
            foreach ($features as $tok => $n) {
                $lp                 = log((($c['counts'][$tok] ?? 0) + $alpha) / ($c['total'] + $alpha * $v));
                $logProb[$id][$tok] = $lp;
                $score             += $n * $lp;
            }
            $scores[$id] = $score;
        }

        // Naive Bayes is over-confident on long documents; soften by length.
        $temp = max(2.5, $weight / 4);
        $max  = max($scores);
        $exp  = array_map(static fn ($s) => exp(($s - $max) / $temp), $scores);
        $sum  = array_sum($exp);

        $out = [];
        foreach ($exp as $id => $e) {
            $out[] = ['category_id' => (int) $id, 'category_name' => $model[$id]['name'], 'confidence' => round($e / $sum, 3)];
        }
        usort($out, static fn ($a, $b) => $b['confidence'] <=> $a['confidence']);

        $matched = [];
        if (isset($out[1])) {
            $win = $out[0]['category_id'];
            $run = $out[1]['category_id'];
            foreach ($features as $tok => $n) {
                if (($model[$win]['counts'][$tok] ?? 0) > 0) {
                    $matched[$tok] = $n * ($logProb[$win][$tok] - $logProb[$run][$tok]);
                }
            }
            arsort($matched);
            $matched = array_keys(array_filter(array_slice($matched, 0, 5, true), static fn ($d) => $d > 0));
        }

        return [$out, $matched];
    }

    /**
     * Word counts per section from the templates already filed there, plus
     * the section's own name (weighted, so a new empty section can still win
     * on its name).
     */
    private function model(): array
    {
        if ($this->model !== null) {
            return $this->model;
        }

        $model = [];
        foreach ((new TemplateCategoryModel())->findAll() as $cat) {
            $model[(int) $cat['id']] = ['name' => $cat['name'], 'docs' => 0, 'total' => 0.0, 'counts' => []];
            foreach ($this->tokenize($cat['name']) as $tok) {
                $model[(int) $cat['id']]['counts'][$tok] = ($model[(int) $cat['id']]['counts'][$tok] ?? 0) + 4;
                $model[(int) $cat['id']]['total'] += 4;
            }
        }

        $rows = (new TemplateModel())->select('id, category_id, title, file_name, description, keywords')->findAll();
        foreach ($rows as $row) {
            $id = (int) $row['category_id'];
            if (! isset($model[$id]) || (int) $row['id'] === $this->excludeId) {
                continue;
            }
            $model[$id]['docs']++;
            $this->docCount++;

            $tokens = array_merge(
                $this->tokenize(pathinfo($row['file_name'], PATHINFO_FILENAME) . ' ' . $row['title']),
                $this->tokenize($row['title']),
                $this->tokenize(($row['description'] ?? '') . ' ' . ($row['keywords'] ?? '')),
            );
            foreach ($tokens as $tok) {
                $model[$id]['counts'][$tok] = ($model[$id]['counts'][$tok] ?? 0) + 1;
                $model[$id]['total']++;
            }
            foreach (array_unique($tokens) as $tok) {
                $this->docFreq[$tok] = ($this->docFreq[$tok] ?? 0) + 1;
            }
        }

        return $this->model = $model;
    }

    /**
     * Most distinctive words of the document: frequent here, rare across the
     * other templates (TF-IDF).
     *
     * @return list<string>
     */
    private function topKeywords(array $nameTokens, array $contentTokens, int $limit): array
    {
        $this->model();

        $tf = [];
        foreach ($nameTokens as $t) {
            $tf[$t] = ($tf[$t] ?? 0) + 3;
        }
        foreach ($contentTokens as $t) {
            $tf[$t] = ($tf[$t] ?? 0) + 1;
        }

        $inName = array_flip($nameTokens);
        $scored = [];
        foreach ($tf as $t => $n) {
            if (strlen($t) < 4 && ! isset($inName[$t])) {
                continue; // short content words are mostly noise
            }
            $idf        = log(($this->docCount + 1) / (($this->docFreq[$t] ?? 0) + 1)) + 1;
            $scored[$t] = (1 + log($n)) * $idf;
        }
        arsort($scored);

        return array_slice(array_keys($scored), 0, $limit);
    }

    /** @return list<string> */
    private function tags(array $nameTokens, array $contentTokens, array $keywords): array
    {
        $tags = [];

        // Document type: from the text first when there is enough of it (same
        // rule as classification), otherwise from the file name first.
        $sources = count($contentTokens) >= 25
            ? [array_slice($contentTokens, 0, 120), $nameTokens]
            : [$nameTokens, array_slice($contentTokens, 0, 120)];
        foreach ($sources as $source) {
            foreach ($source as $tok) {
                foreach (self::TYPE_TAGS as $stem => $tag) {
                    if (self::isWordForm($tok, $stem) && ! in_array($tag, $tags, true)) {
                        $tags[] = $tag;
                    }
                }
            }
            if (count($tags) >= 3) {
                break;
            }
        }
        $tags = array_slice($tags, 0, 3);

        // Plus a few distinctive keywords that aren't already covered.
        foreach ($keywords as $kw) {
            if (count($tags) >= 6) {
                break;
            }
            // Short codes and vowel-less words are acronyms (BAC, COA, HRD).
            $label = strlen($kw) <= 3 || ! preg_match('/[aeiou]/', $kw) ? strtoupper($kw) : ucfirst($kw);
            $covered = false;
            foreach ($tags as $t) {
                if (str_starts_with(strtolower($t), substr($kw, 0, 5))) {
                    $covered = true;
                    break;
                }
            }
            if (! $covered) {
                $tags[] = $label;
            }
        }

        return $tags;
    }

    /** "reports", "certificate", "enrollment" match their stem; "plant" does not match "plan". */
    private static function isWordForm(string $tok, string $stem): bool
    {
        if (! str_starts_with($tok, $stem)) {
            return false;
        }

        return in_array(substr($tok, strlen($stem)), ['', 's', 'e', 'es', 'ed', 'ing', 'ion', 'ions', 'ment', 'ments', 'ee', 'ees', 'ning', 'ners'], true);
    }

    /**
     * Cleans tags typed by the ADAS: trimmed, de-duplicated, at most 10.
     *
     * @return list<string>
     */
    public static function cleanTags(string $raw): array
    {
        $out = [];
        foreach (explode(',', $raw) as $tag) {
            $tag = trim(preg_replace('/\s+/', ' ', $tag));
            if ($tag !== '' && mb_strlen($tag) <= 40 && ! in_array(mb_strtolower($tag), array_map('mb_strtolower', $out), true)) {
                $out[] = $tag;
            }
        }

        return array_slice($out, 0, 10);
    }
}
