#!/bin/bash

if [ "$#" -ne 1 ]; then
	echo ERROR: Must supply a release name!
	exit;
fi

DOJOSRCDIR=/var/www/js/dojo-1.6.1-src
RELEASEVERSION=1.6.1-rpc
RELEASEDIR=/var/www/js/dojo-custom
PROFILEFILE=/home/michael/www/rpc/build/researchcalc.profile.js
#PROFILEFILE=$RELEASEDIR/profiles/researchcalc.profile.js
RELEASENAME=$1

cd $DOJOSRCDIR/util/buildscripts
$DOJOSRCDIR/util/buildscripts/build.sh \
	releaseName=$RELEASENAME \
	releaseDir=$RELEASEDIR \
	profileFile=$PROFILEFILE \
	version=$RELEASEVERSION \
	action=release \
	cssOptimize=comments

echo Removing uncessary files...

# The custom build retains lots of individual dojo
# Necessary files get copied into a new temporary directory, and then the original is deleted
# and replaced.
mkdir -p $RELEASEDIR/$RELEASENAME-tmp/{dojo,dijit}
mkdir -p $RELEASEDIR/$RELEASENAME-tmp/dojo/nls
mkdir -p $RELEASEDIR/$RELEASENAME-tmp/dijit/themes/tundra
cp -r $RELEASEDIR/$RELEASENAME/dijit/themes/*.css $RELEASEDIR/$RELEASENAME-tmp/dijit/themes
cp -r $RELEASEDIR/$RELEASENAME/dijit/themes/tundra/tundra.css $RELEASEDIR/$RELEASENAME-tmp/dijit/themes/tundra
cp -r $RELEASEDIR/$RELEASENAME/dijit/themes/tundra/images $RELEASEDIR/$RELEASENAME-tmp/dijit/themes/tundra
cp -r $RELEASEDIR/$RELEASENAME/dojo/dojo*.js $RELEASEDIR/$RELEASENAME-tmp/dojo
cp -r $RELEASEDIR/$RELEASENAME/dojo/resources $RELEASEDIR/$RELEASENAME-tmp/dojo
cp -r $RELEASEDIR/$RELEASENAME/dojo/rpc-*.js $RELEASEDIR/$RELEASENAME-tmp/dojo
cp -r $RELEASEDIR/$RELEASENAME/dojo/nls/rpc-*.js $RELEASEDIR/$RELEASENAME-tmp/dojo/nls
cp -r $RELEASEDIR/$RELEASENAME/dojo/LICENSE $RELEASEDIR/$RELEASENAME-tmp/dojo
cp -r $RELEASEDIR/$RELEASENAME/dijit/LICENSE $RELEASEDIR/$RELEASENAME-tmp/dijit

rm -Rf $RELEASEDIR/$RELEASENAME
mv $RELEASEDIR/$RELEASENAME-tmp $RELEASEDIR/$RELEASENAME
echo Done.
