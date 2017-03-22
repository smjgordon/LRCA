#!/bin/bash
/usr/bin/mysqldump -ustewart_lrca -pw3b5i7e --lock-tables=FALSE stewart_lrca > "/home/stewart/backup/lrca-`date +%Y-%m-%d\ %H:%M:%S`.sql"
exit