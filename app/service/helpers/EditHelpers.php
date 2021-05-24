<?php

namespace GraphQLServices\Helpers;


function warning($bundle, $message) {
	return [
		'message' => $message,
		'bundle' => $bundle
	];
}