<?php

// Copyright 2022 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

/**
 * Options that can be specified when translating text.
 * @see Translator::translateText()
 */
class TranslateTextOptions
{
    /**
     * Specifies how input translation text should be split into sentences.
     * - 'on': Input translation text will be split into sentences using both newlines and
     *   punctuation, this is the default behaviour.
     * - 'off': Input translation text will not be split into sentences. This is advisable for
     *   applications where each input translation text is only one sentence.
     * - 'nonewlines': Input translation text will be split into sentences using only punctuation
     *   but not newlines.
     */
    public const SPLIT_SENTENCES = 'split_sentences';

    /** Set to true to prevent the translation engine from correcting some formatting aspects, and
     * instead leave the formatting unchanged, default is false. */
    public const PRESERVE_FORMATTING = 'preserve_formatting';

    /** Controls whether translations should lean toward formal or informal language.
     * - 'less': use informal language.
     * - 'more': use formal, more polite language.
     * - 'default': use default formality.
     * - 'prefer_less': use informal language if available, otherwise default.
     * - 'prefer_more': use formal, more polite language if available, otherwise default.
     */
    public const FORMALITY = 'formality';

    /** Specifies additional context to influence translations, that is not
     * translated itself. Note this is an **alpha feature**: it may be deprecated at
     * any time, or incur charges if it becomes generally available.
     * See the API documentation for more information and example usage.
     */
    public const CONTEXT = 'context';

    /** Type of tags to parse before translation, options are 'html' and 'xml'. */
    public const TAG_HANDLING = 'tag_handling';

    /** Set to false to disable automatic tag detection, default is true. */
    public const OUTLINE_DETECTION = 'outline_detection';

    /** List of XML tags that should be used to split text into sentences. */
    public const SPLITTING_TAGS = 'splitting_tags';

    /** List of XML tags that should not be used to split text into sentences. */
    public const NON_SPLITTING_TAGS = 'non_splitting_tags';

    /** List of XML tags containing content that should not be translated. */
    public const IGNORE_TAGS = 'ignore_tags';

    /** Set to string containing a glossary ID to use the glossary for translation.
     *  Can also be set to a GlossaryInfo as returned by createGlossary, getGlossary or listGlossaries.
     *  @see \DeepL\Translator::createGlossary()
     *  @see \DeepL\Translator::getGlossary()
     *  @see \DeepL\Translator::listGlossaries()
     */
    public const GLOSSARY = 'glossary';
}
