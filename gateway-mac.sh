#!/bin/bash

ARP="/usr/sbin/arp"
NETSTAT="/bin/netstat"
GREP="/bin/grep"
SED="/bin/sed"
CUT="/usr/bin/cut"
TAIL="/usr/bin/tail"

# Get the default gateway's IP address.
GATEWAY=`$NETSTAT -rn | $GREP "^0.0.0.0" | $CUT -c17-31`

# Get the MAC address of the default gateway.
MACADDRESS=`$ARP -n $GATEWAY | $TAIL -n1 | $CUT -c34-50`

# Convert letters in the MAC address to lower case.
MACADDRESS=`echo $MACADDRESS | \
$SED 'y/ABCDEFGHIJKLMNOPQRSTUVWXYZ/abcdefghijklmnopqrstuvwxyz/'`

echo "$MACADDRESS"
