<?php

// Copyright 2022 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

/**
 * Holds the result of a text translation request.
 */
class TextResult
{
    /**
     * @var string String containing the translated text.
     */
    public $text;

    /**
     * @var string Language code of the detected source language.
     * @see LanguageCode
     */
    public $detectedSourceLang;

    /**
     * @throws DeepLException
     */
    public function __construct(string $text, string $detectedSourceLang)
    {
        $this->text = $text;
        $this->detectedSourceLang = LanguageCode::standardizeLanguageCode($detectedSourceLang);
    }

    public function __toString(): string
    {
        return $this->text;
    }
}
