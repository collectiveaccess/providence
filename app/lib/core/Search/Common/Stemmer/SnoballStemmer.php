<?php

/*************************************************************************
 *                                                                       *
 * class.stemmer.inc                                                     *
 *                                                                       *
 *************************************************************************
 *                                                                       *
 * Implementation of the Porter Stemming Alorithm                        *
 *                                                                       *
 * Copyright (c) 2003-2007 Jon Abernathy <jon@chuggnutt.com>             *
 * All rights reserved.                                                  *
 *                                                                       *
 * This script is free software; you can redistribute it and/or modify   *
 * it under the terms of the GNU General Public License as published by  *
 * the Free Software Foundation; either version 2 of the License, or     *
 * (at your option) any later version.                                   *
 *                                                                       *
 * The GNU General Public License can be found at                        *
 * http://www.gnu.org/copyleft/gpl.html.                                 *
 *                                                                       *
 * This script is distributed in the hope that it will be useful,        *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the          *
 * GNU General Public License for more details.                          *
 *                                                                       *
 * Author(s): Jon Abernathy <jon@chuggnutt.com>                          *
 *                                                                       *
 * Last modified: 08/08/07                                               *
 *                                                                       *
 *************************************************************************/


/**
 *  Takes a word, or list of words, and reduces them to their English stems.
 *
 *  This is a fairly faithful implementation of the Porter stemming algorithm that
 *  reduces English words to their stems, originally adapted from the ANSI-C code found
 *  on the official Porter Stemming Algorithm website, located at
 *  http://www.tartarus.org/~martin/PorterStemmer and later changed to conform
 *  more accurately to the algorithm itself.
 *
 *  There is a deviation in the way compound words are stemmed, such as
 *  hyphenated words and words starting with certain prefixes. For instance,
 *  "international" should be reduced to "internation" and not "intern," but
 *  an unmodified version of the alorithm will do just that. Currently, only
 *  hyphenated words are accounted for.
 *
 *  Thanks to Mike Boone (http://www.boonedocks.net/) for finding a fatal
 *  error in the is_consonant() function dealing with short word stems beginning
 *  with "Y".
 *
 *  Additional thanks to Mark Plumbley for finding an additional problem with
 *  short words beginning with "Y"--the word "yves" for example. I fixed the
 *  _o() and is_consonant() functions to appropriately sanity check the values
 *  being passed around. Updated 3/12/04.
 *
 *  Thanks to Andrew Jeffries (http://www.nextgendevelopment.co.uk/) for
 *  discovering a bug for words beginning with "yy"--this would cause the
 *  is_consonant() method checking either of these first "y"s to fall into
 *  a recursive infinite loop and crash the program. Updated 9/23/05.
 *
 *  11/09/05, big update. Prompted by an email from Richard Shelquist, I went
 *  back over the class and fixed some errors in the algorithm; in particular
 *  I made sure to conform EXACTLY to the written algorithm found at
 *  the Stemmer website. This class now takes the test vocabulary file found at
 *  http://tartarus.org/~martin/PorterStemmer/voc.txt and stems every single
 *  word exactly as shown in the output file found at
 *  http://tartarus.org/~martin/PorterStemmer/output.txt, with two exceptions:
 *  "ycleped" and "ycliped", which I believe my version stems correctly, due
 *  to assuming the "Y" at the beginning of a word followed by a consonant--
 *  as in "Yvette"--is to be treated as a vowel and NOT a consonant. Yeah,
 *  that's arrogant; allow me some, okay?
 *  Of course, should someone find an exception after boasting of my arrogance,
 *  please let me know. I'm only human, after all.
 *
 *	Thanks to Damon Sauve (http://www.shopping.com/) for suggesting a better
 *  fix to the handling of hyphenated words (in his case, multi-hyphenated
 *  words). His fix used a regular expression to extract the final part of the
 *  hyphenated word, while mine does a substr() split instead. Also, his version
 *  allows dots and apostrophes in words, such as URLs and contractions, and
 *  I realize this is a real-world scenario that I didn't account for, so it's
 *  been incorporated.
 *
 *  @author Jon Abernathy <jon@chuggnutt.com>
 *  @version 2.1
 */
class SnoballStemmer
{
	private $opa_stem_cache;
	
	public function __construct() {
		$this->opa_stem_cache = array();
	}
	
