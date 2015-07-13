import sys
from binascii import unhexlify

if __name__ == "__main__":
	data = sys.stdin.readlines()
	for line in data:
		res = ""
		for byte in line.split(" "):
			res = res + str((chr(int(byte, 16)).encode('utf-8')))
			# print(unicode(s))
		res = res.replace("'\\x", "")
		res = res.replace("'", "")
		res = res.replace("b00b", "")
		print(res)