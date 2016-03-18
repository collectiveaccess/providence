/* ----------------------------------------------------------------------
 * js/ca/ca.objectcheckout.js
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
	function escapeValue(value) {
		return value.replace(/([\-\+&\|!\(\)\{}\[\]\^"~\*\?:\\])/, '\\$1');
	}

	/**
	 * Convert a set of rules from the jQuery query builder into a CA search query.  Performs the inverse operation to
	 * `convertSearchQueryToQueryBuilderRules`.
	 * @param {object} rule
	 * @returns {string}
	 */
	caUI.convertQueryBuilderRulesToSearchQuery = function (rule) {
		if (rule.condition && rule.rules) {
			return '(' + $.map(rule.rules, caUI.convertQueryBuilderRulesToSearchQuery).join(' ' + rule.condition + ' ') + ')';
		}
		if (rule.operator && rule.field) {
			// Escape value to allow special characters
			var negation = rule.operator.match(/not_/),
				prefix = rule.field + (negation ? ':-' : ':');
			switch (negation ? rule.operator.replace('not_', '') : rule.operator) {
				case 'equal':
					return prefix + '"' + escapeValue(rule.value) + '"';
				case 'in':
					return prefix + '(' + escapeValue(rule.value) + ')';
				case 'between':
					return prefix + '[' + escapeValue(rule.value[0]) + ' TO ' + escapeValue(rule.value[1]) + ']';
				case 'begins_with':
					return prefix + '"' + escapeValue(rule.value) + '*"';
				case 'contains':
					return prefix + '"*' + escapeValue(rule.value) + '*"';
				case 'ends_with':
					return prefix + '"*' + escapeValue(rule.value) + '"';
				case 'is_empty':
				case 'is_null':
					return prefix + '*';
			}
			return rule.field + ':' + rule.value;
		}
		return '';
	};

	/**
	 * Convert a CA query string into a set of rules for the jQuery query builder.  Performs the inverse operation to
	 * `convertQueryBuilderRulesToSearchQuery`.
	 * @param {string} query
	 * @returns {object|undefined}
	 */
	caUI.convertSearchQueryToQueryBuilderRules = function (query) {
		var i, j, k, fields, values, conditions, rules, operator, negated, mapping, matches,
			REGEX_STRIP_PARENTHESES = /^\((.*)\)$/,
			REGEX_STRIP_QUOTES = /^"(.*)"$/,
			REGEX_GET_FILTER = /^((?:\w+\.)?\w+):(\S+|"(?:[^"]*|\\")*")(.*)$/,
			REGEX_GET_CONDITION = /^\s+(AND|OR)\s+(.*)$/,
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
		// Trim extraneous whitespace.
		query = query.trim();
		// Strip any number of matching pairs of external parentheses.
		while (query.match(REGEX_STRIP_PARENTHESES)) {
			query = query.replace(REGEX_STRIP_PARENTHESES, '$1');
		}
		if (!query.match(REGEX_GET_FILTER)) {
			// Malformed filter specification.
			return undefined;
		}
		fields = [];
		values = [];
		conditions = [];
		while (query.length > 0) {
			if (query.match(REGEX_GET_FILTER)) {
				fields.push(query.replace(REGEX_GET_FILTER, '$1'));
				values.push(query.replace(REGEX_GET_FILTER, '$2'));
				query = query.replace(REGEX_GET_FILTER, '$3');
			} else if (query[0] === '(') {
				// TODO Handle nested groups
			} else {
				return undefined;
			}
			if (query.match(REGEX_GET_CONDITION)) {
				// If we don't match, then it must be the next nested group.
				conditions.push(query.replace(REGEX_GET_CONDITION, '$1'));
				query = query.replace(REGEX_GET_CONDITION, '$2');
			}
			query = query.trim();
		}
		for (i = 1; i < conditions.length; ++i) {
			if (conditions[i] !== conditions[0]) {
				// TODO Handle different operators, process into separate nested groups.
				return undefined;
			}
		}
		rules = [];
		for (i = 0; i < fields.length; ++i) {
			negated = values[i][0] === '-';
			values[i] = negated ? values[i].substring(1) : values[i];
			values[i] = values[i].replace(REGEX_STRIP_QUOTES, '$1');
			for (j = 0; j < REGEX_OPERATOR_MAP.length; ++j) {
				mapping = REGEX_OPERATOR_MAP[j];
				matches = mapping.regex.exec(values[i]);
				if (matches) {
					operator = negated ? mapping.negatedOperator : mapping.operator;
					if (matches.length < 2) {
						values[i] = undefined;
					} else if (matches.length === 2) {
						values[i] = matches[1];
					} else {
						values[i] = [];
						for (k = 1; k < matches.length; ++k) {
							values[i].push(matches[k]);
						}
					}
				}
			}
			if (Object.prototype.toString.call(values[i]) === '[object Array]') {
				for (k = 0; k < values[i].length; ++k) {
					values[i][k] = values[i][k].replace(REGEX_STRIP_QUOTES, '$1');
				}
			}
			rules.push({
				id: fields[i],
				field: fields[i],
				value: values[i],
				operator: operator
			});
		}
		return {
			operator: conditions[0],
			rules: rules
		};
	}
}());
