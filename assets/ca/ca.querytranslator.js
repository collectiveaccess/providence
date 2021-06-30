/* ----------------------------------------------------------------------
 * js/ca/ca.querytranslator.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2021 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * ----------------------------------------------------------------------
 */

var caUI = caUI || {};

/**
 * This file defines two functions in the `caUI` namespace, for converting between (one function for each direction of
 * conversion) the CA search query syntax and the jQuery query builder hierarchical rule set structure.
 *
 * The only range filter that is supported here is `between`.  The Lucene / CA search syntax does not readily support
 * `greater_than` or `less_than`.
 */
(function () {
	var escapeValue, getTokenList, shiftToken, tokensToRuleSet, fieldToLabel,
		assertNextToken, isNextToken, assertCondition, skipWhitespace, isRawSearchText,
		assignOperatorAndValue, assignOperatorAndRange,
		TOKEN_WORD = 'WORD',
		TOKEN_LPAREN = 'LPAREN',
		TOKEN_RPAREN = 'RPAREN',
		TOKEN_COLON = 'COLON',
		TOKEN_NEGATION = 'NEGATION',
		TOKEN_LBRACKET = 'LBRACKET',
		TOKEN_RBRACKET = 'RBRACKET',
		TOKEN_WHITESPACE = 'WHITESPACE',
		TOKEN_WILDCARD = 'WILDCARD',
		FIELD_FULLTEXT = '_fulltext';

	/**
	 * Escape the user-entered field value.
	 * @param {String} value
	 * @returns {String}
	 */
	escapeValue = function (value) {
		if(typeof value !== 'string') return value;
		return value.replaceAll(/([\-\+&\|!\(\)\{}\[\]\^"'~\*\?:\\])/g, '\\$1');
	};


    /**
     *
     */
    fieldToLabel = function(labels, field) {
        for(var i in labels) {
            if(field === labels[i].field) {
                return labels[i].label;
            }
        }
        return null;
    };
    
	/**
	 * Convert a set of rules from the jQuery query builder into a CA search query.  Performs the inverse operation to
	 * `convertSearchQueryToQueryBuilderRules`.
	 * @param {Object} ruleSet
	 * @param ueNegationSign
	 * @param useLabels
	 *
	 * @returns {String}
	 */
	caUI.convertQueryBuilderRuleSetToSearchQuery = function (ruleSet, useNegationSign=false, useLabels=null) {
		var negation, prefix;
		if (ruleSet.condition && ruleSet.rules) {
		    // The CA Lucene parser does not allow signs (+/-) and booleans (AND/OR/NOT) in the same subquery. We handle this
		    // by using booleans exclusively. However standalone NOT subqueries (Ex. NOT ca_objects.idno:1975.001) will only
		    // work with signs, so we test for standalone NOTs here and force use of signs.
		    if((ruleSet.rules.length === 1) && ruleSet.rules[0].operator.match(/not_/)) {
		        return '(' + caUI.convertQueryBuilderRuleSetToSearchQuery(ruleSet.rules[0], true, useLabels) + ')';
		    }
		    
		    var acc = []
		    for(var i in  ruleSet.rules) {
		        acc.push(caUI.convertQueryBuilderRuleSetToSearchQuery(ruleSet.rules[i], useNegationSign, useLabels));
		    }
			return '(' + acc.join(' ' + ruleSet.condition + ' ') + ')';
		}
		if (ruleSet.operator && ruleSet.field) {
		    var f = useLabels ? fieldToLabel(useLabels, ruleSet.field) : ruleSet.field;
		    if(!f) { f = ruleSet.field; }
		    
			// Escape value to allow special characters
			negation = ruleSet.operator.match(/not_/);
			
			if (useNegationSign === true) {
			    prefix = f + (negation ? ':-' : ':');
			} else {
			    prefix = (negation ? 'NOT ' : '') + f + ':';
			}
			switch (negation ? ruleSet.operator.replace('not_', '') : ruleSet.operator) {
				case 'equal':
					return prefix + '"' + escapeValue(ruleSet.value) + '"';
				case 'in':
					return prefix + '(' + escapeValue(ruleSet.value) + ')';
				case 'between':
					return prefix + '[' + escapeValue(ruleSet.value[0]) + ' TO ' + escapeValue(ruleSet.value[1]) + ']';
				case 'begins_with':
					return prefix + '' + escapeValue(ruleSet.value) + '*';
				case 'contains':
					return prefix + '*' + escapeValue(ruleSet.value) + '*';
				case 'ends_with':
					return prefix + '*' + escapeValue(ruleSet.value) + '';
				case 'less':
					return prefix + '#lt#' + escapeValue(ruleSet.value) + '';
				case 'less_or_equal':
					return prefix + '#lt=' + escapeValue(ruleSet.value) + '';
				case 'greater':
					return prefix + '#gt#' + escapeValue(ruleSet.value) + '';
				case 'greater_or_equal':
					return prefix + '#gt=' + escapeValue(ruleSet.value) + '';
				case 'is_empty':
				case 'is_null':
					// "is_not_empty" is a double negative, so the negation prefix is applied in reverse.
					return f + (!negation ? ':"[BLANK]"' : ':"[SET]"');
			}
			
			return f + ':' + ruleSet.value;
		}
		return '';
	};

	/**
	 * Retrieve the list of tokens from the given query string.
	 * @param {String} query
	 * @return {Array}
	 */
	getTokenList = function (query) {
		var queryArray, token, tokens = [];
		queryArray = query.replace(/\s+/g, ' ').trim().split('');
		while (token = shiftToken(queryArray)) {
			tokens.push(token);
		}
		while (tokens.length >= 2 && tokens[0].type === TOKEN_LPAREN && tokens[tokens.length - 1].type === TOKEN_RPAREN) {
			tokens = tokens.slice(1, tokens.length - 1);
		}
		return tokens;
	};

	/**
	 * Retrieve the next token from the `queryArray`, which is destructively processed.
	 * @param {Array} queryArray
	 * @returns {Object|undefined}
	 */
	shiftToken = function (queryArray) {
		var character, token, quoted, escaped, end;
		quoted = false;
		escaped = false;
		end = false;
		// End condition.
		if (queryArray.length === 0) {
			return undefined;
		}
		// Inspect the first character to determine the type of token.
		character = queryArray.shift();
		switch (character) {
			// Single-character tokens.
			case '(':
				token = { type: TOKEN_LPAREN };
				end = true;
				break;
			case ')':
				token = { type: TOKEN_RPAREN };
				end = true;
				break;
			case '[':
				token = { type: TOKEN_LBRACKET };
				end = true;
				break;
			case ']':
				token = { type: TOKEN_RBRACKET };
				end = true;
				break;
			case ':':
				token = { type: TOKEN_COLON };
				end = true;
				break;
			case '-':
				token = { type: TOKEN_NEGATION };
				end = true;
				break;
			case ' ':
				token = { type: TOKEN_WHITESPACE };
				end = true;
				break;
			case '*':
				token = { type: TOKEN_WILDCARD };
				end = true;
				break;
			// Beginning of a quoted phrase, which ends after the next unescaped quote.
			case '"':
				token = { type: TOKEN_WORD, value: '' };
				quoted = true;
				break;
			default:
				// Beginning of a plain word, which ends before then next non-word character.
				// This includes the word "TO" in "between" filters.
				token = { type: TOKEN_WORD, value: character };
		}
		// Process remaining characters until the end of the token.
		while (queryArray.length > 0 && !end) {
			character = queryArray[0];
			if (escaped) {
				// The previous character was an escape backslash, so pass this character as-is.
				token.value += character;
				escaped = false;
				queryArray.shift();
			} else if (character === '\\') {
				// Unescaped backslash escapes the next character.
				escaped = true;
				queryArray.shift()
			} else if (quoted) {
				// In quoted mode, the next unescaped quote mark character ends the token.
				if (character === '"') {
					end = true;
					queryArray.shift();
				} else {
					token.value += character;
					queryArray.shift();
				}
			} else {
				// In plain word mode, the next non-word, non-dot character ends the token.
				// (Exceptions are "#" and "=" which are used to #lt#, #lt=, #gt#, #gt= and #eq# modifiers
				if (/[\w\/.#=]/.test(character)) {
					token.value += character;
					queryArray.shift();
				} else {
					end = true;
				}
			}
		}
		
		if (token.type === TOKEN_WORD) {
		    if((token.value.toLowerCase() === 'and') || (token.value.toLowerCase() === 'or')) {
		        token.isCondition = true;
		    }
		}
		return token;
	};

	/**
	 * Check that the next token is of the given type, throw an error if it is a different type.  Destructively
	 * processes `tokens` and returns the retrieved token, which always has the given `type`.
	 * @param {Array} tokens
	 * @param {String} type
	 * @param {String} value Optional, only checked if not `undefined`.
	 * @returns {Object}
	 * @throws
	 */
	assertNextToken = function (tokens, type, value) {
		var token;
		if (tokens.length === 0) {
			throw 'Unexpected end of token stream, expected "' + type + '".';
		}
		token = tokens.shift();
		if (token.type !== type) {
			throw 'Unexpected token type "' + token.type + '"' + (token.value ? ' (value: "' + token.value + '"' : '') + ', expected "' + type + '".';
		}
		if (value !== undefined && token.value !== value) {
			throw 'Unexpected token value "' + token.value + '" for token of type "' + token.type + '", expected "' + value + '".';
		}
		return token;
	};

	/**
	 * If the next token is of the given type, remove it from the `tokens` list and return `true`, otherwise do not
	 * modify the `tokens` list and return `false`.  Similar to `assertNextToken` except that a non-match is a no-op
	 * instead of an error.
	 * @param {Array} tokens
	 * @param {String} type
	 * @returns {Boolean}
	 */
	isNextToken = function (tokens, type) {
		if (tokens.length === 0 || tokens[0].type !== type) {
			return false;
		}
		return tokens.shift();
	};
	
	/**
	 * 
	 * @param {Array} tokens
	 * @param {Integer} index
	 * @returns {Object}
	 */
	peekToken = function (tokens, i=0) {
		return tokens[i];
	};

	/**
	 * Check that the next token is a valid condition, i.e. a word token with value "AND" or "OR".  Throw an error if
	 * the token type or value is incorrect.  Destructively processes the `tokens` list.
	 * @param {Array} tokens
	 * @returns {Object}
	 * @throws
	 */
	assertCondition = function (tokens) {
		var token = peekToken(tokens, 0);
		if ((token.type === TOKEN_WORD) && (token.value !== 'AND') && (token.value !== 'OR') && (token.value !== '+') && (token.value !== '-')) {
			//throw 'Unknown condition "' + token.value + '" expecting "AND" or "OR".';
			// Implicit AND
			return {
			    type: TOKEN_WORD,
			    value: 'AND'
			};
		}
		return assertNextToken(tokens, TOKEN_WORD);
	};

	/**
	 * Skip any whitespace tokens by destructively processing the given `tokens` list.
	 * @param {Array} tokens
	 */
	skipWhitespace = function (tokens) {
		while (tokens.length > 0 && tokens[0].type === TOKEN_WHITESPACE) {
			tokens.shift();
		}
	};

	/**
	 * Determine whether the given list of tokens is all words and whitespace.
	 * @param {Array} tokens
	 * @returns {boolean}
	 */
	isRawSearchText = function (tokens) {
		var allWords = true;
		$.each(tokens, function (i, token) {
			allWords = allWords && (token.type === TOKEN_WORD || token.type === TOKEN_WHITESPACE);
		});
		return allWords;
	};

	/**
	 * Use the given `queryValue` to assign a `value` and `condition` to the given `rule`.
	 * @param {Object} rule
	 * @param {String} queryValue
	 * @param {Boolean} negation
	 * @param {String} wildcardPrefix
	 * @param {String} wildcardSuffix
	 */
	assignOperatorAndValue = function (rule, queryValue, negation, wildcardPrefix, wildcardSuffix) {
		// Determine the operator that matches the given query, negation and wildcard positions.
		if (!queryValue) {
			rule.operator = negation ? 'is_empty' : 'is_not_empty';
		} else if(queryValue === '[BLANK]') {
		     rule.operator = 'is_empty';
		} else if(queryValue === '[SET]') {
		    rule.operator = 'is_not_empty';
		} else if (queryValue.match(/^#gt#/)) {
	        rule.operator = 'greater';
	        rule.value = queryValue.replace(/^#gt#/, '');
		} else if (queryValue.match(/^#gt=/)) {
	        rule.operator = 'greater_or_equal';
	        rule.value = queryValue.replace(/^#gt=/, '');
		} else if (queryValue.match(/^#lt#/)) {
	        rule.operator = 'less';
	        rule.value = queryValue.replace(/^#lt#/, '');
		} else if (queryValue.match(/^#lt=/)) {
	        rule.operator = 'less_or_equal';
	        rule.value = queryValue.replace(/^#lt=/, '');
		} else if (queryValue.match(/^#eq#/)) {
	        rule.operator = 'equal';
	        rule.value = queryValue.replace(/^#eq#/, '');
		} else {
			rule.value = queryValue;
			if (wildcardPrefix && wildcardSuffix) {
				rule.operator = negation ? 'not_contains' : 'contains';
			} else if (wildcardPrefix) {
				rule.operator = negation ? 'not_ends_with' : 'ends_with';
			} else if (wildcardSuffix) {
				rule.operator = negation ? 'not_begins_with' : 'begins_with';
			} else {
				rule.operator = negation ? 'not_equal' : 'equal';
			}
		}
	};

	/**
	 * Use the given `queryValue` to assign a `value` and `condition` to the given `rule`.
	 * @param {Object} rule
	 * @param {String} min
	 * @param {String} max
	 * @param {Boolean} negation
	 */
	assignOperatorAndRange = function (rule, min, max, negation) {
        rule.value = [ min, max ];
        rule.operator = negation ? 'not_between' : 'between';
	};

	/**
	 * Parse the given array of tokens into a tree structure for the query builder.
	 * @param {Array} tokens
	 * @return {Object}
	 */
	tokensToRuleSet = function (tokens) {
		var rule, condition, negation, wildcardPrefix, wildcardSuffix, min, max, word, ruleSet;
		ruleSet = {
			condition: undefined,
			rules: []
		};
		skipWhitespace(tokens);
		if (isRawSearchText(tokens)) {
			// Special case: a sequence of only words (and whitespace) should be treated as a single, full text search.
			ruleSet.condition = '+';
			ruleSet.rules.push({
				id: FIELD_FULLTEXT,
				field: FIELD_FULLTEXT,
				operator: 'equal',
				value: $.map(tokens, function (token) {
					return token.type === TOKEN_WHITESPACE ? ' ' : token.value;
				}).join('')
			});
		} else {
			// End this recursion when the string is finished, or when we reach a right parenthesis.
			while (tokens.length > 0 && tokens[0].type !== TOKEN_RPAREN) {
				if (isNextToken(tokens, TOKEN_LPAREN)) {
					// Explicitly nested rule set: recursion.
					rule = tokensToRuleSet(tokens);
					assertNextToken(tokens, TOKEN_RPAREN);
				} else if (tokens[0].type !== TOKEN_RPAREN) {
					// Standard rule, with a field, operator and value.
					rule = {};
					
					negation = false;
					// Look for NOT
					poss_not = peekToken(tokens, 0);
					if(poss_not.value === 'NOT') {
					    assertNextToken(tokens, TOKEN_WORD);
					    assertNextToken(tokens, TOKEN_WHITESPACE);
					    negation = !negation;
					}
					
					rule.field = rule.id = assertNextToken(tokens, TOKEN_WORD).value;
					
					assertNextToken(tokens, TOKEN_COLON);
					negation = isNextToken(tokens, TOKEN_NEGATION) ? !negation : negation;
					
					if (isNextToken(tokens, TOKEN_LBRACKET)) {
						// Between filter value (of the form `[minValue TO maxValue]`)
						min = assertNextToken(tokens, TOKEN_WORD);
						skipWhitespace(tokens);
						assertNextToken(tokens, TOKEN_WORD, 'TO');
						skipWhitespace(tokens);
						max = assertNextToken(tokens, TOKEN_WORD);
						assertNextToken(tokens, TOKEN_RBRACKET);
						assignOperatorAndRange(rule, min.value, max.value, negation);
					} else {
						// Other types can be a (quoted or unquoted) word, with optional wildcard prefix and/or suffix.
						// Alternatively the word itself can be omitted, i.e. just a wildcard (`is_empty`/`is_not_empty`).
						wildcardPrefix = isNextToken(tokens, TOKEN_WILDCARD);
						
						var t1, t2, acc = [];
						skipWhitespace(tokens);
						while((t1 = peekToken(tokens, 0)) && (t1.type === TOKEN_WORD) && (!t1.isCondition) && (((t2 = peekToken(tokens, 1)) && (t2.type !== TOKEN_COLON)) || !t2)) {
						    acc.push(isNextToken(tokens, TOKEN_WORD));
						    skipWhitespace(tokens);
						}
						wildcardSuffix = isNextToken(tokens, TOKEN_WILDCARD);
						
						assignOperatorAndValue(rule, acc.length > 0 ? acc.reduce((a, v) => a + (a ? ' ' : '') + v.value, '') : undefined, negation, wildcardPrefix, wildcardSuffix);
					}
				}
				skipWhitespace(tokens);
				if (rule) {
					ruleSet.rules.push(rule);
					if (tokens.length > 0 && tokens[0].type !== TOKEN_RPAREN) {
						// Process the next condition ("AND" / "OR").
						condition = assertCondition(tokens).value;
						// Assign the first condition to the rule set.
						ruleSet.condition = ruleSet.condition || condition;
						if (condition !== ruleSet.condition) {
							// We have something like "A AND B OR C" in the query.  This is interpreted as "(A AND B) OR C".
							// The "AND" and "OR" conditions are given equal precedence, so the parentheses are always
							// around the left-most set of filters with matching condition.  This is implemented by pushing
							// the existing rule set down a level in the hierarchy, and continuing processing from the new
							// parent.
							ruleSet = {
								condition: condition,
								rules: [ ruleSet ]
							};
						}
					}
				}
				skipWhitespace(tokens);
			}
		}
		return ruleSet;
	};

	/**
	 * Convert a CA query string into a set of rules for the jQuery query builder.  Performs the inverse operation to
	 * `convertQueryBuilderRulesToSearchQuery`.
	 * @param {String} query
	 * @returns {Object|undefined}
	 */
	caUI.convertSearchQueryToQueryBuilderRuleSet = function (query) {
		return query ? tokensToRuleSet(getTokenList(query)) : undefined;
	};
}());
