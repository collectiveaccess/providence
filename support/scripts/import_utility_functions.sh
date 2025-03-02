# Import script utility functions
# CollectiveAccess (c) 2020 Whirl-i-Gig

# -----------------------------------------------------------------
# Display an error message and die
#
# Arguments:
#   $1 = Message
#   $2 = Exit status (optional)
# -----------------------------------------------------------------
function die() 
{
    local m="$1"	# message
    echo "[$2] $m" 
    exit $2
}

# -----------------------------------------------------------------
# Run an import
#
# Arguments:
#   $1 = Path to caUtils
#   $2 = Import format (Ex. csv, xlsx)
#	$3 = Mapping code
#	$4 = Path to data
#	$5 = Options (Ex. --log-level=ERR --log=log --direct --no-search-indexing) [Optional]
#   $6 = Display message [Optional]
#	$7 = Show commands [Optional; default is false]
# -----------------------------------------------------------------
function import() 
{
	log_event $IMPORT_LOG "Start import with data $4 using mapping $3 with format $2"
	if [ ! -z "$6" ]; then
		echo "➔ $6"
	fi
	
	if [ ! -z "$7" ]; then
		# display command 
		echo "	↳ $1 import-data --quiet --format=$2 --mapping=$3 --source=$4 $5"
	fi
	$1 import-data --quiet --format="$2" --mapping="$3" --source="$4" $5
	
	local ret=$?
	if [ $ret -ne 0 ] ; then
		log_event $IMPORT_LOG "Import with data $4 using mapping $3 with format $2 failed"
		die "Could not load data" $ret
	fi
	
	log_event $IMPORT_LOG "End import with data $4 using mapping $3 with format $2"
	return 0
}

# -----------------------------------------------------------------
# Install a profile
#
# Arguments:
#   $1 = Path to caUtils
#   $2 = Profile name
#	$3 = Administrator email
#	$4 = Options (Ex. --log-level=ERR --log=log --direct --no-search-indexing) [Optional]
#   $5 = Display message [Optional]
#	$6 = Show commands [Optional; default is false]
# -----------------------------------------------------------------
function install_profile() 
{
	log_event $IMPORT_LOG "Start load profile for $2"
	if [ ! -z "$5" ]; then
		echo "➔ $5"
	fi
	
	if [ -z "$4" ]; then
		OPTS="--overwrite"
	else
		OPTS=$4
	fi 
	
	if [ ! -z "$6" ]; then
		# display command 
		echo "	↳ $1 install --profile-name=$2 --admin-email=$3 $OPTS"
	fi
	
	$1 install --profile-name=$2 --admin-email=$3 $OPTS
	
	local ret=$?
	if [ $ret -ne 0 ] ; then
		log_event $IMPORT_LOG "Load profile for $2 failed"
		die "Could not install profile" $ret
	fi
	log_event $IMPORT_LOG "Completed load profile for $2"
	return 0
}

# -----------------------------------------------------------------
# Update a profile
#
# Arguments:
#   $1 = Path to caUtils
#   $2 = Profile name
#	$4 = Options (Ex. --skip-roles=true --profile-directory=/path/to/profiles --debug=true) [Optional]
#   $5 = Display message [Optional]
#	$6 = Show commands [Optional; default is false]
# -----------------------------------------------------------------
function update_profile() 
{
	log_event $IMPORT_LOG "Start load profile for $2"
	if [ ! -z "$4" ]; then
		echo "➔ $4"
	fi
	
	if [ -z "$3" ]; then
		OPTS=""
	else
		OPTS=$3
	fi 
	
	if [ ! -z "$5" ]; then
		# display command 
		echo "	↳ $1 update-installation-profile --profile-name=$2 $OPTS"
	fi
	
	$1 update-installation-profile --profile-name=$2 $OPTS
	
	local ret=$?
	if [ $ret -ne 0 ] ; then
		log_event $IMPORT_LOG "Update profile for $2 failed"
		die "Could not update profile" $ret
	fi
	log_event $IMPORT_LOG "Completed update profile for $2"
	return 0
}


# -----------------------------------------------------------------
# Check data files
#
# Arguments:
#   $1 = Path to data
#   $2 = List of data files
# -----------------------------------------------------------------
function check_data() 
{
	local IFS_in=$IFS
	IFS=','
	
	local path=$1
	shift
	echo "Checking that all data files are present..."

	for d in $@
	do
		local dx
		
		# Commas must be encoded as "&comma;" as there's no way to directly include commas in a shell script list of strings
		dx=${d/'&comma;'/','}
		if [ ! -f "${path}/${dx}" ]; then
			local msg="Data file $dx is not in $path. Cannot run import if data is missing. Check the contents of the $path directory and try again."
			
			log_event $IMPORT_LOG "$msg"
			echo "$msg"
			exit 101
		fi
	done
	
	IFS=$IFS_in
	return 0
}

