/* ----------------------------------------------------------------------
 * js/ca/ca.querytranslator.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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

(function () {
	var escapeValue, shiftToken, parseRuleSet, assertNextToken, assertCondition, skipWhitespace, assignOperatorAndValue,
		TOKEN_WORD = 'WORD',
		TOKEN_LPAREN = 'LPAREN',
		TOKEN_RPAREN = 'RPAREN',
		TOKEN_COLON = 'COLON',
		TOKEN_NEGATION = "NEGATION",
		TOKEN_WHITESPACE = 'WHITESPACE',
		REGEX_OPERATOR_MAP = [
			{
				regex: /^(.*)$/,
				operator: 'equal',
				negatedOperator: 'not_equal'
			},
			{
				regex: /^\((.*)\)$/,
				operator: 'in',
				negatedOperator: 'not_in'
			},
			{
				regex: /^\[\s*(.*)\s+TO\s+(.*)\s*]$/,
				operator: 'between',
				negatedOperator: 'not_between'
			},
			{
				regex: /^([^\*].*[^\\])\*$/,
				operator: 'begins_with',
				negatedOperator: 'not_begins_with'
			},
			{
				regex: /^\*(.*[^\\])\*$/,
				operator: 'contains',
				negatedOperator: 'not_contains'
			},
			{
				regex: /^\*(.*(?:\\\*|[^\*]))$/,
				operator: 'ends_with',
				negatedOperator: 'not_ends_with'
			},
			{
				regex: /^\*$/,
				operator: 'is_empty',
				negatedOperator: 'is_not_empty'
			}
		];

	/**
	 * Escape the user-entered field value.
	 * @param {String} value
	 * @returns {String}
     */
	escapeValue = function (value) {
		return value.replace(/([\-\+&\|!\(\)\{}\[\]\^"~\*\?:\\])/, '\\$1');
	};

	/**
	 * Convert a set of rules from the jQuery query builder into a CA search query.  Performs the inverse operation to
	 * `convertSearchQueryToQueryBuilderRules`.
	 * @param {Object} ruleSet
	 * @returns {String}
	 */
	caUI.convertQueryBuilderRuleSetToSearchQuery = function (ruleSet) {
		if (ruleSet.condition && ruleSet.rules) {
			return '(' + $.map(ruleSet.rules, caUI.convertQueryBuilderRuleSetToSearchQuery).join(' ' + ruleSet.condition + ' ') + ')';
		}
		if (ruleSet.operator && ruleSet.field) {
			// Escape value to allow special characters
			var negation = ruleSet.operator.match(/not_/),
				prefix = ruleSet.field + (negation ? ':-' : ':');
			switch (negation ? ruleSet.operator.replace('not_', '') : ruleSet.operator) {
				case 'equal':
					return prefix + '"' + escapeValue(ruleSet.value) + '"';
				case 'in':
					return prefix + '(' + escapeValue(ruleSet.value) + ')';
				case 'between':
					return prefix + '[' + escapeValue(ruleSet.value[0]) + ' TO ' + escapeValue(ruleSet.value[1]) + ']';
				case 'begins_with':
					return prefix + '"' + escapeValue(ruleSet.value) + '*"';
				case 'contains':
					return prefix + '"*' + escapeValue(ruleSet.value) + '*"';
				case 'ends_with':
					return prefix + '"*' + escapeValue(ruleSet.value) + '"';
				case 'is_empty':
				case 'is_null':
					return prefix + '*';
			}
			return ruleSet.field + ':' + ruleSet.value;
		}
		return '';
	};

	/**
	 * Retrieve the next token from the `queryArray`, which is destructively processed.
	 * @param {Array} queryArray
	 * @returns {Object|undefined}
	 */
	shiftToken = function (queryArray) {
		var character, token,
			quoted = false,
			escaped = false,
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
			case ':':
				token = { type: TOKEN_COLON };
				end = true;
				break;
			case '-':
				token = { type: TOKEN_NEGATION };
				end = true;
				break;
			// Collapse whitespace.
			case ' ':
				while (queryArray[0] === ' ') {
					queryArray.shift();
				}
				token = { type: TOKEN_WHITESPACE };
				end = true;
				break;
			// Beginning of a quoted phrase, which ends after the next unescaped quote.
			case '"':
				token = { type: TOKEN_WORD, value: '' };
				quoted = true;
				break;
			// Beginning of a plain word, which ends before then next non-word character.
			default:
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
				// In plain word mode, the next non-word, non-. character ends the token.
				if (/[\w\.]/.test(character)) {
					token.value += character;
					queryArray.shift();
				} else {
					end = true;
				}
			}
		}

		return token;
	};

	/**
	 * Check that the next token is of the given type, throw an error if it is a different type.  Destructively
	 * processes `tokens` and returns the retrieved token, which always has the given `type`.
	 * @param {Array} tokens
	 * @param {String} type
	 * @returns {Object}
	 * @throws
     */
	assertNextToken = function (tokens, type) {
		var token;
		if (tokens.length === 0) {
			throw 'Unexpected end of token stream, expected "' + type + '".';
		}
		token = tokens.shift();
		if (token.type !== type) {
			throw 'Unexpected token type "' + token.type + '"' + (token.value ? ' (value: "' + token.value + '"' : '') + ', expected "' + type + '".';
		}
		return token;
	};

	/**
	 * Check that the next token is a valid condition, i.e. a word token with value "AND" or "OR".  Throw an error if
	 * the token type or value is incorrect.  Destructively processes the `tokens` list.
	 * @param {Array} tokens
	 * @returns {Object}
	 * @throws
     */
	assertCondition = function (tokens) {
		var token = assertNextToken(tokens, TOKEN_WORD);
		if (token.value !== 'AND' && token.value !== 'OR') {
			throw 'Unknown condition "' + token.value + '" expecting "AND" or "OR".';
		}
		return token;
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
	 * Use the given `queryValue` to assign a `value` and `condition` to the given `rule`.
	 * @param {Object} rule
	 * @param {String} queryValue
     */
	assignOperatorAndValue = function (rule, queryValue) {
		var i, j, mapping, matches, negated;
		negated = queryValue[0] === '-';
		queryValue = negated ? queryValue.substring(1) : queryValue;
		queryValue = queryValue.replace(/^"(.*)"$/, '$1');
		for (i = 0; i < REGEX_OPERATOR_MAP.length; ++i) {
			mapping = REGEX_OPERATOR_MAP[i];
			matches = mapping.regex.exec(queryValue);
			if (matches) {
				rule.operator = negated ? mapping.negatedOperator : mapping.operator;
				if (matches.length < 2) {
					rule.value = undefined;
				} else if (matches.length === 2) {
					rule.value = matches[1];
				} else {
					rule.value = [];
					for (j = 1; j < matches.length; ++j) {
						rule.value.push(matches[j]);
					}
				}
			}
		}
	};

	/**
	 * Parse the given array of tokens into a tree structure for the query builder.
	 * @param {Array} tokens
	 * @return {Object}
     */
	parseRuleSet = function (tokens) {
		var rule,
			ruleSet = {
				condition: undefined,
				rules: []
			};
		skipWhitespace(tokens);
		while (tokens.length > 0 && tokens[0].type !== TOKEN_RPAREN) {
			if (tokens[0].type === TOKEN_LPAREN) {
				assertNextToken(tokens, TOKEN_LPAREN);
				ruleSet.rules.push(parseRuleSet(tokens));
				assertNextToken(tokens, TOKEN_RPAREN);
			} else if (tokens[0].type !== TOKEN_RPAREN) {
				rule = {};
				rule.field = rule.id = assertNextToken(tokens, TOKEN_WORD).value;
				assertNextToken(tokens, TOKEN_COLON);
				assignOperatorAndValue(rule, assertNextToken(tokens, TOKEN_WORD).value);
				ruleSet.rules.push(rule);
			}
			skipWhitespace(tokens);
			// TODO Handle heterogenous conditions without parentheses.
			if (tokens.length > 0 && tokens[0].type === TOKEN_WORD) {
				ruleSet.condition = assertCondition(tokens).value;
			}
			skipWhitespace(tokens);
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
		if (!query) {
			return undefined;
		}
		var token,
			tokens = [],
			queryArray = query.trim().split('');
		while (token = shiftToken(queryArray)) {
			tokens.push(token);
		}
		while (tokens.length >= 2 && tokens[0].type === TOKEN_LPAREN && tokens[tokens.length - 1].type === TOKEN_RPAREN) {
			tokens = tokens.slice(1, tokens.length - 1);
		}
		return parseRuleSet(tokens);
	};
}());
