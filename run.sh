#!/bin/bash -
IFS=$'\n'$'\t'' '

CASE_DIR=''
CASE_FILE=''

CONCURR_FLAG='false'

while [ $# -gt 0 ]
do
	case "X$1" in
		"X"-d)
			CASE_DIR=$2
			shift 1
			;;
		"X"-c)
			CONCURR_FLAG='true'
			;;
		"X"-*)
			echo 'error: unrecognized options.'
			exit 1
			;;
		"X"*)
			CASE_FILE=${CASE_FILE}' '$1
			;;
	esac
	shift 1
done

if [ '' !=  "$CASE_DIR" ] && [ -d "$CASE_DIR" ]
then
	CASE_FILE=$(find $CASE_DIR -name '*.case.php')
fi

if [ 'false' == "${CONCURR_FLAG}"  ]
then
	sh bin/run.sh ${CASE_FILE}
elif [ 'true' == "${CONCURR_FLAG}" ]
then
	sh bin/run.sh -c ${CASE_FILE}
fi
