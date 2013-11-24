#!/bin/bash

# Checkout RPC from trunk or elsewhere to ./rpc
if [ ! -d ./rpc ]; then
	echo "ERROR: Checkout/export a copy of the RPC from subversion to ./rpc!"
	exit
fi

RPCVERSION=$(php -q ./rpc-getversion.php)
RPCRELEASEBASE=research-proj-calc
RPCRELEASENAME=$RPCRELEASEBASE-$RPCVERSION

# The packaged version should have a clean smarty cache...
rm -Rf ./rpc/rpc/tmpl/compile/*

#Rename src tree to release name/version
find ./rpc -type d -iname "*.svn*" |xargs rm -Rf
mv ./rpc $RPCRELEASENAME
echo Building Research Project Calculator version $RPCVERSION...
echo Building $RPCRELEASENAME.tar.gz...
tar --exclude=minitex --exclude=.svn -czf /tmp/$RPCRELEASENAME.tar.gz $RPCRELEASENAME
echo Building $RPCRELEASENAME.zip...
zip -qr /tmp/$RPCRELEASENAME.zip $RPCRELEASENAME -x \*/skins/minitex\*

mkdir -p ./release/$RPCVERSION
mv /tmp/$RPCRELEASENAME.tar.gz ./release/$RPCVERSION
mv /tmp/$RPCRELEASENAME.zip ./release/$RPCVERSION

echo Removing subversion checkout...
rm -Rf ./$RPCRELEASENAME
echo Done.
