// ---------------------------------------------------------------------
// app/lib/core/Parsers/ExpressionParser/Expression.pp : Expression grammar
// ----------------------------------------------------------------------
// CollectiveAccess
// Open-source collections management software
// ----------------------------------------------------------------------
//
// Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
// Copyright 2015 Whirl-i-Gig
//
// For more information visit http://www.CollectiveAccess.org
//
// This program is free software; you may redistribute it and/or modify it under
// the terms of the provided license as published by Whirl-i-Gig
//
// CollectiveAccess is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
//
// This source code is free and modifiable under the terms of
// GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
// the "license.txt" file for details, or visit the CollectiveAccess web site at
// http://www.CollectiveAccess.org
//
// @package CollectiveAccess
// @subpackage Parsers
// @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
//
// ----------------------------------------------------------------------
//

// below are the tokens from the FSM parser, for reference
// define("EEP_TOKEN_OPEN_PAREN", 0);
// define("EEP_TOKEN_CLOSE_PAREN", 1);
// define("EEP_TOKEN_STRING_LITERAL", 2);
// define("EEP_TOKEN_NUMERIC_LITERAL", 3);
// define("EEP_TOKEN_MATH_OP", 4);
// define("EEP_TOKEN_COMPARISON_OP", 5);
// define("EEP_TOKEN_LOGICAL_OP", 6);
// define("EEP_TOKEN_IN_OP", 7);
// define("EEP_TOKEN_NOT_IN_OP", 8);
// define("EEP_TOKEN_REGEX_OP", 9);
// define("EEP_TOKEN_FUNCTION", 10);
// define("EEP_TOKEN_VARIABLE", 11);
// define("EEP_TOKEN_REGEX_PATTERN", 12);


%skip   space     \s

%token  bracket_  \(
%token _bracket   \)
%token  sqbracket_  \[
%token _sqbracket   \]
%token  comma     ,

// Literals
%token  number    (0|[1-9]\d*)(\.\d+)?([eE][\+\-]?\d+)?
%token  string    \"[^"]*\"

// Math
%token  plus      \+
%token  minus     \-|−
%token  times     \*|×
// @todo figure out a way to make / work for both /regexes/ and divisions
%token  div       ÷

// Regular expressions
%token  regex     /.+/
%token  regex_match      \=\~
%token  regex_nomatch    \!\~

// Comparison operators
%token  gte       \>\=
%token  lte       \<\=
%token  eq        \=
%token  neq       \!\=|\<\>
%token  gt        \>
%token  lt        \<

// Boolean
%token  bool_and  AND
%token  bool_or   OR
%token  in_op     IN
%token  notin_op  NOT\ IN

// Variables
%token  variable  \^(ca_[A-Za-z]+[A-Za-z0-9_\-\.]+[A-Za-z0-9]{1}[\&\%]{1}[^ <]+|[0-9]+(?=[.,;])|[A-Za-z0-9_\.:\/]+[%]{1}[^\w\^\t\r\n\"\'<>\(\)\{\}\/]*|[A-Za-z0-9_\.\/]+[:]{1}[A-Za-z0-9_\.\/\[\]\@\'\"=:]+\]|[A-Za-z0-9_\.\/]+[:]{1}[A-Za-z0-9_\.\/\[\]\@\'\"=:]+|[A-Za-z0-9_\.\/]+[~]{1}[A-Za-z0-9]+[:]{1}[A-Za-z0-9_\.\/]+|[A-Za-z0-9_\.\/]+)

%token  id        \w+

expression:
    expr() (::bool_or:: expr() #bool_or )?
  | ( ::bracket_:: expression() ::_bracket:: #group )

expr:
    factor() (::bool_and:: expr() #bool_and )?
  | ( ::bracket_:: expr() ::_bracket:: #group )

factor:
    regex_comparison()
  | comparison()
  | scalar()
  | in_expression()
  | notin_expression()
  | ( ::bracket_:: factor() ::_bracket:: #group )

in_expression:
    scalar() <in_op> list_of_values() #in_op

notin_expression:
    scalar() <notin_op> list_of_values() #notin_op

list_of_values:
    ::sqbracket_:: scalar() ( ::comma:: scalar() )+ ::_sqbracket::

// we break these out by operator to make it easier to access the operator
// in the AST. makes for an ugly grammar but for neat-er AST processing code
// same for comparison() below
regex_comparison:
    regex_match() | regex_nomatch()

regex_match:
    scalar() ::regex_match:: <regex> #regex_match

regex_nomatch:
    scalar() ::regex_nomatch:: <regex> #regex_nomatch

comparison:
    comp_gt() | comp_gte() | comp_lt() | comp_lte() | comp_neq() | comp_eq()

comp_gt:
    scalar() ::gt:: #comp_gt scalar()

comp_gte:
    scalar() ::gte:: #comp_gte scalar()

comp_lt:
    scalar() ::lt:: #comp_lt scalar()

comp_lte:
    scalar() ::lte:: #comp_lte scalar()

comp_neq:
    scalar() ::neq:: #comp_neq scalar()

comp_eq:
    scalar() ::eq:: #comp_eq scalar()

scalar:
    (primary() ( ::plus:: #addition scalar() )?)
  | (<string> ( ::plus:: #stradd scalar())?)
  | <variable>

primary:
    secondary() ( ::minus:: #substraction scalar() )?

secondary:
    ternary() ( ::times:: #multiplication scalar() )?

ternary:
    term() ( ::div:: #division scalar() )?


term:
    ( ::bracket_:: scalar() ::_bracket:: #group )
  | number()
  | ( ::minus:: #negative | ::plus:: ) term()
  | function()

number:
    <number>

#function:
    <id> ::bracket_::
    ( scalar() ( ::comma:: scalar() )* )?
    ::_bracket::
