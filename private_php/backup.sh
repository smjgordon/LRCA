#!/bin/bash
PATH="/home/stewart/backup/lrca-`date +%Y-%m-%d\ %H:%M:%S`.sql"
/usr/bin/mysqldump -ustewart_lrca -pw3b5i7e --lock-tables=FALSE stewart_lrca > "$PATH"
/bin/gzip "$PATH"
exit