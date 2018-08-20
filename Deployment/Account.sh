#!/bin/bash
# Command line script for configuring Account server 
# of Wichat servers during automatic deployment

# Arguments passed through command line:
#   WICHAT_ACC_DIR         Relative or absolute path of Account server

# Arguments passed through environment variables:
#   WICHAT_ACC_URL          URL (host name + directory) of Account server
#   WICHAT_ACC_ID           Account server ID
#   WICHAT_ACC_VER_CLT      List of acceptable client versions
#   WICHAT_ACC_DB_DIR       Directory for database files
#   WICHAT_ACC_SCOMM_QUERY_KEY    Key for communicating with internal servers
#   WICHAT_ACC_SCOMM_WEB_KEY    Key for communicating with Wichat web servers

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
WICHAT_ACC_DIR=$1
if [ -z "$WICHAT_ACC_DIR" ]; then
    exit 1
fi

# Read Root server parameters
if [ -z "$WICHAT_ACC_URL" ]; then
    WICHAT_ACC_URL="'127.0.0.1/Account/'"
fi
if [ -z "$WICHAT_ACC_ID" ]; then
    WICHAT_ACC_ID="1"
fi
if [ -z "$WICHAT_ACC_VER_CLT" ]; then
    WICHAT_ACC_VER_CLT="255"
fi
if [ -z "$WICHAT_ACC_DB_DIR" ]; then
    WICHAT_ACC_DB_DIR="'../db'"
fi
if [ -z "$WICHAT_ACC_SCOMM_QUERY_KEY" ]; then
    WICHAT_ACC_SCOMM_QUERY_KEY="'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'"
fi
if [ -z "$WICHAT_ACC_SCOMM_WEB_KEY" ]; then
    WICHAT_ACC_SCOMM_WEB_KEY="'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'"
fi

# Files to deal with
WICHAT_ACC_CONFIG="$WICHAT_ACC_DIR/common/config.php"
WICHAT_ACC_SCOMM_CONFIG="$WICHAT_ACC_DIR/scomm/config.php"

writePHPConfigDefine "$WICHAT_ACC_CONFIG" "ACCOUNT_SERVER" "$WICHAT_ACC_URL"
writePHPConfigDefine "$WICHAT_ACC_CONFIG" "SERVER_ID" "$WICHAT_ACC_ID"
writePHPConfigDefine "$WICHAT_ACC_CONFIG" "ACCOUNT_DB_DIR" "$WICHAT_ACC_DB_DIR"
writePHPConfigArray "$WICHAT_ACC_CONFIG" "validVersion" "$WICHAT_ACC_VER_CLT"

writePHPConfigDefine "$WICHAT_ACC_SCOMM_CONFIG" "ACCOUNT_SCOMM_QUERY_KEY" "$WICHAT_ACC_SCOMM_QUERY_KEY"
writePHPConfigDefine "$WICHAT_ACC_SCOMM_CONFIG" "ACCOUNT_SCOMM_WEB_KEY" "$WICHAT_ACC_SCOMM_WEB_KEY"