<?php

require_once('./setup.php');
require_once(__CA_MODELS_DIR__.'/ca_data_exporters.php');

print ca_data_exporters::exportRecord('testExport',550,array());