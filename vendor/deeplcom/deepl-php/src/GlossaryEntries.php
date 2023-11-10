<?php

// Copyright 2022 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

/**
 * Stores the entries of a glossary.
 */
class GlossaryEntries
{
    /** @var array Associative array storing the glossary entries as source-target entry pairs. */
    private $implEntries;

    public function getEntries(): array
    {
        return $this->implEntries;
    }

    /**
     * @throws DeepLException
     */
    public static function fromTsv(string $tsv): GlossaryEntries
    {
        $entries = [];
        $lineNumber = 0;
        foreach (mb_split('/\r\n|\n|\r/', $tsv) as $line) {
            $lineNumber++;
            $lineTrimmed = trim($line);
            if (strlen($lineTrimmed) == 0) {
                continue;
            }
            $terms = explode("\t", $lineTrimmed, 3);
            if (count($terms) < 2) {
                throw new DeepLException("Entry on line $lineNumber does not contain separator: $line");
            } elseif (count($terms) > 2) {
                throw new DeepLException("Entry on line $lineNumber contains more than one term separator: $line");
            }
            $source = $terms[0];
            $target = $terms[1];
            self::validateGlossaryTerm($source);
            self::validateGlossaryTerm($target);
            if (array_key_exists($source, $entries)) {
                throw new DeepLException("Entry on line $lineNumber duplicates source term \"$source\"");
            }
            $entries[$source] = $target;
        }
        if (count($entries) == 0) {
            throw new DeepLException('Input contains no entries');
        }
        return new GlossaryEntries($entries);
    }

    /**
     * @throws DeepLException
     */
    public static function fromEntries(array $entries): GlossaryEntries
    {
        if (count($entries) == 0) {
            throw new DeepLException('Input contains no entries');
        }
        foreach ($entries as $source => $target) {
            self::validateGlossaryTerm($source);
            self::validateGlossaryTerm($target);
        }
        return new GlossaryEntries($entries);
    }

    public function convertToTsv(): string
    {
        $terms = array_map(function ($k, $v) {
            return "$k\t$v";
        }, array_keys($this->implEntries), array_values($this->implEntries));
        return implode("\n", $terms);
    }

    /**
     * @param array $entries
     */
    private function __construct(array $entries)
    {
        $this->implEntries = $entries;
    }

    /**
     * @throws DeepLException
     */
    private static function validateGlossaryTerm(string $term)
    {
        $termTrimmed = trim($term);
        if (strlen($termTrimmed) == 0) {
            throw new DeepLException("Term \"$term\" contains no non-whitespace characters");
        }
        foreach (mb_str_split($termTrimmed, 1, 'utf-8') as $ch) {
            $ord = mb_ord($ch);
            if ($ord === false || // Conversion failed
                (0 <= $ord && $ord <= 31) || // C0 control characters
                (128 <= $ord && $ord <= 159) || // C1 control characters
                $ord == 0x2028 || $ord == 0x2029 // Unicode newlines
            ) {
                $hex = dechex($ord);
                throw new DeepLException("Term \"$term\" contains invalid characters: '$ch' (0x$hex)");
            }
        }
    }
}
