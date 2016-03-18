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
					return prefix + '"*' + escapeValue(rule.value) + '"';
				case 'contains':
					return prefix + '"*' + escapeValue(rule.value) + '*"';
				case 'ends_with':
					return prefix + '"' + escapeValue(rule.value) + '*"';
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
		var i, fields, values, conditions, rules,
			REGEX_STRIP_PARENTHESES = /^\((.*)\)$/,
			REGEX_GET_FILTER = /^((?:\w+\.)?\w+):(\S+|"(?:[^"]*|\\")*")(.*)$/,
			REGEX_GET_OPERATOR = /^\s+(AND|OR)\s+/;
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
		while (query.match(REGEX_GET_FILTER)) {
			fields.push(query.replace(REGEX_GET_FILTER, '$1'));
			values.push(query.replace(REGEX_GET_FILTER, '$2'));
			query = query.replace(REGEX_GET_FILTER, '$3');
			if (query.match(REGEX_GET_OPERATOR)) {
				// If we don't match, then it must be the next nested group.
				conditions.push(query.replace(REGEX_GET_OPERATOR, '$1'));
				query = query.replace(REGEX_GET_OPERATOR, '$2');
			}
		}
		for (i = 1; i < conditions.length; ++i) {
			if (conditions[i] !== conditions[0]) {
				// TODO Handle different operators, process into separate nested groups.
				return undefined;
			}
		}
		rules = [];
		for (i = 0; i < fields.length; ++i) {
			// TODO Handle different operators
			rules.push({
				field: fields[i],
				value: values[i],
				operator: 'equal'
			});
		}
		// TODO Recursion, handle nested groups
		return {
			operator: conditions[0],
			rules: rules
		};
	}
}());
