<?php
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	require_once("./setup.php");
	require_once(__CA_LIB_DIR__."/core/Parsers/jsmin-1.1.1.php");
	require_once(__CA_LIB_DIR__."/core/Configuration.php");
	
	$va_dir = caGetDirectoryContentsAsList(__CA_BASE_DIR__.'/js');
	
	$o_js = Configuration::load(__CA_CONF_DIR__.'/javascript.conf');
	$va_load_sets = $o_js->getAssoc('loadSets');
	$va_defaults = $va_load_sets['_default'];
	
	$va_packages = $o_js->getAssoc('packages');
	$va_defaults = $va_load_sets['_default'];
	
	$js = '';
	foreach($va_defaults as $vs_default) {
		$va_tmp = explode('/', $vs_default);
		
		$va_file_list = $va_packages[$va_tmp[0]];
		$vs_file_name = $va_file_list[$va_tmp[1]];
		
		if (!preg_match('!\.js$!', $vs_file_name)) { continue; }
		$js .= file_get_contents(__CA_BASE_DIR__.'/js/'.$va_tmp[0].'/'.$vs_file_name);
	}
	
	file_put_contents(JSMin::minify($js));
?>