    /**
     *  Takes a word and returns it reduced to its stem.
     *
     *  Non-alphanumerics and hyphens are removed, except for dots and
	 *  apostrophes, and if the word is less than three characters in
	 *  length, it will be stemmed according to the five-step
     *  Porter stemming algorithm.
     *
     *  Note special cases here: hyphenated words (such as half-life) will
	 *  only have the base after the last hyphen stemmed (so half-life would
	 *  only have "life" subject to stemming). Handles multi-hyphenated
	 *  words, too.
     *
     *  @param string $word Word to reduce
     *  @access public
     *  @return string Stemmed word
     */
    public function stem( $word, $lang = 'en' )
    {
        if ( empty($word) ) {
            return false;
        }
        
        $orig_word = $word;

		if (isset($this->opa_stem_cache[$word])) { return $this->opa_stem_cache[$word]; }
		 // Use PECL function if it is installed
        if (function_exists('stem')) {
        	$this->opa_stem_cache[$word] = stem($word, $this->lang2code($lang));
        	return $this->opa_stem_cache[$word];
        }
        
        $result = '';

        $word = strtolower(caRemoveAccents($word));

        // Strip punctuation, etc. Keep ' and . for URLs and contractions.
        if ( substr($word, -2) == "'s" ) {
            $word = substr($word, 0, -2);
        }
        if (function_exists('iconv')) { $word = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $word); }
        $word = preg_replace("/[^a-z0-9'.-]/", '', $word);

        $first = '';
        if ( strpos($word, '-') !== false ) {
            //list($first, $word) = explode('-', $word);
            //$first .= '-';
            $first = substr($word, 0, strrpos($word, '-') + 1); // Grabs hyphen too
            $word = substr($word, strrpos($word, '-') + 1);
        }
        if ( strlen($word) > 2 ) {
            $word = $this->_step_1($word);
            $word = $this->_step_2($word);
            $word = $this->_step_3($word);
            $word = $this->_step_4($word);
            $word = $this->_step_5($word);
        }

        $result = $first . $word;

