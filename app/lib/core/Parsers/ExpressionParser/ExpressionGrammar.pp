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
%token  comma     ,

// Literals
%token  number    (0|[1-9]\d*)(\.\d+)?([eE][\+\-]?\d+)?
%token  string    \"[^"]+\"

// Math
%token  plus      \+
%token  minus     \-|−
%token  times     \*|×
// @todo figure out a way to make / work for both /regexes/ and divisions
%token  div       ÷

// Regular expressions
%token  regex     /.+/
%token  regex_op  (\=\~|\!\~)

// Comparison operators
%token  comp      (\>|\<|\>\=|\<\=|\<\>|\!\=)|\=

// Boolean
%token  bool_op   AND|OR
%token  in_op     IN
%token  notin_op  NOT\ IN

// Variables
%token  variable  \^([A-Za-z0-9\._\/])+

%token  id        \w+

expression:
    scalar()
  | boolean_expression()
  | boolean_expression() (<bool_op> boolean_expression() #bool_op)+
  | in_expression() | notin_expression()
  | ( ::bracket_:: expression() ::_bracket:: #group )

boolean_expression:
    regex_comparison() | comparison()
  | ( ::bracket_:: boolean_expression() ::_bracket:: #group )

in_expression:
    scalar() <in_op> list_of_values() #in_op

notin_expression:
    scalar() <notin_op> list_of_values() #notin_op

list_of_values:
	::bracket_:: ( scalar() ( ::comma:: scalar() )* )? ::_bracket::

regex_comparison:
    scalar() <regex_op> #regex <regex>

comparison:
    scalar() <comp> #comparison scalar()

scalar:
    (primary() ( ::plus:: #addition scalar() )?)
  | <string> ( ::plus:: #stradd <string>)*
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
