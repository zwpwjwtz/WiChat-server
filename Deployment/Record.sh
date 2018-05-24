#!/bin/bash
# Command line script for configuring Record server 
# of Wichat servers during automatic deployment

# Arguments passed through command line:
#   WICHAT_REC_DIR         Relative or absolute path of Record server

# Arguments passed through environment variables:
#   WICHAT_ACC_URL          URL (host name + directory) of Account server
#   WICHAT_REC_URL          URL (host name + directory) of Record server
#   WICHAT_REC_ID           Record server ID
#   WICHAT_REC_VER_CLT      List of acceptable client versions
#   WICHAT_REC_DB_DIR       Directory for database files
#   WICHAT_REC_SCOMM_ACC_KEY    Key for communicating with Account servers

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
WICHAT_REC_DIR=$1
if [ -z "$WICHAT_REC_DIR" ]; then
    exit 1
fi

# Read Root server parameters
if [ -z "$WICHAT_ACC_URL" ]; then
    WICHAT_ACC_URL="'127.0.0.1/Account/'"
fi
if [ -z "$WICHAT_REC_URL" ]; then
    WICHAT_REC_URL="'127.0.0.1/Record/'"
fi
if [ -z "$WICHAT_REC_ID" ]; then
    WICHAT_REC_ID="1"
fi
if [ -z "$WICHAT_REC_VER_CLT" ]; then
    WICHAT_REC_VER_CLT="255"
fi
if [ -z "$WICHAT_REC_DB_DIR" ]; then
    WICHAT_REC_DB_DIR="'../db'"
fi
if [ -z "$WICHAT_REC_SCOMM_ACC_KEY" ]; then
    WICHAT_REC_SCOMM_ACC_KEY="'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'"
fi

# Files to deal with
WICHAT_REC_CONFIG="$WICHAT_REC_DIR/common/config.php"
WICHAT_REC_SCOMM_CONFIG="$WICHAT_REC_DIR/scomm/config.php"

writePHPConfigDefine "$WICHAT_REC_CONFIG" "RECORD_SERVER" "$WICHAT_REC_URL"
writePHPConfigDefine "$WICHAT_REC_CONFIG" "SERVER_ID" "$WICHAT_REC_ID"
writePHPConfigDefine "$WICHAT_REC_CONFIG" "RECORD_DB_DIR" "$WICHAT_REC_DB_DIR"
writePHPConfigArray "$WICHAT_REC_CONFIG" "validVersion" "$WICHAT_REC_VER_CLT"

writePHPConfigDefine "$WICHAT_REC_SCOMM_CONFIG" "ACCOUNT_SCOMM_KEY" "$WICHAT_REC_SCOMM_ACC_KEY"