# -----------------------------------------------------------------
# Check mapping files
#
# Arguments:
#   $1 = Path to mappings
#   $2 = List of mapping files
# -----------------------------------------------------------------
function check_mappings() 
{
	local IFS_in=$IFS
	IFS=','
	
	local path=$1
	shift
	echo "Checking that all mapping files are present..."

	for m in $@
	do
		if [ ! -f "$path/$m" ]; then
			local msg="Mapping $m is not in $path. Cannot run import if mappings are missing. Check the contents of the $path directory and try again."
			
			log_event $IMPORT_LOG "$msg"
			echo "$msg"
			exit 100
		fi
	done
	
	IFS=$IFS_in
	return 0
}

# -----------------------------------------------------------------
# Load import mappings
#
# Arguments:
#   $1 = Path to caUtils
#   $2 = Path to mappings
#	$3 = Show commands [Optional; default is false]
#   $4 = List of mapping files
# -----------------------------------------------------------------
function load_import_mappings() 
{
	log_event $IMPORT_LOG "Start load import mappings"
	echo "Loading mappings..."
	local cautils_path="$1"
	local path="$2"
	local show_command="$3"
	shift
	shift
	shift
	
	local IFS_in=$IFS
	IFS=','
	for m in $@
	do
		if [ ! -z "$show_command" ]; then
			# display command 
			echo "	↳ $cautils_path load-import-mapping --quiet --file=$path/$m"
		fi
	
		$cautils_path load-import-mapping --quiet --file="$path/$m"
		local ret=$?
		if [ $ret -ne 0 ] ; then
			log_event $IMPORT_LOG "Load import mappings failed on $m"
			die "Could not install import mapping $m" $ret
		fi
	done
	
	IFS=$IFS_in
	log_event $IMPORT_LOG "Completed load import mappings"
	return 0
}

# -----------------------------------------------------------------
# Use load_mappings() as synonym for load_import_mappings() for
# compatibility with older scripts
# -----------------------------------------------------------------
function load_mappings()
{
	load_import_mappings $@
	return 0
}

# -----------------------------------------------------------------
# Load export mappings
#
# Arguments:
#   $1 = Path to caUtils
#   $2 = Path to mappings
#	$3 = Show commands [Optional; default is false]
#   $4 = List of mapping files
# -----------------------------------------------------------------
function load_export_mappings() 
{
	log_event $IMPORT_LOG "Start load export mappings"
	echo "Loading export mappings..."
	local cautils_path="$1"
	local path="$2"
	local show_command="$3"
	shift
	shift
	shift
	
	local IFS_in=$IFS
	IFS=','
	for m in $@
	do
		if [ ! -z "$show_command" ]; then
			# display command 
			echo "	↳ $cautils_path load-export-mapping --quiet --file=$path/$m"
		fi
	
		$cautils_path load-export-mapping --quiet --file="$path/$m"
		local ret=$?
		if [ $ret -ne 0 ] ; then
			log_event $IMPORT_LOG "Load export mappings failed on $m"
			die "Could not install export mapping $m" $ret
		fi
	done
	
	IFS=$IFS_in
	log_event $IMPORT_LOG "Completed load export mappings"
	return 0
}

# -----------------------------------------------------------------
# Run script
#
# Arguments:
#   $1 = Path to script
#	$2 = Script options
#   $3 = Display message [Optional]
#	$4 = Show commands [Optional; default is false]
# -----------------------------------------------------------------
function run_script() 
{

	log_event $IMPORT_LOG "Run script $1 with options $2"
	if [ ! -z "$4" ]; then
		echo "➔ $4"
	fi
	
	if [ ! -z "$4" ]; then
		# display command 
		echo "	↳ $1 $2"
	fi
	$1 "$2"
	
	local ret=$?
	if [ $ret -ne 0 ] ; then
		log_event $IMPORT_LOG "Run script $1 with options $2 failed"
		die "Could not run script" $ret
	fi
	log_event $IMPORT_LOG "Finished running script $1 with options $2"
	return 0
}

# -----------------------------------------------------------------
# Write event to log
#
# Arguments:
#   $1 = Path to log
#	$2 = Message
# -----------------------------------------------------------------
function log_event() 
{
	local date=`date -u +"%Y-%m-%dT%H:%M:%SZ"`
	echo "[$date] $2" >> $1
	
	return 0
}