		$this->opa_stem_cache[$orig_word] = $result;
        return $result;
    }

    /**
     *  Takes a list of words and returns them reduced to their stems.
     *
     *  $words can be either a string or an array. If it is a string, it will
     *  be split into separate words on whitespace, commas, or semicolons. If
     *  an array, it assumes one word per element.
     *
     *  @param mixed $words String or array of word(s) to reduce
     *  @access public
     *  @return array List of word stems
     */
    public function stem_list( $words )
    {
        if ( empty($words) ) {
            return false;
        }

        $results = array();

        if ( !is_array($words) ) {
            $words = split("[ ,;\n\r\t]+", trim($words));
        }

        foreach ( $words as $word ) {
            if ( $result = $this->stem($word) ) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     *  Performs the functions of steps 1a and 1b of the Porter Stemming Algorithm.
     *
     *  First, if the word is in plural form, it is reduced to singular form.
     *  Then, any -ed or -ing endings are removed as appropriate, and finally,
     *  words ending in "y" with a vowel in the stem have the "y" changed to "i".
     *
     *  @param string $word Word to reduce
     *  @access private
     *  @return string Reduced word
     */
    private function _step_1( $word )
    {
		// Step 1a
		if ( substr($word, -1) == 's' ) {
            if ( substr($word, -4) == 'sses' ) {
                $word = substr($word, 0, -2);
            } elseif ( substr($word, -3) == 'ies' ) {
                $word = substr($word, 0, -2);
            } elseif ( substr($word, -2, 1) != 's' ) {
                // If second-to-last character is not "s"
                $word = substr($word, 0, -1);
            }
        }
		// Step 1b
        if ( substr($word, -3) == 'eed' ) {
			if ($this->count_vc(substr($word, 0, -3)) > 0 ) {
	            // Convert '-eed' to '-ee'
	            $word = substr($word, 0, -1);
			}
        } else {
            if ( preg_match('/([aeiou]|[^aeiou]y).*(ed|ing)$/', $word) ) { // vowel in stem
                // Strip '-ed' or '-ing'
                if ( substr($word, -2) == 'ed' ) {
                    $word = substr($word, 0, -2);
                } else {
                    $word = substr($word, 0, -3);
                }
                if ( substr($word, -2) == 'at' || substr($word, -2) == 'bl' ||
                     substr($word, -2) == 'iz' ) {
                    $word .= 'e';
                } else {
                    $last_char = substr($word, -1, 1);
                    $next_to_last = substr($word, -2, 1);
                    // Strip ending double consonants to single, unless "l", "s" or "z"
                    if ( $this->is_consonant($word, -1) &&
                         $last_char == $next_to_last &&
                         $last_char != 'l' && $last_char != 's' && $last_char != 'z' ) {
                        $word = substr($word, 0, -1);
                    } else {
                        // If VC, and cvc (but not w,x,y at end)
                        if ( $this->count_vc($word) == 1 && $this->_o($word) ) {
                            $word .= 'e';
                        }
                    }
                }
            }
        }
        // Step 1c
        // Turn y into i when another vowel in stem
        if ( preg_match('/([aeiou]|[^aeiou]y).*y$/', $word) ) { // vowel in stem
            $word = substr($word, 0, -1) . 'i';
        }
        return $word;
    }

    /**
     *  Performs the function of step 2 of the Porter Stemming Algorithm.
     *
     *  Step 2 maps double suffixes to single ones when the second-to-last character
     *  matches the given letters. So "-ization" (which is "-ize" plus "-ation"
     *  becomes "-ize". Mapping to a single character occurence speeds up the script
     *  by reducing the number of possible string searches.
     *
     *  Note: for this step (and steps 3 and 4), the algorithm requires that if
     *  a suffix match is found (checks longest first), then the step ends, regardless
     *  if a replacement occurred. Some (or many) implementations simply keep
     *  searching though a list of suffixes, even if one is found.
     *
     *  @param string $word Word to reduce
     *  @access private
     *  @return string Reduced word
     */
    private function _step_2( $word )
    {
        switch ( substr($word, -2, 1) ) {
            case 'a':
                if ( $this->_replace($word, 'ational', 'ate', 0) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'tional', 'tion', 0) ) {
                    return $word;
                }
                break;
            case 'c':
                if ( $this->_replace($word, 'enci', 'ence', 0) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'anci', 'ance', 0) ) {
                    return $word;
                }
                break;
            case 'e':
                if ( $this->_replace($word, 'izer', 'ize', 0) ) {
                    return $word;
                }
                break;
            case 'l':
                // This condition is a departure from the original algorithm;
                // I adapted it from the departure in the ANSI-C version.
				if ( $this->_replace($word, 'bli', 'ble', 0) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'alli', 'al', 0) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'entli', 'ent', 0) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'eli', 'e', 0) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'ousli', 'ous', 0) ) {
                    return $word;
                }
                break;
            case 'o':
                if ( $this->_replace($word, 'ization', 'ize', 0) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'isation', 'ize', 0) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'ation', 'ate', 0) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'ator', 'ate', 0) ) {
                    return $word;
                }
                break;
            case 's':
                if ( $this->_replace($word, 'alism', 'al', 0) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'iveness', 'ive', 0) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'fulness', 'ful', 0) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'ousness', 'ous', 0) ) {
                    return $word;
                }
                break;
            case 't':
                if ( $this->_replace($word, 'aliti', 'al', 0) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'iviti', 'ive', 0) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'biliti', 'ble', 0) ) {
                    return $word;
                }
                break;
            case 'g':
                // This condition is a departure from the original algorithm;
                // I adapted it from the departure in the ANSI-C version.
                if ( $this->_replace($word, 'logi', 'log', 0) ) { //*****
                    return $word;
                }
                break;
        }
        return $word;
    }

    /**
     *  Performs the function of step 3 of the Porter Stemming Algorithm.
     *
     *  Step 3 works in a similar stragegy to step 2, though checking the
     *  last character.
     *
     *  @param string $word Word to reduce
     *  @access private
     *  @return string Reduced word
     */
    private function _step_3( $word )
    {
        switch ( substr($word, -1) ) {
            case 'e':
                if ( $this->_replace($word, 'icate', 'ic', 0) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'ative', '', 0) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'alize', 'al', 0) ) {
                    return $word;
                }
                break;
            case 'i':
                if ( $this->_replace($word, 'iciti', 'ic', 0) ) {
                    return $word;
                }
                break;
            case 'l':
                if ( $this->_replace($word, 'ical', 'ic', 0) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'ful', '', 0) ) {
                    return $word;
                }
                break;
            case 's':
                if ( $this->_replace($word, 'ness', '', 0) ) {
                    return $word;
                }
                break;
        }
        return $word;
    }

    /**
     *  Performs the function of step 4 of the Porter Stemming Algorithm.
     *
     *  Step 4 works similarly to steps 3 and 2, above, though it removes
     *  the endings in the context of VCVC (vowel-consonant-vowel-consonant
     *  combinations).
     *
     *  @param string $word Word to reduce
     *  @access private
     *  @return string Reduced word
     */
    private function _step_4( $word )
    {
        switch ( substr($word, -2, 1) ) {
            case 'a':
                if ( $this->_replace($word, 'al', '', 1) ) {
                    return $word;
                }
                break;
            case 'c':
                if ( $this->_replace($word, 'ance', '', 1) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'ence', '', 1) ) {
                    return $word;
                }
                break;
            case 'e':
                if ( $this->_replace($word, 'er', '', 1) ) {
                    return $word;
                }
                break;
            case 'i':
                if ( $this->_replace($word, 'ic', '', 1) ) {
                    return $word;
                }
                break;
            case 'l':
                if ( $this->_replace($word, 'able', '', 1) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'ible', '', 1) ) {
                    return $word;
                }
                break;
            case 'n':
                if ( $this->_replace($word, 'ant', '', 1) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'ement', '', 1) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'ment', '', 1) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'ent', '', 1) ) {
                    return $word;
                }
                break;
            case 'o':
                // special cases
                if ( substr($word, -4) == 'sion' || substr($word, -4) == 'tion' ) {
                    if ( $this->_replace($word, 'ion', '', 1) ) {
                        return $word;
                    }
                }
                if ( $this->_replace($word, 'ou', '', 1) ) {
                    return $word;
                }
                break;
            case 's':
                if ( $this->_replace($word, 'ism', '', 1) ) {
                    return $word;
                }
                break;
            case 't':
                if ( $this->_replace($word, 'ate', '', 1) ) {
                    return $word;
                }
                if ( $this->_replace($word, 'iti', '', 1) ) {
                    return $word;
                }
                break;
            case 'u':
                if ( $this->_replace($word, 'ous', '', 1) ) {
                    return $word;
                }
                break;
            case 'v':
                if ( $this->_replace($word, 'ive', '', 1) ) {
                    return $word;
                }
                break;
            case 'z':
                if ( $this->_replace($word, 'ize', '', 1) ) {
                    return $word;
                }
                break;
        }
        return $word;
    }

    /**
     *  Performs the function of step 5 of the Porter Stemming Algorithm.
     *
     *  Step 5 removes a final "-e" and changes "-ll" to "-l" in the context
     *  of VCVC (vowel-consonant-vowel-consonant combinations).
     *
     *  @param string $word Word to reduce
     *  @access private
     *  @return string Reduced word
     */
    private function _step_5( $word )
    {
        if ( substr($word, -1) == 'e' ) {
            $short = substr($word, 0, -1);
            // Only remove in vcvc context...
            if ( $this->count_vc($short) > 1 ) {
                $word = $short;
            } elseif ( $this->count_vc($short) == 1 && !$this->_o($short) ) {
                $word = $short;
            }
        }
        if ( substr($word, -2) == 'll' ) {
            // Only remove in vcvc context...
            if ( $this->count_vc($word) > 1 ) {
                $word = substr($word, 0, -1);
            }
        }
        return $word;
    }

    /**
     *  Checks that the specified letter (position) in the word is a consonant.
     *
     *  Handy check adapted from the ANSI C program. Regular vowels always return
     *  FALSE, while "y" is a special case: if the prececing character is a vowel,
     *  "y" is a consonant, otherwise it's a vowel.
     *
     *  And, if checking "y" in the first position and the word starts with "yy",
     *  return true even though it's not a legitimate word (it crashes otherwise).
     *
     *  @param string $word Word to check
     *  @param integer $pos Position in the string to check
     *  @access public
     *  @return boolean
     */
    public function is_consonant( $word, $pos )
    {
        // Sanity checking $pos
        if ( abs($pos) > strlen($word) ) {
            if ( $pos < 0 ) {
                // Points "too far back" in the string. Set it to beginning.
                $pos = 0;
            } else {
                // Points "too far forward." Set it to end.
                $pos = -1;
            }
        }
        $char = substr($word, $pos, 1);
        switch ( $char ) {
            case 'a':
            case 'e':
            case 'i':
            case 'o':
            case 'u':
                return false;
            case 'y':
                if ( $pos == 0 || strlen($word) == -$pos ) {
                    // Check second letter of word.
                    // If word starts with "yy", return true.
                    if ( substr($word, 1, 1) == 'y' ) {
                        return true;
                    }
                    return !($this->is_consonant($word, 1));
                } else {
                    return !($this->is_consonant($word, $pos - 1));
                }
            default:
                return true;
        }
    }

    /**
     *  Counts (measures) the number of vowel-consonant occurences.
     *
     *  Based on the algorithm; this handy function counts the number of
     *  occurences of vowels (1 or more) followed by consonants (1 or more),
     *  ignoring any beginning consonants or trailing vowels. A legitimate
     *  VC combination counts as 1 (ie. VCVC = 2, VCVCVC = 3, etc.).
     *
     *  @param string $word Word to measure
     *  @access public
     *  @return integer
     */
    public function count_vc( $word )
    {
        $m = 0;
        $length = strlen($word);
        $prev_c = false;
        for ( $i = 0; $i < $length; $i++ ) {
            $is_c = $this->is_consonant($word, $i);
            if ( $is_c ) {
                if ( $m > 0 && !$prev_c ) {
                    $m += 0.5;
                }
            } else {
                if ( $prev_c || $m == 0 ) {
                    $m += 0.5;
                }
            }
            $prev_c = $is_c;
        }
        $m = floor($m);
        return $m;
    }

    /**
     *  Checks for a specific consonant-vowel-consonant condition.
     *
     *  This function is named directly from the original algorithm. It
     *  looks the last three characters of the word ending as
     *  consonant-vowel-consonant, with the final consonant NOT being one
     *  of "w", "x" or "y".
     *
     *  @param string $word Word to check
     *  @access private
     *  @return boolean
     */
    private function _o( $word )
    {
        if ( strlen($word) >= 3 ) {
            if ( $this->is_consonant($word, -1) && !$this->is_consonant($word, -2) &&
                 $this->is_consonant($word, -3) ) {
		        $last_char = substr($word, -1);
		        if ( $last_char == 'w' || $last_char == 'x' || $last_char == 'y' ) {
		            return false;
		        }
                return true;
            }
        }
        return false;
    }

    /**
     *  Replaces suffix, if found and word measure is a minimum count.
     *
     *  @param string $word Word to check and modify
     *  @param string $suffix Suffix to look for
     *  @param string $replace Suffix replacement
     *  @param integer $m Word measure value that the word must be greater
     *                    than to replace
     *  @access private
     *  @return boolean
     */
    private function _replace( &$word, $suffix, $replace, $m = 0 )
    {
        $sl = strlen($suffix);
        if ( substr($word, -$sl) == $suffix ) {
            $short = substr_replace($word, '', -$sl);
            if ( $this->count_vc($short) > $m ) {
                $word = $short . $replace;
            }
            // Found this suffix, doesn't matter if replacement succeeded
            return true;
        }
        return false;
    }


	#
	# Convert language code to PECL Stem language constant
	#
    private function lang2code($lang) {
    	if (!function_exists('stem')) { return null; }
    	switch($lang) {
    		case 'en':
    			return STEM_ENGLISH;
    			break;
    		case 'da':
    			return STEM_DANISH;
    			break;
    		case 'nl':
    			return STEM_DUTCH;
    			break;
    		case 'fi':
    			return STEM_FINNISH;
    			break;
    		case 'fr':
    			return STEM_FRENCH;
    			break;
    		case 'de':
    			return STEM_GERMAN;
    			break;
    		case 'it':
    			return STEM_ITALIAN;
    			break;
    		case 'no':
    			return STEM_NORWEGIAN;
    			break;
    		case 'pt':
    			return STEM_PORTUGUESE;
    			break;
    		case 'ru':
    			return STEM_RUSSIAN;
    			break;
    		case 'sp':
    			return STEM_SPANISH;
    			break;
    		case 'sv':
    			return STEM_SWEDISH;
    			break;
    		default:
    			return STEM_PORTER;
    	}
    }
}
?>