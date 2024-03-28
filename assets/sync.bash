#!/bin/bash

# Local and remote paths
workspace="/mnt/c/Users/Lenovo/workspace/flutter"
# ftp://username:password@hostname:port
remote="ftp://contactus:IsAja=Dog@testbed.com.ng:21/wp-content/plugins/woo-flutter"
local_path1="$workspace/inc/classes" 
remote_path1="$remote/inc/classes"

local_path2="$workspace/assets/build"
remote_path2="$remote/assets/build" 

local_path3="$workspace/templates"
remote_path3="$remote/templates"

# ftp-upload -h {HOST} -u {USERNAME} --password {PASSWORD} -d {SERVER_DIRECTORY} {FILE_TO_UPLOAD}
ftp-upload -h {"testbed.com.ng"} -u {"contactus@testbed.com.ng"} --password {"IsAja=Dog"} -d {"$workspace"} {"$workspace/inc/classes/class-i18n.php"}


# Monitor local paths for modifications
while inotifywait -e modify "$local_path1" "$local_path2" "$local_path3"; do

  # Sync modified files to remote paths
  rsync -avz --progress "$local_path1" "$remote_path1"
  rsync -avz --progress "$local_path2" "$remote_path2"
  rsync -avz --progress "$local_path3" "$remote_path3"

done
