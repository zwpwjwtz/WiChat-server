#!/bin/bash
# Command line script for configuring Root server 
# of Wichat servers during automatic deployment

# Arguments passed through command line:
#   WICHAT_ROOT_DIR        Relative or absolute path of Root server

# Arguments passed through environment variables:
#   WICHAT_ROOT_URL         URL (host name + directory) of Root server
#   WICHAT_ROOT_ID          Root server ID
#   WICHAT_ROOT_VER_CLT     List of acceptable client versions
#   WICHAT_ROOT_DNS_ACC     DNS records for Wichat Account servers
#   WICHAT_ROOT_DNS_REC     DNS records for Wichat Record servers

# Function for writing value to a specified field in a
# "define" statement in a configure file
function writePHPConfigDefine
{
    fileName=$1
    fieldName=$2
    value=$3
    sed -i "s#^define('$fieldName'.*#define('$fieldName',$value);#g" "$fileName"
}
function writePHPConfigArray
{
    fileName=$1
    fieldName=$2
    values=$3
    
    array=""
    for value in $values; do
        array="$array$value,"
    done
    array=${array%,}
    array="array($array)";
    sed -i "s#^\$$fieldName[\w]*=.*#\$$fieldName=$array;#g" "$fileName"
}

# Read Root server path from command argument
WICHAT_ROOT_DIR=$1
if [ -z "$WICHAT_ROOT_DIR" ]; then
    exit 1
fi

# Read Root server parameters
if [ -z "$WICHAT_ROOT_URL" ]; then
    WICHAT_ROOT_URL="'127.0.0.1/Root/'"
fi
if [ -z "$WICHAT_ROOT_ID" ]; then
    WICHAT_ROOT_ID="1"
fi
if [ -z "$WICHAT_ROOT_VER_CLT" ]; then
    WICHAT_ROOT_VER_CLT="255"
fi
if [ -z "$WICHAT_ROOT_DNS_ACC" ]; then
    WICHAT_ROOT_DNS_ACC="'127.0.0.1'"
fi
if [ -z "$WICHAT_ROOT_DNS_REC" ]; then
    WICHAT_ROOT_DNS_REC="'127.0.0.1'"
fi

# Files to deal with
WICHAT_ROOT_CONFIG="$WICHAT_ROOT_DIR/common/config.php"
WICHAT_ROOT_QUERY_CONFIG="$WICHAT_ROOT_DIR/query/config.php"

writePHPConfigDefine "$WICHAT_ROOT_CONFIG" "ROOT_SERVER" "$WICHAT_ROOT_URL"
writePHPConfigDefine "$WICHAT_ROOT_CONFIG" "SERVER_ID" "$WICHAT_ROOT_ID"
writePHPConfigArray "$WICHAT_ROOT_CONFIG" "validVersion" "$WICHAT_ROOT_VER_CLT"

writePHPConfigArray "$WICHAT_ROOT_QUERY_CONFIG" "AccServer" "$WICHAT_ROOT_DNS_ACC"
writePHPConfigArray "$WICHAT_ROOT_QUERY_CONFIG" "RecServer" "$WICHAT_ROOT_DNS_REC"