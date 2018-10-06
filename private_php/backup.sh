#!/bin/bash
PATH="/home/leicest3/backup/lrca-`date +%Y-%m-%d\ %H:%M:%S`.sql"
/usr/bin/mysqldump -uleicest3_website -pW3b5i7e --lock-tables=FALSE leicest3_lrca > "$PATH"
/bin/gzip "$PATH"
exit